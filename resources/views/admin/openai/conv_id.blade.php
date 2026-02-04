@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">OpenAI / Conversação</h2>
            <p class="text-sm text-slate-500">Busque itens de conversa usando o conv_id presente em AssistantLead.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('adm.openai.conv_id') }}" class="mb-6 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <label for="convId" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conv ID</label>
            <input id="convId" name="conv_id" type="text" value="{{ old('conv_id', $convId ?? '') }}" required class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
        </div>
        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Buscar</button>
            @if($convId && !$error)
                <span class="text-xs text-emerald-600">Pesquisa realizada com conv_id {{ $convId }}</span>
            @endif
        </div>
    </form>

    @if($error)
        <div class="mb-6 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $error }}
        </div>
    @endif

    @if($result)
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-600">Itens da conversa</h3>
                    <p class="text-xs text-slate-500">Itens retornados: {{ count($items) }} @if($status) - Status {{ $status }} @endif</p>
                </div>
                <div class="text-xs text-slate-500">
                    @if($hasMore)
                        Mais itens disponiveis
                    @else
                        Sem mais itens
                    @endif
                </div>
            </div>

            <div id="conv-items"
                class="mt-4 space-y-3"
                data-conv-id="{{ $convId }}"
                data-after="{{ $lastId }}"
                data-has-more="{{ $hasMore ? '1' : '0' }}"
                data-count="{{ count($items) }}"
                data-limit="{{ $limit ?? '' }}">
                @forelse($items as $item)
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Item {{ $loop->iteration }}</div>
                        <pre class="mt-2 max-h-[320px] max-w-full overflow-x-auto overflow-y-auto rounded-md bg-slate-900/80 p-3 text-xs text-slate-50 whitespace-pre-wrap break-words">{{ json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                @empty
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4 text-sm text-slate-500">Nenhum item retornado.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button id="load-more-btn"
                    type="button"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400"
                    @if(!$hasMore) disabled @endif>
                    @if($hasMore)
                        Carregar mais
                    @else
                        Sem mais itens
                    @endif
                </button>
                <span id="load-more-status" class="text-xs text-slate-500"></span>
            </div>

            <div id="load-more-error" class="mt-3 hidden rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

            <details class="mt-6">
                <summary class="cursor-pointer text-xs font-semibold uppercase tracking-wide text-slate-500">JSON retornado</summary>
                <pre class="mt-3 max-h-[650px] max-w-full overflow-x-auto overflow-y-auto rounded-lg bg-slate-900/80 p-4 text-xs text-slate-50 whitespace-pre-wrap break-words">
{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                </pre>
            </details>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loadMoreBtn = document.getElementById('load-more-btn');
            const container = document.getElementById('conv-items');
            const statusEl = document.getElementById('load-more-status');
            const errorEl = document.getElementById('load-more-error');
            if (!loadMoreBtn || !container) {
                return;
            }

            const endpoint = @json(route('adm.openai.conv_id'));
            let after = container.dataset.after || null;
            let hasMore = container.dataset.hasMore === '1';
            let itemCount = Number.parseInt(container.dataset.count || '0', 10);
            const limit = container.dataset.limit || null;

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

            const appendItem = (item) => {
                itemCount += 1;
                const wrapper = document.createElement('div');
                wrapper.className = 'rounded-lg border border-slate-200 bg-slate-50/60 p-4';

                const title = document.createElement('div');
                title.className = 'text-xs font-semibold uppercase tracking-wide text-slate-500';
                title.textContent = `Item ${itemCount}`;

                const pre = document.createElement('pre');
                pre.className = 'mt-2 max-h-[320px] max-w-full overflow-x-auto overflow-y-auto rounded-md bg-slate-900/80 p-3 text-xs text-slate-50 whitespace-pre-wrap break-words';
                pre.textContent = JSON.stringify(item, null, 2);

                wrapper.appendChild(title);
                wrapper.appendChild(pre);
                container.appendChild(wrapper);
            };

            const updateButton = () => {
                if (hasMore) {
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Carregar mais';
                    if (statusEl) {
                        statusEl.textContent = '';
                    }
                } else {
                    loadMoreBtn.disabled = true;
                    loadMoreBtn.textContent = 'Sem mais itens';
                    if (statusEl) {
                        statusEl.textContent = 'Todas as paginas foram carregadas.';
                    }
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

                    const data = Array.isArray(payload.data) ? payload.data : [];
                    data.forEach(appendItem);

                    hasMore = !!payload.has_more;
                    after = payload.last_id || after;
                    container.dataset.after = after || '';
                    container.dataset.hasMore = hasMore ? '1' : '0';
                    updateButton();
                } catch (err) {
                    showError('Erro inesperado ao carregar mais itens.');
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.textContent = 'Tentar novamente';
                }
            });

            updateButton();
        });
    </script>
@endpush
