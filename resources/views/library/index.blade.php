<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Minha Biblioteca
                </h2>
                <p class="text-sm text-gray-600">Armazene textos em Markdown e gere links públicos rápidos.</p>
            </div>
            <a href="{{ route('library.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Novo texto</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    @if (session('success'))
                        <div class="p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
                    @endif

                    <form method="GET" action="{{ route('library.index') }}" class="flex flex-wrap items-center gap-3">
                        <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por título ou conteúdo" class="border rounded-lg px-3 py-2 text-sm w-64">
                        <select name="sort" class="border rounded-lg px-3 py-2 text-sm">
                            <option value="created_at" {{ $sortField === 'created_at' ? 'selected' : '' }}>Data</option>
                            <option value="title" {{ $sortField === 'title' ? 'selected' : '' }}>Título</option>
                        </select>
                        <select name="direction" class="border rounded-lg px-3 py-2 text-sm">
                            <option value="desc" {{ $sortDirection === 'desc' ? 'selected' : '' }}>Desc</option>
                            <option value="asc" {{ $sortDirection === 'asc' ? 'selected' : '' }}>Asc</option>
                        </select>
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2 pr-4">Título</th>
                                    <th class="py-2 pr-4">Slug</th>
                                    <th class="py-2 pr-4">Criado em</th>
                                    <th class="py-2 pr-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $entry)
                                    <tr class="border-b">
                                        <td class="py-3 pr-4 font-semibold text-gray-800">{{ $entry->title }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $entry->slug }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="py-3 pr-4 text-right space-x-2">
                                            <button type="button" class="inline-flex items-center gap-1 text-xs text-blue-700 font-semibold copy-link" data-url="{{ route('library.public.show', $entry->slug) }}">
                                                Copiar link
                                            </button>
                                            <a href="{{ route('library.edit', $entry) }}" class="text-xs text-gray-700 font-semibold inline-flex items-center gap-1">Editar</a>
                                            <form action="{{ route('library.destroy', $entry) }}" method="POST" class="inline" onsubmit="return confirm('Excluir este texto?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 font-semibold inline-flex items-center gap-1">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-center text-gray-500">Nenhum texto encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $entries->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.copy-link').forEach(btn => {
            btn.addEventListener('click', async () => {
                const url = btn.dataset.url;
                try {
                    await navigator.clipboard.writeText(url);
                    btn.textContent = 'Link copiado!';
                    setTimeout(() => btn.textContent = 'Copiar link', 1500);
                } catch (err) {
                    alert('Não foi possível copiar o link.');
                }
            });
        });
    });
</script>
</x-app-layout>
