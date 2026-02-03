@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Cliente Leads</h2>
            <p class="text-sm text-slate-500">Registros de leads associados aos clientes.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">ID</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Cliente</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Telefone</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Bot</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Criado em</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($leads as $lead)
                    @php
                        $viewData = [
                            'id' => $lead->id,
                            'phone' => $lead->phone ?? '-',
                            'name' => $lead->name ?? '-',
                            'info' => $lead->info ?? '-',
                            'bot_enabled' => $lead->bot_enabled ? 'Ativado' : 'Desativado',
                            'created_at' => $lead->created_at?->format('d/m/Y H:i') ?? '-',
                            'cliente' => [
                                'id' => $lead->cliente_id ?? '-',
                                'nome' => optional($lead->cliente)->nome ?? '-',
                                'user_id' => optional($lead->cliente->user)->id ?? '-',
                                'user_name' => optional($lead->cliente->user)->name ?? '-',
                            ],
                            'assistant_leads' => $lead->assistantLeads->map(function ($assistantLead) {
                                return [
                                    'assistant' => optional($assistantLead->assistant)->name ?? '-',
                                    'version' => $assistantLead->version,
                                    'conv_id' => $assistantLead->conv_id ?? '-',
                                    'created_at' => $assistantLead->created_at?->format('d/m/Y H:i') ?? '-',
                                ];
                            })->toArray(),
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $lead->id }}</td>
                        <td class="px-5 py-4 text-slate-600">
                            <div class="font-medium text-slate-800">{{ $lead->cliente_id ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ optional($lead->cliente)->nome ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $lead->phone ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $lead->name ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $lead->bot_enabled ? 'Ativado' : 'Desativado' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $lead->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-slate-600 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700"
                                    data-open-view
                                    data-lead='@json($viewData)'
                                >Ver</button>
                                <form method="POST" action="{{ route('adm.cliente-lead.destroy', $lead) }}" onsubmit="return confirm('Deseja excluir este lead?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-6 text-center text-slate-500">Nenhum lead cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="clienteLeadViewModal" class="fixed inset-0 hidden flex items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[640px] max-w-full rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Detalhes do lead</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-view-close>x</button>
            </div>

            <div class="mt-5 space-y-4 text-sm text-slate-600">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">ID do lead</p>
                    <p id="viewClienteLeadId" class="font-medium text-slate-900"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Cliente</p>
                    <p id="viewClienteLeadCliente" class="font-medium text-slate-900"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Usuário</p>
                    <p id="viewClienteLeadUsuario"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Telefone</p>
                    <p id="viewClienteLeadPhone"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Nome</p>
                    <p id="viewClienteLeadName"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Bot</p>
                    <p id="viewClienteLeadBot"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Informações</p>
                    <p id="viewClienteLeadInfo"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Criado em</p>
                    <p id="viewClienteLeadCreatedAt"></p>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Assistentes relacionados</p>
                    <div class="mt-2 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full text-xs">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 text-left uppercase tracking-wide">Assistente</th>
                                    <th class="px-3 py-2 text-left uppercase tracking-wide">Versão</th>
                                    <th class="px-3 py-2 text-left uppercase tracking-wide">Conv ID</th>
                                    <th class="px-3 py-2 text-left uppercase tracking-wide">Criado em</th>
                                </tr>
                            </thead>
                            <tbody id="clienteLeadAssistantBody" class="text-slate-600">
                                <tr>
                                    <td colspan="4" class="px-3 py-2 text-center text-slate-400">Nenhum assistente associado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('clienteLeadViewModal');
            const assistantBody = document.getElementById('clienteLeadAssistantBody');

            function closeModal() {
                if (modal) {
                    modal.classList.add('hidden');
                }
            }

            function renderAssistants(list) {
                if (!Array.isArray(list) || list.length === 0) {
                    return `<tr><td colspan="4" class="px-3 py-2 text-center text-slate-400">Nenhum assistente associado.</td></tr>`;
                }

                return list.map(item => `
                    <tr>
                        <td class="px-3 py-2 font-medium text-slate-800">${item.assistant}</td>
                        <td class="px-3 py-2">${item.version}</td>
                        <td class="px-3 py-2 font-mono text-xs">${item.conv_id}</td>
                        <td class="px-3 py-2">${item.created_at}</td>
                    </tr>
                `).join('');
            }

            document.querySelectorAll('[data-open-view]').forEach(button => {
                button.addEventListener('click', () => {
                    const data = JSON.parse(button.getAttribute('data-lead'));
                    document.getElementById('viewClienteLeadId').textContent = data.id;
                    document.getElementById('viewClienteLeadCliente').textContent = `${data.cliente.id} · ${data.cliente.nome}`;
                    document.getElementById('viewClienteLeadUsuario').textContent = `${data.cliente.user_id} · ${data.cliente.user_name}`;
                    document.getElementById('viewClienteLeadPhone').textContent = data.phone;
                    document.getElementById('viewClienteLeadName').textContent = data.name;
                    document.getElementById('viewClienteLeadBot').textContent = data.bot_enabled;
                    document.getElementById('viewClienteLeadInfo').textContent = data.info;
                    document.getElementById('viewClienteLeadCreatedAt').textContent = data.created_at;
                    if (assistantBody) {
                        assistantBody.innerHTML = renderAssistants(data.assistant_leads);
                    }
                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                });
            });

            document.querySelectorAll('[data-view-close]').forEach(button => {
                button.addEventListener('click', closeModal);
            });

            if (modal) {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal();
                    }
                });
            }
        })();
    </script>
@endpush
