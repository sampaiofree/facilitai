<?php

use App\Models\Cliente;
use App\Models\ClienteCrmPipeline;
use App\Models\ClienteCrmPipelineColumn;
use App\Models\ClienteLead;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function clienteCrmMakeCliente(User $user, array $attributes = []): Cliente
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

function clienteCrmMakeLead(Cliente $cliente, array $attributes = []): ClienteLead
{
    return ClienteLead::create(array_merge([
        'cliente_id' => $cliente->id,
        'bot_enabled' => true,
        'phone' => '55' . fake()->unique()->numerify('119#######'),
        'name' => 'Lead ' . fake()->unique()->numerify('###'),
        'info' => 'Info de teste',
    ], $attributes));
}

function clienteCrmMakeTag(User $user, Cliente $cliente, array $attributes = []): Tag
{
    return Tag::create(array_merge([
        'user_id' => $user->id,
        'cliente_id' => $cliente->id,
        'name' => 'Tag ' . fake()->unique()->numerify('###'),
        'color' => null,
        'description' => null,
    ], $attributes));
}

function clienteCrmMakePipeline(Cliente $cliente, array $attributes = []): ClienteCrmPipeline
{
    return ClienteCrmPipeline::create(array_merge([
        'cliente_id' => $cliente->id,
        'name' => 'Pipeline ' . fake()->unique()->numerify('###'),
        'position' => 1,
    ], $attributes));
}

function clienteCrmMakeColumn(ClienteCrmPipeline $pipeline, Tag $tag, int $position): ClienteCrmPipelineColumn
{
    return ClienteCrmPipelineColumn::create([
        'pipeline_id' => $pipeline->id,
        'tag_id' => $tag->id,
        'position' => $position,
    ]);
}

test('crm lista pipelines do cliente e permite criar pipeline', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);

    $pipeline = clienteCrmMakePipeline($cliente, [
        'name' => 'Vendas',
        'position' => 1,
    ]);

    $response = $this->actingAs($cliente, 'client')->get(route('cliente.crm.index'));

    $response->assertOk();
    $response->assertSee('Vendas');
    $response->assertSee(route('cliente.crm.show', $pipeline), false);

    $storeResponse = $this->actingAs($cliente, 'client')
        ->from(route('cliente.crm.index'))
        ->post(route('cliente.crm.pipelines.store'), [
            'name' => 'Onboarding',
        ]);

    $storeResponse->assertRedirect(route('cliente.crm.index'));

    $newPipeline = ClienteCrmPipeline::query()
        ->where('cliente_id', $cliente->id)
        ->where('name', 'Onboarding')
        ->first();

    expect($newPipeline)->not->toBeNull();
    expect((int) $newPipeline->position)->toBe(2);
});

test('crm atualiza e reordena pipelines do cliente', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);

    $pipelineA = clienteCrmMakePipeline($cliente, ['name' => 'Primeira', 'position' => 1]);
    $pipelineB = clienteCrmMakePipeline($cliente, ['name' => 'Segunda', 'position' => 2]);

    $this->actingAs($cliente, 'client')
        ->patch(route('cliente.crm.pipelines.update', $pipelineA), [
            'name' => 'Inbound',
        ])
        ->assertRedirect(route('cliente.crm.index'));

    expect($pipelineA->fresh()->name)->toBe('Inbound');

    $this->actingAs($cliente, 'client')
        ->patchJson(route('cliente.crm.pipelines.reorder'), [
            'pipeline_ids' => [$pipelineB->id, $pipelineA->id],
        ])
        ->assertOk();

    expect((int) $pipelineB->fresh()->position)->toBe(1);
    expect((int) $pipelineA->fresh()->position)->toBe(2);
});

test('cliente nao pode acessar pipeline de outro cliente', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);
    $outroCliente = clienteCrmMakeCliente($user, ['email' => fake()->unique()->safeEmail()]);

    $pipelineOutroCliente = clienteCrmMakePipeline($outroCliente, ['name' => 'Privada']);

    $this->actingAs($cliente, 'client')
        ->get(route('cliente.crm.show', $pipelineOutroCliente))
        ->assertForbidden();
});

test('crm renderiza o container do board mesmo quando a pipeline esta vazia', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);
    $pipeline = clienteCrmMakePipeline($cliente, ['name' => 'Vendas']);
    clienteCrmMakeTag($user, $cliente, ['name' => 'Contato']);

    $response = $this->actingAs($cliente, 'client')
        ->get(route('cliente.crm.show', $pipeline));

    $response->assertOk();
    $response->assertSee('data-crm-board', false);
    $response->assertSee('Criar primeira coluna');
});

test('crm cria coluna com tag valida e impede reuso da mesma tag em outra pipeline', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);

    $pipelineA = clienteCrmMakePipeline($cliente, ['name' => 'Vendas', 'position' => 1]);
    $pipelineB = clienteCrmMakePipeline($cliente, ['name' => 'Onboarding', 'position' => 2]);
    $tagValida = clienteCrmMakeTag($user, $cliente, ['name' => 'Contato Vendas']);
    $tagAlternativa = clienteCrmMakeTag($user, $cliente, ['name' => 'Contato Onboarding']);

    $this->actingAs($cliente, 'client')
        ->from(route('cliente.crm.show', $pipelineA))
        ->post(route('cliente.crm.columns.store', $pipelineA), [
            'tag_id' => $tagValida->id,
        ])
        ->assertRedirect(route('cliente.crm.show', $pipelineA));

    $column = ClienteCrmPipelineColumn::query()
        ->where('pipeline_id', $pipelineA->id)
        ->where('tag_id', $tagValida->id)
        ->first();

    expect($column)->not->toBeNull();

    $duplicateResponse = $this->actingAs($cliente, 'client')
        ->from(route('cliente.crm.show', $pipelineB))
        ->post(route('cliente.crm.columns.store', $pipelineB), [
            'tag_id' => $tagValida->id,
        ]);

    $duplicateResponse->assertRedirect(route('cliente.crm.show', $pipelineB));
    $duplicateResponse->assertSessionHasErrors('tag_id');

    $this->actingAs($cliente, 'client')
        ->from(route('cliente.crm.show', $pipelineB))
        ->post(route('cliente.crm.columns.store', $pipelineB), [
            'tag_id' => $tagAlternativa->id,
        ])
        ->assertRedirect(route('cliente.crm.show', $pipelineB));

    expect(ClienteCrmPipelineColumn::query()->count())->toBe(2);
});

test('crm exibe o lead apenas na coluna mais a direita dentro da pipeline atual', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);

    $pipeline = clienteCrmMakePipeline($cliente, ['name' => 'Vendas']);
    $tagNovo = clienteCrmMakeTag($user, $cliente, ['name' => 'Novo']);
    $tagProposta = clienteCrmMakeTag($user, $cliente, ['name' => 'Proposta']);
    $colunaNovo = clienteCrmMakeColumn($pipeline, $tagNovo, 1);
    $colunaProposta = clienteCrmMakeColumn($pipeline, $tagProposta, 2);

    $leadNovo = clienteCrmMakeLead($cliente, ['name' => 'Lead Novo']);
    $leadNovo->tags()->sync([$tagNovo->id]);

    $leadProposta = clienteCrmMakeLead($cliente, ['name' => 'Lead Proposta']);
    $leadProposta->tags()->sync([$tagNovo->id, $tagProposta->id]);

    $leadFora = clienteCrmMakeLead($cliente, ['name' => 'Lead Fora']);

    $responseNovo = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.crm.columns.leads', [$pipeline, $colunaNovo]));

    $responseNovo->assertOk();
    expect($responseNovo->json('lead_ids'))->toBe([$leadNovo->id]);

    $responseProposta = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.crm.columns.leads', [$pipeline, $colunaProposta]));

    $responseProposta->assertOk();
    expect($responseProposta->json('lead_ids'))->toBe([$leadProposta->id]);

    $page = $this->actingAs($cliente, 'client')->get(route('cliente.crm.show', $pipeline));
    $page->assertOk();
    $page->assertDontSee('Lead Fora');
});

test('reordenar colunas altera a prioridade apenas da pipeline atual', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);

    $pipeline = clienteCrmMakePipeline($cliente, ['name' => 'Vendas']);
    $tagA = clienteCrmMakeTag($user, $cliente, ['name' => 'A']);
    $tagB = clienteCrmMakeTag($user, $cliente, ['name' => 'B']);
    $colunaA = clienteCrmMakeColumn($pipeline, $tagA, 1);
    $colunaB = clienteCrmMakeColumn($pipeline, $tagB, 2);

    $lead = clienteCrmMakeLead($cliente, ['name' => 'Lead Multi']);
    $lead->tags()->sync([$tagA->id, $tagB->id]);

    $this->actingAs($cliente, 'client')
        ->patchJson(route('cliente.crm.columns.reorder', $pipeline), [
            'column_ids' => [$colunaB->id, $colunaA->id],
        ])
        ->assertOk();

    $responseA = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.crm.columns.leads', [$pipeline, $colunaA]));

    $responseB = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.crm.columns.leads', [$pipeline, $colunaB]));

    expect($responseA->json('lead_ids'))->toBe([$lead->id]);
    expect($responseB->json('lead_ids'))->toBe([]);
});

test('mesmo lead pode existir em duas pipelines e mover em uma nao altera a outra', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);

    $pipelineVendas = clienteCrmMakePipeline($cliente, ['name' => 'Vendas', 'position' => 1]);
    $pipelineOnboarding = clienteCrmMakePipeline($cliente, ['name' => 'Onboarding', 'position' => 2]);

    $tagContato = clienteCrmMakeTag($user, $cliente, ['name' => 'Contato']);
    $tagFechado = clienteCrmMakeTag($user, $cliente, ['name' => 'Fechado']);
    $tagKickoff = clienteCrmMakeTag($user, $cliente, ['name' => 'Kickoff']);
    $tagImplantacao = clienteCrmMakeTag($user, $cliente, ['name' => 'Implantação']);

    $colunaContato = clienteCrmMakeColumn($pipelineVendas, $tagContato, 1);
    $colunaFechado = clienteCrmMakeColumn($pipelineVendas, $tagFechado, 2);
    $colunaKickoff = clienteCrmMakeColumn($pipelineOnboarding, $tagKickoff, 1);
    $colunaImplantacao = clienteCrmMakeColumn($pipelineOnboarding, $tagImplantacao, 2);

    $lead = clienteCrmMakeLead($cliente, ['name' => 'Lead Compartilhado']);
    $lead->tags()->sync([$tagContato->id, $tagImplantacao->id]);

    $this->actingAs($cliente, 'client')
        ->patchJson(route('cliente.crm.leads.column', [$pipelineVendas, $lead]), [
            'target_column_id' => $colunaFechado->id,
        ])
        ->assertOk()
        ->assertJsonPath('column_id', $colunaFechado->id)
        ->assertJsonPath('previous_column_id', $colunaContato->id);

    $tagIds = $lead->fresh('tags')->tags->pluck('id')->sort()->values()->all();

    expect($tagIds)->toBe(collect([$tagFechado->id, $tagImplantacao->id])->sort()->values()->all());

    $responseOnboarding = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.crm.columns.leads', [$pipelineOnboarding, $colunaImplantacao]));

    $responseOnboarding->assertOk();
    expect($responseOnboarding->json('lead_ids'))->toContain($lead->id);

    $responseKickoff = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.crm.columns.leads', [$pipelineOnboarding, $colunaKickoff]));

    $responseKickoff->assertOk();
    expect($responseKickoff->json('lead_ids'))->not->toContain($lead->id);
});

test('crm adiciona lead pelo modal na pipeline atual preservando outras pipelines e tags normais', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);

    $pipelineVendas = clienteCrmMakePipeline($cliente, ['name' => 'Vendas', 'position' => 1]);
    $pipelineOnboarding = clienteCrmMakePipeline($cliente, ['name' => 'Onboarding', 'position' => 2]);

    $tagProposta = clienteCrmMakeTag($user, $cliente, ['name' => 'Proposta']);
    $tagKickoff = clienteCrmMakeTag($user, $cliente, ['name' => 'Kickoff']);
    $tagVip = clienteCrmMakeTag($user, $cliente, ['name' => 'VIP']);

    $colunaProposta = clienteCrmMakeColumn($pipelineVendas, $tagProposta, 1);
    clienteCrmMakeColumn($pipelineOnboarding, $tagKickoff, 1);

    $lead = clienteCrmMakeLead($cliente, ['name' => 'Lead sem Vendas']);
    $lead->tags()->sync([$tagKickoff->id, $tagVip->id]);

    $this->actingAs($cliente, 'client')
        ->postJson(route('cliente.crm.columns.leads.store', [$pipelineVendas, $colunaProposta]), [
            'lead_id' => $lead->id,
        ])
        ->assertOk()
        ->assertJsonPath('column_id', $colunaProposta->id)
        ->assertJsonPath('previous_column_id', null);

    $tagIds = $lead->fresh('tags')->tags->pluck('id')->sort()->values()->all();

    expect($tagIds)->toBe(collect([$tagProposta->id, $tagKickoff->id, $tagVip->id])->sort()->values()->all());
});

test('crm remove lead apenas da pipeline atual preservando outras pipelines e tags normais', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);

    $pipelineVendas = clienteCrmMakePipeline($cliente, ['name' => 'Vendas', 'position' => 1]);
    $pipelineSucesso = clienteCrmMakePipeline($cliente, ['name' => 'Sucesso', 'position' => 2]);

    $tagFechado = clienteCrmMakeTag($user, $cliente, ['name' => 'Fechado']);
    $tagAtivo = clienteCrmMakeTag($user, $cliente, ['name' => 'Ativo']);
    $tagVip = clienteCrmMakeTag($user, $cliente, ['name' => 'VIP']);

    $colunaFechado = clienteCrmMakeColumn($pipelineVendas, $tagFechado, 1);
    clienteCrmMakeColumn($pipelineSucesso, $tagAtivo, 1);

    $lead = clienteCrmMakeLead($cliente, ['name' => 'Lead Removido']);
    $lead->tags()->sync([$tagFechado->id, $tagAtivo->id, $tagVip->id]);

    $this->actingAs($cliente, 'client')
        ->deleteJson(route('cliente.crm.leads.destroy', [$pipelineVendas, $lead]))
        ->assertOk()
        ->assertJsonPath('previous_column_id', $colunaFechado->id);

    $tagIds = $lead->fresh('tags')->tags->pluck('id')->sort()->values()->all();

    expect($tagIds)->toBe(collect([$tagAtivo->id, $tagVip->id])->sort()->values()->all());
});

test('crm busca leads por nome e telefone sem retornar leads de outro cliente', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);
    $outroCliente = clienteCrmMakeCliente($user, ['email' => fake()->unique()->safeEmail()]);

    $pipeline = clienteCrmMakePipeline($cliente, ['name' => 'Vendas']);
    $tagEntrada = clienteCrmMakeTag($user, $cliente, ['name' => 'Entrada']);
    $colunaEntrada = clienteCrmMakeColumn($pipeline, $tagEntrada, 1);

    $leadNome = clienteCrmMakeLead($cliente, ['name' => 'João Pipeline']);
    $leadTelefone = clienteCrmMakeLead($cliente, ['phone' => '5511912345678', 'name' => 'Telefone Pipeline']);
    $leadOutroCliente = clienteCrmMakeLead($outroCliente, ['name' => 'João Externo', 'phone' => '5511912399999']);

    $responseNome = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.crm.columns.search-leads', [$pipeline, $colunaEntrada]) . '?name=joa');

    $responseNome->assertOk()
        ->assertJsonPath('search_ready', true);

    expect($responseNome->json('lead_ids'))->toContain($leadNome->id);
    expect($responseNome->json('lead_ids'))->not->toContain($leadOutroCliente->id);

    $responseTelefone = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.crm.columns.search-leads', [$pipeline, $colunaEntrada]) . '?phone=1234');

    $responseTelefone->assertOk()
        ->assertJsonPath('search_ready', true);

    expect($responseTelefone->json('lead_ids'))->toContain($leadTelefone->id);
    expect($responseTelefone->json('lead_ids'))->not->toContain($leadOutroCliente->id);
});

test('crm carrega leads em lotes de dez por coluna na pipeline atual', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);

    $pipeline = clienteCrmMakePipeline($cliente, ['name' => 'Vendas']);
    $tag = clienteCrmMakeTag($user, $cliente, ['name' => 'Pipeline']);
    $coluna = clienteCrmMakeColumn($pipeline, $tag, 1);

    for ($i = 1; $i <= 12; $i++) {
        $lead = clienteCrmMakeLead($cliente, ['name' => 'Lead Lote ' . $i]);
        $lead->tags()->sync([$tag->id]);
    }

    $primeiraPagina = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.crm.columns.leads', [$pipeline, $coluna]) . '?offset=0');

    $primeiraPagina->assertOk();
    expect($primeiraPagina->json('lead_ids'))->toHaveCount(10);
    expect($primeiraPagina->json('has_more'))->toBeTrue();

    $segundaPagina = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.crm.columns.leads', [$pipeline, $coluna]) . '?offset=10');

    $segundaPagina->assertOk();
    expect($segundaPagina->json('lead_ids'))->toHaveCount(2);
    expect($segundaPagina->json('has_more'))->toBeFalse();
});

test('crm exclui coluna sem excluir a tag e faz o lead sumir da pipeline', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);

    $pipeline = clienteCrmMakePipeline($cliente, ['name' => 'Vendas']);
    $tagNovo = clienteCrmMakeTag($user, $cliente, ['name' => 'Novo']);
    $tagProposta = clienteCrmMakeTag($user, $cliente, ['name' => 'Proposta']);
    $colunaNovo = clienteCrmMakeColumn($pipeline, $tagNovo, 1);
    $colunaProposta = clienteCrmMakeColumn($pipeline, $tagProposta, 2);

    $lead = clienteCrmMakeLead($cliente, ['name' => 'Lead Antigo']);
    $lead->tags()->sync([$tagNovo->id]);

    $this->actingAs($cliente, 'client')
        ->delete(route('cliente.crm.columns.destroy', [$pipeline, $colunaNovo]))
        ->assertRedirect(route('cliente.crm.show', $pipeline));

    expect(ClienteCrmPipelineColumn::query()->whereKey($colunaNovo->id)->exists())->toBeFalse();
    expect(Tag::query()->whereKey($tagNovo->id)->exists())->toBeTrue();

    $response = $this->actingAs($cliente, 'client')
        ->getJson(route('cliente.crm.columns.leads', [$pipeline, $colunaProposta]));

    $response->assertOk();
    expect($response->json('lead_ids'))->not->toContain($lead->id);
});

test('crm exclui pipeline sem excluir tags nem limpar tags dos leads', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);

    $pipeline = clienteCrmMakePipeline($cliente, ['name' => 'Vendas']);
    $tag = clienteCrmMakeTag($user, $cliente, ['name' => 'Fechado']);
    $column = clienteCrmMakeColumn($pipeline, $tag, 1);
    $lead = clienteCrmMakeLead($cliente, ['name' => 'Lead Fechado']);
    $lead->tags()->sync([$tag->id]);

    $this->actingAs($cliente, 'client')
        ->delete(route('cliente.crm.pipelines.destroy', $pipeline))
        ->assertRedirect(route('cliente.crm.index'));

    expect(ClienteCrmPipeline::query()->whereKey($pipeline->id)->exists())->toBeFalse();
    expect(ClienteCrmPipelineColumn::query()->whereKey($column->id)->exists())->toBeFalse();
    expect(Tag::query()->whereKey($tag->id)->exists())->toBeTrue();
    expect($lead->fresh()->tags->pluck('id')->all())->toBe([$tag->id]);
});

test('cliente nao pode adicionar, mover ou remover lead de outro cliente em uma pipeline', function () {
    $user = User::factory()->create();
    $cliente = clienteCrmMakeCliente($user);
    $outroCliente = clienteCrmMakeCliente($user, ['email' => fake()->unique()->safeEmail()]);

    $pipeline = clienteCrmMakePipeline($cliente, ['name' => 'Vendas']);
    $tag = clienteCrmMakeTag($user, $cliente, ['name' => 'Etapa']);
    $coluna = clienteCrmMakeColumn($pipeline, $tag, 1);
    $leadOutroCliente = clienteCrmMakeLead($outroCliente, ['name' => 'Lead Externo']);

    $this->actingAs($cliente, 'client')
        ->postJson(route('cliente.crm.columns.leads.store', [$pipeline, $coluna]), [
            'lead_id' => $leadOutroCliente->id,
        ])
        ->assertForbidden();

    $this->actingAs($cliente, 'client')
        ->patchJson(route('cliente.crm.leads.column', [$pipeline, $leadOutroCliente]), [
            'target_column_id' => $coluna->id,
        ])
        ->assertForbidden();

    $this->actingAs($cliente, 'client')
        ->deleteJson(route('cliente.crm.leads.destroy', [$pipeline, $leadOutroCliente]))
        ->assertForbidden();
});
