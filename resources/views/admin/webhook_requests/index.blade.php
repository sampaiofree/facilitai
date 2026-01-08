@php
    use Illuminate\Support\Str;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Logs de Webhook') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="grid gap-4 md:grid-cols-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Instância</label>
                        <input type="text" name="instance_id" value="{{ $filters['instance_id'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contato</label>
                        <input type="text" name="contact" value="{{ $filters['contact'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo</label>
                        <input type="text" name="message_type" value="{{ $filters['message_type'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Event ID</label>
                        <input type="text" name="event_id" value="{{ $filters['event_id'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">From me?</label>
                        <select name="from_me" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option value="1" @selected(($filters['from_me'] ?? '') === '1')>Sim</option>
                            <option value="0" @selected(($filters['from_me'] ?? '') === '0')>Não</option>
                        </select>
                    </div>
                    <div class="md:col-span-5 flex items-end gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Filtrar
                        </button>
                        <a href="{{ route('admin.webhook-requests.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Limpar</a>
                    </div>
                </form>
                <div class="mt-4 flex justify-end">
                    <form method="POST" action="{{ route('admin.webhook-requests.destroyAll') }}" onsubmit="return confirm('Tem certeza que deseja excluir todos os logs?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Excluir todos
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instância</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contato</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From me</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Texto</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payload</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm text-gray-700">
                            @forelse ($requests as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap">{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->instance_id ?? '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-medium">{{ $log->contact ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $log->remote_jid }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->from_me ? 'Sim' : 'Não' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->message_type ?? '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->event_id ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        {{ Str::limit($log->message_text ?? data_get($log->payload, 'data.message.conversation'), 80) ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <details>
                                            <summary class="cursor-pointer text-indigo-600">Ver</summary>
                                            <pre class="mt-2 bg-gray-50 p-2 rounded border overflow-x-auto">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <form method="POST" action="{{ route('admin.webhook-requests.destroy', $log) }}" onsubmit="return confirm('Excluir este registro?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-3 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                                Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-gray-500">Nenhum registro encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
