<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ProcessarConversaJob;

class DebounceConversationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    protected string $cacheKey;
    protected string $contactNumber;
    protected string $instanceName;
    protected int $debounceSeconds;
    protected int $maxWaitSeconds;

    public function __construct(string $cacheKey, string $contactNumber, string $instanceName, int $debounceSeconds = 5, int $maxWaitSeconds = 40)
    {
        $this->cacheKey = $cacheKey;
        $this->contactNumber = $contactNumber;
        $this->instanceName = $instanceName;
        $this->debounceSeconds = $debounceSeconds;
        $this->maxWaitSeconds = $maxWaitSeconds;
    }

    public function handle(): void
    {
        $buffer = Cache::get($this->cacheKey);
        if (empty($buffer) || empty($buffer['messages'])) {
            
            return;
        }

        $now = Carbon::now();
        $lastAt = Carbon::createFromTimestamp($buffer['last_at'] ?? $now->timestamp);
        $startedAt = Carbon::createFromTimestamp($buffer['started_at'] ?? $now->timestamp);

        // Ainda dentro da janela de debounce e dentro do limite total
        if ($lastAt->gt($now->subSeconds($this->debounceSeconds)) && $startedAt->gt($now->subSeconds($this->maxWaitSeconds))) {
            // Reagenda mais um ciclo de debounce
            
            self::dispatch($this->cacheKey, $this->contactNumber, $this->instanceName, $this->debounceSeconds, $this->maxWaitSeconds)
                ->delay(now()->addSeconds($this->debounceSeconds));
            return;
        }

        // Finaliza: concatena e dispara processamento
        $combined = implode("\n", $buffer['messages']);
        $data = $buffer['data'] ?? [];

        Cache::forget($this->cacheKey);

        

        ProcessarConversaJob::dispatch($combined, $this->contactNumber, $this->instanceName, $data);
    }
}
