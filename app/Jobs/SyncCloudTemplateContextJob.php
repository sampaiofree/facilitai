<?php

namespace App\Jobs;

use App\Services\WhatsappCloudTemplateContextSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCloudTemplateContextJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public int $timeout = 180;

    /**
     * @param array{
     *   conexao_id:int,
     *   cliente_lead_id:int,
     *   template_id:int,
     *   template_variables?:array<string,mixed>,
     *   assistant_context_instructions?:string|null,
     *   meta_message_id?:string|null,
     *   sent_at?:string|null
     * } $payload
     */
    public function __construct(private readonly array $payload)
    {
    }

    public function handle(WhatsappCloudTemplateContextSyncService $service): void
    {
        $service->sync($this->payload);
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function uniqueId(): string
    {
        $metaMessageId = trim((string) ($this->payload['meta_message_id'] ?? ''));
        if ($metaMessageId !== '') {
            return 'sync-cloud-template-context:meta:' . $metaMessageId;
        }

        return 'sync-cloud-template-context:hash:' . hash(
            'sha256',
            (string) json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    public function uniqueFor(): int
    {
        return 3600;
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('process_job')->error('SyncCloudTemplateContextJob failed.', [
            'error' => $exception->getMessage(),
            'payload' => $this->payload,
        ]);
    }
}
