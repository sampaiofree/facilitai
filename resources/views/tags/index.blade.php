<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tags') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @if (session('success'))
                    <div class="rounded-md bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
                @endif
                @if (session('warning'))
                    <div class="rounded-md bg-yellow-50 px-4 py-3 text-yellow-800">{{ session('warning') }}</div>
                @endif

                <div class="border border-gray-100 rounded-lg p-4 bg-gray-50/50">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Criar nova tag</h3>
                    <form action="{{ route('tags.store') }}" method="POST" class="grid gap-3 md:grid-cols-2 items-end">
                        @csrf
                        <div class="md:col-span-1">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Nome</label>
                            <input type="text" name="name" required
                                class="mt-1 w-full rounded-md border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Ex.: vip, suporte">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Descrição</label>
                            <input type="text" name="description"
                                class="mt-1 w-full rounded-md border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Uso interno (opcional)">
                        </div>
                        <div class="md:col-span-2 flex justify-end">
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-500">
                                Salvar tag
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($tags as $tag)
                                <tr>
                                    <td class="px-4 py-3">
                                        <form action="{{ route('tags.update', $tag) }}" method="POST" class="space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="name" value="{{ $tag->name }}"
                                                class="w-full rounded-md border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                            <div class="text-xs text-gray-500">Criada {{ $tag->created_at?->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                            <input type="text" name="description" value="{{ $tag->description }}"
                                                class="w-full rounded-md border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </td>
                                    <td class="px-4 py-3 text-center space-y-2">
                                            <button type="submit"
                                                class="w-full rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500">
                                                Atualizar
                                            </button>
                                        </form>
                                        <form action="{{ route('tags.destroy', $tag) }}" method="POST" class="w-full">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="w-full rounded-md bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-100">
                                                Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">
                                        Nenhuma tag criada ainda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
