@foreach($leadCards as $card)
    @php
        $conversations = $card['conversations'] ?? collect();
        $sendOptions = $card['send_options'] ?? collect();
        $hasActiveConversation = $conversations->contains(fn ($conversation) => $conversation['is_active']);
    @endphp
    <div
        class="mb-3 rounded-2xl border px-4 py-3 {{ $hasActiveConversation ? 'border-emerald-300 bg-emerald-50 shadow-sm' : 'border-slate-100 bg-slate-50' }}"
        data-chat-card
        data-lead-id="{{ $card['lead_id'] }}"
    >
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
