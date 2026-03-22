@extends('layouts.agencia')

@section('content')
    @php
        $publicUrl = route('api.webhook-links.handle', ['token' => $link->token]);
        $currentConfig = is_array($link->config) ? $link->config : ['lead' => ['phone_path' => null, 'name_path' => null], 'actions' => []];
        $latestDelivery = $link->latestDelivery;
        $tagOptions = $tags->map(fn ($tag) => [
            'id' => $tag->id,
            'name' => $tag->name,
            'cliente_id' => $tag->cliente_id,
        ])->values();
        $fieldOptions = $customFields->map(fn ($field) => [
            'id' => $field->id,
            'name' => $field->name,
            'label' => $field->label,
            'cliente_id' => $field->cliente_id,
        ])->values();
        $conexaoOptions = $conexoes->map(fn ($conexao) => [
            'id' => $conexao->id,
            'name' => $conexao->name,
            'provider_slug' => $conexao->whatsappApi?->slug,
            'whatsapp_cloud_account_id' => $conexao->whatsapp_cloud_account_id ? (int) $conexao->whatsapp_cloud_account_id : null,
        ])->values();
        $cloudTemplateOptions = $cloudTemplates->map(fn ($template) => [
            'id' => $template->id,
            'title' => $template->title ?: $template->template_name,
            'template_name' => $template->template_name,
            'language_code' => $template->language_code,
            'status' => $template->status,
            'conexao_id' => $template->conexao_id ? (int) $template->conexao_id : null,
            'whatsapp_cloud_account_id' => (int) $template->whatsapp_cloud_account_id,
            'variables' => collect((array) ($template->variables ?? []))
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ])->values();
        $templateBindableFieldOptions = collect($templateBindableFields)->map(fn ($field) => [
            'name' => $field['name'] ?? null,
            'label' => $field['label'] ?? null,
            'cliente_id' => $field['cliente_id'] ?? null,
        ])->values();
        $lastPayloadText = $latestPayload ? json_encode($latestPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('agencia.webhook-links.index') }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Voltar</a>
                <div>
                    <h2 class="text-2xl font-semibold text-slate-900">{{ $link->name }}</h2>
                    <p class="text-sm text-slate-500">Cliente fixo: {{ $link->cliente?->nome ?? '—' }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">
            <div class="font-semibold text-slate-800">Última entrega</div>
            <div class="mt-1 text-xs">
                @if($latestDelivery)
                    {{ ucfirst($latestDelivery->status) }} em {{ optional($latestDelivery->processed_at ?? $latestDelivery->created_at)->format('d/m/Y H:i') }}
                @else
                    Nenhuma entrega recebida ainda.
                @endif
            </div>
        </div>
    </div>

    <form id="webhookLinkEditForm" method="POST" action="{{ route('agencia.webhook-links.update', $link) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="config_json" id="webhookConfigJson">

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-1">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Link público</h3>
                            <p class="text-xs text-slate-500">Use este endpoint para enviar JSON.</p>
                        </div>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $link->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                            {{ $link->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <input
                            id="publicWebhookUrl"
                            type="text"
                            readonly
                            value="{{ $publicUrl }}"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600"
                        >
                        <button type="button" id="copyPublicWebhookUrl" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Copiar
                        </button>
                    </div>

                    <div class="mt-4 grid gap-3">
                        <div>
                            <label for="webhookLinkName" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nome</label>
                            <input
                                id="webhookLinkName"
                                name="name"
                                type="text"
                                value="{{ old('name', $link->name) }}"
                                maxlength="255"
                                required
                                class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</label>
                            <input
                                type="text"
                                value="{{ $link->cliente?->nome ?? '—' }}"
                                readonly
                                class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600"
                            >
                        </div>

                        <div>
                            <label for="webhookLinkConexao" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conexão fixa</label>
                            <select id="webhookLinkConexao" name="conexao_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Sem conexão fixa</option>
                                @foreach($conexoes as $conexao)
                                    <option value="{{ $conexao->id }}" @selected((int) old('conexao_id', $link->conexao_id) === (int) $conexao->id)>
                                        {{ $conexao->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-400">Obrigatória apenas quando houver ação de enviar para assistente.</p>
                        </div>

                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2">
                            <input
                                type="hidden"
                                name="is_active" value="0"
                            >
                            <input
                                id="webhookLinkIsActive"
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked(old('is_active', $link->is_active))
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            >
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Webhook ativo</span>
                                <span class="block text-xs text-slate-500">Se desativado, o endpoint público responderá com 404.</span>
                            </span>
                        </label>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            type="submit"
                            form="rotateWebhookTokenForm"
                            class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100"
                            onclick="return confirm('Rotacionar o token vai invalidar a URL atual. Deseja continuar?');"
                        >
                            Rotacionar token
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Último payload recebido</h3>
                            <p class="text-xs text-slate-500">Você pode colar outro JSON abaixo para configurar o mapeamento localmente.</p>
                        </div>
                        @if($latestDelivery)
                            <div class="text-right text-xs text-slate-500">
                                <div class="font-semibold text-slate-700">{{ ucfirst($latestDelivery->status) }}</div>
                                <div>{{ optional($latestDelivery->processed_at ?? $latestDelivery->created_at)->format('d/m/Y H:i') }}</div>
                            </div>
                        @endif
                    </div>

                    <div id="payloadEditorError" class="mt-4 hidden rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-xs text-rose-700"></div>

                    <textarea
                        id="payloadPreviewEditor"
                        rows="20"
                        class="mt-4 w-full rounded-2xl border border-slate-200 bg-slate-950 px-4 py-3 font-mono text-xs text-slate-100"
                    >{{ $latestPayload ? json_encode($latestPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '' }}</textarea>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" id="applyPayloadPreview" class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                            Aplicar preview
                        </button>
                        <button type="button" id="useLastPayload" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Usar último payload
                        </button>
                    </div>

                    @if($latestDelivery)
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                            <div class="font-semibold text-slate-700">Resumo da última entrega</div>
                            <div class="mt-2 space-y-1">
                                <div>Status: {{ ucfirst($latestDelivery->status) }}</div>
                                @if($latestDelivery->resolved_phone)
                                    <div>Telefone resolvido: {{ $latestDelivery->resolved_phone }}</div>
                                @endif
                                @if($latestDelivery->cliente_lead_id)
                                    <div>Lead: #{{ $latestDelivery->cliente_lead_id }}</div>
                                @endif
                                @if($latestDelivery->error_message)
                                    <div class="text-rose-600">Erro: {{ $latestDelivery->error_message }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Mapeamento do lead</h3>
                            <p class="text-xs text-slate-500">Esses cards definem como o payload cria ou atualiza o `ClienteLead`.</p>
                        </div>
                        <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Salvar webhook
                        </button>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">ClienteLead.phone</p>
                                    <p class="text-xs text-slate-500">Obrigatório</p>
                                </div>
                            </div>
                            <select id="leadPhonePath" class="mt-4 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" data-path-select>
                                <option value="">Selecione um caminho do payload</option>
                            </select>
                            <p id="leadPhonePreview" class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600">Valor atual: —</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">ClienteLead.name</p>
                                    <p class="text-xs text-slate-500">Opcional</p>
                                </div>
                            </div>
                            <select id="leadNamePath" class="mt-4 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" data-path-select>
                                <option value="">Não mapear</option>
                            </select>
                            <p id="leadNamePreview" class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600">Valor atual: —</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Ações adicionais</h3>
                            <p class="text-xs text-slate-500">Use ações para aplicar tags fixas, atualizar campos personalizados e enviar para o assistente.</p>
                        </div>

                        <div class="relative">
                            <button type="button" id="toggleActionMenu" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Adicionar novas ações
                            </button>
                            <div id="actionMenu" class="absolute right-0 z-10 mt-2 hidden min-w-[220px] rounded-2xl border border-slate-200 bg-white p-2 shadow-lg">
                                <button type="button" data-add-action="tag" class="block w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                    adicionar tag
                                </button>
                                <button type="button" data-add-action="custom_field" class="mt-1 block w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                    campo do usuario
                                </button>
                                <button type="button" data-add-action="prompt" class="mt-1 block w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                    enviar para assistente
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="actionsValidationMessage" class="mt-4 hidden rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-xs text-amber-700"></div>

                    <div id="actionsContainer" class="mt-5 space-y-4"></div>
                </div>
            </div>
        </div>
    </form>

    <form id="rotateWebhookTokenForm" method="POST" action="{{ route('agencia.webhook-links.rotate-token', $link) }}" class="hidden">
        @csrf
    </form>

    <script>
        (() => {
            const availableTags = @json($tagOptions);
            const availableFields = @json($fieldOptions);
            const availableConexoes = @json($conexaoOptions);
            const availableCloudTemplates = @json($cloudTemplateOptions);
            const templateBindableFields = @json($templateBindableFieldOptions);
            const initialConfig = @json($currentConfig);
            const latestPayload = @json($latestPayload);
            const lastPayloadText = @json($lastPayloadText);

            const form = document.getElementById('webhookLinkEditForm');
            const configInput = document.getElementById('webhookConfigJson');
            const payloadEditor = document.getElementById('payloadPreviewEditor');
            const payloadError = document.getElementById('payloadEditorError');
            const applyPayloadButton = document.getElementById('applyPayloadPreview');
            const useLastPayloadButton = document.getElementById('useLastPayload');
            const leadPhonePath = document.getElementById('leadPhonePath');
            const leadNamePath = document.getElementById('leadNamePath');
            const leadPhonePreview = document.getElementById('leadPhonePreview');
            const leadNamePreview = document.getElementById('leadNamePreview');
            const actionsContainer = document.getElementById('actionsContainer');
            const validationMessage = document.getElementById('actionsValidationMessage');
            const toggleActionMenuButton = document.getElementById('toggleActionMenu');
            const actionMenu = document.getElementById('actionMenu');
            const copyPublicWebhookUrlButton = document.getElementById('copyPublicWebhookUrl');
            const publicWebhookUrlInput = document.getElementById('publicWebhookUrl');
            const webhookLinkConexao = document.getElementById('webhookLinkConexao');

            let currentPayload = Array.isArray(latestPayload) || (latestPayload && typeof latestPayload === 'object')
                ? latestPayload
                : null;

            const pathState = {
                paths: [],
                valuesByPath: {},
            };

            const currentCards = () => Array.from(actionsContainer.querySelectorAll('[data-action-card]'));

            const getSelectedConexao = () => {
                const conexaoId = webhookLinkConexao?.value || '';
                return availableConexoes.find((conexao) => String(conexao.id) === String(conexaoId)) || null;
            };

            const isCloudConexaoSelected = () => {
                const provider = (getSelectedConexao()?.provider_slug || '').toString().trim().toLowerCase();
                return provider === 'whatsapp_cloud';
            };

            const getTemplateBindableFields = () => {
                return templateBindableFields
                    .filter((field) => (field?.name || '').toString().trim() !== '')
                    .slice()
                    .sort((a, b) => {
                        const labelA = ((a?.label || a?.name || '').toString()).toLowerCase();
                        const labelB = ((b?.label || b?.name || '').toString()).toLowerCase();
                        return labelA.localeCompare(labelB, 'pt-BR');
                    });
            };

            const getAvailableTemplatesForSelectedConexao = () => {
                const conexao = getSelectedConexao();
                if (!conexao || !isCloudConexaoSelected()) {
                    return [];
                }

                const accountId = String(conexao.whatsapp_cloud_account_id || '');

                return availableCloudTemplates.filter((template) => {
                    if (String(template.whatsapp_cloud_account_id || '') !== accountId) {
                        return false;
                    }

                    return !template.conexao_id || String(template.conexao_id) === String(conexao.id);
                });
            };

            const getCloudTemplateById = (templateId) => {
                return getAvailableTemplatesForSelectedConexao()
                    .find((template) => String(template.id) === String(templateId)) || null;
            };

            const safeParsePayload = (value) => {
                const trimmed = String(value || '').trim();
                if (trimmed === '') {
                    return { payload: null, error: null };
                }

                try {
                    const parsed = JSON.parse(trimmed);
                    if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed) && parsed.length === 0) {
                        return { payload: parsed, error: null };
                    }

                    return { payload: parsed, error: null };
                } catch (error) {
                    return { payload: null, error: 'JSON inválido. Corrija o conteúdo antes de usar os selects do payload.' };
                }
            };

            const flattenScalarPaths = (value, prefix = 'payload', accumulator = []) => {
                if (Array.isArray(value)) {
                    value.forEach((item, index) => {
                        flattenScalarPaths(item, `${prefix}.${index}`, accumulator);
                    });
                    return accumulator;
                }

                if (value !== null && typeof value === 'object') {
                    Object.entries(value).forEach(([key, item]) => {
                        flattenScalarPaths(item, `${prefix}.${key}`, accumulator);
                    });
                    return accumulator;
                }

                accumulator.push({
                    path: prefix,
                    value: value,
                });
                return accumulator;
            };

            const stringifyValue = (value) => {
                if (value === null || typeof value === 'undefined') {
                    return 'null';
                }

                if (typeof value === 'boolean') {
                    return value ? 'true' : 'false';
                }

                if (typeof value === 'object') {
                    return JSON.stringify(value);
                }

                const text = String(value);
                return text === '' ? '(vazio)' : text;
            };

            const resolvePath = (path) => {
                if (!path || !currentPayload) {
                    return undefined;
                }

                const segments = String(path).split('.');
                if (segments.shift() !== 'payload') {
                    return undefined;
                }

                let value = currentPayload;
                for (const segment of segments) {
                    if (value === null || typeof value === 'undefined') {
                        return undefined;
                    }

                    value = value[segment];
                }

                return value;
            };

            const renderPayloadState = () => {
                const parsed = safeParsePayload(payloadEditor.value);

                if (parsed.error) {
                    currentPayload = null;
                    pathState.paths = [];
                    pathState.valuesByPath = {};
                    payloadError.textContent = parsed.error;
                    payloadError.classList.remove('hidden');
                    refreshPathSelects();
                    refreshPreviews();
                    return false;
                }

                payloadError.classList.add('hidden');
                currentPayload = parsed.payload;
                const flattened = currentPayload && typeof currentPayload === 'object'
                    ? flattenScalarPaths(currentPayload)
                    : [];

                pathState.paths = flattened.map((item) => item.path);
                pathState.valuesByPath = Object.fromEntries(flattened.map((item) => [item.path, item.value]));
                refreshPathSelects();
                refreshPreviews();

                return true;
            };

            const ensureSelectValue = (select, selectedValue) => {
                if (!selectedValue) {
                    return;
                }

                const hasOption = Array.from(select.options).some((option) => option.value === selectedValue);
                if (hasOption) {
                    select.value = selectedValue;
                    return;
                }

                const option = document.createElement('option');
                option.value = selectedValue;
                option.textContent = `${selectedValue} (não encontrado no preview)`;
                option.dataset.synthetic = 'true';
                select.appendChild(option);
                select.value = selectedValue;
            };

            const populatePathSelect = (select, selectedValue, placeholder) => {
                const savedValue = selectedValue || select.value || '';
                select.innerHTML = '';

                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = placeholder;
                select.appendChild(placeholderOption);

                pathState.paths.forEach((path) => {
                    const option = document.createElement('option');
                    option.value = path;
                    option.textContent = `${path} = ${stringifyValue(pathState.valuesByPath[path])}`;
                    select.appendChild(option);
                });

                ensureSelectValue(select, savedValue);
                select.disabled = pathState.paths.length === 0;
            };

            const refreshPathSelects = () => {
                populatePathSelect(leadPhonePath, leadPhonePath.value || initialConfig?.lead?.phone_path || '', 'Selecione um caminho do payload');
                populatePathSelect(leadNamePath, leadNamePath.value || initialConfig?.lead?.name_path || '', 'Não mapear');

                actionsContainer.querySelectorAll('[data-source-path-select]').forEach((select) => {
                    populatePathSelect(select, select.value, 'Selecione um caminho do payload');
                });

                actionsContainer.querySelectorAll('[data-prompt-insert-select]').forEach((select) => {
                    populatePathSelect(select, select.value, 'Selecione um caminho para inserir');
                });
            };

            const setPreviewText = (element, path) => {
                if (!path) {
                    element.textContent = 'Valor atual: —';
                    return;
                }

                const value = resolvePath(path);
                element.textContent = typeof value === 'undefined'
                    ? 'Valor atual: não encontrado no preview'
                    : `Valor atual: ${stringifyValue(value)}`;
            };

            const renderPromptPreview = (template) => {
                const promptTokenPattern = /\{\{\s*(payload(?:\.[^}\s]+)*)\s*\}\}/g;

                return String(template || '').replace(promptTokenPattern, (_, path) => {
                    const value = resolvePath(path);
                    return typeof value === 'undefined' || value === null ? '' : stringifyValue(value);
                });
            };

            const refreshPreviews = () => {
                setPreviewText(leadPhonePreview, leadPhonePath.value);
                setPreviewText(leadNamePreview, leadNamePath.value);

                actionsContainer.querySelectorAll('[data-action-card]').forEach((card) => {
                    const type = card.dataset.actionType;

                    if (type === 'custom_field') {
                        const select = card.querySelector('[data-source-path-select]');
                        const preview = card.querySelector('[data-source-path-preview]');
                        if (select && preview) {
                            setPreviewText(preview, select.value);
                        }
                    }

                    if (type === 'prompt') {
                        const textarea = card.querySelector('[data-prompt-textarea]');
                        const preview = card.querySelector('[data-prompt-preview]');
                        const previewPrefix = card.dataset.promptMode === 'cloud'
                            ? 'Preview do contexto: '
                            : 'Preview do texto: ';
                        if (textarea && preview) {
                            const rendered = renderPromptPreview(textarea.value).trim();
                            preview.textContent = rendered === '' ? `${previewPrefix}—` : `${previewPrefix}${rendered}`;
                        }
                    }
                });
            };

            const populateCloudTemplateSelect = (select, selectedValue) => {
                const templates = getAvailableTemplatesForSelectedConexao();
                select.innerHTML = '';

                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = templates.length === 0
                    ? 'Nenhum modelo aprovado disponível'
                    : 'Selecione um modelo';
                select.appendChild(placeholderOption);

                templates.forEach((template) => {
                    const option = document.createElement('option');
                    option.value = String(template.id);
                    option.textContent = `${template.title} (${template.language_code})`;
                    select.appendChild(option);
                });

                if (selectedValue && templates.some((template) => String(template.id) === String(selectedValue))) {
                    select.value = String(selectedValue);
                } else {
                    select.value = '';
                }

                select.disabled = templates.length === 0;
            };

            const createCloudBindingRow = (variable, selectedValue = '') => {
                const row = document.createElement('div');
                row.className = 'grid gap-2 md:grid-cols-3';
                row.dataset.cloudTemplateBindingRow = 'true';
                row.dataset.variable = variable;

                const optionsHtml = getTemplateBindableFields().map((field) => {
                    const fieldLabel = (field?.label || '').toString().trim();
                    const suffix = field?.cliente_id ? '' : ' (global)';
                    const label = fieldLabel !== '' ? `${field.name} - ${fieldLabel}${suffix}` : `${field.name}${suffix}`;
                    return `<option value="${field.name}">${label}</option>`;
                }).join('');

                row.innerHTML = `
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">${variable}</label>
                    <select class="md:col-span-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" data-cloud-template-binding-select>
                        <option value="">Selecione o campo do lead</option>
                        ${optionsHtml}
                    </select>
                `;

                const select = row.querySelector('[data-cloud-template-binding-select]');
                if (select) {
                    select.value = selectedValue || '';
                }

                return row;
            };

            const collectCloudBindings = (card) => {
                const bindings = {};

                card.querySelectorAll('[data-cloud-template-binding-row]').forEach((row) => {
                    const variable = (row.dataset.variable || '').toString().trim();
                    const value = row.querySelector('[data-cloud-template-binding-select]')?.value || '';

                    if (variable !== '' && value !== '') {
                        bindings[variable] = value;
                    }
                });

                return bindings;
            };

            const renderCloudTemplateBindings = (card, templateId, seededBindings = {}) => {
                const wrap = card.querySelector('[data-cloud-template-bindings-wrap]');
                const list = card.querySelector('[data-cloud-template-bindings]');
                if (!wrap || !list) {
                    return;
                }

                list.innerHTML = '';

                const template = getCloudTemplateById(templateId);
                const variables = Array.isArray(template?.variables)
                    ? template.variables
                        .map((value) => (value || '').toString().trim().toLowerCase())
                        .filter((value, index, array) => value !== '' && array.indexOf(value) === index && /^var_\d+$/.test(value))
                    : [];

                if (!template || variables.length === 0) {
                    wrap.classList.add('hidden');
                    return;
                }

                variables.forEach((variable) => {
                    list.appendChild(createCloudBindingRow(variable, seededBindings[variable] || ''));
                });

                wrap.classList.remove('hidden');
            };

            const buildActionCard = (action = {}) => {
                const type = action.type;
                const wrapper = document.createElement('div');
                wrapper.className = 'rounded-2xl border border-slate-200 bg-slate-50 p-4';
                wrapper.dataset.actionCard = 'true';
                wrapper.dataset.actionType = type;

                const removeButton = '<button type="button" class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-white" data-remove-action>Remover</button>';

                if (type === 'tag') {
                    wrapper.innerHTML = `
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Adicionar tag</p>
                                <p class="text-xs text-slate-500">Essa tag será sempre aplicada ao lead.</p>
                            </div>
                            ${removeButton}
                        </div>
                        <div class="mt-4">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tag fixa</label>
                            <select class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" data-tag-id>
                                <option value="">Selecione uma tag</option>
                                ${availableTags.map((tag) => `<option value="${tag.id}">${tag.name}${tag.cliente_id ? '' : ' (global)'}</option>`).join('')}
                            </select>
                        </div>
                    `;

                    const select = wrapper.querySelector('[data-tag-id]');
                    if (select) {
                        select.value = action.tag_id ? String(action.tag_id) : '';
                    }

                    return wrapper;
                }

                if (type === 'custom_field') {
                    wrapper.innerHTML = `
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Campo do usuário</p>
                                <p class="text-xs text-slate-500">Relaciona um campo personalizado existente com um valor do payload.</p>
                            </div>
                            ${removeButton}
                        </div>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Campo personalizado</label>
                                <select class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" data-field-id>
                                    <option value="">Selecione um campo</option>
                                    ${availableFields.map((field) => `<option value="${field.id}">${field.label ? `${field.label} (${field.name})` : field.name}${field.cliente_id ? '' : ' (global)'}</option>`).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Caminho do payload</label>
                                <select class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" data-source-path-select>
                                    <option value="">Selecione um caminho do payload</option>
                                </select>
                            </div>
                        </div>
                        <p class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600" data-source-path-preview>Valor atual: —</p>
                    `;

                    const fieldSelect = wrapper.querySelector('[data-field-id]');
                    const pathSelect = wrapper.querySelector('[data-source-path-select]');

                    if (fieldSelect) {
                        fieldSelect.value = action.field_id ? String(action.field_id) : '';
                    }

                    if (pathSelect) {
                        populatePathSelect(pathSelect, action.source_path || '', 'Selecione um caminho do payload');
                    }

                    return wrapper;
                }

                if (type === 'prompt') {
                    const isCloudMode = isCloudConexaoSelected();
                    wrapper.dataset.promptMode = isCloudMode ? 'cloud' : 'text';

                    if (isCloudMode) {
                        const seededContext = (action.assistant_context_instructions ?? action.template ?? '').toString();

                        wrapper.innerHTML = `
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">Enviar para assistente</p>
                                    <p class="text-xs text-slate-500">Envie um modelo aprovado pela WhatsApp Cloud e sincronize o contexto no assistente.</p>
                                </div>
                                ${removeButton}
                            </div>
                            <div class="mt-4">
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Modelo aprovado</label>
                                <select class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" data-cloud-template-id></select>
                            </div>
                            <div class="mt-4 hidden rounded-xl border border-slate-200 bg-white p-4" data-cloud-template-bindings-wrap>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Variáveis do modelo</p>
                                <div class="mt-3 space-y-3" data-cloud-template-bindings></div>
                            </div>
                            <div class="mt-4">
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contexto para o assistente (opcional)</label>
                                <textarea rows="5" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm" data-prompt-textarea data-cloud-assistant-context></textarea>
                                <p class="mt-1 text-xs text-slate-400">Esse texto entra apenas como contexto. Ele não força resposta automática do assistente.</p>
                            </div>
                            <div class="mt-4 flex flex-wrap items-end gap-2">
                                <div class="min-w-[240px] flex-1">
                                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Inserir token do payload</label>
                                    <select class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" data-prompt-insert-select>
                                        <option value="">Selecione um caminho para inserir</option>
                                    </select>
                                </div>
                                <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white" data-insert-prompt-placeholder>
                                    Inserir no texto
                                </button>
                            </div>
                            <p class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600" data-prompt-preview>Preview do contexto: —</p>
                        `;

                        const templateSelect = wrapper.querySelector('[data-cloud-template-id]');
                        const insertSelect = wrapper.querySelector('[data-prompt-insert-select]');
                        const textarea = wrapper.querySelector('[data-cloud-assistant-context]');

                        if (templateSelect) {
                            populateCloudTemplateSelect(templateSelect, action.whatsapp_cloud_template_id || '');
                            renderCloudTemplateBindings(wrapper, templateSelect.value, action.template_variable_bindings || {});
                        }

                        if (insertSelect) {
                            populatePathSelect(insertSelect, '', 'Selecione um caminho para inserir');
                        }

                        if (textarea) {
                            textarea.value = seededContext;
                        }

                        return wrapper;
                    }

                    const seededTemplate = (action.template ?? action.assistant_context_instructions ?? '').toString();

                    wrapper.innerHTML = `
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Enviar para assistente</p>
                                <p class="text-xs text-slate-500">Renderiza texto com placeholders do payload e envia ao assistente pela conexão fixa.</p>
                            </div>
                            ${removeButton}
                        </div>
                        <div class="mt-4">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Texto para o assistente</label>
                            <textarea rows="6" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm" data-prompt-textarea data-prompt-template></textarea>
                            <p class="mt-1 text-xs text-slate-400">Use placeholders no formato <code>@{{payload.caminho}}</code>.</p>
                        </div>
                        <div class="mt-4 flex flex-wrap items-end gap-2">
                            <div class="min-w-[240px] flex-1">
                                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Inserir token do payload</label>
                                <select class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" data-prompt-insert-select>
                                    <option value="">Selecione um caminho para inserir</option>
                                </select>
                            </div>
                            <button type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white" data-insert-prompt-placeholder>
                                Inserir no texto
                            </button>
                        </div>
                        <p class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600" data-prompt-preview>Preview do texto: —</p>
                    `;

                    const textarea = wrapper.querySelector('[data-prompt-template]');
                    const insertSelect = wrapper.querySelector('[data-prompt-insert-select]');

                    if (textarea) {
                        textarea.value = seededTemplate;
                    }

                    if (insertSelect) {
                        populatePathSelect(insertSelect, '', 'Selecione um caminho para inserir');
                    }

                    return wrapper;
                }

                return null;
            };

            const createActionCard = (action = {}) => {
                return buildActionCard(action);
            };

            const addActionCard = (action) => {
                const card = createActionCard(action);
                if (!card) {
                    return;
                }

                actionsContainer.appendChild(card);
                refreshPreviews();
                enforceActionUniqueness();
            };

            const hasPromptCard = () => currentCards().some((card) => card.dataset.actionType === 'prompt');

            const showValidationMessage = (message = '') => {
                if (!message) {
                    validationMessage.classList.add('hidden');
                    validationMessage.textContent = '';
                    return;
                }

                validationMessage.textContent = message;
                validationMessage.classList.remove('hidden');
            };

            const enforceActionUniqueness = () => {
                const tagValues = new Set();
                const fieldValues = new Set();

                currentCards().forEach((card) => {
                    if (card.dataset.actionType === 'tag') {
                        const select = card.querySelector('[data-tag-id]');
                        if (!select || !select.value) {
                            return;
                        }

                        if (tagValues.has(select.value)) {
                            select.value = '';
                            showValidationMessage('Não é permitido repetir a mesma tag mais de uma vez.');
                            return;
                        }

                        tagValues.add(select.value);
                    }

                    if (card.dataset.actionType === 'custom_field') {
                        const select = card.querySelector('[data-field-id]');
                        if (!select || !select.value) {
                            return;
                        }

                        if (fieldValues.has(select.value)) {
                            select.value = '';
                            showValidationMessage('Não é permitido repetir o mesmo campo personalizado mais de uma vez.');
                            return;
                        }

                        fieldValues.add(select.value);
                    }
                });
            };

            const extractPromptActionState = (card) => {
                if (card.dataset.promptMode === 'cloud') {
                    return {
                        type: 'prompt',
                        whatsapp_cloud_template_id: card.querySelector('[data-cloud-template-id]')?.value || '',
                        template_variable_bindings: collectCloudBindings(card),
                        assistant_context_instructions: card.querySelector('[data-cloud-assistant-context]')?.value || '',
                    };
                }

                return {
                    type: 'prompt',
                    template: card.querySelector('[data-prompt-template]')?.value || '',
                };
            };

            const rebuildPromptCardsForConnectionChange = () => {
                currentCards()
                    .filter((card) => card.dataset.actionType === 'prompt')
                    .forEach((card) => {
                        const currentState = extractPromptActionState(card);
                        const replacement = createActionCard(
                            isCloudConexaoSelected()
                                ? {
                                    type: 'prompt',
                                    whatsapp_cloud_template_id: currentState.whatsapp_cloud_template_id || '',
                                    template_variable_bindings: currentState.template_variable_bindings || {},
                                    assistant_context_instructions: currentState.assistant_context_instructions || currentState.template || '',
                                }
                                : {
                                    type: 'prompt',
                                    template: currentState.template || currentState.assistant_context_instructions || '',
                                }
                        );

                        if (replacement) {
                            card.replaceWith(replacement);
                        }
                    });

                refreshPathSelects();
                refreshPreviews();
            };

            const collectConfig = () => {
                const config = {
                    lead: {
                        phone_path: leadPhonePath.value || null,
                        name_path: leadNamePath.value || null,
                    },
                    actions: [],
                };

                currentCards().forEach((card) => {
                    if (card.dataset.actionType === 'tag') {
                        const tagId = card.querySelector('[data-tag-id]')?.value || '';
                        if (tagId) {
                            config.actions.push({
                                type: 'tag',
                                tag_id: Number(tagId),
                            });
                        }
                        return;
                    }

                    if (card.dataset.actionType === 'custom_field') {
                        const fieldId = card.querySelector('[data-field-id]')?.value || '';
                        const sourcePath = card.querySelector('[data-source-path-select]')?.value || '';
                        if (fieldId || sourcePath) {
                            config.actions.push({
                                type: 'custom_field',
                                field_id: fieldId ? Number(fieldId) : null,
                                source_path: sourcePath || null,
                            });
                        }
                        return;
                    }

                    if (card.dataset.actionType === 'prompt') {
                        if (card.dataset.promptMode === 'cloud') {
                            const templateId = card.querySelector('[data-cloud-template-id]')?.value || '';
                            const assistantContextInstructions = card.querySelector('[data-cloud-assistant-context]')?.value || '';

                            config.actions.push({
                                type: 'prompt',
                                whatsapp_cloud_template_id: templateId ? Number(templateId) : null,
                                template_variable_bindings: collectCloudBindings(card),
                                assistant_context_instructions: assistantContextInstructions || null,
                            });
                            return;
                        }

                        const template = card.querySelector('[data-prompt-template]')?.value || '';
                        config.actions.push({
                            type: 'prompt',
                            template,
                        });
                    }
                });

                return config;
            };

            copyPublicWebhookUrlButton.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(publicWebhookUrlInput.value);
                    copyPublicWebhookUrlButton.textContent = 'Copiado';
                    window.setTimeout(() => {
                        copyPublicWebhookUrlButton.textContent = 'Copiar';
                    }, 1200);
                } catch (error) {
                    window.prompt('Copie a URL manualmente:', publicWebhookUrlInput.value);
                }
            });

            toggleActionMenuButton.addEventListener('click', (event) => {
                event.stopPropagation();
                actionMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', () => {
                actionMenu.classList.add('hidden');
            });

            actionMenu.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            actionMenu.querySelectorAll('[data-add-action]').forEach((button) => {
                button.addEventListener('click', () => {
                    const type = button.dataset.addAction;

                    if (type === 'prompt' && hasPromptCard()) {
                        showValidationMessage('Este webhook já possui um card de enviar para assistente.');
                        actionMenu.classList.add('hidden');
                        return;
                    }

                    showValidationMessage('');
                    addActionCard({ type });
                    actionMenu.classList.add('hidden');
                });
            });

            actionsContainer.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-action]');
                if (removeButton) {
                    removeButton.closest('[data-action-card]')?.remove();
                    showValidationMessage('');
                    refreshPreviews();
                    return;
                }

                const insertButton = event.target.closest('[data-insert-prompt-placeholder]');
                if (insertButton) {
                    const card = insertButton.closest('[data-action-card]');
                    const select = card?.querySelector('[data-prompt-insert-select]');
                    const textarea = card?.querySelector('[data-prompt-textarea]');
                    if (!card || !select || !textarea || !select.value) {
                        return;
                    }

                    const token = ['{', '{', select.value, '}', '}'].join('');
                    const start = textarea.selectionStart ?? textarea.value.length;
                    const end = textarea.selectionEnd ?? textarea.value.length;
                    textarea.value = `${textarea.value.slice(0, start)}${token}${textarea.value.slice(end)}`;
                    textarea.focus();
                    textarea.selectionStart = textarea.selectionEnd = start + token.length;
                    refreshPreviews();
                }
            });

            actionsContainer.addEventListener('change', (event) => {
                const target = event.target;

                if (target.matches('[data-tag-id], [data-field-id]')) {
                    showValidationMessage('');
                    enforceActionUniqueness();
                }

                if (target.matches('[data-source-path-select], [data-prompt-insert-select], [data-tag-id], [data-field-id]')) {
                    refreshPreviews();
                }

                if (target.matches('[data-cloud-template-id]')) {
                    const card = target.closest('[data-action-card]');
                    if (!card) {
                        return;
                    }

                    renderCloudTemplateBindings(card, target.value, collectCloudBindings(card));
                    refreshPreviews();
                }
            });

            actionsContainer.addEventListener('input', (event) => {
                const target = event.target;
                if (target.matches('[data-prompt-textarea]')) {
                    refreshPreviews();
                }
            });

            leadPhonePath.addEventListener('change', refreshPreviews);
            leadNamePath.addEventListener('change', refreshPreviews);
            webhookLinkConexao?.addEventListener('change', rebuildPromptCardsForConnectionChange);
            applyPayloadButton.addEventListener('click', renderPayloadState);
            useLastPayloadButton.addEventListener('click', () => {
                payloadEditor.value = lastPayloadText || '';
                renderPayloadState();
            });

            form.addEventListener('submit', (event) => {
                showValidationMessage('');
                enforceActionUniqueness();

                const config = collectConfig();
                configInput.value = JSON.stringify(config);
            });

            const initialPhonePath = initialConfig?.lead?.phone_path || '';
            const initialNamePath = initialConfig?.lead?.name_path || '';

            renderPayloadState();
            ensureSelectValue(leadPhonePath, initialPhonePath);
            ensureSelectValue(leadNamePath, initialNamePath);
            leadPhonePath.value = initialPhonePath;
            leadNamePath.value = initialNamePath;

            (Array.isArray(initialConfig?.actions) ? initialConfig.actions : []).forEach((action) => {
                if (!action || typeof action !== 'object') {
                    return;
                }

                addActionCard(action);
            });

            refreshPathSelects();
            refreshPreviews();
        })();
    </script>
@endsection
