<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClienteLeadController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $clients = Cliente::where('user_id', $user->id)->orderBy('nome')->get();
        $tags = Tag::where('user_id', $user->id)->orderBy('name')->get();

        $clientFilter = array_values(array_filter((array) $request->input('cliente_id', []), fn ($value) => $value !== '' && $value !== null));
        $tagFilter = array_values(array_filter((array) $request->input('tags', []), fn ($value) => $value !== '' && $value !== null));
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');

        $query = ClienteLead::with(['cliente', 'assistantLeads.assistant', 'tags'])
            ->whereHas('cliente', fn ($q) => $q->where('user_id', $user->id));

        if (!empty($clientFilter)) {
            $query->whereIn('cliente_id', $clientFilter);
        }

        if (!empty($tagFilter)) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('id', $tagFilter));
        }

        if ($dateStart) {
            $query->whereDate('created_at', '>=', $dateStart);
        }

        if ($dateEnd) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }

        $leads = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('agencia.conversas.index', compact(
            'clients',
            'tags',
            'leads',
            'clientFilter',
            'tagFilter',
            'dateStart',
            'dateEnd',
        ));
    }

    public function destroy(Request $request, ClienteLead $clienteLead): RedirectResponse
    {
        $user = $request->user();

        abort_unless($clienteLead->cliente && $clienteLead->cliente->user_id === $user->id, 403);

        $clienteLead->assistantLeads()->delete();
        $clienteLead->tags()->detach();
        $clienteLead->delete();

        return redirect()
            ->route('agencia.conversas.index')
            ->with('success', 'Lead removido com sucesso.');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $cliente = $this->resolveClienteForUser($request->validate([
            'cliente_id' => ['required', 'integer'],
        ])['cliente_id'], $user->id);

        $data = $request->validate([
            'bot_enabled' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:40'],
            'name' => ['nullable', 'string', 'max:191'],
            'info' => ['nullable', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer'],
        ]);

        $lead = ClienteLead::create([
            'cliente_id' => $cliente->id,
            'bot_enabled' => $request->boolean('bot_enabled'),
            'phone' => $data['phone'] ?? null,
            'name' => $data['name'] ?? null,
            'info' => $data['info'] ?? null,
        ]);

        $lead->tags()->sync($this->filterTags((array) ($data['tags'] ?? []), $user->id));

        return redirect()
            ->route('agencia.conversas.index')
            ->with('success', 'Lead criado com sucesso.');
    }

    public function update(Request $request, ClienteLead $clienteLead): RedirectResponse
    {
        $user = $request->user();
        abort_unless($clienteLead->cliente && $clienteLead->cliente->user_id === $user->id, 403);

        $cliente = $this->resolveClienteForUser($request->validate([
            'cliente_id' => ['required', 'integer'],
        ])['cliente_id'], $user->id);

        $data = $request->validate([
            'bot_enabled' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:40'],
            'name' => ['nullable', 'string', 'max:191'],
            'info' => ['nullable', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer'],
        ]);

        $clienteLead->update([
            'cliente_id' => $cliente->id,
            'bot_enabled' => $request->boolean('bot_enabled'),
            'phone' => $data['phone'] ?? null,
            'name' => $data['name'] ?? null,
            'info' => $data['info'] ?? null,
        ]);

        $clienteLead->tags()->sync($this->filterTags((array) ($data['tags'] ?? []), $user->id));

        return redirect()
            ->route('agencia.conversas.index')
            ->with('success', 'Lead atualizado com sucesso.');
    }

    public function import(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'delimiter' => ['nullable', 'in:semicolon,comma'],
            'map_phone' => ['required', 'integer', 'min:0'],
            'map_name' => ['nullable', 'integer', 'min:0'],
            'map_info' => ['nullable', 'integer', 'min:0'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $cliente = $this->resolveClienteForUser($validated['cliente_id'], $user->id);
        $delimiter = ($validated['delimiter'] ?? 'semicolon') === 'comma' ? ',' : ';';
        $tagIds = $this->filterTags((array) ($validated['tags'] ?? []), $user->id);

        $file = $validated['csv_file'];
        $extension = strtolower($file->getClientOriginalExtension());

        $mapPhone = (int) $validated['map_phone'];
        $mapName = is_numeric($validated['map_name'] ?? null) ? (int) $validated['map_name'] : null;
        $mapInfo = is_numeric($validated['map_info'] ?? null) ? (int) $validated['map_info'] : null;

        $created = 0;
        $skipped = 0;

        if ($extension === 'xlsx') {
            $rows = $this->readXlsxRows($file->getRealPath());
            if ($rows === null || empty($rows)) {
                return redirect()
                    ->route('agencia.conversas.index')
                    ->with('error', 'O XLSX está vazio.');
            }

            array_shift($rows);

            foreach ($rows as $row) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $phone = $this->columnValue($row, $mapPhone);
                if (!$phone) {
                    $skipped++;
                    continue;
                }

                $lead = ClienteLead::create([
                    'cliente_id' => $cliente->id,
                    'bot_enabled' => false,
                    'phone' => $phone,
                    'name' => $mapName !== null ? $this->columnValue($row, $mapName) : null,
                    'info' => $mapInfo !== null ? $this->columnValue($row, $mapInfo) : null,
                ]);

                if (!empty($tagIds)) {
                    $lead->tags()->sync($tagIds);
                }

                $created++;
            }
        } else {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                return redirect()
                    ->route('agencia.conversas.index')
                    ->with('error', 'Não foi possível abrir o arquivo CSV.');
            }

            $header = fgetcsv($handle, 0, $delimiter);
            if ($header === false) {
                fclose($handle);
                return redirect()
                    ->route('agencia.conversas.index')
                    ->with('error', 'O CSV está vazio.');
            }

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $phone = $this->columnValue($row, $mapPhone);
                if (!$phone) {
                    $skipped++;
                    continue;
                }

                $lead = ClienteLead::create([
                    'cliente_id' => $cliente->id,
                    'bot_enabled' => false,
                    'phone' => $phone,
                    'name' => $mapName !== null ? $this->columnValue($row, $mapName) : null,
                    'info' => $mapInfo !== null ? $this->columnValue($row, $mapInfo) : null,
                ]);

                if (!empty($tagIds)) {
                    $lead->tags()->sync($tagIds);
                }

                $created++;
            }

            fclose($handle);
        }

        return redirect()
            ->route('agencia.conversas.index')
            ->with('success', "Importação concluída: {$created} registros adicionados, {$skipped} ignorados.");
    }

    private function resolveClienteForUser(int $clienteId, int $userId): Cliente
    {
        return Cliente::where('user_id', $userId)->findOrFail($clienteId);
    }

    private function filterTags(array $tagIds, int $userId): array
    {
        $tagIds = array_values(array_filter($tagIds, fn ($value) => $value !== '' && $value !== null));

        if (empty($tagIds)) {
            return [];
        }

        return Tag::where('user_id', $userId)->whereIn('id', $tagIds)->pluck('id')->all();
    }

    private function columnValue(array $row, ?int $index): ?string
    {
        if ($index === null || !array_key_exists($index, $row)) {
            return null;
        }

        $value = trim((string) $row[$index]);

        return $value === '' ? null : $value;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function readXlsxRows(string $path): ?array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, false);
    }
}
