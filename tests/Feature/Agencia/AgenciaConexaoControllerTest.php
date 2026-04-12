<?php

use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\Credential;
use App\Models\Iamodelo;
use App\Models\Iaplataforma;
use App\Models\Plan;
use App\Models\User;
use App\Models\WhatsappApi;
use App\Models\WhatsappCloudAccount;
use Illuminate\Support\Str;

function agenciaConexaoFieldMakePlan(array $attributes = []): Plan
{
    return Plan::create(array_merge([
        'name' => 'Plano Agencia',
        'price_cents' => 199,
        'max_conexoes' => 10,
        'storage_limit_mb' => 1024,
    ], $attributes));
}

function agenciaConexaoFieldMakeCliente(User $user, array $attributes = []): Cliente
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

function agenciaConexaoFieldMakePlatform(array $attributes = []): Iaplataforma
{
    return Iaplataforma::create(array_merge([
        'nome' => 'Plataforma ' . Str::lower(Str::random(5)),
        'ativo' => true,
    ], $attributes));
}

function agenciaConexaoFieldMakeModel(Iaplataforma $platform, array $attributes = []): Iamodelo
{
    return Iamodelo::create(array_merge([
        'iaplataforma_id' => $platform->id,
        'nome' => 'modelo-' . Str::lower(Str::random(5)),
        'ativo' => true,
    ], $attributes));
}

function agenciaConexaoFieldMakeCredential(User $user, Iaplataforma $platform, array $attributes = []): Credential
{
    return $user->credentials()->create(array_merge([
        'name' => 'Credencial ' . Str::lower(Str::random(4)),
        'label' => 'Credencial principal',
        'token' => 'super-secreto',
        'iaplataforma_id' => $platform->id,
        'cliente_id' => null,
    ], $attributes));
}

function agenciaConexaoFieldMakeAssistant(User $user, Cliente $cliente, array $attributes = []): Assistant
{
    return Assistant::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'openai_assistant_id' => 'asst_' . Str::lower(Str::random(10)),
        'name' => 'Assistente ' . Str::lower(Str::random(4)),
        'instructions' => 'Instrucoes do assistente',
        'delay' => 5,
        'version' => 1,
    ], $attributes));
}

function agenciaConexaoFieldMakeWhatsappApi(array $attributes = []): WhatsappApi
{
    $attributes = array_merge([
        'nome' => 'WhatsApp Cloud',
        'descricao' => 'API de teste',
        'slug' => 'whatsapp_cloud',
        'ativo' => true,
    ], $attributes);

    return WhatsappApi::firstOrCreate(
        ['slug' => $attributes['slug']],
        $attributes
    );
}

function agenciaConexaoFieldMakeCloudAccount(User $user, array $attributes = []): WhatsappCloudAccount
{
    return WhatsappCloudAccount::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Conta Cloud ' . Str::lower(Str::random(4)),
        'phone_number_id' => fake()->unique()->numerify('100000########'),
        'business_account_id' => fake()->numerify('200000########'),
        'access_token' => 'cloud-token',
        'is_default' => true,
    ], $attributes));
}

function agenciaConexaoFieldMakeConexao(
    User $user,
    Cliente $cliente,
    Credential $credential,
    Assistant $assistant,
    Iamodelo $model,
    WhatsappApi $api,
    array $attributes = []
): Conexao {
    return Conexao::create(array_merge([
        'name' => 'Conexao ' . Str::lower(Str::random(4)),
        'cliente_id' => $cliente->id,
        'credential_id' => $credential->id,
        'assistant_id' => $assistant->id,
        'model' => $model->id,
        'whatsapp_api_id' => $api->id,
        'status' => 'active',
        'is_active' => true,
        'permitiredicao' => false,
    ], $attributes));
}

test('agencia lista a coluna permitir edicao com sim e nao', function () {
    $plan = agenciaConexaoFieldMakePlan();
    $user = User::factory()->create(['plan_id' => $plan->id]);
    $platform = agenciaConexaoFieldMakePlatform(['nome' => 'OpenAI']);
    $model = agenciaConexaoFieldMakeModel($platform, ['nome' => 'gpt-4.1-mini']);
    $api = agenciaConexaoFieldMakeWhatsappApi();

    $clienteSim = agenciaConexaoFieldMakeCliente($user, ['nome' => 'Cliente Sim']);
    $credentialSim = agenciaConexaoFieldMakeCredential($user, $platform, ['name' => 'Credencial Sim']);
    $assistantSim = agenciaConexaoFieldMakeAssistant($user, $clienteSim, ['name' => 'Assistente Sim']);
    agenciaConexaoFieldMakeConexao($user, $clienteSim, $credentialSim, $assistantSim, $model, $api, [
        'name' => 'Conexao Sim',
        'permitiredicao' => true,
    ]);

    $clienteNao = agenciaConexaoFieldMakeCliente($user, ['nome' => 'Cliente Nao']);
    $credentialNao = agenciaConexaoFieldMakeCredential($user, $platform, ['name' => 'Credencial Nao']);
    $assistantNao = agenciaConexaoFieldMakeAssistant($user, $clienteNao, ['name' => 'Assistente Nao']);
    agenciaConexaoFieldMakeConexao($user, $clienteNao, $credentialNao, $assistantNao, $model, $api, [
        'name' => 'Conexao Nao',
        'permitiredicao' => false,
    ]);

    $response = $this->actingAs($user)->get(route('agencia.conexoes.index'));

    $response
        ->assertOk()
        ->assertSeeText('Permitir edicao')
        ->assertSeeText('Conexao Sim')
        ->assertSeeText('Conexao Nao')
        ->assertSeeText('Sim')
        ->assertSeeText('Nao');
});

test('agencia salva permitiredicao false ao criar conexao sem marcar o campo', function () {
    $plan = agenciaConexaoFieldMakePlan();
    $user = User::factory()->create(['plan_id' => $plan->id]);
    $platform = agenciaConexaoFieldMakePlatform(['nome' => 'OpenAI']);
    $model = agenciaConexaoFieldMakeModel($platform, ['nome' => 'gpt-4.1-mini']);
    $cliente = agenciaConexaoFieldMakeCliente($user);
    $credential = agenciaConexaoFieldMakeCredential($user, $platform);
    $assistant = agenciaConexaoFieldMakeAssistant($user, $cliente);
    $api = agenciaConexaoFieldMakeWhatsappApi();
    $cloudAccount = agenciaConexaoFieldMakeCloudAccount($user);

    $response = $this->actingAs($user)
        ->from(route('agencia.conexoes.index'))
        ->post(route('agencia.conexoes.store'), [
            'name' => 'Conexao sem permissao',
            'credential_id' => $credential->id,
            'assistant_id' => $assistant->id,
            'cliente_id' => $cliente->id,
            'model' => $model->id,
            'whatsapp_api_id' => $api->id,
            'whatsapp_cloud_account_id' => $cloudAccount->id,
            'is_active' => '1',
            'permitiredicao' => '0',
        ]);

    $response
        ->assertRedirect(route('agencia.conexoes.index'))
        ->assertSessionHas('success');

    expect(Conexao::query()->where('name', 'Conexao sem permissao')->sole()->permitiredicao)->toBeFalse();
});

test('agencia salva permitiredicao true ao criar conexao com o campo marcado', function () {
    $plan = agenciaConexaoFieldMakePlan();
    $user = User::factory()->create(['plan_id' => $plan->id]);
    $platform = agenciaConexaoFieldMakePlatform(['nome' => 'OpenAI']);
    $model = agenciaConexaoFieldMakeModel($platform, ['nome' => 'gpt-4.1-mini']);
    $cliente = agenciaConexaoFieldMakeCliente($user);
    $credential = agenciaConexaoFieldMakeCredential($user, $platform);
    $assistant = agenciaConexaoFieldMakeAssistant($user, $cliente);
    $api = agenciaConexaoFieldMakeWhatsappApi();
    $cloudAccount = agenciaConexaoFieldMakeCloudAccount($user);

    $response = $this->actingAs($user)
        ->from(route('agencia.conexoes.index'))
        ->post(route('agencia.conexoes.store'), [
            'name' => 'Conexao com permissao',
            'credential_id' => $credential->id,
            'assistant_id' => $assistant->id,
            'cliente_id' => $cliente->id,
            'model' => $model->id,
            'whatsapp_api_id' => $api->id,
            'whatsapp_cloud_account_id' => $cloudAccount->id,
            'is_active' => '1',
            'permitiredicao' => '1',
        ]);

    $response
        ->assertRedirect(route('agencia.conexoes.index'))
        ->assertSessionHas('success');

    expect(Conexao::query()->where('name', 'Conexao com permissao')->sole()->permitiredicao)->toBeTrue();
});

test('agencia atualiza permitiredicao entre false e true', function () {
    $plan = agenciaConexaoFieldMakePlan();
    $user = User::factory()->create(['plan_id' => $plan->id]);
    $platform = agenciaConexaoFieldMakePlatform(['nome' => 'OpenAI']);
    $model = agenciaConexaoFieldMakeModel($platform, ['nome' => 'gpt-4.1-mini']);
    $cliente = agenciaConexaoFieldMakeCliente($user);
    $credential = agenciaConexaoFieldMakeCredential($user, $platform);
    $assistant = agenciaConexaoFieldMakeAssistant($user, $cliente);
    $api = agenciaConexaoFieldMakeWhatsappApi();

    $conexao = agenciaConexaoFieldMakeConexao($user, $cliente, $credential, $assistant, $model, $api, [
        'name' => 'Conexao editavel',
        'permitiredicao' => false,
    ]);

    $response = $this->actingAs($user)
        ->patch(route('agencia.conexoes.update', $conexao), [
            'name' => 'Conexao editavel',
            'credential_id' => $credential->id,
            'assistant_id' => $assistant->id,
            'cliente_id' => $cliente->id,
            'model' => $model->id,
            'is_active' => '1',
            'permitiredicao' => '1',
        ]);

    $response
        ->assertRedirect(route('agencia.conexoes.index'))
        ->assertSessionHas('success');

    expect($conexao->fresh()->permitiredicao)->toBeTrue();

    $secondResponse = $this->actingAs($user)
        ->patch(route('agencia.conexoes.update', $conexao), [
            'name' => 'Conexao editavel',
            'credential_id' => $credential->id,
            'assistant_id' => $assistant->id,
            'cliente_id' => $cliente->id,
            'model' => $model->id,
            'is_active' => '1',
            'permitiredicao' => '0',
        ]);

    $secondResponse
        ->assertRedirect(route('agencia.conexoes.index'))
        ->assertSessionHas('success');

    expect($conexao->fresh()->permitiredicao)->toBeFalse();
});
