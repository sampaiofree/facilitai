<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionInstanceJob;
use App\Models\Instance;
use App\Models\AsaasWebhook; 
use Illuminate\Http\Request;
use App\Models\HotmarlWebhook;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class PaymentWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // AQUI você adicionaria a lógica para validar o webhook (verificar assinatura, etc.)
        // Cada gateway de pagamento tem um jeito de fazer isso. É um passo de segurança CRUCIAL.

        // Por enquanto, vamos assumir que o webhook nos envia o ID da instância
        $instanceId = $request->input('instance_id'); // Adapte este campo ao que o gateway envia

        $instance = Instance::find($instanceId);

        if ($instance && $instance->status == 'pending_payment') {
            
            // 1. Mude o status para 'processing'
            $instance->update(['status' => 'processing']);

            // 2. Despache o Job para a fila (entregue o pedido para a cozinha)
            ProvisionInstanceJob::dispatch($instance);
        }

        // 3. Responda imediatamente para o gateway de pagamento
        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Recebe e processa webhooks do Asaas.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function asaas(Request $request)
    {
        // O Asaas envia o payload como JSON, o Laravel já converte para array/objeto.
        $webhookData = $request->all();

        // Para depuração, é bom logar o payload completo
        Log::info('Webhook Asaas recebido:', $webhookData);

        try {
            // Extrai os dados do payload
            $event_id = $webhookData['id'] ?? null;
            $event_type = $webhookData['event'] ?? null;
            $webhook_created_at = $webhookData['dateCreated'] ?? null;

            // Dados do objeto 'payment'
            $paymentData = $webhookData['payment'] ?? [];
            $payment_id = $paymentData['id'] ?? null;
            $payment_created_at = $paymentData['dateCreated'] ?? null;
            $customer_id = $paymentData['customer'] ?? null;
            $value = $paymentData['value'] ?? null;
            $description = $paymentData['description'] ?? null;
            $billing_type = $paymentData['billingType'] ?? null;
            $confirmed_at = $paymentData['confirmedDate'] ?? null;
            $status = $paymentData['status'] ?? null;
            $payment_at = $paymentData['paymentDate'] ?? null;
            $client_payment_at = $paymentData['clientPaymentDate'] ?? null;
            $invoice_url = $paymentData['invoiceUrl'] ?? null;
            $external_reference = $paymentData['externalReference'] ?? null;
            $transaction_receipt_url = $paymentData['transactionReceiptUrl'] ?? null;
            $nosso_numero = $paymentData['nossoNumero'] ?? null;

            // Salva o webhook no banco de dados
            AsaasWebhook::create([
                'webhook_id' => $event_id,
                'event_type' => $event_type,
                'webhook_created_at' => $webhook_created_at,
                'payment_id' => $payment_id,
                'payment_created_at' => $payment_created_at,
                'customer_id' => $customer_id,
                'value' => $value,
                'description' => $description,
                'billing_type' => $billing_type,
                'confirmed_at' => $confirmed_at,
                'status' => $status,
                'payment_at' => $payment_at,
                'client_payment_at' => $client_payment_at,
                'invoice_url' => $invoice_url,
                'external_reference' => $external_reference,
                'transaction_receipt_url' => $transaction_receipt_url,
                'nosso_numero' => $nosso_numero,
                'payload' => $webhookData, // Salva o payload JSON completo
            ]);

            // Retorna uma resposta de sucesso para o Asaas
            // O Asaas espera um status 200 OK para considerar o webhook entregue.
            return response()->json(['message' => 'Webhook received and stored successfully'], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook Asaas:', [
                'error' => $e->getMessage(),
                'payload' => $webhookData
            ]);
            // Retorna um erro para o Asaas para que ele possa tentar novamente (se configurado)
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }


}