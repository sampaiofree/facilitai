<?php

namespace App\Jobs;

use App\Services\EvolutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPresenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $instance;
    public string $numero;
    public ?string $presence;

    /**
     * @param string $instance
     * @param string $numero
     * @param string|null $presence composing|recording|null
     */
    public function __construct(string $instance, string $numero, ?string $presence = null)
    {
        $this->instance = $instance;
        $this->numero = $numero;
        $this->presence = $presence;
    }

    public function handle(EvolutionService $evolution): void
    {
        try {
            $evolution->enviarPresenca($this->instance, $this->numero, $this->presence);
        } catch (\Throwable $e) {
            Log::warning('SendPresenceJob failed', [
                'instance' => $this->instance,
                'numero' => $this->numero,
                'presence' => $this->presence,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
