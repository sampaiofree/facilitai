<?php

use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\GrupoConjunto;
use App\Models\GrupoConjuntoItem;
use App\Models\GrupoConjuntoMensagem;
use App\Models\User;
use App\Models\WhatsappApi;
use App\Jobs\ExecuteGrupoConjuntoMensagemJob;
use App\Services\UazapiGruposService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

function clienteGruposMakeCliente(User $user, array $attributes = []): Cliente
{
    return Cliente::create(array_merge([
        'user_id' => $user->id,
        'nome' => 'Cliente ' . fake()->unique()->numerify('###'),
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
        'name' => 'Conexao ' . fake()->unique()->numerify('###'),
        'cliente_id' => $cliente->id,
        'whatsapp_api_id' => $provider->id,
        'whatsapp_api_key' => 'token-' . fake()->unique()->numerify('####'),
        'status' => 'active',
        'is_active' => true,
    ], $attributes));
}

function clienteGruposMakeConjunto(User $user, Conexao $conexao, array $attributes = []): GrupoConjunto
{
    return GrupoConjunto::create(array_merge([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto ' . fake()->unique()->numerify('###'),
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
});
