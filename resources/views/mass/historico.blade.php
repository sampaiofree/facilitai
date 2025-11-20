<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center mb-6">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Histórico de Campanhas') }}
        </h2>
        <a href="{{ route('mass.index') }}" 
        class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow">
            + Nova Campanha
        </a>
        </div>
    </x-slot>
    <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm text-gray-700">
                <thead class="bg-gray-100 text-gray-900 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Nome</th>
                        <th class="px-4 py-3 text-center">Total</th>
                        <th class="px-4 py-3 text-center">Enviados</th>
                        <th class="px-4 py-3 text-center">Falhas</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campanhas as $campanha)
                        <tr class="border-t">
                            <td class="px-4 py-3 font-medium">{{ $campanha->nome }}</td>
                            <td class="px-4 py-3 text-center">{{ $campanha->total_contatos }}</td>
                            <td class="px-4 py-3 text-center text-green-600 font-semibold">{{ $campanha->enviados }}</td>
                            <td class="px-4 py-3 text-center text-red-600 font-semibold">{{ $campanha->falhas }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($campanha->status === 'executando')
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">Executando</span>
                                @elseif($campanha->status === 'concluido')
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Concluída</span>
                                @elseif($campanha->status === 'pausado')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">Pausada</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Pendente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('mass.show', $campanha->id) }}"
                                   class="text-purple-600 hover:underline">Ver Detalhes</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                Nenhuma campanha encontrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $campanhas->links() }}
        </div>
    </div>
    </div>
</x-app-layout>
