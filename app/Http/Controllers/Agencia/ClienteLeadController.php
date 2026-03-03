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
use App\Support\PhoneNumberNormalizer;
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
    public function __construct(
        private readonly PhoneNumberNormalizer $phoneNumberNormalizer
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $clients = Cliente::where('user_id', $user->id)->orderBy('nome')->get();
        $assistants = Assistant::where('user_id', $user->id)->orderBy('name')->get();
        $tags = Tag::where('user_id', $user->id)->orderBy('name')->get();
        $sequences = Sequence::query()
            ->where('user_id', $user->id)
            ->with(['cliente:id,nome'])
            ->orderBy('name')
            ->get(['id', 'name', 'cliente_id']);
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

        [
            $clientAddFilter,
            $assistantAddFilter,
            $tagAddFilter,
            $dateStart,
            $dateEnd,
            $query,
            $lastMessageStart,
            $lastMessageEnd,
            $tagRemoveFilter,
            $clientRemoveFilter,
            $assistantRemoveFilter,
            $sequenceAddFilter,
            $sequenceRemoveFilter,
        ] = $this->buildFilteredQuery($request, $user);
        [$sortBy, $sortDir] = $this->resolveLeadSort($request);

        $leads = $query->orderBy($sortBy, $sortDir)->paginate(25)->withQueryString();

        if ($request->ajax()) {
            return view('agencia.conversas._table', compact('leads'));
        }

        return view('agencia.conversas.index', compact(
            'clients',
            'assistants',
            'tags',
            'sequences',
            'leads',
            'clientAddFilter',
            'clientRemoveFilter',
            'assistantAddFilter',
            'assistantRemoveFilter',
            'tagAddFilter',
            'tagRemoveFilter',
            'sequenceAddFilter',
            'sequenceRemoveFilter',
            'dateStart',
            'dateEnd',
            'lastMessageStart',
            'lastMessageEnd',
            'sortBy',
            'sortDir',
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

    public function destroyAll(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        [, , , , , $query] = $this->buildFilteredQuery($request, $user);

        $matchedCount = (clone $query)->count();
        $deletedCount = 0;

        if ($matchedCount > 0) {
            $deletedCount = (clone $query)->delete();
        }

        if ($matchedCount === 0) {
            $message = 'Nenhum lead encontrado com os filtros atuais.';
        } elseif ($deletedCount === 0) {
            $message = 'Nenhum lead foi excluido.';
        } else {
            $message = sprintf(
                'Exclusao concluida: %d lead(s) removido(s). Total considerado pelos filtros: %d.',
                $deletedCount,
                $matchedCount
            );
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'matched_count' => $matchedCount,
                'deleted_count' => $deletedCount,
            ]);
        }

        return redirect()
            ->route('agencia.conversas.index', $request->query())
            ->with('success', $message);
    }

    public function bulkUpdateTags(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'action' => ['required', 'in:add,remove'],
            'tag_ids' => ['required', 'array', 'min:1'],
            'tag_ids.*' => ['integer'],
        ]);

        $action = (string) ($validated['action'] ?? 'add');
        $requestedTagIds = collect((array) ($validated['tag_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($requestedTagIds)) {
            throw ValidationException::withMessages([
                'tag_ids' => ['Selecione ao menos uma tag.'],
            ]);
        }

        $ownedTagIds = $this->filterTags($requestedTagIds, (int) $user->id);
        $sortedRequestedTagIds = $requestedTagIds;
        sort($sortedRequestedTagIds);
        $sortedOwnedTagIds = $ownedTagIds;
        sort($sortedOwnedTagIds);

        if ($sortedRequestedTagIds !== $sortedOwnedTagIds) {
            throw ValidationException::withMessages([
                'tag_ids' => ['Tag(s) invalida(s) para atualizacao em lote.'],
            ]);
        }

        [, , , , , $query] = $this->buildFilteredQuery($request, $user);
        $matchedCount = (clone $query)->count();
        $affectedCount = 0;

        if ($matchedCount > 0) {
            $leadIdsQuery = (clone $query)->setEagerLoads([])->select('cliente_lead.id');

            if ($action === 'add') {
                $now = now();
                $leadIdsQuery->chunkById(1000, function ($leads) use (&$affectedCount, $ownedTagIds, $now) {
                    $rows = [];
                    foreach ($leads as $lead) {
                        $leadId = (int) ($lead->id ?? 0);
                        if ($leadId <= 0) {
                            continue;
                        }

                        foreach ($ownedTagIds as $tagId) {
                            $rows[] = [
                                'cliente_lead_id' => $leadId,
                                'tag_id' => (int) $tagId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    if (!empty($rows)) {
                        $affectedCount += (int) DB::table('cliente_lead_tag')->insertOrIgnore($rows);
                    }
                }, 'cliente_lead.id', 'id');
            } else {
                $leadIdsQuery->chunkById(1000, function ($leads) use (&$affectedCount, $ownedTagIds) {
                    $leadIds = collect($leads)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->filter(fn ($id) => $id > 0)
                        ->values()
                        ->all();

                    if (empty($leadIds)) {
                        return;
                    }

                    $affectedCount += (int) DB::table('cliente_lead_tag')
                        ->whereIn('cliente_lead_id', $leadIds)
                        ->whereIn('tag_id', $ownedTagIds)
                        ->delete();
                }, 'cliente_lead.id', 'id');
            }
        }

        if ($matchedCount === 0) {
            $message = 'Nenhum lead encontrado com os filtros atuais.';
        } elseif ($action === 'add' && $affectedCount === 0) {
            $message = 'As tags selecionadas já estavam vinculadas aos leads filtrados.';
        } elseif ($action === 'remove' && $affectedCount === 0) {
            $message = 'Nenhum vinculo de tag foi removido dos leads filtrados.';
        } elseif ($action === 'add') {
            $message = sprintf(
                'Atualizacao concluida: %d vinculo(s) de tag adicionado(s). Total considerado pelos filtros: %d lead(s).',
                $affectedCount,
                $matchedCount
            );
        } else {
            $message = sprintf(
                'Atualizacao concluida: %d vinculo(s) de tag removido(s). Total considerado pelos filtros: %d lead(s).',
                $affectedCount,
                $matchedCount
            );
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'matched_count' => $matchedCount,
                'affected_count' => $affectedCount,
            ]);
        }

        return redirect()
            ->route('agencia.conversas.index', $request->query())
            ->with('success', $message);
    }

    public function removeSequencesOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        [, , , , , $query] = $this->buildFilteredQuery($request, $user);

        $filteredLeadCount = (clone $query)->count();

        $leadIdsSubQuery = (clone $query)->select('cliente_lead.id');
        $sequences = SequenceChat::query()
            ->join('sequences', 'sequences.id', '=', 'sequence_chats.sequence_id')
            ->where('sequences.user_id', $user->id)
            ->whereIn('sequence_chats.cliente_lead_id', $leadIdsSubQuery)
            ->groupBy('sequence_chats.sequence_id', 'sequences.name')
            ->orderBy('sequences.name')
            ->get([
                'sequence_chats.sequence_id as id',
                'sequences.name',
                DB::raw('COUNT(*) as leads_count'),
            ]);

        return response()->json([
            'filtered_leads_count' => $filteredLeadCount,
            'total_sequences' => $sequences->count(),
            'sequences' => $sequences->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'leads_count' => (int) $item->leads_count,
            ])->values(),
        ]);
    }

    public function removeSequences(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'sequence_ids' => ['required', 'array', 'min:1'],
            'sequence_ids.*' => ['integer'],
        ]);

        $requestedSequenceIds = collect((array) ($validated['sequence_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($requestedSequenceIds)) {
            throw ValidationException::withMessages([
                'sequence_ids' => ['Selecione ao menos uma sequencia para remover.'],
            ]);
        }

        $ownedSequenceIds = Sequence::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $requestedSequenceIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        sort($requestedSequenceIds);
        $sortedOwnedSequenceIds = $ownedSequenceIds;
        sort($sortedOwnedSequenceIds);

        if ($requestedSequenceIds !== $sortedOwnedSequenceIds) {
            throw ValidationException::withMessages([
                'sequence_ids' => ['Sequencia(s) invalida(s) para remocao.'],
            ]);
        }

        [, , , , , $query] = $this->buildFilteredQuery($request, $user);
        $leadIdsSubQuery = fn () => (clone $query)->select('cliente_lead.id');

        $removeQuery = SequenceChat::query()
            ->whereIn('sequence_id', $ownedSequenceIds)
            ->whereIn('cliente_lead_id', $leadIdsSubQuery());

        $matchedCount = (clone $removeQuery)->count();
        $deletedCount = 0;
        if ($matchedCount > 0) {
            $deletedCount = (clone $removeQuery)->delete();
        }

        if ($matchedCount === 0) {
            $message = 'Nenhum lead filtrado estava vinculado as sequencias selecionadas.';
        } elseif ($deletedCount === 0) {
            $message = 'Nenhum vinculo de sequencia foi removido.';
        } else {
            $message = sprintf(
                'Remocao concluida: %d vinculo(s) de sequencia removido(s).',
                $deletedCount
            );
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'matched_count' => $matchedCount,
                'deleted_count' => $deletedCount,
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

        $rawPhone = $data['phone'] ?? null;
        $normalizedPhone = $this->phoneNumberNormalizer->normalizeLeadPhone($rawPhone);
        if ($rawPhone !== null && trim((string) $rawPhone) !== '' && $normalizedPhone === null) {
            return redirect()
                ->route('agencia.conversas.index')
                ->with('error', 'Número inválido. Informe o telefone com DDD ou DDI.');
        }

        if ($normalizedPhone) {
            $exists = ClienteLead::where('cliente_id', $cliente->id)
                ->where('phone', $normalizedPhone)
                ->exists();
            if ($exists) {
                return redirect()
                    ->route('agencia.conversas.index')
                    ->with('error', 'Este telefone já está cadastrado para o cliente selecionado.');
            }
        }

        $lead = DB::transaction(function () use ($request, $data, $cliente, $user, $customFields, $normalizedPhone) {
            $lead = ClienteLead::create([
                'cliente_id' => $cliente->id,
                'bot_enabled' => $request->boolean('bot_enabled'),
                'phone' => $normalizedPhone,
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

        $rawPhone = $data['phone'] ?? null;
        $normalizedPhone = $this->phoneNumberNormalizer->normalizeLeadPhone($rawPhone);
        if ($rawPhone !== null && trim((string) $rawPhone) !== '' && $normalizedPhone === null) {
            return redirect()
                ->route('agencia.conversas.index', $request->query())
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
                    ->route('agencia.conversas.index', $request->query())
                    ->with('error', 'Este telefone já está cadastrado para o cliente selecionado.');
            }
        }

        DB::transaction(function () use ($request, $data, $clienteLead, $cliente, $user, $customFields, $normalizedPhone) {
            $clienteLead->update([
                'cliente_id' => $cliente->id,
                'bot_enabled' => $request->boolean('bot_enabled'),
                'phone' => $normalizedPhone,
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
                ->where('is_active', true)
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
            ->where('is_active', true)
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
            ->where('is_active', true)
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

        $phone = $this->phoneNumberNormalizer->normalizeLeadPhone((string) ($clienteLead->phone ?? ''));
        if ($phone === null) {
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
            'has_header' => ['nullable', 'in:yes,no'],
            'bot_enabled' => ['nullable', 'boolean'],
            'column_mappings' => ['required', 'array', 'min:1'],
            'column_mappings.*.column_index' => ['required', 'integer', 'min:0'],
            'column_mappings.*.target' => ['nullable', 'string', 'max:120'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['integer'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $cliente = $this->resolveClienteForUser($validated['cliente_id'], $user->id);
        $delimiter = ($validated['delimiter'] ?? 'semicolon') === 'comma' ? ',' : ';';
        $hasHeader = ($validated['has_header'] ?? 'yes') === 'yes';
        $importBotEnabled = $request->boolean('bot_enabled', true);
        $tagIds = $this->filterTags((array) ($validated['tags'] ?? []), $user->id);
        $mapping = $this->parseImportColumnMappings(
            (array) ($validated['column_mappings'] ?? []),
            (int) $user->id,
            (int) $cliente->id
        );

        $file = $validated['csv_file'];
        $extension = strtolower($file->getClientOriginalExtension());

        $mapPhone = $mapping['phone_index'];
        $mapName = $mapping['name_index'];
        $customFieldIndexes = $mapping['custom_field_indexes'];

        $created = 0;
        $updated = 0;
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

            $firstDataIndex = $this->firstNonEmptyRowIndex($rows);
            if ($firstDataIndex === null) {
                return redirect()
                    ->route('agencia.conversas.index')
                    ->with('error', 'O XLSX está vazio.');
            }

            $dataStart = $hasHeader ? $firstDataIndex + 1 : $firstDataIndex;
            $rows = array_slice($rows, $dataStart);

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

                $phone = $this->phoneNumberNormalizer->normalizeLeadPhone($rawPhone);
                if (!$phone) {
                    $skippedInvalid++;
                    continue;
                }

                $customFields = $this->resolveImportedCustomFieldValues($row, $customFieldIndexes);
                $name = $mapName !== null ? $this->columnValue($row, $mapName) : null;
                $wasCreated = DB::transaction(function () use ($cliente, $importBotEnabled, $phone, $name, $tagIds, $customFields) {
                    $lead = ClienteLead::where('cliente_id', $cliente->id)
                        ->where('phone', $phone)
                        ->first();

                    if ($lead) {
                        $updatePayload = [
                            'bot_enabled' => $importBotEnabled,
                        ];

                        if ($name !== null) {
                            $updatePayload['name'] = $name;
                        }

                        $lead->update($updatePayload);

                        if (!empty($tagIds)) {
                            $lead->tags()->syncWithoutDetaching($tagIds);
                        }

                        if (!empty($customFields)) {
                            $this->upsertLeadCustomFields($lead, $customFields);
                        }

                        return false;
                    }

                    $lead = ClienteLead::create([
                        'cliente_id' => $cliente->id,
                        'bot_enabled' => $importBotEnabled,
                        'phone' => $phone,
                        'name' => $name,
                        'info' => null,
                    ]);

                    if (!empty($tagIds)) {
                        $lead->tags()->sync($tagIds);
                    }

                    if (!empty($customFields)) {
                        $this->syncLeadCustomFields($lead, $customFields);
                    }

                    return true;
                });

                if ($wasCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        } else {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                return redirect()
                    ->route('agencia.conversas.index')
                    ->with('error', 'Não foi possível abrir o arquivo CSV.');
            }

            if ($hasHeader) {
                $headerFound = false;
                while (($header = fgetcsv($handle, 0, $delimiter)) !== false) {
                    if ($this->rowIsEmpty($header)) {
                        continue;
                    }

                    $headerFound = true;
                    break;
                }

                if (!$headerFound) {
                    fclose($handle);
                    return redirect()
                        ->route('agencia.conversas.index')
                        ->with('error', 'O CSV está vazio.');
                }
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

                $phone = $this->phoneNumberNormalizer->normalizeLeadPhone($rawPhone);
                if (!$phone) {
                    $skippedInvalid++;
                    continue;
                }

                $customFields = $this->resolveImportedCustomFieldValues($row, $customFieldIndexes);
                $name = $mapName !== null ? $this->columnValue($row, $mapName) : null;
                $wasCreated = DB::transaction(function () use ($cliente, $importBotEnabled, $phone, $name, $tagIds, $customFields) {
                    $lead = ClienteLead::where('cliente_id', $cliente->id)
                        ->where('phone', $phone)
                        ->first();

                    if ($lead) {
                        $updatePayload = [
                            'bot_enabled' => $importBotEnabled,
                        ];

                        if ($name !== null) {
                            $updatePayload['name'] = $name;
                        }

                        $lead->update($updatePayload);

                        if (!empty($tagIds)) {
                            $lead->tags()->syncWithoutDetaching($tagIds);
                        }

                        if (!empty($customFields)) {
                            $this->upsertLeadCustomFields($lead, $customFields);
                        }

                        return false;
                    }

                    $lead = ClienteLead::create([
                        'cliente_id' => $cliente->id,
                        'bot_enabled' => $importBotEnabled,
                        'phone' => $phone,
                        'name' => $name,
                        'info' => null,
                    ]);

                    if (!empty($tagIds)) {
                        $lead->tags()->sync($tagIds);
                    }

                    if (!empty($customFields)) {
                        $this->syncLeadCustomFields($lead, $customFields);
                    }

                    return true;
                });

                if ($wasCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            fclose($handle);
        }

        $successMessage = "Importação concluída: {$created} registro(s) adicionado(s)";

        if ($updated > 0) {
            $successMessage .= ", {$updated} atualizado(s)";
        }

        $successMessage .= '.';

        if ($skippedInvalid > 0) {
            $successMessage .= " {$skippedInvalid} inválido(s) ignorado(s).";
        }

        $response = redirect()
            ->route('agencia.conversas.index')
            ->with('success', $successMessage);

        if ($skippedInvalid > 0) {
            $details = [];
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
            'has_header' => ['nullable', 'in:yes,no'],
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $file = $validated['csv_file'];
        $extension = strtolower($file->getClientOriginalExtension());
        $delimiter = ($validated['delimiter'] ?? 'semicolon') === 'comma' ? ',' : ';';
        $hasHeader = ($validated['has_header'] ?? 'yes') === 'yes';

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
            $firstNonEmptyIndex = $this->firstNonEmptyRowIndex($sheetRows);
            if ($firstNonEmptyIndex !== null) {
                if ($hasHeader) {
                    $headers = array_map('strval', array_values($sheetRows[$firstNonEmptyIndex]));
                    $headers = $this->normalizeHeaders($headers);

                    for ($index = $firstNonEmptyIndex + 1, $max = count($sheetRows); $index < $max; $index++) {
                        $row = is_array($sheetRows[$index]) ? $sheetRows[$index] : [];
                        if ($this->rowIsEmpty($row)) {
                            continue;
                        }

                        $rows[] = array_values($row);
                        break;
                    }
                } else {
                    $sampleRow = array_values($sheetRows[$firstNonEmptyIndex]);
                    $rows[] = $sampleRow;
                    $headers = $this->normalizeHeaders(array_fill(0, count($sampleRow), ''));
                }
            }
        } else {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle === false) {
                return response()->json(['headers' => [], 'rows' => [], 'is_xlsx' => false]);
            }

            if ($hasHeader) {
                while (($headerRow = fgetcsv($handle, 0, $delimiter)) !== false) {
                    if ($this->rowIsEmpty($headerRow)) {
                        continue;
                    }

                    $headers = $this->normalizeHeaders(array_map('strval', $headerRow));
                    break;
                }

                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    if ($this->rowIsEmpty($row)) {
                        continue;
                    }

                    $rows[] = $row;
                    break;
                }
            } else {
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    if ($this->rowIsEmpty($row)) {
                        continue;
                    }

                    $rows[] = $row;
                    $headers = $this->normalizeHeaders(array_fill(0, count($row), ''));
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

    private function filterClientes(array $clienteIds, int $userId): array
    {
        $clienteIds = array_values(array_filter($clienteIds, fn ($value) => $value !== '' && $value !== null));

        if (empty($clienteIds)) {
            return [];
        }

        return Cliente::where('user_id', $userId)->whereIn('id', $clienteIds)->pluck('id')->all();
    }

    private function filterAssistants(array $assistantIds, int $userId): array
    {
        $assistantIds = array_values(array_filter($assistantIds, fn ($value) => $value !== '' && $value !== null));

        if (empty($assistantIds)) {
            return [];
        }

        return Assistant::where('user_id', $userId)->whereIn('id', $assistantIds)->pluck('id')->all();
    }

    private function filterSequences(array $sequenceIds, int $userId): array
    {
        $sequenceIds = array_values(array_filter($sequenceIds, fn ($value) => $value !== '' && $value !== null));

        if (empty($sequenceIds)) {
            return [];
        }

        return Sequence::where('user_id', $userId)->whereIn('id', $sequenceIds)->pluck('id')->all();
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

    private function upsertLeadCustomFields(ClienteLead $lead, array $customFields): void
    {
        if (empty($customFields)) {
            return;
        }

        $fieldIds = array_map(fn (array $item) => (int) $item['field_id'], $customFields);
        $fieldIds = array_values(array_unique($fieldIds));

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

    private function parseImportColumnMappings(array $columnMappings, int $userId, int $clienteId): array
    {
        $normalizedByColumn = [];
        foreach ($columnMappings as $row) {
            if (!is_array($row)) {
                continue;
            }

            $columnIndex = (int) ($row['column_index'] ?? -1);
            $target = Str::lower(trim((string) ($row['target'] ?? 'ignore')));

            if ($columnIndex < 0) {
                continue;
            }

            if (array_key_exists($columnIndex, $normalizedByColumn)) {
                throw ValidationException::withMessages([
                    'column_mappings' => ['Cada coluna da planilha pode ser mapeada apenas uma vez.'],
                ]);
            }

            if ($target === '' || $target === 'ignore') {
                continue;
            }

            $normalizedByColumn[$columnIndex] = $target;
        }

        $phoneIndex = null;
        $nameIndex = null;
        $customFieldIndexes = [];
        $customFieldIds = [];

        foreach ($normalizedByColumn as $columnIndex => $target) {
            if ($target === 'phone') {
                if ($phoneIndex !== null) {
                    throw ValidationException::withMessages([
                        'column_mappings' => ['Somente uma coluna pode ser mapeada para telefone.'],
                    ]);
                }

                $phoneIndex = $columnIndex;
                continue;
            }

            if ($target === 'name') {
                if ($nameIndex !== null) {
                    throw ValidationException::withMessages([
                        'column_mappings' => ['Somente uma coluna pode ser mapeada para nome.'],
                    ]);
                }

                $nameIndex = $columnIndex;
                continue;
            }

            if (!Str::startsWith($target, 'custom_field:')) {
                throw ValidationException::withMessages([
                    'column_mappings' => ['Mapeamento inválido enviado para importação.'],
                ]);
            }

            $fieldId = (int) Str::after($target, 'custom_field:');
            if ($fieldId <= 0) {
                throw ValidationException::withMessages([
                    'column_mappings' => ['Mapeamento de campo personalizado inválido.'],
                ]);
            }

            if (in_array($fieldId, $customFieldIds, true)) {
                throw ValidationException::withMessages([
                    'column_mappings' => ['Não é permitido mapear o mesmo campo personalizado em mais de uma coluna.'],
                ]);
            }

            $customFieldIds[] = $fieldId;
            $customFieldIndexes[$columnIndex] = $fieldId;
        }

        if ($phoneIndex === null) {
            throw ValidationException::withMessages([
                'column_mappings' => ['Mapeie uma coluna da planilha para o campo telefone.'],
            ]);
        }

        if (!empty($customFieldIds)) {
            $allowedFieldIds = WhatsappCloudCustomField::query()
                ->where('user_id', $userId)
                ->whereIn('id', $customFieldIds)
                ->where(function ($query) use ($clienteId) {
                    $query->whereNull('cliente_id')
                        ->orWhere('cliente_id', $clienteId);
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            sort($allowedFieldIds);
            $sortedFieldIds = $customFieldIds;
            sort($sortedFieldIds);

            if ($allowedFieldIds !== $sortedFieldIds) {
                throw ValidationException::withMessages([
                    'column_mappings' => ['Há campo(s) personalizado(s) inválido(s) para o cliente selecionado.'],
                ]);
            }
        }

        return [
            'phone_index' => $phoneIndex,
            'name_index' => $nameIndex,
            'custom_field_indexes' => $customFieldIndexes,
        ];
    }

    private function resolveImportedCustomFieldValues(array $row, array $customFieldIndexes): array
    {
        $customFields = [];
        foreach ($customFieldIndexes as $columnIndex => $fieldId) {
            $value = $this->columnValue($row, (int) $columnIndex);
            if ($value === null) {
                continue;
            }

            $customFields[] = [
                'field_id' => (int) $fieldId,
                'value' => $value,
            ];
        }

        return $customFields;
    }

    private function buildFilteredQuery(Request $request, $user, bool $eager = false): array
    {
        $legacyClientFilter = (array) $request->input('cliente_id', []);
        $clientAddFilter = $this->filterClientes((array) $request->input('cliente_add', $legacyClientFilter), (int) $user->id);
        $clientRemoveFilter = $this->filterClientes((array) $request->input('cliente_remove', []), (int) $user->id);
        $clientRemoveFilter = array_values(array_diff($clientRemoveFilter, $clientAddFilter));

        $legacyAssistantFilter = (array) $request->input('assistant_id', []);
        $assistantAddFilter = $this->filterAssistants((array) $request->input('assistant_add', $legacyAssistantFilter), (int) $user->id);
        $assistantRemoveFilter = $this->filterAssistants((array) $request->input('assistant_remove', []), (int) $user->id);
        $assistantRemoveFilter = array_values(array_diff($assistantRemoveFilter, $assistantAddFilter));

        $legacyTagFilter = (array) $request->input('tags', []);
        $tagAddFilter = $this->filterTags((array) $request->input('tags_add', $legacyTagFilter), (int) $user->id);
        $tagRemoveFilter = $this->filterTags((array) $request->input('tags_remove', []), (int) $user->id);
        $tagRemoveFilter = array_values(array_diff($tagRemoveFilter, $tagAddFilter));

        $sequenceAddFilter = $this->filterSequences((array) $request->input('sequence_add', []), (int) $user->id);
        $sequenceRemoveFilter = $this->filterSequences((array) $request->input('sequence_remove', []), (int) $user->id);
        $sequenceRemoveFilter = array_values(array_diff($sequenceRemoveFilter, $sequenceAddFilter));
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');
        $lastMessageStart = $request->input('last_message_start');
        $lastMessageEnd = $request->input('last_message_end');
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

        if (!empty($clientAddFilter)) {
            $query->whereIn('cliente_id', $clientAddFilter);
        }

        if (!empty($clientRemoveFilter)) {
            $query->whereNotIn('cliente_id', $clientRemoveFilter);
        }

        if (!empty($assistantAddFilter)) {
            $query->whereHas('assistantLeads', fn ($q) => $q->whereIn('assistant_id', $assistantAddFilter));
        }

        if (!empty($assistantRemoveFilter)) {
            $query->whereDoesntHave('assistantLeads', fn ($q) => $q->whereIn('assistant_id', $assistantRemoveFilter));
        }

        if (!empty($tagAddFilter)) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagAddFilter));
        }

        if (!empty($tagRemoveFilter)) {
            $query->whereDoesntHave('tags', fn ($q) => $q->whereIn('tags.id', $tagRemoveFilter));
        }

        if (!empty($sequenceAddFilter)) {
            $query->whereHas('sequenceChats', fn ($q) => $q->whereIn('sequence_id', $sequenceAddFilter));
        }

        if (!empty($sequenceRemoveFilter)) {
            $query->whereDoesntHave('sequenceChats', fn ($q) => $q->whereIn('sequence_id', $sequenceRemoveFilter));
        }

        if ($dateStart) {
            $query->whereDate('created_at', '>=', $dateStart);
        }

        if ($dateEnd) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }

        if ($lastMessageStart) {
            $query->whereDate('updated_at', '>=', $lastMessageStart);
        }

        if ($lastMessageEnd) {
            $query->whereDate('updated_at', '<=', $lastMessageEnd);
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

        return [
            $clientAddFilter,
            $assistantAddFilter,
            $tagAddFilter,
            $dateStart,
            $dateEnd,
            $query,
            $lastMessageStart,
            $lastMessageEnd,
            $tagRemoveFilter,
            $clientRemoveFilter,
            $assistantRemoveFilter,
            $sequenceAddFilter,
            $sequenceRemoveFilter,
        ];
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

    private function resolveLeadSort(Request $request): array
    {
        $sortBy = (string) $request->query('sort_by', 'created_at');
        if (!in_array($sortBy, ['created_at', 'updated_at'], true)) {
            $sortBy = 'created_at';
        }

        $sortDir = strtolower((string) $request->query('sort_dir', 'desc'));
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        return [$sortBy, $sortDir];
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
