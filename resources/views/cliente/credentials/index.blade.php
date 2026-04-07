@extends('layouts.cliente')

@section('title', 'Credenciais')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Credenciais</h2>
            <p class="text-sm text-slate-500">Visualize as credenciais vinculadas à sua conta e edite apenas o token.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Name</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Plataforma</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Token</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Criado em</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($credentials as $credential)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $credential->name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ optional($credential->iaplataforma)->nome ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600 font-mono">{{ substr($credential->token, 0, 8) }}•••••</td>
                        <td class="px-5 py-4 text-slate-600">{{ $credential->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-4">
                            <button
                                type="button"
                                class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                data-open-edit
                                data-id="{{ $credential->id }}"
                                data-name="{{ $credential->name }}"
                                data-platform="{{ optional($credential->iaplataforma)->nome ?? '-' }}"
                            >Editar token</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div id="credentialModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Editar token da credencial</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>

            <form id="credentialForm" method="POST" action="" class="mt-5 space-y-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="editing_id" id="credentialEditingId" value="{{ old('editing_id') }}">

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Name</label>
                    <div id="credentialNameDisplay" class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"></div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Plataforma</label>
                    <div id="credentialPlatformDisplay" class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"></div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="credentialToken">Token</label>
                    <textarea id="credentialToken" name="token" rows="4" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Cole o token atualizado aqui">{{ old('token') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-close-modal>Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar token</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('credentialModal');
        const closeBtns = modal.querySelectorAll('[data-close-modal]');
        const form = document.getElementById('credentialForm');
        const editingIdInput = document.getElementById('credentialEditingId');
        const nameDisplay = document.getElementById('credentialNameDisplay');
        const platformDisplay = document.getElementById('credentialPlatformDisplay');
        const tokenInput = document.getElementById('credentialToken');
        const buttons = Array.from(document.querySelectorAll('[data-open-edit]'));
        const sessionEditingId = @json(old('editing_id'));

        const openModal = () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        const populateModal = (button) => {
            const id = button.dataset.id;

            form.action = `{{ url('/cliente/credenciais') }}/${id}`;
            editingIdInput.value = id || '';
            nameDisplay.textContent = button.dataset.name || '-';
            platformDisplay.textContent = button.dataset.platform || '-';

            if (sessionEditingId !== String(id)) {
                tokenInput.value = '';
            }
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                populateModal(button);
                openModal();
            });
        });

        closeBtns.forEach((button) => button.addEventListener('click', closeModal));

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        if (sessionEditingId) {
            const currentButton = buttons.find((button) => button.dataset.id === String(sessionEditingId));
            if (currentButton) {
                populateModal(currentButton);
                openModal();
            }
        }
    })();
</script>
@endpush
