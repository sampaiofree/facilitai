<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteGrupoConjuntoMensagemJob;
use App\Models\Conexao;
use App\Models\GrupoConjunto;
use App\Models\GrupoConjuntoMensagem;
use App\Services\ScheduledMessageService;
use App\Services\UazapiGruposService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AgenciaGrupoController extends Controller
{
    public function __construct(
        protected UazapiGruposService $uazapiGruposService,
        protected ScheduledMessageService $scheduledMessageService
    ) {}

    public function index(Request $request): View
    {
        $userId = $this->resolveGrupoOwnerUserId($request);
        $clienteScopeId = $this->resolveGrupoClienteScopeId($request);
        $requestedConjuntoId = $request->filled('conjunto_id') ? (int) $request->input('conjunto_id') : null;
        $activeTab = $request->input('tab') === 'messages' ? 'messages' : 'groups';

        $conjuntos = $this->scopedConjuntosQuery($userId, $clienteScopeId)
            ->with([
                'conexao:id,name,cliente_id',
                'conexao.cliente:id,nome',
                'items:id,grupo_conjunto_id,group_jid,group_name',
            ])
            ->withCount('items')
            ->orderBy('name')
            ->get();

        $selectedConjunto = null;
        if ($requestedConjuntoId) {
            $selectedConjunto = $conjuntos->firstWhere('id', $requestedConjuntoId);
        }

        if (! $selectedConjunto) {
            $selectedConjunto = $conjuntos->first();
        }

        $conexoes = $this->queryAllowedConnections($userId, $clienteScopeId)
            ->select('id', 'name', 'cliente_id')
            ->with('cliente:id,nome')
            ->orderBy('name')
            ->get();

        $timezone = $this->resolveGrupoTimezone($request);
        $mensagens = collect();

        if ($selectedConjunto) {
            $mensagens = GrupoConjuntoMensagem::query()
                ->where('user_id', $userId)
                ->where('grupo_conjunto_id', (int) $selectedConjunto->id)
                ->orderByDesc('created_at')
                ->get();

            $mensagens->transform(function (GrupoConjuntoMensagem $mensagem) use ($timezone) {
                $mensagem->scheduled_for_label = $this->formatUtcColumn($mensagem, 'scheduled_for', $timezone);
                $mensagem->scheduled_for_input = $this->formatUtcColumn($mensagem, 'scheduled_for', $timezone, 'Y-m-d\\TH:i');
                $mensagem->created_at_label = $this->formatUtcColumn($mensagem, 'created_at', $timezone);
                $mensagem->sent_at_label = $this->formatUtcColumn($mensagem, 'sent_at', $timezone);
                $mensagem->failed_at_label = $this->formatUtcColumn($mensagem, 'failed_at', $timezone);

                return $mensagem;
            });
        }

        return view('shared.grupos.index', [
            'conjuntos' => $conjuntos,
            'selectedConjunto' => $selectedConjunto,
            'conexoes' => $conexoes,
            'activeTab' => $activeTab,
            'timezone' => $timezone,
            'mensagens' => $mensagens,
            'layout' => $this->grupoViewLayout(),
            'routePrefix' => $this->grupoRoutePrefix(),
            'showClienteInfo' => $this->showGrupoClienteInfo(),
            'showFailureDetails' => $this->showGrupoFailureDetails(),
            'showGroupNameSequence' => $this->showGroupNameSequence(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->input('grupo_conjunto_id') === '') {
            $request->merge(['grupo_conjunto_id' => null]);
        }

        $userId = $this->resolveGrupoOwnerUserId($request);
        $clienteScopeId = $this->resolveGrupoClienteScopeId($request);
        $grupoConjuntoId = $request->filled('grupo_conjunto_id') ? (int) $request->input('grupo_conjunto_id') : null;

        $nameUniqueRule = Rule::unique('grupo_conjuntos', 'name')->where(function ($query) use ($request, $userId) {
            $query
                ->where('user_id', $userId)
                ->where('conexao_id', (int) $request->input('conexao_id'));
        });

        if ($grupoConjuntoId) {
            $nameUniqueRule->ignore($grupoConjuntoId);
        }

        $data = $request->validate([
            'grupo_conjunto_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255', $nameUniqueRule],
            'conexao_id' => ['required', 'integer', 'exists:conexoes,id'],
            'groups' => ['required', 'array', 'min:1'],
            'groups.*.jid' => ['required', 'string', 'regex:/^[0-9]+@g\.us$/'],
            'groups.*.name' => ['nullable', 'string', 'max:255'],
        ]);

        $conexao = $this->resolveOwnedConnection($userId, (int) $data['conexao_id'], $clienteScopeId);
        if (! $conexao) {
            abort(404);
        }

        $groups = $this->normalizeInputGroups((array) $data['groups']);
        if ($groups === []) {
            throw ValidationException::withMessages([
                'groups' => ['Selecione ao menos um grupo.'],
            ]);
        }

        $isUpdate = ! empty($data['grupo_conjunto_id']);
        $savedConjuntoId = null;

        DB::transaction(function () use ($data, $groups, $userId, $clienteScopeId, $isUpdate, $grupoConjuntoId, $conexao, &$savedConjuntoId): void {
            $conjunto = $isUpdate
                ? $this->findOwnedConjunto($userId, $clienteScopeId, (int) $grupoConjuntoId)
                : new GrupoConjunto(['user_id' => $userId]);

            $conjunto->fill([
                'conexao_id' => $conexao->id,
                'name' => trim((string) $data['name']),
            ]);
            $conjunto->save();

            $groupsByJid = collect($groups)->keyBy('group_jid');

            $conjunto->items()
                ->whereNotIn('group_jid', $groupsByJid->keys()->all())
                ->delete();

            foreach ($groupsByJid as $group) {
                $conjunto->items()->updateOrCreate(
                    ['group_jid' => $group['group_jid']],
                    ['group_name' => $group['group_name']]
                );
            }

            $savedConjuntoId = (int) $conjunto->id;
        });

        return redirect()
            ->route($this->grupoRoutePrefix().'.index', ['conjunto_id' => $savedConjuntoId])
            ->with('success', $isUpdate ? 'Conjunto atualizado com sucesso.' : 'Conjunto criado com sucesso.');
    }

    public function destroy(Request $request, GrupoConjunto $grupoConjunto): RedirectResponse
    {
        $userId = $this->resolveGrupoOwnerUserId($request);
        $clienteScopeId = $this->resolveGrupoClienteScopeId($request);

        $this->ensureConjuntoOwnership($grupoConjunto, $userId, $clienteScopeId);

        $nextConjuntoId = $this->scopedConjuntosQuery($userId, $clienteScopeId)
            ->where('id', '!=', (int) $grupoConjunto->id)
            ->orderBy('name')
            ->value('id');

        $grupoConjunto->delete();

        $routeParams = $nextConjuntoId ? ['conjunto_id' => (int) $nextConjuntoId] : [];

        return redirect()
            ->route($this->grupoRoutePrefix().'.index', $routeParams)
            ->with('success', 'Conjunto removido com sucesso.');
    }

    public function storeMessage(Request $request, GrupoConjunto $grupoConjunto): RedirectResponse
    {
        $userId = $this->resolveGrupoOwnerUserId($request);
        $clienteScopeId = $this->resolveGrupoClienteScopeId($request);
        $this->ensureConjuntoOwnership($grupoConjunto, $userId, $clienteScopeId);

        $recipients = $this->snapshotRecipientsFromConjunto($grupoConjunto);
        $messageInput = $this->validateMessageActionInput($request, count($recipients));

        if ($recipients === []) {
            return $this->redirectToMessagesTab($grupoConjunto)
                ->with('error', 'Este conjunto não possui grupos válidos para envio.');
        }

        $timezone = $this->resolveGrupoTimezone($request);
        $sendType = (string) $messageInput['send_type'];

        $scheduledForUtc = null;
        if ($sendType === 'scheduled') {
            $scheduledForUtc = $this->validateAndParseScheduledFor(
                trim((string) ($messageInput['scheduled_for'] ?? '')),
                $timezone
            );

            if (! $scheduledForUtc) {
                return $this->redirectToMessagesTab($grupoConjunto)
                    ->withInput()
                    ->with('error', "Data/hora inválida. Use o formato correto no fuso {$timezone}.");
            }
        }

        $nowUtc = Carbon::now('UTC');

        $registro = GrupoConjuntoMensagem::create([
            'user_id' => $userId,
            'created_by_user_id' => $userId,
            'grupo_conjunto_id' => (int) $grupoConjunto->id,
            'conexao_id' => (int) $grupoConjunto->conexao_id,
            'mensagem' => (string) $messageInput['summary'],
            'action_type' => (string) $messageInput['action_type'],
            'payload' => (array) $messageInput['payload'],
            'dispatch_type' => $sendType,
            'scheduled_for' => $sendType === 'scheduled' ? $scheduledForUtc : null,
            'status' => $sendType === 'scheduled' ? 'pending' : 'queued',
            'recipients' => $recipients,
            'queued_at' => $sendType === 'scheduled' ? null : $nowUtc,
        ]);

        if ($sendType === 'scheduled') {
            return $this->redirectToMessagesTab($grupoConjunto)
                ->with('success', 'Ação programada com sucesso.');
        }

        ExecuteGrupoConjuntoMensagemJob::dispatch((int) $registro->id)
            ->onQueue('processarconversa');

        return $this->redirectToMessagesTab($grupoConjunto)
            ->with('success', 'Ação enviada para a fila de processamento.');
    }

    public function updateMessage(
        Request $request,
        GrupoConjunto $grupoConjunto,
        GrupoConjuntoMensagem $grupoConjuntoMensagem
    ): RedirectResponse {
        $userId = $this->resolveGrupoOwnerUserId($request);
        $clienteScopeId = $this->resolveGrupoClienteScopeId($request);
        $this->ensureConjuntoOwnership($grupoConjunto, $userId, $clienteScopeId);
        $this->ensureMensagemOwnership($grupoConjuntoMensagem, $grupoConjunto, $userId);

        if (! in_array((string) $grupoConjuntoMensagem->status, ['pending', 'failed'], true)) {
            return $this->redirectToMessagesTab($grupoConjunto)
                ->with('error', 'Somente mensagens pendentes ou com falha podem ser editadas.');
        }

        $messageInput = $this->validateMessageActionInput(
            $request,
            $this->countValidRecipients((array) ($grupoConjuntoMensagem->recipients ?? []))
        );

        $timezone = $this->resolveGrupoTimezone($request);
        $sendType = (string) $messageInput['send_type'];

        $scheduledForUtc = null;
        if ($sendType === 'scheduled') {
            $scheduledForUtc = $this->validateAndParseScheduledFor(
                trim((string) ($messageInput['scheduled_for'] ?? '')),
                $timezone
            );

            if (! $scheduledForUtc) {
                return $this->redirectToMessagesTab($grupoConjunto)
                    ->withInput()
                    ->with('error', "Data/hora inválida. Use o formato correto no fuso {$timezone}.");
            }
        }

        $nowUtc = Carbon::now('UTC');

        $grupoConjuntoMensagem->fill([
            'mensagem' => (string) $messageInput['summary'],
            'action_type' => (string) $messageInput['action_type'],
            'payload' => (array) $messageInput['payload'],
            'dispatch_type' => $sendType,
            'scheduled_for' => $sendType === 'scheduled' ? $scheduledForUtc : null,
            'status' => $sendType === 'scheduled' ? 'pending' : 'queued',
            'queued_at' => $sendType === 'scheduled' ? null : $nowUtc,
            'sent_at' => null,
            'failed_at' => null,
            'error_message' => null,
            'result' => null,
            'sent_count' => 0,
            'failed_count' => 0,
        ]);
        $grupoConjuntoMensagem->save();

        if ($sendType === 'scheduled') {
            return $this->redirectToMessagesTab($grupoConjunto)
                ->with('success', 'Ação programada atualizada com sucesso.');
        }

        ExecuteGrupoConjuntoMensagemJob::dispatch((int) $grupoConjuntoMensagem->id)
            ->onQueue('processarconversa');

        return $this->redirectToMessagesTab($grupoConjunto)
            ->with('success', 'Ação enviada para a fila de processamento.');
    }

    public function destroyMessage(
        Request $request,
        GrupoConjunto $grupoConjunto,
        GrupoConjuntoMensagem $grupoConjuntoMensagem
    ): RedirectResponse {
        $userId = $this->resolveGrupoOwnerUserId($request);
        $clienteScopeId = $this->resolveGrupoClienteScopeId($request);
        $this->ensureConjuntoOwnership($grupoConjunto, $userId, $clienteScopeId);
        $this->ensureMensagemOwnership($grupoConjuntoMensagem, $grupoConjunto, $userId);

        $grupoConjuntoMensagem->delete();

        return $this->redirectToMessagesTab($grupoConjunto)
            ->with('success', 'Registro de mensagem removido com sucesso.');
    }

    public function connectionGroups(Request $request, Conexao $conexao): JsonResponse
    {
        $ownedConexao = $this->resolveOwnedConnection(
            $this->resolveGrupoOwnerUserId($request),
            (int) $conexao->id,
            $this->resolveGrupoClienteScopeId($request)
        );
        if (! $ownedConexao) {
            abort(404);
        }

        $search = trim((string) $request->query('search', ''));
        $force = true;
        $noParticipants = $request->has('no_participants')
            ? $request->boolean('no_participants')
            : true;

        $response = $this->uazapiGruposService->listGroups(
            (string) $ownedConexao->whatsapp_api_key,
            $force,
            $noParticipants
        );

        if (! empty($response['error'])) {
            $status = (int) ($response['status'] ?? 0);
            if ($status < 400 || $status > 599) {
                $status = 502;
            }

            $message = Arr::get($response, 'body.message')
                ?? Arr::get($response, 'message')
                ?? 'Não foi possível carregar os grupos desta conexão.';

            return response()->json([
                'error' => true,
                'message' => (string) $message,
            ], $status);
        }

        $groups = $this->normalizeServiceGroups($response);
        if ($search !== '') {
            $groups = $this->filterNormalizedGroupsBySearch($groups, $search);
        }

        return response()->json([
            'data' => $groups,
        ]);
    }

    public function connectionGroupInviteInfo(Request $request, Conexao $conexao): JsonResponse
    {
        $ownedConexao = $this->resolveOwnedConnection(
            $this->resolveGrupoOwnerUserId($request),
            (int) $conexao->id,
            $this->resolveGrupoClienteScopeId($request)
        );
        if (! $ownedConexao) {
            abort(404);
        }

        $data = $request->validate([
            'invite_link' => ['required', 'string', 'max:2048'],
        ]);

        $response = $this->uazapiGruposService->getGroupInviteInfo(
            (string) $ownedConexao->whatsapp_api_key,
            (string) $data['invite_link']
        );

        if (! empty($response['error'])) {
            $status = (int) ($response['status'] ?? 0);
            if ($status < 400 || $status > 599) {
                $status = 502;
            }

            $message = Arr::get($response, 'body.message')
                ?? Arr::get($response, 'body.error')
                ?? Arr::get($response, 'message')
                ?? 'Não foi possível consultar o convite do grupo.';

            return response()->json([
                'error' => true,
                'message' => (string) $message,
            ], $status);
        }

        $group = $this->normalizeServiceGroup($response);
        if (! $group) {
            return response()->json([
                'error' => true,
                'message' => 'Não foi possível identificar um grupo válido para este convite.',
            ], 422);
        }

        return response()->json([
            'data' => $group,
        ]);
    }

    protected function resolveGrupoOwnerUserId(Request $request): int
    {
        return (int) $request->user()->id;
    }

    protected function resolveGrupoClienteScopeId(Request $request): ?int
    {
        return null;
    }

    protected function resolveGrupoTimezone(Request $request): string
    {
        return $this->scheduledMessageService->resolveTimezoneForUser($request->user());
    }

    protected function grupoRoutePrefix(): string
    {
        return 'agencia.grupos';
    }

    protected function grupoViewLayout(): string
    {
        return 'layouts.agencia';
    }

    protected function showGrupoClienteInfo(): bool
    {
        return true;
    }

    protected function showGrupoFailureDetails(): bool
    {
        return false;
    }

    protected function showGroupNameSequence(): bool
    {
        return false;
    }

    protected function scopedConjuntosQuery(int $userId, ?int $clienteScopeId = null)
    {
        $query = GrupoConjunto::query()
            ->where('user_id', $userId);

        if ($clienteScopeId !== null) {
            $query->whereHas('conexao', fn ($q) => $q->where('cliente_id', $clienteScopeId));
        }

        return $query;
    }

    protected function findOwnedConjunto(int $userId, ?int $clienteScopeId, int $grupoConjuntoId): GrupoConjunto
    {
        return $this->scopedConjuntosQuery($userId, $clienteScopeId)->findOrFail($grupoConjuntoId);
    }

    protected function queryAllowedConnections(int $userId, ?int $clienteScopeId = null)
    {
        $query = Conexao::query()
            ->where('is_active', true)
            ->whereNotNull('whatsapp_api_key')
            ->where('whatsapp_api_key', '!=', '')
            ->whereHas('whatsappApi', fn ($query) => $query->where('slug', 'uazapi'))
            ->whereHas('cliente', fn ($query) => $query->where('user_id', $userId));

        if ($clienteScopeId !== null) {
            $query->where('cliente_id', $clienteScopeId);
        }

        return $query;
    }

    protected function resolveOwnedConnection(int $userId, int $conexaoId, ?int $clienteScopeId = null): ?Conexao
    {
        return $this->queryAllowedConnections($userId, $clienteScopeId)->find($conexaoId);
    }

    protected function ensureConjuntoOwnership(GrupoConjunto $grupoConjunto, int $userId, ?int $clienteScopeId = null): void
    {
        abort_unless((int) $grupoConjunto->user_id === $userId, 404);

        if ($clienteScopeId !== null) {
            $grupoConjunto->loadMissing('conexao');
            abort_unless((int) ($grupoConjunto->conexao?->cliente_id ?? 0) === $clienteScopeId, 404);
        }
    }

    protected function ensureMensagemOwnership(
        GrupoConjuntoMensagem $grupoConjuntoMensagem,
        GrupoConjunto $grupoConjunto,
        int $userId
    ): void {
        abort_unless((int) $grupoConjuntoMensagem->user_id === $userId, 404);
        abort_unless((int) $grupoConjuntoMensagem->grupo_conjunto_id === (int) $grupoConjunto->id, 404);
    }

    protected function redirectToMessagesTab(GrupoConjunto $grupoConjunto): RedirectResponse
    {
        return redirect()->route($this->grupoRoutePrefix().'.index', [
            'conjunto_id' => (int) $grupoConjunto->id,
            'tab' => 'messages',
        ]);
    }

    private function snapshotRecipientsFromConjunto(GrupoConjunto $grupoConjunto): array
    {
        $grupoConjunto->loadMissing('items');

        return $grupoConjunto->items
            ->map(function ($item) {
                $jid = trim((string) ($item->group_jid ?? ''));
                if ($jid === '' || ! preg_match('/^[0-9]+@g\.us$/', $jid)) {
                    return null;
                }

                $name = trim((string) ($item->group_name ?? ''));

                return [
                    'jid' => $jid,
                    'name' => $name !== '' ? $name : $jid,
                ];
            })
            ->filter()
            ->unique('jid')
            ->values()
            ->all();
    }

    private function countValidRecipients(array $recipients): int
    {
        $jids = [];

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $jid = trim((string) ($recipient['jid'] ?? ''));
            if ($jid === '' || ! preg_match('/^[0-9]+@g\.us$/', $jid)) {
                continue;
            }

            $jids[$jid] = true;
        }

        return count($jids);
    }

    private function validateMessageActionInput(Request $request, int $recipientCount): array
    {
        if ($request->has('group_name')) {
            $request->merge([
                'group_name' => trim((string) $request->input('group_name')),
            ]);
        }

        $rules = [
            'action_type' => ['nullable', 'string', Rule::in(GrupoConjuntoMensagem::ACTION_TYPES)],
            'send_type' => ['required', 'in:now,scheduled'],
            'scheduled_for' => ['nullable', 'string', 'max:50'],
            'mention_all' => ['nullable', 'boolean'],
            'text' => ['nullable', 'string', 'max:2000'],
            'mensagem' => ['nullable', 'string', 'max:2000'],
            'media_type' => ['nullable', 'string', Rule::in(['image', 'video', 'document', 'audio'])],
            'media_url' => ['nullable', 'string', 'max:2048'],
            'caption' => ['nullable', 'string', 'max:1024'],
            'group_name' => ['nullable', 'string', 'max:25'],
            'group_description' => ['nullable', 'string', 'max:2048'],
            'group_image_url' => ['nullable', 'string', 'max:2048'],
            'message_form' => ['nullable', 'in:create,edit'],
            'message_id' => ['nullable', 'integer'],
        ];

        if ($this->showGroupNameSequence()) {
            $rules['group_name_sequence'] = ['nullable', 'boolean'];
            $rules['group_name_sequence_start'] = ['nullable', 'integer', 'min:1', 'max:999999999'];
        }

        $data = $request->validate($rules);

        $actionType = (string) ($data['action_type'] ?? GrupoConjuntoMensagem::ACTION_SEND_TEXT);
        $mentionAll = $request->boolean('mention_all');
        $payload = [];
        $summary = '';

        switch ($actionType) {
            case GrupoConjuntoMensagem::ACTION_SEND_MEDIA:
                $mediaType = trim((string) ($data['media_type'] ?? ''));
                $mediaUrl = trim((string) ($data['media_url'] ?? ''));

                if (! in_array($mediaType, ['image', 'video', 'document', 'audio'], true)) {
                    throw ValidationException::withMessages([
                        'media_type' => ['Selecione um tipo de mídia válido.'],
                    ]);
                }

                if (! $this->isHttpUrl($mediaUrl)) {
                    throw ValidationException::withMessages([
                        'media_url' => ['Informe um link válido (http/https) para a mídia.'],
                    ]);
                }

                $caption = trim((string) ($data['caption'] ?? ''));

                $payload = [
                    'media_type' => $mediaType,
                    'media_url' => $mediaUrl,
                ];

                if ($caption !== '') {
                    $payload['caption'] = $caption;
                }
                if ($mentionAll) {
                    $payload['mention_all'] = true;
                }

                $summary = "Midia [$mediaType] $mediaUrl";
                if ($caption !== '') {
                    $summary .= " ($caption)";
                }
                if ($mentionAll) {
                    $summary .= ' [@todos]';
                }
                break;

            case GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_NAME:
                $groupName = trim((string) ($data['group_name'] ?? ''));
                if ($groupName === '') {
                    throw ValidationException::withMessages([
                        'group_name' => ['Informe o novo título dos grupos.'],
                    ]);
                }

                $payload = ['group_name' => $groupName];
                $summary = "Novo titulo: $groupName";

                if ($this->showGroupNameSequence() && $request->boolean('group_name_sequence')) {
                    if (! $request->filled('group_name_sequence_start')) {
                        throw ValidationException::withMessages([
                            'group_name_sequence_start' => ['Informe o número inicial da sequência.'],
                        ]);
                    }

                    $sequenceStart = (int) $data['group_name_sequence_start'];
                    $sequenceEnd = $sequenceStart + max(0, $recipientCount - 1);
                    $suffix = " #$sequenceEnd";
                    $maxBaseLength = 25 - mb_strlen($suffix);

                    if ($maxBaseLength < 1 || mb_strlen($groupName) > $maxBaseLength) {
                        throw ValidationException::withMessages([
                            'group_name' => ["Para esta sequência, o título-base pode ter no máximo {$maxBaseLength} caracteres, pois o maior sufixo será #{$sequenceEnd}."],
                        ]);
                    }

                    $payload['group_name_sequence'] = true;
                    $payload['group_name_sequence_start'] = $sequenceStart;
                    $summary .= " (sequência a partir de #$sequenceStart)";
                }
                break;

            case GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_DESCRIPTION:
                $description = trim((string) ($data['group_description'] ?? ''));
                if ($description === '') {
                    throw ValidationException::withMessages([
                        'group_description' => ['Informe a nova descrição dos grupos.'],
                    ]);
                }

                $payload = ['group_description' => $description];
                $summary = $description;
                break;

            case GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_IMAGE:
                $imageUrl = trim((string) ($data['group_image_url'] ?? ''));
                if (! $this->isHttpUrl($imageUrl)) {
                    throw ValidationException::withMessages([
                        'group_image_url' => ['Informe um link válido (http/https) para a nova foto.'],
                    ]);
                }

                $payload = ['group_image_url' => $imageUrl];
                $summary = "Nova foto: $imageUrl";
                break;

            case GrupoConjuntoMensagem::ACTION_SEND_TEXT:
            default:
                $text = trim((string) ($data['text'] ?? ($data['mensagem'] ?? '')));
                if ($text === '') {
                    throw ValidationException::withMessages([
                        'text' => ['Informe o texto da mensagem.'],
                    ]);
                }

                $actionType = GrupoConjuntoMensagem::ACTION_SEND_TEXT;
                $payload = ['text' => $text];
                if ($mentionAll) {
                    $payload['mention_all'] = true;
                }
                $summary = $text;
                if ($mentionAll) {
                    $summary .= ' [@todos]';
                }
                break;
        }

        return [
            'action_type' => $actionType,
            'payload' => $payload,
            'summary' => trim($summary) !== '' ? trim($summary) : '-',
            'send_type' => (string) $data['send_type'],
            'scheduled_for' => $data['scheduled_for'] ?? null,
        ];
    }

    private function validateAndParseScheduledFor(string $scheduledForRaw, string $timezone): ?Carbon
    {
        $scheduledForUtc = $this->scheduledMessageService->parseScheduledForToUtc($scheduledForRaw, $timezone);
        if (! $scheduledForUtc) {
            return null;
        }

        $nowUtc = Carbon::now('UTC');
        if ($scheduledForUtc->lte($nowUtc)) {
            return null;
        }

        $maxUtc = Carbon::now($timezone)->addDays(90)->setTimezone('UTC');
        if ($scheduledForUtc->gt($maxUtc)) {
            return null;
        }

        return $scheduledForUtc;
    }

    private function parseColumnAsUtc(GrupoConjuntoMensagem $mensagem, string $column): ?Carbon
    {
        $raw = $mensagem->getRawOriginal($column);

        if (is_string($raw) && trim($raw) !== '') {
            try {
                return Carbon::parse($raw, 'UTC');
            } catch (\Throwable) {
                // Usa o valor convertido pelo Eloquent como fallback.
            }
        }

        $value = $mensagem->getAttribute($column);

        if ($value instanceof Carbon) {
            return $value->copy()->setTimezone('UTC');
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->setTimezone('UTC');
        }

        return null;
    }

    private function formatUtcColumn(
        GrupoConjuntoMensagem $mensagem,
        string $column,
        string $timezone,
        string $format = 'd/m/Y H:i'
    ): ?string {
        return $this->parseColumnAsUtc($mensagem, $column)?->setTimezone($timezone)->format($format);
    }

    private function normalizeInputGroups(array $groups): array
    {
        $normalized = [];

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            $jid = trim((string) ($group['jid'] ?? ''));
            if ($jid === '') {
                continue;
            }

            $name = isset($group['name']) ? trim((string) $group['name']) : null;

            $normalized[$jid] = [
                'group_jid' => $jid,
                'group_name' => $name !== '' ? $name : null,
            ];
        }

        return array_values($normalized);
    }

    private function normalizeServiceGroups(array $payload): array
    {
        $rows = $this->extractGroupRows($payload);
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $item = $this->normalizeServiceGroupRow($row);
            if (! $item) {
                continue;
            }

            $normalized[$item['jid']] = $item;
        }

        $items = array_values($normalized);
        usort($items, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));

        return $items;
    }

    private function normalizeServiceGroup(array $payload): ?array
    {
        $candidates = [];

        if ($payload !== []) {
            $candidates[] = $payload;
        }

        foreach (['response', 'data', 'group'] as $key) {
            $value = $payload[$key] ?? null;
            if (is_array($value)) {
                $candidates[] = $value;
            }
        }

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeServiceGroupRow($candidate);
            if ($normalized) {
                return $normalized;
            }
        }

        $fromList = $this->normalizeServiceGroups($payload);

        return $fromList[0] ?? null;
    }

    private function normalizeServiceGroupRow(array $row): ?array
    {
        $jid = trim((string) (
            Arr::get($row, 'JID')
            ?? Arr::get($row, 'jid')
            ?? Arr::get($row, 'groupjid')
        ));

        if ($jid === '' || ! preg_match('/^[0-9]+@g\.us$/', $jid)) {
            return null;
        }

        $name = trim((string) (
            Arr::get($row, 'Name')
            ?? Arr::get($row, 'name')
            ?? Arr::get($row, 'subject')
        ));

        return [
            'jid' => $jid,
            'name' => $name !== '' ? $name : $jid,
        ];
    }

    private function filterNormalizedGroupsBySearch(array $groups, string $search): array
    {
        $searchTerm = Str::lower(trim($search));
        if ($searchTerm === '') {
            return $groups;
        }

        $filtered = array_filter($groups, static function (array $group) use ($searchTerm): bool {
            $name = Str::lower((string) ($group['name'] ?? ''));
            $jid = Str::lower((string) ($group['jid'] ?? ''));

            return str_contains($name, $searchTerm) || str_contains($jid, $searchTerm);
        });

        return array_values($filtered);
    }

    private function extractGroupRows(array $payload): array
    {
        $candidates = [];

        if ($this->isListArray($payload)) {
            $candidates[] = $payload;
        }

        foreach (['data', 'groups', 'list'] as $key) {
            $value = $payload[$key] ?? null;
            if (is_array($value)) {
                $candidates[] = $value;
            }
        }

        $body = $payload['body'] ?? null;
        if (is_array($body)) {
            if ($this->isListArray($body)) {
                $candidates[] = $body;
            }

            foreach (['data', 'groups', 'list'] as $key) {
                $value = $body[$key] ?? null;
                if (is_array($value)) {
                    $candidates[] = $value;
                }
            }
        }

        foreach ($candidates as $candidate) {
            if ($this->isListArray($candidate)) {
                return $candidate;
            }

            foreach (['data', 'groups', 'list'] as $key) {
                $nested = $candidate[$key] ?? null;
                if (is_array($nested) && $this->isListArray($nested)) {
                    return $nested;
                }
            }
        }

        return [];
    }

    private function isListArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    private function isHttpUrl(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }
}
