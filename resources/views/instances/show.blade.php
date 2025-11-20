<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gerenciar Conexão: {{ $instance->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900" id="connection-container" data-instance-id="{{ $instance->id }}">
                    {{-- O conteúdo do status será inserido aqui pelo JavaScript --}}
                    <div id="status-content">
                        <p>Verificando status inicial...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- ========== Início do Modal de Conexão (Novo Layout) ========== -->
<div id="qr-modal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center p-4 hidden">
    
    {{-- Card do Modal --}}
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl relative transform transition-all duration-300 ease-out">
        
        {{-- Botão de Fechar (X no canto) --}}
        <button id="close-modal-btn" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Layout Grid (1 coluna no mobile, 2 no desktop) --}}
        <div class="grid grid-cols-1 md:grid-cols-2">

            <!-- Coluna da Esquerda: Instruções -->
            <div class="p-8 border-r border-gray-100">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Passos para Conectar</h3>
                
                <ol class="list-decimal list-inside space-y-4 text-gray-600">
                    <li>Abra o aplicativo do **WhatsApp** no seu celular.</li>
                    <li>
                        No <span class="font-semibold">Iphone</span>, selecione <span class="font-semibold">Configurações</span>.
                        <br>
                        No <span class="font-semibold">Android</span>, selecione <span class="font-semibold">Mais opções</span> (⋮).
                    </li>
                    <li>Agora selecione <span class="font-semibold">Aparelhos conectados</span> e depois <span class="font-semibold">Conectar um aparelho</span>.</li>
                    <li>Aponte a câmera do seu celular para o QR code ao lado.</li>
                </ol>

                <div class="mt-8 bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg text-center">
                    <p class="font-semibold">Agora é só aguardar conectar! 🚀</p>
                </div>
            </div>

            <!-- Coluna da Direita: QR Code -->
            <div class="p-8 flex flex-col items-center justify-center bg-gray-50 rounded-r-2xl">
                <h4 class="text-lg font-medium text-gray-700 mb-4">Escaneie para Conectar</h4>
                
                <div id="qrcode-image-container" class="w-64 h-64 flex items-center justify-center bg-white rounded-lg border border-dashed">
                    {{-- O JavaScript vai inserir o QR Code aqui, ou mostrar este estado de loading --}}
                    <div class="text-center text-gray-400">
                        <svg class="animate-spin h-10 w-10 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p>Gerando QR Code...</p>
                    </div>
                </div>

                <p class="mt-4 text-sm text-gray-500">O código será atualizado automaticamente.</p>
            </div>

        </div>
    </div>
</div>
<!-- ========== Fim do Modal ========== -->

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    console.log('Script iniciado!');
    const container = document.getElementById('connection-container');
    const statusContent = document.getElementById('status-content');
    const instanceId = container.dataset.instanceId;

    console.log('ID da Instância:', instanceId); 

    const modal = document.getElementById('qr-modal');
    const qrCodeContainer = document.getElementById('qrcode-image-container');
    const closeModalBtn = document.getElementById('close-modal-btn');

    let qrRefreshInterval = null; // Timer para o QR Code
    let statusCheckInterval = null; // Timer para o status geral

    // Função para buscar e mostrar o QR Code no modal
    const fetchQrCode = async () => {
        try {
            const response = await fetch(`/instances/${instanceId}/qrcode-data`);
            const data = await response.json();
            if (data.base64) {
                qrCodeContainer.innerHTML = `<img src="${data.base64}" alt="QR Code" class="mx-auto border">`;
            } else {
                qrCodeContainer.innerHTML = `<p class="text-red-500">Erro ao gerar QR Code.</p>`;
            }
        } catch (error) {
            console.error('Error fetching QR Code:', error);
            qrCodeContainer.innerHTML = `<p class="text-red-500">Erro de conexão ao buscar QR Code.</p>`;
        }
    };

    // Função para checar o status da conexão
    const checkConnectionStatus = async () => {
        try {
            const response = await fetch(`/instances/${instanceId}/status-data`);
            const data = await response.json();

            if (data.instance && data.instance.state === 'open') {
                // SUCESSO! A instância está conectada
                statusContent.innerHTML = `
                    <div class="p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        <p class="font-bold">Conectado!</p>
                        <p>Seu WhatsApp está conectado. Agora vá no menu instancias e conecte do assistente.</p>
                    </div>`;
                closeModal(); // Fecha o modal se estiver aberto
            } else {
                // Ainda não está conectado, mostra o botão
                statusContent.innerHTML = `
                    <div class="p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded flex items-center justify-between">
                        <div><p class="font-bold">Desconectado</p><p>A instância não está conectada ao WhatsApp.</p></div>
                        <div><button id="connect-btn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Conectar / Gerar QR Code</button></div>
                    </div>`;
                // Adiciona o evento de clique ao botão que acabamos de criar
                document.getElementById('connect-btn').addEventListener('click', openModal);
            }
        } catch (error) {
            console.error('Error checking status:', error);
            statusContent.innerHTML = `<p class="text-red-500">Não foi possível verificar o status da conexão.</p>`;
        }
    };

    const openModal = () => {
        modal.classList.remove('hidden'); // Mostra o modal
        fetchQrCode(); // Busca o primeiro QR Code
        // Inicia o timer para atualizar o QR Code a cada 15 segundos
        qrRefreshInterval = setInterval(fetchQrCode, 15000);
    };

    const closeModal = () => {
        modal.classList.add('hidden'); // Esconde o modal
        clearInterval(qrRefreshInterval); // PARA o timer do QR Code
        qrRefreshInterval = null;
    };

    // Eventos dos botões do modal
    closeModalBtn.addEventListener('click', closeModal);

    // Inicia o processo
    checkConnectionStatus(); // Verifica o status ao carregar a página
    // E continua verificando o status a cada 7 segundos
    statusCheckInterval = setInterval(checkConnectionStatus, 10000);
});
</script>
@endpush
</x-app-layout>