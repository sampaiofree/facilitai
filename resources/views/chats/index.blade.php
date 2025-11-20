<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gerenciamento de Conversas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- ================================================ --}}
                    {{-- ADICIONE O FORMULÁRIO DE PESQUISA AQUI --}}
                    {{-- ================================================ --}}
                    <div class="mb-6">
                        <form action="{{ route('chats.index') }}" method="GET" class="flex items-center space-x-2">
                            <input type="text" name="search" 
                                   placeholder="Pesquisar por número..." 
                                   value="{{ $search ?? '' }}" 
                                   class="w-full md:w-1/3 border-gray-300 rounded-md shadow-sm">
                            <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-md">
                                Pesquisar
                            </button>
                            @if ($search)
                                <a href="{{ route('chats.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Limpar</a>
                            @endif
                        </form>
                    </div>
                    {{-- ================================================ --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left py-3 px-6">Contato (WhatsApp)</th>
                                    <th class="text-left py-3 px-6">Assistente Vinculado</th>
                                    <th class="text-center py-3 px-6">Status do Bot</th>
                                    <th class="text-center py-3 px-6">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($chats as $chat)
                                    <tr class="border-b">
                                        <td class="py-4 px-6">{{ $chat->contact }}</td>
                                        <td class="py-4 px-6 font-mono text-xs">{{ $chat->assistente?->name }}</td> 
                                        <td class="py-4 px-6 text-center">
                                            {{-- Formulário para Ligar/Desligar o Bot --}}
                                            <form action="{{ route('chats.update', $chat) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                @if ($chat->bot_enabled)
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
                                                        Ativo
                                                    </button>
                                                @else
                                                    <button type="submit" class="inline-flex items-center px-3 py-1 bg-red-500 text-white text-xs font-semibold rounded-full">
                                                        Inativo
                                                    </button>
                                                @endif
                                            </form>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            {{-- Formulário para Excluir --}}
                                            <form action="{{ route('chats.destroy', $chat) }}" method="POST" class="delete-chat-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-6">Nenhuma conversa iniciada ainda.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $chats->links() }} {{-- Links da Paginação --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
// Usa a mesma lógica que já fizemos para os assistentes para confirmar a exclusão
document.addEventListener('DOMContentLoaded', function() {
    const deleteForms = document.querySelectorAll('form.delete-chat-form');
    
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Tem certeza?',
                text: `Isso excluirá o histórico de conversa (thread ID) com este contato. Ele começará uma nova conversa na próxima mensagem.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });
});
</script>
@endpush
</x-app-layout>