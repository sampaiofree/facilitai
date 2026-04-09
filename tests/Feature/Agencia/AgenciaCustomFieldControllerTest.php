<?php

use App\Models\Cliente;
use App\Models\User;
use App\Models\WhatsappCloudCustomField;

function agenciaCustomFieldMakeCliente(User $user, array $attributes = []): Cliente
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

function agenciaCustomFieldMakeField(User $user, ?Cliente $cliente = null, array $attributes = []): WhatsappCloudCustomField
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

test('agencia pode criar campo com mesmo nome para clientes diferentes', function () {
    $user = User::factory()->create();
    $clienteA = agenciaCustomFieldMakeCliente($user);
    $clienteB = agenciaCustomFieldMakeCliente($user);

    agenciaCustomFieldMakeField($user, $clienteA, ['name' => 'empresa']);

    $response = $this->actingAs($user)
        ->from(route('agencia.campos-personalizados.index'))
        ->post(route('agencia.campos-personalizados.store'), [
            'name' => 'Empresa',
            'cliente_id' => $clienteB->id,
            'label' => 'Empresa',
        ]);

    $response
        ->assertRedirect(route('agencia.campos-personalizados.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Campo personalizado salvo como empresa.');

    expect(
        WhatsappCloudCustomField::query()
            ->where('user_id', $user->id)
            ->where('name', 'empresa')
            ->count()
    )->toBe(2);
});

test('agencia aplica sufixo ao repetir nome no mesmo cliente', function () {
    $user = User::factory()->create();
    $cliente = agenciaCustomFieldMakeCliente($user);

    agenciaCustomFieldMakeField($user, $cliente, ['name' => 'empresa']);

    $response = $this->actingAs($user)
        ->from(route('agencia.campos-personalizados.index'))
        ->post(route('agencia.campos-personalizados.store'), [
            'name' => 'Empresa',
            'cliente_id' => $cliente->id,
            'label' => 'Empresa',
        ]);

    $response
        ->assertRedirect(route('agencia.campos-personalizados.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Campo personalizado salvo como empresa2.');

    $this->assertDatabaseHas('whatsapp_cloud_custom_fields', [
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'empresa2',
    ]);
});

test('agencia exige cliente ao criar campo personalizado', function () {
    $user = User::factory()->create();
    agenciaCustomFieldMakeCliente($user);

    $response = $this->actingAs($user)
        ->from(route('agencia.campos-personalizados.index'))
        ->post(route('agencia.campos-personalizados.store'), [
            'name' => 'Empresa',
        ]);

    $response
        ->assertRedirect(route('agencia.campos-personalizados.index'))
        ->assertSessionHasErrors('cliente_id');
});

test('agencia bloqueia campo de cliente com nome de global legado', function () {
    $user = User::factory()->create();
    $cliente = agenciaCustomFieldMakeCliente($user);

    agenciaCustomFieldMakeField($user, null, ['name' => 'empresa']);

    $response = $this->actingAs($user)
        ->from(route('agencia.campos-personalizados.index'))
        ->post(route('agencia.campos-personalizados.store'), [
            'name' => 'Empresa',
            'cliente_id' => $cliente->id,
        ]);

    $response
        ->assertRedirect(route('agencia.campos-personalizados.index'))
        ->assertSessionHasErrors('name');

    expect(
        WhatsappCloudCustomField::query()
            ->where('user_id', $user->id)
            ->where('name', 'empresa')
            ->count()
    )->toBe(1);
});

test('agencia nao pode excluir campo global legado', function () {
    $user = User::factory()->create();
    $legacy = agenciaCustomFieldMakeField($user, null, ['name' => 'empresa']);

    $response = $this->actingAs($user)
        ->from(route('agencia.campos-personalizados.index'))
        ->delete(route('agencia.campos-personalizados.destroy', $legacy));

    $response
        ->assertRedirect(route('agencia.campos-personalizados.index'))
        ->assertSessionHas('error', 'Campos globais legados são somente leitura.');

    expect($legacy->fresh())->not()->toBeNull();
});

test('agencia lista global legado como somente leitura', function () {
    $user = User::factory()->create();
    $cliente = agenciaCustomFieldMakeCliente($user, ['nome' => 'Cliente Alpha']);

    agenciaCustomFieldMakeField($user, null, ['name' => 'empresa']);
    agenciaCustomFieldMakeField($user, $cliente, ['name' => 'cargo']);

    $this->actingAs($user)
        ->get(route('agencia.campos-personalizados.index'))
        ->assertOk()
        ->assertSee('global legado')
        ->assertSee('Somente leitura')
        ->assertSee('Cliente Alpha')
        ->assertSee('cargo');
});
