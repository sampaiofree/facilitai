<?php

namespace Tests\Unit\Services;

use App\Services\UazapiGruposService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UazapiGruposServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.uazapi.url', 'https://uazapi.test');
    }

    public function test_list_groups_uses_get_endpoint(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([
            new Response(200, [], json_encode(['groups' => []])),
        ], $history);

        $response = $service->listGroups('token-123', true, true);

        $this->assertArrayNotHasKey('error', $response);
        $this->assertSame(['groups' => []], $response);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/group/list', $request->getUri()->getPath());
        $this->assertSame('token-123', $request->getHeaderLine('token'));

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertArrayHasKey('force', $query);
        $this->assertArrayHasKey('noparticipants', $query);
    }

    public function test_list_groups_paginated_uses_post_endpoint(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([
            new Response(200, [], json_encode([
                'groups' => [],
                'pagination' => ['totalRecords' => 0, 'pageSize' => 20, 'currentPage' => 1],
            ])),
        ], $history);

        $response = $service->listGroupsPaginated('token-123', [
            'page' => 1,
            'pageSize' => 20,
            'search' => 'suporte',
            'force' => true,
            'noParticipants' => true,
        ]);

        $this->assertArrayNotHasKey('error', $response);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/group/list', $request->getUri()->getPath());

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame(1, $payload['page']);
        $this->assertSame(20, $payload['pageSize']);
        $this->assertSame('suporte', $payload['search']);
        $this->assertTrue($payload['force']);
        $this->assertTrue($payload['noParticipants']);
    }

    public function test_get_group_info_sends_expected_payload(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([
            new Response(200, [], json_encode(['JID' => '120363153742561022@g.us', 'Name' => 'Grupo'])),
        ], $history);

        $response = $service->getGroupInfo('token-abc', '120363153742561022@g.us', [
            'getInviteLink' => true,
            'force' => true,
        ]);

        $this->assertArrayNotHasKey('error', $response);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/group/info', $request->getUri()->getPath());

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame('120363153742561022@g.us', $payload['groupjid']);
        $this->assertTrue($payload['getInviteLink']);
        $this->assertTrue($payload['force']);
    }

    public function test_get_group_invite_info_sends_expected_payload(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([
            new Response(200, [], json_encode(['JID' => '120363153742561022@g.us', 'Name' => 'Grupo Convite'])),
        ], $history);

        $response = $service->getGroupInviteInfo(
            'token-abc',
            'https://chat.whatsapp.com/IYnl5Zg9bUcJD32rJrDzO7'
        );

        $this->assertArrayNotHasKey('error', $response);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/group/inviteInfo', $request->getUri()->getPath());

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame(
            'https://chat.whatsapp.com/IYnl5Zg9bUcJD32rJrDzO7',
            $payload['invitecode']
        );
    }

    public function test_update_group_description_sends_expected_payload(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([
            new Response(200, [], json_encode(['response' => 'Group description updated successfully'])),
        ], $history);

        $response = $service->updateGroupDescription(
            'token-abc',
            '120363339858396166@g.us',
            'Grupo oficial de suporte'
        );

        $this->assertArrayNotHasKey('error', $response);
        $this->assertCount(1, $history);

        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('120363339858396166@g.us', $payload['groupjid']);
        $this->assertSame('Grupo oficial de suporte', $payload['description']);
    }

    public function test_update_group_image_sends_expected_payload(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([
            new Response(200, [], json_encode(['response' => 'Group image updated successfully'])),
        ], $history);

        $response = $service->updateGroupImage(
            'token-abc',
            '120363308883996631@g.us',
            'https://example.com/image.jpg'
        );

        $this->assertArrayNotHasKey('error', $response);
        $this->assertCount(1, $history);

        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('120363308883996631@g.us', $payload['groupjid']);
        $this->assertSame('https://example.com/image.jpg', $payload['image']);
    }

    public function test_update_group_name_sends_expected_payload(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([
            new Response(200, [], json_encode(['response' => 'Group name updated successfully'])),
        ], $history);

        $response = $service->updateGroupName(
            'token-abc',
            '120363339858396166@g.us',
            'Grupo de Suporte'
        );

        $this->assertArrayNotHasKey('error', $response);
        $this->assertCount(1, $history);

        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('120363339858396166@g.us', $payload['groupjid']);
        $this->assertSame('Grupo de Suporte', $payload['name']);
    }

    public function test_send_text_to_group_sends_number_as_group_jid(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([
            new Response(200, [], json_encode(['response' => ['status' => 'success']])),
        ], $history);

        $response = $service->sendTextToGroup(
            'token-abc',
            '120363153742561022@g.us',
            'Mensagem para o grupo',
            ['delay' => 1000]
        );

        $this->assertArrayNotHasKey('error', $response);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('/send/text', $request->getUri()->getPath());

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame('120363153742561022@g.us', $payload['number']);
        $this->assertSame('Mensagem para o grupo', $payload['text']);
        $this->assertSame(1000, $payload['delay']);
    }

    public function test_send_media_to_group_sends_expected_payload(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([
            new Response(200, [], json_encode(['response' => ['status' => 'success']])),
        ], $history);

        $response = $service->sendMediaToGroup(
            'token-abc',
            '120363153742561022@g.us',
            'document',
            'https://example.com/arquivo.pdf',
            ['docName' => 'arquivo.pdf']
        );

        $this->assertArrayNotHasKey('error', $response);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('/send/media', $request->getUri()->getPath());

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame('120363153742561022@g.us', $payload['number']);
        $this->assertSame('document', $payload['type']);
        $this->assertSame('https://example.com/arquivo.pdf', $payload['file']);
        $this->assertSame('arquivo.pdf', $payload['docName']);
    }

    public function test_invalid_group_jid_returns_422_without_sending_request(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([], $history);

        $response = $service->getGroupInfo('token-abc', 'grupo-invalido');

        $this->assertTrue($response['error']);
        $this->assertSame(422, $response['status']);
        $this->assertArrayHasKey('groupjid', $response['body']);
        $this->assertCount(0, $history);
    }

    public function test_invalid_invite_link_returns_422_without_sending_request(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([], $history);

        $response = $service->getGroupInviteInfo('token-abc', 'https://example.com/grupo/xyz');

        $this->assertTrue($response['error']);
        $this->assertSame(422, $response['status']);
        $this->assertArrayHasKey('invitecode', $response['body']);
        $this->assertCount(0, $history);
    }

    public function test_send_media_to_group_rejects_non_whitelisted_type(): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([], $history);

        $response = $service->sendMediaToGroup(
            'token-abc',
            '120363153742561022@g.us',
            'sticker',
            'https://example.com/sticker.webp'
        );

        $this->assertTrue($response['error']);
        $this->assertSame(422, $response['status']);
        $this->assertArrayHasKey('type', $response['body']);
        $this->assertCount(0, $history);
    }

    #[DataProvider('remoteErrorProvider')]
    public function test_remote_http_errors_keep_error_contract(int $statusCode): void
    {
        $history = [];
        $service = $this->makeServiceWithResponses([
            new Response($statusCode, [], json_encode(['error' => 'upstream failure'])),
        ], $history);

        $response = $service->sendTextToGroup(
            'token-abc',
            '120363153742561022@g.us',
            'teste'
        );

        $this->assertTrue($response['error']);
        $this->assertSame($statusCode, $response['status']);
        $this->assertSame(['error' => 'upstream failure'], $response['body']);
        $this->assertCount(1, $history);
    }

    public static function remoteErrorProvider(): array
    {
        return [
            'bad request' => [400],
            'unauthorized' => [401],
            'server error' => [500],
        ];
    }

    private function makeServiceWithResponses(array $responses, array &$history): UazapiGruposService
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($history));

        $client = new Client([
            'base_uri' => 'https://uazapi.test',
            'handler' => $handler,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        return new UazapiGruposService($client);
    }
}
