@extends('layouts.agencia')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Assistentes</h2>
            <p class="text-sm text-slate-500">Gerencie os assistentes vinculados ao seu usuário.</p>
        </div>
        <button id="openAssistantModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            Novo assistente
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
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
                                    data-name="{{ e($assistant->name) }}"
                                    data-instructions="{{ e($assistant->instructions) }}"
                                >Editar</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-slate-500">Nenhum assistente cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="assistantModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="h-full w-full overflow-y-auto bg-white shadow-2xl">
            <div class="mx-auto flex min-h-full w-full max-w-6xl flex-col px-6 py-6">
                <div class="flex items-center justify-between">
                    <h3 id="assistantModalTitle" class="text-xl font-semibold text-slate-900">Novo assistente</h3>
                    <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
                </div>

                <form id="assistantForm" method="POST" action="{{ route('agencia.assistant.store') }}" class="mt-6 flex-1 space-y-5">
                    @csrf
                    <input type="hidden" name="_method" id="assistantFormMethod" value="POST">
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
                                        <div class="p-3 text-xs text-slate-400">Tipos &rarr; Seções &rarr; Prompts</div>
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
            const openBtn = document.getElementById('openAssistantModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('assistantForm');
            const methodInput = document.getElementById('assistantFormMethod');
            const editingInput = document.getElementById('assistantEditingId');
            const title = document.getElementById('assistantModalTitle');
            const nameInput = document.getElementById('assistantName');
            const instructionsInput = document.getElementById('assistantInstructions');
            const storeRoute = "{{ route('agencia.assistant.store') }}";
            const baseUrl = "{{ url('/agencia/assistant') }}";
            const hasErrors = @json($errors->any());
            const sessionEditingId = @json(old('editing_id'));
            const oldName = @json(old('name'));
            const oldInstructions = @json(old('instructions'));
            const dropdown = document.getElementById('promptHelpDropdown');
            const dropdownMenu = document.getElementById('promptHelpDropdownMenu');
            const typeButtons = Array.from(document.querySelectorAll('[data-ph-type-btn]'));
            const typeBlocks = Array.from(document.querySelectorAll('[data-ph-type-block]'));
            const sectionToggles = Array.from(document.querySelectorAll('[data-ph-section-toggle]'));
            const sectionContents = Array.from(document.querySelectorAll('[data-ph-section-content]'));
            const promptItems = Array.from(document.querySelectorAll('[data-prompt-help-item]'));
            const activeTypeClasses = ['border-blue-600', 'bg-blue-600', 'text-white'];
            const activeSectionClasses = ['bg-slate-100', 'text-slate-900'];
            let activeTypeId = null;

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
                title.textContent = 'Novo assistente';
                editingInput.value = '';
                nameInput.value = '';
                instructionsInput.value = '';
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
                    title.textContent = 'Editar assistente';
                    nameInput.value = button.dataset.name || '';
                    instructionsInput.value = button.dataset.instructions || '';
                    openModal();
                });
            });

            if (sessionEditingId) {
                resetForm();
                form.action = `${baseUrl}/${sessionEditingId}`;
                methodInput.value = 'PATCH';
                editingInput.value = sessionEditingId;
                title.textContent = 'Editar assistente';
                nameInput.value = oldName ?? '';
                instructionsInput.value = oldInstructions ?? '';
                openModal();
            } else if (hasErrors) {
                resetForm();
                nameInput.value = oldName ?? '';
                instructionsInput.value = oldInstructions ?? '';
                openModal();
            }

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
