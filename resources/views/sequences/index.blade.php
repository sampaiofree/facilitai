<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Sequências') }}
            </h2>
            <a href="{{ route('sequences.create') }}"
               class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-500">
                Nova sequência
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Passos</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Chats</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($sequences as $sequence)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900">{{ $sequence->name }}</div>
                                            <div class="text-xs text-gray-500">Criada {{ $sequence->created_at?->diffForHumans() }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $sequence->description ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center text-sm">{{ $sequence->steps_count }}</td>
                                        <td class="px-4 py-3 text-center text-sm">
                                            <div class="flex flex-col text-xs text-gray-700">
                                                <span><strong>{{ $sequence->chats_em_andamento_count }}</strong> em andamento</span>
                                                <span class="text-gray-500">{{ $sequence->chats_total_count }} no total</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 inline-flex text-xs font-semibold rounded-full {{ $sequence->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                                {{ $sequence->active ? 'Ativa' : 'Inativa' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center space-x-3">
                                            <a href="{{ route('sequences.show', $sequence) }}" class="text-sm text-gray-700 font-semibold hover:underline">Ver</a>
                                            <a href="{{ route('sequences.edit', $sequence) }}" class="text-blue-600 text-sm font-semibold hover:underline">Editar</a>
                                            <form action="{{ route('sequences.destroy', $sequence) }}" method="POST" class="inline-block"
                                                  onsubmit="return confirm('Excluir esta sequência?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 text-sm font-semibold hover:underline">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                                            Nenhuma sequência criada ainda.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        {{ $sequences->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
