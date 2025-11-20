<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; 
use Illuminate\Support\Facades\Log;

class CredentialController extends Controller
{
    // Listar todas as credenciais do usuário
    public function index()
    {
        if(!Auth::user()->canManageCredentials()){
            return redirect()->back()->with('error', 'Opção não disponível no seu plano!');
        }

        $credentials = Auth::user()->credentials()->get();
        return view('credentials.index', compact('credentials'));
    }

    // Mostrar o formulário de criação
    public function create()
    {
        return view('credentials.create');
    }

    // Salvar a nova credencial
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', Rule::in(['OpenAI'])],
            'label' => 'required|string|max:255',
            'token' => 'required|string',
        ]);

        Auth::user()->credentials()->create($validated);

        return redirect()->route('credentials.index')->with('success', 'Credencial salva com sucesso!');
    }

    // Mostrar o formulário de edição
    public function edit(Credential $credential)
    {
        // Segurança: Garante que o usuário só pode editar suas próprias credenciais
        if ($credential->user_id !== Auth::id()) {
            abort(403);
        }
        return view('credentials.edit', compact('credential'));
    }

    // Atualizar a credencial
    public function update(Request $request, Credential $credential)
    {
        Log::info('Iniciando update de credential', [
            'credential_id' => $credential->id ?? null,
            'user_id_credential' => $credential->user_id ?? null,
            'auth_id' => Auth::id(),
        ]);

        if ($credential->user_id !== Auth::id()) {
            Log::warning('Usuário não autorizado a atualizar credential', [
                'credential_id' => $credential->id,
                'user_id_credential' => $credential->user_id,
                'auth_id' => Auth::id(),
            ]);
            abort(403);
        }

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', Rule::in(['OpenAI'])],
                'label' => 'required|string|max:255',
                'token' => 'required|string',
            ]);
            Log::info('Validação concluída com sucesso', ['validated' => $validated]);
        } catch (\Exception $e) {
            Log::error('Erro na validação do request', [
                'message' => $e->getMessage(),
                'input' => $request->all(),
            ]);
            throw $e;
        }

        try {
            $credential->update($validated);
            Log::info('Credential atualizada com sucesso', [
                'credential_id' => $credential->id,
                'dados' => $validated,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar credential', [
                'credential_id' => $credential->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        return redirect()
            ->route('credentials.index')
            ->with('success', 'Credencial atualizada com sucesso!');
    }

    // Excluir a credencial
    public function destroy(Credential $credential)
    {
        if ($credential->user_id !== Auth::id()) {
            abort(403);
        }

        $credential->delete();

        return redirect()->route('credentials.index')->with('success', 'Credencial excluída com sucesso!');
    }
}