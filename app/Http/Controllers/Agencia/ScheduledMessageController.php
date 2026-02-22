<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\ScheduledMessage;
use App\Services\ScheduledMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ScheduledMessageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = trim((string) $request->input('status', ''));
        $dateStart = trim((string) $request->input('date_start', ''));
        $dateEnd = trim((string) $request->input('date_end', ''));
        $search = trim((string) $request->input('q', ''));

        /** @var ScheduledMessageService $scheduledMessageService */
        $scheduledMessageService = app(ScheduledMessageService::class);
        $timezone = $scheduledMessageService->resolveTimezoneForUser($user);

        $query = ScheduledMessage::query()
            ->with(['clienteLead.cliente', 'assistant', 'conexao', 'creator'])
            ->where('created_by_user_id', (int) $user->id)
            ->whereHas('clienteLead.cliente', fn ($q) => $q->where('user_id', (int) $user->id))
            ->orderByDesc('created_at');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($dateStart !== '') {
            $query->whereDate('scheduled_for', '>=', $dateStart);
        }

        if ($dateEnd !== '') {
            $query->whereDate('scheduled_for', '<=', $dateEnd);
        }

        if ($search !== '') {
            $digits = preg_replace('/\D/', '', $search);
            $query->whereHas('clienteLead', function ($q) use ($search, $digits) {
                $q->where(function ($inner) use ($search, $digits) {
                    $inner->where('name', 'like', '%' . $search . '%');
                    if ($digits !== '') {
                        $inner->orWhere('phone', 'like', '%' . $digits . '%');
                    }
                });
            });
        }

        $scheduledMessages = $query->paginate(30)->withQueryString();
        $scheduledMessages->getCollection()->transform(function (ScheduledMessage $item) use ($timezone) {
            $item->scheduled_for_label = $this->formatColumnDate($item, 'scheduled_for', $timezone);
            $item->created_at_label = $this->formatColumnDate($item, 'created_at', $timezone);
            return $item;
        });

        return view('agencia.mensagens-agendadas.index', [
            'scheduledMessages' => $scheduledMessages,
            'timezone' => $timezone,
            'filters' => [
                'status' => $status,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'q' => $search,
            ],
        ]);
    }

    public function show(Request $request, ScheduledMessage $scheduledMessage): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->canAccess($scheduledMessage, (int) $user->id), 403);

        /** @var ScheduledMessageService $scheduledMessageService */
        $scheduledMessageService = app(ScheduledMessageService::class);
        $timezone = $scheduledMessageService->resolveTimezoneForUser($user);

        $scheduledMessage->loadMissing(['clienteLead.cliente', 'assistant', 'conexao', 'creator']);

        return response()->json([
            'id' => $scheduledMessage->id,
            'status' => $scheduledMessage->status,
            'event_id' => $scheduledMessage->event_id,
            'mensagem' => $scheduledMessage->mensagem,
            'attempts' => (int) $scheduledMessage->attempts,
            'error_message' => $scheduledMessage->error_message,
            'timezone' => $timezone,
            'lead' => [
                'id' => $scheduledMessage->clienteLead?->id,
                'name' => $scheduledMessage->clienteLead?->name,
                'phone' => $scheduledMessage->clienteLead?->phone,
                'cliente' => $scheduledMessage->clienteLead?->cliente?->nome,
            ],
            'assistant' => [
                'id' => $scheduledMessage->assistant?->id,
                'name' => $scheduledMessage->assistant?->name,
            ],
            'conexao' => [
                'id' => $scheduledMessage->conexao?->id,
                'name' => $scheduledMessage->conexao?->name,
                'phone' => $scheduledMessage->conexao?->phone,
            ],
            'creator' => [
                'id' => $scheduledMessage->creator?->id,
                'name' => $scheduledMessage->creator?->name,
                'email' => $scheduledMessage->creator?->email,
            ],
            'timestamps' => [
                'scheduled_for' => $this->formatColumnIso($scheduledMessage, 'scheduled_for'),
                'scheduled_for_label' => $this->formatColumnDate($scheduledMessage, 'scheduled_for', $timezone),
                'scheduled_for_input' => $this->formatColumnInput($scheduledMessage, 'scheduled_for', $timezone),
                'queued_at' => $this->formatColumnIso($scheduledMessage, 'queued_at'),
                'queued_at_label' => $this->formatColumnDate($scheduledMessage, 'queued_at', $timezone),
                'sent_at' => $this->formatColumnIso($scheduledMessage, 'sent_at'),
                'sent_at_label' => $this->formatColumnDate($scheduledMessage, 'sent_at', $timezone),
                'failed_at' => $this->formatColumnIso($scheduledMessage, 'failed_at'),
                'failed_at_label' => $this->formatColumnDate($scheduledMessage, 'failed_at', $timezone),
                'canceled_at' => $this->formatColumnIso($scheduledMessage, 'canceled_at'),
                'canceled_at_label' => $this->formatColumnDate($scheduledMessage, 'canceled_at', $timezone),
                'created_at' => $this->formatColumnIso($scheduledMessage, 'created_at'),
                'created_at_label' => $this->formatColumnDate($scheduledMessage, 'created_at', $timezone),
                'updated_at' => $this->formatColumnIso($scheduledMessage, 'updated_at'),
                'updated_at_label' => $this->formatColumnDate($scheduledMessage, 'updated_at', $timezone),
                'now_input' => now($timezone)->format('Y-m-d\TH:i'),
            ],
        ]);
    }

    public function update(Request $request, ScheduledMessage $scheduledMessage): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->canAccess($scheduledMessage, (int) $user->id), 403);

        $data = $request->validate([
            'mensagem' => ['required', 'string', 'max:2000'],
            'scheduled_for' => ['required', 'string', 'max:50'],
        ]);

        $mensagem = trim((string) ($data['mensagem'] ?? ''));
        if ($mensagem === '') {
            return response()->json([
                'message' => 'Mensagem vazia.',
            ], 422);
        }

        /** @var ScheduledMessageService $scheduledMessageService */
        $scheduledMessageService = app(ScheduledMessageService::class);
        $timezone = $scheduledMessageService->resolveTimezoneForUser($user);
        $scheduledForRaw = trim((string) ($data['scheduled_for'] ?? ''));
        $scheduledForUtc = $scheduledMessageService->parseScheduledForToUtc($scheduledForRaw, $timezone);

        if (!$scheduledForUtc) {
            return response()->json([
                'message' => 'Data/hora de agendamento invalida.',
            ], 422);
        }

        $nowUtc = Carbon::now('UTC');
        if ($scheduledForUtc->lte($nowUtc)) {
            return response()->json([
                'message' => 'Agendamento deve ser uma data futura.',
            ], 422);
        }

        $maxUtc = Carbon::now($timezone)->addDays(90)->setTimezone('UTC');
        if ($scheduledForUtc->gt($maxUtc)) {
            return response()->json([
                'message' => 'O limite maximo para agendamento e de 90 dias.',
            ], 422);
        }

        $updated = ScheduledMessage::query()
            ->whereKey($scheduledMessage->id)
            ->where('status', 'pending')
            ->update([
                'mensagem' => $mensagem,
                'scheduled_for' => $scheduledForUtc,
                'updated_at' => Carbon::now('UTC'),
            ]);

        if ($updated !== 1) {
            return response()->json([
                'message' => 'Somente agendamentos pendentes podem ser editados.',
            ], 422);
        }

        $scheduledMessage->refresh();

        return response()->json([
            'message' => 'Agendamento atualizado com sucesso.',
            'id' => $scheduledMessage->id,
            'scheduled_for' => $this->formatColumnIso($scheduledMessage, 'scheduled_for'),
            'scheduled_for_label' => $this->formatColumnDate($scheduledMessage, 'scheduled_for', $timezone),
            'timezone' => $timezone,
        ]);
    }

    public function cancel(Request $request, ScheduledMessage $scheduledMessage): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canAccess($scheduledMessage, (int) $user->id), 403);

        $nowUtc = Carbon::now('UTC');
        $updated = ScheduledMessage::query()
            ->whereKey($scheduledMessage->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'canceled',
                'canceled_at' => $nowUtc,
                'error_message' => null,
                'updated_at' => $nowUtc,
            ]);

        if ($updated !== 1) {
            return back()->with('error', 'Somente agendamentos pendentes podem ser cancelados.');
        }

        return back()->with('success', 'Agendamento cancelado com sucesso.');
    }

    private function canAccess(ScheduledMessage $scheduledMessage, int $userId): bool
    {
        $scheduledMessage->loadMissing('clienteLead.cliente');

        if ((int) $scheduledMessage->created_by_user_id !== $userId) {
            return false;
        }

        return (int) ($scheduledMessage->clienteLead?->cliente?->user_id ?? 0) === $userId;
    }

    private function parseColumnAsUtc(ScheduledMessage $scheduledMessage, string $column): ?Carbon
    {
        $raw = $scheduledMessage->getRawOriginal($column);
        if (is_string($raw) && trim($raw) !== '') {
            try {
                return Carbon::parse($raw, 'UTC');
            } catch (\Throwable) {
                // fallback below
            }
        }

        $value = $scheduledMessage->getAttribute($column);
        if ($value instanceof Carbon) {
            return $value->copy()->setTimezone('UTC');
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->setTimezone('UTC');
        }

        return null;
    }

    private function formatColumnDate(ScheduledMessage $scheduledMessage, string $column, string $timezone): ?string
    {
        $value = $this->parseColumnAsUtc($scheduledMessage, $column);
        if (!$value) {
            return null;
        }

        return $value->setTimezone($timezone)->format('d/m/Y H:i');
    }

    private function formatColumnInput(ScheduledMessage $scheduledMessage, string $column, string $timezone): ?string
    {
        $value = $this->parseColumnAsUtc($scheduledMessage, $column);
        if (!$value) {
            return null;
        }

        return $value->setTimezone($timezone)->format('Y-m-d\TH:i');
    }

    private function formatColumnIso(ScheduledMessage $scheduledMessage, string $column): ?string
    {
        $value = $this->parseColumnAsUtc($scheduledMessage, $column);
        if (!$value) {
            return null;
        }

        return $value->toIso8601String();
    }
}
