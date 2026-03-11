<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Backward-compatible wrapper for debounce ticks queued by older deployments.
 */
class ProcessDebounceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;
    public string $queue = 'processarconversa';

    public $conexaoId = 0;
    public $clienteLeadId = null;
    public $payload = [];
    public $cacheKey = null;
    public $isMedia = false;
    public $debounceSeconds = 5;
    public $maxWaitSeconds = 25;

    public function __construct(
        int $conexaoId = 0,
        ?int $clienteLeadId = null,
        array $payload = [],
        ?string $cacheKey = null,
        bool $isMedia = false,
        int $debounceSeconds = 5,
        int $maxWaitSeconds = 25
    ) {
        $this->conexaoId = $conexaoId;
        $this->clienteLeadId = $clienteLeadId;
        $this->payload = $payload;
        $this->cacheKey = $cacheKey;
        $this->isMedia = $isMedia;
        $this->debounceSeconds = $debounceSeconds;
        $this->maxWaitSeconds = $maxWaitSeconds;
    }

    public function handle(): void
    {
        $conexaoId = (int) ($this->conexaoId ?? 0);
        $cacheKey = trim((string) ($this->cacheKey ?? ''));

        if ($conexaoId <= 0 || $cacheKey === '') {
            Log::channel('process_job')->warning('ProcessDebounceJob legado ignorado por payload inválido.', [
                'conexao_id' => $conexaoId,
                'cache_key' => $cacheKey,
            ]);
            return;
        }

        $job = new ProcessIncomingMessageJob(
            $conexaoId,
            $this->clienteLeadId !== null ? (int) $this->clienteLeadId : null,
            is_array($this->payload) ? $this->payload : [],
            $cacheKey,
            (bool) $this->isMedia,
            max(1, (int) $this->debounceSeconds),
            max(1, (int) $this->maxWaitSeconds)
        );

        $job->handleDebounce();
    }
}

