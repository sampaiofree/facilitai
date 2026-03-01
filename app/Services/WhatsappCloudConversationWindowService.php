<?php

namespace App\Services;

use App\Models\WhatsappCloudConversationWindow;
use Illuminate\Support\Carbon;

class WhatsappCloudConversationWindowService
{
    public function touchInbound(int $leadId, int $conexaoId, ?Carbon $at = null, ?string $eventId = null): void
    {
        $at = ($at ?? Carbon::now('UTC'))->copy()->setTimezone('UTC');

        WhatsappCloudConversationWindow::query()->updateOrCreate(
            [
                'cliente_lead_id' => $leadId,
                'conexao_id' => $conexaoId,
            ],
            [
                'last_inbound_at' => $at,
                'last_inbound_event_id' => $eventId !== null && trim($eventId) !== '' ? trim($eventId) : null,
            ]
        );
    }

    public function touchOutbound(int $leadId, int $conexaoId, ?Carbon $at = null): void
    {
        $at = ($at ?? Carbon::now('UTC'))->copy()->setTimezone('UTC');

        WhatsappCloudConversationWindow::query()->updateOrCreate(
            [
                'cliente_lead_id' => $leadId,
                'conexao_id' => $conexaoId,
            ],
            [
                'last_outbound_at' => $at,
            ]
        );
    }

    public function isInsideWindow(int $leadId, int $conexaoId, ?Carbon $now = null): bool
    {
        $window = WhatsappCloudConversationWindow::query()
            ->where('cliente_lead_id', $leadId)
            ->where('conexao_id', $conexaoId)
            ->first(['last_inbound_at']);

        if (!$window?->last_inbound_at) {
            return false;
        }

        $now = ($now ?? Carbon::now('UTC'))->copy()->setTimezone('UTC');
        return $window->last_inbound_at->copy()->addHours(24)->gt($now);
    }
}

