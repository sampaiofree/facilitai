<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\WhatsappCloudWebhookJob;
use App\Models\User;
use App\Models\WhatsappCloudAccount;
use App\Support\LogContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WhatsappCloudWebhookController extends Controller
{
    public function verify(Request $request, string $webhookKey): Response
    {
        $user = User::query()
            ->where('whatsapp_cloud_webhook_key', trim($webhookKey))
            ->first();

        if (!$user) {
            Log::channel('whatsapp_cloud_webhook')->warning('Webhook Cloud verify ignorado: chave de webhook do usuário não encontrada.', [
                'user_webhook_key' => $webhookKey,
                'ip' => $request->ip(),
            ]);

            return response('forbidden', 403);
        }

        $query = $request->query();
        $mode = (string) ($query['hub.mode'] ?? $query['hub_mode'] ?? '');
        $verifyToken = (string) ($query['hub.verify_token'] ?? $query['hub_verify_token'] ?? '');
        $challenge = $query['hub.challenge'] ?? $query['hub_challenge'] ?? null;

        if (
            $mode === 'subscribe'
            && is_string($challenge)
            && $verifyToken !== ''
            && hash_equals((string) ($user->whatsapp_cloud_webhook_verify_token ?? ''), $verifyToken)
        ) {
            Log::channel('whatsapp_cloud_webhook')->info('Webhook Cloud verificado com sucesso.', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::channel('whatsapp_cloud_webhook')->warning('Webhook Cloud verify inválido.', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'mode' => $mode,
        ]);

        return response('forbidden', 403);
    }

    public function handle(Request $request, string $webhookKey): JsonResponse
    {
        $user = User::query()
            ->where('whatsapp_cloud_webhook_key', trim($webhookKey))
            ->first();

        if (!$user) {
            Log::channel('whatsapp_cloud_webhook')->warning('Webhook Cloud ignorado: chave de webhook do usuário não encontrada.', [
                'user_webhook_key' => $webhookKey,
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => 'forbidden'], 403);
        }

        $rawBody = $request->getContent();
        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $accounts = WhatsappCloudAccount::query()
            ->where('user_id', $user->id)
            ->get(['id', 'app_id', 'app_secret']);

        [$signatureValid, $validatedAccountIds] = $this->validateSignatureAgainstUserAccounts(
            $rawBody,
            $signature,
            $accounts
        );

        if (!$signatureValid) {
            Log::channel('whatsapp_cloud_webhook')->warning('Webhook Cloud assinatura inválida.', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => 'invalid_signature'], 403);
        }

        if ($validatedAccountIds === []) {
            Log::channel('whatsapp_cloud_webhook')->warning('Webhook Cloud recebido sem app_secret em nenhuma conta do usuário.', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
        }

        $payload = $request->json()->all();
        if (!is_array($payload)) {
            $payload = [];
        }

        WhatsappCloudWebhookJob::dispatch([
            'user_id' => $user->id,
            'validated_account_ids' => $validatedAccountIds,
            'payload' => $payload,
            'received_at' => now(),
        ])->onQueue('webhook');

        Log::channel('whatsapp_cloud_webhook')->info('Webhook Cloud enfileirado.', LogContext::merge([
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'object' => (string) ($payload['object'] ?? ''),
        ]));

        return response()->json(['status' => 'queued']);
    }

    private function validateSignatureAgainstUserAccounts(
        string $rawBody,
        string $signatureHeader,
        Collection $accounts
    ): array {
        $secrets = [];
        foreach ($accounts as $account) {
            $secret = trim((string) ($account->app_secret ?? ''));
            if ($secret === '') {
                continue;
            }

            $secrets[] = [
                'account_id' => (int) $account->id,
                'secret' => $secret,
            ];
        }

        // Se nenhum app_secret estiver configurado para o usuário, mantém compatibilidade:
        // permite o webhook e delega validação por vínculo de conta no job.
        if ($secrets === []) {
            return [true, []];
        }

        $signatureHeader = trim($signatureHeader);
        if ($signatureHeader === '' || !str_starts_with($signatureHeader, 'sha256=')) {
            return [false, []];
        }

        $receivedHash = substr($signatureHeader, 7);
        if ($receivedHash === '') {
            return [false, []];
        }

        $validatedAccountIds = [];
        foreach ($secrets as $entry) {
            $computedHash = hash_hmac('sha256', $rawBody, $entry['secret']);
            if (hash_equals($computedHash, $receivedHash)) {
                $validatedAccountIds[] = (int) $entry['account_id'];
            }
        }

        $validatedAccountIds = array_values(array_unique($validatedAccountIds));

        return [!empty($validatedAccountIds), $validatedAccountIds];
    }
}
