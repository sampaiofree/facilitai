<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $instances = Auth::user()->instances()->orderBy('name')->get(['id', 'name']);
        $assistants = Auth::user()->assistants()->orderBy('name')->get(['id', 'name']);

        $chats = $this->buildChatQuery($request)
            ->paginate(20)
            ->withQueryString();

        $filters = [
            'search' => $request->query('search'),
            'instance_id' => $request->query('instance_id'),
            'assistant_id' => $request->query('assistant_id'),
            'aguardando_atendimento' => $request->query('aguardando_atendimento'),
            'order' => $request->query('order', 'updated_at_desc'),
        ];

        return view('chats.index', compact('chats', 'filters', 'instances', 'assistants'));
    }

    public function export(Request $request)
    {
        $chats = $this->buildChatQuery($request)->get();
        $filename = 'chats-' . now()->format('YmdHis') . '.csv';

        $response = new StreamedResponse(function () use ($chats) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['conv_id', 'instancia', 'assistente', 'contato', 'nome', 'informacoes', 'aguardando_atendimento']);

            foreach ($chats as $chat) {
                fputcsv($handle, [
                    $chat->conv_id,
                    $chat->instance?->name ?? '—',
                    $chat->assistant?->name ?? $chat->assistente?->name ?? '—',
                    $chat->contact,
                    $chat->nome,
                    $chat->informacoes,
                    $chat->aguardando_atendimento ? 'Sim' : 'Não',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function update(Request $request, Chat $chat)
    {
        $this->authorizeChatOwnership($chat);

        $validated = $request->validate([
            'nome' => ['nullable', 'string', 'max:255'],
            'informacoes' => ['nullable', 'string', 'max:1000'],
            'aguardando_atendimento' => ['sometimes', 'boolean'],
        ]);

        $chat->update([
            'nome' => $validated['nome'] ?? $chat->nome,
            'informacoes' => $validated['informacoes'] ?? $chat->informacoes,
            'aguardando_atendimento' => $request->has('aguardando_atendimento'),
        ]);

        return redirect()->back()->with('success', 'Chat atualizado.');
    }

    public function toggleBot(Chat $chat)
    {
        $this->authorizeChatOwnership($chat);

        $chat->update([
            'bot_enabled' => !$chat->bot_enabled,
        ]);

        return redirect()->back()->with('success', 'Status do bot atualizado.');
    }

    public function bulkMarkAttended(Request $request)
    {
        $selected = collect($request->input('selected', []))->filter()->values();

        if ($selected->isEmpty()) {
            return redirect()->back()->with('warning', 'Selecione ao menos um chat.');
        }

        $updated = Auth::user()->chats()
            ->whereIn('id', $selected)
            ->update(['aguardando_atendimento' => false]);

        return redirect()->back()->with('success', "{$updated} chat(s) marcados como atendidos.");
    }

    public function destroy(Chat $chat)
    {
        $this->authorizeChatOwnership($chat);

        $chat->delete();

        return redirect()->route('chats.index')->with('success', 'Conversa excluída com sucesso.');
    }

    private function buildChatQuery(Request $request)
    {
        $query = Auth::user()->chats()->with(['instance', 'assistant']);

        if ($search = $request->query('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('contact', 'like', "%{$search}%")
                    ->orWhere('conv_id', 'like', "%{$search}%")
                    ->orWhere('nome', 'like', "%{$search}%");
            });
        }

        if ($instanceId = $request->query('instance_id')) {
            $query->where('instance_id', $instanceId);
        }

        if ($assistantId = $request->query('assistant_id')) {
            $query->where('assistant_id', $assistantId);
        }

        if ($aguardando = $request->query('aguardando_atendimento')) {
            if (in_array($aguardando, ['0', '1'], true)) {
                $query->where('aguardando_atendimento', $aguardando === '1');
            }
        }

        $sortOption = $request->query('order', 'updated_at_desc');
        $sortMap = [
            'created_at_asc' => ['created_at', 'asc'],
            'created_at_desc' => ['created_at', 'desc'],
            'updated_at_asc' => ['updated_at', 'asc'],
            'updated_at_desc' => ['updated_at', 'desc'],
        ];

        [$column, $direction] = $sortMap[$sortOption] ?? $sortMap['updated_at_desc'];
        $query->orderBy($column, $direction);

        return $query;
    }

    private function authorizeChatOwnership(Chat $chat): void
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
