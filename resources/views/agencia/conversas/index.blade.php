@extends('layouts.agencia')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Conversas</h2>
            <p class="text-sm text-slate-500">Todos os leads dos seus clientes, filtráveis por cliente, data e tags.</p>
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
                    id="exportToggle"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 flex items-center gap-2"
                >
                    Exportar
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="exportMenu" class="hidden absolute right-0 mt-1 w-40 rounded-2xl border border-slate-200 bg-white shadow-lg">
                    <a href="{{ $exportXlsx }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">XLSX</a>
                    <a href="{{ $exportCsv }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">CSV</a>
                    <a href="{{ $exportPdf }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">PDF</a>
                </div>
            </div>
            <button
                type="button"
                id="openClienteLeadForm"
                class="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
            >Adicionar</button>
        </div>
    </div>

    

    <form method="GET" class="flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-500 shadow-sm" style="align-items: end;">
        <div class="flex flex-1 min-w-[220px] flex-col gap-1" data-chip-select="filter-clients" data-input-name="cliente_id[]">
            <span class="text-[10px] uppercase tracking-wide text-slate-400">Cliente</span>
            <div class="flex flex-wrap gap-2" data-chip-list></div>
            <div class="relative">
                <input
                    type="search"
                    data-chip-search
                    placeholder="Buscar cliente"
                    class="w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
                >
                <div class="absolute left-0 right-0 z-10 mt-1 hidden max-h-56 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-lg" data-chip-options>
                    @foreach($clients as $client)
                        <button
                            type="button"
                            data-chip-option
                            data-value="{{ $client->id }}"
                            data-label="{{ $client->nome }}"
                            class="flex w-full items-center justify-between px-3 py-2 text-left text-xs text-slate-600 hover:bg-slate-50"
                        >
                            <span>{{ $client->nome }}</span>
                            <span class="text-[10px] text-slate-400">ID {{ $client->id }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="hidden" data-chip-inputs>
                @foreach($clientFilter as $clientId)
                    <input type="hidden" name="cliente_id[]" value="{{ $clientId }}">
                @endforeach
            </div>
        </div>

        <div class="flex flex-1 min-w-[280px] flex-col gap-1" data-chip-select="filter-tags" data-input-name="tags[]">
            <span class="text-[10px] uppercase tracking-wide text-slate-400">Tags</span>
            <div class="flex flex-wrap gap-2" data-chip-list></div>
            <div class="relative">
                <input
                    type="search"
                    data-chip-search
                    placeholder="Buscar tags"
                    class="w-full rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-[12px] text-slate-700 focus:border-slate-400 focus:outline-none"
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
                        <div class="px-3 py-2 text-xs text-slate-400">Nenhuma tag vinculada ainda.</div>
                    @endforelse
                </div>
            </div>
            <div class="hidden" data-chip-inputs>
                @foreach($tagFilter as $tagId)
                    <input type="hidden" name="tags[]" value="{{ $tagId }}">
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

        <div class="ml-auto flex items-center gap-2">
            <button type="submit" class="rounded-2xl bg-blue-600 px-4 py-2 text-[12px] font-semibold text-white hover:bg-blue-700">Aplicar</button>
            <a href="{{ route('agencia.conversas.index') }}" class="text-[12px] font-semibold text-slate-500">Limpar</a>
        </div>
    </form>

    <div class="mt-4 border-b border-slate-200"></div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <table class="min-w-full text-sm text-slate-600">
            <thead class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-wide">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold">Cliente</th>
                    <th class="px-5 py-3 text-left font-semibold">Bot</th>
                    <th class="px-5 py-3 text-left font-semibold">Telefone</th>
                    <th class="px-5 py-3 text-left font-semibold">Lead</th>
                    <th class="px-5 py-3 text-left font-semibold">Criado em</th>
                    <th class="px-5 py-3 text-right font-semibold">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($leads as $lead)
                    @php
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
                            'tags' => $lead->tags->pluck('name')->all(),
                            'tag_ids' => $lead->tags->pluck('id')->all(),
                            'bot_enabled' => $lead->bot_enabled,
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-800">{{ $lead->cliente_id ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ $lead->cliente?->nome ?? '—' }}</div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $lead->bot_enabled ? 'Ativado' : 'Desativado' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $lead->phone ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $lead->name ?? '-' }}</td>
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
                        <td colspan="6" class="px-5 py-6 text-center text-xs text-slate-400">Nenhum lead encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        {{ $leads->links('pagination::tailwind') }}
    </div>

    <div id="agenciaClienteLeadFormModal" class="fixed inset-0 z-50 hidden flex items-start justify-center overflow-auto bg-black/50 px-4 py-6">
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="clienteLeadFormTitle" class="text-lg font-semibold text-slate-900">Adicionar lead</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-form-close>x</button>
            </div>
            <div class="mt-4 flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 p-1 text-xs font-semibold text-slate-500">
                <button type="button" data-form-tab="manual" class="rounded-full px-4 py-1.5 text-slate-700 bg-white shadow-sm">Adicionar</button>
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

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="clienteLeadFormBot" name="bot_enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
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

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <span class="text-[11px] uppercase tracking-wide text-slate-400">Cliente</span>
                        <select
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
                        <span class="text-[11px] uppercase tracking-wide text-slate-400">Arquivo CSV</span>
                        <input
                            type="file"
                            name="csv_file"
                            accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            required
                            data-csv-file
                            class="w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-2 text-sm text-slate-600 focus:border-slate-400 focus:outline-none"
                        >
                        <p class="text-[11px] text-slate-400">A primeira linha do arquivo será usada como cabeçalho.</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
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
                    <div class="space-y-2">
                        <span class="text-[11px] uppercase tracking-wide text-slate-400">Mapear telefone</span>
                        <select
                            name="map_phone"
                            required
                            data-map-select="phone"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                        >
                            <option value="">Selecione a coluna</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <span class="text-[11px] uppercase tracking-wide text-slate-400">Mapear nome</span>
                        <select
                            name="map_name"
                            data-map-select="name"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                        >
                            <option value="">Não mapear</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <span class="text-[11px] uppercase tracking-wide text-slate-400">Mapear informações</span>
                        <select
                            name="map_info"
                            data-map-select="info"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                        >
                            <option value="">Não mapear</option>
                        </select>
                        <p class="text-[11px] text-slate-400">Em XLSX, as colunas são exibidas por posição.</p>
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

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Previa do arquivo (ate 3 linhas)</p>
                            <p class="text-xs text-slate-500">Revise os dados antes de importar e confirme se o telefone esta correto.</p>
                        </div>
                        <span id="previewPhoneStatus" class="text-[11px] font-semibold text-slate-500">Telefone: -</span>
                    </div>
                    <div id="previewEmpty" class="mt-3 rounded-xl border border-dashed border-slate-300 bg-white px-4 py-3 text-xs text-slate-500">
                        Selecione um arquivo para visualizar a previa.
                    </div>
                    <div id="previewCards" class="mt-3 space-y-3 hidden"></div>
                </div>


                <div class="flex justify-end gap-3">
                    <button type="button" data-form-close class="rounded-2xl border border-slate-200 px-4 py-1 text-[12px] font-semibold text-slate-600 hover:border-slate-400">Cancelar</button>
                    <button type="submit" class="rounded-2xl bg-blue-600 px-4 py-1 text-[12px] font-semibold text-white hover:bg-blue-700">Importar</button>
                </div>
            </form>
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
                            </tr>
                        </thead>
                        <tbody id="viewLeadAssistants" class="border-t border-slate-100 text-slate-700">
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-center text-slate-400">Nenhum assistente associado.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
            const addLeadBtn = document.getElementById('openClienteLeadForm');
            const formTabs = document.querySelectorAll('[data-form-tab]');
            const csvFileInput = document.querySelector('[data-csv-file]');
            const csvDelimiterSelect = document.querySelector('[data-csv-delimiter]');
            const exportToggle = document.getElementById('exportToggle');
            const exportMenu = document.getElementById('exportMenu');
            const previewEmpty = document.getElementById('previewEmpty');
            const previewCards = document.getElementById('previewCards');
            const previewPhoneStatus = document.getElementById('previewPhoneStatus');
            const previewEmptyDefault = previewEmpty?.textContent || '';
            let previewHeaders = [];
            let previewRows = [];
            const mapSelects = document.querySelectorAll('[data-map-select]');
            const chipSelects = {};

            if (exportToggle && exportMenu) {
                exportToggle.addEventListener('click', () => {
                    exportMenu.classList.toggle('hidden');
                });
                document.addEventListener('click', (event) => {
                    if (!exportMenu.contains(event.target) && !exportToggle.contains(event.target)) {
                        exportMenu.classList.add('hidden');
                    }
                });
            }

            const closeModal = () => {
                modal?.classList.add('hidden');
            };

            const renderAssistants = (list = []) => {
                if (!Array.isArray(list) || list.length === 0) {
                    return `<tr>
                        <td colspan="4" class="px-3 py-2 text-center text-slate-400">Nenhum assistente associado.</td>
                    </tr>`;
                }

                return list.map(item => `
                    <tr>
                        <td class="px-3 py-2 font-medium text-slate-800">${item.assistant}</td>
                        <td class="px-3 py-2">${item.version}</td>
                        <td class="px-3 py-2 font-mono text-[11px]">${item.conv_id}</td>
                        <td class="px-3 py-2">${item.created_at}</td>
                    </tr>
                `).join('');
            };

            document.querySelectorAll('[data-open-conversation]').forEach(button => {
                button.addEventListener('click', () => {
                    const raw = button.getAttribute('data-lead');
                    if (!raw) return;

                    const data = JSON.parse(raw);

                    document.getElementById('viewLeadId').textContent = data.id;
                    document.getElementById('viewLeadCliente').textContent = `${data.cliente.id} · ${data.cliente.nome}`;
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

                    modal?.classList.remove('hidden');
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

            const closeFormModal = () => {
                formModal?.classList.add('hidden');
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
                    chip.innerHTML = `<span>${label}</span>`;

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

            document.querySelectorAll('[data-chip-select]').forEach(root => {
                const key = root.dataset.chipSelect;
                const api = initChipSelect(root);
                if (key && api) {
                    chipSelects[key] = api;
                }
            });

            const setActiveTab = (tab) => {
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
                    clientLeadFormBot.checked = false;
                }
                chipSelects['lead-tags']?.setSelected([]);
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
            };

            const openForm = (mode = 'create', data = null) => {
                if (!clientLeadForm || !formModal) {
                    return;
                }

                resetForm();
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
                    clientLeadForm.action = clientLeadForm.dataset.updateRouteTemplate.replace('__LEAD_ID__', data.id);
                    fillForm(data);
                }

                formModal.classList.remove('hidden');
            };

            addLeadBtn?.addEventListener('click', () => openForm('create'));

            document.querySelectorAll('[data-open-lead-form]').forEach(button => {
                button.addEventListener('click', () => {
                    const raw = button.getAttribute('data-lead');
                    if (!raw) {
                        return;
                    }

                    const leadData = JSON.parse(raw);
                    openForm('edit', leadData);
                });
            });

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
                return digits.length >= 11;
            };

            const renderPreview = () => {
                if (!previewCards || !previewEmpty) {
                    return;
                }

                previewCards.innerHTML = '';

                if (!previewRows.length) {
                    previewCards.classList.add('hidden');
                    previewEmpty.classList.remove('hidden');
                    if (previewEmptyDefault && previewEmpty) {
                        previewEmpty.textContent = previewEmptyDefault;
                    }
                    if (previewPhoneStatus) {
                        previewPhoneStatus.textContent = 'Telefone: -';
                        previewPhoneStatus.className = 'text-[11px] font-semibold text-slate-500';
                    }
                    return;
                }

                const phoneSelect = document.querySelector('[data-map-select="phone"]');
                const phoneIndex = phoneSelect && phoneSelect.value !== '' ? Number(phoneSelect.value) : null;

                let validCount = 0;
                previewRows.forEach((row, idx) => {
                    const card = document.createElement('div');
                    card.className = 'rounded-xl border border-slate-200 bg-white px-4 py-3';

                    const header = document.createElement('div');
                    header.className = 'flex items-center justify-between mb-2';
                    header.innerHTML = `<span class="text-xs font-semibold text-slate-500">Linha ${idx + 1}</span>`;

                    const phoneValue = phoneIndex !== null ? sanitizeValue(row[phoneIndex]) : '';
                    const phoneOk = phoneIndex !== null ? isValidPhone(phoneValue) : false;
                    if (phoneOk) {
                        validCount += 1;
                    }

                    const statusBadge = document.createElement('span');
                    statusBadge.className = phoneIndex === null
                        ? 'text-[11px] text-slate-400'
                        : phoneOk
                            ? 'text-[11px] font-semibold text-emerald-600'
                            : 'text-[11px] font-semibold text-amber-600';
                    statusBadge.textContent = phoneIndex === null
                        ? 'Telefone: selecione a coluna'
                        : phoneOk
                            ? 'Telefone valido'
                            : 'Telefone possivelmente invalido';
                    header.appendChild(statusBadge);
                    card.appendChild(header);

                    const grid = document.createElement('div');
                    grid.className = 'grid gap-2 sm:grid-cols-2';

                    previewHeaders.forEach((label, colIndex) => {
                        const value = sanitizeValue(row[colIndex]);
                        if (value === '' && !label) {
                            return;
                        }
                        const block = document.createElement('div');
                        block.className = 'rounded-lg border border-slate-100 bg-slate-50 px-3 py-2';
                        const title = document.createElement('p');
                        title.className = 'text-[10px] uppercase tracking-wide text-slate-400';
                        title.textContent = label || `Coluna ${colIndex + 1}`;
                        const content = document.createElement('p');
                        content.className = colIndex === phoneIndex
                            ? 'text-xs font-semibold text-slate-800'
                            : 'text-xs text-slate-700';
                        content.textContent = value || '-';
                        block.appendChild(title);
                        block.appendChild(content);
                        grid.appendChild(block);
                    });

                    card.appendChild(grid);
                    previewCards.appendChild(card);
                });

                previewEmpty.classList.add('hidden');
                previewCards.classList.remove('hidden');
                if (previewPhoneStatus) {
                    previewPhoneStatus.textContent = phoneIndex === null
                        ? 'Telefone: coluna nao selecionada'
                        : `Telefone: ${validCount}/${previewRows.length} linhas validas`;
                    previewPhoneStatus.className = phoneIndex === null
                        ? 'text-[11px] font-semibold text-slate-500'
                        : validCount === previewRows.length
                            ? 'text-[11px] font-semibold text-emerald-600'
                            : 'text-[11px] font-semibold text-amber-600';
                }
            };

            const parseCsvLine = (line, delimiter) => {
                const result = [];
                let current = '';
                let inQuotes = false;
                for (let i = 0; i < line.length; i += 1) {
                    const char = line[i];
                    const next = line[i + 1];
                    if (char === '"' && inQuotes && next === '"') {
                        current += '"';
                        i += 1;
                        continue;
                    }
                    if (char === '"') {
                        inQuotes = !inQuotes;
                        continue;
                    }
                    if (char === delimiter && !inQuotes) {
                        result.push(current);
                        current = '';
                        continue;
                    }
                    current += char;
                }
                result.push(current);
                return result.map(value => value.trim());
            };

            const populateMappingOptions = (headers) => {
                mapSelects.forEach(select => {
                    const isPhone = select.dataset.mapSelect === 'phone';
                    const defaultLabel = isPhone ? 'Selecione a coluna' : 'Não mapear';
                    select.innerHTML = `<option value="">${defaultLabel}</option>`;
                    headers.forEach((header, index) => {
                        const option = document.createElement('option');
                        option.value = index;
                        option.textContent = header;
                        select.appendChild(option);
                    });
                });
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
                if (previewCards) {
                    previewCards.classList.add('hidden');
                }
            };


            const readCsvHeaders = async () => {
                if (!csvFileInput || !csvFileInput.files || !csvFileInput.files[0]) {
                    previewHeaders = [];
                    previewRows = [];
                    renderPreview();
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
                const formData = new FormData();
                formData.append('csv_file', file);
                formData.append('delimiter', delimiterKey);

                const previewUrl = importForm?.dataset.previewUrl;
                if (!previewUrl) {
                    return;
                }

                try {
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        previewHeaders = [];
                        previewRows = [];
                        populateMappingOptions([]);
                        setPreviewMessage('Não foi possível ler o arquivo. Verifique o formato e tente novamente.');
                        return;
                    }

                    const payload = await response.json();
                    if (payload?.error) {
                        previewHeaders = [];
                        previewRows = [];
                        populateMappingOptions([]);
                        setPreviewMessage(payload.error);
                        return;
                    }

                    previewHeaders = normalizeArray(payload?.headers);
                    previewRows = normalizeArray(payload?.rows).map(row => normalizeArray(row));

                    populateMappingOptions(previewHeaders);
                    renderPreview();
                } catch (error) {
                    previewHeaders = [];
                    previewRows = [];
                    populateMappingOptions([]);
                    setPreviewMessage('Não foi possível ler o arquivo. Verifique o formato e tente novamente.');
                }
            };

            csvFileInput?.addEventListener('change', readCsvHeaders);
            csvDelimiterSelect?.addEventListener('change', readCsvHeaders);

            mapSelects.forEach(select => {
                select.addEventListener('change', renderPreview);
            });

            // Chip filters are handled by initChipSelect above.
        })();
    </script>
@endpush
