<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Models\Iaplataforma;
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

        $credentials = Auth::user()->credentials()->with('iaplataforma')->get();
        return view('credentials.index', compact('credentials'));
    }

    // Mostrar o formulário de criação
    public function create()
    {
        return view('credentials.create', compact('iaplataformas'));
    }

    // Salvar a nova credencial
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', Rule::in(['OpenAI'])],
            'label' => 'required|string|max:255',
            'token' => 'required|string',
            'iaplataforma_id' => ['required', 'exists:iaplataformas,id'],
        ]);

        Auth::user()->credentials()->create($validated);

        return redirect()->route('credentials.index')->with('success', 'Credencial salva com sucesso!');
    }

    // Mostrar o formulário de edição
    public function edit(Credential $credential)
    {
        $iaplataformas = Iaplataforma::orderBy('nome')->get();

        // Segurança: Garante que o usuário só pode editar suas próprias credenciais
        if ($credential->user_id !== Auth::id()) {
            abort(403);
        }
        $iaplataformas = Iaplataforma::orderBy('nome')->get();

        return view('credentials.edit', compact('credential', 'iaplataformas'));
    }

    // Atualizar a credencial
    public function update(Request $request, Credential $credential)
    {
        

        if ($credential->user_id !== Auth::id()) {
            
            abort(403);
        }

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', Rule::in(['OpenAI'])],
                'label' => 'required|string|max:255',
                'token' => 'required|string',
            'iaplataforma_id' => ['required', 'exists:iaplataformas,id'],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro na validação do request', [
                'message' => $e->getMessage(),
                'input' => $request->all(),
            ]);
            throw $e;
        }

        try {
            $credential->update($validated);
            
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