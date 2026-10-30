@extends('layouts.adm')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Storage</h2>
            <p class="mt-1 text-sm text-slate-500">Mídias da biblioteca dos usuários e arquivos recebidos pelo WhatsApp.</p>
        </div>
        <a href="{{ route('adm.storage.index', $filters) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Atualizar listagem</a>
    </div>

    <form method="GET" action="{{ route('adm.storage.index') }}" class="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-4 xl:grid-cols-4 xl:items-end">
        <div>
            <label for="storageUser" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Usuário</label>
            <select id="storageUser" name="user" class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Todos os usuários</option>
                <option value="unknown" @selected(($filters['user'] ?? '') === 'unknown')>Usuário não identificado</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) ($filters['user'] ?? '') === (string) $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="storageDateFrom" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Modificado a partir de</label>
            <input id="storageDateFrom" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
            <label for="storageDateTo" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Modificado até</label>
            <input id="storageDateTo" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Filtrar</button>
            <a href="{{ route('adm.storage.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Limpar</a>
        </div>
    </form>

    <p class="mb-4 text-xs text-slate-500">As datas consideram a última modificação do arquivo, no horário de Brasília. As mídias recebidas pelo WhatsApp não possuem usuário identificado.</p>
    @foreach($inventoryErrors as $error)
        <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ $error }} A listagem pode estar incompleta.</p>
    @endforeach

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
            <div>
                <p class="text-sm font-semibold text-slate-700">{{ number_format($files->total(), 0, ',', '.') }} arquivo(s) encontrado(s) · {{ \Illuminate\Support\Number::fileSize($totalSize, precision: 2) }}</p>
                <p id="storageSelectionCount" class="mt-1 text-xs text-slate-500" aria-live="polite">Nenhum arquivo selecionado.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button id="storageSelectAll" type="button" @disabled($files->total() === 0) class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50">Selecionar todos os filtrados</button>
                <button id="storageClearSelection" type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Limpar seleção</button>
                <form id="storageBulkDelete" action="{{ route('adm.storage.bulk-destroy') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="snapshot" value="{{ $snapshotToken }}">
                    <input id="storageSelectionMode" type="hidden" name="mode" value="selected">
                    <input id="storageSelectionValue" type="hidden" name="selection" value="[]">
                    <button id="storageDeleteSelected" type="submit" disabled class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50">Excluir selecionados</button>
                </form>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3"><span class="sr-only">Selecionar</span></th>
                        <th class="px-4 py-3 text-left">Arquivo</th>
                        <th class="px-4 py-3 text-left">Usuário relacionado</th>
                        <th class="px-4 py-3 text-left">Origem</th>
                        <th class="px-4 py-3 text-left">Tamanho</th>
                        <th class="px-4 py-3 text-left">Modificado em</th>
                        <th class="px-4 py-3 text-left">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($files as $file)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4"><input type="checkbox" data-storage-file="{{ $file['id'] }}" aria-label="Selecionar {{ $file['name'] }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"></td>
                            <td class="px-4 py-4">
                                <p class="break-all font-medium text-slate-800">{{ $file['name'] }}</p>
                                <p class="mt-1 break-all text-xs text-slate-400">{{ $file['disk'] }} / {{ $file['path'] }}</p>
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                <p>{{ $file['user_name'] ?? 'Usuário não identificado' }}</p>
                                @if($file['user_email'])<p class="mt-1 text-xs text-slate-400">{{ $file['user_email'] }}</p>@endif
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ $file['origin'] }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ \Illuminate\Support\Number::fileSize($file['size'], precision: 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ \Illuminate\Support\Carbon::createFromTimestamp($file['modified'], 'America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('adm.storage.show', ['file' => $file['id'], 'snapshot' => $snapshotToken]) }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Ver</a>
                                    <form method="POST" action="{{ route('adm.storage.destroy', ['file' => $file['id']]) }}" data-storage-delete>
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="snapshot" value="{{ $snapshotToken }}">
                                        <button type="submit" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">Nenhum arquivo encontrado para os filtros selecionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($files->hasPages())<div class="border-t border-slate-200 p-4">{{ $files->links() }}</div>@endif
    </div>

    @push('scripts')
        <script>
            (() => {
                const total = @json($files->total());
                const key = 'admin-storage-selection:' + @json($snapshotToken);
                let all = false;
                let selected = new Set();
                try {
                    const saved = JSON.parse(sessionStorage.getItem(key));
                    if (saved && Array.isArray(saved.ids)) {
                        all = saved.all === true;
                        selected = new Set(saved.ids);
                    }
                } catch (_) {}

                const checkboxes = document.querySelectorAll('[data-storage-file]');
                const bulkForm = document.getElementById('storageBulkDelete');
                const deleteButton = document.getElementById('storageDeleteSelected');
                const count = () => all ? total - selected.size : selected.size;
                const update = () => {
                    checkboxes.forEach(input => {
                        input.checked = all ? !selected.has(input.dataset.storageFile) : selected.has(input.dataset.storageFile);
                    });
                    document.getElementById('storageSelectionCount').textContent = `${count()} arquivo(s) selecionado(s) de ${total}.`;
                    document.getElementById('storageSelectionMode').value = all ? 'all' : 'selected';
                    document.getElementById('storageSelectionValue').value = JSON.stringify([...selected]);
                    deleteButton.disabled = count() === 0;
                    try { sessionStorage.setItem(key, JSON.stringify({ all, ids: [...selected] })); } catch (_) {}
                };
                checkboxes.forEach(input => input.addEventListener('change', () => {
                    if (all ? !input.checked : input.checked) selected.add(input.dataset.storageFile);
                    else selected.delete(input.dataset.storageFile);
                    update();
                }));
                document.getElementById('storageSelectAll').addEventListener('click', () => {
                    all = true;
                    selected.clear();
                    update();
                });
                document.getElementById('storageClearSelection').addEventListener('click', () => {
                    all = false;
                    selected.clear();
                    update();
                });
                bulkForm.addEventListener('submit', event => {
                    if (!count() || !confirm(`Excluir permanentemente ${count()} arquivo(s) selecionado(s)? A exclusão também remove as mídias da biblioteca e atualiza o espaço usado.`)) {
                        event.preventDefault();
                    } else {
                        deleteButton.disabled = true;
                    }
                });
                document.querySelectorAll('[data-storage-delete]').forEach(form => form.addEventListener('submit', event => {
                    if (!confirm('Excluir permanentemente este arquivo? Se estiver na biblioteca, o registro e o espaço usado também serão atualizados.')) event.preventDefault();
                }));
                update();
            })();
        </script>
    @endpush
@endsection
