<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

test('agencia user can create whatsapp cloud template', function () {
    $user = User::factory()->create();

    $accountId = DB::table('whatsapp_cloud_accounts')->insertGetId([
        'user_id' => $user->id,
        'name' => 'Conta Teste',
        'phone_number_id' => '1006011152593389',
        'business_account_id' => '596676946503382',
        'access_token' => 'raw-token-for-testing',
        'is_default' => 1,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    DB::table('whatsapp_cloud_custom_fields')->insert([
        [
            'user_id' => $user->id,
            'name' => 'nome_cliente',
            'label' => 'Nome do cliente',
            'sample_value' => 'Bruno',
            'description' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
        [
            'user_id' => $user->id,
            'name' => 'valor_total',
            'label' => 'Valor total',
            'sample_value' => 'R$ 99,90',
            'description' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ],
    ]);

    Http::fake([
        '*' => Http::response([
            'id' => 'meta-template-id-1',
            'status' => 'PENDING',
        ], 200),
    ]);

    $response = $this->actingAs($user)->post(route('agencia.whatsapp-cloud.templates.store'), [
        'active_tab' => 'templates',
        'whatsapp_cloud_account_id' => $accountId,
        'title' => 'Lembrete Pagamento',
        'language_code' => 'pt_BR',
        'category' => 'UTILITY',
        'body_text' => 'Olá {nome_cliente}, seu pagamento {valor_total} está pendente.',
        'variable_examples' => [
            'nome_cliente' => 'Bruno',
            'valor_total' => 'R$ 99,90',
        ],
    ]);

    $response->assertRedirect(route('agencia.whatsapp-cloud.index', ['account_id' => $accountId]));

    $this->assertDatabaseHas('whatsapp_cloud_templates', [
        'user_id' => $user->id,
        'whatsapp_cloud_account_id' => $accountId,
        'title' => 'Lembrete Pagamento',
        'template_name' => 'lembrete_pagamento',
        'language_code' => 'pt_BR',
        'category' => 'UTILITY',
        'status' => 'PENDING',
        'meta_template_id' => 'meta-template-id-1',
    ]);

    $template = DB::table('whatsapp_cloud_templates')
        ->where('whatsapp_cloud_account_id', $accountId)
        ->where('template_name', 'lembrete_pagamento')
        ->first();

    expect(json_decode((string) $template->variables, true))->toBe([
        'nome_cliente',
        'valor_total',
    ]);
});

test('user cannot create template using account from another user', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $accountId = DB::table('whatsapp_cloud_accounts')->insertGetId([
        'user_id' => $owner->id,
        'name' => 'Conta Owner',
        'phone_number_id' => '1006011152593390',
        'business_account_id' => '596676946503383',
        'access_token' => 'raw-token-for-testing',
        'is_default' => 1,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    $response = $this->actingAs($attacker)->post(route('agencia.whatsapp-cloud.templates.store'), [
        'active_tab' => 'templates',
        'whatsapp_cloud_account_id' => $accountId,
        'title' => 'Template Outro User',
        'language_code' => 'pt_BR',
        'category' => 'UTILITY',
        'body_text' => 'Mensagem simples',
    ]);

    $response->assertStatus(404);

    $this->assertDatabaseMissing('whatsapp_cloud_templates', [
        'template_name' => 'template_outro_user',
    ]);
});

test('template name is auto-generated with incremental suffix when duplicated', function () {
    $user = User::factory()->create();

    $accountId = DB::table('whatsapp_cloud_accounts')->insertGetId([
        'user_id' => $user->id,
        'name' => 'Conta Unica',
        'phone_number_id' => '1006011152593391',
        'business_account_id' => '596676946503384',
        'access_token' => 'raw-token-for-testing',
        'is_default' => 1,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    Http::fake([
        '*' => Http::response([
            'id' => 'meta-template-id-2',
            'status' => 'PENDING',
        ], 200),
    ]);

    DB::table('whatsapp_cloud_templates')->insert([
        'user_id' => $user->id,
        'whatsapp_cloud_account_id' => $accountId,
        'conexao_id' => null,
        'title' => 'Seguimento Venda',
        'template_name' => 'seguimento_venda',
        'language_code' => 'pt_BR',
        'category' => 'UTILITY',
        'variables' => null,
        'body_text' => 'Texto',
        'status' => 'PENDING',
        'meta_template_id' => 'meta-template-id-existing',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    $response = $this->actingAs($user)
        ->post(route('agencia.whatsapp-cloud.templates.store'), [
            'active_tab' => 'templates',
            'whatsapp_cloud_account_id' => $accountId,
            'title' => 'Seguimento Venda',
            'language_code' => 'pt_BR',
            'category' => 'UTILITY',
            'body_text' => 'Mensagem sem variável',
        ]);

    $response->assertRedirect(route('agencia.whatsapp-cloud.index', ['account_id' => $accountId]));

    expect(
        DB::table('whatsapp_cloud_templates')
            ->where('whatsapp_cloud_account_id', $accountId)
            ->whereIn('template_name', ['seguimento_venda', 'seguimento_venda2'])
            ->where('language_code', 'pt_BR')
            ->count()
    )->toBe(2);

    $this->assertDatabaseHas('whatsapp_cloud_templates', [
        'whatsapp_cloud_account_id' => $accountId,
        'template_name' => 'seguimento_venda2',
    ]);
});
