<?php

namespace App\Jobs;

use App\Models\WhatsappCloudCampaign;
use App\Models\WhatsappCloudCampaignItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class DispatchWhatsappCloudCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const QUEUE_NAME = 'whatsapp-cloud-campaign';

    public int $tries = 3;
    public int $timeout = 180;
    public string $queue = self::QUEUE_NAME;

    public function __construct(private readonly int $campaignId)
    {
    }

    public function handle(): void
    {
        $campaign = WhatsappCloudCampaign::query()->find($this->campaignId);
        if (!$campaign) {
            return;
        }

        if (in_array($campaign->status, ['canceled', 'completed', 'failed'], true)) {
            return;
        }

        $nowUtc = Carbon::now('UTC');

        if (!$campaign->started_at) {
            $campaign->forceFill([
                'status' => 'running',
                'started_at' => $nowUtc,
                'last_error' => null,
            ])->save();
        } elseif ($campaign->status !== 'running') {
            $campaign->forceFill(['status' => 'running'])->save();
        }

        if ($this->queueNextPendingItem((int) $campaign->id, 0)) {
            return;
        }
        $this->finalizeCampaignIfDone((int) $campaign->id);
    }

    public function failed(\Throwable $exception): void
    {
        $campaign = WhatsappCloudCampaign::query()->find($this->campaignId);
        if (!$campaign) {
            return;
        }

        $campaign->forceFill([
            'status' => 'failed',
            'last_error' => $exception->getMessage(),
            'finished_at' => Carbon::now('UTC'),
        ])->save();

        Log::channel('process_job')->error('DispatchWhatsappCloudCampaignJob failed.', [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function queueNextPendingItem(int $campaignId, int $intervalSeconds = 0): bool
    {
        if ($campaignId <= 0) {
            return false;
        }

        $campaign = WhatsappCloudCampaign::query()->find($campaignId);
        if (!$campaign || in_array($campaign->status, ['canceled', 'completed', 'failed'], true)) {
            return false;
        }

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
            $job = SendWhatsappCloudCampaignItemJob::dispatch((int) $pending->id)
                ->onQueue(self::QUEUE_NAME);

            if ($delaySeconds > 0) {
                $job->delay($queuedAt->copy()->addSeconds($delaySeconds));
            }

            $campaign->increment('queued_count');
            return true;
        }

        return false;
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
        $campaign->forceFill([
            'status' => $campaign->sent_count > 0 ? 'completed' : 'failed',
            'finished_at' => Carbon::now('UTC'),
        ])->save();
    }
}
