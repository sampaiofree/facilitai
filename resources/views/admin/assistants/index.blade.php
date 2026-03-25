@extends('layouts.adm')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Assistants</h2>
            <p class="text-sm text-slate-500">Visualize os assistants cadastrados e abra os prompts completos em tela cheia.</p>
        </div>
    </div>

    <div id="assistantPageFeedback" class="mb-6 hidden rounded-lg border px-4 py-3 text-sm"></div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Usuário</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Cliente</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Assistant</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($assistants as $assistant)
                    <tr class="hover:bg-slate-50" data-assistant-row-id="{{ $assistant->id }}">
                        <td class="px-5 py-4 text-slate-700">{{ $assistant->user?->name ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $assistant->cliente?->nome ?? '-' }}</td>
                        <td class="px-5 py-4 font-medium text-slate-900">
                            <span data-assistant-name>{{ $assistant->name }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <button
                                type="button"
                                data-show-url="{{ route('adm.assistants.show', $assistant) }}"
                                class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                            >Ver</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-6 text-center text-slate-500">Nenhum assistant encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($assistants->hasPages())
        <div class="mt-4 flex items-center justify-end">
            {{ $assistants->links('pagination::tailwind') }}
        </div>
    @endif

    <div id="assistantViewModal" class="fixed inset-0 z-50 hidden bg-black/60 p-2 backdrop-blur-sm sm:p-4">
        <div data-modal-panel class="flex h-full w-full flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
                <div>
                    <h3 id="assistantModalTitle" class="text-lg font-semibold text-slate-900">Detalhes do Assistant</h3>
                    <p id="assistantModalSubtitle" class="text-sm text-slate-500">Resumo do cadastro e prompts completos do registro selecionado.</p>
                </div>
                <div id="assistantModalViewActions" class="flex items-center gap-2">
                    <button
                        id="assistantEditButton"
                        type="button"
                        class="hidden rounded-full border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:border-slate-400 hover:text-slate-900"
                    >Editar</button>
                    <button
                        type="button"
                        data-close-modal
                        class="rounded-full border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-500 hover:border-slate-400 hover:text-slate-700"
                    >Fechar</button>
                </div>
                <div id="assistantModalEditActions" class="hidden items-center gap-2">
                    <button
                        id="assistantCancelEditButton"
                        type="button"
                        class="rounded-full border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:border-slate-400 hover:text-slate-900"
                    >Cancelar</button>
                    <button
                        id="assistantSaveButton"
                        type="submit"
                        form="assistantEditForm"
                        class="rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                    >Salvar</button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6">
                <div id="assistantModalLoading" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                    Carregando detalhes do assistant...
                </div>

                <div id="assistantModalError" class="hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">
                    <p id="assistantModalErrorMessage">Falha ao carregar os detalhes do assistant.</p>
                </div>

                <div id="assistantModalContent" class="hidden space-y-8">
                    <section>
                        <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Resumo</h4>
                        <dl class="mt-3 grid grid-cols-1 gap-4 text-sm text-slate-700 md:grid-cols-2 xl:grid-cols-3">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <dt class="text-xs uppercase tracking-wide text-slate-400">ID</dt>
                                <dd id="assistantSummaryId" class="mt-1 font-semibold text-slate-900"></dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Nome</dt>
                                <dd id="assistantSummaryName" class="mt-1 font-semibold text-slate-900"></dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Usuário</dt>
                                <dd id="assistantSummaryUser" class="mt-1"></dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Cliente</dt>
                                <dd id="assistantSummaryCliente" class="mt-1"></dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <dt class="text-xs uppercase tracking-wide text-slate-400">OpenAI Assistant ID</dt>
                                <dd id="assistantSummaryOpenAi" class="mt-1 break-all"></dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Modelo</dt>
                                <dd id="assistantSummaryModelo" class="mt-1"></dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Delay</dt>
                                <dd id="assistantSummaryDelay" class="mt-1"></dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Versão</dt>
                                <dd id="assistantSummaryVersion" class="mt-1"></dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Criado em</dt>
                                <dd id="assistantSummaryCreatedAt" class="mt-1"></dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 md:col-span-2 xl:col-span-3">
                                <dt class="text-xs uppercase tracking-wide text-slate-400">Atualizado em</dt>
                                <dd id="assistantSummaryUpdatedAt" class="mt-1"></dd>
                            </div>
                        </dl>
                    </section>

                    <section>
                        <div class="flex items-center justify-between gap-4">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Prompts e textos</h4>
                            <span class="text-xs text-slate-400">`instructions` sempre aparece; demais campos só quando houver conteúdo.</span>
                        </div>
                        <div id="assistantTextCards" class="mt-3 space-y-4"></div>
                    </section>
                </div>

                <div id="assistantEditContent" class="hidden">
                    <div id="assistantEditErrors" class="hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700"></div>

                    <form id="assistantEditForm" class="mt-0 space-y-5">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="assistantEditName">Nome</label>
                            <input
                                id="assistantEditName"
                                name="name"
                                type="text"
                                maxlength="255"
                                required
                                class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="assistantEditDelay">Delay (segundos)</label>
                            <input
                                id="assistantEditDelay"
                                name="delay"
                                type="number"
                                min="0"
                                step="1"
                                class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Prompt ajuda</div>
                                    <p class="mt-1 text-xs text-slate-500">Escolha um prompt para inserir nas instruções.</p>
                                </div>
                                @if(!$promptHelpTipos->isEmpty())
                                    <div class="relative" id="promptHelpDropdown">
                                        <div class="flex flex-wrap items-center justify-end gap-2" id="promptHelpTypeButtons">
                                            @foreach($promptHelpTipos as $tipo)
                                                <button
                                                    type="button"
                                                    class="prompt-help-type inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-700"
                                                    data-ph-type-btn
                                                    data-ph-type-id="{{ $tipo->id }}"
                                                >
                                                    {{ $tipo->name }}
                                                    <span class="text-slate-400">▾</span>
                                                </button>
                                            @endforeach
                                        </div>
                                        <div
                                            id="promptHelpDropdownMenu"
                                            class="absolute right-0 z-10 mt-2 hidden max-h-96 w-[360px] overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"
                                        >
                                            @foreach($promptHelpTipos as $tipo)
                                                <div class="px-4 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500" data-ph-type-block="{{ $tipo->id }}">{{ $tipo->name }}</div>
                                                @foreach($tipo->sections as $section)
                                                    <button
                                                        type="button"
                                                        class="prompt-help-section-toggle flex w-full items-center justify-between px-5 py-2 text-left text-[11px] font-semibold text-slate-700 hover:bg-slate-50"
                                                        data-ph-type-block="{{ $tipo->id }}"
                                                        data-ph-section-toggle
                                                        data-ph-type-id="{{ $tipo->id }}"
                                                        data-ph-section-id="{{ $section->id }}"
                                                    >
                                                        <span>{{ $section->name }}</span>
                                                        <span class="text-slate-400">▸</span>
                                                    </button>
                                                    <div
                                                        class="hidden border-l border-slate-200"
                                                        data-ph-section-content
                                                        data-ph-type-id="{{ $tipo->id }}"
                                                        data-ph-section-id="{{ $section->id }}"
                                                    >
                                                        @forelse($section->prompts as $prompt)
                                                            <button
                                                                type="button"
                                                                class="prompt-help-item w-full px-6 py-2 text-left text-xs text-slate-700 hover:bg-slate-50"
                                                                data-prompt-help-item
                                                                data-ph-type-id="{{ $tipo->id }}"
                                                                data-ph-section-id="{{ $section->id }}"
                                                                data-prompt='@json($prompt->prompt)'
                                                            >
                                                                <div class="font-semibold text-slate-900">{{ $prompt->name }}</div>
                                                                <div class="text-[11px] text-slate-500">
                                                                    {{ $prompt->descricao ? \Illuminate\Support\Str::limit($prompt->descricao, 80) : 'Clique para inserir no campo de instruções.' }}
                                                                </div>
                                                            </button>
                                                        @empty
                                                            <div class="px-6 py-2 text-xs text-slate-400">Nenhum prompt nesta seção.</div>
                                                        @endforelse
                                                    </div>
                                                @endforeach
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @if($promptHelpTipos->isEmpty())
                                <p class="mt-3 text-sm text-slate-500">Nenhum prompt de ajuda cadastrado.</p>
                            @endif
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="assistantEditInstructions">Instruções</label>
                            <textarea
                                id="assistantEditInstructions"
                                name="instructions"
                                rows="12"
                                required
                                class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('assistantViewModal');
            if (!modal) {
                return;
            }

            const pageFeedback = document.getElementById('assistantPageFeedback');
            const modalTitle = document.getElementById('assistantModalTitle');
            const modalSubtitle = document.getElementById('assistantModalSubtitle');
            const loadingState = document.getElementById('assistantModalLoading');
            const errorState = document.getElementById('assistantModalError');
            const errorMessage = document.getElementById('assistantModalErrorMessage');
            const contentState = document.getElementById('assistantModalContent');
            const editState = document.getElementById('assistantEditContent');
            const textCards = document.getElementById('assistantTextCards');
            const closeButtons = modal.querySelectorAll('[data-close-modal]');
            const openButtons = document.querySelectorAll('[data-show-url]');
            const viewActions = document.getElementById('assistantModalViewActions');
            const editActions = document.getElementById('assistantModalEditActions');
            const editButton = document.getElementById('assistantEditButton');
            const cancelEditButton = document.getElementById('assistantCancelEditButton');
            const saveButton = document.getElementById('assistantSaveButton');
            const editForm = document.getElementById('assistantEditForm');
            const editErrors = document.getElementById('assistantEditErrors');
            const editNameInput = document.getElementById('assistantEditName');
            const editDelayInput = document.getElementById('assistantEditDelay');
            const editInstructionsInput = document.getElementById('assistantEditInstructions');
            const summaryFields = {
                id: document.getElementById('assistantSummaryId'),
                name: document.getElementById('assistantSummaryName'),
                user_name: document.getElementById('assistantSummaryUser'),
                cliente_name: document.getElementById('assistantSummaryCliente'),
                openai_assistant_id: document.getElementById('assistantSummaryOpenAi'),
                modelo: document.getElementById('assistantSummaryModelo'),
                delay: document.getElementById('assistantSummaryDelay'),
                version: document.getElementById('assistantSummaryVersion'),
                created_at: document.getElementById('assistantSummaryCreatedAt'),
                updated_at: document.getElementById('assistantSummaryUpdatedAt'),
            };
            const textLabels = {
                instructions: 'Instructions',
                systemPrompt: 'System Prompt',
                developerPrompt: 'Developer Prompt',
                prompt_notificar_adm: 'Prompt Notificar ADM',
                prompt_buscar_get: 'Prompt Buscar GET',
                prompt_enviar_media: 'Prompt Enviar Mídia',
                prompt_registrar_info_chat: 'Prompt Registrar Info Chat',
                prompt_gerenciar_agenda: 'Prompt Gerenciar Agenda',
                prompt_aplicar_tags: 'Prompt Aplicar Tags',
                prompt_sequencia: 'Prompt Sequência',
            };
            const dropdown = document.getElementById('promptHelpDropdown');
            const dropdownMenu = document.getElementById('promptHelpDropdownMenu');
            const typeButtons = Array.from(document.querySelectorAll('[data-ph-type-btn]'));
            const typeBlocks = Array.from(document.querySelectorAll('[data-ph-type-block]'));
            const sectionToggles = Array.from(document.querySelectorAll('[data-ph-section-toggle]'));
            const sectionContents = Array.from(document.querySelectorAll('[data-ph-section-content]'));
            const promptItems = Array.from(document.querySelectorAll('[data-prompt-help-item]'));
            const activeTypeClasses = ['border-blue-600', 'bg-blue-600'];
            const activeSectionClasses = ['bg-slate-100', 'text-slate-900'];
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            let activeTypeId = null;
            let currentAssistant = null;
            let currentShowUrl = null;
            let feedbackTimeout = null;
            let lastActiveElement = null;
            let requestSequence = 0;

            const hasContent = (value) => typeof value === 'string'
                ? value.trim() !== ''
                : value !== null && value !== undefined;

            const parseJsonSafe = async (response) => {
                try {
                    return await response.json();
                } catch (error) {
                    return null;
                }
            };

            const openModal = () => {
                lastActiveElement = document.activeElement;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeDropdown = () => {
                dropdownMenu?.classList.add('hidden');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
                resetEditErrors();
                closeDropdown();
                activateViewChrome(false);

                if (lastActiveElement instanceof HTMLElement) {
                    lastActiveElement.focus();
                }
            };

            const showPageFeedback = (message, tone = 'success') => {
                if (!pageFeedback) {
                    return;
                }

                if (feedbackTimeout) {
                    window.clearTimeout(feedbackTimeout);
                }

                pageFeedback.textContent = message;
                pageFeedback.className = 'mb-6 rounded-lg border px-4 py-3 text-sm';

                if (tone === 'success') {
                    pageFeedback.classList.add('border-emerald-100', 'bg-emerald-50', 'text-emerald-700');
                } else {
                    pageFeedback.classList.add('border-rose-100', 'bg-rose-50', 'text-rose-700');
                }

                pageFeedback.classList.remove('hidden');

                feedbackTimeout = window.setTimeout(() => {
                    pageFeedback.classList.add('hidden');
                }, 5000);
            };

            const activateViewChrome = (canEdit = false) => {
                viewActions.classList.remove('hidden');
                viewActions.classList.add('flex');
                editActions.classList.add('hidden');
                editActions.classList.remove('flex');
                editButton.classList.toggle('hidden', !canEdit);
                modalTitle.textContent = 'Detalhes do Assistant';
                modalSubtitle.textContent = 'Resumo do cadastro e prompts completos do registro selecionado.';
            };

            const activateEditChrome = () => {
                viewActions.classList.add('hidden');
                viewActions.classList.remove('flex');
                editActions.classList.remove('hidden');
                editActions.classList.add('flex');
                modalTitle.textContent = 'Editar Assistant';
                modalSubtitle.textContent = 'Atualize o nome, o delay e o texto principal do assistant.';
            };

            const showLoading = () => {
                currentAssistant = null;
                activateViewChrome(false);
                loadingState.classList.remove('hidden');
                errorState.classList.add('hidden');
                contentState.classList.add('hidden');
                editState.classList.add('hidden');
                errorMessage.textContent = '';
                textCards.innerHTML = '';
                resetEditErrors();
            };

            const showLoadError = (message) => {
                activateViewChrome(false);
                loadingState.classList.add('hidden');
                errorState.classList.remove('hidden');
                contentState.classList.add('hidden');
                editState.classList.add('hidden');
                errorMessage.textContent = message;
                resetEditErrors();
            };

            const showViewContent = () => {
                activateViewChrome(true);
                loadingState.classList.add('hidden');
                errorState.classList.add('hidden');
                contentState.classList.remove('hidden');
                editState.classList.add('hidden');
                resetEditErrors();
            };

            const showEditContent = () => {
                activateEditChrome();
                loadingState.classList.add('hidden');
                errorState.classList.add('hidden');
                contentState.classList.add('hidden');
                editState.classList.remove('hidden');
                resetEditErrors();
            };

            const fillSummary = (summary) => {
                Object.entries(summaryFields).forEach(([key, element]) => {
                    if (!element) {
                        return;
                    }

                    const value = summary?.[key];
                    element.textContent = hasContent(value) ? String(value) : '-';
                });
            };

            const buildCard = (title, value) => {
                const article = document.createElement('article');
                article.className = 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm';

                const heading = document.createElement('h5');
                heading.className = 'text-sm font-semibold text-slate-900';
                heading.textContent = title;

                const pre = document.createElement('pre');
                pre.className = 'mt-3 whitespace-pre-wrap break-words rounded-xl bg-slate-50 p-4 text-sm text-slate-700';
                pre.textContent = hasContent(value) ? String(value) : '-';

                article.appendChild(heading);
                article.appendChild(pre);

                return article;
            };

            const renderTexts = (texts) => {
                textCards.innerHTML = '';
                textCards.appendChild(buildCard(textLabels.instructions, texts?.instructions));

                const optionalKeys = Object.keys(textLabels).filter((key) => key !== 'instructions');
                const optionalWithContent = optionalKeys.filter((key) => hasContent(texts?.[key]));

                optionalWithContent.forEach((key) => {
                    textCards.appendChild(buildCard(textLabels[key], texts[key]));
                });

                if (optionalWithContent.length === 0) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500';
                    emptyState.textContent = 'Sem prompts adicionais.';
                    textCards.appendChild(emptyState);
                }
            };

            const fillEditForm = (payload) => {
                editNameInput.value = payload?.summary?.name ?? '';
                editDelayInput.value = payload?.summary?.delay ?? '';
                editInstructionsInput.value = payload?.texts?.instructions ?? '';
                editNameInput.focus();
            };

            const resetEditErrors = () => {
                editErrors.classList.add('hidden');
                editErrors.innerHTML = '';
            };

            const renderEditErrors = (errors = {}, message = '') => {
                const lines = [];

                if (message && message !== 'The given data was invalid.') {
                    lines.push(message);
                }

                Object.values(errors).flat().forEach((line) => {
                    if (line) {
                        lines.push(line);
                    }
                });

                if (!lines.length) {
                    resetEditErrors();
                    return;
                }

                editErrors.innerHTML = lines
                    .map((line) => `<p>${line}</p>`)
                    .join('');
                editErrors.classList.remove('hidden');
            };

            const updateTableRow = (summary) => {
                const row = document.querySelector(`[data-assistant-row-id="${summary?.id}"]`);
                const nameElement = row?.querySelector('[data-assistant-name]');

                if (nameElement && hasContent(summary?.name)) {
                    nameElement.textContent = String(summary.name);
                }
            };

            const insertAtCursor = (field, text) => {
                if (!field) {
                    return;
                }

                const start = field.selectionStart ?? field.value.length;
                const end = field.selectionEnd ?? field.value.length;
                const before = field.value.slice(0, start);
                const after = field.value.slice(end);
                const addSeparator = before.length > 0 && start === end && start === field.value.length ? '\n\n' : '';
                field.value = `${before}${addSeparator}${text}${after}`;
                const cursor = (before + addSeparator + text).length;
                field.setSelectionRange(cursor, cursor);
                field.focus();
            };

            const setActiveType = (typeId) => {
                activeTypeId = typeId;

                typeButtons.forEach((button) => {
                    const isActive = button.dataset.phTypeId === typeId;
                    activeTypeClasses.forEach((className) => {
                        button.classList.toggle(className, isActive);
                    });
                });

                typeBlocks.forEach((block) => {
                    block.classList.toggle('hidden', block.dataset.phTypeBlock !== typeId);
                });

                sectionToggles.forEach((toggle) => {
                    toggle.classList.toggle('hidden', toggle.dataset.phTypeId !== typeId);
                    activeSectionClasses.forEach((className) => toggle.classList.remove(className));
                });

                sectionContents.forEach((content) => {
                    content.classList.add('hidden');
                });

                promptItems.forEach((item) => {
                    item.classList.toggle('hidden', item.dataset.phTypeId !== typeId);
                });
            };

            const loadAssistant = async (url) => {
                requestSequence += 1;
                const currentRequest = requestSequence;
                currentShowUrl = url;

                openModal();
                showLoading();

                try {
                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        const payload = await parseJsonSafe(response);
                        throw new Error(payload?.message || 'Falha ao carregar os detalhes do assistant.');
                    }

                    const payload = await response.json();
                    if (currentRequest !== requestSequence) {
                        return;
                    }

                    currentAssistant = payload;
                    fillSummary(payload.summary ?? {});
                    renderTexts(payload.texts ?? {});
                    showViewContent();
                } catch (error) {
                    if (currentRequest !== requestSequence) {
                        return;
                    }

                    showLoadError(error instanceof Error ? error.message : 'Falha ao carregar os detalhes do assistant.');
                }
            };

            const saveAssistant = async () => {
                if (!currentShowUrl) {
                    renderEditErrors({}, 'Falha ao localizar a rota de atualização do assistant.');
                    return;
                }

                resetEditErrors();
                saveButton.disabled = true;
                saveButton.textContent = 'Salvando...';

                try {
                    const response = await fetch(currentShowUrl, {
                        method: 'PATCH',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            name: editNameInput.value,
                            delay: editDelayInput.value === '' ? null : Number(editDelayInput.value),
                            instructions: editInstructionsInput.value,
                        }),
                    });

                    const payload = await parseJsonSafe(response);

                    if (response.status === 422) {
                        renderEditErrors(payload?.errors ?? {}, payload?.message || 'Os dados informados são inválidos.');
                        return;
                    }

                    if (!response.ok) {
                        renderEditErrors({}, payload?.message || 'Falha ao atualizar o assistant.');
                        return;
                    }

                    currentAssistant = payload;
                    updateTableRow(payload.summary ?? {});
                    closeModal();
                    showPageFeedback(payload?.message || 'Assistente atualizado com sucesso.');
                } catch (error) {
                    renderEditErrors({}, error instanceof Error ? error.message : 'Falha ao atualizar o assistant.');
                } finally {
                    saveButton.disabled = false;
                    saveButton.textContent = 'Salvar';
                }
            };

            openButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const { showUrl } = button.dataset;
                    if (!showUrl) {
                        return;
                    }

                    loadAssistant(showUrl);
                });
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            editButton.addEventListener('click', () => {
                if (!currentAssistant) {
                    return;
                }

                fillEditForm(currentAssistant);
                showEditContent();
            });

            cancelEditButton.addEventListener('click', () => {
                if (!currentAssistant) {
                    closeModal();
                    return;
                }

                fillSummary(currentAssistant.summary ?? {});
                renderTexts(currentAssistant.texts ?? {});
                showViewContent();
            });

            editForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await saveAssistant();
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            if (typeButtons.length && dropdownMenu) {
                typeButtons.forEach((button) => {
                    button.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const typeId = button.dataset.phTypeId;
                        const isSameType = activeTypeId === typeId;

                        if (isSameType && !dropdownMenu.classList.contains('hidden')) {
                            closeDropdown();
                            return;
                        }

                        setActiveType(typeId);
                        dropdownMenu.classList.remove('hidden');
                    });
                });

                sectionToggles.forEach((button) => {
                    button.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const typeId = button.dataset.phTypeId;
                        const sectionId = button.dataset.phSectionId;

                        if (activeTypeId !== typeId) {
                            setActiveType(typeId);
                        }

                        const target = sectionContents.find((content) => {
                            return content.dataset.phTypeId === typeId && content.dataset.phSectionId === sectionId;
                        });
                        const isOpen = target && !target.classList.contains('hidden');

                        sectionContents.forEach((content) => {
                            if (content.dataset.phTypeId === typeId) {
                                content.classList.add('hidden');
                            }
                        });

                        sectionToggles.forEach((toggle) => {
                            if (toggle.dataset.phTypeId === typeId) {
                                activeSectionClasses.forEach((className) => toggle.classList.remove(className));
                            }
                        });

                        if (target && !isOpen) {
                            target.classList.remove('hidden');
                            activeSectionClasses.forEach((className) => button.classList.add(className));
                        }
                    });
                });

                document.addEventListener('click', (event) => {
                    if (!dropdown?.contains(event.target)) {
                        closeDropdown();
                    }
                });
            }

            if (promptItems.length) {
                promptItems.forEach((button) => {
                    button.addEventListener('click', () => {
                        const raw = button.dataset.prompt || '""';
                        const text = JSON.parse(raw);
                        insertAtCursor(editInstructionsInput, text);
                        closeDropdown();
                    });
                });
            }
        })();
    </script>
@endpush
