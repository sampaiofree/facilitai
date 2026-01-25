@extends('layouts.agencia')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Assistentes</h2>
            <p class="text-sm text-slate-500">Gerencie os assistentes vinculados ao seu usuário.</p>
        </div>
        <button id="openAssistantModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            Novo assistente
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Versão</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Instruções</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Atualizado em</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($assistants as $assistant)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $assistant->name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $assistant->version }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ \Illuminate\Support\Str::limit($assistant->instructions, 70) }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $assistant->updated_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                    data-open-edit
                                    data-id="{{ $assistant->id }}"
                                    data-name="{{ e($assistant->name) }}"
                                    data-instructions="{{ e($assistant->instructions) }}"
                                >Editar</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-slate-500">Nenhum assistente cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="assistantModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="assistantModalTitle" class="text-lg font-semibold text-slate-900">Novo assistente</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>

            <form id="assistantForm" method="POST" action="{{ route('agencia.assistant.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="assistantFormMethod" value="POST">
                <input type="hidden" name="editing_id" id="assistantEditingId" value="{{ old('editing_id') }}">

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="assistantName">Nome</label>
                    <input
                        id="assistantName"
                        name="name"
                        type="text"
                        required
                        maxlength="255"
                        value="{{ old('name') }}"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="assistantInstructions">Instruções</label>
                    <textarea
                        id="assistantInstructions"
                        name="instructions"
                        rows="4"
                        required
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >{{ old('instructions') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-close-modal>Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('assistantModal');
            const openBtn = document.getElementById('openAssistantModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('assistantForm');
            const methodInput = document.getElementById('assistantFormMethod');
            const editingInput = document.getElementById('assistantEditingId');
            const title = document.getElementById('assistantModalTitle');
            const nameInput = document.getElementById('assistantName');
            const instructionsInput = document.getElementById('assistantInstructions');
            const storeRoute = "{{ route('agencia.assistant.store') }}";
            const baseUrl = "{{ url('/agencia/assistant') }}";
            const hasErrors = @json($errors->any());
            const sessionEditingId = @json(old('editing_id'));
            const oldName = @json(old('name'));
            const oldInstructions = @json(old('instructions'));

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                form.action = storeRoute;
                methodInput.value = 'POST';
                title.textContent = 'Novo assistente';
                editingInput.value = '';
                nameInput.value = '';
                instructionsInput.value = '';
            };

            openBtn.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.querySelectorAll('[data-open-edit]').forEach(button => {
                button.addEventListener('click', () => {
                    const id = button.dataset.id;
                    resetForm();
                    form.action = `${baseUrl}/${id}`;
                    methodInput.value = 'PATCH';
                    editingInput.value = id;
                    title.textContent = 'Editar assistente';
                    nameInput.value = button.dataset.name || '';
                    instructionsInput.value = button.dataset.instructions || '';
                    openModal();
                });
            });

            if (sessionEditingId) {
                resetForm();
                form.action = `${baseUrl}/${sessionEditingId}`;
                methodInput.value = 'PATCH';
                editingInput.value = sessionEditingId;
                title.textContent = 'Editar assistente';
                nameInput.value = oldName ?? '';
                instructionsInput.value = oldInstructions ?? '';
                openModal();
            } else if (hasErrors) {
                resetForm();
                nameInput.value = oldName ?? '';
                instructionsInput.value = oldInstructions ?? '';
                openModal();
            }
        })();
    </script>
@endsection
