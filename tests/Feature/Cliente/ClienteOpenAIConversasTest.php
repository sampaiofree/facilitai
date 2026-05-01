<?php

use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\Credential;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function clienteOpenAiConversasMakeCliente(User $user, array $attributes = []): Cliente
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

function clienteOpenAiConversasMakeCredential(User $user, Cliente $cliente, array $attributes = []): Credential
{
    return $user->credentials()->create(array_merge([
        'name' => 'Credencial ' . Str::lower(Str::random(4)),
        'label' => 'OpenAI',
        'token' => 'token-openai-valido',
        'cliente_id' => $cliente->id,
        'iaplataforma_id' => null,
    ], $attributes));
}

function clienteOpenAiConversasMakeAssistant(User $user, Cliente $cliente, ?Credential $credential = null, array $attributes = []): Assistant
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

function clienteOpenAiConversasMakeLead(Cliente $cliente, array $attributes = []): ClienteLead
{
    return ClienteLead::create(array_merge([
        'cliente_id' => $cliente->id,
        'bot_enabled' => true,
        'phone' => '55' . fake()->unique()->numerify('119#######'),
        'name' => 'Lead ' . fake()->unique()->numerify('###'),
        'info' => 'Info de teste',
    ], $attributes));
}

function clienteOpenAiConversasMakeConexao(Cliente $cliente, Credential $credential, Assistant $assistant, array $attributes = []): Conexao
{
    return Conexao::create(array_merge([
        'name' => 'Conexao ' . Str::lower(Str::random(4)),
        'cliente_id' => $cliente->id,
        'credential_id' => $credential->id,
        'assistant_id' => $assistant->id,
        'status' => 'ativo',
        'is_active' => true,
    ], $attributes));
}

function clienteOpenAiConversasMakeAssistantLead(ClienteLead $lead, Assistant $assistant, array $attributes = []): AssistantLead
{
    return AssistantLead::create(array_merge([
        'lead_id' => $lead->id,
        'assistant_id' => $assistant->id,
        'version' => 1,
        'conv_id' => 'conv_' . Str::lower(Str::random(12)),
    ], $attributes));
}

function clienteOpenAiConversasMakeTag(User $user, Cliente $cliente, array $attributes = []): Tag
{
    return Tag::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'Tag ' . fake()->unique()->numerify('###'),
        'color' => null,
        'description' => null,
    ], $attributes));
}

function clienteOpenAiConversasFakeResponse(): array
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
                        'text' => 'Olá, eu sou a *Joana*.',
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
                'id' => 'reasoning_1',
                'type' => 'reasoning',
                'status' => 'completed',
            ],
            [
                'id' => 'tool_1',
                'type' => 'function_call',
                'status' => 'completed',
                'name' => 'buscar_lead',
            ],
            [
                'id' => 'msg_user_1',
                'type' => 'message',
                'status' => 'completed',
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => 'Tenho interesse.',
                    ],
                ],
            ],
        ],
        'has_more' => false,
        'last_id' => 'msg_user_1',
        'first_id' => 'msg_assistant_1',
    ];
}

function clienteOpenAiConversasFakeResponseWithMore(): array
{
    return array_merge(clienteOpenAiConversasFakeResponse(), [
        'has_more' => true,
    ]);
}

test('cliente ve a aba chat dentro de conversas', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Assistente Chat']);
    $lead = clienteOpenAiConversasMakeLead($cliente, ['name' => 'Maria Chat']);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant, ['name' => 'Conexao Chat']);
    clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_cliente_tab']);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
    ]));

    $response->assertOk();
    $response->assertSee('Lista');
    $response->assertSee('Chat');
    $response->assertSee('Maria Chat');
    $response->assertSee('Selecione uma conversa');
});

test('aba chat do cliente mostra apenas leads do proprio cliente', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user, ['nome' => 'Cliente A']);
    $outroCliente = clienteOpenAiConversasMakeCliente($user, ['nome' => 'Cliente B', 'email' => fake()->unique()->safeEmail()]);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    $leadDoCliente = clienteOpenAiConversasMakeLead($cliente, ['name' => 'Lead Visivel']);
    $leadDeOutroCliente = clienteOpenAiConversasMakeLead($outroCliente, ['name' => 'Lead Oculto']);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);
    clienteOpenAiConversasMakeAssistantLead($leadDoCliente, $assistant, ['conv_id' => 'conv_visivel']);
    clienteOpenAiConversasMakeAssistantLead($leadDeOutroCliente, $assistant, ['conv_id' => 'conv_oculto']);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
    ]));

    $response->assertOk();
    $response->assertSee('Lead Visivel');
    $response->assertDontSee('Lead Oculto');
});

test('aba chat bloqueia conv_id de outro cliente sem consultar openai', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $outroCliente = clienteOpenAiConversasMakeCliente($user, ['email' => fake()->unique()->safeEmail()]);
    $credential = clienteOpenAiConversasMakeCredential($user, $outroCliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $outroCliente, $credential);
    $lead = clienteOpenAiConversasMakeLead($outroCliente);
    $assistantLead = clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_outro_cliente']);

    Http::fake([
        'https://api.openai.com/v1/conversations/*/items*' => Http::response(clienteOpenAiConversasFakeResponse(), 200),
    ]);

    $response = $this->actingAs($cliente, 'client')->getJson(route('cliente.conversas.index', [
        'tab' => 'chat',
        'conv_id' => $assistantLead->conv_id,
    ]));

    $response->assertStatus(400);
    $response->assertJsonPath('error', 'Conv_id "conv_outro_cliente" nao encontrado para este cliente.');
    Http::assertNothingSent();
});

test('aba chat renderiza mensagens visiveis e oculta itens tecnicos', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Joana IA']);
    $lead = clienteOpenAiConversasMakeLead($cliente, ['name' => 'Maria', 'phone' => '5511999999999']);
    $assistantLead = clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_cliente_chat']);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);

    Http::fake([
        'https://api.openai.com/v1/conversations/*/items*' => Http::response(clienteOpenAiConversasFakeResponse(), 200),
    ]);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
        'conv_id' => $assistantLead->conv_id,
    ]));

    $response->assertOk();
    $response->assertSee('Maria');
    $response->assertSee('Tenho interesse.');
    $response->assertSee('<strong>Joana</strong>', false);
    $response->assertDontSee('Contexto interno oculto');
    $response->assertDontSee('Reasoning');
    $response->assertDontSee('buscar_lead');
    $response->assertDontSee('Itens técnicos');
});

test('aba chat alinha lead a esquerda e assistente a direita', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    $lead = clienteOpenAiConversasMakeLead($cliente);
    $assistantLead = clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_alinhamento']);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);

    Http::fake([
        'https://api.openai.com/v1/conversations/*/items*' => Http::response(clienteOpenAiConversasFakeResponse(), 200),
    ]);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
        'conv_id' => $assistantLead->conv_id,
    ]));

    $response->assertOk();
    $response->assertSee('class="flex justify-start"', false);
    $response->assertSee('data-chat-message-role="user"', false);
    $response->assertSee('class="flex justify-end"', false);
    $response->assertSee('data-chat-message-role="assistant"', false);
});

test('json da aba chat retorna apenas mensagens normalizadas', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    $lead = clienteOpenAiConversasMakeLead($cliente);
    $assistantLead = clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_cliente_json']);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);

    Http::fake([
        'https://api.openai.com/v1/conversations/*/items*' => Http::response(clienteOpenAiConversasFakeResponse(), 200),
    ]);

    $response = $this->actingAs($cliente, 'client')->getJson(route('cliente.conversas.index', [
        'tab' => 'chat',
        'conv_id' => $assistantLead->conv_id,
        'after' => 'msg_prev',
        'limit' => 10,
    ]));

    $response->assertOk();
    $response->assertJsonPath('conv_id', 'conv_cliente_json');
    $response->assertJsonPath('after', 'msg_prev');
    $response->assertJsonPath('limit', 10);
    $response->assertJsonPath('messages.0.role', 'assistant');
    $response->assertJsonPath('messages.1.role', 'user');
    $response->assertJsonMissingPath('data');
    $response->assertDontSee('Contexto interno oculto');
    $response->assertDontSee('reasoning');
    $response->assertDontSee('function_call');
});

test('poll state da aba chat retorna apenas estado local sem consultar openai', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    $lead = clienteOpenAiConversasMakeLead($cliente);
    $assistantLead = clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_poll_state']);
    $assistantLead->forceFill(['updated_at' => Carbon::parse('2026-05-01 13:45:00')])->saveQuietly();
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);

    Http::fake([
        'https://api.openai.com/v1/conversations/*/items*' => Http::response(clienteOpenAiConversasFakeResponse(), 200),
    ]);

    $response = $this->actingAs($cliente, 'client')->getJson(route('cliente.conversas.index', [
        'tab' => 'chat',
        'conv_id' => $assistantLead->conv_id,
        'poll_state' => 1,
    ]));

    $response->assertOk();
    $response->assertJsonPath('conv_id', 'conv_poll_state');
    $response->assertJsonPath('assistant_lead_id', $assistantLead->id);
    $response->assertJsonPath('assistant_lead_updated_at', Carbon::parse('2026-05-01 13:45:00')->toJSON());
    $response->assertJsonMissingPath('messages');
    $response->assertJsonMissingPath('data');
    Http::assertNothingSent();
});

test('filtros atuais afetam os cards da aba chat', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);

    $leadMaria = clienteOpenAiConversasMakeLead($cliente, ['name' => 'Maria Filtrada']);
    $leadJose = clienteOpenAiConversasMakeLead($cliente, ['name' => 'Jose Oculto']);
    clienteOpenAiConversasMakeAssistantLead($leadMaria, $assistant, ['conv_id' => 'conv_maria']);
    clienteOpenAiConversasMakeAssistantLead($leadJose, $assistant, ['conv_id' => 'conv_jose']);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
        'q' => 'Maria',
    ]));

    $response->assertOk();
    $response->assertSee('Maria Filtrada');
    $response->assertDontSee('Jose Oculto');
});

test('aba chat carrega somente vinte cards na primeira pagina', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);
    $baseTime = Carbon::parse('2026-05-01 12:00:00');

    for ($i = 1; $i <= 25; $i++) {
        $lead = clienteOpenAiConversasMakeLead($cliente, ['name' => sprintf('Lead Pag %02d', $i)]);
        $lead->forceFill(['updated_at' => $baseTime->copy()->subMinutes($i)])->saveQuietly();
        clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => sprintf('conv_pag_%02d', $i)]);
    }

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
    ]));

    $response->assertOk();
    expect(substr_count($response->getContent(), 'data-chat-card'))->toBe(20);
    $response->assertSee('Lead Pag 01');
    $response->assertSee('Lead Pag 20');
    $response->assertDontSee('Lead Pag 21');
    $response->assertSee('data-has-more="1"', false);
    $response->assertSee('data-next-offset="20"', false);
});

test('aba chat retorna o proximo lote de cards via json', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);
    $baseTime = Carbon::parse('2026-05-01 12:00:00');

    for ($i = 1; $i <= 25; $i++) {
        $lead = clienteOpenAiConversasMakeLead($cliente, ['name' => sprintf('Lead Lote %02d', $i)]);
        $lead->forceFill(['updated_at' => $baseTime->copy()->subMinutes($i)])->saveQuietly();
        clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => sprintf('conv_lote_%02d', $i)]);
    }

    $response = $this->actingAs($cliente, 'client')->getJson(route('cliente.conversas.index', [
        'tab' => 'chat',
        'cards_only' => 1,
        'cards_offset' => 20,
        'cards_limit' => 20,
    ]));

    $response->assertOk();
    $response->assertJsonPath('count', 5);
    $response->assertJsonPath('has_more', false);
    $response->assertJsonPath('next_offset', 25);
    expect(substr_count($response->json('html'), 'data-chat-card'))->toBe(5);
    expect($response->json('html'))->toContain('Lead Lote 21');
    expect($response->json('html'))->toContain('Lead Lote 25');
    expect($response->json('html'))->not->toContain('Lead Lote 20');
});

test('card ativo fora do primeiro lote fica fixado e nao duplica no proximo lote', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);
    $baseTime = Carbon::parse('2026-05-01 12:00:00');
    $activeConvId = 'conv_pin_25';

    for ($i = 1; $i <= 25; $i++) {
        $lead = clienteOpenAiConversasMakeLead($cliente, ['name' => sprintf('Lead Pin %02d', $i)]);
        $lead->forceFill(['updated_at' => $baseTime->copy()->subMinutes($i)])->saveQuietly();
        clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => $i === 25 ? $activeConvId : sprintf('conv_pin_%02d', $i)]);
    }

    Http::fake([
        'https://api.openai.com/v1/conversations/*/items*' => Http::response(clienteOpenAiConversasFakeResponse(), 200),
    ]);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
        'conv_id' => $activeConvId,
    ]));

    $response->assertOk();
    expect(substr_count($response->getContent(), 'data-chat-card'))->toBe(20);
    $content = $response->getContent();
    expect(strpos($content, 'Lead Pin 25'))->toBeLessThan(strpos($content, 'Lead Pin 01'));
    $response->assertSee('Lead Pin 25');
    $response->assertDontSee('Lead Pin 20');
    $response->assertSee('data-next-offset="19"', false);

    $nextResponse = $this->actingAs($cliente, 'client')->getJson(route('cliente.conversas.index', [
        'tab' => 'chat',
        'conv_id' => $activeConvId,
        'cards_only' => 1,
        'cards_offset' => 19,
        'cards_limit' => 20,
    ]));

    $nextResponse->assertOk();
    expect($nextResponse->json('html'))->toContain('Lead Pin 20');
    expect($nextResponse->json('html'))->not->toContain('Lead Pin 25');
});

test('multiplas conversas do mesmo lead ficam agrupadas no card', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistantA = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Assistente A']);
    $assistantB = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Assistente B']);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistantA);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistantB);
    $lead = clienteOpenAiConversasMakeLead($cliente, ['name' => 'Lead Multi']);
    clienteOpenAiConversasMakeAssistantLead($lead, $assistantA, ['conv_id' => 'conv_multi_a']);
    clienteOpenAiConversasMakeAssistantLead($lead, $assistantB, ['conv_id' => 'conv_multi_b']);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
    ]));

    $response->assertOk();
    expect(substr_count($response->getContent(), 'Lead Multi'))->toBe(1);
    $response->assertSee('Assistente A');
    $response->assertSee('Assistente B');
});

test('cards da aba chat nao exibem tags nem bloco visual de conv id', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Assistente Sem Tecnico']);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);
    $lead = clienteOpenAiConversasMakeLead($cliente, ['name' => 'Lead Sem Tecnico']);
    $tag = clienteOpenAiConversasMakeTag($user, $cliente, ['name' => 'Tag Que Nao Deve Aparecer No Card']);
    $lead->tags()->sync([$tag->id]);
    clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_visual_oculto']);

    $response = $this->actingAs($cliente, 'client')->getJson(route('cliente.conversas.index', [
        'tab' => 'chat',
        'cards_only' => 1,
    ]));

    $response->assertOk();
    $html = $response->json('html');
    expect($html)->toContain('Lead Sem Tecnico');
    expect($html)->toContain('Assistente Sem Tecnico');
    expect($html)->not->toContain('Tag Que Nao Deve Aparecer No Card');
    expect($html)->not->toContain('font-mono');
});

test('lead sem conv_id aparece com seletor para iniciar envio', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Assistente Inicial']);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant, ['name' => 'Conexao Inicial']);
    clienteOpenAiConversasMakeLead($cliente, ['name' => 'Lead Sem Chat']);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
    ]));

    $response->assertOk();
    $response->assertSee('Lead Sem Chat');
    $response->assertSee('Sem chat OpenAI');
    $response->assertSee('Conexao Inicial - Assistente Inicial');
});

test('listagem de conversas mostra botao chat somente para leads com conv_id', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    $leadComChat = clienteOpenAiConversasMakeLead($cliente, ['name' => 'Lead Com Chat']);
    clienteOpenAiConversasMakeLead($cliente, ['name' => 'Lead Sem Chat']);
    clienteOpenAiConversasMakeAssistantLead($leadComChat, $assistant, ['conv_id' => 'conv_botao_chat']);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index'));

    $response->assertOk();
    $response->assertSee('Lead Com Chat');
    $response->assertSee('Lead Sem Chat');
    $response->assertSee('tab=chat&amp;conv_id=conv_botao_chat', false);
});

test('card da aba chat mostra ultima mensagem do webhook payload mais recente', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistantA = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Assistente Antigo']);
    $assistantB = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential, ['name' => 'Assistente Novo']);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistantA);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistantB);
    $lead = clienteOpenAiConversasMakeLead($cliente, [
        'name' => 'Carla Resumo',
        'phone' => '5511991112222',
    ]);

    $oldAssistantLead = clienteOpenAiConversasMakeAssistantLead($lead, $assistantA, [
        'conv_id' => 'conv_summary_old',
        'webhook_payload' => ['text' => 'Mensagem antiga do lead'],
    ]);
    $oldAssistantLead->forceFill(['updated_at' => Carbon::parse('2026-04-30 09:15:00')])->saveQuietly();

    $newAssistantLead = clienteOpenAiConversasMakeAssistantLead($lead, $assistantB, [
        'conv_id' => 'conv_summary_new',
        'webhook_payload' => ['text' => 'Mensagem mais recente do lead'],
    ]);
    $newAssistantLead->forceFill(['updated_at' => Carbon::parse('2026-05-01 11:30:00')])->saveQuietly();

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
    ]));

    $response->assertOk();
    $response->assertSee('Carla Resumo');
    $response->assertSee('5511991112222');
    $response->assertSee('01/05/2026 11:30');
    $response->assertSee('Mensagem mais recente do lead');
    $response->assertDontSee('Mensagem antiga do lead');
});

test('card da aba chat mostra fallback quando webhook payload nao tem texto', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);
    $lead = clienteOpenAiConversasMakeLead($cliente, ['name' => 'Lead Sem Texto']);
    clienteOpenAiConversasMakeAssistantLead($lead, $assistant, [
        'conv_id' => 'conv_sem_texto',
        'webhook_payload' => ['text' => ''],
    ]);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
    ]));

    $response->assertOk();
    $response->assertSee('Lead Sem Texto');
    $response->assertSee('Sem última mensagem do lead');
});

test('mensagens iniciais do chat renderizam mais antigas acima das recentes', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    $lead = clienteOpenAiConversasMakeLead($cliente);
    $assistantLead = clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_ordem_inicial']);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);

    Http::fake([
        'https://api.openai.com/v1/conversations/*/items*' => Http::response(clienteOpenAiConversasFakeResponse(), 200),
    ]);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
        'conv_id' => $assistantLead->conv_id,
    ]));

    $response->assertOk();
    $content = $response->getContent();
    expect(strpos($content, 'Tenho interesse.'))->toBeLessThan(strpos($content, 'Olá, eu sou a'));
});

test('botao carregar mais fica dentro do chat antes das mensagens', function () {
    $user = User::factory()->create();
    $cliente = clienteOpenAiConversasMakeCliente($user);
    $credential = clienteOpenAiConversasMakeCredential($user, $cliente);
    $assistant = clienteOpenAiConversasMakeAssistant($user, $cliente, $credential);
    $lead = clienteOpenAiConversasMakeLead($cliente);
    $assistantLead = clienteOpenAiConversasMakeAssistantLead($lead, $assistant, ['conv_id' => 'conv_botao_topo']);
    clienteOpenAiConversasMakeConexao($cliente, $credential, $assistant);

    Http::fake([
        'https://api.openai.com/v1/conversations/*/items*' => Http::response(clienteOpenAiConversasFakeResponseWithMore(), 200),
    ]);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conversas.index', [
        'tab' => 'chat',
        'conv_id' => $assistantLead->conv_id,
    ]));

    $response->assertOk();
    $content = $response->getContent();
    expect(strpos($content, 'id="cliente-chat-items"'))->toBeLessThan(strpos($content, 'id="cliente-chat-load-more-btn"'));
    expect(strpos($content, 'id="cliente-chat-load-more-btn"'))->toBeLessThan(strpos($content, 'Tenho interesse.'));
    $response->assertSee('Carregar mais');
});
