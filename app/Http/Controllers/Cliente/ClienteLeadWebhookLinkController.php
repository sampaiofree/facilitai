<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Agencia\LeadWebhookLinkController;
use App\Models\LeadWebhookLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClienteLeadWebhookLinkController extends LeadWebhookLinkController
{
    public function index(Request $request)
    {
        $cliente = $request->user('client');

        $links = LeadWebhookLink::query()
            ->with([
                'cliente:id,nome',
                'conexao:id,name,cliente_id',
                'latestDelivery',
            ])
            ->where('user_id', $cliente->user_id)
            ->where('cliente_id', $cliente->id)
            ->latest()
            ->get();

        $conexoes = $this->availableConexoes((int) $cliente->user_id, (int) $cliente->id);

        return view('agencia.webhook-links.index', [
            'links' => $links,
            'clientes' => collect([$cliente]),
            'conexoes' => $conexoes,
            'layout' => 'layouts.cliente',
            'routePrefix' => 'cliente.webhook-links',
            'showClienteSelect' => false,
            'fixedCliente' => $cliente,
            'isClientPortal' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cliente = $request->user('client');
        $data = $request->validate([
            'conexao_id' => ['nullable', 'integer'],
        ]);

        $conexao = null;

        if (!empty($data['conexao_id'])) {
            $conexao = $this->resolveConexaoForLink(
                (int) $data['conexao_id'],
                (int) $cliente->user_id,
                (int) $cliente->id
            );
        }

        $link = LeadWebhookLink::create([
            'user_id' => $cliente->user_id,
            'cliente_id' => $cliente->id,
            'conexao_id' => $conexao?->id,
            'name' => $this->generateDefaultName($cliente->nome, (int) $cliente->user_id),
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
            ->route('cliente.webhook-links.edit', $link)
            ->with('success', 'Webhook criado com sucesso.');
    }

    public function edit(Request $request, LeadWebhookLink $leadWebhookLink)
    {
        $cliente = $request->user('client');
        $this->ensureOwnershipForClient($leadWebhookLink, (int) $cliente->user_id, (int) $cliente->id);

        $leadWebhookLink->load([
            'cliente:id,nome',
            'conexao:id,name,cliente_id,assistant_id,whatsapp_api_id,whatsapp_cloud_account_id',
            'conexao.whatsappApi:id,slug',
            'latestDelivery',
        ]);

        $conexoes = $this->availableConexoes((int) $cliente->user_id, (int) $leadWebhookLink->cliente_id);
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
            ])->merge($customFields->map(fn ($field) => [
                'name' => $field->name,
                'label' => $field->label,
                'cliente_id' => $field->cliente_id,
            ]))->values(),
            'latestPayload' => $latestPayload,
            'latestPayloadPaths' => $latestPayload ? $this->payloadMapper->scalarPaths($latestPayload) : [],
            'layout' => 'layouts.cliente',
            'routePrefix' => 'cliente.webhook-links',
            'isClientPortal' => true,
        ]);
    }

    public function update(Request $request, LeadWebhookLink $leadWebhookLink): RedirectResponse
    {
        $cliente = $request->user('client');
        $this->ensureOwnershipForClient($leadWebhookLink, (int) $cliente->user_id, (int) $cliente->id);

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
                (int) $cliente->user_id,
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
            ->route('cliente.webhook-links.edit', $leadWebhookLink)
            ->with('success', 'Webhook atualizado com sucesso.');
    }

    public function rotateToken(Request $request, LeadWebhookLink $leadWebhookLink): RedirectResponse
    {
        $cliente = $request->user('client');
        $this->ensureOwnershipForClient($leadWebhookLink, (int) $cliente->user_id, (int) $cliente->id);

        $leadWebhookLink->update([
            'token' => $this->generateUniqueToken(),
        ]);

        return redirect()
            ->route('cliente.webhook-links.edit', $leadWebhookLink)
            ->with('success', 'Token do webhook rotacionado com sucesso.');
    }

    public function updateStatus(Request $request, LeadWebhookLink $leadWebhookLink): RedirectResponse
    {
        $cliente = $request->user('client');
        $this->ensureOwnershipForClient($leadWebhookLink, (int) $cliente->user_id, (int) $cliente->id);

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
        $cliente = $request->user('client');
        $this->ensureOwnershipForClient($leadWebhookLink, (int) $cliente->user_id, (int) $cliente->id);

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

    private function ensureOwnershipForClient(LeadWebhookLink $leadWebhookLink, int $userId, int $clienteId): void
    {
        abort_unless(
            (int) $leadWebhookLink->user_id === $userId && (int) $leadWebhookLink->cliente_id === $clienteId,
            403
        );
    }
}
