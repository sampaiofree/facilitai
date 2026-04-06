<?php

use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function clienteConversasMakeCliente(User $user, array $attributes = []): Cliente
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

function clienteConversasMakeLead(Cliente $cliente, array $attributes = []): ClienteLead
{
    return ClienteLead::create(array_merge([
        'cliente_id' => $cliente->id,
        'bot_enabled' => true,
        'phone' => '55' . fake()->unique()->numerify('119#######'),
        'name' => 'Lead ' . fake()->unique()->numerify('###'),
        'info' => 'Info de teste',
    ], $attributes));
}

function clienteConversasMakeTag(User $user, Cliente $cliente, array $attributes = []): Tag
{
    return Tag::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'Tag ' . fake()->unique()->numerify('###'),
        'color' => null,
        'description' => null,
    ], $attributes));
}

test('cliente pode adicionar tags ao editar lead em conversas', function () {
    $user = User::factory()->create();
    $cliente = clienteConversasMakeCliente($user);
    $outroCliente = clienteConversasMakeCliente($user, ['email' => fake()->unique()->safeEmail()]);

    $lead = clienteConversasMakeLead($cliente, ['name' => 'Lead com tags']);
    $tagQualificado = clienteConversasMakeTag($user, $cliente, ['name' => 'Qualificado']);
    $tagVip = clienteConversasMakeTag($user, $cliente, ['name' => 'VIP']);
    $tagOutroCliente = clienteConversasMakeTag($user, $outroCliente, ['name' => 'Externa']);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.conversas.index'))
        ->put(route('cliente.conversas.update', $lead), [
            'bot_enabled' => 1,
            'phone' => $lead->phone,
            'name' => $lead->name,
            'info' => $lead->info,
            'tags' => [$tagQualificado->id, $tagVip->id, $tagOutroCliente->id],
        ]);

    $response->assertRedirect(route('cliente.conversas.index'));
    $response->assertSessionHas('success', 'Lead atualizado com sucesso.');

    $tagIds = $lead->fresh('tags')->tags->pluck('id')->sort()->values()->all();

    expect($tagIds)->toBe(collect([$tagQualificado->id, $tagVip->id])->sort()->values()->all());
});
