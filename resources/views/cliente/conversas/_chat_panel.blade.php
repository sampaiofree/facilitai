@php
    $selectedAssistantLead = $chatAssistantLead ?? null;
    $selectedLead = $selectedAssistantLead?->lead;
    $selectedAssistant = $selectedAssistantLead?->assistant;
    $selectedSendConexao = $chatSendConexao ?? null;
    $visibleMessages = $chatMessages ?? [];
    $canRenderConversation = !empty($chatConvId) && !empty($selectedAssistantLead) && empty($chatError) && $chatResult !== null;
    $selectedLeadTags = $selectedLead?->tags?->sortBy('name')->values() ?? collect();
    $selectedLeadSequences = $selectedLead?->sequenceChats?->map(fn ($sequenceChat) => $sequenceChat->sequence)
        ->filter(fn ($sequence) => $sequence && (int) $sequence->cliente_id === (int) $selectedLead->cliente_id)
        ->unique('id')
        ->sortBy('name')
        ->values() ?? collect();
@endphp

<section id="cliente-chat-panel" class="space-y-4 lg:col-span-3">
    @if($chatError)
        <div class="rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $chatError }}
        </div>
    @endif

    @if($canRenderConversation)
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-lg font-semibold text-slate-900">{{ $selectedLead?->name ?: 'Lead sem nome' }}</p>
                    <p class="text-sm text-slate-500">{{ $selectedLead?->phone ?: '-' }}</p>

                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Etiquetas</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @forelse($selectedLeadTags as $tag)
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-600">{{ $tag->name }}</span>
                                @empty
                                    <span class="text-xs text-slate-400">Sem etiquetas</span>
                                @endforelse
                            </div>
                        </div>

                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Sequências</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @forelse($selectedLeadSequences as $sequence)
                                    <span class="rounded-full border border-blue-100 bg-blue-50 px-2 py-1 text-[11px] font-medium text-blue-700">{{ $sequence->name }}</span>
                                @empty
                                    <span class="text-xs text-slate-400">Sem sequências</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
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

        <div class="flex h-[52vh] min-h-[320px] max-h-[560px] flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm lg:h-[calc(100vh-26rem)] lg:min-h-[420px] lg:max-h-[720px]">
            <div class="shrink-0 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
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
                class="min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-contain bg-[#efeae2] px-4 py-5 sm:px-6"
                data-conv-id="{{ $chatConvId }}"
                data-after="{{ $chatLastId }}"
                data-has-more="{{ $chatHasMore ? '1' : '0' }}"
                data-visible-count="{{ count($visibleMessages) }}"
                data-limit="{{ $chatLimit ?? '' }}"
                data-assistant-lead-updated-at="{{ $chatAssistantLeadUpdatedAt ?? '' }}"
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
                    <div
                        class="flex {{ $message['role'] === 'user' ? 'justify-start' : 'justify-end' }}"
                        data-chat-message-id="{{ $message['id'] }}"
                        data-chat-message-role="{{ $message['role'] }}"
                    >
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
                <div
                    id="cliente-chat-poll-timer"
                    class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-medium text-slate-500"
                    data-default-label="Próxima verificação em 15s"
                >
                    Próxima verificação em 15s
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
