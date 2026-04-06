<section
    class="flex w-[320px] shrink-0 flex-col rounded-3xl border border-slate-200 bg-slate-50/70 p-4 shadow-sm"
    data-column-id="{{ $column['id'] }}"
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <span
                    class="h-3 w-3 shrink-0 rounded-full border border-white/60 shadow-sm"
                    style="background-color: {{ $column['tag_color'] !== '' ? $column['tag_color'] : '#0f172a' }};"
                ></span>
                <h3 class="truncate text-sm font-semibold text-slate-900">{{ $column['tag_name'] }}</h3>
            </div>
            <p class="mt-1 text-xs text-slate-500">
                <span data-column-count>{{ $column['count'] }}</span> lead(s)
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span
                class="cursor-move rounded-full border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                data-column-handle
            >
                mover
            </span>
            <button
                type="button"
                class="rounded-full border border-sky-200 bg-sky-50 px-2 py-1 text-[10px] font-semibold text-sky-700 hover:bg-sky-100"
                data-open-add-lead-modal
                data-column-id="{{ $column['id'] }}"
                data-column-name="{{ $column['tag_name'] }}"
                data-search-url="{{ route('cliente.crm.columns.search-leads', [$crmPipeline, $column['id']]) }}"
                data-add-url="{{ route('cliente.crm.columns.leads.store', [$crmPipeline, $column['id']]) }}"
            >
                adicionar
            </button>
            <form method="POST" action="{{ route('cliente.crm.columns.destroy', [$crmPipeline, $column['id']]) }}" onsubmit="return confirm('Deseja remover esta coluna da pipeline?');">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="rounded-full border border-rose-200 bg-white px-2 py-1 text-[10px] font-semibold text-rose-600 hover:bg-rose-50"
                >
                    excluir
                </button>
            </form>
        </div>
    </div>

    <div class="mt-4 flex min-h-[320px] flex-1">
        <div class="flex min-h-full flex-1 flex-col gap-3 rounded-2xl p-1" data-column-cards>
            @include('cliente.crm._lead_cards', ['leads' => $column['leads']])

            <div
                class="pointer-events-none flex min-h-[200px] flex-1 items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white/70 px-4 py-6 text-center text-xs text-slate-400 {{ count($column['leads']) > 0 ? 'hidden' : '' }}"
                data-column-empty
            >
                Nenhum lead visível nesta etapa.
            </div>
        </div>
    </div>

    <button
        type="button"
        class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 shadow-sm hover:bg-slate-100 {{ $column['has_more'] ? '' : 'hidden' }}"
        data-load-more
        data-url="{{ route('cliente.crm.columns.leads', [$crmPipeline, $column['id']]) }}"
        data-offset="{{ $column['next_offset'] }}"
        data-query="{{ $searchValue }}"
    >
        Carregar mais
    </button>
</section>
