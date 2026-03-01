<?php

use App\Jobs\WhatsappCloudWebhookJob;
use App\Models\User;
use App\Models\WhatsappCloudAccount;
use Illuminate\Support\Facades\Queue;

it('validates whatsapp cloud webhook challenge with user verify token', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'whatsapp_cloud_webhook_key' => 'wcu_test_verify_1',
        'whatsapp_cloud_webhook_verify_token' => 'wvu_test_verify_1',
    ])->save();

    $response = $this->get(route('api.whatsapp-cloud.webhook.verify', [
        'webhookKey' => $user->whatsapp_cloud_webhook_key,
        'hub.mode' => 'subscribe',
        'hub.verify_token' => 'wvu_test_verify_1',
        'hub.challenge' => '123456',
    ]));

    $response->assertOk();
    expect($response->getContent())->toBe('123456');
});

it('rejects whatsapp cloud webhook challenge with invalid token', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'whatsapp_cloud_webhook_key' => 'wcu_test_verify_2',
        'whatsapp_cloud_webhook_verify_token' => 'wvu_test_verify_2',
    ])->save();

    $response = $this->get(route('api.whatsapp-cloud.webhook.verify', [
        'webhookKey' => $user->whatsapp_cloud_webhook_key,
        'hub.mode' => 'subscribe',
        'hub.verify_token' => 'token-invalido',
        'hub.challenge' => '123456',
    ]));

    $response->assertForbidden();
});

it('queues whatsapp cloud webhook job when signature is valid', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->forceFill([
        'whatsapp_cloud_webhook_key' => 'wcu_test_signature_1',
        'whatsapp_cloud_webhook_verify_token' => 'wvu_test_signature_1',
    ])->save();

    WhatsappCloudAccount::create([
        'user_id' => $user->id,
        'name' => 'Conta Cloud 3',
        'phone_number_id' => '1006011152593391',
        'business_account_id' => '596676946503384',
        'app_id' => '930699719381274',
        'app_secret' => 'secret-signature-test',
        'access_token' => 'test-token',
        'is_default' => false,
    ]);

    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [
            [
                'id' => '596676946503384',
                'changes' => [
                    [
                        'field' => 'messages',
                        'value' => [
                            'metadata' => [
                                'phone_number_id' => '1006011152593391',
                            ],
                            'messages' => [],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $signature = 'sha256=' . hash_hmac('sha256', (string) $json, 'secret-signature-test');

    $response = $this->call(
        'POST',
        route('api.whatsapp-cloud.webhook', ['webhookKey' => $user->whatsapp_cloud_webhook_key]),
        [],
        [],
        [],
        [
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ],
        $json
    );

    $response->assertOk();
    $response->assertJson(['status' => 'queued']);

    Queue::assertPushed(WhatsappCloudWebhookJob::class);
});

it('rejects whatsapp cloud webhook post when signature is invalid', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->forceFill([
        'whatsapp_cloud_webhook_key' => 'wcu_test_signature_2',
        'whatsapp_cloud_webhook_verify_token' => 'wvu_test_signature_2',
    ])->save();

    WhatsappCloudAccount::create([
        'user_id' => $user->id,
        'name' => 'Conta Cloud 4',
        'phone_number_id' => '1006011152593392',
        'business_account_id' => '596676946503385',
        'app_id' => '930699719381275',
        'app_secret' => 'secret-signature-test-2',
        'access_token' => 'test-token',
        'is_default' => false,
    ]);

    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [],
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $response = $this->call(
        'POST',
        route('api.whatsapp-cloud.webhook', ['webhookKey' => $user->whatsapp_cloud_webhook_key]),
        [],
        [],
        [],
        [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
            'CONTENT_TYPE' => 'application/json',
        ],
        $json
    );

    $response->assertForbidden();

    Queue::assertNotPushed(WhatsappCloudWebhookJob::class);
});
