<?php

use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

test('admin can view assistants listing with related user and client names', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $owner = User::factory()->create([
        'name' => 'Maria Gestora',
    ]);
    $cliente = createAdminAssistantCliente($owner, [
        'nome' => 'Cliente Premium',
    ]);
    createAdminAssistantRecord($owner, $cliente, [
        'name' => 'Assistant Comercial',
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('adm.assistants.index'));

    $response->assertOk();
    $response->assertSeeText('Maria Gestora');
    $response->assertSeeText('Cliente Premium');
    $response->assertSeeText('Assistant Comercial');
});

test('admin can fetch assistant detail json for modal', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $owner = User::factory()->create([
        'name' => 'Rafael Owner',
    ]);
    $cliente = createAdminAssistantCliente($owner, [
        'nome' => 'Cliente JSON',
    ]);
    $assistant = createAdminAssistantRecord($owner, $cliente, [
        'name' => 'Assistant JSON',
        'instructions' => 'Instrucao principal',
        'systemPrompt' => 'System prompt completo',
        'developerPrompt' => 'Developer prompt completo',
        'prompt_notificar_adm' => 'Notificar administracao',
        'prompt_buscar_get' => 'Buscar dados externos',
        'prompt_enviar_media' => 'Enviar midia',
        'prompt_registrar_info_chat' => 'Registrar dados do chat',
        'prompt_gerenciar_agenda' => 'Gerenciar agenda',
        'prompt_aplicar_tags' => 'Aplicar tags',
        'prompt_sequencia' => 'Inscrever em sequencia',
        'modelo' => 'gpt-4.1-mini',
        'delay' => 12,
        'version' => 3,
        'openai_assistant_id' => 'asst_json_123',
    ]);

    $response = $this
        ->actingAs($admin)
        ->getJson(route('adm.assistants.show', $assistant));

    $response->assertOk();
    $response->assertJsonStructure([
        'summary' => [
            'id',
            'name',
            'user_name',
            'cliente_name',
            'openai_assistant_id',
            'modelo',
            'delay',
            'version',
            'created_at',
            'updated_at',
        ],
        'texts' => [
            'instructions',
            'systemPrompt',
            'developerPrompt',
            'prompt_notificar_adm',
            'prompt_buscar_get',
            'prompt_enviar_media',
            'prompt_registrar_info_chat',
            'prompt_gerenciar_agenda',
            'prompt_aplicar_tags',
            'prompt_sequencia',
        ],
    ]);
    $response->assertJsonPath('summary.name', 'Assistant JSON');
    $response->assertJsonPath('summary.user_name', 'Rafael Owner');
    $response->assertJsonPath('summary.cliente_name', 'Cliente JSON');
    $response->assertJsonPath('summary.openai_assistant_id', 'asst_json_123');
    $response->assertJsonPath('summary.modelo', 'gpt-4.1-mini');
    $response->assertJsonPath('summary.delay', 12);
    $response->assertJsonPath('summary.version', 3);
    $response->assertJsonPath('texts.instructions', 'Instrucao principal');
    $response->assertJsonPath('texts.systemPrompt', 'System prompt completo');
    $response->assertJsonPath('texts.prompt_sequencia', 'Inscrever em sequencia');
});

test('assistant without client shows placeholder in listing and null client in json', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $owner = User::factory()->create([
        'name' => 'Sem Cliente Owner',
    ]);
    $assistant = createAdminAssistantRecord($owner, null, [
        'name' => 'Assistant Sem Cliente',
    ]);

    $listResponse = $this
        ->actingAs($admin)
        ->get(route('adm.assistants.index'));

    $listResponse->assertOk();
    $listResponse->assertSeeText('Assistant Sem Cliente');
    $listResponse->assertSeeHtml('<td class="px-5 py-4 text-slate-600">-</td>');

    $detailResponse = $this
        ->actingAs($admin)
        ->getJson(route('adm.assistants.show', $assistant));

    $detailResponse->assertOk();
    $detailResponse->assertJsonPath('summary.cliente_name', null);
});

test('non admin user is redirected away from admin assistants area', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('adm.assistants.index'));

    $response->assertRedirect('/dashboard');
    $response->assertSessionHas('error');
});

test('admin can update assistant main fields through admin modal endpoint', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $owner = User::factory()->create();
    $assistant = createAdminAssistantRecord($owner, null, [
        'name' => 'Assistant Antigo',
        'instructions' => 'Texto antigo',
        'delay' => 3,
        'version' => 2,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patchJson(route('adm.assistants.update', $assistant), [
            'name' => 'Assistant Atualizado',
            'instructions' => 'Texto atualizado pelo admin',
            'delay' => 9,
        ]);

    $response->assertOk();
    $response->assertJsonPath('message', 'Assistente atualizado com sucesso.');
    $response->assertJsonPath('summary.name', 'Assistant Atualizado');
    $response->assertJsonPath('summary.delay', 9);
    $response->assertJsonPath('summary.version', 3);
    $response->assertJsonPath('texts.instructions', 'Texto atualizado pelo admin');

    $this->assertDatabaseHas('assistants', [
        'id' => $assistant->id,
        'name' => 'Assistant Atualizado',
        'instructions' => 'Texto atualizado pelo admin',
        'delay' => 9,
        'version' => 3,
    ]);
});

test('admin update endpoint returns the same summary and texts shape used by the detail modal', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $owner = User::factory()->create([
        'name' => 'Owner do Update',
    ]);
    $cliente = createAdminAssistantCliente($owner, [
        'nome' => 'Cliente do Update',
    ]);
    $assistant = createAdminAssistantRecord($owner, $cliente, [
        'name' => 'Assistant de Retorno',
        'instructions' => 'Texto inicial',
        'systemPrompt' => 'System inicial',
        'prompt_sequencia' => 'Sequencia inicial',
        'openai_assistant_id' => 'asst_update_shape',
        'delay' => 4,
        'version' => 1,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patchJson(route('adm.assistants.update', $assistant), [
            'name' => 'Assistant de Retorno Atualizado',
            'instructions' => 'Texto final',
            'delay' => 10,
        ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'message',
        'summary' => [
            'id',
            'name',
            'user_name',
            'cliente_name',
            'openai_assistant_id',
            'modelo',
            'delay',
            'version',
            'created_at',
            'updated_at',
        ],
        'texts' => [
            'instructions',
            'systemPrompt',
            'developerPrompt',
            'prompt_notificar_adm',
            'prompt_buscar_get',
            'prompt_enviar_media',
            'prompt_registrar_info_chat',
            'prompt_gerenciar_agenda',
            'prompt_aplicar_tags',
            'prompt_sequencia',
        ],
    ]);
    $response->assertJsonPath('summary.user_name', 'Owner do Update');
    $response->assertJsonPath('summary.cliente_name', 'Cliente do Update');
    $response->assertJsonPath('summary.openai_assistant_id', 'asst_update_shape');
    $response->assertJsonPath('texts.instructions', 'Texto final');
    $response->assertJsonPath('texts.systemPrompt', 'System inicial');
    $response->assertJsonPath('texts.prompt_sequencia', 'Sequencia inicial');
});

test('admin update endpoint validates required assistant fields', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $owner = User::factory()->create();
    $assistant = createAdminAssistantRecord($owner);

    $response = $this
        ->actingAs($admin)
        ->patchJson(route('adm.assistants.update', $assistant), [
            'name' => '',
            'instructions' => '',
            'delay' => -1,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'name',
        'instructions',
        'delay',
    ]);
});

test('non admin user can not update assistant through admin endpoint', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);
    $owner = User::factory()->create();
    $assistant = createAdminAssistantRecord($owner);

    $response = $this
        ->actingAs($user)
        ->patch(route('adm.assistants.update', $assistant), [
            'name' => 'Nao deve salvar',
            'instructions' => 'Nao deve salvar',
            'delay' => 2,
        ]);

    $response->assertRedirect('/dashboard');
    $response->assertSessionHas('error');
});

function createAdminAssistantCliente(User $user, array $overrides = []): Cliente
{
    return Cliente::create(array_merge([
        'user_id' => $user->id,
        'nome' => 'Cliente Teste',
        'email' => Str::lower(Str::random(8)).'@example.com',
        'telefone' => '5511999999999',
        'password' => Hash::make('password'),
        'is_active' => true,
    ], $overrides));
}

function createAdminAssistantRecord(User $user, ?Cliente $cliente = null, array $overrides = []): Assistant
{
    return Assistant::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente?->id,
        'openai_assistant_id' => 'asst_'.Str::lower(Str::random(12)),
        'name' => 'Assistant Teste',
        'instructions' => 'Instructions base',
        'version' => 1,
    ], $overrides));
}
