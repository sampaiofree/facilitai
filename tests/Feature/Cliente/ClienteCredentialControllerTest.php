<?php

use App\Models\Cliente;
use App\Models\Credential;
use App\Models\Iaplataforma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function clienteCredentialMakeCliente(User $user, array $attributes = []): Cliente
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

function clienteCredentialMakePlatform(array $attributes = []): Iaplataforma
{
    return Iaplataforma::create(array_merge([
        'nome' => 'OpenAI',
        'ativo' => true,
    ], $attributes));
}

function clienteCredentialMakeCredential(User $user, Iaplataforma $platform, array $attributes = []): Credential
{
    return $user->credentials()->create(array_merge([
        'name' => 'OpenAI Principal',
        'label' => 'OpenAI Principal',
        'token' => 'super-secreto',
        'iaplataforma_id' => $platform->id,
        'cliente_id' => null,
    ], $attributes));
}

test('menu de cliente mostra credenciais apenas quando existe credencial vinculada', function () {
    $user = User::factory()->create();
    $cliente = clienteCredentialMakeCliente($user);
    $platform = clienteCredentialMakePlatform();

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.dashboard'))
        ->assertOk()
        ->assertDontSee(route('cliente.credentials.index'), false)
        ->assertDontSee('Credenciais');

    clienteCredentialMakeCredential($user, $platform, [
        'cliente_id' => $cliente->id,
    ]);

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.dashboard'))
        ->assertOk()
        ->assertSee(route('cliente.credentials.index'), false)
        ->assertSee('Credenciais');
});

test('cliente sem credencial vinculada e redirecionado ao tentar abrir a tela', function () {
    $user = User::factory()->create();
    $cliente = clienteCredentialMakeCliente($user);

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.credentials.index'))
        ->assertRedirect(route('cliente.dashboard'))
        ->assertSessionHas('error', 'Nenhuma credencial vinculada à sua conta.');
});

test('cliente visualiza apenas credenciais vinculadas e nao pode criar ou excluir', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $cliente = clienteCredentialMakeCliente($user, ['nome' => 'Cliente Logado']);
    $otherCliente = clienteCredentialMakeCliente($otherUser, ['nome' => 'Cliente Externo']);
    $platform = clienteCredentialMakePlatform();

    clienteCredentialMakeCredential($user, $platform, [
        'name' => 'Credencial Cliente',
        'label' => 'Credencial Cliente',
        'cliente_id' => $cliente->id,
    ]);
    clienteCredentialMakeCredential($user, $platform, [
        'name' => 'Credencial Global',
        'label' => 'Credencial Global',
        'cliente_id' => null,
    ]);
    clienteCredentialMakeCredential($otherUser, $platform, [
        'name' => 'Credencial Externa',
        'label' => 'Credencial Externa',
        'cliente_id' => $otherCliente->id,
    ]);

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.credentials.index'))
        ->assertOk()
        ->assertSee('Credencial Cliente')
        ->assertDontSee('Credencial Global')
        ->assertDontSee('Credencial Externa')
        ->assertDontSee('Nova credencial')
        ->assertDontSee('Excluir');
});

test('cliente pode atualizar apenas o token da credencial vinculada', function () {
    $user = User::factory()->create();
    $cliente = clienteCredentialMakeCliente($user);
    $platform = clienteCredentialMakePlatform();
    $credential = clienteCredentialMakeCredential($user, $platform, [
        'name' => 'Credencial Cliente',
        'label' => 'Credencial Cliente',
        'cliente_id' => $cliente->id,
    ]);

    $response = $this->actingAs($cliente, 'client')
        ->patch(route('cliente.credentials.update', $credential), [
            'editing_id' => $credential->id,
            'token' => 'novo-token-seguro',
        ]);

    $response->assertRedirect(route('cliente.credentials.index'));
    $response->assertSessionHas('success', 'Token atualizado com sucesso.');

    $credential->refresh();

    expect($credential->token)->toBe('novo-token-seguro');
    expect(DB::table('credentials')->where('id', $credential->id)->value('token'))->not->toBe('novo-token-seguro');
    expect($credential->name)->toBe('Credencial Cliente');
});

test('cliente nao pode atualizar credencial nao relacionada', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $cliente = clienteCredentialMakeCliente($user);
    $otherCliente = clienteCredentialMakeCliente($otherUser);
    $platform = clienteCredentialMakePlatform();
    $credential = clienteCredentialMakeCredential($otherUser, $platform, [
        'cliente_id' => $otherCliente->id,
    ]);

    $this->actingAs($cliente, 'client')
        ->patch(route('cliente.credentials.update', $credential), [
            'editing_id' => $credential->id,
            'token' => 'token-invalido',
        ])
        ->assertForbidden();

    expect($credential->fresh()->token)->toBe('super-secreto');
});
