<?php

use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\Sequence;
use App\Models\SequenceLog;
use App\Models\SequenceChat;
use App\Models\SequenceStep;
use App\Models\User;
use App\Models\WhatsappApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeSequenceContext(string $providerSlug, ?string $whatsappApiKey): array
{
    $user = User::factory()->create();

    $cliente = Cliente::create([
        'user_id' => $user->id,
        'nome' => 'Cliente Sequencia',
        'email' => fake()->unique()->safeEmail(),
        'telefone' => '11999999999',
        'password' => 'secret123',
        'is_active' => true,
    ]);

    $assistant = Assistant::create([
        'user_id' => $user->id,
        'openai_assistant_id' => 'asst_' . fake()->unique()->lexify('????????'),
        'name' => 'Assistente Sequencia',
        'instructions' => 'Instrucoes de teste',
    ]);

    $provider = WhatsappApi::query()->firstOrCreate(
        ['slug' => $providerSlug],
        ['nome' => strtoupper($providerSlug), 'ativo' => true]
    );

    $conexao = Conexao::create([
        'name' => 'Conexao ' . strtoupper($providerSlug),
        'cliente_id' => $cliente->id,
        'assistant_id' => $assistant->id,
        'whatsapp_api_id' => $provider->id,
        'whatsapp_api_key' => $whatsappApiKey,
        'status' => 'active',
        'is_active' => true,
    ]);

    $sequence = Sequence::create([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'conexao_id' => $conexao->id,
        'name' => 'Sequencia Teste',
        'description' => 'Descricao teste',
        'active' => true,
    ]);

    $step = SequenceStep::create([
        'sequence_id' => $sequence->id,
        'title' => 'Passo 1',
        'ordem' => 1,
        'atraso_tipo' => 'minuto',
        'atraso_valor' => 0,
        'prompt' => 'Mensagem da sequencia',
        'active' => true,
    ]);

    $lead = ClienteLead::create([
        'cliente_id' => $cliente->id,
        'bot_enabled' => true,
        'phone' => '5511999999999',
        'name' => 'Lead Sequencia',
        'info' => null,
    ]);

    $chat = SequenceChat::create([
        'sequence_id' => $sequence->id,
        'cliente_lead_id' => $lead->id,
        'assistant_id' => $assistant->id,
        'conexao_id' => $conexao->id,
        'passo_atual_id' => $step->id,
        'status' => 'em_andamento',
        'iniciado_em' => Carbon::now('America/Sao_Paulo')->subHours(2),
        'proximo_envio_em' => Carbon::now('America/Sao_Paulo')->subMinute(),
        'criado_por' => 'manual',
    ]);

    return compact('chat');
}

test('sequences process dispara para api_oficial sem whatsapp_api_key', function () {
    DB::statement('PRAGMA foreign_keys = OFF');
    Bus::fake([ProcessIncomingMessageJob::class]);

    $context = makeSequenceContext('api_oficial', null);
    $chat = $context['chat'];

    $this->artisan('sequences:process')
        ->assertSuccessful();

    Bus::assertDispatched(ProcessIncomingMessageJob::class);

    $errorLogs = SequenceLog::query()
        ->where('sequence_chat_id', $chat->id)
        ->where('status', 'erro')
        ->count();

    expect($errorLogs)->toBe(0);
});

test('sequences process bloqueia uazapi sem whatsapp_api_key', function () {
    DB::statement('PRAGMA foreign_keys = OFF');
    Bus::fake([ProcessIncomingMessageJob::class]);

    $context = makeSequenceContext('uazapi', null);
    $chat = $context['chat'];

    $this->artisan('sequences:process')
        ->assertSuccessful();

    Bus::assertNotDispatched(ProcessIncomingMessageJob::class);

    $hasExpectedError = SequenceLog::query()
        ->where('sequence_chat_id', $chat->id)
        ->where('status', 'erro')
        ->where('message', 'like', '%sem whatsapp_api_key%')
        ->exists();

    expect($hasExpectedError)->toBeTrue();
});
