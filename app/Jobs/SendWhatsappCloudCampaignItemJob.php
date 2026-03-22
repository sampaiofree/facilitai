<?php

namespace App\Jobs;

use App\Models\WhatsappCloudCampaign;
use App\Models\WhatsappCloudCampaignItem;
use App\Services\WhatsappCloudConversationWindowService;
use App\Services\WhatsappCloudTemplateContextSyncService;
use App\Services\WhatsappCloudTemplateSendService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendWhatsappCloudCampaignItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public int $timeout = 900;

    public function __construct(private readonly int $campaignItemId)
    {
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(
        WhatsappCloudTemplateSendService $templateSendService,
        WhatsappCloudConversationWindowService $conversationWindowService,
        WhatsappCloudTemplateContextSyncService $templateContextSyncService
    ): void {
        $item = WhatsappCloudCampaignItem::query()
            ->with([
                'campaign.user',
                'campaign.conexao.whatsappApi',
                'campaign.conexao.whatsappCloudAccount',
                'campaign.template',
                'lead.customFieldValues',
            ])
            ->find($this->campaignItemId);

        if (!$item) {
            return;
        }

        if (in_array($item->status, ['sent', 'failed', 'skipped', 'canceled'], true)) {
            $this->continueCampaign((int) $item->whatsapp_cloud_campaign_id);
            return;
        }

        /** @var WhatsappCloudCampaign|null $campaign */
        $campaign = $item->campaign;
        if (!$campaign) {
            $this->markAsFailed($item, 'Campanha não encontrada para este item.');
            return;
        }

        if ($campaign->status === 'canceled') {
            $this->markAsCanceled($item);
            $this->finalizeCampaignIfDone((int) $campaign->id);
            return;
        }

        $conexao = $campaign->conexao;
        $template = $campaign->template;
        $lead = $item->lead;
        $userId = (int) ($campaign->user_id ?? 0);

        if (!$conexao || !$template || !$lead || $userId <= 0) {
            $this->markAsFailed($item, 'Contexto inválido de conexão/template/lead.');
            $this->incrementCampaignCounter((int) $campaign->id, 'failed_count');
            $this->continueCampaign((int) $campaign->id);
            return;
        }

        $result = $templateSendService->sendToLead([
            'user_id' => $userId,
            'conexao' => $conexao,
            'template' => $template,
            'lead' => $lead,
            'template_variables' => $templateSendService->resolveBoundTemplateVariablesForLead(
                $template,
                $lead,
                $userId,
                (array) data_get($campaign->settings, 'template_variable_bindings', []),
                true,
                ' '
            )[0],
            'allow_empty_variables' => true,
            'empty_variable_fallback' => ' ',
        ]);

        $attempts = max(1, (int) $this->attempts());
        $nowUtc = Carbon::now('UTC');

        if (!$result['ok']) {
            $errorCode = (string) ($result['error_code'] ?? '');
            $message = (string) ($result['message'] ?? 'Falha ao enviar template em campanha.');

            if (in_array($errorCode, ['lead_phone_invalid', 'template_missing_variables'], true)) {
                $item->forceFill([
                    'status' => 'skipped',
                    'attempts' => $attempts,
                    'skipped_at' => $nowUtc,
                    'failed_at' => null,
                    'sent_at' => null,
                    'error_message' => Str::limit($message, 1900),
                    'resolved_variables' => !empty($result['resolved_variables']) ? $result['resolved_variables'] : null,
                    'rendered_message' => $result['rendered_message'] ?? null,
                ])->save();

                $this->incrementCampaignCounter((int) $campaign->id, 'skipped_count');
                $this->continueCampaign((int) $campaign->id);
                return;
            }

            $item->forceFill([
                'status' => 'failed',
                'attempts' => $attempts,
                'failed_at' => $nowUtc,
                'sent_at' => null,
                'skipped_at' => null,
                'error_message' => Str::limit($message, 1900),
                'resolved_variables' => !empty($result['resolved_variables']) ? $result['resolved_variables'] : null,
                'rendered_message' => $result['rendered_message'] ?? null,
                'meta_response' => is_array($result['response']) ? $result['response'] : null,
            ])->save();

            $this->incrementCampaignCounter((int) $campaign->id, 'failed_count');
            $this->continueCampaign((int) $campaign->id);
            return;
        }

        $response = is_array($result['response']) ? $result['response'] : [];
        $metaMessageId = trim((string) data_get($response, 'body.messages.0.id', ''));
        $resolvedVariables = is_array($result['resolved_variables'] ?? null)
            ? $result['resolved_variables']
            : [];

        $item->forceFill([
            'status' => 'sent',
            'attempts' => $attempts,
            'sent_at' => $nowUtc,
            'failed_at' => null,
            'skipped_at' => null,
            'meta_message_id' => $metaMessageId !== '' ? $metaMessageId : null,
            'error_message' => null,
            'resolved_variables' => !empty($resolvedVariables) ? $resolvedVariables : null,
            'rendered_message' => $result['rendered_message'] ?? null,
            'meta_response' => !empty($response) ? $response : null,
            'idempotency_key' => $item->idempotency_key ?: $this->buildItemIdempotencyKey((int) $campaign->id, (int) $item->cliente_lead_id),
        ])->save();

        $this->incrementCampaignCounter((int) $campaign->id, 'sent_count');

        $conversationWindowService->touchOutbound(
            (int) $lead->id,
            (int) $conexao->id,
            $nowUtc
        );

        try {
            $templateContextSyncService->sync([
                'conexao_id' => (int) $conexao->id,
                'cliente_lead_id' => (int) $lead->id,
                'template_id' => (int) $template->id,
                'template_variables' => $resolvedVariables,
                'assistant_context_instructions' => trim((string) data_get($campaign->settings, 'assistant_context_instructions', '')) ?: null,
                'meta_message_id' => $metaMessageId !== '' ? $metaMessageId : null,
                'sent_at' => $nowUtc->toIso8601String(),
            ]);
        } catch (\Throwable $exception) {
            Log::channel('process_job')->warning('Falha ao sincronizar contexto OpenAI no fluxo sequencial da campanha.', [
                'campaign_id' => (int) $campaign->id,
                'campaign_item_id' => (int) $item->id,
                'lead_id' => (int) $lead->id,
                'template_id' => (int) $template->id,
                'error' => $exception->getMessage(),
            ]);
        }

        $this->continueCampaign((int) $campaign->id);
    }

    public function failed(\Throwable $exception): void
    {
        $item = WhatsappCloudCampaignItem::query()->find($this->campaignItemId);
        if (!$item) {
            return;
        }

        if (in_array($item->status, ['sent', 'skipped', 'canceled'], true)) {
            $this->continueCampaign((int) $item->whatsapp_cloud_campaign_id);
            return;
        }

        $this->markAsFailed($item, $exception->getMessage());
        $this->incrementCampaignCounter((int) $item->whatsapp_cloud_campaign_id, 'failed_count');
        $this->continueCampaign((int) $item->whatsapp_cloud_campaign_id);

        Log::channel('process_job')->error('SendWhatsappCloudCampaignItemJob failed.', [
            'campaign_item_id' => $this->campaignItemId,
            'error' => $exception->getMessage(),
            'attempt' => $this->attempts(),
        ]);
    }

    private function markAsFailed(WhatsappCloudCampaignItem $item, string $message): void
    {
        $item->forceFill([
            'status' => 'failed',
            'attempts' => max((int) $item->attempts, (int) $this->attempts()),
            'failed_at' => Carbon::now('UTC'),
            'error_message' => Str::limit(trim($message) !== '' ? $message : 'Falha no envio do item da campanha.', 1900),
        ])->save();
    }

    private function markAsCanceled(WhatsappCloudCampaignItem $item): void
    {
        $item->forceFill([
            'status' => 'canceled',
            'error_message' => 'Item cancelado junto com a campanha.',
            'skipped_at' => Carbon::now('UTC'),
        ])->save();
    }

    private function incrementCampaignCounter(int $campaignId, string $column): void
    {
        if (!in_array($column, ['queued_count', 'sent_count', 'failed_count', 'skipped_count'], true)) {
            return;
        }

        DB::table('whatsapp_cloud_campaigns')
            ->where('id', $campaignId)
            ->increment($column);
    }

    private function finalizeCampaignIfDone(int $campaignId): void
    {
        $remaining = WhatsappCloudCampaignItem::query()
            ->where('whatsapp_cloud_campaign_id', $campaignId)
            ->whereIn('status', ['pending', 'queued'])
            ->count();

        if ($remaining > 0) {
            return;
        }

        $campaign = WhatsappCloudCampaign::query()->find($campaignId);
        if (!$campaign || in_array($campaign->status, ['canceled', 'completed', 'failed'], true)) {
            return;
        }

        $campaign->refresh();

        $newStatus = $campaign->sent_count > 0 ? 'completed' : 'failed';
        $campaign->forceFill([
            'status' => $newStatus,
            'finished_at' => Carbon::now('UTC'),
        ])->save();
    }

    private function continueCampaign(int $campaignId): void
    {
        if ($campaignId <= 0) {
            return;
        }

        $campaign = WhatsappCloudCampaign::query()->find($campaignId);
        if (!$campaign || in_array($campaign->status, ['canceled', 'completed', 'failed'], true)) {
            return;
        }

        $intervalSeconds = max(0, (int) data_get($campaign->settings, 'interval_seconds', 0));
        $this->queueNextPendingItem($campaignId, $intervalSeconds);
        $this->finalizeCampaignIfDone($campaignId);
    }

    private function queueNextPendingItem(int $campaignId, int $intervalSeconds = 0): bool
    {
        $hasQueuedItem = WhatsappCloudCampaignItem::query()
            ->where('whatsapp_cloud_campaign_id', $campaignId)
            ->where('status', 'queued')
            ->exists();

        if ($hasQueuedItem) {
            return true;
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $pending = WhatsappCloudCampaignItem::query()
                ->where('whatsapp_cloud_campaign_id', $campaignId)
                ->where('status', 'pending')
                ->orderBy('id')
                ->first(['id']);

            if (!$pending) {
                return false;
            }

            $queuedAt = Carbon::now('UTC');
            $updated = WhatsappCloudCampaignItem::query()
                ->whereKey((int) $pending->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'queued',
                    'queued_at' => $queuedAt,
                    'updated_at' => $queuedAt,
                ]);

            if ($updated !== 1) {
                continue;
            }

            $delaySeconds = max(0, (int) $intervalSeconds);
            $job = self::dispatch((int) $pending->id)->onQueue(self::queueName());
            if ($delaySeconds > 0) {
                $job->delay($queuedAt->copy()->addSeconds($delaySeconds));
            }

            $this->incrementCampaignCounter($campaignId, 'queued_count');
            return true;
        }

        return false;
    }

    private static function queueName(): string
    {
        return DispatchWhatsappCloudCampaignJob::QUEUE_NAME;
    }

    private function buildItemIdempotencyKey(int $campaignId, int $leadId): string
    {
        return 'wcc_item_' . hash('sha256', "{$campaignId}:{$leadId}");
    }
}
