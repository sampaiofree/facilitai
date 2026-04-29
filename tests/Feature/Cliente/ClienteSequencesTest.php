<?php

use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\Sequence;
use App\Models\SequenceChat;
use App\Models\SequenceStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function clienteSequencesMakeCliente(User $user, array $attributes = []): Cliente
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

function clienteSequencesMakeConexao(Cliente $cliente, array $attributes = []): Conexao
{
    return Conexao::create(array_merge([
        'cliente_id' => $cliente->id,
        'name' => 'Conexao ' . fake()->unique()->numerify('###'),
        'status' => 'active',
        'is_active' => true,
    ], $attributes));
}

function clienteSequencesMakeSequence(User $user, Cliente $cliente, Conexao $conexao, array $attributes = []): Sequence
{
    return Sequence::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'conexao_id' => $conexao->id,
        'name' => 'Sequencia Modelo',
        'description' => 'Descricao original',
        'active' => true,
        'tags_incluir' => ['interessado', 'vip'],
        'tags_excluir' => ['bloqueado'],
    ], $attributes));
}

function clienteSequencesMakeStep(Sequence $sequence, array $attributes = []): SequenceStep
{
    return SequenceStep::create(array_merge([
        'sequence_id' => $sequence->id,
        'title' => 'Etapa ' . fake()->unique()->numerify('###'),
        'ordem' => 1,
        'atraso_tipo' => 'hora',
        'atraso_valor' => 2,
        'janela_inicio' => '08:00',
        'janela_fim' => '18:00',
        'dias_semana' => ['mon', 'wed', 'fri'],
        'prompt' => 'Mensagem da etapa',
        'active' => true,
    ], $attributes));
}

test('cliente pode duplicar sequencia propria com etapas sem copiar chats e logs', function () {
    $user = User::factory()->create();
    $cliente = clienteSequencesMakeCliente($user);
    $conexao = clienteSequencesMakeConexao($cliente);
    $sequence = clienteSequencesMakeSequence($user, $cliente, $conexao);
    $firstStep = clienteSequencesMakeStep($sequence, [
        'title' => 'Primeiro contato',
        'ordem' => 1,
        'atraso_tipo' => 'minuto',
        'atraso_valor' => 15,
        'prompt' => 'Prompt inicial',
        'active' => true,
    ]);
    clienteSequencesMakeStep($sequence, [
        'title' => 'Follow up',
        'ordem' => 2,
        'atraso_tipo' => 'dia',
        'atraso_valor' => 1,
        'janela_inicio' => null,
        'janela_fim' => null,
        'dias_semana' => ['tue'],
        'prompt' => 'Prompt de follow up',
        'active' => false,
    ]);
    $lead = ClienteLead::create([
        'cliente_id' => $cliente->id,
        'phone' => '5511999999999',
        'name' => 'Lead Teste',
        'bot_enabled' => true,
    ]);
    SequenceChat::create([
        'sequence_id' => $sequence->id,
        'cliente_lead_id' => $lead->id,
        'conexao_id' => $conexao->id,
        'passo_atual_id' => $firstStep->id,
        'status' => 'em_andamento',
        'criado_por' => 'system',
    ]);

    $response = $this->actingAs($cliente, 'client')
        ->post(route('cliente.sequences.duplicate', $sequence));

    $response
        ->assertRedirect(route('cliente.sequences.index'))
        ->assertSessionHas('success', 'Sequência duplicada com sucesso.');

    expect(Sequence::query()->count())->toBe(2)
        ->and(SequenceStep::query()->count())->toBe(4)
        ->and(SequenceChat::query()->count())->toBe(1);

    $copy = Sequence::query()
        ->where('id', '!=', $sequence->id)
        ->with('steps')
        ->firstOrFail();

    expect($copy->name)->toBe('Sequencia Modelo (cópia)')
        ->and($copy->active)->toBeFalse()
        ->and($copy->user_id)->toBe($sequence->user_id)
        ->and($copy->cliente_id)->toBe($sequence->cliente_id)
        ->and($copy->conexao_id)->toBe($sequence->conexao_id)
        ->and($copy->description)->toBe($sequence->description)
        ->and($copy->tags_incluir)->toBe($sequence->tags_incluir)
        ->and($copy->tags_excluir)->toBe($sequence->tags_excluir)
        ->and($copy->steps)->toHaveCount(2)
        ->and($copy->chats()->count())->toBe(0)
        ->and($copy->logs()->count())->toBe(0);

    $originalSteps = $sequence->steps()->orderBy('ordem')->get()->values();
    $copiedSteps = $copy->steps->values();

    foreach ($originalSteps as $index => $originalStep) {
        $copiedStep = $copiedSteps[$index];

        expect($copiedStep->sequence_id)->toBe($copy->id)
            ->and($copiedStep->title)->toBe($originalStep->title)
            ->and($copiedStep->ordem)->toBe($originalStep->ordem)
            ->and($copiedStep->atraso_tipo)->toBe($originalStep->atraso_tipo)
            ->and($copiedStep->atraso_valor)->toBe($originalStep->atraso_valor)
            ->and($copiedStep->janela_inicio)->toBe($originalStep->janela_inicio)
            ->and($copiedStep->janela_fim)->toBe($originalStep->janela_fim)
            ->and($copiedStep->dias_semana)->toBe($originalStep->dias_semana)
            ->and($copiedStep->prompt)->toBe($originalStep->prompt)
            ->and($copiedStep->active)->toBe($originalStep->active);
    }
});

test('cliente nao pode duplicar sequencia de outro cliente', function () {
    $user = User::factory()->create();
    $clienteA = clienteSequencesMakeCliente($user);
    $clienteB = clienteSequencesMakeCliente($user);
    $conexaoB = clienteSequencesMakeConexao($clienteB);
    $sequenceB = clienteSequencesMakeSequence($user, $clienteB, $conexaoB);
    clienteSequencesMakeStep($sequenceB);

    $response = $this->actingAs($clienteA, 'client')
        ->post(route('cliente.sequences.duplicate', $sequenceB));

    $response->assertNotFound();

    expect(Sequence::query()->count())->toBe(1)
        ->and(SequenceStep::query()->count())->toBe(1);
});
