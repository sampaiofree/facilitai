<?php

use App\Jobs\ProcessIncomingMessageJob;
use App\Jobs\SyncCloudTemplateContextJob;
use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\LeadWebhookDelivery;
use App\Models\LeadWebhookLink;
use App\Models\Tag;
use App\Models\User;
use App\Models\WhatsappApi;
use App\Models\WhatsappCloudAccount;
use App\Models\WhatsappCloudCustomField;
use App\Models\WhatsappCloudTemplate;
use App\Services\WhatsappCloudTemplateSendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function leadWebhookApiMakeCliente(User $user, array $attributes = []): Cliente
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

function leadWebhookApiMakeAssistant(User $user, Cliente $cliente, array $attributes = []): Assistant
{
    return Assistant::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'Assistente ' . fake()->numerify('###'),
        'instructions' => 'Instruções',
        'systemPrompt' => 'Prompt base',
    ], $attributes));
}

function leadWebhookApiMakeWhatsappApi(array $attributes = []): WhatsappApi
{
    return WhatsappApi::create(array_merge([
        'nome' => 'Uazapi',
        'descricao' => 'API teste',
        'slug' => 'uazapi_' . fake()->unique()->numerify('###'),
        'ativo' => true,
    ], $attributes));
}

function leadWebhookApiMakeConexao(User $user, Cliente $cliente, ?Assistant $assistant = null, ?WhatsappApi $api = null, array $attributes = []): Conexao
{
    $assistant ??= leadWebhookApiMakeAssistant($user, $cliente);
    $api ??= leadWebhookApiMakeWhatsappApi();

    return Conexao::create(array_merge([
        'name' => 'Conexão ' . fake()->numerify('###'),
        'cliente_id' => $cliente->id,
        'assistant_id' => $assistant->id,
        'whatsapp_api_id' => $api->id,
        'is_active' => true,
        'status' => 'active',
    ], $attributes));
}

function leadWebhookApiMakeLink(User $user, Cliente $cliente, ?Conexao $conexao = null, array $config = []): LeadWebhookLink
{
    return LeadWebhookLink::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'conexao_id' => $conexao?->id,
        'name' => 'Webhook teste',
        'token' => fake()->unique()->regexify('[A-Za-z0-9]{40}'),
        'is_active' => true,
        'config' => $config,
    ]);
}

function leadWebhookApiMakeCloudConexao(User $user, Cliente $cliente, array $attributes = []): Conexao
{
    $assistant = leadWebhookApiMakeAssistant($user, $cliente);
    $api = WhatsappApi::firstOrCreate(
        ['slug' => 'whatsapp_cloud'],
        [
            'nome' => 'WhatsApp Cloud',
            'descricao' => 'Cloud teste',
            'ativo' => true,
        ]
    );

    $account = WhatsappCloudAccount::create([
        'user_id' => $user->id,
        'name' => 'Conta Cloud webhook',
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

function leadWebhookApiMakeCloudTemplate(User $user, Conexao $conexao, array $attributes = []): WhatsappCloudTemplate
{
    return WhatsappCloudTemplate::create(array_merge([
        'user_id' => $user->id,
        'whatsapp_cloud_account_id' => $conexao->whatsapp_cloud_account_id,
        'conexao_id' => $conexao->id,
        'title' => 'Template webhook',
        'template_name' => 'template_webhook_' . fake()->unique()->numerify('###'),
        'language_code' => 'pt_BR',
        'category' => 'UTILITY',
        'variables' => ['var_1'],
        'body_text' => 'Olá {var_1}',
        'status' => 'APPROVED',
    ], $attributes));
}

test('webhook publico cria lead aplica tag campo e enfileira prompt', function () {
    Queue::fake();

    $user = User::factory()->create();
    $cliente = leadWebhookApiMakeCliente($user);
    $conexao = leadWebhookApiMakeConexao($user, $cliente);

    $tag = Tag::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'name' => 'Novo lead',
        'color' => null,
        'description' => null,
    ]);

    $field = WhatsappCloudCustomField::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'empresa',
        'label' => 'Empresa',
        'sample_value' => null,
        'description' => null,
    ]);

    $link = leadWebhookApiMakeLink($user, $cliente, $conexao, [
        'lead' => [
            'phone_path' => 'payload.contact.phone',
            'name_path' => 'payload.contact.name',
        ],
        'actions' => [
            ['type' => 'tag', 'tag_id' => $tag->id],
            ['type' => 'custom_field', 'field_id' => $field->id, 'source_path' => 'payload.company.name'],
            ['type' => 'prompt', 'template' => 'Novo lead {{payload.contact.name}} da empresa {{payload.company.name}}'],
        ],
    ]);

    $response = $this->postJson(route('api.webhook-links.handle', ['token' => $link->token]), [
        'contact' => [
            'name' => 'Maria',
            'phone' => '(11) 99999-9999',
        ],
        'company' => [
            'name' => 'ACME LTDA',
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('status', 'processed');

    $lead = ClienteLead::query()
        ->where('cliente_id', $cliente->id)
        ->where('phone', '5511999999999')
        ->first();

    expect($lead)->not()->toBeNull();
    expect($lead?->name)->toBe('Maria');

    $this->assertDatabaseHas('cliente_lead_tag', [
        'cliente_lead_id' => $lead->id,
        'tag_id' => $tag->id,
    ]);

    $this->assertDatabaseHas('cliente_lead_custom_fields', [
        'cliente_lead_id' => $lead->id,
        'whatsapp_cloud_custom_field_id' => $field->id,
        'value' => 'ACME LTDA',
    ]);

    $this->assertDatabaseHas('lead_webhook_deliveries', [
        'lead_webhook_link_id' => $link->id,
        'status' => 'processed',
        'cliente_lead_id' => $lead->id,
        'resolved_phone' => '5511999999999',
    ]);

    Queue::assertPushed(ProcessIncomingMessageJob::class, 1);
});

test('webhook publico retorna partial quando prompt nao pode ser enviado mas salva lead', function () {
    Queue::fake();

    $user = User::factory()->create();
    $cliente = leadWebhookApiMakeCliente($user);

    $link = leadWebhookApiMakeLink($user, $cliente, null, [
        'lead' => [
            'phone_path' => 'payload.contact.phone',
            'name_path' => 'payload.contact.name',
        ],
        'actions' => [
            ['type' => 'prompt', 'template' => 'Olá {{payload.contact.name}}'],
        ],
    ]);

    $response = $this->postJson(route('api.webhook-links.handle', ['token' => $link->token]), [
        'contact' => [
            'name' => 'João',
            'phone' => '11988887777',
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('status', 'partial');

    $lead = ClienteLead::query()
        ->where('cliente_id', $cliente->id)
        ->where('phone', '5511988887777')
        ->first();

    expect($lead)->not()->toBeNull();

    Queue::assertNothingPushed();
});

test('webhook publico falha com telefone invalido e registra delivery failed', function () {
    Queue::fake();

    $user = User::factory()->create();
    $cliente = leadWebhookApiMakeCliente($user);
    $link = leadWebhookApiMakeLink($user, $cliente, null, [
        'lead' => [
            'phone_path' => 'payload.contact.phone',
            'name_path' => 'payload.contact.name',
        ],
        'actions' => [],
    ]);

    $response = $this->postJson(route('api.webhook-links.handle', ['token' => $link->token]), [
        'contact' => [
            'name' => 'Sem telefone',
            'phone' => 'abc',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('ok', false)
        ->assertJsonPath('status', 'failed');

    expect(ClienteLead::count())->toBe(0);

    $delivery = LeadWebhookDelivery::query()->where('lead_webhook_link_id', $link->id)->first();
    expect($delivery?->status)->toBe('failed');
});

test('webhook publico detecta payload duplicado e nao reprocessa prompt', function () {
    Queue::fake();

    $user = User::factory()->create();
    $cliente = leadWebhookApiMakeCliente($user);
    $conexao = leadWebhookApiMakeConexao($user, $cliente);

    $link = leadWebhookApiMakeLink($user, $cliente, $conexao, [
        'lead' => [
            'phone_path' => 'payload.contact.phone',
            'name_path' => 'payload.contact.name',
        ],
        'actions' => [
            ['type' => 'prompt', 'template' => 'Oi {{payload.contact.name}}'],
        ],
    ]);

    $payload = [
        'contact' => [
            'name' => 'Maria',
            'phone' => '11999999999',
        ],
    ];

    $this->postJson(route('api.webhook-links.handle', ['token' => $link->token]), $payload)
        ->assertOk()
        ->assertJsonPath('status', 'processed');

    $this->postJson(route('api.webhook-links.handle', ['token' => $link->token]), $payload)
        ->assertOk()
        ->assertJsonPath('status', 'duplicate');

    expect(LeadWebhookDelivery::query()->where('lead_webhook_link_id', $link->id)->count())->toBe(2);
    expect(LeadWebhookDelivery::query()->where('lead_webhook_link_id', $link->id)->where('status', 'duplicate')->count())->toBe(1);

    Queue::assertPushed(ProcessIncomingMessageJob::class, 1);
});

test('webhook publico cloud envia template e enfileira sync de contexto sem despachar process incoming message', function () {
    Queue::fake();

    $user = User::factory()->create();
    $cliente = leadWebhookApiMakeCliente($user);
    $conexao = leadWebhookApiMakeCloudConexao($user, $cliente);
    $template = leadWebhookApiMakeCloudTemplate($user, $conexao);

    app()->instance(WhatsappCloudTemplateSendService::class, \Mockery::mock(WhatsappCloudTemplateSendService::class, function ($mock) {
        $mock->shouldReceive('resolveBoundTemplateVariablesForLead')
            ->once()
            ->andReturn([['var_1' => 'Maria'], []]);

        $mock->shouldReceive('sendToLead')
            ->once()
            ->andReturn([
                'ok' => true,
                'message' => 'Modelo enviado com sucesso.',
                'response' => [
                    'body' => [
                        'messages' => [
                            ['id' => 'wamid.123'],
                        ],
                    ],
                ],
                'resolved_variables' => ['var_1' => 'Maria'],
            ]);
    }));

    $link = leadWebhookApiMakeLink($user, $cliente, $conexao, [
        'lead' => [
            'phone_path' => 'payload.contact.phone',
            'name_path' => 'payload.contact.name',
        ],
        'actions' => [
            [
                'type' => 'prompt',
                'whatsapp_cloud_template_id' => $template->id,
                'template_variable_bindings' => ['var_1' => 'name'],
                'assistant_context_instructions' => 'Origem {{payload.source}}',
            ],
        ],
    ]);

    $response = $this->postJson(route('api.webhook-links.handle', ['token' => $link->token]), [
        'contact' => [
            'name' => 'Maria',
            'phone' => '11999999999',
        ],
        'source' => 'landing-page',
    ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('status', 'processed');

    Queue::assertPushed(SyncCloudTemplateContextJob::class, function (SyncCloudTemplateContextJob $job) use ($template): bool {
        $property = new ReflectionProperty($job, 'payload');
        $property->setAccessible(true);
        $payload = $property->getValue($job);

        return (int) ($payload['template_id'] ?? 0) === (int) $template->id
            && ($payload['assistant_context_instructions'] ?? null) === 'Origem landing-page';
    });

    Queue::assertNotPushed(ProcessIncomingMessageJob::class);
});

test('webhook publico cloud envia template e omite contexto extra quando a renderizacao fica vazia', function () {
    Queue::fake();

    $user = User::factory()->create();
    $cliente = leadWebhookApiMakeCliente($user);
    $conexao = leadWebhookApiMakeCloudConexao($user, $cliente);
    $template = leadWebhookApiMakeCloudTemplate($user, $conexao);

    app()->instance(WhatsappCloudTemplateSendService::class, \Mockery::mock(WhatsappCloudTemplateSendService::class, function ($mock) {
        $mock->shouldReceive('resolveBoundTemplateVariablesForLead')
            ->once()
            ->andReturn([['var_1' => 'João'], []]);

        $mock->shouldReceive('sendToLead')
            ->once()
            ->andReturn([
                'ok' => true,
                'message' => 'Modelo enviado com sucesso.',
                'response' => [
                    'body' => [
                        'messages' => [
                            ['id' => 'wamid.456'],
                        ],
                    ],
                ],
                'resolved_variables' => ['var_1' => 'João'],
            ]);
    }));

    $link = leadWebhookApiMakeLink($user, $cliente, $conexao, [
        'lead' => [
            'phone_path' => 'payload.contact.phone',
            'name_path' => 'payload.contact.name',
        ],
        'actions' => [
            [
                'type' => 'prompt',
                'whatsapp_cloud_template_id' => $template->id,
                'template_variable_bindings' => ['var_1' => 'name'],
                'assistant_context_instructions' => '{{payload.contexto_inexistente}}',
            ],
        ],
    ]);

    $response = $this->postJson(route('api.webhook-links.handle', ['token' => $link->token]), [
        'contact' => [
            'name' => 'João',
            'phone' => '11988887777',
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('status', 'processed');

    Queue::assertPushed(SyncCloudTemplateContextJob::class, function (SyncCloudTemplateContextJob $job): bool {
        $property = new ReflectionProperty($job, 'payload');
        $property->setAccessible(true);
        $payload = $property->getValue($job);

        return array_key_exists('assistant_context_instructions', $payload)
            && $payload['assistant_context_instructions'] === null;
    });

    Queue::assertNotPushed(ProcessIncomingMessageJob::class);
});
