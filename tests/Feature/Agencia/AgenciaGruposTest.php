<?php

use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\AgencySetting;
use App\Models\GrupoConjunto;
use App\Models\GrupoConjuntoItem;
use App\Models\GrupoConjuntoMensagem;
use App\Models\User;
use App\Models\WhatsappApi;
use App\Services\UazapiGruposService;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function agenciaGruposMakeCliente(User $user, array $attributes = []): Cliente
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

function agenciaGruposMakeUazapiProvider(array $attributes = []): WhatsappApi
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

function agenciaGruposMakeUazapiConexao(User $user, WhatsappApi $provider, array $attributes = []): Conexao
{
    $cliente = $attributes['cliente'] ?? agenciaGruposMakeCliente($user);

    return Conexao::create(array_merge([
        'name' => 'Conexao ' . fake()->numerify('###'),
        'cliente_id' => $cliente->id,
        'whatsapp_api_id' => $provider->id,
        'whatsapp_api_key' => 'token-' . fake()->numerify('####'),
        'status' => 'active',
        'is_active' => true,
    ], array_diff_key($attributes, ['cliente' => true])));
}

test('usuario autenticado acessa a view de grupos', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider, ['name' => 'Conexao Principal']);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto de Teste',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo A',
    ]);

    $response = $this->actingAs($user)->get(route('agencia.grupos.index'));

    $response->assertOk();
    $response->assertSee('Conjuntos de grupos');
    $response->assertSee('Conjunto de Teste');
    $response->assertSee('Conexao Principal');
    $response->assertSee('Conjunto selecionado');
    $response->assertSee('Grupo A');
});

test('estado vazio exibe cta de novo conjunto', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('agencia.grupos.index'));

    $response->assertOk();
    $response->assertSee('Nenhum conjunto de grupos cadastrado.');
    $response->assertSee('Novo conjunto');
    $response->assertSee('id="openGrupoModal"', false);
});

test('index com conjunto_id seleciona o conjunto correto no painel direito', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $conjuntoA = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto A',
    ]);

    $conjuntoB = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto B',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjuntoA->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo do A',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjuntoB->id,
        'group_jid' => '120363339858396166@g.us',
        'group_name' => 'Grupo do B',
    ]);

    $response = $this->actingAs($user)->get(route('agencia.grupos.index', ['conjunto_id' => $conjuntoB->id]));

    $response->assertOk();
    $response->assertSeeInOrder(['Conjunto selecionado', 'Conjunto B']);
});

test('index sem conjunto_id aplica fallback para primeiro conjunto por nome', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $conjuntoZ = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Zeta',
    ]);

    $conjuntoA = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Alfa',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjuntoA->id,
        'group_jid' => '120363308883996631@g.us',
        'group_name' => 'Grupo Alfa',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjuntoZ->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo Zeta',
    ]);

    $response = $this->actingAs($user)->get(route('agencia.grupos.index'));

    $response->assertOk();
    $response->assertSeeInOrder(['Conjunto selecionado', 'Alfa']);
});

test('index com conjunto_id invalido aplica fallback sem quebrar', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto Unico',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo Unico',
    ]);

    $response = $this->actingAs($user)->get(route('agencia.grupos.index', ['conjunto_id' => 999999]));

    $response->assertOk();
    $response->assertSeeInOrder(['Conjunto selecionado', 'Conjunto Unico']);
});

test('acoes de editar e excluir conjunto aparecem apenas no painel direito do selecionado', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $conjuntoA = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto A',
    ]);

    $conjuntoB = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto B',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjuntoA->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo A',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjuntoB->id,
        'group_jid' => '120363339858396166@g.us',
        'group_name' => 'Grupo B',
    ]);

    $response = $this->actingAs($user)->get(route('agencia.grupos.index', ['conjunto_id' => $conjuntoB->id]));

    $response->assertOk();
    $response->assertSee('id="openGrupoModal"', false);
    $response->assertSee(route('agencia.grupos.destroy', $conjuntoB), false);
    $response->assertDontSee(route('agencia.grupos.destroy', $conjuntoA), false);

    $html = $response->getContent();
    $htmlWithoutScripts = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
    expect(substr_count($htmlWithoutScripts, 'data-action="edit-conjunto"'))->toBe(1);
});

test('cria conjunto com grupos da lista e manual', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $response = $this->actingAs($user)->post(route('agencia.grupos.store'), [
        'name' => 'Campanha Abril',
        'conexao_id' => $conexao->id,
        'groups' => [
            [
                'jid' => '120363153742561022@g.us',
                'name' => 'Grupo Lista',
            ],
            [
                'jid' => '120363339858396166@g.us',
                'name' => 'Grupo Manual',
            ],
        ],
    ]);

    $this->assertDatabaseHas('grupo_conjuntos', [
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Campanha Abril',
    ]);

    $conjuntoId = (int) GrupoConjunto::query()
        ->where('user_id', $user->id)
        ->where('name', 'Campanha Abril')
        ->value('id');

    $response->assertRedirect(route('agencia.grupos.index', ['conjunto_id' => $conjuntoId]));

    $this->assertDatabaseHas('grupo_conjunto_itens', [
        'grupo_conjunto_id' => $conjuntoId,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo Lista',
    ]);

    $this->assertDatabaseHas('grupo_conjunto_itens', [
        'grupo_conjunto_id' => $conjuntoId,
        'group_jid' => '120363339858396166@g.us',
        'group_name' => 'Grupo Manual',
    ]);
});

test('edita conjunto e sincroniza itens removendo os nao selecionados', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto Inicial',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo Antigo A',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363339858396166@g.us',
        'group_name' => 'Grupo Antigo B',
    ]);

    $response = $this->actingAs($user)->post(route('agencia.grupos.store'), [
        'grupo_conjunto_id' => $conjunto->id,
        'name' => 'Conjunto Atualizado',
        'conexao_id' => $conexao->id,
        'groups' => [
            [
                'jid' => '120363153742561022@g.us',
                'name' => 'Grupo Atualizado A',
            ],
            [
                'jid' => '120363308883996631@g.us',
                'name' => 'Grupo Novo C',
            ],
        ],
    ]);

    $response->assertRedirect(route('agencia.grupos.index', ['conjunto_id' => $conjunto->id]));

    $this->assertDatabaseHas('grupo_conjuntos', [
        'id' => $conjunto->id,
        'name' => 'Conjunto Atualizado',
    ]);

    $this->assertDatabaseHas('grupo_conjunto_itens', [
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo Atualizado A',
    ]);

    $this->assertDatabaseHas('grupo_conjunto_itens', [
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363308883996631@g.us',
        'group_name' => 'Grupo Novo C',
    ]);

    $this->assertDatabaseMissing('grupo_conjunto_itens', [
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363339858396166@g.us',
    ]);
});

test('nao permite usar conexao de outro usuario ao salvar conjunto', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $provider = agenciaGruposMakeUazapiProvider();
    $ownerConexao = agenciaGruposMakeUazapiConexao($owner, $provider);

    $response = $this->actingAs($attacker)->post(route('agencia.grupos.store'), [
        'name' => 'Tentativa Invalida',
        'conexao_id' => $ownerConexao->id,
        'groups' => [
            [
                'jid' => '120363153742561022@g.us',
                'name' => 'Grupo',
            ],
        ],
    ]);

    $response->assertNotFound();
});

test('retorna erro de validacao para group jid invalido', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $response = $this->actingAs($user)->from(route('agencia.grupos.index'))->post(route('agencia.grupos.store'), [
        'name' => 'Conjunto Invalido',
        'conexao_id' => $conexao->id,
        'groups' => [
            [
                'jid' => 'grupo-invalido',
                'name' => 'Grupo Invalido',
            ],
        ],
    ]);

    $response->assertRedirect(route('agencia.grupos.index'));
    $response->assertSessionHasErrors(['groups.0.jid']);
});

test('retorna erro de validacao quando nenhum grupo e informado', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $response = $this->actingAs($user)->from(route('agencia.grupos.index'))->post(route('agencia.grupos.store'), [
        'name' => 'Sem Grupos',
        'conexao_id' => $conexao->id,
        'groups' => [],
    ]);

    $response->assertRedirect(route('agencia.grupos.index'));
    $response->assertSessionHasErrors(['groups']);
});

test('exclui conjunto e remove itens associados', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto para Excluir',
    ]);

    $item = GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo X',
    ]);

    $response = $this->actingAs($user)->delete(route('agencia.grupos.destroy', $conjunto));

    $response->assertRedirect(route('agencia.grupos.index'));

    $this->assertDatabaseMissing('grupo_conjuntos', ['id' => $conjunto->id]);
    $this->assertDatabaseMissing('grupo_conjunto_itens', ['id' => $item->id]);
});

test('exclui conjunto e redireciona para proximo conjunto quando existir', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $conjuntoA = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Alfa',
    ]);

    $conjuntoB = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Beta',
    ]);

    $response = $this->actingAs($user)->delete(route('agencia.grupos.destroy', $conjuntoA));

    $response->assertRedirect(route('agencia.grupos.index', ['conjunto_id' => $conjuntoB->id]));
});

test('endpoint de grupos da conexao retorna dados normalizados em sucesso', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider, ['whatsapp_api_key' => 'token-abc']);

        $this->mock(UazapiGruposService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('listGroups')
                ->once()
                ->with('token-abc', true, true)
                ->andReturn([
                    'data' => [
                        ['JID' => '120363153742561022@g.us', 'Name' => 'Grupo A'],
                    ['jid' => '120363339858396166@g.us', 'subject' => 'Grupo B'],
                    ['groupjid' => '120363308883996631@g.us', 'name' => 'Grupo C'],
                    ['jid' => 'invalido'],
                ],
            ]);
    });

    $response = $this->actingAs($user)->get(route('agencia.grupos.conexoes.groups', $conexao));

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
    $response->assertJson([
        'data' => [
            ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
            ['jid' => '120363339858396166@g.us', 'name' => 'Grupo B'],
            ['jid' => '120363308883996631@g.us', 'name' => 'Grupo C'],
        ],
    ]);
});

test('endpoint de grupos da conexao filtra por search em nome e jid', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider, ['whatsapp_api_key' => 'token-abc']);

        $this->mock(UazapiGruposService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('listGroups')
                ->once()
                ->with('token-abc', true, true)
                ->andReturn([
                    'data' => [
                        ['JID' => '120363153742561022@g.us', 'Name' => 'FacilitAI Vendas'],
                    ['jid' => '120363339858396166@g.us', 'subject' => 'Grupo Suporte'],
                    ['jid' => '120300000000000000@g.us', 'name' => 'Comercial'],
                ],
            ]);
    });

    $responseByName = $this->actingAs($user)->get(route('agencia.grupos.conexoes.groups', [
        'conexao' => $conexao,
        'search' => 'facilit',
    ]));

    $responseByName->assertOk();
    $responseByName->assertJsonCount(1, 'data');
    $responseByName->assertJson([
        'data' => [
            ['jid' => '120363153742561022@g.us', 'name' => 'FacilitAI Vendas'],
        ],
    ]);
});

test('endpoint de grupos da conexao retorna erro consistente quando uazapi falha', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider, ['whatsapp_api_key' => 'token-abc']);

        $this->mock(UazapiGruposService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('listGroups')
                ->once()
                ->with('token-abc', true, true)
                ->andReturn([
                    'error' => true,
                    'status' => 500,
                'body' => ['message' => 'Erro remoto'],
            ]);
    });

    $response = $this->actingAs($user)->get(route('agencia.grupos.conexoes.groups', $conexao));

    $response->assertStatus(500);
    $response->assertJson([
        'error' => true,
        'message' => 'Erro remoto',
    ]);
});

test('endpoint de convite da conexao resolve grupo por link e normaliza retorno', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider, ['whatsapp_api_key' => 'token-abc']);
    $inviteLink = 'https://chat.whatsapp.com/IYnl5Zg9bUcJD32rJrDzO7';

    $this->mock(UazapiGruposService::class, function (MockInterface $mock) use ($inviteLink): void {
        $mock->shouldReceive('getGroupInviteInfo')
            ->once()
            ->with('token-abc', $inviteLink)
            ->andReturn([
                'JID' => '120363153742561022@g.us',
                'Name' => 'Grupo Convite',
            ]);
    });

    $response = $this->actingAs($user)->get(route('agencia.grupos.conexoes.group-invite', [
        'conexao' => $conexao,
        'invite_link' => $inviteLink,
    ]));

    $response->assertOk();
    $response->assertJson([
        'data' => [
            'jid' => '120363153742561022@g.us',
            'name' => 'Grupo Convite',
        ],
    ]);
});

test('endpoint de convite da conexao retorna erro consistente quando uazapi falha', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider, ['whatsapp_api_key' => 'token-abc']);

    $this->mock(UazapiGruposService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getGroupInviteInfo')
            ->once()
            ->with('token-abc', 'https://chat.whatsapp.com/codigo-invalido')
            ->andReturn([
                'error' => true,
                'status' => 400,
                'body' => ['message' => 'Convite inválido'],
            ]);
    });

    $response = $this->actingAs($user)->get(route('agencia.grupos.conexoes.group-invite', [
        'conexao' => $conexao,
        'invite_link' => 'https://chat.whatsapp.com/codigo-invalido',
    ]));

    $response->assertStatus(400);
    $response->assertJson([
        'error' => true,
        'message' => 'Convite inválido',
    ]);
});

test('cria acao imediata send_text para conjunto e registra envio', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider, ['whatsapp_api_key' => 'token-xyz']);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto Mensageria',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo A',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363339858396166@g.us',
        'group_name' => 'Grupo B',
    ]);

    $this->mock(UazapiGruposService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('sendTextToGroup')
            ->times(2)
            ->withArgs(function (string $token, string $jid, string $text): bool {
                return $token === 'token-xyz'
                    && in_array($jid, ['120363153742561022@g.us', '120363339858396166@g.us'], true)
                    && $text === 'Mensagem imediata para grupos';
            })
            ->andReturn(['ok' => true, 'status' => 200]);
    });

    $response = $this->actingAs($user)->post(route('agencia.grupos.mensagens.store', $conjunto), [
        'action_type' => 'send_text',
        'text' => 'Mensagem imediata para grupos',
        'send_type' => 'now',
    ]);

    $response->assertRedirect(route('agencia.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));

    $registro = GrupoConjuntoMensagem::query()
        ->where('grupo_conjunto_id', $conjunto->id)
        ->latest('id')
        ->firstOrFail();

    expect($registro->dispatch_type)->toBe('now');
    expect($registro->status)->toBe('sent');
    expect($registro->action_type)->toBe('send_text');
    expect(data_get($registro->payload, 'text'))->toBe('Mensagem imediata para grupos');
    expect($registro->sent_count)->toBe(2);
    expect($registro->failed_count)->toBe(0);
});

test('cria acao send_text com marcar todos e envia mentions all', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider, ['whatsapp_api_key' => 'token-mentions']);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto Mention All',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo A',
    ]);

    $this->mock(UazapiGruposService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('sendTextToGroup')
            ->once()
            ->with(
                'token-mentions',
                '120363153742561022@g.us',
                'Aviso para todos',
                ['mentions' => 'all']
            )
            ->andReturn(['ok' => true, 'status' => 200]);
    });

    $response = $this->actingAs($user)->post(route('agencia.grupos.mensagens.store', $conjunto), [
        'action_type' => 'send_text',
        'text' => 'Aviso para todos',
        'mention_all' => '1',
        'send_type' => 'now',
    ]);

    $response->assertRedirect(route('agencia.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));

    $registro = GrupoConjuntoMensagem::query()
        ->where('grupo_conjunto_id', $conjunto->id)
        ->latest('id')
        ->firstOrFail();

    expect($registro->status)->toBe('sent');
    expect(data_get($registro->payload, 'mention_all'))->toBeTrue();
});

test('index da aba mensagens exibe historico unificado de acoes', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto Historico',
    ]);

    GrupoConjuntoMensagem::create([
        'user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'grupo_conjunto_id' => $conjunto->id,
        'conexao_id' => $conexao->id,
        'mensagem' => 'Texto teste',
        'action_type' => 'send_text',
        'payload' => ['text' => 'Texto teste'],
        'dispatch_type' => 'scheduled',
        'scheduled_for' => Carbon::now('UTC')->addHour(),
        'status' => 'pending',
        'recipients' => [
            ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
        ],
    ]);

    GrupoConjuntoMensagem::create([
        'user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'grupo_conjunto_id' => $conjunto->id,
        'conexao_id' => $conexao->id,
        'mensagem' => 'Midia [image] https://example.com/banner.jpg',
        'action_type' => 'send_media',
        'payload' => [
            'media_type' => 'image',
            'media_url' => 'https://example.com/banner.jpg',
        ],
        'dispatch_type' => 'scheduled',
        'scheduled_for' => Carbon::now('UTC')->addHours(2),
        'status' => 'pending',
        'recipients' => [
            ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
        ],
    ]);

    $response = $this->actingAs($user)->get(route('agencia.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));

    $response->assertOk();
    $response->assertSee('Ações do conjunto');
    $response->assertSee('Enviar texto');
    $response->assertSee('Enviar mídia');
});

test('cria acao imediata send_media com legenda opcional', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider, ['whatsapp_api_key' => 'token-mid']);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto Midia',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo A',
    ]);

    $this->mock(UazapiGruposService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('sendMediaToGroup')
            ->once()
            ->with(
                'token-mid',
                '120363153742561022@g.us',
                'image',
                'https://example.com/banner.jpg',
                ['text' => 'Legenda da imagem']
            )
            ->andReturn(['ok' => true, 'status' => 200]);
    });

    $response = $this->actingAs($user)->post(route('agencia.grupos.mensagens.store', $conjunto), [
        'action_type' => 'send_media',
        'media_type' => 'image',
        'media_url' => 'https://example.com/banner.jpg',
        'caption' => 'Legenda da imagem',
        'send_type' => 'now',
    ]);

    $response->assertRedirect(route('agencia.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));

    $registro = GrupoConjuntoMensagem::query()
        ->where('grupo_conjunto_id', $conjunto->id)
        ->latest('id')
        ->firstOrFail();

    expect($registro->action_type)->toBe('send_media');
    expect(data_get($registro->payload, 'media_type'))->toBe('image');
    expect(data_get($registro->payload, 'media_url'))->toBe('https://example.com/banner.jpg');
    expect(data_get($registro->payload, 'caption'))->toBe('Legenda da imagem');
    expect($registro->status)->toBe('sent');
});

test('cria acoes imediatas de update de grupo chamando endpoints corretos', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider, ['whatsapp_api_key' => 'token-upd']);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto Atualizacoes',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo A',
    ]);

    $this->mock(UazapiGruposService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('updateGroupName')
            ->once()
            ->with('token-upd', '120363153742561022@g.us', 'Novo Titulo')
            ->andReturn(['ok' => true, 'status' => 200]);

        $mock->shouldReceive('updateGroupDescription')
            ->once()
            ->with('token-upd', '120363153742561022@g.us', 'Nova descricao oficial')
            ->andReturn(['ok' => true, 'status' => 200]);

        $mock->shouldReceive('updateGroupImage')
            ->once()
            ->with('token-upd', '120363153742561022@g.us', 'https://example.com/new-photo.png')
            ->andReturn(['ok' => true, 'status' => 200]);
    });

    $this->actingAs($user)->post(route('agencia.grupos.mensagens.store', $conjunto), [
        'action_type' => 'update_group_name',
        'group_name' => 'Novo Titulo',
        'send_type' => 'now',
    ])->assertRedirect();

    $this->actingAs($user)->post(route('agencia.grupos.mensagens.store', $conjunto), [
        'action_type' => 'update_group_description',
        'group_description' => 'Nova descricao oficial',
        'send_type' => 'now',
    ])->assertRedirect();

    $this->actingAs($user)->post(route('agencia.grupos.mensagens.store', $conjunto), [
        'action_type' => 'update_group_image',
        'group_image_url' => 'https://example.com/new-photo.png',
        'send_type' => 'now',
    ])->assertRedirect();

    $this->assertDatabaseHas('grupo_conjunto_mensagens', [
        'grupo_conjunto_id' => $conjunto->id,
        'action_type' => 'update_group_name',
        'status' => 'sent',
    ]);
    $this->assertDatabaseHas('grupo_conjunto_mensagens', [
        'grupo_conjunto_id' => $conjunto->id,
        'action_type' => 'update_group_description',
        'status' => 'sent',
    ]);
    $this->assertDatabaseHas('grupo_conjunto_mensagens', [
        'grupo_conjunto_id' => $conjunto->id,
        'action_type' => 'update_group_image',
        'status' => 'sent',
    ]);
});

test('cria acoes programadas com timezone de agency setting', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    AgencySetting::create([
        'user_id' => $user->id,
        'timezone' => 'America/Manaus',
    ]);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto Programado',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo A',
    ]);

    $scheduledText = Carbon::now('America/Manaus')->addHours(3)->format('Y-m-d\\TH:i');
    $scheduledUpdate = Carbon::now('America/Manaus')->addHours(4)->format('Y-m-d\\TH:i');

    $responseA = $this->actingAs($user)->post(route('agencia.grupos.mensagens.store', $conjunto), [
        'action_type' => 'send_text',
        'text' => 'Mensagem programada',
        'send_type' => 'scheduled',
        'scheduled_for' => $scheduledText,
    ]);

    $responseB = $this->actingAs($user)->post(route('agencia.grupos.mensagens.store', $conjunto), [
        'action_type' => 'update_group_name',
        'group_name' => 'Titulo Programado',
        'send_type' => 'scheduled',
        'scheduled_for' => $scheduledUpdate,
    ]);

    $responseA->assertRedirect(route('agencia.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));
    $responseB->assertRedirect(route('agencia.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));

    $this->assertDatabaseHas('grupo_conjunto_mensagens', [
        'user_id' => $user->id,
        'grupo_conjunto_id' => $conjunto->id,
        'action_type' => 'send_text',
        'dispatch_type' => 'scheduled',
        'status' => 'pending',
    ]);
    $this->assertDatabaseHas('grupo_conjunto_mensagens', [
        'user_id' => $user->id,
        'grupo_conjunto_id' => $conjunto->id,
        'action_type' => 'update_group_name',
        'dispatch_type' => 'scheduled',
        'status' => 'pending',
    ]);
});

test('edita acao pendente e envia agora usando o mesmo modal', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider, ['whatsapp_api_key' => 'token-xyz']);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto Editavel',
    ]);

    $mensagem = GrupoConjuntoMensagem::create([
        'user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'grupo_conjunto_id' => $conjunto->id,
        'conexao_id' => $conexao->id,
        'mensagem' => 'Midia antiga',
        'action_type' => 'send_media',
        'payload' => [
            'media_type' => 'image',
            'media_url' => 'https://example.com/old.jpg',
        ],
        'dispatch_type' => 'scheduled',
        'scheduled_for' => Carbon::now('UTC')->addHour(),
        'status' => 'pending',
        'recipients' => [
            ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
        ],
    ]);

    $this->mock(UazapiGruposService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('sendMediaToGroup')
            ->once()
            ->with(
                'token-xyz',
                '120363153742561022@g.us',
                'document',
                'https://example.com/novo-arquivo.pdf',
                ['text' => 'Confira o arquivo']
            )
            ->andReturn(['ok' => true, 'status' => 200]);
    });

    $response = $this->actingAs($user)->patch(route('agencia.grupos.mensagens.update', [
        'grupoConjunto' => $conjunto,
        'grupoConjuntoMensagem' => $mensagem,
    ]), [
        'action_type' => 'send_media',
        'media_type' => 'document',
        'media_url' => 'https://example.com/novo-arquivo.pdf',
        'caption' => 'Confira o arquivo',
        'send_type' => 'now',
    ]);

    $response->assertRedirect(route('agencia.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));

    $this->assertDatabaseHas('grupo_conjunto_mensagens', [
        'id' => $mensagem->id,
        'action_type' => 'send_media',
        'dispatch_type' => 'now',
        'status' => 'sent',
    ]);
});

test('retorna erro de validacao por tipo de acao', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto Validacao',
    ]);

    GrupoConjuntoItem::create([
        'grupo_conjunto_id' => $conjunto->id,
        'group_jid' => '120363153742561022@g.us',
        'group_name' => 'Grupo A',
    ]);

    $responseMedia = $this->actingAs($user)
        ->from(route('agencia.grupos.index', ['conjunto_id' => $conjunto->id, 'tab' => 'messages']))
        ->post(route('agencia.grupos.mensagens.store', $conjunto), [
            'action_type' => 'send_media',
            'media_type' => 'image',
            'media_url' => 'arquivo-invalido',
            'send_type' => 'now',
        ]);

    $responseMedia->assertRedirect(route('agencia.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));
    $responseMedia->assertSessionHasErrors(['media_url']);

    $responseText = $this->actingAs($user)
        ->from(route('agencia.grupos.index', ['conjunto_id' => $conjunto->id, 'tab' => 'messages']))
        ->post(route('agencia.grupos.mensagens.store', $conjunto), [
            'action_type' => 'send_text',
            'text' => '',
            'send_type' => 'now',
        ]);

    $responseText->assertRedirect(route('agencia.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));
    $responseText->assertSessionHasErrors(['text']);
});

test('exclui registro de acao do conjunto', function () {
    $user = User::factory()->create();
    $provider = agenciaGruposMakeUazapiProvider();
    $conexao = agenciaGruposMakeUazapiConexao($user, $provider);

    $conjunto = GrupoConjunto::create([
        'user_id' => $user->id,
        'conexao_id' => $conexao->id,
        'name' => 'Conjunto com Mensagens',
    ]);

    $mensagem = GrupoConjuntoMensagem::create([
        'user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'grupo_conjunto_id' => $conjunto->id,
        'conexao_id' => $conexao->id,
        'mensagem' => 'Registro para remover',
        'action_type' => 'send_text',
        'payload' => ['text' => 'Registro para remover'],
        'dispatch_type' => 'scheduled',
        'scheduled_for' => Carbon::now('UTC')->addHour(),
        'status' => 'pending',
        'recipients' => [
            ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
        ],
    ]);

    $response = $this->actingAs($user)->delete(route('agencia.grupos.mensagens.destroy', [
        'grupoConjunto' => $conjunto,
        'grupoConjuntoMensagem' => $mensagem,
    ]));

    $response->assertRedirect(route('agencia.grupos.index', [
        'conjunto_id' => $conjunto->id,
        'tab' => 'messages',
    ]));

    $this->assertDatabaseMissing('grupo_conjunto_mensagens', [
        'id' => $mensagem->id,
    ]);
});
