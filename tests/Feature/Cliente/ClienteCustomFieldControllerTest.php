<?php

use App\Models\Cliente;
use App\Models\User;
use App\Models\WhatsappCloudCustomField;

function clienteCustomFieldMakeCliente(User $user, array $attributes = []): Cliente
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

function clienteCustomFieldMakeField(User $user, ?Cliente $cliente = null, array $attributes = []): WhatsappCloudCustomField
{
    return WhatsappCloudCustomField::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente?->id,
        'name' => 'campo_' . fake()->unique()->numerify('###'),
        'label' => 'Campo ' . fake()->numerify('###'),
        'sample_value' => null,
        'description' => null,
    ], $attributes));
}

test('cliente lista apenas campos do proprio cliente', function () {
    $user = User::factory()->create();
    $clienteA = clienteCustomFieldMakeCliente($user);
    $clienteB = clienteCustomFieldMakeCliente($user);

    clienteCustomFieldMakeField($user, $clienteA, ['name' => 'empresa']);
    clienteCustomFieldMakeField($user, $clienteB, ['name' => 'cargo']);

    $this->actingAs($clienteA, 'client')
        ->get(route('cliente.campos-personalizados.index'))
        ->assertOk()
        ->assertSee('empresa')
        ->assertDontSee('cargo');
});

test('cliente pode criar campo com mesmo nome usado por outro cliente da mesma conta', function () {
    $user = User::factory()->create();
    $clienteA = clienteCustomFieldMakeCliente($user);
    $clienteB = clienteCustomFieldMakeCliente($user);

    clienteCustomFieldMakeField($user, $clienteA, ['name' => 'empresa']);

    $response = $this->actingAs($clienteB, 'client')
        ->from(route('cliente.campos-personalizados.index'))
        ->post(route('cliente.campos-personalizados.store'), [
            'name' => 'Empresa',
        ]);

    $response
        ->assertRedirect(route('cliente.campos-personalizados.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Campo personalizado salvo como empresa.');

    expect(
        WhatsappCloudCustomField::query()
            ->where('user_id', $user->id)
            ->where('name', 'empresa')
            ->count()
    )->toBe(2);
});

test('cliente aplica sufixo ao repetir nome no proprio cliente', function () {
    $user = User::factory()->create();
    $cliente = clienteCustomFieldMakeCliente($user);

    clienteCustomFieldMakeField($user, $cliente, ['name' => 'empresa']);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.campos-personalizados.index'))
        ->post(route('cliente.campos-personalizados.store'), [
            'name' => 'Empresa',
        ]);

    $response
        ->assertRedirect(route('cliente.campos-personalizados.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Campo personalizado salvo como empresa2.');

    $this->assertDatabaseHas('whatsapp_cloud_custom_fields', [
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'empresa2',
    ]);
});

test('cliente bloqueia colisao com global legado', function () {
    $user = User::factory()->create();
    $cliente = clienteCustomFieldMakeCliente($user);

    clienteCustomFieldMakeField($user, null, ['name' => 'empresa']);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.campos-personalizados.index'))
        ->post(route('cliente.campos-personalizados.store'), [
            'name' => 'Empresa',
        ]);

    $response
        ->assertRedirect(route('cliente.campos-personalizados.index'))
        ->assertSessionHasErrors('name');

    expect(
        WhatsappCloudCustomField::query()
            ->where('user_id', $user->id)
            ->where('name', 'empresa')
            ->count()
    )->toBe(1);
});

test('cliente nao pode atualizar campo de outro cliente', function () {
    $user = User::factory()->create();
    $clienteA = clienteCustomFieldMakeCliente($user);
    $clienteB = clienteCustomFieldMakeCliente($user);
    $field = clienteCustomFieldMakeField($user, $clienteB, ['name' => 'empresa']);

    $this->actingAs($clienteA, 'client')
        ->patch(route('cliente.campos-personalizados.update', $field), [
            'name' => 'Novo nome',
        ])
        ->assertForbidden();
});

test('cliente nao pode excluir campo de outro cliente', function () {
    $user = User::factory()->create();
    $clienteA = clienteCustomFieldMakeCliente($user);
    $clienteB = clienteCustomFieldMakeCliente($user);
    $field = clienteCustomFieldMakeField($user, $clienteB, ['name' => 'empresa']);

    $this->actingAs($clienteA, 'client')
        ->delete(route('cliente.campos-personalizados.destroy', $field))
        ->assertForbidden();
});
