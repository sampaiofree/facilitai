<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; 
use App\Models\Instance;
use App\Models\ProxyIpBan;
use App\Models\Credential;
use App\Services\OpenAIService;
use App\Services\WebshareService; 
use App\Services\EvolutionService;
use App\Models\Payment;
use App\Models\Chat;
use App\Models\Agenda;
use Illuminate\Support\Facades\Response;



class InstanceController extends Controller
{
    /*public function index()
    {
        $instances = Auth::user()->instances()->get();

        foreach ($instances as $instance) {
            // Chama nosso novo método privado para obter o status
            $statusData = $this->fetchInstanceStatus($instance);
            // Anexa o estado ('open', 'error', etc.) ao objeto da instância
            $instance->connection_state = $statusData['state'] ?? 'error';
        }

        return view('instances.index', compact('instances'));
    }*/

    /*public function dashboardPublica($id, Request $request) // <-- Adiciona Request para pegar os filtros
    {
        $instance = Instance::findOrFail($id); // Usar findOrFail para erro 404 automático
        $sessionKey = 'dashboard_unlocked_' . $instance->id;

        // ... (sua lógica de status da conexão) ...
        $statusData = $this->fetchInstanceStatus($instance);
        $instance->connection_state = ($statusData['state'] ?? 'error') === 'open';

        if($instance->connection_state){
            // ETAPA DE VERIFICAÇÃO (POST)
            // Se o usuário está enviando o formulário de login...
            if ($request->isMethod('post')) {
                if ($request->input('instance_name') === $instance->name) {
                    // Nome correto: armazena a permissão na sessão e redireciona
                    $request->session()->put($sessionKey, true);
                    return redirect()->route('public.dashboard', $instance->id);
                } else {
                    // Nome incorreto: volta para o login com uma mensagem de erro
                    return back()->with('error', 'O nome da conexão está incorreto.');
                }
            }

            // ETAPA DE EXIBIÇÃO (GET)
            // Se não houver permissão na sessão...
            if (!$request->session()->has($sessionKey)) {
                // ...mostra a tela de login.
                return view('instances.dashboad_login', compact('instance'));
            }
        }
        
        

        // Pega as datas do filtro da URL (ex: /dash/1?start_date=2025-08-01)
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Usa nosso novo método para buscar as métricas!
        $metrics = $instance->getUsageMetrics($startDate, $endDate);
        
        // Prepara os dados para a view
        $dados = [
            'totalTokens' => $metrics['total_tokens'],
            'numeroConversas' => $metrics['unique_conversations'],
        ];
        
        return view('instances.dashboard', compact('instance', 'dados'));
    }*/

    public function dashboardPublica($id, Request $request)
    {
        $instance = Instance::findOrFail($id);
        $sessionKey = 'dashboard_unlocked_' . $instance->id;

        // --- conexão e autenticação ---
        $statusData = $this->fetchInstanceStatus($instance);
        $instance->connection_state = ($statusData['state'] ?? 'error') === 'open';
        if ($instance->connection_state) {
            if ($request->isMethod('post')) {
                if ($request->input('instance_name') === $instance->name) {
                    $request->session()->put($sessionKey, true);
                    return redirect()->route('public.dashboard', $instance->id);
                } else {
                    return back()->with('error', 'O nome da conexão está incorreto.');
                }
            }

            if (!$request->session()->has($sessionKey)) {
                return view('instances.dashboad_login', compact('instance'));
            }
        }

        // --- filtros de data e métricas ---
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $metrics = $instance->getUsageMetrics($startDate, $endDate);
        $dados = [
            'totalTokens' => $metrics['total_tokens'],
            'numeroConversas' => $metrics['unique_conversations'],
        ];

        // --- busca de chats aguardando atendimento ---
        $chatsAguardando = Chat::where('instance_id', $instance->id)
            ->where('aguardando_atendimento', true)
            ->latest()
            ->get(['id', 'nome', 'informacoes', 'contact', 'updated_at']);

        // --- horários agendados (ocupados) ---
        $horariosAgendados = \App\Models\Disponibilidade::whereHas('agenda', function ($q) use ($instance) {
                $q->where('user_id', $instance->user_id);
            })
            ->where('ocupado', true)
            ->orderBy('data', 'asc')
            ->orderBy('inicio', 'asc')
            ->get();


        // --- exportar CSV se solicitado ---
        if ($request->get('export') === 'csv') {
            $filename = 'chats_aguardando_' . now()->format('Ymd_His') . '.csv';

            return response()->streamDownload(function () use ($chatsAguardando) {
                // Garante que não existe buffer antigo
                if (ob_get_level()) { ob_end_clean(); }

                $out = fopen('php://output', 'w');

                // BOM UTF-8 (primeiros bytes!)
                echo "\xEF\xBB\xBF";

                // Cabeçalho (use ; se seu Excel estiver em pt-BR)
                fputcsv($out, ['ID','Nome','Informações','WhatsApp','Última Atualização'], ';');

                foreach ($chatsAguardando as $chat) {
                    fputcsv($out, [
                        $chat->id,
                        $chat->nome,
                        $chat->informacoes,
                        $chat->contact,
                        $chat->updated_at->format('d/m/Y H:i'),
                    ], ';');
                }

                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }

        // --- exportar CSV de horários agendados ---
        if ($request->get('export') === 'csv_agendados') {
            $filename = 'horarios_agendados_' . now()->format('Ymd_His') . '.csv';

            return response()->streamDownload(function () use ($horariosAgendados) {
                if (ob_get_level()) { ob_end_clean(); }

                $out = fopen('php://output', 'w');
                echo "\xEF\xBB\xBF"; // BOM UTF-8

                fputcsv($out, ['Data', 'Início', 'Fim', 'Nome', 'Telefone', 'Observações'], ';');

                foreach ($horariosAgendados as $h) {
                    fputcsv($out, [
                        \Carbon\Carbon::parse($h->data)->format('d/m/Y'),
                        \Carbon\Carbon::parse($h->inicio)->format('H:i'),
                        \Carbon\Carbon::parse($h->fim)->format('H:i'),
                        $h->nome,
                        $h->telefone,
                        $h->observacoes,
                    ], ';');
                }

                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }




        return view('instances.dashboard', compact('instance', 'dados', 'chatsAguardando', 'horariosAgendados'));

    }


    public function index()
    {
        $user = Auth::user();

        $availableSlots = $user->availableInstanceSlots(); //PEGA A QUANTIDADE DE SLOTS DISPONÍVEIS
        
        // Pega as instâncias do usuário (como antes)
        $instances = $user->instances()->get();
        
        // Itera sobre as instâncias para buscar o status da conexão (como antes)
        foreach ($instances as $instance) {
            $statusData = $this->fetchInstanceStatus($instance);
            $instance->connection_state = $statusData['state'] ?? 'error';
            //dd($instance->defaultAssistantByOpenAi->name);
            $instance->nomeAssistente = $instance->assistente?->name ?? 'Sem assistente';

        }

        //dd($instances[0]->defaultAssistantByOpenAi->name);

        // Passa a nova variável '$availableCredit' para a view
        return view('instances.index', [
            'instances' => $instances,
            //'hasAvailableCredit' => $availableCredit,
            'availableSlots' => $availableSlots,
        ]);
    }

    /**
     * Exclui a instância no Evolution API e no banco de dados local.
     */
    public function destroy(Instance $instance)
    {
        // 1. Segurança: Garante que o usuário só pode excluir suas próprias instâncias
        if ($instance->user_id !== Auth::id()) {
            abort(403, 'Acesso não autorizado.');
        }

        try {
            // 2. Chama a API do Evolution para excluir a instância remotamente
            $evolutionUrl = config('services.evolution.url') . "/instance/delete/{$instance->id}";
            
            Log::info("Iniciando exclusão da instância {$instance->id} no Evolution para o usuário " . Auth::id());

            $response = Http::withHeaders([
                'apiKey' => config('services.evolution.key') // Usa a chave de API GLOBAL
            ])->delete($evolutionUrl);

            // 3. Verifica a resposta da API
            // Consideramos sucesso se a resposta for bem-sucedida (2xx) ou "Não Encontrado" (404),
            // pois em ambos os casos a instância não existe mais no Evolution.
            if ($response->successful() || $response->notFound()) {
                Log::info("Instância {$instance->id} excluída com sucesso (ou já não existia) no Evolution.");
                
                // 4. Registra o proxy como banido para não reutilizar
                if (!empty($instance->proxy_ip)) {
                    ProxyIpBan::firstOrCreate(['ip' => $instance->proxy_ip]);
                }

                // 5. Exclui a instância do nosso banco de dados local
                $instance->delete();

                // Opcional: Desvincular o pagamento
                // Se você tiver um relacionamento Payment -> Instance, pode desvinculá-lo aqui
                // Payment::where('instance_id', $instance->id)->update(['instance_id' => null]);
                // Isso liberaria o "crédito" para o usuário usar novamente.

                return redirect()->route('instances.index')->with('success', 'Conexão excluída com sucesso.');
            } else {
                // Se a API do Evolution retornar um erro inesperado
                throw new \Exception('A API do Evolution retornou um erro: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("Falha ao excluir a instância {$instance->id}: " . $e->getMessage());
            return redirect()->route('instances.index')->with('error', 'Antes de excluir, desconecte o WhatsApp desta instância.');
        }
    }

    public function create()
    {
        // Apenas retorna a view que vamos criar a seguir
        return view('instances.create'); 
    }

    public function store(Request $request, WebshareService $webshare) // <-- A MÁGICA DA INJEÇÃO DE DEPENDÊNCIA
    {
        // 1. Validação dos dados do formulário (sem alterações)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'openai_api_key' => 'required|string',
            'default_assistant_id' => 'nullable|string|max:255',
        ]);

        try {
            // --- ETAPA 1: OBTER PROXY (AGORA USANDO O SERVIÇO) ---
            // Toda a lógica complexa de encontrar um proxy disponível está aqui.
            $proxyData = $webshare->getNewProxy();

            // --- ETAPA 2: Juntar os dados para criar a instância
            $instance = Auth::user()->instances()->create($validated);

            // --- ETAPA 3: CRIAR INSTÂNCIA NO EVOLUTION ---
            $evolutionUrl = config('services.evolution.url') . '/instance/create';

            $evolutionResponse = Http::withHeaders([
                'apiKey' => config('services.evolution.key')
            ])->post($evolutionUrl, [
                'instanceName' => (string) $instance->id,
                'integration' => "WHATSAPP-BAILEYS",
                'proxyHost' => $proxyData['proxy_address'],
                'proxyPort' => (string)$proxyData['port'],
                'proxyProtocol' => "http",
                'proxyUsername' => $proxyData['username'],
                'proxyPassword' => $proxyData['password'],

                'webhook' => [
                    'base64' => true,
                    'events' => ['MESSAGES_UPSERT'],
                    'url' => 'https://app.3f7.org/api/conversation', 
                ],
            ]);


            if ($evolutionResponse->failed()) {
                $instance->delete();
                throw new \Exception('Falha ao criar instância no Evolution. Resposta: ' . $evolutionResponse->body());
            }

            $evolutionData = $evolutionResponse->json();

            // --- ETAPA 4: ATUALIZAR NOSSO BANCO COM TODOS OS DADOS ---
            $instance->update([
                'evolution_api_key' => $evolutionData['hash'],
                'proxy_ip' => $proxyData['proxy_address'],
                'proxy_port' => $proxyData['port'], // CORREÇÃO: Usando a chave 'port'
                'proxy_username' => $proxyData['username'],
                'proxy_password' => $proxyData['password'],
                'proxy_provider' => 'webshare',
                'status' => 'active',
            ]);

            // --- ETAPA 5: REDIRECIONAR PARA A PÁGINA DE GERENCIAMENTO ---
            return redirect()->route('instances.show', $instance->id)
                ->with('success', 'Conexão criada e provisionada com sucesso!');

        } catch (\Throwable $e) {
            Log::error('Erro ao provisionar nova instância: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Ocorreu um erro ao criar sua conexão. Por favor, tente novamente.');
        }
    }

    public function storeDirect(WebshareService $webshare)
    {
        $user = Auth::user();

        $availableSlots = $user->availableInstanceSlots(); //PEGA A QUANTIDADE DE SLOTS DISPONÍVEIS

        if ($availableSlots <= 0) {
            // Se não houver crédito, retorna com um erro. Isso não deveria acontecer
            // se a interface estiver funcionando corretamente, mas é uma boa segurança.
            return redirect()->route('instances.index')
                ->with('error', 'Você não possui um pagamento disponível para criar uma nova conexão.');
        }

        try {
            // ETAPA 1: OBTER PROXY
            $proxyData = $webshare->getNewProxy();

            // ETAPA 2: CRIAR UM REGISTRO INICIAL NA NOSSA TABELA
            // Usamos um nome temporário que será atualizado depois.
            $instance = Auth::user()->instances()->create([
                'name' => 'Nova Conexão Pendente #' . uniqid(),
                'status' => 'pending', // Um status inicial
                'proxy_ip' =>$proxyData['proxy_address'],
                'proxy_port' =>(string)$proxyData['port'],
                'proxy_username' =>$proxyData['username'],
                'proxy_password' =>$proxyData['password'],
                'proxy_provider' =>"http",
            ]);            

            // ETAPA 3: CRIAR A INSTÂNCIA NO EVOLUTION
            $evolutionUrl = config('services.evolution.url') . '/instance/create';
            $response = Http::withHeaders(['apiKey' => config('services.evolution.key')])
                ->post($evolutionUrl, [
                    'instanceName' => (string) $instance->id,
                    'integration' => "WHATSAPP-BAILEYS",
                    'proxyHost' => $proxyData['proxy_address'],
                    'proxyPort' => (string)$proxyData['port'],
                    'proxyProtocol' => "http",
                    'proxyUsername' => $proxyData['username'],
                    'proxyPassword' => $proxyData['password'],

                    'webhook' => [
                        'base64' => true,
                        'events' => ['MESSAGES_UPSERT'],
                        'url' => 'https://app.3f7.org/api/conversation',
                    ],
                ]);

            if ($response->failed()) {
                $instance->delete(); // Limpa o registro se a API falhar
                throw new \Exception('Falha ao criar instância no Evolution. Resposta: ' . $response->body());
            }

            $evolutionData = $response->json();
            $apiKey = $evolutionData['instance']['instanceId'] ?? null; // Pega a chave correta

            // ETAPA 4: ATUALIZAR NOSSO BANCO COM OS DADOS FINAIS
            $instance->update([
                'evolution_api_key' => $apiKey,
                //'proxy_ip' => $proxyData['proxy_address'],
                //'proxy_port' => $proxyData['port'],
                //'proxy_username' => $proxyData['username'],
                //'proxy_password' => $proxyData['password'],
                //'proxy_provider' => 'webshare',
                'status' => 'active',
            ]);

            // ETAPA 5: REDIRECIONAR DIRETAMENTE PARA A PÁGINA DE GERENCIAMENTO
            return redirect()->route('instances.show', $instance->id)
                ->with('success', 'Conexão criada! Escaneie o QR Code para conectar.');

        } catch (\Throwable $e) {
            Log::error('Erro na criação direta de instância: ' . $e->getMessage());
            return redirect()->route('instances.index')
                ->with('error', 'Ocorreu um erro ao criar sua conexão. Por favor, tente novamente.');
        }
    }

    /*public function storeDirect(WebshareService $webshare)
    {
        $user = Auth::user();

        // 1. VERIFICAÇÃO DE SEGURANÇA: Garante que o usuário tem um crédito antes de prosseguir
        $credit = Payment::where('user_id', $user->id)
                        ->where('status', 'paid')
                        ->whereNull('instance_id')
                        ->oldest() // Pega o pagamento mais antigo disponível
                        ->first();

        if (!$credit) {
            // Se não houver crédito, retorna com um erro. Isso não deveria acontecer
            // se a interface estiver funcionando corretamente, mas é uma boa segurança.
            return redirect()->route('instances.index')
                ->with('error', 'Você não possui um pagamento disponível para criar uma nova conexão.');
        }

        try {
            // ... (toda a sua lógica existente para obter proxy e criar a instância no Evolution)
            // A lógica de criar a instância continua a mesma...
            
            $proxyData = $webshare->getNewProxy();
            $instance = $user->instances()->create([
                'name' => 'Nova Conexão #' . uniqid(),
                'status' => 'pending',
            ]);
            
            // ... (chamada Http para o Evolution)

            // Depois de criar a instância no Evolution e atualizar nosso registro...
            
            // 2. VINCULAÇÃO DO CRÉDITO: Atualiza o pagamento para vinculá-lo à nova instância
            $credit->instance_id = $instance->id;
            $credit->save();

            Log::info("Pagamento ID {$credit->id} vinculado com sucesso à nova instância ID {$instance->id}.");
            
            // O resto do seu código (redirecionamento, etc.) continua o mesmo
            return redirect()->route('instances.show', $instance->id)
                ->with('success', 'Conexão criada e vinculada ao seu pagamento com sucesso!');

        } catch (\Throwable $e) {
            Log::error('Erro na criação direta de instância: ' . $e->getMessage());
            // Se a instância foi criada no nosso banco mas algo falhou depois, removemos para evitar lixo.
            if (isset($instance)) {
                $instance->delete();
            }
            return redirect()->route('instances.index')
                ->with('error', 'Ocorreu um erro ao criar sua conexão. Por favor, tente novamente.');
        }
    }    */

    public function show(Instance $instance)
    {
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }
        // Apenas carrega a view. O JavaScript fará o resto.
        return view('instances.show', compact('instance'));
    }

    // Método 1: Busca o QR Code
    public function getQrCodeData(Instance $instance)
    {
        // Segurança: só o dono pode acessar
        //if ($instance->user_id !== Auth::id()) {return response()->json(['error' => 'Forbidden'], 403);}

        $instanceController = new EvolutionService();
        $response = $instanceController->conectarInstancia($instance->id); //RESPOSTA JÁ VEM COMO JSON
        //dd($response->json()); exit;
        //$response = Http::withHeaders(['apiKey' => config('services.evolution.key')])->get($connectUrl);

        //Log::info("InstanceController 193", $response);

        return $response;
    }

    // Método 2: Busca o Status
    public function getConnectionStatusData(Instance $instance)
    {
        // Segurança
        //if ($instance->user_id !== Auth::id()) {return response()->json(['error' => 'Forbidden'], 403);}

        $statusUrl = config('services.evolution.url') . "/instance/connectionState/{$instance->id}";
        $response = Http::withHeaders(['apiKey' => config('services.evolution.key')])->get($statusUrl);

        if ($response->successful()) {
            // Retorna apenas o JSON com o status
            return response()->json($response->json());
        }

        return response()->json(['error' => 'Failed to fetch status'], 500);
    }

    public function fetchInstanceStatus(Instance $instance): array
    {


        try {
            $statusUrl = config('services.evolution.url') . "/instance/connectionState/{$instance->id}";
            $response = Http::withHeaders(['apiKey' => config('services.evolution.key')])->get($statusUrl);

            if ($response->successful()) {
                //$this->atualizarNomeInstanciaLocal($instance);
                // Retorna os dados da instância da resposta
                return $response->json()['instance'];
            }
        } catch (\Exception $e) {
            Log::error("Falha ao buscar status para a instância {$instance->id}: " . $e->getMessage());
        }

        // Se a chamada falhar ou der erro, retorna um estado de erro
        return ['state' => 'error'];
    }

    private function atualizarNomeInstanciaLocal($instanceID) 
    {
        try {
            $statusUrl = config('services.evolution.url') . "/instance/fetchInstances?instanceName={$instanceID}";
            $response = Http::withHeaders(['apiKey' => config('services.evolution.key')])->get($statusUrl);

            


                 //Log::error("atualizarNomeInstanciaLocal ownerJid".$response[0] );

            if ($response->successful()) {
                $data = $response->json();

                 //Log::error("atualizarNomeInstanciaLocal ownerJid".$data[0]['ownerJid']);

                if (!empty($data[0]['ownerJid'])) {
                    return  preg_replace('/\D/', '', $data[0]['ownerJid']);
                }
            }
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar nome da instância {$instanceID}: " . $e->getMessage());
            return null;
        }
    }


    // Método para mostrar o formulário de edição
    public function edit(Instance $instance)
    {
        // Segurança: Garante que o usuário só pode editar suas próprias instâncias
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }

        if (!ctype_digit( $instance->name)) {
            $instance->name = $this->atualizarNomeInstanciaLocal($instance->id) ?? $instance->name;
            $instance->save();
        }

        //Log::error("Novo nome ".$this->atualizarNomeInstanciaLocal($instance->id). "/n Instancia Completa". $instance);

        // Pega as credenciais OpenAI do usuário para a primeira lista suspensa
        $assistants = Auth::user()->assistants;
        $credentials = Auth::user()->credentials;
        $agendas = Agenda::where('user_id', Auth::id())->get(); // 👈 adiciona isso

        return view('instances.edit', compact('instance', 'assistants', 'credentials', 'agendas'));
    }

    // Método para salvar as atualizações
    public function update(Request $request, Instance $instance)
    {
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }

        // Valida os dados recebidos do formulário
        $validated = $request->validate([
            //'name' => 'required|string|max:255',
            'default_assistant_id' => 'required|string|', // Garante que é um ID de assistente válido
            'credential_id' => 'nullable|integer|exists:credentials,id',  // Garante que é um ID de credencial válido
            'agenda_id' => 'nullable|integer|exists:agendas,id',
            'model' => 'required|string|',
        ]);

        $instance->update($validated);
        
        //REINICIA TODOS OS CHATS
        Chat::where('assistant_id', (string)$validated['default_assistant_id'])->update(['conv_id' => null]);

        return redirect()->route('instances.index')->with('success', 'Conexão atualizada com sucesso!');
    }

    // Novo método que retorna JSON para o nosso JavaScript
    public function getAssistantsForCredential(Credential $credential)
    {
        // Segurança
        if ($credential->user_id !== Auth::id()) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        try {
            $openaiService = new OpenAIService($credential->token);
            $assistants = $openaiService->listAssistants();
            // Retorna a lista de assistentes como JSON
            return response()->json($assistants);
        } catch (\Exception $e) {
            Log::error("Falha ao buscar assistentes para a credencial {$credential->id}: " . $e->getMessage());
            return response()->json(['error' => 'Falha ao buscar dados na API da OpenAI'], 500);
        }
    }
}
