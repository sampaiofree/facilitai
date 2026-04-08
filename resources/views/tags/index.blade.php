<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tags') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @if (session('success'))
                    <div class="rounded-md bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
                @endif
                @if (session('warning'))
                    <div class="rounded-md bg-yellow-50 px-4 py-3 text-yellow-800">{{ session('warning') }}</div>
                @endif

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p class="font-semibold">Tela legada em modo somente leitura</p>
                    <p class="mt-1">As tags globais antigas continuam visíveis aqui apenas para diagnóstico temporário. Novas tags devem ser criadas vinculadas a um cliente.</p>
                    <a href="{{ route('agencia.tags.index') }}" class="mt-3 inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Ir para tags por cliente
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criada em</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($tags as $tag)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $tag->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $tag->description ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $tag->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">
                                        Nenhuma tag global legada encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
