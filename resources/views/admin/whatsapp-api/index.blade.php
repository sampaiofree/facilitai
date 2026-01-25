@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">WhatsApp API</h2>
            <p class="text-sm text-slate-500">Gerencie os registros de integrações WhatsApp.</p>
        </div>
        <button id="openWhatsappApiModal" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Novo registro</button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Slug</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Status</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Descri&ccedil;&atilde;o</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Criado em</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">A&ccedil;&otilde;es</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($whatsappApis as $api)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $api->nome }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $api->slug }}</td>
                        <td class="px-5 py-4 text-slate-600">
                            @if($api->ativo)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">Ativo</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold text-rose-700">Inativo</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            {{ $api->descricao ? \Illuminate\Support\Str::limit($api->descricao, 80) : '-' }}
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $api->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                    data-open-edit
                                    data-id="{{ $api->id }}"
                                    data-nome="{{ $api->nome }}"
                                    data-slug="{{ $api->slug }}"
                                    data-descricao="{{ $api->descricao }}"
                                    data-ativo="{{ $api->ativo ? '1' : '0' }}"
                                >Editar</button>
                                <form method="POST" action="{{ route('adm.whatsapp-api.destroy', $api) }}" onsubmit="return confirm('Deseja excluir este registro?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-6 text-center text-slate-500">Nenhum registro cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="whatsappApiModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="whatsappApiModalTitle" class="text-lg font-semibold text-slate-900">Novo registro</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-modal>x</button>
            </div>

            <form id="whatsappApiForm" method="POST" action="{{ route('adm.whatsapp-api.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="whatsappApiFormMethod" value="POST">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="whatsappApiNome">Nome</label>
                    <input id="whatsappApiNome" name="nome" type="text" maxlength="100" required value="{{ old('nome') }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="whatsappApiSlug">Slug</label>
                    <input id="whatsappApiSlug" name="slug" type="text" maxlength="120" required value="{{ old('slug') }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="whatsappApiDescricao">Descri&ccedil;&atilde;o</label>
                    <textarea id="whatsappApiDescricao" name="descricao" rows="3" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('descricao') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="whatsappApiAtivo">Status</label>
                    <select id="whatsappApiAtivo" name="ativo" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1" @selected(old('ativo', '1') === '1')>Ativo</option>
                        <option value="0" @selected(old('ativo') === '0')>Inativo</option>
                    </select>
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
            const modal = document.getElementById('whatsappApiModal');
            const openBtn = document.getElementById('openWhatsappApiModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('whatsappApiForm');
            const methodInput = document.getElementById('whatsappApiFormMethod');
            const title = document.getElementById('whatsappApiModalTitle');
            const nome = document.getElementById('whatsappApiNome');
            const slug = document.getElementById('whatsappApiSlug');
            const descricao = document.getElementById('whatsappApiDescricao');
            const statusSelect = document.getElementById('whatsappApiAtivo');

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };
            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                form.action = "{{ route('adm.whatsapp-api.store') }}";
                methodInput.value = 'POST';
                title.textContent = 'Novo registro';
                nome.value = '';
                slug.value = '';
                descricao.value = '';
                statusSelect.value = '1';
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
                    form.action = `{{ url('/adm/whatsapp-api') }}/${id}`;
                    methodInput.value = 'PATCH';
                    title.textContent = 'Editar registro';
                    nome.value = button.dataset.nome || '';
                    slug.value = button.dataset.slug || '';
                    descricao.value = button.dataset.descricao || '';
                    statusSelect.value = button.dataset.ativo || '1';
                    openModal();
                });
            });

            const oldAtivo = @json(old('ativo', '1'));

            if (@json($errors->any())) {
                statusSelect.value = oldAtivo;
                openModal();
            }
        })();
    </script>
@endsection
