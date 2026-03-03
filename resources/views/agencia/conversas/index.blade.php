@extends('layouts.agencia')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Conversas</h2>
            <p class="text-sm text-slate-500">Todos os leads dos seus clientes, filtráveis por cliente, assistente, sequencia, data e tags.</p>
        </div>
        <div class="flex items-center gap-2">
            @php
                $exportCsv = route('agencia.conversas.export', array_merge(request()->query(), ['format' => 'csv']));
                $exportXlsx = route('agencia.conversas.export', array_merge(request()->query(), ['format' => 'xlsx']));
                $exportPdf = route('agencia.conversas.export', array_merge(request()->query(), ['format' => 'pdf']));
            @endphp
            <div class="relative">
                <button
                    type="button"
                    id="filtersToggle"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 flex items-center gap-2"
                >
                    Filtros
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="filtersMenu" class="hidden absolute right-0 mt-2 w-[90vw] max-w-[56rem] rounded-3xl border border-slate-200 bg-white p-4 shadow-lg">
                    <form method="GET" class="flex flex-wrap items-end gap-4 text-xs text-slate-500">
                        <input type="hidden" name="q" value="{{ request('q') }}">
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                        <div class="flex flex-1 min-w-[220px] flex-col gap-2" data-tag-mode-filter data-input-add-name="cliente_add[]" data-input-remove-name="cliente_remove[]">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] uppercase tracking-wide text-slate-400">Clientes</span>
                                <span class="text-[10px] text-slate-400">Escolha na lista: adicionar ou remover</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="inline-flex flex-wrap items-center gap-2" data-tag-chip-list="add"></div>
                                <div class="inline-flex flex-wrap items-center gap-2" data-tag-chip-list="remove"></div>
                            </div>
                            <div class="relative">
                                <input
                                    type="search"
                                    data-tag-search
                                    placeholder="Buscar cliente"
                                    class="w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                <div class="absolute left-0 right-0 z-10 mt-1 hidden max-h-56 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-lg" data-tag-options>
                                    @foreach($clients as $client)
                                        <div
                                            data-tag-option
                                            data-value="{{ $client->id }}"
                                            data-label="{{ $client->nome }}"
                                            class="flex items-center justify-between gap-2 px-3 py-2 text-xs text-slate-600 hover:bg-slate-50"
                                        >
                                            <span class="truncate">{{ $client->nome }}</span>
                                            <div class="flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    data-tag-option-action="add"
                                                    class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700"
                                                >
                                                    Adicionar
                                                </button>
                                                <button
                                                    type="button"
                                                    data-tag-option-action="remove"
                                                    class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700"
                                                >
                                                    Remover
                                                </button>
                                                <span data-tag-option-status class="text-[10px] text-slate-400">ID {{ $client->id }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="hidden" data-tag-inputs-add>
                                @foreach($clientAddFilter as $clientId)
                                    <input type="hidden" name="cliente_add[]" value="{{ $clientId }}">
                                @endforeach
                            </div>
                            <div class="hidden" data-tag-inputs-remove>
                                @foreach($clientRemoveFilter as $clientId)
                                    <input type="hidden" name="cliente_remove[]" value="{{ $clientId }}">
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-1 min-w-[220px] flex-col gap-2" data-tag-mode-filter data-input-add-name="assistant_add[]" data-input-remove-name="assistant_remove[]">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] uppercase tracking-wide text-slate-400">Assistentes</span>
                                <span class="text-[10px] text-slate-400">Escolha na lista: adicionar ou remover</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="inline-flex flex-wrap items-center gap-2" data-tag-chip-list="add"></div>
                                <div class="inline-flex flex-wrap items-center gap-2" data-tag-chip-list="remove"></div>
                            </div>
                            <div class="relative">
                                <input
                                    type="search"
                                    data-tag-search
                                    placeholder="Buscar assistente"
                                    class="w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                <div class="absolute left-0 right-0 z-10 mt-1 hidden max-h-56 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-lg" data-tag-options>
                                    @forelse($assistants as $assistant)
                                        <div
                                            data-tag-option
                                            data-value="{{ $assistant->id }}"
                                            data-label="{{ $assistant->name }}"
                                            class="flex items-center justify-between gap-2 px-3 py-2 text-xs text-slate-600 hover:bg-slate-50"
                                        >
                                            <span class="truncate">{{ $assistant->name }}</span>
                                            <div class="flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    data-tag-option-action="add"
                                                    class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700"
                                                >
                                                    Adicionar
                                                </button>
                                                <button
                                                    type="button"
                                                    data-tag-option-action="remove"
                                                    class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700"
                                                >
                                                    Remover
                                                </button>
                                                <span data-tag-option-status class="text-[10px] text-slate-400">ID {{ $assistant->id }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-3 py-2 text-xs text-slate-400">Nenhum assistente cadastrado.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="hidden" data-tag-inputs-add>
                                @foreach($assistantAddFilter as $assistantId)
                                    <input type="hidden" name="assistant_add[]" value="{{ $assistantId }}">
                                @endforeach
                            </div>
                            <div class="hidden" data-tag-inputs-remove>
                                @foreach($assistantRemoveFilter as $assistantId)
                                    <input type="hidden" name="assistant_remove[]" value="{{ $assistantId }}">
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-1 min-w-[280px] flex-col gap-2" data-tag-mode-filter data-input-add-name="tags_add[]" data-input-remove-name="tags_remove[]">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] uppercase tracking-wide text-slate-400">Tags</span>
                                <span class="text-[10px] text-slate-400">Escolha na lista: adicionar ou remover</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="inline-flex flex-wrap items-center gap-2" data-tag-chip-list="add"></div>
                                <div class="inline-flex flex-wrap items-center gap-2" data-tag-chip-list="remove"></div>
                            </div>
                            <div class="relative">
                                <input
                                    type="search"
                                    data-tag-search
                                    placeholder="Buscar tags"
                                    class="w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                <div class="absolute left-0 right-0 z-10 mt-1 hidden max-h-56 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-lg" data-tag-options>
                                    @forelse($tags as $tag)
                                        <div
                                            data-tag-option
                                            data-value="{{ $tag->id }}"
                                            data-label="{{ $tag->name }}"
                                            class="flex items-center justify-between gap-2 px-3 py-2 text-xs text-slate-600 hover:bg-slate-50"
                                        >
                                            <span class="truncate">{{ $tag->name }}</span>
                                            <div class="flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    data-tag-option-action="add"
                                                    class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700"
                                                >
                                                    Adicionar
                                                </button>
                                                <button
                                                    type="button"
                                                    data-tag-option-action="remove"
                                                    class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700"
                                                >
                                                    Remover
                                                </button>
                                                <span data-tag-option-status class="text-[10px] text-slate-400">Tag</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-3 py-2 text-xs text-slate-400">Nenhuma tag vinculada ainda.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="hidden" data-tag-inputs-add>
                                @foreach($tagAddFilter as $tagId)
                                    <input type="hidden" name="tags_add[]" value="{{ $tagId }}">
                                @endforeach
                            </div>
                            <div class="hidden" data-tag-inputs-remove>
                                @foreach($tagRemoveFilter as $tagId)
                                    <input type="hidden" name="tags_remove[]" value="{{ $tagId }}">
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-1 min-w-[280px] flex-col gap-2" data-tag-mode-filter data-input-add-name="sequence_add[]" data-input-remove-name="sequence_remove[]">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] uppercase tracking-wide text-slate-400">Sequencias</span>
                                <span class="text-[10px] text-slate-400">Escolha na lista: adicionar ou remover</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="inline-flex flex-wrap items-center gap-2" data-tag-chip-list="add"></div>
                                <div class="inline-flex flex-wrap items-center gap-2" data-tag-chip-list="remove"></div>
                            </div>
                            <div class="relative">
                                <input
                                    type="search"
                                    data-tag-search
                                    placeholder="Buscar sequencia"
                                    class="w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                <div class="absolute left-0 right-0 z-10 mt-1 hidden max-h-56 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-lg" data-tag-options>
                                    @forelse($sequences as $sequence)
                                        @php
                                            $sequenceLabel = $sequence->name . ($sequence->cliente?->nome ? ' (' . $sequence->cliente->nome . ')' : '');
                                        @endphp
                                        <div
                                            data-tag-option
                                            data-value="{{ $sequence->id }}"
                                            data-label="{{ $sequenceLabel }}"
                                            class="flex items-center justify-between gap-2 px-3 py-2 text-xs text-slate-600 hover:bg-slate-50"
                                        >
                                            <span class="truncate">{{ $sequence->name }}</span>
                                            <div class="flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    data-tag-option-action="add"
                                                    class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700"
                                                >
                                                    Adicionar
                                                </button>
                                                <button
                                                    type="button"
                                                    data-tag-option-action="remove"
                                                    class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700"
                                                >
                                                    Remover
                                                </button>
                                                <span data-tag-option-status class="text-[10px] text-slate-400">
                                                    {{ $sequence->cliente?->nome ? 'Cliente: ' . $sequence->cliente->nome : 'Sem cliente' }}
                                                </span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-3 py-2 text-xs text-slate-400">Nenhuma sequencia cadastrada.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="hidden" data-tag-inputs-add>
                                @foreach($sequenceAddFilter as $sequenceId)
                                    <input type="hidden" name="sequence_add[]" value="{{ $sequenceId }}">
                                @endforeach
                            </div>
                            <div class="hidden" data-tag-inputs-remove>
                                @foreach($sequenceRemoveFilter as $sequenceId)
                                    <input type="hidden" name="sequence_remove[]" value="{{ $sequenceId }}">
                                @endforeach
                            </div>
                        </div>

                        <div class="flex min-w-[220px] flex-col gap-1">
                            <span class="text-[10px] uppercase tracking-wide text-slate-400">Data</span>
                            <div class="flex gap-2">
                                <input
                                    type="date"
                                    name="date_start"
                                    value="{{ $dateStart }}"
                                    class="flex-1 rounded-2xl border border-slate-200 bg-white px-3 py-1.5 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                <input
                                    type="date"
                                    name="date_end"
                                    value="{{ $dateEnd }}"
                                    class="flex-1 rounded-2xl border border-slate-200 bg-white px-3 py-1.5 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                            </div>
                        </div>

                        <div class="flex min-w-[220px] flex-col gap-1">
                            <span class="text-[10px] uppercase tracking-wide text-slate-400">Ultima mensagem</span>
                            <div class="flex gap-2">
                                <input
                                    type="date"
                                    name="last_message_start"
                                    value="{{ $lastMessageStart }}"
                                    class="flex-1 rounded-2xl border border-slate-200 bg-white px-3 py-1.5 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                <input
                                    type="date"
                                    name="last_message_end"
                                    value="{{ $lastMessageEnd }}"
                                    class="flex-1 rounded-2xl border border-slate-200 bg-white px-3 py-1.5 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                            </div>
                        </div>

                        <div class="ml-auto flex items-center gap-2">
                            <button type="submit" class="rounded-2xl bg-blue-600 px-4 py-2 text-[12px] font-semibold text-white hover:bg-blue-700">Aplicar</button>
                            <a href="{{ route('agencia.conversas.index') }}" class="text-[12px] font-semibold text-slate-500">Limpar</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="relative">
                <button
                    type="button"
                    id="exportToggle"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 flex items-center gap-2"
                >
                    Ações
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="exportMenu" class="hidden absolute right-0 mt-1 w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-lg">
                    <p class="px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Ações</p>
                    <button
                        type="button"
                        id="activateBotForAllAction"
                        class="mt-1 flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm font-semibold text-emerald-700 hover:bg-emerald-50"
                    >
                        <span>Ativar bot para todos</span>
                        <span class="text-[11px] font-medium text-emerald-600">Filtros atuais</span>
                    </button>
                    <button
                        type="button"
                        id="deleteAllAction"
                        class="mt-1 flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50"
                    >
                        <span>Excluir todos</span>
                        <span class="text-[11px] font-medium text-rose-600">Filtros atuais</span>
                    </button>
                    <button
                        type="button"
                        id="removeSequenceAction"
                        class="mt-1 flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm font-semibold text-amber-700 hover:bg-amber-50"
                    >
                        <span>Sequencia</span>
                        <span class="text-[11px] font-medium text-amber-600">Remover dos filtros</span>
                    </button>
                    <button
                        type="button"
                        id="bulkTagsAction"
                        class="mt-1 flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm font-semibold text-sky-700 hover:bg-sky-50"
                    >
                        <span>Tags</span>
                        <span class="text-[11px] font-medium text-sky-600">Filtros atuais</span>
                    </button>
                    <div class="my-2 border-t border-slate-100"></div>
                    <p class="px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Exportar</p>
                    <a href="{{ $exportXlsx }}" data-export-format="xlsx" class="mt-1 block rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">XLSX</a>
                    <a href="{{ $exportCsv }}" data-export-format="csv" class="block rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">CSV</a>
                    <a href="{{ $exportPdf }}" data-export-format="pdf" class="block rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">PDF</a>
                </div>
            </div>
            <button
                type="button"
                id="openClienteLeadForm"
                class="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
            >Adicionar</button>
        </div>
    </div>

    <div class="mt-4 border-b border-slate-200"></div>

    <div class="mt-4 mb-4 flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[240px]">
            <input
                type="search"
                id="leadSearchInput"
                placeholder="Buscar por nome ou telefone (min. 3 caracteres)"
                value="{{ request('q') }}"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm focus:border-slate-400 focus:outline-none"
            >
        </div>
        <button
            type="button"
            id="leadSearchClear"
            class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50"
        >Limpar</button>
    </div>

    <div id="leadTableContainer">
        @include('agencia.conversas._table', ['leads' => $leads])
    </div>

    <div id="agenciaClienteLeadFormModal" class="fixed inset-0 z-50 hidden bg-black/50">
        <div class="flex h-full w-full flex-col bg-white">
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-6 py-4">
                <h3 id="clienteLeadFormTitle" class="text-lg font-semibold text-slate-900">Adicionar lead</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-form-close>x</button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                <div class="mx-auto w-full max-w-6xl">
                    <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 p-1 text-xs font-semibold text-slate-500">
                        <button type="button" data-form-tab="manual" class="rounded-full bg-white px-4 py-1.5 text-slate-700 shadow-sm">Adicionar</button>
                        <button type="button" data-form-tab="import" class="rounded-full px-4 py-1.5 text-slate-500">Importar lista</button>
                    </div>

                    <form
                        id="clienteLeadForm"
                        method="POST"
                        action="{{ route('agencia.conversas.store') }}"
                        data-create-route="{{ route('agencia.conversas.store') }}"
                        data-update-route-template="{{ route('agencia.conversas.update', ['clienteLead' => '__LEAD_ID__']) }}"
                        class="mt-4 space-y-4"
                    >
                        @csrf
                        <input type="hidden" name="_method" value="POST" id="clienteLeadFormMethod">

                        <div class="space-y-2">
                            <span class="text-[11px] uppercase tracking-wide text-slate-400">Cliente</span>
                            <select
                                id="clienteLeadFormClient"
                                name="cliente_id"
                                required
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            >
                                <option value="">Selecione um cliente</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div data-chip-select="lead-sequences" data-input-name="sequence_ids[]">
                            <span class="text-[11px] uppercase tracking-wide text-slate-400">Sequências</span>
                            <div class="mt-2 flex flex-wrap gap-2" data-chip-list></div>
                            <div class="relative mt-2">
                                <input
                                    type="search"
                                    data-chip-search
                                    placeholder="Buscar sequência"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                <div class="absolute left-0 right-0 z-10 mt-1 hidden max-h-56 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-lg" data-chip-options></div>
                            </div>
                            <div class="hidden" data-chip-inputs></div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="clienteLeadFormBot" name="bot_enabled" value="1" checked class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <label for="clienteLeadFormBot" class="text-sm text-slate-600">Bot habilitado</label>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <input
                                id="clienteLeadFormPhone"
                                name="phone"
                                type="text"
                                placeholder="Telefone"
                                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            >
                            <input
                                id="clienteLeadFormName"
                                name="name"
                                type="text"
                                placeholder="Nome"
                                class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            >
                        </div>

                        <div>
                            <textarea
                                id="clienteLeadFormInfo"
                                name="info"
                                rows="3"
                                placeholder="Informações adicionais"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            ></textarea>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] uppercase tracking-wide text-slate-400">Campos personalizados</span>
                                <button
                                    type="button"
                                    id="leadCustomFieldAddBtn"
                                    class="rounded-full border border-slate-300 px-3 py-1 text-[11px] font-semibold text-slate-600 hover:border-slate-500 hover:text-slate-900"
                                >Adicionar campo personalizado</button>
                            </div>
                            <div
                                id="leadCustomFieldsEmpty"
                                class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500"
                            >Selecione um cliente e adicione os campos personalizados do lead.</div>
                            <div id="leadCustomFieldsRows" class="space-y-2"></div>
                        </div>

                        <div data-chip-select="lead-tags" data-input-name="tags[]">
                            <span class="text-[11px] uppercase tracking-wide text-slate-400">Tags</span>
                            <div class="mt-2 flex flex-wrap gap-2" data-chip-list></div>
                            <div class="relative mt-2">
                                <input
                                    type="search"
                                    data-chip-search
                                    placeholder="Buscar tags"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                <div class="absolute left-0 right-0 z-10 mt-1 hidden max-h-56 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-lg" data-chip-options>
                                    @forelse($tags as $tag)
                                        <button
                                            type="button"
                                            data-chip-option
                                            data-value="{{ $tag->id }}"
                                            data-label="{{ $tag->name }}"
                                            class="flex w-full items-center justify-between px-3 py-2 text-left text-xs text-slate-600 hover:bg-slate-50"
                                        >
                                            <span>{{ $tag->name }}</span>
                                            <span class="text-[10px] text-slate-400">Tag</span>
                                        </button>
                                    @empty
                                        <div class="px-3 py-2 text-xs text-slate-400">Nenhuma tag cadastrada.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="hidden" data-chip-inputs></div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" data-form-close class="rounded-2xl border border-slate-200 px-4 py-1 text-[12px] font-semibold text-slate-600 hover:border-slate-400">Cancelar</button>
                            <button type="submit" id="clienteLeadFormSubmit" class="rounded-2xl bg-blue-600 px-4 py-1 text-[12px] font-semibold text-white hover:bg-blue-700">Salvar</button>
                        </div>
                    </form>

                    <form
                        id="clienteLeadImportForm"
                        method="POST"
                        data-preview-url="{{ route('agencia.conversas.preview') }}"
                        action="{{ route('agencia.conversas.import') }}"
                        enctype="multipart/form-data"
                        class="mt-4 hidden space-y-4"
                    >
                        @csrf

                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="space-y-2">
                                <span class="text-[11px] uppercase tracking-wide text-slate-400">Cliente</span>
                                <select
                                    id="clienteLeadImportClient"
                                    name="cliente_id"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                    <option value="">Selecione um cliente</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2">
                                <span class="text-[11px] uppercase tracking-wide text-slate-400">Arquivo CSV/XLSX</span>
                                <input
                                    type="file"
                                    name="csv_file"
                                    accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                    required
                                    data-csv-file
                                    class="w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-2 text-sm text-slate-600 focus:border-slate-400 focus:outline-none"
                                >
                            </div>
                            <div class="space-y-2">
                                <span class="text-[11px] uppercase tracking-wide text-slate-400">Planilha tem cabeçalho?</span>
                                <select
                                    name="has_header"
                                    data-csv-has-header
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                    <option value="yes" selected>Sim</option>
                                    <option value="no">Não</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <span class="text-[11px] uppercase tracking-wide text-slate-400">Delimitador</span>
                                <select
                                    name="delimiter"
                                    data-csv-delimiter
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                                >
                                    <option value="semicolon">; (padrão)</option>
                                    <option value="comma">, (vírgula)</option>
                                </select>
                                <p class="text-[11px] text-slate-400">Para XLSX, o delimitador é ignorado.</p>
                            </div>
                            <div data-chip-select="import-tags" data-input-name="tags[]" class="space-y-2">
                                <span class="text-[11px] uppercase tracking-wide text-slate-400">Tags para todos</span>
                                <div class="relative">
                                    <input
                                        type="search"
                                        data-chip-search
                                        placeholder="Buscar tags"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                                    >
                                    <div class="absolute left-0 right-0 z-10 mt-1 hidden max-h-56 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-lg" data-chip-options>
                                        @forelse($tags as $tag)
                                            <button
                                                type="button"
                                                data-chip-option
                                                data-value="{{ $tag->id }}"
                                                data-label="{{ $tag->name }}"
                                                class="flex w-full items-center justify-between px-3 py-2 text-left text-xs text-slate-600 hover:bg-slate-50"
                                            >
                                                <span>{{ $tag->name }}</span>
                                                <span class="text-[10px] text-slate-400">Tag</span>
                                            </button>
                                        @empty
                                            <div class="px-3 py-2 text-xs text-slate-400">Nenhuma tag cadastrada.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2" data-chip-list></div>
                                <div class="hidden" data-chip-inputs>
                                    @foreach((array) old('tags', []) as $tagId)
                                        <input type="hidden" name="tags[]" value="{{ $tagId }}">
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="bot_enabled" value="0">
                            <input
                                type="checkbox"
                                id="clienteLeadImportBot"
                                name="bot_enabled"
                                value="1"
                                checked
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            >
                            <label for="clienteLeadImportBot" class="text-sm text-slate-600">Bot habilitado para leads importados</label>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">Prévia e mapeamento (1 linha)</p>
                                    <p class="text-xs text-slate-500">Coluna da planilha na esquerda e campo de destino na direita.</p>
                                </div>
                                <span id="previewPhoneStatus" class="text-[11px] font-semibold text-slate-500">Telefone: -</span>
                            </div>
                            <div id="previewEmpty" class="mt-3 rounded-xl border border-dashed border-slate-300 bg-white px-4 py-3 text-xs text-slate-500">
                                Selecione um arquivo para visualizar a prévia.
                            </div>
                            <div id="importMappingWrap" class="mt-3 hidden overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                <table class="min-w-full text-xs text-slate-700">
                                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold">Coluna da planilha</th>
                                            <th class="px-3 py-2 text-left font-semibold">Associar com</th>
                                        </tr>
                                    </thead>
                                    <tbody id="importMappingRows" class="divide-y divide-slate-100"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" data-form-close class="rounded-2xl border border-slate-200 px-4 py-1 text-[12px] font-semibold text-slate-600 hover:border-slate-400">Cancelar</button>
                            <button type="submit" class="rounded-2xl bg-blue-600 px-4 py-1 text-[12px] font-semibold text-white hover:bg-blue-700">Importar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="agenciaClienteLeadModal" class="fixed inset-0 z-50 hidden flex items-center justify-center overflow-auto bg-black/50 px-4 py-6">
        <div class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Detalhes do lead</h3>
                <button type="button" data-view-close class="text-slate-500 hover:text-slate-700">x</button>
            </div>
            <div class="mt-4 grid gap-4 text-sm text-slate-600 md:grid-cols-2">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">ID</p>
                    <p id="viewLeadId" class="font-medium text-slate-900"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Cliente</p>
                    <p id="viewLeadCliente" class="font-medium text-slate-900"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Telefone</p>
                    <p id="viewLeadPhone"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Bot</p>
                    <p id="viewLeadBot"></p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Lead</p>
                    <p id="viewLeadName"></p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Informações</p>
                    <p id="viewLeadInfo"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Criado em</p>
                    <p id="viewLeadCreatedAt"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Tags</p>
                    <div id="viewLeadTags" class="mt-2 flex flex-wrap gap-2 text-[11px]"></div>
                </div>
                <div class="md:col-span-2">
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Campos personalizados</p>
                    <div id="viewLeadCustomFields" class="mt-2 space-y-2"></div>
                </div>
            </div>
            <div class="mt-6">
                <h4 class="text-sm font-semibold text-slate-700">Assistentes relacionados</h4>
                <div class="mt-2 overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-400 uppercase tracking-wide">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Assistente</th>
                                <th class="px-3 py-2 text-left font-semibold">Versão</th>
                                <th class="px-3 py-2 text-left font-semibold">Conv ID</th>
                                <th class="px-3 py-2 text-left font-semibold">Criado em</th>
                                <th class="px-3 py-2 text-left font-semibold">Acoes</th>
                            </tr>
                        </thead>
                        <tbody id="viewLeadAssistants" class="border-t border-slate-100 text-slate-700">
                            <tr>
                                <td colspan="5" class="px-3 py-2 text-center text-slate-400">Nenhum assistente associado.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="leadMessageModal" class="fixed inset-0 z-50 hidden flex items-center justify-center overflow-auto bg-black/50 px-4 py-6">
        <div class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Enviar mensagem</h3>
                <button type="button" data-message-close class="text-slate-500 hover:text-slate-700">x</button>
            </div>
            <p class="mt-2 text-xs text-slate-500">
                Lead: <span id="messageLeadName" class="font-semibold text-slate-700"></span>
                <span class="mx-1 text-slate-300">|</span>
                Assistente: <span id="messageAssistantName" class="font-semibold text-slate-700"></span>
            </p>
            <div class="mt-4 inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                <button
                    type="button"
                    data-message-tab-button="text"
                    class="rounded-lg px-3 py-1 text-xs font-semibold text-slate-600"
                >Dentro da janela 24h</button>
                <button
                    type="button"
                    data-message-tab-button="template"
                    class="rounded-lg px-3 py-1 text-xs font-semibold text-slate-600"
                >Fora da janela 24h</button>
            </div>
            <form id="leadMessageForm" class="mt-4 space-y-4">
                <div id="leadMessageTabText" class="space-y-4">
                    <div id="leadMessageConexaoWrap" class="hidden rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <label for="leadMessageConexao" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            Conexao (obrigatorio neste caso)
                        </label>
                        <select
                            id="leadMessageConexao"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                        >
                            <option value="" selected>Selecione a conexao</option>
                        </select>
                        <p id="leadMessageConexaoHint" class="mt-1 text-[11px] text-slate-500">Escolha a conexao para definir o assistente.</p>
                    </div>

                    <textarea
                        id="leadMessageText"
                        rows="4"
                        maxlength="2000"
                        placeholder="Digite a mensagem..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                    ></textarea>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <label for="leadMessageScheduledFor" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            Agendar para (opcional)
                        </label>
                        <input
                            id="leadMessageScheduledFor"
                            type="datetime-local"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                        >
                        <p id="leadMessageTimezoneHint" class="mt-1 text-[11px] text-slate-500">Timezone: America/Sao_Paulo</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-3">
                        <p id="leadScheduledSummary" class="text-xs text-slate-600">Carregando agendamentos...</p>
                        <div id="leadScheduledList" class="mt-2 space-y-2"></div>
                    </div>
                </div>

                <div id="leadMessageTabTemplate" class="hidden space-y-4">
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                        Fora da janela de 24h, a WhatsApp Cloud exige envio por modelo aprovado.
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <label for="leadTemplateConexao" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            Conexao Cloud
                        </label>
                        <select
                            id="leadTemplateConexao"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                        >
                            <option value="" selected>Selecione a conexao cloud</option>
                        </select>
                        <p id="leadTemplateWindowStatus" class="mt-1 text-[11px] text-slate-500">Carregando status da janela...</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <label for="leadTemplateSelect" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            Modelo aprovado
                        </label>
                        <select
                            id="leadTemplateSelect"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                        >
                            <option value="" selected>Selecione um modelo</option>
                        </select>
                    </div>

                    <div id="leadTemplateVariablesWrap" class="hidden rounded-2xl border border-slate-200 bg-white p-3">
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Variáveis do modelo</p>
                        <div id="leadTemplateVariables" class="grid gap-3 md:grid-cols-2"></div>
                    </div>
                </div>

                <p id="leadMessageError" class="hidden rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-xs text-rose-600"></p>
                <p id="leadMessageSuccess" class="hidden rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs text-emerald-700"></p>
                <div class="flex justify-end gap-3">
                    <button type="button" data-message-close class="rounded-2xl border border-slate-200 px-4 py-1 text-[12px] font-semibold text-slate-600 hover:border-slate-400">Cancelar</button>
                    <button type="submit" id="leadMessageSubmit" class="rounded-2xl bg-blue-600 px-4 py-1 text-[12px] font-semibold text-white hover:bg-blue-700">Enviar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="removeSequenceModal" class="fixed inset-0 z-50 hidden flex items-center justify-center overflow-auto bg-black/50 px-4 py-6">
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Remover sequencias</h3>
                <button type="button" data-remove-sequence-close class="text-slate-500 hover:text-slate-700">x</button>
            </div>
            <p class="mt-2 text-xs text-slate-500">Selecione as sequencias que serao removidas.</p>

            <div class="mt-4 space-y-2" data-chip-select="remove-sequences" data-input-name="sequence_ids[]">
                <div class="flex flex-wrap gap-2" data-chip-list></div>
                <div class="relative">
                    <input
                        type="search"
                        data-chip-search
                        placeholder="Buscar sequencia"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                    >
                    <div class="absolute left-0 right-0 z-10 mt-1 hidden max-h-56 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-lg" data-chip-options></div>
                </div>
                <div class="hidden" data-chip-inputs></div>
            </div>

            <p id="removeSequenceModalHint" class="mt-3 text-[11px] text-slate-500">Carregando sequencias...</p>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" data-remove-sequence-close class="rounded-2xl border border-slate-200 px-4 py-1 text-[12px] font-semibold text-slate-600 hover:border-slate-400">Cancelar</button>
                <button type="button" id="removeSequenceSubmit" class="rounded-2xl bg-rose-600 px-4 py-1 text-[12px] font-semibold text-white hover:bg-rose-700">Remover sequencias</button>
            </div>
        </div>
    </div>

    <div id="bulkTagsModal" class="fixed inset-0 z-50 hidden flex items-center justify-center overflow-auto bg-black/50 px-4 py-6">
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Tags em lote</h3>
                <button type="button" data-bulk-tags-close class="text-slate-500 hover:text-slate-700">x</button>
            </div>
            <p class="mt-2 text-xs text-slate-500">A ação será aplicada a todos os leads dos filtros atuais.</p>

            <div class="mt-4">
                <label for="bulkTagsOperation" class="text-[11px] uppercase tracking-wide text-slate-400">Operação</label>
                <select
                    id="bulkTagsOperation"
                    class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                >
                    <option value="add">Adicionar tags</option>
                    <option value="remove">Remover tags</option>
                </select>
            </div>

            <div class="mt-4 space-y-2" data-chip-select="bulk-tags" data-input-name="tag_ids[]">
                <div class="flex flex-wrap gap-2" data-chip-list></div>
                <div class="relative">
                    <input
                        type="search"
                        data-chip-search
                        placeholder="Buscar tags"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                    >
                    <div class="absolute left-0 right-0 z-10 mt-1 hidden max-h-56 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-lg" data-chip-options>
                        @forelse($tags as $tag)
                            <button
                                type="button"
                                data-chip-option
                                data-value="{{ $tag->id }}"
                                data-label="{{ $tag->name }}"
                                class="flex w-full items-center justify-between px-3 py-2 text-left text-xs text-slate-600 hover:bg-slate-50"
                            >
                                <span>{{ $tag->name }}</span>
                                <span class="text-[10px] text-slate-400">Tag</span>
                            </button>
                        @empty
                            <div class="px-3 py-2 text-xs text-slate-400">Nenhuma tag cadastrada.</div>
                        @endforelse
                    </div>
                </div>
                <div class="hidden" data-chip-inputs></div>
            </div>

            <p id="bulkTagsModalHint" class="mt-3 text-[11px] text-slate-500">Selecione as tags e a operação desejada.</p>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" data-bulk-tags-close class="rounded-2xl border border-slate-200 px-4 py-1 text-[12px] font-semibold text-slate-600 hover:border-slate-400">Cancelar</button>
                <button type="button" id="bulkTagsSubmit" class="rounded-2xl bg-sky-600 px-4 py-1 text-[12px] font-semibold text-white hover:bg-sky-700">Aplicar tags</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('agenciaClienteLeadModal');
            const assistantBody = document.getElementById('viewLeadAssistants');
            const tagsContainer = document.getElementById('viewLeadTags');
            const customFieldsContainer = document.getElementById('viewLeadCustomFields');
            const formModal = document.getElementById('agenciaClienteLeadFormModal');
            const clientLeadForm = document.getElementById('clienteLeadForm');
            const importForm = document.getElementById('clienteLeadImportForm');
            const clientLeadFormMethod = document.getElementById('clienteLeadFormMethod');
            const clientLeadFormTitle = document.getElementById('clienteLeadFormTitle');
            const clientLeadFormSubmit = document.getElementById('clienteLeadFormSubmit');
            const clientLeadFormBot = document.getElementById('clienteLeadFormBot');
            const clientLeadFormPhone = document.getElementById('clienteLeadFormPhone');
            const clientLeadFormName = document.getElementById('clienteLeadFormName');
            const clientLeadFormInfo = document.getElementById('clienteLeadFormInfo');
            const clientLeadFormSelect = document.getElementById('clienteLeadFormClient');
            const leadCustomFieldAddBtn = document.getElementById('leadCustomFieldAddBtn');
            const leadCustomFieldsRows = document.getElementById('leadCustomFieldsRows');
            const leadCustomFieldsEmpty = document.getElementById('leadCustomFieldsEmpty');
            const addLeadBtn = document.getElementById('openClienteLeadForm');
            const formTabs = document.querySelectorAll('[data-form-tab]');
            const importTabButton = document.querySelector('[data-form-tab="import"]');
            const csvFileInput = document.querySelector('[data-csv-file]');
            const csvDelimiterSelect = document.querySelector('[data-csv-delimiter]');
            const csvHasHeaderSelect = document.querySelector('[data-csv-has-header]');
            const importClientSelect = document.getElementById('clienteLeadImportClient');
            const exportToggle = document.getElementById('exportToggle');
            const exportMenu = document.getElementById('exportMenu');
            const activateBotForAllAction = document.getElementById('activateBotForAllAction');
            const deleteAllAction = document.getElementById('deleteAllAction');
            const removeSequenceAction = document.getElementById('removeSequenceAction');
            const bulkTagsAction = document.getElementById('bulkTagsAction');
            const exportMenuLinks = Array.from(document.querySelectorAll('[data-export-format]'));
            const filtersToggle = document.getElementById('filtersToggle');
            const filtersMenu = document.getElementById('filtersMenu');
            const dualModeFilterRoots = Array.from(document.querySelectorAll('[data-tag-mode-filter]'));
            const leadSearchInput = document.getElementById('leadSearchInput');
            const leadSearchClear = document.getElementById('leadSearchClear');
            const leadTableContainer = document.getElementById('leadTableContainer');
            const filtersQueryInput = filtersMenu?.querySelector('input[name="q"]');
            const filtersSortByInput = filtersMenu?.querySelector('input[name="sort_by"]');
            const filtersSortDirInput = filtersMenu?.querySelector('input[name="sort_dir"]');
            const convIdBaseUrl = @json(route('agencia.openai.conv_id'));
            const previewEmpty = document.getElementById('previewEmpty');
            const importMappingWrap = document.getElementById('importMappingWrap');
            const importMappingRows = document.getElementById('importMappingRows');
            const previewPhoneStatus = document.getElementById('previewPhoneStatus');
            const previewEmptyDefault = previewEmpty?.textContent || '';
            const sequencesUrlTemplate = @json(route('agencia.sequences.cliente.sequences', ['cliente' => '__CLIENT__']));
            const conexoesUrlTemplate = @json(route('agencia.sequences.cliente.conexoes', ['cliente' => '__CLIENT__']));
            const messageModal = document.getElementById('leadMessageModal');
            const messageForm = document.getElementById('leadMessageForm');
            const messageText = document.getElementById('leadMessageText');
            const messageScheduledFor = document.getElementById('leadMessageScheduledFor');
            const messageTimezoneHint = document.getElementById('leadMessageTimezoneHint');
            const scheduledSummary = document.getElementById('leadScheduledSummary');
            const scheduledList = document.getElementById('leadScheduledList');
            const messageLeadName = document.getElementById('messageLeadName');
            const messageAssistantName = document.getElementById('messageAssistantName');
            const messageConexaoWrap = document.getElementById('leadMessageConexaoWrap');
            const messageConexaoSelect = document.getElementById('leadMessageConexao');
            const messageConexaoHint = document.getElementById('leadMessageConexaoHint');
            const messageError = document.getElementById('leadMessageError');
            const messageSuccess = document.getElementById('leadMessageSuccess');
            const messageSubmit = document.getElementById('leadMessageSubmit');
            const removeSequenceModal = document.getElementById('removeSequenceModal');
            const removeSequenceSubmit = document.getElementById('removeSequenceSubmit');
            const removeSequenceModalHint = document.getElementById('removeSequenceModalHint');
            const removeSequenceChipRoot = document.querySelector('[data-chip-select="remove-sequences"]');
            const bulkTagsModal = document.getElementById('bulkTagsModal');
            const bulkTagsSubmit = document.getElementById('bulkTagsSubmit');
            const bulkTagsModalHint = document.getElementById('bulkTagsModalHint');
            const bulkTagsOperation = document.getElementById('bulkTagsOperation');
            const bulkTagsChipRoot = document.querySelector('[data-chip-select="bulk-tags"]');
            const messageTabButtons = document.querySelectorAll('[data-message-tab-button]');
            const messageTabText = document.getElementById('leadMessageTabText');
            const messageTabTemplate = document.getElementById('leadMessageTabTemplate');
            const templateConexaoSelect = document.getElementById('leadTemplateConexao');
            const templateWindowStatus = document.getElementById('leadTemplateWindowStatus');
            const templateSelect = document.getElementById('leadTemplateSelect');
            const templateVariablesWrap = document.getElementById('leadTemplateVariablesWrap');
            const templateVariablesContainer = document.getElementById('leadTemplateVariables');
            const sendMessageUrlTemplate = @json(route('agencia.conversas.send-message', ['clienteLead' => '__LEAD_ID__']));
            const cloudSendContextUrlTemplate = @json(route('agencia.conversas.cloud-send-context', ['clienteLead' => '__LEAD_ID__']));
            const scheduledMessagesUrlTemplate = @json(route('agencia.conversas.scheduled-messages.index', ['clienteLead' => '__LEAD_ID__']));
            const cancelScheduledMessageUrlTemplate = @json(route('agencia.conversas.scheduled-messages.cancel', ['scheduledMessage' => '__SCHEDULE_ID__']));
            const activateBotForAllUrl = @json(route('agencia.conversas.activate-bot-all'));
            const destroyAllUrl = @json(route('agencia.conversas.destroy-all'));
            const removeSequencesOptionsUrl = @json(route('agencia.conversas.remove-sequences.options'));
            const removeSequencesUrl = @json(route('agencia.conversas.remove-sequences'));
            const bulkTagsUrl = @json(route('agencia.conversas.tags.bulk'));
            const exportBaseUrl = @json(route('agencia.conversas.export'));
            const availableLeadCustomFields = @json($leadCustomFieldsData, JSON_UNESCAPED_UNICODE);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            let previewHeaders = [];
            let previewSampleRow = [];
            const chipSelects = {};
            let currentLead = null;
            let currentAssistant = null;
            let messageRequiresConexao = false;
            let messageMode = 'text';
            const messageConexoesCache = new Map();
            let currentCloudContext = null;

            const buildFilteredActionUrl = (baseUrl, extraParams = {}) => {
                const url = new URL(baseUrl, window.location.origin);
                const currentUrl = new URL(window.location.href);

                currentUrl.searchParams.forEach((value, key) => {
                    if (key === 'page') {
                        return;
                    }
                    url.searchParams.append(key, value);
                });

                Object.entries(extraParams).forEach(([key, value]) => {
                    if (value === null || value === undefined || value === '') {
                        return;
                    }

                    url.searchParams.delete(key);
                    url.searchParams.append(key, String(value));
                });

                return url.toString();
            };

            const refreshExportMenuLinks = () => {
                exportMenuLinks.forEach(link => {
                    const format = link.dataset.exportFormat;
                    if (!format) {
                        return;
                    }

                    link.href = buildFilteredActionUrl(exportBaseUrl, { format });
                });
            };

            if (exportToggle && exportMenu) {
                exportToggle.addEventListener('click', () => {
                    refreshExportMenuLinks();
                    exportMenu.classList.toggle('hidden');
                });
                document.addEventListener('click', (event) => {
                    if (!exportMenu.contains(event.target) && !exportToggle.contains(event.target)) {
                        exportMenu.classList.add('hidden');
                    }
                });
            }

            activateBotForAllAction?.addEventListener('click', async () => {
                const confirmed = window.confirm('Ativar o bot para todos os leads encontrados pelos filtros atuais? Esta ação considera todas as páginas.');
                if (!confirmed) {
                    return;
                }

                exportMenu?.classList.add('hidden');

                const originalHtml = activateBotForAllAction.innerHTML;
                activateBotForAllAction.disabled = true;
                activateBotForAllAction.classList.add('opacity-60', 'pointer-events-none');
                activateBotForAllAction.textContent = 'Ativando...';

                try {
                    const response = await fetch(buildFilteredActionUrl(activateBotForAllUrl), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok) {
                        throw new Error(payload.message || 'Nao foi possivel ativar o bot em lote.');
                    }

                    window.alert(payload.message || 'Bot ativado para os leads filtrados.');
                    await fetchLeads(window.location.href);
                } catch (error) {
                    window.alert(error.message || 'Nao foi possivel ativar o bot em lote.');
                } finally {
                    activateBotForAllAction.disabled = false;
                    activateBotForAllAction.classList.remove('opacity-60', 'pointer-events-none');
                    activateBotForAllAction.innerHTML = originalHtml || '<span>Ativar bot para todos</span>';
                }
            });

            deleteAllAction?.addEventListener('click', async () => {
                const confirmed = window.confirm('Excluir todos os leads encontrados pelos filtros atuais? Esta ação é irreversível e considera todas as páginas.');
                if (!confirmed) {
                    return;
                }

                exportMenu?.classList.add('hidden');

                const originalHtml = deleteAllAction.innerHTML;
                deleteAllAction.disabled = true;
                deleteAllAction.classList.add('opacity-60', 'pointer-events-none');
                deleteAllAction.textContent = 'Excluindo...';

                try {
                    const response = await fetch(buildFilteredActionUrl(destroyAllUrl), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok) {
                        throw new Error(payload.message || 'Nao foi possivel excluir os leads em lote.');
                    }

                    window.alert(payload.message || 'Leads excluidos com sucesso.');

                    const refreshUrl = new URL(window.location.href);
                    refreshUrl.searchParams.delete('page');
                    await fetchLeads(refreshUrl.toString(), { pushState: true });
                } catch (error) {
                    window.alert(error.message || 'Nao foi possivel excluir os leads em lote.');
                } finally {
                    deleteAllAction.disabled = false;
                    deleteAllAction.classList.remove('opacity-60', 'pointer-events-none');
                    deleteAllAction.innerHTML = originalHtml || '<span>Excluir todos</span>';
                }
            });

            const closeBulkTagsModal = () => {
                bulkTagsModal?.classList.add('hidden');
            };

            const setBulkTagsModalHint = (message, isError = false) => {
                if (!bulkTagsModalHint) {
                    return;
                }

                bulkTagsModalHint.textContent = message;
                bulkTagsModalHint.className = isError
                    ? 'mt-3 text-[11px] text-rose-600'
                    : 'mt-3 text-[11px] text-slate-500';
            };

            const getBulkTagsSelectedIds = () => {
                if (!bulkTagsChipRoot) {
                    return [];
                }

                return Array.from(
                    bulkTagsChipRoot.querySelectorAll('[data-chip-inputs] input[name="tag_ids[]"]')
                )
                    .map((input) => Number(input.value || 0))
                    .filter((id) => Number.isInteger(id) && id > 0);
            };

            bulkTagsAction?.addEventListener('click', () => {
                exportMenu?.classList.add('hidden');
                bulkTagsModal?.classList.remove('hidden');
                if (bulkTagsOperation) {
                    bulkTagsOperation.value = 'add';
                }
                setBulkTagsModalHint('Selecione as tags e a operação desejada.');
                chipSelects['bulk-tags']?.setSelected([]);
            });

            document.querySelectorAll('[data-bulk-tags-close]').forEach((button) => {
                button.addEventListener('click', closeBulkTagsModal);
            });

            bulkTagsModal?.addEventListener('click', (event) => {
                if (event.target === bulkTagsModal) {
                    closeBulkTagsModal();
                }
            });

            bulkTagsSubmit?.addEventListener('click', async () => {
                const tagIds = getBulkTagsSelectedIds();
                if (!tagIds.length) {
                    window.alert('Selecione ao menos uma tag.');
                    return;
                }

                const action = bulkTagsOperation?.value === 'remove' ? 'remove' : 'add';
                const confirmed = action === 'remove'
                    ? window.confirm('Remover as tags selecionadas de todos os leads filtrados?')
                    : window.confirm('Adicionar as tags selecionadas em todos os leads filtrados?');
                if (!confirmed) {
                    return;
                }

                const originalHtml = bulkTagsSubmit.innerHTML;
                bulkTagsSubmit.disabled = true;
                bulkTagsSubmit.classList.add('opacity-60', 'pointer-events-none');
                bulkTagsSubmit.textContent = action === 'remove' ? 'Removendo...' : 'Adicionando...';

                try {
                    const response = await fetch(buildFilteredActionUrl(bulkTagsUrl), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            action,
                            tag_ids: tagIds,
                        }),
                    });

                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok) {
                        throw new Error(payload.message || 'Nao foi possivel atualizar tags em lote.');
                    }

                    window.alert(payload.message || 'Tags atualizadas com sucesso.');
                    closeBulkTagsModal();

                    const refreshUrl = new URL(window.location.href);
                    refreshUrl.searchParams.delete('page');
                    await fetchLeads(refreshUrl.toString(), { pushState: true });
                } catch (error) {
                    window.alert(error.message || 'Nao foi possivel atualizar tags em lote.');
                } finally {
                    bulkTagsSubmit.disabled = false;
                    bulkTagsSubmit.classList.remove('opacity-60', 'pointer-events-none');
                    bulkTagsSubmit.innerHTML = originalHtml || 'Aplicar tags';
                }
            });

            const closeRemoveSequenceModal = () => {
                removeSequenceModal?.classList.add('hidden');
            };

            const setRemoveSequenceModalHint = (message, isError = false) => {
                if (!removeSequenceModalHint) {
                    return;
                }

                removeSequenceModalHint.textContent = message;
                removeSequenceModalHint.className = isError
                    ? 'mt-3 text-[11px] text-rose-600'
                    : 'mt-3 text-[11px] text-slate-500';
            };

            const renderRemoveSequenceOptions = (items = []) => {
                if (!removeSequenceChipRoot) {
                    return;
                }

                const optionsWrap = removeSequenceChipRoot.querySelector('[data-chip-options]');
                if (!optionsWrap) {
                    return;
                }

                optionsWrap.innerHTML = items.length
                    ? items.map((item) => {
                        const id = Number(item?.id || 0);
                        const name = (item?.name || '').toString().trim();
                        const safeName = name
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                        const leadsCount = Number(item?.leads_count || 0);
                        if (!Number.isInteger(id) || id <= 0 || name === '') {
                            return '';
                        }

                        return `<button type="button" data-chip-option data-value="${id}" data-label="${safeName}" class="flex w-full items-center justify-between px-3 py-2 text-left text-xs text-slate-600 hover:bg-slate-50"><span>${safeName}</span><span class="text-[10px] text-slate-400">${leadsCount} lead(s)</span></button>`;
                    }).join('')
                    : '<div class="px-3 py-2 text-xs text-slate-400">Nenhuma sequencia encontrada para os filtros atuais.</div>';

                chipSelects['remove-sequences'] = initChipSelect(removeSequenceChipRoot);
                chipSelects['remove-sequences']?.setSelected([]);
            };

            const getRemoveSequenceSelectedIds = () => {
                if (!removeSequenceChipRoot) {
                    return [];
                }

                const inputs = Array.from(
                    removeSequenceChipRoot.querySelectorAll('[data-chip-inputs] input[name="sequence_ids[]"]')
                );

                return inputs
                    .map((input) => Number(input.value || 0))
                    .filter((id) => Number.isInteger(id) && id > 0);
            };

            const loadRemoveSequenceOptions = async () => {
                setRemoveSequenceModalHint('Carregando sequencias...');
                renderRemoveSequenceOptions([]);

                const response = await fetch(buildFilteredActionUrl(removeSequencesOptionsUrl), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                let payload = {};
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = {};
                }

                if (!response.ok) {
                    throw new Error(payload.message || 'Nao foi possivel carregar as sequencias para remocao.');
                }

                const sequences = Array.isArray(payload?.sequences) ? payload.sequences : [];
                renderRemoveSequenceOptions(sequences);

                if (!sequences.length) {
                    setRemoveSequenceModalHint('Nenhuma sequencia encontrada para os filtros atuais.');
                    return;
                }

                const filteredLeadsCount = Number(payload?.filtered_leads_count || 0);
                setRemoveSequenceModalHint(
                    `${sequences.length} sequencia(s) disponivel(is) para ${filteredLeadsCount} lead(s) filtrado(s).`
                );
            };

            removeSequenceAction?.addEventListener('click', async () => {
                exportMenu?.classList.add('hidden');
                removeSequenceModal?.classList.remove('hidden');

                const originalHtml = removeSequenceAction.innerHTML;
                removeSequenceAction.disabled = true;
                removeSequenceAction.classList.add('opacity-60', 'pointer-events-none');
                removeSequenceAction.textContent = 'Carregando...';

                try {
                    await loadRemoveSequenceOptions();
                } catch (error) {
                    setRemoveSequenceModalHint(error.message || 'Nao foi possivel carregar as sequencias para remocao.', true);
                } finally {
                    removeSequenceAction.disabled = false;
                    removeSequenceAction.classList.remove('opacity-60', 'pointer-events-none');
                    removeSequenceAction.innerHTML = originalHtml || '<span>Sequencia</span>';
                }
            });

            document.querySelectorAll('[data-remove-sequence-close]').forEach((button) => {
                button.addEventListener('click', closeRemoveSequenceModal);
            });

            removeSequenceModal?.addEventListener('click', (event) => {
                if (event.target === removeSequenceModal) {
                    closeRemoveSequenceModal();
                }
            });

            removeSequenceSubmit?.addEventListener('click', async () => {
                const sequenceIds = getRemoveSequenceSelectedIds();
                if (!sequenceIds.length) {
                    window.alert('Selecione ao menos uma sequencia para remover.');
                    return;
                }

                const confirmed = window.confirm('Remover as sequencias selecionadas de todos os leads filtrados?');
                if (!confirmed) {
                    return;
                }

                const originalHtml = removeSequenceSubmit.innerHTML;
                removeSequenceSubmit.disabled = true;
                removeSequenceSubmit.classList.add('opacity-60', 'pointer-events-none');
                removeSequenceSubmit.textContent = 'Removendo...';

                try {
                    const response = await fetch(buildFilteredActionUrl(removeSequencesUrl), {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            sequence_ids: sequenceIds,
                        }),
                    });

                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok) {
                        throw new Error(payload.message || 'Nao foi possivel remover as sequencias em lote.');
                    }

                    window.alert(payload.message || 'Sequencias removidas com sucesso.');
                    closeRemoveSequenceModal();

                    const refreshUrl = new URL(window.location.href);
                    refreshUrl.searchParams.delete('page');
                    await fetchLeads(refreshUrl.toString(), { pushState: true });
                } catch (error) {
                    window.alert(error.message || 'Nao foi possivel remover as sequencias em lote.');
                } finally {
                    removeSequenceSubmit.disabled = false;
                    removeSequenceSubmit.classList.remove('opacity-60', 'pointer-events-none');
                    removeSequenceSubmit.innerHTML = originalHtml || 'Remover sequencias';
                }
            });

            if (filtersToggle && filtersMenu) {
                filtersToggle.addEventListener('click', () => {
                    filtersMenu.classList.toggle('hidden');
                });
                document.addEventListener('click', (event) => {
                    if (!filtersMenu.contains(event.target) && !filtersToggle.contains(event.target)) {
                        filtersMenu.classList.add('hidden');
                    }
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        filtersMenu.classList.add('hidden');
                    }
                });
            }

            const normalizeSearchTerm = (value) => value
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');

            const debounce = (fn, delay = 350) => {
                let timer;
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn(...args), delay);
                };
            };

            const setFiltersQueryValue = (value) => {
                if (filtersQueryInput) {
                    filtersQueryInput.value = value;
                }
            };

            const syncFiltersSortFromUrl = () => {
                const params = new URL(window.location.href).searchParams;
                const sortBy = params.get('sort_by') === 'updated_at' ? 'updated_at' : 'created_at';
                const sortDir = params.get('sort_dir') === 'asc' ? 'asc' : 'desc';

                if (filtersSortByInput) {
                    filtersSortByInput.value = sortBy;
                }

                if (filtersSortDirInput) {
                    filtersSortDirInput.value = sortDir;
                }
            };

            const fetchLeads = async (url, { pushState = false } = {}) => {
                if (!leadTableContainer) {
                    return;
                }

                leadTableContainer.classList.add('opacity-60', 'pointer-events-none');

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Request failed');
                    }

                    const html = await response.text();
                    leadTableContainer.innerHTML = html;

                    if (pushState) {
                        window.history.pushState({}, '', url);
                    }

                    syncFiltersSortFromUrl();
                } catch (error) {
                    if (pushState) {
                        window.location.href = url;
                    }
                } finally {
                    leadTableContainer.classList.remove('opacity-60', 'pointer-events-none');
                }
            };

            const syncSearchInputFromUrl = () => {
                if (!leadSearchInput) {
                    return;
                }
                const current = new URL(window.location.href).searchParams.get('q') || '';
                leadSearchInput.value = current;
                setFiltersQueryValue(current);
            };

            const syncSearchQuery = () => {
                if (!leadSearchInput) {
                    return;
                }

                const raw = leadSearchInput.value.trim();
                const normalized = normalizeSearchTerm(raw);
                const url = new URL(window.location.href);
                const current = url.searchParams.get('q') || '';

                if (normalized.length >= 3) {
                    if (current === normalized) {
                        setFiltersQueryValue(normalized);
                        return;
                    }
                    url.searchParams.set('q', normalized);
                    url.searchParams.delete('page');
                    setFiltersQueryValue(normalized);
                    fetchLeads(url.toString(), { pushState: true });
                    return;
                }

                if (current !== '') {
                    url.searchParams.delete('q');
                    url.searchParams.delete('page');
                    setFiltersQueryValue('');
                    fetchLeads(url.toString(), { pushState: true });
                } else {
                    setFiltersQueryValue('');
                }
            };

            if (leadSearchInput) {
                leadSearchInput.addEventListener('input', debounce(syncSearchQuery));
                leadSearchInput.addEventListener('blur', syncSearchQuery);
            }

            if (leadSearchClear) {
                leadSearchClear.addEventListener('click', () => {
                    if (leadSearchInput) {
                        leadSearchInput.value = '';
                    }
                    syncSearchQuery();
                });
            }

            window.addEventListener('popstate', () => {
                syncSearchInputFromUrl();
                fetchLeads(window.location.href);
            });

            syncFiltersSortFromUrl();

            const closeModal = () => {
                modal?.classList.add('hidden');
            };

            const closeMessageModal = () => {
                messageModal?.classList.add('hidden');
                messageRequiresConexao = false;
                currentAssistant = null;
                currentCloudContext = null;
                if (messageConexaoSelect) {
                    messageConexaoSelect.value = '';
                }
                if (messageConexaoWrap) {
                    messageConexaoWrap.classList.add('hidden');
                }
                if (templateConexaoSelect) {
                    templateConexaoSelect.value = '';
                }
                if (templateSelect) {
                    templateSelect.value = '';
                }
                if (templateVariablesContainer) {
                    templateVariablesContainer.innerHTML = '';
                }
                templateVariablesWrap?.classList.add('hidden');
                if (templateWindowStatus) {
                    templateWindowStatus.textContent = 'Selecione uma conexao para verificar a janela de 24h.';
                }
                if (scheduledList) {
                    scheduledList.innerHTML = '';
                }
                if (scheduledSummary) {
                    scheduledSummary.textContent = '';
                }
                setMessageMode('text');
            };

            const escapeHtml = (value) => (value ?? '')
                .toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const toLocalDatetimeInput = (date) => {
                const pad = (value) => value.toString().padStart(2, '0');
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
            };

            const renderScheduledMessages = (payload) => {
                const pendingCount = payload?.pending_count ?? 0;
                const timezone = payload?.timezone || 'America/Sao_Paulo';
                const nextLabel = payload?.next_scheduled_for_label || '-';
                const items = Array.isArray(payload?.items) ? payload.items : [];

                if (messageTimezoneHint) {
                    messageTimezoneHint.textContent = `Timezone: ${timezone}`;
                }

                if (scheduledSummary) {
                    scheduledSummary.textContent = `Pendentes: ${pendingCount} | Proximo: ${nextLabel}`;
                }

                if (!scheduledList) {
                    return;
                }

                if (!items.length) {
                    scheduledList.innerHTML = '<p class="text-xs text-slate-400">Sem agendamentos pendentes para este lead.</p>';
                    return;
                }

                scheduledList.innerHTML = items.map(item => {
                    const cancelButton = item.can_cancel
                        ? `<button type="button" data-cancel-scheduled-message data-scheduled-id="${item.id}" class="rounded-md border border-rose-200 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-50">Cancelar</button>`
                        : '';

                    return `
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold text-slate-700">${escapeHtml(item.scheduled_for_label || '-')} | ${escapeHtml(item.assistant || '-')}</p>
                                    <p class="mt-1 text-[11px] text-slate-600">${escapeHtml(item.mensagem_preview || '')}</p>
                                </div>
                                ${cancelButton}
                            </div>
                        </div>
                    `;
                }).join('');
            };

            const loadScheduledMessages = async () => {
                if (!currentLead || !scheduledSummary || !scheduledList) {
                    return;
                }

                scheduledSummary.textContent = 'Carregando agendamentos...';
                scheduledList.innerHTML = '';

                const url = scheduledMessagesUrlTemplate.replace('__LEAD_ID__', currentLead.id);
                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Request failed');
                    }

                    const payload = await response.json();
                    renderScheduledMessages(payload);
                } catch (error) {
                    scheduledSummary.textContent = 'Nao foi possivel carregar os agendamentos.';
                    scheduledList.innerHTML = '';
                }
            };

            const populateMessageConexoes = (list = []) => {
                if (!messageConexaoSelect) {
                    return;
                }

                messageConexaoSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Selecione a conexao';
                placeholder.selected = true;
                messageConexaoSelect.appendChild(placeholder);

                list.forEach(item => {
                    const option = document.createElement('option');
                    option.value = String(item.id);
                    option.textContent = item.name;
                    messageConexaoSelect.appendChild(option);
                });
            };

            const loadMessageConexoes = async (clienteId) => {
                if (!messageConexaoSelect || !clienteId) {
                    populateMessageConexoes([]);
                    return [];
                }

                const cacheKey = String(clienteId);
                if (messageConexoesCache.has(cacheKey)) {
                    const cached = messageConexoesCache.get(cacheKey) || [];
                    populateMessageConexoes(cached);
                    if (messageConexaoHint) {
                        messageConexaoHint.textContent = cached.length
                            ? 'Escolha a conexao para definir o assistente.'
                            : 'Nenhuma conexao disponivel para este cliente.';
                    }
                    return cached;
                }

                if (messageConexaoHint) {
                    messageConexaoHint.textContent = 'Carregando conexoes...';
                }
                messageConexaoSelect.disabled = true;

                try {
                    const url = conexoesUrlTemplate.replace('__CLIENT__', cacheKey);
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Request failed');
                    }

                    const payload = await response.json();
                    const normalized = Array.isArray(payload)
                        ? payload
                            .map(item => ({
                                id: Number(item.id),
                                name: (item.name ?? '').toString().trim(),
                            }))
                            .filter(item => Number.isInteger(item.id) && item.id > 0 && item.name !== '')
                        : [];

                    messageConexoesCache.set(cacheKey, normalized);
                    populateMessageConexoes(normalized);
                    if (messageConexaoHint) {
                        messageConexaoHint.textContent = normalized.length
                            ? 'Escolha a conexao para definir o assistente.'
                            : 'Nenhuma conexao disponivel para este cliente.';
                    }

                    return normalized;
                } catch (error) {
                    populateMessageConexoes([]);
                    if (messageConexaoHint) {
                        messageConexaoHint.textContent = 'Nao foi possivel carregar as conexoes deste cliente.';
                    }
                    return [];
                } finally {
                    messageConexaoSelect.disabled = false;
                }
            };

            const setMessageMode = (mode) => {
                const normalized = mode === 'template' ? 'template' : 'text';
                messageMode = normalized;

                messageTabButtons.forEach((button) => {
                    const isActive = button.dataset.messageTabButton === normalized;
                    button.classList.toggle('bg-white', isActive);
                    button.classList.toggle('text-slate-900', isActive);
                    button.classList.toggle('shadow-sm', isActive);
                    button.classList.toggle('text-slate-600', !isActive);
                });

                messageTabText?.classList.toggle('hidden', normalized !== 'text');
                messageTabTemplate?.classList.toggle('hidden', normalized !== 'template');
            };
            setMessageMode('text');

            const populateTemplateConexoes = (connections = []) => {
                if (!templateConexaoSelect) {
                    return;
                }

                templateConexaoSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Selecione a conexao cloud';
                placeholder.selected = true;
                templateConexaoSelect.appendChild(placeholder);

                connections.forEach((connection) => {
                    const option = document.createElement('option');
                    option.value = String(connection.id);
                    option.textContent = connection.name;
                    templateConexaoSelect.appendChild(option);
                });
            };

            const renderTemplateWindowStatus = (connectionId) => {
                if (!templateWindowStatus) {
                    return;
                }

                if (!connectionId || !currentCloudContext) {
                    templateWindowStatus.textContent = 'Selecione uma conexao para verificar a janela de 24h.';
                    return;
                }

                const connection = (currentCloudContext.connections || []).find(
                    (item) => Number(item.id) === Number(connectionId)
                );

                if (!connection?.window) {
                    templateWindowStatus.textContent = 'Sem dados da janela para esta conexao.';
                    return;
                }

                const lastInbound = connection.window.last_inbound_at_label || '-';
                const expiresAt = connection.window.expires_at_label || '-';
                templateWindowStatus.textContent = connection.window.is_open
                    ? `Janela aberta. Última mensagem recebida: ${lastInbound}. Expira em: ${expiresAt}.`
                    : `Janela fechada. Última mensagem recebida: ${lastInbound}.`;
            };

            const filteredTemplatesByConnection = (connectionId) => {
                if (!currentCloudContext || !connectionId) {
                    return [];
                }

                const connection = (currentCloudContext.connections || []).find(
                    (item) => Number(item.id) === Number(connectionId)
                );

                if (!connection) {
                    return [];
                }

                return (currentCloudContext.templates || []).filter((template) => {
                    const status = (template.status || '').toString().toUpperCase();
                    if (!['APPROVED', 'ACTIVE'].includes(status)) {
                        return false;
                    }

                    if (Number(template.whatsapp_cloud_account_id) !== Number(connection.whatsapp_cloud_account_id)) {
                        return false;
                    }

                    if (template.conexao_id && Number(template.conexao_id) !== Number(connection.id)) {
                        return false;
                    }

                    return true;
                });
            };

            const populateTemplateOptions = (templates = []) => {
                if (!templateSelect) {
                    return;
                }

                templateSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = templates.length
                    ? 'Selecione um modelo'
                    : 'Nenhum modelo aprovado para esta conexao';
                placeholder.selected = true;
                templateSelect.appendChild(placeholder);

                templates.forEach((template) => {
                    const option = document.createElement('option');
                    option.value = String(template.id);
                    option.textContent = `${template.title} (${template.language_code})`;
                    templateSelect.appendChild(option);
                });
            };

            const renderTemplateVariableInputs = () => {
                if (!templateVariablesContainer || !templateVariablesWrap) {
                    return;
                }

                const selectedTemplateId = Number(templateSelect?.value || 0);
                const templates = filteredTemplatesByConnection(Number(templateConexaoSelect?.value || 0));
                const selectedTemplate = templates.find((item) => Number(item.id) === selectedTemplateId);

                templateVariablesContainer.innerHTML = '';

                if (!selectedTemplate || !Array.isArray(selectedTemplate.variables) || !selectedTemplate.variables.length) {
                    templateVariablesWrap.classList.add('hidden');
                    return;
                }

                selectedTemplate.variables.forEach((variable) => {
                    const wrap = document.createElement('div');
                    const label = document.createElement('label');
                    const input = document.createElement('input');

                    wrap.className = 'space-y-1';
                    label.className = 'block text-[11px] font-semibold uppercase tracking-wide text-slate-500';
                    label.textContent = variable.label
                        ? `${variable.label} ({${variable.name}})`
                        : `{${variable.name}}`;

                    input.type = 'text';
                    input.className = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none';
                    input.value = (variable.sample_value || '').toString();
                    input.dataset.templateVariable = variable.name;
                    input.placeholder = `Valor para {${variable.name}}`;

                    wrap.appendChild(label);
                    wrap.appendChild(input);
                    templateVariablesContainer.appendChild(wrap);
                });

                templateVariablesWrap.classList.remove('hidden');
            };

            const collectTemplateVariables = () => {
                const payload = {};
                templateVariablesContainer?.querySelectorAll('[data-template-variable]').forEach((input) => {
                    const key = input.dataset.templateVariable;
                    if (!key) {
                        return;
                    }

                    payload[key] = (input.value || '').trim();
                });

                return payload;
            };

            const loadCloudContext = async () => {
                if (!currentLead) {
                    currentCloudContext = null;
                    return null;
                }

                const url = cloudSendContextUrlTemplate.replace('__LEAD_ID__', currentLead.id);
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Nao foi possivel carregar contexto Cloud deste lead.');
                }

                const payload = await response.json();
                currentCloudContext = payload;

                return payload;
            };

            const syncTemplateTabState = () => {
                const selectedConnectionId = Number(templateConexaoSelect?.value || 0);
                const templates = filteredTemplatesByConnection(selectedConnectionId);

                populateTemplateOptions(templates);
                renderTemplateWindowStatus(selectedConnectionId || null);
                renderTemplateVariableInputs();
            };

            const openMessageModal = async ({ assistantId = null, assistantName = '-', requireConexao = false } = {}) => {
                if (!messageModal || !currentLead) {
                    return;
                }

                currentAssistant = {
                    id: assistantId ? String(assistantId) : null,
                    name: assistantName ?? '-',
                };
                messageRequiresConexao = Boolean(requireConexao);

                if (messageLeadName) {
                    const leadLabel = currentLead.name_raw || currentLead.phone_raw || currentLead.phone || '-';
                    messageLeadName.textContent = leadLabel;
                }
                if (messageAssistantName) {
                    messageAssistantName.textContent = assistantName ?? '-';
                }
                if (messageText) {
                    messageText.value = '';
                }
                if (messageScheduledFor) {
                    messageScheduledFor.value = '';
                    messageScheduledFor.min = toLocalDatetimeInput(new Date(Date.now() + 60000));
                }
                if (messageConexaoSelect) {
                    messageConexaoSelect.value = '';
                }
                if (messageConexaoWrap) {
                    messageConexaoWrap.classList.toggle('hidden', !messageRequiresConexao);
                }
                if (messageConexaoHint) {
                    messageConexaoHint.textContent = 'Escolha a conexao para definir o assistente.';
                }
                if (templateConexaoSelect) {
                    templateConexaoSelect.value = '';
                }
                if (templateSelect) {
                    templateSelect.value = '';
                }
                if (templateVariablesContainer) {
                    templateVariablesContainer.innerHTML = '';
                }
                templateVariablesWrap?.classList.add('hidden');
                setMessageMode('text');

                messageError?.classList.add('hidden');
                messageSuccess?.classList.add('hidden');
                messageModal.classList.remove('hidden');
                loadScheduledMessages();

                if (messageRequiresConexao) {
                    const conexoes = await loadMessageConexoes(currentLead?.cliente?.id);
                    if (!conexoes.length && messageError) {
                        messageError.textContent = 'Nao existem conexoes disponiveis para este cliente.';
                        messageError.classList.remove('hidden');
                    }
                }

                try {
                    const cloudContext = await loadCloudContext();
                    const cloudConnections = Array.isArray(cloudContext?.connections)
                        ? cloudContext.connections
                        : [];
                    populateTemplateConexoes(cloudConnections);
                    syncTemplateTabState();
                } catch (error) {
                    currentCloudContext = null;
                    populateTemplateConexoes([]);
                    populateTemplateOptions([]);
                    templateVariablesWrap?.classList.add('hidden');
                    if (templateWindowStatus) {
                        templateWindowStatus.textContent = 'Nao foi possivel carregar contexto Cloud.';
                    }
                }
            };

            const renderAssistants = (list = []) => {
                if (!Array.isArray(list) || list.length === 0) {
                    return `<tr>
                        <td colspan="4" class="px-3 py-2 text-center text-slate-400">Nenhum assistente associado.</td>
                        <td class="px-3 py-2 text-right">
                            <button
                                type="button"
                                class="rounded-full border border-slate-300 px-3 py-1 text-[11px] font-semibold text-slate-600 hover:border-slate-500 hover:text-slate-900"
                                data-send-message
                                data-require-conexao="1"
                                data-assistant-name="Definido pela conexao"
                            >Enviar via conexao</button>
                        </td>
                    </tr>`;
                }

                return list.map(item => {
                    const convId = item.conv_id || '-';
                    const convLink = convId && convId !== '-'
                        ? `<a class="text-blue-600 hover:underline" href="${convIdBaseUrl}?conv_id=${encodeURIComponent(convId)}">${convId}</a>`
                        : convId;
                    const assistantId = item.assistant_id ?? '';
                    const assistantName = item.assistant ?? '-';
                    const disabledClass = assistantId ? '' : 'opacity-40 pointer-events-none';

                    return `
                    <tr>
                        <td class="px-3 py-2 font-medium text-slate-800">${assistantName}</td>
                        <td class="px-3 py-2">${item.version}</td>
                        <td class="px-3 py-2 font-mono text-[11px]">${convLink}</td>
                        <td class="px-3 py-2">${item.created_at}</td>
                        <td class="px-3 py-2">
                            <button
                                type="button"
                                class="rounded-full border border-slate-300 px-3 py-1 text-[11px] font-semibold text-slate-600 hover:border-slate-500 hover:text-slate-900 ${disabledClass}"
                                data-send-message
                                data-assistant-id="${assistantId}"
                                data-assistant-name="${assistantName}"
                            >Enviar mensagem</button>
                        </td>
                    </tr>
                `;
                }).join('');
            };

            const parseLeadData = (button) => {
                const raw = button.getAttribute('data-lead');
                if (!raw) {
                    return null;
                }

                try {
                    return JSON.parse(raw);
                } catch (error) {
                    return null;
                }
            };

            const openConversation = (data) => {
                currentLead = data;
                document.getElementById('viewLeadId').textContent = data.id;
                document.getElementById('viewLeadCliente').textContent = `${data.cliente.id} - ${data.cliente.nome}`;
                document.getElementById('viewLeadPhone').textContent = data.phone;
                document.getElementById('viewLeadBot').textContent = data.bot;
                document.getElementById('viewLeadName').textContent = data.name;
                document.getElementById('viewLeadInfo').textContent = data.info;
                document.getElementById('viewLeadCreatedAt').textContent = data.created_at;

                if (assistantBody) {
                    assistantBody.innerHTML = renderAssistants(data.assistant_leads);
                }

                if (tagsContainer) {
                    tagsContainer.innerHTML = data.tags.length
                        ? data.tags.map(tag => `<span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] text-slate-600">${tag}</span>`).join('')
                        : '<span class="text-[11px] text-slate-400">Sem tags</span>';
                }

                if (customFieldsContainer) {
                    const customFields = Array.isArray(data.custom_fields) ? data.custom_fields : [];
                    customFieldsContainer.innerHTML = customFields.length
                        ? customFields.map((item) => {
                            const label = (item?.label || '').toString().trim();
                            const name = (item?.name || '').toString().trim();
                            const value = (item?.value || '').toString().trim();
                            const identifier = name !== '' ? `{${name}}` : '{campo}';
                            const title = label !== '' ? `${label} (${identifier})` : identifier;

                            return `
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">${escapeHtml(title)}</p>
                                    <p class="mt-1 text-xs text-slate-700">${escapeHtml(value !== '' ? value : '-')}</p>
                                </div>
                            `;
                        }).join('')
                        : '<span class="text-[11px] text-slate-400">Sem campos personalizados</span>';
                }

                modal?.classList.remove('hidden');
            };

            assistantBody?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-send-message]');
                if (!button) {
                    return;
                }

                const assistantId = button.dataset.assistantId;
                const assistantName = button.dataset.assistantName || '-';
                const requireConexao = button.dataset.requireConexao === '1';

                if (!assistantId && !requireConexao) {
                    return;
                }

                openMessageModal({
                    assistantId: assistantId || null,
                    assistantName,
                    requireConexao,
                });
            });

            document.querySelectorAll('[data-view-close]').forEach(button => {
                button.addEventListener('click', closeModal);
            });

            modal?.addEventListener('click', event => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.querySelectorAll('[data-message-close]').forEach(button => {
                button.addEventListener('click', closeMessageModal);
            });

            messageModal?.addEventListener('click', event => {
                if (event.target === messageModal) {
                    closeMessageModal();
                }
            });

            messageTabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const nextMode = button.dataset.messageTabButton === 'template' ? 'template' : 'text';
                    setMessageMode(nextMode);
                });
            });

            templateConexaoSelect?.addEventListener('change', () => {
                syncTemplateTabState();
            });

            templateSelect?.addEventListener('change', () => {
                renderTemplateVariableInputs();
            });

            scheduledList?.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-cancel-scheduled-message]');
                if (!button) {
                    return;
                }

                const scheduledId = button.dataset.scheduledId;
                if (!scheduledId) {
                    return;
                }

                const url = cancelScheduledMessageUrlTemplate.replace('__SCHEDULE_ID__', scheduledId);
                button.disabled = true;
                try {
                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok) {
                        throw new Error(payload.message || 'Falha ao cancelar agendamento.');
                    }

                    messageSuccess?.classList.remove('hidden');
                    if (messageSuccess) {
                        messageSuccess.textContent = payload.message || 'Agendamento cancelado com sucesso.';
                    }
                    messageError?.classList.add('hidden');
                    await loadScheduledMessages();
                } catch (error) {
                    if (messageError) {
                        messageError.textContent = error.message || 'Nao foi possivel cancelar o agendamento.';
                        messageError.classList.remove('hidden');
                    }
                } finally {
                    button.disabled = false;
                }
            });

            messageForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!currentLead || !currentAssistant) {
                    return;
                }

                const isTemplateMode = messageMode === 'template';
                const url = sendMessageUrlTemplate.replace('__LEAD_ID__', currentLead.id);

                messageError?.classList.add('hidden');
                messageSuccess?.classList.add('hidden');

                let requestBody = {};
                let loadingLabel = 'Enviando...';
                let scheduledFor = '';

                if (isTemplateMode) {
                    const templateConexaoId = Number(templateConexaoSelect?.value || 0);
                    const templateId = Number(templateSelect?.value || 0);

                    if (!Number.isInteger(templateConexaoId) || templateConexaoId <= 0) {
                        if (messageError) {
                            messageError.textContent = 'Selecione a conexao cloud para enviar o modelo.';
                            messageError.classList.remove('hidden');
                        }
                        return;
                    }

                    if (!Number.isInteger(templateId) || templateId <= 0) {
                        if (messageError) {
                            messageError.textContent = 'Selecione um modelo aprovado para enviar.';
                            messageError.classList.remove('hidden');
                        }
                        return;
                    }

                    requestBody = {
                        mode: 'template_cloud',
                        conexao_id: templateConexaoId,
                        template_id: templateId,
                        template_variables: collectTemplateVariables(),
                    };
                    loadingLabel = 'Enviando modelo...';
                } else {
                    const text = messageText?.value?.trim() ?? '';
                    scheduledFor = messageScheduledFor?.value?.trim() ?? '';

                    if (text === '') {
                        if (messageError) {
                            messageError.textContent = 'Informe a mensagem antes de enviar.';
                            messageError.classList.remove('hidden');
                        }
                        return;
                    }

                    let conexaoId = null;
                    if (messageRequiresConexao) {
                        const rawConexaoId = messageConexaoSelect?.value ?? '';
                        if (rawConexaoId === '') {
                            if (messageError) {
                                messageError.textContent = 'Selecione uma conexao antes de enviar.';
                                messageError.classList.remove('hidden');
                            }
                            return;
                        }

                        const parsedConexaoId = Number(rawConexaoId);
                        if (!Number.isInteger(parsedConexaoId) || parsedConexaoId <= 0) {
                            if (messageError) {
                                messageError.textContent = 'Conexao selecionada invalida.';
                                messageError.classList.remove('hidden');
                            }
                            return;
                        }
                        conexaoId = parsedConexaoId;
                    } else if (!currentAssistant.id) {
                        if (messageError) {
                            messageError.textContent = 'Assistente nao identificado para este envio.';
                            messageError.classList.remove('hidden');
                        }
                        return;
                    }

                    requestBody = {
                        mode: 'text',
                        mensagem: text,
                        scheduled_for: scheduledFor || null,
                    };

                    if (currentAssistant.id) {
                        requestBody.assistant_id = Number(currentAssistant.id);
                    }
                    if (conexaoId) {
                        requestBody.conexao_id = conexaoId;
                    }

                    loadingLabel = scheduledFor ? 'Agendando...' : 'Enviando...';
                }

                if (messageSubmit) {
                    messageSubmit.disabled = true;
                    messageSubmit.textContent = loadingLabel;
                }

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(requestBody),
                    });

                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok) {
                        const message = payload.message || 'Nao foi possivel enviar a mensagem.';
                        if (messageError) {
                            messageError.textContent = message;
                            messageError.classList.remove('hidden');
                        }
                        return;
                    }

                    if (messageSuccess) {
                        const fallbackMessage = isTemplateMode
                            ? 'Modelo enviado com sucesso.'
                            : (scheduledFor ? 'Mensagem agendada com sucesso.' : 'Mensagem enviada para a fila.');
                        messageSuccess.textContent = payload.message || fallbackMessage;
                        messageSuccess.classList.remove('hidden');
                    }

                    if (isTemplateMode) {
                        templateSelect.value = '';
                        renderTemplateVariableInputs();
                    } else {
                        if (messageText && !scheduledFor) {
                            messageText.value = '';
                        }
                        if (messageScheduledFor) {
                            messageScheduledFor.value = '';
                        }
                        await loadScheduledMessages();
                    }
                } catch (error) {
                    if (messageError) {
                        messageError.textContent = 'Nao foi possivel enviar a mensagem.';
                        messageError.classList.remove('hidden');
                    }
                } finally {
                    if (messageSubmit) {
                        messageSubmit.disabled = false;
                        messageSubmit.textContent = 'Enviar';
                    }
                }
            });

            const closeFormModal = () => {
                formModal?.classList.add('hidden');
            };

            const initTagModeFilter = (root) => {
                if (!root) {
                    return null;
                }

                const search = root.querySelector('[data-tag-search]');
                const optionsWrap = root.querySelector('[data-tag-options]');
                const options = Array.from(root.querySelectorAll('[data-tag-option]'));
                const addChipList = root.querySelector('[data-tag-chip-list="add"]');
                const removeChipList = root.querySelector('[data-tag-chip-list="remove"]');
                const addInputsWrap = root.querySelector('[data-tag-inputs-add]');
                const removeInputsWrap = root.querySelector('[data-tag-inputs-remove]');

                if (!addChipList || !removeChipList || !addInputsWrap || !removeInputsWrap) {
                    return null;
                }

                const addInputName = root.dataset.inputAddName || 'tags_add[]';
                const removeInputName = root.dataset.inputRemoveName || 'tags_remove[]';
                const INPUT_NAME_BY_MODE = {
                    add: addInputName,
                    remove: removeInputName,
                };

                const chipListByMode = {
                    add: addChipList,
                    remove: removeChipList,
                };

                const inputWrapByMode = {
                    add: addInputsWrap,
                    remove: removeInputsWrap,
                };

                const getSelectedValues = (mode) => Array.from(inputWrapByMode[mode].querySelectorAll('input'))
                    .map((input) => String(input.value));

                const findOptionByValue = (value) => options.find((item) => String(item.dataset.value ?? '') === String(value));

                options.forEach((option) => {
                    const status = option.querySelector('[data-tag-option-status]');
                    if (status && !status.dataset.defaultLabel) {
                        status.dataset.defaultLabel = status.textContent?.trim() || 'Selecionar';
                    }
                });

                const syncOptionsVisibility = () => {
                    const term = (search?.value ?? '').toLowerCase();
                    const selectedAdd = new Set(getSelectedValues('add'));
                    const selectedRemove = new Set(getSelectedValues('remove'));

                    options.forEach((option) => {
                        const label = (option.dataset.label ?? '').toLowerCase();
                        const value = String(option.dataset.value ?? '');
                        const matches = !term || label.includes(term);

                        option.classList.toggle('hidden', !matches);

                        const status = option.querySelector('[data-tag-option-status]');
                        if (!status) {
                            return;
                        }

                        const addActionButton = option.querySelector('[data-tag-option-action="add"]');
                        const removeActionButton = option.querySelector('[data-tag-option-action="remove"]');

                        if (selectedAdd.has(value)) {
                            status.textContent = 'Adicionar';
                            status.className = 'text-[10px] font-semibold text-emerald-600';
                            if (addActionButton) {
                                addActionButton.disabled = true;
                                addActionButton.classList.add('opacity-60', 'pointer-events-none');
                            }
                            if (removeActionButton) {
                                removeActionButton.disabled = false;
                                removeActionButton.classList.remove('opacity-60', 'pointer-events-none');
                            }
                            return;
                        }

                        if (selectedRemove.has(value)) {
                            status.textContent = 'Remover';
                            status.className = 'text-[10px] font-semibold text-rose-600';
                            if (addActionButton) {
                                addActionButton.disabled = false;
                                addActionButton.classList.remove('opacity-60', 'pointer-events-none');
                            }
                            if (removeActionButton) {
                                removeActionButton.disabled = true;
                                removeActionButton.classList.add('opacity-60', 'pointer-events-none');
                            }
                            return;
                        }

                        status.textContent = status.dataset.defaultLabel || 'Selecionar';
                        status.className = 'text-[10px] text-slate-400';
                        if (addActionButton) {
                            addActionButton.disabled = false;
                            addActionButton.classList.remove('opacity-60', 'pointer-events-none');
                        }
                        if (removeActionButton) {
                            removeActionButton.disabled = false;
                            removeActionButton.classList.remove('opacity-60', 'pointer-events-none');
                        }
                    });
                };

                const removeChip = (mode, value) => {
                    const normalizedMode = mode === 'remove' ? 'remove' : 'add';
                    const input = inputWrapByMode[normalizedMode].querySelector(`input[value="${value}"]`);
                    if (input) {
                        input.remove();
                    }

                    const chip = chipListByMode[normalizedMode].querySelector(`[data-tag-chip-value="${value}"]`);
                    if (chip) {
                        chip.remove();
                    }

                    syncOptionsVisibility();
                };

                const addChip = (mode, value, label) => {
                    const normalizedMode = mode === 'remove' ? 'remove' : 'add';
                    const oppositeMode = normalizedMode === 'add' ? 'remove' : 'add';
                    const normalizedValue = String(value ?? '');
                    if (!normalizedValue) {
                        return;
                    }

                    removeChip(oppositeMode, normalizedValue);

                    if (inputWrapByMode[normalizedMode].querySelector(`input[value="${normalizedValue}"]`)) {
                        syncOptionsVisibility();
                        return;
                    }

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = INPUT_NAME_BY_MODE[normalizedMode];
                    input.value = normalizedValue;
                    inputWrapByMode[normalizedMode].appendChild(input);

                    const chip = document.createElement('span');
                    chip.dataset.tagChipValue = normalizedValue;
                    chip.className = normalizedMode === 'add'
                        ? 'inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-medium text-emerald-700'
                        : 'inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-[11px] font-medium text-rose-700';

                    const chipLabel = document.createElement('span');
                    chipLabel.textContent = label;
                    chip.appendChild(chipLabel);

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = normalizedMode === 'add'
                        ? 'text-emerald-500 hover:text-emerald-700'
                        : 'text-rose-500 hover:text-rose-700';
                    removeButton.textContent = '×';
                    removeButton.addEventListener('click', () => removeChip(normalizedMode, normalizedValue));
                    chip.appendChild(removeButton);

                    chipListByMode[normalizedMode].appendChild(chip);
                    syncOptionsVisibility();
                };

                const hydrateFromInputs = () => {
                    const addValues = getSelectedValues('add');
                    const removeValues = getSelectedValues('remove');

                    addInputsWrap.innerHTML = '';
                    removeInputsWrap.innerHTML = '';
                    addChipList.innerHTML = '';
                    removeChipList.innerHTML = '';

                    addValues.forEach((value) => {
                        const option = findOptionByValue(value);
                        if (option) {
                            addChip('add', value, option.dataset.label ?? value);
                        }
                    });

                    removeValues.forEach((value) => {
                        const option = findOptionByValue(value);
                        if (option) {
                            addChip('remove', value, option.dataset.label ?? value);
                        }
                    });

                    syncOptionsVisibility();
                };

                options.forEach((option) => {
                    const addActionButton = option.querySelector('[data-tag-option-action="add"]');
                    const removeActionButton = option.querySelector('[data-tag-option-action="remove"]');
                    const value = option.dataset.value ?? '';
                    const label = option.dataset.label ?? value;

                    addActionButton?.addEventListener('click', (event) => {
                        event.stopPropagation();
                        addChip('add', value, label);
                        if (search) {
                            search.value = '';
                            search.focus();
                        }
                    });

                    removeActionButton?.addEventListener('click', (event) => {
                        event.stopPropagation();
                        addChip('remove', value, label);
                        if (search) {
                            search.value = '';
                            search.focus();
                        }
                    });

                    option.addEventListener('click', (event) => {
                        if (event.target.closest('[data-tag-option-action]')) {
                            return;
                        }
                        const value = option.dataset.value ?? '';
                        const label = option.dataset.label ?? value;
                        addChip('add', value, label);
                        if (search) {
                            search.value = '';
                            search.focus();
                        }
                    });
                });

                search?.addEventListener('focus', () => {
                    optionsWrap?.classList.remove('hidden');
                    syncOptionsVisibility();
                });

                search?.addEventListener('input', syncOptionsVisibility);

                document.addEventListener('click', (event) => {
                    if (!root.contains(event.target)) {
                        optionsWrap?.classList.add('hidden');
                    }
                });

                optionsWrap?.addEventListener('click', (event) => {
                    event.stopPropagation();
                });

                hydrateFromInputs();

                return { hydrateFromInputs };
            };

            const initChipSelect = (root) => {
                const inputName = root.dataset.inputName;
                const chipList = root.querySelector('[data-chip-list]');
                const search = root.querySelector('[data-chip-search]');
                const optionsWrap = root.querySelector('[data-chip-options]');
                const inputsWrap = root.querySelector('[data-chip-inputs]');
                const options = Array.from(root.querySelectorAll('[data-chip-option]'));

                if (!inputName || !chipList || !inputsWrap) {
                    return null;
                }

                const getSelectedValues = () => Array.from(inputsWrap.querySelectorAll('input')).map(input => input.value);

                const syncOptionsVisibility = () => {
                    const term = (search?.value ?? '').toLowerCase();
                    const selected = new Set(getSelectedValues());

                    options.forEach(option => {
                        const label = option.dataset.label?.toLowerCase() ?? '';
                        const value = option.dataset.value ?? '';
                        const matches = !term || label.includes(term);
                        const isSelected = selected.has(value);
                        option.classList.toggle('hidden', isSelected || !matches);
                    });
                };

                const removeChip = (value) => {
                    const input = inputsWrap.querySelector(`input[value="${value}"]`);
                    if (input) {
                        input.remove();
                    }
                    const chip = chipList.querySelector(`[data-chip-value="${value}"]`);
                    if (chip) {
                        chip.remove();
                    }
                    syncOptionsVisibility();
                };

                const addChip = (value, label) => {
                    if (!value || inputsWrap.querySelector(`input[value="${value}"]`)) {
                        return;
                    }

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = inputName;
                    input.value = value;
                    inputsWrap.appendChild(input);

                    const chip = document.createElement('span');
                    chip.dataset.chipValue = value;
                    chip.className = 'inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] text-slate-700';
                    const chipLabel = document.createElement('span');
                    chipLabel.textContent = label;
                    chip.appendChild(chipLabel);

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'text-slate-400 hover:text-slate-700';
                    removeButton.textContent = '×';
                    removeButton.addEventListener('click', () => removeChip(value));
                    chip.appendChild(removeButton);

                    chipList.appendChild(chip);
                    syncOptionsVisibility();
                };

                const hydrateFromInputs = () => {
                    const values = getSelectedValues();
                    inputsWrap.innerHTML = '';
                    chipList.innerHTML = '';
                    values.forEach(value => {
                        const option = options.find(item => item.dataset.value === value);
                        if (option) {
                            addChip(value, option.dataset.label ?? value);
                        }
                    });
                };

                const setSelected = (values = []) => {
                    inputsWrap.innerHTML = '';
                    chipList.innerHTML = '';
                    values.forEach(value => {
                        const stringValue = String(value);
                        const option = options.find(item => item.dataset.value === stringValue);
                        if (option) {
                            addChip(stringValue, option.dataset.label ?? stringValue);
                        }
                    });
                    syncOptionsVisibility();
                };

                hydrateFromInputs();

                options.forEach(option => {
                    option.addEventListener('click', () => {
                        addChip(option.dataset.value, option.dataset.label ?? option.dataset.value);
                        if (search) {
                            search.value = '';
                            search.focus();
                        }
                    });
                });

                search?.addEventListener('focus', () => {
                    optionsWrap?.classList.remove('hidden');
                    syncOptionsVisibility();
                });

                search?.addEventListener('input', syncOptionsVisibility);

            document.addEventListener('click', event => {
                if (!root.contains(event.target)) {
                    optionsWrap?.classList.add('hidden');
                }
            });

            optionsWrap?.addEventListener('click', event => {
                event.stopPropagation();
            });

                return { setSelected, hydrateFromInputs };
            };

            dualModeFilterRoots.forEach((root) => initTagModeFilter(root));

            document.querySelectorAll('[data-chip-select]').forEach(root => {
                const key = root.dataset.chipSelect;
                const api = initChipSelect(root);
                if (key && api) {
                    chipSelects[key] = api;
                }
            });

            const setImportTabVisible = (visible) => {
                if (!importTabButton) {
                    return;
                }
                importTabButton.classList.toggle('hidden', !visible);
                if (!visible) {
                    importForm?.classList.add('hidden');
                    clientLeadForm?.classList.remove('hidden');
                }
            };

            const setActiveTab = (tab) => {
                if (tab === 'import' && importTabButton?.classList.contains('hidden')) {
                    tab = 'manual';
                }
                formTabs.forEach(button => {
                    const isActive = button.dataset.formTab === tab;
                    button.classList.toggle('bg-white', isActive);
                    button.classList.toggle('text-slate-700', isActive);
                    button.classList.toggle('shadow-sm', isActive);
                    button.classList.toggle('text-slate-500', !isActive);
                });

                if (tab === 'import') {
                    clientLeadForm?.classList.add('hidden');
                    importForm?.classList.remove('hidden');
                } else {
                    importForm?.classList.add('hidden');
                    clientLeadForm?.classList.remove('hidden');
                }
            };

            const getLeadCustomFieldRows = () => Array.from(
                leadCustomFieldsRows?.querySelectorAll('[data-lead-custom-field-row]') ?? []
            );

            const resolveAllowedLeadCustomFields = () => {
                const clienteId = Number(clientLeadFormSelect?.value || 0);
                if (!Number.isInteger(clienteId) || clienteId <= 0) {
                    return [];
                }

                return (Array.isArray(availableLeadCustomFields) ? availableLeadCustomFields : [])
                    .filter((field) => {
                        const fieldClienteId = field?.cliente_id ? Number(field.cliente_id) : null;
                        return fieldClienteId === null || fieldClienteId === clienteId;
                    });
            };

            const syncLeadCustomFieldInputNames = () => {
                getLeadCustomFieldRows().forEach((row, index) => {
                    const select = row.querySelector('[data-lead-custom-field-select]');
                    const input = row.querySelector('[data-lead-custom-field-value]');
                    if (select) {
                        select.name = `custom_fields[${index}][field_id]`;
                    }
                    if (input) {
                        input.name = `custom_fields[${index}][value]`;
                    }
                });
            };

            const renderLeadCustomFieldsEmptyState = (options = null) => {
                if (!leadCustomFieldsEmpty) {
                    return;
                }

                const rows = getLeadCustomFieldRows();
                if (rows.length > 0) {
                    leadCustomFieldsEmpty.classList.add('hidden');
                    return;
                }

                const allowed = Array.isArray(options) ? options : resolveAllowedLeadCustomFields();
                const hasClientSelected = Number(clientLeadFormSelect?.value || 0) > 0;

                if (!hasClientSelected) {
                    leadCustomFieldsEmpty.textContent = 'Selecione um cliente para ver os campos disponíveis.';
                } else if (!allowed.length) {
                    leadCustomFieldsEmpty.textContent = 'Nenhum campo personalizado disponível para este cliente.';
                } else {
                    leadCustomFieldsEmpty.textContent = 'Nenhum campo adicionado para este lead.';
                }

                leadCustomFieldsEmpty.classList.remove('hidden');
            };

            const buildLeadCustomFieldLabel = (field) => {
                const baseLabel = (field?.label || '').toString().trim();
                const name = (field?.name || '').toString().trim();
                const scope = field?.cliente_id ? 'Cliente' : 'Global';

                if (baseLabel !== '') {
                    return `${baseLabel} ({${name}}) - ${scope}`;
                }

                return `{${name}} - ${scope}`;
            };

            const syncLeadCustomFieldSelectOptions = () => {
                const options = resolveAllowedLeadCustomFields();
                const rows = getLeadCustomFieldRows();
                const selectedIds = rows
                    .map((row) => Number(row.querySelector('[data-lead-custom-field-select]')?.value || 0))
                    .filter((value) => Number.isInteger(value) && value > 0);

                rows.forEach((row) => {
                    const select = row.querySelector('[data-lead-custom-field-select]');
                    if (!select) {
                        return;
                    }

                    const currentValue = Number(select.value || 0);
                    const hasCurrentInOptions = options.some((field) => Number(field.id) === currentValue);
                    const normalizedCurrent = hasCurrentInOptions ? currentValue : 0;

                    if (currentValue > 0 && normalizedCurrent === 0) {
                        const valueInput = row.querySelector('[data-lead-custom-field-value]');
                        if (valueInput) {
                            valueInput.value = '';
                        }
                    }

                    select.innerHTML = '';

                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = options.length
                        ? 'Selecione um campo'
                        : 'Sem campos disponíveis';
                    select.appendChild(placeholder);

                    options.forEach((field) => {
                        const fieldId = Number(field.id);
                        const option = document.createElement('option');
                        option.value = String(fieldId);
                        option.textContent = buildLeadCustomFieldLabel(field);

                        const selectedInOtherRow = selectedIds.includes(fieldId) && fieldId !== normalizedCurrent;
                        if (selectedInOtherRow) {
                            option.disabled = true;
                        }

                        if (fieldId === normalizedCurrent) {
                            option.selected = true;
                        }

                        select.appendChild(option);
                    });
                });

                renderLeadCustomFieldsEmptyState(options);
            };

            const addLeadCustomFieldRow = (rowData = {}, { sync = true } = {}) => {
                if (!leadCustomFieldsRows) {
                    return;
                }

                const row = document.createElement('div');
                row.className = 'grid gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-[2fr_3fr_auto]';
                row.dataset.leadCustomFieldRow = '1';

                const select = document.createElement('select');
                select.className = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none';
                select.dataset.leadCustomFieldSelect = '1';

                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none';
                input.placeholder = 'Valor do campo';
                input.dataset.leadCustomFieldValue = '1';
                input.value = (rowData?.value || '').toString();

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-slate-500 hover:text-slate-900';
                removeButton.textContent = 'Remover';
                removeButton.addEventListener('click', () => {
                    row.remove();
                    syncLeadCustomFieldInputNames();
                    syncLeadCustomFieldSelectOptions();
                });

                select.addEventListener('change', () => {
                    syncLeadCustomFieldSelectOptions();
                });

                row.appendChild(select);
                row.appendChild(input);
                row.appendChild(removeButton);
                leadCustomFieldsRows.appendChild(row);

                const initialFieldId = Number(rowData?.field_id || 0);
                if (sync) {
                    syncLeadCustomFieldInputNames();
                    syncLeadCustomFieldSelectOptions();
                    if (Number.isInteger(initialFieldId) && initialFieldId > 0) {
                        select.value = String(initialFieldId);
                        syncLeadCustomFieldSelectOptions();
                    }
                    return;
                }

                if (Number.isInteger(initialFieldId) && initialFieldId > 0) {
                    select.dataset.initialValue = String(initialFieldId);
                }
            };

            const setLeadCustomFieldRows = (rows = []) => {
                if (!leadCustomFieldsRows) {
                    return;
                }

                leadCustomFieldsRows.innerHTML = '';

                if (!Array.isArray(rows) || rows.length === 0) {
                    syncLeadCustomFieldInputNames();
                    syncLeadCustomFieldSelectOptions();
                    return;
                }

                rows.forEach((row) => addLeadCustomFieldRow(row, { sync: false }));
                syncLeadCustomFieldInputNames();
                syncLeadCustomFieldSelectOptions();

                getLeadCustomFieldRows().forEach((row) => {
                    const select = row.querySelector('[data-lead-custom-field-select]');
                    const initialValue = select?.dataset.initialValue;
                    if (select && initialValue) {
                        select.value = initialValue;
                        delete select.dataset.initialValue;
                    }
                });

                syncLeadCustomFieldSelectOptions();
            };

            const resetForm = () => {
                clientLeadForm?.reset();
                if (clientLeadFormMethod) {
                    clientLeadFormMethod.value = 'POST';
                }
                if (clientLeadForm) {
                    clientLeadForm.action = clientLeadForm.dataset.createRoute;
                }
                if (clientLeadFormTitle) {
                    clientLeadFormTitle.textContent = 'Adicionar lead';
                }
                if (clientLeadFormSubmit) {
                    clientLeadFormSubmit.textContent = 'Salvar';
                }
                if (clientLeadFormBot) {
                    clientLeadFormBot.checked = true;
                }
                chipSelects['lead-sequences']?.setSelected([]);
                chipSelects['lead-tags']?.setSelected([]);
                setLeadCustomFieldRows([]);
            };

            const populateSequences = async (clienteId, selected = []) => {
                const root = document.querySelector('[data-chip-select="lead-sequences"]');
                if (!root) {
                    return;
                }

                const optionsWrap = root.querySelector('[data-chip-options]');
                if (!optionsWrap) {
                    return;
                }

                if (!clienteId) {
                    optionsWrap.innerHTML = '';
                    chipSelects['lead-sequences'] = initChipSelect(root);
                    chipSelects['lead-sequences']?.setSelected([]);
                    return;
                }

                const url = sequencesUrlTemplate.replace('__CLIENT__', clienteId);
                const response = await fetch(url);
                if (!response.ok) {
                    optionsWrap.innerHTML = '';
                    chipSelects['lead-sequences'] = initChipSelect(root);
                    chipSelects['lead-sequences']?.setSelected([]);
                    return;
                }

                const json = await response.json();
                optionsWrap.innerHTML = json.map(item => {
                    const label = item.conexao_name ? `${item.name} (${item.conexao_name})` : item.name;
                    return `<button type="button" data-chip-option data-value="${item.id}" data-label="${label}" class="flex w-full items-center justify-between px-3 py-2 text-left text-xs text-slate-600 hover:bg-slate-50">${label}</button>`;
                }).join('');

                chipSelects['lead-sequences'] = initChipSelect(root);
                const normalized = Array.isArray(selected) ? selected.map(value => String(value)) : [];
                chipSelects['lead-sequences']?.setSelected(normalized);
            };

            const fillForm = (data) => {
                if (clientLeadFormSelect) {
                    clientLeadFormSelect.value = data?.cliente?.id ?? '';
                }
                if (clientLeadFormBot) {
                    clientLeadFormBot.checked = Boolean(data?.bot_enabled);
                }
                if (clientLeadFormPhone) {
                    clientLeadFormPhone.value = data?.phone_raw ?? '';
                }
                if (clientLeadFormName) {
                    clientLeadFormName.value = data?.name_raw ?? '';
                }
                if (clientLeadFormInfo) {
                    clientLeadFormInfo.value = data?.info_raw ?? '';
                }
                if (Array.isArray(data?.tag_ids)) {
                    chipSelects['lead-tags']?.setSelected(data.tag_ids);
                }
                if (clientLeadFormSelect) {
                    populateSequences(clientLeadFormSelect.value, data?.sequence_ids ?? []);
                }
                setLeadCustomFieldRows(data?.custom_fields ?? []);
            };

            const openForm = (mode = 'create', data = null) => {
                if (!clientLeadForm || !formModal) {
                    return;
                }

                resetForm();
                importForm?.reset();
                chipSelects['import-tags']?.setSelected([]);
                if (csvDelimiterSelect) {
                    csvDelimiterSelect.disabled = false;
                    csvDelimiterSelect.classList.remove('opacity-60');
                }
                resetImportPreviewState();
                setImportTabVisible(mode !== 'edit');
                setActiveTab('manual');
                if (mode === 'edit' && data) {
                    if (clientLeadFormTitle) {
                        clientLeadFormTitle.textContent = 'Editar lead';
                    }
                    if (clientLeadFormSubmit) {
                        clientLeadFormSubmit.textContent = 'Atualizar';
                    }
                    if (clientLeadFormMethod) {
                        clientLeadFormMethod.value = 'PUT';
                    }
                    clientLeadForm.action = buildFilteredActionUrl(
                        clientLeadForm.dataset.updateRouteTemplate.replace('__LEAD_ID__', data.id)
                    );
                    fillForm(data);
                }

                formModal.classList.remove('hidden');
            };

            addLeadBtn?.addEventListener('click', () => openForm('create'));

            clientLeadFormSelect?.addEventListener('change', () => {
                populateSequences(clientLeadFormSelect.value);
                syncLeadCustomFieldSelectOptions();
            });

            leadCustomFieldAddBtn?.addEventListener('click', () => {
                addLeadCustomFieldRow();
            });

            const isModifiedClick = (event) => event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;

            const handleLeadTableClick = (event) => {
                if (!leadTableContainer) {
                    return;
                }

                const conversationButton = event.target.closest('[data-open-conversation]');
                if (conversationButton && leadTableContainer.contains(conversationButton)) {
                    const data = parseLeadData(conversationButton);
                    if (data) {
                        openConversation(data);
                    }
                    return;
                }

                const editButton = event.target.closest('[data-open-lead-form]');
                if (editButton && leadTableContainer.contains(editButton)) {
                    const data = parseLeadData(editButton);
                    if (data) {
                        openForm('edit', data);
                    }
                    return;
                }

                const pageLink = event.target.closest('a[href]');
                if (pageLink && leadTableContainer.contains(pageLink)) {
                    if (pageLink.getAttribute('aria-disabled') === 'true' || isModifiedClick(event)) {
                        return;
                    }
                    event.preventDefault();
                    fetchLeads(pageLink.href, { pushState: true });
                }
            };

            leadTableContainer?.addEventListener('click', handleLeadTableClick);

            document.querySelectorAll('[data-form-close]').forEach(button => {
                button.addEventListener('click', closeFormModal);
            });

            formModal?.addEventListener('click', event => {
                if (event.target === formModal) {
                    closeFormModal();
                }
            });

            formTabs.forEach(button => {
                button.addEventListener('click', () => setActiveTab(button.dataset.formTab));
            });

            const sanitizeValue = (value) => (value ?? '').toString().trim();

            const isValidPhone = (value) => {
                const digits = sanitizeValue(value).replace(/\D/g, '');
                return digits.length >= 10 && digits.length <= 15;
            };

            const normalizeArray = (value) => {
                if (Array.isArray(value)) {
                    return value;
                }
                if (value && typeof value === 'object') {
                    return Object.values(value);
                }
                return [];
            };

            const setPreviewMessage = (message) => {
                if (previewEmpty) {
                    previewEmpty.textContent = message;
                    previewEmpty.classList.remove('hidden');
                }
                if (importMappingWrap) {
                    importMappingWrap.classList.add('hidden');
                }
                if (previewPhoneStatus) {
                    previewPhoneStatus.textContent = 'Telefone: -';
                    previewPhoneStatus.className = 'text-[11px] font-semibold text-slate-500';
                }
            };

            const resolveAllowedImportCustomFields = () => {
                const clienteId = Number(importClientSelect?.value || 0);
                if (!Number.isInteger(clienteId) || clienteId <= 0) {
                    return [];
                }

                return (Array.isArray(availableLeadCustomFields) ? availableLeadCustomFields : [])
                    .filter((field) => {
                        const fieldClienteId = field?.cliente_id ? Number(field.cliente_id) : null;
                        return fieldClienteId === null || fieldClienteId === clienteId;
                    });
            };

            const getImportMappingSelects = () => Array.from(
                importMappingRows?.querySelectorAll('[data-import-map-select]') ?? []
            );

            const syncImportMappingSelectAvailability = () => {
                const selects = getImportMappingSelects();
                selects.forEach((select) => {
                    const currentValue = select.value;
                    Array.from(select.options).forEach((option) => {
                        if (option.value === 'ignore' || option.value === currentValue) {
                            option.disabled = false;
                            return;
                        }

                        const selectedElsewhere = selects.some((other) => other !== select && other.value === option.value);
                        option.disabled = selectedElsewhere;
                    });
                });
            };

            const buildImportMappingRows = () => {
                if (!importMappingRows) {
                    return;
                }

                const previousSelections = new Map(
                    getImportMappingSelects().map((select) => [
                        Number(select.dataset.columnIndex || -1),
                        select.value || 'ignore',
                    ])
                );

                importMappingRows.innerHTML = '';
                const columnCount = Math.max(previewHeaders.length, previewSampleRow.length);
                if (columnCount === 0) {
                    if (importMappingWrap) {
                        importMappingWrap.classList.add('hidden');
                    }
                    return;
                }

                const customFieldOptions = resolveAllowedImportCustomFields();
                for (let columnIndex = 0; columnIndex < columnCount; columnIndex += 1) {
                    const label = sanitizeValue(previewHeaders[columnIndex]) || `Coluna ${columnIndex + 1}`;
                    const sample = sanitizeValue(previewSampleRow[columnIndex]) || '-';
                    const selectedValue = previousSelections.get(columnIndex) || 'ignore';

                    const row = document.createElement('tr');
                    row.className = 'align-top';

                    const columnCell = document.createElement('td');
                    columnCell.className = 'px-3 py-2';
                    columnCell.innerHTML = `
                        <p class="font-semibold text-slate-700">${escapeHtml(label)}</p>
                        <p class="mt-1 text-[11px] text-slate-500">${escapeHtml(sample)}</p>
                    `;

                    const mappingCell = document.createElement('td');
                    mappingCell.className = 'px-3 py-2';

                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = `column_mappings[${columnIndex}][column_index]`;
                    hiddenInput.value = String(columnIndex);

                    const select = document.createElement('select');
                    select.name = `column_mappings[${columnIndex}][target]`;
                    select.dataset.importMapSelect = '1';
                    select.dataset.columnIndex = String(columnIndex);
                    select.className = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 focus:border-slate-400 focus:outline-none';

                    const options = [
                        { value: 'ignore', label: 'Não importar' },
                        { value: 'phone', label: 'ClienteLead.phone (Telefone)' },
                        { value: 'name', label: 'ClienteLead.name (Nome)' },
                        ...customFieldOptions.map((field) => ({
                            value: `custom_field:${field.id}`,
                            label: `Campo personalizado: ${buildLeadCustomFieldLabel(field)}`,
                        })),
                    ];

                    options.forEach((item) => {
                        const option = document.createElement('option');
                        option.value = item.value;
                        option.textContent = item.label;
                        if (item.value === selectedValue) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });

                    if (!Array.from(select.options).some((option) => option.value === selectedValue)) {
                        select.value = 'ignore';
                    }

                    select.addEventListener('change', () => {
                        if (select.value !== 'ignore') {
                            const duplicated = getImportMappingSelects().find(
                                (other) => other !== select && other.value === select.value
                            );
                            if (duplicated) {
                                window.alert('Este campo já foi associado a outra coluna.');
                                select.value = 'ignore';
                            }
                        }

                        syncImportMappingSelectAvailability();
                        renderPreview();
                    });

                    mappingCell.appendChild(hiddenInput);
                    mappingCell.appendChild(select);
                    row.appendChild(columnCell);
                    row.appendChild(mappingCell);
                    importMappingRows.appendChild(row);
                }

                syncImportMappingSelectAvailability();
                if (importMappingWrap) {
                    importMappingWrap.classList.remove('hidden');
                }
            };

            const renderPreview = () => {
                if (!previewEmpty) {
                    return;
                }

                const columnCount = Math.max(previewHeaders.length, previewSampleRow.length);
                if (columnCount === 0) {
                    if (previewEmptyDefault) {
                        previewEmpty.textContent = previewEmptyDefault;
                    }
                    previewEmpty.classList.remove('hidden');
                    importMappingWrap?.classList.add('hidden');
                    if (previewPhoneStatus) {
                        previewPhoneStatus.textContent = 'Telefone: -';
                        previewPhoneStatus.className = 'text-[11px] font-semibold text-slate-500';
                    }
                    return;
                }

                previewEmpty.classList.add('hidden');
                importMappingWrap?.classList.remove('hidden');

                const phoneSelect = getImportMappingSelects().find((select) => select.value === 'phone');
                const phoneIndex = phoneSelect ? Number(phoneSelect.dataset.columnIndex || -1) : -1;
                const phoneValue = phoneIndex >= 0 ? sanitizeValue(previewSampleRow[phoneIndex]) : '';
                const phoneValid = phoneIndex >= 0 && phoneValue !== '' ? isValidPhone(phoneValue) : false;

                if (!previewPhoneStatus) {
                    return;
                }

                if (phoneIndex < 0) {
                    previewPhoneStatus.textContent = 'Telefone: selecione um mapeamento';
                    previewPhoneStatus.className = 'text-[11px] font-semibold text-slate-500';
                    return;
                }

                if (phoneValue === '') {
                    previewPhoneStatus.textContent = 'Telefone: coluna mapeada, sem valor na prévia';
                    previewPhoneStatus.className = 'text-[11px] font-semibold text-amber-600';
                    return;
                }

                previewPhoneStatus.textContent = phoneValid
                    ? 'Telefone: valor de prévia válido'
                    : 'Telefone: valor de prévia possivelmente inválido';
                previewPhoneStatus.className = phoneValid
                    ? 'text-[11px] font-semibold text-emerald-600'
                    : 'text-[11px] font-semibold text-amber-600';
            };

            const resetImportPreviewState = () => {
                previewHeaders = [];
                previewSampleRow = [];
                buildImportMappingRows();
                renderPreview();
            };

            const readCsvHeaders = async () => {
                if (!csvFileInput || !csvFileInput.files || !csvFileInput.files[0]) {
                    resetImportPreviewState();
                    return;
                }

                const file = csvFileInput.files[0];
                const filename = file.name.toLowerCase();
                const isXlsx = filename.endsWith('.xlsx');

                if (csvDelimiterSelect) {
                    csvDelimiterSelect.disabled = isXlsx;
                    csvDelimiterSelect.classList.toggle('opacity-60', isXlsx);
                }

                const delimiterKey = csvDelimiterSelect?.value ?? 'semicolon';
                const hasHeader = csvHasHeaderSelect?.value ?? 'yes';
                const formData = new FormData();
                formData.append('csv_file', file);
                formData.append('delimiter', delimiterKey);
                formData.append('has_header', hasHeader);

                const previewUrl = importForm?.dataset.previewUrl;
                if (!previewUrl) {
                    return;
                }

                try {
                    setPreviewMessage('Carregando prévia do arquivo...');
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        resetImportPreviewState();
                        setPreviewMessage('Não foi possível ler o arquivo. Verifique o formato e tente novamente.');
                        return;
                    }

                    const payload = await response.json();
                    if (payload?.error) {
                        resetImportPreviewState();
                        setPreviewMessage(payload.error);
                        return;
                    }

                    previewHeaders = normalizeArray(payload?.headers);
                    const previewRows = normalizeArray(payload?.rows).map(row => normalizeArray(row));
                    previewSampleRow = previewRows.length ? previewRows[0] : [];

                    buildImportMappingRows();
                    renderPreview();
                } catch (error) {
                    resetImportPreviewState();
                    setPreviewMessage('Não foi possível ler o arquivo. Verifique o formato e tente novamente.');
                }
            };

            csvFileInput?.addEventListener('change', readCsvHeaders);
            csvDelimiterSelect?.addEventListener('change', readCsvHeaders);
            csvHasHeaderSelect?.addEventListener('change', readCsvHeaders);
            importClientSelect?.addEventListener('change', () => {
                buildImportMappingRows();
                renderPreview();
            });

            importForm?.addEventListener('submit', (event) => {
                const hasPhoneMapping = getImportMappingSelects().some((select) => select.value === 'phone');
                if (!hasPhoneMapping) {
                    event.preventDefault();
                    window.alert('Mapeie uma coluna para o campo telefone antes de importar.');
                }
            });

            // Chip filters are handled by initChipSelect above.
        })();
    </script>
@endpush
