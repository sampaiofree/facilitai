@php
    $assistant = $assistant ?? null;
    $wizardData = [
        'name' => old('name', $assistant->name ?? ''),
        'delay' => old('delay', $assistant->delay ?? ''),
        'instructions' => old('instructions', $assistant->instructions ?? ''),
        'prompt_notificar_adm' => old('prompt_notificar_adm', $assistant->prompt_notificar_adm ?? ''),
        'prompt_buscar_get' => old('prompt_buscar_get', $assistant->prompt_buscar_get ?? ''),
        'prompt_enviar_media' => old('prompt_enviar_media', $assistant->prompt_enviar_media ?? ''),
        'prompt_registrar_info_chat' => old('prompt_registrar_info_chat', $assistant->prompt_registrar_info_chat ?? ''),
        'prompt_gerenciar_agenda' => old('prompt_gerenciar_agenda', $assistant->prompt_gerenciar_agenda ?? ''),
        'prompt_aplicar_tags' => old('prompt_aplicar_tags', $assistant->prompt_aplicar_tags ?? ''),
        'prompt_sequencia' => old('prompt_sequencia', $assistant->prompt_sequencia ?? ''),
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ isset($assistant) ? 'Editar assistente' : 'Criar assistente (wizard rápido)' }}
                </h2>
                <p class="text-sm text-gray-600">
                    {{ isset($assistant) ? 'Atualize as instruções do assistente usando o mesmo wizard.' : 'Preencha apenas o essencial, navegando por etapas sem sair da página.' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                @isset($assistant)
                    <form action="{{ route('assistants.destroy', $assistant) }}" method="POST" onsubmit="return confirm('Deseja excluir este assistente?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 font-semibold">Excluir assistente</button>
                    </form>
                @endisset
                <a href="{{ route('assistants.index') }}" class="text-sm text-blue-600 font-semibold">Voltar para lista</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data='assistantWizard(@json($wizardData))'>
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                {{-- Passos do wizard --}}
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <template x-for="(stepItem, index) in steps" :key="stepItem.id">
                                <button type="button"
                                    class="flex items-center gap-2 px-3 py-2 rounded-lg border transition"
                                    :class="step === stepItem.id ? 'bg-blue-50 border-blue-200 text-blue-700 font-semibold' : 'bg-gray-50 border-gray-200 text-gray-700'"
                                    @click="step = stepItem.id">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full"
                                          :class="step > stepItem.id ? 'bg-green-500 text-white' : step === stepItem.id ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'">
                                          <span x-text="step > stepItem.id ? '✔' : stepItem.id"></span>
                                    </span>
                                    <span class="text-sm flex items-center gap-1">
                                        <span x-text="stepItem.label"></span>
                                        
                                    </span>
                                </button>
                            </template>
                        </div>

                <form x-ref="wizardForm" @submit.prevent="submitForm" method="POST" action="{{ isset($assistant) ? route('assistants.wizard.update', $assistant) : route('assistants.wizard.store') }}" class="space-y-6">
                    @csrf
                    @isset($assistant)
                        @method('PUT')
                    @endisset

                    {{-- Passo 1 --}}
                    <div x-show="step === 1" class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                            Informações básicas
        
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do assistente</label>
                                <input type="text" name="name" x-model="form.name" value="{{ old('name', $assistant->name ?? '') }}" maxlength="255" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Ex: Atendente Loja XYZ" aria-required="true">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-2">
                                    Delay de resposta (segundos)
                                    <span class="text-gray-400 text-xs">Tempo de espera para parecer natural.</span>
                                </label>
                                <input type="number" name="delay" x-model.number="form.delay" value="{{ old('delay', $assistant->delay ?? '') }}" min="0" step="1" pattern="\d*" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Ex: 2">
                            </div>
                        </div>
                    </div>

                    {{-- Passo 2 --}}
                    <div x-show="step === 2" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                    Instruções gerais
                                    
                                </h3>
                                <p class="text-sm text-gray-600">Explique como o assistente deve se comportar (tom, estilo, o que sempre fazer).</p>
                            </div>
                            <div class="relative">
                                <button type="button" class="text-sm text-blue-600 font-semibold" @click="toggleInstructionsMenu">Usar texto sugerido</button>
                                <div x-show="instructionsMenu" @click.away="instructionsMenu = false" x-cloak class="absolute right-0 mt-2 w-64 bg-white border rounded-lg shadow text-sm z-10">
                                    <template x-for="snippet in instructionSnippets" :key="snippet.label">
                                        <button type="button" class="w-full text-left px-4 py-2 hover:bg-blue-50 border-b last:border-b-0 text-gray-800 font-semibold" @click="applyInstructionSnippet(snippet.value)">
                                            <span x-text="snippet.label"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <textarea name="instructions" data-field="instructions" x-model="form.instructions" rows="8" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Ex: Seja cordial, responda de forma breve e sempre peça confirmação antes de concluir." aria-required="true">{{ old('instructions', $assistant->instructions ?? '') }}</textarea>
                    </div>

                    {{-- Passo 3 --}}
                    <div x-show="step === 3" class="space-y-4">
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">Funções avançadas</h3>
                                <p class="text-sm text-gray-600">Descreva gatilhos e exemplos para cada ferramenta (notificar admin, buscar links, enviar mídias, registrar dados, agenda e aplicação de tags).</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <template x-for="tab in tabs" :key="tab.id">
                                <button type="button" class="px-3 py-2 rounded-lg border text-sm"
                                    :class="activeTab === tab.id ? 'bg-blue-50 border-blue-200 text-blue-700 font-semibold' : 'bg-gray-50 border-gray-200 text-gray-700'"
                                    @click="activeTab = tab.id">
                                    <span x-text="tab.label"></span>
                                </button>
                            </template>
                        </div>

                        <div class="bg-gray-50 border rounded-lg p-4">
                            <template x-for="tab in tabs" :key="tab.id">
                                <div x-show="activeTab === tab.id" x-cloak class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-gray-600" x-text="tab.hint"></p>
                                        </div>
                                        <button type="button" class="text-sm text-blue-600 font-semibold" @click="fill(tab.field)">Usar texto sugerido</button>
                                    </div>
                                    <textarea :name="tab.field" x-bind:data-field="tab.field" x-model="form[tab.field]" rows="7" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Passo 4 --}}
                    <div x-show="step === 4" class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-800">Pronto para criar</h3>
                        <p class="text-sm text-gray-600">Você pode voltar às etapas para ajustar qualquer informação.</p>
                        <div class="flex items-center gap-3">
                            <button type="button" class="px-4 py-2 rounded-lg border text-sm text-gray-700" @click="step = 3">Voltar</button>
                            <button type="submit" class="bg-blue-600 text-white font-semibold px-5 py-2 rounded-lg">
                                {{ isset($assistant) ? 'Atualizar assistente' : 'Criar assistente' }}
                            </button>
                        </div>
                    </div>

                    {{-- Navegação geral --}}
                    <div class="flex items-center justify-between pt-4 border-t">
                        <button type="button" class="px-4 py-2 rounded-lg border text-sm text-gray-700"
                            :disabled="step === 1"
                            :class="step === 1 ? 'opacity-50 cursor-not-allowed' : ''"
                            @click="step = Math.max(1, step - 1)">
                            Voltar
                        </button>
                        <button type="button" class="px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold text-sm"
                            x-show="step < 4"
                            @click="goNext()">
                            Próximo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function assistantWizard(initialData = {}) {
            return {
                step: 1,
                steps: [
                    { id: 1, label: '1 • Básico' },
                    { id: 2, label: '2 • Instruções' },
                    { id: 3, label: '3 • Avançado' },
                    { id: 4, label: '4 • Criar' },
                ],
                form: {
                    name: initialData.name ?? '',
                    delay: initialData.delay ?? '',
                    instructions: initialData.instructions ?? '',
                    prompt_notificar_adm: initialData.prompt_notificar_adm ?? '',
                    prompt_buscar_get: initialData.prompt_buscar_get ?? '',
                    prompt_enviar_media: initialData.prompt_enviar_media ?? '',
                    prompt_registrar_info_chat: initialData.prompt_registrar_info_chat ?? '',
                    prompt_gerenciar_agenda: initialData.prompt_gerenciar_agenda ?? '',
                    prompt_aplicar_tags: initialData.prompt_aplicar_tags ?? '',
                    prompt_sequencia: initialData.prompt_sequencia ?? '',
                },
                instructionSnippets: [
                    {
                        label: 'Perfil do Assistente',
                        value: "## Perfil do Assistente\n- Seu nome é **{nome do assistente}** e você é o atendente oficial da empresa.\n- **Função Principal:** Seu objetivo é tirar dúvidas sobre o produto.\n- **Público-Alvo:** Deve se comunicar primariamente com pessoas com dúvidas sobre nossos serviços.\n- **Tom de Voz:** A comunicação deve ser divertido e descontraído.\n- **Mensagem de Saudação:** Quando receber a primeira mensagem, inicie a primeira interação com a seguinte frase: \"Olá, como posso ajudar?\"\n"
                    },
                    {
                        label: 'Processo de Atendimento',
                        value: "## Processo de Atendimento\nSiga a seguinte sequência de passos ao iniciar uma conversa:\n1. Se apresente com seu nome e pergunte o nome do cliente.\n2. Entenda a situação do cliente diagnosticando suas necessidades.\n3. Apresente nossa solução de forma atraente, demonstrando que resolve o problema do cliente.\n4. Agradeça a conversa e se despeça de forma entusiástica.\n- Não repita o processo de atendimento.\n"
                    },
                    {
                        label: 'Situações Específicas',
                        value: "## Tratamento de Situações Específicas\nResponda a cenários específicos da seguinte maneira:\n- Se o usuário disser: \"Está caro\", então responda: \"Vou explicar melhor como funciona\".\n- Se o usuário disser: \"fala do usuário\", então responda: \"resposta do assistente\".\n"
                    },
                    {
                        label: 'Sobre a empresa',
                        value: "## Sobre a empresa {nome da empresa}\n- Descreva aqui o que a empresa faz.\n- Inclua endereço, CNPJ, telefone e outros dados essenciais quando necessário.\n"
                    },
                    {
                        label: 'Produtos e Serviços',
                        value: "## Produtos e Serviços\n- Descreva aqui os produtos ou serviços oferecidos.\n- Inclua detalhes como preço, prazo de entrega e diferenciais que ajudem na venda.\n"
                    },
                    {
                        label: 'Restrições',
                        value: "## Restrições\n- Descreva aqui o que o assistente **não** deve fazer ou responder.\n"
                    },
                ],
                tabs: [
                    { id: 'notificar', label: 'Notificação', field: 'prompt_notificar_adm', hint: 'Informe quando avisar o admin. Ex: cliente pediu humano -> usar notificar_adm com o número correto.' },
                    { id: 'buscar', label: 'Buscar GET', field: 'prompt_buscar_get', hint: 'Liste URLs e momentos em que o bot precisa consultar dados externos antes de responder.' },
                    { id: 'midia', label: 'Enviar mídia', field: 'prompt_enviar_media', hint: 'Relacione mídias disponíveis e descreva quando enviar cada uma.' },
                    { id: 'registro', label: 'Registrar info', field: 'prompt_registrar_info_chat', hint: 'Explique quais informações coletar (nome, email, resumo) e o momento de salvar.' },
                    { id: 'agenda', label: 'Gerenciar agenda', field: 'prompt_gerenciar_agenda', hint: 'Defina pré-requisitos para consultar horários e confirmar novos agendamentos.' },
                    { id: 'tags', label: 'Aplicar tags', field: 'prompt_aplicar_tags', hint: 'Liste as tags existentes e oriente quando aplicar cada uma usando a tool aplicar_tags.' },
                    { id: 'sequencia', label: 'Sequências', field: 'prompt_sequencia', hint: 'Explique quando inscrever o chat em sequências existentes usando a tool inscrever_sequencia.' },
                ],
                activeTab: 'notificar',
                suggestions: {
                    instructions: "Seja cordial e claro. Use frases curtas, confirme entendimentos e mantenha o contexto. Se não souber, peça mais detalhes. Nunca invente dados.",
                    prompt_notificar_adm: "## Quando usar a tool `notificar_adm`\n- Notifique o administrador sempre que o cliente pedir um humano, quando houver reclamações graves ou bloqueio no atendimento.\n- Exemplo: \"Quando o cliente disser que quer falar com um humano, notifique o administrador com o número 551199999999 e resuma o contexto em uma frase\".\n",
                    prompt_buscar_get: "## Quando usar a tool `buscar_get`\n- Sempre que precisar de informações atualizadas ou citar dados específicos, consulte a URL indicada antes de responder.\n- Exemplo: \"Quando o cliente perguntar sobre o produto XYZ, busque informações em https://www.produto.xyz e responda apenas após analisar o resultado\".\n",
                    prompt_enviar_media: "## Quando usar a tool `enviar_media`\n- Defina quais imagens, vídeos, áudios ou PDFs devem ser enviados e em qual cenário cada um se aplica.\n- Exemplo: \"Quando o cliente perguntar como funciona o serviço XYZ, envie o áudio https://www.audioxyz.com e explique em uma frase o conteúdo\".\n",
                    prompt_registrar_info_chat: "## Quando usar a tool `registrar_info_chat`\n- Instrua o assistente a coletar nome, email, telefone ou resumo da conversa e registrar tudo assim que obtiver os dados.\n- Exemplo: \"Durante o atendimento descubra nome e email; assim que tiver os dois, registre um resumo da necessidade do cliente\".\n",
                    prompt_gerenciar_agenda: "## Quando usar a tool `gerenciar_agenda`\n- Utilize após confirmar que o cliente tem interesse e atende aos requisitos. Liste as regras antes de consultar e confirmar horários.\n- Exemplo: \"Antes de consultar a agenda, confirme que o cliente entendeu o serviço e deseja realizá-lo; só então verifique horários e finalize o agendamento\".\n",
                    prompt_aplicar_tags: "## Quando usar a tool `aplicar_tags`\nAs tags são:\n- aluno – pessoa que já comprou e está pedindo suporte.\n- não aluno – pessoa interessada em comprar ou pedir informações.\n\nRegra obrigatória:\n- Assim que o usuário responder se é aluno ou não, você deve interromper a conversa imediatamente e chamar a tool `aplicar_tags` com a tag correta.\n- Não escreva nenhuma mensagem junto da chamada da tool.\n- Após a tool executar, continue o atendimento normalmente.\n\nNunca crie tags novas.\nSe não der para saber ainda, não use a ferramenta."
                    prompt_sequencia: "## Regra obrigatória para uso da tool `inscrever_sequencia`\n- o identificar que o usuário não é aluno, você deve imediatamente chamar a ferramenta inscrever_sequencia usando: {\"sequence_id\": 3}.\n- Não escreva nenhuma mensagem antes ou depois.\n- Apenas execute a chamada da tool.\n- Depois que a tool for executada, continue o atendimento normalmente.",
                },
                instructionsMenu: false,
                goNext() {
                    if (this.step === 1 && !this.validateBasics()) return;
                    if (this.step === 2 && !this.validateInstructions()) return;
                    this.step = Math.min(4, this.step + 1);
                },
                submitForm() {
                    if (!this.validateBasics()) {
                        this.step = 1;
                        return;
                    }
                    if (!this.validateInstructions()) {
                        this.step = 2;
                        return;
                    }

                    // Preenche os inputs reais antes de submeter
                    this.$refs.wizardForm.querySelectorAll('[name]').forEach((el) => {
                        const key = el.getAttribute('name');
                        if (Object.prototype.hasOwnProperty.call(this.form, key)) {
                            el.value = this.form[key] ?? '';
                        }
                    });

                    this.$refs.wizardForm.submit();
                },
                validateBasics() {
                    if (!this.form.name || !this.form.name.trim()) {
                        showAlert?.('Informe o nome do assistente.', 'warning');
                        return false;
                    }
                    return true;
                },
                validateInstructions() {
                    if (!this.form.instructions || !this.form.instructions.trim()) {
                        showAlert?.('As instruções gerais são obrigatórias.', 'warning');
                        return false;
                    }
                    return true;
                },
                fill(field) {
                    this.form[field] = this.suggestions[field] || '';
                    showAlert?.('Texto sugerido aplicado.', 'info');
                },
                toggleInstructionsMenu() {
                    this.instructionsMenu = !this.instructionsMenu;
                },
                applyInstructionSnippet(text) {
                    const current = (this.form.instructions || '').trim();
                    this.form.instructions = current ? `${current}\n\n${text}` : text;
                    showAlert?.('Trecho adicionado ao texto.', 'info');
                    this.instructionsMenu = false;
                },
            };
        }
    </script>
</x-app-layout>
