<?php

use App\Models\Cliente;
use App\Models\Tag;
use App\Models\User;

function clienteTagMakeCliente(User $user, array $attributes = []): Cliente
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

test('cliente pode criar tag com mesmo nome usado por outro cliente da mesma conta', function () {
    $user = User::factory()->create();
    $clienteA = clienteTagMakeCliente($user);
    $clienteB = clienteTagMakeCliente($user);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => $clienteA->id,
        'name' => 'Aguardando',
        'color' => null,
        'description' => null,
    ]);

    $response = $this->actingAs($clienteB, 'client')
        ->from(route('cliente.tags.index'))
        ->post(route('cliente.tags.store'), [
            'name' => 'Aguardando',
            'description' => 'Mesmo nome em outro cliente',
        ]);

    $response
        ->assertRedirect(route('cliente.tags.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Tag criada com sucesso.');

    expect(
        Tag::query()
            ->where('user_id', $user->id)
            ->where('name', 'Aguardando')
            ->count()
    )->toBe(2);
});

test('cliente recebe erro amigavel ao tentar criar tag com nome de tag global da conta', function () {
    $user = User::factory()->create();
    $cliente = clienteTagMakeCliente($user);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'name' => 'Aguardando',
        'color' => null,
        'description' => null,
    ]);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.tags.index'))
        ->post(route('cliente.tags.store'), [
            'name' => 'Aguardando',
            'description' => 'Conflito com tag global',
        ]);

    $response
        ->assertRedirect(route('cliente.tags.index'))
        ->assertSessionHasErrors('name');

    expect(Tag::query()->where('user_id', $user->id)->where('name', 'Aguardando')->count())->toBe(1);
});

test('cliente pode atualizar a propria tag mantendo o mesmo nome', function () {
    $user = User::factory()->create();
    $cliente = clienteTagMakeCliente($user);

    $tag = Tag::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'Aguardando',
        'color' => null,
        'description' => 'Original',
    ]);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.tags.index'))
        ->post(route('cliente.tags.store'), [
            'tag_id' => $tag->id,
            'name' => 'Aguardando',
            'description' => 'Atualizada',
        ]);

    $response
        ->assertRedirect(route('cliente.tags.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Tag atualizada com sucesso.');

    expect($tag->fresh()->description)->toBe('Atualizada');
});
