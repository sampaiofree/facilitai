<?php

use App\Models\Cliente;
use App\Models\KommoAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function agenciaKommoMakeCliente(User $user, array $attributes = []): Cliente
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

function agenciaKommoMakeAccount(User $user, Cliente $cliente, array $attributes = []): KommoAccount
{
    return KommoAccount::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'kommo-principal-' . fake()->unique()->numerify('###'),
        'subdomain' => fake()->unique()->slug(),
        'access_token' => 'kommo-token',
        'kommo_account_id' => '123456',
        'kommo_account_name' => 'Conta Teste',
        'pipeline_id' => '10',
        'pipeline_name' => 'Pipeline Principal',
        'status_id' => '100',
        'status_name' => 'Novo Lead',
        'last_verified_at' => now(),
        'last_verification_error' => null,
    ], $attributes));
}

function agenciaKommoFakeAccountValidation(string $subdomain, string $accountId = '111222', string $accountName = 'Conta A'): void
{
    Http::fake([
        "https://{$subdomain}.kommo.com/api/v4/account" => Http::response([
            'id' => $accountId,
            'name' => $accountName,
        ], 200),
    ]);
}

function agenciaKommoFakeDestinationResponses(string $subdomain, array $pipelines, array $statusesByPipeline): void
{
    $responses = [
        "https://{$subdomain}.kommo.com/api/v4/leads/pipelines" => Http::response([
            '_embedded' => ['pipelines' => $pipelines],
        ], 200),
    ];

    foreach ($statusesByPipeline as $pipelineId => $statuses) {
        $responses["https://{$subdomain}.kommo.com/api/v4/leads/pipelines/{$pipelineId}/statuses"] = Http::response([
            '_embedded' => ['statuses' => $statuses],
        ], 200);
    }

    Http::fake($responses);
}

test('agencia lista apenas integracoes kommo do usuario logado e mostra destino padrao', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $cliente = agenciaKommoMakeCliente($user);
    $otherCliente = agenciaKommoMakeCliente($otherUser, ['email' => fake()->unique()->safeEmail()]);

    $account = agenciaKommoMakeAccount($user, $cliente, [
        'name' => 'kommo-vendas-sp',
        'pipeline_name' => 'Pipeline Vendas',
        'status_name' => 'Contato',
    ]);
    agenciaKommoMakeAccount($otherUser, $otherCliente, ['name' => 'kommo-externa']);

    $this->actingAs($user)
        ->get(route('agencia.kommo.index'))
        ->assertOk()
        ->assertSee('kommo-vendas-sp')
        ->assertSee('Pipeline Vendas')
        ->assertSee('Contato')
        ->assertDontSee('kommo-externa')
        ->assertSee(route('agencia.kommo.destroy', $account), false);
});

test('agencia valida conexao kommo antes de salvar', function () {
    $user = User::factory()->create();

    Http::fake([
        'https://minhaconta.kommo.com/api/v4/account' => Http::response([
            'id' => 987654,
            'name' => 'Conta Kommo',
            'subdomain' => 'minhaconta',
        ], 200),
    ]);

    $this->actingAs($user)
        ->postJson(route('agencia.kommo.validate'), [
            'subdomain' => 'https://minhaconta.kommo.com',
            'access_token' => 'token-kommo',
        ])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('account_id', '987654')
        ->assertJsonPath('account_name', 'Conta Kommo')
        ->assertJsonPath('subdomain', 'minhaconta');
});

test('agencia carrega pipelines e estagios do kommo', function () {
    $user = User::factory()->create();

    Http::fake([
        'https://cliente-a.kommo.com/api/v4/leads/pipelines' => Http::response([
            '_embedded' => [
                'pipelines' => [
                    ['id' => 10, 'name' => 'Pipeline A'],
                ],
            ],
        ], 200),
        'https://cliente-a.kommo.com/api/v4/leads/pipelines/10/statuses' => Http::response([
            '_embedded' => [
                'statuses' => [
                    ['id' => 100, 'name' => 'Novo Lead'],
                ],
            ],
        ], 200),
    ]);

    $this->actingAs($user)
        ->postJson(route('agencia.kommo.pipelines'), [
            'subdomain' => 'cliente-a',
            'access_token' => 'token-a',
        ])
        ->assertOk()
        ->assertJsonPath('pipelines.0.id', '10')
        ->assertJsonPath('pipelines.0.name', 'Pipeline A');

    $this->actingAs($user)
        ->postJson(route('agencia.kommo.statuses'), [
            'subdomain' => 'cliente-a',
            'access_token' => 'token-a',
            'pipeline_id' => '10',
        ])
        ->assertOk()
        ->assertJsonPath('statuses.0.id', '100')
        ->assertJsonPath('statuses.0.name', 'Novo Lead');
});

test('agencia cria integracao kommo valida com destino padrao e criptografa token no banco', function () {
    $user = User::factory()->create();
    $cliente = agenciaKommoMakeCliente($user);

    Http::fake([
        'https://cliente-a.kommo.com/api/v4/account' => Http::response([
            'id' => 111222,
            'name' => 'Conta A',
        ], 200),
        'https://cliente-a.kommo.com/api/v4/leads/pipelines' => Http::response([
            '_embedded' => [
                'pipelines' => [
                    ['id' => 10, 'name' => 'Pipeline Vendas'],
                ],
            ],
        ], 200),
        'https://cliente-a.kommo.com/api/v4/leads/pipelines/10/statuses' => Http::response([
            '_embedded' => [
                'statuses' => [
                    ['id' => 100, 'name' => 'Novo Lead'],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)->post(route('agencia.kommo.store'), [
        'cliente_id' => $cliente->id,
        'name' => 'Kommo Vendas SP',
        'subdomain' => 'cliente-a',
        'access_token' => 'super-secreto',
        'pipeline_id' => '10',
        'status_id' => '100',
    ]);

    $response->assertRedirect(route('agencia.kommo.index'));
    $response->assertSessionHas('success');

    $account = KommoAccount::query()->sole();

    expect($account->cliente_id)->toBe($cliente->id);
    expect($account->name)->toBe('kommo-vendas-sp');
    expect($account->subdomain)->toBe('cliente-a');
    expect($account->kommo_account_id)->toBe('111222');
    expect($account->kommo_account_name)->toBe('Conta A');
    expect($account->pipeline_id)->toBe('10');
    expect($account->pipeline_name)->toBe('Pipeline Vendas');
    expect($account->status_id)->toBe('100');
    expect($account->status_name)->toBe('Novo Lead');
    expect($account->last_verified_at)->not->toBeNull();
    expect($account->access_token)->toBe('super-secreto');

    $rawToken = DB::table('kommo_accounts')->where('id', $account->id)->value('access_token');
    expect($rawToken)->not->toBe('super-secreto');
});

test('agencia bloqueia cliente de outro usuario ao criar integracao kommo', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCliente = agenciaKommoMakeCliente($otherUser);

    Http::fake();

    $response = $this->actingAs($user)
        ->from(route('agencia.kommo.index'))
        ->post(route('agencia.kommo.store'), [
            'cliente_id' => $otherCliente->id,
            'name' => 'kommo-invalido',
            'subdomain' => 'conta-invalida',
            'access_token' => 'token',
            'pipeline_id' => '10',
            'status_id' => '100',
        ]);

    $response->assertRedirect(route('agencia.kommo.index'));
    $response->assertSessionHasErrors('cliente_id');
    expect(KommoAccount::query()->count())->toBe(0);
});

test('agencia bloqueia nome duplicado por usuario', function () {
    $user = User::factory()->create();
    $clienteA = agenciaKommoMakeCliente($user);
    $clienteB = agenciaKommoMakeCliente($user, ['email' => fake()->unique()->safeEmail()]);

    agenciaKommoMakeAccount($user, $clienteA, ['name' => 'kommo-vendas']);

    Http::fake();

    $response = $this->actingAs($user)
        ->from(route('agencia.kommo.index'))
        ->post(route('agencia.kommo.store'), [
            'cliente_id' => $clienteB->id,
            'name' => 'Kommo Vendas',
            'subdomain' => 'duplicado',
            'access_token' => 'token-a',
            'pipeline_id' => '10',
            'status_id' => '100',
        ]);

    $response->assertRedirect(route('agencia.kommo.index'));
    $response->assertSessionHasErrors('name');
});

test('agencia permite mesmo subdomain para o mesmo cliente com nomes diferentes', function () {
    $user = User::factory()->create();
    $cliente = agenciaKommoMakeCliente($user);

    agenciaKommoMakeAccount($user, $cliente, ['name' => 'kommo-a', 'subdomain' => 'duplicado']);

    Http::fake([
        'https://duplicado.kommo.com/api/v4/account' => Http::response([
            'id' => 999,
            'name' => 'Conta Duplicada',
        ], 200),
        'https://duplicado.kommo.com/api/v4/leads/pipelines' => Http::response([
            '_embedded' => [
                'pipelines' => [
                    ['id' => 10, 'name' => 'Pipeline Duplicada'],
                ],
            ],
        ], 200),
        'https://duplicado.kommo.com/api/v4/leads/pipelines/10/statuses' => Http::response([
            '_embedded' => [
                'statuses' => [
                    ['id' => 100, 'name' => 'Novo Lead'],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)->post(route('agencia.kommo.store'), [
        'cliente_id' => $cliente->id,
        'name' => 'kommo-b',
        'subdomain' => 'duplicado',
        'access_token' => 'token-b',
        'pipeline_id' => '10',
        'status_id' => '100',
    ]);

    $response->assertRedirect(route('agencia.kommo.index'));
    expect(KommoAccount::query()->count())->toBe(2);
});

test('agencia exige pipeline e estagio ao criar integracao', function () {
    $user = User::factory()->create();
    $cliente = agenciaKommoMakeCliente($user);

    Http::fake();

    $response = $this->actingAs($user)
        ->from(route('agencia.kommo.index'))
        ->post(route('agencia.kommo.store'), [
            'cliente_id' => $cliente->id,
            'name' => 'kommo-a',
            'subdomain' => 'cliente-a',
            'access_token' => 'token-a',
            'pipeline_id' => '',
            'status_id' => '',
        ]);

    $response->assertRedirect(route('agencia.kommo.index'));
    $response->assertSessionHasErrors(['pipeline_id', 'status_id']);
});

test('agencia atualiza apenas pipeline e estagio sem revalidar credenciais', function () {
    $user = User::factory()->create();
    $cliente = agenciaKommoMakeCliente($user);
    $account = agenciaKommoMakeAccount($user, $cliente, [
        'name' => 'kommo-atual',
        'subdomain' => 'conta-atual',
        'access_token' => 'token-atual',
        'pipeline_id' => '10',
        'status_id' => '100',
    ]);

    Http::fake([
        'https://conta-atual.kommo.com/api/v4/leads/pipelines' => Http::response([
            '_embedded' => [
                'pipelines' => [
                    ['id' => 10, 'name' => 'Pipeline A'],
                    ['id' => 20, 'name' => 'Pipeline B'],
                ],
            ],
        ], 200),
        'https://conta-atual.kommo.com/api/v4/leads/pipelines/20/statuses' => Http::response([
            '_embedded' => [
                'statuses' => [
                    ['id' => 200, 'name' => 'Qualificado'],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)->patch(route('agencia.kommo.update', $account), [
        'cliente_id' => $cliente->id,
        'name' => 'kommo-atual',
        'subdomain' => 'conta-atual',
        'access_token' => '',
        'pipeline_id' => '20',
        'status_id' => '200',
        'form_mode' => 'edit',
        'kommo_account_context_id' => $account->id,
    ]);

    $response->assertRedirect(route('agencia.kommo.index'));
    $account->refresh();

    expect($account->pipeline_id)->toBe('20');
    expect($account->pipeline_name)->toBe('Pipeline B');
    expect($account->status_id)->toBe('200');
    expect($account->status_name)->toBe('Qualificado');

    Http::assertSentCount(2);
    Http::assertNotSent(fn ($request) => $request->url() === 'https://conta-atual.kommo.com/api/v4/account');
});

test('agencia bloqueia alteracao de nome e cliente depois da criacao', function () {
    $user = User::factory()->create();
    $clienteA = agenciaKommoMakeCliente($user);
    $clienteB = agenciaKommoMakeCliente($user, ['email' => fake()->unique()->safeEmail()]);
    $account = agenciaKommoMakeAccount($user, $clienteA, ['name' => 'kommo-fixo']);

    Http::fake();

    $response = $this->actingAs($user)
        ->from(route('agencia.kommo.index'))
        ->patch(route('agencia.kommo.update', $account), [
            'cliente_id' => $clienteB->id,
            'name' => 'kommo-editado',
            'subdomain' => $account->subdomain,
            'access_token' => '',
            'pipeline_id' => $account->pipeline_id,
            'status_id' => $account->status_id,
            'form_mode' => 'edit',
            'kommo_account_context_id' => $account->id,
        ]);

    $response->assertRedirect(route('agencia.kommo.index'));
    $response->assertSessionHasErrors(['cliente_id', 'name']);
});

test('agencia revalida e exige novo destino ao trocar subdomain usando token atual', function () {
    $user = User::factory()->create();
    $cliente = agenciaKommoMakeCliente($user);
    $account = agenciaKommoMakeAccount($user, $cliente, [
        'name' => 'kommo-atualizado',
        'subdomain' => 'subantigo',
        'access_token' => 'token-antigo',
        'kommo_account_id' => '1',
        'kommo_account_name' => 'Conta Antiga',
        'pipeline_id' => '10',
        'status_id' => '100',
    ]);

    Http::fake([
        'https://subnovo.kommo.com/api/v4/account' => Http::response([
            'id' => 2,
            'name' => 'Conta Nova',
        ], 200),
        'https://subnovo.kommo.com/api/v4/leads/pipelines' => Http::response([
            '_embedded' => [
                'pipelines' => [
                    ['id' => 20, 'name' => 'Pipeline Nova'],
                ],
            ],
        ], 200),
        'https://subnovo.kommo.com/api/v4/leads/pipelines/20/statuses' => Http::response([
            '_embedded' => [
                'statuses' => [
                    ['id' => 200, 'name' => 'Contato'],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)->patch(route('agencia.kommo.update', $account), [
        'cliente_id' => $cliente->id,
        'name' => 'kommo-atualizado',
        'subdomain' => 'subnovo',
        'access_token' => '',
        'pipeline_id' => '20',
        'status_id' => '200',
        'form_mode' => 'edit',
        'kommo_account_context_id' => $account->id,
    ]);

    $response->assertRedirect(route('agencia.kommo.index'));

    $account->refresh();
    expect($account->subdomain)->toBe('subnovo');
    expect($account->kommo_account_id)->toBe('2');
    expect($account->kommo_account_name)->toBe('Conta Nova');
    expect($account->pipeline_id)->toBe('20');
    expect($account->status_id)->toBe('200');
    expect($account->access_token)->toBe('token-antigo');

    Http::assertSent(fn ($request) => $request->url() === 'https://subnovo.kommo.com/api/v4/account');
});

test('agencia bloqueia update quando pipeline ou estagio salvo nao existem mais no kommo', function () {
    $user = User::factory()->create();
    $cliente = agenciaKommoMakeCliente($user);
    $account = agenciaKommoMakeAccount($user, $cliente, [
        'name' => 'kommo-invalido',
        'subdomain' => 'conta-atual',
        'pipeline_id' => '10',
        'status_id' => '100',
    ]);

    Http::fake([
        'https://conta-atual.kommo.com/api/v4/leads/pipelines' => Http::response([
            '_embedded' => [
                'pipelines' => [
                    ['id' => 20, 'name' => 'Pipeline B'],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)
        ->from(route('agencia.kommo.index'))
        ->patch(route('agencia.kommo.update', $account), [
            'cliente_id' => $cliente->id,
            'name' => 'kommo-invalido',
            'subdomain' => 'conta-atual',
            'access_token' => '',
            'pipeline_id' => '10',
            'status_id' => '100',
            'form_mode' => 'edit',
            'kommo_account_context_id' => $account->id,
        ]);

    $response->assertRedirect(route('agencia.kommo.index'));
    $response->assertSessionHasErrors('pipeline_id');
});

test('agencia nao permite excluir integracao kommo de outro usuario', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCliente = agenciaKommoMakeCliente($otherUser);
    $otherAccount = agenciaKommoMakeAccount($otherUser, $otherCliente);

    $this->actingAs($user)
        ->delete(route('agencia.kommo.destroy', $otherAccount))
        ->assertForbidden();

    expect(KommoAccount::query()->whereKey($otherAccount->id)->exists())->toBeTrue();
});
