<?php

use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Str;

function clienteAssistantMakeCliente(User $user, array $attributes = []): Cliente
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

function clienteAssistantMakeAssistant(User $user, Cliente $cliente, array $attributes = []): Assistant
{
    return Assistant::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'openai_assistant_id' => 'asst_' . Str::lower(Str::random(12)),
        'name' => 'Assistente teste',
        'instructions' => 'Instruções originais',
        'delay' => 5,
        'version' => 1,
    ], $attributes));
}

test('cliente pode atualizar o delay do proprio assistente', function () {
    $user = User::factory()->create();
    $cliente = clienteAssistantMakeCliente($user);
    $assistant = clienteAssistantMakeAssistant($user, $cliente, ['delay' => 4]);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.assistant.index'))
        ->patch(route('cliente.assistant.update', $assistant), [
            'name' => 'Assistente atualizado',
            'instructions' => 'Instruções atualizadas',
            'delay' => 12,
        ]);

    $response
        ->assertRedirect(route('cliente.assistant.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Assistente atualizado com sucesso.');

    expect($assistant->fresh()->delay)->toBe(12);
    expect($assistant->fresh()->name)->toBe('Assistente atualizado');
    expect($assistant->fresh()->instructions)->toBe('Instruções atualizadas');
    expect($assistant->fresh()->version)->toBe(2);
});

test('cliente pode salvar delay zero no assistente', function () {
    $user = User::factory()->create();
    $cliente = clienteAssistantMakeCliente($user);
    $assistant = clienteAssistantMakeAssistant($user, $cliente, ['delay' => 9]);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.assistant.index'))
        ->patch(route('cliente.assistant.update', $assistant), [
            'name' => $assistant->name,
            'instructions' => $assistant->instructions,
            'delay' => 0,
        ]);

    $response
        ->assertRedirect(route('cliente.assistant.index'))
        ->assertSessionHasNoErrors();

    expect($assistant->fresh()->delay)->toBe(0);
});

test('cliente recebe erro ao informar delay negativo', function () {
    $user = User::factory()->create();
    $cliente = clienteAssistantMakeCliente($user);
    $assistant = clienteAssistantMakeAssistant($user, $cliente, ['delay' => 7]);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.assistant.index'))
        ->patch(route('cliente.assistant.update', $assistant), [
            'name' => 'Assistente teste',
            'instructions' => 'Instruções originais',
            'delay' => -1,
        ]);

    $response
        ->assertRedirect(route('cliente.assistant.index'))
        ->assertSessionHasErrors('delay')
        ->assertSessionHasInput('delay', '-1');

    expect($assistant->fresh()->delay)->toBe(7);
});

test('cliente nao pode atualizar assistente de outro cliente', function () {
    $user = User::factory()->create();
    $clienteA = clienteAssistantMakeCliente($user);
    $clienteB = clienteAssistantMakeCliente($user);
    $assistant = clienteAssistantMakeAssistant($user, $clienteA, ['delay' => 6]);

    $this->actingAs($clienteB, 'client')
        ->patch(route('cliente.assistant.update', $assistant), [
            'name' => 'Tentativa',
            'instructions' => 'Tentativa',
            'delay' => 10,
        ])
        ->assertForbidden();

    expect($assistant->fresh()->delay)->toBe(6);
});

test('pagina de assistentes do cliente exibe o delay atual e a ajuda do campo', function () {
    $user = User::factory()->create();
    $cliente = clienteAssistantMakeCliente($user);
    $assistant = clienteAssistantMakeAssistant($user, $cliente, ['delay' => 11]);

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.assistant.index'))
        ->assertOk()
        ->assertSee('data-delay="' . $assistant->delay . '"', false)
        ->assertSee('0 usa o padrão atual de 25 segundos');
});
