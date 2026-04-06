@extends('layouts.agencia')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Kommo</h2>
            <p class="text-sm text-slate-500">Cadastre integrações Kommo por cliente, valide as credenciais e defina o destino padrão no Kommo.</p>
        </div>
        <button type="button" id="openKommoModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            Nova integração
        </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Nome técnico</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Cliente</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Subdomain</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Conta Kommo</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Destino padrão</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Última validação</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($accounts as $account)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $account->name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $account->cliente?->nome ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $account->subdomain }}</td>
                        <td class="px-5 py-4 text-slate-600">
                            <div class="font-medium text-slate-800">{{ $account->kommo_account_name ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-400">{{ $account->kommo_account_id ? 'ID ' . $account->kommo_account_id : 'Sem ID sincronizado' }}</div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            <div class="font-medium text-slate-800">{{ $account->pipeline_name ?: '-' }}</div>
                            <div class="mt-1 text-xs text-slate-400">{{ $account->status_name ?: 'Sem estágio configurado' }}</div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            <div>{{ $account->last_verified_at?->format('d/m/Y H:i') ?? 'Nunca validada' }}</div>
                            @if($account->last_verification_error)
                                <div class="mt-1 text-xs text-rose-600">{{ $account->last_verification_error }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                    data-open-edit
                                    data-id="{{ $account->id }}"
                                    data-name="{{ $account->name }}"
                                    data-cliente-id="{{ $account->cliente_id }}"
                                    data-subdomain="{{ $account->subdomain }}"
                                    data-update-url="{{ route('agencia.kommo.update', $account) }}"
                                    data-account-name="{{ $account->kommo_account_name }}"
                                    data-account-id="{{ $account->kommo_account_id }}"
                                    data-pipeline-id="{{ $account->pipeline_id }}"
                                    data-pipeline-name="{{ $account->pipeline_name }}"
                                    data-status-id="{{ $account->status_id }}"
                                    data-status-name="{{ $account->status_name }}"
                                >
                                    Editar
                                </button>
                                <form method="POST" action="{{ route('agencia.kommo.destroy', $account) }}" onsubmit="return confirm('Deseja excluir esta integração Kommo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                            Nenhuma integração Kommo cadastrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="kommoModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-full max-w-3xl rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <div>
                    <h3 id="kommoModalTitle" class="text-lg font-semibold text-slate-900">Nova integração Kommo</h3>
                    <p id="kommoModalDescription" class="mt-1 text-sm text-slate-500">Teste a conexão, carregue os destinos do Kommo e escolha pipeline e estágio antes de salvar.</p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>

            <form id="kommoForm" method="POST" action="{{ route('agencia.kommo.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="kommoFormMethod" value="POST">
                <input type="hidden" name="form_mode" id="kommoFormMode" value="create">
                <input type="hidden" name="kommo_account_context_id" id="kommoAccountContextId" value="">
                <input type="hidden" id="kommoClienteIdHidden" value="" disabled>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="kommoClienteId" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</label>
                        <select id="kommoClienteId" name="cliente_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione um cliente</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                            @endforeach
                        </select>
                        <p id="kommoClienteHint" class="mt-1 text-xs text-slate-400">O cliente vinculado será o contexto fixo desta integração.</p>
                    </div>
                    <div>
                        <label for="kommoName" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nome técnico</label>
                        <input id="kommoName" name="name" type="text" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex.: kommo-vendas-sp">
                        <p id="kommoNameHint" class="mt-1 text-xs text-slate-400">Use um slug técnico único na agência. Esse nome será usado pelas tools no futuro.</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="kommoSubdomain" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subdomain</label>
                        <input id="kommoSubdomain" name="subdomain" type="text" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="minhaconta">
                        <p class="mt-1 text-xs text-slate-400">Aceita `minhaconta`, `minhaconta.kommo.com` ou URL completa.</p>
                    </div>
                    <div>
                        <label for="kommoToken" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Long-lived token</label>
                        <textarea id="kommoToken" name="access_token" rows="3" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Cole o token do Kommo aqui"></textarea>
                        <p id="kommoTokenHint" class="mt-1 text-xs text-slate-400">Obrigatório para validar e salvar a integração.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" id="kommoValidateButton" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                            Testar conexão
                        </button>
                        <span id="kommoValidationStatus" class="text-sm text-slate-500">Nenhum teste executado.</span>
                    </div>
                    <div id="kommoValidationDetails" class="mt-2 hidden text-sm text-slate-600"></div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="kommoPipelineId" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pipeline Kommo</label>
                            <select id="kommoPipelineId" name="pipeline_id" required disabled class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Valide a conexão para carregar</option>
                            </select>
                        </div>
                        <div>
                            <label for="kommoStatusId" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estágio Kommo</label>
                            <select id="kommoStatusId" name="status_id" required disabled class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione uma pipeline primeiro</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div id="kommoDestinationStatus" class="text-sm text-slate-500">O destino padrão será habilitado após validar a conexão.</div>
                        <div id="kommoDestinationDetails" class="mt-2 hidden text-sm text-slate-600"></div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-close-modal>
                        Cancelar
                    </button>
                    <button type="submit" id="kommoSubmitButton" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300" disabled>
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('kommoModal');
            const openBtn = document.getElementById('openKommoModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('kommoForm');
            const methodInput = document.getElementById('kommoFormMethod');
            const formModeInput = document.getElementById('kommoFormMode');
            const contextIdInput = document.getElementById('kommoAccountContextId');
            const clienteId = document.getElementById('kommoClienteId');
            const clienteIdHidden = document.getElementById('kommoClienteIdHidden');
            const clienteHint = document.getElementById('kommoClienteHint');
            const title = document.getElementById('kommoModalTitle');
            const description = document.getElementById('kommoModalDescription');
            const nameInput = document.getElementById('kommoName');
            const nameHint = document.getElementById('kommoNameHint');
            const subdomainInput = document.getElementById('kommoSubdomain');
            const tokenInput = document.getElementById('kommoToken');
            const tokenHint = document.getElementById('kommoTokenHint');
            const validateButton = document.getElementById('kommoValidateButton');
            const submitButton = document.getElementById('kommoSubmitButton');
            const validationStatus = document.getElementById('kommoValidationStatus');
            const validationDetails = document.getElementById('kommoValidationDetails');
            const pipelineSelect = document.getElementById('kommoPipelineId');
            const statusSelect = document.getElementById('kommoStatusId');
            const destinationStatus = document.getElementById('kommoDestinationStatus');
            const destinationDetails = document.getElementById('kommoDestinationDetails');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const validateUrl = @js(route('agencia.kommo.validate'));
            const pipelinesUrl = @js(route('agencia.kommo.pipelines'));
            const statusesUrl = @js(route('agencia.kommo.statuses'));
            const storeUrl = @js(route('agencia.kommo.store'));

            const oldInput = @js([
                'form_mode' => old('form_mode'),
                'kommo_account_context_id' => old('kommo_account_context_id'),
                'cliente_id' => old('cliente_id'),
                'name' => old('name'),
                'subdomain' => old('subdomain'),
                'access_token' => old('access_token'),
                'pipeline_id' => old('pipeline_id'),
                'status_id' => old('status_id'),
            ]);

            const state = {
                mode: 'create',
                initialSubdomain: '',
                initialPipelineId: '',
                initialStatusId: '',
                hasStoredToken: false,
                validatedKey: null,
                validationOk: false,
                destinationValid: false,
                pipelineRequestId: 0,
                statusRequestId: 0,
            };

            const normalizeSubdomain = (value) => {
                let normalized = String(value || '').trim().toLowerCase();
                if (normalized === '') {
                    return '';
                }

                if (normalized.startsWith('http://') || normalized.startsWith('https://')) {
                    try {
                        normalized = new URL(normalized).host;
                    } catch (error) {
                        normalized = normalized.replace(/^https?:\/\//, '');
                    }
                }

                normalized = normalized.replace(/[/?#].*$/, '');
                normalized = normalized.replace(/:\d+$/, '');

                if (normalized.endsWith('.kommo.com')) {
                    normalized = normalized.slice(0, -'.kommo.com'.length);
                }

                return normalized.replace(/^\.+|\.+$/g, '');
            };

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const credentialsChanged = () => {
                if (state.mode === 'create') {
                    return true;
                }

                const currentSubdomain = normalizeSubdomain(subdomainInput.value);
                const tokenChanged = tokenInput.value.trim() !== '';

                return currentSubdomain !== state.initialSubdomain || tokenChanged;
            };

            const validationKey = () => {
                const subdomain = normalizeSubdomain(subdomainInput.value);
                const token = tokenInput.value.trim() !== ''
                    ? tokenInput.value.trim()
                    : (state.hasStoredToken ? '__stored_token__' : '');

                return `${subdomain}|${token}`;
            };

            const currentLookupPayload = () => ({
                subdomain: normalizeSubdomain(subdomainInput.value),
                access_token: tokenInput.value.trim(),
                kommo_account_id: contextIdInput.value || null,
            });

            const setClienteLocked = (locked) => {
                clienteId.disabled = locked;
                if (locked) {
                    clienteId.removeAttribute('name');
                    clienteIdHidden.name = 'cliente_id';
                    clienteIdHidden.disabled = false;
                    clienteHint.textContent = 'O cliente da integração não pode ser alterado depois da criação.';
                } else {
                    clienteId.name = 'cliente_id';
                    clienteIdHidden.removeAttribute('name');
                    clienteIdHidden.disabled = true;
                    clienteHint.textContent = 'O cliente vinculado será o contexto fixo desta integração.';
                }
            };

            const setNameLocked = (locked) => {
                nameInput.readOnly = locked;
                nameInput.classList.toggle('bg-slate-100', locked);
                nameHint.textContent = locked
                    ? 'O nome técnico é um identificador estável e não pode ser alterado depois da criação.'
                    : 'Use um slug técnico único na agência. Esse nome será usado pelas tools no futuro.';
            };

            const setValidationMessage = (message, tone = 'neutral') => {
                validationStatus.textContent = message;
                validationStatus.className = 'text-sm';

                if (tone === 'success') {
                    validationStatus.classList.add('font-semibold', 'text-emerald-700');
                    return;
                }

                if (tone === 'error') {
                    validationStatus.classList.add('font-semibold', 'text-rose-700');
                    return;
                }

                validationStatus.classList.add('text-slate-500');
            };

            const setValidationDetails = (html = '') => {
                validationDetails.innerHTML = html;
                validationDetails.classList.toggle('hidden', html.trim() === '');
            };

            const setDestinationMessage = (message, tone = 'neutral') => {
                destinationStatus.textContent = message;
                destinationStatus.className = 'text-sm';

                if (tone === 'success') {
                    destinationStatus.classList.add('font-semibold', 'text-emerald-700');
                    return;
                }

                if (tone === 'error') {
                    destinationStatus.classList.add('font-semibold', 'text-rose-700');
                    return;
                }

                destinationStatus.classList.add('text-slate-500');
            };

            const setDestinationDetails = (html = '') => {
                destinationDetails.innerHTML = html;
                destinationDetails.classList.toggle('hidden', html.trim() === '');
            };

            const populateSelect = (select, items, placeholder) => {
                select.innerHTML = '';
                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = placeholder;
                select.appendChild(placeholderOption);

                items.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name || item.id;
                    select.appendChild(option);
                });
            };

            const clearDestinationState = (message = 'O destino padrão será habilitado após validar a conexão.', detailsHtml = '') => {
                populateSelect(pipelineSelect, [], 'Valide a conexão para carregar');
                populateSelect(statusSelect, [], 'Selecione uma pipeline primeiro');
                pipelineSelect.disabled = true;
                statusSelect.disabled = true;
                state.destinationValid = false;
                setDestinationMessage(message, 'neutral');
                setDestinationDetails(detailsHtml);
                updateSubmitAvailability();
            };

            const updateSubmitAvailability = () => {
                const destinationReady = state.destinationValid && pipelineSelect.value !== '' && statusSelect.value !== '';

                if (state.mode === 'edit' && !credentialsChanged()) {
                    submitButton.disabled = !destinationReady;
                    return;
                }

                submitButton.disabled = !(state.validationOk && state.validatedKey === validationKey() && destinationReady);
            };

            const resetValidationState = (message = 'Nenhum teste executado.') => {
                state.validatedKey = null;
                state.validationOk = false;
                setValidationMessage(message, 'neutral');
                setValidationDetails('');
                updateSubmitAvailability();
            };

            const applyCreateMode = () => {
                state.mode = 'create';
                state.initialSubdomain = '';
                state.initialPipelineId = '';
                state.initialStatusId = '';
                state.hasStoredToken = false;
                form.action = storeUrl;
                methodInput.value = 'POST';
                formModeInput.value = 'create';
                contextIdInput.value = '';
                title.textContent = 'Nova integração Kommo';
                description.textContent = 'Teste a conexão, carregue os destinos do Kommo e escolha pipeline e estágio antes de salvar.';
                tokenHint.textContent = 'Obrigatório para validar e salvar a integração.';
                clienteId.value = '';
                clienteIdHidden.value = '';
                nameInput.value = '';
                subdomainInput.value = '';
                tokenInput.value = '';
                setClienteLocked(false);
                setNameLocked(false);
                resetValidationState();
                clearDestinationState();
            };

            const applyEditMode = (button, overrides = {}) => {
                state.mode = 'edit';
                state.initialSubdomain = normalizeSubdomain(overrides.subdomain ?? button.dataset.subdomain ?? '');
                state.initialPipelineId = String(overrides.pipeline_id ?? button.dataset.pipelineId ?? '');
                state.initialStatusId = String(overrides.status_id ?? button.dataset.statusId ?? '');
                state.hasStoredToken = true;
                form.action = button.dataset.updateUrl;
                methodInput.value = 'PATCH';
                formModeInput.value = 'edit';
                contextIdInput.value = button.dataset.id;
                title.textContent = 'Editar integração Kommo';
                description.textContent = 'Atualize subdomain, token e destino padrão. Nome técnico e cliente ficam travados depois da criação.';
                tokenHint.textContent = 'Opcional na edição. Deixe em branco para manter o token atual.';

                const currentClienteId = String(overrides.cliente_id ?? button.dataset.clienteId ?? '');
                clienteId.value = currentClienteId;
                clienteIdHidden.value = currentClienteId;
                nameInput.value = overrides.name ?? button.dataset.name ?? '';
                subdomainInput.value = overrides.subdomain ?? button.dataset.subdomain ?? '';
                tokenInput.value = overrides.access_token ?? '';
                setClienteLocked(true);
                setNameLocked(true);

                setValidationMessage('Credenciais atuais já validadas. Recarregando o destino salvo no Kommo...', 'neutral');

                const accountName = button.dataset.accountName || '';
                const accountId = button.dataset.accountId || '';
                setValidationDetails(`
                    <div><strong>Conta atual:</strong> ${accountName || '-'}</div>
                    <div><strong>ID atual:</strong> ${accountId || '-'}</div>
                    <div><strong>Subdomain:</strong> ${subdomainInput.value || '-'}</div>
                `);

                clearDestinationState(
                    'Carregando a pipeline e o estágio atuais desta integração...',
                    `
                        <div><strong>Pipeline salva:</strong> ${button.dataset.pipelineName || '-'}</div>
                        <div><strong>Estágio salvo:</strong> ${button.dataset.statusName || '-'}</div>
                    `
                );

                state.validatedKey = null;
                state.validationOk = false;
                updateSubmitAvailability();
            };

            const handleCredentialInputChange = () => {
                if (state.mode === 'edit' && !credentialsChanged()) {
                    resetValidationState('Credenciais atuais já validadas. O destino salvo será recarregado.');
                    loadPipelines({
                        selectedPipelineId: pipelineSelect.value || state.initialPipelineId,
                        selectedStatusId: statusSelect.value || state.initialStatusId,
                    });

                    return;
                }

                resetValidationState('As credenciais mudaram. Teste a conexão novamente antes de salvar.');
                clearDestinationState('As credenciais mudaram. O destino padrão será recarregado após nova validação.');
            };

            const requestJson = async (url, payload) => {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!response.ok || !data.ok) {
                    throw new Error(data.message || 'Não foi possível carregar os dados do Kommo.');
                }

                return data;
            };

            const loadStatuses = async ({ selectedStatusId = '', expectedExisting = false } = {}) => {
                const pipelineId = pipelineSelect.value;

                if (pipelineId === '') {
                    populateSelect(statusSelect, [], 'Selecione uma pipeline primeiro');
                    statusSelect.disabled = true;
                    state.destinationValid = false;
                    setDestinationMessage('Selecione uma pipeline para carregar os estágios.', 'neutral');
                    setDestinationDetails('');
                    updateSubmitAvailability();
                    return;
                }

                const requestId = ++state.statusRequestId;
                statusSelect.disabled = true;
                populateSelect(statusSelect, [], 'Carregando estágios...');
                state.destinationValid = false;
                setDestinationMessage('Carregando os estágios da pipeline Kommo...', 'neutral');
                setDestinationDetails('');
                updateSubmitAvailability();

                try {
                    const data = await requestJson(statusesUrl, {
                        ...currentLookupPayload(),
                        pipeline_id: pipelineId,
                    });

                    if (requestId !== state.statusRequestId) {
                        return;
                    }

                    populateSelect(statusSelect, data.statuses || [], 'Selecione um estágio');
                    statusSelect.disabled = false;

                    if ((data.statuses || []).length === 0) {
                        state.destinationValid = false;
                        setDestinationMessage('A pipeline selecionada não possui estágios disponíveis no Kommo.', 'error');
                        updateSubmitAvailability();
                        return;
                    }

                    if (selectedStatusId !== '') {
                        const exists = (data.statuses || []).some((item) => String(item.id) === String(selectedStatusId));

                        if (exists) {
                            statusSelect.value = String(selectedStatusId);
                            state.destinationValid = true;
                            setDestinationMessage('Destino Kommo pronto para salvar.', 'success');
                            setDestinationDetails(`
                                <div><strong>Pipeline:</strong> ${pipelineSelect.options[pipelineSelect.selectedIndex]?.text || '-'}</div>
                                <div><strong>Estágio:</strong> ${statusSelect.options[statusSelect.selectedIndex]?.text || '-'}</div>
                            `);
                            updateSubmitAvailability();
                            return;
                        }

                        if (expectedExisting) {
                            state.destinationValid = false;
                            setDestinationMessage('O estágio salvo não existe mais nessa pipeline do Kommo. Escolha um novo estágio para continuar.', 'error');
                            updateSubmitAvailability();
                            return;
                        }
                    }

                    setDestinationMessage('Selecione o estágio padrão desta integração.', 'neutral');
                    updateSubmitAvailability();
                } catch (error) {
                    if (requestId !== state.statusRequestId) {
                        return;
                    }

                    populateSelect(statusSelect, [], 'Falha ao carregar estágios');
                    statusSelect.disabled = true;
                    state.destinationValid = false;
                    setDestinationMessage(error.message || 'Não foi possível carregar os estágios do Kommo.', 'error');
                    setDestinationDetails('');
                    updateSubmitAvailability();
                }
            };

            const loadPipelines = async ({ selectedPipelineId = '', selectedStatusId = '' } = {}) => {
                const payload = currentLookupPayload();

                if (payload.subdomain === '') {
                    clearDestinationState('Informe um subdomain válido para carregar as pipelines.');
                    return;
                }

                if (state.mode === 'create' && payload.access_token === '') {
                    clearDestinationState('Informe o token e valide a conexão para carregar as pipelines.');
                    return;
                }

                if (state.mode === 'edit' && payload.access_token === '' && !state.hasStoredToken) {
                    clearDestinationState('Informe um token válido para carregar as pipelines.');
                    return;
                }

                const requestId = ++state.pipelineRequestId;
                pipelineSelect.disabled = true;
                statusSelect.disabled = true;
                populateSelect(pipelineSelect, [], 'Carregando pipelines...');
                populateSelect(statusSelect, [], 'Selecione uma pipeline primeiro');
                state.destinationValid = false;
                setDestinationMessage('Carregando as pipelines do Kommo...', 'neutral');
                setDestinationDetails('');
                updateSubmitAvailability();

                try {
                    const data = await requestJson(pipelinesUrl, payload);

                    if (requestId !== state.pipelineRequestId) {
                        return;
                    }

                    populateSelect(pipelineSelect, data.pipelines || [], 'Selecione uma pipeline');
                    pipelineSelect.disabled = false;

                    if ((data.pipelines || []).length === 0) {
                        state.destinationValid = false;
                        setDestinationMessage('Nenhuma pipeline foi encontrada nesta conta Kommo.', 'error');
                        updateSubmitAvailability();
                        return;
                    }

                    if (selectedPipelineId !== '') {
                        const exists = (data.pipelines || []).some((item) => String(item.id) === String(selectedPipelineId));

                        if (exists) {
                            pipelineSelect.value = String(selectedPipelineId);
                            await loadStatuses({
                                selectedStatusId,
                                expectedExisting: true,
                            });
                            return;
                        }

                        state.destinationValid = false;
                        setDestinationMessage('A pipeline salva não existe mais no Kommo. Escolha uma nova pipeline para continuar.', 'error');
                        updateSubmitAvailability();
                        return;
                    }

                    setDestinationMessage('Selecione a pipeline padrão desta integração.', 'neutral');
                    updateSubmitAvailability();
                } catch (error) {
                    if (requestId !== state.pipelineRequestId) {
                        return;
                    }

                    clearDestinationState(error.message || 'Não foi possível carregar as pipelines do Kommo.');
                    validationStatus.classList.remove('font-semibold', 'text-emerald-700');
                }
            };

            const runValidation = async () => {
                const payload = currentLookupPayload();

                if (payload.subdomain === '') {
                    setValidationMessage('Informe um subdomain válido antes de testar.', 'error');
                    setValidationDetails('');
                    return;
                }

                if (state.mode === 'create' && payload.access_token === '') {
                    setValidationMessage('Informe o long-lived token antes de testar.', 'error');
                    setValidationDetails('');
                    return;
                }

                if (state.mode === 'edit' && payload.access_token === '' && !state.hasStoredToken) {
                    setValidationMessage('Informe um token válido antes de testar.', 'error');
                    setValidationDetails('');
                    return;
                }

                validateButton.disabled = true;
                validateButton.textContent = 'Testando...';
                submitButton.disabled = true;
                setValidationMessage('Validando conexão com o Kommo...', 'neutral');
                clearDestinationState('Aguardando a validação da conta para carregar as pipelines.');

                try {
                    const data = await requestJson(validateUrl, payload);

                    state.validatedKey = validationKey();
                    state.validationOk = true;
                    setValidationMessage(data.message || 'Conexão validada com sucesso.', 'success');
                    setValidationDetails(`
                        <div><strong>Conta:</strong> ${data.account_name || '-'}</div>
                        <div><strong>ID:</strong> ${data.account_id || '-'}</div>
                        <div><strong>Subdomain:</strong> ${data.subdomain || payload.subdomain}</div>
                    `);

                    await loadPipelines({
                        selectedPipelineId: pipelineSelect.value || state.initialPipelineId,
                        selectedStatusId: statusSelect.value || state.initialStatusId,
                    });
                } catch (error) {
                    state.validatedKey = null;
                    state.validationOk = false;
                    setValidationMessage(error.message || 'Não foi possível validar a conexão.', 'error');
                    setValidationDetails('');
                    clearDestinationState('Não foi possível carregar o destino padrão sem validar a conexão.');
                    updateSubmitAvailability();
                } finally {
                    validateButton.disabled = false;
                    validateButton.textContent = 'Testar conexão';
                }
            };

            openBtn.addEventListener('click', () => {
                applyCreateMode();
                openModal();
            });

            closeBtns.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.querySelectorAll('[data-open-edit]').forEach((button) => {
                button.addEventListener('click', () => {
                    applyEditMode(button);
                    openModal();
                    loadPipelines({
                        selectedPipelineId: button.dataset.pipelineId || '',
                        selectedStatusId: button.dataset.statusId || '',
                    });
                });
            });

            [subdomainInput, tokenInput].forEach((field) => {
                field.addEventListener('input', handleCredentialInputChange);
            });

            pipelineSelect.addEventListener('change', () => {
                if (pipelineSelect.value === '') {
                    populateSelect(statusSelect, [], 'Selecione uma pipeline primeiro');
                    statusSelect.disabled = true;
                    state.destinationValid = false;
                    setDestinationMessage('Selecione uma pipeline para carregar os estágios.', 'neutral');
                    setDestinationDetails('');
                    updateSubmitAvailability();
                    return;
                }

                loadStatuses();
            });

            statusSelect.addEventListener('change', () => {
                if (statusSelect.value === '') {
                    state.destinationValid = false;
                    setDestinationMessage('Selecione o estágio padrão desta integração.', 'neutral');
                    setDestinationDetails('');
                    updateSubmitAvailability();
                    return;
                }

                state.destinationValid = true;
                setDestinationMessage('Destino Kommo pronto para salvar.', 'success');
                setDestinationDetails(`
                    <div><strong>Pipeline:</strong> ${pipelineSelect.options[pipelineSelect.selectedIndex]?.text || '-'}</div>
                    <div><strong>Estágio:</strong> ${statusSelect.options[statusSelect.selectedIndex]?.text || '-'}</div>
                `);
                updateSubmitAvailability();
            });

            validateButton.addEventListener('click', runValidation);

            if (oldInput.form_mode === 'edit' && oldInput.kommo_account_context_id) {
                const button = document.querySelector(`[data-open-edit][data-id="${oldInput.kommo_account_context_id}"]`);
                if (button) {
                    applyEditMode(button, oldInput);
                    openModal();
                    loadPipelines({
                        selectedPipelineId: oldInput.pipeline_id || button.dataset.pipelineId || '',
                        selectedStatusId: oldInput.status_id || button.dataset.statusId || '',
                    });
                }
            } else if (@js($errors->any())) {
                applyCreateMode();
                clienteId.value = oldInput.cliente_id || '';
                clienteIdHidden.value = oldInput.cliente_id || '';
                nameInput.value = oldInput.name || '';
                subdomainInput.value = oldInput.subdomain || '';
                tokenInput.value = oldInput.access_token || '';
                resetValidationState('Confira os campos, valide a conexão e escolha o destino padrão antes de salvar.');
                clearDestinationState('Valide a conexão para recarregar as pipelines do Kommo.');
                openModal();
            }
        })();
    </script>
@endpush
