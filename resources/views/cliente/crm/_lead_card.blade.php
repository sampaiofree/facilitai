@php
    $leadJson = json_encode($lead, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $leadPayload = base64_encode($leadJson);
@endphp

<article
    class="group cursor-grab select-none rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md active:cursor-grabbing"
    data-lead-card
    data-lead-id="{{ $lead['id'] }}"
    data-lead="{{ $leadPayload }}"
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900">{{ $lead['name'] }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $lead['phone'] }}</p>
        </div>
        <span class="inline-flex shrink-0 rounded-full px-2 py-1 text-[10px] font-semibold {{ $lead['bot_enabled'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
            {{ $lead['bot_enabled'] ? 'Bot on' : 'Bot off' }}
        </span>
    </div>

    <p class="mt-3 line-clamp-3 text-xs leading-5 text-slate-600">
        {{ $lead['info'] }}
    </p>

    <div class="mt-4 flex flex-wrap gap-2">
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

    <div class="mt-4 flex items-center justify-between text-[11px] text-slate-400">
        <span>ID #{{ $lead['id'] }}</span>
        <div class="flex items-center gap-3">
            <span>{{ $lead['created_at'] }}</span>
            <button
                type="button"
                class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold text-slate-600 hover:bg-slate-100"
                data-open-lead-modal
            >
                detalhes
            </button>
            <button
                type="button"
                class="rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-[10px] font-semibold text-rose-700 hover:bg-rose-100"
                data-remove-lead-from-pipeline
                data-remove-url="{{ route('cliente.crm.leads.destroy', [$crmPipeline, $lead['id']]) }}"
            >
                excluir
            </button>
        </div>
    </div>
</article>
