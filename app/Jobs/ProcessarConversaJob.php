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

    protected $messageText;
    protected $contactNumber;
    protected $instanceName;
    protected $data;
    protected $uazapi;

    public function __construct($messageText, $contactNumber, $instanceName, $data, $uazapi = null)
    {
        $this->messageText = $messageText;
        $this->contactNumber = $contactNumber;
        $this->instanceName = $instanceName;
        $this->data = $data;
        $this->uazapi = $uazapi;
    }

    public function handle()
    {

        //API EVOLUTION   
        if(!$this->uazapi){
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
        }else{
            //API UAZAPI
        }
        
    }
}
