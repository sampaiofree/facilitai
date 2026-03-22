<?php

use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\LeadWebhookLink;
use App\Models\User;
use App\Models\WhatsappApi;
use App\Models\WhatsappCloudAccount;
use App\Models\WhatsappCloudCustomField;
use App\Models\WhatsappCloudTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function agenciaWebhookMakeCliente(User $user, array $attributes = []): Cliente
{
    return Cliente::create(array_merge([
        'user_id' => $user->id,
        'nome' => 'Cliente ' . fake()->numerify('###'),
        'email' => fake()->unique()->safeEmail(),
        'telefone' => '11999999999',
        'password' => 'secret123',
        'is_active' => true,
    ], $attributes));
}

function agenciaWebhookMakeAssistant(User $user, Cliente $cliente, array $attributes = []): Assistant
{
    return Assistant::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'Assistente ' . fake()->numerify('###'),
        'instructions' => 'Instruções',
        'systemPrompt' => 'Prompt base',
    ], $attributes));
}

function agenciaWebhookMakeConexao(User $user, Cliente $cliente, array $attributes = []): Conexao
{
    $assistant = agenciaWebhookMakeAssistant($user, $cliente);
    $api = WhatsappApi::create([
        'nome' => 'Uazapi',
        'descricao' => 'API teste',
        'slug' => fake()->unique()->slug(),
        'ativo' => true,
    ]);

    return Conexao::create(array_merge([
        'name' => 'Conexão ' . fake()->numerify('###'),
        'cliente_id' => $cliente->id,
        'assistant_id' => $assistant->id,
        'whatsapp_api_id' => $api->id,
        'is_active' => true,
        'status' => 'active',
    ], $attributes));
}

function agenciaWebhookMakeCloudConexao(User $user, Cliente $cliente, array $attributes = []): Conexao
{
    $assistant = agenciaWebhookMakeAssistant($user, $cliente);
    $api = WhatsappApi::firstOrCreate(
        ['slug' => 'whatsapp_cloud'],
        [
            'nome' => 'WhatsApp Cloud',
            'descricao' => 'API Cloud teste',
            'ativo' => true,
        ]
    );

    $account = WhatsappCloudAccount::create([
        'user_id' => $user->id,
        'name' => 'Conta Cloud teste',
        'phone_number_id' => fake()->numerify('100000########'),
        'business_account_id' => fake()->numerify('200000########'),
        'access_token' => 'cloud-token',
        'is_default' => true,
    ]);

    return Conexao::create(array_merge([
        'name' => 'Conexão Cloud ' . fake()->numerify('###'),
        'cliente_id' => $cliente->id,
        'assistant_id' => $assistant->id,
        'whatsapp_api_id' => $api->id,
        'whatsapp_cloud_account_id' => $account->id,
        'is_active' => true,
        'status' => 'active',
    ], $attributes));
}

function agenciaWebhookMakeCloudTemplate(User $user, Conexao $conexao, array $attributes = []): WhatsappCloudTemplate
{
    return WhatsappCloudTemplate::create(array_merge([
        'user_id' => $user->id,
        'whatsapp_cloud_account_id' => $conexao->whatsapp_cloud_account_id,
        'conexao_id' => $conexao->id,
        'title' => 'Modelo teste',
        'template_name' => 'modelo_teste_' . fake()->unique()->numerify('###'),
        'language_code' => 'pt_BR',
        'category' => 'UTILITY',
        'variables' => ['var_1'],
        'body_text' => 'Olá {var_1}',
        'status' => 'APPROVED',
    ], $attributes));
}

test('usuario agencia consegue criar e editar webhook link', function () {
    $user = User::factory()->create();
    $cliente = agenciaWebhookMakeCliente($user);
    $conexao = agenciaWebhookMakeConexao($user, $cliente);

    $response = $this->actingAs($user)->post(route('agencia.webhook-links.store'), [
        'cliente_id' => $cliente->id,
        'conexao_id' => $conexao->id,
    ]);

    $link = LeadWebhookLink::query()->first();

    $response->assertRedirect(route('agencia.webhook-links.edit', $link));

    $this->assertDatabaseHas('lead_webhook_links', [
        'id' => $link->id,
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'conexao_id' => $conexao->id,
    ]);

    $this->actingAs($user)
        ->get(route('agencia.webhook-links.edit', $link))
        ->assertOk()
        ->assertSee('Último payload recebido')
        ->assertSee('Adicionar novas ações')
        ->assertSee('enviar para assistente');
});

test('usuario agencia consegue salvar configuracao do webhook link', function () {
    $user = User::factory()->create();
    $cliente = agenciaWebhookMakeCliente($user);
    $conexao = agenciaWebhookMakeConexao($user, $cliente);

    $link = LeadWebhookLink::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'conexao_id' => null,
        'name' => 'Webhook original',
        'token' => fake()->unique()->regexify('[A-Za-z0-9]{40}'),
        'is_active' => true,
        'config' => [
            'lead' => [
                'phone_path' => null,
                'name_path' => null,
            ],
            'actions' => [],
        ],
    ]);

    $config = [
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
    ];

    $response = $this->actingAs($user)->put(route('agencia.webhook-links.update', $link), [
        'name' => 'Webhook atualizado',
        'is_active' => '1',
        'conexao_id' => $conexao->id,
        'config_json' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    $response->assertRedirect(route('agencia.webhook-links.edit', $link));
    $response->assertSessionHas('success');

    $link->refresh();

    expect($link->name)->toBe('Webhook atualizado');
    expect($link->conexao_id)->toBe($conexao->id);
    expect(data_get($link->config, 'lead.phone_path'))->toBe('payload.contact.phone');
    expect(data_get($link->config, 'actions.0.type'))->toBe('prompt');
});

test('usuario agencia consegue salvar configuracao cloud do webhook link', function () {
    $user = User::factory()->create();
    $cliente = agenciaWebhookMakeCliente($user);
    $conexao = agenciaWebhookMakeCloudConexao($user, $cliente);
    $template = agenciaWebhookMakeCloudTemplate($user, $conexao);

    $link = LeadWebhookLink::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'conexao_id' => null,
        'name' => 'Webhook cloud',
        'token' => fake()->unique()->regexify('[A-Za-z0-9]{40}'),
        'is_active' => true,
        'config' => [
            'lead' => [
                'phone_path' => null,
                'name_path' => null,
            ],
            'actions' => [],
        ],
    ]);

    $response = $this->actingAs($user)->put(route('agencia.webhook-links.update', $link), [
        'name' => 'Webhook cloud atualizado',
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
                    'whatsapp_cloud_template_id' => $template->id,
                    'template_variable_bindings' => [
                        'var_1' => 'name',
                    ],
                    'assistant_context_instructions' => 'Origem {{payload.source}}',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    $response->assertRedirect(route('agencia.webhook-links.edit', $link));
    $response->assertSessionHas('success');

    $link->refresh();

    expect(data_get($link->config, 'actions.0.whatsapp_cloud_template_id'))->toBe($template->id);
    expect(data_get($link->config, 'actions.0.template_variable_bindings.var_1'))->toBe('name');
    expect(data_get($link->config, 'actions.0.assistant_context_instructions'))->toBe('Origem {{payload.source}}');
});

test('webhook link cloud exige modelo aprovado para salvar a acao de enviar para assistente', function () {
    $user = User::factory()->create();
    $cliente = agenciaWebhookMakeCliente($user);
    $conexao = agenciaWebhookMakeCloudConexao($user, $cliente);

    $link = LeadWebhookLink::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'conexao_id' => null,
        'name' => 'Webhook cloud sem modelo',
        'token' => fake()->unique()->regexify('[A-Za-z0-9]{40}'),
        'is_active' => true,
        'config' => [
            'lead' => [
                'phone_path' => null,
                'name_path' => null,
            ],
            'actions' => [],
        ],
    ]);

    $response = $this->from(route('agencia.webhook-links.edit', $link))
        ->actingAs($user)
        ->put(route('agencia.webhook-links.update', $link), [
            'name' => 'Webhook cloud sem modelo',
            'is_active' => '1',
            'conexao_id' => $conexao->id,
            'config_json' => json_encode([
                'lead' => [
                    'phone_path' => 'payload.contact.phone',
                    'name_path' => null,
                ],
                'actions' => [
                    [
                        'type' => 'prompt',
                        'assistant_context_instructions' => 'Contexto sem modelo',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

    $response->assertRedirect(route('agencia.webhook-links.edit', $link));
    $response->assertSessionHasErrors('config_json');
});

test('webhook link cloud rejeita binding invalida na configuracao', function () {
    $user = User::factory()->create();
    $cliente = agenciaWebhookMakeCliente($user);
    $conexao = agenciaWebhookMakeCloudConexao($user, $cliente);
    $template = agenciaWebhookMakeCloudTemplate($user, $conexao);

    WhatsappCloudCustomField::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'empresa',
        'label' => 'Empresa',
        'sample_value' => null,
        'description' => null,
    ]);

    $link = LeadWebhookLink::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'conexao_id' => null,
        'name' => 'Webhook cloud inválido',
        'token' => fake()->unique()->regexify('[A-Za-z0-9]{40}'),
        'is_active' => true,
        'config' => [
            'lead' => [
                'phone_path' => null,
                'name_path' => null,
            ],
            'actions' => [],
        ],
    ]);

    $response = $this->from(route('agencia.webhook-links.edit', $link))
        ->actingAs($user)
        ->put(route('agencia.webhook-links.update', $link), [
            'name' => 'Webhook cloud inválido',
            'is_active' => '1',
            'conexao_id' => $conexao->id,
            'config_json' => json_encode([
                'lead' => [
                    'phone_path' => 'payload.contact.phone',
                    'name_path' => null,
                ],
                'actions' => [
                    [
                        'type' => 'prompt',
                        'whatsapp_cloud_template_id' => $template->id,
                        'template_variable_bindings' => [
                            'var_1' => 'campo_inexistente',
                        ],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

    $response->assertRedirect(route('agencia.webhook-links.edit', $link));
    $response->assertSessionHasErrors('template_variable_bindings');
});

test('webhook link cloud rejeita template de outra conta ou conexao incompatível', function () {
    $user = User::factory()->create();
    $cliente = agenciaWebhookMakeCliente($user);
    $conexaoPrincipal = agenciaWebhookMakeCloudConexao($user, $cliente);
    $conexaoSecundaria = agenciaWebhookMakeCloudConexao($user, $cliente);
    $template = agenciaWebhookMakeCloudTemplate($user, $conexaoSecundaria);

    $link = LeadWebhookLink::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'conexao_id' => null,
        'name' => 'Webhook cloud incompatível',
        'token' => fake()->unique()->regexify('[A-Za-z0-9]{40}'),
        'is_active' => true,
        'config' => [
            'lead' => [
                'phone_path' => null,
                'name_path' => null,
            ],
            'actions' => [],
        ],
    ]);

    $response = $this->from(route('agencia.webhook-links.edit', $link))
        ->actingAs($user)
        ->put(route('agencia.webhook-links.update', $link), [
            'name' => 'Webhook cloud incompatível',
            'is_active' => '1',
            'conexao_id' => $conexaoPrincipal->id,
            'config_json' => json_encode([
                'lead' => [
                    'phone_path' => 'payload.contact.phone',
                    'name_path' => null,
                ],
                'actions' => [
                    [
                        'type' => 'prompt',
                        'whatsapp_cloud_template_id' => $template->id,
                        'template_variable_bindings' => [
                            'var_1' => 'name',
                        ],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

    $response->assertRedirect(route('agencia.webhook-links.edit', $link));
    $response->assertSessionHasErrors('config_json');
});
