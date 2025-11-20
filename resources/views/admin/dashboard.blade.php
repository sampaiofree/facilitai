<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Painel Administrativo') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="dashboardUsers()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Card de Estatísticas -->
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-2">Visão Geral</h3>
                    <p class="text-2xl font-bold">{{ $totalUsers }}</p>
                    <p class="text-sm text-gray-500">Total de Usuários Cadastrados</p>
                </div>
            </div>

            <!-- Botão CSV -->
            <div class="flex justify-end mb-4">
                <button 
                    @click="baixarCSV"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow"
                >
                    ⬇️ Baixar CSV
                </button>
            </div>

            <!-- Tabela de Usuários -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Detalhes por Usuário</h3>
                    <div class="overflow-x-auto">
                        <table id="users-table" class="min-w-full w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left py-3 px-6">ID</th>
                                    <th class="text-left py-3 px-6">Cadastro</th>
                                    <th class="text-left py-3 px-6">customer_asaas_id</th>
                                    <th class="text-left py-3 px-6">Usuário</th>
                                    <th class="text-left py-3 px-6">WhatsApp</th>
                                    <th class="text-left py-3 px-6">Plano</th>
                                    <th class="text-right py-3 px-6">Tokens Comprados</th>
                                    <th class="text-right py-3 px-6">Tokens Bônus</th>
                                    <th class="text-right py-3 px-6">Tokens Usados</th>
                                    <th class="text-right py-3 px-6">Tokens Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                @forelse ($users as $user)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-4 px-6">{{ $user->id }}</td>
                                        <td class="py-4 px-6">{{ $user->created_at }}</td>
                                        <td class="py-4 px-6">{{ $user->customer_asaas_id }}</td>
                                        <td class="py-4 px-6">{{ $user->name }}</td>
                                        <td class="py-4 px-6">{{ $user->mobile_phone }}</td>
                                        <td class="py-4 px-6">{{ $user->hotmartWebhooks()->offer_code ?? '' }}</td>
                                        <td class="py-4 px-6 text-right font-mono">{{ number_format($user->totalTokens()) }}</td>
                                        <td class="py-4 px-6 text-right font-mono">{{ number_format($user->tokensBonusValidos()) }}</td>
                                        <td class="py-4 px-6 text-right font-mono">{{ number_format($user->totalTokensUsed()) }}</td>
                                        <td class="py-4 px-6 text-right font-mono">{{ number_format($user->tokensAvailable()) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-6 text-gray-500">
                                            Nenhum usuário encontrado.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-6">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        function dashboardUsers() {
            return {
                baixarCSV() {
                    const table = document.getElementById('users-table');
                    if (!table) {
                        window.showAlert?.('Tabela não encontrada', 'error');
                        return;
                    }

                    // Cabeçalhos
                    const headers = Array.from(table.querySelectorAll('thead th'))
                        .map(th => th.textContent.trim());

                    // Linhas
                    const rows = Array.from(table.querySelectorAll('tbody tr'))
                        .map(tr => Array.from(tr.querySelectorAll('td')).map(td => {
                            return (td.textContent || '')
                                .replace(/\r?\n|\r/g, ' ')   // remove quebras de linha
                                .replace(/"/g, '""')         // escapa aspas
                                .trim();
                        }));

                    if (!rows.length) {
                        window.showAlert?.('Nenhum dado para exportar', 'warning');
                        return;
                    }

                    // Monta CSV (com BOM p/ Excel abrir certo)
                    let csv = '\uFEFF';
                    csv += headers.map(h => `"${h.replace(/"/g, '""')}"`).join(',') + '\n';
                    rows.forEach(cols => {
                        csv += cols.map(v => `"${v}"`).join(',') + '\n';
                    });

                    // Download
                    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'usuarios.csv';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);

                    window.showAlert?.('Arquivo CSV baixado com sucesso!', 'success');
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
