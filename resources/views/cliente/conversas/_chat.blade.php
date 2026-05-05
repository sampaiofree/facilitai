@php
    $selectedAssistantLead = $chatAssistantLead ?? null;
    $selectedLead = $selectedAssistantLead?->lead;
    $selectedAssistant = $selectedAssistantLead?->assistant;
    $selectedConexao = $chatConexao ?? null;
    $selectedSendConexao = $chatSendConexao ?? null;
    $visibleMessages = $chatMessages ?? [];
    $leadCards = $chatLeadCards ?? collect();
    $leadCardsHasMore = $chatLeadCardsHasMore ?? false;
    $leadCardsNextOffset = $chatLeadCardsNextOffset ?? $leadCards->count();
    $leadCardsLimit = $chatLeadCardsLimit ?? 20;
    $canRenderConversation = !empty($chatConvId) && !empty($selectedAssistantLead) && empty($chatError) && $chatResult !== null;
@endphp

<div class="mt-4 space-y-4">
    <form method="GET" action="{{ route('cliente.conversas.index') }}" class="flex flex-wrap items-center gap-3">
        <input type="hidden" name="tab" value="chat">
        @foreach((array) request('assistant_id', []) as $assistantId)
            <input type="hidden" name="assistant_id[]" value="{{ $assistantId }}">
        @endforeach
        @foreach((array) request('tags', []) as $tagId)
            <input type="hidden" name="tags[]" value="{{ $tagId }}">
        @endforeach
        @if(request('date_start'))
            <input type="hidden" name="date_start" value="{{ request('date_start') }}">
        @endif
        @if(request('date_end'))
            <input type="hidden" name="date_end" value="{{ request('date_end') }}">
        @endif

        <div class="min-w-[240px] flex-1">
            <input
                type="search"
                name="q"
                value="{{ request('q') }}"
                placeholder="Buscar por nome ou telefone"
                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm focus:border-slate-400 focus:outline-none"
            >
        </div>
        <button type="submit" class="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Buscar</button>
        <a href="{{ route('cliente.conversas.index', ['tab' => 'chat']) }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50">Limpar</a>
    </form>

    <div class="grid gap-6 lg:grid-cols-4">
        <aside class="space-y-4 lg:col-span-1">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-4">
                    <h3 class="text-sm font-semibold text-slate-800">Leads</h3>
                    <p class="text-xs text-slate-500">Filtrados pelos mesmos critérios da lista.</p>
                </div>

                @if($leadCards->isEmpty())
                    <div id="cliente-chat-cards-empty" class="px-4 py-5 text-sm text-slate-500">
                        Nenhum lead encontrado para os filtros atuais.
                    </div>
                @endif

                <div
                    id="cliente-chat-cards-list"
                    class="max-h-[72vh] overflow-y-auto p-2 {{ $leadCards->isEmpty() ? 'hidden' : '' }}"
                    data-next-offset="{{ $leadCardsNextOffset }}"
                    data-has-more="{{ $leadCardsHasMore ? '1' : '0' }}"
                    data-limit="{{ $leadCardsLimit }}"
                >
                    @include('cliente.conversas._chat_cards', ['leadCards' => $leadCards])
                    <div id="cliente-chat-cards-sentinel" class="{{ $leadCardsHasMore ? '' : 'hidden' }} py-2 text-center text-xs text-slate-400">
                        Carregando mais contatos...
                    </div>
                    <div id="cliente-chat-cards-status" class="px-2 py-2 text-center text-xs text-slate-400"></div>
                    <div id="cliente-chat-cards-error" class="mx-2 mb-2 hidden rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs text-rose-700"></div>
                </div>
            </div>
        </aside>

        @include('cliente.conversas._chat_panel')
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sendMessageUrlTemplate = @json(route('cliente.conversas.send-message', ['clienteLead' => '__LEAD_ID__']));
            const chatEndpoint = @json(route('cliente.conversas.index'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            let triggerEarlyChatPoll = () => {};

            const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            };

            const escapeSelectorValue = (value) => {
                const rawValue = String(value ?? '');

                if (window.CSS && typeof window.CSS.escape === 'function') {
                    return window.CSS.escape(rawValue);
                }

                return rawValue.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
            };

            const showInline = (element, message) => {
                if (!element) {
                    return;
                }

                element.textContent = message;
                element.classList.remove('hidden');
            };

            const hideInline = (element) => {
                if (!element) {
                    return;
                }

                element.textContent = '';
                element.classList.add('hidden');
            };

            document.addEventListener('submit', async (event) => {
                const form = event.target.closest('[data-chat-send-form]');
                if (!form) {
                    return;
                }

                event.preventDefault();

                const leadId = form.dataset.leadId || '';
                const select = form.querySelector('[data-chat-conexao]');
                const textarea = form.querySelector('[data-chat-message]');
                const errorEl = form.querySelector('[data-chat-error]');
                const successEl = form.querySelector('[data-chat-success]');
                const button = form.querySelector('button[type="submit"]');
                const conexaoId = select ? select.value.trim() : (form.dataset.conexaoId || '').trim();
                const mensagem = textarea?.value?.trim() || '';

                hideInline(errorEl);
                hideInline(successEl);

                if (!leadId) {
                    showInline(errorEl, 'Lead nao encontrado para envio.');
                    return;
                }

                if (!conexaoId) {
                    showInline(errorEl, 'Selecione uma conexão antes de enviar.');
                    return;
                }

                if (!mensagem) {
                    showInline(errorEl, 'Informe a mensagem antes de enviar.');
                    return;
                }

                if (button) {
                    button.disabled = true;
                    button.textContent = 'Enviando...';
                }

                try {
                    const response = await fetch(sendMessageUrlTemplate.replace('__LEAD_ID__', leadId), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            conexao_id: conexaoId,
                            mensagem,
                        }),
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'Não foi possível enviar a mensagem.');
                    }

                    showInline(successEl, payload.message || 'Mensagem enviada para a fila.');
                    if (textarea) {
                        textarea.value = '';
                    }
                    triggerEarlyChatPoll();
                } catch (error) {
                    showInline(errorEl, error.message || 'Não foi possível enviar a mensagem.');
                } finally {
                    if (button) {
                        button.disabled = false;
                        button.textContent = 'Enviar';
                    }
                }
            });

            const cardsList = document.getElementById('cliente-chat-cards-list');
            const cardsSentinel = document.getElementById('cliente-chat-cards-sentinel');
            const cardsStatus = document.getElementById('cliente-chat-cards-status');
            const cardsError = document.getElementById('cliente-chat-cards-error');
            const cardsEmpty = document.getElementById('cliente-chat-cards-empty');

            if (cardsList && cardsSentinel) {
                let cardsHasMore = cardsList.dataset.hasMore === '1';
                let cardsNextOffset = Number.parseInt(cardsList.dataset.nextOffset || '0', 10);
                let cardsLoading = false;
                const cardsLimit = cardsList.dataset.limit || '20';

                const setCardsError = (message = '') => {
                    if (!cardsError) {
                        return;
                    }

                    cardsError.textContent = message;
                    cardsError.classList.toggle('hidden', message === '');
                };

                const loadMoreCards = async () => {
                    if (!cardsHasMore || cardsLoading) {
                        return;
                    }

                    cardsLoading = true;
                    setCardsError('');
                    cardsSentinel.classList.remove('hidden');
                    if (cardsStatus) {
                        cardsStatus.textContent = '';
                    }

                    try {
                        const params = new URLSearchParams(window.location.search);
                        params.set('tab', 'chat');
                        params.set('cards_only', '1');
                        params.set('cards_offset', String(cardsNextOffset));
                        params.set('cards_limit', cardsLimit);
                        params.delete('after');
                        params.delete('limit');
                        params.delete('poll_state');

                        const response = await fetch(`${chatEndpoint}?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const payload = await response.json();

                        if (!response.ok) {
                            throw new Error(payload.message || 'Falha ao carregar mais contatos.');
                        }

                        if (payload.html) {
                            cardsEmpty?.remove();
                            cardsList.classList.remove('hidden');
                            cardsSentinel.insertAdjacentHTML('beforebegin', payload.html);
                        }

                        cardsHasMore = !!payload.has_more;
                        cardsNextOffset = Number.parseInt(payload.next_offset || cardsNextOffset, 10);
                        cardsList.dataset.hasMore = cardsHasMore ? '1' : '0';
                        cardsList.dataset.nextOffset = String(cardsNextOffset);

                        if (!cardsHasMore) {
                            cardsSentinel.classList.add('hidden');
                            if (cardsStatus) {
                                cardsStatus.textContent = payload.count > 0 ? 'Todos os contatos foram carregados.' : '';
                            }
                        }
                    } catch (error) {
                        setCardsError(error.message || 'Falha ao carregar mais contatos.');
                        cardsSentinel.classList.add('hidden');
                    } finally {
                        cardsLoading = false;
                    }
                };

                if ('IntersectionObserver' in window) {
                    const observer = new IntersectionObserver((entries) => {
                        if (entries.some((entry) => entry.isIntersecting)) {
                            loadMoreCards();
                        }
                    }, {
                        root: cardsList,
                        rootMargin: '120px 0px',
                    });

                    observer.observe(cardsSentinel);
                } else {
                    cardsList.addEventListener('scroll', () => {
                        const nearBottom = cardsList.scrollTop + cardsList.clientHeight >= cardsList.scrollHeight - 120;
                        if (nearBottom) {
                            loadMoreCards();
                        }
                    });
                }
            }

            let chatPanelState = null;

            const splitClasses = (value) => (value || '').split(/\s+/).filter(Boolean);
            const addClasses = (element, value) => splitClasses(value).forEach((className) => element.classList.add(className));
            const removeClasses = (element, value) => splitClasses(value).forEach((className) => element.classList.remove(className));

            const destroyChatPanelState = () => {
                if (!chatPanelState) {
                    return;
                }

                window.clearTimeout(chatPanelState.pollTimer);
                window.clearInterval(chatPanelState.countdownTimer);
                if (chatPanelState.visibilityHandler) {
                    document.removeEventListener('visibilitychange', chatPanelState.visibilityHandler);
                }
                chatPanelState = null;
                triggerEarlyChatPoll = () => {};
            };

            const setActiveConversation = (convId) => {
                document.querySelectorAll('[data-chat-conversation-link]').forEach((link) => {
                    removeClasses(link, link.dataset.activeConversationClass);
                    addClasses(link, link.dataset.inactiveConversationClass);
                });

                document.querySelectorAll('[data-chat-card]').forEach((card) => {
                    removeClasses(card, card.dataset.activeCardClass);
                    addClasses(card, card.dataset.inactiveCardClass);
                    card.querySelector('[data-chat-active-badge]')?.classList.add('hidden');
                });

                const activeLink = document.querySelector(`[data-chat-conversation-link][data-conv-id="${escapeSelectorValue(convId)}"]`);
                if (!activeLink) {
                    return;
                }

                removeClasses(activeLink, activeLink.dataset.inactiveConversationClass);
                addClasses(activeLink, activeLink.dataset.activeConversationClass);

                const activeCard = activeLink.closest('[data-chat-card]');
                if (activeCard) {
                    removeClasses(activeCard, activeCard.dataset.inactiveCardClass);
                    addClasses(activeCard, activeCard.dataset.activeCardClass);
                    activeCard.querySelector('[data-chat-active-badge]')?.classList.remove('hidden');
                }
            };

            const initializeClienteChatPanel = () => {
                destroyChatPanelState();

                const loadMoreBtn = document.getElementById('cliente-chat-load-more-btn');
                const container = document.getElementById('cliente-chat-items');
                const statusEl = document.getElementById('cliente-chat-load-more-status');
                const errorEl = document.getElementById('cliente-chat-load-more-error');
                const pollTimerEl = document.getElementById('cliente-chat-poll-timer');

                if (!loadMoreBtn || !container) {
                    return;
                }

                let after = container.dataset.after || null;
                let hasMore = container.dataset.hasMore === '1';
                let visibleCount = Number.parseInt(container.dataset.visibleCount || '0', 10);
                let messageLoadInFlight = false;
                let refreshInFlight = false;
                let pollInFlight = false;
                let pollDelay = 15000;
                let nextPollAt = null;
                let pollTimerMode = 'scheduled';
                let knownAssistantLeadUpdatedAt = container.dataset.assistantLeadUpdatedAt || '';
                const limit = container.dataset.limit || null;
                const renderedMessageIds = new Set(
                    Array.from(container.querySelectorAll('[data-chat-message-id]'))
                        .map((element) => element.dataset.chatMessageId || '')
                        .filter((id) => id !== '')
                );

                const buildMessageElement = (message, options = {}) => {
                    if (!message || !['user', 'assistant'].includes(message.role)) {
                        return null;
                    }

                    const messageId = typeof message.id === 'string' ? message.id : '';
                    if ((options.dedupe ?? true) && messageId !== '' && renderedMessageIds.has(messageId)) {
                        return null;
                    }

                    document.getElementById('cliente-chat-empty-state')?.remove();

                    const wrapper = document.createElement('div');
                    wrapper.className = `flex ${message.role === 'user' ? 'justify-start' : 'justify-end'}`;
                    wrapper.dataset.chatMessageRole = message.role;
                    if (messageId !== '') {
                        wrapper.dataset.chatMessageId = messageId;
                        renderedMessageIds.add(messageId);
                    }

                    const bubble = document.createElement('div');
                    bubble.className = `max-w-[88%] rounded-2xl px-4 py-3 shadow-sm ${message.role === 'user' ? 'bg-emerald-500 text-white' : 'bg-white text-slate-800'}`;

                    const label = document.createElement('div');
                    label.className = `mb-1 text-[11px] font-semibold uppercase tracking-wide ${message.role === 'user' ? 'text-emerald-50/90' : 'text-slate-400'}`;
                    label.textContent = message.sender || (message.role === 'user' ? 'Lead' : 'Assistente');

                    const content = document.createElement('div');
                    content.className = 'whitespace-pre-wrap break-words text-sm leading-relaxed';
                    content.innerHTML = typeof message.html === 'string' ? message.html : escapeHtml(message.text || '');

                    bubble.appendChild(label);
                    bubble.appendChild(content);
                    wrapper.appendChild(bubble);

                    return wrapper;
                };

                const appendMessage = (message, options = {}) => {
                    const wrapper = buildMessageElement(message, options);
                    if (!wrapper) {
                        return false;
                    }

                    visibleCount += 1;
                    container.dataset.visibleCount = String(visibleCount);
                    container.appendChild(wrapper);

                    return true;
                };

                const prependMessages = (messages) => {
                    if (!Array.isArray(messages) || messages.length === 0) {
                        return 0;
                    }

                    const scrollBefore = container.scrollTop;
                    const heightBefore = container.scrollHeight;
                    const anchor = container.querySelector('[data-chat-message-id], [data-chat-message-role]');
                    let inserted = 0;

                    messages.forEach((message) => {
                        const wrapper = buildMessageElement(message, { dedupe: true });
                        if (!wrapper) {
                            return;
                        }

                        visibleCount += 1;
                        inserted += 1;
                        container.dataset.visibleCount = String(visibleCount);
                        container.insertBefore(wrapper, anchor);
                    });

                    if (inserted > 0) {
                        const heightAfter = container.scrollHeight;
                        container.scrollTop = scrollBefore + (heightAfter - heightBefore);
                    }

                    return inserted;
                };

                const updateButton = (extraStatus = '') => {
                    if (hasMore) {
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.textContent = 'Carregar mais';
                        if (statusEl) {
                            statusEl.textContent = extraStatus;
                        }
                        return;
                    }

                    loadMoreBtn.disabled = true;
                    loadMoreBtn.textContent = 'Sem mais mensagens';
                    if (statusEl) {
                        statusEl.textContent = extraStatus || 'Todas as paginas foram carregadas.';
                    }
                };

                const showLoadError = (message) => {
                    if (!errorEl) {
                        return;
                    }

                    errorEl.textContent = message;
                    errorEl.classList.remove('hidden');
                };

                const clearLoadError = () => {
                    if (!errorEl) {
                        return;
                    }

                    errorEl.textContent = '';
                    errorEl.classList.add('hidden');
                };

                loadMoreBtn.addEventListener('click', async () => {
                    if (!hasMore || messageLoadInFlight) {
                        return;
                    }

                    messageLoadInFlight = true;
                    clearLoadError();
                    loadMoreBtn.disabled = true;
                    loadMoreBtn.textContent = 'Carregando...';

                    try {
                        const params = new URLSearchParams();
                        params.set('tab', 'chat');
                        params.set('conv_id', container.dataset.convId || '');
                        if (after) {
                            params.set('after', after);
                        }
                        if (limit) {
                            params.set('limit', limit);
                        }

                        const response = await fetch(`${chatEndpoint}?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });

                        const payload = await response.json();
                        if (!response.ok || payload.error) {
                            showLoadError(payload.error || 'Falha ao carregar mais mensagens.');
                            loadMoreBtn.disabled = false;
                            loadMoreBtn.textContent = 'Tentar novamente';
                            return;
                        }

                        const messages = Array.isArray(payload.messages) ? payload.messages : [];
                        const insertedCount = prependMessages(messages.slice().reverse());
                        hasMore = !!payload.has_more;
                        after = payload.last_id || after;
                        container.dataset.after = after || '';
                        container.dataset.hasMore = hasMore ? '1' : '0';

                        updateButton(insertedCount === 0 ? 'Nenhuma nova mensagem visivel nesta pagina.' : '');
                    } catch (error) {
                        showLoadError('Erro inesperado ao carregar mais mensagens.');
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.textContent = 'Tentar novamente';
                    } finally {
                        messageLoadInFlight = false;
                    }
                });

                updateButton();

                const refreshVisibleMessages = async () => {
                    if (refreshInFlight || messageLoadInFlight) {
                        return;
                    }

                    refreshInFlight = true;

                    try {
                        const params = new URLSearchParams();
                        params.set('tab', 'chat');
                        params.set('conv_id', container.dataset.convId || '');
                        params.set('limit', '20');

                        const response = await fetch(`${chatEndpoint}?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const payload = await response.json();

                        if (!response.ok || payload.error) {
                            throw new Error(payload.error || 'Falha ao atualizar o chat.');
                        }

                        const messages = Array.isArray(payload.messages) ? payload.messages : [];
                        messages.slice().reverse().forEach((message) => appendMessage(message, { dedupe: true }));

                        if (payload.assistant_lead_updated_at) {
                            knownAssistantLeadUpdatedAt = payload.assistant_lead_updated_at;
                            container.dataset.assistantLeadUpdatedAt = knownAssistantLeadUpdatedAt;
                        }
                    } finally {
                        refreshInFlight = false;
                    }
                };

                const setPollTimerText = (text) => {
                    if (!pollTimerEl) {
                        return;
                    }

                    pollTimerEl.textContent = text;
                };

                const updatePollCountdown = () => {
                    if (!pollTimerEl) {
                        return;
                    }

                    if (document.hidden) {
                        setPollTimerText('Verificação pausada até a aba ficar ativa');
                        return;
                    }

                    if (pollTimerMode === 'checking') {
                        setPollTimerText('Verificando novas mensagens...');
                        return;
                    }

                    if (!nextPollAt) {
                        setPollTimerText(pollTimerEl.dataset.defaultLabel || 'Próxima verificação em 15s');
                        return;
                    }

                    const seconds = Math.max(0, Math.ceil((nextPollAt - Date.now()) / 1000));
                    const prefix = pollTimerMode === 'retry' ? 'Nova tentativa em' : 'Próxima verificação em';
                    setPollTimerText(`${prefix} ${seconds}s`);
                };

                const schedulePoll = (delay = pollDelay, mode = 'scheduled') => {
                    window.clearTimeout(chatPanelState?.pollTimer);
                    pollTimerMode = mode;
                    nextPollAt = Date.now() + delay;
                    updatePollCountdown();
                    chatPanelState.pollTimer = window.setTimeout(pollChatState, delay);
                };

                const pollChatState = async () => {
                    if (!container.dataset.convId || document.hidden || messageLoadInFlight || refreshInFlight || pollInFlight) {
                        schedulePoll();
                        return;
                    }

                    pollInFlight = true;
                    pollTimerMode = 'checking';
                    updatePollCountdown();

                    try {
                        const params = new URLSearchParams();
                        params.set('tab', 'chat');
                        params.set('conv_id', container.dataset.convId || '');
                        params.set('poll_state', '1');

                        const response = await fetch(`${chatEndpoint}?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const payload = await response.json();

                        if (!response.ok || payload.error) {
                            throw new Error(payload.error || 'Falha ao verificar atualizações.');
                        }

                        const updatedAt = payload.assistant_lead_updated_at || '';
                        if (updatedAt !== '' && updatedAt !== knownAssistantLeadUpdatedAt) {
                            knownAssistantLeadUpdatedAt = updatedAt;
                            container.dataset.assistantLeadUpdatedAt = updatedAt;
                            await refreshVisibleMessages();
                        }

                        pollDelay = 15000;
                    } catch (error) {
                        pollDelay = Math.min(pollDelay * 2, 60000);
                    } finally {
                        const nextMode = pollDelay > 15000 ? 'retry' : 'scheduled';
                        pollInFlight = false;
                        schedulePoll(pollDelay, nextMode);
                    }
                };

                const visibilityHandler = () => {
                    if (!document.hidden) {
                        triggerEarlyChatPoll();
                    } else {
                        updatePollCountdown();
                    }
                };

                chatPanelState = {
                    pollTimer: null,
                    countdownTimer: window.setInterval(updatePollCountdown, 1000),
                    visibilityHandler,
                };

                triggerEarlyChatPoll = () => {
                    pollDelay = 15000;
                    schedulePoll(3000, 'scheduled');
                };

                document.addEventListener('visibilitychange', visibilityHandler);
                schedulePoll();
            };

            const renderChatPanelError = (message) => {
                const panel = document.getElementById('cliente-chat-panel');
                if (!panel) {
                    return;
                }

                panel.innerHTML = `<div class="rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">${escapeHtml(message || 'Falha ao carregar conversa.')}</div>`;
            };

            const loadClienteChatPanel = async (url, pushHistory = true) => {
                const currentPanel = document.getElementById('cliente-chat-panel');
                if (!currentPanel) {
                    window.location.href = url;
                    return;
                }

                const requestUrl = new URL(url, window.location.origin);
                requestUrl.searchParams.set('tab', 'chat');
                requestUrl.searchParams.set('panel_only', '1');
                requestUrl.searchParams.delete('after');
                requestUrl.searchParams.delete('limit');
                requestUrl.searchParams.delete('poll_state');
                requestUrl.searchParams.delete('cards_only');

                currentPanel.classList.add('opacity-60');

                try {
                    const response = await fetch(requestUrl.toString(), {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));

                    if (!payload.html) {
                        throw new Error(payload.error || 'Falha ao carregar conversa.');
                    }

                    destroyChatPanelState();
                    currentPanel.outerHTML = payload.html;
                    initializeClienteChatPanel();

                    if (!payload.error) {
                        setActiveConversation(payload.conv_id || requestUrl.searchParams.get('conv_id') || '');
                    }

                    if (pushHistory && !payload.error) {
                        const historyUrl = new URL(requestUrl.toString());
                        historyUrl.searchParams.delete('panel_only');
                        window.history.pushState({ clienteChatConvId: payload.conv_id || null }, '', historyUrl.toString());
                    }
                } catch (error) {
                    destroyChatPanelState();
                    currentPanel.classList.remove('opacity-60');
                    renderChatPanelError(error.message || 'Falha ao carregar conversa.');
                }
            };

            document.addEventListener('click', (event) => {
                const link = event.target.closest('[data-chat-conversation-link]');
                if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                event.preventDefault();
                loadClienteChatPanel(link.href, true);
            });

            window.addEventListener('popstate', () => {
                const url = new URL(window.location.href);
                if (url.searchParams.get('tab') !== 'chat') {
                    return;
                }

                loadClienteChatPanel(url.toString(), false);
            });

            initializeClienteChatPanel();
        });
    </script>
@endpush
