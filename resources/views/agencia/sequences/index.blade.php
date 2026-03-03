@extends('layouts.agencia')

@push('head')
    <style>
        .log-accordion summary svg {
            transition: transform .2s ease;
        }

        .log-accordion[open] summary svg {
            transform: rotate(180deg);
        }

        .sequence-loader {
            width: 24px;
            height: 24px;
            border-radius: 9999px;
            border: 2px solid #cbd5e1;
            border-top-color: #2563eb;
            animation: sequence-loader-spin .8s linear infinite;
        }

        @keyframes sequence-loader-spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@section('content')
    @php
        $baseFilterQuery = [];
        if (!empty($selectedClientIds)) {
            $baseFilterQuery['cliente_ids'] = $selectedClientIds;
        }

        $baseSequenceQuery = $baseFilterQuery;
        if ($activeTab === 'chats') {
            $baseSequenceQuery['tab'] = 'chats';
        }

        $sequenceChats = collect($sequenceChatsPaginator?->items() ?? []);
        $clientsById = $clients->keyBy('id');
    @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Sequências</h2>
            <p class="text-sm text-slate-500">Gerencie as sequências vinculadas ao seu usuário.</p>
        </div>
        <button id="openSequenceModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Nova sequência</button>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <aside class="xl:col-span-1 space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <details id="sequenceFiltersDetails">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold text-slate-900">
                        <span class="inline-flex items-center gap-2">
                            <span>Filtros</span>
                            @if(!empty($selectedClientIds))
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700">{{ count($selectedClientIds) }}</span>
                            @endif
                        </span>
                        <span class="text-xs text-slate-500">abrir</span>
                    </summary>
                    <form id="sequenceFiltersForm" method="GET" action="{{ route('agencia.sequences.index') }}" class="mt-3 space-y-3">
                        <div>
                            <label for="sequenceFilterClientSelect" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</label>
                            <select id="sequenceFilterClientSelect" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Selecione</option>
                                @foreach($clients as $cliente)
                                    <option value="{{ $cliente->id }}" data-label="{{ $cliente->nome }}">{{ $cliente->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="sequenceFilterClientPills" class="flex flex-wrap gap-2">
                            @foreach($selectedClientIds as $clientId)
                                @php
                                    $selectedClient = $clientsById->get($clientId);
                                @endphp
                                <span
                                    class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700"
                                    data-client-pill
                                    data-client-id="{{ $clientId }}"
                                >
                                    <span>{{ $selectedClient?->nome ?? ('Cliente #' . $clientId) }}</span>
                                    <button type="button" class="text-blue-500 hover:text-blue-700" data-remove-client-pill>&times;</button>
                                    <input type="hidden" name="cliente_ids[]" value="{{ $clientId }}">
                                </span>
                            @endforeach
                            <span id="sequenceFilterClientPillsEmpty" class="text-xs text-slate-500 {{ empty($selectedClientIds) ? '' : 'hidden' }}">Nenhum cliente selecionado.</span>
                        </div>

                        @if($selectedSequence)
                            <input type="hidden" name="sequence_id" value="{{ $selectedSequence->id }}">
                        @endif
                        @if($activeTab === 'chats')
                            <input type="hidden" name="tab" value="chats">
                        @endif
                        <div class="flex items-center gap-2 pt-1">
                            <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Aplicar</button>
                            <a href="{{ route('agencia.sequences.index') }}" data-show-loader class="text-xs font-semibold text-slate-500 hover:text-slate-700">Limpar</a>
                        </div>
                    </form>
                </details>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                <h3 class="px-1 text-sm font-semibold text-slate-900">Sequências</h3>
                <div class="mt-3 space-y-2 max-h-[calc(100vh-20rem)] overflow-y-auto pr-1">
                    @forelse($sequences as $sequenceItem)
                        @php
                            $itemQuery = array_filter(array_merge($baseSequenceQuery, ['sequence_id' => $sequenceItem->id]), static fn ($value) => $value !== null && $value !== '');
                            $isSelected = $selectedSequence && (int) $selectedSequence->id === (int) $sequenceItem->id;
                        @endphp
                        <a
                            href="{{ route('agencia.sequences.index', $itemQuery) }}"
                            data-sequence-nav
                            class="block rounded-xl border px-3 py-2 transition {{ $isSelected ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50' }}"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $sequenceItem->name }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">id: {{ $sequenceItem->id }} · {{ $sequenceItem->cliente?->nome ?? 'Sem cliente' }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $sequenceItem->active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $sequenceItem->active ? 'Ativa' : 'Inativa' }}
                                </span>
                            </div>
                            <div class="mt-2 flex items-center gap-2 text-[11px] text-slate-500">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $sequenceItem->steps_count }} etapa(s)</span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $sequenceItem->chats_count }} chat(s)</span>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-xs text-slate-500">
                            Nenhuma sequência encontrada.
                        </div>
                    @endforelse
                </div>
            </div>
        </aside>

        <section id="sequenceDetailPanel" class="xl:col-span-2">
            @if(!$selectedSequence)
                <div class="flex min-h-[420px] items-center justify-center rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Nenhuma sequência selecionada</p>
                        <p class="mt-1 text-xs text-slate-500">Escolha uma sequência na lateral para carregar etapas e chats.</p>
                    </div>
                </div>
            @else
                @php
                    $stepsTabQuery = array_filter(array_merge($baseFilterQuery, ['sequence_id' => $selectedSequence->id]), static fn ($value) => $value !== null && $value !== '');
                    $chatsTabQuery = array_filter(array_merge($baseFilterQuery, ['sequence_id' => $selectedSequence->id, 'tab' => 'chats']), static fn ($value) => $value !== null && $value !== '');
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-semibold text-slate-900">{{ $selectedSequence->name }}</h3>
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">id: {{ $selectedSequence->id }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $selectedSequence->description ?? 'Sem descrição' }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $selectedSequence->active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                            {{ $selectedSequence->active ? 'Ativa' : 'Inativa' }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-600">
                        <div><span class="font-semibold text-slate-800">Cliente:</span> {{ $selectedSequence->cliente?->nome ?? '—' }}</div>
                        <div><span class="font-semibold text-slate-800">Conexão:</span> {{ $selectedSequence->conexao?->name ?? '—' }}</div>
                        <div><span class="font-semibold text-slate-800">Tags incluir:</span> {{ collect($selectedSequence->tags_incluir ?? [])->implode(', ') ?: '—' }}</div>
                        <div><span class="font-semibold text-slate-800">Tags excluir:</span> {{ collect($selectedSequence->tags_excluir ?? [])->implode(', ') ?: '—' }}</div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg bg-indigo-500 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                            data-action="edit-sequence"
                            data-payload="{{ json_encode([
                                'id' => $selectedSequence->id,
                                'name' => $selectedSequence->name,
                                'description' => $selectedSequence->description,
                                'active' => $selectedSequence->active,
                                'cliente_id' => $selectedSequence->cliente_id,
                                'conexao_id' => $selectedSequence->conexao_id,
                                'tags_incluir' => $selectedSequence->tags_incluir ?? [],
                                'tags_excluir' => $selectedSequence->tags_excluir ?? [],
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                        >Editar</button>
                        <button
                            type="button"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                            data-action="create-step"
                            data-sequence="{{ $selectedSequence->id }}"
                        >Criar Etapa</button>
                        <form
                            method="POST"
                            action="{{ route('agencia.sequences.destroy', $selectedSequence) }}"
                            data-show-loader-on-submit
                            onsubmit="return confirm('Deseja excluir esta sequencia? Esta acao tambem removera etapas, chats e logs vinculados.');"
                        >
                            @csrf
                            @method('DELETE')
                            @foreach($selectedClientIds as $filterClientId)
                                <input type="hidden" name="filter_cliente_ids[]" value="{{ $filterClientId }}">
                            @endforeach
                            @if($activeTab === 'chats')
                                <input type="hidden" name="tab" value="chats">
                            @endif
                            <input type="hidden" name="current_sequence_id" value="{{ $selectedSequence->id }}">
                            @if($sequenceChatsPaginator && $sequenceChatsPaginator->currentPage() > 1)
                                <input type="hidden" name="sequence_chats_page" value="{{ $sequenceChatsPaginator->currentPage() }}">
                            @endif
                            <button
                                type="submit"
                                class="rounded-lg bg-rose-500 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-600"
                            >Excluir</button>
                        </form>
                    </div>

                    <div class="mt-5 border-b border-slate-200">
                        <div class="-mb-px flex items-center gap-2">
                            <a
                                href="{{ route('agencia.sequences.index', $stepsTabQuery) }}"
                                data-sequence-nav
                                class="rounded-t-lg border border-b-0 px-4 py-2 text-xs font-semibold transition {{ $activeTab === 'steps' ? 'border-slate-200 bg-white text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                            >
                                Etapas ({{ $selectedSequence->steps->count() }})
                            </a>
                            <a
                                href="{{ route('agencia.sequences.index', $chatsTabQuery) }}"
                                data-sequence-nav
                                class="rounded-t-lg border border-b-0 px-4 py-2 text-xs font-semibold transition {{ $activeTab === 'chats' ? 'border-slate-200 bg-white text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                            >
                                Chats ({{ $sequenceChatsPaginator ? $sequenceChatsPaginator->total() : 0 }})
                            </a>
                        </div>
                    </div>

                    <div class="pt-4">
                        @if($activeTab === 'steps')
                            @if($selectedSequence->steps->isEmpty())
                                <p class="text-xs text-slate-500">Nenhuma etapa criada.</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs text-slate-600 border border-slate-100">
                                        <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Título</th>
                                                <th class="px-3 py-2 text-left">Atraso</th>
                                                <th class="px-3 py-2 text-left">Tipo</th>
                                                <th class="px-3 py-2 text-left">Ativo</th>
                                                <th class="px-3 py-2 text-left">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($selectedSequence->steps as $step)
                                                <tr class="border-t border-slate-100">
                                                    <td class="px-3 py-2">{{ $step->title ?: 'Sem título' }}</td>
                                                    <td class="px-3 py-2">{{ $step->atraso_valor }}</td>
                                                    <td class="px-3 py-2">{{ ucfirst($step->atraso_tipo) }}</td>
                                                    <td class="px-3 py-2">
                                                        @if($step->active)
                                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">Sim</span>
                                                        @else
                                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-semibold text-rose-700">Não</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <button
                                                            type="button"
                                                            class="rounded-lg bg-slate-900 px-3 py-1 text-[11px] font-semibold text-white hover:bg-slate-800"
                                                            data-action="edit-step"
                                                            data-sequence="{{ $selectedSequence->id }}"
                                                            data-step="{{ json_encode([
                                                                'id' => $step->id,
                                                                'title' => $step->title,
                                                                'atraso_valor' => $step->atraso_valor,
                                                                'atraso_tipo' => $step->atraso_tipo,
                                                                'janela_inicio' => $step->janela_inicio,
                                                                'janela_fim' => $step->janela_fim,
                                                                'dias_semana' => $step->dias_semana ?? [],
                                                                'prompt' => $step->prompt,
                                                                'active' => $step->active,
                                                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                                                        >Editar</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @else
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Chats da sequência ({{ $sequenceChatsPaginator ? $sequenceChatsPaginator->total() : 0 }})
                                </p>
                                <form
                                    method="POST"
                                    action="{{ route('agencia.sequence-chats.destroy-by-sequence', $selectedSequence) }}"
                                    data-show-loader-on-submit
                                    onsubmit="return confirm('Tem certeza de que deseja limpar todos os chats desta sequência?');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    @foreach($selectedClientIds as $filterClientId)
                                        <input type="hidden" name="filter_cliente_ids[]" value="{{ $filterClientId }}">
                                    @endforeach
                                    <input type="hidden" name="tab" value="chats">
                                    <input type="hidden" name="current_sequence_id" value="{{ $selectedSequence->id }}">
                                    @if($sequenceChatsPaginator && $sequenceChatsPaginator->currentPage() > 1)
                                        <input type="hidden" name="sequence_chats_page" value="{{ $sequenceChatsPaginator->currentPage() }}">
                                    @endif
                                    <button
                                        type="submit"
                                        class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700 transition hover:bg-rose-200"
                                    >
                                        Limpar chats
                                    </button>
                                </form>
                            </div>

                            @if($sequenceChats->isEmpty())
                                <p class="text-xs text-slate-500">Nenhum chat associado a esta sequência.</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs text-slate-600 border border-slate-100">
                                        <thead class="bg-slate-100 text-slate-500 text-[11px] uppercase">
                                            <tr>
                                                <th class="px-3 py-2 text-left">ClienteLead (ID)</th>
                                                <th class="px-3 py-2 text-left">Passo atual</th>
                                                <th class="px-3 py-2 text-left">Status</th>
                                                <th class="px-3 py-2 text-left">Iniciado em</th>
                                                <th class="px-3 py-2 text-left">Próximo envio</th>
                                                <th class="px-3 py-2 text-left">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sequenceChats as $chat)
                                                <tr class="border-t border-slate-100">
                                                    <td class="px-3 py-2">
                                                        {{ $chat->cliente_lead_id ?? '—' }}
                                                        @if($chat->clienteLead?->name)
                                                            ({{ $chat->clienteLead->name }})
                                                        @endif
                                                        @if($chat->clienteLead?->phone)
                                                            ({{ $chat->clienteLead->phone }})
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2">{{ $chat->passo_atual_id ?? '—' }}</td>
                                                    <td class="px-3 py-2">{{ $chat->status ? ucfirst($chat->status) : '—' }}</td>
                                                    <td class="px-3 py-2">{{ $chat->iniciado_em?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? '—' }}</td>
                                                    <td class="px-3 py-2">{{ $chat->proximo_envio_em?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? '—' }}</td>
                                                    <td class="px-3 py-2">
                                                        <form method="POST" action="{{ route('agencia.sequence-chats.destroy', $chat) }}" data-show-loader-on-submit onsubmit="return confirm('Tem certeza de que deseja excluir este SequenceChat?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            @foreach($selectedClientIds as $filterClientId)
                                                                <input type="hidden" name="filter_cliente_ids[]" value="{{ $filterClientId }}">
                                                            @endforeach
                                                            <input type="hidden" name="tab" value="chats">
                                                            <input type="hidden" name="current_sequence_id" value="{{ $selectedSequence->id }}">
                                                            @if($sequenceChatsPaginator && $sequenceChatsPaginator->currentPage() > 1)
                                                                <input type="hidden" name="sequence_chats_page" value="{{ $sequenceChatsPaginator->currentPage() }}">
                                                            @endif
                                                            <button
                                                                type="submit"
                                                                class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700 transition hover:bg-rose-200"
                                                            >
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($sequenceChatsPaginator && $sequenceChatsPaginator->hasPages())
                                    <div class="mt-3 flex items-center justify-end">
                                        {{ $sequenceChatsPaginator->links('pagination::tailwind') }}
                                    </div>
                                @endif
                            @endif
                        @endif
                    </div>
                </article>
            @endif
        </section>
    </div>

    <div id="sequenceModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 backdrop-blur">
        <div class="w-[min(720px,calc(100%-2rem))] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900" id="sequenceModalTitle">Nova sequência</h3>
                <button type="button" data-close-modal class="text-slate-500 hover:text-slate-700">x</button>
            </div>
            <form id="sequenceForm" method="POST" action="{{ route('agencia.sequences.store') }}" class="mt-5 space-y-4" data-show-loader-on-submit>
                @csrf
                <input type="hidden" name="sequence_id" id="sequenceId" value="">
                @foreach($selectedClientIds as $filterClientId)
                    <input type="hidden" name="filter_cliente_ids[]" value="{{ $filterClientId }}">
                @endforeach
                @if($activeTab === 'chats')
                    <input type="hidden" name="tab" value="chats">
                @endif
                <input type="hidden" name="current_sequence_id" id="sequenceCurrentSequenceId" value="{{ $selectedSequence?->id }}">
                @if($sequenceChatsPaginator && $sequenceChatsPaginator->currentPage() > 1)
                    <input type="hidden" name="sequence_chats_page" value="{{ $sequenceChatsPaginator->currentPage() }}">
                @endif

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="sequenceClient">Cliente</label>
                    <select id="sequenceClient" name="cliente_id" required class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm">
                        <option value="">Selecione um cliente</option>
                        @foreach($clients as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="sequenceConexao">Conexão</label>
                    <select id="sequenceConexao" name="conexao_id" required class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm">
                        <option value="">Escolha o cliente primeiro</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="sequenceName">Nome</label>
                    <input id="sequenceName" name="name" type="text" maxlength="255" required class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm">
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="sequenceDescription">Descrição</label>
                    <textarea id="sequenceDescription" name="description" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input id="sequenceActive" name="active" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label for="sequenceActive" class="text-sm text-slate-600">Ativa</label>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tags a incluir</label>
                    <div id="sequenceTagsIncluirContainer" class="relative mt-2 rounded-lg border border-slate-200 bg-white p-2" data-input-name="tags_incluir[]" data-tags="{{ json_encode($tags->pluck('name')) }}">
                        <div class="flex flex-wrap gap-2" data-selected></div>
                        <input type="text" placeholder="Selecione tags..." class="mt-1 w-full border-none bg-transparent px-1 text-sm focus:outline-none" data-search>
                        <ul class="absolute left-0 right-0 z-10 mt-1 max-h-52 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg hidden" data-list></ul>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tags a excluir</label>
                    <div id="sequenceTagsExcluirContainer" class="relative mt-2 rounded-lg border border-slate-200 bg-white p-2" data-input-name="tags_excluir[]" data-tags="{{ json_encode($tags->pluck('name')) }}">
                        <div class="flex flex-wrap gap-2" data-selected></div>
                        <input type="text" placeholder="Selecione tags..." class="mt-1 w-full border-none bg-transparent px-1 text-sm focus:outline-none" data-search>
                        <ul class="absolute left-0 right-0 z-10 mt-1 max-h-52 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg hidden" data-list></ul>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" data-close-modal class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="sequenceStepModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[min(520px,calc(100%-2rem))] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="stepModalTitle" class="text-lg font-semibold text-slate-900">Nova etapa</h3>
                <button type="button" data-close-step-modal class="text-slate-500 hover:text-slate-700">x</button>
            </div>
            <form id="sequenceStepForm" method="POST" class="mt-5 space-y-4" data-show-loader-on-submit>
                @csrf
                <input type="hidden" name="_method" id="stepFormMethod" value="POST">
                <input type="hidden" name="sequence_id" id="stepSequenceId" value="">
                <input type="hidden" name="step_id" id="stepId" value="">
                @foreach($selectedClientIds as $filterClientId)
                    <input type="hidden" name="filter_cliente_ids[]" value="{{ $filterClientId }}">
                @endforeach
                @if($activeTab === 'chats')
                    <input type="hidden" name="tab" value="chats">
                @endif
                <input type="hidden" name="current_sequence_id" id="stepCurrentSequenceId" value="{{ $selectedSequence?->id }}">
                @if($sequenceChatsPaginator && $sequenceChatsPaginator->currentPage() > 1)
                    <input type="hidden" name="sequence_chats_page" value="{{ $sequenceChatsPaginator->currentPage() }}">
                @endif

                <div>
                    <label class="text-xs uppercase tracking-wide text-slate-500">Título</label>
                    <input type="text" name="title" id="stepTitle" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Nome do passo">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs uppercase tracking-wide text-slate-500">Atraso valor</label>
                        <input type="number" name="atraso_valor" id="stepAtrasoValor" min="0" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" value="1">
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-slate-500">Atraso tipo</label>
                        <select name="atraso_tipo" id="stepAtrasoTipo" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <option value="minuto">Minuto(s)</option>
                            <option value="hora" selected>Hora(s)</option>
                            <option value="dia">Dia(s)</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="stepActive" name="active" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                        <label for="stepActive" class="text-sm text-slate-600">Passo ativo</label>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs uppercase tracking-wide text-slate-500">Janela início</label>
                        <input type="time" name="janela_inicio" id="stepJanelaInicio" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-slate-500">Janela fim</label>
                        <input type="time" name="janela_fim" id="stepJanelaFim" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-slate-500">Dias da semana</label>
                    <div class="flex flex-wrap gap-2 mt-2" id="stepDias">
                        @foreach(['mon'=>'Seg','tue'=>'Ter','wed'=>'Qua','thu'=>'Qui','fri'=>'Sex','sat'=>'Sáb','sun'=>'Dom'] as $value => $label)
                            <label class="inline-flex items-center gap-1 text-sm text-slate-700">
                                <input type="checkbox" value="{{ $value }}" class="text-blue-600" name="dias_semana[]" data-step-day>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="text-xs uppercase tracking-wide text-slate-500">Prompt</label>
                        @if(!$promptHelpTipos->isEmpty())
                            <div class="relative" id="stepPromptHelpDropdown">
                                <div class="flex flex-wrap items-center justify-end gap-2" id="stepPromptHelpTypeButtons">
                                    @foreach($promptHelpTipos as $tipo)
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-700"
                                            data-step-ph-type-btn
                                            data-step-ph-type-id="{{ $tipo->id }}"
                                        >
                                            {{ $tipo->name }}
                                            <span class="text-slate-400">▾</span>
                                        </button>
                                    @endforeach
                                </div>
                                <div
                                    id="stepPromptHelpDropdownMenu"
                                    class="absolute right-0 z-20 mt-2 hidden w-[360px] max-h-96 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"
                                >
                                    @foreach($promptHelpTipos as $tipo)
                                        <div class="px-4 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500" data-step-ph-type-block="{{ $tipo->id }}">{{ $tipo->name }}</div>
                                        @foreach($tipo->sections as $section)
                                            <button
                                                type="button"
                                                class="flex w-full items-center justify-between px-5 py-2 text-left text-[11px] font-semibold text-slate-700 hover:bg-slate-50"
                                                data-step-ph-type-block="{{ $tipo->id }}"
                                                data-step-ph-section-toggle
                                                data-step-ph-type-id="{{ $tipo->id }}"
                                                data-step-ph-section-id="{{ $section->id }}"
                                            >
                                                <span>{{ $section->name }}</span>
                                                <span class="text-slate-400">▸</span>
                                            </button>
                                            <div
                                                class="hidden border-l border-slate-200"
                                                data-step-ph-section-content
                                                data-step-ph-type-id="{{ $tipo->id }}"
                                                data-step-ph-section-id="{{ $section->id }}"
                                            >
                                                @forelse($section->prompts as $prompt)
                                                    <button
                                                        type="button"
                                                        class="w-full px-6 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                                        data-step-prompt-help-item
                                                        data-step-ph-type-id="{{ $tipo->id }}"
                                                        data-step-ph-section-id="{{ $section->id }}"
                                                        data-prompt='@json($prompt->prompt)'
                                                    >
                                                        <div class="font-semibold text-slate-900">{{ $prompt->name }}</div>
                                                        <div class="text-[11px] text-slate-500">
                                                            {{ $prompt->descricao ? \Illuminate\Support\Str::limit($prompt->descricao, 80) : 'Clique para inserir no campo de prompt.' }}
                                                        </div>
                                                    </button>
                                                @empty
                                                    <div class="px-6 py-2 text-xs text-slate-400">Nenhum prompt nesta seção.</div>
                                                @endforelse
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    @if($promptHelpTipos->isEmpty())
                        <p class="mt-1 text-xs text-slate-500">Nenhum prompt de ajuda cadastrado.</p>
                    @endif
                    <textarea name="prompt" id="stepPrompt" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" data-close-step-modal class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="sequencePageLoader" class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-900/35 backdrop-blur-[1px]">
        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-2xl">
            <span class="sequence-loader" aria-hidden="true"></span>
            <div>
                <p class="text-sm font-semibold text-slate-800">Carregando sequência</p>
                <p class="text-xs text-slate-500">Atualizando etapas e chats...</p>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const pageLoader = document.getElementById('sequencePageLoader');
            const showPageLoader = () => {
                if (!pageLoader) {
                    return;
                }
                pageLoader.classList.remove('hidden');
                pageLoader.classList.add('flex');
            };

            document.querySelectorAll('[data-sequence-nav], [data-show-loader]').forEach(element => {
                element.addEventListener('click', showPageLoader);
            });

            document.querySelectorAll('form[data-show-loader-on-submit]').forEach(form => {
                form.addEventListener('submit', (event) => {
                    if (event.defaultPrevented) {
                        return;
                    }
                    showPageLoader();
                });
            });

            const filterForm = document.getElementById('sequenceFiltersForm');
            const filterClientSelect = document.getElementById('sequenceFilterClientSelect');
            const filterClientPills = document.getElementById('sequenceFilterClientPills');
            const filterClientPillsEmpty = document.getElementById('sequenceFilterClientPillsEmpty');

            const updateClientPillsEmptyState = () => {
                if (!filterClientPills || !filterClientPillsEmpty) {
                    return;
                }
                const hasPills = filterClientPills.querySelector('[data-client-pill]');
                filterClientPillsEmpty.classList.toggle('hidden', !!hasPills);
            };

            filterForm?.addEventListener('submit', showPageLoader);

            const bindClientPillRemove = (button) => {
                button?.addEventListener('click', () => {
                    const pill = button.closest('[data-client-pill]');
                    pill?.remove();
                    updateClientPillsEmptyState();
                });
            };

            filterClientPills?.querySelectorAll('[data-remove-client-pill]').forEach(bindClientPillRemove);

            const addClientPill = () => {
                if (!filterClientSelect || !filterClientPills) {
                    return;
                }

                const clientId = String(filterClientSelect.value || '').trim();
                if (!clientId) {
                    return;
                }
                if (filterClientPills.querySelector(`[data-client-pill][data-client-id="${clientId}"]`)) {
                    filterClientSelect.value = '';
                    return;
                }

                const selectedOption = filterClientSelect.options[filterClientSelect.selectedIndex];
                const clientLabel = selectedOption?.dataset?.label || selectedOption?.textContent || `Cliente #${clientId}`;

                const pill = document.createElement('span');
                pill.className = 'inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700';
                pill.dataset.clientPill = '';
                pill.dataset.clientId = clientId;

                const label = document.createElement('span');
                label.textContent = clientLabel;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'text-blue-500 hover:text-blue-700';
                removeButton.dataset.removeClientPill = '';
                removeButton.innerHTML = '&times;';
                bindClientPillRemove(removeButton);

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'cliente_ids[]';
                hiddenInput.value = clientId;

                pill.appendChild(label);
                pill.appendChild(removeButton);
                pill.appendChild(hiddenInput);
                filterClientPills.appendChild(pill);
                filterClientSelect.value = '';
                updateClientPillsEmptyState();
            };

            filterClientSelect?.addEventListener('change', addClientPill);
            updateClientPillsEmptyState();

            const detailPanel = document.getElementById('sequenceDetailPanel');
            detailPanel?.addEventListener('click', (event) => {
                const link = event.target.closest('a[href*="sequence_chats_page="]');
                if (link) {
                    showPageLoader();
                }
            });

            const modal = document.getElementById('sequenceModal');
            const openBtn = document.getElementById('openSequenceModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const clientSelect = document.getElementById('sequenceClient');
            const connectionSelect = document.getElementById('sequenceConexao');
            const form = document.getElementById('sequenceForm');
            const title = document.getElementById('sequenceModalTitle');
            const hiddenId = document.getElementById('sequenceId');
            const currentSequenceInput = document.getElementById('sequenceCurrentSequenceId');
            const nameInput = document.getElementById('sequenceName');
            const descriptionInput = document.getElementById('sequenceDescription');
            const activeInput = document.getElementById('sequenceActive');
            const tagsIncluirContainer = document.getElementById('sequenceTagsIncluirContainer');
            const tagsExcluirContainer = document.getElementById('sequenceTagsExcluirContainer');

            const connectionsUrlTemplate = "{{ route('agencia.sequences.cliente.conexoes', ['cliente' => '__CLIENT__']) }}";

            class MultiTagSelect {
                constructor(root) {
                    this.root = root;
                    this.name = root.dataset.inputName;
                    this.options = JSON.parse(root.dataset.tags || '[]');
                    this.selectedValues = new Set();
                    this.selectedContainer = root.querySelector('[data-selected]');
                    this.searchInput = root.querySelector('[data-search]');
                    this.list = root.querySelector('[data-list]');
                    this.bind();
                    this.renderList();
                }

                bind() {
                    this.searchInput.addEventListener('input', () => this.renderList(this.searchInput.value));
                    this.searchInput.addEventListener('focus', () => this.renderList(this.searchInput.value));
                    this.list.addEventListener('click', (event) => {
                        const value = event.target.dataset.value;
                        if (value) {
                            this.addValue(value);
                            this.searchInput.value = '';
                            this.renderList();
                        }
                    });
                    document.addEventListener('click', (event) => {
                        if (!this.root.contains(event.target)) {
                            this.list.classList.add('hidden');
                        }
                    });
                }

                renderList(filter = '') {
                    const term = filter.toLowerCase();
                    const matches = this.options.filter(tag => {
                        const normalized = String(tag).toLowerCase();
                        return !this.selectedValues.has(tag) && (term === '' || normalized.includes(term));
                    });
                    if (matches.length === 0) {
                        this.list.innerHTML = '';
                        this.list.classList.add('hidden');
                        return;
                    }
                    this.list.innerHTML = matches.map(tag => `<li class="cursor-pointer px-3 py-2 hover:bg-slate-100" data-value="${tag}">${tag}</li>`).join('');
                    this.list.classList.remove('hidden');
                }

                addValue(value) {
                    const normalized = String(value).trim();
                    if (normalized === '' || this.selectedValues.has(normalized)) {
                        return;
                    }
                    this.selectedValues.add(normalized);
                    const chip = document.createElement('span');
                    chip.className = 'flex items-center gap-1 rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-800';
                    chip.textContent = normalized;
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'text-slate-500 hover:text-slate-700';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.addEventListener('click', () => this.removeValue(normalized, chip));
                    chip.appendChild(removeBtn);
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = this.name;
                    hiddenInput.value = normalized;
                    chip.dataset.value = normalized;
                    chip.appendChild(hiddenInput);
                    this.selectedContainer.appendChild(chip);
                }

                removeValue(value, chipElement) {
                    this.selectedValues.delete(value);
                    this.selectedContainer.removeChild(chipElement);
                    this.renderList(this.searchInput.value);
                }

                clear() {
                    this.selectedValues.clear();
                    this.selectedContainer.innerHTML = '';
                }

                setValues(values) {
                    this.clear();
                    (values ?? []).forEach(value => this.addValue(value));
                }
            }

            const incluirSelect = new MultiTagSelect(tagsIncluirContainer);
            const excluirSelect = new MultiTagSelect(tagsExcluirContainer);

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                hiddenId.value = '';
                form.reset();
                connectionSelect.innerHTML = '<option value="">Escolha o cliente primeiro</option>';
                title.textContent = 'Nova sequência';
                incluirSelect.clear();
                excluirSelect.clear();
            };

            const populateConnections = async (clienteId, selected = null) => {
                if (!clienteId) {
                    connectionSelect.innerHTML = '<option value="">Escolha o cliente primeiro</option>';
                    return;
                }
                const url = connectionsUrlTemplate.replace('__CLIENT__', clienteId);
                const response = await fetch(url);
                if (!response.ok) {
                    connectionSelect.innerHTML = '<option value="">Não foi possível carregar conexões</option>';
                    return;
                }
                const json = await response.json();
                connectionSelect.innerHTML = '<option value="">Selecione uma conexão</option>';
                json.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    connectionSelect.appendChild(opt);
                });
                if (selected) {
                    connectionSelect.value = selected;
                }
            };

            clientSelect.addEventListener('change', () => populateConnections(clientSelect.value));

            openBtn.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeBtns.forEach(button => button.addEventListener('click', closeModal));
            modal.addEventListener('click', event => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.querySelectorAll('[data-action="edit-sequence"]').forEach(button => {
                button.addEventListener('click', async () => {
                    const data = JSON.parse(button.dataset.payload);
                    hiddenId.value = data.id;
                    if (currentSequenceInput) {
                        currentSequenceInput.value = data.id;
                    }
                    nameInput.value = data.name ?? '';
                    descriptionInput.value = data.description ?? '';
                    activeInput.checked = data.active ?? true;
                    clientSelect.value = data.cliente_id ?? '';
                    await populateConnections(data.cliente_id, data.conexao_id);
                    incluirSelect.setValues(data.tags_incluir ?? []);
                    excluirSelect.setValues(data.tags_excluir ?? []);
                    title.textContent = 'Editar sequência';
                    openModal();
                });
            });

            const stepModal = document.getElementById('sequenceStepModal');
            const stepForm = document.getElementById('sequenceStepForm');
            const stepMethodInput = document.getElementById('stepFormMethod');
            const stepSequenceInput = document.getElementById('stepSequenceId');
            const stepCurrentSequenceInput = document.getElementById('stepCurrentSequenceId');
            const stepIdInput = document.getElementById('stepId');
            const stepTitleInput = document.getElementById('stepTitle');
            const stepAtrasoValorInput = document.getElementById('stepAtrasoValor');
            const stepAtrasoTipoInput = document.getElementById('stepAtrasoTipo');
            const stepJanelaInicioInput = document.getElementById('stepJanelaInicio');
            const stepJanelaFimInput = document.getElementById('stepJanelaFim');
            const stepPromptInput = document.getElementById('stepPrompt');
            const stepActiveInput = document.getElementById('stepActive');
            const stepDayCheckboxes = stepModal.querySelectorAll('[data-step-day]');
            const stepModalTitle = document.getElementById('stepModalTitle');
            const stepCloseButtons = stepModal.querySelectorAll('[data-close-step-modal]');
            const stepPromptHelpDropdown = document.getElementById('stepPromptHelpDropdown');
            const stepPromptHelpDropdownMenu = document.getElementById('stepPromptHelpDropdownMenu');
            const stepTypeButtons = Array.from(stepModal.querySelectorAll('[data-step-ph-type-btn]'));
            const stepTypeBlocks = Array.from(stepModal.querySelectorAll('[data-step-ph-type-block]'));
            const stepSectionToggles = Array.from(stepModal.querySelectorAll('[data-step-ph-section-toggle]'));
            const stepSectionContents = Array.from(stepModal.querySelectorAll('[data-step-ph-section-content]'));
            const stepPromptItems = Array.from(stepModal.querySelectorAll('[data-step-prompt-help-item]'));
            const stepActiveTypeClasses = ['border-blue-600', 'bg-blue-600', 'text-white'];
            const stepActiveSectionClasses = ['bg-slate-100', 'text-slate-900'];
            let stepActiveTypeId = null;

            const stepStoreTemplate = "{{ route('agencia.sequences.steps.store', ['sequence' => '__SEQ__']) }}";
            const stepUpdateTemplate = "{{ route('agencia.sequences.steps.update', ['sequence' => '__SEQ__', 'step' => '__STEP__']) }}";

            const defaultDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

            const insertAtCursor = (field, text) => {
                if (!field) {
                    return;
                }
                const start = field.selectionStart ?? field.value.length;
                const end = field.selectionEnd ?? field.value.length;
                const before = field.value.slice(0, start);
                const after = field.value.slice(end);
                const addSeparator = before.length > 0 && start === end && start === field.value.length ? '\n\n' : '';
                field.value = `${before}${addSeparator}${text}${after}`;
                const cursor = (before + addSeparator + text).length;
                field.setSelectionRange(cursor, cursor);
                field.focus();
            };

            const closeStepPromptHelpDropdown = () => {
                stepPromptHelpDropdownMenu?.classList.add('hidden');
            };

            const openStepPromptHelpDropdown = () => {
                stepPromptHelpDropdownMenu?.classList.remove('hidden');
            };

            const setStepActiveType = (typeId) => {
                stepActiveTypeId = typeId;
                stepTypeButtons.forEach(btn => {
                    const isActive = btn.dataset.stepPhTypeId === typeId;
                    stepActiveTypeClasses.forEach(cls => btn.classList.toggle(cls, isActive));
                });

                stepTypeBlocks.forEach(block => {
                    block.classList.toggle('hidden', block.dataset.stepPhTypeBlock !== typeId);
                });

                stepSectionToggles.forEach(toggle => {
                    toggle.classList.toggle('hidden', toggle.dataset.stepPhTypeId !== typeId);
                    stepActiveSectionClasses.forEach(cls => toggle.classList.remove(cls));
                });

                stepSectionContents.forEach(content => {
                    content.classList.add('hidden');
                });

                stepPromptItems.forEach(item => {
                    item.classList.toggle('hidden', item.dataset.stepPhTypeId !== typeId);
                });
            };

            if (stepTypeButtons.length && stepPromptHelpDropdownMenu) {
                stepTypeButtons.forEach(button => {
                    button.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const typeId = button.dataset.stepPhTypeId;
                        const isSameType = stepActiveTypeId === typeId;
                        if (isSameType && !stepPromptHelpDropdownMenu.classList.contains('hidden')) {
                            closeStepPromptHelpDropdown();
                            return;
                        }
                        setStepActiveType(typeId);
                        openStepPromptHelpDropdown();
                    });
                });

                stepSectionToggles.forEach(button => {
                    button.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const typeId = button.dataset.stepPhTypeId;
                        const sectionId = button.dataset.stepPhSectionId;
                        if (stepActiveTypeId !== typeId) {
                            setStepActiveType(typeId);
                        }

                        const target = stepSectionContents.find(content => {
                            return content.dataset.stepPhTypeId === typeId && content.dataset.stepPhSectionId === sectionId;
                        });
                        const isOpen = target && !target.classList.contains('hidden');

                        stepSectionContents.forEach(content => {
                            if (content.dataset.stepPhTypeId === typeId) {
                                content.classList.add('hidden');
                            }
                        });
                        stepSectionToggles.forEach(toggle => {
                            if (toggle.dataset.stepPhTypeId === typeId) {
                                stepActiveSectionClasses.forEach(cls => toggle.classList.remove(cls));
                            }
                        });

                        if (target && !isOpen) {
                            target.classList.remove('hidden');
                            stepActiveSectionClasses.forEach(cls => button.classList.add(cls));
                        }
                    });
                });

                document.addEventListener('click', (event) => {
                    if (!stepPromptHelpDropdown?.contains(event.target)) {
                        closeStepPromptHelpDropdown();
                    }
                });
            }

            if (stepPromptItems.length) {
                stepPromptItems.forEach(button => {
                    button.addEventListener('click', () => {
                        const raw = button.dataset.prompt || '""';
                        let text = '';
                        try {
                            text = JSON.parse(raw);
                        } catch (error) {
                            text = '';
                        }

                        if (typeof text !== 'string' || text.trim() === '') {
                            closeStepPromptHelpDropdown();
                            return;
                        }

                        insertAtCursor(stepPromptInput, text);
                        closeStepPromptHelpDropdown();
                    });
                });
            }

            const openStepModal = (sequenceId, stepData = null) => {
                stepSequenceInput.value = sequenceId;
                if (stepCurrentSequenceInput) {
                    stepCurrentSequenceInput.value = sequenceId;
                }
                if (stepData) {
                    stepForm.action = stepUpdateTemplate.replace('__SEQ__', sequenceId).replace('__STEP__', stepData.id);
                    stepMethodInput.value = 'PATCH';
                    stepIdInput.value = stepData.id;
                    stepTitleInput.value = stepData.title ?? '';
                    stepAtrasoValorInput.value = stepData.atraso_valor ?? 1;
                    stepAtrasoTipoInput.value = stepData.atraso_tipo ?? 'hora';
                    stepJanelaInicioInput.value = stepData.janela_inicio ?? '';
                    stepJanelaFimInput.value = stepData.janela_fim ?? '';
                    stepPromptInput.value = stepData.prompt ?? '';
                    stepActiveInput.checked = !!stepData.active;
                    setDayCheckboxes(stepData.dias_semana ?? []);
                    stepModalTitle.textContent = 'Editar etapa';
                } else {
                    stepForm.action = stepStoreTemplate.replace('__SEQ__', sequenceId);
                    stepMethodInput.value = 'POST';
                    stepIdInput.value = '';
                    stepTitleInput.value = '';
                    stepAtrasoValorInput.value = 1;
                    stepAtrasoTipoInput.value = 'hora';
                    stepJanelaInicioInput.value = '';
                    stepJanelaFimInput.value = '';
                    stepPromptInput.value = '';
                    stepActiveInput.checked = true;
                    setDayCheckboxes(defaultDays);
                    stepModalTitle.textContent = 'Nova etapa';
                }
                stepModal.classList.remove('hidden');
                stepModal.classList.add('flex');
            };

            const setDayCheckboxes = (values) => {
                const normalized = (values ?? []).map(v => String(v));
                stepDayCheckboxes.forEach(checkbox => {
                    checkbox.checked = normalized.includes(checkbox.value);
                });
            };

            const closeStepModal = () => {
                stepModal.classList.add('hidden');
                stepModal.classList.remove('flex');
                closeStepPromptHelpDropdown();
            };

            document.querySelectorAll('[data-action="create-step"]').forEach(button => {
                button.addEventListener('click', () => {
                    openStepModal(button.dataset.sequence);
                });
            });

            document.querySelectorAll('[data-action="edit-step"]').forEach(button => {
                button.addEventListener('click', () => {
                    const stepData = JSON.parse(button.dataset.step);
                    openStepModal(button.dataset.sequence, stepData);
                });
            });

            stepCloseButtons.forEach(btn => btn.addEventListener('click', closeStepModal));
            stepModal.addEventListener('click', (event) => {
                if (event.target === stepModal) {
                    closeStepModal();
                }
            });
        })();
    </script>
@endsection
