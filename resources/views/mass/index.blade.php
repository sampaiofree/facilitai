<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Disparo em Massa') }}
        </h2>
    </x-slot>
    <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" x-data="{ usarIA: false }">

        <form method="POST" action="{{ route('mass.store') }}" class="space-y-6" id="mass-form">
            @csrf

            <div>
                <label class="block font-semibold mb-1">Instância</label>
                <select name="instance_id" required class="w-full border-gray-300 rounded-lg">
                    <option value="">Selecione...</option>
                    @foreach($instances as $instance)
                        <option value="{{ $instance->id }}">{{ $instance->name }}</option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" name="tipo_envio" value="texto">

            <div>
                <label class="block font-semibold mb-1">Prompt para Envio</label>
                <textarea name="mensagem" rows="3" class="w-full border-gray-300 rounded-lg" placeholder="Digite sua mensagem de texto..."></textarea>
            </div>

            <!--<div class="flex items-center gap-2">
                <input type="checkbox" id="usar_ia" name="usar_ia" x-model="usarIA" class="rounded border-gray-300">
                <label for="usar_ia" class="font-medium">Usar Inteligência Artificial para gerar as respostas</label>
            </div>-->

            <div>
                <label class="block font-semibold mb-1">Intervalo entre envios (segundos)</label>
                <input type="number" name="intervalo_segundos" min="2" max="900" value="5"
                       class="w-32 border-gray-300 rounded-lg p-2 text-center">
            </div>

            <div class="border rounded-lg p-4 space-y-3 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold">Filtrar chats por tags</p>
                        <p class="text-sm text-gray-500">Os chats vêm da instância escolhida. Seleção vazia = todas as tags.</p>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-700">
                        <label class="inline-flex items-center gap-1">
                            <input type="radio" name="tags_mode" value="any" class="text-purple-600" {{ old('tags_mode', 'any') === 'any' ? 'checked' : '' }}>
                            Qualquer
                        </label>
                        <label class="inline-flex items-center gap-1">
                            <input type="radio" name="tags_mode" value="all" class="text-purple-600" {{ old('tags_mode') === 'all' ? 'checked' : '' }}>
                            Todas
                        </label>
                    </div>
                </div>
                @if($tags->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma tag cadastrada.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($tags as $tag)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="tags[]" value="{{ $tag->name }}"
                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                    {{ in_array($tag->name, old('tags', [])) ? 'checked' : '' }}>
                                {{ $tag->name }}
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="border rounded-lg p-4 space-y-3 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold">Filtrar chats por sequência</p>
                        <p class="text-sm text-gray-500">Considera sequências em andamento. Seleção vazia = todas.</p>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-700">
                        <label class="inline-flex items-center gap-1">
                            <input type="radio" name="sequences_mode" value="any" class="text-purple-600" {{ old('sequences_mode', 'any') === 'any' ? 'checked' : '' }}>
                            Qualquer
                        </label>
                        <label class="inline-flex items-center gap-1">
                            <input type="radio" name="sequences_mode" value="all" class="text-purple-600" {{ old('sequences_mode') === 'all' ? 'checked' : '' }}>
                            Todas
                        </label>
                    </div>
                </div>
                @if($sequences->isEmpty())
                    <p class="text-sm text-gray-500">Nenhuma sequência cadastrada.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($sequences as $sequence)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="sequences[]" value="{{ $sequence->id }}"
                                    class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                    {{ in_array($sequence->id, old('sequences', [])) ? 'checked' : '' }}>
                                {{ $sequence->name }}
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white border rounded-lg p-4 text-sm text-gray-600">
                <p>Serão usados os contatos dos chats da instância selecionada. Números duplicados serão ignorados.</p>
            </div>

            <div class="bg-gray-50 border rounded-lg p-4 flex flex-wrap items-center gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="text-gray-600">Chats encontrados:</span>
                    <span id="mass-total" class="font-semibold text-gray-900">—</span>
                    <span id="mass-loading" class="text-gray-500 hidden">(carregando...)</span>
                </div>
                <div class="flex items-center gap-2 text-gray-600">
                    <span>Inválidos:</span>
                    <span id="mass-invalid" class="font-semibold text-amber-700">—</span>
                </div>
                <button type="button" id="mass-show-contacts"
                    class="ml-auto inline-flex items-center gap-2 px-4 py-2 rounded-md bg-white border border-gray-300 text-gray-700 shadow-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    Ver contatos
                </button>
            </div>

            <div class="text-right">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg shadow">
                    Iniciar Disparo
                </button>
            </div>
        </form>
    </div>
    </div>

    <div id="contacts-modal" class="fixed inset-0 hidden z-50 items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-full max-w-3xl rounded-xl bg-white shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Contatos filtrados</h3>
                    <p class="text-sm text-gray-500">Mostrando números deduplicados da instância selecionada.</p>
                </div>
                <button type="button" id="contacts-close" class="text-gray-500 hover:text-gray-700">✕</button>
            </div>
            <div class="px-6 py-4 space-y-3">
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <div>
                        <span>Total: </span><span id="contacts-total" class="font-semibold text-gray-900">—</span>
                        <span class="ml-3 text-amber-700" id="contacts-invalid"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="contacts-prev" class="px-3 py-1 rounded border text-sm disabled:opacity-50">Anterior</button>
                        <button type="button" id="contacts-next" class="px-3 py-1 rounded border text-sm disabled:opacity-50">Próximo</button>
                    </div>
                </div>
                <div id="contacts-loading" class="text-sm text-gray-500 hidden">Carregando...</div>
                <div id="contacts-empty" class="text-sm text-gray-500 hidden">Nenhum contato para exibir.</div>
                <ul id="contacts-list" class="divide-y divide-gray-200 max-h-96 overflow-auto text-sm"></ul>
            </div>
            <div class="px-6 py-4 bg-gray-50 text-xs text-gray-600">
                Números duplicados na instância são mostrados apenas uma vez. Limite de 200 contatos por página no preview.
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('mass-form');
            const totalEl = document.getElementById('mass-total');
            const invalidEl = document.getElementById('mass-invalid');
            const loadingEl = document.getElementById('mass-loading');
            const showContactsBtn = document.getElementById('mass-show-contacts');
            const modal = document.getElementById('contacts-modal');
            const closeModalBtn = document.getElementById('contacts-close');
            const listEl = document.getElementById('contacts-list');
            const modalTotalEl = document.getElementById('contacts-total');
            const modalInvalidEl = document.getElementById('contacts-invalid');
            const modalLoadingEl = document.getElementById('contacts-loading');
            const modalEmptyEl = document.getElementById('contacts-empty');
            const prevBtn = document.getElementById('contacts-prev');
            const nextBtn = document.getElementById('contacts-next');

            const previewUrl = "{{ route('mass.preview') }}";
            let debounceTimer = null;
            let lastParamsKey = '';
            let currentTotal = 0;
            let currentInvalid = 0;
            let pagination = { offset: 0, limit: 100 };

            const inputs = form.querySelectorAll('select, input[type=radio], input[type=checkbox]');

            inputs.forEach(el => {
                el.addEventListener('change', () => schedulePreview());
            });

            schedulePreview();

            showContactsBtn.addEventListener('click', () => {
                pagination.offset = 0;
                openModal();
                fetchPreview(true);
            });

            closeModalBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });

            prevBtn.addEventListener('click', () => {
                if (pagination.offset === 0) return;
                pagination.offset = Math.max(0, pagination.offset - pagination.limit);
                fetchPreview(true);
            });

            nextBtn.addEventListener('click', () => {
                if (pagination.offset + pagination.limit >= currentTotal) return;
                pagination.offset += pagination.limit;
                fetchPreview(true);
            });

            function schedulePreview() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => fetchPreview(false), 400);
            }

            function collectParams(withList = false) {
                const params = new URLSearchParams();
                const instance = form.querySelector('select[name="instance_id"]').value;
                if (!instance) {
                    return null;
                }
                params.append('instance_id', instance);

                const tagsMode = form.querySelector('input[name="tags_mode"]:checked')?.value;
                if (tagsMode) params.append('tags_mode', tagsMode);
                const seqMode = form.querySelector('input[name="sequences_mode"]:checked')?.value;
                if (seqMode) params.append('sequences_mode', seqMode);

                form.querySelectorAll('input[name="tags[]"]:checked').forEach(el => params.append('tags[]', el.value));
                form.querySelectorAll('input[name="sequences[]"]:checked').forEach(el => params.append('sequences[]', el.value));

                if (withList) {
                    params.append('with_list', '1');
                    params.append('limit', pagination.limit);
                    params.append('offset', pagination.offset);
                }

                return params;
            }

            async function fetchPreview(withList) {
                const params = collectParams(withList);
                if (!params) {
                    updateSummary(null);
                    return;
                }

                const key = params.toString();
                if (!withList && key === lastParamsKey) {
                    return;
                }

                if (!withList) {
                    lastParamsKey = key;
                    loadingEl.classList.remove('hidden');
                } else {
                    modalLoadingEl.classList.remove('hidden');
                    modalEmptyEl.classList.add('hidden');
                    listEl.innerHTML = '';
                }

                try {
                    const res = await fetch(`${previewUrl}?${params.toString()}`);
                    if (!res.ok) throw new Error('Erro ao consultar preview');
                    const data = await res.json();
                    updateSummary(data);
                    if (withList) {
                        renderList(data);
                    } else {
                        pagination.offset = 0;
                    }
                } catch (err) {
                    console.error(err);
                    updateSummary(null);
                    if (withList) {
                        modalEmptyEl.classList.remove('hidden');
                        modalEmptyEl.textContent = 'Erro ao carregar contatos.';
                    }
                } finally {
                    loadingEl.classList.add('hidden');
                    modalLoadingEl.classList.add('hidden');
                }
            }

            function updateSummary(data) {
                if (!data) {
                    totalEl.textContent = '—';
                    invalidEl.textContent = '—';
                    showContactsBtn.disabled = true;
                    currentTotal = 0;
                    currentInvalid = 0;
                    return;
                }

                currentTotal = data.total || 0;
                currentInvalid = data.invalid || 0;
                totalEl.textContent = currentTotal;
                invalidEl.textContent = currentInvalid;
                showContactsBtn.disabled = currentTotal === 0;
            }

            function renderList(data) {
                modalTotalEl.textContent = data.total ?? '—';
                modalInvalidEl.textContent = data.invalid ? `${data.invalid} inválidos ignorados` : '';

                const items = data.items || [];
                listEl.innerHTML = '';

                if (!items.length) {
                    modalEmptyEl.classList.remove('hidden');
                } else {
                    modalEmptyEl.classList.add('hidden');
                    items.forEach(item => {
                        const li = document.createElement('li');
                        li.className = 'py-3';
                        li.innerHTML = `
                            <div class="font-semibold text-gray-900">${item.nome || 'Sem nome'}</div>
                            <div class="text-sm text-gray-700">${item.contact}</div>
                            ${item.conv_id ? `<div class="text-xs text-gray-500">Conv: ${item.conv_id}</div>` : ''}
                        `;
                        listEl.appendChild(li);
                    });
                }

                prevBtn.disabled = pagination.offset === 0;
                nextBtn.disabled = pagination.offset + pagination.limit >= (data.total || 0);
            }

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });
    </script>
</x-app-layout>
