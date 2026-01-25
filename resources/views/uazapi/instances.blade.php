<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Instancias Uazapi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Minhas instancias</h3>
                            <p class="text-sm text-gray-500">Gerencie conexoes, status e assistentes vinculados.</p>
                        </div>
                        <button type="button" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" id="openCreateInstanceModal">Criar nova instancia</button>
                    </div>

                    @if($instances->isEmpty())
                        <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-6 py-10 text-center text-sm text-gray-500">
                            Nenhuma instancia cadastrada ainda.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nome</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Proxy</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Token</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Assistente</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Criado em</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Acoes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($instances as $instance)
                                        <tr class="hover:bg-gray-50/80">
                                            <td class="px-4 py-4 text-xs font-mono text-gray-500">{{ $instance->id }}</td>
                                            <td class="px-4 py-4 font-medium text-gray-800">{{ $instance->name }}</td>
                                            <td class="px-4 py-4">
                                                <span
                                                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600"
                                                    data-status-instance="{{ $instance->id }}"
                                                    data-status-url="{{ route('uazapi.instances.status', ['instance' => $instance->id]) }}"
                                                >Aguardando...</span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span
                                                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600"
                                                    data-proxy-instance="{{ $instance->id }}"
                                                >Aguardando...</span>
                                            </td>
                                            <td class="px-4 py-4 text-xs font-mono text-gray-500">{{ $instance->token }}</td>
                                            <td class="px-4 py-4">
                                                @if($instance->assistant?->name)
                                                    <span class="font-medium text-gray-800">{{ $instance->assistant->name }}</span>
                                                @else
                                                    <span class="text-gray-400">Sem assistente</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-sm text-gray-600">{{ $instance->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="px-4 py-4">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <button
                                                        type="button"
                                                        class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-600"
                                                        data-open-edit-modal
                                                        data-instance-id="{{ $instance->id }}"
                                                        data-instance-name="{{ $instance->name }}"
                                                    >Editar</button>
                                                    <button
                                                        type="button"
                                                        class="rounded-lg bg-blue-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-600"
                                                        data-open-connect-modal
                                                        data-instance-id="{{ $instance->id }}"
                                                        data-instance-name="{{ $instance->name }}"
                                                    >Conectar</button>
                                                    <form method="POST" action="{{ route('uazapi.instances.destroy', ['instance' => $instance->id]) }}">
                                                        @csrf
                                                        <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-600">Excluir</button>
                                                    </form>
                                                    <button
                                                        type="button"
                                                        class="rounded-lg bg-slate-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-700"
                                                        data-open-assistant-modal
                                                        data-instance-id="{{ $instance->id }}"
                                                        data-instance-assistant="{{ $instance->assistant?->id }}"
                                                    >Assistente</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('uazapi.elements.create-instance-modal')
    @include('uazapi.elements.edit-instance-modal')
    @include('uazapi.elements.connect-instance-modal')
    @include('uazapi.elements.assistant-instance-modal')

    <script>
        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const toggleModal = (modalElement, show) => {
                modalElement.setAttribute('aria-hidden', show ? 'false' : 'true');
                modalElement.classList.toggle('hidden', !show);
                modalElement.classList.toggle('flex', show);
            };

            let isModalActive = false;
            const statusCache = new Map();
            const statusTTL = 10000;
            let statusQueueRunning = false;
            let statusQueue = Array.from(document.querySelectorAll('[data-status-instance]'));

            const renderStatus = (element, label) => {
                element.textContent = label;
                element.classList.remove(
                    'bg-gray-100',
                    'text-gray-600',
                    'bg-emerald-100',
                    'text-emerald-700',
                    'bg-rose-100',
                    'text-rose-700'
                );
                if (label === 'Conectado') {
                    element.classList.add('bg-emerald-100', 'text-emerald-700');
                } else if (label === 'Desconectado') {
                    element.classList.add('bg-rose-100', 'text-rose-700');
                } else {
                    element.classList.add('bg-gray-100', 'text-gray-600');
                }
            };

            const updateProxyLabel = (instanceId, label) => {
                const element = document.querySelector(`[data-proxy-instance="${instanceId}"]`);
                if (!element) {
                    return;
                }

                element.textContent = label;
                element.classList.remove(
                    'bg-gray-100',
                    'text-gray-600',
                    'bg-emerald-100',
                    'text-emerald-700',
                    'bg-rose-100',
                    'text-rose-700'
                );
                if (label === 'Aguardando...') {
                    element.classList.add('bg-gray-100', 'text-gray-600');
                } else if (label === 'Nao identificado') {
                    element.classList.add('bg-rose-100', 'text-rose-700');
                } else {
                    element.classList.add('bg-emerald-100', 'text-emerald-700');
                }
            };

            const fetchStatus = async (element) => {
                const instanceId = element.dataset.statusInstance;
                const statusUrl = element.dataset.statusUrl;
                const cached = statusCache.get(instanceId);
                const now = Date.now();

                if (cached && (now - cached.ts) < statusTTL) {
                    renderStatus(element, cached.label);
                    updateProxyLabel(instanceId, cached.proxy || 'Aguardando...');
                    return;
                }

                renderStatus(element, 'Aguardando...');
                updateProxyLabel(instanceId, 'Aguardando...');

                try {
                    const response = await fetch(statusUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await response.json();
                    const label = (response.ok && !data.error && data.status === 'connected')
                        ? 'Conectado'
                        : 'Desconectado';
                    const proxyLabel = (response.ok && !data.error && data.proxy_url)
                        ? data.proxy_url
                        : 'Nao identificado';

                    statusCache.set(instanceId, { label, proxy: proxyLabel, ts: now });
                    renderStatus(element, label);
                    updateProxyLabel(instanceId, proxyLabel);
                } catch (error) {
                    const label = 'Desconectado';
                    statusCache.set(instanceId, { label, proxy: 'Nao identificado', ts: now });
                    renderStatus(element, label);
                    updateProxyLabel(instanceId, 'Nao identificado');
                }
            };

            const runStatusQueue = async () => {
                if (statusQueueRunning) {
                    return;
                }
                statusQueueRunning = true;

                while (statusQueue.length > 0) {
                    if (isModalActive) {
                        statusQueueRunning = false;
                        return;
                    }
                    const element = statusQueue.shift();
                    await fetchStatus(element);
                }

                statusQueueRunning = false;
            };

            const createModal = document.getElementById('createInstanceModal');
            const createToggleButtons = {
                open: document.getElementById('openCreateInstanceModal'),
                close: createModal.querySelectorAll('[data-modal-close]')
            };

            createToggleButtons.open.addEventListener('click', () => toggleModal(createModal, true));
            createToggleButtons.close.forEach(button => button.addEventListener('click', () => toggleModal(createModal, false)));
            createModal.addEventListener('click', (event) => {
                if (event.target === createModal) {
                    toggleModal(createModal, false);
                }
            });

            const editModal = document.getElementById('editInstanceModal');
            const editForm = document.getElementById('editInstanceForm');
            const editNameInput = document.getElementById('edit-instance-name');
            const editToggleButtons = editModal.querySelectorAll('[data-modal-close]');

            editToggleButtons.forEach(button => button.addEventListener('click', () => toggleModal(editModal, false)));
            editModal.addEventListener('click', (event) => {
                if (event.target === editModal) {
                    toggleModal(editModal, false);
                }
            });

            const editButtons = document.querySelectorAll('[data-open-edit-modal]');
            editButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const instanceId = button.dataset.instanceId;
                    const instanceName = button.dataset.instanceName;
                    const template = editForm.dataset.actionTemplate;
                    editForm.action = template.replace('__INSTANCE_ID__', instanceId);
                    editNameInput.value = instanceName;
                    toggleModal(editModal, true);
                });
            });

            const shouldOpenCreate = @json($errors->default->any());
            if (shouldOpenCreate) {
                toggleModal(createModal, true);
            }

            const editInstanceId = @json(session('edit_instance_id'));
            const editInstanceName = @json(session('edit_instance_name'));
            const editOldName = @json(old('name'));

            if (editInstanceId) {
                const targetButton = document.querySelector(`[data-instance-id="${editInstanceId}"]`);
                if (targetButton) {
                    targetButton.click();
                    if (editOldName) {
                        editNameInput.value = editOldName;
                    }
                } else if (editInstanceName) {
                    const template = editForm.dataset.actionTemplate;
                    editForm.action = template.replace('__INSTANCE_ID__', editInstanceId);
                    editNameInput.value = editOldName || editInstanceName;
                    toggleModal(editModal, true);
                }
            }

            const connectModal = document.getElementById('connectInstanceModal');
            const connectForm = document.getElementById('connectInstanceForm');
            const connectTitle = document.getElementById('connect-instance-title');
            const connectModeSelect = document.getElementById('connect-mode');
            const connectPhoneField = document.getElementById('connect-phone-field');
            const connectPhoneInput = document.getElementById('connect-phone');
            const connectPhoneError = document.getElementById('connect-phone-error');
            const connectResult = document.getElementById('connect-result');
            const connectQrCode = document.getElementById('connect-qr-code');
            const connectPaircodeText = document.getElementById('connect-paircode-text');
            const connectStatusMessage = document.getElementById('connect-status-message');
            const connectCloseButtons = connectModal.querySelectorAll('[data-modal-close]');
            const connectButtons = document.querySelectorAll('[data-open-connect-modal]');
            let pollingTimer = null;
            let pollingAttempts = 0;
            const maxPollingAttempts = 24;

            const stopPolling = () => {
                if (pollingTimer) {
                    clearInterval(pollingTimer);
                    pollingTimer = null;
                }
                pollingAttempts = 0;
            };

            const setConnectMessage = (message, isError = false) => {
                connectStatusMessage.textContent = message || '';
                connectStatusMessage.classList.toggle('text-rose-600', isError);
                connectStatusMessage.classList.toggle('text-gray-600', !isError);
            };

            const resetConnectResult = () => {
                connectResult.classList.add('hidden');
                connectQrCode.removeAttribute('src');
                connectQrCode.classList.add('hidden');
                connectPaircodeText.textContent = '';
                setConnectMessage('');
                connectPhoneError.textContent = '';
            };

            const updatePhoneVisibility = () => {
                const requiresPhone = connectModeSelect.value === 'paircode';
                connectPhoneField.classList.toggle('hidden', !requiresPhone);
                connectPhoneInput.required = requiresPhone;
                if (!requiresPhone) {
                    connectPhoneInput.value = '';
                    connectPhoneError.textContent = '';
                }
            };

            const validatePhone = () => {
                connectPhoneError.textContent = '';
                if (connectModeSelect.value !== 'paircode') {
                    return '';
                }
                const raw = connectPhoneInput.value.trim();
                const normalized = raw.replace(/\s+/g, '');
                if (!/^\d+$/.test(normalized)) {
                    connectPhoneError.textContent = 'Informe apenas numeros.';
                    return null;
                }
                if (normalized.length < 11) {
                    connectPhoneError.textContent = 'Informe no minimo 11 numeros.';
                    return null;
                }
                connectPhoneInput.value = normalized;
                return normalized;
            };

            const startPolling = (instanceId) => {
                stopPolling();
                pollingTimer = setInterval(async () => {
                    pollingAttempts += 1;
                    if (pollingAttempts > maxPollingAttempts) {
                        stopPolling();
                        setConnectMessage('Conexao ainda pendente. Atualize a pagina e tente novamente.', true);
                        return;
                    }

                    const statusTemplate = connectForm.dataset.statusTemplate;
                    const statusUrl = statusTemplate.replace('__INSTANCE_ID__', instanceId);

                    try {
                        const response = await fetch(statusUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();
                        if (data.status === 'connected') {
                            stopPolling();
                            window.location.reload();
                        }
                    } catch (error) {
                        stopPolling();
                        setConnectMessage('Erro ao consultar status. Atualize a pagina e tente novamente.', true);
                    }
                }, 5000);
            };

            connectModeSelect.addEventListener('change', updatePhoneVisibility);

            connectButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const instanceId = button.dataset.instanceId;
                    const instanceName = button.dataset.instanceName;
                    const actionTemplate = connectForm.dataset.actionTemplate;
                    connectForm.action = actionTemplate.replace('__INSTANCE_ID__', instanceId);
                    connectForm.dataset.currentInstanceId = instanceId;
                    connectTitle.textContent = `Conectar instancia ${instanceName}`;
                    connectModeSelect.value = 'qrcode';
                    updatePhoneVisibility();
                    resetConnectResult();
                    setConnectMessage('Selecione o tipo de conexao e clique em conectar.');
                    isModalActive = true;
                    toggleModal(connectModal, true);
                });
            });

            connectCloseButtons.forEach(button => button.addEventListener('click', () => {
                stopPolling();
                isModalActive = false;
                runStatusQueue();
                toggleModal(connectModal, false);
            }));

            connectModal.addEventListener('click', (event) => {
                if (event.target === connectModal) {
                    stopPolling();
                    isModalActive = false;
                    runStatusQueue();
                    toggleModal(connectModal, false);
                }
            });

            connectForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                resetConnectResult();
                const phone = validatePhone();
                if (connectModeSelect.value === 'paircode' && phone === null) {
                    return;
                }

                const payload = {
                    connect_mode: connectModeSelect.value,
                };
                if (connectModeSelect.value === 'paircode' && phone) {
                    payload.phone = phone;
                }

                try {
                    setConnectMessage('Conectando...');
                    const response = await fetch(connectForm.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken || ''
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();
                    if (!response.ok || data.error) {
                        setConnectMessage(data.message || 'Erro ao conectar.', true);
                        return;
                    }

                    connectResult.classList.remove('hidden');
                    if (data.qrcode) {
                        connectQrCode.src = data.qrcode;
                        connectQrCode.classList.remove('hidden');
                    }

                    if (data.paircode) {
                        connectPaircodeText.textContent = `Ou digite o codigo de pareamento ${data.paircode}`;
                    }

                    setConnectMessage(data.message || 'Conectando...');
                    const instanceId = connectForm.dataset.currentInstanceId;
                    if (instanceId) {
                        startPolling(instanceId);
                    }
                } catch (error) {
                    setConnectMessage('Erro ao conectar. Atualize a pagina e tente novamente.', true);
                }
            });

            const assistantModal = document.getElementById('assistantInstanceModal');
            if (assistantModal) {
                const assistantForm = document.getElementById('assistantInstanceForm');
                const assistantButtons = document.querySelectorAll('[data-open-assistant-modal]');
                const assistantCloseButtons = assistantModal.querySelectorAll('[data-modal-close]');
                const assistantSelect = assistantForm.querySelector('select[name="assistant_id"]');

                const toggleAssistantModal = (show) => {
                    assistantModal.setAttribute('aria-hidden', show ? 'false' : 'true');
                    assistantModal.classList.toggle('hidden', !show);
                    assistantModal.classList.toggle('flex', show);
                    isModalActive = show;
                };

                assistantButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        const instanceId = button.dataset.instanceId;
                        const currentAssistantId = button.dataset.instanceAssistant;
                        const template = assistantForm.dataset.routeTemplate;
                        assistantForm.action = template.replace('__INSTANCE_ID__', instanceId);

                        if (assistantSelect) {
                            assistantSelect.value = currentAssistantId || '';
                        }

                        toggleAssistantModal(true);
                    });
                });

                assistantCloseButtons.forEach(button => {
                    button.addEventListener('click', () => toggleAssistantModal(false));
                });

                assistantModal.addEventListener('click', (event) => {
                    if (event.target === assistantModal) {
                        toggleAssistantModal(false);
                    }
                });
            }

            runStatusQueue();
        })();
    </script>
</x-app-layout>
