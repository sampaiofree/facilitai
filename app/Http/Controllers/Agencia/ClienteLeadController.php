<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\ScheduledMessage;
use App\Models\Sequence;
use App\Models\SequenceChat;
use App\Models\Tag;
use App\Models\WhatsappCloudConversationWindow;
use App\Models\WhatsappCloudCustomField;
use App\Models\WhatsappCloudTemplate;
use App\Jobs\ProcessIncomingMessageJob;
use App\Jobs\SyncCloudTemplateContextJob;
use App\Services\ScheduledMessageService;
use App\Services\WhatsappCloudConversationWindowService;
use App\Services\WhatsappCloudTemplateSendService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClienteLeadController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $clients = Cliente::where('user_id', $user->id)->orderBy('nome')->get();
        $assistants = Assistant::where('user_id', $user->id)->orderBy('name')->get();
        $tags = Tag::where('user_id', $user->id)->orderBy('name')->get();
        $leadCustomFieldsData = WhatsappCloudCustomField::query()
            ->where('user_id', $user->id)
            ->orderByRaw('CASE WHEN cliente_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('name')
            ->get(['id', 'cliente_id', 'name', 'label'])
            ->map(fn (WhatsappCloudCustomField $field) => [
                'id' => (int) $field->id,
                'cliente_id' => $field->cliente_id ? (int) $field->cliente_id : null,
                'name' => (string) $field->name,
                'label' => (string) ($field->label ?? ''),
            ])
            ->values();

        [$clientFilter, $assistantFilter, $tagFilter, $dateStart, $dateEnd, $query] = $this->buildFilteredQuery($request, $user);

        $leads = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        if ($request->ajax()) {
            return view('agencia.conversas._table', compact('leads'));
        }

        return view('agencia.conversas.index', compact(
            'clients',
            'assistants',
            'tags',
            'leads',
            'clientFilter',
            'assistantFilter',
            'tagFilter',
            'dateStart',
            'dateEnd',
            'leadCustomFieldsData',
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

    public function activateBotForAll(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        [, , , , , $query] = $this->buildFilteredQuery($request, $user);

        $matchedCount = (clone $query)->count();
        $updatedCount = 0;

        if ($matchedCount > 0) {
            $updatedCount = (clone $query)
                ->where(function ($builder) {
                    $builder->where('bot_enabled', false)
                        ->orWhereNull('bot_enabled');
                })
                ->update([
                    'bot_enabled' => true,
                ]);
        }

        if ($matchedCount === 0) {
            $message = 'Nenhum lead encontrado com os filtros atuais.';
        } elseif ($updatedCount === 0) {
            $message = 'Todos os leads filtrados já estavam com o bot ativado.';
        } else {
            $message = sprintf(
                'Bot ativado em %d lead(s). Total considerado pelos filtros: %d.',
                $updatedCount,
                $matchedCount
            );
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'matched_count' => $matchedCount,
                'updated_count' => $updatedCount,
            ]);
        }

        return redirect()
            ->route('agencia.conversas.index', $request->query())
            ->with('success', $message);
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
            'sequence_ids' => ['nullable', 'array'],
            'sequence_ids.*' => ['integer'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.field_id' => ['nullable', 'integer'],
            'custom_fields.*.value' => ['nullable', 'string', 'max:5000'],
        ]);

        $customFields = $this->resolveLeadCustomFieldsForPersist(
            (array) ($data['custom_fields'] ?? []),
            (int) $user->id,
            (int) $cliente->id
        );

        if (!empty($data['phone'])) {
            $exists = ClienteLead::where('cliente_id', $cliente->id)
                ->where('phone', $data['phone'])
                ->exists();
            if ($exists) {
                return redirect()
                    ->route('agencia.conversas.index')
                    ->with('error', 'Este telefone já está cadastrado para o cliente selecionado.');
            }
        }

        $lead = DB::transaction(function () use ($request, $data, $cliente, $user, $customFields) {
            $lead = ClienteLead::create([
                'cliente_id' => $cliente->id,
                'bot_enabled' => $request->boolean('bot_enabled'),
                'phone' => $data['phone'] ?? null,
                'name' => $data['name'] ?? null,
                'info' => $data['info'] ?? null,
            ]);

            $lead->tags()->sync($this->filterTags((array) ($data['tags'] ?? []), $user->id));
            $this->attachLeadToSequence($lead, $data['sequence_ids'] ?? [], $user->id, $request->has('sequence_ids'));
            $this->syncLeadCustomFields($lead, $customFields);

            return $lead;
        });

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
            'sequence_ids' => ['nullable', 'array'],
            'sequence_ids.*' => ['integer'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*.field_id' => ['nullable', 'integer'],
            'custom_fields.*.value' => ['nullable', 'string', 'max:5000'],
        ]);
        $customFields = $this->resolveLeadCustomFieldsForPersist(
            (array) ($data['custom_fields'] ?? []),
            (int) $user->id,
            (int) $cliente->id
        );

        DB::transaction(function () use ($request, $data, $clienteLead, $cliente, $user, $customFields) {
            $clienteLead->update([
                'cliente_id' => $cliente->id,
                'bot_enabled' => $request->boolean('bot_enabled'),
                'phone' => $data['phone'] ?? null,
                'name' => $data['name'] ?? null,
                'info' => $data['info'] ?? null,
            ]);

            $clienteLead->tags()->sync($this->filterTags((array) ($data['tags'] ?? []), $user->id));
            $this->attachLeadToSequence($clienteLead, $data['sequence_ids'] ?? [], $user->id, $request->has('sequence_ids'));
            $this->syncLeadCustomFields($clienteLead, $customFields);
        });

        return redirect()
            ->route('agencia.conversas.index', $request->query())
            ->with('success', 'Lead atualizado com sucesso.');
    }

    public function sendMessage(Request $request, ClienteLead $clienteLead): JsonResponse
    {
        $user = $request->user();
        abort_unless($clienteLead->cliente && $clienteLead->cliente->user_id === $user->id, 403);

        $mode = Str::lower(trim((string) $request->input('mode', 'text')));
        if (!in_array($mode, ['text', 'template_cloud'], true)) {
            return response()->json([
                'message' => 'Modo de envio inválido.',
            ], 422);
        }

        if ($mode === 'template_cloud') {
            return $this->sendCloudTemplateMessage($request, $clienteLead);
        }

        $data = $request->validate([
            'assistant_id' => ['nullable', 'integer'],
            'conexao_id' => ['nullable', 'integer'],
            'mensagem' => ['required', 'string', 'max:2000'],
            'scheduled_for' => ['nullable', 'string', 'max:50'],
        ]);

        $assistantId = (int) ($data['assistant_id'] ?? 0);
        $conexaoId = (int) ($data['conexao_id'] ?? 0);

        $selectedConexao = null;
        if ($conexaoId > 0) {
            $selectedConexao = Conexao::query()
                ->whereKey($conexaoId)
                ->whereNull('deleted_at')
                ->whereHas('cliente', fn ($query) => $query->where('user_id', $user->id))
                ->first();

            if (!$selectedConexao) {
                return response()->json([
                    'message' => 'Conexao selecionada nao encontrada.',
                ], 422);
            }

            if ((int) $selectedConexao->cliente_id !== (int) $clienteLead->cliente_id) {
                return response()->json([
                    'message' => 'Conexao selecionada nao pertence ao cliente deste lead.',
                ], 422);
            }

            $assistantFromConexao = (int) ($selectedConexao->assistant_id ?? 0);
            if ($assistantFromConexao <= 0) {
                return response()->json([
                    'message' => 'Conexao selecionada sem assistente vinculado.',
                ], 422);
            }

            if ($assistantId > 0 && $assistantId !== $assistantFromConexao) {
                return response()->json([
                    'message' => 'Assistente informado nao corresponde ao assistente da conexao.',
                ], 422);
            }

            $assistantId = $assistantFromConexao;
        }

        if ($assistantId <= 0) {
            return response()->json([
                'message' => 'Selecione um assistente ou uma conexao para enviar a mensagem.',
            ], 422);
        }

        $assistant = Assistant::query()
            ->where('user_id', $user->id)
            ->find($assistantId);

        if (!$assistant) {
            return response()->json([
                'message' => 'Assistente selecionado nao encontrado.',
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
            $assistantId,
            (int) $user->id,
            $selectedConexao?->id
        );
        if (!$context['ok']) {
            return response()->json([
                'message' => $context['message'] ?? 'Nao foi possivel validar o contexto de envio.',
            ], 422);
        }

        /** @var Conexao $conexao */
        $conexao = $context['conexao'];
        /** @var string $phone */
        $phone = $context['phone'];

        $timezone = $scheduledMessageService->resolveTimezoneForUser($user);
        $scheduledForRaw = trim((string) ($data['scheduled_for'] ?? ''));

        if ($this->isWhatsappCloudConexao($conexao)) {
            if ($scheduledForRaw !== '') {
                return response()->json([
                    'message' => 'Agendamento de texto livre não é suportado para WhatsApp Cloud. Use envio imediato ou template.',
                ], 422);
            }

            $isInsideWindow = app(WhatsappCloudConversationWindowService::class)
                ->isInsideWindow((int) $clienteLead->id, (int) $conexao->id);

            if (!$isInsideWindow) {
                return response()->json([
                    'message' => 'Esta conversa está fora da janela de 24h. Use um modelo da WhatsApp Cloud.',
                ], 422);
            }
        }

        if ($scheduledForRaw !== '') {
            $scheduledForUtc = $scheduledMessageService->parseScheduledForToUtc($scheduledForRaw, $timezone);
            if (!$scheduledForUtc) {
                return response()->json([
                    'message' => 'Data/hora de agendamento invalida.',
                ], 422);
            }

            $nowUtc = Carbon::now('UTC');
            if ($scheduledForUtc->lte($nowUtc)) {
                return response()->json([
                    'message' => 'Agendamento deve ser uma data futura.',
                ], 422);
            }

            $maxUtc = Carbon::now($timezone)->addDays(90)->setTimezone('UTC');
            if ($scheduledForUtc->gt($maxUtc)) {
                return response()->json([
                    'message' => 'O limite maximo para agendamento e de 90 dias.',
                ], 422);
            }

            $scheduledMessage = ScheduledMessage::create([
                'cliente_lead_id' => $clienteLead->id,
                'assistant_id' => $assistantId,
                'conexao_id' => $conexao->id,
                'mensagem' => $mensagem,
                'scheduled_for' => $scheduledForUtc,
                'status' => 'pending',
                'event_id' => sprintf('scheduled:lead:%d:%s', $clienteLead->id, (string) Str::uuid()),
                'created_by_user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Mensagem agendada com sucesso.',
                'scheduled_message_id' => $scheduledMessage->id,
                'scheduled_for' => $scheduledForUtc->toIso8601String(),
                'timezone' => $timezone,
            ]);
        }

        $agoraUtc = Carbon::now('UTC');
        $eventId = sprintf(
            'manual:lead:%d:assistant:%d:ts:%d',
            $clienteLead->id,
            $assistantId,
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

        ProcessIncomingMessageJob::dispatch($conexao->id, $clienteLead->id, $payload)
            ->onQueue('processarconversa');

        if ($this->isWhatsappCloudConexao($conexao)) {
            app(WhatsappCloudConversationWindowService::class)
                ->touchOutbound((int) $clienteLead->id, (int) $conexao->id, $agoraUtc);
        }

        return response()->json([
            'message' => 'Mensagem enviada para a fila.',
        ]);
    }

    public function cloudSendContext(Request $request, ClienteLead $clienteLead): JsonResponse
    {
        $user = $request->user();
        abort_unless($clienteLead->cliente && $clienteLead->cliente->user_id === $user->id, 403);

        $conexoes = Conexao::query()
            ->where('cliente_id', $clienteLead->cliente_id)
            ->whereNull('deleted_at')
            ->whereNotNull('whatsapp_cloud_account_id')
            ->whereHas('whatsappApi', fn ($query) => $query->where('slug', 'whatsapp_cloud'))
            ->orderBy('name')
            ->get(['id', 'name', 'whatsapp_cloud_account_id']);

        $accountIds = $conexoes->pluck('whatsapp_cloud_account_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $templates = WhatsappCloudTemplate::query()
            ->where('user_id', $user->id)
            ->whereIn('whatsapp_cloud_account_id', $accountIds)
            ->where(function ($query) use ($clienteLead) {
                $query->whereNull('conexao_id')
                    ->orWhereHas('conexao', fn ($subQuery) => $subQuery
                        ->whereNull('deleted_at')
                        ->where('cliente_id', $clienteLead->cliente_id));
            })
            ->orderBy('title')
            ->orderBy('template_name')
            ->get([
                'id',
                'conexao_id',
                'whatsapp_cloud_account_id',
                'title',
                'template_name',
                'language_code',
                'status',
                'variables',
            ]);

        $customFields = WhatsappCloudCustomField::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($clienteLead) {
                $query->whereNull('cliente_id')
                    ->orWhere('cliente_id', $clienteLead->cliente_id);
            })
            ->orderByRaw('CASE WHEN cliente_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('name')
            ->get(['id', 'name', 'label', 'sample_value', 'cliente_id']);

        $customFieldLeadValues = $clienteLead->customFieldValues()
            ->whereIn('whatsapp_cloud_custom_field_id', $customFields->pluck('id')->all())
            ->get(['whatsapp_cloud_custom_field_id', 'value'])
            ->mapWithKeys(fn ($row) => [
                (int) $row->whatsapp_cloud_custom_field_id => trim((string) ($row->value ?? '')),
            ])
            ->all();

        $fieldMap = [];
        foreach ($customFields as $field) {
            if (!array_key_exists($field->name, $fieldMap)) {
                $fieldMap[$field->name] = [
                    'name' => $field->name,
                    'label' => $field->label,
                    'sample_value' => $field->sample_value,
                    'lead_value' => $customFieldLeadValues[(int) $field->id] ?? '',
                    'cliente_id' => $field->cliente_id,
                ];
            }
        }

        $windows = WhatsappCloudConversationWindow::query()
            ->where('cliente_lead_id', $clienteLead->id)
            ->whereIn('conexao_id', $conexoes->pluck('id')->all())
            ->get()
            ->keyBy('conexao_id');

        $nowUtc = Carbon::now('UTC');

        return response()->json([
            'connections' => $conexoes->map(function (Conexao $conexao) use ($windows, $nowUtc) {
                /** @var WhatsappCloudConversationWindow|null $window */
                $window = $windows->get($conexao->id);
                $lastInboundAt = $window?->last_inbound_at?->copy()->setTimezone('UTC');
                $expiresAt = $lastInboundAt?->copy()->addHours(24);
                $isOpen = $expiresAt?->gt($nowUtc) ?? false;

                return [
                    'id' => (int) $conexao->id,
                    'name' => (string) $conexao->name,
                    'whatsapp_cloud_account_id' => $conexao->whatsapp_cloud_account_id ? (int) $conexao->whatsapp_cloud_account_id : null,
                    'window' => [
                        'is_open' => $isOpen,
                        'last_inbound_at' => $lastInboundAt?->toIso8601String(),
                        'expires_at' => $expiresAt?->toIso8601String(),
                        'last_inbound_at_label' => $lastInboundAt?->setTimezone(config('app.timezone', 'America/Sao_Paulo'))->format('d/m/Y H:i'),
                        'expires_at_label' => $expiresAt?->setTimezone(config('app.timezone', 'America/Sao_Paulo'))->format('d/m/Y H:i'),
                    ],
                ];
            })->values(),
            'templates' => $templates->map(function (WhatsappCloudTemplate $template) use ($fieldMap) {
                $variables = collect((array) ($template->variables ?? []))
                    ->map(fn ($value) => trim((string) $value))
                    ->filter()
                    ->unique()
                    ->values()
                    ->map(function (string $name) use ($fieldMap) {
                        $field = $fieldMap[$name] ?? null;

                        return [
                            'name' => $name,
                            'label' => is_array($field) ? ($field['label'] ?: null) : null,
                            'sample_value' => is_array($field)
                                ? (($field['lead_value'] !== '' ? $field['lead_value'] : ($field['sample_value'] ?? '')) ?: null)
                                : null,
                        ];
                    })
                    ->values();

                return [
                    'id' => (int) $template->id,
                    'title' => (string) ($template->title ?: $template->template_name),
                    'template_name' => (string) $template->template_name,
                    'language_code' => (string) $template->language_code,
                    'status' => Str::upper(trim((string) $template->status)),
                    'conexao_id' => $template->conexao_id ? (int) $template->conexao_id : null,
                    'whatsapp_cloud_account_id' => (int) $template->whatsapp_cloud_account_id,
                    'variables' => $variables,
                ];
            })->values(),
        ]);
    }

    private function sendCloudTemplateMessage(Request $request, ClienteLead $clienteLead): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'conexao_id' => ['required', 'integer'],
            'template_id' => ['required', 'integer'],
            'template_variables' => ['nullable', 'array'],
            'template_variables.*' => ['nullable', 'string', 'max:1000'],
            'scheduled_for' => ['nullable', 'string', 'max:50'],
        ]);

        $scheduledFor = trim((string) ($data['scheduled_for'] ?? ''));
        if ($scheduledFor !== '') {
            return response()->json([
                'message' => 'Agendamento de template Cloud será adicionado em uma próxima etapa.',
            ], 422);
        }

        $conexao = Conexao::query()
            ->with(['whatsappApi', 'whatsappCloudAccount'])
            ->whereKey((int) $data['conexao_id'])
            ->whereNull('deleted_at')
            ->whereHas('cliente', fn ($query) => $query->where('user_id', $user->id))
            ->first();

        if (!$conexao) {
            return response()->json([
                'message' => 'Conexao selecionada nao encontrada.',
            ], 422);
        }

        if ((int) $conexao->cliente_id !== (int) $clienteLead->cliente_id) {
            return response()->json([
                'message' => 'Conexao selecionada nao pertence ao cliente deste lead.',
            ], 422);
        }

        if (!$this->isWhatsappCloudConexao($conexao)) {
            return response()->json([
                'message' => 'A conexao selecionada nao e do tipo WhatsApp Cloud.',
            ], 422);
        }

        $accountId = (int) ($conexao->whatsapp_cloud_account_id ?? 0);
        if ($accountId <= 0) {
            return response()->json([
                'message' => 'Conexao Cloud sem conta vinculada.',
            ], 422);
        }

        $template = WhatsappCloudTemplate::query()
            ->where('user_id', $user->id)
            ->find((int) $data['template_id']);

        if (!$template) {
            return response()->json([
                'message' => 'Modelo selecionado nao encontrado.',
            ], 422);
        }

        $templateStatus = Str::upper(trim((string) ($template->status ?? '')));
        if (!in_array($templateStatus, ['APPROVED', 'ACTIVE'], true)) {
            return response()->json([
                'message' => 'Somente modelos aprovados podem ser enviados fora da janela de 24h.',
            ], 422);
        }

        if ((int) $template->whatsapp_cloud_account_id !== $accountId) {
            return response()->json([
                'message' => 'O modelo selecionado nao pertence a conta Cloud desta conexao.',
            ], 422);
        }

        if ($template->conexao_id !== null && (int) $template->conexao_id !== (int) $conexao->id) {
            return response()->json([
                'message' => 'O modelo selecionado esta restrito a outra conexao.',
            ], 422);
        }

        $phone = preg_replace('/\D/', '', (string) ($clienteLead->phone ?? ''));
        if (!is_string($phone) || $phone === '' || strlen($phone) < 8) {
            return response()->json([
                'message' => 'Lead sem telefone valido para envio.',
            ], 422);
        }

        /** @var WhatsappCloudTemplateSendService $templateSendService */
        $templateSendService = app(WhatsappCloudTemplateSendService::class);
        $sendResult = $templateSendService->sendToLead([
            'user_id' => (int) $user->id,
            'conexao' => $conexao,
            'template' => $template,
            'lead' => $clienteLead,
            'template_variables' => (array) ($data['template_variables'] ?? []),
        ]);

        if (!$sendResult['ok']) {
            return response()->json([
                'message' => (string) ($sendResult['message'] ?? 'Falha ao enviar template pela WhatsApp Cloud API.'),
            ], 422);
        }

        $response = is_array($sendResult['response'] ?? null)
            ? $sendResult['response']
            : [];
        $variableValues = is_array($sendResult['resolved_variables'] ?? null)
            ? $sendResult['resolved_variables']
            : [];

        app(WhatsappCloudConversationWindowService::class)->touchOutbound(
            (int) $clienteLead->id,
            (int) $conexao->id,
            Carbon::now('UTC')
        );

        // O sync de contexto é assíncrono para não bloquear o envio do template.
        // Mesmo que o job falhe, o template já foi enviado ao WhatsApp.
        try {
            $metaMessageId = trim((string) data_get($response, 'body.messages.0.id', ''));

            SyncCloudTemplateContextJob::dispatch([
                'conexao_id' => (int) $conexao->id,
                'cliente_lead_id' => (int) $clienteLead->id,
                'template_id' => (int) $template->id,
                'template_variables' => $variableValues,
                'meta_message_id' => $metaMessageId !== '' ? $metaMessageId : null,
                'sent_at' => Carbon::now('UTC')->toIso8601String(),
            ])->onQueue('processarconversa');
        } catch (\Throwable $exception) {
            Log::channel('process_job')->warning('Falha ao enfileirar SyncCloudTemplateContextJob.', [
                'conexao_id' => (int) $conexao->id,
                'cliente_lead_id' => (int) $clienteLead->id,
                'template_id' => (int) $template->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Modelo enviado com sucesso.',
        ]);
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

    public function scheduledMessages(Request $request, ClienteLead $clienteLead): JsonResponse
    {
        $user = $request->user();
        abort_unless($clienteLead->cliente && $clienteLead->cliente->user_id === $user->id, 403);

        $timezone = app(ScheduledMessageService::class)->resolveTimezoneForUser($user);

        $query = ScheduledMessage::query()
            ->with('assistant:id,name')
            ->where('cliente_lead_id', $clienteLead->id)
            ->where('status', 'pending')
            ->orderBy('scheduled_for');

        $pendingCount = (clone $query)->count();
        $next = (clone $query)->first();
        $items = (clone $query)->limit(5)->get();

        return response()->json([
            'pending_count' => $pendingCount,
            'timezone' => $timezone,
            'next_scheduled_for' => $this->formatScheduledMessageIso($next, 'scheduled_for'),
            'next_scheduled_for_label' => $this->formatScheduledMessageDate($next, 'scheduled_for', $timezone),
            'items' => $items->map(function (ScheduledMessage $scheduledMessage) use ($timezone) {
                return [
                    'id' => $scheduledMessage->id,
                    'assistant' => $scheduledMessage->assistant?->name ?? '-',
                    'scheduled_for' => $this->formatScheduledMessageIso($scheduledMessage, 'scheduled_for'),
                    'scheduled_for_label' => $this->formatScheduledMessageDate($scheduledMessage, 'scheduled_for', $timezone),
                    'mensagem_preview' => Str::limit($scheduledMessage->mensagem, 90),
                    'status' => $scheduledMessage->status,
                    'can_cancel' => $scheduledMessage->status === 'pending',
                ];
            })->values(),
        ]);
    }

    public function cancelScheduledMessage(Request $request, ScheduledMessage $scheduledMessage): JsonResponse
    {
        $user = $request->user();
        $scheduledMessage->loadMissing('clienteLead.cliente');

        abort_unless(
            $scheduledMessage->clienteLead?->cliente
            && (int) $scheduledMessage->clienteLead->cliente->user_id === (int) $user->id,
            403
        );

        if ($scheduledMessage->status !== 'pending') {
            return response()->json([
                'message' => 'Somente agendamentos pendentes podem ser cancelados.',
            ], 422);
        }

        $scheduledMessage->update([
            'status' => 'canceled',
            'canceled_at' => Carbon::now('UTC'),
            'error_message' => null,
        ]);

        return response()->json([
            'message' => 'Agendamento cancelado com sucesso.',
        ]);
    }

    private function scheduledMessageUtcValue(?ScheduledMessage $scheduledMessage, string $column): ?Carbon
    {
        if (!$scheduledMessage) {
            return null;
        }

        $raw = $scheduledMessage->getRawOriginal($column);
        if (is_string($raw) && trim($raw) !== '') {
            try {
                return Carbon::parse($raw, 'UTC');
            } catch (\Throwable) {
                // fallback below
            }
        }

        $value = $scheduledMessage->getAttribute($column);
        if ($value instanceof Carbon) {
            return $value->copy()->setTimezone('UTC');
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->setTimezone('UTC');
        }

        return null;
    }

    private function formatScheduledMessageDate(?ScheduledMessage $scheduledMessage, string $column, string $timezone): ?string
    {
        $value = $this->scheduledMessageUtcValue($scheduledMessage, $column);
        if (!$value) {
            return null;
        }

        return $value->setTimezone($timezone)->format('d/m/Y H:i');
    }

    private function formatScheduledMessageIso(?ScheduledMessage $scheduledMessage, string $column): ?string
    {
        $value = $this->scheduledMessageUtcValue($scheduledMessage, $column);
        if (!$value) {
            return null;
        }

        return $value->toIso8601String();
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
        $skippedDuplicate = 0;
        $skippedInvalid = 0;

        if ($extension === 'xlsx') {
            $rows = $this->readXlsxRows($file->getRealPath());
            if ($rows === null) {
                return redirect()
                    ->route('agencia.conversas.index')
                    ->with('error', 'Não foi possível ler o XLSX enviado. Confirme se o arquivo é um .xlsx válido (Excel 2007+).');
            }
            if (empty($rows)) {
                return redirect()
                    ->route('agencia.conversas.index')
                    ->with('error', 'O XLSX está vazio.');
            }

            $headerIndex = $this->firstNonEmptyRowIndex($rows);
            if ($headerIndex === null) {
                return redirect()
                    ->route('agencia.conversas.index')
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
            ->route('agencia.conversas.index')
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
        $user = $request->user();
        $format = strtolower($request->query('format', 'csv'));
        if (!in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            $format = 'csv';
        }

        [, , , , , $query] = $this->buildFilteredQuery($request, $user, eager: true);
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

    private function resolveLeadCustomFieldsForPersist(array $customFields, int $userId, int $clienteId): array
    {
        $normalized = [];
        foreach ($customFields as $row) {
            if (!is_array($row)) {
                continue;
            }

            $fieldId = (int) ($row['field_id'] ?? 0);
            $value = trim((string) ($row['value'] ?? ''));

            if ($fieldId <= 0 && $value === '') {
                continue;
            }

            if ($fieldId <= 0 && $value !== '') {
                throw ValidationException::withMessages([
                    'custom_fields' => ['Selecione o campo personalizado antes de informar um valor.'],
                ]);
            }

            if ($value === '') {
                continue;
            }

            $normalized[] = [
                'field_id' => $fieldId,
                'value' => $value,
            ];
        }

        if (empty($normalized)) {
            return [];
        }

        $fieldIds = array_values(array_unique(array_map(
            fn (array $item) => (int) $item['field_id'],
            $normalized
        )));

        if (count($fieldIds) !== count($normalized)) {
            throw ValidationException::withMessages([
                'custom_fields' => ['Não é permitido repetir o mesmo campo personalizado para o lead.'],
            ]);
        }

        $allowedFieldIds = WhatsappCloudCustomField::query()
            ->where('user_id', $userId)
            ->whereIn('id', $fieldIds)
            ->where(function ($query) use ($clienteId) {
                $query->whereNull('cliente_id')
                    ->orWhere('cliente_id', $clienteId);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        sort($allowedFieldIds);
        $sortedFieldIds = $fieldIds;
        sort($sortedFieldIds);

        if ($allowedFieldIds !== $sortedFieldIds) {
            throw ValidationException::withMessages([
                'custom_fields' => ['Há campo(s) personalizado(s) inválido(s) para este cliente.'],
            ]);
        }

        return $normalized;
    }

    private function syncLeadCustomFields(ClienteLead $lead, array $customFields): void
    {
        if (empty($customFields)) {
            $lead->customFieldValues()->delete();
            return;
        }

        $fieldIds = array_map(fn (array $item) => (int) $item['field_id'], $customFields);
        $fieldIds = array_values(array_unique($fieldIds));

        $lead->customFieldValues()
            ->whereNotIn('whatsapp_cloud_custom_field_id', $fieldIds)
            ->delete();

        $existing = $lead->customFieldValues()
            ->whereIn('whatsapp_cloud_custom_field_id', $fieldIds)
            ->get()
            ->keyBy('whatsapp_cloud_custom_field_id');

        foreach ($customFields as $item) {
            $fieldId = (int) $item['field_id'];
            $value = $item['value'];
            $valueRecord = $existing->get($fieldId);

            if ($valueRecord) {
                if ((string) ($valueRecord->value ?? '') !== $value) {
                    $valueRecord->update(['value' => $value]);
                }
                continue;
            }

            $lead->customFieldValues()->create([
                'whatsapp_cloud_custom_field_id' => $fieldId,
                'value' => $value,
            ]);
        }
    }

    private function buildFilteredQuery(Request $request, $user, bool $eager = false): array
    {
        $clientFilter = array_values(array_filter((array) $request->input('cliente_id', []), fn ($value) => $value !== '' && $value !== null));
        $assistantFilter = array_values(array_filter((array) $request->input('assistant_id', []), fn ($value) => $value !== '' && $value !== null));
        $tagFilter = array_values(array_filter((array) $request->input('tags', []), fn ($value) => $value !== '' && $value !== null));
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');
        $searchTerm = trim((string) $request->input('q', ''));

        $base = ClienteLead::query();
        if ($eager) {
            $base->with(['cliente', 'tags']);
        } else {
            $base->with([
                'cliente',
                'assistantLeads.assistant',
                'tags',
                'sequenceChats.sequence',
                'customFieldValues.customField',
            ]);
        }

        $query = $base->whereHas('cliente', fn ($q) => $q->where('user_id', $user->id));

        if (!empty($clientFilter)) {
            $query->whereIn('cliente_id', $clientFilter);
        }

        if (!empty($assistantFilter)) {
            $query->whereHas('assistantLeads', fn ($q) => $q->whereIn('assistant_id', $assistantFilter));
        }

        if (!empty($tagFilter)) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagFilter));
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

        return [$clientFilter, $assistantFilter, $tagFilter, $dateStart, $dateEnd, $query];
    }

    private function attachLeadToSequence(ClienteLead $lead, array $sequenceIds, int $userId, bool $sync = false): void
    {
        $sequenceIds = array_values(array_filter($sequenceIds, fn ($value) => $value !== '' && $value !== null));
        if (empty($sequenceIds) && !$sync) {
            return;
        }

        $ownedSequenceIds = Sequence::where('user_id', $userId)
            ->where('cliente_id', $lead->cliente_id)
            ->pluck('id')
            ->all();

        if ($sync && !empty($ownedSequenceIds)) {
            $removeIds = array_values(array_diff($ownedSequenceIds, $sequenceIds));
            if (!empty($removeIds)) {
                SequenceChat::where('cliente_lead_id', $lead->id)
                    ->whereIn('sequence_id', $removeIds)
                    ->delete();
            }
        }

        if (empty($sequenceIds)) {
            return;
        }

        $sequences = Sequence::where('user_id', $userId)
            ->where('cliente_id', $lead->cliente_id)
            ->where('active', true)
            ->whereIn('id', $sequenceIds)
            ->get();

        if ($sequences->isEmpty()) {
            return;
        }

        foreach ($sequences as $sequence) {
            $existing = SequenceChat::where('sequence_id', $sequence->id)
                ->where('cliente_lead_id', $lead->id)
                ->whereIn('status', ['em_andamento', 'concluida', 'pausada'])
                ->first();

            if ($existing) {
                continue;
            }

            SequenceChat::create([
                'sequence_id' => $sequence->id,
                'cliente_lead_id' => $lead->id,
                'status' => 'em_andamento',
                'iniciado_em' => now('America/Sao_Paulo'),
                'proximo_envio_em' => null,
                'criado_por' => 'manual',
            ]);
        }
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
        $pdf = Pdf::loadView('agencia.conversas.export-pdf', [
            'headers' => $headers,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        $fileName = 'conversas_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }
}
