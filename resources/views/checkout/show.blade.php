<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-3xl text-gray-800 leading-tight">
            Escolha seu Plano de Tokens
        </h2>
    </x-slot>

    <div class="py-12 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- Mensagem estratégica com animação --}}
            <!--<div 
                x-data="{ show: false }" 
                x-init="setTimeout(() => show = true, 100)"
                x-show="show"
                x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 text-white p-8 rounded-3xl mb-12 shadow-2xl text-center relative overflow-hidden"
            >
                {{-- Efeito de brilho animado --}}
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-20 transform -skew-x-12 animate-pulse"></div>
                
                <div class="relative z-10">
                    <div class="inline-block bg-yellow-400 text-purple-900 text-sm font-bold px-4 py-1 rounded-full mb-4">
                        🎉 OFERTA ESPECIAL
                    </div>
                    <p class="text-2xl font-bold mb-2">
                        Assine o plano anual e receba até <span class="text-yellow-300">15 milhões de tokens GRÁTIS</span> todo mês!
                    </p>
                    <p class="text-lg opacity-90">
                        + Desconto de até <span class="font-semibold text-yellow-300">90%</span> em tokens adicionais
                    </p>
                    <a 
                        href="https://wa.me/5527981227636?text=Ol%C3%A1%2C%20gostaria%20de%20saber%20mais%20sobre%20os%20planos%20anuais%20de%20tokens." 
                        class="inline-block bg-yellow-400 hover:bg-yellow-300 text-purple-900 font-bold px-8 py-3 rounded-full transition-all duration-200 transform hover:scale-105 hover:shadow-xl"
                    >
                        Ver Planos Anuais →
                    </a>
                </div>
            </div>

            {{-- Contador de clientes --}}
            <div class="text-center mb-10" x-data="{ count: 0 }" x-init="setInterval(() => { if(count < 1247) count += 7 }, 20)">
                <p class="text-gray-600 text-sm">
                    <span class="font-bold text-indigo-600 text-xl" x-text="count"></span> profissionais já escolheram nossos planos
                </p>
            </div>-->

            {{-- Cards dos pacotes com Alpine.js --}}
            <div 
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-12"
                x-data="{ hoveredPlan: null }"
            >
                @php
                    if (!Auth::user()->canManageCredentials()) {
                    $plans = [
                        ['tokens' => 200000, 'value' => 67, 'label' => '200 mil Tokens', 'desc' => 'Ideal para iniciantes', 'icon' => '🚀', 'color' => 'from-blue-500 to-cyan-500'],
                        ['tokens' => 500000, 'value' => 147, 'label' => '500 mil Tokens', 'desc' => 'Perfeito para pequenos projetos', 'icon' => '⚡', 'color' => 'from-green-500 to-emerald-500'],
                        ['tokens' => 1000000, 'value' => 247, 'label' => '1 milhão de Tokens', 'desc' => 'Mais autonomia e uso constante', 'popular' => true, 'icon' => '🔥', 'color' => 'from-purple-500 to-pink-500'],
                        ['tokens' => 2000000, 'value' => 297, 'label' => '2 milhões de Tokens', 'desc' => 'Recomendado para agências', 'icon' => '💎', 'color' => 'from-orange-500 to-red-500'],
                        ['tokens' => 5000000, 'value' => 447, 'label' => '5 milhões de Tokens', 'desc' => 'Pacote profissional com economia máxima', 'icon' => '👑', 'color' => 'from-yellow-500 to-orange-500'],
                    ];
                }else {
                    $plans = [
                        ['tokens' => 1000000, 'value' => 67, 'label' => '1 milhão de Tokens', 'desc' => 'Mais autonomia e uso constante', 'popular' => true, 'icon' => '🔥', 'color' => 'from-purple-500 to-pink-500'],
                        ['tokens' => 2000000, 'value' => 97, 'label' => '2 milhões de Tokens', 'desc' => 'Recomendado para agências', 'icon' => '💎', 'color' => 'from-orange-500 to-red-500'],
                        ['tokens' => 5000000, 'value' => 147, 'label' => '5 milhões de Tokens', 'desc' => 'Pacote profissional com economia máxima', 'icon' => '👑', 'color' => 'from-yellow-500 to-orange-500'],
                    ];
                }

                @endphp

                @foreach ($plans as $index => $plan)
                    <div 
                        x-data="{ 
                            isHovered: false,
                            pulseAnimation: false 
                        }"
                        @mouseenter="isHovered = true; hoveredPlan = {{ $index }}"
                        @mouseleave="isHovered = false; hoveredPlan = null"
                        x-init="setTimeout(() => pulseAnimation = true, {{ $index * 100 }})"
                        :class="isHovered ? 'scale-105 shadow-2xl' : 'scale-100'"
                        class="relative bg-white rounded-3xl shadow-lg p-6 flex flex-col justify-between border-2 transition-all duration-300 {{ !empty($plan['popular']) ? 'border-indigo-500' : 'border-gray-200' }}"
                    >
                        {{-- Selo "Mais Popular" animado --}}
                        @if (!empty($plan['popular']))
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-20">
                                <div class="bg-gradient-to-r {{ $plan['color'] }} text-white text-xs font-bold px-6 py-2 rounded-full shadow-lg animate-bounce">
                                    ⭐ MAIS POPULAR
                                </div>
                            </div>
                        @endif

                        {{-- Header do card com gradiente --}}
                        <div>
                            <div class="bg-gradient-to-br {{ $plan['color'] }} rounded-2xl p-4 mb-4 text-center">
                                <div class="text-5xl mb-2">{{ $plan['icon'] }}</div>
                                <h3 class="text-xl font-bold text-white">{{ $plan['label'] }}</h3>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4 min-h-[40px] text-center">{{ $plan['desc'] }}</p>
                            
                            {{-- Preço com destaque --}}
                            <div class="text-center mb-6">
                                <div class="flex items-baseline justify-center gap-1">
                                    <span class="text-gray-500 text-lg">R$</span>
                                    <span class="text-5xl font-black bg-gradient-to-r {{ $plan['color'] }} bg-clip-text text-transparent">
                                        {{ number_format($plan['value'], 0) }}
                                    </span>
                                </div>
                                <p class="text-gray-400 text-xs mt-1">pagamento único</p>
                            </div>

                            {{-- Benefícios --}}
                            <ul class="space-y-2 mb-6">
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Tokens nunca expiram
                                </li>
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Suporte prioritário
                                </li>
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Uso ilimitado
                                </li>
                            </ul>
                        </div>

                        {{-- Formulário --}}
                        <form 
                            action="{{ route('tokens.createPayment') }}" 
                            method="POST"
                        >
                            @csrf
                            <input type="hidden" name="tokens" value="{{ $plan['tokens'] }}">
                            <input type="hidden" name="value" value="{{ $plan['value'] }}">
                            <input type="hidden" name="description" value="Compra de {{ $plan['label'] }}">
                            <input type="hidden" name="externalReference" value="{{ $plan['tokens'] }}">

                            <button 
                                type="submit" 
                                :class="isHovered ? 'shadow-lg' : ''"
                                class="w-full bg-gradient-to-r {{ $plan['color'] }} text-white py-4 rounded-xl font-bold text-lg hover:shadow-xl transform transition-all duration-200 relative overflow-hidden group"
                            >
                                <span class="relative z-10">Comprar Agora</span>
                                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity duration-200"></div>
                            </button>
                        </form>

                        {{-- Badge de economia (se aplicável) --}}
                        @if($plan['tokens'] >= 1000000)
                            <div class="mt-3 text-center">
                                <span class="inline-block bg-green-100 text-green-700 text-xs font-semibold px-3 py-1 rounded-full">
                                    💰 Economia de {{ round((1 - ($plan['value'] / ($plan['tokens'] / 1000))) * 100) }}%
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Garantia e segurança --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
                <div class="bg-white rounded-2xl p-6 text-center shadow-md">
                    <div class="text-4xl mb-3">🔒</div>
                    <h4 class="font-bold text-gray-800 mb-2">Pagamento Seguro</h4>
                    <p class="text-gray-600 text-sm">Transação 100% protegida e criptografada</p>
                </div>
                <div class="bg-white rounded-2xl p-6 text-center shadow-md">
                    <div class="text-4xl mb-3">⚡</div>
                    <h4 class="font-bold text-gray-800 mb-2">Ativação Instantânea</h4>
                    <p class="text-gray-600 text-sm">Tokens disponíveis em até 2 minutos</p>
                </div>
                <div class="bg-white rounded-2xl p-6 text-center shadow-md">
                    <div class="text-4xl mb-3">💯</div>
                    <h4 class="font-bold text-gray-800 mb-2">Validade Vitalícia</h4>
                    <p class="text-gray-600 text-sm">Use quando quiser, sem pressa</p>
                </div>
            </div>

            {{-- FAQ rápido --}}
            <div class="mt-12 bg-white rounded-2xl shadow-lg p-8" x-data="{ openFaq: null }">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Perguntas Frequentes</h3>
                
                <div class="space-y-4">
                    @php
                        $faqs = [
                            ['q' => 'Os tokens expiram?', 'a' => 'Não! Seus tokens nunca expiram e ficam disponíveis para uso a qualquer momento.'],
                            ['q' => 'Posso comprar mais tokens depois?', 'a' => 'Sim! Você pode comprar tokens adicionais sempre que precisar.'],
                            ['q' => 'Como funciona a ativação?', 'a' => 'Após o pagamento confirmado, os tokens são creditados automaticamente em sua conta em até 2 minutos.'],
                        ];
                    @endphp

                    @foreach($faqs as $index => $faq)
                        <div class="border-b border-gray-200 pb-4">
                            <button 
                                @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                                class="w-full flex justify-between items-center text-left font-semibold text-gray-700 hover:text-indigo-600 transition-colors"
                            >
                                <span>{{ $faq['q'] }}</span>
                                <svg 
                                    :class="openFaq === {{ $index }} ? 'rotate-180' : ''"
                                    class="w-5 h-5 transition-transform duration-200" 
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div 
                                x-show="openFaq === {{ $index }}"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform -translate-y-2"
                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                class="mt-3 text-gray-600 text-sm"
                            >
                                {{ $faq['a'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>