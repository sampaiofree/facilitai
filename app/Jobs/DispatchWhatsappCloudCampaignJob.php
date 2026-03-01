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

    public int $tries = 3;
    public int $timeout = 180;

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
        $intervalSeconds = max(0, (int) data_get($campaign->settings, 'interval_seconds', 2));

        if (!$campaign->started_at) {
            $campaign->forceFill([
                'status' => 'running',
                'started_at' => $nowUtc,
                'last_error' => null,
            ])->save();
        } elseif ($campaign->status !== 'running') {
            $campaign->forceFill(['status' => 'running'])->save();
        }

        $queuedCount = 0;
        $pendingItems = WhatsappCloudCampaignItem::query()
            ->where('whatsapp_cloud_campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->get(['id']);

        foreach ($pendingItems as $index => $item) {
            $queuedAt = Carbon::now('UTC');
            $updated = WhatsappCloudCampaignItem::query()
                ->whereKey((int) $item->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'queued',
                    'queued_at' => $queuedAt,
                    'updated_at' => $queuedAt,
                ]);

            if ($updated !== 1) {
                continue;
            }

            $delaySeconds = $intervalSeconds > 0 ? ($index * $intervalSeconds) : 0;
            $job = SendWhatsappCloudCampaignItemJob::dispatch((int) $item->id)
                ->onQueue('processarconversa');

            if ($delaySeconds > 0) {
                $job->delay($nowUtc->copy()->addSeconds($delaySeconds));
            }

            $queuedCount++;
        }

        if ($queuedCount > 0) {
            $campaign->increment('queued_count', $queuedCount);
            return;
        }

        $remaining = WhatsappCloudCampaignItem::query()
            ->where('whatsapp_cloud_campaign_id', $campaign->id)
            ->whereIn('status', ['pending', 'queued'])
            ->count();

        if ($remaining === 0) {
            $campaign->refresh();
            $campaign->forceFill([
                'status' => $campaign->sent_count > 0 ? 'completed' : 'failed',
                'finished_at' => Carbon::now('UTC'),
            ])->save();
        }
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
}

