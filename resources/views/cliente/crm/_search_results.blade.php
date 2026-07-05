@if($results === [])
    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
        Nenhum lead encontrado para os filtros informados.
    </div>
@else
    <div class="space-y-3">
        @foreach($results as $lead)
            <article class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 shadow-sm" data-search-result-lead-id="{{ $lead['id'] }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $lead['name'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $lead['phone'] }}</p>
                        <p class="mt-2 text-[11px] uppercase tracking-wide text-slate-400">
                            Etapa atual:
                            <span class="font-semibold text-slate-600">
                                {{ $lead['current_column_name'] ?: 'Fora do CRM' }}
                            </span>
                        </p>
                    </div>

                    <button
                        type="button"
                        class="shrink-0 rounded-2xl px-3 py-2 text-xs font-semibold shadow-sm {{ $lead['same_column'] ? 'cursor-not-allowed border border-slate-200 bg-slate-100 text-slate-400' : 'bg-blue-600 text-white hover:bg-blue-700' }}"
                        data-add-lead-to-column
                        data-lead-id="{{ $lead['id'] }}"
                        @disabled($lead['same_column'])
                    >
                        {{ $lead['same_column'] ? 'Já nesta coluna' : 'Adicionar' }}
                    </button>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse($lead['tags'] as $tag)
                        <span
                            class="inline-flex rounded-full border border-slate-200 px-2.5 py-1 text-[10px] font-semibold text-slate-600"
                            @if(!empty($tag['color']))
                                style="border-color: {{ $tag['color'] }}40; background-color: {{ $tag['color'] }}18; color: {{ $tag['color'] }};"
                            @endif
                        >
                            {{ $tag['name'] }}
                        </span>
                    @empty
                        <span class="text-[10px] text-slate-400">Sem tags</span>
                    @endforelse
                </div>
            </article>
        @endforeach
    </div>
@endif
