<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\Tag;
use App\Jobs\ProcessIncomingMessageJob;
use App\Services\OpenAIService;
use App\Services\ScheduledMessageService;
use App\Support\PhoneNumberNormalizer;
use App\Support\OpenAIConversationFormatter;
use App\Services\WhatsappCloudConversationWindowService;
use App\Services\UazapiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;

class ClienteLeadController extends Controller
{
    private const CHAT_CARD_LIMIT = 20;

    public function __construct(
        protected UazapiService $uazapiService,
        protected PhoneNumberNormalizer $phoneNumberNormalizer
    )
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        $cliente = auth('client')->user();
        $activeTab = $request->query('tab') === 'chat' ? 'chat' : 'list';
        $assistants = Assistant::where('cliente_id', $cliente->id)
            ->orderBy('name')
            ->get();
        $messageConexoes = Conexao::query()
            ->with(['assistant:id,name,version,cliente_id', 'whatsappApi:id,slug'])
            ->where('cliente_id', $cliente->id)
            ->where('is_active', true)
            ->whereNotNull('assistant_id')
            ->whereNull('deleted_at')
            ->whereHas('assistant', fn ($query) => $query->where('cliente_id', $cliente->id))
            ->orderBy('name')
            ->get();
        $tags = Tag::where('user_id', $cliente->user_id)
            ->where('cliente_id', $cliente->id)
            ->orderBy('name')
            ->get();

        [$assistantFilter, $tagFilter, $dateStart, $dateEnd, $query] = $this->buildFilteredQuery($request, $cliente);

        $leads = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $chatData = [];
        if ($activeTab === 'chat') {
            $chatData = $this->resolveClienteChatData($request, $cliente, $messageConexoes);

            if ($request->wantsJson()) {
                if ($request->boolean('cards_only')) {
                    return $this->jsonClienteChatCardsResponse($chatData);
                }

                if ($request->boolean('panel_only')) {
                    return $this->jsonClienteChatPanelResponse($chatData);
                }

                if ($request->boolean('poll_state')) {
                    return $this->jsonClienteChatPollStateResponse($chatData);
                }

                return $this->jsonClienteChatResponse($chatData);
            }
        }

        if ($activeTab !== 'chat' && $request->ajax()) {
            return view('cliente.conversas._table', compact('leads'));
        }

        return view('cliente.conversas.index', array_merge(compact(
            'cliente',
            'assistants',
            'tags',
            'leads',
            'messageConexoes',
            'assistantFilter',
            'tagFilter',
            'dateStart',
            'dateEnd',
            'activeTab',
        ), $chatData));
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

    public function sendMessage(Request $request, ClienteLead $clienteLead): JsonResponse
    {
        $cliente = auth('client')->user();
        abort_unless($clienteLead->cliente_id === $cliente->id, 403);

        $data = $request->validate([
            'conexao_id' => ['required', 'integer'],
            'mensagem' => ['required', 'string', 'max:2000'],
        ]);

        $conexao = Conexao::query()
            ->with(['assistant', 'whatsappApi', 'whatsappCloudAccount'])
            ->whereKey((int) $data['conexao_id'])
            ->where('cliente_id', $cliente->id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if (!$conexao) {
            return response()->json([
                'message' => 'Conexao selecionada nao encontrada.',
            ], 422);
        }

        $assistant = $conexao->assistant;
        if (!$assistant || (int) $assistant->cliente_id !== (int) $cliente->id) {
            return response()->json([
                'message' => 'Conexao selecionada sem assistente vinculado.',
            ], 422);
        }

        $mensagem = trim((string) $data['mensagem']);
        if ($mensagem === '') {
            return response()->json([
                'message' => 'Mensagem vazia.',
            ], 422);
        }

        $this->ensureAssistantLeadAssociation($clienteLead, $assistant);

        $scheduledMessageService = app(ScheduledMessageService::class);
        $context = $scheduledMessageService->resolveDispatchContext(
            $clienteLead,
            (int) $assistant->id,
            (int) $cliente->user_id,
            (int) $conexao->id
        );

        if (!$context['ok']) {
            return response()->json([
                'message' => $context['message'] ?? 'Nao foi possivel validar o contexto de envio.',
            ], 422);
        }

        /** @var Conexao $dispatchConexao */
        $dispatchConexao = $context['conexao'];
        /** @var string $phone */
        $phone = $context['phone'];

        if ($this->isWhatsappCloudConexao($dispatchConexao)) {
            $isInsideWindow = app(WhatsappCloudConversationWindowService::class)
                ->isInsideWindow((int) $clienteLead->id, (int) $dispatchConexao->id);

            if (!$isInsideWindow) {
                return response()->json([
                    'message' => 'Esta conversa está fora da janela de 24h. Use um modelo da WhatsApp Cloud.',
                ], 422);
            }
        }

        $agoraUtc = Carbon::now('UTC');
        $eventId = sprintf(
            'manual:cliente:lead:%d:assistant:%d:ts:%d',
            $clienteLead->id,
            $assistant->id,
            $agoraUtc->valueOf()
        );

        $payload = [
            'phone' => $phone,
            'text' => $mensagem,
            'tipo' => 'text',
            'from_me' => false,
            'is_group' => false,
            'lead_name' => $clienteLead->name ?? $phone,
            'openai_role' => 'system',
            'event_id' => $eventId,
            'message_timestamp' => $agoraUtc->valueOf(),
            'message_type' => 'conversation',
        ];

        ProcessIncomingMessageJob::dispatch($dispatchConexao->id, $clienteLead->id, $payload)
            ->onQueue('processarconversa');

        if ($this->isWhatsappCloudConexao($dispatchConexao)) {
            app(WhatsappCloudConversationWindowService::class)
                ->touchOutbound((int) $clienteLead->id, (int) $dispatchConexao->id, $agoraUtc);
        }

        return response()->json([
            'message' => 'Mensagem enviada para a fila.',
        ]);
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
                ->with('error', 'Número inválido. Informe o telefone com DDD ou DDI.');
        }

        if ($normalizedPhone) {
            if (!preg_match('/^\d{11,15}$/', $normalizedPhone)) {
                return redirect()
                    ->route('cliente.conversas.index')
                    ->with('error', 'Número inválido. Informe o telefone com DDD ou DDI.');
            }

            $uazapiToken = $this->resolveUazapiToken($cliente);
            if (!$uazapiToken) {
                return redirect()
                    ->route('cliente.conversas.index')
                    ->with('error', 'Conexao Uazapi nao configurada.');
            }

            $chatCheck = $this->uazapiService->chat_check([$normalizedPhone], $uazapiToken);
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

        try {
            $lead = ClienteLead::create([
                'cliente_id' => $cliente->id,
                'bot_enabled' => $request->boolean('bot_enabled'),
                'phone' => $normalizedPhone,
                'name' => $data['name'] ?? null,
                'info' => $data['info'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            if (!$this->isClienteLeadPhoneUniqueViolation($exception)) {
                throw $exception;
            }

            return redirect()
                ->route('cliente.conversas.index')
                ->with('error', 'Este telefone já está cadastrado para o cliente selecionado.');
        }

        $lead->tags()->sync($this->filterTags((array) ($data['tags'] ?? []), $cliente->user_id, $cliente->id));

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

        $rawPhone = $data['phone'] ?? null;
        $normalizedPhone = $this->normalizePhone($rawPhone);
        if ($rawPhone !== null && trim((string) $rawPhone) !== '' && !$normalizedPhone) {
            return redirect()
                ->route('cliente.conversas.index')
                ->with('error', 'Número inválido. Informe o telefone com DDD ou DDI.');
        }

        if ($normalizedPhone) {
            $exists = ClienteLead::query()
                ->where('cliente_id', $cliente->id)
                ->where('phone', $normalizedPhone)
                ->where('id', '!=', $clienteLead->id)
                ->exists();
            if ($exists) {
                return redirect()
                    ->route('cliente.conversas.index')
                    ->with('error', 'Este telefone já está cadastrado para o cliente selecionado.');
            }
        }

        try {
            $clienteLead->update([
                'bot_enabled' => $request->boolean('bot_enabled'),
                'phone' => $normalizedPhone,
                'name' => $data['name'] ?? null,
                'info' => $data['info'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            if (!$this->isClienteLeadPhoneUniqueViolation($exception)) {
                throw $exception;
            }

            return redirect()
                ->route('cliente.conversas.index')
                ->with('error', 'Este telefone já está cadastrado para o cliente selecionado.');
        }

        $clienteLead->tags()->sync($this->filterTags((array) ($data['tags'] ?? []), $cliente->user_id, $cliente->id));

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
        $tagIds = $this->filterTags((array) ($validated['tags'] ?? []), $cliente->user_id, $cliente->id);

        $file = $validated['csv_file'];
        $extension = strtolower($file->getClientOriginalExtension());

        $mapPhone = (int) $validated['map_phone'];
        $mapName = is_numeric($validated['map_name'] ?? null) ? (int) $validated['map_name'] : null;
        $mapInfo = is_numeric($validated['map_info'] ?? null) ? (int) $validated['map_info'] : null;

        $uazapiToken = $this->resolveUazapiToken($cliente);
        $missingUazapiConnection = $uazapiToken === null;
        $chatCheckChunkSize = 50;
        $pendingRows = [];
        $pendingPhones = [];
        $importError = null;

        $created = 0;
        $skippedDuplicate = 0;
        $skippedInvalid = 0;

        $flushPending = function () use (
            &$pendingRows,
            &$pendingPhones,
            $uazapiToken,
            &$created,
            &$skippedDuplicate,
            &$skippedInvalid,
            $tagIds,
            &$importError
        ): bool {
            if (empty($pendingRows)) {
                return true;
            }

            $validMap = null;
            if ($uazapiToken) {
                $chatCheck = $this->uazapiService->chat_check(array_values($pendingPhones), $uazapiToken);
                $status = $chatCheck['status'] ?? null;
                if (!empty($chatCheck['error']) || $status !== 200) {
                    $importError = 'NÃ£o foi possÃ­vel validar os telefones na Uazapi. Tente novamente.';
                    return false;
                }
                $validMap = $this->buildChatCheckMap($chatCheck);
            }

            foreach ($pendingRows as $rowData) {
                $phone = $rowData['phone'];
                if ($uazapiToken && $validMap !== null && array_key_exists($phone, $validMap) && !$validMap[$phone]) {
                    $skippedInvalid++;
                    continue;
                }

                $exists = ClienteLead::where('cliente_id', $rowData['cliente_id'])
                    ->where('phone', $phone)
                    ->exists();
                if ($exists) {
                    $skippedDuplicate++;
                    continue;
                }

                try {
                    $lead = ClienteLead::create([
                        'cliente_id' => $rowData['cliente_id'],
                        'bot_enabled' => false,
                        'phone' => $phone,
                        'name' => $rowData['name'],
                        'info' => $rowData['info'],
                    ]);
                } catch (UniqueConstraintViolationException $exception) {
                    if (!$this->isClienteLeadPhoneUniqueViolation($exception)) {
                        throw $exception;
                    }

                    $skippedDuplicate++;
                    continue;
                }

                if (!empty($tagIds)) {
                    $lead->tags()->sync($tagIds);
                }

                $created++;
            }

            $pendingRows = [];
            $pendingPhones = [];
            return true;
        };

        $queueRow = function (array $rowData) use (
            &$pendingRows,
            &$pendingPhones,
            $chatCheckChunkSize,
            $flushPending
        ): bool {
            $pendingRows[] = $rowData;
            $pendingPhones[$rowData['phone']] = $rowData['phone'];

            if (count($pendingRows) < $chatCheckChunkSize) {
                return true;
            }

            return $flushPending();
        };

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
                $rawPhone = $this->columnValue($row, $mapPhone);
                if (!$rawPhone) {
                    $skippedInvalid++;
                    continue;
                }

                $normalizedPhone = $this->normalizePhone($rawPhone);
                if (!$normalizedPhone || !preg_match('/^\d{11,15}$/', $normalizedPhone)) {
                    $skippedInvalid++;
                    continue;
                }

                $rowData = [
                    'cliente_id' => $cliente->id,
                    'phone' => $normalizedPhone,
                    'name' => $mapName !== null ? $this->columnValue($row, $mapName) : null,
                    'info' => $mapInfo !== null ? $this->columnValue($row, $mapInfo) : null,
                ];

                if (!$queueRow($rowData)) {
                    break;
                }
            }
            if ($importError === null) {
                $flushPending();
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

                $rawPhone = $this->columnValue($row, $mapPhone);
                if (!$rawPhone) {
                    $skippedInvalid++;
                    continue;
                }

                $normalizedPhone = $this->normalizePhone($rawPhone);
                if (!$normalizedPhone || !preg_match('/^\d{11,15}$/', $normalizedPhone)) {
                    $skippedInvalid++;
                    continue;
                }

                $rowData = [
                    'cliente_id' => $cliente->id,
                    'phone' => $normalizedPhone,
                    'name' => $mapName !== null ? $this->columnValue($row, $mapName) : null,
                    'info' => $mapInfo !== null ? $this->columnValue($row, $mapInfo) : null,
                ];

                if (!$queueRow($rowData)) {
                    break;
                }
            }

            fclose($handle);
            if ($importError === null) {
                $flushPending();
            }
        }

        if ($importError !== null) {
            return redirect()
                ->route('cliente.conversas.index')
                ->with('error', $importError);
        }

        $skipped = $skippedDuplicate + $skippedInvalid;
        $successMessage = "Importação concluída: {$created} registros adicionados.";

        if ($skipped > 0) {
            $successMessage .= " {$skipped} ignorados.";
        }

        $response = redirect()
            ->route('cliente.conversas.index')
            ->with('success', $successMessage);

        $errorMessages = [];

        if ($skippedDuplicate > 0 || $skippedInvalid > 0) {
            $details = [];
            if ($skippedDuplicate > 0) {
                $details[] = "{$skippedDuplicate} duplicado(s)";
            }
            if ($skippedInvalid > 0) {
                $details[] = "{$skippedInvalid} inv?lido(s)";
            }
            $errorMessages[] = 'Alguns registros foram ignorados (' . implode(', ', $details) . ').';
        }

        if ($missingUazapiConnection) {
            $errorMessages[] = 'Cliente n?o possui conex?o Uazapi configurada. Importa??o feita sem valida??o de WhatsApp.';
        }

        if (!empty($errorMessages)) {
            $response->with('error', implode(' ', $errorMessages));
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

        [, , , , $query] = $this->buildFilteredQuery($request, $cliente, eager: true);
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

    private function resolveClienteChatData(Request $request, Cliente $cliente, $messageConexoes): array
    {
        $convId = trim((string) $request->input('conv_id'));
        $after = (string) $request->input('after');
        $limit = $request->integer('limit');
        $isCardsOnly = $request->boolean('cards_only');
        $isPollState = $request->boolean('poll_state');
        $openAiConexoes = $isPollState ? collect() : $this->loadClienteOpenAIConexoes($cliente);
        $chatLeadCardsPage = (!$request->wantsJson() || $isCardsOnly)
            ? $this->loadClienteChatLeadCards($request, $cliente, $openAiConexoes, $messageConexoes)
            : [
                'cards' => collect(),
                'has_more' => false,
                'next_offset' => 0,
                'limit' => self::CHAT_CARD_LIMIT,
            ];

        $data = [
            'chatConvId' => $convId,
            'chatResult' => null,
            'chatError' => null,
            'chatAssistantLead' => null,
            'chatAssistantLeadUpdatedAt' => null,
            'chatAssistantLeadMatchesCount' => 0,
            'chatMessages' => [],
            'chatHasMore' => false,
            'chatLastId' => null,
            'chatFirstId' => null,
            'chatObject' => null,
            'chatStatus' => null,
            'chatAfter' => $after !== '' ? $after : null,
            'chatLimit' => $limit,
            'chatConexao' => null,
            'chatSendConexao' => null,
            'chatLeadCards' => $chatLeadCardsPage['cards'],
            'chatLeadCardsHasMore' => $chatLeadCardsPage['has_more'],
            'chatLeadCardsNextOffset' => $chatLeadCardsPage['next_offset'],
            'chatLeadCardsLimit' => $chatLeadCardsPage['limit'],
            'chatSendOptions' => $request->wantsJson()
                ? collect()
                : $this->mapClienteChatSendOptions($messageConexoes),
        ];

        if ($isCardsOnly) {
            return $data;
        }

        if ($convId === '') {
            return $data;
        }

        $assistantLeadQuery = AssistantLead::query()
            ->where('conv_id', $convId)
            ->whereHas('lead', fn ($query) => $query->where('cliente_id', $cliente->id));

        $data['chatAssistantLeadMatchesCount'] = (clone $assistantLeadQuery)->count();
        $assistantLead = (clone $assistantLeadQuery)
            ->with([
                'assistant',
                'lead.cliente',
                'lead.tags',
                'lead.sequenceChats.sequence',
                'lead.customFieldValues.customField',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        $data['chatAssistantLead'] = $assistantLead;
        $data['chatAssistantLeadUpdatedAt'] = $assistantLead?->updated_at?->toJSON();

        if (!$assistantLead) {
            $data['chatError'] = "Conv_id \"{$convId}\" nao encontrado para este cliente.";

            return $data;
        }

        $lead = $assistantLead->lead;
        if (!$lead || (int) $lead->cliente_id !== (int) $cliente->id) {
            $data['chatError'] = 'Lead associado ao conv_id nao encontrado para este cliente.';

            return $data;
        }

        if ($isPollState) {
            return $data;
        }

        $conexao = $this->resolveClienteOpenAIConexaoForAssistantLead($assistantLead, $openAiConexoes);
        $data['chatConexao'] = $conexao;
        $data['chatSendConexao'] = $this->resolveClienteSendConexaoForAssistantLead($assistantLead, $messageConexoes);

        if (!$conexao) {
            $data['chatError'] = 'Nao ha conexao com credencial disponivel para este cliente.';

            return $data;
        }

        $credential = $conexao->credential;
        if (!$credential || !$credential->token) {
            $data['chatError'] = 'Credencial vinculada a conexao nao contem token.';

            return $data;
        }

        try {
            $openAi = new OpenAIService($credential->token);
            $query = ['order' => 'desc'];
            if ($after !== '') {
                $query['after'] = $after;
            }
            if ($limit) {
                $query['limit'] = $limit;
            }

            $response = $openAi->getConversationItems($convId, $query);
            if ($response === null) {
                $data['chatError'] = 'OpenAIService retornou resposta nula.';

                return $data;
            }

            $data['chatResult'] = $response->json();
            $data['chatStatus'] = $response->status();

            if (!$response->successful()) {
                $apiMessage = $response->json('error.message') ?? $response->body();
                $data['chatError'] = 'OpenAI retornou erro (' . $data['chatStatus'] . '): ' . $apiMessage;

                return $data;
            }

            $items = is_array($data['chatResult']['data'] ?? null) ? $data['chatResult']['data'] : [];
            $messages = OpenAIConversationFormatter::normalizeItems($items);
            $data['chatMessages'] = ($request->wantsJson() && !$request->boolean('panel_only'))
                ? $messages
                : array_reverse($messages);
            $data['chatHasMore'] = (bool) ($data['chatResult']['has_more'] ?? false);
            $data['chatLastId'] = $data['chatResult']['last_id'] ?? null;
            $data['chatFirstId'] = $data['chatResult']['first_id'] ?? null;
            $data['chatObject'] = $data['chatResult']['object'] ?? null;
        } catch (\Throwable $exception) {
            Log::error('Erro ao buscar conversa OpenAI para cliente', [
                'cliente_id' => $cliente->id,
                'conv_id' => $convId,
                'conexao_id' => $conexao->id,
                'error' => $exception->getMessage(),
            ]);
            $data['chatError'] = 'Falha ao consultar o OpenAI: ' . $exception->getMessage();
        }

        return $data;
    }

    private function loadClienteChatLeadCards(Request $request, Cliente $cliente, $openAiConexoes, $messageConexoes): array
    {
        [$assistantFilter, , , , $query] = $this->buildFilteredQuery($request, $cliente);
        $sendOptions = $this->mapClienteChatSendOptions($messageConexoes, $assistantFilter);
        $activeConvId = trim((string) $request->input('conv_id'));
        $limit = min(max($request->integer('cards_limit', self::CHAT_CARD_LIMIT), 1), self::CHAT_CARD_LIMIT);
        $offset = max($request->integer('cards_offset', 0), 0);

        $this->prepareClienteChatLeadCardQuery($query);

        $activeLead = null;
        if ($activeConvId !== '') {
            $activeLeadQuery = clone $query;
            $this->prepareClienteChatLeadCardQuery($activeLeadQuery);

            $activeLead = $activeLeadQuery
                ->whereHas('assistantLeads', fn ($assistantLeadQuery) => $assistantLeadQuery->where('conv_id', $activeConvId))
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();
        }

        $normalLimit = $offset === 0 && $activeLead ? max($limit - 1, 0) : $limit;
        $normalQuery = clone $query;
        $this->prepareClienteChatLeadCardQuery($normalQuery);

        if ($activeLead) {
            $normalQuery->whereKeyNot($activeLead->getKey());
        }

        $normalLeads = $normalLimit > 0
            ? $normalQuery
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->skip($offset)
                ->take($normalLimit + 1)
                ->get()
            : collect();

        $hasMore = $normalLeads->count() > $normalLimit;
        $normalLeads = $normalLeads->take($normalLimit)->values();
        $leads = collect();

        if ($offset === 0 && $activeLead) {
            $leads->push($activeLead);
        }

        $leads = $leads->concat($normalLeads)->values();

        return [
            'cards' => $leads
            ->map(function (ClienteLead $lead) use ($request, $openAiConexoes, $sendOptions, $activeConvId) {
                $conversations = $lead->assistantLeads
                    ->filter(fn (AssistantLead $assistantLead) => trim((string) ($assistantLead->conv_id ?? '')) !== '')
                    ->sortByDesc(fn (AssistantLead $assistantLead) => $assistantLead->updated_at?->getTimestamp() ?? 0)
                    ->map(function (AssistantLead $assistantLead) use ($request, $openAiConexoes, $activeConvId) {
                        $convId = trim((string) ($assistantLead->conv_id ?? ''));
                        $conexao = $this->resolveClienteOpenAIConexaoForAssistantLead($assistantLead, $openAiConexoes);
                        $lastMessageText = $this->extractLeadWebhookText($assistantLead);

                        return [
                            'assistant_lead_id' => (int) $assistantLead->id,
                            'assistant_id' => (int) $assistantLead->assistant_id,
                            'assistant' => $assistantLead->assistant?->name ?: 'Assistente',
                            'version' => $assistantLead->version,
                            'conv_id' => $convId,
                            'conexao_id' => $conexao?->id,
                            'conexao' => $conexao?->name ?: ($conexao ? 'Conexao #' . $conexao->id : null),
                            'updated_at_label' => $assistantLead->updated_at?->format('d/m/Y H:i') ?: '-',
                            'last_message_text' => $lastMessageText !== '' ? $lastMessageText : 'Sem última mensagem do lead',
                            'url' => route('cliente.conversas.index', $this->buildClienteChatRouteQuery($request, [
                                'conv_id' => $convId,
                            ])),
                            'is_active' => $convId !== '' && $convId === $activeConvId,
                        ];
                    })
                    ->values();

                return [
                    'lead_id' => (int) $lead->id,
                    'name' => trim((string) ($lead->name ?? '')) ?: 'Lead sem nome',
                    'phone' => trim((string) ($lead->phone ?? '')) ?: '-',
                    'conversations' => $conversations,
                    'send_options' => $sendOptions,
                ];
            })
            ->values(),
            'has_more' => $hasMore,
            'next_offset' => $offset + $normalLeads->count(),
            'limit' => $limit,
        ];
    }

    private function prepareClienteChatLeadCardQuery($query): void
    {
        $query->setEagerLoads([]);
        $query->with([
            'assistantLeads' => fn ($assistantLeadQuery) => $assistantLeadQuery
                ->select('id', 'lead_id', 'assistant_id', 'version', 'conv_id', 'webhook_payload', 'updated_at')
                ->orderByDesc('updated_at')
                ->orderByDesc('id'),
            'assistantLeads.assistant:id,name,version,cliente_id',
        ]);
    }

    private function extractLeadWebhookText(?AssistantLead $assistantLead): string
    {
        if (!$assistantLead) {
            return '';
        }

        $payload = $assistantLead->webhook_payload;
        if (!is_array($payload)) {
            return '';
        }

        $text = $payload['text'] ?? null;

        return is_string($text) ? trim($text) : '';
    }

    private function loadClienteOpenAIConexoes(Cliente $cliente)
    {
        return Conexao::query()
            ->with(['assistant:id,name,version,cliente_id', 'credential'])
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('assistant_id')
            ->whereNotNull('credential_id')
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    }

    private function resolveClienteOpenAIConexaoForAssistantLead(?AssistantLead $assistantLead, $openAiConexoes): ?Conexao
    {
        if (!$assistantLead) {
            return null;
        }

        $matching = $openAiConexoes->first(
            fn (Conexao $conexao) => (int) $conexao->assistant_id === (int) $assistantLead->assistant_id
        );

        if ($matching instanceof Conexao) {
            return $matching;
        }

        return null;
    }

    private function resolveClienteSendConexaoForAssistantLead(?AssistantLead $assistantLead, $messageConexoes): ?Conexao
    {
        if (!$assistantLead) {
            return null;
        }

        $matching = $messageConexoes->first(
            fn (Conexao $conexao) => (int) $conexao->assistant_id === (int) $assistantLead->assistant_id
        );

        return $matching instanceof Conexao ? $matching : null;
    }

    private function mapClienteChatSendOptions($messageConexoes, array $assistantFilter = [])
    {
        $assistantIds = array_values(array_filter(array_map('intval', $assistantFilter)));

        return $messageConexoes
            ->filter(function (Conexao $conexao) use ($assistantIds) {
                if (empty($assistantIds)) {
                    return true;
                }

                return in_array((int) $conexao->assistant_id, $assistantIds, true);
            })
            ->map(function (Conexao $conexao) {
                $connectionName = trim((string) ($conexao->name ?? ''));
                $assistantName = trim((string) ($conexao->assistant?->name ?? ''));

                return [
                    'id' => (int) $conexao->id,
                    'assistant_id' => (int) $conexao->assistant_id,
                    'label' => ($connectionName !== '' ? $connectionName : 'Conexao #' . $conexao->id)
                        . ($assistantName !== '' ? ' - ' . $assistantName : ''),
                ];
            })
            ->values();
    }

    private function buildClienteChatRouteQuery(Request $request, array $overrides = []): array
    {
        $query = $request->query();
        unset($query['page'], $query['after'], $query['limit']);
        $query['tab'] = 'chat';

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
                continue;
            }

            $query[$key] = $value;
        }

        return $query;
    }

    private function jsonClienteChatResponse(array $data): JsonResponse
    {
        $statusCode = $data['chatError'] ? ($data['chatStatus'] ?: 400) : 200;

        return response()->json([
            'conv_id' => $data['chatConvId'],
            'assistant_lead_id' => $data['chatAssistantLead']?->id,
            'assistant_lead_updated_at' => $data['chatAssistantLeadUpdatedAt'],
            'messages' => $data['chatMessages'],
            'has_more' => $data['chatHasMore'],
            'last_id' => $data['chatLastId'],
            'first_id' => $data['chatFirstId'],
            'object' => $data['chatObject'],
            'after' => $data['chatAfter'],
            'limit' => $data['chatLimit'],
            'status' => $data['chatStatus'],
            'error' => $data['chatError'],
        ], $statusCode);
    }

    private function jsonClienteChatCardsResponse(array $data): JsonResponse
    {
        return response()->json([
            'html' => view('cliente.conversas._chat_cards', [
                'leadCards' => $data['chatLeadCards'],
            ])->render(),
            'has_more' => (bool) $data['chatLeadCardsHasMore'],
            'next_offset' => (int) $data['chatLeadCardsNextOffset'],
            'limit' => (int) $data['chatLeadCardsLimit'],
            'count' => $data['chatLeadCards']->count(),
        ]);
    }

    private function jsonClienteChatPanelResponse(array $data): JsonResponse
    {
        $statusCode = $data['chatError'] ? ($data['chatStatus'] ?: 400) : 200;

        return response()->json([
            'html' => view('cliente.conversas._chat_panel', $data)->render(),
            'conv_id' => $data['chatConvId'],
            'assistant_lead_id' => $data['chatAssistantLead']?->id,
            'assistant_lead_updated_at' => $data['chatAssistantLeadUpdatedAt'],
            'status' => $data['chatStatus'],
            'error' => $data['chatError'],
        ], $statusCode);
    }

    private function jsonClienteChatPollStateResponse(array $data): JsonResponse
    {
        $statusCode = $data['chatError'] ? 400 : 200;

        return response()->json([
            'conv_id' => $data['chatConvId'],
            'assistant_lead_id' => $data['chatAssistantLead']?->id,
            'assistant_lead_updated_at' => $data['chatAssistantLeadUpdatedAt'],
            'error' => $data['chatError'],
        ], $statusCode);
    }


    private function filterTags(array $tagIds, int $userId, int $clienteId): array
    {
        $tagIds = array_values(array_filter($tagIds, fn ($value) => $value !== '' && $value !== null));

        if (empty($tagIds)) {
            return [];
        }

        return Tag::where('user_id', $userId)
            ->where('cliente_id', $clienteId)
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->all();
    }

    private function normalizePhone(?string $phone): ?string
    {
        return $this->phoneNumberNormalizer->normalizeLeadPhone($phone);
    }

    private function isClienteLeadPhoneUniqueViolation(\Throwable $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return Str::contains($message, [
            'cliente_lead_cliente_id_phone_unique',
            'key (cliente_id, phone)',
        ]);
    }

    private function resolveUazapiToken($cliente): ?string
    {
        $conexao = Conexao::query()
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('whatsapp_api_key')
            ->whereHas('whatsappApi', fn ($query) => $query->where('slug', 'uazapi'))
            ->orderByRaw("status = 'connected' desc")
            ->latest('updated_at')
            ->first();

        return $conexao?->whatsapp_api_key ?: null;
    }

    private function isWhatsappCloudConexao(Conexao $conexao): bool
    {
        return Str::lower(trim((string) ($conexao->whatsappApi?->slug ?? ''))) === 'whatsapp_cloud';
    }

    private function ensureAssistantLeadAssociation(ClienteLead $lead, Assistant $assistant): void
    {
        AssistantLead::query()->firstOrCreate(
            [
                'lead_id' => $lead->id,
                'assistant_id' => $assistant->id,
            ],
            [
                'version' => max(1, (int) ($assistant->version ?? 1)),
                'conv_id' => null,
            ]
        );
    }

    private function buildChatCheckMap(array $payload): ?array
    {
        $items = null;
        foreach (['data', 'result', 'results', 'body', 'numbers'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $items = $payload[$key];
                break;
            }
        }

        if ($items === null && isset($payload[0]) && is_array($payload)) {
            $items = $payload;
        }

        if ($items === null) {
            return null;
        }

        $map = [];
        foreach ($items as $item) {
            if (is_string($item) || is_numeric($item)) {
                $normalized = $this->normalizePhone((string) $item);
                if ($normalized) {
                    $map[$normalized] = true;
                }
                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            $number = $item['number'] ?? $item['phone'] ?? $item['jid'] ?? $item['id'] ?? null;
            if ($number === null) {
                continue;
            }

            $normalized = $this->normalizePhone((string) $number);
            if (!$normalized) {
                continue;
            }

            $flag = $item['exists']
                ?? $item['valid']
                ?? $item['is_valid']
                ?? $item['has_whatsapp']
                ?? $item['isWhatsApp']
                ?? $item['status']
                ?? null;

            $isValid = $this->coerceChatCheckFlag($flag);
            if ($isValid === null) {
                continue;
            }

            $map[$normalized] = $isValid;
        }

        return $map ?: null;
    }

    private function coerceChatCheckFlag(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = strtolower(trim($value));
        $trueValues = ['true', '1', 'yes', 'sim', 'ok', 'valid', 'exists', 'existe'];
        $falseValues = ['false', '0', 'no', 'nao', 'não', 'invalid', 'invalido', 'inválido', 'not_found', 'notfound'];

        if (in_array($normalized, $trueValues, true)) {
            return true;
        }

        if (in_array($normalized, $falseValues, true)) {
            return false;
        }

        return null;
    }

    private function buildFilteredQuery(Request $request, $cliente, bool $eager = false): array
    {
        $assistantFilter = array_values(array_filter((array) $request->input('assistant_id', []), fn ($value) => $value !== '' && $value !== null));
        $tagFilter = array_values(array_filter((array) $request->input('tags', []), fn ($value) => $value !== '' && $value !== null));
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');
        $searchTerm = trim((string) $request->input('q', ''));

        $base = ClienteLead::query();
        if ($eager) {
            $base->with(['cliente', 'tags']);
        } else {
            $base->with(['cliente', 'assistantLeads.assistant', 'tags']);
        }

        $query = $base->where('cliente_id', $cliente->id);

        if (!empty($assistantFilter)) {
            $allowedAssistantIds = Assistant::where('cliente_id', $cliente->id)
                ->whereIn('id', $assistantFilter)
                ->pluck('id')
                ->all();

            if (empty($allowedAssistantIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('assistantLeads', fn ($q) => $q->whereIn('assistant_id', $allowedAssistantIds));
            }
        }

        if (!empty($tagFilter)) {
            $allowedTagIds = Tag::where('user_id', $cliente->user_id)
                ->where('cliente_id', $cliente->id)
                ->whereIn('id', $tagFilter)
                ->pluck('id')
                ->all();

            if (empty($allowedTagIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $allowedTagIds));
            }
        }

        if ($dateStart) {
            $query->whereDate('created_at', '>=', $dateStart);
        }

        if ($dateEnd) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }

        if ($searchTerm !== '' && mb_strlen($searchTerm) >= 3) {
            $normalizedTerm = Str::ascii($searchTerm);
            $normalizedTerm = mb_strtolower($normalizedTerm);
            $termLower = mb_strtolower($searchTerm);
            $digits = preg_replace('/\D/', '', $searchTerm);
            $normalizedNameExpr = $this->normalizedColumnSql('name');

            $query->where(function ($subQuery) use ($termLower, $normalizedTerm, $digits, $normalizedNameExpr) {
                $subQuery->whereRaw('LOWER(name) LIKE ?', ["%{$termLower}%"])
                    ->orWhereRaw("{$normalizedNameExpr} LIKE ?", ["%{$normalizedTerm}%"]);

                if ($digits !== '') {
                    $subQuery->orWhere('phone', 'like', "%{$digits}%");
                }
            });
        }

        return [$assistantFilter, $tagFilter, $dateStart, $dateEnd, $query];
    }

    private function normalizedColumnSql(string $column): string
    {
        $expression = "LOWER({$column})";
        $replacements = [
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
        ];

        foreach ($replacements as $from => $to) {
            $expression = "REPLACE({$expression}, '{$from}', '{$to}')";
        }

        return $expression;
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
