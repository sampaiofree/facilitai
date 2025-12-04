@php
    // Os dados agora vêm do controller, este bloco não é mais necessário aqui.
@endphp

<x-public-dashboard-layout>
    <div class="container mx-auto p-4 sm:p-6 lg:p-8" id="connection-container" data-instance-id="{{ $instance->id }}">

        {{-- Lógica principal: Mostra o modal de QR Code OU o Dashboard --}}
        @if(!$instance->connection_state)
             
            {{-- MODAL DE QR CODE (agora visível por padrão se não conectado) --}}
            <div id="qr-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center p-4 z-50">     
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl relative transform transition-all duration-300 ease-out">
                    {{-- O conteúdo do seu modal continua o mesmo --}}
                    <div class="grid grid-cols-1 md:grid-cols-2">
                        <!-- Coluna da Esquerda: Instruções -->
                        <div class="p-8 border-r border-gray-100">
                            <h3 class="text-2xl font-bold text-gray-800 mb-6">Passos para Conectar</h3>
                            <ol class="list-decimal list-inside space-y-4 text-gray-600">
                                <li>Abra o aplicativo do **WhatsApp** no seu celular.</li>
                                <li>Vá em <span class="font-semibold">Configurações</span> > <span class="font-semibold">Aparelhos conectados</span>.</li>
                                <li>Toque em <span class="font-semibold">Conectar um aparelho</span>.</li>
                                <li>Aponte a câmera do seu celular para o QR code ao lado.</li>
                            </ol>
                            <div class="mt-8 bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg text-center">
                                <p class="font-semibold">Aguardando conexão... 🚀</p>
                            </div>
                        </div>
                        <!-- Coluna da Direita: QR Code -->
                        <div class="p-8 flex flex-col items-center justify-center bg-gray-50 rounded-r-2xl">
                            <h4 class="text-lg font-medium text-gray-700 mb-4">Escaneie para Conectar</h4>
                            <div id="qrcode-image-container" class="w-64 h-64 flex items-center justify-center bg-white rounded-lg border border-dashed">
                                <div class="text-center text-gray-400">
                                    <svg class="animate-spin h-10 w-10 mx-auto mb-2" xmlns="http://www.w.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <p>Gerando QR Code...</p>
                                </div>
                            </div>
                            <p class="mt-4 text-sm text-gray-500">O código será atualizado automaticamente.</p>
                        </div>
                    </div>
                </div>
            </div>

        @else
        
            {{-- Bloco de estilo para o gradiente de fundo --}}
            @push('head')
            <style>
                body {
                    background-image: linear-gradient(to bottom right, #111827, #3b0764, #111827);
                }
            </style>
            @endpush

            <div class="p-6">
                <div class="max-w-7xl mx-auto">
                    {{-- Header --}}
                    <div class="mb-8">
                        <h1 class="text-4xl font-bold text-white mb-2">Dashboard WhatsApp {{$instance->name}}</h1>
                        <p class="text-gray-300">Monitore suas conversas e uso de tokens em tempo real</p>
                    </div>

                    {{-- Formulário de Filtro de Datas --}}
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-6 border border-white/20 shadow-xl">
                        <form method="GET" action="{{ route('public.dashboard', ['id' => $instance->id]) }}">
                            <div class="flex items-center gap-2 mb-4">
                                {{-- Ícone de Calendário (SVG) --}}
                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <h2 class="text-xl font-semibold text-white">Período de Análise</h2>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-300 mb-2">Data Início</label>
                                    <input type="date" id="start_date" name="start_date" value="{{ request('start_date', now()->subDays(6)->format('Y-m-d')) }}"
                                        class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-300 mb-2">Data Final</label>
                                    <input type="date" id="end_date" name="end_date" value="{{ request('end_date', now()->format('Y-m-d')) }}"
                                        class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                                </div>
                                <button type="submit" class="w-full md:w-auto bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-xl transition-all">Filtrar</button>
                            </div>
                        </form>
                    </div>

                    {{-- Status da Conexão --}}
                    <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 backdrop-blur-lg rounded-2xl p-6 mb-6 border border-green-500/30 shadow-xl">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="bg-green-500/20 p-4 rounded-xl">
                                    {{-- Ícone Wifi (SVG) --}}
                                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.555a5.5 5.5 0 017.778 0M12 20.25a.75.75 0 100-1.5.75.75 0 000 1.5zM4.444 12.889a11 11 0 0115.112 0"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-white">{{ $instance->name }}</h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="w-3 h-3 rounded-full {{ $instance->connection_state ? 'bg-green-400 animate-pulse' : 'bg-red-400' }}"></div>
                                        <span class="text-gray-300 font-medium">
                                            {{ $instance->connection_state ? 'Conexão Ativa' : 'Conexão Inativa' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @if($instance->connection_state)
                                <div class="hidden md:block">
                                    <span class="bg-green-500/30 text-green-300 px-4 py-2 rounded-full text-sm font-semibold">Online</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Cards de Estatísticas --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @php
                            // Cálculos feitos aqui para manter o HTML limpo
                            $conversations = $dados['numeroConversas'];
                            $totalTokens = $dados['totalTokens'];
                            $cost = ($totalTokens / 1000) * 0.002; 
                        @endphp
                        {{-- Card de Conversas --}}
                        <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 backdrop-blur-lg rounded-2xl p-6 border border-blue-500/30 shadow-xl hover:scale-[1.03] transition-transform duration-300">
                            <div class="flex items-start justify-between mb-4">
                                <div class="bg-blue-500/20 p-4 rounded-xl">
                                    {{-- Ícone MessageCircle (SVG) --}}
                                    <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                </div>
                                <div class="text-right"><p class="text-gray-300 text-sm font-medium mb-1">Total de</p><h3 class="text-blue-300 text-lg font-semibold">Conversas</h3></div>
                            </div>
                            <div class="mt-6"><p class="text-5xl font-bold text-white mb-2">{{ number_format($conversations) }}</p></div>
                        </div>

                        {{-- Card de Tokens --}}
                        <div class="bg-gradient-to-br from-purple-500/20 to-pink-500/20 backdrop-blur-lg rounded-2xl p-6 border border-purple-500/30 shadow-xl hover:scale-[1.03] transition-transform duration-300">
                            <div class="flex items-start justify-between mb-4">
                                <div class="bg-purple-500/20 p-4 rounded-xl">
                                    {{-- Ícone Zap (SVG) --}}
                                    <svg class="w-10 h-10 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                                <div class="text-right"><p class="text-gray-300 text-sm font-medium mb-1">Total de</p><h3 class="text-purple-300 text-lg font-semibold">Tokens Usados</h3></div>
                            </div>
                            <div class="mt-6">
                                <p class="text-5xl font-bold text-white mb-2">{{ number_format($totalTokens) }}</p>
                                <!--<div class="flex items-center gap-2 text-sm"><span class="text-gray-400">Custo estimado:</span><span class="text-purple-400 font-semibold">${{ number_format($cost, 2) }}</span></div>-->
                            </div>
                        </div>
                    </div>
                    
                    {{-- Informações Adicionais --}}
                    
                    <div class="mt-6 bg-white/5 backdrop-blur-lg rounded-2xl p-6 border border-white/10">
                        @php
                            // Evita divisão por zero se não houver conversas
                            $conversations = $dados['numeroConversas'];
                            $totalTokens = $dados['totalTokens'];
                            
                            // Calcula a duração do período para a média diária
                            $startDate = \Carbon\Carbon::parse(request('start_date', now()->subDays(6)));
                            $endDate = \Carbon\Carbon::parse(request('end_date', now()));
                            $periodInDays = $startDate->diffInDays($endDate) + 1;

                            // Métrica 1: Média de Conversas por Dia
                            // Garante que não haverá divisão por zero se o período for menor que 1 dia
                            $conversationsPerDay = ($periodInDays > 0) ? $conversations / $periodInDays : 0;

                            // Métrica 2: Tokens por Conversa
                            $tokensPerConversation = ($conversations > 0) ? $totalTokens / $conversations : 0;
                        @endphp
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center divide-y md:divide-y-0 md:divide-x divide-white/10">
                            
                            {{-- Card 1: Média de Conversas por Dia --}}
                            <div class="py-4 md:py-0">
                                <p class="text-gray-400 text-sm mb-1">Média de Conversas por Dia</p>
                                <p class="text-2xl font-bold text-white">
                                    {{ number_format($conversationsPerDay, 1) }}
                                </p>
                            </div>
                            
                            {{-- Card 2: Tokens Médios por Conversa --}}
                            <div class="py-4 md:py-0">
                                <p class="text-gray-400 text-sm mb-1">Tokens Médios por Conversa</p>
                                <p class="text-2xl font-bold text-white">
                                    {{ number_format($tokensPerConversation, 0) }}
                                </p>
                            </div>
                            
                            {{-- Card 3: Período Selecionado --}}
                            <div class="py-4 md:py-0">
                                <p class="text-gray-400 text-sm mb-1">Período Selecionado</p>
                                <p class="text-2xl font-bold text-white">
                                    {{ (int)$periodInDays }} dia{{ $periodInDays > 1 ? 's' : '' }}
                                </p>
                            </div>

                        </div>
                    </div>

                    {{-- 🔹 Tabela de Chats Aguardando Atendimento --}}
                    @if(isset($chatsAguardando) && $chatsAguardando->count())
                    <div id="aguardando-container" class="mt-10 bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 shadow-xl">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                Aguardando Atendimento (<span id="aguardando-count">{{ $chatsAguardando->count() }}</span>)
                            </h2>

                            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-all">
                            📥 Baixar CSV
                            </a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-left text-gray-300">
                                <thead class="bg-white/5 uppercase text-xs text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">ID</th>
                                        <th class="px-4 py-3">Nome</th>
                                        <th class="px-4 py-3">Informações</th>
                                        <th class="px-4 py-3">WhatsApp</th>
                                        <th class="px-4 py-3">Atualizado em</th>
                                        <th class="px-4 py-3 text-center">Ação</th>
                                    </tr>
                                </thead>
                                <tbody id="aguardando-tbody">
                                    @foreach($chatsAguardando as $chat)
                                        <tr id="chat-row-{{ $chat->id }}" class="border-b border-white/10 hover:bg-white/5 transition">
                                            <td class="px-4 py-3">{{ $chat->id }}</td>
                                            <td class="px-4 py-3">{{ $chat->nome ?? '—' }}</td>
                                            <td class="px-4 py-3">{{ Str::limit($chat->informacoes, 80) ?? '—' }}</td>
                                            <td class="px-4 py-3 text-purple-300">{{ $chat->contact }}</td>
                                            <td class="px-4 py-3">{{ $chat->updated_at->format('d/m/Y H:i') }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <button 
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg text-xs font-semibold transition-all"
                                                    id="atender-btn-{{ $chat->id }}"
                                                    data-chat-id="{{ $chat->id }}"
                                                    onclick="marcarAtendido({{ $chat->id }})">
                                                    ✅ Marcar como atendido
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- 🔹 Tabela de Horários Agendados --}}
                    @if(isset($horariosAgendados) && $horariosAgendados->count())
                    <div class="mt-10 bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 shadow-xl">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m2 8H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2z" />
                                </svg>
                                Horários Agendados ({{ $horariosAgendados->count() }})
                            </h2>

                            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv_agendados']) }}"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-lg transition-all">
                            📥 Baixar CSV
                            </a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm text-left text-gray-300">
                                <thead class="bg-white/5 uppercase text-xs text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">Data</th>
                                        <th class="px-4 py-3">Início</th>
                                        <th class="px-4 py-3">Fim</th>
                                        <th class="px-4 py-3">Nome</th>
                                        <th class="px-4 py-3">Telefone</th>
                                        <th class="px-4 py-3">Observações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($horariosAgendados as $h)
                                        <tr class="border-b border-white/10 hover:bg-white/5 transition">
                                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($h->data)->format('d/m/Y') }}</td>
                                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($h->inicio)->format('H:i') }}</td>
                                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($h->fim)->format('H:i') }}</td>
                                            <td class="px-4 py-3 text-white font-semibold">{{ $h->nome ?? '—' }}</td>
                                            <td class="px-4 py-3 text-emerald-300">{{ $h->telefone ?? '—' }} | {{ $h->chat?->contact ?? '—' }}</td>
                                            <td class="px-4 py-3">{{ $h->observacoes ?? '—' }} | {{ $h->chat?->informacoes ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $horariosAgendados->links() }}
                        </div>
                    </div>
@endif



                </div>
            </div>

        @endif
    </div>

    @push('scripts')
    <script>
    // Este script só precisa rodar se o modal de QR Code estiver na página
    @if(!$instance->connection_state)
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('connection-container');
        const instanceId = container.dataset.instanceId;
        const qrCodeContainer = document.getElementById('qrcode-image-container');

        let qrRefreshInterval = null;
        let statusCheckInterval = null;

        // Função para buscar e mostrar o QR Code
        const fetchQrCode = async () => {
            console.log('Buscando QR Code...');
            try {
                const response = await fetch(`/instances/${instanceId}/qrcode-data`);
                const data = await response.json();
                if (data.base64) {
                    qrCodeContainer.innerHTML = `<img src="${data.base64}" alt="QR Code" class="mx-auto border rounded-lg">`;
                } else {
                    qrCodeContainer.innerHTML = `<p class="text-red-500 p-4">Erro ao gerar QR Code. Tentando novamente...</p>`;
                }
            } catch (error) {
                console.error('Error fetching QR Code:', error);
                qrCodeContainer.innerHTML = `<p class="text-red-500 p-4">Erro de conexão ao buscar QR Code.</p>`;
            }
        };

        // Função para checar o status da conexão
        const checkConnectionStatus = async () => {
            console.log('Verificando status...');
            try {
                const response = await fetch(`/instances/${instanceId}/status-data`);
                const data = await response.json();
                
                if (data.instance && data.instance.state === 'open') {
                    // SUCESSO! A instância está conectada.
                    console.log('Conectado! Recarregando página...');
                    // Para os timers e recarrega a página para mostrar o dashboard.
                    clearInterval(qrRefreshInterval);
                    clearInterval(statusCheckInterval);
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error checking status:', error);
            }
        };

        // --- CORREÇÃO PRINCIPAL: INICIA O PROCESSO IMEDIATAMENTE ---
        fetchQrCode(); // Busca o primeiro QR Code assim que a página carrega
        qrRefreshInterval = setInterval(fetchQrCode, 15000); // E o atualiza a cada 15s

        // Também começa a verificar o status da conexão imediatamente
        statusCheckInterval = setInterval(checkConnectionStatus, 7000); // A cada 7s
    });
    @endif

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    async function marcarAtendido(chatId) {
        const button = document.getElementById(`atender-btn-${chatId}`);
        const row = document.getElementById(`chat-row-${chatId}`);
        const tbody = document.getElementById('aguardando-tbody');
        const countElement = document.getElementById('aguardando-count');
        const container = document.getElementById('aguardando-container');

        if (!csrfToken) {
            alert('Token de segurança não encontrado. Recarregue a página e tente novamente.');
            return;
        }

        if (button) {
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');
            button.textContent = 'Marcando...';
        }

        try {
            const response = await fetch(`/chats/${chatId}/marcar-atendido`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Falha na requisição');
            }

            const data = await response.json();

            if (row) {
                row.remove();
            }

            const remaining = typeof data.remaining === 'number'
                ? data.remaining
                : (tbody ? tbody.querySelectorAll('tr').length : 0);

            if (countElement) {
                countElement.textContent = remaining;
            }

            if (remaining === 0 && container) {
                container.classList.add('hidden');
            }
        } catch (error) {
            console.error('Erro ao marcar atendimento:', error);
            if (button) {
                button.disabled = false;
                button.classList.remove('opacity-70', 'cursor-not-allowed');
                button.textContent = '✅ Marcar como atendido';
            }
            alert('Não foi possível marcar como atendido. Tente novamente.');
        }
    }
    </script>
    @endpush
</x-public-dashboard-layout>
