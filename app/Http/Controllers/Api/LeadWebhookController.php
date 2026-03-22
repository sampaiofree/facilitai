<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadWebhookLink;
use App\Services\LeadWebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadWebhookController extends Controller
{
    public function __construct(
        protected LeadWebhookProcessor $processor
    ) {
    }

    public function handle(Request $request, string $token): JsonResponse
    {
        $link = LeadWebhookLink::query()
            ->where('token', trim($token))
            ->where('is_active', true)
            ->first();

        if (!$link) {
            return response()->json([
                'ok' => false,
                'message' => 'Webhook não encontrado.',
            ], 404);
        }

        $contentType = strtolower((string) $request->header('Content-Type', ''));
        if (!str_contains($contentType, 'application/json')) {
            return response()->json([
                'ok' => false,
                'message' => 'Este endpoint aceita apenas application/json.',
            ], 415);
        }

        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return response()->json([
                'ok' => false,
                'message' => 'JSON inválido.',
            ], 422);
        }

        if (!is_array($payload)) {
            return response()->json([
                'ok' => false,
                'message' => 'JSON inválido.',
            ], 422);
        }

        $result = $this->processor->process($link, $payload);

        return response()->json([
            'ok' => (bool) $result['ok'],
            'delivery_id' => $result['delivery_id'] ?? null,
            'status' => $result['status'] ?? null,
            'lead_id' => $result['lead_id'] ?? null,
            'message' => $result['message'] ?? null,
        ], (int) ($result['http_status'] ?? 200));
    }
}
