<?php

use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\Credential;
use App\Models\Iamodelo;
use App\Models\Iaplataforma;
use App\Models\User;
use App\Models\WhatsappApi;
use Illuminate\Support\Str;

function clienteConexaoEditMakeCliente(User $user, array $attributes = []): Cliente
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

function clienteConexaoEditMakePlatform(array $attributes = []): Iaplataforma
{
    return Iaplataforma::create(array_merge([
        'nome' => 'Plataforma ' . Str::lower(Str::random(5)),
        'ativo' => true,
    ], $attributes));
}

function clienteConexaoEditMakeModel(Iaplataforma $platform, array $attributes = []): Iamodelo
{
    return Iamodelo::create(array_merge([
        'iaplataforma_id' => $platform->id,
        'nome' => 'modelo-' . Str::lower(Str::random(5)),
        'ativo' => true,
    ], $attributes));
}

function clienteConexaoEditMakeCredential(User $user, Iaplataforma $platform, array $attributes = []): Credential
{
    return $user->credentials()->create(array_merge([
        'name' => 'Credencial ' . Str::lower(Str::random(4)),
        'label' => 'Credencial principal',
        'token' => 'super-secreto',
        'iaplataforma_id' => $platform->id,
        'cliente_id' => null,
    ], $attributes));
}

function clienteConexaoEditMakeAssistant(User $user, Cliente $cliente, array $attributes = []): Assistant
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

function clienteConexaoEditMakeWhatsappApi(array $attributes = []): WhatsappApi
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

function clienteConexaoEditMakeConexao(
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

test('cliente ve botao de editar apenas quando permitiredicao estiver ativo', function () {
    $user = User::factory()->create();
    $cliente = clienteConexaoEditMakeCliente($user);
    $platform = clienteConexaoEditMakePlatform(['nome' => 'OpenAI']);
    $model = clienteConexaoEditMakeModel($platform, ['nome' => 'gpt-4.1-mini']);
    $credential = clienteConexaoEditMakeCredential($user, $platform);
    $assistant = clienteConexaoEditMakeAssistant($user, $cliente);
    $api = clienteConexaoEditMakeWhatsappApi();

    clienteConexaoEditMakeConexao($cliente, $credential, $assistant, $model, $api, [
        'name' => 'Conexao permitida',
        'permitiredicao' => true,
    ]);

    clienteConexaoEditMakeConexao($cliente, $credential, $assistant, $model, $api, [
        'name' => 'Conexao bloqueada',
        'permitiredicao' => false,
    ]);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.conexoes.index'));

    $response
        ->assertOk()
        ->assertSeeText('Conexao permitida')
        ->assertSeeText('Conexao bloqueada');

    expect(substr_count($response->getContent(), '>Editar</button>'))->toBe(1);
});

test('cliente pode editar apenas modelo e conexao ativa quando permitiredicao estiver ativo', function () {
    $user = User::factory()->create();
    $cliente = clienteConexaoEditMakeCliente($user);
    $platform = clienteConexaoEditMakePlatform(['nome' => 'OpenAI']);
    $modelA = clienteConexaoEditMakeModel($platform, ['nome' => 'gpt-4.1-mini']);
    $modelB = clienteConexaoEditMakeModel($platform, ['nome' => 'gpt-4.1']);
    $credential = clienteConexaoEditMakeCredential($user, $platform);
    $assistant = clienteConexaoEditMakeAssistant($user, $cliente);
    $api = clienteConexaoEditMakeWhatsappApi();

    $conexao = clienteConexaoEditMakeConexao($cliente, $credential, $assistant, $modelA, $api, [
        'name' => 'Conexao do cliente',
        'is_active' => true,
        'permitiredicao' => true,
    ]);

    $response = $this->actingAs($cliente, 'client')
        ->from(route('cliente.conexoes.index'))
        ->patch(route('cliente.conexoes.update', $conexao), [
            'name' => 'Nome nao deveria mudar',
            'assistant_id' => $assistant->id,
            'cliente_id' => $cliente->id,
            'model' => $modelB->id,
            'is_active' => '0',
        ]);

    $response
        ->assertRedirect(route('cliente.conexoes.index'))
        ->assertSessionHas('success', 'Conexao atualizada com sucesso.');

    $conexao->refresh();

    expect($conexao->model)->toBe($modelB->id);
    expect($conexao->is_active)->toBeFalse();
    expect($conexao->name)->toBe('Conexao do cliente');
    expect($conexao->assistant_id)->toBe($assistant->id);
    expect($conexao->cliente_id)->toBe($cliente->id);
});

test('cliente nao pode editar conexao quando permitiredicao estiver inativo', function () {
    $user = User::factory()->create();
    $cliente = clienteConexaoEditMakeCliente($user);
    $platform = clienteConexaoEditMakePlatform(['nome' => 'OpenAI']);
    $model = clienteConexaoEditMakeModel($platform, ['nome' => 'gpt-4.1-mini']);
    $credential = clienteConexaoEditMakeCredential($user, $platform);
    $assistant = clienteConexaoEditMakeAssistant($user, $cliente);
    $api = clienteConexaoEditMakeWhatsappApi();

    $conexao = clienteConexaoEditMakeConexao($cliente, $credential, $assistant, $model, $api, [
        'permitiredicao' => false,
    ]);

    $this->actingAs($cliente, 'client')
        ->patch(route('cliente.conexoes.update', $conexao), [
            'model' => $model->id,
            'is_active' => '0',
        ])
        ->assertForbidden();

    expect($conexao->fresh()->is_active)->toBeTrue();
});
