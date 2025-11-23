<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gerenciamento de Conversas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    @if (session('success'))
                        <div class="px-4 py-3 bg-green-100 text-green-800 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('warning'))
                        <div class="px-4 py-3 bg-yellow-100 text-yellow-800 rounded-md">
                            {{ session('warning') }}
                        </div>
                    @endif

                    <div class="rounded-lg border border-gray-100 bg-gray-50/60 p-4 space-y-4">
                        <form action="{{ route('chats.index') }}" method="GET" class="space-y-4" id="filtersForm">
                            <div class="flex flex-wrap items-end gap-3">
                                <div class="flex-1 min-w-[220px]">
                                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Busca</label>
                                    <div class="relative mt-1">
                                        <input type="text" name="search" placeholder="Conv ID, contato ou nome"
                                            value="{{ $filters['search'] ?? '' }}"
                                            class="w-full border-gray-200 focus:border-blue-500 focus:ring-blue-500 rounded-md px-4 py-2 pl-10">
                                        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-sm">🔍</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Ordenar</label>
                                    <select name="order" class="mt-1 w-full border-gray-200 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="updated_at_desc" {{ $filters['order'] === 'updated_at_desc' ? 'selected' : '' }}>Atualização ↓</option>
                                        <option value="updated_at_asc" {{ $filters['order'] === 'updated_at_asc' ? 'selected' : '' }}>Atualização ↑</option>
                                        <option value="created_at_desc" {{ $filters['order'] === 'created_at_desc' ? 'selected' : '' }}>Criação ↓</option>
                                        <option value="created_at_asc" {{ $filters['order'] === 'created_at_asc' ? 'selected' : '' }}>Criação ↑</option>
                                    </select>
                                </div>
                                
                                <button type="button" id="toggleFilters" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                                <!-- Ícone de Funil (Vazado) -->
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                                </svg>
                                Filtros
                            </button>
                                <div class="ml-auto flex gap-2">
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-white font-semibold shadow hover:bg-blue-500 transition-colors">
                                    <!-- Ícone de Ajustes com cor personalizada (azul claro) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-blue-200">
                                        <path d="M10 3.75a2 2 0 10-4 0 2 2 0 004 0zM17.25 4.5a.75.75 0 000-1.5h-5.5a.75.75 0 000 1.5h5.5zM5 3.75a.75.75 0 01-.75.75h-1.5a.75.75 0 010-1.5h1.5a.75.75 0 01.75.75zM4.25 17a.75.75 0 000-1.5h-1.5a.75.75 0 000 1.5h1.5zM17.25 17a.75.75 0 000-1.5h-5.5a.75.75 0 000 1.5h5.5zM9 10a.75.75 0 01-.75.75h-5.5a.75.75 0 010-1.5h5.5A.75.75 0 019 10zM17.25 10.75a.75.75 0 000-1.5h-1.5a.75.75 0 000 1.5h1.5zM14 10a2 2 0 10-4 0 2 2 0 004 0zM10 16.25a2 2 0 10-4 0 2 2 0 004 0z" />
                                    </svg>
                                    
                                    Aplicar filtros
                                </button>
                                    <a href="{{ route('chats.index') }}"
                                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-white">
                                        Limpar
                                    </a>
                                </div>
                            </div>

                            <div id="filtersPanel" class="hidden border-t border-gray-200 pt-4 space-y-4">
                                <div class="grid gap-4 lg:grid-cols-2">
                                    <div class="rounded-lg border border-gray-100 bg-white p-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-semibold text-gray-800">Instância</h4>
                                        </div>
                                        <div class="mt-3 grid grid-cols-2 gap-3">
                                            <div>
                                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">É</p>
                                                <div class="space-y-1 max-h-32 overflow-auto pr-1">
                                                    @foreach ($instances as $instance)
                                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                                            <input type="checkbox" name="instance_in[]" value="{{ $instance->id }}"
                                                                {{ in_array($instance->id, $filters['instance_in']) ? 'checked' : '' }}
                                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                            {{ $instance->name }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Não é</p>
                                                <div class="space-y-1 max-h-32 overflow-auto pr-1">
                                                    @foreach ($instances as $instance)
                                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                                            <input type="checkbox" name="instance_out[]" value="{{ $instance->id }}"
                                                                {{ in_array($instance->id, $filters['instance_out']) ? 'checked' : '' }}
                                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                            {{ $instance->name }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-lg border border-gray-100 bg-white p-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-semibold text-gray-800">Assistente</h4>
                                        </div>
                                        <div class="mt-3 grid grid-cols-2 gap-3">
                                            <div>
                                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">É</p>
                                                <div class="space-y-1 max-h-32 overflow-auto pr-1">
                                                    @foreach ($assistants as $assistant)
                                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                                            <input type="checkbox" name="assistant_in[]" value="{{ $assistant->id }}"
                                                                {{ in_array($assistant->id, $filters['assistant_in']) ? 'checked' : '' }}
                                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                            {{ $assistant->name }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Não é</p>
                                                <div class="space-y-1 max-h-32 overflow-auto pr-1">
                                                    @foreach ($assistants as $assistant)
                                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                                            <input type="checkbox" name="assistant_out[]" value="{{ $assistant->id }}"
                                                                {{ in_array($assistant->id, $filters['assistant_out']) ? 'checked' : '' }}
                                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                            {{ $assistant->name }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-lg border border-gray-100 bg-white p-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-semibold text-gray-800">Status</h4>
                                        </div>
                                        <div class="mt-3 grid grid-cols-2 gap-3">
                                            <div>
                                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">É</p>
                                                <div class="space-y-1">
                                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox" name="status_in[]" value="1"
                                                            {{ in_array('1', $filters['status_in']) || in_array(1, $filters['status_in']) ? 'checked' : '' }}
                                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        Aguardando
                                                    </label>
                                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox" name="status_in[]" value="0"
                                                            {{ in_array('0', $filters['status_in']) || in_array(0, $filters['status_in']) ? 'checked' : '' }}
                                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        Atendido
                                                    </label>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Não é</p>
                                                <div class="space-y-1">
                                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox" name="status_out[]" value="1"
                                                            {{ in_array('1', $filters['status_out']) || in_array(1, $filters['status_out']) ? 'checked' : '' }}
                                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        Aguardando
                                                    </label>
                                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox" name="status_out[]" value="0"
                                                            {{ in_array('0', $filters['status_out']) || in_array(0, $filters['status_out']) ? 'checked' : '' }}
                                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                        Atendido
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-lg border border-gray-100 bg-white p-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-semibold text-gray-800">Tags</h4>
                                        </div>
                                        <div class="mt-3 grid grid-cols-2 gap-3">
                                            <div>
                                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">É</p>
                                                <div class="space-y-1 max-h-32 overflow-auto pr-1">
                                                    @foreach ($tags as $tag)
                                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                                            <input type="checkbox" name="tags_in[]" value="{{ $tag->name }}"
                                                                {{ in_array($tag->name, $filters['tags_in']) ? 'checked' : '' }}
                                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                            {{ $tag->name }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Não é</p>
                                                <div class="space-y-1 max-h-32 overflow-auto pr-1">
                                                    @foreach ($tags as $tag)
                                                        <label class="flex items-center gap-2 text-sm text-gray-700">
                                                            <input type="checkbox" name="tags_out[]" value="{{ $tag->name }}"
                                                                {{ in_array($tag->name, $filters['tags_out']) ? 'checked' : '' }}
                                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                            {{ $tag->name }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-lg border border-gray-100 bg-white p-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-semibold text-gray-800">Sequências</h4>
                                        </div>
        <div class="mt-3 grid grid-cols-2 gap-3">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">É</p>
                <div class="space-y-1 max-h-32 overflow-auto pr-1">
                    @foreach ($sequences as $sequence)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="sequences_in[]" value="{{ $sequence->id }}"
                                {{ in_array($sequence->id, $filters['sequences_in']) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ $sequence->name }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Não é</p>
                <div class="space-y-1 max-h-32 overflow-auto pr-1">
                    @foreach ($sequences as $sequence)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="sequences_out[]" value="{{ $sequence->id }}"
                                {{ in_array($sequence->id, $filters['sequences_out']) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ $sequence->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
    
                        <a href="{{ route('chats.export', request()->query()) }}" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        <!-- Ícone Nuvem -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        Exportar CSV
                    </a>
                        <form id="sequence-form" method="POST" action="{{ route('chats.sequence.subscribe') }}" class="hidden">
                            @csrf
                            <input type="hidden" name="sequence_id" id="sequence_id">
                        </form>
                        <button type="button" id="open-sequence-modal" class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-purple-500">
                            <!-- Sinal de mais simples, talvez aumentando um pouco o tamanho -->
                            <span class="text-lg leading-none">+</span> 
                            Inscrever em sequência
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">
                                        <input type="checkbox" id="select-all-chats" class="h-4 w-4 text-blue-600">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nome
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Contato
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aguardando
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Sequencias
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Bot
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($chats as $chat)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <input type="checkbox" class="bulk-checkbox h-4 w-4 text-blue-600"
                                                value="{{ $chat->id }}">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900">{{ $chat->nome ?? 'Sem nome' }}</div>
                                            <p class="text-xs text-gray-500">Atualizado {{ $chat->updated_at?->diffForHumans() }}</p>
                                            @if ($chat->tags->count())
                                                <div class="mt-2 flex flex-wrap gap-1">
                                                    @foreach ($chat->tags as $tag)
                                                        <form action="{{ route('chats.tags.remove', [$chat, $tag]) }}" method="POST" class="inline-flex">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-700 hover:bg-blue-100">
                                                                {{ $tag->name }}
                                                                <span aria-hidden="true">✕</span>
                                                            </button>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-mono text-sm text-gray-800">
                                            {{ $chat->contact }}
                                            <div class="text-xs text-gray-500">{{ $chat->conv_id }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button type="button"
                                                class="awaiting-toggle px-3 py-1 text-xs font-semibold rounded-full shadow-sm transition {{ $chat->aguardando_atendimento ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-green-100 text-green-800 hover:bg-green-200' }}"
                                                data-chat-id="{{ $chat->id }}"
                                                data-current="{{ $chat->aguardando_atendimento ? '1' : '0' }}">
                                                {{ $chat->aguardando_atendimento ? 'Sim' : 'Não' }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-600 text-center">
                                            @php
                                                $sequencias = $chat->sequenceChats()->where('status', 'em_andamento')->with('sequence')->get();
                                            @endphp
                                            @if ($sequencias->isEmpty())
                                                <span class="text-gray-400">Nenhuma</span>
                                            @else
                                                <div class="flex justify-center flex-wrap gap-1">
                                                    @foreach ($sequencias as $seqChat)
                                                        <button type="button"
                                                            id="seq-chip-{{ $seqChat->id }}"
                                                            class="sequence-remove px-2 py-1 rounded-full bg-purple-50 text-purple-700 font-semibold hover:bg-purple-100 inline-flex items-center gap-1"
                                                            data-url="{{ route('sequences.chats.destroy', [$seqChat->sequence_id, $seqChat->id]) }}">
                                                            <span>{{ $seqChat->sequence?->name ?? 'Sequência' }}</span>
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button type="button"
                                                class="toggle-bot-btn inline-flex items-center rounded-full px-4 py-1 text-xs font-semibold shadow transition {{ $chat->bot_enabled ? 'bg-emerald-500 text-white hover:bg-emerald-400' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}"
                                                data-chat-id="{{ $chat->id }}"
                                                data-url="{{ route('chats.toggle_bot', $chat) }}"
                                                data-enabled="{{ $chat->bot_enabled ? '1' : '0' }}">
                                                {{ $chat->bot_enabled ? 'Ativo' : 'Pausado' }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button"
                                                    title="Ver detalhes"
                                                    class="p-2 rounded-md text-gray-500 hover:bg-gray-100 hover:text-purple-600 transition-colors view-chat-btn inline-flex items-center justify-center"
                                                    data-chat-nome="{{ $chat->nome ?? '—' }}"
                                                    data-chat-informacoes="{{ $chat->informacoes ?? 'Sem informações adicionais.' }}"
                                                    data-chat-conv="{{ $chat->conv_id }}"
                                                    data-chat-assistente="{{ $chat->assistant?->name ?? $chat->assistente?->name ?? '—' }}"
                                                    data-chat-instancia="{{ $chat->instance?->name ?? '—' }}"
                                                    data-chat-sequencias='@json($chat->sequenceChats->where('status','em_andamento')->pluck('sequence.name')->filter()->values())'>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.64 0 8.577 3.01 9.964 7.183.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.64 0-8.577-3.01-9.964-7.178z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </button>

                                                <button type="button"
                                                    title="Editar"
                                                    class="p-2 rounded-md text-blue-500 hover:bg-blue-50 hover:text-blue-700 transition-colors edit-chat-btn inline-flex items-center justify-center"
                                                    data-chat-id="{{ $chat->id }}"
                                                    data-chat-action="{{ route('chats.update', $chat) }}"
                                                    data-chat-nome="{{ $chat->nome }}"
                                                    data-chat-informacoes="{{ $chat->informacoes }}"
                                                    data-chat-aguardando="{{ $chat->aguardando_atendimento ? '1' : '0' }}"
                                                    data-chat-tags='@json($chat->tags->pluck('name'))'>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.862 4.487l1.651-1.651a1.875 1.875 0 112.652 2.652L10.582 16.071a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897l7.53-7.53z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 14v4.75A1.25 1.25 0 0116.75 20H5.25A1.25 1.25 0 014 18.75V7.25A1.25 1.25 0 015.25 6H10" />
                                                    </svg>
                                                </button>

                                                <form action="{{ route('chats.destroy', $chat) }}" method="POST"
                                                    class="delete-chat-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        title="Excluir"
                                                        class="p-2 rounded-md text-red-500 hover:bg-red-50 hover:text-red-700 transition-colors inline-flex items-center justify-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 7h12M9.75 7V5.25A1.25 1.25 0 0111 4h2a1.25 1.25 0 011.25 1.25V7M10 11v6M14 11v6M6.75 7h10.5l-.637 11.215A1.25 1.25 0 0115.369 19.5H8.631a1.25 1.25 0 01-1.244-1.285L6.75 7z" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500">
                                            Nenhuma conversa encontrada com os filtros atuais.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $chats->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="viewModal" class="fixed inset-0 hidden z-40 items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Detalhes do chat</h3>
                <button type="button" id="closeViewModal" class="text-gray-500 hover:text-gray-700">
                    ✕
                </button>
            </div>
            <dl class="mt-4 space-y-3 text-sm text-gray-700">
                <div>
                    <dt class="font-semibold text-gray-600 uppercase text-xs">Nome</dt>
                    <dd id="viewNome" class="mt-1 text-gray-900"></dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-600 uppercase text-xs">Informações</dt>
                    <dd id="viewInformacoes" class="mt-1 text-gray-900 whitespace-pre-wrap"></dd>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="font-semibold text-gray-600 uppercase text-xs">Conv ID</dt>
                        <dd id="viewConv" class="mt-1 font-mono text-gray-900 text-xs"></dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-600 uppercase text-xs">Assistente</dt>
                        <dd id="viewAssistente" class="mt-1 text-gray-900"></dd>
                    </div>
                </div>
                <div>
                    <dt class="font-semibold text-gray-600 uppercase text-xs">Instância</dt>
                    <dd id="viewInstancia" class="mt-1 text-gray-900"></dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-600 uppercase text-xs">Sequências</dt>
                    <dd id="viewSequencias" class="mt-1 text-gray-900 text-xs whitespace-pre-wrap"></dd>
                </div>
            </dl>
            <div class="mt-6 flex justify-end">
                <button type="button" id="closeViewModalFooter"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <div id="sequenceModal" class="fixed inset-0 hidden z-50 items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Inscrever em sequência</h3>
                <button type="button" id="closeSequenceModal" class="text-gray-500 hover:text-gray-700">
                    ✕
                </button>
            </div>
            <div class="mt-4 space-y-3">
                <p class="text-sm text-gray-600">Escolha a sequência para inscrever os chats selecionados.</p>
                <select id="sequenceSelect" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Selecione uma sequência</option>
                    @foreach ($sequences as $sequence)
                        <option value="{{ $sequence->id }}">{{ $sequence->name }} {{ $sequence->active ? '' : '(inativa)' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="cancelSequence"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" id="confirmSequence"
                    class="inline-flex items-center gap-2 rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-purple-500">
                    Inscrever
                </button>
            </div>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 hidden z-50 items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Editar chat</h3>
                <button type="button" id="closeEditModal" class="text-gray-500 hover:text-gray-700">
                    ✕
                </button>
            </div>
            <form id="editChatForm" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Nome</label>
                    <input type="text" name="nome" id="modalNome"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Informações</label>
                    <textarea name="informacoes" id="modalInformacoes" rows="3"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Tags</label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="modalTagInput" placeholder="nova tag" class="w-full rounded-md border-gray-300 px-2 py-1 text-sm focus:border-blue-500 focus:ring-blue-500" list="modal-tags-options">
                        <button type="button" id="addTagBtn"
                            class="rounded-md bg-blue-600 px-3 py-1 text-white text-xs font-semibold hover:bg-blue-500">
                            +
                        </button>
                    </div>
                    <datalist id="modal-tags-options">
                        @foreach ($tags as $tag)
                            <option value="{{ $tag->name }}"></option>
                        @endforeach
                    </datalist>
                    <div id="modalTagsList" class="flex flex-wrap gap-2 mt-2"></div>
                    <div class="mt-3">
                        <p class="text-[11px] text-gray-500 font-semibold uppercase mb-1">Tags existentes</p>
                        <div id="modalAvailableTags" class="flex flex-wrap gap-2"></div>
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <input type="checkbox" name="aguardando_atendimento" id="modalAguardando" value="1"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Aguardando atendimento
                </label>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" id="cancelEdit"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-500">
                        Salvar alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const availableTags = @json($tags->pluck('name'));
            const toggleFilters = document.getElementById('toggleFilters');
            const filtersPanel = document.getElementById('filtersPanel');
            toggleFilters?.addEventListener('click', () => {
                filtersPanel?.classList.toggle('hidden');
            });
            const deleteForms = document.querySelectorAll('form.delete-chat-form');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Tem certeza?',
                        text: `Isso excluirá o histórico de conversa com este contato.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sim, excluir!',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.submit();
                        }
                    });
                });
            });

            const selectAll = document.getElementById('select-all-chats');
            const checkboxes = document.querySelectorAll('input.bulk-checkbox');
            selectAll?.addEventListener('change', function() {
                checkboxes.forEach(box => {
                    box.checked = this.checked;
                });
            });

            const getSelectedChats = () => Array.from(document.querySelectorAll('input.bulk-checkbox:checked')).map(box => box.value);

            const seqForm = document.getElementById('sequence-form');
            const openSeqBtn = document.getElementById('open-sequence-modal');
            const sequenceModal = document.getElementById('sequenceModal');
            const closeSequenceModal = document.getElementById('closeSequenceModal');
            const cancelSequence = document.getElementById('cancelSequence');
            const confirmSequence = document.getElementById('confirmSequence');
            const sequenceSelect = document.getElementById('sequenceSelect');

            const openSequenceModal = () => {
                const selected = getSelectedChats();
                if (!selected.length) {
                    Swal.fire('Selecione ao menos um chat.');
                    return;
                }
                sequenceModal.classList.remove('hidden');
                sequenceModal.classList.add('flex');
            };

            const closeSequence = () => {
                sequenceModal.classList.add('hidden');
                sequenceModal.classList.remove('flex');
            };

            openSeqBtn?.addEventListener('click', openSequenceModal);
            closeSequenceModal?.addEventListener('click', closeSequence);
            cancelSequence?.addEventListener('click', closeSequence);
            sequenceModal?.addEventListener('click', (event) => {
                if (event.target === sequenceModal) {
                    closeSequence();
                }
            });

            confirmSequence?.addEventListener('click', () => {
                const selected = getSelectedChats();
                if (!selected.length) {
                    Swal.fire('Selecione ao menos um chat.');
                    return;
                }
                if (!sequenceSelect.value) {
                    Swal.fire('Selecione a sequência.');
                    return;
                }
                document.getElementById('sequence_id').value = sequenceSelect.value;
                seqForm.querySelectorAll('input[name=\"selected[]\"]').forEach(node => node.remove());
                selected.forEach(value => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected[]';
                    input.value = value;
                    seqForm.appendChild(input);
                });
                seqForm.submit();
            });

            // Toggle aguardando_atendimento inline
            const awaitingButtons = document.querySelectorAll('.awaiting-toggle');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            awaitingButtons.forEach(button => {
                button.addEventListener('click', async () => {
                    const chatId = button.dataset.chatId;
                    const current = button.dataset.current === '1';
                    const next = !current;

                    button.disabled = true;
                    button.classList.add('opacity-70');

                    const formData = new FormData();
                    formData.append('_method', 'PATCH');
                    formData.append('_token', csrfToken || '');
                    if (next) {
                        formData.append('aguardando_atendimento', '1');
                    }

                    try {
                        const response = await fetch(`/chats/${chatId}`, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Falha ao atualizar.');
                        }

                        button.dataset.current = next ? '1' : '0';
                        button.textContent = next ? 'Sim' : 'Não';
                        button.classList.remove('bg-yellow-100', 'text-yellow-800', 'hover:bg-yellow-200', 'bg-green-100', 'text-green-800', 'hover:bg-green-200');
                        if (next) {
                            button.classList.add('bg-yellow-100', 'text-yellow-800', 'hover:bg-yellow-200');
                        } else {
                            button.classList.add('bg-green-100', 'text-green-800', 'hover:bg-green-200');
                        }
                    } catch (error) {
                        Swal.fire('Erro ao atualizar status. Tente novamente.');
                    } finally {
                        button.disabled = false;
                        button.classList.remove('opacity-70');
                    }
                });
            });

            // Toggle bot_enabled inline
            const botButtons = document.querySelectorAll('.toggle-bot-btn');
            botButtons.forEach(button => {
                button.addEventListener('click', async () => {
                    const url = button.dataset.url;
                    const enabled = button.dataset.enabled === '1';

                    button.disabled = true;
                    button.classList.add('opacity-70');

                    const formData = new FormData();
                    formData.append('_token', csrfToken || '');

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Falha ao atualizar.');
                        }

                        const data = await response.json();
                        const nextEnabled = data.bot_enabled ? true : false;
                        button.dataset.enabled = nextEnabled ? '1' : '0';
                        button.textContent = nextEnabled ? 'Ativo' : 'Pausado';

                        button.classList.remove('bg-emerald-500', 'text-white', 'hover:bg-emerald-400', 'bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
                        if (nextEnabled) {
                            button.classList.add('bg-emerald-500', 'text-white', 'hover:bg-emerald-400');
                        } else {
                            button.classList.add('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
                        }
                    } catch (error) {
                        Swal.fire('Erro ao atualizar o bot. Tente novamente.');
                    } finally {
                        button.disabled = false;
                        button.classList.remove('opacity-70');
                    }
                });
            });

            // Remover chat da sequência inline
            const seqRemoveButtons = document.querySelectorAll('.sequence-remove');
            seqRemoveButtons.forEach(button => {
                button.addEventListener('click', async () => {
                    const url = button.dataset.url;
                    if (!url) return;

                    button.disabled = true;
                    button.classList.add('opacity-70');

                    const formData = new FormData();
                    formData.append('_method', 'DELETE');
                    formData.append('_token', csrfToken || '');

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Falha ao remover.');
                        }

                        button.remove();
                    } catch (error) {
                        Swal.fire('Erro ao remover da sequência. Tente novamente.');
                        button.disabled = false;
                        button.classList.remove('opacity-70');
                    }
                });
            });

            const modal = document.getElementById('editModal');
            const form = document.getElementById('editChatForm');
            const nomeField = document.getElementById('modalNome');
            const infoField = document.getElementById('modalInformacoes');
            const aguardandoField = document.getElementById('modalAguardando');
            const closeButtons = [document.getElementById('closeEditModal'), document.getElementById('cancelEdit')];
            const tagsContainer = document.getElementById('modalTagsList');
            const tagInput = document.getElementById('modalTagInput');
            const addTagBtn = document.getElementById('addTagBtn');
            const availableTagsContainer = document.getElementById('modalAvailableTags');
            let currentTags = [];

            document.querySelectorAll('.edit-chat-btn').forEach(button => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.chatAction;
                    nomeField.value = button.dataset.chatNome ?? '';
                    infoField.value = button.dataset.chatInformacoes ?? '';
                    aguardandoField.checked = button.dataset.chatAguardando === '1';
                    currentTags = [];
                    try {
                        const parsed = JSON.parse(button.dataset.chatTags || '[]');
                        if (Array.isArray(parsed)) {
                            currentTags = parsed;
                        }
                    } catch (e) {
                        currentTags = [];
                    }
                    renderTags();
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            closeButtons.forEach(btn => btn?.addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }));

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });

            addTagBtn?.addEventListener('click', () => {
                const value = (tagInput.value || '').trim();
                if (!value) return;
                if (!currentTags.includes(value)) {
                    currentTags.push(value);
                    renderTags();
                }
                tagInput.value = '';
            });

            function renderAvailableTags() {
                availableTagsContainer.innerHTML = '';
                if (!availableTags.length) {
                    availableTagsContainer.textContent = 'Nenhuma tag cadastrada.';
                    availableTagsContainer.classList.add('text-xs', 'text-gray-400');
                    return;
                }
                availableTags.forEach((tag) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'px-2 py-1 rounded-full border border-gray-200 text-xs text-gray-700 hover:bg-gray-100';
                    btn.textContent = tag;
                    btn.addEventListener('click', () => {
                        if (!currentTags.includes(tag)) {
                            currentTags.push(tag);
                            renderTags();
                        }
                    });
                    availableTagsContainer.appendChild(btn);
                });
            }

            function renderTags() {
                tagsContainer.innerHTML = '';
                if (!currentTags.length) {
                    const span = document.createElement('span');
                    span.className = 'text-xs text-gray-400';
                    span.textContent = 'Nenhuma tag aplicada.';
                    tagsContainer.appendChild(span);
                } else {
                    currentTags.forEach((tag, idx) => {
                        const chip = document.createElement('span');
                        chip.className = 'inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700';
                        chip.textContent = tag;
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'text-blue-600';
                        btn.textContent = '✕';
                        btn.addEventListener('click', () => {
                            currentTags.splice(idx, 1);
                            renderTags();
                        });
                        chip.appendChild(btn);
                        tagsContainer.appendChild(chip);
                    });
                }
                form.querySelectorAll('input[name="tags[]"]').forEach(el => el.remove());
                currentTags.forEach((tag) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tags[]';
                    input.value = tag;
                    form.appendChild(input);
                });
            }
            renderAvailableTags();

            const viewModal = document.getElementById('viewModal');
            const viewFields = {
                nome: document.getElementById('viewNome'),
                info: document.getElementById('viewInformacoes'),
                conv: document.getElementById('viewConv'),
                assistente: document.getElementById('viewAssistente'),
                instancia: document.getElementById('viewInstancia'),
                sequencias: document.getElementById('viewSequencias'),
            };
            const closeViewButtons = [
                document.getElementById('closeViewModal'),
                document.getElementById('closeViewModalFooter'),
            ];

            document.querySelectorAll('.view-chat-btn').forEach(button => {
                button.addEventListener('click', () => {
                    viewFields.nome.textContent = button.dataset.chatNome ?? '—';
                    viewFields.info.textContent = button.dataset.chatInformacoes ?? '—';
                    viewFields.conv.textContent = button.dataset.chatConv ?? '—';
                    viewFields.assistente.textContent = button.dataset.chatAssistente ?? '—';
                    viewFields.instancia.textContent = button.dataset.chatInstancia ?? '—';
                    let seqNames = [];
                    try {
                        seqNames = JSON.parse(button.dataset.chatSequencias || '[]');
                    } catch (e) {
                        seqNames = [];
                    }
                    viewFields.sequencias.textContent = seqNames.length
                        ? seqNames.join(', ')
                        : 'Nenhuma sequência ativa.';
                    viewModal.classList.remove('hidden');
                    viewModal.classList.add('flex');
                });
            });

            closeViewButtons.forEach(btn => btn?.addEventListener('click', () => {
                viewModal.classList.add('hidden');
                viewModal.classList.remove('flex');
            }));

            viewModal.addEventListener('click', (event) => {
                if (event.target === viewModal) {
                    viewModal.classList.add('hidden');
                    viewModal.classList.remove('flex');
                }
            });
        });
    </script>
@endpush
</x-app-layout>
