<?php

use App\Services\ToolsFactory;

test('registrar_campo_personalizado tool nao aparece sem keyword no prompt', function () {
    $tools = ToolsFactory::fromSystemPrompt('Use outras ferramentas.', [
        'lead_custom_fields' => [
            ['name' => 'empresa', 'label' => 'Empresa'],
        ],
    ]);

    expect(collect($tools)->pluck('name')->all())->not->toContain('registrar_campo_personalizado');
});

test('registrar_campo_personalizado tool aparece com enum dos campos do lead', function () {
    $tools = ToolsFactory::fromSystemPrompt('Use registrar_campo_personalizado quando necessario.', [
        'lead_custom_fields' => [
            ['name' => 'empresa', 'label' => 'Empresa'],
            ['name' => 'cargo', 'label' => 'Cargo'],
        ],
    ]);

    $tool = collect($tools)->firstWhere('name', 'registrar_campo_personalizado');

    expect($tool)->not->toBeNull();
    expect(data_get($tool, 'parameters.properties.campos.items.properties.campo.enum'))
        ->toBe(['cargo', 'empresa']);
});

test('registrar_campo_personalizado tool nao aparece sem campos elegiveis', function () {
    $tools = ToolsFactory::fromSystemPrompt('Use registrar_campo_personalizado quando necessario.', [
        'lead_custom_fields' => [],
    ]);

    expect(collect($tools)->pluck('name')->all())->not->toContain('registrar_campo_personalizado');
});

test('desativar_bot tool nao aparece sem keyword no prompt', function () {
    $tools = ToolsFactory::fromSystemPrompt('Use outras ferramentas.');

    expect(collect($tools)->pluck('name')->all())->not->toContain('desativar_bot');
});

test('desativar_bot tool aparece com keyword no prompt', function () {
    $tools = ToolsFactory::fromSystemPrompt('Use desativar_bot quando precisar pausar o atendimento automatico.');

    $tool = collect($tools)->firstWhere('name', 'desativar_bot');

    expect($tool)->not->toBeNull();
});

test('desativar_bot tool possui schema vazio e strict', function () {
    $tools = ToolsFactory::fromSystemPrompt('desativar_bot');

    $tool = collect($tools)->firstWhere('name', 'desativar_bot');
    $parametersJson = json_encode(data_get($tool, 'parameters'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    expect(data_get($tool, 'parameters.type'))->toBe('object');
    expect(data_get($tool, 'parameters.properties'))->toBeInstanceOf(\stdClass::class);
    expect(data_get($tool, 'parameters.required'))->toBe([]);
    expect(data_get($tool, 'parameters.additionalProperties'))->toBeFalse();
    expect($parametersJson)->toContain('"properties":{}');
    expect(data_get($tool, 'strict'))->toBeTrue();
});

test('enviar_lead_kommo tool nao aparece sem keyword no prompt', function () {
    $tools = ToolsFactory::fromSystemPrompt('Use outras ferramentas.', [
        'kommo_accounts' => [
            ['name' => 'kommo-vendas'],
        ],
    ]);

    expect(collect($tools)->pluck('name')->all())->not->toContain('enviar_lead_kommo');
});

test('enviar_lead_kommo tool nao aparece sem integracoes disponiveis', function () {
    $tools = ToolsFactory::fromSystemPrompt('Use enviar_lead_kommo quando necessario.', [
        'kommo_accounts' => [],
    ]);

    expect(collect($tools)->pluck('name')->all())->not->toContain('enviar_lead_kommo');
});

test('enviar_lead_kommo tool aparece com enum das integracoes kommo do cliente', function () {
    $tools = ToolsFactory::fromSystemPrompt('Use enviar_lead_kommo quando necessario.', [
        'kommo_accounts' => [
            ['name' => 'kommo-pos-venda'],
            ['name' => 'kommo-vendas'],
            ['name' => 'kommo-vendas'],
        ],
    ]);

    $tool = collect($tools)->firstWhere('name', 'enviar_lead_kommo');

    expect($tool)->not->toBeNull();
    expect(data_get($tool, 'parameters.properties.kommo_account_name.enum'))
        ->toBe(['kommo-pos-venda', 'kommo-vendas']);
    expect(data_get($tool, 'strict'))->toBeTrue();
});
