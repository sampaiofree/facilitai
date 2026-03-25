@extends('layouts.adm')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Assistants</h2>
            <p class="text-sm text-slate-500">Visualize os assistants cadastrados e abra os prompts completos em tela cheia.</p>
        </div>
    </div>

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
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 text-slate-700">{{ $assistant->user?->name ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $assistant->cliente?->nome ?? '-' }}</td>
                        <td class="px-5 py-4 font-medium text-slate-900">{{ $assistant->name }}</td>
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
                    <h3 class="text-lg font-semibold text-slate-900">Detalhes do Assistant</h3>
                    <p class="text-sm text-slate-500">Resumo do cadastro e prompts completos do registro selecionado.</p>
                </div>
                <button
                    type="button"
                    data-close-modal
                    class="rounded-full border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-500 hover:border-slate-400 hover:text-slate-700"
                >Fechar</button>
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

            const loadingState = document.getElementById('assistantModalLoading');
            const errorState = document.getElementById('assistantModalError');
            const errorMessage = document.getElementById('assistantModalErrorMessage');
            const contentState = document.getElementById('assistantModalContent');
            const textCards = document.getElementById('assistantTextCards');
            const closeButtons = modal.querySelectorAll('[data-close-modal]');
            const openButtons = document.querySelectorAll('[data-show-url]');
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
            let lastActiveElement = null;
            let requestSequence = 0;

            const openModal = () => {
                lastActiveElement = document.activeElement;
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                modal.querySelector('[data-close-modal]')?.focus();
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');

                if (lastActiveElement instanceof HTMLElement) {
                    lastActiveElement.focus();
                }
            };

            const showLoading = () => {
                loadingState.classList.remove('hidden');
                errorState.classList.add('hidden');
                contentState.classList.add('hidden');
                errorMessage.textContent = '';
                textCards.innerHTML = '';
            };

            const showError = (message) => {
                loadingState.classList.add('hidden');
                contentState.classList.add('hidden');
                errorState.classList.remove('hidden');
                errorMessage.textContent = message;
            };

            const showContent = () => {
                loadingState.classList.add('hidden');
                errorState.classList.add('hidden');
                contentState.classList.remove('hidden');
            };

            const hasContent = (value) => typeof value === 'string'
                ? value.trim() !== ''
                : value !== null && value !== undefined;

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

            const loadAssistant = async (url) => {
                requestSequence += 1;
                const currentRequest = requestSequence;

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
                        throw new Error('Falha ao carregar os detalhes do assistant.');
                    }

                    const payload = await response.json();
                    if (currentRequest !== requestSequence) {
                        return;
                    }

                    fillSummary(payload.summary ?? {});
                    renderTexts(payload.texts ?? {});
                    showContent();
                } catch (error) {
                    if (currentRequest !== requestSequence) {
                        return;
                    }

                    showError(error instanceof Error ? error.message : 'Falha ao carregar os detalhes do assistant.');
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
        })();
    </script>
@endpush
