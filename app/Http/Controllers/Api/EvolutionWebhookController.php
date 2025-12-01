<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Instance;
use App\Models\Chat; // <-- Precisamos deste modelo
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessWebhookJob;
use App\Services\ConversationsService;
use App\Jobs\ProcessarConversaJob;
use Illuminate\Support\Facades\Cache;
use App\Jobs\DebounceConversationJob;
use Illuminate\Support\Carbon;

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
            //CRIA UM NOVO REGISTRO NA TABELA CHAT CASO NÃO EXISTA
            $chat = new Chat();
            $chat->instance_id = $instance->id;
            
            $chat->user_id = $instance->user_id;
            $chat->assistant_id = $instance->default_assistant_id;

            $chat->contact = $contactNumber;
            $chat->bot_enabled = 1; //ATIVA O BOT POR PADRÃO
            $chat->save();
        }

        // ===================================================================
        // REGRA DE NEGÓCIO 2: VERIFICAR SE O BOT ESTÁ ATIVO
        // ===================================================================
        
        
        // Se o registro de chat ainda não existe, o bot está ativo por padrão.

        //VERIFICAR SE MENSAGEM VEIO DE GRUPO OU NÃO
        if (str_ends_with($remoteJid, '@g.us')) {
            Log::info("Webhook ignorado mensagem_de_grupo.");
            return response()->json(['status' => 'ignored', 'reason' => 'mensagem_de_grupo']);
        }

        // Validação inicial: se não tiver os dados mínimos, ignora.
        if (!$instanceName || !$remoteJid || is_null($fromMe)) {
            Log::info("Webhook ignorado (dados essenciais ausentes).", $request->all());
            // Retornamos 200 OK para o Evolution não tentar reenviar.
            return response()->json(['status' => 'ignored', 'reason' => 'missing_data']);
        }
        
        // ===================================================================
        // REGRA DE NEGÓCIO 1: IGNORAR MENSAGENS ENVIADAS PELA PRÓPRIA INSTÂNCIA
        // ===================================================================
        str($fromMe);
        Log::info("fromMe é ".str($fromMe));
        if ($fromMe === true) { //MENSAGEM DO PRÓPRIO ADM

            Log::info("Webhook ignorado (fromMe é true) para a instância {$instanceName}.");
            
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
            Log::info("Webhook ignorado (bot desativado) para o contato {$contactNumber} na instância {$instanceName}.");
            return response()->json(['status' => 'ignored', 'reason' => 'bot_disabled']);
        }

        // ===================================================================
        // VALIDAÇÃO DA INSTÂNCIA E BUSCA DO ASSISTENTE
        // ===================================================================
        // Busca a instância pelo nome, como você especificou
       

        if (!$instance) {
            Log::warning("Webhook recebido para uma instância desconhecida: {$instanceName}");
            return response()->json(['error' => 'Instance not found'], 404);
        }

        // Pega o ID do assistente vinculado a esta instância
        $assistantId = $instance->default_assistant_id;

        if (!$assistantId) {
            Log::warning("Instância {$instanceName} não possui um assistente vinculado. Mensagem ignorada.");
            return response()->json(['status' => 'ignored', 'reason' => 'no_assistant_linked']);
        }
        
   

        // ===================================================================
        // PONTO DE PARADA: SE CHEGAMOS ATÉ AQUI, A MENSAGEM DEVE SER PROCESSADA
        // ===================================================================
        
        $threadId = $chat->thread_id ?? null;

        Log::info("VALIDAÇÃO CONCLUÍDA: A mensagem do contato {$contactNumber} para a instância {$instanceName} será enviada para o Job.", [
            'assistant_id' => $assistantId,
            'thread_id' => $threadId,
            'message' => $messageText
        ]);

        // Despacha o Job para a fila, passando apenas os dados essenciais
        ProcessWebhookJob::dispatch(
            $instance,
            $contactNumber,
            $request->input('data'), 
        ); 

        // Resposta imediata de sucesso para o Evolution
        return response()->json(['status' => 'queued_for_processing']);
    }

    public function conversation(Request $request) 
    {

        if (str_contains($request->input('data.key.remoteJid'), '@lid')) {
            $remoteJid = $request->input('data.key.remoteJidAlt'); 
        }else{
            $remoteJid = $request->input('data.key.remoteJid'); 
        }

        //VERIFICAR SE MENSAGEM VEIO DE GRUPO OU NÃO
        if (str_ends_with($remoteJid, '@g.us')) { return true; }


        Log::info("instance:".$request->input('instance')." - Request: " . json_encode($request->input('data.key'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));


        //Log::info('🚨 Dados do Evolution:', $request->all());
        // 2. Extrair dados cruciais do JSON (com base na estrutura que você forneceu)
        $instanceName = $request->input('instance'); // 'instance'
         // 'data.key.remoteJid'
        
        //$sender = $request->input('sender') ?? '';
       

        $fromMe = $request->input('data.key.fromMe'); // 'data.key.fromMe'
        $messageText = $request->input('data.message.conversation'); // 'data.message.conversation'
        $data = $request->input('data'); // 'data'
        $contactNumber = $this->padronizarNumero(preg_replace('/[^0-9]/', '', $remoteJid));
        $instance = Instance::where('id', $instanceName)->first(); //BUSCAR INSTANCIA
        $eventId = $request->input('data.key.id') ?? $request->input('data.id') ?? null;
        $messageTimestamp = $request->input('data.messageTimestamp');
        
        if(isset($data['messageType']) AND $data['messageType']=='reactionMessage'){
            //Log::warning("reacao");
            return true;
        }

        if(!$contactNumber){
            Log::warning("⚠️ Número do contato não pôde ser determinado.");
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_contact_number']);
        }

        Log::info('conv.received', [
            'instance' => $instanceName,
            'contact' => $contactNumber,
            'from_me' => $fromMe,
            'message_type' => $data['messageType'] ?? 'text',
            'has_text' => !empty($messageText),
        ]);

        $dedupKeySeed = $eventId ?: hash('sha256', json_encode([
            $instanceName,
            $contactNumber,
            $messageTimestamp,
            $messageText,
            $data['messageType'] ?? null,
        ]));
        $dedupKey = "webhook:conv:{$instanceName}:{$dedupKeySeed}";
        if (!Cache::add($dedupKey, true, now()->addMinutes(10))) {
            Log::info('conv.duplicate_skip', [
                'instance' => $instanceName,
                'contact' => $contactNumber,
                'event_id' => $eventId,
                'message_timestamp' => $messageTimestamp,
            ]);
            return true;
        }

        if (!$instance or $instance->default_assistant_id===null) {
            Log::warning("⚠️ Instância {$instanceName} não encontrada ou sem assistente vinculado.");
            return response()->json(['status' => 'ignored', 'reason' => 'instance_not_found']);
        }
        

       
        // ===================================================================
        // LÓGICA DA TABELA CHATS
        // ===================================================================
        

        // Busca o registro de chat para este contato nesta instância
        $chat = Chat::where('instance_id', $instance->id)->where('contact', $contactNumber)->first();

        //CRIA UM NOVO REGISTRO NA TABELA CHAT CASO NÃO EXISTA
        if(!$chat){
            $chat = new Chat();
            $chat->instance_id = $instance->id;
            
            $chat->user_id = $instance->user_id;
            $chat->assistant_id = $instance->default_assistant_id;

            $chat->contact = $contactNumber;
            $chat->bot_enabled = 1; //ATIVA O BOT POR PADRÃO
            $chat->save();
        }
        
        // Se o registro de chat ainda não existe, o bot está ativo por padrão.

        //VERIFICAR SE MENSAGEM VEIO DE GRUPO OU NÃO
        if (str_ends_with($remoteJid, '@g.us')) { return true; }

        
        
        // ===================================================================
        // REGRA DE NEGÓCIO 1: IGNORAR MENSAGENS ENVIADAS PELA PRÓPRIA INSTÂNCIA
        // ===================================================================
        
        if ($fromMe === true) { //MENSAGEM DO PRÓPRIO ADM

            
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
        // REGRA DE NEGÓCIO 2: IGNORAR MENSAGENS com bot_enabled FALSE
        // ===================================================================
        if (isset($chat->bot_enabled) and !$chat->bot_enabled) {Log::info("return true"); return true;}

        //
       /*if ($contactNumber == '556295772922') {
            $timeout = 3;

            if (isset($chat->id)) {
                $key = $chat->id;
            } else {
                $key = $contactNumber;
            }

            // Log inicial de recebimento
            Log::info("📩 Mensagem recebida de {$contactNumber}: {$messageText}");

            // Recupera mensagens existentes no cache
            $mensagens = Cache::get($key, []);
            Log::info("🗂️ Mensagens atuais no cache ({$key}):", $mensagens);

            // Adiciona a nova mensagem
            $mensagens[] = $messageText;

            // Salva novamente com o novo timeout
            Cache::put($key, $mensagens, now()->addSeconds($timeout));
            Log::info("💾 Cache atualizado ({$key}) com timeout de {$timeout}s:", $mensagens);

            // Dispara o job com delay
            ProcessarConversaJob::dispatch($messageText, $contactNumber, $instanceName, $data, $key)->delay(now()->addSeconds($timeout));

            Log::info("🚀 Job agendado para {$contactNumber} com delay de {$timeout}s (chave {$key})");
            return true;
        }*/

        $instance = Instance::find($instanceName);
        if(!$instance){
            Log::warning("⚠️ Instância {$instanceName} não encontrada");
            return true;
        }else{
            Log::info("✅ Instância {$instanceName} encontrada");
        }

        // Se nao e texto (ex.: midia), processa imediatamente
        if (empty($messageText)) {
            Log::info('conv.media_immediate', [
                'instance' => $instanceName,
                'contact' => $contactNumber,
                'message_type' => $data['messageType'] ?? 'media',
            ]);
            ProcessarConversaJob::dispatch($messageText, $contactNumber, $instanceName, $data);
            return true;
        }

        // Tenta usar cache para debounce; se indisponivel, processa direto
        if (!$this->cacheDisponivel()) {
            Log::warning('conv.debounce_fallback_no_cache', [
                'instance' => $instanceName,
                'contact' => $contactNumber,
            ]);
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

        Log::info('conv.buffer_update', [
            'instance' => $instanceName,
            'contact' => $contactNumber,
            'messages' => count($buffer['messages']),
            'started_at' => $buffer['started_at'],
            'last_at' => $buffer['last_at'],
        ]);

// TTL curto para evitar vazar cache (120s)
        Cache::put($cacheKey, $buffer, now()->addSeconds(120));

        // Agenda job de debounce com delay de 5s e teto de 40s
        DebounceConversationJob::dispatch($cacheKey, $contactNumber, $instanceName, 5, 40)
            ->delay(now()->addSeconds(5));

        Log::info('conv.debounce_scheduled', [
            'instance' => $instanceName,
            'contact' => $contactNumber,
            'messages' => count($buffer['messages']),
        ]);

        
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
            Log::warning('Cache indisponivel para debounce, processando direto.', ['erro' => $e->getMessage()]);
            return false;
        }
    }

}
