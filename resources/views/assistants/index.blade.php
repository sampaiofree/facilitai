<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Meus Assistentes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    {{-- Cabeçalho da página: Ações e Filtros --}}
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">

                        {{-- Botões --}}
                        <div class="flex-1 text-right">
                            @if ($availableSlots > 0)
                                <div class="flex items-center justify-end space-x-4">
                                    <span class="text-sm text-gray-600">
                                        @if($availableSlots > 9000)
                                            Slots: <span class="font-bold text-green-600">Ilimitados</span>
                                        @else
                                            Slots disponíveis: <span class="font-bold text-green-600">{{ $availableSlots }}</span>
                                        @endif
                                    </span>
                                    <a href="{{ route('assistants.builder') }}" class="inline-block bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg">
                                        Criar Novo Assistente
                                    </a>
                                </div>
                            @else
                                <div class="p-4 bg-yellow-50 border text-yellow-800 rounded-lg text-left">
                                    <p class="font-semibold">Você não tem mais slots disponíveis.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Tabela de Assistentes (renderizada diretamente com Blade) --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left py-3 px-6">Nome</th>
                                    <!--<th class="text-left py-3 px-6">Credencial Usada</th>-->
                                    <th class="text-left py-3 px-6">Tempo de Resposta</th>
                                    <!--<th class="text-left py-3 px-6">Modelo</th>-->
                                    <th class="text-center py-3 px-6">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="assistants-tbody">
                                @forelse ($assistants as $assistant)
                                    {{-- O 'data-credential-id' é para o filtro do JS --}}
                                    <tr class="border-b" data-credential-id="{{ $assistant->credential_id ?? 'global' }}">
                                        <td class="py-4 px-6">{{ $assistant->name }}</td>
                                        <!--<td class="py-4 px-6 text-sm text-gray-600">
                                            {{-- Mostra o label da credencial ou "Padrão do Sistema" --}}
                                            {{ $assistant->credential->label ?? 'Tokens' }}
                                        </td>-->
                                        <td class="py-4 px-6 font-mono text-xs">{{ $assistant->delay }} seg</td>
                                        <!--<td class="py-4 px-6 font-mono text-xs">{{ $assistant->modelo ?? 'gpt-4.1-mini' }}</td>-->
                                        <td class="py-4 px-6 text-center">
                                            <div class="flex items-center justify-center space-x-4">
                                            <a href="{{ route('assistants.edit', $assistant) }}" class="flex items-center space-x-2 px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-xs font-semibold">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                                        </svg>
                                                        Editar</a>
                                            {{-- O data-id e a classe são para o JS de exclusão --}}
                                            <form action="{{ route('assistants.destroy', $assistant) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="flex items-center space-x-2 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-xs font-semibold"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>Excluir</button> 
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-6 text-gray-500">Nenhum assistente criado ainda.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(Auth::user()->canManageCredentials()) //SÓ FUNCIONA SE O USUÁRIO E DO PLANO AGENCIA
    const selector = document.getElementById('credential_selector');
    const tableBody = document.getElementById('assistants-tbody');
    const allRows = tableBody.querySelectorAll('tr');

    // Lógica do Filtro (só funciona se o seletor existir)
    if (selector) {
        selector.addEventListener('change', function() {
            const selectedCredentialId = this.value;

            allRows.forEach(row => {
                if (selectedCredentialId === '' || selectedCredentialId === 'all' || row.dataset.credentialId === selectedCredentialId) {
                    row.style.display = ''; // Mostra a linha
                } else {
                    row.style.display = 'none'; // Esconde a linha
                }
            });
        });
    } 
    @endif

    // Lógica de Confirmação para Exclusão
    const deleteForms = document.querySelectorAll('form');
    deleteForms.forEach(form => {
        // Verifica se o formulário é de exclusão
        if (form.querySelector('button.delete-assistant-btn')) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Impede o envio imediato
                
                Swal.fire({
                    title: 'Tem certeza?',
                    text: `Você está prestes a excluir este assistente. Esta ação não pode ser revertida.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit(); // Envia o formulário se o usuário confirmar
                    }
                });
            });
        }
    });
});
</script>
@endpush
</x-app-layout>