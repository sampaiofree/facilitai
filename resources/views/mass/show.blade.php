<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detalhes da Campanha') }}
        </h2>
        <a href="{{ route('mass.historico') }}"
               class="text-sm text-purple-600 hover:underline">← Voltar ao Histórico</a>
    </x-slot>
    <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Informações gerais -->
        <div class="bg-white shadow rounded-lg p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700">
                <div>
                    <p><span class="font-semibold">Nome:</span> {{ $campanha->nome }}</p>
                    <p><span class="font-semibold">Instância:</span> {{ $campanha->instance->name ?? '—' }}</p>
                    <p><span class="font-semibold">Tipo de Envio:</span> {{ ucfirst($campanha->tipo_envio) }}</p>
                    <p><span class="font-semibold">Usa IA:</span> {{ $campanha->usar_ia ? 'Sim' : 'Não' }}</p>
                </div>
                <div>
                    <p><span class="font-semibold">Intervalo:</span> {{ $campanha->intervalo_segundos }}s</p>
                    <p><span class="font-semibold">Total de Contatos:</span> {{ $campanha->total_contatos }}</p>
                    <p><span class="font-semibold">Enviados:</span> <span class="text-green-600 font-semibold">{{ $campanha->enviados }}</span></p>
                    <p><span class="font-semibold">Falhas:</span> <span class="text-red-600 font-semibold">{{ $campanha->falhas }}</span></p>
                </div>
            </div>

            <div class="mt-4">
                <p><span class="font-semibold">Mensagem:</span></p>
                <div class="bg-gray-50 border rounded p-3 mt-1 text-gray-700 text-sm whitespace-pre-wrap">
                    {{ $campanha->mensagem }}
                </div>
            </div>

            <div class="mt-4">
                <span class="font-semibold">Status:</span>
                @if($campanha->status === 'executando')
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">Executando</span>
                @elseif($campanha->status === 'concluido')
                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Concluída</span>
                @elseif($campanha->status === 'pausado')
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">Pausada</span>
                @else
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Pendente</span>
                @endif
            </div>
        </div>

        <!-- Lista de contatos -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full text-sm text-gray-700">
                <thead class="bg-gray-100 text-xs uppercase text-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Chat</th>
                        <th class="px-4 py-3 text-left">Número</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Tentativas</th>
                        <th class="px-4 py-3 text-center">Enviado em</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campanha->contatos as $i => $contato)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $i + 1 }}</td>
                            <td class="px-4 py-2">
                                @if($contato->chat)
                                    <div class="font-semibold text-gray-900">{{ $contato->chat->nome ?? 'Sem nome' }}</div>
                                    <div class="text-xs text-gray-500">{{ $contato->chat->contact }}</div>
                                    @if($contato->chat->conv_id)
                                        <div class="text-xs text-gray-400">Conv: {{ $contato->chat->conv_id }}</div>
                                    @endif
                                @else
                                    <span class="text-gray-400 text-sm">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 font-medium">{{ $contato->numero }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($contato->status === 'enviado')
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Enviado</span>
                                @elseif($contato->status === 'falhou')
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Falhou</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">Pendente</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">{{ $contato->tentativa }}</td>
                            <td class="px-4 py-2 text-center">
                                {{ $contato->enviado_em ? \Carbon\Carbon::parse($contato->enviado_em)->format('d/m/Y H:i:s') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                Nenhum contato encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</x-app-layout>
