<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Instâncias') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Visão geral das instâncias</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Instância</th>
                                    <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário proprietário</th>
                                    <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Hotmart Webhook</th>
                                    <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Assistente</th>
                                    <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Status da instância</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                @forelse ($instances as $instance)
                                    @php
                                        $ownerEmail = optional($instance->user)->email;
                                        $webhook = $ownerEmail ? $hotmartWebhooks->get($ownerEmail) : null;
                                        $assistant = $instance->assistente;
                                    @endphp
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-4 px-6">
                                            <div class="text-sm font-semibold text-gray-900">#{{ $instance->id }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ $instance->name ?? '—' }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ $instance->credential_id ?? '—' }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="text-sm font-semibold text-gray-900">{{ optional($instance->user)->name ?? '—' }}</div>
                                            <div class="text-xs text-gray-500">{{ optional($instance->user)->email ?? '—' }}</div>
                                            <div class="text-xs text-gray-500">{{ optional($instance->user)->mobile_phone ?? '—' }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            @if ($webhook)
                                                <div class="text-sm font-semibold text-gray-900">{{ $webhook->event }}</div>
                                                <div class="text-xs text-gray-500">Status: {{ $webhook->status }}</div>
                                                <div class="text-xs text-gray-500">Oferta: {{ $webhook->offer_code }}</div>
                                                <div class="text-xs text-gray-500">{{ $webhook->transaction }}</div>
                                            @else
                                                <span class="text-sm text-gray-400">Sem registro válido</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6">
                                            @if ($assistant)
                                                <div class="text-sm font-semibold text-gray-900">{{ $assistant->name }}</div>
                                                <div class="text-xs text-gray-500">ID: {{ $assistant->id }}</div>
                                            @else
                                                <span class="text-sm text-gray-400">Sem assistente default</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="text-sm font-semibold text-gray-900">{{ $instance->status ?? '—' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-6 text-gray-500">
                                            Nenhuma instância encontrada.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $instances->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
