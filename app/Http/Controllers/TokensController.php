<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AsaasService;
use Illuminate\Support\Facades\Log;
use App\Models\AsaasWebhook;
use Illuminate\Pagination\LengthAwarePaginator;

class TokensController extends Controller
{
    protected AsaasService $asaasService;

    public function __construct(AsaasService $asaasService)
    {
        $this->asaasService = $asaasService;
    }

   public function index()
    {
        $user = Auth::user();
        if(!$user->customer_asaas_id){
            return redirect()->back()->with('error', 'Ops! Este menu só é liberado após completar seu cadastro! Acesse seu perfil e complete agora mesmo');
        }
        $payments = new LengthAwarePaginator([], 0, 15); // vazio por padrão

        if ($user->customer_asaas_id) {
            $payments = AsaasWebhook::where('customer_id', $user->customer_asaas_id)
                ->latest('payment_created_at')
                ->paginate(15);
        }

        return view('payments.index', compact('payments'));
    }

    public function comprar(){
        $user = Auth::user();
        if(!$user->customer_asaas_id){
            return redirect()->back()->with('error', 'Ops! Este menu só é liberado após completar seu cadastro! Acesse seu perfil e complete agora mesmo');
        }
        return view('checkout.show');
    }

    /**
     * Cria um pagamento no Asaas para compra de tokens
     */
    public function createPayment(Request $request)
    {
        $user = Auth::user();
        if(!$user->customer_asaas_id){
            return redirect()->back()->with('error', 'Ops! Este menu só é liberado após completar seu cadastro! Acesse seu perfil e complete agora mesmo');
        }

        $data = $request->validate([
            'tokens' => 'required|integer|min:100000|max:1000000',
            'value' => 'required|numeric|min:20',
            'description' => 'required|string|max:500',
            'externalReference' => 'required|integer',
        ]);

        $paymentData = [
            'billingType' => 'UNDEFINED', //PIX
            'customer' => $user->customer_asaas_id,
            'value' => number_format($data['value'], 2, '.', ''),
            'dueDate' => now()->toDateString(),
            'description' => $data['description'],
            'externalReference' => $data['externalReference'],
        ];


        try {
            $payment = $this->asaasService->createPayment($paymentData);

            if ($payment && isset($payment['invoiceUrl'])) {
                // ✅ Redireciona direto para a fatura PIX/Boleto
                return redirect()->away($payment['invoiceUrl']);
            }

            return back()->withErrors([
                'payment' => 'Não foi possível gerar o pagamento. Tente novamente.'
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao criar pagamento de tokens", [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return back()->withErrors([
                'payment' => 'Erro interno ao gerar o pagamento. Tente novamente.'
            ]);
        }
    }
}
