<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lista de Empresas (Leads)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <table class="min-w-full w-full bg-white">
                        <thead>
                            <tr>
                                <th class="text-left py-3 px-6">Nome</th>
                                <th class="text-left py-3 px-6">Segmento</th>
                                <th class="text-left py-3 px-6">Telefone</th>
                                <th class="text-left py-3 px-6">Cidade/Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leads as $lead)
                                <tr class="border-b">
                                    <td class="py-4 px-6">{{ $lead->nome }}</td>
                                    <td class="py-4 px-6">{{ $lead->segmento }}</td>
                                    <td class="py-4 px-6">{{ $lead->telefone }}</td>
                                    <td class="py-4 px-6">{{ $lead->cidade }} / {{ $lead->estado }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-6">Nenhum lead encontrado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        {{ $leads->links() }} {{-- Links da Paginação --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>