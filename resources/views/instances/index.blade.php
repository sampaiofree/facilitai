<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Minhas Conexões') }}</h2>
    </x-slot>

    

    <div class="py-12">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 14h.01M16 10h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-700 text-sm leading-relaxed">
                            💬 <strong>Como conectar o seu WhatsApp</strong><br>
                            Para ativar o atendimento automático, siga estes passos simples:<br><br>
                            1️⃣ Clique no botão <strong>"Criar Nova Conexão"</strong> aqui na plataforma.<br>
                            2️⃣ Será exibido um <strong>QR Code</strong> na tela.<br>
                            3️⃣ No seu celular, abra o <strong>WhatsApp &gt; Dispositivos Conectados &gt; Conectar um novo dispositivo</strong>.<br>
                            4️⃣ Aponte a câmera do celular para o QR Code.<br><br>
                            ✅ Pronto! Seu WhatsApp estará conectado à plataforma e depois só clicar em "editar" para vincular seu assistente IA para responder automaticamente.
                        </p>
                    </div>
                </div>
            </div>


            {{-- ... (bloco da mensagem de sucesso) ... --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6 text-right">
                        {{-- Substitua por este formulário --}}
                        <div class="mb-6 text-right">
                            {{-- NOVA LÓGICA DE CONDIÇÃO --}}
                            @if ($availableSlots > 0)
                                <div class="flex items-center justify-end space-x-4">
                                        <span class="text-sm text-gray-600">
                                            @if($availableSlots > 9000)
                                                Slots: <span class="font-bold text-green-600">Ilimitados</span>
                                            @else
                                                Slots disponíveis: <span class="font-bold text-green-600">{{ $availableSlots }}</span>
                                            @endif
                                        </span>
                                        <form action="{{ route('instances.store_direct') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md">
                                            + Criar Nova Conexão
                                        </button>
                                    </form> 
                                </div>
                                 
                            @else
                                <div class="p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg text-left">
                                    <p class="font-semibold">Nenhum slot disponível.</p>
                                    <p class="text-sm">Você precisa adquirir um novo plano para poder criar mais conexões.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="w-2/5 text-left py-3 px-6">Nome</th>
                                    <th class="w-1/5 text-left py-3 px-6">Status</th>
                                    <th class="w-1/5 text-left py-3 px-6">Credencial</th>
                                    <th class="w-2/5 text-left py-3 px-6">Assistente Vinculado</th>
                                    <th class="text-center py-3 px-6">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($instances as $instance)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-4 px-6">{{ $instance->name }}</td>
                                        <td class="py-4 px-6">
                                            @if ($instance->connection_state == 'open')
                                                <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs">Conectado</span>
                                            @else
                                                <span class="bg-yellow-200 text-yellow-800 py-1 px-3 rounded-full text-xs">Desconectado</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6 font-mono text-xs">
                                            {{ $instance->credential->name ?? 'Tokens'}}
                                        </td>
                                        <td class="py-4 px-6 font-mono text-xs">
                                            {{ $instance->nomeAssistente }}
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <div class="flex items-center justify-center space-x-4">

                                                <button type="button"
                                                    onclick="copiarLink('{{ route('public.dashboard', $instance->id) }}')" 
                                                    class="flex items-center space-x-2 px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors text-xs font-semibold" 
                                                    title="Copiar link do Dashboard">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M3 3a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1H4a1 1 0 01-1-1V3zM3 12a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1H4a1 1 0 01-1-1v-5zM12 3a1 1 0 011-1h4a1 1 0 011 1v9a1 1 0 01-1 1h-4a1 1 0 01-1-1V3zM12 15a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1v-2z"/>
                                                </svg>
                                                <span>Dashboard</span>
                                            </button>


                                                <script>
                                                function copiarLink(url) {
                                                // tenta API moderna
                                                if (navigator.clipboard && window.isSecureContext) {
                                                    navigator.clipboard.writeText(url)
                                                    .then(() => showAlert("✅ Link copiado!", "success"))
                                                    .catch(() => copiaAntiga(url));
                                                } else {
                                                    copiaAntiga(url);
                                                }
                                                }

                                                function copiaAntiga(texto) {
                                                const ta = document.createElement('textarea');
                                                ta.value = texto;
                                                ta.setAttribute('readonly', '');
                                                ta.style.position = 'fixed';
                                                ta.style.top = '-9999px';
                                                document.body.appendChild(ta);
                                                ta.select();
                                                try {
                                                    document.execCommand('copy');
                                                    showAlert("Link copiado!", "success");
                                                } catch (e) {
                                                    showAlert("❌ Não foi possível copiar.", "error");
                                                } finally {
                                                    document.body.removeChild(ta);
                                                }
                                                }
                                                </script>



                                                {{-- Botão Conectar ou Editar (com lógica condicional) --}}
                                                @if ($instance->connection_state == 'open')
                                                    {{-- Botão Editar --}}
                                                    <a href="{{ route('instances.edit', $instance->id) }}" 
                                                    class="flex items-center space-x-2 px-3 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors text-xs font-semibold" 
                                                    title="Editar Configurações">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span>Editar</span>
                                                    </a>
                                                @else
                                                    {{-- Botão Conectar --}}
                                                    <a href="{{ route('instances.show', $instance->id) }}" 
                                                    class="flex items-center space-x-2 px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-xs font-semibold" 
                                                    title="Conectar e ver QR Code">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                                        </svg>
                                                        <span>Conectar</span>
                                                    </a>
                                                @endif
                                                
                                                {{-- Botão Excluir --}}
                                                <form action="{{ route('instances.destroy', $instance->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir permanentemente esta conexão? Esta ação não pode ser desfeita.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="flex items-center space-x-2 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-xs font-semibold" 
                                                            title="Excluir Conexão">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span>Excluir</span>
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-6">Nenhuma conexão criada.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>