@extends('layouts.cliente')

@section('title', 'CRM - ' . $crmPipeline->name)

@push('head')
    @vite('resources/js/cliente-crm.js')
@endpush

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('cliente.crm.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">
                ← Voltar para pipelines
            </a>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $crmPipeline->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">Board kanban da pipeline atual, organizado por etapas do funil.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                data-open-column-modal
            >
                Adicionar etapa
            </button>
        </div>
    </div>

    <form method="GET" action="{{ route('cliente.crm.show', $crmPipeline) }}" class="mt-6 flex flex-wrap items-center gap-3">
        <div class="min-w-[260px] flex-1">
            <input
                type="search"
                name="q"
                value="{{ $searchValue }}"
                placeholder="Buscar por nome ou telefone"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm focus:border-slate-400 focus:outline-none"
            >
        </div>
        <button
            type="submit"
            class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
        >
            Buscar
        </button>
        <a
            href="{{ route('cliente.crm.show', $crmPipeline) }}"
            class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50"
        >
            Limpar
        </a>
        <p class="w-full text-xs text-slate-400">A busca só entra em vigor com pelo menos 3 caracteres.</p>
    </form>

    <div
        class="mt-8"
        data-crm-board
        data-move-url-template="{{ route('cliente.crm.leads.column', ['crmPipeline' => $crmPipeline, 'clienteLead' => '__LEAD_ID__']) }}"
    >
        @if($columns->isEmpty())
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-8 py-12 text-center shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Configure as primeiras etapas deste funil.</h3>
                <p class="mt-2 text-sm text-slate-500">Crie as colunas que representam o avanço dos leads nesta pipeline.</p>
                <div class="mt-6">
                    <button type="button" data-open-column-modal class="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Criar primeira etapa
                    </button>
                </div>
            </div>
        @else
            <div class="mb-4 flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Use as setas de cada etapa para reordenar o funil e arraste os cards entre etapas.</p>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Esquerda para direita</p>
            </div>

            <div class="overflow-x-auto pb-4">
                <div class="flex min-w-max items-start gap-4">
                    @foreach($board as $column)
                        @include('cliente.crm._column', [
                            'crmPipeline' => $crmPipeline,
                            'column' => $column,
                            'searchValue' => $searchValue,
                        ])
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div id="crmColumnModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Nova etapa da pipeline</h3>
                    <p class="mt-1 text-sm text-slate-500">Defina o nome da etapa que será exibida no funil.</p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-column-modal>x</button>
            </div>

            <form method="POST" action="{{ route('cliente.crm.columns.store', $crmPipeline) }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="crmColumnName" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nome da etapa</label>
                    <input
                        id="crmColumnName"
                        name="name"
                        type="text"
                        maxlength="191"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                        placeholder="Ex.: Novo contato"
                        required
                    >
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" data-close-column-modal>
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Criar etapa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="crmAddLeadModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6">
        <div class="w-full max-w-4xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Adicionar lead na coluna</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Busque por nome ou telefone para posicionar o lead em <span class="font-semibold text-slate-700" data-add-lead-column-name>-</span>.
                    </p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-add-lead-modal>x</button>
            </div>

            <form class="mt-6 grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]" data-add-lead-search-form>
                <div>
                    <label for="crmAddLeadSearchName" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nome</label>
                    <input
                        id="crmAddLeadSearchName"
                        type="search"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm focus:border-slate-400 focus:outline-none"
                        placeholder="Digite ao menos 3 caracteres"
                    >
                </div>
                <div>
                    <label for="crmAddLeadSearchPhone" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Telefone</label>
                    <input
                        id="crmAddLeadSearchPhone"
                        type="search"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm focus:border-slate-400 focus:outline-none"
                        placeholder="Digite ao menos 3 dígitos"
                    >
                </div>
                <div class="flex items-end gap-3">
                    <button
                        type="submit"
                        class="rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        data-add-lead-search-submit
                    >
                        Buscar
                    </button>
                </div>
            </form>

            <p class="mt-3 text-xs text-slate-400" data-add-lead-search-feedback>
                Informe ao menos 3 caracteres em nome ou 3 dígitos no telefone para buscar leads.
            </p>

            <div class="mt-5 max-h-[420px] overflow-y-auto pr-1" data-add-lead-search-results></div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <button
                    type="button"
                    class="hidden rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50"
                    data-add-lead-load-more
                >
                    Carregar mais
                </button>
                <button type="button" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" data-close-add-lead-modal>
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <div id="crmLeadModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6">
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Detalhes do lead</h3>
                    <p class="mt-1 text-sm text-slate-500">Informações rápidas do card selecionado.</p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-lead-modal>x</button>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Lead</p>
                    <p id="crmLeadModalName" class="mt-1 text-sm font-semibold text-slate-900"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Telefone</p>
                    <p id="crmLeadModalPhone" class="mt-1 text-sm text-slate-700"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Criado em</p>
                    <p id="crmLeadModalCreatedAt" class="mt-1 text-sm text-slate-700"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Bot</p>
                    <p id="crmLeadModalBot" class="mt-1 text-sm text-slate-700"></p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Informações</p>
                    <p id="crmLeadModalInfo" class="mt-1 text-sm leading-6 text-slate-700"></p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Tags</p>
                    <div id="crmLeadModalTags" class="mt-2 flex flex-wrap gap-2"></div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" data-close-lead-modal>
                    Fechar
                </button>
            </div>
        </div>
    </div>
@endsection
