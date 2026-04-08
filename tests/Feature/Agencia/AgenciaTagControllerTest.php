<?php

use App\Models\Cliente;
use App\Models\Tag;
use App\Models\User;

function agenciaTagMakeCliente(User $user, array $attributes = []): Cliente
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

test('agencia recebe erro amigavel ao tentar criar tag duplicada para o mesmo cliente', function () {
    $user = User::factory()->create();
    $cliente = agenciaTagMakeCliente($user);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'Aguardando',
        'color' => null,
        'description' => null,
    ]);

    $response = $this->actingAs($user)
        ->from(route('agencia.tags.index'))
        ->post(route('agencia.tags.store'), [
            'name' => 'Aguardando',
            'cliente_id' => $cliente->id,
            'description' => 'Duplicada',
        ]);

    $response
        ->assertRedirect(route('agencia.tags.index'))
        ->assertSessionHasErrors('name');

    expect(Tag::query()->where('user_id', $user->id)->where('name', 'Aguardando')->count())->toBe(1);
});

test('agencia pode criar tags com mesmo nome para clientes diferentes', function () {
    $user = User::factory()->create();
    $clienteA = agenciaTagMakeCliente($user);
    $clienteB = agenciaTagMakeCliente($user);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => $clienteA->id,
        'name' => 'Aguardando',
        'color' => null,
        'description' => null,
    ]);

    $response = $this->actingAs($user)
        ->from(route('agencia.tags.index'))
        ->post(route('agencia.tags.store'), [
            'name' => 'Aguardando',
            'cliente_id' => $clienteB->id,
            'description' => 'Mesmo nome em outro cliente',
        ]);

    $response
        ->assertRedirect(route('agencia.tags.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Tag criada com sucesso.');

    expect(Tag::query()->where('user_id', $user->id)->where('name', 'Aguardando')->count())->toBe(2);
});

test('agencia exige cliente ao criar uma tag', function () {
    $user = User::factory()->create();
    agenciaTagMakeCliente($user);

    $response = $this->actingAs($user)
        ->from(route('agencia.tags.index'))
        ->post(route('agencia.tags.store'), [
            'name' => 'Aguardando',
            'description' => 'Sem cliente',
        ]);

    $response
        ->assertRedirect(route('agencia.tags.index'))
        ->assertSessionHasErrors('cliente_id');

    expect(Tag::query()->where('user_id', $user->id)->where('name', 'Aguardando')->exists())->toBeFalse();
});

test('agencia recebe erro amigavel ao tentar criar tag de cliente com nome de tag global legada', function () {
    $user = User::factory()->create();
    $cliente = agenciaTagMakeCliente($user);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'name' => 'Aguardando',
        'color' => null,
        'description' => null,
    ]);

    $response = $this->actingAs($user)
        ->from(route('agencia.tags.index'))
        ->post(route('agencia.tags.store'), [
            'name' => 'Aguardando',
            'cliente_id' => $cliente->id,
            'description' => 'Conflito com global legado',
        ]);

    $response
        ->assertRedirect(route('agencia.tags.index'))
        ->assertSessionHasErrors('name');

    expect(Tag::query()->where('user_id', $user->id)->where('name', 'Aguardando')->count())->toBe(1);
});

test('agencia lista apenas tags vinculadas a clientes', function () {
    $user = User::factory()->create();
    $cliente = agenciaTagMakeCliente($user, ['nome' => 'Cliente Alpha']);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'name' => 'Global legado',
        'color' => null,
        'description' => null,
    ]);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'Cliente ativo',
        'color' => null,
        'description' => null,
    ]);

    $this->actingAs($user)
        ->get(route('agencia.tags.index'))
        ->assertOk()
        ->assertSee('Cliente ativo')
        ->assertSee('Cliente Alpha')
        ->assertDontSee('Global legado');
});
