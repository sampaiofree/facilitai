@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">IA Modelos</h2>
            <p class="text-sm text-slate-500">Gestão dos modelos vinculados às plataformas de IA.</p>
        </div>
        <button id="openIamodeloModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Novo modelo</button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Plataforma</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Status</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Criado em</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($iamodelos as $modelo)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 text-slate-600">{{ $modelo->iaplataforma->nome ?? '-' }}</td>
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $modelo->nome }}</td>
                        <td class="px-5 py-4">
                            @if($modelo->ativo)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Ativo</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Inativo</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $modelo->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button type="button" class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600" data-open-edit data-id="{{ $modelo->id }}" data-nome="{{ $modelo->nome }}" data-plataforma="{{ $modelo->iaplataforma_id }}" data-ativo="{{ $modelo->ativo ? '1' : '0' }}">Editar</button>
                                <form method="POST" action="{{ route('adm.iamodelos.destroy', $modelo) }}" onsubmit="return confirm('Deseja excluir este modelo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-slate-500">Nenhum modelo cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="iamodeloModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="iamodeloModalTitle" class="text-lg font-semibold text-slate-900">Novo modelo</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>
            <form id="iamodeloForm" method="POST" action="{{ route('adm.iamodelos.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="iamodeloFormMethod" value="POST">

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="iamodeloPlataforma">Plataforma</label>
                    <select id="iamodeloPlataforma" name="iaplataforma_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        @foreach($iaplataformas as $plataforma)
                            <option value="{{ $plataforma->id }}">{{ $plataforma->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="iamodeloNome">Nome</label>
                    <input id="iamodeloNome" name="nome" type="text" maxlength="50" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex items-center gap-2">
                    <input id="iamodeloAtivo" name="ativo" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                    <label for="iamodeloAtivo" class="text-sm text-slate-600">Modelo ativo</label>
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
            const modal = document.getElementById('iamodeloModal');
            const openBtn = document.getElementById('openIamodeloModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('iamodeloForm');
            const methodInput = document.getElementById('iamodeloFormMethod');
            const title = document.getElementById('iamodeloModalTitle');

            const nome = document.getElementById('iamodeloNome');
            const plataforma = document.getElementById('iamodeloPlataforma');
            const ativo = document.getElementById('iamodeloAtivo');

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };
            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                form.action = "{{ route('adm.iamodelos.store') }}";
                methodInput.value = 'POST';
                title.textContent = 'Novo modelo';
                nome.value = '';
                plataforma.value = '';
                ativo.checked = true;
            };

            openBtn.addEventListener('click', () => { resetForm(); openModal(); });

            closeBtns.forEach(btn => btn.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });

            document.querySelectorAll('[data-open-edit]').forEach(button => {
                button.addEventListener('click', () => {
                    const id = button.dataset.id;
                    resetForm();
                    form.action = `{{ url('/adm/ia-modelos') }}/${id}`;
                    methodInput.value = 'PATCH';
                    title.textContent = 'Editar modelo';
                    nome.value = button.dataset.nome || '';
                    plataforma.value = button.dataset.plataforma || '';
                    ativo.checked = button.dataset.ativo === '1';
                    openModal();
                });
            });

            if (@json($errors->any())) {
                openModal();
            }
        })();
    </script>
@endsection
