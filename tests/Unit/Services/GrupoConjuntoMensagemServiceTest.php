<?php

namespace Tests\Unit\Services;

use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\GrupoConjunto;
use App\Models\GrupoConjuntoMensagem;
use App\Models\User;
use App\Models\WhatsappApi;
use App\Services\GrupoConjuntoActionTimingService;
use App\Services\GrupoConjuntoMensagemService;
use App\Services\UazapiGruposService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoConjuntoMensagemServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_and_persist_routes_send_media_action(): void
    {
        [$user, $conexao, $conjunto] = $this->makeConjuntoContext('token-media');

        $mensagem = GrupoConjuntoMensagem::create([
            'user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'grupo_conjunto_id' => $conjunto->id,
            'conexao_id' => $conexao->id,
            'mensagem' => 'Midia [image] https://example.com/banner.jpg',
            'action_type' => GrupoConjuntoMensagem::ACTION_SEND_MEDIA,
            'payload' => [
                'media_type' => 'image',
                'media_url' => 'https://example.com/banner.jpg',
                'caption' => 'Legenda',
            ],
            'dispatch_type' => 'now',
            'status' => 'queued',
            'recipients' => [
                ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
            ],
            'queued_at' => now('UTC'),
        ]);

        $mock = \Mockery::mock(UazapiGruposService::class);
        $mock->shouldReceive('sendMediaToGroup')
            ->once()
            ->with(
                'token-media',
                '120363153742561022@g.us',
                'image',
                'https://example.com/banner.jpg',
                ['text' => 'Legenda']
            )
            ->andReturn(['ok' => true, 'status' => 200]);

        $timing = \Mockery::mock(GrupoConjuntoActionTimingService::class);
        $timing->shouldReceive('acquireDispatchSlot')
            ->once()
            ->with($user->id, $conexao->id, '120363153742561022@g.us', GrupoConjuntoMensagem::ACTION_SEND_MEDIA)
            ->andReturn(true);
        $timing->shouldReceive('registerRemoteStatus')
            ->once()
            ->with($conexao->id, 200);

        $service = new GrupoConjuntoMensagemService($mock, $timing);
        $updated = $service->dispatchAndPersist($mensagem, 1);

        $this->assertSame('sent', $updated->status);
        $this->assertSame(1, (int) $updated->sent_count);
        $this->assertSame(0, (int) $updated->failed_count);
        $this->assertNull($updated->error_message);
    }

    public function test_dispatch_and_persist_marks_failed_on_partial_failure(): void
    {
        [$user, $conexao, $conjunto] = $this->makeConjuntoContext('token-partial');

        $mensagem = GrupoConjuntoMensagem::create([
            'user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'grupo_conjunto_id' => $conjunto->id,
            'conexao_id' => $conexao->id,
            'mensagem' => 'Texto em lote',
            'action_type' => GrupoConjuntoMensagem::ACTION_SEND_TEXT,
            'payload' => ['text' => 'Texto em lote'],
            'dispatch_type' => 'now',
            'status' => 'queued',
            'recipients' => [
                ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
                ['jid' => '120363339858396166@g.us', 'name' => 'Grupo B'],
            ],
            'queued_at' => now('UTC'),
        ]);

        $mock = \Mockery::mock(UazapiGruposService::class);
        $mock->shouldReceive('sendTextToGroup')
            ->once()
            ->with('token-partial', '120363153742561022@g.us', 'Texto em lote')
            ->andReturn(['ok' => true, 'status' => 200]);
        $mock->shouldReceive('sendTextToGroup')
            ->once()
            ->with('token-partial', '120363339858396166@g.us', 'Texto em lote')
            ->andReturn([
                'error' => true,
                'status' => 500,
                'body' => ['message' => 'Erro remoto'],
            ]);

        $timing = \Mockery::mock(GrupoConjuntoActionTimingService::class);
        $timing->shouldReceive('acquireDispatchSlot')
            ->twice()
            ->andReturn(true);
        $timing->shouldReceive('registerRemoteStatus')
            ->once()
            ->with($conexao->id, 200);
        $timing->shouldReceive('registerRemoteStatus')
            ->once()
            ->with($conexao->id, 500);

        $service = new GrupoConjuntoMensagemService($mock, $timing);
        $updated = $service->dispatchAndPersist($mensagem, 1);

        $this->assertSame('failed', $updated->status);
        $this->assertSame(1, (int) $updated->sent_count);
        $this->assertSame(1, (int) $updated->failed_count);
        $this->assertSame('Erro remoto', $updated->error_message);
    }

    public function test_dispatch_and_persist_routes_send_text_with_mentions_all(): void
    {
        [$user, $conexao, $conjunto] = $this->makeConjuntoContext('token-mention-text');

        $mensagem = GrupoConjuntoMensagem::create([
            'user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'grupo_conjunto_id' => $conjunto->id,
            'conexao_id' => $conexao->id,
            'mensagem' => 'Aviso geral',
            'action_type' => GrupoConjuntoMensagem::ACTION_SEND_TEXT,
            'payload' => [
                'text' => 'Aviso geral',
                'mention_all' => true,
            ],
            'dispatch_type' => 'now',
            'status' => 'queued',
            'recipients' => [
                ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
            ],
            'queued_at' => now('UTC'),
        ]);

        $mock = \Mockery::mock(UazapiGruposService::class);
        $mock->shouldReceive('sendTextToGroup')
            ->once()
            ->with(
                'token-mention-text',
                '120363153742561022@g.us',
                'Aviso geral',
                ['mentions' => 'all']
            )
            ->andReturn(['ok' => true, 'status' => 200]);

        $timing = \Mockery::mock(GrupoConjuntoActionTimingService::class);
        $timing->shouldReceive('acquireDispatchSlot')
            ->once()
            ->with($user->id, $conexao->id, '120363153742561022@g.us', GrupoConjuntoMensagem::ACTION_SEND_TEXT)
            ->andReturn(true);
        $timing->shouldReceive('registerRemoteStatus')
            ->once()
            ->with($conexao->id, 200);

        $service = new GrupoConjuntoMensagemService($mock, $timing);
        $updated = $service->dispatchAndPersist($mensagem, 1);

        $this->assertSame('sent', $updated->status);
        $this->assertSame(1, (int) $updated->sent_count);
        $this->assertSame(0, (int) $updated->failed_count);
    }

    public function test_dispatch_and_persist_uses_legacy_text_fallback_when_payload_missing(): void
    {
        [$user, $conexao, $conjunto] = $this->makeConjuntoContext('token-legacy');

        $mensagem = GrupoConjuntoMensagem::create([
            'user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'grupo_conjunto_id' => $conjunto->id,
            'conexao_id' => $conexao->id,
            'mensagem' => 'Texto legado sem payload',
            'action_type' => GrupoConjuntoMensagem::ACTION_SEND_TEXT,
            'payload' => null,
            'dispatch_type' => 'now',
            'status' => 'queued',
            'recipients' => [
                ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
            ],
            'queued_at' => now('UTC'),
        ]);

        $mock = \Mockery::mock(UazapiGruposService::class);
        $mock->shouldReceive('sendTextToGroup')
            ->once()
            ->with('token-legacy', '120363153742561022@g.us', 'Texto legado sem payload')
            ->andReturn(['ok' => true, 'status' => 200]);

        $timing = \Mockery::mock(GrupoConjuntoActionTimingService::class);
        $timing->shouldReceive('acquireDispatchSlot')
            ->once()
            ->with($user->id, $conexao->id, '120363153742561022@g.us', GrupoConjuntoMensagem::ACTION_SEND_TEXT)
            ->andReturn(true);
        $timing->shouldReceive('registerRemoteStatus')
            ->once()
            ->with($conexao->id, 200);

        $service = new GrupoConjuntoMensagemService($mock, $timing);
        $updated = $service->dispatchAndPersist($mensagem, 1);

        $this->assertSame('sent', $updated->status);
        $this->assertSame(1, (int) $updated->sent_count);
        $this->assertSame(0, (int) $updated->failed_count);
    }

    public function test_dispatch_and_persist_marks_group_failed_when_timing_slot_is_not_granted(): void
    {
        [$user, $conexao, $conjunto] = $this->makeConjuntoContext('token-throttle');

        $mensagem = GrupoConjuntoMensagem::create([
            'user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'grupo_conjunto_id' => $conjunto->id,
            'conexao_id' => $conexao->id,
            'mensagem' => 'Texto throttled',
            'action_type' => GrupoConjuntoMensagem::ACTION_SEND_TEXT,
            'payload' => ['text' => 'Texto throttled'],
            'dispatch_type' => 'now',
            'status' => 'queued',
            'recipients' => [
                ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
            ],
            'queued_at' => now('UTC'),
        ]);

        $mock = \Mockery::mock(UazapiGruposService::class);
        $mock->shouldNotReceive('sendTextToGroup');

        $timing = \Mockery::mock(GrupoConjuntoActionTimingService::class);
        $timing->shouldReceive('acquireDispatchSlot')
            ->once()
            ->with($user->id, $conexao->id, '120363153742561022@g.us', GrupoConjuntoMensagem::ACTION_SEND_TEXT)
            ->andReturn(false);
        $timing->shouldNotReceive('registerRemoteStatus');

        $service = new GrupoConjuntoMensagemService($mock, $timing);
        $updated = $service->dispatchAndPersist($mensagem, 1);

        $this->assertSame('failed', $updated->status);
        $this->assertSame(0, (int) $updated->sent_count);
        $this->assertSame(1, (int) $updated->failed_count);
        $this->assertNotNull($updated->error_message);
    }

    public function test_dispatch_and_persist_treats_update_name_error_as_success_when_group_info_matches(): void
    {
        [$user, $conexao, $conjunto] = $this->makeConjuntoContext('token-name-fallback');

        $mensagem = GrupoConjuntoMensagem::create([
            'user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'grupo_conjunto_id' => $conjunto->id,
            'conexao_id' => $conexao->id,
            'mensagem' => 'Novo titulo: Venda Mais com IA',
            'action_type' => GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_NAME,
            'payload' => ['group_name' => 'Venda Mais com IA'],
            'dispatch_type' => 'now',
            'status' => 'queued',
            'recipients' => [
                ['jid' => '120363153742561022@g.us', 'name' => 'Grupo A'],
            ],
            'queued_at' => now('UTC'),
        ]);

        $mock = \Mockery::mock(UazapiGruposService::class);
        $mock->shouldReceive('updateGroupName')
            ->once()
            ->with('token-name-fallback', '120363153742561022@g.us', 'Venda Mais com IA')
            ->andReturn([
                'error' => true,
                'status' => 500,
                'body' => ['error' => 'Failed to set group name: info query returned status 400: bad-request'],
            ]);
        $mock->shouldReceive('getGroupInfo')
            ->once()
            ->with('token-name-fallback', '120363153742561022@g.us')
            ->andReturn([
                'Name' => 'Venda Mais com IA',
            ]);

        $timing = \Mockery::mock(GrupoConjuntoActionTimingService::class);
        $timing->shouldReceive('acquireDispatchSlot')
            ->once()
            ->andReturn(true);
        $timing->shouldReceive('registerRemoteStatus')
            ->once()
            ->with($conexao->id, 200);

        $service = new GrupoConjuntoMensagemService($mock, $timing);
        $updated = $service->dispatchAndPersist($mensagem, 1);

        $this->assertSame('sent', $updated->status);
        $this->assertSame(1, (int) $updated->sent_count);
        $this->assertSame(0, (int) $updated->failed_count);
    }

    /**
     * @return array{0:User,1:Conexao,2:GrupoConjunto}
     */
    private function makeConjuntoContext(string $token): array
    {
        $user = User::factory()->create();

        $provider = WhatsappApi::query()->firstOrCreate(
            ['slug' => 'uazapi'],
            ['nome' => 'Uazapi', 'ativo' => true]
        );

        $cliente = Cliente::create([
            'user_id' => $user->id,
            'nome' => 'Cliente Teste',
            'email' => 'cliente.' . uniqid() . '@teste.com',
            'telefone' => '11999999999',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        $conexao = Conexao::create([
            'name' => 'Conexao Teste',
            'cliente_id' => $cliente->id,
            'whatsapp_api_id' => $provider->id,
            'whatsapp_api_key' => $token,
            'status' => 'active',
            'is_active' => true,
        ]);

        $conjunto = GrupoConjunto::create([
            'user_id' => $user->id,
            'conexao_id' => $conexao->id,
            'name' => 'Conjunto Teste',
        ]);

        return [$user, $conexao, $conjunto];
    }
}
