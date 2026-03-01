<?php

namespace Tests\Unit\Services;

use App\Services\WhatsappCloudApiService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappCloudApiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.whatsapp_cloud.base_url', 'https://graph.facebook.com');
        Config::set('services.whatsapp_cloud.version', 'v23.0');
        Config::set('services.whatsapp_cloud.phone_number_id', '1234567890');
        Config::set('services.whatsapp_cloud.access_token', 'test-token');
        Config::set('services.whatsapp_cloud.timeout', 15);
        Config::set('services.whatsapp_cloud.retry_times', 0);
        Config::set('services.whatsapp_cloud.retry_sleep_ms', 0);
    }

    public function test_send_text_uses_meta_messages_endpoint(): void
    {
        Http::fake([
            '*' => Http::response(['messages' => [['id' => 'wamid.text.1']]], 200),
        ]);

        $service = new WhatsappCloudApiService();
        $response = $service->sendText('+55 (11) 98888-7777', 'Ola, mundo');

        $this->assertFalse($response['error']);
        $this->assertSame(200, $response['status']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v23.0/1234567890/messages'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $data['messaging_product'] === 'whatsapp'
                && $data['to'] === '5511988887777'
                && $data['type'] === 'text'
                && $data['text']['body'] === 'Ola, mundo';
        });
    }

    public function test_send_audio_ptt_sends_audio_payload(): void
    {
        Http::fake([
            '*' => Http::response(['messages' => [['id' => 'wamid.audio.1']]], 200),
        ]);

        $service = new WhatsappCloudApiService();
        $response = $service->sendAudioPtt('5511988887777', 'https://cdn.exemplo.com/audio.ogg', [
            'upload_before_send' => false,
        ]);

        $this->assertFalse($response['error']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $data['type'] === 'audio'
                && $data['audio']['link'] === 'https://cdn.exemplo.com/audio.ogg'
                && ($data['audio']['voice'] ?? null) === true;
        });
    }

    public function test_send_audio_ptt_uploads_media_before_send_by_default(): void
    {
        Http::fake([
            'https://cdn.exemplo.com/audio.mp3' => Http::response('fake-audio-binary', 200, [
                'Content-Type' => 'audio/mpeg',
            ]),
            'https://graph.facebook.com/v23.0/1234567890/media' => Http::response(['id' => 'media-audio-123'], 200),
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response(['messages' => [['id' => 'wamid.audio.2']]], 200),
        ]);

        $service = new WhatsappCloudApiService();
        $response = $service->sendAudioPtt('5511988887777', 'https://cdn.exemplo.com/audio.mp3');

        $this->assertFalse($response['error']);
        $this->assertSame(200, $response['status']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v23.0/1234567890/media'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-token');
        });

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://graph.facebook.com/v23.0/1234567890/messages') {
                return false;
            }

            $data = $request->data();

            return ($data['type'] ?? null) === 'audio'
                && ($data['audio']['id'] ?? null) === 'media-audio-123'
                && ($data['audio']['voice'] ?? null) === true;
        });
    }

    public function test_send_document_pdf_rejects_non_pdf_url(): void
    {
        Http::fake();

        $service = new WhatsappCloudApiService();
        $response = $service->sendDocumentPdf('5511988887777', 'https://cdn.exemplo.com/arquivo.jpg');

        $this->assertTrue($response['error']);
        $this->assertSame(422, $response['status']);

        Http::assertNothingSent();
    }

    public function test_send_template_utility_builds_components_from_variables(): void
    {
        Http::fake([
            '*' => Http::response(['messages' => [['id' => 'wamid.template.1']]], 200),
        ]);

        $service = new WhatsappCloudApiService();
        $response = $service->sendTemplateUtility(
            '5511988887777',
            'lembrete_pagamento',
            ['Bruno', 'R$ 99,90'],
            ['language_code' => 'pt_BR']
        );

        $this->assertFalse($response['error']);

        Http::assertSent(function ($request) {
            $data = $request->data();
            $parameters = $data['template']['components'][0]['parameters'] ?? [];

            return $data['type'] === 'template'
                && $data['template']['name'] === 'lembrete_pagamento'
                && $data['template']['language']['code'] === 'pt_BR'
                && $parameters[0]['text'] === 'Bruno'
                && $parameters[1]['text'] === 'R$ 99,90';
        });
    }

    public function test_send_text_allows_credentials_override_by_options(): void
    {
        Config::set('services.whatsapp_cloud.phone_number_id', null);
        Config::set('services.whatsapp_cloud.access_token', null);

        Http::fake([
            '*' => Http::response(['messages' => [['id' => 'wamid.text.2']]], 200),
        ]);

        $service = new WhatsappCloudApiService();
        $response = $service->sendText('5511988887777', 'Teste override', [
            'phone_number_id' => '9988776655',
            'access_token' => 'override-token',
        ]);

        $this->assertFalse($response['error']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v23.0/9988776655/messages'
                && $request->hasHeader('Authorization', 'Bearer override-token');
        });
    }

    public function test_create_message_template_uses_waba_endpoint(): void
    {
        Http::fake([
            '*' => Http::response(['id' => 'template-123', 'status' => 'PENDING'], 200),
        ]);

        $service = new WhatsappCloudApiService();
        $response = $service->createMessageTemplate('596676946503382', [
            'name' => 'lembrete_pagamento',
            'language' => 'pt_BR',
            'category' => 'UTILITY',
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => 'Olá {{1}}',
                    'example' => [
                        'body_text' => [['Bruno']],
                    ],
                ],
            ],
        ], [
            'access_token' => 'meta-token',
        ]);

        $this->assertFalse($response['error']);
        $this->assertSame(200, $response['status']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v23.0/596676946503382/message_templates'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer meta-token')
                && $data['name'] === 'lembrete_pagamento'
                && $data['language'] === 'pt_BR'
                && $data['category'] === 'UTILITY';
        });
    }

    public function test_edit_message_template_uses_template_id_endpoint(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $service = new WhatsappCloudApiService();
        $response = $service->editMessageTemplate('123456789', [
            'category' => 'UTILITY',
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => 'Olá {{1}}',
                    'example' => [
                        'body_text' => [['Bruno']],
                    ],
                ],
            ],
        ], [
            'access_token' => 'meta-token',
        ]);

        $this->assertFalse($response['error']);
        $this->assertSame(200, $response['status']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v23.0/123456789'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer meta-token')
                && $data['category'] === 'UTILITY';
        });
    }
}
