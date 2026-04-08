<?php

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

function clientePasswordMakeCliente(User $user, array $attributes = []): Cliente
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

test('cliente autenticado acessa a tela de alteracao de senha', function () {
    $user = User::factory()->create();
    $cliente = clientePasswordMakeCliente($user);

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.password.edit'))
        ->assertOk()
        ->assertSee('Alterar senha')
        ->assertSee('Senha atual')
        ->assertSee('Nova senha');
});

test('visitante e redirecionado para o login do cliente ao acessar tela de senha', function () {
    $this->get(route('cliente.password.edit'))
        ->assertRedirect(route('cliente.login'));
});

test('cliente pode atualizar a propria senha', function () {
    $user = User::factory()->create();
    $cliente = clientePasswordMakeCliente($user);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.password.edit'))
        ->put(route('cliente.password.update'), [
            'current_password' => 'secret123',
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ]);

    $response
        ->assertRedirect(route('cliente.password.edit'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Senha atualizada com sucesso.');

    expect(Hash::check('novaSenha123', $cliente->refresh()->password))->toBeTrue();
    expect(Auth::guard('client')->validate([
        'email' => $cliente->email,
        'password' => 'secret123',
        'is_active' => true,
    ]))->toBeFalse();
    expect(Auth::guard('client')->validate([
        'email' => $cliente->email,
        'password' => 'novaSenha123',
        'is_active' => true,
    ]))->toBeTrue();
});

test('cliente precisa informar a senha atual correta para atualizar a senha', function () {
    $user = User::factory()->create();
    $cliente = clientePasswordMakeCliente($user);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.password.edit'))
        ->put(route('cliente.password.update'), [
            'current_password' => 'senha-incorreta',
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ]);

    $response
        ->assertRedirect(route('cliente.password.edit'))
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('secret123', $cliente->refresh()->password))->toBeTrue();
});

test('cliente precisa confirmar a nova senha corretamente', function () {
    $user = User::factory()->create();
    $cliente = clientePasswordMakeCliente($user);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.password.edit'))
        ->put(route('cliente.password.update'), [
            'current_password' => 'secret123',
            'password' => 'novaSenha123',
            'password_confirmation' => 'diferente123',
        ]);

    $response
        ->assertRedirect(route('cliente.password.edit'))
        ->assertSessionHasErrors('password');

    expect(Hash::check('secret123', $cliente->refresh()->password))->toBeTrue();
});

test('header do cliente exibe dropdown de conta com senha e sair', function () {
    $user = User::factory()->create();
    $cliente = clientePasswordMakeCliente($user, ['nome' => 'Cliente Longo Teste']);

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.dashboard'))
        ->assertOk()
        ->assertSee('data-client-account-trigger', false)
        ->assertSee('data-client-account-menu', false)
        ->assertSee('Cliente Longo Teste')
        ->assertSee(route('cliente.password.edit'), false)
        ->assertSee(route('cliente.logout'), false)
        ->assertSee('Senha')
        ->assertSee('Sair');
});
