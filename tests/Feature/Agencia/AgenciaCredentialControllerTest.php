<?php

use App\Models\Cliente;
use App\Models\Credential;
use App\Models\Iaplataforma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function agenciaCredentialMakeCliente(User $user, array $attributes = []): Cliente
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

function agenciaCredentialMakePlatform(array $attributes = []): Iaplataforma
{
    return Iaplataforma::create(array_merge([
        'nome' => 'OpenAI',
        'ativo' => true,
    ], $attributes));
}

function agenciaCredentialMakeCredential(User $user, Iaplataforma $platform, array $attributes = []): Credential
{
    return $user->credentials()->create(array_merge([
        'name' => 'OpenAI Principal',
        'label' => 'OpenAI Principal',
        'token' => 'super-secreto',
        'iaplataforma_id' => $platform->id,
        'cliente_id' => null,
    ], $attributes));
}

test('agencia cria credencial sem cliente vinculado', function () {
    $user = User::factory()->create();
    $platform = agenciaCredentialMakePlatform();

    $response = $this->actingAs($user)->post(route('agencia.credentials.store'), [
        'name' => 'OpenAI Principal',
        'token' => 'super-secreto',
        'iaplataforma_id' => $platform->id,
        'cliente_id' => '',
    ]);

    $response->assertRedirect(route('agencia.credentials.index'));
    $response->assertSessionHas('success');

    $credential = Credential::query()->sole();

    expect($credential->cliente_id)->toBeNull();
    expect($credential->iaplataforma_id)->toBe($platform->id);
});

test('agencia cria credencial com cliente do proprio usuario', function () {
    $user = User::factory()->create();
    $cliente = agenciaCredentialMakeCliente($user, ['nome' => 'Cliente Alpha']);
    $platform = agenciaCredentialMakePlatform();

    $response = $this->actingAs($user)->post(route('agencia.credentials.store'), [
        'name' => 'OpenAI Cliente Alpha',
        'token' => 'super-secreto',
        'iaplataforma_id' => $platform->id,
        'cliente_id' => $cliente->id,
    ]);

    $response->assertRedirect(route('agencia.credentials.index'));
    $response->assertSessionHas('success');

    $credential = Credential::query()->sole();

    expect($credential->cliente_id)->toBe($cliente->id);
});

test('agencia rejeita cliente de outro usuario ao criar credencial', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCliente = agenciaCredentialMakeCliente($otherUser);
    $platform = agenciaCredentialMakePlatform();

    $response = $this->actingAs($user)
        ->from(route('agencia.credentials.index'))
        ->post(route('agencia.credentials.store'), [
            'name' => 'OpenAI Externa',
            'token' => 'super-secreto',
            'iaplataforma_id' => $platform->id,
            'cliente_id' => $otherCliente->id,
        ]);

    $response->assertRedirect(route('agencia.credentials.index'));
    $response->assertSessionHasErrors('cliente_id');

    expect(Credential::count())->toBe(0);
});

test('agencia atualiza credencial para vincular cliente', function () {
    $user = User::factory()->create();
    $cliente = agenciaCredentialMakeCliente($user);
    $platform = agenciaCredentialMakePlatform();
    $credential = agenciaCredentialMakeCredential($user, $platform, [
        'name' => 'OpenAI Global',
        'label' => 'OpenAI Global',
    ]);

    $response = $this->actingAs($user)->patch(route('agencia.credentials.update', $credential), [
        'name' => 'OpenAI Cliente',
        'token' => '',
        'iaplataforma_id' => $platform->id,
        'cliente_id' => $cliente->id,
    ]);

    $response->assertRedirect(route('agencia.credentials.index'));
    $response->assertSessionHas('success');

    $credential->refresh();

    expect($credential->cliente_id)->toBe($cliente->id);
    expect($credential->name)->toBe('OpenAI Cliente');
});

test('agencia atualiza credencial para remover cliente vinculado', function () {
    $user = User::factory()->create();
    $cliente = agenciaCredentialMakeCliente($user);
    $platform = agenciaCredentialMakePlatform();
    $credential = agenciaCredentialMakeCredential($user, $platform, [
        'cliente_id' => $cliente->id,
    ]);

    $response = $this->actingAs($user)->patch(route('agencia.credentials.update', $credential), [
        'name' => 'OpenAI Principal',
        'token' => '',
        'iaplataforma_id' => $platform->id,
        'cliente_id' => '',
    ]);

    $response->assertRedirect(route('agencia.credentials.index'));
    $response->assertSessionHas('success');

    expect($credential->fresh()->cliente_id)->toBeNull();
});

test('agencia lista cliente vinculado ou global na tabela de credenciais', function () {
    $user = User::factory()->create();
    $cliente = agenciaCredentialMakeCliente($user, ['nome' => 'Cliente Premium']);
    $platform = agenciaCredentialMakePlatform(['nome' => 'OpenAI']);

    agenciaCredentialMakeCredential($user, $platform, [
        'name' => 'Credencial Global',
        'label' => 'Credencial Global',
        'cliente_id' => null,
    ]);

    agenciaCredentialMakeCredential($user, $platform, [
        'name' => 'Credencial Cliente',
        'label' => 'Credencial Cliente',
        'cliente_id' => $cliente->id,
    ]);

    $this->actingAs($user)
        ->get(route('agencia.credentials.index'))
        ->assertOk()
        ->assertSee('Cliente')
        ->assertSee('Global')
        ->assertSee('Cliente Premium')
        ->assertSee('Sem cliente / Global');
});
