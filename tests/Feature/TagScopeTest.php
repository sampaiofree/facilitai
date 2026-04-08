<?php

use App\Models\Cliente;
use App\Models\Tag;
use App\Models\User;
use App\Support\TagScope;

function tagScopeMakeCliente(User $user, array $attributes = []): Cliente
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

test('resolveForLead considera apenas tags globais e do proprio cliente', function () {
    $user = User::factory()->create();
    $clienteA = tagScopeMakeCliente($user);
    $clienteB = tagScopeMakeCliente($user);

    $tagA = Tag::create([
        'user_id' => $user->id,
        'cliente_id' => $clienteA->id,
        'name' => 'VIP',
        'color' => null,
        'description' => null,
    ]);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => $clienteB->id,
        'name' => 'VIP',
        'color' => null,
        'description' => null,
    ]);

    $resolved = TagScope::resolveForLead($user->id, $clienteA->id, ['VIP']);

    expect($resolved['matched']->pluck('id')->all())->toBe([$tagA->id]);
    expect($resolved['missing']->all())->toBe([]);
    expect($resolved['conflicts']->all())->toBe([]);
});

test('resolveForLead marca conflito quando encontra tag global e do cliente com mesmo nome', function () {
    $user = User::factory()->create();
    $cliente = tagScopeMakeCliente($user);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'name' => 'VIP',
        'color' => null,
        'description' => null,
    ]);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'VIP',
        'color' => null,
        'description' => null,
    ]);

    $resolved = TagScope::resolveForLead($user->id, $cliente->id, ['VIP']);

    expect($resolved['matched']->all())->toBe([]);
    expect($resolved['missing']->all())->toBe([]);
    expect($resolved['conflicts']->all())->toBe(['VIP']);
});
