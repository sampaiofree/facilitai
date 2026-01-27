<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Instance;
use App\Models\Chat; // <-- Precisamos deste modelo
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\WebhookRequest;
use App\Jobs\SendPresenceJob;

class EvolutionWebhookController extends Controller
{
    public function handle(Request $request) 
    {        
        // 2. Extrair dados cruciais do JSON (com base na estrutura que vocÃª forneceu)
        $instanceName = $request->input('instance'); // 'instance'
        $remoteJid = $request->input('data.key.remoteJid'); // 'data.key.remoteJid'
        $fromMe = $request->input('data.key.fromMe'); // 'data.key.fromMe'
        $messageText = $request->input('data.message.conversation'); // 'data.message.conversation'
        $contactNumber = preg_replace('/[^0-9]/', '', $remoteJid);// Extrai apenas os nÃºmeros do JID do contato

        $contactNumber = $this->padronizarNumero($contactNumber);
        $instance = Instance::where('id', $instanceName)->first(); //BUSCAR INSTANCIA
        
        // Se nÃ£o achar a instÃ¢ncia, jÃ¡ retorna
        if (!$instance) {
            return response()->json(['error' => 'Instance not found'], 404);
        }
        // ===================================================================
        // LÃ“GICA DA TABELA CHATS
        // ===================================================================
        

        // Busca o registro de chat para este contato nesta instÃ¢ncia
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
        // REGRA DE NEGÃ“CIO 2: VERIFICAR SE O BOT ESTÃ ATIVO
        // ===================================================================
        
        
        // Se o registro de chat ainda nÃ£o existe, o bot estÃ¡ ativo por padrÃ£o.

        //VERIFICAR SE MENSAGEM VEIO DE GRUPO OU NÃƒO
        if (str_ends_with($remoteJid, '@g.us')) {
            
            return response()->json(['status' => 'ignored', 'reason' => 'mensagem_de_grupo']);
        }

        // ValidaÃ§Ã£o inicial: se nÃ£o tiver os dados mÃ­nimos, ignora.
        if (!$instanceName || !$remoteJid || is_null($fromMe)) {
            
            // Retornamos 200 OK para o Evolution nÃ£o tentar reenviar.
            return response()->json(['status' => 'ignored', 'reason' => 'missing_data']);
        }
        
        // ===================================================================
        // REGRA DE NEGÃ“CIO 1: IGNORAR MENSAGENS ENVIADAS PELA PRÃ“PRIA INSTÃ‚NCIA
        // ===================================================================
        str($fromMe);
        
        if ($fromMe === true) { //MENSAGEM DO PRÃ“PRIO ADM

            
            
            //FALTA REGISTRAR BOT COMO FALSO CASO EXISTA UM REGISTRO
            if(isset($chat->bot_enabled) AND $chat->bot_enabled){$chat->bot_enabled = 0;$chat->save();}
            

            //VERIFICAR SE NA MENSAGEM DO ADM TEM A PALAVRA CHAVE PARA ATIVAR NOVAMENTE O BOT
            if (strpos($messageText, '#') !== false) {
                $chat->bot_enabled = 1;
                $chat->save();
            }


            return response()->json(['status' => 'ignored', 'reason' => 'from_me']);
        }

        // Se um chat existe E o bot_enabled Ã© false, ignora.
        if (isset($chat->bot_enabled) and !$chat->bot_enabled) {
            
            return response()->json(['status' => 'ignored', 'reason' => 'bot_disabled']);
        }

        // ===================================================================
        // VALIDAÃ‡ÃƒO DA INSTÃ‚NCIA E BUSCA DO ASSISTENTE
        // ===================================================================
        // Busca a instÃ¢ncia pelo nome, como vocÃª especificou
       

        if (!$instance) {
            
            return response()->json(['error' => 'Instance not found'], 404);
        }

        // Pega o ID do assistente vinculado a esta instÃ¢ncia
        $assistantId = $instance->default_assistant_id;

        if (!$assistantId) {
            
            return response()->json(['status' => 'ignored', 'reason' => 'no_assistant_linked']);
        }
        
   

        // ===================================================================
        // PONTO DE PARADA: SE CHEGAMOS ATÃ‰ AQUI, A MENSAGEM DEVE SER PROCESSADA
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

        //NUMERO INVÃLIDO
        if(!$contactNumber){
            
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_contact_number']);
        }

        // ===================================================================
        // DETECÃ‡ÃƒO DE DUPLICIDADE VIA CACHE

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
        // PROCESSAMENTO DA MENSAGEM (fluxo antigo removido)
        // Mantemos apenas o indicador de digitando.
        //SendPresenceJob::dispatch($instanceName, $contactNumber, 'composing');

        return true;
 
    }


    function padronizarNumero($numero) {
    // Remove espaÃ§os, traÃ§os, parÃªnteses e tudo que nÃ£o for nÃºmero
    $numero = preg_replace('/\D/', '', $numero);

    // Se tiver 13 dÃ­gitos (ex: 55 + 62 + 995772922), remove o 9 se for celular antigo
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

