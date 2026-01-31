@extends('layouts.agencia')

@section('content')
    @php
        $conexoesUser = auth()->user();
        $conexoesLimit = $conexoesUser?->plan?->max_conexoes ?? 0;
        $conexoesUsed = $conexoesUser?->conexoesCount() ?? 0;
        $conexoesCanCreate = $conexoesLimit > 0 && $conexoesUsed < $conexoesLimit;
    @endphp
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Conexões</h2>
            <p class="text-sm text-slate-500">Gerencie as conexões vinculadas ao seu usuário.</p>
        </div>
        <button
            id="openConexaoModal"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
            @disabled(!$conexoesCanCreate)
        >
            Nova conexão
        </button>
    </div>
    @if(!$conexoesCanCreate)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            @if($conexoesLimit <= 0)
                Selecione um plano para liberar novas conexoes.
            @else
                Limite de conexoes do plano atingido ({{ $conexoesUsed }}/{{ $conexoesLimit }}).
            @endif
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Credencial</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Status</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Phone</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Assistente</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Cliente</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Modelo</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($conexoes as $conexao)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $conexao->name ?? '-' }}</td>
                        <td class="px-5 py-4 font-medium text-slate-800">{{ optional($conexao->credential)->name ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold transition {{ $conexao->status === 'connected' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}"
                                    data-conexao-status
                                    data-conexao-id="{{ $conexao->id }}"
                                >
                                    {{ $conexao->status ?? 'pendente' }}
                                </span>
                                <button
                                    type="button"
                                    class="hidden rounded-lg border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                    data-conexao-connect
                                    data-conexao-id="{{ $conexao->id }}"
                                    data-can-connect="{{ $conexao->whatsappApi?->slug === 'uazapi' ? '1' : '0' }}"
                                >Conectar</button>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $conexao->phone ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ optional($conexao->assistant)->name ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ optional($conexao->cliente)->nome ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ optional($conexao->iamodelo)->nome ?? '-' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                    data-open-edit
                                    data-id="{{ $conexao->id }}"
                                    data-name="{{ $conexao->name }}"
                                    data-credential-id="{{ $conexao->credential_id }}"
                                    data-assistant-id="{{ $conexao->assistant_id }}"
                                    data-cliente-id="{{ $conexao->cliente_id }}"
                                    data-model-id="{{ $conexao->model }}"
                                    data-whatsapp-api-id="{{ $conexao->whatsapp_api_id }}"
                                    data-phone="{{ $conexao->phone }}"
                                >Editar</button>
                                <form method="POST" action="{{ route('agencia.conexoes.destroy', $conexao) }}" onsubmit="return confirm('Deseja excluir esta conexão?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-6 text-center text-slate-500">Nenhuma conexão cadastrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="conexaoModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="conexaoModalTitle" class="text-lg font-semibold text-slate-900">Nova conexão</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>

            <form id="conexaoForm" method="POST" action="{{ route('agencia.conexoes.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="conexaoFormMethod" value="POST">
                <input type="hidden" name="editing_id" id="conexaoEditingId" value="{{ old('editing_id') }}">

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="conexaoName">Nome</label>
                    <input
                        id="conexaoName"
                        name="name"
                        type="text"
                        required
                        value="{{ old('name') }}"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="conexaoCredential">Credencial</label>
                    <select id="conexaoCredential" name="credential_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="" disabled {{ old('credential_id') ? '' : 'selected' }}>Escolha uma credencial</option>
                        @foreach ($credentials as $credential)
                            <option value="{{ $credential->id }}" data-iaplataforma-id="{{ $credential->iaplataforma_id }}" @selected(old('credential_id') == $credential->id)>{{ $credential->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="conexaoAssistant">Assistente</label>
                    <select id="conexaoAssistant" name="assistant_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="" disabled {{ old('assistant_id') ? '' : 'selected' }}>Escolha um assistente</option>
                        @foreach ($assistants as $assistant)
                            <option value="{{ $assistant->id }}" @selected(old('assistant_id') == $assistant->id)>{{ $assistant->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="conexaoCliente">Cliente</label>
                    <select id="conexaoCliente" name="cliente_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="" disabled {{ old('cliente_id') ? '' : 'selected' }}>Escolha um cliente</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="conexaoModel">Modelo</label>
                    <select id="conexaoModel" name="model" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="" disabled {{ old('model') ? '' : 'selected' }}>Escolha um modelo</option>
                        @foreach ($iamodelos as $modelo)
                            <option value="{{ $modelo->id }}" data-iaplataforma-id="{{ $modelo->iaplataforma_id }}" @selected(old('model') == $modelo->id)>{{ $modelo->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="conexaoPhone">Telefone</label>
                    <input
                        id="conexaoPhone"
                        name="phone"
                        type="text"
                        inputmode="numeric"
                        minlength="11"
                        required
                        pattern="[0-9]{11,}"
                        title="Informe apenas números (mínimo 11 dígitos)"
                        value="{{ old('phone') }}"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="conexaoWhatsappApi">Integração WhatsApp</label>
                    <select id="conexaoWhatsappApi" name="whatsapp_api_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="" disabled {{ old('whatsapp_api_id') ? '' : 'selected' }}>Escolha uma API</option>
                        @foreach ($whatsappApis as $api)
                            <option value="{{ $api->id }}" @selected(old('whatsapp_api_id') == $api->id)>{{ $api->nome }}</option>
                        @endforeach
                    </select>
                    <p id="whatsappApiEditHint" class="mt-1 text-xs text-slate-400 hidden">O provedor WhatsApp não pode ser alterado após a criação.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-close-modal>Cancelar</button>
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

    <script>
        (function () {
            const modal = document.getElementById('conexaoModal');
            const openBtn = document.getElementById('openConexaoModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('conexaoForm');
            const methodInput = document.getElementById('conexaoFormMethod');
            const editingInput = document.getElementById('conexaoEditingId');
            const title = document.getElementById('conexaoModalTitle');
            const nameInput = document.getElementById('conexaoName');
            const credentialSelect = document.getElementById('conexaoCredential');
            const assistantSelect = document.getElementById('conexaoAssistant');
            const clienteSelect = document.getElementById('conexaoCliente');
            const modelSelect = document.getElementById('conexaoModel');
            const modelOptions = Array.from(modelSelect.querySelectorAll('option'));
            const placeholderOption = modelOptions.find(option => option.value === '');
            const whatsappApiSelect = document.getElementById('conexaoWhatsappApi');
            const whatsappApiHint = document.getElementById('whatsappApiEditHint');
            const phoneInput = document.getElementById('conexaoPhone');
            const storeRoute = "{{ route('agencia.conexoes.store') }}";
            const baseUrl = "{{ url('/agencia/conexoes') }}";
            const hasErrors = @json($errors->any());
            const sessionEditingId = @json(old('editing_id'));
            const oldName = @json(old('name'));
            const oldCredentialId = @json(old('credential_id'));
            const oldAssistantId = @json(old('assistant_id'));
            const oldClienteId = @json(old('cliente_id'));
            const oldModelId = @json(old('model'));
            const oldWhatsappApiId = @json(old('whatsapp_api_id'));
            const oldPhone = @json(old('phone'));
            const statusElements = Array.from(document.querySelectorAll('[data-conexao-status]'));
            const connectButtons = Array.from(document.querySelectorAll('[data-conexao-connect]'));
            const statusUrl = (id) => `${baseUrl}/${id}/status`;
            const connectUrl = (id) => `${baseUrl}/${id}/connect`;

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
                modelOptions.forEach(option => {
                    if (!option.value) {
                        return;
                    }
                    const matches = iaplataformaId && option.dataset.iaplataformaId === String(iaplataformaId);
                    option.hidden = !matches;
                    option.disabled = !matches;
                    if (matches) {
                        hasVisible = true;
                    }
                });

                if (placeholderOption) {
                    placeholderOption.disabled = false;
                    if (!iaplataformaId) {
                        placeholderOption.textContent = 'Escolha uma credencial primeiro';
                    } else if (hasVisible) {
                        placeholderOption.textContent = 'Escolha um modelo';
                    } else {
                        placeholderOption.textContent = 'Nenhum modelo disponível para esta plataforma';
                    }
                }

                if (!hasVisible) {
                    modelSelect.value = '';
                }
            };

            const handleCredentialChange = () => {
                const selectedOption = credentialSelect.selectedOptions[0];
                const platformId = selectedOption?.dataset.iaplataformaId ?? '';
                filterModelOptions(platformId);
            };

            const getStatusElement = (id) => statusElements.find(el => el.dataset.conexaoId === String(id));
            const getConnectButton = (id) => connectButtons.find(el => el.dataset.conexaoId === String(id));
            const updateConnectVisibility = (id, status) => {
                const button = getConnectButton(id);
                if (!button) return;
                if (button.dataset.canConnect !== '1') {
                    button.classList.add('hidden');
                    return;
                }
                const normalized = (status || '').toString().trim().toLowerCase();
                const shouldShow = normalized !== 'connected';
                button.classList.toggle('hidden', !shouldShow);
            };

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };
            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                form.action = storeRoute;
                methodInput.value = 'POST';
                title.textContent = 'Nova conexão';
                editingInput.value = '';
                nameInput.value = '';
                credentialSelect.value = '';
                assistantSelect.value = '';
                clienteSelect.value = '';
                modelSelect.value = '';
                filterModelOptions('');
                if (whatsappApiSelect) {
                    whatsappApiSelect.disabled = false;
                    whatsappApiSelect.value = '';
                }
                if (whatsappApiHint) {
                    whatsappApiHint.classList.add('hidden');
                }
                if (phoneInput) {
                    phoneInput.value = '';
                }
            };

            credentialSelect.addEventListener('change', handleCredentialChange);

            openBtn.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.querySelectorAll('[data-open-edit]').forEach(button => {
                    button.addEventListener('click', () => {
                        const id = button.dataset.id;
                        resetForm();
                        form.action = `${baseUrl}/${id}`;
                        methodInput.value = 'PATCH';
                        editingInput.value = id;
                        title.textContent = 'Editar conexão';
                        nameInput.value = button.dataset.name || '';
                        credentialSelect.value = button.dataset.credentialId || '';
                        handleCredentialChange();
                        assistantSelect.value = button.dataset.assistantId || '';
                        clienteSelect.value = button.dataset.clienteId || '';
                        modelSelect.value = button.dataset.modelId || '';
                        if (whatsappApiSelect) {
                            whatsappApiSelect.value = button.dataset.whatsappApiId || '';
                            whatsappApiSelect.disabled = true;
                        }
                        if (phoneInput) {
                            phoneInput.value = button.dataset.phone || '';
                        }
                        if (whatsappApiHint) {
                            whatsappApiHint.classList.remove('hidden');
                        }
                        openModal();
                    });
                });

            if (sessionEditingId) {
                resetForm();
                form.action = `${baseUrl}/${sessionEditingId}`;
                methodInput.value = 'PATCH';
                editingInput.value = sessionEditingId;
                title.textContent = 'Editar conexão';
                nameInput.value = oldName ?? '';
                credentialSelect.value = oldCredentialId ?? '';
                handleCredentialChange();
                assistantSelect.value = oldAssistantId ?? '';
                clienteSelect.value = oldClienteId ?? '';
                modelSelect.value = oldModelId ?? '';
                if (whatsappApiSelect) {
                    whatsappApiSelect.value = oldWhatsappApiId ?? '';
                    whatsappApiSelect.disabled = true;
                }
                if (phoneInput) {
                    phoneInput.value = oldPhone ?? '';
                }
                if (whatsappApiHint) {
                    whatsappApiHint.classList.remove('hidden');
                }
                openModal();
            } else if (hasErrors) {
                resetForm();
                nameInput.value = oldName ?? '';
                credentialSelect.value = oldCredentialId ?? '';
                handleCredentialChange();
                assistantSelect.value = oldAssistantId ?? '';
                clienteSelect.value = oldClienteId ?? '';
                modelSelect.value = oldModelId ?? '';
                if (whatsappApiSelect) {
                    whatsappApiSelect.value = oldWhatsappApiId ?? '';
                    whatsappApiSelect.disabled = false;
                }
                if (phoneInput) {
                    phoneInput.value = oldPhone ?? '';
                }
                if (whatsappApiHint) {
                    whatsappApiHint.classList.add('hidden');
                }
                openModal();
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
                const statusEl = getStatusElement(id);
                const normalized = (status || '').toString().trim().toLowerCase();
                if (statusEl) {
                    statusEl.textContent = status;
                    const isConnected = normalized === 'connected';
                    statusEl.classList.toggle('bg-emerald-100', isConnected);
                    statusEl.classList.toggle('text-emerald-700', isConnected);
                    statusEl.classList.toggle('bg-slate-100', !isConnected);
                    statusEl.classList.toggle('text-slate-600', !isConnected);
                }
                updateConnectVisibility(id, status);
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
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
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
                } catch (error) {
                    // ignora erro pontual
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
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                    });
                    const payload = await response.json();
                    if (!response.ok || payload?.error) {
                        showConnectError(payload?.message || 'Erro ao conectar.');
                        return;
                    }

                    if (!payload.qrcode && !payload.paircode) {
                        showConnectError(payload?.message || 'Resposta sem QR code ou código.');
                        return;
                    }

                    connectSpinner.classList.add('hidden');
                    connectErrorText.textContent = '';

                    if (payload.qrcode) {
                        connectQrCode.src = payload.qrcode;
                        connectQrCode.classList.remove('hidden');
                    }
                    if (payload.paircode) {
                        connectPaircodeText.textContent = `Seu cliente também pode conectar com o código ${payload.paircode}.`;
                    }
                } catch (error) {
                    showConnectError('Erro ao conectar. Tente novamente.');
                }
            };

            connectButtons.forEach(button => {
                button.addEventListener('click', () => {
                    if (button.dataset.canConnect !== '1') {
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
                    const response = await fetch(statusUrl(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const payload = await response.json();
                    if (payload?.status) {
                        element.textContent = payload.status;
                        updateConnectVisibility(id, payload.status);
                    } else {
                        element.textContent = payload?.message ?? 'erro';
                    }
                } catch (error) {
                    element.textContent = 'erro';
                }
            };

            statusElements.forEach(element => {
                updateConnectVisibility(element.dataset.conexaoId, element.textContent);
                fetchStatus(element);
            });
        })();
    </script>
@endsection
