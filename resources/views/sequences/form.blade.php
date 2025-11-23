<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $title }}
            </h2>
            <a href="{{ route('sequences.index') }}" class="text-sm text-blue-600 font-semibold">Voltar</a>
        </div>
    </x-slot>

    <div class="py-12" x-data="sequenceForm(@js($sequence ?? null), @js($steps ?? []), @js($tags ?? []), '{{ $action }}', '{{ $method ?? 'POST' }}', '{{ $submitLabel ?? 'Salvar' }}')">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @if ($errors->any())
                    <div class="rounded-md bg-red-50 px-4 py-3 text-red-800">
                        <ul class="list-disc ml-4 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" :action="submitUrl" @submit.prevent="submit">
                    @csrf
                    <template x-if="method !== 'POST'">
                        <input type="hidden" name="_method" :value="method">
                    </template>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                            <input type="text" name="name" x-model="sequence.name"
                                class="w-full border rounded-lg px-3 py-2 text-sm"
                                placeholder="Ex.: Onboarding pós-compra" required>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="active" name="active" x-model="sequence.active" class="h-4 w-4 text-blue-600">
                            <label for="active" class="text-sm font-semibold text-gray-700">Sequência ativa</label>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição (opcional)</label>
                            <textarea name="description" x-model="sequence.description" rows="2"
                                class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>

                        <div class="md:col-span-3 grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tags necessárias (incluir)</label>
                                <p class="text-xs text-gray-500 mb-2">Selecione ou crie tags que devem estar no chat para disparar.</p>
                                <div class="flex gap-2">
                                    <input type="text" x-model="tagInputs.incluir" class="w-full border rounded-lg px-3 py-2 text-sm"
                                        list="tags-options" placeholder="Digite ou selecione uma tag">
                                    <button type="button" class="px-3 py-2 text-sm font-semibold rounded-md bg-blue-600 text-white"
                                        @click="addTag('incluir')">Adicionar</button>
                                </div>
                                <div class="flex flex-wrap gap-2 mt-3">
                                    <template x-for="(tag, index) in sequence.tags_incluir" :key="tag + '-inc' + index">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                            <span x-text="tag"></span>
                                            <button type="button" class="text-blue-600" @click="removeTag('incluir', index)">✕</button>
                                        </span>
                                    </template>
                                    <template x-if="sequence.tags_incluir.length === 0">
                                        <span class="text-xs text-gray-400">Nenhuma tag adicionada.</span>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tags bloqueadas (excluir)</label>
                                <p class="text-xs text-gray-500 mb-2">Se o chat tiver alguma destas tags, ele sai da sequência.</p>
                                <div class="flex gap-2">
                                    <input type="text" x-model="tagInputs.excluir" class="w-full border rounded-lg px-3 py-2 text-sm"
                                        list="tags-options" placeholder="Digite ou selecione uma tag">
                                    <button type="button" class="px-3 py-2 text-sm font-semibold rounded-md bg-blue-600 text-white"
                                        @click="addTag('excluir')">Adicionar</button>
                                </div>
                                <div class="flex flex-wrap gap-2 mt-3">
                                    <template x-for="(tag, index) in sequence.tags_excluir" :key="tag + '-exc' + index">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                            <span x-text="tag"></span>
                                            <button type="button" class="text-red-600" @click="removeTag('excluir', index)">✕</button>
                                        </span>
                                    </template>
                                    <template x-if="sequence.tags_excluir.length === 0">
                                        <span class="text-xs text-gray-400">Nenhuma tag bloqueada.</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <datalist id="tags-options">
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->name }}"></option>
                            @endforeach
                        </datalist>
                        <div class="text-xs text-gray-500 mt-2 space-x-3 md:col-span-3">
                            <button type="button" class="text-blue-600 font-semibold" @click="tagModal.open = true">Criar nova tag</button>
                            <a href="{{ route('tags.index') }}" class="text-blue-600 font-semibold" target="_blank">Gerenciar tags</a>
                        </div>
                    </div>

                    <div class="border-t pt-4 mt-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold text-gray-800">Passos da sequência</h3>
                            <button type="button" @click="addStep"
                                class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-2 text-white text-sm font-semibold">
                                + Adicionar passo
                            </button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(step, index) in steps" :key="step.uid">
                                <div class="border rounded-lg p-4 bg-gray-50/70 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm font-semibold text-gray-700">Passo <span x-text="index + 1"></span></div>
                                        <button type="button" class="text-red-600 text-sm font-semibold" @click="removeStep(index)">Remover</button>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-600 uppercase">Nome do passo (opcional)</label>
                                        <input type="text" x-model="step.title" class="w-full border rounded px-3 py-2 text-sm"
                                            placeholder="Ex.: Mensagem de boas-vindas">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                        <div>
                                            <label class="text-xs font-semibold text-gray-600 uppercase">Ordem</label>
                                            <input type="number" min="1" x-model.number="step.ordem" class="w-full border rounded px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-600 uppercase">Atraso</label>
                                            <div class="flex gap-2">
                                                <input type="number" min="0" x-model.number="step.atraso_valor" class="w-20 border rounded px-2 py-2 text-sm">
                                                <select x-model="step.atraso_tipo" class="border rounded px-2 py-2 text-sm">
                                                    <option value="minuto">Minuto(s)</option>
                                                    <option value="hora">Hora(s)</option>
                                                    <option value="dia">Dia(s)</option>
                                                </select>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-1">A partir do início do passo anterior.</p>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-600 uppercase">Janela (início)</label>
                                            <input type="time" x-model="step.janela_inicio" class="w-full border rounded px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-600 uppercase">Janela (fim)</label>
                                            <input type="time" x-model="step.janela_fim" class="w-full border rounded px-3 py-2 text-sm">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-600 uppercase">Dias da semana</label>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <template x-for="day in days" :key="day.value">
                                                <label class="inline-flex items-center gap-1 text-sm text-gray-700">
                                                    <input type="checkbox" :value="day.value" x-model="step.dias_semana" class="text-blue-600">
                                                    <span x-text="day.label"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-600 uppercase">Prompt do passo</label>
                                        <textarea rows="3" x-model="step.prompt" class="w-full border rounded px-3 py-2 text-sm"
                                            placeholder="Instrução que o assistente deve usar para enviar a mensagem"></textarea>
                                    </div>
                                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                                        <input type="checkbox" x-model="step.active" class="text-blue-600">
                                        Passo ativo
                                    </label>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t mt-4">
                        <a href="{{ route('sequences.index') }}" class="text-sm text-gray-600">Cancelar</a>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white">
                            <span x-text="submitLabel"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="tagModal.open" x-cloak style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-full max-w-md bg-white rounded-xl shadow-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Criar nova tag</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" @click="tagModal.open = false">✕</button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-semibold text-gray-600 uppercase">Nome</label>
                    <input type="text" x-model="tagModal.name" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 uppercase">Cor (opcional)</label>
                    <input type="text" x-model="tagModal.color" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" placeholder="#4F46E5 ou 'blue'">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 uppercase">Descrição</label>
                    <input type="text" x-model="tagModal.description" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                </div>
                <p class="text-xs text-gray-500">As tags serão salvas e ficarão disponíveis em qualquer sequência.</p>
                <p x-show="tagModal.error" class="text-sm text-red-600" x-text="tagModal.error"></p>
            </div>
            <div class="flex items-center justify-end gap-3">
                <button type="button" class="text-sm text-gray-600" @click="tagModal.open = false">Cancelar</button>
                <button type="button" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white"
                    :disabled="tagModal.loading"
                    @click="createTag">
                    <span x-show="tagModal.loading">Salvando...</span>
                    <span x-show="!tagModal.loading">Salvar tag</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function sequenceForm(seq = null, initialSteps = [], availableTags = [], submitUrl = '', method = 'POST', submitLabel = 'Salvar') {
            const baseSequence = seq ?? { name: '', description: '', active: true, tags_incluir: [], tags_excluir: [] };
            return {
                submitUrl,
                method,
                submitLabel,
                availableTags,
                sequence: {
                    name: baseSequence.name ?? '',
                    description: baseSequence.description ?? '',
                    active: baseSequence.active ?? true,
                    tags_incluir: baseSequence.tags_incluir ?? [],
                    tags_excluir: baseSequence.tags_excluir ?? [],
                },
                tagInputs: { incluir: '', excluir: '' },
                tagModal: { open: false, name: '', color: '', description: '', loading: false, error: null },
                tagCreateUrl: "{{ route('tags.store') }}",
                csrfToken: "{{ csrf_token() }}",
                steps: (initialSteps || []).map((s) => ({
                    uid: Date.now() + Math.random(),
                    title: s.title ?? '',
                    ordem: s.ordem ?? 1,
                    atraso_tipo: s.atraso_tipo ?? 'hora',
                    atraso_valor: s.atraso_valor ?? 1,
                    janela_inicio: s.janela_inicio ?? '',
                    janela_fim: s.janela_fim ?? '',
                    dias_semana: s.dias_semana ?? [],
                    prompt: s.prompt ?? '',
                    active: s.active ?? true,
                })),
                days: [
                    { value: 'mon', label: 'Seg' },
                    { value: 'tue', label: 'Ter' },
                    { value: 'wed', label: 'Qua' },
                    { value: 'thu', label: 'Qui' },
                    { value: 'fri', label: 'Sex' },
                    { value: 'sat', label: 'Sáb' },
                    { value: 'sun', label: 'Dom' },
                ],
                addStep() {
                    this.steps.push({
                        uid: Date.now() + Math.random(),
                        title: '',
                        ordem: this.steps.length + 1,
                        atraso_tipo: 'hora',
                        atraso_valor: 1,
                        janela_inicio: '',
                        janela_fim: '',
                        dias_semana: [],
                        prompt: '',
                        active: true,
                    });
                },
                removeStep(index) {
                    this.steps.splice(index, 1);
                },
                addTag(type) {
                    const value = (type === 'incluir' ? this.tagInputs.incluir : this.tagInputs.excluir).trim();
                    if (!value) return;
                    if (type === 'incluir' && !this.sequence.tags_incluir.includes(value)) {
                        this.sequence.tags_incluir.push(value);
                    }
                    if (type === 'excluir' && !this.sequence.tags_excluir.includes(value)) {
                        this.sequence.tags_excluir.push(value);
                    }
                    if (type === 'incluir') {
                        this.tagInputs.incluir = '';
                    } else {
                        this.tagInputs.excluir = '';
                    }
                },
                removeTag(type, index) {
                    if (type === 'incluir') {
                        this.sequence.tags_incluir.splice(index, 1);
                    } else {
                        this.sequence.tags_excluir.splice(index, 1);
                    }
                },
                async createTag() {
                    if (!this.tagModal.name.trim()) {
                        this.tagModal.error = 'Informe o nome da tag.';
                        return;
                    }
                    this.tagModal.loading = true;
                    this.tagModal.error = null;
                    try {
                        const fd = new FormData();
                        fd.append('name', this.tagModal.name);
                        fd.append('color', this.tagModal.color);
                        fd.append('description', this.tagModal.description);
                        fd.append('_token', this.csrfToken);

                        const response = await fetch(this.tagCreateUrl, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                            body: fd,
                        });
                        if (!response.ok) {
                            throw new Error('Erro');
                        }
                        const data = await response.json();
                        if (!this.availableTags.find(tag => tag.name === data.name)) {
                            this.availableTags.push({ name: data.name });
                        }
                        this.tagModal = { open: false, name: '', color: '', description: '', loading: false, error: null };
                    } catch (error) {
                        this.tagModal.loading = false;
                        this.tagModal.error = 'Não foi possível salvar a tag.';
                    }
                },
                submit() {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = this.submitUrl;
                    form.innerHTML = `@csrf`;
                    if (this.method !== 'POST') {
                        const m = document.createElement('input');
                        m.type = 'hidden';
                        m.name = '_method';
                        m.value = this.method;
                        form.appendChild(m);
                    }

                    const append = (name, value) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        form.appendChild(input);
                    };

                    append('name', this.sequence.name);
                    append('description', this.sequence.description || '');
                    append('active', this.sequence.active ? '1' : '0');
                    (this.sequence.tags_incluir || []).forEach((tag, i) => append(`tags_incluir[${i}]`, tag));
                    (this.sequence.tags_excluir || []).forEach((tag, i) => append(`tags_excluir[${i}]`, tag));

                    this.steps.forEach((step, idx) => {
                        append(`steps[${idx}][ordem]`, step.ordem || (idx + 1));
                        append(`steps[${idx}][title]`, step.title || '');
                        append(`steps[${idx}][atraso_tipo]`, step.atraso_tipo);
                        append(`steps[${idx}][atraso_valor]`, step.atraso_valor);
                        append(`steps[${idx}][janela_inicio]`, step.janela_inicio || '');
                        append(`steps[${idx}][janela_fim]`, step.janela_fim || '');
                        (step.dias_semana || []).forEach((day, i) => {
                            append(`steps[${idx}][dias_semana][${i}]`, day);
                        });
                        append(`steps[${idx}][prompt]`, step.prompt || '');
                        append(`steps[${idx}][active]`, step.active ? '1' : '0');
                    });

                    document.body.appendChild(form);
                    form.submit();
                }
            };
        }
    </script>
</x-app-layout>
