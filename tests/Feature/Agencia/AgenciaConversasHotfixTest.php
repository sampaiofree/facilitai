<?php

use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Sequence;
use App\Models\SequenceChat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function agenciaConversasMakeCliente(User $user, array $attributes = []): Cliente
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

function agenciaConversasMakeSequence(User $user, Cliente $cliente, array $attributes = []): Sequence
{
    return Sequence::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'Sequência ' . fake()->numerify('###'),
        'active' => true,
    ], $attributes));
}

function agenciaConversasMakeLead(Cliente $cliente, array $attributes = []): ClienteLead
{
    return ClienteLead::create(array_merge([
        'cliente_id' => $cliente->id,
        'bot_enabled' => true,
        'phone' => '55' . fake()->numerify('119#######'),
        'name' => 'Lead ' . fake()->numerify('###'),
        'info' => 'Info de teste',
    ], $attributes));
}

test('update do lead com sequence chat cancelada nao duplica e desativa bot sem erro 500', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $lead = agenciaConversasMakeLead($cliente, [
        'bot_enabled' => true,
        'phone' => '5511999999999',
    ]);
    $sequence = agenciaConversasMakeSequence($user, $cliente);

    SequenceChat::create([
        'sequence_id' => $sequence->id,
        'cliente_lead_id' => $lead->id,
        'status' => 'cancelada',
        'iniciado_em' => now('America/Sao_Paulo'),
        'proximo_envio_em' => null,
        'criado_por' => 'manual',
    ]);

    $response = $this->actingAs($user)->put(route('agencia.conversas.update', $lead), [
        'cliente_id' => $cliente->id,
        'phone' => $lead->phone,
        'name' => $lead->name,
        'info' => $lead->info,
        'sequence_ids' => [$sequence->id],
    ]);

    $response->assertRedirect(route('agencia.conversas.index'));
    $response->assertSessionHas('success');

    expect($lead->fresh()->bot_enabled)->toBeFalse();
    expect(
        SequenceChat::query()
            ->where('sequence_id', $sequence->id)
            ->where('cliente_lead_id', $lead->id)
            ->count()
    )->toBe(1);
});

test('update do lead com sequence chat ativo concluido ou pausado nao duplica registro', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $sequence = agenciaConversasMakeSequence($user, $cliente);

    foreach (['em_andamento', 'concluida', 'pausada'] as $status) {
        $lead = agenciaConversasMakeLead($cliente, [
            'phone' => '55' . fake()->numerify('119#######'),
        ]);

        SequenceChat::create([
            'sequence_id' => $sequence->id,
            'cliente_lead_id' => $lead->id,
            'status' => $status,
            'iniciado_em' => now('America/Sao_Paulo'),
            'proximo_envio_em' => null,
            'criado_por' => 'manual',
        ]);

        $response = $this->actingAs($user)->put(route('agencia.conversas.update', $lead), [
            'cliente_id' => $cliente->id,
            'phone' => $lead->phone,
            'name' => $lead->name,
            'info' => $lead->info,
            'sequence_ids' => [$sequence->id],
        ]);

        $response->assertRedirect(route('agencia.conversas.index'));
        $response->assertSessionHas('success');

        expect(
            SequenceChat::query()
                ->where('sequence_id', $sequence->id)
                ->where('cliente_lead_id', $lead->id)
                ->count()
        )->toBe(1);
    }
});

test('tool inscrever sequencia nao duplica quando par ja existe em qualquer status', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $sequence = agenciaConversasMakeSequence($user, $cliente);
    $job = new ProcessIncomingMessageJob(1, null, ['assistant_id' => null]);

    foreach (['em_andamento', 'concluida', 'pausada', 'cancelada'] as $status) {
        $lead = agenciaConversasMakeLead($cliente, [
            'bot_enabled' => true,
            'phone' => '55' . fake()->numerify('119#######'),
        ]);

        SequenceChat::create([
            'sequence_id' => $sequence->id,
            'cliente_lead_id' => $lead->id,
            'status' => $status,
            'iniciado_em' => now('America/Sao_Paulo'),
            'proximo_envio_em' => null,
            'criado_por' => 'assistant',
        ]);

        $handlers = (fn (array $payload, $conexao, $currentLead) => $this->buildToolHandlers($payload, $conexao, $currentLead))
            ->call($job, ['assistant_id' => null], null, $lead);
        $result = $handlers['inscrever_sequencia'](['sequence_id' => $sequence->id], []);

        expect($result)->toBeArray();
        expect((string) ($result['output'] ?? ''))->toContain('já está inscrito');
        expect(
            SequenceChat::query()
                ->where('sequence_id', $sequence->id)
                ->where('cliente_lead_id', $lead->id)
                ->count()
        )->toBe(1);
    }
});
