<?php

use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\LeadWebhookLink;
use App\Models\User;
use App\Models\WhatsappApi;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function clienteWebhookMakeCliente(User $user, array $attributes = []): Cliente
{
    return Cliente::create(array_merge([
        'user_id' => $user->id,
        'nome' => 'Cliente ' . fake()->unique()->numerify('###'),
        'email' => fake()->unique()->safeEmail(),
        'telefone' => '11999999999',
        'password' => 'secret123',
        'is_active' => true,
    ], $attributes));
}

function clienteWebhookMakeAssistant(User $user, Cliente $cliente, array $attributes = []): Assistant
{
    return Assistant::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'Assistente ' . fake()->unique()->numerify('###'),
        'instructions' => 'Instruções',
        'systemPrompt' => 'Prompt base',
    ], $attributes));
}

function clienteWebhookMakeConexao(User $user, Cliente $cliente, array $attributes = []): Conexao
{
    $assistant = clienteWebhookMakeAssistant($user, $cliente);
    $api = WhatsappApi::create([
        'nome' => 'Uazapi',
        'descricao' => 'API teste',
        'slug' => fake()->unique()->slug(),
        'ativo' => true,
    ]);

    return Conexao::create(array_merge([
        'name' => 'Conexão ' . fake()->unique()->numerify('###'),
        'cliente_id' => $cliente->id,
        'assistant_id' => $assistant->id,
        'whatsapp_api_id' => $api->id,
        'is_active' => true,
        'status' => 'active',
    ], $attributes));
}

function clienteWebhookMakeLink(User $user, Cliente $cliente, array $attributes = []): LeadWebhookLink
{
    return LeadWebhookLink::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'conexao_id' => null,
        'name' => 'Webhook ' . fake()->unique()->numerify('###'),
        'token' => fake()->unique()->regexify('[A-Za-z0-9]{40}'),
        'is_active' => true,
        'config' => [
            'lead' => [
                'phone_path' => null,
                'name_path' => null,
            ],
            'actions' => [],
        ],
    ], $attributes));
}

test('cliente lista apenas webhooks do proprio cliente', function () {
    $user = User::factory()->create();
    $clienteA = clienteWebhookMakeCliente($user, ['nome' => 'Cliente A']);
    $clienteB = clienteWebhookMakeCliente($user, ['nome' => 'Cliente B']);

    clienteWebhookMakeLink($user, $clienteA, ['name' => 'Webhook A']);
    clienteWebhookMakeLink($user, $clienteB, ['name' => 'Webhook B']);

    $this->actingAs($clienteA, 'client')
        ->get(route('cliente.webhook-links.index'))
        ->assertOk()
        ->assertSee('Webhook A')
        ->assertDontSee('Webhook B')
        ->assertSee('Crie links públicos do seu cliente', false);
});

test('cliente cria webhook fixado no proprio cliente logado', function () {
    $user = User::factory()->create();
    $clienteA = clienteWebhookMakeCliente($user);
    $clienteB = clienteWebhookMakeCliente($user);
    $conexaoA = clienteWebhookMakeConexao($user, $clienteA);

    $response = $this->actingAs($clienteA, 'client')
        ->post(route('cliente.webhook-links.store'), [
            'cliente_id' => $clienteB->id,
            'conexao_id' => $conexaoA->id,
        ]);

    $link = LeadWebhookLink::query()->first();

    $response->assertRedirect(route('cliente.webhook-links.edit', $link));

    $this->assertDatabaseHas('lead_webhook_links', [
        'id' => $link->id,
        'user_id' => $user->id,
        'cliente_id' => $clienteA->id,
        'conexao_id' => $conexaoA->id,
    ]);
});

test('cliente consegue editar webhook proprio', function () {
    $user = User::factory()->create();
    $cliente = clienteWebhookMakeCliente($user);
    $conexao = clienteWebhookMakeConexao($user, $cliente);
    $link = clienteWebhookMakeLink($user, $cliente);

    $response = $this->actingAs($cliente, 'client')
        ->put(route('cliente.webhook-links.update', $link), [
            'name' => 'Webhook atualizado pelo cliente',
            'is_active' => '1',
            'conexao_id' => $conexao->id,
            'config_json' => json_encode([
                'lead' => [
                    'phone_path' => 'payload.contact.phone',
                    'name_path' => 'payload.contact.name',
                ],
                'actions' => [
                    [
                        'type' => 'prompt',
                        'template' => 'Olá {{payload.contact.name}}',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

    $response->assertRedirect(route('cliente.webhook-links.edit', $link));
    $response->assertSessionHas('success');

    $link->refresh();

    expect($link->name)->toBe('Webhook atualizado pelo cliente');
    expect($link->conexao_id)->toBe($conexao->id);
    expect(data_get($link->config, 'lead.phone_path'))->toBe('payload.contact.phone');
    expect(data_get($link->config, 'actions.0.type'))->toBe('prompt');
});

test('cliente nao pode abrir webhook de outro cliente', function () {
    $user = User::factory()->create();
    $clienteA = clienteWebhookMakeCliente($user);
    $clienteB = clienteWebhookMakeCliente($user);
    $link = clienteWebhookMakeLink($user, $clienteB);

    $this->actingAs($clienteA, 'client')
        ->get(route('cliente.webhook-links.edit', $link))
        ->assertForbidden();
});

test('cliente nao pode atualizar webhook de outro cliente', function () {
    $user = User::factory()->create();
    $clienteA = clienteWebhookMakeCliente($user);
    $clienteB = clienteWebhookMakeCliente($user);
    $link = clienteWebhookMakeLink($user, $clienteB);

    $this->actingAs($clienteA, 'client')
        ->put(route('cliente.webhook-links.update', $link), [
            'name' => 'Tentativa inválida',
            'config_json' => json_encode([
                'lead' => [
                    'phone_path' => 'payload.phone',
                    'name_path' => null,
                ],
                'actions' => [],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ])
        ->assertForbidden();
});
