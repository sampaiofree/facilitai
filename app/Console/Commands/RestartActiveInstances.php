<?php

namespace App\Console\Commands;

use App\Models\Instance;
use App\Services\EvolutionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RestartActiveInstances extends Command
{
    protected $signature = 'instances:restart-active {--chunk=100} {--sleep-ms=0}';
    protected $description = 'Restart all active instances via Evolution API';

    public function handle(EvolutionService $service): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $sleepMs = max(0, (int) $this->option('sleep-ms'));
        $total = 0;
        $failed = 0;
        $missing = 0;

        

        Instance::query()
            ->where('status', 'active')
            ->select('id')
            ->chunkById($chunk, function ($instances) use ($service, $sleepMs, &$total, &$failed, &$missing) {
                foreach ($instances as $instance) {
                    $total++;

                    try {
                        $result = $service->reiniciarInstancia((string) $instance->id);

                        if (!is_array($result) || !($result['ok'] ?? false)) {
                            if (is_array($result) && ($result['error'] ?? null) === 'not_found') {
                                $missing++;
                                $instance->update(['status' => 'error']);
                                Log::warning('instances:restart-active instance not found', [
                                    'instance_id' => $instance->id,
                                ]);
                            } else {
                                $failed++;
                            }
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::error('instances:restart-active exception', [
                            'instance_id' => $instance->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    if ($sleepMs > 0) {
                        usleep($sleepMs * 1000);
                    }
                }
            });

        

        $this->info("Restart finished. total={$total} failed={$failed} missing={$missing}");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
