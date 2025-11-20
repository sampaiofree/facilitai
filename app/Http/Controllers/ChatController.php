<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class ChatController extends Controller
{
    /**
     * Exibe uma lista dos chats do usuário autenticado.
     */
    public function index(Request $request)
    {
        // 1. Pega o termo de pesquisa da URL (ex: /chats?search=55119999)
        $search = $request->query('search');

        // 2. Inicia a construção da consulta ao banco de dados
        $query = Auth::user()->chats();

        // 3. Se um termo de pesquisa existir, adiciona uma condição 'where' à consulta
        if ($search) {
            // O 'like' com '%' permite buscas parciais (ex: procurar por '9999' encontrará '551199998888')
            $query->where('contact', 'like', '%' . $search . '%')->orWhere('thread_id', 'like', '%' . $search . '%');
        }

        // 4. Executa a consulta com paginação e ordenação
        $chats = $query->latest()->paginate(20);
        
        // 5. Adiciona os parâmetros da pesquisa aos links de paginação
        // Isso garante que, ao clicar na página 2, a busca seja mantida.
        $chats->appends(['search' => $search]);

        // 6. Retorna a view com os resultados filtrados
        return view('chats.index', compact('chats', 'search'));
    }

    public function marcarAtendido(Chat $chat)
    {
        $chat->aguardando_atendimento = false;
        $chat->save();

        return response()->json(['success' => true]);
    }

    /**
     * Atualiza o status do bot (liga/desliga).
     */
    public function update(Request $request, Chat $chat)
    {
        // Segurança: Garante que o usuário só pode alterar seus próprios chats.
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        // Inverte o valor booleano (true vira false, false vira true)
        $chat->bot_enabled = !$chat->bot_enabled;
        $chat->save();

        return redirect()->route('chats.index')->with('success', 'Status do bot atualizado com sucesso!');
    }

    /**
     * Exclui um registro de chat.
     */
    public function destroy(Chat $chat)
    {
        // Segurança: Garante que o usuário só pode excluir seus próprios chats.
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }

        $chat->delete();

        return redirect()->route('chats.index')->with('success', 'Conversa excluída com sucesso.');
    }
}