<?php

use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function agenciaLeadUniqueMakeCliente(User $user, array $attributes = []): Cliente
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

function agenciaLeadUniqueMakeLead(Cliente $cliente, array $attributes = []): ClienteLead
{
    return ClienteLead::create(array_merge([
        'cliente_id' => $cliente->id,
        'bot_enabled' => true,
        'phone' => '55' . fake()->numerify('119#######'),
        'name' => 'Lead ' . fake()->numerify('###'),
        'info' => 'Info de teste',
    ], $attributes));
}

function agenciaLeadUniqueException(string $sql): UniqueConstraintViolationException
{
    return new UniqueConstraintViolationException(
        'pgsql',
        $sql,
        [],
        new \PDOException(
            'SQLSTATE[23505]: duplicate key value violates unique constraint "cliente_lead_cliente_id_phone_unique" DETAIL: Key (cliente_id, phone)=(1, 5511999999999) already exists.'
        )
    );
}

test('store da agencia retorna erro amigavel quando ocorre unique de cliente_lead por corrida', function () {
    $user = User::factory()->create();
    $cliente = agenciaLeadUniqueMakeCliente($user);
    $dispatcher = ClienteLead::getEventDispatcher();

    try {
        ClienteLead::creating(function () {
            throw agenciaLeadUniqueException('insert into "cliente_lead" (...) values (...)');
        });

        $response = $this->actingAs($user)->post(route('agencia.conversas.store'), [
            'cliente_id' => $cliente->id,
            'phone' => '5511999999999',
            'name' => 'Lead Teste',
            'info' => 'Info',
            'bot_enabled' => 1,
        ]);

        $response->assertRedirect(route('agencia.conversas.index'));
        $response->assertSessionHas('error', 'Este telefone já está cadastrado para o cliente selecionado.');
    } finally {
        ClienteLead::setEventDispatcher($dispatcher);
    }
});

test('update da agencia retorna erro amigavel quando ocorre unique de cliente_lead por corrida', function () {
    $user = User::factory()->create();
    $cliente = agenciaLeadUniqueMakeCliente($user);
    $lead = agenciaLeadUniqueMakeLead($cliente, ['phone' => '5511988887777']);
    $dispatcher = ClienteLead::getEventDispatcher();

    try {
        ClienteLead::updating(function () {
            throw agenciaLeadUniqueException('update "cliente_lead" set ...');
        });

        $response = $this->actingAs($user)->put(route('agencia.conversas.update', $lead), [
            'cliente_id' => $cliente->id,
            'phone' => $lead->phone,
            'name' => $lead->name,
            'info' => $lead->info,
        ]);

        $response->assertRedirect(route('agencia.conversas.index'));
        $response->assertSessionHas('error', 'Este telefone já está cadastrado para o cliente selecionado.');
    } finally {
        ClienteLead::setEventDispatcher($dispatcher);
    }
});

test('process incoming message recupera lead quando create sofre unique por corrida', function () {
    $user = User::factory()->create();
    $cliente = agenciaLeadUniqueMakeCliente($user);
    $conexao = Conexao::create([
        'cliente_id' => $cliente->id,
        'status' => 'active',
        'is_active' => true,
    ]);

    $phone = '5511999999999';
    $dispatcher = ClienteLead::getEventDispatcher();
    $inserted = false;

    try {
        ClienteLead::creating(function () use (&$inserted, $cliente, $phone) {
            if ($inserted) {
                return;
            }

            $inserted = true;
            DB::table('cliente_lead')->insert([
                'cliente_id' => $cliente->id,
                'bot_enabled' => true,
                'phone' => $phone,
                'name' => 'Lead concorrente',
                'info' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            throw agenciaLeadUniqueException('insert into "cliente_lead" (...) values (...)');
        });

        $job = new ProcessIncomingMessageJob((int) $conexao->id, null, []);

        (function ($conexao) {
            $this->conexao = $conexao;
        })->call($job, $conexao);

        /** @var ClienteLead|null $resolved */
        $resolved = (function (string $phone, string $leadName) {
            return $this->resolveClienteLead($phone, $leadName);
        })->call($job, $phone, 'Lead teste');

        expect($resolved)->not->toBeNull();
        expect((int) $resolved->cliente_id)->toBe((int) $cliente->id);
        expect((string) $resolved->phone)->toBe($phone);
        expect(
            ClienteLead::query()
                ->where('cliente_id', $cliente->id)
                ->where('phone', $phone)
                ->count()
        )->toBe(1);
    } finally {
        ClienteLead::setEventDispatcher($dispatcher);
    }
});
