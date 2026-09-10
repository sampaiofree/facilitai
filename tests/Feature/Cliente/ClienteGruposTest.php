<?php

use App\Jobs\ExecuteGrupoConjuntoMensagemJob;
use App\Models\AgencySetting;
use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\GrupoConjunto;
use App\Models\GrupoConjuntoItem;
use App\Models\GrupoConjuntoMensagem;
use App\Models\User;
use App\Models\WhatsappApi;
use App\Services\UazapiGruposService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

function clienteGruposMakeCliente(User $user, array $attributes = []): Cliente
{
    return Cliente::create(array_merge([
        'user_id' => $user->id,
        'nome' => 'Cliente '.fake()->unique()->numerify('###'),
        'email' => fake()->unique()->safeEmail(),
        'telefone' => '11999999999',
        'password' => 'secret123',
        'is_active' => true,
        'can_access_groups' => true,
    ], $attributes));
}

function clienteGruposMakeUazapiProvider(array $attributes = []): WhatsappApi
{
    $slug = (string) ($attributes['slug'] ?? 'uazapi');

    return WhatsappApi::query()->firstOrCreate(
        ['slug' => $slug],
        array_merge([
            'nome' => 'Uazapi',
            'ativo' => true,
        ], $attributes)
    );
}

function clienteGruposMakeUazapiConexao(Cliente $cliente, WhatsappApi $provider, array $attributes = []): Conexao
{
    return Conexao::create(array_merge([
        'name' => 'Conexao '.fake()->unique()->numerify('###'),
        'cliente_id' => $cliente->id,
        'whatsapp_api_id' => $provider->id,
        'whatsapp_api_key' => 'token-'.fake()->unique()->numerify('####'),
        'status' => 'active',
        'is_active' => true,
    ], $attributes));
}

function clienteGruposMakeConjunto(User $user, Conexao $conexao, array $attributes = []): GrupoConjunto
{
    return GrupoConjunto::create(array_merge([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto '.fake()->unique()->numerify('###'),
    ], $attributes));
}

function clienteGruposAddItem(GrupoConjunto $conjunto, array $attributes = []): GrupoConjuntoItem
{
    return GrupoConjuntoItem::create(array_merge([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo A',
    ], $attributes));
}

test('cliente acessa grupos vendo apenas conjuntos das proprias conexoes', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $clienteA = clienteGruposMakeCliente($user, ['nome' => 'Cliente Alpha']);
    $clienteB = clienteGruposMakeCliente($user, ['nome' => 'Cliente Beta']);
    $conexaoA = clienteGruposMakeUazapiConexao($clienteA, $provider, ['name' => 'Conexao Alpha']);
    $conexaoB = clienteGruposMakeUazapiConexao($clienteB, $provider, ['name' => 'Conexao Beta']);
    $conjuntoA = clienteGruposMakeConjunto($user, $conexaoA, ['name' => 'Conjunto Alpha']);
    clienteGruposMakeConjunto($user, $conexaoB, ['name' => 'Conjunto Beta']);
    clienteGruposAddItem($conjuntoA, ['group_name' => 'Grupo Alpha']);

    $response = $this->actingAs($clienteA, 'client')->get(route('cliente.grupos.index'));

    $response->assertOk();
    $response->assertSee('Conjuntos de grupos');
    $response->assertSee('Conjunto Alpha');
    $response->assertSee('Conexao Alpha');
    $response->assertSee('Grupo Alpha');
    $response->assertDontSee('Conjunto Beta');
    $response->assertDontSee('Conexao Beta');
    $response->assertDontSee('Cliente:</span>', false);
    $response->assertSee(route('cliente.grupos.store'), false);
    $response->assertSee(route('cliente.grupos.destroy', $conjuntoA), false);
    $response->assertDontSee(route('agencia.grupos.store'), false);
});

test('cliente cria conjunto usando owner user da agencia', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $cliente = clienteGruposMakeCliente($user);
    $conexao = clienteGruposMakeUazapiConexao($cliente, $provider, ['name' => 'Conexao Cliente']);

    $response = $this->actingAs($cliente, 'client')->post(route('cliente.grupos.store'), [
        'name' => 'Conjunto Cliente',
        'conexao_id' => $conexao->id,
        'groups' => [
            ['jid' => '120363153742561022@g.us', 'name' => 'Grupo Cliente'],
        ],
    ]);

    $conjunto = GrupoConjunto::query()
        ->where('name', 'Conjunto Cliente')
        ->firstOrFail();

    $response->assertRedirect(route('cliente.grupos.index', ['conjunto_id' => $conjunto->id]));

    expect((int) $conjunto->user_id)->toBe((int) $user->id);
    expect((int) $conjunto->conexao_id)->toBe((int) $conexao->id);
    $this->assertDatabaseHas('grupo_conjunto_itens', [
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo Cliente',
    ]);
});

test('cliente nao cria conjunto em conexao de outro cliente', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $clienteA = clienteGruposMakeCliente($user);
    $clienteB = clienteGruposMakeCliente($user);
    $conexaoB = clienteGruposMakeUazapiConexao($clienteB, $provider);

    $this->actingAs($clienteA, 'client')
        ->post(route('cliente.grupos.store'), [
            'name' => 'Conjunto Indevido',
            'conexao_id' => $conexaoB->id,
            'groups' => [
                ['jid' => '120363153742561022@g.us', 'name' => 'Grupo Indevido'],
            ],
        ])
        ->assertNotFound();

    $this->assertDatabaseMissing('grupo_conjuntos', [
        'name' => 'Conjunto Indevido',
    ]);
});

test('cliente nao acessa conjunto ou mensagem de outro cliente da mesma agencia', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $clienteA = clienteGruposMakeCliente($user);
    $clienteB = clienteGruposMakeCliente($user);
    $conexaoB = clienteGruposMakeUazapiConexao($clienteB, $provider);
    $conjuntoB = clienteGruposMakeConjunto($user, $conexaoB, ['name' => 'Conjunto Beta']);
    $mensagemB = GrupoConjuntoMensagem::create([
        'user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'grupo_conjunto_id' => $conjuntoB->id,
        'conexao_id' => $conexaoB->id,
        'mensagem' => 'Mensagem Beta',
        'action_type' => 'send_text',
        'payload' => ['text' => 'Mensagem Beta'],
        'dispatch_type' => 'scheduled',
        'scheduled_for' => Carbon::now('UTC')->addHour(),
        'status' => 'pending',
        'recipients' => [
            ['jid' => '120363153742561022@g.us', 'name' => 'Grupo Beta'],
        ],
    ]);

    $this->actingAs($clienteA, 'client')
        ->get(route('cliente.grupos.index', ['conjunto_id' => $conjuntoB->id]))
        ->assertOk()
        ->assertDontSee('Conjunto Beta');

    $this->actingAs($clienteA, 'client')
        ->delete(route('cliente.grupos.mensagens.destroy', [
            'grupoConjunto' => $conjuntoB,
            'grupoConjuntoMensagem' => $mensagemB,
        ]))
        ->assertNotFound();
});

test('endpoint de grupos do cliente aceita apenas conexao propria', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $clienteA = clienteGruposMakeCliente($user);
    $clienteB = clienteGruposMakeCliente($user);
    $conexaoA = clienteGruposMakeUazapiConexao($clienteA, $provider, ['whatsapp_api_key' => 'token-alpha']);
    $conexaoB = clienteGruposMakeUazapiConexao($clienteB, $provider, ['whatsapp_api_key' => 'token-beta']);

    $this->mock(UazapiGruposService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('listGroups')
            ->once()
            ->with('token-alpha', true, true)
            ->andReturn([
                'data' => [
                    ['JID' => '120363153742561022@g.us', 'Name' => 'Grupo Alpha'],
                ],
            ]);
    });

    $this->actingAs($clienteA, 'client')
        ->get(route('cliente.grupos.conexoes.groups', $conexaoA))
        ->assertOk()
        ->assertJson([
            'data' => [
                ['jid' => '120363153742561022@g.us', 'name' => 'Grupo Alpha'],
            ],
        ]);

    $this->actingAs($clienteA, 'client')
        ->get(route('cliente.grupos.conexoes.groups', $conexaoB))
        ->assertNotFound();
});

test('endpoint de convite do cliente aceita apenas conexao propria', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $clienteA = clienteGruposMakeCliente($user);
    $clienteB = clienteGruposMakeCliente($user);
    $conexaoA = clienteGruposMakeUazapiConexao($clienteA, $provider, ['whatsapp_api_key' => 'token-alpha']);
    $conexaoB = clienteGruposMakeUazapiConexao($clienteB, $provider, ['whatsapp_api_key' => 'token-beta']);
    $inviteLink = 'https://chat.whatsapp.com/IYnl5Zg9bUcJD32rJrDzO7';

    $this->mock(UazapiGruposService::class, function (MockInterface $mock) use ($inviteLink): void {
        $mock->shouldReceive('getGroupInviteInfo')
            ->once()
            ->with('token-alpha', $inviteLink)
            ->andReturn([
                'JID' => '120363153742561022@g.us',
                'Name' => 'Grupo Convite',
            ]);
    });

    $this->actingAs($clienteA, 'client')
        ->get(route('cliente.grupos.conexoes.group-invite', [
            'conexao' => $conexaoA,
            'invite_link' => $inviteLink,
        ]))
        ->assertOk()
        ->assertJson([
            'data' => [
                'jid' => '120363153742561022@g.us',
                'name' => 'Grupo Convite',
            ],
        ]);

    $this->actingAs($clienteA, 'client')
        ->get(route('cliente.grupos.conexoes.group-invite', [
            'conexao' => $conexaoB,
            'invite_link' => $inviteLink,
        ]))
        ->assertNotFound();
});

test('cliente agenda acao registrando user e criador como dono da agencia', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $cliente = clienteGruposMakeCliente($user);
    $conexao = clienteGruposMakeUazapiConexao($cliente, $provider);
    $conjunto = clienteGruposMakeConjunto($user, $conexao, ['name' => 'Conjunto Agenda']);
    clienteGruposAddItem($conjunto, ['group_name' => 'Grupo Agenda']);
    $scheduledFor = Carbon::now('America/Sao_Paulo')->addHours(2)->format('Y-m-d\TH:i');

    $response = $this->actingAs($cliente, 'client')->post(route('cliente.grupos.mensagens.store', $conjunto), [
        'action_type' => 'send_text',
        'text' => 'Mensagem programada pelo cliente',
        'send_type' => 'scheduled',
        'scheduled_for' => $scheduledFor,
    ]);

    $response->assertRedirect(route('cliente.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));

    $registro = GrupoConjuntoMensagem::query()
        ->where('grupo_conjunto_id', $conjunto->id)
        ->firstOrFail();

    expect((int) $registro->user_id)->toBe((int) $user->id);
    expect((int) $registro->created_by_user_id)->toBe((int) $user->id);
    expect($registro->status)->toBe('pending');
    expect($registro->dispatch_type)->toBe('scheduled');
    expect(data_get($registro->payload, 'text'))->toBe('Mensagem programada pelo cliente');
});

test('cliente visualiza horario programado no timezone da agencia quando app usa timezone local', function () {
    $originalAppTimezone = config('app.timezone');
    $originalPhpTimezone = date_default_timezone_get();

    config(['app.timezone' => 'America/Sao_Paulo']);
    date_default_timezone_set('America/Sao_Paulo');

    try {
        $user = User::factory()->create();
        AgencySetting::create([
            'user_id' => $user->id,
            'timezone' => 'America/Sao_Paulo',
        ]);

        $provider = clienteGruposMakeUazapiProvider();
        $cliente = clienteGruposMakeCliente($user);
        $conexao = clienteGruposMakeUazapiConexao($cliente, $provider);
        $conjunto = clienteGruposMakeConjunto($user, $conexao, ['name' => 'Conjunto Horario Local']);
        clienteGruposAddItem($conjunto);

        $scheduledForLocal = Carbon::now('America/Sao_Paulo')->addHours(2)->startOfMinute();
        $scheduledForInput = $scheduledForLocal->format('Y-m-d\\TH:i');

        $this->actingAs($cliente, 'client')->post(route('cliente.grupos.mensagens.store', $conjunto), [
            'action_type' => 'send_text',
            'text' => 'Mensagem no horario local',
            'send_type' => 'scheduled',
            'scheduled_for' => $scheduledForInput,
        ])->assertRedirect();

        $registro = GrupoConjuntoMensagem::query()
            ->where('grupo_conjunto_id', $conjunto->id)
            ->firstOrFail();

        expect($registro->getRawOriginal('scheduled_for'))
            ->toBe($scheduledForLocal->copy()->setTimezone('UTC')->format('Y-m-d H:i:s'));

        $this->actingAs($cliente, 'client')
            ->get(route('cliente.grupos.index', [
                'conjunto_id' => $conjunto->id,
                'tab' => 'messages',
            ]))
            ->assertOk()
            ->assertSee($scheduledForLocal->format('d/m/Y H:i'))
            ->assertSee('&quot;scheduled_for_input&quot;:&quot;'.$scheduledForInput.'&quot;', false);
    } finally {
        config(['app.timezone' => $originalAppTimezone]);
        date_default_timezone_set($originalPhpTimezone);
    }
});

test('cliente envia acao imediata para fila usando owner user da agencia', function () {
    Queue::fake();

    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $cliente = clienteGruposMakeCliente($user);
    $conexao = clienteGruposMakeUazapiConexao($cliente, $provider, ['whatsapp_api_key' => 'token-cliente']);
    $conjunto = clienteGruposMakeConjunto($user, $conexao, ['name' => 'Conjunto Imediato']);
    clienteGruposAddItem($conjunto, ['group_name' => 'Grupo Imediato']);

    $this->mock(UazapiGruposService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('sendTextToGroup')
            ->never();
    });

    $response = $this->actingAs($cliente, 'client')->post(route('cliente.grupos.mensagens.store', $conjunto), [
        'action_type' => 'send_text',
        'text' => 'Mensagem imediata pelo cliente',
        'send_type' => 'now',
    ]);

    $response->assertRedirect(route('cliente.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));

    $registro = GrupoConjuntoMensagem::query()
        ->where('grupo_conjunto_id', $conjunto->id)
        ->firstOrFail();

    expect((int) $registro->user_id)->toBe((int) $user->id);
    expect((int) $registro->created_by_user_id)->toBe((int) $user->id);
    expect($registro->status)->toBe('queued');
    expect($registro->dispatch_type)->toBe('now');
    expect($registro->queued_at)->not->toBeNull();
    expect(data_get($registro->payload, 'text'))->toBe('Mensagem imediata pelo cliente');
    Queue::assertPushedOn('processarconversa', ExecuteGrupoConjuntoMensagemJob::class);
});

test('cliente cria titulos sequenciais imediatos e programados na ordem alfabetica persistida', function () {
    Queue::fake();

    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $cliente = clienteGruposMakeCliente($user);
    $conexao = clienteGruposMakeUazapiConexao($cliente, $provider);
    $conjunto = clienteGruposMakeConjunto($user, $conexao, ['name' => 'Conjunto Sequencial']);
    clienteGruposAddItem($conjunto, [
        'group_jid' => '120363222222222222@g.us',
        'group_name' => 'Grupo Zeta',
    ]);
    clienteGruposAddItem($conjunto, [
        'group_jid' => '120363111111111111@g.us',
        'group_name' => 'Grupo Alpha',
    ]);

    $this->actingAs($cliente, 'client')->post(route('cliente.grupos.mensagens.store', $conjunto), [
        'action_type' => 'update_group_name',
        'group_name' => '  Grupo  ',
        'group_name_sequence' => '1',
        'group_name_sequence_start' => 23,
        'send_type' => 'now',
    ])->assertRedirect(route('cliente.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));

    $immediate = GrupoConjuntoMensagem::query()->latest('id')->firstOrFail();

    expect($immediate->payload)->toMatchArray([
        'group_name' => 'Grupo',
        'group_name_sequence' => true,
        'group_name_sequence_start' => 23,
    ]);
    expect($immediate->recipients)->toBe([
        ['jid' => '120363111111111111@g.us', 'name' => 'Grupo Alpha'],
        ['jid' => '120363222222222222@g.us', 'name' => 'Grupo Zeta'],
    ]);
    expect($immediate->toEditorPayload())->toMatchArray([
        'group_name' => 'Grupo',
        'group_name_sequence' => true,
        'group_name_sequence_start' => 23,
    ]);

    $scheduledFor = Carbon::now('America/Sao_Paulo')->addHours(2)->format('Y-m-d\TH:i');
    $this->actingAs($cliente, 'client')->post(route('cliente.grupos.mensagens.store', $conjunto), [
        'action_type' => 'update_group_name',
        'group_name' => 'Turma',
        'group_name_sequence' => '1',
        'group_name_sequence_start' => 1,
        'send_type' => 'scheduled',
        'scheduled_for' => $scheduledFor,
    ])->assertRedirect();

    $scheduled = GrupoConjuntoMensagem::query()->latest('id')->firstOrFail();
    expect($scheduled->status)->toBe('pending');
    expect($scheduled->payload)->toMatchArray([
        'group_name' => 'Turma',
        'group_name_sequence' => true,
        'group_name_sequence_start' => 1,
    ]);
    Queue::assertPushed(ExecuteGrupoConjuntoMensagemJob::class, 1);
});

test('cliente preserva a sequencia ao editar uma acao pendente', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $cliente = clienteGruposMakeCliente($user);
    $conexao = clienteGruposMakeUazapiConexao($cliente, $provider);
    $conjunto = clienteGruposMakeConjunto($user, $conexao);
    clienteGruposAddItem($conjunto);

    $mensagem = GrupoConjuntoMensagem::create([
        'user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'grupo_conjunto_id' => $conjunto->id,
        'conexao_id' => $conexao->id,
        'mensagem' => 'Título sequencial',
        'action_type' => 'update_group_name',
        'payload' => [
            'group_name' => 'Grupo',
            'group_name_sequence' => true,
            'group_name_sequence_start' => 23,
        ],
        'dispatch_type' => 'scheduled',
        'scheduled_for' => Carbon::now('UTC')->addHours(2),
        'status' => 'pending',
        'recipients' => [
            ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
        ],
    ]);

    expect($mensagem->toEditorPayload())->toMatchArray([
        'group_name_sequence' => true,
        'group_name_sequence_start' => 23,
    ]);

    $scheduledFor = Carbon::now('America/Sao_Paulo')->addHours(3)->format('Y-m-d\TH:i');
    $this->actingAs($cliente, 'client')->patch(route('cliente.grupos.mensagens.update', [
        'grupoConjunto' => $conjunto,
        'grupoConjuntoMensagem' => $mensagem,
    ]), [
        'action_type' => 'update_group_name',
        'group_name' => 'Novo Grupo',
        'group_name_sequence' => '1',
        'group_name_sequence_start' => 23,
        'send_type' => 'scheduled',
        'scheduled_for' => $scheduledFor,
    ])->assertRedirect();

    expect($mensagem->fresh()->payload)->toMatchArray([
        'group_name' => 'Novo Grupo',
        'group_name_sequence' => true,
        'group_name_sequence_start' => 23,
    ]);
});

test('cliente bloqueia sequencia cujo maior titulo ultrapassa 25 caracteres', function () {
    Queue::fake();

    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $cliente = clienteGruposMakeCliente($user);
    $conexao = clienteGruposMakeUazapiConexao($cliente, $provider);
    $conjunto = clienteGruposMakeConjunto($user, $conexao);
    clienteGruposAddItem($conjunto, ['group_jid' => '120363111111111111@g.us']);
    clienteGruposAddItem($conjunto, ['group_jid' => '120363222222222222@g.us']);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.grupos.index', ['conjunto_id' => $conjunto->id, 'tab' => 'messages']))
        ->post(route('cliente.grupos.mensagens.store', $conjunto), [
            'action_type' => 'update_group_name',
            'group_name' => str_repeat('A', 21),
            'group_name_sequence' => '1',
            'group_name_sequence_start' => 99,
            'send_type' => 'now',
        ]);

    $response->assertSessionHasErrors([
        'group_name' => 'Para esta sequência, o título-base pode ter no máximo 20 caracteres, pois o maior sufixo será #100.',
    ]);
    expect(GrupoConjuntoMensagem::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

test('cliente sem sequencia mantem o titulo unico legado', function () {
    Queue::fake();

    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $cliente = clienteGruposMakeCliente($user);
    $conexao = clienteGruposMakeUazapiConexao($cliente, $provider);
    $conjunto = clienteGruposMakeConjunto($user, $conexao);
    clienteGruposAddItem($conjunto);

    $this->actingAs($cliente, 'client')->post(route('cliente.grupos.mensagens.store', $conjunto), [
        'action_type' => 'update_group_name',
        'group_name' => 'Título único',
        'send_type' => 'now',
    ])->assertRedirect();

    $mensagem = GrupoConjuntoMensagem::query()->firstOrFail();
    expect($mensagem->payload)->toBe(['group_name' => 'Título único']);
    expect($mensagem->toEditorPayload())->toMatchArray([
        'group_name_sequence' => false,
        'group_name_sequence_start' => 1,
    ]);
});

test('cliente consulta falhas amigaveis das acoes do proprio conjunto', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $cliente = clienteGruposMakeCliente($user);
    $conexao = clienteGruposMakeUazapiConexao($cliente, $provider);
    $conjunto = clienteGruposMakeConjunto($user, $conexao);

    $mensagem = GrupoConjuntoMensagem::create([
        'user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'grupo_conjunto_id' => $conjunto->id,
        'conexao_id' => $conexao->id,
        'mensagem' => 'Ação com falha parcial',
        'action_type' => 'send_text',
        'payload' => ['text' => 'Ação com falha parcial'],
        'dispatch_type' => 'now',
        'status' => 'failed',
        'recipients' => [],
        'result' => [
            'items' => [
                [
                    'jid' => '120363153742561022@g.us',
                    'name' => 'Grupo sem autorização',
                    'status' => 'failed',
                    'http_status' => 401,
                    'error' => 'segredo bruto unauthorized',
                ],
                [
                    'jid' => '120363339858396166@g.us',
                    'name' => 'Grupo limitado',
                    'status' => 'failed',
                    'http_status' => 429,
                    'error' => 'segredo bruto rate limit',
                ],
                [
                    'jid' => '120363000000000000@g.us',
                    'name' => 'Grupo enviado',
                    'status' => 'sent',
                    'http_status' => 200,
                ],
            ],
        ],
        'sent_count' => 1,
        'failed_count' => 2,
        'failed_at' => Carbon::now('UTC'),
        'error_message' => 'segredo bruto do provedor',
    ]);

    $response = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.grupos.mensagens.status', $conjunto));

    $response
        ->assertOk()
        ->assertJsonPath('has_processing', false)
        ->assertJsonPath('data.0.id', $mensagem->id)
        ->assertJsonPath('data.0.status', 'failed')
        ->assertJsonPath('data.0.status_label', 'Falhou')
        ->assertJsonPath('data.0.sent_count', 1)
        ->assertJsonPath('data.0.failed_count', 2)
        ->assertJsonPath('data.0.failure.message', 'A conexão não está autorizada para executar esta ação. Verifique a conexão e tente novamente.')
        ->assertJsonPath('data.0.failure.items.0.name', 'Grupo sem autorização')
        ->assertJsonPath('data.0.failure.items.0.http_status', 401)
        ->assertJsonPath('data.0.failure.items.1.message', 'O limite temporário de requisições foi atingido. Tente novamente em alguns instantes.')
        ->assertJsonMissing(['name' => 'Grupo enviado']);

    $response->assertDontSee('segredo bruto', false);
});

test('endpoint de status usa fallback seguro e informa processamento', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $cliente = clienteGruposMakeCliente($user);
    $conexao = clienteGruposMakeUazapiConexao($cliente, $provider);
    $conjunto = clienteGruposMakeConjunto($user, $conexao);

    GrupoConjuntoMensagem::create([
        'user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'grupo_conjunto_id' => $conjunto->id,
        'conexao_id' => $conexao->id,
        'mensagem' => 'Falha do job',
        'dispatch_type' => 'now',
        'status' => 'failed',
        'recipients' => [],
        'result' => ['items' => 'resultado legado inválido'],
        'error_message' => '/var/www/app segredo interno da exceção',
    ]);

    GrupoConjuntoMensagem::create([
        'user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'grupo_conjunto_id' => $conjunto->id,
        'conexao_id' => $conexao->id,
        'mensagem' => 'Na fila',
        'dispatch_type' => 'now',
        'status' => 'queued',
        'recipients' => [],
    ]);

    $response = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.grupos.mensagens.status', $conjunto));

    $response
        ->assertOk()
        ->assertJsonPath('has_processing', true)
        ->assertJsonPath('data.1.failure.message', 'Não foi possível identificar o motivo da falha.')
        ->assertJsonPath('data.1.failure.items', []);

    $response->assertDontSee('segredo interno', false);
});

test('cliente nao consulta status de conjunto de outro cliente', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $clienteA = clienteGruposMakeCliente($user);
    $clienteB = clienteGruposMakeCliente($user);
    $conexaoB = clienteGruposMakeUazapiConexao($clienteB, $provider);
    $conjuntoB = clienteGruposMakeConjunto($user, $conexaoB);

    $this->actingAs($clienteA, 'client')
        ->getJson(route('cliente.grupos.mensagens.status', $conjuntoB))
        ->assertNotFound();
});

test('detalhes de falha aparecem apenas na view do cliente', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $cliente = clienteGruposMakeCliente($user);
    $conexao = clienteGruposMakeUazapiConexao($cliente, $provider);
    $conjunto = clienteGruposMakeConjunto($user, $conexao);

    GrupoConjuntoMensagem::create([
        'user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'grupo_conjunto_id' => $conjunto->id,
        'conexao_id' => $conexao->id,
        'mensagem' => 'Falhou',
        'dispatch_type' => 'now',
        'status' => 'failed',
        'recipients' => [],
        'failed_count' => 1,
        'error_message' => 'timeout',
    ]);

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.grupos.index', ['conjunto_id' => $conjunto->id, 'tab' => 'messages']))
        ->assertOk()
        ->assertSee('id="groupFailureModal"', false)
        ->assertSee('id="groupMessageGroupNameSequence"', false)
        ->assertSee('Adicionar sequência automática')
        ->assertSee('data-view-group-failure', false)
        ->assertSee(route('cliente.grupos.mensagens.status', $conjunto), false);

    $this->actingAs($user)
        ->get(route('agencia.grupos.index', ['conjunto_id' => $conjunto->id, 'tab' => 'messages']))
        ->assertOk()
        ->assertDontSee('id="groupFailureModal"', false)
        ->assertDontSee('id="groupMessageGroupNameSequence"', false)
        ->assertDontSee('Adicionar sequência automática')
        ->assertDontSee('data-view-group-failure', false);
});

test('cliente sem permissao nao ve menu nem acessa grupos diretamente', function () {
    $user = User::factory()->create();
    $provider = clienteGruposMakeUazapiProvider();
    $cliente = clienteGruposMakeCliente($user, ['can_access_groups' => false]);
    $conexao = clienteGruposMakeUazapiConexao($cliente, $provider);
    $conjunto = clienteGruposMakeConjunto($user, $conexao, ['name' => 'Conjunto Bloqueado']);
    clienteGruposAddItem($conjunto);

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.dashboard'))
        ->assertOk()
        ->assertDontSee(route('cliente.grupos.index'), false);

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.grupos.index'))
        ->assertForbidden();

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.grupos.conexoes.groups', $conexao))
        ->assertForbidden();

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.grupos.mensagens.status', $conjunto))
        ->assertForbidden();
});
