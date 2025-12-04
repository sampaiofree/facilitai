<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Histórico da conversa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div class="text-sm text-gray-500">Conv ID</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $chat->conv_id }}</div>
                        <div class="text-sm text-gray-600 mt-1">
                            Contato: {{ $chat->contact }} @if($chat->nome) — {{ $chat->nome }} @endif
                        </div>
                        <div class="text-sm text-gray-600">
                            Instância: {{ $chat->instance->name ?? '-' }} | Assistente: {{ $chat->assistant->name ?? $chat->assistente->name ?? '-' }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('chats.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition">
                            ← Voltar para chats
                        </a>
                    </div>
                </div>

                @if($hasMore)
                    <div class="mt-4 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md p-3">
                        Existem mensagens mais antigas não carregadas (has_more = true). Aumente o limite na URL se precisar (parâmetro `?limit=`).
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-sm text-gray-500">Mensagens carregadas: {{ $messages->count() }} / limite {{ $limit }}</div>
                    <div class="flex items-center gap-2 text-sm">
                        @if($hasMore && $lastId)
                            <a href="{{ route('chats.conv', ['conv' => $chat->conv_id, 'limit' => $limit, 'after' => $lastId]) }}"
                               class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 transition">
                                Ver mais antigas
                            </a>
                        @endif
                        @if($after || $before)
                            <a href="{{ route('chats.conv', ['conv' => $chat->conv_id, 'limit' => $limit]) }}"
                               class="inline-flex items-center px-3 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition">
                                Voltar para mais recentes
                            </a>
                        @endif
                    </div>
                </div>

                @if($messages->isEmpty())
                    <div class="text-center text-gray-500 py-10">
                        Nenhuma mensagem encontrada para esta conversa.
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($messages as $message)
                            @php
                                $role = $message['role'] ?? 'system';
                                $isUser = $role === 'user';
                                $isAssistant = $role === 'assistant';
                                $bubbleClasses = match(true) {
                                    $isUser => 'bg-emerald-500 text-white',
                                    $isAssistant => 'bg-gray-100 text-gray-900',
                                    default => 'bg-sky-50 text-sky-900'
                                };
                                $alignClass = $isUser ? 'ml-auto text-right' : 'mr-auto text-left';
                            @endphp
                            <div class="max-w-3xl {{ $alignClass }}">
                                <div class="inline-block w-full sm:w-auto px-4 py-3 rounded-2xl shadow-sm {{ $bubbleClasses }}">
                                    <div class="text-xs opacity-80 mb-1 uppercase tracking-wide">
                                        {{ $role }}
                                    </div>
                                    <div class="whitespace-pre-wrap text-sm leading-relaxed">
                                        {{ $message['text'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
