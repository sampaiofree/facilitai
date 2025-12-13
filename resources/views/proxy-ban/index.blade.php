<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Proxy IP Ban') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="p-6">
                    @if (session('status'))
                        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">IPs bloqueados</h3>
                            <p class="text-sm text-gray-600">Lista de IPs que não devem ser reutilizados em novas instâncias.</p>
                        </div>
                        <span class="text-sm text-gray-500">Total: {{ $bans->total() }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">IP</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Criado em</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($bans as $ban)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-800 font-semibold">{{ $ban->ip }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $ban->created_at }}</td>
                                        <td class="px-4 py-2">
                                            <form method="POST" action="{{ route('proxy-ban.destroy', $ban) }}" onsubmit="return confirm('Remover este IP da lista?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center rounded border border-red-200 px-3 py-1 text-sm font-medium text-red-600 hover:bg-red-50">
                                                    Remover
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-center text-gray-500">Nenhum IP banido.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $bans->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
