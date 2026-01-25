<div id="createInstanceModal" class="fixed inset-0 hidden z-50 items-center justify-center bg-black/40 backdrop-blur px-4" aria-hidden="true">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between">
            <h3 id="create-instance-title" class="text-lg font-semibold text-gray-800">Nova instancia</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" data-modal-close="true" aria-label="Fechar">x</button>
        </div>

        <form method="POST" action="{{ route('uazapi.instances.create') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="instance-name" class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Nome</label>
                <input
                    id="instance-name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    maxlength="255"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50" data-modal-close="true">Cancelar</button>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Criar</button>
            </div>
        </form>
    </div>
</div>
