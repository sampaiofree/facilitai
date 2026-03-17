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
