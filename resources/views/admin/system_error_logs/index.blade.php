@php
    use Illuminate\Support\Str;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Logs de Erros') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="grid gap-4 md:grid-cols-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contexto</label>
                        <input type="text" name="context" value="{{ $filters['context'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Instancia</label>
                        <input type="text" name="instance_id" value="{{ $filters['instance_id'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Conversation ID</label>
                        <input type="text" name="conversation_id" value="{{ $filters['conversation_id'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Funcao</label>
                        <input type="text" name="function_name" value="{{ $filters['function_name'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mensagem</label>
                        <input type="text" name="message" value="{{ $filters['message'] ?? '' }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="md:col-span-5 flex items-end gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Filtrar
                        </button>
                        <a href="{{ route('admin.system-error-logs.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Limpar</a>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contexto</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instancia</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conversa</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Funcao</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mensagem</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payload</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 text-sm text-gray-700">
                            @forelse ($logs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap">{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->context ?? '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->instance_id ?? '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->user_id ?? '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->conversation_id ?? '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $log->function_name ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ Str::limit($log->message ?? '-', 120) }}</td>
                                    <td class="px-4 py-3 text-xs">
                                        <details>
                                            <summary class="cursor-pointer text-indigo-600">Ver</summary>
                                            <pre class="mt-2 bg-gray-50 p-2 rounded border overflow-x-auto">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">Nenhum registro encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
