@extends('layouts.cliente')

@section('title', 'Assistentes')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Assistentes</h2>
            <p class="text-sm text-slate-500">Edite os assistentes vinculados ao seu cliente.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">ID</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Versão</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Instruções</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Atualizado em</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($assistants as $assistant)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 text-slate-600">{{ $assistant->id }}</td>
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $assistant->name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $assistant->version }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ \Illuminate\Support\Str::limit($assistant->instructions, 70) }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $assistant->updated_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                    data-open-edit
                                    data-id="{{ $assistant->id }}"
                                    data-name='@json($assistant->name)'
                                    data-instructions='@json($assistant->instructions)'
                                    data-delay="{{ $assistant->delay ?? 0 }}"
                                >Editar</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-6 text-center text-slate-500">Nenhum assistente cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="assistantModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="h-full w-full overflow-y-auto bg-white shadow-2xl">
            <div class="mx-auto flex min-h-full w-full max-w-6xl flex-col px-6 py-6">
                <div class="flex items-center justify-between">
                    <h3 id="assistantModalTitle" class="text-xl font-semibold text-slate-900">Editar assistente</h3>
                    <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
                </div>

                <form id="assistantForm" method="POST" class="mt-6 flex-1 space-y-5">
                    @csrf
                    <input type="hidden" name="_method" id="assistantFormMethod" value="PATCH">
                    <input type="hidden" name="editing_id" id="assistantEditingId" value="{{ old('editing_id') }}">

                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="assistantName">Nome</label>
                        <input
                            id="assistantName"
                            name="name"
                            type="text"
                            required
                            maxlength="255"
                            value="{{ old('name') }}"
                            class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="assistantDelay">Tempo de resposta (segundos)</label>
                        <input
                            id="assistantDelay"
                            name="delay"
                            type="number"
                            min="0"
                            step="1"
                            value="{{ old('delay') }}"
                            class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        <p class="mt-1 text-xs text-slate-500">0 usa o padrão atual de 25 segundos.</p>
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
                                        class="absolute right-0 z-10 mt-2 hidden w-[360px] max-h-96 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"
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
                        <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="assistantInstructions">Instruções</label>
                        <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-3" data-instruction-topics>
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="instruction-topic-button rounded-lg border border-blue-600 bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition"
                                    data-topic-view-all
                                >
                                    Ver tudo
                                </button>
                                <div class="flex flex-wrap items-center gap-2" data-topic-buttons></div>
                                <button
                                    type="button"
                                    class="rounded-lg border border-dashed border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700"
                                    data-topic-add-toggle
                                >
                                    + Adicionar tópico
                                </button>
                            </div>
                            <div class="mt-3 hidden items-end gap-2" data-topic-add-form>
                                <div class="min-w-0 flex-1">
                                    <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500" for="newInstructionTopicName">Nome do tópico</label>
                                    <input
                                        id="newInstructionTopicName"
                                        type="text"
                                        class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        data-topic-add-name
                                    >
                                </div>
                                <button type="button" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700" data-topic-add-confirm>Adicionar</button>
                                <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-white" data-topic-add-cancel>Cancelar</button>
                            </div>
                        </div>
                        <textarea
                            id="assistantInstructions"
                            name="instructions"
                            rows="10"
                            required
                            class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >{{ old('instructions') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-close-modal>Cancelar</button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('assistantModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('assistantForm');
            const methodInput = document.getElementById('assistantFormMethod');
            const editingInput = document.getElementById('assistantEditingId');
            const title = document.getElementById('assistantModalTitle');
            const nameInput = document.getElementById('assistantName');
            const delayInput = document.getElementById('assistantDelay');
            const instructionsInput = document.getElementById('assistantInstructions');
            const baseUrl = "{{ url('/cliente/assistant') }}";
            const hasErrors = @json($errors->any());
            const sessionEditingId = @json(old('editing_id'));
            const oldName = @json(old('name'));
            const oldDelay = @json(old('delay'));
            const oldInstructions = @json(old('instructions'));
            const dropdown = document.getElementById('promptHelpDropdown');
            const dropdownMenu = document.getElementById('promptHelpDropdownMenu');
            const typeButtons = Array.from(document.querySelectorAll('[data-ph-type-btn]'));
            const typeBlocks = Array.from(document.querySelectorAll('[data-ph-type-block]'));
            const sectionToggles = Array.from(document.querySelectorAll('[data-ph-section-toggle]'));
            const sectionContents = Array.from(document.querySelectorAll('[data-ph-section-content]'));
            const promptItems = Array.from(document.querySelectorAll('[data-prompt-help-item]'));
            const viewAllButton = document.querySelector('[data-topic-view-all]');
            const topicButtonsWrap = document.querySelector('[data-topic-buttons]');
            const addTopicToggle = document.querySelector('[data-topic-add-toggle]');
            const addTopicForm = document.querySelector('[data-topic-add-form]');
            const addTopicName = document.querySelector('[data-topic-add-name]');
            const addTopicConfirm = document.querySelector('[data-topic-add-confirm]');
            const addTopicCancel = document.querySelector('[data-topic-add-cancel]');
            const activeTypeClasses = ['border-blue-600', 'bg-blue-600'];
            const activeSectionClasses = ['bg-slate-100', 'text-slate-900'];
            const activeTopicClasses = ['border-blue-600', 'bg-blue-600', 'text-white'];
            const inactiveTopicClasses = ['border-slate-200', 'bg-white', 'text-slate-700'];
            let activeTypeId = null;
            let fullInstructions = '';
            let activeTopicIndex = null;
            let activeTopicStart = null;
            let activeTopicEnd = null;
            let topicSyncing = false;

            const parseDataValue = (value) => {
                if (value === undefined || value === null || value === '') {
                    return '';
                }
                try {
                    return JSON.parse(value);
                } catch (error) {
                    return value;
                }
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
                methodInput.value = 'PATCH';
                title.textContent = 'Editar assistente';
                editingInput.value = '';
                nameInput.value = '';
                if (delayInput) {
                    delayInput.value = '';
                }
                hideAddTopicForm();
                setInstructionsValue('');
            };

            const insertAtCursor = (field, text) => {
                if (!field) return;
                const start = field.selectionStart ?? field.value.length;
                const end = field.selectionEnd ?? field.value.length;
                const before = field.value.slice(0, start);
                const after = field.value.slice(end);
                const addSeparator = before.length > 0 && start === end && start === field.value.length ? '\n\n' : '';
                field.value = `${before}${addSeparator}${text}${after}`;
                const cursor = (before + addSeparator + text).length;
                field.setSelectionRange(cursor, cursor);
                field.focus();
                field.dispatchEvent(new Event('input', { bubbles: true }));
            };

            const parseTopics = (markdown) => {
                const topics = [];
                const headingPattern = /^# (.*)$/gm;
                let match;

                while ((match = headingPattern.exec(markdown)) !== null) {
                    topics.push({
                        start: match.index,
                        end: markdown.length,
                        title: (match[1] || '').trim() || 'Sem título',
                    });
                }

                topics.forEach((topic, index) => {
                    topic.end = topics[index + 1]?.start ?? markdown.length;
                });

                return topics;
            };

            const setTopicButtonState = () => {
                if (!viewAllButton) return;
                const isAllActive = activeTopicIndex === null;
                activeTopicClasses.forEach(cls => viewAllButton.classList.toggle(cls, isAllActive));
                inactiveTopicClasses.forEach(cls => viewAllButton.classList.toggle(cls, !isAllActive));

                topicButtonsWrap?.querySelectorAll('[data-topic-index]').forEach(button => {
                    const isActive = Number(button.dataset.topicIndex) === activeTopicIndex;
                    activeTopicClasses.forEach(cls => button.classList.toggle(cls, isActive));
                    inactiveTopicClasses.forEach(cls => button.classList.toggle(cls, !isActive));
                });
            };

            const renderTopicButtons = () => {
                if (!topicButtonsWrap) return;
                const topics = parseTopics(fullInstructions);
                topicButtonsWrap.innerHTML = '';

                topics.forEach((topic, index) => {
                    const group = document.createElement('div');
                    group.className = 'inline-flex max-w-full items-center overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm';

                    const moveBeforeButton = document.createElement('button');
                    moveBeforeButton.type = 'button';
                    moveBeforeButton.className = 'px-2 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:text-slate-300 disabled:hover:bg-white';
                    moveBeforeButton.textContent = '←';
                    moveBeforeButton.title = 'Mover tópico para antes';
                    moveBeforeButton.disabled = index === 0;
                    moveBeforeButton.addEventListener('click', () => {
                        moveTopic(index, -1);
                    });

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.dataset.topicIndex = String(index);
                    button.className = 'instruction-topic-button max-w-[220px] truncate border-x border-slate-200 px-3 py-2 text-xs font-semibold transition hover:text-blue-700';
                    button.textContent = topic.title;
                    button.addEventListener('click', () => {
                        showTopic(index);
                    });

                    const moveAfterButton = document.createElement('button');
                    moveAfterButton.type = 'button';
                    moveAfterButton.className = 'px-2 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:text-slate-300 disabled:hover:bg-white';
                    moveAfterButton.textContent = '→';
                    moveAfterButton.title = 'Mover tópico para depois';
                    moveAfterButton.disabled = index === topics.length - 1;
                    moveAfterButton.addEventListener('click', () => {
                        moveTopic(index, 1);
                    });

                    group.appendChild(moveBeforeButton);
                    group.appendChild(button);
                    group.appendChild(moveAfterButton);
                    topicButtonsWrap.appendChild(group);
                });

                if (activeTopicIndex !== null && activeTopicIndex >= topics.length) {
                    activeTopicIndex = null;
                    activeTopicStart = null;
                    activeTopicEnd = null;
                    instructionsInput.value = fullInstructions;
                }

                setTopicButtonState();
            };

            const moveTopic = (index, direction) => {
                syncActiveTopic();
                const topics = parseTopics(fullInstructions);
                const targetIndex = index + direction;

                if (!topics[index] || !topics[targetIndex]) {
                    return;
                }

                const selectedOriginalIndex = activeTopicIndex;
                const prefix = topics.length ? fullInstructions.slice(0, topics[0].start) : fullInstructions;
                const blocks = topics.map((topic, topicIndex) => ({
                    originalIndex: topicIndex,
                    content: fullInstructions.slice(topic.start, topic.end),
                }));

                [blocks[index], blocks[targetIndex]] = [blocks[targetIndex], blocks[index]];
                fullInstructions = prefix + blocks.map(block => block.content).join('');

                if (selectedOriginalIndex === null) {
                    topicSyncing = true;
                    instructionsInput.value = fullInstructions;
                    topicSyncing = false;
                    renderTopicButtons();
                    return;
                }

                const selectedNewIndex = blocks.findIndex(block => block.originalIndex === selectedOriginalIndex);
                activeTopicIndex = null;
                activeTopicStart = null;
                activeTopicEnd = null;
                showTopic(selectedNewIndex === -1 ? targetIndex : selectedNewIndex);
            };

            const syncActiveTopic = () => {
                if (topicSyncing || activeTopicIndex === null) return;
                if (activeTopicStart === null || activeTopicEnd === null) {
                    activeTopicIndex = null;
                    activeTopicStart = null;
                    activeTopicEnd = null;
                    fullInstructions = instructionsInput.value;
                    renderTopicButtons();
                    return;
                }

                fullInstructions = `${fullInstructions.slice(0, activeTopicStart)}${instructionsInput.value}${fullInstructions.slice(activeTopicEnd)}`;
                activeTopicEnd = activeTopicStart + instructionsInput.value.length;
                const topics = parseTopics(fullInstructions);
                activeTopicIndex = topics.findIndex(topic => topic.start === activeTopicStart);
                if (activeTopicIndex === -1) {
                    activeTopicIndex = null;
                    activeTopicStart = null;
                    activeTopicEnd = null;
                    topicSyncing = true;
                    instructionsInput.value = fullInstructions;
                    topicSyncing = false;
                }
                renderTopicButtons();
            };

            const setInstructionsValue = (value) => {
                fullInstructions = value || '';
                activeTopicIndex = null;
                activeTopicStart = null;
                activeTopicEnd = null;
                topicSyncing = true;
                instructionsInput.value = fullInstructions;
                topicSyncing = false;
                renderTopicButtons();
            };

            const showAllInstructions = () => {
                syncActiveTopic();
                activeTopicIndex = null;
                activeTopicStart = null;
                activeTopicEnd = null;
                topicSyncing = true;
                instructionsInput.value = fullInstructions;
                topicSyncing = false;
                renderTopicButtons();
                instructionsInput.focus();
            };

            function showTopic(index) {
                syncActiveTopic();
                const topics = parseTopics(fullInstructions);
                const topic = topics[index];
                if (!topic) {
                    showAllInstructions();
                    return;
                }

                activeTopicIndex = index;
                activeTopicStart = topic.start;
                activeTopicEnd = topic.end;
                topicSyncing = true;
                instructionsInput.value = fullInstructions.slice(topic.start, topic.end);
                topicSyncing = false;
                renderTopicButtons();
                instructionsInput.focus();
            }

            const hideAddTopicForm = () => {
                addTopicForm?.classList.add('hidden');
                addTopicForm?.classList.remove('flex');
                if (addTopicName) {
                    addTopicName.value = '';
                }
            };

            const showAddTopicForm = () => {
                addTopicForm?.classList.remove('hidden');
                addTopicForm?.classList.add('flex');
                addTopicName?.focus();
            };

            const addTopic = () => {
                const topicName = (addTopicName?.value || '').trim();
                if (!topicName) {
                    addTopicName?.focus();
                    return;
                }

                syncActiveTopic();
                const separator = fullInstructions.trim().length > 0 ? '\n\n' : '';
                fullInstructions = `${fullInstructions.trimEnd()}${separator}# ${topicName}\n\nDigite as instruções aqui...`;
                hideAddTopicForm();
                renderTopicButtons();
                showTopic(parseTopics(fullInstructions).length - 1);
            };

            const closeDropdown = () => {
                dropdownMenu?.classList.add('hidden');
            };

            const openDropdown = () => {
                dropdownMenu?.classList.remove('hidden');
            };

            const setActiveType = (typeId) => {
                activeTypeId = typeId;
                typeButtons.forEach(btn => {
                    const isActive = btn.dataset.phTypeId === typeId;
                    activeTypeClasses.forEach(cls => btn.classList.toggle(cls, isActive));
                });

                typeBlocks.forEach(block => {
                    block.classList.toggle('hidden', block.dataset.phTypeBlock !== typeId);
                });

                sectionToggles.forEach(toggle => {
                    toggle.classList.toggle('hidden', toggle.dataset.phTypeId !== typeId);
                    activeSectionClasses.forEach(cls => toggle.classList.remove(cls));
                });

                sectionContents.forEach(content => {
                    content.classList.add('hidden');
                });

                promptItems.forEach(item => {
                    item.classList.toggle('hidden', item.dataset.phTypeId !== typeId);
                });
            };

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
                    editingInput.value = id;
                    nameInput.value = parseDataValue(button.dataset.name);
                    if (delayInput) {
                        delayInput.value = button.dataset.delay ?? '';
                    }
                    setInstructionsValue(parseDataValue(button.dataset.instructions));
                    openModal();
                });
            });

            if (sessionEditingId) {
                resetForm();
                form.action = `${baseUrl}/${sessionEditingId}`;
                editingInput.value = sessionEditingId;
                nameInput.value = oldName ?? '';
                if (delayInput) {
                    delayInput.value = oldDelay ?? '';
                }
                setInstructionsValue(oldInstructions ?? '');
                openModal();
            } else if (hasErrors) {
                resetForm();
                nameInput.value = oldName ?? '';
                if (delayInput) {
                    delayInput.value = oldDelay ?? '';
                }
                setInstructionsValue(oldInstructions ?? '');
                openModal();
            }

            instructionsInput.addEventListener('input', () => {
                if (topicSyncing) return;
                if (activeTopicIndex === null) {
                    fullInstructions = instructionsInput.value;
                    renderTopicButtons();
                    return;
                }

                syncActiveTopic();
            });

            form.addEventListener('submit', () => {
                syncActiveTopic();
                instructionsInput.value = fullInstructions;
            });

            viewAllButton?.addEventListener('click', showAllInstructions);
            addTopicToggle?.addEventListener('click', showAddTopicForm);
            addTopicCancel?.addEventListener('click', hideAddTopicForm);
            addTopicConfirm?.addEventListener('click', addTopic);
            addTopicName?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    addTopic();
                }

                if (event.key === 'Escape') {
                    hideAddTopicForm();
                }
            });

            if (typeButtons.length && dropdownMenu) {
                typeButtons.forEach(button => {
                    button.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const typeId = button.dataset.phTypeId;
                        const isSameType = activeTypeId === typeId;
                        if (isSameType && !dropdownMenu.classList.contains('hidden')) {
                            closeDropdown();
                            return;
                        }
                        setActiveType(typeId);
                        openDropdown();
                    });
                });

                sectionToggles.forEach(button => {
                    button.addEventListener('click', (event) => {
                        event.stopPropagation();
                        const typeId = button.dataset.phTypeId;
                        const sectionId = button.dataset.phSectionId;
                        if (activeTypeId !== typeId) {
                            setActiveType(typeId);
                        }

                        const target = sectionContents.find(content => {
                            return content.dataset.phTypeId === typeId && content.dataset.phSectionId === sectionId;
                        });
                        const isOpen = target && !target.classList.contains('hidden');

                        sectionContents.forEach(content => {
                            if (content.dataset.phTypeId === typeId) {
                                content.classList.add('hidden');
                            }
                        });
                        sectionToggles.forEach(toggle => {
                            if (toggle.dataset.phTypeId === typeId) {
                                activeSectionClasses.forEach(cls => toggle.classList.remove(cls));
                            }
                        });

                        if (target && !isOpen) {
                            target.classList.remove('hidden');
                            activeSectionClasses.forEach(cls => button.classList.add(cls));
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
                promptItems.forEach(button => {
                    button.addEventListener('click', () => {
                        const raw = button.dataset.prompt || '""';
                        const text = JSON.parse(raw);
                        insertAtCursor(instructionsInput, text);
                        closeDropdown();
                    });
                });
            }
        })();
    </script>
@endsection
