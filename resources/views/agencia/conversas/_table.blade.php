@php
    $currentSortBy = (string) request()->query('sort_by', 'created_at');
    if (!in_array($currentSortBy, ['created_at', 'updated_at'], true)) {
        $currentSortBy = 'created_at';
    }

    $currentSortDir = strtolower((string) request()->query('sort_dir', 'desc'));
    if (!in_array($currentSortDir, ['asc', 'desc'], true)) {
        $currentSortDir = 'desc';
    }

    $buildSortUrl = function (string $column) use ($currentSortBy, $currentSortDir): string {
        $nextDir = $currentSortBy === $column && $currentSortDir === 'desc' ? 'asc' : 'desc';
        $query = request()->query();
        unset($query['page']);
        $query['sort_by'] = $column;
        $query['sort_dir'] = $nextDir;

        return route('agencia.conversas.index', $query);
    };
@endphp

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <table class="min-w-full text-sm text-slate-600">
        <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wide">
            <tr>
                <th class="px-5 py-3 text-left font-semibold">Cliente</th>
                <th class="px-5 py-3 text-left font-semibold">Bot</th>
                <th class="px-5 py-3 text-left font-semibold">Telefone</th>
                <th class="px-5 py-3 text-left font-semibold">Lead</th>
                <th class="px-5 py-3 text-left font-semibold">Sequencias</th>
                <th class="px-5 py-3 text-left font-semibold">
                    <a
                        href="{{ $buildSortUrl('updated_at') }}"
                        class="inline-flex items-center gap-1 hover:text-slate-600"
                    >
                        <span>Ultimo contato</span>
                        @if($currentSortBy === 'updated_at')
                            @if($currentSortDir === 'asc')
                                <svg class="h-3 w-3 text-slate-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 4a1 1 0 0 1 .707.293l4 4a1 1 0 1 1-1.414 1.414L10 6.414 6.707 9.707a1 1 0 0 1-1.414-1.414l4-4A1 1 0 0 1 10 4Z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="h-3 w-3 text-slate-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 16a1 1 0 0 1-.707-.293l-4-4a1 1 0 1 1 1.414-1.414L10 13.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4A1 1 0 0 1 10 16Z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        @else
                            <svg class="h-3 w-3 text-slate-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 4a1 1 0 0 1 .707.293l2.5 2.5a1 1 0 1 1-1.414 1.414L10 6.414 8.207 8.207A1 1 0 0 1 6.793 6.793l2.5-2.5A1 1 0 0 1 10 4Zm0 12a1 1 0 0 1-.707-.293l-2.5-2.5a1 1 0 0 1 1.414-1.414L10 13.586l1.793-1.793a1 1 0 0 1 1.414 1.414l-2.5 2.5A1 1 0 0 1 10 16Z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </a>
                </th>
                <th class="px-5 py-3 text-left font-semibold">
                    <a
                        href="{{ $buildSortUrl('created_at') }}"
                        class="inline-flex items-center gap-1 hover:text-slate-600"
                    >
                        <span>Criado em</span>
                        @if($currentSortBy === 'created_at')
                            @if($currentSortDir === 'asc')
                                <svg class="h-3 w-3 text-slate-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 4a1 1 0 0 1 .707.293l4 4a1 1 0 1 1-1.414 1.414L10 6.414 6.707 9.707a1 1 0 0 1-1.414-1.414l4-4A1 1 0 0 1 10 4Z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="h-3 w-3 text-slate-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 16a1 1 0 0 1-.707-.293l-4-4a1 1 0 1 1 1.414-1.414L10 13.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4A1 1 0 0 1 10 16Z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        @else
                            <svg class="h-3 w-3 text-slate-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 4a1 1 0 0 1 .707.293l2.5 2.5a1 1 0 1 1-1.414 1.414L10 6.414 8.207 8.207A1 1 0 0 1 6.793 6.793l2.5-2.5A1 1 0 0 1 10 4Zm0 12a1 1 0 0 1-.707-.293l-2.5-2.5a1 1 0 0 1 1.414-1.414L10 13.586l1.793-1.793a1 1 0 0 1 1.414 1.414l-2.5 2.5A1 1 0 0 1 10 16Z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </a>
                </th>
                <th class="px-5 py-3 text-right font-semibold">Acoes</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($leads as $lead)
                @php
                    $destroyUrl = route('agencia.conversas.destroy', array_merge(
                        ['clienteLead' => $lead],
                        request()->query()
                    ));
                    $sequenceNames = $lead->sequenceChats
                        ->map(fn ($sequenceChat) => $sequenceChat->sequence?->name)
                        ->filter()
                        ->unique()
                        ->values();
                    $viewData = [
                        'id' => $lead->id,
                        'cliente' => [
                            'id' => $lead->cliente_id,
                            'nome' => $lead->cliente?->nome,
                            'user_id' => optional($lead->cliente->user)->id,
                            'user_name' => optional($lead->cliente->user)->name,
                        ],
                        'phone' => $lead->phone ?? '-',
                        'phone_raw' => $lead->phone,
                        'name' => $lead->name ?? '-',
                        'name_raw' => $lead->name,
                        'info' => $lead->info ?? '-',
                        'info_raw' => $lead->info,
                        'bot' => $lead->bot_enabled ? 'Ativado' : 'Desativado',
                        'created_at' => $lead->created_at?->format('d/m/Y H:i') ?? '-',
                        'assistant_leads' => $lead->assistantLeads->map(function ($assistantLead) {
                            return [
                                'assistant_id' => $assistantLead->assistant_id,
                                'assistant' => optional($assistantLead->assistant)->name ?? '-',
                                'version' => $assistantLead->version,
                                'conv_id' => $assistantLead->conv_id ?? '-',
                                'created_at' => $assistantLead->created_at?->format('d/m/Y H:i') ?? '-',
                            ];
                        })->toArray(),
                        'sequence_ids' => $lead->sequenceChats
                            ->pluck('sequence_id')
                            ->unique()
                            ->values()
                            ->all(),
                        'tags' => $lead->tags->pluck('name')->all(),
                        'tag_ids' => $lead->tags->pluck('id')->all(),
                        'custom_fields' => $lead->customFieldValues
                            ->map(function ($item) {
                                return [
                                    'field_id' => (int) $item->whatsapp_cloud_custom_field_id,
                                    'name' => (string) ($item->customField?->name ?? ''),
                                    'label' => (string) ($item->customField?->label ?? ''),
                                    'value' => (string) ($item->value ?? ''),
                                ];
                            })
                            ->values()
                            ->all(),
                        'bot_enabled' => $lead->bot_enabled,
                    ];
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-4">
                        <div class="text-xs text-slate-400">{{ $lead->cliente_id ?? '' }} {{ $lead->cliente?->nome ?? '' }}</div>
                    </td>
                    <td class="px-5 py-4 text-slate-600">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $lead->bot_enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                            {{ $lead->bot_enabled ? 'Ativado' : 'Desativado' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-slate-600">{{ $lead->phone ?? '-' }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $lead->name ?? '-' }}</td>
                    <td class="px-5 py-4 text-slate-600">
                        @if($sequenceNames->isEmpty())
                            -
                        @else
                            <div class="flex flex-wrap gap-1">
                                @foreach($sequenceNames as $sequenceName)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">{{ $sequenceName }}</span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-slate-600">{{ $lead->updated_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $lead->created_at?->format('d/m/Y') ?? '-' }}</td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex flex-wrap justify-end gap-2">
                            <button
                                type="button"
                                class="rounded-full bg-slate-900 px-4 py-1 text-[12px] font-semibold text-white hover:bg-slate-800"
                                data-open-conversation
                                data-lead='@json($viewData, JSON_UNESCAPED_UNICODE)'
                            >Ver</button>
                            <button
                                type="button"
                                class="rounded-full border border-slate-300 px-4 py-1 text-[12px] font-semibold text-slate-600 hover:border-slate-500 hover:text-slate-900"
                                data-open-lead-form
                                data-lead='@json($viewData, JSON_UNESCAPED_UNICODE)'
                            >Editar</button>
                            <form method="POST" action="{{ $destroyUrl }}" onsubmit="return confirm('Deseja excluir este lead?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-full bg-rose-100 px-4 py-1 text-[12px] font-semibold text-rose-700 hover:bg-rose-200">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-5 py-6 text-center text-xs text-slate-400">Nenhum lead encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 flex justify-end">
    {{ $leads->links('pagination::tailwind') }}
</div>
