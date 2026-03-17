<?php

use App\Jobs\DispatchWhatsappCloudCampaignJob;
use App\Jobs\SendWhatsappCloudCampaignItemJob;
use App\Models\User;
use App\Models\WhatsappCloudCampaign;
use App\Models\WhatsappCloudCampaignItem;
use App\Services\WhatsappCloudConversationWindowService;
use App\Services\WhatsappCloudTemplateContextSyncService;
use App\Services\WhatsappCloudTemplateSendService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

test('agencia user can create immediate whatsapp cloud campaign and queue dispatch job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $fixture = createWhatsappCloudCampaignFixture($user, 2);

    $response = $this->actingAs($user)->post(route('agencia.whatsapp-cloud.campaigns.store'), [
        'active_tab' => 'campaigns',
        'name' => 'Campanha Teste',
        'cliente_id' => $fixture['cliente_id'],
        'conexao_id' => $fixture['conexao_id'],
        'whatsapp_cloud_template_id' => $fixture['template_id'],
        'mode' => 'immediate',
    ]);

    $response->assertRedirect(route('agencia.whatsapp-cloud.index', [
        'tab' => 'campaigns',
        'account_id' => $fixture['account_id'],
    ]));

    Queue::assertPushed(DispatchWhatsappCloudCampaignJob::class, function (DispatchWhatsappCloudCampaignJob $job): bool {
        return $job->queue === DispatchWhatsappCloudCampaignJob::QUEUE_NAME;
    });

    $campaign = WhatsappCloudCampaign::query()->sole();

    expect($campaign->user_id)->toBe($user->id);
    expect($campaign->created_by_user_id)->toBe($user->id);
    expect($campaign->cliente_id)->toBe($fixture['cliente_id']);
    expect($campaign->conexao_id)->toBe($fixture['conexao_id']);
    expect($campaign->whatsapp_cloud_account_id)->toBe($fixture['account_id']);
    expect($campaign->whatsapp_cloud_template_id)->toBe($fixture['template_id']);
    expect($campaign->status)->toBe('draft');
    expect($campaign->mode)->toBe('immediate');
    expect($campaign->total_leads)->toBe(2);

    expect(
        WhatsappCloudCampaignItem::query()
            ->where('whatsapp_cloud_campaign_id', $campaign->id)
            ->count()
    )->toBe(2);

    expect(
        WhatsappCloudCampaignItem::query()
            ->where('whatsapp_cloud_campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->count()
    )->toBe(2);
});

test('send whatsapp cloud campaign item job queues next item on campaign queue', function () {
    Queue::fake();

    $user = User::factory()->create();
    $fixture = createWhatsappCloudCampaignFixture($user, 2);
    $now = Carbon::now();

    $campaign = WhatsappCloudCampaign::create([
        'user_id' => $user->id,
        'created_by_user_id' => $user->id,
        'cliente_id' => $fixture['cliente_id'],
        'conexao_id' => $fixture['conexao_id'],
        'whatsapp_cloud_account_id' => $fixture['account_id'],
        'whatsapp_cloud_template_id' => $fixture['template_id'],
        'name' => 'Campanha em andamento',
        'mode' => 'immediate',
        'status' => 'running',
        'total_leads' => 2,
        'settings' => [
            'interval_seconds' => 0,
            'assistant_context_instructions' => null,
            'template_variable_bindings' => [],
        ],
        'filter_payload' => [
            'source' => 'cliente_all_leads',
            'tags' => ['include' => [], 'exclude' => []],
            'sequences' => ['include' => [], 'exclude' => []],
            'rules' => ['include_logic' => 'or', 'exclude_logic' => 'any'],
        ],
        'started_at' => $now,
    ]);

    $sentItem = WhatsappCloudCampaignItem::create([
        'whatsapp_cloud_campaign_id' => $campaign->id,
        'cliente_lead_id' => $fixture['lead_ids'][0],
        'phone' => '5511999990001',
        'status' => 'sent',
        'attempts' => 1,
        'idempotency_key' => 'sent-item',
        'sent_at' => $now,
    ]);

    $pendingItem = WhatsappCloudCampaignItem::create([
        'whatsapp_cloud_campaign_id' => $campaign->id,
        'cliente_lead_id' => $fixture['lead_ids'][1],
        'phone' => '5511999990002',
        'status' => 'pending',
        'attempts' => 0,
        'idempotency_key' => 'pending-item',
    ]);

    $job = new SendWhatsappCloudCampaignItemJob($sentItem->id);
    $job->handle(
        \Mockery::mock(WhatsappCloudTemplateSendService::class),
        \Mockery::mock(WhatsappCloudConversationWindowService::class),
        \Mockery::mock(WhatsappCloudTemplateContextSyncService::class)
    );

    Queue::assertPushed(SendWhatsappCloudCampaignItemJob::class, function (SendWhatsappCloudCampaignItemJob $job): bool {
        return $job->queue === DispatchWhatsappCloudCampaignJob::QUEUE_NAME;
    });

    $pendingItem->refresh();
    $campaign->refresh();

    expect($pendingItem->status)->toBe('queued');
    expect($pendingItem->queued_at)->not->toBeNull();
    expect($campaign->queued_count)->toBe(1);
});

function createWhatsappCloudCampaignFixture(User $user, int $leadCount): array
{
    $now = Carbon::now();

    $whatsappApiId = DB::table('whatsapp_api')
        ->where('slug', 'whatsapp_cloud')
        ->value('id');

    if (!$whatsappApiId) {
        $whatsappApiId = DB::table('whatsapp_api')->insertGetId([
            'nome' => 'WhatsApp Cloud',
            'descricao' => 'Provider de teste',
            'slug' => 'whatsapp_cloud',
            'ativo' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $clienteId = DB::table('clientes')->insertGetId([
        'user_id' => $user->id,
        'nome' => 'Cliente Teste',
        'email' => 'cliente+' . $user->id . '@example.com',
        'telefone' => '5511999991111',
        'password' => bcrypt('secret'),
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $accountId = DB::table('whatsapp_cloud_accounts')->insertGetId([
        'user_id' => $user->id,
        'name' => 'Conta Cloud',
        'phone_number_id' => '1006011152593389',
        'business_account_id' => '596676946503382',
        'access_token' => 'cloud-token',
        'is_default' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $conexaoId = DB::table('conexoes')->insertGetId([
        'name' => 'Conexao Cloud',
        'status' => 'conectado',
        'is_active' => true,
        'cliente_id' => $clienteId,
        'whatsapp_api_id' => $whatsappApiId,
        'whatsapp_cloud_account_id' => $accountId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $templateId = DB::table('whatsapp_cloud_templates')->insertGetId([
        'user_id' => $user->id,
        'whatsapp_cloud_account_id' => $accountId,
        'conexao_id' => $conexaoId,
        'title' => 'Template Campanha',
        'template_name' => 'template_campanha',
        'language_code' => 'pt_BR',
        'category' => 'UTILITY',
        'variables' => json_encode([]),
        'body_text' => 'Mensagem de campanha',
        'status' => 'APPROVED',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $leadIds = [];
    for ($index = 1; $index <= $leadCount; $index++) {
        $leadIds[] = DB::table('cliente_lead')->insertGetId([
            'cliente_id' => $clienteId,
            'bot_enabled' => true,
            'phone' => '55119999900' . str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            'name' => 'Lead ' . $index,
            'info' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    return [
        'cliente_id' => $clienteId,
        'account_id' => $accountId,
        'conexao_id' => $conexaoId,
        'template_id' => $templateId,
        'lead_ids' => $leadIds,
    ];
}
