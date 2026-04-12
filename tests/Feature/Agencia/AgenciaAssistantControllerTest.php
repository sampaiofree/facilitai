<?php

use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Str;

function agenciaAssistantMakeCliente(User $user, array $attributes = []): Cliente
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

function agenciaAssistantMakeAssistant(User $user, ?Cliente $cliente = null, array $attributes = []): Assistant
{
    return Assistant::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente?->id,
        'openai_assistant_id' => 'asst_' . Str::lower(Str::random(12)),
        'name' => 'Assistente ' . fake()->unique()->numerify('###'),
        'instructions' => 'Instrucoes do assistente',
        'delay' => 5,
        'version' => 1,
    ], $attributes));
}

test('agencia lista coluna cliente ao lado de nome com fallback e respeita escopo do usuario', function () {
    $user = User::factory()->create();
    $cliente = agenciaAssistantMakeCliente($user, [
        'nome' => 'Cliente Alpha',
    ]);

    agenciaAssistantMakeAssistant($user, $cliente, [
        'name' => 'Assistente Vinculado',
    ]);

    agenciaAssistantMakeAssistant($user, null, [
        'name' => 'Assistente Sem Cliente',
    ]);

    $otherUser = User::factory()->create();
    $otherCliente = agenciaAssistantMakeCliente($otherUser, [
        'nome' => 'Cliente Externo',
    ]);

    agenciaAssistantMakeAssistant($otherUser, $otherCliente, [
        'name' => 'Assistente de Outro Usuario',
    ]);

    $this->actingAs($user)
        ->get(route('agencia.assistant.index'))
        ->assertOk()
        ->assertSeeTextInOrder(['Nome', 'Cliente', 'Versão'])
        ->assertSeeText('Assistente Vinculado')
        ->assertSeeText('Cliente Alpha')
        ->assertSeeText('Assistente Sem Cliente')
        ->assertSeeText('Sem cliente')
        ->assertDontSeeText('Assistente de Outro Usuario')
        ->assertDontSeeText('Cliente Externo');
});
