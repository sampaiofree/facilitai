@extends('layouts.cliente')

@push('head')
    <style>
        .log-accordion summary svg {
            transition: transform .2s ease;
        }

        .log-accordion[open] summary svg {
            transform: rotate(180deg);
        }
    </style>
@endpush

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Sequências</h2>
            <p class="text-sm text-slate-500">Gerencie as sequências vinculadas ao cliente logado.</p>
        </div>
        <button id="openSequenceModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Nova sequência</button>
    </div>

    <div class="grid gap-4">
        @forelse($sequences as $sequence)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $sequence->name }}</h3>
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">id: {{ $sequence->id }}</span>
                        </div>
                        <p class="text-sm text-slate-500">{{ $sequence->description ?? 'Sem descrição' }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $sequence->active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                        {{ $sequence->active ? 'Ativa' : 'Inativa' }}
                    </span>
                </div>
                <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-600">
                    <div><span class="font-semibold text-slate-800">Cliente:</span> {{ $sequence->cliente?->nome ?? '—' }}</div>
                    <div><span class="font-semibold text-slate-800">Conexão:</span> {{ $sequence->conexao?->name ?? '—' }}</div>
                    <div>
                        <span class="font-semibold text-slate-800">Tags incluir:</span>
                        {{ collect($sequence->tags_incluir ?? [])->implode(', ') ?: '—' }}
                    </div>
                    <div>
                        <span class="font-semibold text-slate-800">Tags excluir:</span>
                        {{ collect($sequence->tags_excluir ?? [])->implode(', ') ?: '—' }}
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <button type="button"
                        class="rounded-lg bg-indigo-500 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                        data-action="edit-sequence"
                        data-payload="{{ json_encode([
                            'id' => $sequence->id,
                            'name' => $sequence->name,
                            'description' => $sequence->description,
                            'active' => $sequence->active,
                            'cliente_id' => $sequence->cliente_id,
                            'conexao_id' => $sequence->conexao_id,
                            'tags_incluir' => $sequence->tags_incluir ?? [],
                            'tags_excluir' => $sequence->tags_excluir ?? [],
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                    >Editar</button>
                    <button type="button"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                        data-action="create-step"
                        data-sequence="{{ $sequence->id }}"
                    >Criar Etapa</button>
                    <form
                        method="POST"
                        action="{{ route('cliente.sequences.duplicate', $sequence) }}"
                        onsubmit="return confirm('Duplicar esta sequência com todas as etapas?');"
                    >
                        @csrf
                        <button
                            type="submit"
                            class="rounded-lg border border-blue-200 px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                        >Duplicar</button>
                    </form>
                    <form
                        method="POST"
                        action="{{ route('cliente.sequences.destroy', $sequence) }}"
                        onsubmit="return confirm('Deseja excluir esta sequencia? Esta acao tambem removera etapas, chats e logs vinculados.');"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="rounded-lg bg-rose-500 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-600"
                        >Excluir</button>
                    </form>
                </div>
                <div class="mt-4">
                    <details class="step-accordion rounded-2xl border border-slate-200 bg-slate-50">
                        <summary class="flex items-center justify-between px-4 py-3 text-sm font-semibold text-slate-700 cursor-pointer">
                            <span>Etapas ({{ $sequence->steps->count() }})</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6" />
                            </svg>
                        </summary>
                        <div class="px-4 py-3">
                            @if($sequence->steps->isEmpty())
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
                                            @forelse($sequence->steps as $step)
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
                                                        <button type="button"
                                                            class="rounded-lg bg-slate-900 px-3 py-1 text-[11px] font-semibold text-white hover:bg-slate-800"
                                                            data-action="edit-step"
                                                            data-sequence="{{ $sequence->id }}"
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
                                                        {{--
                                                        <form
                                                            method="POST"
                                                            action="{{ route('cliente.sequences.steps.destroy', ['sequence' => $sequence->id, 'step' => $step->id]) }}"
                                                            class="inline"
                                                            onsubmit="return confirm('Deseja excluir esta etapa?');"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="submit"
                                                                class="rounded-lg bg-rose-500 px-3 py-1 text-[11px] font-semibold text-white hover:bg-rose-600"
                                                            >Excluir</button>
                                                        </form>
                                                        --}}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-3 py-2 text-xs text-slate-400">Nenhuma etapa criada.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </details>
                </div>
            {{--
            <div class="mt-4">
                <details class="log-accordion rounded-2xl border border-slate-200 bg-slate-50">
                    <summary class="flex items-center justify-between px-4 py-3 text-sm font-semibold text-slate-700 cursor-pointer">
                        <span>Log ({{ $sequence->logs->count() }})</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6" />
                        </svg>
                    </summary>
                    <div class="px-4 py-3">
                        @if($sequence->logs->isEmpty())
                            <p class="text-xs text-slate-500">Sem registros no log.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs text-slate-600 border border-slate-100">
                                    <thead class="bg-slate-100 text-slate-500 text-[11px] uppercase">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Log</th>
                                            <th class="px-3 py-2 text-left">SequenceChat</th>
                                            <th class="px-3 py-2 text-left">SequenceStep</th>
                                            <th class="px-3 py-2 text-left">Passo</th>
                                            <th class="px-3 py-2 text-left">Status</th>
                                            <th class="px-3 py-2 text-left">Mensagem</th>
                                            <th class="px-3 py-2 text-left">Criado em</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sequence->logs as $log)
                                        <tr class="border-t border-slate-100">
                                            <td class="px-3 py-2">{{ $log->id }}</td>
                                            <td class="px-3 py-2">{{ $log->sequence_chat_id }}</td>
                                            <td class="px-3 py-2">{{ $log->sequence_step_id ?? '—' }}</td>
                                            <td class="px-3 py-2">
                                                {{ $log->sequenceStep?->ordem ? 'Passo ' . $log->sequenceStep->ordem : '—' }}
                                            </td>
                                            <td class="px-3 py-2">{{ ucfirst($log->status) }}</td>
                                            <td class="px-3 py-2">{{ $log->message ?? '—' }}</td>
                                            <td class="px-3 py-2">
                                                {{ $log->created_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? '—' }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </details>
            </div>
            --}}
            <div class="mt-4">
                @php
                    $sequenceChatsPaginator = $sequenceChatsBySequence[$sequence->id] ?? null;
                    $sequenceChats = collect($sequenceChatsPaginator?->items() ?? []);
                @endphp
                <details class="log-accordion sequence-chats-accordion rounded-2xl border border-slate-200 bg-slate-50">
                    <summary class="flex items-center justify-between px-4 py-3 text-sm font-semibold text-slate-700 cursor-pointer">
                        <span class="inline-flex items-center gap-2">
                            <span>Chats da sequência ({{ $sequenceChatsPaginator ? $sequenceChatsPaginator->total() : 0 }})</span>
                            <form method="POST" action="{{ route('cliente.sequence-chats.destroy-by-sequence', $sequence) }}" onclick="event.stopPropagation();">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700 transition hover:bg-rose-200"
                                    onclick="event.stopPropagation(); return confirm('Tem certeza de que deseja limpar todos os chats desta sequência?');"
                                >
                                    Limpar chats
                                </button>
                            </form>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6" />
                        </svg>
                    </summary>
                    <div class="px-4 py-3">
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
                                                <td class="px-3 py-2">
                                                    {{ $chat->iniciado_em?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? '—' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $chat->proximo_envio_em?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? '—' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    <form method="POST" action="{{ route('cliente.sequence-chats.destroy', $chat) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-700 transition hover:bg-rose-200"
                                                            onclick="return confirm('Tem certeza de que deseja excluir este SequenceChat?');"
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
                    </div>
                </details>
            </div>
        </article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center text-sm text-slate-500">
                Nenhuma sequência encontrada.
            </div>
        @endforelse
    </div>

    <div id="sequenceModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 backdrop-blur">
        <div class="w-[min(720px,calc(100%-2rem))] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900" id="sequenceModalTitle">Nova sequência</h3>
                <button type="button" data-close-modal class="text-slate-500 hover:text-slate-700">x</button>
            </div>
            <form id="sequenceForm" method="POST" action="{{ route('cliente.sequences.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="sequence_id" id="sequenceId" value="">

                <div>
                    @php($loggedClient = $clients->first())
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="sequenceClient">Cliente</label>
                    <input type="hidden" name="cliente_id" id="sequenceClientId" value="{{ $loggedClient?->id }}">
                    <select id="sequenceClient" disabled class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-100 px-4 py-2 text-sm text-slate-600">
                        @foreach($clients as $cliente)
                            <option value="{{ $cliente->id }}" @selected($loggedClient && (int) $loggedClient->id === (int) $cliente->id)>{{ $cliente->nome }}</option>
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
                    <p class="mt-1 text-xs text-slate-500">O lead precisa ter todas essas tags para entrar e continuar na sequência.</p>
                    <div id="sequenceTagsIncluirContainer" class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-3" data-input-name="tags_incluir[]" data-tags='@json($tags->pluck("name")->values())'>
                        <div class="max-h-36 overflow-y-auto pr-1">
                            <div class="flex flex-wrap gap-2" data-chip-group></div>
                            <p class="hidden text-sm text-slate-400" data-empty-state>Nenhuma tag cadastrada.</p>
                        </div>
                        <div class="hidden" data-inputs></div>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tags a excluir</label>
                    <p class="mt-1 text-xs text-slate-500">Se o lead tiver qualquer uma dessas tags, a sequência será cancelada para ele.</p>
                    <div id="sequenceTagsExcluirContainer" class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-3" data-input-name="tags_excluir[]" data-tags='@json($tags->pluck("name")->values())'>
                        <div class="max-h-36 overflow-y-auto pr-1">
                            <div class="flex flex-wrap gap-2" data-chip-group></div>
                            <p class="hidden text-sm text-slate-400" data-empty-state>Nenhuma tag cadastrada.</p>
                        </div>
                        <div class="hidden" data-inputs></div>
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
            <form id="sequenceStepForm" method="POST" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="stepFormMethod" value="POST">
                <input type="hidden" name="sequence_id" id="stepSequenceId" value="">
                <input type="hidden" name="step_id" id="stepId" value="">

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

    <script>
        (function () {
            const modal = document.getElementById('sequenceModal');
            const openBtn = document.getElementById('openSequenceModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const clientSelect = document.getElementById('sequenceClient');
            const clientIdInput = document.getElementById('sequenceClientId');
            const connectionSelect = document.getElementById('sequenceConexao');
            const form = document.getElementById('sequenceForm');
            const title = document.getElementById('sequenceModalTitle');
            const hiddenId = document.getElementById('sequenceId');
            const nameInput = document.getElementById('sequenceName');
            const descriptionInput = document.getElementById('sequenceDescription');
            const activeInput = document.getElementById('sequenceActive');
            const tagsIncluirContainer = document.getElementById('sequenceTagsIncluirContainer');
            const tagsExcluirContainer = document.getElementById('sequenceTagsExcluirContainer');
            const defaultClientId = @json($clients->first()?->id);

            const connectionsUrlTemplate = "{{ route('cliente.sequences.cliente.conexoes', ['cliente' => '__CLIENT__']) }}";

            class TagChipSelect {
                constructor(root) {
                    this.root = root;
                    this.name = root.dataset.inputName;
                    this.baseOptions = this.normalizeValues(JSON.parse(root.dataset.tags || '[]'));
                    this.options = [...this.baseOptions];
                    this.selectedValues = new Set();
                    this.chipGroup = root.querySelector('[data-chip-group]');
                    this.emptyState = root.querySelector('[data-empty-state]');
                    this.inputsWrap = root.querySelector('[data-inputs]');
                    this.bind();
                    this.render();
                }

                bind() {
                    this.chipGroup?.addEventListener('click', (event) => {
                        const button = event.target.closest('[data-tag-value]');
                        if (!button) {
                            return;
                        }

                        this.toggleValue(button.dataset.tagValue);
                    });
                }

                normalizeValues(values) {
                    return [...new Set((values ?? [])
                        .map(value => String(value).trim())
                        .filter(value => value !== ''))];
                }

                toggleValue(value) {
                    if (!value) {
                        return;
                    }

                    if (this.selectedValues.has(value)) {
                        this.selectedValues.delete(value);
                    } else {
                        this.selectedValues.add(value);
                    }

                    this.syncButtons();
                    this.renderInputs();
                }

                ensureOption(value) {
                    if (!value || this.options.includes(value)) {
                        return;
                    }

                    this.options.push(value);
                }

                render() {
                    if (!this.chipGroup) {
                        return;
                    }

                    this.chipGroup.innerHTML = '';

                    if (!this.options.length) {
                        this.emptyState?.classList.remove('hidden');
                        this.renderInputs();
                        return;
                    }

                    this.emptyState?.classList.add('hidden');

                    this.options.forEach(tag => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.dataset.tagValue = tag;
                        button.textContent = tag;
                        button.className = 'inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-blue-200';
                        this.chipGroup.appendChild(button);
                    });

                    this.syncButtons();
                    this.renderInputs();
                }

                syncButtons() {
                    this.chipGroup?.querySelectorAll('[data-tag-value]').forEach(button => {
                        const selected = this.selectedValues.has(button.dataset.tagValue);
                        button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                        button.classList.toggle('border-blue-600', selected);
                        button.classList.toggle('bg-blue-600', selected);
                        button.classList.toggle('text-white', selected);
                        button.classList.toggle('shadow-sm', selected);
                        button.classList.toggle('border-slate-200', !selected);
                        button.classList.toggle('bg-white', !selected);
                        button.classList.toggle('text-slate-600', !selected);
                        button.classList.toggle('hover:border-slate-300', !selected);
                        button.classList.toggle('hover:bg-slate-100', !selected);
                    });
                }

                renderInputs() {
                    if (!this.inputsWrap) {
                        return;
                    }

                    this.inputsWrap.innerHTML = '';
                    this.selectedValues.forEach(value => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = this.name;
                        input.value = value;
                        this.inputsWrap.appendChild(input);
                    });
                }

                clear() {
                    this.selectedValues.clear();
                    this.options = [...this.baseOptions];
                    this.render();
                }

                setValues(values) {
                    const normalized = this.normalizeValues(values);
                    this.selectedValues = new Set(normalized);
                    this.options = [...this.baseOptions];
                    normalized.forEach(value => this.ensureOption(value));
                    this.render();
                }
            }

            const incluirSelect = new TagChipSelect(tagsIncluirContainer);
            const excluirSelect = new TagChipSelect(tagsExcluirContainer);

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const syncClientSelection = async (selectedConnectionId = null) => {
                const clienteId = defaultClientId ? String(defaultClientId) : '';

                clientSelect.value = clienteId;
                clientIdInput.value = clienteId;

                await populateConnections(clienteId, selectedConnectionId);
            };

            const resetForm = async () => {
                hiddenId.value = '';
                form.reset();
                title.textContent = 'Nova sequência';
                incluirSelect.clear();
                excluirSelect.clear();
                await syncClientSelection();
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

            openBtn.addEventListener('click', async () => {
                await resetForm();
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
                    nameInput.value = data.name ?? '';
                    descriptionInput.value = data.description ?? '';
                    activeInput.checked = data.active ?? true;
                    await syncClientSelection(data.conexao_id);
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

            const stepStoreTemplate = "{{ route('cliente.sequences.steps.store', ['sequence' => '__SEQ__']) }}";
            const stepUpdateTemplate = "{{ route('cliente.sequences.steps.update', ['sequence' => '__SEQ__', 'step' => '__STEP__']) }}";

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
