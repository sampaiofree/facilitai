<?php

use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\User;
use App\Services\UazapiService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('erro ao enviar texto via uazapi encerra sem excecao e notifica agencia e dev', function () {
    config(['services.dev.whatsapp' => '5562999991111']);

    $user = User::factory()->create([
        'mobile_phone' => '5562999990000',
    ]);

    $cliente = Cliente::create([
        'user_id' => $user->id,
        'nome' => 'Cliente Uazapi',
        'email' => 'cliente-uazapi@example.com',
        'telefone' => '11999999999',
        'password' => 'secret123',
        'is_active' => true,
    ]);

    $conexao = Conexao::create([
        'cliente_id' => $cliente->id,
        'status' => 'active',
        'is_active' => true,
        'whatsapp_api_key' => 'token-uazapi',
    ]);

    $fakeUazapi = new class extends UazapiService {
        public array $calls = [];

        public function __construct()
        {
        }

        public function sendText(string $token, string $number, string $text): array
        {
            $this->calls[] = compact('token', 'number', 'text');

            return [
                'error' => true,
                'status' => 500,
                'body' => [
                    'error_key' => 'WHATSAPP_REACHOUT_TIMELOCK',
                    'provider_code' => 463,
                    'message_ptbr' => 'Conta temporariamente restrita.',
                ],
            ];
        }
    };

    $job = new class((int) $conexao->id, null, [
        'event_id' => 'event-uazapi-failure',
        'phone' => '5511999999999',
    ], $fakeUazapi) extends ProcessIncomingMessageJob {
        public function __construct(
            int $conexaoId,
            ?int $clienteLeadId,
            array $payload,
            private UazapiService $fakeUazapi
        ) {
            parent::__construct($conexaoId, $clienteLeadId, $payload);
        }

        protected function makeUazapiService(): UazapiService
        {
            return $this->fakeUazapi;
        }
    };

    (function () use ($conexao) {
        $this->conexao = $conexao->load('cliente.user');
    })->call($job);

    \Closure::bind(function () {
        $this->sendText('token-uazapi', '5511999999999', 'Mensagem original', [
            'conexao_id' => 999,
            'assistant_id' => 888,
            'lead_id' => 777,
            'event_id' => 'event-uazapi-failure',
        ]);
    }, $job, ProcessIncomingMessageJob::class)();

    expect($fakeUazapi->calls)->toHaveCount(3)
        ->and($fakeUazapi->calls[0]['number'])->toBe('5511999999999')
        ->and($fakeUazapi->calls[1]['number'])->toBe('5562999990000')
        ->and($fakeUazapi->calls[2]['number'])->toBe('5562999991111')
        ->and($fakeUazapi->calls[1]['text'])->toContain('WHATSAPP_REACHOUT_TIMELOCK')
        ->and($fakeUazapi->calls[1]['text'])->toContain('provider_code: 463')
        ->and($fakeUazapi->calls[2]['text'])->toContain('Conta temporariamente restrita.');
});
