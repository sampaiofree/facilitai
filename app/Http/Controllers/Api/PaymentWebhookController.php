<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAsaasWebhookJob;
use App\Jobs\ProvisionInstanceJob;
use App\Models\Instance;
use Illuminate\Http\Request;
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
        $webhookData = $request->all();

        $accessToken = config('services.asaas.webhook_access_token');
        if (!empty($accessToken)) {
            $incomingToken = (string) $request->header('asaas-access-token', '');
            if ($incomingToken === '' || !hash_equals((string) $accessToken, $incomingToken)) {
                Log::channel('asaas')->warning('Webhook Asaas rejeitado por token inválido.', [
                    'ip' => $request->ip(),
                    'event_id' => $webhookData['id'] ?? null,
                    'event_type' => $webhookData['event'] ?? null,
                ]);

                return response()->json(['received' => false, 'message' => 'Unauthorized'], 401);
            }
        }

        $allowedIps = config('services.asaas.webhook_allowed_ips', []);
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps, true)) {
            Log::channel('asaas')->warning('Webhook Asaas rejeitado por IP não permitido.', [
                'ip' => $request->ip(),
                'event_id' => $webhookData['id'] ?? null,
                'event_type' => $webhookData['event'] ?? null,
            ]);

            return response()->json(['received' => false, 'message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($webhookData, [
            'id' => ['required', 'string', 'max:120'],
            'event' => ['required', 'string', 'max:120'],
            'dateCreated' => ['required'],
            'payment' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            Log::channel('asaas')->warning('Webhook Asaas ignorado por payload inválido.', [
                'errors' => $validator->errors()->toArray(),
                'payload' => $webhookData,
            ]);

            return response()->json(['received' => true, 'ignored' => true], 200);
        }

        try {
            ProcessAsaasWebhookJob::dispatch($webhookData)->onQueue('webhook');

            return response()->json(['received' => true], 200);
        } catch (\Throwable $exception) {
            Log::channel('asaas')->error('Erro ao enfileirar webhook Asaas.', [
                'error' => $exception->getMessage(),
                'payload' => $webhookData,
            ]);

            return response()->json(['received' => false, 'message' => 'Error processing webhook'], 500);
        }
    }


}
