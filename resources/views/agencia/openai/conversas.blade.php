@extends('layouts.agencia')

@section('content')
    @php
        $lead = $assistantLead?->lead;
        $assistant = $assistantLead?->assistant;
        $visibleMessages = $messages ?? [];
        $connections = $conexoes ?? collect();
        $selectedConnection = $selectedConexao ?? null;
        $connectionLeads = $sidebarLeads ?? collect();
        $canRenderConversation = !empty($convId) && !empty($assistantLead) && empty($error) && $result !== null;
    @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">OpenAI / Conversas</h2>
            <p class="text-sm text-slate-500">Selecione uma conexão e escolha um lead para abrir a conversa.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
            @if($selectedConnection)
                <span class="rounded-full border border-slate-200 bg-white px-4 py-2 shadow-sm">
                    Conexão: <span class="font-semibold text-slate-800">{{ $selectedConnection->name ?: 'Conexão #' . $selectedConnection->id }}</span>
                </span>
            @endif
            @if(!empty($convId))
                <span class="rounded-full border border-slate-200 bg-white px-4 py-2 shadow-sm">
                    Conv ID: <span class="font-mono font-semibold text-slate-800">{{ $convId }}</span>
                </span>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-4">
        <aside class="space-y-4 lg:col-span-1">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-slate-800">Conexões</h3>
                    <p class="text-xs text-slate-500">A conexão define o cliente e o assistente usados na lista lateral.</p>
                </div>

                @if($connections->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                        Nenhuma conexão válida disponível para esta tela.
                    </div>
                @else
                    <form id="conexao-select-form" method="GET" action="{{ route('agencia.openai.conversas') }}">
                        <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" for="conexao-select">Conexão</label>
                        <select
                            id="conexao-select"
                            name="conexao_id"
                            class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            @foreach($connections as $connectionOption)
                                <option value="{{ $connectionOption->id }}" @selected($selectedConnection?->id === $connectionOption->id)>
                                    {{ $connectionOption->name ?: 'Conexão #' . $connectionOption->id }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    @if($selectedConnection)
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Resumo</p>
                            <p class="mt-2 text-sm font-semibold text-slate-800">{{ $selectedConnection->cliente?->nome ?: '-' }}</p>
                            <p class="text-xs text-slate-500">Assistente: {{ $selectedConnection->assistant?->name ?: '-' }}</p>
                        </div>
                    @endif
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-4">
                    <h3 class="text-sm font-semibold text-slate-800">Leads</h3>
                    <p class="text-xs text-slate-500">Ordenados pela última atualização do lead.</p>
                </div>

                @if(!$selectedConnection)
                    <div class="px-4 py-5 text-sm text-slate-500">
                        Selecione uma conexão para visualizar os leads.
                    </div>
                @elseif($connectionLeads->isEmpty())
                    <div class="px-4 py-5 text-sm text-slate-500">
                        Esta conexão ainda não possui leads com `conv_id`.
                    </div>
                @else
                    <div class="max-h-[68vh] overflow-y-auto p-2">
                        @foreach($connectionLeads as $connectionLead)
                            <a
                                href="{{ $connectionLead['url'] }}"
                                class="mb-2 block rounded-2xl border px-4 py-3 transition {{ $connectionLead['is_active'] ? 'border-emerald-300 bg-emerald-50 shadow-sm' : 'border-transparent bg-slate-50 hover:border-slate-200 hover:bg-white' }}"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800">{{ $connectionLead['name'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $connectionLead['phone'] }}</p>
                                    </div>
                                    @if($connectionLead['is_active'])
                                        <span class="rounded-full bg-emerald-500 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">Ativo</span>
                                    @endif
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-3 text-[11px] text-slate-400">
                                    <span>{{ $connectionLead['updated_at_label'] }}</span>
                                    <span class="truncate font-mono text-slate-500">{{ $connectionLead['conv_id'] }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </aside>

        <section class="space-y-4 lg:col-span-3">
            @if($error)
                <div class="rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $error }}
                </div>
            @endif

            @if($canRenderConversation)
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-lg font-semibold text-slate-900">{{ $lead?->name ?: 'Lead sem nome' }}</p>
                            <p class="text-sm text-slate-500">{{ $lead?->phone ?: '-' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Assistente</p>
                            <p class="text-sm font-semibold text-slate-800">{{ $assistant?->name ?: '-' }}</p>
                            @if(($assistantLeadMatchesCount ?? 0) > 1)
                                <p class="mt-1 text-[11px] text-amber-700">{{ $assistantLeadMatchesCount }} vínculos encontrados; exibindo o mais recente</p>
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
                                @if($status)
                                    · Status {{ $status }}
                                @endif
                            </p>
                        </div>
                        <div class="text-xs text-slate-500">
                            @if($hasMore)
                                Mais itens disponíveis
                            @else
                                Sem mais itens
                            @endif
                        </div>
                    </div>

                    <div
                        id="conv-chat-items"
                        class="space-y-3 bg-[#efeae2] px-4 py-5 sm:px-6"
                        data-conv-id="{{ $convId }}"
                        data-after="{{ $lastId }}"
                        data-has-more="{{ $hasMore ? '1' : '0' }}"
                        data-visible-count="{{ count($visibleMessages) }}"
                        data-limit="{{ $limit ?? '' }}"
                    >
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
                                id="chat-empty-state"
                                class="rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-5 text-center text-sm text-slate-500"
                            >
                                Nenhuma mensagem de lead ou assistente retornada.
                            </div>
                        @endforelse
                    </div>

                    <div class="border-t border-slate-200 bg-white px-5 py-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                id="load-more-btn"
                                type="button"
                                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                                @if(!$hasMore) disabled @endif
                            >
                                @if($hasMore)
                                    Carregar mais
                                @else
                                    Sem mais itens
                                @endif
                            </button>
                            <span id="load-more-status" class="text-xs text-slate-500"></span>
                        </div>

                        <div id="load-more-error" class="mt-3 hidden rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>
                    </div>
                </div>
            @elseif($selectedConnection)
                <div class="rounded-[28px] border border-slate-200 bg-white px-6 py-10 shadow-sm">
                    <div class="mx-auto max-w-xl text-center">
                        <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Conversa</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Selecione um lead para visualizar a conversa</h3>
                        <p class="mt-3 text-sm text-slate-500">
                            Escolha um lead na coluna ao lado para abrir o `conv_id` desta conexão.
                        </p>
                        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-left">
                            <p class="text-xs uppercase tracking-wide text-slate-400">Conexão atual</p>
                            <p class="mt-2 text-sm font-semibold text-slate-800">{{ $selectedConnection->name ?: 'Conexão #' . $selectedConnection->id }}</p>
                            <p class="mt-1 text-xs text-slate-500">Cliente: {{ $selectedConnection->cliente?->nome ?: '-' }}</p>
                            <p class="text-xs text-slate-500">Assistente: {{ $selectedConnection->assistant?->name ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-[28px] border border-slate-200 bg-white px-6 py-10 shadow-sm">
                    <div class="mx-auto max-w-xl text-center">
                        <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Conexões</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Nenhuma conexão disponível</h3>
                        <p class="mt-3 text-sm text-slate-500">
                            Cadastre ou ajuste uma conexão com cliente, assistente e credencial para usar esta visualização.
                        </p>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const connectionSelect = document.getElementById('conexao-select');
            const connectionForm = document.getElementById('conexao-select-form');

            if (connectionSelect && connectionForm) {
                connectionSelect.addEventListener('change', () => {
                    connectionForm.submit();
                });
            }

            const loadMoreBtn = document.getElementById('load-more-btn');
            const container = document.getElementById('conv-chat-items');
            const statusEl = document.getElementById('load-more-status');
            const errorEl = document.getElementById('load-more-error');
            const emptyState = document.getElementById('chat-empty-state');

            if (!loadMoreBtn || !container) {
                return;
            }

            const endpoint = @json(route('agencia.openai.conversas'));
            let after = container.dataset.after || null;
            let hasMore = container.dataset.hasMore === '1';
            let visibleCount = Number.parseInt(container.dataset.visibleCount || '0', 10);
            const limit = container.dataset.limit || null;

            const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = value;
                return div.innerHTML;
            };

            const formatWhatsappText = (value) => {
                return escapeHtml(value).replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>');
            };

            const extractText = (item) => {
                if (!item || !Array.isArray(item.content)) {
                    return '';
                }

                const parts = [];
                item.content.forEach((contentItem) => {
                    if (!contentItem || typeof contentItem !== 'object') {
                        return;
                    }

                    const type = typeof contentItem.type === 'string' ? contentItem.type : '';
                    if (type && !['input_text', 'output_text'].includes(type)) {
                        return;
                    }

                    const rawText = typeof contentItem.text === 'string' ? contentItem.text : '';
                    const text = rawText.replace(/\r\n?/g, '\n').trim();
                    if (text) {
                        parts.push(text);
                    }
                });

                return parts.join('\n\n');
            };

            const toVisibleMessages = (items) => {
                if (!Array.isArray(items)) {
                    return [];
                }

                return items.map((item) => {
                    if (!item || item.type !== 'message') {
                        return null;
                    }

                    const role = item.role === 'assistant' ? 'assistant' : (item.role === 'user' ? 'user' : '');
                    if (!role) {
                        return null;
                    }

                    const text = extractText(item);
                    if (!text) {
                        return null;
                    }

                    return {
                        role,
                        sender: role === 'assistant' ? 'Assistente' : 'Lead',
                        html: role === 'assistant' ? formatWhatsappText(text) : escapeHtml(text),
                    };
                }).filter(Boolean);
            };

            const showError = (message) => {
                if (!errorEl) {
                    return;
                }

                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            };

            const clearError = () => {
                if (!errorEl) {
                    return;
                }

                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            };

            const appendMessage = (message) => {
                if (emptyState) {
                    emptyState.remove();
                }

                visibleCount += 1;
                container.dataset.visibleCount = String(visibleCount);

                const wrapper = document.createElement('div');
                wrapper.className = `flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`;

                const bubble = document.createElement('div');
                bubble.className = `max-w-[88%] rounded-2xl px-4 py-3 shadow-sm ${message.role === 'user' ? 'bg-emerald-500 text-white' : 'bg-white text-slate-800'}`;

                const label = document.createElement('div');
                label.className = `mb-1 text-[11px] font-semibold uppercase tracking-wide ${message.role === 'user' ? 'text-emerald-50/90' : 'text-slate-400'}`;
                label.textContent = message.sender;

                const content = document.createElement('div');
                content.className = 'whitespace-pre-wrap break-words text-sm leading-relaxed';
                content.innerHTML = message.html;

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
                loadMoreBtn.textContent = 'Sem mais itens';
                if (statusEl) {
                    statusEl.textContent = extraStatus || 'Todas as paginas foram carregadas.';
                }
            };

            loadMoreBtn.addEventListener('click', async () => {
                if (!hasMore) {
                    return;
                }

                clearError();
                loadMoreBtn.disabled = true;
                loadMoreBtn.textContent = 'Carregando...';

                try {
                    const params = new URLSearchParams();
                    params.set('conv_id', container.dataset.convId || '');
                    if (after) {
                        params.set('after', after);
                    }
                    if (limit) {
                        params.set('limit', limit);
                    }

                    const response = await fetch(`${endpoint}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    const payload = await response.json();
                    if (!response.ok || payload.error) {
                        const message = payload.error || 'Falha ao carregar mais itens.';
                        showError(message);
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.textContent = 'Tentar novamente';
                        return;
                    }

                    const visibleMessages = toVisibleMessages(payload.data);
                    visibleMessages.forEach(appendMessage);

                    hasMore = !!payload.has_more;
                    after = payload.last_id || after;
                    container.dataset.after = after || '';
                    container.dataset.hasMore = hasMore ? '1' : '0';

                    const statusMessage = visibleMessages.length === 0 && Array.isArray(payload.data) && payload.data.length > 0
                        ? 'Nenhuma nova mensagem visivel nesta pagina.'
                        : '';

                    updateButton(statusMessage);
                } catch (error) {
                    showError('Erro inesperado ao carregar mais itens.');
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Tentar novamente';
                }
            });

            updateButton();
        });
    </script>
@endpush
