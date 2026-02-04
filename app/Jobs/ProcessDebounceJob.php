<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessDebounceJob extends ProcessIncomingMessageJob implements ShouldQueue
{
    public function handle(): void
    {
        $this->handleDebounce();
    }
}
