<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Minhas Credenciais') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6 text-right">
                        <a href="{{ route('credentials.create') }}" class="inline-block bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">+ Nova Credencial</a>
                    </div>
                    <table class="min-w-full w-full bg-white">
                        <thead>
                            <tr>
                                <th class="w-1/3 text-left py-3 px-6">Serviço</th>
                                <th class="w-1/3 text-left py-3 px-6">Rótulo</th> {{-- <-- NOVA COLUNA --}}
                                <th class="w-1/3 text-left py-3 px-6">Token (Início)</th>
                                <th class="w-1/3 text-center py-3 px-6">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                              @forelse ($credentials as $credential)
                                    <tr class="border-b">
                                        <td class="py-4 px-6">{{ $credential->name }}</td>
                                        <td class="py-4 px-6">{{ $credential->label }}</td> {{-- <-- DADOS DA NOVA COLUNA --}}
                                        <td class="py-4 px-6 font-mono">{{ substr($credential->token, 0, 8) }}...</td>
                                        <td class="py-4 px-6 text-center">
                                            <div class="flex items-center justify-center space-x-4">
                                            <a href="{{ route('credentials.edit', $credential) }}" class="flex items-center space-x-2 px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-xs font-semibold">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                                </svg>
                                                Editar
                                            </a>
                                            <form action="{{ route('credentials.destroy', $credential) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esta credencial?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="flex items-center space-x-2 px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-xs font-semibold">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Excluir
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-6">Você ainda não possui nenhuma credencial.</td> {{-- <-- Mude o colspan para 4 --}}
                                    </tr>
        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>