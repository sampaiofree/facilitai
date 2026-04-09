@extends('layouts.cliente')

@section('title', 'Campos personalizados')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Campos personalizados</h2>
            <p class="text-sm text-slate-500">Gerencie os campos personalizados reutilizáveis dos leads do seu cliente.</p>
        </div>
        <button id="openCustomFieldModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Novo campo
        </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Nome técnico</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Rótulo</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Exemplo padrão</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Descrição</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($fields as $field)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-mono text-slate-800">{{ $field->name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $field->label ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $field->sample_value ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $field->description ?? '—' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                    data-edit-custom-field
                                    data-id="{{ $field->id }}"
                                    data-name="{{ $field->name }}"
                                    data-label="{{ $field->label }}"
                                    data-sample-value="{{ $field->sample_value }}"
                                    data-description="{{ $field->description }}"
                                >
                                    Editar
                                </button>
                                <form method="POST" action="{{ route('cliente.campos-personalizados.destroy', $field) }}" onsubmit="return confirm('Deseja excluir este campo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-slate-500">Nenhum campo personalizado cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="customFieldModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[640px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="customFieldModalTitle" class="text-lg font-semibold text-slate-900">Novo campo personalizado</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-custom-field-close>x</button>
            </div>

            <form id="customFieldForm" method="POST" action="{{ route('cliente.campos-personalizados.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="customFieldFormMethod" value="POST">

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="customFieldName">Nome do campo</label>
                    <input
                        id="customFieldName"
                        name="name"
                        type="text"
                        maxlength="120"
                        required
                        placeholder="Ex: Nome Cliente"
                        class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                    >
                    <p class="mt-1 text-xs text-slate-400">Será normalizado automaticamente para minúsculo sem acentos/espaços, com sufixo se já existir para este cliente.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="customFieldLabel">Rótulo (opcional)</label>
                        <input id="customFieldLabel" name="label" type="text" maxlength="120" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="customFieldSampleValue">Exemplo padrão (opcional)</label>
                        <input id="customFieldSampleValue" name="sample_value" type="text" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="customFieldDescription">Descrição (opcional)</label>
                    <textarea id="customFieldDescription" name="description" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-custom-field-close>Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('customFieldModal');
            const openButton = document.getElementById('openCustomFieldModal');
            const closeButtons = modal.querySelectorAll('[data-custom-field-close]');
            const form = document.getElementById('customFieldForm');
            const formMethod = document.getElementById('customFieldFormMethod');
            const modalTitle = document.getElementById('customFieldModalTitle');
            const nameInput = document.getElementById('customFieldName');
            const labelInput = document.getElementById('customFieldLabel');
            const sampleValueInput = document.getElementById('customFieldSampleValue');
            const descriptionInput = document.getElementById('customFieldDescription');

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                form.action = "{{ route('cliente.campos-personalizados.store') }}";
                formMethod.value = 'POST';
                modalTitle.textContent = 'Novo campo personalizado';
                nameInput.value = '';
                labelInput.value = '';
                sampleValueInput.value = '';
                descriptionInput.value = '';
            };

            openButton.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.querySelectorAll('[data-edit-custom-field]').forEach((button) => {
                button.addEventListener('click', () => {
                    resetForm();
                    const id = button.dataset.id;
                    form.action = `{{ url('/cliente/campos-personalizados') }}/${id}`;
                    formMethod.value = 'PATCH';
                    modalTitle.textContent = 'Editar campo personalizado';
                    nameInput.value = button.dataset.name || '';
                    labelInput.value = button.dataset.label || '';
                    sampleValueInput.value = button.dataset.sampleValue || '';
                    descriptionInput.value = button.dataset.description || '';
                    openModal();
                });
            });
        })();
    </script>
@endpush
