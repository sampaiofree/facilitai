<?php

use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\Sequence;
use App\Models\SequenceStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function agenciaSequencesMakeCliente(User $user, array $attributes = []): Cliente
{
    return Cliente::create(array_merge([
        'user_id' => $user->id,
        'nome' => 'Cliente ' . fake()->numerify('###'),
        'email' => fake()->unique()->safeEmail(),
        'telefone' => '11999999999',
        'password' => 'secret123',
        'is_active' => true,
    ], $attributes));
}

function agenciaSequencesMakeConexao(Cliente $cliente, array $attributes = []): Conexao
{
    return Conexao::create(array_merge([
        'cliente_id' => $cliente->id,
        'name' => 'Conexao ' . fake()->numerify('###'),
        'status' => 'active',
        'is_active' => true,
    ], $attributes));
}

function agenciaSequencesMakeSequence(User $user, Cliente $cliente, Conexao $conexao, array $attributes = []): Sequence
{
    return Sequence::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'conexao_id' => $conexao->id,
        'name' => 'Sequência ' . fake()->numerify('###'),
        'active' => true,
    ], $attributes));
}

function agenciaSequencesMakeStep(Sequence $sequence, array $attributes = []): SequenceStep
{
    return SequenceStep::create(array_merge([
        'sequence_id' => $sequence->id,
        'title' => 'Etapa ' . fake()->numerify('###'),
        'ordem' => 1,
        'atraso_tipo' => 'hora',
        'atraso_valor' => 2,
        'prompt' => 'Mensagem da etapa',
        'active' => true,
    ], $attributes));
}

test('agencia pode excluir etapa da sequencia preservando contexto da tela', function () {
    $user = User::factory()->create();
    $cliente = agenciaSequencesMakeCliente($user);
    $conexao = agenciaSequencesMakeConexao($cliente);
    $sequence = agenciaSequencesMakeSequence($user, $cliente, $conexao);
    $step = agenciaSequencesMakeStep($sequence);

    $payload = [
        'filter_cliente_ids' => [(string) $cliente->id],
        'current_sequence_id' => (string) $sequence->id,
    ];

    $response = $this->actingAs($user)->delete(
        route('agencia.sequences.steps.destroy', ['sequence' => $sequence->id, 'step' => $step->id]),
        $payload
    );

    $response->assertRedirect(route('agencia.sequences.index', [
        'sequence_id' => $sequence->id,
        'cliente_ids' => [$cliente->id],
    ]));
    $response->assertSessionHas('success', 'Etapa removida com sucesso.');
    $this->assertDatabaseMissing('sequence_steps', [
        'id' => $step->id,
    ]);
});
