<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Histórico de Pagamentos') }}
            </h2>
            
            <!-- Botão Comprar Tokens -->
            <a href="{{ route('tokens.comprar') }}" 
               class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow">
                Comprar Tokens
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            
        @include('partials.tokens-summary') 



            <!-- Tabela de Pagamentos -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left py-3 px-6">Data</th>
                                    <th class="text-left py-3 px-6">Tokens Comprados</th>
                                    <th class="text-left py-3 px-6">Valor</th>
                                    <th class="text-left py-3 px-6">Status</th>
                                    <th class="text-center py-3 px-6">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                @forelse ($payments as $payment)
                                    @php
                                        $statusInfo = match($payment->event_type) {
                                            'PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED' => ['text' => 'Pago', 'class' => 'bg-green-200 text-green-800'],
                                            'PAYMENT_CREATED' => ['text' => 'Pendente', 'class' => 'bg-yellow-200 text-yellow-800'],
                                            'PAYMENT_OVERDUE' => ['text' => 'Vencido', 'class' => 'bg-red-200 text-red-800'],
                                            'PAYMENT_REFUNDED' => ['text' => 'Estornado', 'class' => 'bg-gray-200 text-gray-800'],
                                            default => ['text' => ucfirst(strtolower($payment->status)), 'class' => 'bg-gray-200 text-gray-800'],
                                        };
                                        $isPayable = in_array($payment->event_type, ['PAYMENT_CREATED']);
                                    @endphp
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-4 px-6">
                                            {{ $payment->payment_created_at->format('d/m/Y') }}
                                        </td>
                                        <td class="py-4 px-6 font-semibold">
                                            {{ $payment->external_reference ?? $payment->description }}
                                        </td>
                                        <td class="py-4 px-6">
                                            R$ {{ number_format($payment->value, 2, ',', '.') }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="py-1 px-3 rounded-full text-xs font-semibold {{ $statusInfo['class'] }}">
                                                {{ $statusInfo['text'] }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-center space-x-2">
                                            @if ($isPayable && $payment->invoice_url)
                                                <a href="{{ $payment->invoice_url }}" target="_blank"
                                                   class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg text-xs">
                                                    Pagar Fatura
                                                </a>
                                            @elseif($payment->transaction_receipt_url)
                                                <a href="{{ $payment->transaction_receipt_url }}" target="_blank"
                                                   class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg text-xs">
                                                    Ver Comprovante
                                                </a>
                                            @else
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-6 text-gray-500">
                                            Nenhum registro de pagamento encontrado.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Links de Paginação -->
                    <div class="mt-6">
                        @if($payments->links())
                        {{ $payments->links() }}
                        @endif
                    </div>
                </div>
            </div>

            
            <!-- Histórico Completo de Bônus de Tokens -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Histórico de Bônus</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full w-full bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left py-3 px-6">Tokens</th>
                                    <th class="text-left py-3 px-6">Descrição</th>
                                    <th class="text-left py-3 px-6">Início</th>
                                    <!--<th class="text-left py-3 px-6">Expira em</th>-->
                                    <th class="text-left py-3 px-6">Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                @foreach(Auth::user()->tokensBonus()->latest('inicio')->get() as $bonus)
                                    @php
                                        $agora = now();
                                        //$status = 'Expirado';
                                        //$cor = 'text-gray-500';
                                        if ($bonus->inicio > $agora) {
                                            $status = 'Agendado';
                                            $cor = 'text-blue-500';
                                        } else{
                                            $status = 'Ativo';
                                            $cor = 'text-green-600 font-semibold';
                                        }
                                    @endphp
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-4 px-6 font-semibold">
                                            {{ number_format($bonus->tokens, 0, ',', '.') }}
                                        </td>
                                        <td class="py-4 px-6">
                                            {{ $bonus->informacoes ?? 'Bônus' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            {{ \Carbon\Carbon::parse($bonus->inicio)->format('d/m/Y') }}
                                        </td>
                                        <!--<td class="py-4 px-6">
                                            {{ \Carbon\Carbon::parse($bonus->fim)->format('d/m/Y') }}
                                        </td>-->
                                        <td class="py-4 px-6">
                                            <span class="{{ $cor }}">{{ $status }}</span>
                                        </td>
                                    </tr>
                                @endforeach

                                @if(Auth::user()->tokensBonus()->count() == 0)
                                    <tr>
                                        <td colspan="5" class="text-center py-6 text-gray-500">
                                            Nenhum bônus encontrado.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>



        </div>
    </div>
</x-app-layout>
