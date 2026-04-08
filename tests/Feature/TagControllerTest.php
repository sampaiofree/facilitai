<?php

use App\Models\Cliente;
use App\Models\Tag;
use App\Models\User;

function legacyTagMakeCliente(User $user, array $attributes = []): Cliente
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

test('rota legada de tags exibe apenas tags globais', function () {
    $user = User::factory()->create();
    $cliente = legacyTagMakeCliente($user);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'name' => 'Global',
        'color' => null,
        'description' => null,
    ]);

    Tag::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'Do cliente',
        'color' => null,
        'description' => null,
    ]);

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertOk()
        ->assertSee('Global')
        ->assertDontSee('Do cliente')
        ->assertSee('somente leitura')
        ->assertDontSee('Salvar tag')
        ->assertDontSee('Atualizar')
        ->assertDontSee('Excluir');
});

test('rota legada de tags nao permite criar novas tags globais', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('tags.index'))
        ->post(route('tags.store'), [
            'name' => 'Aguardando',
            'description' => 'Tentativa de criar global',
        ]);

    $response
        ->assertRedirect(route('tags.index'))
        ->assertSessionHas('warning', 'Tags globais legadas estão em modo somente leitura. Crie e gerencie tags vinculadas a um cliente.');

    expect(Tag::query()->where('user_id', $user->id)->where('name', 'Aguardando')->exists())->toBeFalse();
});

test('rota legada de tags nao permite editar tags globais existentes', function () {
    $user = User::factory()->create();

    $tag = Tag::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'name' => 'Aguardando',
        'color' => null,
        'description' => 'Original',
    ]);

    $response = $this->actingAs($user)
        ->from(route('tags.index'))
        ->put(route('tags.update', $tag), [
            'name' => 'Alterada',
            'description' => 'Editada',
        ]);

    $response
        ->assertRedirect(route('tags.index'))
        ->assertSessionHas('warning', 'Tags globais legadas estão em modo somente leitura. Crie e gerencie tags vinculadas a um cliente.');

    expect($tag->fresh()->name)->toBe('Aguardando');
    expect($tag->fresh()->description)->toBe('Original');
});

test('rota legada de tags nao permite excluir tags globais existentes', function () {
    $user = User::factory()->create();

    $tag = Tag::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'name' => 'Aguardando',
        'color' => null,
        'description' => null,
    ]);

    $response = $this->actingAs($user)
        ->from(route('tags.index'))
        ->delete(route('tags.destroy', $tag));

    $response
        ->assertRedirect(route('tags.index'))
        ->assertSessionHas('warning', 'Tags globais legadas estão em modo somente leitura. Crie e gerencie tags vinculadas a um cliente.');

    expect($tag->fresh())->not()->toBeNull();
});
