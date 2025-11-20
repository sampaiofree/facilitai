<?php

namespace App\Console\Commands;

use App\Models\LeadEmpresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\ConversationsService;
use App\Models\Instance;
use App\Models\Chat;
use App\Models\Lead;
use Carbon\Carbon;

class ContatarEmpresas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contatar:empresas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inicia o processo agendado de contato com novas empresas via WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
         $chat = Chat::where('assistant_id', 4)
        //->where('updated_at', '<=', Carbon::now()->subDay()) //Exclui as ultimas 24horas //->where('updated_at', '<', Carbon::today()) // exclui hoje
        ->where('bot_enabled', '1')
        ->orderBy('updated_at', 'asc')
        ->first();
            
        $mensagem = "[#Notificação do sistema: Informe o cliente que antecipamos a blackfriday. O cliente pode escolher qualquer curso com 80% de desconto - alguns cursos podem sair por menos de R$40. Depois que o cliente escolher o curso envio o link do checkout com a Query string offDiscount=80OFF]";


        $op = new ConversationsService($mensagem, $chat->contact, '177');
        $op->enviarMSG();
    
        return Command::SUCCESS;
    }
}
