@extends('layouts.agencia')

@push('head')
    <style>
        .log-accordion summary svg {
            transition: transform .2s ease;
        }

        .log-accordion[open] summary svg {
            transform: rotate(180deg);
        }
    </style>
@endpush

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Sequências</h2>
            <p class="text-sm text-slate-500">Gerencie as sequências vinculadas ao seu usuário.</p>
        </div>
        <button id="openSequenceModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Nova sequência</button>
    </div>

    <div class="grid gap-4">
        @forelse($sequences as $sequence)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $sequence->name }}</h3>
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">id: {{ $sequence->id }}</span>
                        </div>
                        <p class="text-sm text-slate-500">{{ $sequence->description ?? 'Sem descrição' }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $sequence->active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                        {{ $sequence->active ? 'Ativa' : 'Inativa' }}
                    </span>
                </div>
                <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-600">
                    <div><span class="font-semibold text-slate-800">Cliente:</span> {{ $sequence->cliente?->nome ?? '—' }}</div>
                    <div><span class="font-semibold text-slate-800">Conexão:</span> {{ $sequence->conexao?->name ?? '—' }}</div>
                    <div>
                        <span class="font-semibold text-slate-800">Tags incluir:</span>
                        {{ collect($sequence->tags_incluir ?? [])->implode(', ') ?: '—' }}
                    </div>
                    <div>
                        <span class="font-semibold text-slate-800">Tags excluir:</span>
                        {{ collect($sequence->tags_excluir ?? [])->implode(', ') ?: '—' }}
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <button type="button"
                        class="rounded-lg bg-indigo-500 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                        data-action="edit-sequence"
                        data-payload="{{ json_encode([
                            'id' => $sequence->id,
                            'name' => $sequence->name,
                            'description' => $sequence->description,
                            'active' => $sequence->active,
                            'cliente_id' => $sequence->cliente_id,
                            'conexao_id' => $sequence->conexao_id,
                            'tags_incluir' => $sequence->tags_incluir ?? [],
                            'tags_excluir' => $sequence->tags_excluir ?? [],
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                    >Editar</button>
                    <button type="button"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                        data-action="create-step"
                        data-sequence="{{ $sequence->id }}"
                    >Criar Etapa</button>
                </div>
                <div class="mt-4">
                    <details class="step-accordion rounded-2xl border border-slate-200 bg-slate-50">
                        <summary class="flex items-center justify-between px-4 py-3 text-sm font-semibold text-slate-700 cursor-pointer">
                            <span>Etapas ({{ $sequence->steps->count() }})</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6" />
                            </svg>
                        </summary>
                        <div class="px-4 py-3">
                            @if($sequence->steps->isEmpty())
                                <p class="text-xs text-slate-500">Nenhuma etapa criada.</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs text-slate-600 border border-slate-100">
                                        <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Título</th>
                                                <th class="px-3 py-2 text-left">Atraso</th>
                                                <th class="px-3 py-2 text-left">Tipo</th>
                                                <th class="px-3 py-2 text-left">Ativo</th>
                                                <th class="px-3 py-2 text-left">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($sequence->steps as $step)
                                                <tr class="border-t border-slate-100">
                                                    <td class="px-3 py-2">{{ $step->title ?: 'Sem título' }}</td>
                                                    <td class="px-3 py-2">{{ $step->atraso_valor }}</td>
                                                    <td class="px-3 py-2">{{ ucfirst($step->atraso_tipo) }}</td>
                                                    <td class="px-3 py-2">
                                                        @if($step->active)
                                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700">Sim</span>
                                                        @else
                                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-semibold text-rose-700">Não</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <button type="button"
                                                            class="rounded-lg bg-slate-900 px-3 py-1 text-[11px] font-semibold text-white hover:bg-slate-800"
                                                            data-action="edit-step"
                                                            data-sequence="{{ $sequence->id }}"
                                                            data-step="{{ json_encode([
                                                                'id' => $step->id,
                                                                'title' => $step->title,
                                                                'atraso_valor' => $step->atraso_valor,
                                                                'atraso_tipo' => $step->atraso_tipo,
                                                                'janela_inicio' => $step->janela_inicio,
                                                                'janela_fim' => $step->janela_fim,
                                                                'dias_semana' => $step->dias_semana ?? [],
                                                                'prompt' => $step->prompt,
                                                                'active' => $step->active,
                                                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                                                        >Editar</button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-3 py-2 text-xs text-slate-400">Nenhuma etapa criada.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </details>
                </div>
            <div class="mt-4">
                <details class="log-accordion rounded-2xl border border-slate-200 bg-slate-50">
                    <summary class="flex items-center justify-between px-4 py-3 text-sm font-semibold text-slate-700 cursor-pointer">
                        <span>Log ({{ $sequence->logs->count() }})</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6" />
                        </svg>
                    </summary>
                    <div class="px-4 py-3">
                        @if($sequence->logs->isEmpty())
                            <p class="text-xs text-slate-500">Sem registros no log.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs text-slate-600 border border-slate-100">
                                    <thead class="bg-slate-100 text-slate-500 text-[11px] uppercase">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Log</th>
                                            <th class="px-3 py-2 text-left">SequenceChat</th>
                                            <th class="px-3 py-2 text-left">SequenceStep</th>
                                            <th class="px-3 py-2 text-left">Passo</th>
                                            <th class="px-3 py-2 text-left">Status</th>
                                            <th class="px-3 py-2 text-left">Mensagem</th>
                                            <th class="px-3 py-2 text-left">Criado em</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sequence->logs as $log)
                                        <tr class="border-t border-slate-100">
                                            <td class="px-3 py-2">{{ $log->id }}</td>
                                            <td class="px-3 py-2">{{ $log->sequence_chat_id }}</td>
                                            <td class="px-3 py-2">{{ $log->sequence_step_id ?? '—' }}</td>
                                            <td class="px-3 py-2">
                                                {{ $log->sequenceStep?->ordem ? 'Passo ' . $log->sequenceStep->ordem : '—' }}
                                            </td>
                                            <td class="px-3 py-2">{{ ucfirst($log->status) }}</td>
                                            <td class="px-3 py-2">{{ $log->message ?? '—' }}</td>
                                            <td class="px-3 py-2">
                                                {{ $log->created_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? '—' }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </details>
            </div>
        </article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-center text-sm text-slate-500">
                Nenhuma sequência encontrada.
            </div>
        @endforelse
    </div>

    <div id="sequenceModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 backdrop-blur">
        <div class="w-[min(720px,calc(100%-2rem))] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900" id="sequenceModalTitle">Nova sequência</h3>
                <button type="button" data-close-modal class="text-slate-500 hover:text-slate-700">x</button>
            </div>
            <form id="sequenceForm" method="POST" action="{{ route('agencia.sequences.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="sequence_id" id="sequenceId" value="">

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="sequenceClient">Cliente</label>
                    <select id="sequenceClient" name="cliente_id" required class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm">
                        <option value="">Selecione um cliente</option>
                        @foreach($clients as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="sequenceConexao">Conexão</label>
                    <select id="sequenceConexao" name="conexao_id" required class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm">
                        <option value="">Escolha o cliente primeiro</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="sequenceName">Nome</label>
                    <input id="sequenceName" name="name" type="text" maxlength="255" required class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm">
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="sequenceDescription">Descrição</label>
                    <textarea id="sequenceDescription" name="description" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 px-4 py-2 text-sm"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input id="sequenceActive" name="active" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label for="sequenceActive" class="text-sm text-slate-600">Ativa</label>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tags a incluir</label>
                    <div id="sequenceTagsIncluirContainer" class="relative mt-2 rounded-lg border border-slate-200 bg-white p-2" data-input-name="tags_incluir[]" data-tags="{{ json_encode($tags->pluck('name')) }}">
                        <div class="flex flex-wrap gap-2" data-selected></div>
                        <input type="text" placeholder="Selecione tags..." class="mt-1 w-full border-none bg-transparent px-1 text-sm focus:outline-none" data-search>
                        <ul class="absolute left-0 right-0 z-10 mt-1 max-h-52 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg hidden" data-list></ul>
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tags a excluir</label>
                    <div id="sequenceTagsExcluirContainer" class="relative mt-2 rounded-lg border border-slate-200 bg-white p-2" data-input-name="tags_excluir[]" data-tags="{{ json_encode($tags->pluck('name')) }}">
                        <div class="flex flex-wrap gap-2" data-selected></div>
                        <input type="text" placeholder="Selecione tags..." class="mt-1 w-full border-none bg-transparent px-1 text-sm focus:outline-none" data-search>
                        <ul class="absolute left-0 right-0 z-10 mt-1 max-h-52 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg hidden" data-list></ul>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" data-close-modal class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="sequenceStepModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[min(520px,calc(100%-2rem))] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="stepModalTitle" class="text-lg font-semibold text-slate-900">Nova etapa</h3>
                <button type="button" data-close-step-modal class="text-slate-500 hover:text-slate-700">x</button>
            </div>
            <form id="sequenceStepForm" method="POST" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="stepFormMethod" value="POST">
                <input type="hidden" name="sequence_id" id="stepSequenceId" value="">
                <input type="hidden" name="step_id" id="stepId" value="">

                <div>
                    <label class="text-xs uppercase tracking-wide text-slate-500">Título</label>
                    <input type="text" name="title" id="stepTitle" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Nome do passo">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs uppercase tracking-wide text-slate-500">Atraso valor</label>
                        <input type="number" name="atraso_valor" id="stepAtrasoValor" min="0" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" value="1">
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-slate-500">Atraso tipo</label>
                        <select name="atraso_tipo" id="stepAtrasoTipo" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <option value="minuto">Minuto(s)</option>
                            <option value="hora" selected>Hora(s)</option>
                            <option value="dia">Dia(s)</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="stepActive" name="active" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                        <label for="stepActive" class="text-sm text-slate-600">Passo ativo</label>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs uppercase tracking-wide text-slate-500">Janela início</label>
                        <input type="time" name="janela_inicio" id="stepJanelaInicio" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-slate-500">Janela fim</label>
                        <input type="time" name="janela_fim" id="stepJanelaFim" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-slate-500">Dias da semana</label>
                    <div class="flex flex-wrap gap-2 mt-2" id="stepDias">
                        @foreach(['mon'=>'Seg','tue'=>'Ter','wed'=>'Qua','thu'=>'Qui','fri'=>'Sex','sat'=>'Sáb','sun'=>'Dom'] as $value => $label)
                            <label class="inline-flex items-center gap-1 text-sm text-slate-700">
                                <input type="checkbox" value="{{ $value }}" class="text-blue-600" name="dias_semana[]" data-step-day>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-slate-500">Prompt</label>
                    <textarea name="prompt" id="stepPrompt" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" data-close-step-modal class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('sequenceModal');
            const openBtn = document.getElementById('openSequenceModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const clientSelect = document.getElementById('sequenceClient');
            const connectionSelect = document.getElementById('sequenceConexao');
            const form = document.getElementById('sequenceForm');
            const title = document.getElementById('sequenceModalTitle');
            const hiddenId = document.getElementById('sequenceId');
            const nameInput = document.getElementById('sequenceName');
            const descriptionInput = document.getElementById('sequenceDescription');
            const activeInput = document.getElementById('sequenceActive');
            const tagsIncluirContainer = document.getElementById('sequenceTagsIncluirContainer');
            const tagsExcluirContainer = document.getElementById('sequenceTagsExcluirContainer');

            const connectionsUrlTemplate = "{{ route('agencia.sequences.cliente.conexoes', ['cliente' => '__CLIENT__']) }}";

            class MultiTagSelect {
                constructor(root) {
                    this.root = root;
                    this.name = root.dataset.inputName;
                    this.options = JSON.parse(root.dataset.tags || '[]');
                    this.selectedValues = new Set();
                    this.selectedContainer = root.querySelector('[data-selected]');
                    this.searchInput = root.querySelector('[data-search]');
                    this.list = root.querySelector('[data-list]');
                    this.bind();
                    this.renderList();
                }

                bind() {
                    this.searchInput.addEventListener('input', () => this.renderList(this.searchInput.value));
                    this.searchInput.addEventListener('focus', () => this.renderList(this.searchInput.value));
                    this.list.addEventListener('click', (event) => {
                        const value = event.target.dataset.value;
                        if (value) {
                            this.addValue(value);
                            this.searchInput.value = '';
                            this.renderList();
                        }
                    });
                    document.addEventListener('click', (event) => {
                        if (!this.root.contains(event.target)) {
                            this.list.classList.add('hidden');
                        }
                    });
                }

                renderList(filter = '') {
                    const term = filter.toLowerCase();
                    const matches = this.options.filter(tag => {
                        const normalized = String(tag).toLowerCase();
                        return !this.selectedValues.has(tag) && (term === '' || normalized.includes(term));
                    });
                    if (matches.length === 0) {
                        this.list.innerHTML = '';
                        this.list.classList.add('hidden');
                        return;
                    }
                    this.list.innerHTML = matches.map(tag => `<li class="cursor-pointer px-3 py-2 hover:bg-slate-100" data-value="${tag}">${tag}</li>`).join('');
                    this.list.classList.remove('hidden');
                }

                addValue(value) {
                    const normalized = String(value).trim();
                    if (normalized === '' || this.selectedValues.has(normalized)) {
                        return;
                    }
                    this.selectedValues.add(normalized);
                    const chip = document.createElement('span');
                    chip.className = 'flex items-center gap-1 rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-800';
                    chip.textContent = normalized;
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'text-slate-500 hover:text-slate-700';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.addEventListener('click', () => this.removeValue(normalized, chip));
                    chip.appendChild(removeBtn);
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = this.name;
                    hiddenInput.value = normalized;
                    chip.dataset.value = normalized;
                    chip.appendChild(hiddenInput);
                    this.selectedContainer.appendChild(chip);
                }

                removeValue(value, chipElement) {
                    this.selectedValues.delete(value);
                    this.selectedContainer.removeChild(chipElement);
                    this.renderList(this.searchInput.value);
                }

                clear() {
                    this.selectedValues.clear();
                    this.selectedContainer.innerHTML = '';
                }

                setValues(values) {
                    this.clear();
                    (values ?? []).forEach(value => this.addValue(value));
                }
            }

            const incluirSelect = new MultiTagSelect(tagsIncluirContainer);
            const excluirSelect = new MultiTagSelect(tagsExcluirContainer);

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                hiddenId.value = '';
                form.reset();
                connectionSelect.innerHTML = '<option value="">Escolha o cliente primeiro</option>';
                title.textContent = 'Nova sequência';
                incluirSelect.clear();
                excluirSelect.clear();
            };

            const populateConnections = async (clienteId, selected = null) => {
                if (!clienteId) {
                    connectionSelect.innerHTML = '<option value="">Escolha o cliente primeiro</option>';
                    return;
                }
                const url = connectionsUrlTemplate.replace('__CLIENT__', clienteId);
                const response = await fetch(url);
                if (!response.ok) {
                    connectionSelect.innerHTML = '<option value="">Não foi possível carregar conexões</option>';
                    return;
                }
                const json = await response.json();
                connectionSelect.innerHTML = '<option value="">Selecione uma conexão</option>';
                json.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    connectionSelect.appendChild(opt);
                });
                if (selected) {
                    connectionSelect.value = selected;
                }
            };

            clientSelect.addEventListener('change', () => populateConnections(clientSelect.value));

            openBtn.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeBtns.forEach(button => button.addEventListener('click', closeModal));
            modal.addEventListener('click', event => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            const setSelectOptions = (select, values) => {
                const normalized = values.map(v => String(v).trim()).filter(v => v !== '');
                [...select.options].forEach(option => {
                    option.selected = normalized.includes(option.value);
                });
            };

            document.querySelectorAll('[data-action="edit-sequence"]').forEach(button => {
                button.addEventListener('click', async () => {
                    const data = JSON.parse(button.dataset.payload);
                    hiddenId.value = data.id;
                    nameInput.value = data.name ?? '';
                    descriptionInput.value = data.description ?? '';
                    activeInput.checked = data.active ?? true;
                    clientSelect.value = data.cliente_id ?? '';
                    await populateConnections(data.cliente_id, data.conexao_id);
                    incluirSelect.setValues(data.tags_incluir ?? []);
                    excluirSelect.setValues(data.tags_excluir ?? []);
                    title.textContent = 'Editar sequência';
                    openModal();
                });
            });

            const stepModal = document.getElementById('sequenceStepModal');
            const stepForm = document.getElementById('sequenceStepForm');
            const stepMethodInput = document.getElementById('stepFormMethod');
            const stepSequenceInput = document.getElementById('stepSequenceId');
            const stepIdInput = document.getElementById('stepId');
            const stepTitleInput = document.getElementById('stepTitle');
            const stepAtrasoValorInput = document.getElementById('stepAtrasoValor');
            const stepAtrasoTipoInput = document.getElementById('stepAtrasoTipo');
            const stepJanelaInicioInput = document.getElementById('stepJanelaInicio');
            const stepJanelaFimInput = document.getElementById('stepJanelaFim');
            const stepPromptInput = document.getElementById('stepPrompt');
            const stepActiveInput = document.getElementById('stepActive');
            const stepDayCheckboxes = stepModal.querySelectorAll('[data-step-day]');
            const stepModalTitle = document.getElementById('stepModalTitle');
            const stepCloseButtons = stepModal.querySelectorAll('[data-close-step-modal]');

            const stepStoreTemplate = "{{ route('agencia.sequences.steps.store', ['sequence' => '__SEQ__']) }}";
            const stepUpdateTemplate = "{{ route('agencia.sequences.steps.update', ['sequence' => '__SEQ__', 'step' => '__STEP__']) }}";

            const openStepModal = (sequenceId, stepData = null) => {
                stepSequenceInput.value = sequenceId;
                if (stepData) {
                    stepForm.action = stepUpdateTemplate.replace('__SEQ__', sequenceId).replace('__STEP__', stepData.id);
                    stepMethodInput.value = 'PATCH';
                    stepIdInput.value = stepData.id;
                    stepTitleInput.value = stepData.title ?? '';
                    stepAtrasoValorInput.value = stepData.atraso_valor ?? 1;
                    stepAtrasoTipoInput.value = stepData.atraso_tipo ?? 'hora';
                    stepJanelaInicioInput.value = stepData.janela_inicio ?? '';
                    stepJanelaFimInput.value = stepData.janela_fim ?? '';
                    stepPromptInput.value = stepData.prompt ?? '';
                    stepActiveInput.checked = !!stepData.active;
                    setDayCheckboxes(stepData.dias_semana ?? []);
                    stepModalTitle.textContent = 'Editar etapa';
                } else {
                    stepForm.action = stepStoreTemplate.replace('__SEQ__', sequenceId);
                    stepMethodInput.value = 'POST';
                    stepIdInput.value = '';
                    stepTitleInput.value = '';
                    stepAtrasoValorInput.value = 1;
                    stepAtrasoTipoInput.value = 'hora';
                    stepJanelaInicioInput.value = '';
                    stepJanelaFimInput.value = '';
                    stepPromptInput.value = '';
                    stepActiveInput.checked = true;
                    setDayCheckboxes([]);
                    stepModalTitle.textContent = 'Nova etapa';
                }
                stepModal.classList.remove('hidden');
                stepModal.classList.add('flex');
            };

            const setDayCheckboxes = (values) => {
                const normalized = (values ?? []).map(v => String(v));
                stepDayCheckboxes.forEach(checkbox => {
                    checkbox.checked = normalized.includes(checkbox.value);
                });
            };

            const closeStepModal = () => {
                stepModal.classList.add('hidden');
                stepModal.classList.remove('flex');
            };

            document.querySelectorAll('[data-action="create-step"]').forEach(button => {
                button.addEventListener('click', () => {
                    openStepModal(button.dataset.sequence);
                });
            });

            document.querySelectorAll('[data-action="edit-step"]').forEach(button => {
                button.addEventListener('click', () => {
                    const stepData = JSON.parse(button.dataset.step);
                    openStepModal(button.dataset.sequence, stepData);
                });
            });

            stepCloseButtons.forEach(btn => btn.addEventListener('click', closeStepModal));
            stepModal.addEventListener('click', (event) => {
                if (event.target === stepModal) {
                    closeStepModal();
                }
            });
        })();
    </script>
@endsection
