@extends('layouts.cliente')

@section('title', 'Conexoes')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Conexoes</h2>
            <p class="text-sm text-slate-500">Visualize, conecte e, quando permitido, edite suas instancias.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($conexoes->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-6 text-center text-slate-500">
            Nenhuma conexao cadastrada para sua conta.
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($conexoes as $conexao)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $conexao->name ?? 'Conexao' }}</p>
                            <p class="text-xs text-slate-500">Phone: {{ $conexao->phone ?? '-' }}</p>
                            <p class="text-xs text-slate-500">Modelo: {{ $conexao->iamodelo?->nome ?? '-' }}</p>
                            <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $conexao->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $conexao->is_active ? 'Ativa' : 'Inativa' }}
                            </span>
                        </div>
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold transition {{ $conexao->status === 'connected' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}"
                            data-conexao-status
                            data-conexao-id="{{ $conexao->id }}"
                        >
                            {{ $conexao->status ?? 'pendente' }}
                        </span>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <button
                            type="button"
                            class="{{ $conexao->permitiredicao ? 'flex-1' : 'w-full' }} rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                            data-conexao-connect
                            data-conexao-id="{{ $conexao->id }}"
                            data-can-connect="{{ $conexao->whatsappApi?->slug === 'uazapi' ? '1' : '0' }}"
                            data-is-active="{{ $conexao->is_active ? '1' : '0' }}"
                            {{ $conexao->whatsappApi?->slug === 'uazapi' && $conexao->is_active ? '' : 'disabled' }}
                        >Conectar</button>

                        @if($conexao->permitiredicao)
                            <button
                                type="button"
                                class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                data-open-edit
                                data-id="{{ $conexao->id }}"
                                data-name="{{ $conexao->name }}"
                                data-model-id="{{ $conexao->model }}"
                                data-iaplataforma-id="{{ $conexao->credential?->iaplataforma_id ?? '' }}"
                                data-is-active="{{ $conexao->is_active ? '1' : '0' }}"
                            >Editar</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div id="conexaoEditModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="conexaoEditModalTitle" class="text-lg font-semibold text-slate-900">Editar conexao</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-edit-close>x</button>
            </div>

            <form id="conexaoEditForm" method="POST" action="" class="mt-5 space-y-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="editing_id" id="conexaoEditingId" value="{{ old('editing_id') }}">

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="conexaoEditModel">Modelo</label>
                    <select id="conexaoEditModel" name="model" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="" selected>Escolha um modelo</option>
                        @foreach ($iamodelos as $modelo)
                            <option value="{{ $modelo->id }}" data-iaplataforma-id="{{ $modelo->iaplataforma_id }}" @selected(old('model') == $modelo->id)>{{ $modelo->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input
                        id="conexaoEditActive"
                        name="is_active"
                        type="checkbox"
                        value="1"
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        @checked(old('is_active', '1') === '1')
                    >
                    <label for="conexaoEditActive" class="text-sm text-slate-600">Conexao ativa</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-edit-close>Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="conexaoConnectModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Conectar WhatsApp</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-connect-close>x</button>
            </div>

            <div class="mt-6 flex flex-col items-center gap-4">
                <div id="connectSpinner" class="flex items-center gap-3 text-slate-600">
                    <span class="inline-flex h-6 w-6 animate-spin rounded-full border-2 border-slate-200 border-t-blue-500"></span>
                    <span>Aguarde...</span>
                </div>
                <img id="connectQrCode" class="hidden h-56 w-56 rounded-lg border border-slate-200 object-contain" alt="QR Code">
                <p id="connectPaircodeText" class="text-sm text-slate-600"></p>
                <p id="connectErrorText" class="text-sm font-semibold text-rose-600"></p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (() => {
        const statusElements = Array.from(document.querySelectorAll('[data-conexao-status]'));
        const connectButtons = Array.from(document.querySelectorAll('[data-conexao-connect]'));
        const editButtons = Array.from(document.querySelectorAll('[data-open-edit]'));
        const baseUrl = "{{ url('/cliente/conexoes') }}";
        const statusUrl = (id) => `${baseUrl}/${id}/status`;
        const connectUrl = (id) => `${baseUrl}/${id}/connect`;

        const hasErrors = @json($errors->any());
        const sessionEditingId = @json(old('editing_id'));
        const oldModelId = @json(old('model'));
        const oldIsActive = @json(old('is_active', '1'));

        const editModal = document.getElementById('conexaoEditModal');
        const editForm = document.getElementById('conexaoEditForm');
        const editTitle = document.getElementById('conexaoEditModalTitle');
        const editModelSelect = document.getElementById('conexaoEditModel');
        const editModelOptions = Array.from(editModelSelect.querySelectorAll('option'));
        const editModelPlaceholder = editModelOptions.find((option) => option.value === '');
        const editActiveInput = document.getElementById('conexaoEditActive');
        const editingInput = document.getElementById('conexaoEditingId');
        const editCloseButtons = editModal?.querySelectorAll('[data-edit-close]') ?? [];

        const connectModal = document.getElementById('conexaoConnectModal');
        const connectCloseBtn = connectModal?.querySelector('[data-connect-close]');
        const connectSpinner = document.getElementById('connectSpinner');
        const connectQrCode = document.getElementById('connectQrCode');
        const connectPaircodeText = document.getElementById('connectPaircodeText');
        const connectErrorText = document.getElementById('connectErrorText');
        let connectStatusTimer = null;
        let connectRefreshTimer = null;
        let connectStatusAttempts = 0;
        let connectRefreshAttempts = 0;
        const maxStatusAttempts = 10;
        const maxConnectAttempts = 10;
        let currentConnectId = null;

        const filterModelOptions = (iaplataformaId) => {
            let hasVisible = false;

            editModelOptions.forEach((option) => {
                if (!option.value) {
                    return;
                }

                const matches = !iaplataformaId || option.dataset.iaplataformaId === String(iaplataformaId);
                option.hidden = !matches;
                option.disabled = !matches;

                if (matches) {
                    hasVisible = true;
                }
            });

            if (editModelPlaceholder) {
                if (!iaplataformaId) {
                    editModelPlaceholder.textContent = 'Escolha um modelo';
                } else if (hasVisible) {
                    editModelPlaceholder.textContent = 'Escolha um modelo';
                } else {
                    editModelPlaceholder.textContent = 'Nenhum modelo disponivel para esta plataforma';
                }
            }

            if (!hasVisible) {
                editModelSelect.value = '';
            }
        };

        const openEditModal = () => {
            if (!editModal) return;
            editModal.classList.remove('hidden');
            editModal.classList.add('flex');
        };

        const closeEditModal = () => {
            if (!editModal) return;
            editModal.classList.add('hidden');
            editModal.classList.remove('flex');
        };

        const resetEditForm = () => {
            editForm.action = '';
            editingInput.value = '';
            editTitle.textContent = 'Editar conexao';
            filterModelOptions('');
            editModelSelect.value = '';
            editActiveInput.checked = true;
        };

        editButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                resetEditForm();
                editForm.action = `${baseUrl}/${id}`;
                editingInput.value = id;
                editTitle.textContent = `Editar conexao: ${button.dataset.name || 'Conexao'}`;
                filterModelOptions(button.dataset.iaplataformaId || '');
                editModelSelect.value = button.dataset.modelId || '';
                editActiveInput.checked = button.dataset.isActive !== '0';
                openEditModal();
            });
        });

        editCloseButtons.forEach((button) => {
            button.addEventListener('click', closeEditModal);
        });

        editModal?.addEventListener('click', (event) => {
            if (event.target === editModal) {
                closeEditModal();
            }
        });

        if (sessionEditingId) {
            const sourceButton = editButtons.find((button) => button.dataset.id === String(sessionEditingId));
            resetEditForm();
            editForm.action = `${baseUrl}/${sessionEditingId}`;
            editingInput.value = sessionEditingId;
            editTitle.textContent = `Editar conexao: ${sourceButton?.dataset.name || 'Conexao'}`;
            filterModelOptions(sourceButton?.dataset.iaplataformaId || '');
            editModelSelect.value = oldModelId ?? '';
            editActiveInput.checked = oldIsActive === '1' || oldIsActive === 1 || oldIsActive === true;
            openEditModal();
        } else if (hasErrors && oldModelId) {
            resetEditForm();
            filterModelOptions('');
            editModelSelect.value = oldModelId ?? '';
            editActiveInput.checked = oldIsActive === '1' || oldIsActive === 1 || oldIsActive === true;
            openEditModal();
        }

        const openConnectModal = () => {
            if (!connectModal) return;
            connectModal.classList.remove('hidden');
            connectModal.classList.add('flex');
        };

        const closeConnectModal = () => {
            if (!connectModal) return;
            connectModal.classList.add('hidden');
            connectModal.classList.remove('flex');
            currentConnectId = null;
        };

        const resetConnectModal = () => {
            connectErrorText.textContent = '';
            connectPaircodeText.textContent = '';
            connectQrCode.classList.add('hidden');
            connectQrCode.removeAttribute('src');
            connectSpinner.classList.remove('hidden');
        };

        const stopConnectTimers = () => {
            if (connectStatusTimer) {
                clearInterval(connectStatusTimer);
                connectStatusTimer = null;
            }
            if (connectRefreshTimer) {
                clearInterval(connectRefreshTimer);
                connectRefreshTimer = null;
            }
            connectStatusAttempts = 0;
            connectRefreshAttempts = 0;
        };

        const showConnectError = (message) => {
            connectErrorText.textContent = message || 'Erro ao conectar.';
            connectSpinner.classList.add('hidden');
        };

        const applyStatusUpdate = (id, status) => {
            const el = statusElements.find((element) => element.dataset.conexaoId === String(id));
            const normalized = (status || '').toString().trim().toLowerCase();

            if (el) {
                el.textContent = status;
                const isConnected = normalized === 'connected';
                el.classList.toggle('bg-emerald-100', isConnected);
                el.classList.toggle('text-emerald-700', isConnected);
                el.classList.toggle('bg-slate-100', !isConnected);
                el.classList.toggle('text-slate-600', !isConnected);
            }

            const btn = connectButtons.find((element) => element.dataset.conexaoId === String(id));
            if (btn) {
                const show = normalized !== 'connected' && btn.dataset.canConnect === '1' && btn.dataset.isActive === '1';
                btn.classList.toggle('hidden', !show);
            }
        };

        const fetchStatusForConnect = async () => {
            if (!currentConnectId) return;
            connectStatusAttempts += 1;
            if (connectStatusAttempts > maxStatusAttempts) {
                stopConnectTimers();
                showConnectError('Limite de tentativas atingido. Tente novamente.');
                return;
            }

            try {
                const response = await fetch(statusUrl(currentConnectId), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const payload = await response.json();
                const status = payload?.status;
                if (status) {
                    applyStatusUpdate(currentConnectId, status);
                }
                if (status === 'connected') {
                    stopConnectTimers();
                    closeConnectModal();
                }
            } catch (_) {
                // Ignora erro pontual.
            }
        };

        const callInstanceConnect = async () => {
            if (!currentConnectId) return;
            connectRefreshAttempts += 1;
            if (connectRefreshAttempts > maxConnectAttempts) {
                stopConnectTimers();
                showConnectError('Limite de tentativas atingido. Tente novamente.');
                return;
            }

            try {
                const response = await fetch(connectUrl(currentConnectId), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const payload = await response.json();

                if (!response.ok || payload?.error) {
                    showConnectError(payload?.message || 'Erro ao conectar.');
                    return;
                }

                if (!payload.qrcode && !payload.paircode) {
                    showConnectError(payload?.message || 'Resposta sem QR code ou codigo.');
                    return;
                }

                connectSpinner.classList.add('hidden');
                connectErrorText.textContent = '';

                if (payload.qrcode) {
                    connectQrCode.src = payload.qrcode;
                    connectQrCode.classList.remove('hidden');
                }
                if (payload.paircode) {
                    connectPaircodeText.textContent = `Seu cliente tambem pode conectar com o codigo ${payload.paircode}.`;
                }
            } catch (_) {
                showConnectError('Erro ao conectar. Tente novamente.');
            }
        };

        connectButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.dataset.canConnect !== '1' || button.dataset.isActive !== '1') {
                    return;
                }

                currentConnectId = button.dataset.conexaoId;
                stopConnectTimers();
                resetConnectModal();
                openConnectModal();
                callInstanceConnect();
                connectStatusTimer = setInterval(fetchStatusForConnect, 15000);
                connectRefreshTimer = setInterval(callInstanceConnect, 20000);
            });
        });

        connectCloseBtn?.addEventListener('click', () => {
            stopConnectTimers();
            closeConnectModal();
        });

        connectModal?.addEventListener('click', (event) => {
            if (event.target === connectModal) {
                stopConnectTimers();
                closeConnectModal();
            }
        });

        const fetchStatus = async (element) => {
            const id = element.dataset.conexaoId;
            if (!id) return;

            element.textContent = 'Atualizando...';
            try {
                const response = await fetch(statusUrl(id), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const payload = await response.json();
                const button = connectButtons.find((candidate) => candidate.dataset.conexaoId === String(id));

                if (button && payload?.is_active !== undefined) {
                    button.dataset.isActive = payload.is_active ? '1' : '0';
                }

                if (payload?.status) {
                    element.textContent = payload.status;
                    applyStatusUpdate(id, payload.status);
                } else {
                    element.textContent = payload?.message ?? 'erro';
                }
            } catch (_) {
                element.textContent = 'erro';
            }
        };

        statusElements.forEach((element) => {
            applyStatusUpdate(element.dataset.conexaoId, element.textContent);
            fetchStatus(element);
        });
    })();
</script>
@endpush
