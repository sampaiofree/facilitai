@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Logs</h2>
            <p class="text-sm text-slate-500">Arquivos armazenados em <span class="font-mono">storage/logs</span>.</p>
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="logSearch">Nome do arquivo</label>
                <input id="logSearch"
                       name="nome"
                       type="text"
                       value="{{ $search ?? '' }}"
                       placeholder="ex: laravel.log"
                       class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Buscar</button>
                <a href="{{ route('adm.logs.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Limpar</a>
            </div>
        </form>
    </div>

    @if($dirMissing)
        <div class="mb-6 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            Diretório de logs não encontrado.
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Arquivo</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Tamanho</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Atualizado em</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($files as $file)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $file['name'] }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $file['size_human'] }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $file['modified_at'] }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button type="button"
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-slate-400 hover:text-slate-900"
                                    data-open-log
                                    data-file="{{ $file['name'] }}"
                                >Ver</button>
                                <a href="{{ route('adm.logs.download', ['file' => $file['name']]) }}"
                                   class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                >Baixar</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-6 text-center text-slate-500">Nenhum arquivo encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="logViewModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[920px] max-w-[95vw] max-h-[90vh] overflow-hidden rounded-2xl bg-white p-6 shadow-2xl flex flex-col">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">
                        Log: <span id="logName" class="font-mono text-slate-700"></span>
                    </h3>
                    <p id="logMeta" class="mt-1 text-xs text-slate-500"></p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-close-log>x</button>
            </div>

            <p id="logTruncatedNote" class="mt-3 hidden rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-xs text-amber-700"></p>
            <p id="logLoading" class="mt-3 text-xs text-slate-500 hidden">Carregando...</p>
            <p id="logError" class="mt-3 text-xs text-rose-600 hidden">Não foi possível carregar o conteúdo do log.</p>

            <div class="mt-3 flex-1 overflow-hidden rounded-lg border border-slate-200 bg-slate-900">
                <pre id="logContent" class="p-4 text-xs leading-relaxed text-slate-100 whitespace-pre-wrap font-mono"></pre>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const modal = document.getElementById('logViewModal');
                const closeButtons = modal.querySelectorAll('[data-close-log]');
                const openButtons = document.querySelectorAll('[data-open-log]');
                const logName = document.getElementById('logName');
                const logMeta = document.getElementById('logMeta');
                const logContent = document.getElementById('logContent');
                const logLoading = document.getElementById('logLoading');
                const logError = document.getElementById('logError');
                const logTruncatedNote = document.getElementById('logTruncatedNote');
                const showUrlTemplate = @json(route('adm.logs.show', ['file' => '__FILE__']));
                const maxBytesHuman = @json($maxBytesHuman);

                const openModal = () => {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                };
                const closeModal = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                };
                const setLoading = (state) => {
                    logLoading.classList.toggle('hidden', !state);
                };

                closeButtons.forEach(button => button.addEventListener('click', closeModal));
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                openButtons.forEach(button => {
                    button.addEventListener('click', async () => {
                        const file = button.dataset.file;
                        if (!file) {
                            return;
                        }

                        logName.textContent = file;
                        logMeta.textContent = '';
                        logContent.textContent = '';
                        logError.classList.add('hidden');
                        logTruncatedNote.classList.add('hidden');
                        setLoading(true);
                        openModal();

                        try {
                            const url = showUrlTemplate.replace('__FILE__', encodeURIComponent(file));
                            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                            if (!response.ok) {
                                throw new Error('Falha ao carregar log');
                            }

                            const data = await response.json();
                            logName.textContent = data.name ?? file;

                            const metaParts = [];
                            if (data.size_human) {
                                metaParts.push(`Tamanho: ${data.size_human}`);
                            }
                            if (data.modified_at) {
                                metaParts.push(`Atualizado: ${data.modified_at}`);
                            }
                            if (data.truncated && data.bytes_shown_human && data.size_human) {
                                metaParts.push(`Exibindo ${data.bytes_shown_human} de ${data.size_human}`);
                            }
                            logMeta.textContent = metaParts.join(' • ');

                            if (data.truncated) {
                                logTruncatedNote.textContent = `Exibindo apenas os últimos ${maxBytesHuman} do arquivo.`;
                                logTruncatedNote.classList.remove('hidden');
                            }

                            logContent.textContent = data.content ?? '';
                        } catch (error) {
                            logError.classList.remove('hidden');
                        } finally {
                            setLoading(false);
                        }
                    });
                });
            })();
        </script>
    @endpush
@endsection
