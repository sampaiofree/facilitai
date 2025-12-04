<x-app-layout>
    <x-slot name="header">
        {{-- Podemos deixar o cabeçalho mais amigável --}}
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            Bem-vindo(a) de volta, <span class="text-indigo-600">{{ Auth::user()->name }}</span>!
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @include('partials.tokens-summary')

            @if(!Auth::user()->credentials()->exists())
                 <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 14h.01M16 10h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-700 text-sm leading-relaxed">
                                    🎥 <strong>Você ainda não cadastrou uma credencial</strong><br>
                                    👉 Sem isso, a ferramenta não funciona. 
                                    Assista ao vídeo e aprenda a criar sua credencial passo a passo.
                                </p>
                            </div>
                        </div>

                        <a href="https://youtu.be/nYJrUaVJd0o" target="_blank"
                        class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M4 6h8m0 0v12m0-12L4 18" />
                            </svg>
                            Assistir tutorial
                        </a>
                    </div>
            @endif


            {{-- Seção de Introdução --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6 md:p-8 border-b border-gray-200 flex flex-col md:flex-row items-center justify-between gap-6">
                    
                    <div class="flex-1 text-center md:text-left">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">
                            Comece a automatizar agora
                        </h3>
                        <p class="text-gray-600 text-lg leading-relaxed">
                            Não sabe por onde começar? <span class="text-indigo-600 font-medium">Assista ao guia rápido</span> antes de configurar seus passos abaixo.
                        </p>
                    </div>

                    <button class="lessons-help-trigger group flex-shrink-0 inline-flex items-center gap-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 text-white shadow-lg shadow-indigo-500/40 transition-all duration-300 hover:scale-105 hover:shadow-indigo-500/60 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        
                        <div class="bg-white/20 rounded-full p-2 group-hover:bg-white/30 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8">
                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm14.024-.983a1.125 1.125 0 010 1.966l-5.603 3.113A1.125 1.125 0 019 15.113V8.887c0-.857.921-1.4 1.671-.983l5.603 3.113z" clip-rule="evenodd" />
                            </svg>
                        </div>

                        <div class="text-left">
                            <span class="block text-xs font-medium text-indigo-100 uppercase tracking-wider">Tutorial em Vídeo</span>
                            <span class="block font-bold text-xl leading-none">Assistir Aulas</span>
                        </div>
                    </button>

                </div>
            </div>

            {{-- Grid com os 3 Blocos de Ação --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                <!-- Bloco 1: Criar Assistentes -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg transform transition-transform hover:-translate-y-1">
                    <div class="p-6 text-center">
                        {{-- Ícone --}}
                        <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.898 20.562L16.25 21.75l-.648-1.188a2.25 2.25 0 01-1.423-1.423L13.25 18.5l1.188-.648a2.25 2.25 0 011.423-1.423L16.25 15l.648 1.188a2.25 2.25 0 011.423 1.423L19.5 18.5l-1.188.648a2.25 2.25 0 01-1.423 1.423z" />
                            </svg>
                        </div>
                        {{-- Título e Descrição --}}
                        <h4 class="text-lg font-bold text-gray-900">1. Crie seus Assistentes</h4>
                        <p class="text-gray-600 mt-2 mb-6 text-sm">Use nosso guia passo a passo para ensinar sua IA. Defina o tom de voz, as regras e o que ela precisa saber.</p>
                        {{-- Botão de Ação --}}
                        <a href="{{ route('assistants.index') }}" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                            Criar Assistente
                        </a>
                    </div>
                </div>
                
                <!-- Bloco 2: Conectar o WhatsApp -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg transform transition-transform hover:-translate-y-1">
                    <div class="p-6 text-center">
                        {{-- Ícone --}}
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                            </svg>
                        </div>
                        {{-- Título e Descrição --}}
                        <h4 class="text-lg font-bold text-gray-900">2. Conecte seu WhatsApp</h4>
                        <p class="text-gray-600 mt-2 mb-6 text-sm">Crie uma nova conexão e escaneie o QR Code para deixar seu número online e pronto para receber mensagens.</p>
                        {{-- Botão de Ação --}}
                        <a href="{{ route('instances.index') }}" class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                            Conectar Agora
                        </a>
                    </div>
                </div>

                

                <!-- Bloco 3: Vincular Assistentes -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg transform transition-transform hover:-translate-y-1">
                    <div class="p-6 text-center">
                        {{-- Ícone --}}
                        <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                           <svg class="w-10 h-10 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                            </svg>
                        </div>
                        {{-- Título e Descrição --}}
                        <h4 class="text-lg font-bold text-gray-900">3. Vincule a IA ao WhatsApp</h4>
                        <p class="text-gray-600 mt-2 mb-6 text-sm">Escolha qual assistente de IA responderá em cada número de WhatsApp conectado. Simples e rápido.</p>
                        {{-- Botão de Ação --}}
                        <a href="{{ route('instances.index') }}" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                            Vincular Agora
                        </a>
                    </div>
                </div>

            </div>
            
        </div>
    </div>
</x-app-layout>