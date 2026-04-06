<?php

namespace Tests\Unit\Services;

use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\KommoAccount;
use App\Models\User;
use App\Services\KommoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KommoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_account_returns_account_data_on_success(): void
    {
        Http::fake([
            'https://minhaconta.kommo.com/api/v4/account' => Http::response([
                'id' => 123456,
                'name' => 'Minha Conta',
                'subdomain' => 'minhaconta',
            ], 200),
        ]);

        $service = new KommoService();
        $result = $service->validateAccount('https://minhaconta.kommo.com', 'token-valido');

        $this->assertTrue($result['ok']);
        $this->assertSame('123456', $result['account_id']);
        $this->assertSame('Minha Conta', $result['account_name']);
        $this->assertSame('minhaconta', $result['subdomain']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://minhaconta.kommo.com/api/v4/account'
                && $request->method() === 'GET'
                && $request->hasHeader('Authorization', 'Bearer token-valido');
        });
    }

    public function test_list_pipelines_returns_normalized_options(): void
    {
        Http::fake([
            'https://minhaconta.kommo.com/api/v4/leads/pipelines' => Http::response([
                '_embedded' => [
                    'pipelines' => [
                        ['id' => 10, 'name' => 'Pipeline A'],
                        ['id' => 20, 'name' => 'Pipeline B'],
                    ],
                ],
            ], 200),
        ]);

        $service = new KommoService();
        $result = $service->listPipelines('minhaconta', 'token-valido');

        $this->assertTrue($result['ok']);
        $this->assertSame([
            ['id' => '10', 'name' => 'Pipeline A'],
            ['id' => '20', 'name' => 'Pipeline B'],
        ], $result['pipelines']);
    }

    public function test_list_statuses_returns_normalized_options(): void
    {
        Http::fake([
            'https://minhaconta.kommo.com/api/v4/leads/pipelines/10/statuses' => Http::response([
                '_embedded' => [
                    'statuses' => [
                        ['id' => 100, 'name' => 'Novo Lead'],
                        ['id' => 200, 'name' => 'Contato'],
                    ],
                ],
            ], 200),
        ]);

        $service = new KommoService();
        $result = $service->listStatuses('minhaconta', 'token-valido', '10');

        $this->assertTrue($result['ok']);
        $this->assertSame([
            ['id' => '100', 'name' => 'Novo Lead'],
            ['id' => '200', 'name' => 'Contato'],
        ], $result['statuses']);
    }

    public function test_list_pipelines_returns_friendly_message_for_unauthorized(): void
    {
        Http::fake([
            'https://invalida.kommo.com/api/v4/leads/pipelines' => Http::response([
                'title' => 'Unauthorized',
            ], 401),
        ]);

        $service = new KommoService();
        $result = $service->listPipelines('invalida', 'token-invalido');

        $this->assertFalse($result['ok']);
        $this->assertSame(401, $result['status']);
        $this->assertSame('Unauthorized', $result['message']);
    }

    public function test_validate_account_handles_connection_failure(): void
    {
        Http::fake(function () {
            throw new \RuntimeException('timeout');
        });

        $service = new KommoService();
        $result = $service->validateAccount('minhaconta', 'token-valido');

        $this->assertFalse($result['ok']);
        $this->assertSame(0, $result['status']);
        $this->assertSame('Não foi possível conectar ao Kommo agora. Tente novamente.', $result['message']);
    }

    public function test_list_statuses_requires_pipeline_id(): void
    {
        $service = new KommoService();
        $result = $service->listStatuses('minhaconta', 'token-valido', '');

        $this->assertFalse($result['ok']);
        $this->assertSame(422, $result['status']);
        $this->assertSame('Selecione uma pipeline Kommo válida.', $result['message']);
    }

    public function test_normalize_subdomain_accepts_host_and_url_variants(): void
    {
        $service = new KommoService();

        $this->assertSame('minhaconta', $service->normalizeSubdomain('minhaconta'));
        $this->assertSame('minhaconta', $service->normalizeSubdomain('minhaconta.kommo.com'));
        $this->assertSame('minhaconta', $service->normalizeSubdomain('https://minhaconta.kommo.com/api/v4/account'));
    }

    public function test_send_lead_to_account_builds_minimal_complex_payload(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::create([
            'user_id' => $user->id,
            'nome' => 'Cliente Teste',
            'email' => 'cliente-kommo@example.com',
            'telefone' => '11999999999',
            'password' => 'secret123',
            'is_active' => true,
        ]);
        $lead = ClienteLead::create([
            'cliente_id' => $cliente->id,
            'bot_enabled' => true,
            'phone' => '5511999999999',
            'name' => 'Alessandro Gatto',
            'info' => 'Nao enviar na v1',
        ]);
        $account = KommoAccount::create([
            'user_id' => $user->id,
            'cliente_id' => $cliente->id,
            'name' => 'kommo-vendas',
            'subdomain' => 'minhaconta',
            'access_token' => 'token-valido',
            'kommo_account_id' => '123',
            'kommo_account_name' => 'Conta Kommo',
            'pipeline_id' => '10',
            'pipeline_name' => 'Pipeline A',
            'status_id' => '100',
            'status_name' => 'Novo Lead',
        ]);

        Http::fake([
            'https://minhaconta.kommo.com/api/v4/leads/complex' => Http::response([
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

        $service = new KommoService();
        $result = $service->sendLeadToAccount($account, $lead);

        $this->assertTrue($result['ok']);
        $this->assertSame('777', $result['lead_id']);
        $this->assertSame('888', $result['contact_id']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://minhaconta.kommo.com/api/v4/leads/complex'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer token-valido')
                && data_get($data, '0.name') === 'Lead enviado pelo assistente'
                && data_get($data, '0.pipeline_id') === 10
                && data_get($data, '0.status_id') === 100
                && data_get($data, '0._embedded.contacts.0.name') === 'Alessandro Gatto'
                && data_get($data, '0._embedded.contacts.0.custom_fields_values.0.field_code') === 'PHONE'
                && data_get($data, '0._embedded.contacts.0.custom_fields_values.0.values.0.value') === '5511999999999';
        });
    }

    public function test_send_lead_to_account_returns_friendly_error_when_kommo_rejects_request(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::create([
            'user_id' => $user->id,
            'nome' => 'Cliente Teste',
            'email' => 'cliente-kommo-error@example.com',
            'telefone' => '11999999999',
            'password' => 'secret123',
            'is_active' => true,
        ]);
        $lead = ClienteLead::create([
            'cliente_id' => $cliente->id,
            'bot_enabled' => true,
            'phone' => '5511999999999',
            'name' => 'Lead Erro',
            'info' => 'Info',
        ]);
        $account = KommoAccount::create([
            'user_id' => $user->id,
            'cliente_id' => $cliente->id,
            'name' => 'kommo-erro',
            'subdomain' => 'minhaconta',
            'access_token' => 'token-valido',
            'kommo_account_id' => '123',
            'kommo_account_name' => 'Conta Kommo',
            'pipeline_id' => '10',
            'pipeline_name' => 'Pipeline A',
            'status_id' => '100',
            'status_name' => 'Novo Lead',
        ]);

        Http::fake([
            'https://minhaconta.kommo.com/api/v4/leads/complex' => Http::response([
                'detail' => 'Invalid pipeline',
            ], 400),
        ]);

        $service = new KommoService();
        $result = $service->sendLeadToAccount($account, $lead);

        $this->assertFalse($result['ok']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('Invalid pipeline', $result['message']);
    }
}
