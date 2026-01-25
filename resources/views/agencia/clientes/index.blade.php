@extends('layouts.agencia')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Clientes</h2>
            <p class="text-sm text-slate-500">Lista de clientes vinculados ao seu usuário.</p>
        </div>
        <button type="button" id="openClienteModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            Novo cliente
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Email</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Telefone</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Status</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Último login</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($clientes as $cliente)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $cliente->nome }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $cliente->email }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $cliente->telefone ?? '-' }}</td>
                        <td class="px-5 py-4">
                            @if($cliente->is_active)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ativo</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Inativo</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            {{ $cliente->last_login_at?->format('d/m/Y H:i') ?? '-' }}
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                    data-open-edit
                                    data-id="{{ $cliente->id }}"
                                    data-nome="{{ $cliente->nome }}"
                                    data-email="{{ $cliente->email }}"
                                    data-telefone="{{ $cliente->telefone }}"
                                    data-active="{{ $cliente->is_active ? '1' : '0' }}"
                                >Editar</button>
                                <form method="POST" action="{{ route('agencia.clientes.destroy', $cliente) }}" onsubmit="return confirm('Deseja excluir este cliente?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-6 text-center text-slate-500">Nenhum cliente cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="clienteModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="clienteModalTitle" class="text-lg font-semibold text-slate-900">Novo cliente</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>

            <form id="clienteForm" method="POST" action="{{ route('agencia.clientes.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="clienteFormMethod" value="POST">

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="clienteNome">Nome</label>
                    <input id="clienteNome" name="nome" type="text" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="clienteEmail">Email</label>
                    <input id="clienteEmail" name="email" type="email" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="clienteTelefone">Telefone</label>
                    <input id="clienteTelefone" name="telefone" type="text" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex items-center gap-2">
                    <input id="clienteAtivo" name="is_active" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label for="clienteAtivo" class="text-sm text-slate-600">Cliente ativo</label>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="clienteSenha">Senha</label>
                    <input id="clienteSenha" name="password" type="password" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Preencha apenas para criar ou alterar">
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
            const modal = document.getElementById('clienteModal');
            const openBtn = document.getElementById('openClienteModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('clienteForm');
            const methodInput = document.getElementById('clienteFormMethod');
            const title = document.getElementById('clienteModalTitle');

            const nome = document.getElementById('clienteNome');
            const email = document.getElementById('clienteEmail');
            const telefone = document.getElementById('clienteTelefone');
            const ativo = document.getElementById('clienteAtivo');
            const senha = document.getElementById('clienteSenha');

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };
            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                form.action = "{{ route('agencia.clientes.store') }}";
                methodInput.value = 'POST';
                title.textContent = 'Novo cliente';
                nome.value = '';
                email.value = '';
                telefone.value = '';
                ativo.checked = true;
                senha.value = '';
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
                    form.action = `{{ url('/agencia/clientes') }}/${id}`;
                    methodInput.value = 'PATCH';
                    title.textContent = 'Editar cliente';
                    nome.value = button.dataset.nome || '';
                    email.value = button.dataset.email || '';
                    telefone.value = button.dataset.telefone || '';
                    ativo.checked = button.dataset.active === '1';
                    senha.value = '';
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
