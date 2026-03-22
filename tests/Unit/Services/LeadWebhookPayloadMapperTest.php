<?php

use App\Services\LeadWebhookPayloadMapper;

test('payload mapper lista caminhos escalares e renderiza template', function () {
    $mapper = app(LeadWebhookPayloadMapper::class);
    $payload = [
        'contact' => [
            'name' => 'Maria',
            'phone' => '5511999999999',
        ],
        'company' => [
            'name' => 'ACME LTDA',
        ],
        'flags' => [
            'vip' => true,
        ],
    ];

    $paths = $mapper->scalarPaths($payload);

    expect($paths)->toMatchArray([
        'payload.company.name' => 'ACME LTDA',
        'payload.contact.name' => 'Maria',
        'payload.contact.phone' => '5511999999999',
        'payload.flags.vip' => true,
    ]);

    $rendered = $mapper->renderTemplate(
        'Lead {{payload.contact.name}} da empresa {{payload.company.name}}. VIP: {{payload.flags.vip}}.',
        $payload
    );

    expect($rendered)->toBe('Lead Maria da empresa ACME LTDA. VIP: true.');
});
