@extends('layouts.cliente')

@section('title', 'CRM')

@push('head')
    @vite('resources/js/cliente-crm.js')
@endpush

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">CRM</h2>
            <p class="mt-1 text-sm text-slate-500">Organize seus leads em múltiplas pipelines com estágios baseados em tags.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a
                href="{{ route('cliente.conversas.index') }}"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Ver conversas
            </a>
            <a
                href="{{ route('cliente.tags.index') }}"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Gerenciar tags
            </a>
            <button
                type="button"
                class="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                data-open-pipeline-create-modal
            >
                Nova pipeline
            </button>
        </div>
    </div>

    @if($pipelines->isEmpty())
        <div class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white px-8 py-12 text-center shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Você ainda não criou nenhuma pipeline.</h3>
            <p class="mt-2 text-sm text-slate-500">Crie sua primeira pipeline para começar a organizar o CRM por estágios.</p>
            <div class="mt-6">
                <button
                    type="button"
                    class="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    data-open-pipeline-create-modal
                >
                    Criar primeira pipeline
                </button>
            </div>
        </div>
    @else
        <div class="mt-8" data-crm-pipelines-list data-reorder-url="{{ route('cliente.crm.pipelines.reorder') }}">
            <div class="mb-4 flex items-center justify-between gap-3">
                <p class="text-sm text-slate-500">Arraste para reordenar suas pipelines.</p>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Clique em abrir para acessar o board</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3" data-pipelines-track>
                @foreach($pipelines as $pipeline)
                    <article
                        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                        data-pipeline-id="{{ $pipeline->id }}"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="cursor-move rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                                        data-pipeline-handle
                                    >
                                        mover
                                    </span>
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Pipeline</p>
                                </div>
                                <h3 class="mt-3 truncate text-lg font-semibold text-slate-900">{{ $pipeline->name }}</h3>
                                <p class="mt-2 text-sm text-slate-500">{{ $pipeline->columns_count }} etapa(s) configurada(s)</p>
                            </div>

                            <button
                                type="button"
                                class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                data-open-pipeline-edit-modal
                                data-pipeline-id="{{ $pipeline->id }}"
                                data-pipeline-name="{{ $pipeline->name }}"
                                data-update-url="{{ route('cliente.crm.pipelines.update', $pipeline) }}"
                                data-delete-url="{{ route('cliente.crm.pipelines.destroy', $pipeline) }}"
                            >
                                editar
                            </button>
                        </div>

                        <div class="mt-6 flex items-center justify-between gap-3">
                            <p class="text-xs text-slate-400">Etapas baseadas em tags exclusivas da pipeline.</p>
                            <a
                                href="{{ route('cliente.crm.show', $pipeline) }}"
                                class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                            >
                                Abrir
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    <div id="crmPipelineModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900" data-pipeline-modal-title>Nova pipeline</h3>
                    <p class="mt-1 text-sm text-slate-500" data-pipeline-modal-description>Defina o nome da pipeline que será usada no CRM.</p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-pipeline-modal>x</button>
            </div>

            <form method="POST" action="{{ route('cliente.crm.pipelines.store') }}" class="mt-6 space-y-4" data-pipeline-form>
                @csrf
                <input type="hidden" name="_method" value="POST" data-pipeline-method>

                <div>
                    <label for="crmPipelineName" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nome</label>
                    <input
                        id="crmPipelineName"
                        name="name"
                        type="text"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                        placeholder="Ex.: Vendas"
                        required
                    >
                </div>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <div class="hidden" data-pipeline-delete-slot></div>
                    <div class="ml-auto flex gap-3">
                        <button type="button" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" data-close-pipeline-modal>
                            Cancelar
                        </button>
                        <button type="submit" class="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700" data-pipeline-submit-label>
                            Criar pipeline
                        </button>
                    </div>
                </div>
            </form>

            <form method="POST" action="#" class="mt-3 hidden" data-pipeline-delete-form>
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100"
                    onclick="return confirm('Deseja remover esta pipeline do CRM?');"
                >
                    Excluir pipeline
                </button>
            </form>
        </div>
    </div>
@endsection
