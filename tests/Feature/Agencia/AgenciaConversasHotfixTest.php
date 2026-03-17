<?php

use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Sequence;
use App\Models\SequenceChat;
use App\Models\User;
use App\Models\WhatsappCloudCustomField;
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

function agenciaConversasMakeCustomField(User $user, ?Cliente $cliente = null, array $attributes = []): WhatsappCloudCustomField
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

test('tool registrar_campo_personalizado faz upsert, ignora vazios e rejeita campo fora do escopo', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $outroCliente = agenciaConversasMakeCliente($user, [
        'email' => fake()->unique()->safeEmail(),
        'nome' => 'Outro cliente',
    ]);
    $lead = agenciaConversasMakeLead($cliente);
    $job = new ProcessIncomingMessageJob(1, null, []);

    $empresaField = agenciaConversasMakeCustomField($user, null, [
        'name' => 'empresa',
        'label' => 'Empresa',
    ]);
    $cargoField = agenciaConversasMakeCustomField($user, $cliente, [
        'name' => 'cargo',
        'label' => 'Cargo',
    ]);
    agenciaConversasMakeCustomField($user, $cliente, [
        'name' => 'observacao',
        'label' => 'Observacao',
    ]);
    agenciaConversasMakeCustomField($user, $outroCliente, [
        'name' => 'segmento',
        'label' => 'Segmento',
    ]);

    $lead->customFieldValues()->create([
        'whatsapp_cloud_custom_field_id' => $empresaField->id,
        'value' => 'Valor antigo',
    ]);

    $handlers = (fn (array $payload, $conexao, $currentLead) => $this->buildToolHandlers($payload, $conexao, $currentLead))
        ->call($job, [], null, $lead);

    $result = $handlers['registrar_campo_personalizado']([
        'campos' => [
            ['campo' => 'empresa', 'valor' => 'ACME LTDA'],
            ['campo' => 'cargo', 'valor' => 'Comprador'],
            ['campo' => 'observacao', 'valor' => ''],
            ['campo' => 'segmento', 'valor' => 'Financeiro'],
        ],
    ], []);

    expect($result)->toBe('Campos salvos: empresa, cargo. Ignorados por valor vazio: observacao. Invalidos: segmento.');

    $this->assertDatabaseHas('cliente_lead_custom_fields', [
        'cliente_lead_id' => $lead->id,
        'whatsapp_cloud_custom_field_id' => $empresaField->id,
        'value' => 'ACME LTDA',
    ]);

    $this->assertDatabaseHas('cliente_lead_custom_fields', [
        'cliente_lead_id' => $lead->id,
        'whatsapp_cloud_custom_field_id' => $cargoField->id,
        'value' => 'Comprador',
    ]);

    expect(
        $lead->customFieldValues()
            ->where('whatsapp_cloud_custom_field_id', $empresaField->id)
            ->count()
    )->toBe(1);

    expect(
        $lead->customFieldValues()
            ->whereHas('customField', fn ($query) => $query->where('name', 'observacao'))
            ->count()
    )->toBe(0);

    expect(
        $lead->customFieldValues()
            ->whereHas('customField', fn ($query) => $query->where('name', 'segmento'))
            ->count()
    )->toBe(0);
});
