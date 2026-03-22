<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\LeadWebhookLink;
use App\Models\Tag;
use App\Models\WhatsappCloudCustomField;
use App\Models\WhatsappCloudTemplate;
use App\Services\LeadWebhookPayloadMapper;
use App\Services\WhatsappCloudTemplateSendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeadWebhookLinkController extends Controller
{
    public function __construct(
        protected LeadWebhookPayloadMapper $payloadMapper,
        protected WhatsappCloudTemplateSendService $templateSendService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $links = LeadWebhookLink::query()
            ->with([
                'cliente:id,nome',
                'conexao:id,name,cliente_id',
                'latestDelivery',
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $clientes = Cliente::query()
            ->where('user_id', $user->id)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $conexoes = Conexao::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('cliente', fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name', 'cliente_id']);

        return view('agencia.webhook-links.index', [
            'links' => $links,
            'clientes' => $clientes,
            'conexoes' => $conexoes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'conexao_id' => ['nullable', 'integer'],
        ]);

        $cliente = $this->resolveClienteForUser((int) $data['cliente_id'], (int) $user->id);
        $conexao = null;

        if (!empty($data['conexao_id'])) {
            $conexao = $this->resolveConexaoForLink(
                (int) $data['conexao_id'],
                (int) $user->id,
                (int) $cliente->id
            );
        }

        $link = LeadWebhookLink::create([
            'user_id' => $user->id,
            'cliente_id' => $cliente->id,
            'conexao_id' => $conexao?->id,
            'name' => $this->generateDefaultName($cliente->nome, (int) $user->id),
            'token' => $this->generateUniqueToken(),
            'is_active' => true,
            'config' => [
                'lead' => [
                    'phone_path' => null,
                    'name_path' => null,
                ],
                'actions' => [],
            ],
        ]);

        return redirect()
            ->route('agencia.webhook-links.edit', $link)
            ->with('success', 'Webhook criado com sucesso.');
    }

    public function edit(Request $request, LeadWebhookLink $leadWebhookLink)
    {
        $this->ensureOwnership($request, $leadWebhookLink);

        $leadWebhookLink->load([
            'cliente:id,nome',
            'conexao:id,name,cliente_id,assistant_id,whatsapp_api_id,whatsapp_cloud_account_id',
            'conexao.whatsappApi:id,slug',
            'latestDelivery',
        ]);

        $conexoes = $this->availableConexoes((int) $request->user()->id, (int) $leadWebhookLink->cliente_id);
        $tags = $this->availableTags($leadWebhookLink);
        $customFields = $this->availableCustomFields($leadWebhookLink);
        $cloudTemplates = $this->availableCloudTemplates($leadWebhookLink, $conexoes);

        $latestPayload = $leadWebhookLink->latestDelivery?->payload;
        if (!is_array($latestPayload)) {
            $latestPayload = null;
        }

        return view('agencia.webhook-links.edit', [
            'link' => $leadWebhookLink,
            'conexoes' => $conexoes,
            'tags' => $tags,
            'customFields' => $customFields,
            'cloudTemplates' => $cloudTemplates,
            'templateBindableFields' => collect([
                [
                    'name' => 'name',
                    'label' => 'Nome do lead',
                    'cliente_id' => null,
                ],
            ])->merge($customFields->map(fn (WhatsappCloudCustomField $field) => [
                'name' => $field->name,
                'label' => $field->label,
                'cliente_id' => $field->cliente_id,
            ]))->values(),
            'latestPayload' => $latestPayload,
            'latestPayloadPaths' => $latestPayload ? $this->payloadMapper->scalarPaths($latestPayload) : [],
        ]);
    }

    public function update(Request $request, LeadWebhookLink $leadWebhookLink): RedirectResponse
    {
        $this->ensureOwnership($request, $leadWebhookLink);

        $rawConexaoId = $request->input('conexao_id');
        $conexaoId = $rawConexaoId === '' || $rawConexaoId === null ? null : (int) $rawConexaoId;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'config_json' => ['required', 'string'],
        ]);

        $conexao = null;
        if ($conexaoId !== null) {
            $conexao = $this->resolveConexaoForLink(
                $conexaoId,
                (int) $request->user()->id,
                (int) $leadWebhookLink->cliente_id
            )->loadMissing('whatsappApi:id,slug');
        }

        $config = $this->parseConfig(
            (string) $data['config_json'],
            $leadWebhookLink,
            $conexao
        );

        if ($this->hasPromptAction($config) && !$conexao) {
            throw ValidationException::withMessages([
                'conexao_id' => 'Selecione uma conexão ativa para usar a ação de prompt.',
            ]);
        }

        $leadWebhookLink->update([
            'name' => trim((string) $data['name']),
            'is_active' => $request->boolean('is_active'),
            'conexao_id' => $conexao?->id,
            'config' => $config,
        ]);

        return redirect()
            ->route('agencia.webhook-links.edit', $leadWebhookLink)
            ->with('success', 'Webhook atualizado com sucesso.');
    }

    public function rotateToken(Request $request, LeadWebhookLink $leadWebhookLink): RedirectResponse
    {
        $this->ensureOwnership($request, $leadWebhookLink);

        $leadWebhookLink->update([
            'token' => $this->generateUniqueToken(),
        ]);

        return redirect()
            ->route('agencia.webhook-links.edit', $leadWebhookLink)
            ->with('success', 'Token do webhook rotacionado com sucesso.');
    }

    public function updateStatus(Request $request, LeadWebhookLink $leadWebhookLink): RedirectResponse
    {
        $this->ensureOwnership($request, $leadWebhookLink);

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $leadWebhookLink->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->back()
            ->with('success', $leadWebhookLink->is_active ? 'Webhook ativado com sucesso.' : 'Webhook desativado com sucesso.');
    }

    public function latestDelivery(Request $request, LeadWebhookLink $leadWebhookLink): JsonResponse
    {
        $this->ensureOwnership($request, $leadWebhookLink);

        $delivery = $leadWebhookLink->latestDelivery()->first();

        if (!$delivery) {
            return response()->json([
                'delivery' => null,
            ]);
        }

        return response()->json([
            'delivery' => [
                'id' => $delivery->id,
                'status' => $delivery->status,
                'payload' => $delivery->payload,
                'result' => $delivery->result,
                'error_message' => $delivery->error_message,
                'created_at' => optional($delivery->created_at)->toIso8601String(),
                'processed_at' => optional($delivery->processed_at)->toIso8601String(),
                'resolved_phone' => $delivery->resolved_phone,
                'cliente_lead_id' => $delivery->cliente_lead_id,
            ],
        ]);
    }

    private function ensureOwnership(Request $request, LeadWebhookLink $leadWebhookLink): void
    {
        abort_unless((int) $leadWebhookLink->user_id === (int) $request->user()->id, 403);
    }

    private function resolveClienteForUser(int $clienteId, int $userId): Cliente
    {
        return Cliente::query()
            ->where('user_id', $userId)
            ->findOrFail($clienteId);
    }

    private function resolveConexaoForLink(int $conexaoId, int $userId, int $clienteId): Conexao
    {
        return Conexao::query()
            ->whereKey($conexaoId)
            ->where('cliente_id', $clienteId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('cliente', fn ($query) => $query->where('user_id', $userId))
            ->firstOrFail();
    }

    private function availableConexoes(int $userId, int $clienteId)
    {
        return Conexao::query()
            ->with('whatsappApi:id,slug')
            ->where('cliente_id', $clienteId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('cliente', fn ($query) => $query->where('user_id', $userId))
            ->orderBy('name')
            ->get(['id', 'name', 'cliente_id', 'assistant_id', 'whatsapp_api_id', 'whatsapp_cloud_account_id']);
    }

    private function availableTags(LeadWebhookLink $link)
    {
        return Tag::query()
            ->where('user_id', $link->user_id)
            ->where(function ($query) use ($link) {
                $query->whereNull('cliente_id')
                    ->orWhere('cliente_id', $link->cliente_id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'cliente_id']);
    }

    private function availableCustomFields(LeadWebhookLink $link)
    {
        return WhatsappCloudCustomField::query()
            ->where('user_id', $link->user_id)
            ->where(function ($query) use ($link) {
                $query->whereNull('cliente_id')
                    ->orWhere('cliente_id', $link->cliente_id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'label', 'cliente_id']);
    }

    private function availableCloudTemplates(LeadWebhookLink $link, $conexoes)
    {
        $accountIds = $conexoes
            ->filter(fn (Conexao $conexao): bool => $this->isWhatsappCloudConexao($conexao))
            ->pluck('whatsapp_cloud_account_id')
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        if (empty($accountIds)) {
            return collect();
        }

        return WhatsappCloudTemplate::query()
            ->where('user_id', $link->user_id)
            ->whereIn('whatsapp_cloud_account_id', $accountIds)
            ->whereRaw('UPPER(status) IN (?, ?)', ['APPROVED', 'ACTIVE'])
            ->where(function ($query) use ($link) {
                $query->whereNull('conexao_id')
                    ->orWhereHas('conexao', fn ($subQuery) => $subQuery
                        ->whereNull('deleted_at')
                        ->where('cliente_id', $link->cliente_id));
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
    }

    private function parseConfig(string $configJson, LeadWebhookLink $link, ?Conexao $conexao): array
    {
        try {
            $decoded = json_decode($configJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                'config_json' => 'Configuração do webhook inválida.',
            ]);
        }

        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'config_json' => 'Configuração do webhook inválida.',
            ]);
        }

        $phonePath = $this->payloadMapper->normalizePath((string) data_get($decoded, 'lead.phone_path', ''));
        if ($phonePath === null) {
            throw ValidationException::withMessages([
                'config_json' => 'Selecione um caminho do payload para o telefone do lead.',
            ]);
        }

        $namePath = $this->payloadMapper->normalizePath((string) data_get($decoded, 'lead.name_path', ''));
        $actions = data_get($decoded, 'actions', []);
        if (!is_array($actions)) {
            throw ValidationException::withMessages([
                'config_json' => 'A lista de ações do webhook é inválida.',
            ]);
        }

        $availableTagIds = $this->availableTags($link)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $availableFieldIds = $this->availableCustomFields($link)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $normalizedActions = [];
        $seenTagIds = [];
        $seenFieldIds = [];
        $hasPrompt = false;
        $isCloudConexao = $conexao && $this->isWhatsappCloudConexao($conexao);

        foreach ($actions as $index => $action) {
            if (!is_array($action)) {
                throw ValidationException::withMessages([
                    'config_json' => "A ação #" . ($index + 1) . ' é inválida.',
                ]);
            }

            $type = trim((string) ($action['type'] ?? ''));

            if ($type === 'tag') {
                $tagId = (int) ($action['tag_id'] ?? 0);
                if ($tagId <= 0 || !in_array($tagId, $availableTagIds, true)) {
                    throw ValidationException::withMessages([
                        'config_json' => "A tag da ação #" . ($index + 1) . ' não está disponível para este webhook.',
                    ]);
                }

                if (in_array($tagId, $seenTagIds, true)) {
                    throw ValidationException::withMessages([
                        'config_json' => 'Não é permitido repetir a mesma tag mais de uma vez.',
                    ]);
                }

                $seenTagIds[] = $tagId;
                $normalizedActions[] = [
                    'type' => 'tag',
                    'tag_id' => $tagId,
                ];
                continue;
            }

            if ($type === 'custom_field') {
                $fieldId = (int) ($action['field_id'] ?? 0);
                $sourcePath = $this->payloadMapper->normalizePath((string) ($action['source_path'] ?? ''));

                if ($fieldId <= 0 || !in_array($fieldId, $availableFieldIds, true)) {
                    throw ValidationException::withMessages([
                        'config_json' => "O campo personalizado da ação #" . ($index + 1) . ' não está disponível para este webhook.',
                    ]);
                }

                if ($sourcePath === null) {
                    throw ValidationException::withMessages([
                        'config_json' => "Selecione um caminho do payload para o campo personalizado da ação #" . ($index + 1) . '.',
                    ]);
                }

                if (in_array($fieldId, $seenFieldIds, true)) {
                    throw ValidationException::withMessages([
                        'config_json' => 'Não é permitido repetir o mesmo campo personalizado mais de uma vez.',
                    ]);
                }

                $seenFieldIds[] = $fieldId;
                $normalizedActions[] = [
                    'type' => 'custom_field',
                    'field_id' => $fieldId,
                    'source_path' => $sourcePath,
                ];
                continue;
            }

            if ($type === 'prompt') {
                if ($hasPrompt) {
                    throw ValidationException::withMessages([
                        'config_json' => 'É permitido configurar apenas um prompt por webhook.',
                    ]);
                }

                if (!$conexao) {
                    throw ValidationException::withMessages([
                        'conexao_id' => 'Selecione uma conexão ativa para usar a ação de enviar para assistente.',
                    ]);
                }

                $hasPrompt = true;

                if ($isCloudConexao) {
                    $templateId = (int) ($action['whatsapp_cloud_template_id'] ?? 0);
                    if ($templateId <= 0) {
                        throw ValidationException::withMessages([
                            'config_json' => 'Selecione um modelo aprovado para a ação de enviar para assistente.',
                        ]);
                    }

                    $template = $this->findCloudTemplateForConexao($link, $conexao, $templateId);
                    if (!$template) {
                        throw ValidationException::withMessages([
                            'config_json' => 'O modelo selecionado não está disponível para a conexão Cloud escolhida.',
                        ]);
                    }

                    $assistantContextInstructions = trim((string) ($action['assistant_context_instructions'] ?? ''));
                    $bindings = $this->templateSendService->normalizeTemplateVariableBindings(
                        $template,
                        (array) ($action['template_variable_bindings'] ?? []),
                        (int) $link->user_id,
                        (int) $link->cliente_id
                    );

                    $normalizedActions[] = [
                        'type' => 'prompt',
                        'whatsapp_cloud_template_id' => (int) $template->id,
                        'template_variable_bindings' => $bindings,
                        'assistant_context_instructions' => $assistantContextInstructions !== '' ? $assistantContextInstructions : null,
                    ];
                    continue;
                }

                $template = trim((string) ($action['template'] ?? ''));
                if ($template === '') {
                    throw ValidationException::withMessages([
                        'config_json' => 'O texto da ação de enviar para assistente não pode ficar vazio.',
                    ]);
                }

                $normalizedActions[] = [
                    'type' => 'prompt',
                    'template' => $template,
                ];
                continue;
            }

            throw ValidationException::withMessages([
                'config_json' => "Tipo de ação inválido na posição #" . ($index + 1) . '.',
            ]);
        }

        if ($hasPrompt && $conexao === null) {
            throw ValidationException::withMessages([
                'conexao_id' => 'Selecione uma conexão ativa para usar a ação de enviar para assistente.',
            ]);
        }

        return [
            'lead' => [
                'phone_path' => $phonePath,
                'name_path' => $namePath,
            ],
            'actions' => $normalizedActions,
        ];
    }

    private function hasPromptAction(array $config): bool
    {
        foreach ((array) ($config['actions'] ?? []) as $action) {
            if (is_array($action) && ($action['type'] ?? null) === 'prompt') {
                return true;
            }
        }

        return false;
    }

    private function isWhatsappCloudConexao(Conexao $conexao): bool
    {
        return Str::lower(trim((string) ($conexao->whatsappApi?->slug ?? ''))) === 'whatsapp_cloud';
    }

    private function findCloudTemplateForConexao(LeadWebhookLink $link, Conexao $conexao, int $templateId): ?WhatsappCloudTemplate
    {
        $accountId = (int) ($conexao->whatsapp_cloud_account_id ?? 0);
        if ($accountId <= 0) {
            return null;
        }

        return WhatsappCloudTemplate::query()
            ->where('user_id', $link->user_id)
            ->whereKey($templateId)
            ->where('whatsapp_cloud_account_id', $accountId)
            ->whereRaw('UPPER(status) IN (?, ?)', ['APPROVED', 'ACTIVE'])
            ->where(function ($query) use ($conexao) {
                $query->whereNull('conexao_id')
                    ->orWhere('conexao_id', $conexao->id);
            })
            ->first();
    }

    private function generateDefaultName(string $clienteNome, int $userId): string
    {
        $base = 'Webhook ' . trim($clienteNome);
        $count = LeadWebhookLink::query()
            ->where('user_id', $userId)
            ->count();

        return Str::limit(trim($base) . ' #' . ($count + 1), 255, '');
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(40);
        } while (LeadWebhookLink::query()->where('token', $token)->exists());

        return $token;
    }
}
