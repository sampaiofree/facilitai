@extends('layouts.agencia')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Credenciais</h2>
            <p class="text-sm text-slate-500">Gerencie tokens vinculados ao seu usuário.</p>
        </div>
        <button type="button" id="openCredentialModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            Nova credencial
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Name</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Label</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Plataforma</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Token</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Criado em</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($credentials as $credential)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $credential->name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $credential->label }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ optional($credential->iaplataforma)->nome ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600 font-mono">{{ substr($credential->token, 0, 8) }}•••••</td>
                        <td class="px-5 py-4 text-slate-600">{{ $credential->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                    data-open-edit
                                    data-id="{{ $credential->id }}"
                                    data-name="{{ $credential->name }}"
                                    data-name="{{ $credential->name }}"
                                    data-iaplataforma-id="{{ $credential->iaplataforma_id }}"
                                >Editar</button>
                                <form method="POST" action="{{ route('agencia.credentials.destroy', $credential) }}" onsubmit="return confirm('Deseja excluir esta credencial?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-6 text-center text-slate-500">Nenhuma credencial cadastrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="credentialModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="credentialModalTitle" class="text-lg font-semibold text-slate-900">Nova credencial</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>

            <form id="credentialForm" method="POST" action="{{ route('agencia.credentials.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="credentialFormMethod" value="POST">

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="credentialName">Name</label>
                    <input
                        id="credentialName"
                        name="name"
                        type="text"
                        required
                        placeholder="Ex: OpenAI"
                        value="{{ old('name') }}"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="credentialIaplataforma">Plataforma</label>
                    <select id="credentialIaplataforma" name="iaplataforma_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Selecione uma plataforma</option>
                        @foreach ($iaplataformas as $iaplataforma)
                            <option value="{{ $iaplataforma->id }}" @selected(old('iaplataforma_id') == $iaplataforma->id)>{{ $iaplataforma->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="credentialToken">Token</label>
                    <textarea id="credentialToken" name="token" rows="3" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Cole o token aqui">{{ old('token') }}</textarea>
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
            const modal = document.getElementById('credentialModal');
            const openBtn = document.getElementById('openCredentialModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('credentialForm');
            const methodInput = document.getElementById('credentialFormMethod');
            const title = document.getElementById('credentialModalTitle');

            const name = document.getElementById('credentialName');
            const plataforma = document.getElementById('credentialIaplataforma');
            const token = document.getElementById('credentialToken');

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };
            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                form.action = "{{ route('agencia.credentials.store') }}";
                methodInput.value = 'POST';
                title.textContent = 'Nova credencial';
                name.value = '';
                plataforma.value = '';
                token.value = '';
                token.required = true;
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
                    form.action = `{{ url('/agencia/credenciais') }}/${id}`;
                    methodInput.value = 'PATCH';
                    title.textContent = 'Editar credencial';
                    name.value = button.dataset.name || '';
                    plataforma.value = button.dataset.iaplataformaId || '';
                    token.value = '';
                    token.required = false;
                    openModal();
                });
            });

            const shouldOpen = @json($errors->any());
            if (shouldOpen) {
                openModal();
            }
        })();
    </script>
@endsection


