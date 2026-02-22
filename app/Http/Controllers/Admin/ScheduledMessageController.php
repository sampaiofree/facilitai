<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteScheduledMessageJob;
use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\ScheduledMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ScheduledMessageController extends Controller
{
    public function index(Request $request): View
    {
        $status = trim((string) $request->input('status', ''));
        $clienteId = $request->filled('cliente_id') ? (int) $request->input('cliente_id') : null;
        $assistantId = $request->filled('assistant_id') ? (int) $request->input('assistant_id') : null;
        $dateStart = trim((string) $request->input('date_start', ''));
        $dateEnd = trim((string) $request->input('date_end', ''));
        $search = trim((string) $request->input('q', ''));

        $query = ScheduledMessage::query()
            ->with(['clienteLead.cliente.user', 'assistant', 'conexao', 'creator'])
            ->orderByDesc('created_at');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($clienteId) {
            $query->whereHas('clienteLead', fn ($q) => $q->where('cliente_id', $clienteId));
        }

        if ($assistantId) {
            $query->where('assistant_id', $assistantId);
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
                $q->where('name', 'like', '%' . $search . '%');
                if ($digits !== '') {
                    $q->orWhere('phone', 'like', '%' . $digits . '%');
                }
            });
        }

        return view('admin.agendamentos.index', [
            'scheduledMessages' => $query->paginate(50)->withQueryString(),
            'clientes' => Cliente::query()->orderBy('nome')->get(['id', 'nome']),
            'assistants' => Assistant::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => $status,
                'cliente_id' => $clienteId,
                'assistant_id' => $assistantId,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'q' => $search,
            ],
        ]);
    }

    public function cancel(ScheduledMessage $scheduledMessage): RedirectResponse
    {
        if ($scheduledMessage->status !== 'pending') {
            return back()->with('error', 'Somente agendamentos pendentes podem ser cancelados.');
        }

        $scheduledMessage->update([
            'status' => 'canceled',
            'canceled_at' => Carbon::now('UTC'),
            'error_message' => null,
        ]);

        return back()->with('success', 'Agendamento cancelado com sucesso.');
    }

    public function retry(ScheduledMessage $scheduledMessage): RedirectResponse
    {
        if ($scheduledMessage->status !== 'failed') {
            return back()->with('error', 'Somente agendamentos com falha podem ser reprocessados.');
        }

        $nowUtc = Carbon::now('UTC');
        $scheduledMessage->update([
            'status' => 'queued',
            'scheduled_for' => $nowUtc,
            'queued_at' => $nowUtc,
            'sent_at' => null,
            'failed_at' => null,
            'canceled_at' => null,
            'error_message' => null,
            'attempts' => 0,
        ]);

        ExecuteScheduledMessageJob::dispatch($scheduledMessage->id)
            ->onQueue('processarconversa');

        return back()->with('success', 'Agendamento enviado para reprocessamento.');
    }
}

