@extends('layouts.agencia')

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Mensagens agendadas</h2>
                    <p class="text-sm text-slate-500">Historico de agendamentos do usuario logado.</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                    Timezone: <span class="font-semibold text-slate-700">{{ $timezone }}</span>
                </div>
            </div>

            <form method="GET" class="mt-4 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-5">
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Status</label>
                    <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach(['pending', 'queued', 'sent', 'failed', 'canceled'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">De</label>
                    <input
                        type="date"
                        name="date_start"
                        value="{{ $filters['date_start'] ?? '' }}"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Ate</label>
                    <input
                        type="date"
                        name="date_end"
                        value="{{ $filters['date_end'] ?? '' }}"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    >
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Busca</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Nome ou telefone do lead"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    >
                </div>
                <div class="md:col-span-5 flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">
                        Filtrar
                    </button>
                    <a href="{{ route('agencia.mensagens-agendadas.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-900">
                        Limpar
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Lead</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Assistente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Agendado</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Tentativas</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Criado em</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Acoes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($scheduledMessages as $scheduledMessage)
                        @php
                            $statusClass = match ($scheduledMessage->status) {
                                'pending' => 'bg-amber-100 text-amber-700',
                                'queued' => 'bg-blue-100 text-blue-700',
                                'sent' => 'bg-emerald-100 text-emerald-700',
                                'failed' => 'bg-rose-100 text-rose-700',
                                'canceled' => 'bg-slate-200 text-slate-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">{{ $scheduledMessage->id }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-800">{{ $scheduledMessage->clienteLead?->name ?? '-' }}</div>
                                <div class="text-xs text-slate-400">{{ $scheduledMessage->clienteLead?->phone ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $scheduledMessage->assistant?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-[11px] font-semibold {{ $statusClass }}">
                                    {{ $scheduledMessage->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $scheduledMessage->scheduled_for_label ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $scheduledMessage->attempts }}</td>
                            <td class="px-4 py-3">{{ $scheduledMessage->created_at_label ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        data-view-scheduled
                                        data-scheduled-id="{{ $scheduledMessage->id }}"
                                        class="rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                    >
                                        Ver
                                    </button>

                                    @if($scheduledMessage->status === 'pending')
                                        <button
                                            type="button"
                                            data-edit-scheduled
                                            data-scheduled-id="{{ $scheduledMessage->id }}"
                                            class="rounded-md border border-blue-200 px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                                        >
                                            Editar
                                        </button>
                                        <form method="POST" action="{{ route('agencia.mensagens-agendadas.cancel', $scheduledMessage) }}" onsubmit="return confirm('Cancelar este agendamento?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-md bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-200">
                                                Cancelar
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum agendamento encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <div>
            {{ $scheduledMessages->links('pagination::tailwind') }}
        </div>
    </div>

    <div id="scheduledMessageModal" class="fixed inset-0 z-50 hidden flex items-center justify-center overflow-auto bg-black/50 px-4 py-6">
        <div class="w-full max-w-4xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Detalhes do agendamento</h3>
                    <p id="scheduledModalTimezone" class="text-xs text-slate-500"></p>
                </div>
                <button type="button" data-scheduled-modal-close class="text-slate-500 hover:text-slate-800">x</button>
            </div>

            <div id="scheduledModalLoading" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Carregando...
            </div>

            <div id="scheduledModalFetchError" class="mt-4 hidden rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

            <div id="scheduledModalContent" class="mt-4 hidden space-y-4">
                <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">ID</p>
                        <p id="sm_id" class="text-sm font-semibold text-slate-800">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Status</p>
                        <p id="sm_status" class="text-sm font-semibold text-slate-800">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Tentativas</p>
                        <p id="sm_attempts" class="text-sm font-semibold text-slate-800">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Lead</p>
                        <p id="sm_lead" class="text-sm text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Telefone</p>
                        <p id="sm_phone" class="text-sm text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Cliente</p>
                        <p id="sm_cliente" class="text-sm text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Assistente</p>
                        <p id="sm_assistant" class="text-sm text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Conexao</p>
                        <p id="sm_conexao" class="text-sm text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Criado por</p>
                        <p id="sm_creator" class="text-sm text-slate-700">-</p>
                    </div>
                </div>

                <div class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-2">
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Event ID</p>
                        <p id="sm_event_id" class="break-all font-mono text-xs text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Agendado para</p>
                        <p id="sm_scheduled_for" class="text-sm text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Enfileirado em</p>
                        <p id="sm_queued_at" class="text-sm text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Enviado em</p>
                        <p id="sm_sent_at" class="text-sm text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Falhou em</p>
                        <p id="sm_failed_at" class="text-sm text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Cancelado em</p>
                        <p id="sm_canceled_at" class="text-sm text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Criado em</p>
                        <p id="sm_created_at" class="text-sm text-slate-700">-</p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Atualizado em</p>
                        <p id="sm_updated_at" class="text-sm text-slate-700">-</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="mb-2 text-[11px] uppercase tracking-wide text-slate-500">Mensagem</p>
                    <pre id="sm_mensagem" class="whitespace-pre-wrap break-words text-sm text-slate-700">-</pre>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="mb-2 text-[11px] uppercase tracking-wide text-slate-500">Erro</p>
                    <p id="sm_error" class="text-sm text-rose-700">-</p>
                </div>
            </div>
        </div>
    </div>

    <div id="scheduledEditModal" class="fixed inset-0 z-50 hidden flex items-center justify-center overflow-auto bg-black/50 px-4 py-6">
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Editar agendamento</h3>
                    <p id="scheduledEditTimezone" class="text-xs text-slate-500"></p>
                </div>
                <button type="button" data-scheduled-edit-close class="text-slate-500 hover:text-slate-800">x</button>
            </div>

            <form id="scheduledEditForm" class="mt-4 space-y-4">
                <input type="hidden" id="scheduledEditId">

                <div>
                    <label for="scheduledEditMessage" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Mensagem</label>
                    <textarea
                        id="scheduledEditMessage"
                        rows="5"
                        maxlength="2000"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                    ></textarea>
                </div>

                <div>
                    <label for="scheduledEditDate" class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Data e horario</label>
                    <input
                        id="scheduledEditDate"
                        type="datetime-local"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                    >
                </div>

                <p id="scheduledEditError" class="hidden rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-xs text-rose-700"></p>
                <p id="scheduledEditSuccess" class="hidden rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs text-emerald-700"></p>

                <div class="flex justify-end gap-3">
                    <button type="button" data-scheduled-edit-close class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:border-slate-400">Cancelar</button>
                    <button type="submit" id="scheduledEditSubmit" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('scheduledMessageModal');
            const loading = document.getElementById('scheduledModalLoading');
            const fetchError = document.getElementById('scheduledModalFetchError');
            const content = document.getElementById('scheduledModalContent');
            const editModal = document.getElementById('scheduledEditModal');
            const editForm = document.getElementById('scheduledEditForm');
            const editId = document.getElementById('scheduledEditId');
            const editMessage = document.getElementById('scheduledEditMessage');
            const editDate = document.getElementById('scheduledEditDate');
            const editTimezone = document.getElementById('scheduledEditTimezone');
            const editError = document.getElementById('scheduledEditError');
            const editSuccess = document.getElementById('scheduledEditSuccess');
            const editSubmit = document.getElementById('scheduledEditSubmit');
            const showUrlTemplate = @json(route('agencia.mensagens-agendadas.show', ['scheduledMessage' => '__SCHEDULED_ID__']));
            const updateUrlTemplate = @json(route('agencia.mensagens-agendadas.update', ['scheduledMessage' => '__SCHEDULED_ID__']));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const closeModal = () => {
                if (!modal) {
                    return;
                }
                modal.classList.add('hidden');
            };

            const openModal = () => {
                if (!modal) {
                    return;
                }
                modal.classList.remove('hidden');
            };

            const closeEditModal = () => {
                if (!editModal) {
                    return;
                }
                editModal.classList.add('hidden');
                if (editError) {
                    editError.classList.add('hidden');
                }
                if (editSuccess) {
                    editSuccess.classList.add('hidden');
                }
            };

            const openEditModal = () => {
                if (!editModal) {
                    return;
                }
                editModal.classList.remove('hidden');
            };

            const setText = (id, value) => {
                const element = document.getElementById(id);
                if (!element) {
                    return;
                }
                element.textContent = value || '-';
            };

            const buildDate = (label, iso) => {
                return label || iso || '-';
            };

            const renderDetails = (payload) => {
                setText('sm_id', payload.id ? String(payload.id) : '-');
                setText('sm_status', payload.status || '-');
                setText('sm_attempts', payload.attempts !== null && payload.attempts !== undefined ? String(payload.attempts) : '-');
                setText('sm_lead', payload.lead?.name || '-');
                setText('sm_phone', payload.lead?.phone || '-');
                setText('sm_cliente', payload.lead?.cliente || '-');
                setText('sm_assistant', payload.assistant?.name || '-');

                const conexaoText = payload.conexao?.id
                    ? `${payload.conexao.id} - ${payload.conexao.name || '-'} (${payload.conexao.phone || '-'})`
                    : '-';
                setText('sm_conexao', conexaoText);

                const creatorText = payload.creator?.id
                    ? `${payload.creator.name || '-'} (${payload.creator.email || '-'})`
                    : '-';
                setText('sm_creator', creatorText);

                setText('sm_event_id', payload.event_id || '-');
                setText('sm_scheduled_for', buildDate(payload.timestamps?.scheduled_for_label, payload.timestamps?.scheduled_for));
                setText('sm_queued_at', buildDate(payload.timestamps?.queued_at_label, payload.timestamps?.queued_at));
                setText('sm_sent_at', buildDate(payload.timestamps?.sent_at_label, payload.timestamps?.sent_at));
                setText('sm_failed_at', buildDate(payload.timestamps?.failed_at_label, payload.timestamps?.failed_at));
                setText('sm_canceled_at', buildDate(payload.timestamps?.canceled_at_label, payload.timestamps?.canceled_at));
                setText('sm_created_at', buildDate(payload.timestamps?.created_at_label, payload.timestamps?.created_at));
                setText('sm_updated_at', buildDate(payload.timestamps?.updated_at_label, payload.timestamps?.updated_at));
                setText('sm_mensagem', payload.mensagem || '-');
                setText('sm_error', payload.error_message || '-');
                setText('scheduledModalTimezone', `Timezone: ${payload.timezone || '-'}`);
            };

            const fetchScheduledMessage = async (scheduledId) => {
                const url = showUrlTemplate.replace('__SCHEDULED_ID__', scheduledId);
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                let payload = {};
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = {};
                }

                if (!response.ok) {
                    throw new Error(payload.message || 'Nao foi possivel carregar os detalhes do agendamento.');
                }

                return payload;
            };

            const loadDetails = async (scheduledId) => {
                if (!scheduledId || !loading || !fetchError || !content) {
                    return;
                }

                loading.classList.remove('hidden');
                fetchError.classList.add('hidden');
                content.classList.add('hidden');
                openModal();

                try {
                    const payload = await fetchScheduledMessage(scheduledId);
                    renderDetails(payload);
                    content.classList.remove('hidden');
                } catch (error) {
                    fetchError.textContent = error.message || 'Falha ao carregar o agendamento.';
                    fetchError.classList.remove('hidden');
                } finally {
                    loading.classList.add('hidden');
                }
            };

            const loadEditData = async (scheduledId) => {
                if (!scheduledId || !editForm || !editMessage || !editDate || !editSubmit) {
                    return;
                }

                if (editError) {
                    editError.classList.add('hidden');
                }
                if (editSuccess) {
                    editSuccess.classList.add('hidden');
                }

                editSubmit.disabled = true;
                editSubmit.textContent = 'Carregando...';
                openEditModal();

                try {
                    const payload = await fetchScheduledMessage(scheduledId);
                    if (payload.status !== 'pending') {
                        throw new Error('Somente agendamentos pendentes podem ser editados.');
                    }

                    if (editId) {
                        editId.value = String(payload.id || scheduledId);
                    }
                    editMessage.value = payload.mensagem || '';
                    editDate.value = payload.timestamps?.scheduled_for_input || '';
                    editDate.min = payload.timestamps?.now_input || '';

                    if (editTimezone) {
                        editTimezone.textContent = `Timezone: ${payload.timezone || '-'}`;
                    }
                } catch (error) {
                    if (editError) {
                        editError.textContent = error.message || 'Nao foi possivel carregar o agendamento para edicao.';
                        editError.classList.remove('hidden');
                    }
                } finally {
                    editSubmit.disabled = false;
                    editSubmit.textContent = 'Salvar';
                }
            };

            document.querySelectorAll('[data-view-scheduled]').forEach((button) => {
                button.addEventListener('click', () => {
                    loadDetails(button.dataset.scheduledId || '');
                });
            });

            document.querySelectorAll('[data-edit-scheduled]').forEach((button) => {
                button.addEventListener('click', () => {
                    loadEditData(button.dataset.scheduledId || '');
                });
            });

            document.querySelectorAll('[data-scheduled-modal-close]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.querySelectorAll('[data-scheduled-edit-close]').forEach((button) => {
                button.addEventListener('click', closeEditModal);
            });

            modal?.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            editModal?.addEventListener('click', (event) => {
                if (event.target === editModal) {
                    closeEditModal();
                }
            });

            editForm?.addEventListener('submit', async (event) => {
                event.preventDefault();

                const scheduledId = editId?.value || '';
                const mensagem = (editMessage?.value || '').trim();
                const scheduledFor = (editDate?.value || '').trim();

                if (editError) {
                    editError.classList.add('hidden');
                }
                if (editSuccess) {
                    editSuccess.classList.add('hidden');
                }

                if (!scheduledId) {
                    if (editError) {
                        editError.textContent = 'Agendamento invalido para edicao.';
                        editError.classList.remove('hidden');
                    }
                    return;
                }

                if (mensagem === '') {
                    if (editError) {
                        editError.textContent = 'Informe a mensagem.';
                        editError.classList.remove('hidden');
                    }
                    return;
                }

                if (scheduledFor === '') {
                    if (editError) {
                        editError.textContent = 'Informe a data e horario.';
                        editError.classList.remove('hidden');
                    }
                    return;
                }

                if (editSubmit) {
                    editSubmit.disabled = true;
                    editSubmit.textContent = 'Salvando...';
                }

                try {
                    const url = updateUrlTemplate.replace('__SCHEDULED_ID__', scheduledId);
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            mensagem,
                            scheduled_for: scheduledFor,
                        }),
                    });

                    let payload = {};
                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok) {
                        throw new Error(payload.message || 'Nao foi possivel salvar as alteracoes.');
                    }

                    if (editSuccess) {
                        editSuccess.textContent = payload.message || 'Agendamento atualizado com sucesso.';
                        editSuccess.classList.remove('hidden');
                    }

                    setTimeout(() => {
                        window.location.reload();
                    }, 700);
                } catch (error) {
                    if (editError) {
                        editError.textContent = error.message || 'Falha ao atualizar agendamento.';
                        editError.classList.remove('hidden');
                    }
                } finally {
                    if (editSubmit) {
                        editSubmit.disabled = false;
                        editSubmit.textContent = 'Salvar';
                    }
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                    closeModal();
                }
                if (event.key === 'Escape' && editModal && !editModal.classList.contains('hidden')) {
                    closeEditModal();
                }
            });
        })();
    </script>
@endpush
