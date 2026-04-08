<?php

use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\ScheduledMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

function clienteScheduledMakeCliente(User $user, array $attributes = []): Cliente
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

function clienteScheduledMakeAssistant(User $user, Cliente $cliente, array $attributes = []): Assistant
{
    return Assistant::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'openai_assistant_id' => 'asst_' . Str::lower(Str::random(12)),
        'name' => 'Assistente ' . fake()->unique()->numerify('###'),
        'instructions' => 'Instruções base',
        'version' => 1,
        'delay' => 0,
    ], $attributes));
}

function clienteScheduledMakeLead(Cliente $cliente, array $attributes = []): ClienteLead
{
    return ClienteLead::create(array_merge([
        'cliente_id' => $cliente->id,
        'bot_enabled' => true,
        'phone' => '5511999999999',
        'name' => 'Lead ' . fake()->unique()->numerify('###'),
        'info' => null,
    ], $attributes));
}

function clienteScheduledMakeMessage(User $user, ClienteLead $lead, Assistant $assistant, array $attributes = []): ScheduledMessage
{
    return ScheduledMessage::create(array_merge([
        'cliente_lead_id' => $lead->id,
        'assistant_id' => $assistant->id,
        'conexao_id' => null,
        'mensagem' => 'Mensagem agendada teste',
        'scheduled_for' => Carbon::now('UTC')->addHours(2),
        'status' => 'pending',
        'event_id' => 'evt_' . Str::lower(Str::random(16)),
        'attempts' => 0,
        'created_by_user_id' => $user->id,
    ], $attributes));
}

test('cliente lista apenas mensagens agendadas dos proprios leads', function () {
    $user = User::factory()->create();
    $clienteA = clienteScheduledMakeCliente($user, ['nome' => 'Cliente Alpha']);
    $clienteB = clienteScheduledMakeCliente($user, ['nome' => 'Cliente Beta']);

    $assistantA = clienteScheduledMakeAssistant($user, $clienteA, ['name' => 'Assistente Alpha']);
    $assistantB = clienteScheduledMakeAssistant($user, $clienteB, ['name' => 'Assistente Beta']);
    $leadA = clienteScheduledMakeLead($clienteA, ['name' => 'Lead Alpha']);
    $leadB = clienteScheduledMakeLead($clienteB, ['name' => 'Lead Beta']);

    clienteScheduledMakeMessage($user, $leadA, $assistantA);
    clienteScheduledMakeMessage($user, $leadB, $assistantB);

    $this->actingAs($clienteA, 'client')
        ->get(route('cliente.mensagens-agendadas.index'))
        ->assertOk()
        ->assertSee('Lead Alpha')
        ->assertDontSee('Lead Beta')
        ->assertSee('Filtrar')
        ->assertSee('Ver')
        ->assertSee('Editar')
        ->assertSee(route('cliente.mensagens-agendadas.index'), false);
});

test('cliente pode ver detalhes de um agendamento proprio sem dados do criador', function () {
    $user = User::factory()->create();
    $cliente = clienteScheduledMakeCliente($user, ['nome' => 'Cliente Alpha']);
    $assistant = clienteScheduledMakeAssistant($user, $cliente, ['name' => 'Assistente Alpha']);
    $lead = clienteScheduledMakeLead($cliente, ['name' => 'Lead Alpha']);
    $scheduledMessage = clienteScheduledMakeMessage($user, $lead, $assistant);

    $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.mensagens-agendadas.show', $scheduledMessage))
        ->assertOk()
        ->assertJsonPath('id', $scheduledMessage->id)
        ->assertJsonPath('lead.name', 'Lead Alpha')
        ->assertJsonPath('lead.cliente', 'Cliente Alpha')
        ->assertJsonPath('assistant.name', 'Assistente Alpha')
        ->assertJsonMissingPath('creator');
});

test('cliente nao pode ver detalhes de agendamento de outro cliente', function () {
    $user = User::factory()->create();
    $clienteA = clienteScheduledMakeCliente($user);
    $clienteB = clienteScheduledMakeCliente($user);
    $assistantB = clienteScheduledMakeAssistant($user, $clienteB);
    $leadB = clienteScheduledMakeLead($clienteB);
    $scheduledMessage = clienteScheduledMakeMessage($user, $leadB, $assistantB);

    $this->actingAs($clienteA, 'client')
        ->getJson(route('cliente.mensagens-agendadas.show', $scheduledMessage))
        ->assertForbidden();
});

test('cliente pode editar um agendamento pendente proprio', function () {
    $user = User::factory()->create();
    $cliente = clienteScheduledMakeCliente($user);
    $assistant = clienteScheduledMakeAssistant($user, $cliente);
    $lead = clienteScheduledMakeLead($cliente);
    $scheduledMessage = clienteScheduledMakeMessage($user, $lead, $assistant, [
        'mensagem' => 'Mensagem original',
    ]);

    $scheduledFor = Carbon::now('America/Sao_Paulo')->addHours(5)->format('Y-m-d\TH:i');

    $this->actingAs($cliente, 'client')
        ->patchJson(route('cliente.mensagens-agendadas.update', $scheduledMessage), [
            'mensagem' => 'Mensagem editada',
            'scheduled_for' => $scheduledFor,
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Agendamento atualizado com sucesso.');

    expect($scheduledMessage->fresh()->mensagem)->toBe('Mensagem editada');
});

test('cliente nao pode editar agendamento nao pendente', function () {
    $user = User::factory()->create();
    $cliente = clienteScheduledMakeCliente($user);
    $assistant = clienteScheduledMakeAssistant($user, $cliente);
    $lead = clienteScheduledMakeLead($cliente);
    $scheduledMessage = clienteScheduledMakeMessage($user, $lead, $assistant, [
        'status' => 'sent',
    ]);

    $scheduledFor = Carbon::now('America/Sao_Paulo')->addHours(5)->format('Y-m-d\TH:i');

    $this->actingAs($cliente, 'client')
        ->patchJson(route('cliente.mensagens-agendadas.update', $scheduledMessage), [
            'mensagem' => 'Mensagem editada',
            'scheduled_for' => $scheduledFor,
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Somente agendamentos pendentes podem ser editados.');
});

test('cliente pode cancelar um agendamento pendente proprio', function () {
    $user = User::factory()->create();
    $cliente = clienteScheduledMakeCliente($user);
    $assistant = clienteScheduledMakeAssistant($user, $cliente);
    $lead = clienteScheduledMakeLead($cliente);
    $scheduledMessage = clienteScheduledMakeMessage($user, $lead, $assistant);

    $this->actingAs($cliente, 'client')
        ->from(route('cliente.mensagens-agendadas.index'))
        ->patch(route('cliente.mensagens-agendadas.cancel', $scheduledMessage))
        ->assertRedirect(route('cliente.mensagens-agendadas.index'))
        ->assertSessionHas('success', 'Agendamento cancelado com sucesso.');

    expect($scheduledMessage->fresh()->status)->toBe('canceled');
});

test('cliente nao pode cancelar agendamento de outro cliente', function () {
    $user = User::factory()->create();
    $clienteA = clienteScheduledMakeCliente($user);
    $clienteB = clienteScheduledMakeCliente($user);
    $assistantB = clienteScheduledMakeAssistant($user, $clienteB);
    $leadB = clienteScheduledMakeLead($clienteB);
    $scheduledMessage = clienteScheduledMakeMessage($user, $leadB, $assistantB);

    $this->actingAs($clienteA, 'client')
        ->patch(route('cliente.mensagens-agendadas.cancel', $scheduledMessage))
        ->assertForbidden();
});
