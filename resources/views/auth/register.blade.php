<x-guest-layout>
    <script>
    //window.location.href = "{{ route('homepage') }}";
    </script>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nome Completo')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- CPF/CNPJ -->
        <div class="mt-4">
            <x-input-label for="cpf_cnpj" :value="__('CPF')" />
            <x-text-input id="cpf_cnpj" class="block mt-1 w-full" type="text" name="cpf_cnpj" :value="old('cpf_cnpj')" required autocomplete="cpf_cnpj"
                          x-data x-mask="999.999.999-999" placeholder="000.000.000-00"/>
            <x-input-error :messages="$errors->get('cpf_cnpj')" class="mt-2" />
        </div>

        <!-- Mobile Phone -->
        <div class="mt-4">
            <x-input-label for="mobile_phone" :value="__('Telefone celular com código do país e DDD')" />
            <x-text-input id="mobile_phone" class="block mt-1 w-full" type="text" name="mobile_phone" :value="old('mobile_phone')" required autocomplete="tel-national"
                          x-data x-mask="+99(99)99999-9999" placeholder="Ex: +99(99)9999-9999"/> {{-- Máscara para até 19 dígitos --}}
            <x-input-error :messages="$errors->get('mobile_phone')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Senha')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirme sua Senha')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Já tem uma conta?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Criar minha conta') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>