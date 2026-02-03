<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\Tag;
use App\Services\UazapiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClienteLeadController extends Controller
{
    public function __construct(protected UazapiService $uazapiService)
    {
    }

    public function index(Request $request): View
    {
        $cliente = auth('client')->user();
        $tags = Tag::where('user_id', $cliente->user_id)->orderBy('name')->get();

        [$dateStart, $dateEnd, $query] = $this->buildFilteredQuery($request, $cliente);

        $leads = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('cliente.conversas.index', compact(
            'cliente',
            'tags',
            'leads',
            'dateStart',
            'dateEnd',
        ));
    }

    public function destroy(Request $request, ClienteLead $clienteLead): RedirectResponse
    {
        $cliente = auth('client')->user();
        abort_unless($clienteLead->cliente_id === $cliente->id, 403);

        $clienteLead->assistantLeads()->delete();
        $clienteLead->tags()->detach();
        $clienteLead->delete();

        return redirect()
            ->route('cliente.conversas.index')
            ->with('success', 'Lead removido com sucesso.');
    }

    public function store(Request $request): RedirectResponse
    {
        $cliente = auth('client')->user();

        $data = $request->validate([
            'bot_enabled' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:40'],
            'name' => ['nullable', 'string', 'max:191'],
            'info' => ['nullable', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer'],
        ]);

        $rawPhone = $data['phone'] ?? null;
        $normalizedPhone = $this->normalizePhone($rawPhone);
        if ($rawPhone !== null && $rawPhone !== '' && !$normalizedPhone) {
            return redirect()
                ->route('cliente.conversas.index')
                ->with('error', 'Número inválido. Informe o telefone com DDI (ex: 55).');
        }

        if ($normalizedPhone) {
            if (!preg_match('/^\d{11,15}$/', $normalizedPhone)) {
                return redirect()
                    ->route('cliente.conversas.index')
                    ->with('error', 'Número inválido. Informe o telefone com DDI (ex: 55).');
            }

            $conexao = Conexao::query()
                ->where('cliente_id', $cliente->id)
                ->whereNotNull('whatsapp_api_key')
                ->whereHas('whatsappApi', fn ($query) => $query->where('slug', 'uazapi'))
                ->orderByRaw("status = 'connected' desc")
                ->latest('updated_at')
                ->first();

            if (!$conexao?->whatsapp_api_key) {
                return redirect()
                    ->route('cliente.conversas.index')
                    ->with('error', 'Conexao Uazapi nao configurada.');
            }

            $chatCheck = $this->uazapiService->chat_check([$normalizedPhone], $conexao->whatsapp_api_key);
            $status = $chatCheck['status'] ?? null;
            if (!empty($chatCheck['error']) || $status !== 200) {
                return redirect()
                    ->route('cliente.conversas.index')
                    ->with('error', 'Número inválido ou inexistente no WhatsApp.');
            }

            $exists = ClienteLead::where('cliente_id', $cliente->id)
                ->where('phone', $normalizedPhone)
                ->exists();
            if ($exists) {
                return redirect()
                    ->route('cliente.conversas.index')
                    ->with('error', 'Este telefone já está cadastrado para o cliente selecionado.');
            }
        }

        $lead = ClienteLead::create([
            'cliente_id' => $cliente->id,
            'bot_enabled' => $request->boolean('bot_enabled'),
            'phone' => $normalizedPhone,
            'name' => $data['name'] ?? null,
            'info' => $data['info'] ?? null,
        ]);

        $lead->tags()->sync($this->filterTags((array) ($data['tags'] ?? []), $cliente->user_id));

        return redirect()
            ->route('cliente.conversas.index')
            ->with('success', 'Lead criado com sucesso.');
    }

    public function update(Request $request, ClienteLead $clienteLead): RedirectResponse
    {
        $cliente = auth('client')->user();
        abort_unless($clienteLead->cliente_id === $cliente->id, 403);

        $data = $request->validate([
            'bot_enabled' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:40'],
            'name' => ['nullable', 'string', 'max:191'],
            'info' => ['nullable', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer'],
        ]);

        $clienteLead->update([
            'bot_enabled' => $request->boolean('bot_enabled'),
            'phone' => $data['phone'] ?? null,
            'name' => $data['name'] ?? null,
            'info' => $data['info'] ?? null,
        ]);

        $clienteLead->tags()->sync($this->filterTags((array) ($data['tags'] ?? []), $cliente->user_id));

        return redirect()
            ->route('cliente.conversas.index')
            ->with('success', 'Lead atualizado com sucesso.');
    }

    public function import(Request $request): RedirectResponse
    {
        $cliente = auth('client')->user();

        $validated = $request->validate([
            'delimiter' => ['nullable', 'in:semicolon,comma'],
            'map_phone' => ['required', 'integer', 'min:0'],
            'map_name' => ['nullable', 'integer', 'min:0'],
            'map_info' => ['nullable', 'integer', 'min:0'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $delimiter = ($validated['delimiter'] ?? 'semicolon') === 'comma' ? ',' : ';';
        $tagIds = $this->filterTags((array) ($validated['tags'] ?? []), $cliente->user_id);

        $file = $validated['csv_file'];
        $extension = strtolower($file->getClientOriginalExtension());

        $mapPhone = (int) $validated['map_phone'];
        $mapName = is_numeric($validated['map_name'] ?? null) ? (int) $validated['map_name'] : null;
        $mapInfo = is_numeric($validated['map_info'] ?? null) ? (int) $validated['map_info'] : null;

        $created = 0;
        $skippedDuplicate = 0;
        $skippedInvalid = 0;

        if ($extension === 'xlsx') {
            $rows = $this->readXlsxRows($file->getRealPath());
            if ($rows === null) {
                return redirect()
                    ->route('cliente.conversas.index')
                    ->with('error', 'Não foi possível ler o XLSX enviado. Confirme se o arquivo é um .xlsx válido (Excel 2007+).');
            }
            if (empty($rows)) {
                return redirect()
                    ->route('cliente.conversas.index')
                    ->with('error', 'O XLSX está vazio.');
            }

            $headerIndex = $this->firstNonEmptyRowIndex($rows);
            if ($headerIndex === null) {
                return redirect()
                    ->route('cliente.conversas.index')
                    ->with('error', 'O XLSX está vazio.');
            }

            $rows = array_slice($rows, $headerIndex + 1);

            foreach ($rows as $row) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $row = array_values($row);
                $phone = $this->columnValue($row, $mapPhone);
                if (!$phone) {
                    $skippedInvalid++;
                    continue;
                }

                $exists = ClienteLead::where('cliente_id', $cliente->id)
                    ->where('phone', $phone)
                    ->exists();
                if ($exists) {
                    $skippedDuplicate++;
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
                    ->route('cliente.conversas.index')
                    ->with('error', 'Não foi possível abrir o arquivo CSV.');
            }

            $header = fgetcsv($handle, 0, $delimiter);
            if ($header === false) {
                fclose($handle);
                return redirect()
                    ->route('cliente.conversas.index')
                    ->with('error', 'O CSV está vazio.');
            }

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $phone = $this->columnValue($row, $mapPhone);
                if (!$phone) {
                    $skippedInvalid++;
                    continue;
                }

                $exists = ClienteLead::where('cliente_id', $cliente->id)
                    ->where('phone', $phone)
                    ->exists();
                if ($exists) {
                    $skippedDuplicate++;
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

        $skipped = $skippedDuplicate + $skippedInvalid;
        $successMessage = "Importação concluída: {$created} registros adicionados.";

        if ($skipped > 0) {
            $successMessage .= " {$skipped} ignorados.";
        }

        $response = redirect()
            ->route('cliente.conversas.index')
            ->with('success', $successMessage);

        if ($skippedDuplicate > 0 || $skippedInvalid > 0) {
            $details = [];
            if ($skippedDuplicate > 0) {
                $details[] = "{$skippedDuplicate} duplicado(s)";
            }
            if ($skippedInvalid > 0) {
                $details[] = "{$skippedInvalid} inválido(s)";
            }
            $response->with('error', 'Alguns registros foram ignorados (' . implode(', ', $details) . ').');
        }

        return $response;
    }

    public function export(Request $request): Response
    {
        $cliente = auth('client')->user();
        $format = strtolower($request->query('format', 'csv'));
        if (!in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            $format = 'csv';
        }

        [, , $query] = $this->buildFilteredQuery($request, $cliente, eager: true);
        $leads = $query->orderByDesc('created_at')->get();

        $mapped = $leads->map(function (ClienteLead $lead) {
            return [
                'cliente' => $lead->cliente?->nome ?? '-',
                'telefone' => $lead->phone ?? '-',
                'nome' => $lead->name ?? '-',
                'tags' => $lead->tags->pluck('name')->implode(', '),
                'bot' => $lead->bot_enabled ? 'Sim' : 'Não',
                'criado_em' => $lead->created_at?->format('d/m/Y H:i') ?? '-',
            ];
        })->all();

        $headers = ['Cliente', 'Telefone', 'Nome', 'Tags', 'Bot', 'Criado em'];

        return match ($format) {
            'xlsx' => $this->exportXlsx($headers, $mapped),
            'pdf' => $this->exportPdf($headers, $mapped),
            default => $this->exportCsv($headers, $mapped),
        };
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delimiter' => ['nullable', 'in:semicolon,comma'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $file = $validated['csv_file'];
        $extension = strtolower($file->getClientOriginalExtension());
        $delimiter = ($validated['delimiter'] ?? 'semicolon') === 'comma' ? ',' : ';';

        $headers = [];
        $rows = [];

        if ($extension === 'xlsx') {
            $sheetRows = $this->readXlsxRows($file->getRealPath());
            if ($sheetRows === null) {
                return response()->json([
                    'headers' => [],
                    'rows' => [],
                    'is_xlsx' => true,
                    'error' => 'Não foi possível ler o XLSX enviado. Confirme se o arquivo é um .xlsx válido (Excel 2007+).',
                ]);
            }
            $sheetRows = $sheetRows ?? [];
            $headerIndex = $this->firstNonEmptyRowIndex($sheetRows);
            if ($headerIndex !== null) {
                $headers = array_map('strval', array_values($sheetRows[$headerIndex]));
                $headers = $this->normalizeHeaders($headers);
                $rows = array_slice($sheetRows, $headerIndex + 1, 3);
                $rows = array_map('array_values', $rows);
            }
        } else {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                return response()->json(['headers' => [], 'rows' => [], 'is_xlsx' => false]);
            }

            $headerRow = fgetcsv($handle, 0, $delimiter);
            if ($headerRow !== false) {
                $headers = $this->normalizeHeaders(array_map('strval', $headerRow));
            }

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }
                $rows[] = $row;
                if (count($rows) >= 3) {
                    break;
                }
            }
            fclose($handle);
        }

        $headers = array_values($headers);
        $rows = array_map('array_values', $rows);

        return response()->json([
            'headers' => $headers,
            'rows' => $rows,
            'is_xlsx' => $extension === 'xlsx',
        ]);
    }


    private function filterTags(array $tagIds, int $userId): array
    {
        $tagIds = array_values(array_filter($tagIds, fn ($value) => $value !== '' && $value !== null));

        if (empty($tagIds)) {
            return [];
        }

        return Tag::where('user_id', $userId)->whereIn('id', $tagIds)->pluck('id')->all();
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) <= 11) {
            $digits = '55' . $digits;
        }

        return $digits;
    }

    private function buildFilteredQuery(Request $request, $cliente, bool $eager = false): array
    {
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');

        $base = ClienteLead::query();
        if ($eager) {
            $base->with(['cliente', 'tags']);
        } else {
            $base->with(['cliente', 'assistantLeads.assistant', 'tags']);
        }

        $query = $base->where('cliente_id', $cliente->id);

        if ($dateStart) {
            $query->whereDate('created_at', '>=', $dateStart);
        }

        if ($dateEnd) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }

        return [$dateStart, $dateEnd, $query];
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

    private function normalizeHeaders(array $headers): array
    {
        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        $hasAny = collect($headers)->contains(fn ($header) => $header !== '');

        return array_map(function ($header, $index) use ($hasAny) {
            if ($hasAny && $header !== '') {
                return $header;
            }
            return 'Coluna ' . ($index + 1);
        }, $headers, array_keys($headers));
    }

    private function firstNonEmptyRowIndex(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            if (is_array($row) && !$this->rowIsEmpty($row)) {
                return (int) $index;
            }
        }

        return null;
    }


    private function readXlsxRows(string $path): ?array
    {
        if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            try {
                $reader = IOFactory::createReaderForFile($path);
                if (method_exists($reader, 'setReadDataOnly')) {
                    $reader->setReadDataOnly(true);
                }
                if (method_exists($reader, 'setReadEmptyCells')) {
                    $reader->setReadEmptyCells(false);
                }

                $spreadsheet = $reader->load($path);
                $sheet = $spreadsheet->getActiveSheet();

                return $sheet->toArray(null, true, true, false);
            } catch (\Throwable $e) {
                \Log::warning('XLSX read failed in ClienteLeadController', [
                    'message' => $e->getMessage(),
                    'file' => $path,
                ]);
            }
        }

        return $this->readXlsxRowsFallback($path);
    }

    private function readXlsxRowsFallback(string $path): ?array
    {
        if (!class_exists('ZipArchive')) {
            \Log::warning('XLSX read failed: ZipArchive not available', [
                'file' => $path,
            ]);
            return null;
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            \Log::warning('XLSX read failed: unable to open zip', [
                'file' => $path,
            ]);
            return null;
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $shared = @simplexml_load_string($sharedXml);
            if ($shared !== false && isset($shared->si)) {
                foreach ($shared->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                        continue;
                    }
                    $text = '';
                    if (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $text .= (string) ($run->t ?? '');
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetPath = $this->resolveFirstWorksheetPath($zip) ?? 'xl/worksheets/sheet1.xml';
        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            \Log::warning('XLSX read failed: worksheet not found', [
                'file' => $path,
                'sheet' => $sheetPath,
            ]);
            $zip->close();
            return null;
        }

        $sheet = @simplexml_load_string($sheetXml);
        if ($sheet === false || !isset($sheet->sheetData)) {
            \Log::warning('XLSX read failed: invalid worksheet xml', [
                'file' => $path,
                'sheet' => $sheetPath,
            ]);
            $zip->close();
            return null;
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $rowValues = [];
            foreach ($row->c as $cell) {
                $cellRef = (string) ($cell['r'] ?? '');
                $colIndex = $this->columnIndexFromCell($cellRef);
                $type = (string) ($cell['t'] ?? '');
                $value = '';

                if ($type === 's') {
                    $index = (int) ($cell->v ?? 0);
                    $value = $sharedStrings[$index] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                } else {
                    $value = (string) ($cell->v ?? '');
                }

                $rowValues[$colIndex] = $value;
            }

            if (!empty($rowValues)) {
                ksort($rowValues);
                $rows[] = array_values($rowValues);
            }
        }

        $zip->close();
        return $rows;
    }

    private function resolveFirstWorksheetPath(\ZipArchive $zip): ?string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relsXml === false) {
            return null;
        }

        $workbook = @simplexml_load_string($workbookXml);
        $rels = @simplexml_load_string($relsXml);
        if ($workbook === false || $rels === false || !isset($workbook->sheets->sheet[0])) {
            return null;
        }

        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheet = $workbook->sheets->sheet[0];
        $attributes = $sheet->attributes('r', true);
        $relId = $attributes['id'] ?? null;
        if (!$relId) {
            return null;
        }

        foreach ($rels->Relationship as $rel) {
            if ((string) $rel['Id'] === (string) $relId) {
                $target = (string) $rel['Target'];
                return 'xl/' . ltrim($target, '/');
            }
        }

        return null;
    }

    private function columnIndexFromCell(string $cellRef): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($cellRef));
        if ($letters === '') {
            return 0;
        }

        $index = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function exportCsv(array $headers, array $rows): StreamedResponse
    {
        $fileName = 'conversas_' . now()->format('Ymd_His') . '.csv';
        $callback = function () use ($headers, $rows) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($output, $row, ';');
            }
            fclose($output);
        };

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportXlsx(array $headers, array $rows): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'conversas_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function exportPdf(array $headers, array $rows): Response
    {
        $pdf = Pdf::loadView('cliente.conversas.export-pdf', [
            'headers' => $headers,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        $fileName = 'conversas_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }
}
