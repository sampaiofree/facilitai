<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Assistente de Criação Guiada') }}
        </h2>
    </x-slot>

     @push('head')
        {{-- 1. Inclui a biblioteca SortableJS (Alpine.js e Tailwind já vêm do Vite) --}}
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

        {{-- 2. Adiciona o CSS customizado para esta página --}}
        <style>
            [x-cloak] { display: none !important; }
            .step-transition {
                transition: all 0.3s ease-in-out;
            }
            .progress-bar {
                transition: width 0.3s ease-in-out;
            }
        </style>
    @endpush

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                {{-- Inicializa o Alpine.js para gerenciar os dados do formulário --}}
                <form method="POST" action="{{ route('assistants.store_from_builder') }}" x-data="formBuilderData()" x-init="initSortable()" class="p-8">
                    @csrf

                    <!-- Step 1: Credencial -->
                    
                    <div x-show.immediate="currentStep === 1" x-transition x-cloak class="step-transition">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Primeiro, Escolha o tempo de resposta do assistente</h2>
                            
                        </div>
                        
                        <div class="max-w-md mx-auto">
                            
                            <!--<div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Selecione sua credencial</label>
                                <select x-model="formData.credential_id" name="credential_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <option value="0">Selecione uma credencial</option>
                                    @if(Auth::user()->canManageCredentials())
                                        
                                        @foreach ($credentials as $credential)
                                            <option value="{{ $credential->id }}" @selected(old('credential_id') == $credential->id)>
                                                {{ $credential->label }}
                                            </option>
                                        @endforeach
                                    @endif
                                        <option value="">Tokens</option>
                                </select>
                            </div>-->
                            
                                
                            

                            {{-- Campo: Tempo de resposta (delay) --}}
                            <div class="mt-6">
                                <label for="delay" class="block text-sm font-medium text-gray-700 mb-1">Tempo de resposta em segundos</label>
                                <input 
                                    type="number" 
                                    id="delay" 
                                    name="delay" 
                                    x-model.number="formData.delay" {{-- Use x-model.number para garantir que o valor seja tratado como número --}}
                                    min="0" 
                                    placeholder="Ex: 5" 
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>

                            {{-- Campo: Modelo --}}
                            <!--<div class="mt-6">
                                <label for="modelo" class="block text-sm font-medium text-gray-700 mb-1">Modelo de IA</label>
                                <select 
                                    id="modelo" 
                                    name="modelo" 
                                    x-model="formData.modelo" 
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                    required
                                >
                                    <option value="gpt-5">gpt-5</option>
                                    <option value="gpt-5-mini">gpt-5-mini</option>
                                    <option value="gpt-5-nano">gpt-5-nano</option>
                                    @if(Auth::user()->canManageCredentials())<option value="gpt-4.1">gpt-4.1</option>@endif
                                    <option value="gpt-4.1-mini">gpt-4.1-mini</option> {{-- Remova o 'selected' daqui --}}
                                    @if(Auth::user()->canManageCredentials())<option value="gpt-4.1-nano">gpt-4.1-nano</option>@endif
                                </select>
                                <p x-show="formData.modelo === 'gpt-4.1-mini'" class="mt-1 text-xs text-gray-500">
                                    
                                </p>
                            </div>-->
                        </div>
                    </div>

                    <!-- Step 2: Nome do Assistente -->
                    <div x-show.immediate="currentStep === 2" x-transition x-cloak class="step-transition">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Nome do assistente</h2>
                            <p class="text-gray-600">Como você quer chamar seu assistente?</p>
                        </div>
                        
                        <div class="max-w-md mx-auto">
                            <input x-model="formData.assistant_name" type="text" name="assistant_name" 
                                   placeholder="Ex: Atendente Virtual, Consultor João" 
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required />
                        </div>
                    </div>


                    <!-- Step 3: Função Principal -->
                    <div x-show.immediate="currentStep === 3" x-transition x-cloak class="step-transition">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Função principal do assistente</h2>
                            <p class="text-gray-600">O que você quer que o assistente faça?</p>
                        </div>
                        
                        <div class="max-w-2xl mx-auto">
                            <textarea x-model="formData.main_function" name="main_function" rows="4" 
                                      class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                      placeholder="Descreva a função principal do seu assistente..." required></textarea>
                            
                            <div class="mt-4 flex flex-wrap gap-2 justify-center">
                                <button type="button" @click="addToField('main_function', 'tirar dúvidas')" 
                                        class="bg-blue-100 hover:bg-blue-200 text-blue-800 text-sm font-medium px-3 py-1 rounded-full transition-colors">
                                    tirar dúvidas
                                </button>
                                <button type="button" @click="addToField('main_function', 'vender cursos')" 
                                        class="bg-blue-100 hover:bg-blue-200 text-blue-800 text-sm font-medium px-3 py-1 rounded-full transition-colors">
                                    vender cursos
                                </button>
                                <button type="button" @click="addToField('main_function', 'agendar atendimentos')" 
                                        class="bg-blue-100 hover:bg-blue-200 text-blue-800 text-sm font-medium px-3 py-1 rounded-full transition-colors">
                                    agendar atendimentos
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Público-Alvo -->
                    <div x-show.immediate="currentStep === 4" x-transition x-cloak class="step-transition">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Público-alvo</h2>
                            <p class="text-gray-600">Para quem o assistente vai falar?</p>
                        </div>
                        
                        <div class="max-w-2xl mx-auto">
                            <textarea x-model="formData.target_audience" name="target_audience" rows="4" 
                                      class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                      placeholder="Descreva seu público-alvo..." required></textarea>
                            
                            <div class="mt-4 flex flex-wrap gap-2 justify-center">
                                <button type="button" @click="addToField('target_audience', 'alunos')" 
                                        class="bg-pink-100 hover:bg-pink-200 text-pink-800 text-sm font-medium px-3 py-1 rounded-full transition-colors">
                                    alunos
                                </button>
                                <button type="button" @click="addToField('target_audience', 'clientes novos')" 
                                        class="bg-pink-100 hover:bg-pink-200 text-pink-800 text-sm font-medium px-3 py-1 rounded-full transition-colors">
                                    clientes novos
                                </button>
                                <button type="button" @click="addToField('target_audience', 'pessoas com dúvidas')" 
                                        class="bg-pink-100 hover:bg-pink-200 text-pink-800 text-sm font-medium px-3 py-1 rounded-full transition-colors">
                                    pessoas com dúvidas
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Tom de Voz -->
                    <div x-show.immediate="currentStep === 5" x-transition x-cloak class="step-transition">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Tom de voz</h2>
                            <p class="text-gray-600">Como o assistente deve falar?</p>
                        </div>
                        
                        <div class="max-w-2xl mx-auto">
                            <input x-model="formData.tone_of_voice" type="text" name="tone_of_voice" 
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-4" 
                                   placeholder="Descreva o tom de voz desejado..." required />
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <button type="button" @click="formData.tone_of_voice = 'Formal, educado e profissional'" 
                                        class="p-4 border-2 border-gray-200 hover:border-yellow-400 rounded-lg text-center transition-colors">
                                    <div class="font-medium text-gray-900">Formal</div>
                                    <div class="text-sm text-gray-600">Educado e profissional</div>
                                </button>
                                <button type="button" @click="formData.tone_of_voice = 'Leve, amigável e informal'" 
                                        class="p-4 border-2 border-gray-200 hover:border-yellow-400 rounded-lg text-center transition-colors">
                                    <div class="font-medium text-gray-900">Informal</div>
                                    <div class="text-sm text-gray-600">Amigável e descontraído</div>
                                </button>
                                <button type="button" @click="formData.tone_of_voice = 'Divertido e descontraído'" 
                                        class="p-4 border-2 border-gray-200 hover:border-yellow-400 rounded-lg text-center transition-colors">
                                    <div class="font-medium text-gray-900">Engraçado</div>
                                    <div class="text-sm text-gray-600">Divertido e espontâneo</div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 6: Informações Importantes -->
                    <div x-show.immediate="currentStep === 6" x-transition x-cloak class="step-transition">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Informações importantes</h2>
                            <p class="text-gray-600">O que o assistente precisa saber para atender bem? Aqui você também pode colocar as URLS (links das páginas) que seu assistente deve consultar se necessário.</p>
                        </div>
                        
                        <div class="max-w-2xl mx-auto">
                            <textarea x-model="formData.important_info" name="important_info" rows="6" 
                                      class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                      placeholder="Ex: lista de cursos, preços, horários, políticas da empresa, veja https://wwww.meusite.com para saber sobre..."></textarea>
                        </div>
                    </div>

                    <!-- Step 7: Restrições -->
                    <div x-show.immediate="currentStep === 7" x-transition x-cloak class="step-transition">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Restrições</h2>
                            <p class="text-gray-600">O que ele NÃO deve fazer ou falar?</p>
                        </div>
                        
                        <div class="max-w-2xl mx-auto">
                            <textarea x-model="formData.restrictions" name="restrictions" rows="4" 
                                      class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                      placeholder="Ex: não pode dar desconto, não deve falar sobre concorrentes..."></textarea>
                        </div>
                    </div>

                    <!-- Step 8: Primeira Mensagem -->
                    <div x-show.immediate="currentStep === 8" x-transition x-cloak class="step-transition">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Primeira mensagem</h2>
                            <p class="text-gray-600">Como o assistente deve iniciar a conversa?</p>
                        </div>
                        
                        <div class="max-w-2xl mx-auto">
                            <textarea x-model="formData.first_message" name="first_message" rows="4" 
                                      class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                      placeholder="Ex: Olá! Sou o assistente virtual da empresa. Como posso ajudar você hoje?" required></textarea>
                        </div>
                    </div>

                    <!-- Step 9: Passo a Passo -->
                    <div x-show.immediate="currentStep === 9" x-transition x-cloak class="step-transition">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h6a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Passo a passo do Atendimento</h2>
                            <p class="text-gray-600">Arraste os blocos para reordenar a sequência</p>
                        </div>
                        
                        <div class="max-w-3xl mx-auto">
                            <div x-ref="sortableSteps" class="space-y-3">
                                <template x-for="(step, index) in formData.steps" :key="index">
                                    <div class="flex items-center space-x-3 p-4 border-2 border-gray-200 rounded-lg bg-white hover:shadow-md transition-shadow">
                                        <div class="flex items-center justify-center w-8 h-8 bg-orange-100 text-orange-600 rounded-full text-sm font-medium" x-text="index + 1"></div>
                                        <svg class="h-6 w-6 text-gray-400 cursor-grab hover:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                        <input type="text" :name="'step_by_step[' + index + ']'" x-model="formData.steps[index]" 
                                               class="flex-1 border-0 p-0 focus:ring-0 focus:border-0 bg-transparent">
                                        <button type="button" @click="formData.steps.length > 1 ? formData.steps.splice(index, 1) : null" 
                                                class="text-gray-400 hover:text-red-600 p-1" :disabled="formData.steps.length <= 1">
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            
                            <button type="button" @click="formData.steps.push('Novo passo...')" 
                                    class="mt-4 w-full border-2 border-dashed border-gray-300 rounded-lg p-4 text-gray-600 hover:border-orange-400 hover:text-orange-600 transition-colors">
                                + Adicionar novo passo
                            </button>
                        </div>
                    </div>

                    <!-- Step 10: Situações Específicas -->
                    <div x-show.immediate="currentStep === 10" x-transition x-cloak class="step-transition">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Situações Específicas</h2>
                            <p class="text-gray-600">Regras "Se o usuário disser X, o assistente deve fazer Y"</p>
                        </div>
                        
                        <div class="max-w-4xl mx-auto">
                            <div class="space-y-4">
                                <template x-for="(rule, index) in formData.rules" :key="index">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border-2 border-gray-200 rounded-lg bg-white">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Sempre que o usuário disser...</label>
                                            <input x-model="rule.situation" type="text" :name="'situations[' + index + '][situation]'" 
                                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                                   placeholder="Ex: quero cancelar">
                                        </div>
                                        <div class="flex space-x-2">
                                            <div class="flex-1">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">O assistente deve...</label>
                                                <input x-model="rule.response" type="text" :name="'situations[' + index + '][response]'" 
                                                       class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                                       placeholder="Ex: Oferecer outra solução">
                                            </div>
                                            <button type="button" @click="formData.rules.length > 1 ? formData.rules.splice(index, 1) : null" 
                                                    class="mt-7 text-gray-400 hover:text-red-600 p-2" :disabled="formData.rules.length <= 1">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <button type="button" @click="formData.rules.push({ situation: '', response: '' })" 
                                    class="mt-4 w-full border-2 border-dashed border-gray-300 rounded-lg p-4 text-gray-600 hover:border-cyan-400 hover:text-cyan-600 transition-colors">
                                + Adicionar nova regra
                            </button>
                        </div>
                    </div>

                    <!-- Step 11: Notificar Administradores -->
                    <div x-show.immediate="currentStep === 11" x-transition x-cloak class="step-transition">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2zM12 6v6m0 0v6"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">Notificar Administradores</h2>
                            <p class="text-gray-600">Telefones que serão notificados em caso de atendimento humanizado</p>
                        </div>
                        
                        <div class="max-w-2xl mx-auto">
                            <div class="space-y-3">
                                <template x-for="(phone, index) in formData.admin_phones" :key="index">
                                    <div class="flex items-center space-x-3 p-3 border-2 border-gray-200 rounded-lg bg-white">
                                        <div class="flex items-center justify-center w-8 h-8 bg-emerald-100 text-emerald-600 rounded-full text-sm font-medium" x-text="index + 1"></div>
                                        <input type="tel" x-model="formData.admin_phones[index]" :name="'admin_phones[' + index + ']'" 
                                               class="flex-1 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                               placeholder="55 (11) 99999-9999">
                                        <button type="button" @click="formData.admin_phones.length > 1 ? formData.admin_phones.splice(index, 1) : null" 
                                                class="text-gray-400 hover:text-red-600 p-1" :disabled="formData.admin_phones.length <= 1">
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            
                            <button type="button" @click="formData.admin_phones.push('')" 
                                    class="mt-4 w-full border-2 border-dashed border-gray-300 rounded-lg p-4 text-gray-600 hover:border-emerald-400 hover:text-emerald-600 transition-colors">
                                + Adicionar telefone de administrador
                            </button>
                            
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <div class="flex items-start space-x-2">
                                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div class="text-sm text-blue-800">
                                        <p class="font-medium mb-1">Como funciona:</p>
                                        <p>Quando um usuário solicitar atendimento humano ou a conversa precisar ser transferida, os administradores cadastrados aqui receberão uma notificação via WhatsApp.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between mt-8 pt-6 border-t">
                        <button type="button" @click="prevStep()" x-show.immediate="currentStep > 1" 
                                class="flex items-center space-x-2 px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            <span>Anterior</span>
                        </button>
                        
                        <div class="flex space-x-4">
                            <button type="button" @click="nextStep()" x-show.immediate="currentStep < totalSteps" 
                                    class="flex items-center space-x-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <span>Próximo</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            
                            <button type="submit" x-show.immediate="currentStep === totalSteps" 
                                    class="flex items-center space-x-2 px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors transform hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Gerar e Criar Assistente</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            function formBuilderData() {
            return {
                currentStep: 1,
                totalSteps: 11,
                formData: {
                    credential_id: '0',
                    assistant_name: '',
                    main_function: '',
                    target_audience: '',
                    tone_of_voice: '',
                    important_info: '',
                    restrictions: '',
                    first_message: '',
                    steps: [
                        'Se apresente com seu nome e pergunte o nome do cliente',
                        'Entenda a situação do cliente diagnosticando suas necessidades',
                        'Apresente a nossa solução de forma atraente, demonstrando que resolve o problema do cliente',
                        'Agradeça a conversa e se despeça de forma entusiástica'
                    ],
                    rules: [
                        { situation: '', response: '' }
                    ],
                    admin_phones: [''],
                    delay: '0',
                    modelo: 'gpt-4.1-mini',
                },

                nextStep() {
                    if (this.validateCurrentStep() && this.currentStep < this.totalSteps) {
                        this.currentStep++;
                    }
                },

                prevStep() {
                    if (this.currentStep > 1) {
                        this.currentStep--;
                    }
                },

                validateCurrentStep() {
                    switch(this.currentStep) {
                        
                        case 1:
                            /*if (this.formData.credential_id === '0') {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Campo obrigatório',
                                    text: 'Por favor, selecione uma credencial.'
                                });
                                return false;
                            }*/
                             if (this.formData.delay !== '' && isNaN(parseFloat(this.formData.delay))) { // Use parseFloat para lidar com strings numéricas
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Formato inválido',
                                    text: 'O tempo de resposta (delay) deve ser um número em segundos ou pode ser deixado com 0.'
                                });
                                return false;
                            }
                            break;
                        
                        case 2:
                            if (!this.formData.assistant_name.trim()) {
                                Swal.fire({
                                icon: 'warning',
                                title: 'Campo obrigatório',
                                text: 'Por favor, digite o nome do assistente.'
                                });
                                return false;
                            }
                            break;
                        case 3:
                            if (!this.formData.main_function.trim()) {
                                //alert('Por favor, descreva a função principal do assistente.');
                                Swal.fire({
                                icon: 'warning',
                                title: 'Campo obrigatório',
                                text: 'Por favor, descreva a função principal do assistente.'
                                });
                                return false;
                            }
                            break;
                        case 4:
                            if (!this.formData.target_audience.trim()) {
                                //alert('Por favor, descreva o público-alvo.');
                                Swal.fire({
                                icon: 'warning',
                                title: 'Campo obrigatório',
                                text: 'Por favor, descreva o público-alvo.'
                                });
                                return false;
                            }
                            break;
                        case 5:
                            if (!this.formData.tone_of_voice.trim()) {
                                //alert('Por favor, defina o tom de voz.');
                                Swal.fire({
                                icon: 'warning',
                                title: 'Campo obrigatório',
                                text: 'Por favor, defina o tom de voz.'
                                });
                                return false;
                            }
                            break;
                        case 8:
                            if (!this.formData.first_message.trim()) {
                                //alert('Por favor, digite a primeira mensagem.');
                                Swal.fire({
                                icon: 'warning',
                                title: 'Campo obrigatório',
                                text: 'Por favor, digite a primeira mensagem.'
                                });
                                return false;
                            }
                            break;
                    }
                    return true;
                },

                addToField(field, value) {
                    if (this.formData[field]) {
                        this.formData[field] += (this.formData[field] ? ', ' : '') + value;
                    } else {
                        this.formData[field] = value;
                    }
                },

                initSortable() {
                    this.$nextTick(() => {
                        if (this.$refs.sortableSteps) {
                            new Sortable(this.$refs.sortableSteps, {
                                animation: 150,
                                ghostClass: 'opacity-50',
                                handle: '.cursor-grab',
                                onEnd: (evt) => {
                                    let items = Array.from(this.formData.steps);
                                    const [reorderedItem] = items.splice(evt.oldIndex, 1);
                                    items.splice(evt.newIndex, 0, reorderedItem);
                                    this.formData.steps = items;
                                }
                            });
                        }
                    });
                }
            }
        }
        </script>
    @endpush
</x-app-layout>