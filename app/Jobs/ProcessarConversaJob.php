<?php

namespace App\Jobs;

use App\Services\ConversationsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SendPresenceJob;


class ProcessarConversaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 240;
    public int $backoff = 30;

    protected $messageText;
    protected $contactNumber;
    protected $instanceName;
    protected $data;

    public function __construct($messageText, $contactNumber, $instanceName, $data)
    {
        $this->messageText = $messageText;
        $this->contactNumber = $contactNumber;
        $this->instanceName = $instanceName;
        $this->data = $data;
    }

    public function handle()
    {

        SendPresenceJob::dispatch($this->instanceName, $this->contactNumber, 'composing');

        

        $open = new ConversationsService(
            $this->messageText,
            $this->contactNumber,
            $this->instanceName
        );

        if(!$open->ready) {
            
            return; // ✅ processado com sucesso, sem falha
        }
        
        $open->evolution($this->data);
    }
}
