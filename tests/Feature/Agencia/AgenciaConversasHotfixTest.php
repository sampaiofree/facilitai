<?php

use App\DTOs\IAResult;
use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\KommoAccount;
use App\Models\Sequence;
use App\Models\SequenceChat;
use App\Models\User;
use App\Models\WhatsappCloudCustomField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

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

function agenciaConversasMakeKommoAccount(User $user, Cliente $cliente, array $attributes = []): KommoAccount
{
    return KommoAccount::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'kommo-vendas',
        'subdomain' => 'conta-kommo',
        'access_token' => 'token-kommo',
        'kommo_account_id' => '123456',
        'kommo_account_name' => 'Conta Kommo',
        'pipeline_id' => '10',
        'pipeline_name' => 'Pipeline A',
        'status_id' => '100',
        'status_name' => 'Novo Lead',
        'last_verified_at' => now(),
        'last_verification_error' => null,
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

test('tool desativar_bot desliga o bot do lead atual e persiste no banco', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $lead = agenciaConversasMakeLead($cliente, [
        'bot_enabled' => true,
    ]);
    $job = new ProcessIncomingMessageJob(1, null, []);

    $handlers = (fn (array $payload, $conexao, $currentLead) => $this->buildToolHandlers($payload, $conexao, $currentLead))
        ->call($job, [], null, $lead);

    $result = $handlers['desativar_bot']([], []);

    expect($result)->toContain('Bot desativado para este lead');
    expect($lead->fresh()->bot_enabled)->toBeFalse();
});

test('tool desativar_bot e idempotente quando o bot ja esta desativado', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $lead = agenciaConversasMakeLead($cliente, [
        'bot_enabled' => false,
    ]);
    $job = new ProcessIncomingMessageJob(1, null, []);

    $handlers = (fn (array $payload, $conexao, $currentLead) => $this->buildToolHandlers($payload, $conexao, $currentLead))
        ->call($job, [], null, $lead);

    $result = $handlers['desativar_bot']([], []);

    expect($result)->toContain('Bot já estava desativado para este lead');
    expect($lead->fresh()->bot_enabled)->toBeFalse();
});

test('tool desativar_bot responde quando nao existe lead atual', function () {
    $job = new ProcessIncomingMessageJob(1, null, []);

    $handlers = (fn (array $payload, $conexao, $currentLead) => $this->buildToolHandlers($payload, $conexao, $currentLead))
        ->call($job, [], null, null);

    $result = $handlers['desativar_bot']([], []);

    expect($result)->toContain('Lead não encontrado');
});

test('tool enviar_lead_kommo envia o lead atual usando a integracao selecionada', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $lead = agenciaConversasMakeLead($cliente, [
        'phone' => '5511999999999',
        'name' => 'Lead Kommo',
    ]);
    agenciaConversasMakeKommoAccount($user, $cliente, [
        'name' => 'kommo-vendas',
        'subdomain' => 'conta-kommo',
    ]);
    $job = new ProcessIncomingMessageJob(1, null, []);

    Http::fake([
        'https://conta-kommo.kommo.com/api/v4/leads/complex' => Http::response([
            '_embedded' => [
                'leads' => [[
                    'id' => 777,
                    '_embedded' => [
                        'contacts' => [[
                            'id' => 888,
                        ]],
                    ],
                ]],
            ],
        ], 200),
    ]);

    $handlers = (fn (array $payload, $conexao, $currentLead) => $this->buildToolHandlers($payload, $conexao, $currentLead))
        ->call($job, [], null, $lead);

    $result = $handlers['enviar_lead_kommo']([
        'kommo_account_name' => 'kommo-vendas',
    ], []);

    expect($result)->toContain('Lead enviado para a integração Kommo kommo-vendas');
    expect($result)->toContain('lead ID 777');
});

test('tool enviar_lead_kommo falha quando integracao nao pertence ao cliente do lead', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $otherCliente = agenciaConversasMakeCliente($user, ['email' => fake()->unique()->safeEmail()]);
    $lead = agenciaConversasMakeLead($cliente, [
        'phone' => '5511999999999',
    ]);
    agenciaConversasMakeKommoAccount($user, $otherCliente, [
        'name' => 'kommo-vendas',
    ]);
    $job = new ProcessIncomingMessageJob(1, null, []);

    $handlers = (fn (array $payload, $conexao, $currentLead) => $this->buildToolHandlers($payload, $conexao, $currentLead))
        ->call($job, [], null, $lead);

    $result = $handlers['enviar_lead_kommo']([
        'kommo_account_name' => 'kommo-vendas',
    ], []);

    expect($result)->toBeArray();
    expect((string) ($result['output'] ?? ''))->toContain('integração selecionada não foi encontrada');
    expect((string) ($result['fallback_text'] ?? ''))->toContain('integração selecionada não foi encontrada');
});

test('tool enviar_lead_kommo falha quando o lead atual nao possui telefone', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $lead = agenciaConversasMakeLead($cliente, [
        'phone' => '',
    ]);
    agenciaConversasMakeKommoAccount($user, $cliente, [
        'name' => 'kommo-vendas',
    ]);
    $job = new ProcessIncomingMessageJob(1, null, []);

    $handlers = (fn (array $payload, $conexao, $currentLead) => $this->buildToolHandlers($payload, $conexao, $currentLead))
        ->call($job, [], null, $lead);

    $result = $handlers['enviar_lead_kommo']([
        'kommo_account_name' => 'kommo-vendas',
    ], []);

    expect($result)->toBeArray();
    expect((string) ($result['output'] ?? ''))->toContain('não possui telefone válido');
    expect((string) ($result['fallback_text'] ?? ''))->toContain('não possui telefone válido');
});

test('tool enviar_lead_kommo retorna falha amigavel quando kommo rejeita o envio', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $lead = agenciaConversasMakeLead($cliente, [
        'phone' => '5511999999999',
    ]);
    agenciaConversasMakeKommoAccount($user, $cliente, [
        'name' => 'kommo-vendas',
        'subdomain' => 'conta-kommo',
    ]);
    $job = new ProcessIncomingMessageJob(1, null, []);

    Http::fake([
        'https://conta-kommo.kommo.com/api/v4/leads/complex' => Http::response([
            'detail' => 'Invalid pipeline',
        ], 400),
    ]);

    $handlers = (fn (array $payload, $conexao, $currentLead) => $this->buildToolHandlers($payload, $conexao, $currentLead))
        ->call($job, [], null, $lead);

    $result = $handlers['enviar_lead_kommo']([
        'kommo_account_name' => 'kommo-vendas',
    ], []);

    expect($result)->toBeArray();
    expect((string) ($result['output'] ?? ''))->toContain('Invalid pipeline');
    expect((string) ($result['fallback_text'] ?? ''))->toContain('Invalid pipeline');
});

test('destroy de lead preserva filtros ordenacao e pagina no redirect', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $lead = agenciaConversasMakeLead($cliente);

    $query = [
        'client_add_filter' => (string) $cliente->id,
        'assistant_add_filter' => '7',
        'tag_add_filter' => '3',
        'date_start' => '2026-03-01',
        'date_end' => '2026-03-20',
        'last_message_start' => '2026-03-10',
        'last_message_end' => '2026-03-21',
        'sort_by' => 'updated_at',
        'sort_dir' => 'asc',
        'page' => '3',
    ];

    $response = $this->actingAs($user)->delete(route('agencia.conversas.destroy', array_merge([
        'clienteLead' => $lead,
    ], $query)));

    $response->assertRedirect(route('agencia.conversas.index', $query));
    $response->assertSessionHas('success', 'Lead removido com sucesso.');
    $this->assertDatabaseMissing('cliente_lead', [
        'id' => $lead->id,
    ]);
});

test('listagem de conversas da agencia exibe horario na coluna criado em', function () {
    $user = User::factory()->create();
    $cliente = agenciaConversasMakeCliente($user);
    $lead = agenciaConversasMakeLead($cliente, [
        'name' => 'Lead Horario',
    ]);

    $createdAt = Carbon::parse('2026-04-16 10:30:00');
    $updatedAt = Carbon::parse('2026-04-17 11:45:00');

    $lead->forceFill([
        'created_at' => $createdAt,
        'updated_at' => $updatedAt,
    ])->saveQuietly();

    $response = $this->actingAs($user)->get(route('agencia.conversas.index'));

    $response->assertOk();
    $response->assertSee(
        '<td class="px-5 py-4 text-slate-600">' . $createdAt->format('d/m/Y H:i') . '</td>',
        false
    );
});

test('job classifica resposta concluida sem texto como silent', function () {
    $job = new ProcessIncomingMessageJob(1, null, []);

    $mode = (fn (IAResult $result) => $this->resolveIAResponseMode($result))
        ->call($job, IAResult::success('', 'openai'));

    expect($mode)->toBe('silent');
});

test('job classifica resposta com erro como error', function () {
    $job = new ProcessIncomingMessageJob(1, null, []);

    $mode = (fn (IAResult $result) => $this->resolveIAResponseMode($result))
        ->call($job, IAResult::error('OpenAI error.', 'openai'));

    expect($mode)->toBe('error');
});

test('job classifica resposta com texto final como reply', function () {
    $job = new ProcessIncomingMessageJob(1, null, []);

    $mode = (fn (IAResult $result) => $this->resolveIAResponseMode($result))
        ->call($job, IAResult::success('Resposta final', 'openai'));

    expect($mode)->toBe('reply');
});
