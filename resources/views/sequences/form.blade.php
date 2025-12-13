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
                                :class="inlineErrors.sequenceName ? 'border-red-400 ring-1 ring-red-200' : ''"
                                :data-error="inlineErrors.sequenceName ? 'true' : null"
                                @input="clearError('sequenceName')"
                                placeholder="Ex.: Onboarding pós-compra" required>
                            <p x-show="inlineErrors.sequenceName" class="text-xs text-red-600 mt-1" x-text="inlineErrors.sequenceName"></p>
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
                                <p class="text-xs text-gray-500 mb-2">Selecione tags já existentes para disparar a sequência.</p>
                                <div class="flex gap-2">
                                    <select x-model="tagInputs.incluir" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
                                        <option value="">Selecione uma tag</option>
                                        <template x-for="tag in availableTags" :key="tag.name">
                                            <option :value="tag.name" x-text="tag.name"></option>
                                        </template>
                                    </select>
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
                                    <select x-model="tagInputs.excluir" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
                                        <option value="">Selecione uma tag</option>
                                        <template x-for="tag in availableTags" :key="tag.name + '-ex'">
                                            <option :value="tag.name" x-text="tag.name"></option>
                                        </template>
                                    </select>
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

                    </div>

                    <div class="border-t pt-4 mt-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold text-gray-800">Passos da sequência</h3>
                            <button type="button" @click="openStepModal()"
                                class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-2 text-white text-sm font-semibold">
                                + Adicionar passo
                            </button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(step, index) in steps" :key="step.uid">
                                <div class="border rounded-lg p-4 bg-gray-50/70 space-y-3"
                                     :id="'step-' + step.uid">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3">
                                            <button type="button" @click="step.expanded = !step.expanded"
                                                class="h-8 w-8 inline-flex items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-white">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          :d="step.expanded ? 'M18 12H6' : 'M12 6v12m6-6H6'" />
                                                </svg>
                                            </button>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-700 flex items-center gap-2 flex-wrap">
                                                    <span>Passo <span x-text="index + 1"></span></span>
                                                    <span class="text-gray-500">— <span x-text="step.title || 'Sem título'"></span></span>
                                                    <span class="px-2 py-1 text-[11px] rounded-full"
                                                          :class="step.active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'">
                                                        <span x-text="step.active ? 'Ativo' : 'Inativo'"></span>
                                                    </span>
                                                </div>
                                                <div class="text-xs text-gray-600 flex flex-wrap gap-2">
                                                    <span class="text-gray-400">• Ordem</span> <span x-text="step.ordem || (index + 1)"></span>
                                                    <span class="text-gray-400">• Atraso</span>
                                                    <span x-text="(step.atraso_valor || 0) + ' ' + (step.atraso_tipo || 'hora' )"></span>
                                                    <span class="text-gray-400">• Dias</span>
                                                    <span x-text="displayDays(step.dias_semana)" class="text-gray-700"></span>
                                                    <span class="text-gray-400">• Janela</span>
                                                    <span x-text="step.janela_inicio && step.janela_fim ? step.janela_inicio + ' – ' + step.janela_fim : 'Sem janela'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button type="button" class="text-blue-600 text-sm font-semibold" @click="duplicateStep(index)">Duplicar</button>
                                            <button type="button" class="text-red-600 text-sm font-semibold" @click="removeStep(index)">Remover</button>
                                        </div>
                                    </div>

                                    <div x-show="step.expanded" x-transition class="space-y-3 pt-2">
                                        <div>
                                            <label class="text-xs font-semibold text-gray-600 uppercase">Nome do passo (opcional)</label>
                                            <input type="text" x-model="step.title" class="w-full border rounded px-3 py-2 text-sm"
                                                placeholder="Ex.: Mensagem de boas-vindas">
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                            <div>
                                                <label class="text-xs font-semibold text-gray-600 uppercase">Ordem</label>
                                                <input type="number" min="1" x-model.number="step.ordem"
                                                    :data-error="inlineErrors['step_' + index + '_ordem'] ? 'true' : null"
                                                    @input="clearError('step_' + index + '_ordem')"
                                                    class="w-full border rounded px-3 py-2 text-sm"
                                                    :class="inlineErrors['step_' + index + '_ordem'] ? 'border-red-400 ring-1 ring-red-200' : ''">
                                                <p x-show="inlineErrors['step_' + index + '_ordem']" class="text-[11px] text-red-600 mt-1" x-text="inlineErrors['step_' + index + '_ordem']"></p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-gray-600 uppercase" title="Tempo de espera após o passo anterior">Atraso</label>
                                                <div class="flex gap-2">
                                                    <input type="number" min="0" x-model.number="step.atraso_valor"
                                                        :data-error="inlineErrors['step_' + index + '_atraso'] ? 'true' : null"
                                                        @input="clearError('step_' + index + '_atraso')"
                                                        class="w-20 border rounded px-2 py-2 text-sm"
                                                        :class="inlineErrors['step_' + index + '_atraso'] ? 'border-red-400 ring-1 ring-red-200' : ''">
                                                    <select x-model="step.atraso_tipo" class="border rounded px-2 py-2 text-sm">
                                                        <option value="minuto">Minuto(s)</option>
                                                        <option value="hora">Hora(s)</option>
                                                        <option value="dia">Dia(s)</option>
                                                    </select>
                                                </div>
                                                <p class="text-[11px] text-gray-500 mt-1">A partir do início do passo anterior.</p>
                                                <p x-show="inlineErrors['step_' + index + '_atraso']" class="text-[11px] text-red-600 mt-1" x-text="inlineErrors['step_' + index + '_atraso']"></p>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-gray-600 uppercase" title="Horário mínimo para disparar este passo">Janela (início)</label>
                                                <input type="time" x-model="step.janela_inicio"
                                                    :data-error="inlineErrors['step_' + index + '_janela'] ? 'true' : null"
                                                    @input="clearError('step_' + index + '_janela')"
                                                    class="w-full border rounded px-3 py-2 text-sm"
                                                    :class="inlineErrors['step_' + index + '_janela'] ? 'border-red-400 ring-1 ring-red-200' : ''">
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-gray-600 uppercase" title="Horário máximo para disparar este passo">Janela (fim)</label>
                                                <input type="time" x-model="step.janela_fim"
                                                    :data-error="inlineErrors['step_' + index + '_janela'] ? 'true' : null"
                                                    @input="clearError('step_' + index + '_janela')"
                                                    class="w-full border rounded px-3 py-2 text-sm"
                                                    :class="inlineErrors['step_' + index + '_janela'] ? 'border-red-400 ring-1 ring-red-200' : ''">
                                                <p x-show="inlineErrors['step_' + index + '_janela']" class="text-[11px] text-red-600 mt-1" x-text="inlineErrors['step_' + index + '_janela']"></p>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-600 uppercase" title="Dias permitidos para disparar este passo">Dias da semana</label>
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
                                        <div class="flex items-center justify-between pt-2">
                                            <button type="button" class="text-sm text-gray-600" @click="scrollToTop()">Voltar ao topo</button>
                                            <button type="button" class="text-sm text-blue-600 font-semibold"
                                                x-show="index + 1 < steps.length"
                                                @click="scrollToStep(index + 1)">Ir para próximo passo</button>
                                        </div>
                                    </div>
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

    <div x-show="stepModal.open" x-cloak style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-full max-w-3xl bg-white rounded-xl shadow-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase text-gray-500 font-semibold">Novo passo</p>
                    <h3 class="text-lg font-semibold text-gray-800">Adicionar passo à sequência</h3>
                </div>
                <button type="button" class="text-gray-500 hover:text-gray-700" @click="closeStepModal()">✕</button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="text-xs font-semibold text-gray-600 uppercase">Nome do passo (opcional)</label>
                    <input type="text" x-model="stepModal.data.title" class="w-full border rounded px-3 py-2 text-sm"
                        placeholder="Ex.: Mensagem de boas-vindas">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-600 uppercase">Ordem</label>
                        <input type="number" min="1" x-model.number="stepModal.data.ordem" class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-600 uppercase">Atraso</label>
                        <div class="flex gap-2">
                            <input type="number" min="0" x-model.number="stepModal.data.atraso_valor" class="w-20 border rounded px-2 py-2 text-sm">
                            <select x-model="stepModal.data.atraso_tipo" class="border rounded px-2 py-2 text-sm">
                                <option value="minuto">Minuto(s)</option>
                                <option value="hora">Hora(s)</option>
                                <option value="dia">Dia(s)</option>
                            </select>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">A partir do início do passo anterior.</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-600 uppercase">Janela (início)</label>
                        <input type="time" x-model="stepModal.data.janela_inicio" class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-600 uppercase">Janela (fim)</label>
                        <input type="time" x-model="stepModal.data.janela_fim" class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 uppercase">Dias da semana</label>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <template x-for="day in days" :key="day.value">
                            <label class="inline-flex items-center gap-1 text-sm text-gray-700">
                                <input type="checkbox" :value="day.value" x-model="stepModal.data.dias_semana" class="text-blue-600">
                                <span x-text="day.label"></span>
                            </label>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 uppercase">Prompt do passo</label>
                    <textarea rows="3" x-model="stepModal.data.prompt" class="w-full border rounded px-3 py-2 text-sm"
                        placeholder="Instrução que o assistente deve usar para enviar a mensagem"></textarea>
                </div>
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <input type="checkbox" x-model="stepModal.data.active" class="text-blue-600">
                    Passo ativo
                </label>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="button" class="text-sm text-gray-600" @click="closeStepModal()">Cancelar</button>
                <button type="button" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white"
                    @click="saveStepFromModal">
                    Salvar passo
                </button>
            </div>
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
                inlineErrors: {},
                sequence: {
                    name: baseSequence.name ?? '',
                    description: baseSequence.description ?? '',
                    active: baseSequence.active ?? true,
                    tags_incluir: baseSequence.tags_incluir ?? [],
                    tags_excluir: baseSequence.tags_excluir ?? [],
                },
                tagInputs: { incluir: '', excluir: '' },
                tagModal: { open: false, name: '', color: '', description: '', loading: false, error: null },
                stepModal: {
                    open: false,
                    data: {
                        title: '',
                        ordem: 1,
                        atraso_tipo: 'hora',
                        atraso_valor: 1,
                        janela_inicio: '',
                        janela_fim: '',
                        dias_semana: [],
                        prompt: '',
                        active: true,
                    },
                },
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
                    expanded: false,
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
                openStepModal() {
                    this.stepModal.data = {
                        title: '',
                        ordem: this.steps.length + 1,
                        atraso_tipo: 'hora',
                        atraso_valor: 1,
                        janela_inicio: '',
                        janela_fim: '',
                        dias_semana: [],
                        prompt: '',
                        active: true,
                    };
                    this.stepModal.open = true;
                },
                closeStepModal() {
                    this.stepModal.open = false;
                },
                saveStepFromModal() {
                    this.steps.push({
                        uid: Date.now() + Math.random(),
                        ...this.stepModal.data,
                        expanded: true,
                    });
                    this.stepModal.open = false;
                },
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
                        expanded: true,
                    });
                },
                duplicateStep(index) {
                    const base = this.steps[index];
                    this.steps.splice(index + 1, 0, {
                        ...JSON.parse(JSON.stringify(base)),
                        uid: Date.now() + Math.random(),
                        expanded: true,
                        ordem: (base?.ordem ?? index + 1) + 1,
                    });
                },
                removeStep(index) {
                    this.steps.splice(index, 1);
                },
                addTag(type) {
                    const value = (type === 'incluir' ? this.tagInputs.incluir : this.tagInputs.excluir).trim();
                    if (!value) return;
                    if (!this.availableTags.some(tag => tag.name === value)) return;
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
                displayDays(list = []) {
                    if (!list.length) return 'Sem dias';
                    return list.map(v => (this.days.find(d => d.value === v)?.label || v)).join(', ');
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
                clearError(key) {
                    if (this.inlineErrors[key]) {
                        delete this.inlineErrors[key];
                    }
                },
                validateForm() {
                    this.inlineErrors = {};
                    if (!this.sequence.name || !this.sequence.name.trim()) {
                        this.inlineErrors.sequenceName = 'Informe o nome da sequência.';
                    }
                    this.steps.forEach((step, idx) => {
                        if (!step.ordem || step.ordem < 1) {
                            this.inlineErrors['step_' + idx + '_ordem'] = 'Informe a ordem.';
                        }
                        if (step.atraso_valor === null || step.atraso_valor === undefined || step.atraso_valor === '') {
                            this.inlineErrors['step_' + idx + '_atraso'] = 'Informe o atraso.';
                        }
                        if (step.janela_inicio && step.janela_fim && step.janela_inicio > step.janela_fim) {
                            this.inlineErrors['step_' + idx + '_janela'] = 'Janela inicial deve ser antes do fim.';
                        }
                    });
                    return Object.keys(this.inlineErrors).length === 0;
                },
                scrollToTop() {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                scrollToStep(idx) {
                    const target = this.steps[idx];
                    if (!target) return;
                    const el = document.getElementById('step-' + target.uid);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                },
                submit() {
                    if (!this.validateForm()) {
                        const firstErrorEl = document.querySelector('[data-error="true"]');
                        if (firstErrorEl) {
                            firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return;
                    }
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
