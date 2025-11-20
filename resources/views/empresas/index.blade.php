<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buscar Empresas') }}
        </h2>
    </x-slot>
    <div 
    x-data="buscarEmpresas()"
    class="max-w-6xl mx-auto py-10 px-4"
>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Buscar Empresas</h1>

    <!-- Formulário -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <input type="text" x-model="segmento" placeholder="Ex: Petshop, Farmácia..." class="border-gray-300 rounded-lg p-2 w-full" />
        <input type="text" x-model="cidade" placeholder="Cidade" class="border-gray-300 rounded-lg p-2 w-full" />
        <input type="text" x-model="estado" placeholder="Estado (Ex: SP)" class="border-gray-300 rounded-lg p-2 w-full" />
    </div>

    <!-- Botões -->
    <div class="flex gap-3 mb-8">
        <button 
            @click="buscar"
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg flex items-center gap-2"
            x-bind:disabled="carregando"
        >
            <template x-if="!carregando">
                <span>Buscar</span>
            </template>
            <template x-if="carregando">
                <span class="animate-pulse">Buscando...</span>
            </template>
        </button>

        <button 
            @click="baixarCSV"
            class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg"
        >
            Baixar CSV
        </button>
    </div>

    <!-- Tabela -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-700">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="py-3 px-4">Nome</th>
                    <th class="py-3 px-4">Telefone</th>
                    <th class="py-3 px-4">Website</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="empresas.length === 0 && !carregando">
                    <tr>
                        <td colspan="2" class="text-center py-6 text-gray-500">Nenhum resultado encontrado.</td>
                    </tr>
                </template>

                <template x-for="(empresa, index) in empresas" :key="index">
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4" x-text="empresa.nome"></td>
                        <td class="py-3 px-4" x-text="empresa.telefone || '—'"></td>
                        <td class="py-3 px-4">
                            <template x-if="empresa.website">
                                <a :href="empresa.website" target="_blank" class="text-blue-600 hover:underline" x-text="empresa.website"></a>
                            </template>
                            <template x-if="!empresa.website">
                                <span>—</span>
                            </template>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    function buscarEmpresas() {
        return {
            segmento: '',
            cidade: '',
            estado: '',
            empresas: [],
            carregando: false,

            async buscar() {
                if (!this.segmento || !this.cidade || !this.estado) {
                    showAlert('Preencha todos os campos', 'warning');
                    return;
                }

                this.carregando = true;
                this.empresas = [];

                try {
                    const response = await fetch('{{ route('empresas.buscar') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        },
                        body: JSON.stringify({
                            segmento: this.segmento,
                            cidade: this.cidade,
                            estado: this.estado
                        })
                    });

                    const data = await response.json();
                    this.empresas = data.empresas || [];
                } catch (e) {
                    showAlert('Erro ao buscar empresas', 'error');
                } finally {
                    this.carregando = false;
                }
            },

            baixarCSV() {
                if (this.empresas.length === 0) {
                    showAlert('Nenhum dado para exportar', 'warning');
                    return;
                }

                let csv = 'Nome,Telefone,Website\n';
                this.empresas.forEach(e => {
                    const nome = (e.nome || '').replace(/"/g, '""');
                    const telefone = (e.telefone || '').replace(/"/g, '""');
                    const website = (e.website || '').replace(/"/g, '""');
                    csv += `"${nome}","${telefone}","${website}"\n`;
                });

                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'empresas.csv';
                a.click();
                showAlert('Lista baixada com sucesso!', 'success');
            }

        };
    }
</script>
@endpush
</x-app-layout>
