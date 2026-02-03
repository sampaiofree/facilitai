<x-guest-layout>
    @if(isset($domainAllowed) && !$domainAllowed)
        <div class="min-h-[60vh] flex items-center justify-center">
            <div class="text-center space-y-3">
                <h2 class="text-xl font-semibold text-slate-900">Domínio não configurado</h2>
                <p class="text-sm text-slate-600">Cadastre seu domínio personalizado na dashboard para acessar.</p>
            </div>
        </div>
    @else
        <!-- Status da Sessão -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- E-mail -->
            <div>
                <x-input-label for="email" :value="__('E-mail')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Senha -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Senha')" />

                <x-text-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Lembrar-me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                    <span class="ms-2 text-sm text-gray-600">{{ __('Lembrar-me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-between mt-4">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('Esqueceu sua senha?') }}
                    </a>
                @endif

                <x-primary-button class="ms-3">
                    {{ __('Entrar') }}
                </x-primary-button>
            </div>
        </form>

        <!-- Botão para Cadastro -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">Ainda não tem uma conta? Clique <a href="{{ route('register') }}" style="font-weight: bolder;color: purple;">AQUI</a> para criar</p>
        </div>
    @endif
</x-guest-layout>
