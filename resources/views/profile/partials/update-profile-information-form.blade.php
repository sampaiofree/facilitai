<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Informações do Perfil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Atualize as informações do seu perfil e seus dados de contato.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- Nome -->
        <div>
            <x-input-label for="name" :value="__('Nome completo')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <!-- CPF/CNPJ -->
        <div>
            <x-input-label for="cpf_cnpj" :value="__('CPF/CNPJ')" />
            <x-text-input id="cpf_cnpj" name="cpf_cnpj" type="text" class="mt-1 block w-full"
                :value="old('cpf_cnpj', $user->cpf_cnpj)" required
                x-data x-mask="99999999999999" placeholder="Somente números" />
            <x-input-error class="mt-2" :messages="$errors->get('cpf_cnpj')" />
        </div>

        <!-- Telefone Celular -->
        <div>
            <x-input-label for="mobile_phone" :value="__('Telefone celular (apenas números)')" />
            <x-text-input id="mobile_phone" name="mobile_phone" type="text" class="mt-1 block w-full"
                :value="old('mobile_phone', $user->mobile_phone)" required
                x-data x-mask="999999999999999" placeholder="Ex: 62999999999" />
            <x-input-error class="mt-2" :messages="$errors->get('mobile_phone')" />
        </div>

        <!-- Campo oculto de e-mail -->
        <input type="hidden" name="email" value="{{ $user->email }}">


        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Salvar') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-600 font-medium"
                >{{ __('Alterações salvas com sucesso.') }}</p>
            @endif
        </div>
    </form>
</section>
