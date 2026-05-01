@php
    $selectedAssistantLead = $chatAssistantLead ?? null;
    $selectedLead = $selectedAssistantLead?->lead;
    $selectedAssistant = $selectedAssistantLead?->assistant;
    $selectedConexao = $chatConexao ?? null;
    $selectedSendConexao = $chatSendConexao ?? null;
    $visibleMessages = $chatMessages ?? [];
    $leadCards = $chatLeadCards ?? collect();
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
                    <div class="px-4 py-5 text-sm text-slate-500">
                        Nenhum lead encontrado para os filtros atuais.
                    </div>
                @else
                    <div class="max-h-[72vh] overflow-y-auto p-2">
                        @foreach($leadCards as $card)
                            @php
                                $conversations = $card['conversations'] ?? collect();
                                $sendOptions = $card['send_options'] ?? collect();
                                $hasActiveConversation = $conversations->contains(fn ($conversation) => $conversation['is_active']);
                            @endphp
                            <div class="mb-3 rounded-2xl border px-4 py-3 {{ $hasActiveConversation ? 'border-emerald-300 bg-emerald-50 shadow-sm' : 'border-slate-100 bg-slate-50' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
                                            <span class="truncate text-sm font-semibold text-slate-800">{{ $card['name'] }}</span>
                                            <span class="text-xs text-slate-400">-</span>
                                            <span class="truncate text-xs text-slate-500">{{ $card['phone'] }}</span>
                                            <span class="text-xs text-slate-400">-</span>
                                            <span class="text-[11px] text-slate-400">{{ $card['last_message_at_label'] }}</span>
                                        </div>
                                        <p class="mt-2 truncate text-xs leading-relaxed text-slate-600">
                                            {{ $card['last_message_text'] }}
                                        </p>
                                    </div>
                                    @if($hasActiveConversation)
                                        <span class="rounded-full bg-emerald-500 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">Ativo</span>
                                    @endif
                                </div>

                                @if(!empty($card['tags']) && $card['tags']->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-1">
                                        @foreach($card['tags'] as $tag)
                                            <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-medium text-slate-500">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($conversations->isNotEmpty())
                                    <div class="mt-3 space-y-2">
                                        @foreach($conversations as $conversation)
                                            <a
                                                href="{{ $conversation['url'] }}"
                                                class="block rounded-xl border px-3 py-2 text-xs transition {{ $conversation['is_active'] ? 'border-emerald-300 bg-white text-emerald-800' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' }}"
                                            >
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-semibold">{{ $conversation['assistant'] }}</span>
                                                    <span class="text-[10px] text-slate-400">{{ $conversation['updated_at_label'] }}</span>
                                                </div>
                                                <div class="mt-1 truncate font-mono text-[10px] text-slate-400">{{ $conversation['conv_id'] }}</div>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mt-3 rounded-xl border border-dashed border-slate-300 bg-white px-3 py-3">
                                        <p class="text-xs font-semibold text-slate-700">Sem chat OpenAI</p>
                                        <p class="mt-1 text-[11px] text-slate-500">Escolha uma conexão para iniciar o envio.</p>
                                        <form class="mt-3 space-y-2" data-chat-send-form data-lead-id="{{ $card['lead_id'] }}">
                                            <select
                                                data-chat-conexao
                                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 focus:border-slate-400 focus:outline-none"
                                                @disabled($sendOptions->isEmpty())
                                            >
                                                <option value="">Conexão / Assistente</option>
                                                @foreach($sendOptions as $option)
                                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                                @endforeach
                                            </select>
                                            <textarea
                                                data-chat-message
                                                rows="2"
                                                maxlength="2000"
                                                placeholder="Digite a mensagem"
                                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 focus:border-slate-400 focus:outline-none"
                                                @disabled($sendOptions->isEmpty())
                                            ></textarea>
                                            <p class="hidden rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-[11px] text-rose-700" data-chat-error></p>
                                            <p class="hidden rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-700" data-chat-success></p>
                                            <button
                                                type="submit"
                                                class="w-full rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                                @disabled($sendOptions->isEmpty())
                                            >Enviar</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </aside>

        <section class="space-y-4 lg:col-span-3">
            @if($chatError)
                <div class="rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $chatError }}
                </div>
            @endif

            @if($canRenderConversation)
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-lg font-semibold text-slate-900">{{ $selectedLead?->name ?: 'Lead sem nome' }}</p>
                            <p class="text-sm text-slate-500">{{ $selectedLead?->phone ?: '-' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Assistente</p>
                            <p class="text-sm font-semibold text-slate-800">{{ $selectedAssistant?->name ?: '-' }}</p>
                            @if(($chatAssistantLeadMatchesCount ?? 0) > 1)
                                <p class="mt-1 text-[11px] text-amber-700">{{ $chatAssistantLeadMatchesCount }} vínculos encontrados; exibindo o mais recente</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">Chat</h3>
                            <p class="text-xs text-slate-500">
                                Mensagens visíveis: {{ count($visibleMessages) }}
                                @if($chatStatus)
                                    · Status {{ $chatStatus }}
                                @endif
                            </p>
                        </div>
                        <div class="text-xs text-slate-500">
                            @if($chatHasMore)
                                Mais mensagens disponíveis
                            @else
                                Sem mais mensagens
                            @endif
                        </div>
                    </div>

                    <div
                        id="cliente-chat-items"
                        class="space-y-3 bg-[#efeae2] px-4 py-5 sm:px-6"
                        data-conv-id="{{ $chatConvId }}"
                        data-after="{{ $chatLastId }}"
                        data-has-more="{{ $chatHasMore ? '1' : '0' }}"
                        data-visible-count="{{ count($visibleMessages) }}"
                        data-limit="{{ $chatLimit ?? '' }}"
                    >
                        <div class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm">
                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    id="cliente-chat-load-more-btn"
                                    type="button"
                                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                                    @if(!$chatHasMore) disabled @endif
                                >
                                    @if($chatHasMore)
                                        Carregar mais
                                    @else
                                        Sem mais mensagens
                                    @endif
                                </button>
                                <span id="cliente-chat-load-more-status" class="text-xs text-slate-500"></span>
                            </div>

                            <div id="cliente-chat-load-more-error" class="mt-3 hidden rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
                        </div>

                        @forelse($visibleMessages as $message)
                            <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[88%] rounded-2xl px-4 py-3 shadow-sm {{ $message['role'] === 'user' ? 'bg-emerald-500 text-white' : 'bg-white text-slate-800' }}">
                                    <div class="mb-1 text-[11px] font-semibold uppercase tracking-wide {{ $message['role'] === 'user' ? 'text-emerald-50/90' : 'text-slate-400' }}">
                                        {{ $message['sender'] }}
                                    </div>
                                    <div class="whitespace-pre-wrap break-words text-sm leading-relaxed">
                                        {!! $message['html'] !!}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div
                                id="cliente-chat-empty-state"
                                class="rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-5 text-center text-sm text-slate-500"
                            >
                                Nenhuma mensagem de lead ou assistente retornada.
                            </div>
                        @endforelse
                    </div>
                </div>

                <form
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                    data-chat-send-form
                    data-lead-id="{{ $selectedLead?->id }}"
                    data-conexao-id="{{ $selectedSendConexao?->id }}"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800">Enviar mensagem</h4>
                            <p class="text-xs text-slate-500">
                                @if($selectedSendConexao)
                                    Envio via {{ $selectedSendConexao->name ?: 'Conexao #' . $selectedSendConexao->id }}.
                                @else
                                    Nenhuma conexão ativa disponível para este assistente.
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <textarea
                            data-chat-message
                            rows="3"
                            maxlength="2000"
                            placeholder="Digite a mensagem"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                            @disabled(!$selectedSendConexao)
                        ></textarea>
                    </div>
                    <p class="mt-3 hidden rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-xs text-rose-700" data-chat-error></p>
                    <p class="mt-3 hidden rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs text-emerald-700" data-chat-success></p>
                    <div class="mt-3 flex justify-end">
                        <button
                            type="submit"
                            class="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            @disabled(!$selectedSendConexao)
                        >Enviar</button>
                    </div>
                </form>
            @elseif(!$chatError)
                <div class="rounded-[28px] border border-slate-200 bg-white px-6 py-10 shadow-sm">
                    <div class="mx-auto max-w-xl text-center">
                        <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Chat</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Selecione uma conversa</h3>
                        <p class="mt-3 text-sm text-slate-500">
                            Escolha um lead na coluna ao lado para abrir uma conversa existente ou envie uma mensagem para iniciar o atendimento.
                        </p>
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sendMessageUrlTemplate = @json(route('cliente.conversas.send-message', ['clienteLead' => '__LEAD_ID__']));
            const chatEndpoint = @json(route('cliente.conversas.index'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
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

            document.querySelectorAll('[data-chat-send-form]').forEach((form) => {
                form.addEventListener('submit', async (event) => {
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
                    } catch (error) {
                        showInline(errorEl, error.message || 'Não foi possível enviar a mensagem.');
                    } finally {
                        if (button) {
                            button.disabled = false;
                            button.textContent = 'Enviar';
                        }
                    }
                });
            });

            const loadMoreBtn = document.getElementById('cliente-chat-load-more-btn');
            const container = document.getElementById('cliente-chat-items');
            const statusEl = document.getElementById('cliente-chat-load-more-status');
            const errorEl = document.getElementById('cliente-chat-load-more-error');
            const emptyState = document.getElementById('cliente-chat-empty-state');

            if (!loadMoreBtn || !container) {
                return;
            }

            let after = container.dataset.after || null;
            let hasMore = container.dataset.hasMore === '1';
            let visibleCount = Number.parseInt(container.dataset.visibleCount || '0', 10);
            const limit = container.dataset.limit || null;

            const appendMessage = (message) => {
                if (!message || !['user', 'assistant'].includes(message.role)) {
                    return;
                }

                emptyState?.remove();
                visibleCount += 1;
                container.dataset.visibleCount = String(visibleCount);

                const wrapper = document.createElement('div');
                wrapper.className = `flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`;

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
                container.appendChild(wrapper);
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
                if (!hasMore) {
                    return;
                }

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
                    messages.forEach(appendMessage);
                    hasMore = !!payload.has_more;
                    after = payload.last_id || after;
                    container.dataset.after = after || '';
                    container.dataset.hasMore = hasMore ? '1' : '0';

                    updateButton(messages.length === 0 ? 'Nenhuma nova mensagem visivel nesta pagina.' : '');
                } catch (error) {
                    showLoadError('Erro inesperado ao carregar mais mensagens.');
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Tentar novamente';
                }
            });

            updateButton();
        });
    </script>
@endpush
