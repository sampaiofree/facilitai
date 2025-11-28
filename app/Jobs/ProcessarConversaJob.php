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


class ProcessarConversaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        Log::info('processar_conversa.start', [
            'instance' => $this->instanceName,
            'contact' => $this->contactNumber,
            'chars' => strlen((string)$this->messageText),
            'preview' => mb_substr((string)$this->messageText, 0, 200),
        ]);

        $open = new ConversationsService(
            $this->messageText,
            $this->contactNumber,
            $this->instanceName
        );

        if(!$open->ready) {
            Log::info('Job finalizado sem erro (soft-abort).');
            return; // ✅ processado com sucesso, sem falha
        }
        
        $open->evolution($this->data);
    }
}
