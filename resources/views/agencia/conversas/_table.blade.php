<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <table class="min-w-full text-sm text-slate-600">
        <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wide">
            <tr>
                <th class="px-5 py-3 text-left font-semibold">Cliente</th>
                <th class="px-5 py-3 text-left font-semibold">Bot</th>
                <th class="px-5 py-3 text-left font-semibold">Telefone</th>
                <th class="px-5 py-3 text-left font-semibold">Lead</th>
                <th class="px-5 py-3 text-left font-semibold">Sequencias</th>
                <th class="px-5 py-3 text-left font-semibold">Criado em</th>
                <th class="px-5 py-3 text-right font-semibold">Acoes</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($leads as $lead)
                @php
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
                            <form method="POST" action="{{ route('agencia.conversas.destroy', $lead) }}" onsubmit="return confirm('Deseja excluir este lead?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-full bg-rose-100 px-4 py-1 text-[12px] font-semibold text-rose-700 hover:bg-rose-200">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-5 py-6 text-center text-xs text-slate-400">Nenhum lead encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 flex justify-end">
    {{ $leads->links('pagination::tailwind') }}
</div>
