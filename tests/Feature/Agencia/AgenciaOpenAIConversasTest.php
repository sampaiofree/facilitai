<?php

use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

function agenciaOpenAiConversasMakeCliente(User $user, array $attributes = []): Cliente
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

function agenciaOpenAiConversasMakeCredential(User $user, array $attributes = []): Credential
{
    return $user->credentials()->create(array_merge([
        'name' => 'Credencial ' . Str::lower(Str::random(4)),
        'label' => 'OpenAI',
        'token' => 'token-openai-valido',
        'cliente_id' => null,
        'iaplataforma_id' => null,
    ], $attributes));
}

function agenciaOpenAiConversasMakeAssistant(User $user, Cliente $cliente, ?Credential $credential = null, array $attributes = []): Assistant
{
    return Assistant::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'credential_id' => $credential?->id,
        'openai_assistant_id' => 'asst_' . Str::lower(Str::random(10)),
        'name' => 'Assistente ' . Str::lower(Str::random(4)),
        'instructions' => 'Instrucoes',
        'delay' => 5,
        'version' => 1,
    ], $attributes));
}

function agenciaOpenAiConversasMakeLead(Cliente $cliente, array $attributes = []): ClienteLead
{
    return ClienteLead::create(array_merge([
        'cliente_id' => $cliente->id,
        'bot_enabled' => true,
        'phone' => '55' . fake()->unique()->numerify('119#######'),
        'name' => 'Lead ' . fake()->numerify('###'),
        'info' => 'Info de teste',
    ], $attributes));
}

function agenciaOpenAiConversasMakeConexao(Cliente $cliente, Credential $credential, Assistant $assistant, array $attributes = []): Conexao
{
    return Conexao::create(array_merge([
        'name' => 'Conexao ' . Str::lower(Str::random(4)),
        'cliente_id' => $cliente->id,
        'credential_id' => $credential->id,
        'assistant_id' => $assistant->id,
        'status' => 'ativo',
    ], $attributes));
}

function agenciaOpenAiConversasMakeAssistantLead(ClienteLead $lead, Assistant $assistant, array $attributes = []): AssistantLead
{
    return AssistantLead::create(array_merge([
        'lead_id' => $lead->id,
        'assistant_id' => $assistant->id,
        'version' => 1,
        'conv_id' => 'conv_' . Str::lower(Str::random(12)),
    ], $attributes));
}

function agenciaOpenAiConversasFakeResponse(string $convId): array
{
    return [
        'object' => 'list',
        'data' => [
            [
                'id' => 'msg_assistant_1',
                'type' => 'message',
                'status' => 'completed',
                'role' => 'assistant',
                'content' => [
                    [
                        'type' => 'output_text',
                        'text' => "Olá! Eu sou a *Joana*, Orientadora de Carreiras do *Portal EAD CONQUISTAFLIX*.\n\nPara eu te atender certinho, me fala por favor: *qual é o seu nome e a sua cidade*?",
                    ],
                ],
            ],
            [
                'id' => 'msg_system_1',
                'type' => 'message',
                'status' => 'completed',
                'role' => 'system',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => 'Contexto interno oculto',
                    ],
                ],
            ],
            [
                'id' => 'msg_user_1',
                'type' => 'message',
                'status' => 'completed',
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => 'Olá! Tenho interesse COMBO ADMINISTRATIVO...',
                    ],
                ],
            ],
        ],
        'has_more' => false,
        'last_id' => 'msg_user_1',
        'first_id' => 'msg_assistant_1',
        'conversation_id' => $convId,
    ];
}

test('rota agencia openai conversas renderiza somente mensagens de lead e assistente', function () {
    $user = User::factory()->create();
    $cliente = agenciaOpenAiConversasMakeCliente($user);
    $credential = agenciaOpenAiConversasMakeCredential($user, ['cliente_id' => $cliente->id]);
    $assistant = agenciaOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Joana IA']);
    $lead = agenciaOpenAiConversasMakeLead($cliente, [
        'name' => 'Maria',
        'phone' => '5511999999999',
    ]);
    $assistantLead = agenciaOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_chat_123']);
    agenciaOpenAiConversasMakeConexao($cliente, $credential, $assistant);

    Http::fake([
        'https://api.openai.com/v1/conversations/*/items*' => Http::response(
            agenciaOpenAiConversasFakeResponse($assistantLead->conv_id),
            200
        ),
    ]);

    $response = $this->actingAs($user)->get(route('agencia.openai.conversas', [
        'conv_id' => $assistantLead->conv_id,
    ]));

    $response->assertOk();
    $response->assertSee('OpenAI / Conversas');
    $response->assertSee('Maria');
    $response->assertSee('5511999999999');
    $response->assertSee('Joana IA');
    $response->assertSee('Olá! Tenho interesse COMBO ADMINISTRATIVO...');
    $response->assertSee('<strong>Joana</strong>', false);
    $response->assertSee('<strong>Portal EAD CONQUISTAFLIX</strong>', false);
    $response->assertDontSee('Contexto interno oculto');
    $response->assertDontSee('msg_system_1');
    $response->assertDontSee('JSON retornado');
});

test('rota agencia openai conversas bloqueia conv_id de outro usuario', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $cliente = agenciaOpenAiConversasMakeCliente($owner);
    $credential = agenciaOpenAiConversasMakeCredential($owner, ['cliente_id' => $cliente->id]);
    $assistant = agenciaOpenAiConversasMakeAssistant($owner, $cliente, $credential);
    $lead = agenciaOpenAiConversasMakeLead($cliente);
    $assistantLead = agenciaOpenAiConversasMakeAssistantLead($lead, $assistant);

    $response = $this->actingAs($intruder)->get(route('agencia.openai.conversas', [
        'conv_id' => $assistantLead->conv_id,
    ]));

    $response->assertOk();
    $response->assertSee('nao encontrado para este usuario.');
});

test('rota agencia openai conversas mantem payload json bruto para paginação incremental', function () {
    $user = User::factory()->create();
    $cliente = agenciaOpenAiConversasMakeCliente($user);
    $credential = agenciaOpenAiConversasMakeCredential($user, ['cliente_id' => $cliente->id]);
    $assistant = agenciaOpenAiConversasMakeAssistant($user, $cliente, $credential);
    $lead = agenciaOpenAiConversasMakeLead($cliente);
    $assistantLead = agenciaOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_json_123']);
    agenciaOpenAiConversasMakeConexao($cliente, $credential, $assistant);

    Http::fake([
        'https://api.openai.com/v1/conversations/*/items*' => Http::response(
            agenciaOpenAiConversasFakeResponse($assistantLead->conv_id),
            200
        ),
    ]);

    $response = $this->actingAs($user)->getJson(route('agencia.openai.conversas', [
        'conv_id' => $assistantLead->conv_id,
        'after' => 'msg_prev',
        'limit' => 15,
    ]));

    $response->assertOk();
    $response->assertJsonPath('conv_id', $assistantLead->conv_id);
    $response->assertJsonPath('after', 'msg_prev');
    $response->assertJsonPath('limit', 15);
    $response->assertJsonPath('data.0.role', 'assistant');
    $response->assertJsonPath('data.1.role', 'system');
    $response->assertJsonPath('data.2.role', 'user');
    $response->assertJsonPath('has_more', false);
    $response->assertJsonPath('last_id', 'msg_user_1');
    $response->assertJsonPath('first_id', 'msg_assistant_1');
    $response->assertJsonMissingPath('messages');
});

test('listagem de conversas usa a nova rota agencia openai conversas no payload do lead', function () {
    $user = User::factory()->create();
    $cliente = agenciaOpenAiConversasMakeCliente($user);
    $credential = agenciaOpenAiConversasMakeCredential($user, ['cliente_id' => $cliente->id]);
    $assistant = agenciaOpenAiConversasMakeAssistant($user, $cliente, $credential);
    $lead = agenciaOpenAiConversasMakeLead($cliente);
    $assistantLead = agenciaOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_list_123']);

    $response = $this->actingAs($user)->get(route('agencia.conversas.index'));

    $response->assertOk();
    $response->assertSee(
        '"conv_url":"http:\\/\\/facilitai.test\\/agencia\\/openai\\/conversas?conv_id=' . $assistantLead->conv_id . '"',
        false
    );
});

test('rota agencia openai conversas com apenas conexao_id mostra sidebar e placeholder de conversa', function () {
    $user = User::factory()->create();
    $cliente = agenciaOpenAiConversasMakeCliente($user, ['nome' => 'Cliente Sidebar']);
    $credential = agenciaOpenAiConversasMakeCredential($user, ['cliente_id' => $cliente->id]);
    $assistant = agenciaOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Assistente Sidebar']);
    $conexao = agenciaOpenAiConversasMakeConexao($cliente, $credential, $assistant, ['name' => 'Conexão Principal']);

    $leadRecent = agenciaOpenAiConversasMakeLead($cliente, ['name' => 'Lead Recente', 'phone' => '5511999991000']);
    agenciaOpenAiConversasMakeAssistantLead($leadRecent, $assistant, ['conv_id' => 'conv_sidebar_recent']);
    $leadRecent->forceFill(['updated_at' => Carbon::parse('2026-04-16 10:30:00')])->saveQuietly();

    $leadOlder = agenciaOpenAiConversasMakeLead($cliente, ['name' => 'Lead Antigo', 'phone' => '5511999992000']);
    agenciaOpenAiConversasMakeAssistantLead($leadOlder, $assistant, ['conv_id' => 'conv_sidebar_old']);
    $leadOlder->forceFill(['updated_at' => Carbon::parse('2026-04-15 10:30:00')])->saveQuietly();

    $response = $this->actingAs($user)->get(route('agencia.openai.conversas', [
        'conexao_id' => $conexao->id,
    ]));

    $response->assertOk();
    $response->assertSee('Conexão Principal');
    $response->assertSee('Cliente Sidebar');
    $response->assertSee('Assistente Sidebar');
    $response->assertSee('Selecione um lead para visualizar a conversa');
    $response->assertSee('Lead Recente');
    $response->assertSee('Lead Antigo');
    $response->assertSee(e(route('agencia.openai.conversas', [
        'conexao_id' => $conexao->id,
        'conv_id' => 'conv_sidebar_recent',
    ])), false);
});

test('sidebar da rota agencia openai conversas filtra por cliente assistente e conv_id e respeita ordenacao do lead', function () {
    $user = User::factory()->create();
    $cliente = agenciaOpenAiConversasMakeCliente($user, ['nome' => 'Cliente A']);
    $outroCliente = agenciaOpenAiConversasMakeCliente($user, ['nome' => 'Cliente B', 'email' => fake()->unique()->safeEmail()]);
    $credential = agenciaOpenAiConversasMakeCredential($user, ['cliente_id' => $cliente->id]);
    $assistantA = agenciaOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Assistente A']);
    $assistantB = agenciaOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Assistente B']);
    $conexao = agenciaOpenAiConversasMakeConexao($cliente, $credential, $assistantA, ['name' => 'Conexão A']);

    $leadRecent = agenciaOpenAiConversasMakeLead($cliente, ['name' => 'Lead Mais Recente']);
    agenciaOpenAiConversasMakeAssistantLead($leadRecent, $assistantA, ['conv_id' => 'conv_filter_recent']);
    $leadRecent->forceFill(['updated_at' => Carbon::parse('2026-04-16 12:00:00')])->saveQuietly();

    $leadOlder = agenciaOpenAiConversasMakeLead($cliente, ['name' => 'Lead Mais Antigo']);
    agenciaOpenAiConversasMakeAssistantLead($leadOlder, $assistantA, ['conv_id' => 'conv_filter_old']);
    $leadOlder->forceFill(['updated_at' => Carbon::parse('2026-04-15 12:00:00')])->saveQuietly();

    $leadWrongAssistant = agenciaOpenAiConversasMakeLead($cliente, ['name' => 'Lead Outro Assistente']);
    agenciaOpenAiConversasMakeAssistantLead($leadWrongAssistant, $assistantB, ['conv_id' => 'conv_wrong_assistant']);
    $leadWrongAssistant->forceFill(['updated_at' => Carbon::parse('2026-04-17 12:00:00')])->saveQuietly();

    $leadWithoutConv = agenciaOpenAiConversasMakeLead($cliente, ['name' => 'Lead Sem Conv']);
    agenciaOpenAiConversasMakeAssistantLead($leadWithoutConv, $assistantA, ['conv_id' => null]);

    $leadOtherClient = agenciaOpenAiConversasMakeLead($outroCliente, ['name' => 'Lead Outro Cliente']);
    agenciaOpenAiConversasMakeAssistantLead($leadOtherClient, $assistantA, ['conv_id' => 'conv_other_client']);

    $response = $this->actingAs($user)->get(route('agencia.openai.conversas', [
        'conexao_id' => $conexao->id,
    ]));

    $response->assertOk();
    $response->assertSee('Lead Mais Recente');
    $response->assertSee('Lead Mais Antigo');
    $response->assertDontSee('Lead Outro Assistente');
    $response->assertDontSee('Lead Sem Conv');
    $response->assertDontSee('Lead Outro Cliente');

    $content = $response->getContent();
    expect(strpos($content, 'Lead Mais Recente'))->toBeLessThan(strpos($content, 'Lead Mais Antigo'));
});

test('conv_id valido seleciona automaticamente a conexao correta e sincroniza conexao_id divergente', function () {
    $user = User::factory()->create();
    $clienteA = agenciaOpenAiConversasMakeCliente($user, ['nome' => 'Cliente A']);
    $clienteB = agenciaOpenAiConversasMakeCliente($user, ['nome' => 'Cliente B', 'email' => fake()->unique()->safeEmail()]);

    $credentialA = agenciaOpenAiConversasMakeCredential($user, ['cliente_id' => $clienteA->id]);
    $credentialB = agenciaOpenAiConversasMakeCredential($user, ['cliente_id' => $clienteB->id, 'name' => 'Credencial B']);

    $assistantA = agenciaOpenAiConversasMakeAssistant($user, $clienteA, $credentialA, ['name' => 'Assistente A']);
    $assistantB = agenciaOpenAiConversasMakeAssistant($user, $clienteB, $credentialB, ['name' => 'Assistente B']);

    $conexaoA = agenciaOpenAiConversasMakeConexao($clienteA, $credentialA, $assistantA, ['name' => 'Conexão A']);
    $conexaoB = agenciaOpenAiConversasMakeConexao($clienteB, $credentialB, $assistantB, ['name' => 'Conexão B']);

    $leadB = agenciaOpenAiConversasMakeLead($clienteB, ['name' => 'Lead B', 'phone' => '5511988887777']);
    $assistantLeadB = agenciaOpenAiConversasMakeAssistantLead($leadB, $assistantB, ['conv_id' => 'conv_sync_123']);

    Http::fake([
        'https://api.openai.com/v1/conversations/*/items*' => Http::response(
            agenciaOpenAiConversasFakeResponse($assistantLeadB->conv_id),
            200
        ),
    ]);

    $response = $this->actingAs($user)->get(route('agencia.openai.conversas', [
        'conexao_id' => $conexaoA->id,
        'conv_id' => $assistantLeadB->conv_id,
    ]));

    $response->assertOk();
    $response->assertSee('Conexão B');
    $response->assertSee('Lead B');
    $response->assertSee('<option value="' . $conexaoB->id . '" selected>', false);
    $response->assertSee(e(route('agencia.openai.conversas', [
        'conexao_id' => $conexaoB->id,
        'conv_id' => $assistantLeadB->conv_id,
    ])), false);
});

test('conexao_id de outro usuario e ignorado e a primeira conexao valida do usuario e selecionada', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $clienteUser = agenciaOpenAiConversasMakeCliente($user, ['nome' => 'Cliente User']);
    $credentialUser = agenciaOpenAiConversasMakeCredential($user, ['cliente_id' => $clienteUser->id]);
    $assistantUser = agenciaOpenAiConversasMakeAssistant($user, $clienteUser, $credentialUser, ['name' => 'Assistente User']);
    $conexaoUser = agenciaOpenAiConversasMakeConexao($clienteUser, $credentialUser, $assistantUser, ['name' => 'Conexão User']);
    $leadUser = agenciaOpenAiConversasMakeLead($clienteUser, ['name' => 'Lead User']);
    agenciaOpenAiConversasMakeAssistantLead($leadUser, $assistantUser, ['conv_id' => 'conv_user_123']);

    $clienteOther = agenciaOpenAiConversasMakeCliente($otherUser, ['nome' => 'Cliente Other', 'email' => fake()->unique()->safeEmail()]);
    $credentialOther = agenciaOpenAiConversasMakeCredential($otherUser, ['cliente_id' => $clienteOther->id, 'name' => 'Credencial Other']);
    $assistantOther = agenciaOpenAiConversasMakeAssistant($otherUser, $clienteOther, $credentialOther, ['name' => 'Assistente Other']);
    $conexaoOther = agenciaOpenAiConversasMakeConexao($clienteOther, $credentialOther, $assistantOther, ['name' => 'Conexão Other']);

    $response = $this->actingAs($user)->get(route('agencia.openai.conversas', [
        'conexao_id' => $conexaoOther->id,
    ]));

    $response->assertOk();
    $response->assertSee('<option value="' . $conexaoUser->id . '" selected>', false);
    $response->assertDontSee('Conexão Other');
    $response->assertSee('Lead User');
    $response->assertSee('Selecione um lead para visualizar a conversa');
});
