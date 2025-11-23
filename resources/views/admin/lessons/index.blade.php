<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Aulas de Onboarding
            </h2>
            <a href="{{ route('admin.lessons.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 transition ease-in-out duration-150">
                Nova aula
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="text-sm text-gray-600 mb-4">
                        As aulas aparecem conforme a URL acessada (campo <strong>Página</strong>) e são ordenadas pelo campo <strong>Ordem</strong>.
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Título</th>
                                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Página</th>
                                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Locale</th>
                                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Ordem</th>
                                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lessons as $lesson)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-3 px-4">
                                            <div class="font-semibold text-gray-900">{{ $lesson->title }}</div>
                                            <div class="text-xs text-gray-500">{{ $lesson->video_url }}</div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                                                {{ $lesson->page_match }}
                                            </span>
                                            <span class="ml-2 text-xs text-gray-500">{{ $lesson->match_type === 'prefix' ? 'Prefixo' : 'Exata' }}</span>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-gray-700">{{ $lesson->locale }}</td>
                                        <td class="py-3 px-4 text-sm text-gray-700">{{ $lesson->position }}</td>
                                        <td class="py-3 px-4">
                                            @if ($lesson->is_active)
                                                <span class="text-green-700 bg-green-100 px-2 py-1 rounded-full text-xs font-semibold">Ativa</span>
                                            @else
                                                <span class="text-gray-600 bg-gray-100 px-2 py-1 rounded-full text-xs font-semibold">Inativa</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 space-x-2">
                                            <a href="{{ route('admin.lessons.edit', $lesson) }}" class="text-indigo-600 hover:underline text-sm">Editar</a>
                                            <form action="{{ route('admin.lessons.destroy', $lesson) }}" method="POST" class="inline-block" onsubmit="return confirm('Remover esta aula?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline text-sm">Remover</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-6 text-gray-600">Nenhuma aula cadastrada.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $lessons->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
