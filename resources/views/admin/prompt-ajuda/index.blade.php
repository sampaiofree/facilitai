@extends('layouts.adm')

@php
    $oldContext = old('form_context');
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Prompt ajuda</h2>
            <p class="text-sm text-slate-500">Gerencie tipos, seções e prompts de ajuda.</p>
        </div>
    </div>

    <div class="grid gap-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Tipos</h3>
                    <p class="text-xs text-slate-500">Categorias principais para os prompts.</p>
                </div>
                <button id="openPromptHelpTipoModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Novo</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-slate-600">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Nome</th>
                            <th class="px-3 py-2 text-left font-semibold">Descri&ccedil;&atilde;o</th>
                            <th class="px-3 py-2 text-left font-semibold">Criado em</th>
                            <th class="px-3 py-2 text-left font-semibold">A&ccedil;&otilde;es</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($tipos as $tipo)
                            <tr>
                                <td class="px-3 py-3 font-medium text-slate-900">{{ $tipo->name }}</td>
                                <td class="px-3 py-3">{{ $tipo->descricao ? \Illuminate\Support\Str::limit($tipo->descricao, 80) : '-' }}</td>
                                <td class="px-3 py-3">{{ $tipo->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                            data-tipo-edit
                                            data-id="{{ $tipo->id }}"
                                            data-name="{{ $tipo->name }}"
                                            data-descricao="{{ $tipo->descricao }}"
                                        >Editar</button>
                                        <form method="POST" action="{{ route('adm.prompt-ajuda.tipos.destroy', $tipo) }}" onsubmit="return confirm('Deseja excluir este tipo?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-slate-400">Nenhum tipo cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Se&ccedil;&otilde;es</h3>
                    <p class="text-xs text-slate-500">Organize os prompts por se&ccedil;&otilde;es.</p>
                </div>
                <button id="openPromptHelpSectionModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Novo</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-slate-600">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Tipo</th>
                            <th class="px-3 py-2 text-left font-semibold">Nome</th>
                            <th class="px-3 py-2 text-left font-semibold">Descri&ccedil;&atilde;o</th>
                            <th class="px-3 py-2 text-left font-semibold">Criado em</th>
                            <th class="px-3 py-2 text-left font-semibold">A&ccedil;&otilde;es</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sections as $section)
                            <tr>
                                <td class="px-3 py-3 text-slate-600">{{ $section->tipo->name ?? '-' }}</td>
                                <td class="px-3 py-3 font-medium text-slate-900">{{ $section->name }}</td>
                                <td class="px-3 py-3">{{ $section->descricao ? \Illuminate\Support\Str::limit($section->descricao, 80) : '-' }}</td>
                                <td class="px-3 py-3">{{ $section->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                            data-section-edit
                                            data-id="{{ $section->id }}"
                                            data-name="{{ $section->name }}"
                                            data-descricao="{{ $section->descricao }}"
                                            data-tipo-id="{{ $section->prompt_help_tipo_id }}"
                                        >Editar</button>
                                        <form method="POST" action="{{ route('adm.prompt-ajuda.sections.destroy', $section) }}" onsubmit="return confirm('Deseja excluir esta se&ccedil;&atilde;o?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-slate-400">Nenhuma se&ccedil;&atilde;o cadastrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Prompts</h3>
                    <p class="text-xs text-slate-500">Prompts prontos para reutiliza&ccedil;&atilde;o.</p>
                </div>
                <button id="openPromptHelpPromptModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Novo</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-slate-600">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Tipo</th>
                            <th class="px-3 py-2 text-left font-semibold">Se&ccedil;&atilde;o</th>
                            <th class="px-3 py-2 text-left font-semibold">Nome</th>
                            <th class="px-3 py-2 text-left font-semibold">Descri&ccedil;&atilde;o</th>
                            <th class="px-3 py-2 text-left font-semibold">Prompt</th>
                            <th class="px-3 py-2 text-left font-semibold">Criado em</th>
                            <th class="px-3 py-2 text-left font-semibold">A&ccedil;&otilde;es</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($prompts as $prompt)
                            <tr>
                                <td class="px-3 py-3 text-slate-600">{{ $prompt->section->tipo->name ?? '-' }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $prompt->section->name ?? '-' }}</td>
                                <td class="px-3 py-3 font-medium text-slate-900">{{ $prompt->name }}</td>
                                <td class="px-3 py-3">{{ $prompt->descricao ? \Illuminate\Support\Str::limit($prompt->descricao, 60) : '-' }}</td>
                                <td class="px-3 py-3">{{ \Illuminate\Support\Str::limit($prompt->prompt, 80) }}</td>
                                <td class="px-3 py-3">{{ $prompt->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                            data-prompt-edit
                                            data-id="{{ $prompt->id }}"
                                            data-name="{{ $prompt->name }}"
                                            data-descricao="{{ $prompt->descricao }}"
                                            data-prompt="{{ $prompt->prompt }}"
                                            data-section-id="{{ $prompt->prompt_help_section_id }}"
                                        >Editar</button>
                                        <form method="POST" action="{{ route('adm.prompt-ajuda.prompts.destroy', $prompt) }}" onsubmit="return confirm('Deseja excluir este prompt?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-slate-400">Nenhum prompt cadastrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="promptHelpTipoModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="promptHelpTipoModalTitle" class="text-lg font-semibold text-slate-900">Novo tipo</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>

            <form
                id="promptHelpTipoForm"
                method="POST"
                action="{{ route('adm.prompt-ajuda.tipos.store') }}"
                data-create-route="{{ route('adm.prompt-ajuda.tipos.store') }}"
                data-update-route-template="{{ route('adm.prompt-ajuda.tipos.update', ['tipo' => '__TIPO_ID__']) }}"
                class="mt-5 space-y-4"
            >
                @csrf
                <input type="hidden" name="_method" id="promptHelpTipoFormMethod" value="POST">
                <input type="hidden" name="form_context" value="tipo">
                <input type="hidden" name="record_id" id="promptHelpTipoRecordId" value="{{ $oldContext === 'tipo' ? old('record_id') : '' }}">

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="promptHelpTipoName">Nome</label>
                    <input id="promptHelpTipoName" name="name" type="text" maxlength="255" required value="{{ $oldContext === 'tipo' ? old('name') : '' }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="promptHelpTipoDescricao">Descri&ccedil;&atilde;o</label>
                    <textarea id="promptHelpTipoDescricao" name="descricao" rows="3" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $oldContext === 'tipo' ? old('descricao') : '' }}</textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-close-modal>Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="promptHelpSectionModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="promptHelpSectionModalTitle" class="text-lg font-semibold text-slate-900">Nova se&ccedil;&atilde;o</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>

            <form
                id="promptHelpSectionForm"
                method="POST"
                action="{{ route('adm.prompt-ajuda.sections.store') }}"
                data-create-route="{{ route('adm.prompt-ajuda.sections.store') }}"
                data-update-route-template="{{ route('adm.prompt-ajuda.sections.update', ['section' => '__SECTION_ID__']) }}"
                class="mt-5 space-y-4"
            >
                @csrf
                <input type="hidden" name="_method" id="promptHelpSectionFormMethod" value="POST">
                <input type="hidden" name="form_context" value="section">
                <input type="hidden" name="record_id" id="promptHelpSectionRecordId" value="{{ $oldContext === 'section' ? old('record_id') : '' }}">

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="promptHelpSectionTipo">Tipo</label>
                    <select id="promptHelpSectionTipo" name="prompt_help_tipo_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        @foreach($tipos as $tipo)
                            <option
                                value="{{ $tipo->id }}"
                                @selected($oldContext === 'section' && (string) old('prompt_help_tipo_id') === (string) $tipo->id)
                            >{{ $tipo->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="promptHelpSectionName">Nome</label>
                    <input id="promptHelpSectionName" name="name" type="text" maxlength="255" required value="{{ $oldContext === 'section' ? old('name') : '' }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="promptHelpSectionDescricao">Descri&ccedil;&atilde;o</label>
                    <textarea id="promptHelpSectionDescricao" name="descricao" rows="3" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $oldContext === 'section' ? old('descricao') : '' }}</textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-close-modal>Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="promptHelpPromptModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[600px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="promptHelpPromptModalTitle" class="text-lg font-semibold text-slate-900">Novo prompt</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>

            <form
                id="promptHelpPromptForm"
                method="POST"
                action="{{ route('adm.prompt-ajuda.prompts.store') }}"
                data-create-route="{{ route('adm.prompt-ajuda.prompts.store') }}"
                data-update-route-template="{{ route('adm.prompt-ajuda.prompts.update', ['prompt' => '__PROMPT_ID__']) }}"
                class="mt-5 space-y-4"
            >
                @csrf
                <input type="hidden" name="_method" id="promptHelpPromptFormMethod" value="POST">
                <input type="hidden" name="form_context" value="prompt">
                <input type="hidden" name="record_id" id="promptHelpPromptRecordId" value="{{ $oldContext === 'prompt' ? old('record_id') : '' }}">

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="promptHelpPromptSection">Se&ccedil;&atilde;o</label>
                    <select id="promptHelpPromptSection" name="prompt_help_section_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        @foreach($sections as $section)
                            <option
                                value="{{ $section->id }}"
                                @selected($oldContext === 'prompt' && (string) old('prompt_help_section_id') === (string) $section->id)
                            >{{ $section->tipo->name ?? '-' }} / {{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="promptHelpPromptName">Nome</label>
                    <input id="promptHelpPromptName" name="name" type="text" maxlength="255" required value="{{ $oldContext === 'prompt' ? old('name') : '' }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="promptHelpPromptDescricao">Descri&ccedil;&atilde;o</label>
                    <textarea id="promptHelpPromptDescricao" name="descricao" rows="3" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $oldContext === 'prompt' ? old('descricao') : '' }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="promptHelpPromptPrompt">Prompt</label>
                    <textarea id="promptHelpPromptPrompt" name="prompt" rows="5" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $oldContext === 'prompt' ? old('prompt') : '' }}</textarea>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-close-modal>Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('promptHelpTipoModal');
            const openBtn = document.getElementById('openPromptHelpTipoModal');
            const closeBtns = modal?.querySelectorAll('[data-close-modal]') || [];
            const form = document.getElementById('promptHelpTipoForm');
            const methodInput = document.getElementById('promptHelpTipoFormMethod');
            const title = document.getElementById('promptHelpTipoModalTitle');
            const nameInput = document.getElementById('promptHelpTipoName');
            const descricaoInput = document.getElementById('promptHelpTipoDescricao');
            const recordInput = document.getElementById('promptHelpTipoRecordId');
            const updateRouteTemplate = form?.dataset.updateRouteTemplate;

            const openModal = () => {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            };
            const closeModal = () => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            };
            const resetForm = () => {
                form.action = form.dataset.createRoute;
                methodInput.value = 'POST';
                title.textContent = 'Novo tipo';
                nameInput.value = '';
                descricaoInput.value = '';
                recordInput.value = '';
            };

            openBtn?.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
            modal?.addEventListener('click', event => {
                if (event.target === modal) closeModal();
            });

            document.querySelectorAll('[data-tipo-edit]').forEach(button => {
                button.addEventListener('click', () => {
                    const id = button.dataset.id;
                    resetForm();
                    form.action = updateRouteTemplate.replace('__TIPO_ID__', id);
                    methodInput.value = 'PATCH';
                    title.textContent = 'Editar tipo';
                    nameInput.value = button.dataset.name || '';
                    descricaoInput.value = button.dataset.descricao || '';
                    recordInput.value = id;
                    openModal();
                });
            });

            if (@json($oldContext) === 'tipo') {
                const oldId = @json($oldContext === 'tipo' ? old('record_id') : null);
                if (oldId) {
                    form.action = updateRouteTemplate.replace('__TIPO_ID__', oldId);
                    methodInput.value = 'PATCH';
                    title.textContent = 'Editar tipo';
                }
                openModal();
            }
        })();

        (() => {
            const modal = document.getElementById('promptHelpSectionModal');
            const openBtn = document.getElementById('openPromptHelpSectionModal');
            const closeBtns = modal?.querySelectorAll('[data-close-modal]') || [];
            const form = document.getElementById('promptHelpSectionForm');
            const methodInput = document.getElementById('promptHelpSectionFormMethod');
            const title = document.getElementById('promptHelpSectionModalTitle');
            const tipoSelect = document.getElementById('promptHelpSectionTipo');
            const nameInput = document.getElementById('promptHelpSectionName');
            const descricaoInput = document.getElementById('promptHelpSectionDescricao');
            const recordInput = document.getElementById('promptHelpSectionRecordId');
            const updateRouteTemplate = form?.dataset.updateRouteTemplate;

            const openModal = () => {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            };
            const closeModal = () => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            };
            const resetForm = () => {
                form.action = form.dataset.createRoute;
                methodInput.value = 'POST';
                title.textContent = 'Nova seção';
                tipoSelect.value = '';
                nameInput.value = '';
                descricaoInput.value = '';
                recordInput.value = '';
            };

            openBtn?.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
            modal?.addEventListener('click', event => {
                if (event.target === modal) closeModal();
            });

            document.querySelectorAll('[data-section-edit]').forEach(button => {
                button.addEventListener('click', () => {
                    const id = button.dataset.id;
                    resetForm();
                    form.action = updateRouteTemplate.replace('__SECTION_ID__', id);
                    methodInput.value = 'PATCH';
                    title.textContent = 'Editar seção';
                    tipoSelect.value = button.dataset.tipoId || '';
                    nameInput.value = button.dataset.name || '';
                    descricaoInput.value = button.dataset.descricao || '';
                    recordInput.value = id;
                    openModal();
                });
            });

            if (@json($oldContext) === 'section') {
                const oldId = @json($oldContext === 'section' ? old('record_id') : null);
                if (oldId) {
                    form.action = updateRouteTemplate.replace('__SECTION_ID__', oldId);
                    methodInput.value = 'PATCH';
                    title.textContent = 'Editar seção';
                }
                openModal();
            }
        })();

        (() => {
            const modal = document.getElementById('promptHelpPromptModal');
            const openBtn = document.getElementById('openPromptHelpPromptModal');
            const closeBtns = modal?.querySelectorAll('[data-close-modal]') || [];
            const form = document.getElementById('promptHelpPromptForm');
            const methodInput = document.getElementById('promptHelpPromptFormMethod');
            const title = document.getElementById('promptHelpPromptModalTitle');
            const sectionSelect = document.getElementById('promptHelpPromptSection');
            const nameInput = document.getElementById('promptHelpPromptName');
            const descricaoInput = document.getElementById('promptHelpPromptDescricao');
            const promptInput = document.getElementById('promptHelpPromptPrompt');
            const recordInput = document.getElementById('promptHelpPromptRecordId');
            const updateRouteTemplate = form?.dataset.updateRouteTemplate;

            const openModal = () => {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            };
            const closeModal = () => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            };
            const resetForm = () => {
                form.action = form.dataset.createRoute;
                methodInput.value = 'POST';
                title.textContent = 'Novo prompt';
                sectionSelect.value = '';
                nameInput.value = '';
                descricaoInput.value = '';
                promptInput.value = '';
                recordInput.value = '';
            };

            openBtn?.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
            modal?.addEventListener('click', event => {
                if (event.target === modal) closeModal();
            });

            document.querySelectorAll('[data-prompt-edit]').forEach(button => {
                button.addEventListener('click', () => {
                    const id = button.dataset.id;
                    resetForm();
                    form.action = updateRouteTemplate.replace('__PROMPT_ID__', id);
                    methodInput.value = 'PATCH';
                    title.textContent = 'Editar prompt';
                    sectionSelect.value = button.dataset.sectionId || '';
                    nameInput.value = button.dataset.name || '';
                    descricaoInput.value = button.dataset.descricao || '';
                    promptInput.value = button.dataset.prompt || '';
                    recordInput.value = id;
                    openModal();
                });
            });

            if (@json($oldContext) === 'prompt') {
                const oldId = @json($oldContext === 'prompt' ? old('record_id') : null);
                if (oldId) {
                    form.action = updateRouteTemplate.replace('__PROMPT_ID__', oldId);
                    methodInput.value = 'PATCH';
                    title.textContent = 'Editar prompt';
                }
                openModal();
            }
        })();
    </script>
@endpush
