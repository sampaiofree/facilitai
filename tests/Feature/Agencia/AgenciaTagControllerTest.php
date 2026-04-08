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

test('agencia recebe erro amigavel ao tentar criar tag com nome duplicado na conta', function () {
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
