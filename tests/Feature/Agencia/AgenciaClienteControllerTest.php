<?php

use App\Models\Cliente;
use App\Models\User;

function agenciaClientesMakeCliente(User $user, array $attributes = []): Cliente
{
    return Cliente::create(array_merge([
        'user_id' => $user->id,
        'nome' => 'Cliente ' . fake()->unique()->numerify('###'),
        'email' => fake()->unique()->safeEmail(),
        'telefone' => '11999999999',
        'password' => 'secret123',
        'is_active' => true,
        'can_access_groups' => false,
    ], $attributes));
}

test('agencia cria cliente com acesso a grupos liberado quando marcado', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('agencia.clientes.index'))
        ->post(route('agencia.clientes.store'), [
            'nome' => 'Cliente Grupos',
            'email' => 'cliente-grupos@example.com',
            'telefone' => '11999999999',
            'is_active' => '1',
            'can_access_groups' => '1',
            'password' => 'secret123',
        ]);

    $response->assertRedirect(route('agencia.clientes.index'));

    $cliente = Cliente::query()
        ->where('email', 'cliente-grupos@example.com')
        ->firstOrFail();

    expect($cliente->can_access_groups)->toBeTrue();
});

test('agencia cria cliente com acesso a grupos bloqueado quando desmarcado', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('agencia.clientes.index'))
        ->post(route('agencia.clientes.store'), [
            'nome' => 'Cliente Sem Grupos',
            'email' => 'cliente-sem-grupos@example.com',
            'telefone' => '11999999999',
            'is_active' => '1',
            'password' => 'secret123',
        ]);

    $response->assertRedirect(route('agencia.clientes.index'));

    $cliente = Cliente::query()
        ->where('email', 'cliente-sem-grupos@example.com')
        ->firstOrFail();

    expect($cliente->can_access_groups)->toBeFalse();
});

test('agencia edita cliente alternando acesso a grupos', function () {
    $user = User::factory()->create();
    $cliente = agenciaClientesMakeCliente($user, [
        'nome' => 'Cliente Editavel',
        'email' => 'cliente-editavel@example.com',
        'can_access_groups' => false,
    ]);

    $this->actingAs($user)
        ->from(route('agencia.clientes.index'))
        ->patch(route('agencia.clientes.update', $cliente), [
            'nome' => 'Cliente Editavel',
            'email' => 'cliente-editavel@example.com',
            'telefone' => '11999999999',
            'is_active' => '1',
            'can_access_groups' => '1',
        ])
        ->assertRedirect(route('agencia.clientes.index'));

    expect($cliente->fresh()->can_access_groups)->toBeTrue();

    $this->actingAs($user)
        ->from(route('agencia.clientes.index'))
        ->patch(route('agencia.clientes.update', $cliente), [
            'nome' => 'Cliente Editavel',
            'email' => 'cliente-editavel@example.com',
            'telefone' => '11999999999',
            'is_active' => '1',
        ])
        ->assertRedirect(route('agencia.clientes.index'));

    expect($cliente->fresh()->can_access_groups)->toBeFalse();
});

test('agencia visualiza estado de acesso a grupos na lista e no botao de edicao', function () {
    $user = User::factory()->create();
    $liberado = agenciaClientesMakeCliente($user, [
        'nome' => 'Cliente Liberado',
        'email' => 'liberado@example.com',
        'can_access_groups' => true,
    ]);
    $bloqueado = agenciaClientesMakeCliente($user, [
        'nome' => 'Cliente Bloqueado',
        'email' => 'bloqueado@example.com',
        'can_access_groups' => false,
    ]);

    $this->actingAs($user)
        ->get(route('agencia.clientes.index'))
        ->assertOk()
        ->assertSee('Grupos')
        ->assertSee('Liberado')
        ->assertSee('Bloqueado')
        ->assertSee('data-can-access-groups="1"', false)
        ->assertSee('data-can-access-groups="0"', false);
});
