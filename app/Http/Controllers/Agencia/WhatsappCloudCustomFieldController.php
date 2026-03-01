<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\WhatsappCloudCustomField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WhatsappCloudCustomFieldController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;
        $filterData = $request->validate([
            'cliente_id' => [
                'nullable',
                'integer',
                Rule::exists('clientes', 'id')
                    ->where(fn ($query) => $query->where('user_id', $userId)->whereNull('deleted_at')),
            ],
        ]);
        $clienteFilter = isset($filterData['cliente_id']) ? (int) $filterData['cliente_id'] : null;

        $fieldsQuery = WhatsappCloudCustomField::query()
            ->where('user_id', $userId)
            ->with('cliente:id,nome')
            ->orderBy('name');

        if ($clienteFilter) {
            $fieldsQuery->where('cliente_id', $clienteFilter);
        }

        $fields = $fieldsQuery->get();

        $clientes = Cliente::query()
            ->where('user_id', $userId)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('agencia.campos-personalizados.index', [
            'fields' => $fields,
            'clientes' => $clientes,
            'clienteFilter' => $clienteFilter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cliente_id' => [
                'nullable',
                'integer',
                Rule::exists('clientes', 'id')
                    ->where(fn ($query) => $query->where('user_id', $userId)->whereNull('deleted_at')),
            ],
            'label' => ['nullable', 'string', 'max:120'],
            'sample_value' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $normalizedName = $this->normalizeFieldName((string) $data['name']);
        $uniqueName = $this->resolveUniqueFieldName($userId, $normalizedName, null);

        WhatsappCloudCustomField::create([
            'user_id' => $userId,
            'cliente_id' => $data['cliente_id'] ?? null,
            'name' => $uniqueName,
            'label' => $this->nullableTrim($data['label'] ?? null),
            'sample_value' => $this->nullableTrim($data['sample_value'] ?? null),
            'description' => $this->nullableTrim($data['description'] ?? null),
        ]);

        return redirect()
            ->route('agencia.campos-personalizados.index')
            ->with('success', "Campo personalizado salvo como {$uniqueName}.");
    }

    public function update(Request $request, WhatsappCloudCustomField $campoPersonalizado): RedirectResponse
    {
        $this->ensureOwnership($campoPersonalizado, $request->user()->id);
        $userId = (int) $request->user()->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cliente_id' => [
                'nullable',
                'integer',
                Rule::exists('clientes', 'id')
                    ->where(fn ($query) => $query->where('user_id', $userId)->whereNull('deleted_at')),
            ],
            'label' => ['nullable', 'string', 'max:120'],
            'sample_value' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $normalizedName = $this->normalizeFieldName((string) $data['name']);
        $uniqueName = $this->resolveUniqueFieldName($userId, $normalizedName, $campoPersonalizado->id);

        $campoPersonalizado->update([
            'name' => $uniqueName,
            'cliente_id' => $data['cliente_id'] ?? null,
            'label' => $this->nullableTrim($data['label'] ?? null),
            'sample_value' => $this->nullableTrim($data['sample_value'] ?? null),
            'description' => $this->nullableTrim($data['description'] ?? null),
        ]);

        return redirect()
            ->route('agencia.campos-personalizados.index')
            ->with('success', "Campo personalizado atualizado para {$uniqueName}.");
    }

    public function destroy(Request $request, WhatsappCloudCustomField $campoPersonalizado): RedirectResponse
    {
        $this->ensureOwnership($campoPersonalizado, $request->user()->id);
        $campoPersonalizado->delete();

        return redirect()
            ->route('agencia.campos-personalizados.index')
            ->with('success', 'Campo personalizado removido com sucesso.');
    }

    private function ensureOwnership(WhatsappCloudCustomField $field, int $userId): void
    {
        if ((int) $field->user_id !== $userId) {
            abort(403);
        }
    }

    private function normalizeFieldName(string $value): string
    {
        $value = Str::ascii($value);
        $value = Str::lower($value);
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? '';
        $value = trim($value, '_');

        if ($value === '') {
            $value = 'campo';
        }

        if (preg_match('/^\d/', $value)) {
            $value = 'campo_' . $value;
        }

        return Str::limit($value, 120, '');
    }

    private function resolveUniqueFieldName(int $userId, string $base, ?int $ignoreId): string
    {
        $query = WhatsappCloudCustomField::query()
            ->where('user_id', $userId);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $existing = $query->pluck('name')->all();
        if (!in_array($base, $existing, true)) {
            return $base;
        }

        $index = 2;
        do {
            $suffix = (string) $index;
            $trimmedBase = Str::limit($base, max(1, 120 - strlen($suffix)), '');
            $candidate = $trimmedBase . $suffix;
            $index++;
        } while (in_array($candidate, $existing, true));

        return $candidate;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
