<?php

use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\User;
use App\Models\WhatsappCloudCustomField;
use App\Services\OpenAIOrchestratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function openAiOrchestratorMakeCliente(User $user, array $attributes = []): Cliente
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

function openAiOrchestratorMakeLead(Cliente $cliente, array $attributes = []): ClienteLead
{
    return ClienteLead::create(array_merge([
        'cliente_id' => $cliente->id,
        'bot_enabled' => true,
        'phone' => '55' . fake()->numerify('119#######'),
        'name' => 'Lead ' . fake()->numerify('###'),
        'info' => 'Info de teste',
    ], $attributes));
}

test('resolve campos validos para tools inclui apenas globais e do mesmo cliente do lead', function () {
    $user = User::create([
        'name' => 'Owner User',
        'email' => 'owner@example.com',
        'password' => 'password',
    ]);
    $otherUser = User::create([
        'name' => 'Other User',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);
    $cliente = openAiOrchestratorMakeCliente($user, ['nome' => 'Cliente alvo']);
    $otherCliente = openAiOrchestratorMakeCliente($user, ['nome' => 'Outro cliente', 'email' => fake()->unique()->safeEmail()]);
    $lead = openAiOrchestratorMakeLead($cliente);

    WhatsappCloudCustomField::create([
        'user_id' => $user->id,
        'cliente_id' => null,
        'name' => 'empresa',
        'label' => 'Empresa',
    ]);

    WhatsappCloudCustomField::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'cargo',
        'label' => 'Cargo',
    ]);

    WhatsappCloudCustomField::create([
        'user_id' => $user->id,
        'cliente_id' => $otherCliente->id,
        'name' => 'segmento',
        'label' => 'Segmento',
    ]);

    WhatsappCloudCustomField::create([
        'user_id' => $otherUser->id,
        'cliente_id' => null,
        'name' => 'crm',
        'label' => 'CRM',
    ]);

    $service = new OpenAIOrchestratorService();

    $fields = (fn (ClienteLead $currentLead) => $this->resolveLeadCustomFieldsForTools($currentLead))
        ->call($service, $lead);

    expect(collect($fields)->pluck('name')->all())->toBe(['cargo', 'empresa']);
});
