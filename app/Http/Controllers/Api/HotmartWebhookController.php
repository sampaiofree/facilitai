<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HotmarlWebhook;
use Illuminate\Support\Facades\Log; 
use App\Jobs\ProvisionInstanceJob;
use App\Models\Instance;
use Illuminate\Support\Facades\Validator;
use App\Services\TokenBonusService;
use Illuminate\Support\Carbon;

class HotmartWebhookController extends Controller
{
    /**
     * Recebe e processa os dados do webhook da Hotmart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
       public function handleWebhook(Request $request)
    {

        //return response()->json(['message' => 'Teste OK'], 200);
        // Logar payload recebido
        //Log::info('🚨 Webhook Hotmart recebido:', $request->all());

        echo "-1-";
        // Validação manual (sem redirect)
        $validator = Validator::make($request->all(), [
            'event' => 'required|string',
            'data.product.id' => 'required|integer',
            'data.buyer.email' => 'required|email',
            'data.buyer.name' => 'required|string',
            'data.purchase.status' => 'required|string',
            'data.purchase.transaction' => 'required|string',
            'data.purchase.offer.code' => 'required|string',
        ]);

        echo "-2-";

        if ($validator->fails()) {
            Log::warning('❌ Webhook Hotmart inválido', $validator->errors()->toArray());
            return response()->json([
                'message' => 'Invalid payload',
                'errors' => $validator->errors(),
            ], 422);
        }

        echo "-3-";

        $validatedData = $validator->validated();
        $transaction   = $validatedData['data']['purchase']['transaction'];

        echo "-4-";

        // Extrair dados que serão salvos
        $dataToStore = [
            'event'                     => $validatedData['event'],
            'product_id'                => $validatedData['data']['product']['id'], //6344441 cod do FacilitAI
            'buyer_email'               => $validatedData['data']['buyer']['email'],
            'buyer_name'                => $validatedData['data']['buyer']['name'],
            'buyer_first_name'          => $request->input('data.buyer.first_name'),
            'buyer_last_name'           => $request->input('data.buyer.last_name'),
            'buyer_checkout_phone_code' => $request->input('data.buyer.checkout_phone_code') ?? null,
            'buyer_checkout_phone'      => $request->input('data.buyer.checkout_phone')?? null,
            'status'                    => $validatedData['data']['purchase']['status'],
            'transaction'               => $transaction,
            'offer_code'                => $validatedData['data']['purchase']['offer']['code'],
            'full_payload'              => json_encode($request->all()), // salvar como JSON
        ];

        echo "-5-";

        try {
            $hotmart = HotmarlWebhook::updateOrCreate(
                ['transaction' => $transaction], // condição única
                $dataToStore
            );

            Log::info("✅ Webhook Hotmart processado com sucesso para transação: {$transaction}");

            //ADICIONAR OS BONUS
            $tokensBonus = new TokenBonusService; 
            $tokensBonus->bonusHotmart($hotmart); 
            


            return response()->json(['message' => 'Webhook processed successfully'], 200);
        } catch (\Exception $e) {
            Log::error("💥 Erro ao processar webhook Hotmart para transação {$transaction}: " . $e->getMessage());

            return response()->json([
                'message' => 'Error processing webhook',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function testHotmart(Request $request)
{
    return response()->json(['message' => 'Teste OK'], 200);
}
}