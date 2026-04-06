<?php

use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\KommoAccount;
use App\Models\User;
use App\Models\WhatsappCloudCustomField;
use App\Services\OpenAIOrchestratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function openAiOrchestratorMakeCliente(User $user, array $attributes = []): Cliente
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

function openAiOrchestratorMakeLead(Cliente $cliente, array $attributes = []): ClienteLead
{
    return ClienteLead::create(array_merge([
        'cliente_id' => $cliente->id,
        'bot_enabled' => true,
        'phone' => '55' . fake()->numerify('119#######'),
        'name' => 'Lead ' . fake()->numerify('###'),
        'info' => 'Info de teste',
    ], $attributes));
}

test('resolve campos validos para tools inclui apenas globais e do mesmo cliente do lead', function () {
    $user = User::create([
        'name' => 'Owner User',
        'email' => 'owner@example.com',
        'password' => 'password',
    ]);
    $otherUser = User::create([
        'name' => 'Other User',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    $cliente = openAiOrchestratorMakeCliente($user, ['nome' => 'Cliente alvo']);
    $otherCliente = openAiOrchestratorMakeCliente($user, ['nome' => 'Outro cliente', 'email' => fake()->unique()->safeEmail()]);
    $lead = openAiOrchestratorMakeLead($cliente);

    WhatsappCloudCustomField::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'name' => 'empresa',
        'label' => 'Empresa',
    ]);

    WhatsappCloudCustomField::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'cargo',
        'label' => 'Cargo',
    ]);

    WhatsappCloudCustomField::create([
        'user_id' => $user->id,
        'cliente_id' => $otherCliente->id,
        'name' => 'segmento',
        'label' => 'Segmento',
    ]);

    WhatsappCloudCustomField::create([
        'user_id' => $otherUser->id,
        'cliente_id' => null,
        'name' => 'crm',
        'label' => 'CRM',
    ]);

    $service = new OpenAIOrchestratorService();

    $fields = (fn (ClienteLead $currentLead) => $this->resolveLeadCustomFieldsForTools($currentLead))
        ->call($service, $lead);

    expect(collect($fields)->pluck('name')->all())->toBe(['cargo', 'empresa']);
});

test('resolve integracoes kommo para tools inclui apenas as validas do mesmo cliente do lead', function () {
    $user = User::create([
        'name' => 'Owner User',
        'email' => 'owner-kommo@example.com',
        'password' => 'password',
    ]);
    $otherUser = User::create([
        'name' => 'Other User',
        'email' => 'other-kommo@example.com',
        'password' => 'password',
    ]);
    $cliente = openAiOrchestratorMakeCliente($user, ['nome' => 'Cliente Kommo']);
    $otherCliente = openAiOrchestratorMakeCliente($user, ['nome' => 'Outro cliente', 'email' => fake()->unique()->safeEmail()]);
    $lead = openAiOrchestratorMakeLead($cliente);

    $validAccount = KommoAccount::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'kommo-vendas',
        'subdomain' => 'conta-a',
        'access_token' => 'token-a',
        'pipeline_id' => '10',
        'pipeline_name' => 'Pipeline A',
        'status_id' => '100',
        'status_name' => 'Novo Lead',
    ]);

    KommoAccount::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'kommo-incompleta',
        'subdomain' => 'conta-b',
        'access_token' => 'token-b',
        'pipeline_id' => null,
        'pipeline_name' => null,
        'status_id' => null,
        'status_name' => null,
    ]);

    KommoAccount::create([
        'user_id' => $user->id,
        'cliente_id' => $otherCliente->id,
        'name' => 'kommo-outro-cliente',
        'subdomain' => 'conta-c',
        'access_token' => 'token-c',
        'pipeline_id' => '20',
        'pipeline_name' => 'Pipeline C',
        'status_id' => '200',
        'status_name' => 'Contato',
    ]);

    KommoAccount::create([
        'user_id' => $otherUser->id,
        'cliente_id' => $cliente->id,
        'name' => 'kommo-outro-usuario',
        'subdomain' => 'conta-d',
        'access_token' => 'token-d',
        'pipeline_id' => '30',
        'pipeline_name' => 'Pipeline D',
        'status_id' => '300',
        'status_name' => 'Qualificado',
    ]);

    $service = new OpenAIOrchestratorService();

    $accounts = (fn (ClienteLead $currentLead) => $this->resolveKommoAccountsForTools($currentLead))
        ->call($service, $lead);

    expect($accounts)->toBe([
        [
            'id' => $validAccount->id,
            'name' => 'kommo-vendas',
            'pipeline_id' => '10',
            'status_id' => '100',
        ],
    ]);
});

test('prepend system context inclui telefone do lead e preserva blocos existentes', function () {
    $user = User::create([
        'name' => 'Owner User',
        'email' => 'owner-context@example.com',
        'password' => 'password',
    ]);
    $cliente = openAiOrchestratorMakeCliente($user, ['nome' => 'Cliente contexto']);
    $lead = openAiOrchestratorMakeLead($cliente, [
        'phone' => '5511999999999',
        'info' => 'Perfil premium',
    ]);

    $field = WhatsappCloudCustomField::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'empresa',
        'label' => 'Empresa',
    ]);

    $lead->customFieldValues()->create([
        'whatsapp_cloud_custom_field_id' => $field->id,
        'value' => 'Acme',
    ]);

    $service = new OpenAIOrchestratorService();
    $input = [
        [
            'role' => 'user',
            'content' => 'Oi',
        ],
    ];

    $result = (fn (array $currentInput, ClienteLead $currentLead) => $this->prependSystemContext($currentInput, $currentLead))
        ->call($service, $input, $lead->fresh());

    expect($result[0]['role'])->toBe('system');
    expect($result[0]['content'])
        ->toContain('Agora:')
        ->toContain('Telefone do lead: 5511999999999')
        ->toContain('Info do lead: Perfil premium')
        ->toContain("Campos personalizados do lead:\n- Empresa (empresa): Acme");
    expect($result[1])->toBe($input[0]);
});

test('prepend system context busca telefone no banco quando o model chega sem a coluna phone carregada', function () {
    $user = User::create([
        'name' => 'Owner User',
        'email' => 'owner-context-missing-phone@example.com',
        'password' => 'password',
    ]);
    $cliente = openAiOrchestratorMakeCliente($user, ['nome' => 'Cliente contexto parcial']);
    $lead = openAiOrchestratorMakeLead($cliente, [
        'phone' => '551188887777',
        'info' => 'Lead carregado sem coluna phone',
    ]);

    $partialLead = ClienteLead::query()
        ->select(['id', 'cliente_id', 'name', 'info'])
        ->findOrFail($lead->id);

    $service = new OpenAIOrchestratorService();

    $result = (fn (array $currentInput, ClienteLead $currentLead) => $this->prependSystemContext($currentInput, $currentLead))
        ->call($service, [], $partialLead);

    expect($result[0]['content'])
        ->toContain('Telefone do lead: 551188887777')
        ->toContain('Info do lead: Lead carregado sem coluna phone');
});

test('prepend system context usa payload phone quando model e banco nao possuem telefone', function () {
    $lead = new ClienteLead([
        'info' => 'Telefone vindo do payload',
    ]);

    $service = new OpenAIOrchestratorService();

    $result = (fn (array $currentInput, ClienteLead $currentLead, ?string $currentPayloadPhone) => $this->prependSystemContext($currentInput, $currentLead, $currentPayloadPhone))
        ->call($service, [], $lead, '551177776666');

    expect($result[0]['content'])
        ->toContain('Telefone do lead: 551177776666')
        ->toContain('Info do lead: Telefone vindo do payload');
});

test('prepend system context omite linha de telefone quando phone no banco esta vazio', function () {
    $user = User::create([
        'name' => 'Owner User',
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);
    $cliente = openAiOrchestratorMakeCliente($user);
    $lead = openAiOrchestratorMakeLead($cliente, [
        'info' => 'Info sem telefone no contexto',
    ]);
    $lead->forceFill(['phone' => ''])->save();

    $service = new OpenAIOrchestratorService();

    $result = (fn (array $currentInput, ClienteLead $currentLead) => $this->prependSystemContext($currentInput, $currentLead))
        ->call($service, [], $lead->fresh());

    expect($result[0]['content'])
        ->toContain('Agora:')
        ->toContain('Info do lead: Info sem telefone no contexto')
        ->not->toContain('Telefone do lead:');
});

test('prepend system context omite linha de telefone quando o lead ainda nao tem id e phone esta null', function () {
    $lead = new ClienteLead([
        'info' => 'Info sem telefone no contexto',
    ]);

    $service = new OpenAIOrchestratorService();

    $result = (fn (array $currentInput, ClienteLead $currentLead) => $this->prependSystemContext($currentInput, $currentLead))
        ->call($service, [], $lead);

    expect($result[0]['content'])
        ->toContain('Agora:')
        ->toContain('Info do lead: Info sem telefone no contexto')
        ->not->toContain('Telefone do lead:');
});

test('resolve resultado do assistant trata ausencia de texto sem function_call pendente como silencio legitimo', function () {
    $service = new OpenAIOrchestratorService();

    $result = (fn (array $payload) => $this->resolveAssistantResult($payload))
        ->call($service, [
            'output' => [
                ['type' => 'reasoning', 'id' => 'rs_123'],
            ],
        ]);

    expect($result->ok)->toBeTrue();
    expect($result->text)->toBe('');
    expect($result->error)->toBeNull();
});

test('resolve resultado do assistant mantem erro quando existe function_call pendente sem texto final', function () {
    $service = new OpenAIOrchestratorService();

    $result = (fn (array $payload) => $this->resolveAssistantResult($payload))
        ->call($service, [
            'output' => [
                [
                    'type' => 'function_call',
                    'id' => 'fc_123',
                    'call_id' => 'call_123',
                    'name' => 'desativar_bot',
                    'arguments' => '{}',
                ],
            ],
        ]);

    expect($result->ok)->toBeFalse();
    expect($result->text)->toBeNull();
    expect($result->error)->toBe('OpenAI sem mensagem do assistente.');
});

test('resolve resultado do assistant usa fallback da tool quando nao ha texto final apos erro controlado', function () {
    $service = new OpenAIOrchestratorService();

    (fn () => $this->lastToolFallbackMessage = 'Não consegui enviar o lead ao Kommo porque a integração selecionada não foi encontrada para este cliente.')
        ->call($service);

    $result = (fn (array $payload) => $this->resolveAssistantResult($payload))
        ->call($service, [
            'output' => [
                ['type' => 'reasoning', 'id' => 'rs_kommo_123'],
            ],
        ]);

    expect($result->ok)->toBeTrue();
    expect($result->text)->toBe('Não consegui enviar o lead ao Kommo porque a integração selecionada não foi encontrada para este cliente.');
    expect($result->error)->toBeNull();
});
