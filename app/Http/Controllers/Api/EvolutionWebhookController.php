<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Instance;
use App\Models\Chat; // <-- Precisamos deste modelo
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ConversationsService;
use App\Jobs\ProcessarConversaJob;
use Illuminate\Support\Facades\Cache;
use App\Jobs\DebounceConversationJob;
use Illuminate\Support\Carbon;
use App\Models\WebhookRequest;
use App\Services\EvolutionService;
use App\Jobs\SendPresenceJob;

class EvolutionWebhookController extends Controller
{
    public function handle(Request $request) 
    {        
        // 2. Extrair dados cruciais do JSON (com base na estrutura que você forneceu)
        $instanceName = $request->input('instance'); // 'instance'
        $remoteJid = $request->input('data.key.remoteJid'); // 'data.key.remoteJid'
        $fromMe = $request->input('data.key.fromMe'); // 'data.key.fromMe'
        $messageText = $request->input('data.message.conversation'); // 'data.message.conversation'
        $contactNumber = preg_replace('/[^0-9]/', '', $remoteJid);// Extrai apenas os números do JID do contato

        $contactNumber = $this->padronizarNumero($contactNumber);
        $instance = Instance::where('id', $instanceName)->first(); //BUSCAR INSTANCIA
        
        // Se não achar a instância, já retorna
        if (!$instance) {
            return response()->json(['error' => 'Instance not found'], 404);
        }
        // ===================================================================
        // LÓGICA DA TABELA CHATS
        // ===================================================================
        

        // Busca o registro de chat para este contato nesta instância
        $chat = Chat::where('instance_id', $instance->id)
                    ->where('contact', $contactNumber)
                    ->first();

        if(!$chat){
            // CRIA/ATUALIZA DE FORMA ATOMICA PARA EVITAR DUPLICIDADE
            $now = now();
            Chat::upsert(
                [[
                    'instance_id' => $instance->id,
                    'contact' => $contactNumber,
                    'user_id' => $instance->user_id,
                    'assistant_id' => $instance->default_assistant_id,
                    'bot_enabled' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['instance_id', 'contact'],
                ['user_id', 'assistant_id', 'bot_enabled', 'updated_at']
            );

            $chat = Chat::where('instance_id', $instance->id)
                ->where('contact', $contactNumber)
                ->first();
        }

        // ===================================================================
        // REGRA DE NEGÓCIO 2: VERIFICAR SE O BOT ESTÁ ATIVO
        // ===================================================================
        
        
        // Se o registro de chat ainda não existe, o bot está ativo por padrão.

        //VERIFICAR SE MENSAGEM VEIO DE GRUPO OU NÃO
        if (str_ends_with($remoteJid, '@g.us')) {
            
            return response()->json(['status' => 'ignored', 'reason' => 'mensagem_de_grupo']);
        }

        // Validação inicial: se não tiver os dados mínimos, ignora.
        if (!$instanceName || !$remoteJid || is_null($fromMe)) {
            
            // Retornamos 200 OK para o Evolution não tentar reenviar.
            return response()->json(['status' => 'ignored', 'reason' => 'missing_data']);
        }
        
        // ===================================================================
        // REGRA DE NEGÓCIO 1: IGNORAR MENSAGENS ENVIADAS PELA PRÓPRIA INSTÂNCIA
        // ===================================================================
        str($fromMe);
        
        if ($fromMe === true) { //MENSAGEM DO PRÓPRIO ADM

            
            
            //FALTA REGISTRAR BOT COMO FALSO CASO EXISTA UM REGISTRO
            if(isset($chat->bot_enabled) AND $chat->bot_enabled){$chat->bot_enabled = 0;$chat->save();}
            

            //VERIFICAR SE NA MENSAGEM DO ADM TEM A PALAVRA CHAVE PARA ATIVAR NOVAMENTE O BOT
            if (strpos($messageText, '#') !== false) {
                $chat->bot_enabled = 1;
                $chat->save();
            }


            return response()->json(['status' => 'ignored', 'reason' => 'from_me']);
        }

        // Se um chat existe E o bot_enabled é false, ignora.
        if (isset($chat->bot_enabled) and !$chat->bot_enabled) {
            
            return response()->json(['status' => 'ignored', 'reason' => 'bot_disabled']);
        }

        // ===================================================================
        // VALIDAÇÃO DA INSTÂNCIA E BUSCA DO ASSISTENTE
        // ===================================================================
        // Busca a instância pelo nome, como você especificou
       

        if (!$instance) {
            
            return response()->json(['error' => 'Instance not found'], 404);
        }

        // Pega o ID do assistente vinculado a esta instância
        $assistantId = $instance->default_assistant_id;

        if (!$assistantId) {
            
            return response()->json(['status' => 'ignored', 'reason' => 'no_assistant_linked']);
        }
        
   

        // ===================================================================
        // PONTO DE PARADA: SE CHEGAMOS ATÉ AQUI, A MENSAGEM DEVE SER PROCESSADA
        // ===================================================================
        
        $threadId = $chat->thread_id ?? null;

        

        // Fluxo antigo desativado (ProcessWebhookJob removido).
        return response()->json(['status' => 'ignored', 'reason' => 'deprecated_flow']);
    }

    
    public function conversation(Request $request) 
    {
        $data = $request->input('data', []);
        $instanceName = $request->input('instance');

        $remoteJidInput = (string) $request->input('data.key.remoteJid');
        if (str_contains($remoteJidInput, '@lid')) {
            $remoteJid = $request->input('data.key.remoteJidAlt');
        } else {
            $remoteJid = $remoteJidInput;
        }

        $contactNumber = $this->padronizarNumero(preg_replace('/[^0-9]/', '', (string) $remoteJid));
        $fromMe = $request->input('data.key.fromMe'); // 'data.key.fromMe'
        $messageText = $request->input('data.message.conversation'); // 'data.message.conversation'
        $eventId = $request->input('data.key.id') ?? $request->input('data.id') ?? null;
        $messageTimestamp = $request->input('data.messageTimestamp');
        $messageType = $data['messageType'] ?? null;

        /*try {
            WebhookRequest::create([
                'instance_id' => $instanceName,
                'remote_jid' => $remoteJid,
                'contact' => $contactNumber ?: null,
                'from_me' => $fromMe,
                'message_type' => $messageType,
                'event_id' => $eventId,
                'message_timestamp' => $messageTimestamp,
                'message_text' => $messageText,
                'payload' => $request->all(),
            ]);
        } catch (\Throwable $e) {
            
        }*/

        //VERIFICAR SE MENSAGEM VEIO DE GRUPO OU NAO
        if (str_ends_with((string) $remoteJid, '@g.us')) { return true; }

        
       
        //BUSCAR INSTANCIA
        $instance = Instance::where('id', $instanceName)->first(); 
        if(isset($data['messageType']) AND $data['messageType']=='reactionMessage'){
            
            return true;
        }

        //NUMERO INVÁLIDO
        if(!$contactNumber){
            
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_contact_number']);
        }

        // ===================================================================
        // DETECÇÃO DE DUPLICIDADE VIA CACHE

        $dedupKeySeed = $eventId ?: hash('sha256', json_encode([
            $instanceName,
            $contactNumber,
            $messageTimestamp,
            $messageText,
            $data['messageType'] ?? null,
        ]));
        $dedupKey = "webhook:conv:{$instanceName}:{$dedupKeySeed}";
        if (!Cache::add($dedupKey, true, now()->addMinutes(10))) {
            
            return true;
        }

        if (!$instance or $instance->default_assistant_id===null) {
            
            return response()->json(['status' => 'ignored', 'reason' => 'instance_not_found']);
        }
        

       
        // ===================================================================
        // LOGICA DA TABELA CHATS
        // ===================================================================
        

        // Busca o registro de chat para este contato nesta instancia
        $chat = Chat::where('instance_id', $instance->id)->where('contact', $contactNumber)->first();

        //CRIA UM NOVO REGISTRO NA TABELA CHAT CASO NAO EXISTA
        if(!$chat){
            $now = now();
            Chat::upsert(
                [[
                    'instance_id' => $instance->id,
                    'contact' => $contactNumber,
                    'user_id' => $instance->user_id,
                    'assistant_id' => $instance->default_assistant_id,
                    'bot_enabled' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['instance_id', 'contact'],
                ['user_id', 'assistant_id', 'bot_enabled', 'updated_at']
            );

            $chat = Chat::where('instance_id', $instance->id)
                ->where('contact', $contactNumber)
                ->first();
        }
        
        // Se o registro de chat ainda nao existe, o bot esta ativo por padrao.

        //VERIFICAR SE MENSAGEM VEIO DE GRUPO OU NAO
        if (str_ends_with($remoteJid, '@g.us')) { return true; }

        
        // ===================================================================
        // REGRA DE NEGOCIO 1: IGNORAR MENSAGENS ENVIADAS PELA PROPRIA INSTANCIA
        // ===================================================================
        
        if ($fromMe === true) { //MENSAGEM DO PROPRIO ADM

            
            // REGISTRAR BOT COMO FALSO CASO EXISTA UM REGISTRO
            if(isset($chat->bot_enabled) AND $chat->bot_enabled){$chat->bot_enabled = 0;$chat->save();}
            

            //VERIFICAR SE NA MENSAGEM DO ADM TEM A PALAVRA CHAVE PARA ATIVAR NOVAMENTE O BOT
            if (strpos($messageText, '#') !== false) {
                if(isset($chat->bot_enabled)){
                    $chat->bot_enabled = 1;
                    $chat->save();
                }
                
            }

            return true;
        }

        // ===================================================================
        // REGRA DE NEGOCIO 2: IGNORAR MENSAGENS com bot_enabled FALSE
        // ===================================================================
        if (isset($chat->bot_enabled) and !$chat->bot_enabled) { return true;}

        // ===================================================================
        // PROCESSAMENTO DA MENSAGEM COM DEBOUNCE

        //DISPLAY DE DIGITANDO (assíncrono)
        SendPresenceJob::dispatch($instanceName, $contactNumber, 'composing');

        // Se nao é texto (ex.: midia), processa imediatamente
        if (empty($messageText)) {
            
            ProcessarConversaJob::dispatch($messageText, $contactNumber, $instanceName, $data);
            return true;
        }

        // Tenta usar cache para debounce; se indisponivel, processa direto
        if (!$this->cacheDisponivel()) {
            
            ProcessarConversaJob::dispatch($messageText, $contactNumber, $instanceName, $data);
            return true;
        }
        $cacheKey = "conv_buffer:{$instanceName}:{$contactNumber}";
        $buffer = Cache::get($cacheKey, []);
        $agora = Carbon::now()->timestamp;

        if (empty($buffer)) {
            $buffer = [
                'started_at' => $agora,
                'last_at' => $agora,
                'messages' => [$messageText],
                'data' => $data,
            ];
        } else {
            $buffer['last_at'] = $agora;
            $buffer['messages'][] = $messageText;
            $buffer['data'] = $data; // mantem dados da ultima mensagem
        }

        

// TTL curto para evitar vazar cache (120s)
        Cache::put($cacheKey, $buffer, now()->addSeconds(120));

        // Agenda job de debounce com delay de 5s e teto de 40s
        DebounceConversationJob::dispatch($cacheKey, $contactNumber, $instanceName, 7, 40)
            ->delay(now()->addSeconds(5));

        

        
        /*
        //ABILITE ISSO AQUI EM CASO DE ERRO NO JOB
        $open = new ConversationsService(
            $messageText,
            $contactNumber,
            $instanceName
        );
        
        $open->evolution($data);*/
        
        return true;
 
    }


    function padronizarNumero($numero) {
    // Remove espaços, traços, parênteses e tudo que não for número
    $numero = preg_replace('/\D/', '', $numero);

    // Se tiver 13 dígitos (ex: 55 + 62 + 995772922), remove o 9 se for celular antigo
    if (strlen($numero) === 13 && substr($numero, 4, 1) === '9') {
        // Remove o 9 extra
        $numero = substr($numero, 0, 4) . substr($numero, 5);
    }

    return $numero;
}

    private function cacheDisponivel(): bool
    {
        try {
            $key = 'conv_cache_test_' . uniqid();
            Cache::put($key, 1, 5);
            Cache::forget($key);
            return true;
        } catch (\Throwable $e) {
            
            return false;
        }
    }

}
