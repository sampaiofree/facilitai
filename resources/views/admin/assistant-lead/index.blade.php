@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Assistant Leads</h2>
            <p class="text-sm text-slate-500">Registros concluídos pelo job de processamento de mensagens.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('adm.assistant-lead.index') }}" class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="assistantLeadSearch" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Buscar por nome ou telefone</label>
            <input
                id="assistantLeadSearch"
                name="q"
                type="text"
                value="{{ $search ?? '' }}"
                placeholder="Ex.: Maria ou 551199999999"
                class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
            >
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Buscar</button>
            @if(!empty($search))
                <a href="{{ route('adm.assistant-lead.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Limpar</a>
            @endif
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">ID</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Lead</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Assistente</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Conv ID</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Criado em</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($assistantLeads as $assistantLead)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $assistantLead->id }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $assistantLead->lead?->name ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $assistantLead->assistant?->name ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $assistantLead->conv_id ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $assistantLead->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button type="button"
                                    data-view-record="{{ json_encode([
                                        'id' => $assistantLead->id,
                                        'lead' => $assistantLead->lead?->name,
                                        'assistant' => $assistantLead->assistant?->name,
                                        'version' => $assistantLead->version,
                                        'conv_id' => $assistantLead->conv_id,
                                        'created_at' => optional($assistantLead->created_at)->format('d/m/Y H:i:s'),
                                        'updated_at' => optional($assistantLead->updated_at)->format('d/m/Y H:i:s'),
                                        'webhook_payload' => $assistantLead->webhook_payload,
                                        'assistant_response' => $assistantLead->assistant_response,
                                        'job_message' => $assistantLead->job_message,
                                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                >Ver</button>
                                <form method="POST" action="{{ route('adm.assistant-lead.destroy', $assistantLead) }}" onsubmit="return confirm('Deseja excluir este registro?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-6 text-center text-slate-500">Nenhum registro encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($assistantLeads->hasPages())
        <div class="mt-4 flex items-center justify-end">
            {{ $assistantLeads->links('pagination::tailwind') }}
        </div>
    @endif

    <div id="assistantLeadModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 backdrop-blur">
        <div class="w-[min(720px,calc(100%-2rem))] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Detalhes do AssistantLead</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>
            <div class="mt-5 space-y-4 text-slate-700">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">ID</dt>
                        <dd id="assistantLeadDetailId" class="font-medium text-slate-900"></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Lead</dt>
                        <dd id="assistantLeadDetailLead"></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Assistente</dt>
                        <dd id="assistantLeadDetailAssistant"></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Versão</dt>
                        <dd id="assistantLeadDetailVersion"></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Conv ID</dt>
                        <dd id="assistantLeadDetailConvId" style="word-wrap: anywhere;"></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Criado em</dt>
                        <dd id="assistantLeadDetailCreatedAt"></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Atualizado em</dt>
                        <dd id="assistantLeadDetailUpdatedAt"></dd>
                    </div>
                </dl>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Webhook Payload</p>
                    <pre id="assistantLeadPayload" class="mt-1 max-h-48 overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-3 text-[0.70rem] text-slate-700"></pre>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Resposta da IA</p>
                    <pre id="assistantLeadResponse" class="mt-1 max-h-48 overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-3 text-[0.70rem] text-slate-700"></pre>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Mensagem do job</p>
                    <pre id="assistantLeadJobMessage" class="mt-1 max-h-48 overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-3 text-[0.70rem] text-slate-700"></pre>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('assistantLeadModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const fields = {
                id: document.getElementById('assistantLeadDetailId'),
                lead: document.getElementById('assistantLeadDetailLead'),
                assistant: document.getElementById('assistantLeadDetailAssistant'),
                version: document.getElementById('assistantLeadDetailVersion'),
                convId: document.getElementById('assistantLeadDetailConvId'),
                createdAt: document.getElementById('assistantLeadDetailCreatedAt'),
                updatedAt: document.getElementById('assistantLeadDetailUpdatedAt'),
                payload: document.getElementById('assistantLeadPayload'),
                response: document.getElementById('assistantLeadResponse'),
                jobMessage: document.getElementById('assistantLeadJobMessage'),
            };

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };
            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.querySelectorAll('[data-view-record]').forEach(button => {
                button.addEventListener('click', () => {
                    const data = JSON.parse(button.dataset.viewRecord);
                    fields.id.textContent = data.id ?? '—';
                    fields.lead.textContent = data.lead ?? '—';
                    fields.assistant.textContent = data.assistant ?? '—';
                    fields.version.textContent = data.version ?? '—';
                    fields.convId.textContent = data.conv_id ?? '—';
                    fields.createdAt.textContent = data.created_at ?? '—';
                    fields.updatedAt.textContent = data.updated_at ?? '—';
                    fields.payload.textContent = JSON.stringify(data.webhook_payload ?? {}, null, 2);
                    fields.response.textContent = JSON.stringify(data.assistant_response ?? {}, null, 2);
                    fields.jobMessage.textContent = data.job_message ?? '—';
                    openModal();
                });
            });
        })();
    </script>
@endsection
