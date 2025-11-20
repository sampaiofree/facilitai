@if(Auth::user()->tokensAvailable() > 0)
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Tokens Restantes -->
    <div class="bg-gradient-to-br from-indigo-600 to-blue-600 text-white rounded-2xl p-6 shadow-xl hover:scale-[1.02] transition-transform">
        <div class="flex items-center gap-4">
            <div class="bg-white/20 p-4 rounded-xl">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-medium">Tokens Restantes</h3>
                <p class="text-4xl font-extrabold mt-1">
                    {{ number_format(Auth::user()->tokensAvailable(), 0, ',', '.') }}
                </p>
                <p class="text-sm text-indigo-100 mt-1">Saldo disponível</p> 
            </div>
        </div>

        {{-- Botão Comprar Tokens --}}
        <div class="mt-6 text-center">
            <a href="{{ route('tokens.comprar') }}"
            class="inline-flex items-center justify-center gap-2 bg-white text-indigo-700 font-semibold px-5 py-2.5 rounded-xl shadow hover:bg-indigo-50 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 4v16m8-8H4" />
                </svg>
                Comprar Tokens
            </a>
        </div>
    </div>


    <!-- Tokens Comprados -->
    <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 rounded-2xl p-6 shadow-xl border border-green-500/30 hover:scale-[1.02] transition-transform">
        <div class="flex items-center gap-4">
            <div class="bg-green-500/20 p-4 rounded-xl">
                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m13-9l2 9m-5-9v9m-4-9v9"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-600">Total Comprado</h3>
                <p class="text-3xl font-bold text-green-600 mt-1">
                    {{ number_format(Auth::user()->totalTokens(), 0, ',', '.') }}
                </p>

                @if (Auth::user()->tokensBonusValidos() > 0)
                    <p class="text-sm text-green-700 mt-1">
                        + {{ number_format(Auth::user()->tokensBonusValidos(), 0, ',', '.') }} bônus
                    </p>
                @endif
            </div>
        </div>
    </div>


    <!-- Tokens Usados -->
    <div class="bg-gradient-to-br from-red-500/20 to-pink-500/20 rounded-2xl p-6 shadow-xl border border-red-500/30 hover:scale-[1.02] transition-transform">
        <div class="flex items-center gap-4">
            <div class="bg-red-500/20 p-4 rounded-xl">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 3v2.25M6.75 6.75l1.5 1.5M3 12h2.25M6.75 17.25l1.5-1.5M12 21v-2.25M17.25 17.25l-1.5-1.5M21 12h-2.25M17.25 6.75l-1.5 1.5"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-600">Total Usado</h3>
                <p class="text-3xl font-bold text-red-600 mt-1">
                    {{ number_format(Auth::user()->totalTokensUsed(), 0, ',', '.') }}
                </p>
            </div>
        </div>
    </div>
</div>
@endif
