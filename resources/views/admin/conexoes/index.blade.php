@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Conexões</h2>
            <p class="text-sm text-slate-500">Gerencie as conexões disponíveis na plataforma.</p>
        </div>
        <button type="button" id="openConexaoModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            Nova conexão
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Usuário</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Cliente</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Telefone</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Assistente</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Modelo</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">WhatsApp API</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($conexoes as $conexao)
                    @php
                        $viewData = [
                            'nome' => $conexao->name,
                            'status' => $conexao->ativo ? 'Ativo' : 'Inativo',
                            'created_at' => $conexao->created_at?->format('d/m/Y H:i') ?? '-',
                            'informacoes' => $conexao->informacoes ?? '-',
                            'cliente' => optional($conexao->cliente)->nome ?? '-',
                            'cliente_id' => $conexao->cliente_id ?? '-',
                            'user_id' => optional($conexao->cliente)->user_id ?? '-',
                            'user_name' => optional($conexao->cliente->user)->name ?? '-',
                            'credential' => optional($conexao->credential)->name ?? '-',
                            'assistant' => optional($conexao->assistant)->name ?? '-',
                            'modelo' => optional($conexao->iamodelo)->nome ?? '-',
                            'whatsapp_api' => optional($conexao->whatsappApi)->nome ?? '-',
                            'whatsapp_api_key' => $conexao->whatsapp_api_key ?? '-',
                            'phone' => $conexao->phone ?? '-',
                            'proxy_ip' => $conexao->proxy_ip ?? '-',
                            'proxy_port' => $conexao->proxy_port ? (string) $conexao->proxy_port : '-',
                            'proxy_username' => $conexao->proxy_username ?? '-',
                            'proxy_password' => $conexao->proxy_password ?? '-',
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $conexao->name }}</td>
                        <td class="px-5 py-4 text-slate-600">
                            <div class="font-medium text-slate-800">{{ optional($conexao->cliente)->user_id ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ optional($conexao->cliente->user)->name ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            <div class="font-medium text-slate-800">{{ $conexao->cliente_id ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ optional($conexao->cliente)->nome ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $conexao->phone ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ optional($conexao->assistant)->name ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ optional($conexao->iamodelo)->nome ?? '-' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ optional($conexao->whatsappApi)->nome ?? '-' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                    data-open-edit
                                    data-id="{{ $conexao->id }}"
                                    data-nome="{{ $conexao->name }}"
                                    data-ativo="{{ $conexao->ativo ? '1' : '0' }}"
                                >Editar</button>
                                <button
                                    type="button"
                                    class="rounded-lg bg-slate-600 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700"
                                    data-open-view
                                    data-conexao='@json($viewData)'
                                >Ver</button>
                                <form method="POST" action="{{ route('adm.conexoes.destroy', $conexao) }}" onsubmit="return confirm('Deseja excluir esta conexão?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-6 text-center text-slate-500">Nenhuma conexão cadastrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="conexaoModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="conexaoModalTitle" class="text-lg font-semibold text-slate-900">Nova conexão</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>

            <form id="conexaoForm" method="POST" action="{{ route('adm.conexoes.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="conexaoFormMethod" value="POST">

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="conexaoNome">Nome</label>
                    <input id="conexaoNome" name="nome" type="text" maxlength="50" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex items-center gap-2">
                    <input id="conexaoAtivo" name="ativo" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label for="conexaoAtivo" class="text-sm text-slate-600">Conexão ativa</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-close-modal>Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="conexaoViewModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Detalhes da conexão</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-view-close>x</button>
            </div>

            <div class="mt-5 space-y-4 text-sm text-slate-600">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Nome</p>
                    <p id="viewConexaoNome" class="font-medium text-slate-900"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Status</p>
                    <p id="viewConexaoStatus" class="font-medium"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Criado em</p>
                    <p id="viewConexaoCreatedAt"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Cliente</p>
                    <p id="viewConexaoCliente"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Credencial</p>
                    <p id="viewConexaoCredential"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Assistente</p>
                    <p id="viewConexaoAssistant"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Modelo IA</p>
                    <p id="viewConexaoModelo"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Integração WhatsApp</p>
                    <p id="viewConexaoWhatsappApi"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Chave WhatsApp API</p>
                    <p id="viewConexaoWhatsappApiKey" class="font-mono text-xs"></p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Telefone</p>
                    <p id="viewConexaoPhone"></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Proxy IP</p>
                        <p id="viewConexaoProxyIp"></p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Porta</p>
                        <p id="viewConexaoProxyPort"></p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Usuário proxy</p>
                        <p id="viewConexaoProxyUsername"></p>
                    </div>
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Senha proxy</p>
                        <p id="viewConexaoProxyPassword"></p>
                    </div>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400">Informações</p>
                    <p id="viewConexaoInformacoes"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('conexaoModal');
            const openBtn = document.getElementById('openConexaoModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('conexaoForm');
            const methodInput = document.getElementById('conexaoFormMethod');
            const title = document.getElementById('conexaoModalTitle');

            const nome = document.getElementById('conexaoNome');
            const ativo = document.getElementById('conexaoAtivo');

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };
            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                form.action = "{{ route('adm.conexoes.store') }}";
                methodInput.value = 'POST';
                title.textContent = 'Nova conexão';
                nome.value = '';
                ativo.checked = true;
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
            const viewModal = document.getElementById('conexaoViewModal');
            const viewCloseBtn = viewModal?.querySelector('[data-view-close]');
            const viewFields = {
                nome: document.getElementById('viewConexaoNome'),
                status: document.getElementById('viewConexaoStatus'),
                created_at: document.getElementById('viewConexaoCreatedAt'),
                cliente: document.getElementById('viewConexaoCliente'),
                credential: document.getElementById('viewConexaoCredential'),
                assistant: document.getElementById('viewConexaoAssistant'),
                modelo: document.getElementById('viewConexaoModelo'),
                whatsapp_api: document.getElementById('viewConexaoWhatsappApi'),
                phone: document.getElementById('viewConexaoPhone'),
                proxy_ip: document.getElementById('viewConexaoProxyIp'),
                proxy_port: document.getElementById('viewConexaoProxyPort'),
                proxy_username: document.getElementById('viewConexaoProxyUsername'),
                proxy_password: document.getElementById('viewConexaoProxyPassword'),
                informacoes: document.getElementById('viewConexaoInformacoes'),
                whatsapp_api_key: document.getElementById('viewConexaoWhatsappApiKey'),
            };

            const openViewModal = () => {
                if (!viewModal) return;
                viewModal.classList.remove('hidden');
                viewModal.classList.add('flex');
            };
            const closeViewModal = () => {
                if (!viewModal) return;
                viewModal.classList.add('hidden');
                viewModal.classList.remove('flex');
            };

            document.querySelectorAll('[data-open-view]').forEach(button => {
                button.addEventListener('click', () => {
                    const data = button.dataset.conexao ? JSON.parse(button.dataset.conexao) : {};
                    Object.entries(viewFields).forEach(([key, element]) => {
                        if (!element) return;
                        element.textContent = data[key] ?? '-';
                    });
                    openViewModal();
                });
            });

            viewCloseBtn?.addEventListener('click', closeViewModal);
            viewModal?.addEventListener('click', (event) => {
                if (event.target === viewModal) {
                    closeViewModal();
                }
            });

            document.querySelectorAll('[data-open-edit]').forEach(button => {
                button.addEventListener('click', () => {
                    const id = button.dataset.id;
                    resetForm();
                    form.action = `{{ url('/adm/conexoes') }}/${id}`;
                    methodInput.value = 'PATCH';
                    title.textContent = 'Editar conexão';
                    nome.value = button.dataset.nome || '';
                    ativo.checked = button.dataset.ativo === '1';
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
