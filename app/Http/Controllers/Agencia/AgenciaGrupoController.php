<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Conexao;
use App\Models\GrupoConjunto;
use App\Models\GrupoConjuntoMensagem;
use App\Services\GrupoConjuntoMensagemService;
use App\Services\ScheduledMessageService;
use App\Services\UazapiGruposService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AgenciaGrupoController extends Controller
{
    public function __construct(
        protected UazapiGruposService $uazapiGruposService,
        protected GrupoConjuntoMensagemService $grupoConjuntoMensagemService,
        protected ScheduledMessageService $scheduledMessageService
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $userId = (int) $user->id;
        $requestedConjuntoId = $request->filled('conjunto_id') ? (int) $request->input('conjunto_id') : null;
        $activeTab = $request->input('tab') === 'messages' ? 'messages' : 'groups';

        $conjuntos = GrupoConjunto::query()
            ->with([
                'conexao:id,name,cliente_id',
                'conexao.cliente:id,nome',
                'items:id,grupo_conjunto_id,group_jid,group_name',
            ])
            ->withCount('items')
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get();

        $selectedConjunto = null;
        if ($requestedConjuntoId) {
            $selectedConjunto = $conjuntos->firstWhere('id', $requestedConjuntoId);
        }

        if (!$selectedConjunto) {
            $selectedConjunto = $conjuntos->first();
        }

        $conexoes = $this->queryAllowedConnections($userId)
            ->select('id', 'name', 'cliente_id')
            ->with('cliente:id,nome')
            ->orderBy('name')
            ->get();

        $timezone = $this->scheduledMessageService->resolveTimezoneForUser($user);
        $mensagens = collect();

        if ($selectedConjunto) {
            $mensagens = GrupoConjuntoMensagem::query()
                ->where('user_id', $userId)
                ->where('grupo_conjunto_id', (int) $selectedConjunto->id)
                ->orderByDesc('created_at')
                ->get();

            $mensagens->transform(function (GrupoConjuntoMensagem $mensagem) use ($timezone) {
                $mensagem->scheduled_for_label = $this->formatUtcDate($mensagem->scheduled_for, $timezone);
                $mensagem->scheduled_for_input = $mensagem->scheduled_for?->copy()->setTimezone($timezone)->format('Y-m-d\\TH:i');
                $mensagem->created_at_label = $this->formatUtcDate($mensagem->created_at, $timezone);
                $mensagem->sent_at_label = $this->formatUtcDate($mensagem->sent_at, $timezone);
                $mensagem->failed_at_label = $this->formatUtcDate($mensagem->failed_at, $timezone);
                return $mensagem;
            });
        }

        return view('agencia.grupos.index', [
            'conjuntos' => $conjuntos,
            'selectedConjunto' => $selectedConjunto,
            'conexoes' => $conexoes,
            'activeTab' => $activeTab,
            'timezone' => $timezone,
            'mensagens' => $mensagens,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->input('grupo_conjunto_id') === '') {
            $request->merge(['grupo_conjunto_id' => null]);
        }

        $userId = (int) $request->user()->id;
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

        $conexao = $this->resolveOwnedConnection($userId, (int) $data['conexao_id']);
        if (!$conexao) {
            abort(404);
        }

        $groups = $this->normalizeInputGroups((array) $data['groups']);
        if ($groups === []) {
            throw ValidationException::withMessages([
                'groups' => ['Selecione ao menos um grupo.'],
            ]);
        }

        $isUpdate = !empty($data['grupo_conjunto_id']);
        $savedConjuntoId = null;

        DB::transaction(function () use ($data, $groups, $userId, $isUpdate, $grupoConjuntoId, $conexao, &$savedConjuntoId): void {
            $conjunto = $isUpdate
                ? GrupoConjunto::where('user_id', $userId)->findOrFail($grupoConjuntoId)
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
            ->route('agencia.grupos.index', ['conjunto_id' => $savedConjuntoId])
            ->with('success', $isUpdate ? 'Conjunto atualizado com sucesso.' : 'Conjunto criado com sucesso.');
    }

    public function destroy(Request $request, GrupoConjunto $grupoConjunto): RedirectResponse
    {
        abort_unless((int) $grupoConjunto->user_id === (int) $request->user()->id, 404);

        $nextConjuntoId = GrupoConjunto::query()
            ->where('user_id', (int) $request->user()->id)
            ->where('id', '!=', (int) $grupoConjunto->id)
            ->orderBy('name')
            ->value('id');

        $grupoConjunto->delete();

        $routeParams = $nextConjuntoId ? ['conjunto_id' => (int) $nextConjuntoId] : [];

        return redirect()
            ->route('agencia.grupos.index', $routeParams)
            ->with('success', 'Conjunto removido com sucesso.');
    }

    public function storeMessage(Request $request, GrupoConjunto $grupoConjunto): RedirectResponse
    {
        $user = $request->user();
        $this->ensureConjuntoOwnership($grupoConjunto, (int) $user->id);

        $messageInput = $this->validateMessageActionInput($request);

        $recipients = $this->snapshotRecipientsFromConjunto($grupoConjunto);
        if ($recipients === []) {
            return $this->redirectToMessagesTab($grupoConjunto)
                ->with('error', 'Este conjunto não possui grupos válidos para envio.');
        }

        $timezone = $this->scheduledMessageService->resolveTimezoneForUser($user);
        $sendType = (string) $messageInput['send_type'];

        $scheduledForUtc = null;
        if ($sendType === 'scheduled') {
            $scheduledForUtc = $this->validateAndParseScheduledFor(
                trim((string) ($messageInput['scheduled_for'] ?? '')),
                $timezone
            );

            if (!$scheduledForUtc) {
                return $this->redirectToMessagesTab($grupoConjunto)
                    ->withInput()
                    ->with('error', "Data/hora inválida. Use o formato correto no fuso {$timezone}.");
            }
        }

        $nowUtc = Carbon::now('UTC');

        $registro = GrupoConjuntoMensagem::create([
            'user_id' => (int) $user->id,
            'created_by_user_id' => (int) $user->id,
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

        $registro = $this->grupoConjuntoMensagemService->dispatchAndPersist($registro, 1);

        if ((string) $registro->status === 'sent') {
            return $this->redirectToMessagesTab($grupoConjunto)
                ->with('success', 'Ação executada com sucesso para os grupos do conjunto.');
        }

        return $this->redirectToMessagesTab($grupoConjunto)
            ->with('error', $registro->error_message ?: 'Falha ao executar ação para um ou mais grupos.');
    }

    public function updateMessage(
        Request $request,
        GrupoConjunto $grupoConjunto,
        GrupoConjuntoMensagem $grupoConjuntoMensagem
    ): RedirectResponse {
        $user = $request->user();
        $this->ensureConjuntoOwnership($grupoConjunto, (int) $user->id);
        $this->ensureMensagemOwnership($grupoConjuntoMensagem, $grupoConjunto, (int) $user->id);

        if (!in_array((string) $grupoConjuntoMensagem->status, ['pending', 'failed'], true)) {
            return $this->redirectToMessagesTab($grupoConjunto)
                ->with('error', 'Somente mensagens pendentes ou com falha podem ser editadas.');
        }

        $messageInput = $this->validateMessageActionInput($request);

        $timezone = $this->scheduledMessageService->resolveTimezoneForUser($user);
        $sendType = (string) $messageInput['send_type'];

        $scheduledForUtc = null;
        if ($sendType === 'scheduled') {
            $scheduledForUtc = $this->validateAndParseScheduledFor(
                trim((string) ($messageInput['scheduled_for'] ?? '')),
                $timezone
            );

            if (!$scheduledForUtc) {
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

        $grupoConjuntoMensagem = $this->grupoConjuntoMensagemService->dispatchAndPersist($grupoConjuntoMensagem, 1);

        if ((string) $grupoConjuntoMensagem->status === 'sent') {
            return $this->redirectToMessagesTab($grupoConjunto)
                ->with('success', 'Ação executada com sucesso para os grupos do conjunto.');
        }

        return $this->redirectToMessagesTab($grupoConjunto)
            ->with('error', $grupoConjuntoMensagem->error_message ?: 'Falha ao executar ação para um ou mais grupos.');
    }

    public function destroyMessage(
        Request $request,
        GrupoConjunto $grupoConjunto,
        GrupoConjuntoMensagem $grupoConjuntoMensagem
    ): RedirectResponse {
        $this->ensureConjuntoOwnership($grupoConjunto, (int) $request->user()->id);
        $this->ensureMensagemOwnership($grupoConjuntoMensagem, $grupoConjunto, (int) $request->user()->id);

        $grupoConjuntoMensagem->delete();

        return $this->redirectToMessagesTab($grupoConjunto)
            ->with('success', 'Registro de mensagem removido com sucesso.');
    }

    public function connectionGroups(Request $request, Conexao $conexao): JsonResponse
    {
        $ownedConexao = $this->resolveOwnedConnection((int) $request->user()->id, (int) $conexao->id);
        if (!$ownedConexao) {
            abort(404);
        }

        $search = trim((string) $request->query('search', ''));
        $force = $request->boolean('force', false);
        $noParticipants = $request->has('no_participants')
            ? $request->boolean('no_participants')
            : true;

        $response = $this->uazapiGruposService->listGroups(
            (string) $ownedConexao->whatsapp_api_key,
            $force,
            $noParticipants
        );

        if (!empty($response['error'])) {
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
        $ownedConexao = $this->resolveOwnedConnection((int) $request->user()->id, (int) $conexao->id);
        if (!$ownedConexao) {
            abort(404);
        }

        $data = $request->validate([
            'invite_link' => ['required', 'string', 'max:2048'],
        ]);

        $response = $this->uazapiGruposService->getGroupInviteInfo(
            (string) $ownedConexao->whatsapp_api_key,
            (string) $data['invite_link']
        );

        if (!empty($response['error'])) {
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
        if (!$group) {
            return response()->json([
                'error' => true,
                'message' => 'Não foi possível identificar um grupo válido para este convite.',
            ], 422);
        }

        return response()->json([
            'data' => $group,
        ]);
    }

    private function queryAllowedConnections(int $userId)
    {
        return Conexao::query()
            ->where('is_active', true)
            ->whereNotNull('whatsapp_api_key')
            ->where('whatsapp_api_key', '!=', '')
            ->whereHas('whatsappApi', fn ($query) => $query->where('slug', 'uazapi'))
            ->whereHas('cliente', fn ($query) => $query->where('user_id', $userId));
    }

    private function resolveOwnedConnection(int $userId, int $conexaoId): ?Conexao
    {
        return $this->queryAllowedConnections($userId)->find($conexaoId);
    }

    private function ensureConjuntoOwnership(GrupoConjunto $grupoConjunto, int $userId): void
    {
        abort_unless((int) $grupoConjunto->user_id === $userId, 404);
    }

    private function ensureMensagemOwnership(
        GrupoConjuntoMensagem $grupoConjuntoMensagem,
        GrupoConjunto $grupoConjunto,
        int $userId
    ): void {
        abort_unless((int) $grupoConjuntoMensagem->user_id === $userId, 404);
        abort_unless((int) $grupoConjuntoMensagem->grupo_conjunto_id === (int) $grupoConjunto->id, 404);
    }

    private function redirectToMessagesTab(GrupoConjunto $grupoConjunto): RedirectResponse
    {
        return redirect()->route('agencia.grupos.index', [
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
                if ($jid === '' || !preg_match('/^[0-9]+@g\.us$/', $jid)) {
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

    private function validateMessageActionInput(Request $request): array
    {
        $data = $request->validate([
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
            'group_description' => ['nullable', 'string', 'max:512'],
            'group_image_url' => ['nullable', 'string', 'max:2048'],
            'message_form' => ['nullable', 'in:create,edit'],
            'message_id' => ['nullable', 'integer'],
        ]);

        $actionType = (string) ($data['action_type'] ?? GrupoConjuntoMensagem::ACTION_SEND_TEXT);
        $mentionAll = $request->boolean('mention_all');
        $payload = [];
        $summary = '';

        switch ($actionType) {
            case GrupoConjuntoMensagem::ACTION_SEND_MEDIA:
                $mediaType = trim((string) ($data['media_type'] ?? ''));
                $mediaUrl = trim((string) ($data['media_url'] ?? ''));

                if (!in_array($mediaType, ['image', 'video', 'document', 'audio'], true)) {
                    throw ValidationException::withMessages([
                        'media_type' => ['Selecione um tipo de mídia válido.'],
                    ]);
                }

                if (!$this->isHttpUrl($mediaUrl)) {
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
                if (!$this->isHttpUrl($imageUrl)) {
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
        if (!$scheduledForUtc) {
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

    private function formatUtcDate($value, string $timezone): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value, 'UTC')->setTimezone($timezone)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeInputGroups(array $groups): array
    {
        $normalized = [];

        foreach ($groups as $group) {
            if (!is_array($group)) {
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
            if (!is_array($row)) {
                continue;
            }

            $item = $this->normalizeServiceGroupRow($row);
            if (!$item) {
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

        if ($jid === '' || !preg_match('/^[0-9]+@g\.us$/', $jid)) {
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

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }
}
