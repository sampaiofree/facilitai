<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\PromptBuilderService;
use App\Models\Payment;
use App\Models\Assistant;
use Illuminate\Support\Str;

class AssistantController extends Controller
{
    // Apenas carrega a página principal com a lista de credenciais
    // Em app/Http/Controllers/AssistantController.php



    public function index()
    {
        $user = Auth::user();

        // LÓGICA SIMPLIFICADA: Sempre busca os assistentes do nosso banco de dados.
        $assistants = $user->assistants()->with('credential')->get();
        
        // As permissões continuam sendo importantes.
        
        $availableSlots = $user->availableAssistantSlots(); 
        //$credentials = $user->credentials;
        
        return view('assistants.index', [
            'assistants' => $assistants,
            //'credentials' => $credentials,
            'availableSlots' => $availableSlots,
            
        ]);
    }

    // Busca a lista de assistentes de uma credencial específica
    


    /**
     * Wizard simples - exibe a página de criação rápida de assistente.
     */
    public function wizard()
    {
        $user = Auth::user();

        if ($user->availableAssistantSlots() <= 0) {
            return redirect()->route('assistants.index')
                ->with('error', 'Você não tem slots disponíveis para criar um novo assistente.');
        }

        return view('assistants.wizard');
    }

    /**
     * Wizard simples - exibe a página de edição rápida de assistente.
     */
    public function editWizard(Assistant $assistant)
    {
        $this->authorizeAssistant($assistant);

        return view('assistants.wizard', [
            'assistant' => $assistant,
        ]);
    }

    /**
     * Wizard simples - salva o assistente localmente com prompts básicos.
     */
    public function storeWizard(Request $request)
    {
        $user = Auth::user();

        if ($user->availableAssistantSlots() <= 0) {
            return redirect()->route('assistants.index')
                ->with('error', 'Você não tem slots disponíveis para criar um novo assistente.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'delay' => ['nullable', 'integer', 'min:0'],
            'instructions' => ['required', 'string'],
            'prompt_notificar_adm' => ['nullable', 'string'],
            'prompt_buscar_get' => ['nullable', 'string'],
            'prompt_enviar_media' => ['nullable', 'string'],
            'prompt_registrar_info_chat' => ['nullable', 'string'],
            'prompt_gerenciar_agenda' => ['nullable', 'string'],
            'prompt_aplicar_tags' => ['nullable', 'string'],
            'prompt_sequencia' => ['nullable', 'string'],
        ]);

        $assistant = Assistant::create([
            'user_id' => $user->id,
            'payment_id' => null,
            'credential_id' => null,
            'openai_assistant_id' => 'local-' . Str::uuid(),
            'name' => $validated['name'],
            'instructions' => $validated['instructions'],
            'delay' => $validated['delay'] ?? null,
            'prompt_notificar_adm' => $validated['prompt_notificar_adm'] ?? null,
            'prompt_buscar_get' => $validated['prompt_buscar_get'] ?? null,
            'prompt_enviar_media' => $validated['prompt_enviar_media'] ?? null,
            'prompt_registrar_info_chat' => $validated['prompt_registrar_info_chat'] ?? null,
            'prompt_gerenciar_agenda' => $validated['prompt_gerenciar_agenda'] ?? null,
            'prompt_aplicar_tags' => $validated['prompt_aplicar_tags'] ?? null,
            'prompt_sequencia' => $validated['prompt_sequencia'] ?? null,
        ]);

        return redirect()->route('assistants.index')->with('success', 'Assistente criado com sucesso.');
    }

    /**
     * Wizard simples - atualiza assistente existente.
     */
    public function updateWizard(Request $request, Assistant $assistant)
    {
        $this->authorizeAssistant($assistant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'delay' => ['nullable', 'integer', 'min:0'],
            'instructions' => ['required', 'string'],
            'prompt_notificar_adm' => ['nullable', 'string'],
            'prompt_buscar_get' => ['nullable', 'string'],
            'prompt_enviar_media' => ['nullable', 'string'],
            'prompt_registrar_info_chat' => ['nullable', 'string'],
            'prompt_gerenciar_agenda' => ['nullable', 'string'],
            'prompt_aplicar_tags' => ['nullable', 'string'],
            'prompt_sequencia' => ['nullable', 'string'],
        ]);

        $assistant->update([
            'name' => $validated['name'],
            'instructions' => $validated['instructions'],
            'delay' => $validated['delay'] ?? null,
            'prompt_notificar_adm' => $validated['prompt_notificar_adm'] ?? null,
            'prompt_buscar_get' => $validated['prompt_buscar_get'] ?? null,
            'prompt_enviar_media' => $validated['prompt_enviar_media'] ?? null,
            'prompt_registrar_info_chat' => $validated['prompt_registrar_info_chat'] ?? null,
            'prompt_gerenciar_agenda' => $validated['prompt_gerenciar_agenda'] ?? null,
            'prompt_aplicar_tags' => $validated['prompt_aplicar_tags'] ?? null,
            'prompt_sequencia' => $validated['prompt_sequencia'] ?? null,
        ]);

        return redirect()->route('assistants.index')->with('success', 'Assistente atualizado com sucesso.');
    }

    public function destroy(Assistant $assistant)
    {
        if ($assistant->user_id !== Auth::id()) {
            abort(403);
        }

        // Exclui os chats relacionados
        //Chat::where('assistant_id', $assistant->id)->delete();

        // Exclui o assistente localmente
        $assistant->delete();

        return redirect()->route('assistants.index')->with('success', 'Assistente excluído com sucesso!');
    }


    // Em app/Http\Controllers/AssistantController.php

    // ... (outros métodos como index, fetchAssistants, etc.)

    /**
     * Mostra a página do "Assistente de Criação" (o quiz).
     */
    public function showBuilder()
    {
        $user = Auth::user();

        // Segurança: Só permite entrar no builder se tiver slots disponíveis
        if ($user->availableAssistantSlots() <= 0) {
            return redirect()->route('assistants.index')
                ->with('error', 'Você não tem slots disponíveis para criar um novo assistente.');
        }

        // Pega as credenciais do usuário para a lista suspensa (se ele puder gerenciá-las)
        $credentials = $user->credentials;
        
        return view('assistants.builder', [
            'credentials' => $credentials,
            
        ]);
    }

    private function authorizeAssistant(Assistant $assistant): void
    {
        if ($assistant->user_id !== Auth::id()) {
            abort(403);
        }
    }

    /**
     * Recebe os dados do quiz, constrói o prompt e cria o assistente.
     */
    public function storeFromBuilder(Request $request)
    {
        $user = Auth::user();

        // 1. VERIFICAÇÃO DE SLOT
        // Segurança extra para garantir que o usuário ainda tem slots, mesmo que tenha deixado a página aberta.
        if ($user->availableAssistantSlots() <= 0) {
            return redirect()->route('assistants.index')->with('error', 'Você não tem mais slots disponíveis.');
        }
       

        $validated = $request->validate([
            //'credential_id'    => 'nullable|integer|exists:credentials,id',
            'delay'    => 'required|int',
            //'modelo'    => 'required|string',
            'assistant_name'   => 'required|string|max:255',
            'main_function'    => 'required|string',
            'target_audience'  => 'required|string',
            'tone_of_voice'    => 'required|string',
            'important_info'   => 'nullable|string',
            'restrictions'     => 'nullable|string',
            'first_message'    => 'required|string',
            'step_by_step'     => 'required|array',
            'step_by_step.*'   => 'required|string|max:1000',
            'situations'       => 'nullable|array',
            'situations.*.situation' => 'nullable|string|max:1000',
            'situations.*.response'  => 'nullable|string|max:1000',
            'admin_phones'     => 'nullable|array',
            'admin_phones.*'   => 'nullable|string|max:20',
        ]);
        

       try {
        // 4. PREPARAR DADOS PARA A CRIAÇÃO
           
        
        /*if($validated['credential_id']){

            $credential = null;
            $apiKey = null; 
            $credential = Credential::findOrFail($validated['credential_id']); //dd($credential); exit;
            $apiKey = $credential->token;
            if ($credential->user_id !== Auth::id()) {abort(403, 'Acesso não autorizado a esta credencial.');}

            // Se a API key estiver vazia por algum motivo, para o processo.
            if (empty($apiKey)) {throw new \Exception('A chave de API da OpenAI não está configurada para esta operação.');}
            if($credential){$credential_id = $credential->id;}
        }else{
            $credential_id = null;
        }*/


        // 2. Usar o PromptBuilderService para construir as instruções
        
        $promptBuilder = new PromptBuilderService();
        
        $instructions = $promptBuilder->build($validated);
        
        // Log para depuração - veja o prompt gerado!
        

        //dd($credential->user_id); exit;
        // Segurança: Garante que a credencial pertence ao usuário logado
        

        // OpenAIService para criar o assistente
        //$openaiService = new OpenAIService($apiKey);
        //$openaiAssistant = $openaiService->createAssistant($validated['assistant_name'],$instructions,);
        
        
        // Vinculando tudo: usuário, pagamento e a credencial (se for premium)
        $assistant = new Assistant();
        $assistant->user_id = $user->id;
        $assistant->payment_id = $paymentSlot->id ?? null; // Vincula ao slot de pagamento
        //$assistant->credential_id = $credential_id; // Vincula à credencial do usuário, se houver
        //$assistant->openai_assistant_id = $openaiAssistant->id;
        $assistant->name = $validated['assistant_name'];
        $assistant->delay = $validated['delay'];
        //$assistant->modelo = $validated['modelo'];
        $assistant->instructions = $instructions; // Salva o prompt gerado para referência
        $assistant->save();
        
        return redirect()->route('assistants.index')
            ->with('success', 'Seu novo assistente foi criado com sucesso!');

        } catch (\Exception $e) {
            Log::error("Falha ao criar assistente a partir do quiz: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->with('error', 'Ocorreu um erro ao criar seu assistente. Verifique os logs para detalhes.');
        }
    }

    // Em app/Http/Controllers/AssistantController.php

    /**
     * Mostra o formulário para editar um assistente específico.
     */
    public function edit(Assistant $assistant)
    {
        // Segurança: Garante que o usuário só pode editar seus próprios assistentes
        if ($assistant->user_id !== Auth::id()) {
            abort(403);
        }

        $user = Auth::user();
        $credentials = $user->credentials;
        
        return view('assistants.edit', compact('assistant', 'credentials'));
    }

    /**
     * Processa a atualização de um assistente.
     */
    public function update(Request $request, Assistant $assistant)
    {
        // Segurança
        if ($assistant->user_id !== Auth::id()) {
            abort(403);
        }

        // Validação dos dados do formulário
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'instructions' => 'required|string',
            'credential_id' => 'nullable|integer|exists:credentials,id',
            'delay' => 'required|int',
            //'modelo' => 'required|string',
        ]); 

        try {
            // 3. Atualizar os dados no nosso banco de dados local
            $assistant->version = $assistant->version + 1;
            $assistant->update($validated);

            //dd($assistant);

            //Chat::where('assistant_id', (string)$assistant->id)->delete();
            //Chat::where('assistant_id', (string)$assistant->id)->update(['conv_id' => null]);

            
            
            return redirect()->route('assistants.index')->with('success', 'Assistente atualizado com sucesso!');

        } catch (\Exception $e) {
            Log::error("Falha ao atualizar o assistente ID {$assistant->id}: " . $e->getMessage());
            return back()->withInput()->with('error', 'Ocorreu um erro ao atualizar o assistente.');
        }
    }
}
