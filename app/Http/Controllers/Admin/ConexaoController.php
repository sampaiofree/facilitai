<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conexao;
use Illuminate\Http\Request;

class ConexaoController extends Controller
{
    public function index()
    {
        $conexoes = Conexao::with(['cliente', 'credential', 'assistant', 'iamodelo', 'whatsappApi'])
            ->latest()
            ->get();

        return view('admin.conexoes.index', [
            'conexoes' => $conexoes,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:50', 'unique:conexoes,nome'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        Conexao::create([
            'nome' => $data['nome'],
            'ativo' => isset($data['ativo']) ? (bool) $data['ativo'] : true,
        ]);

        return redirect()
            ->route('adm.conexoes.index')
            ->with('success', 'Conexão criada com sucesso.');
    }

    public function update(Request $request, Conexao $conexao)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:50', 'unique:conexoes,nome,' . $conexao->id],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $conexao->nome = $data['nome'];
        $conexao->ativo = isset($data['ativo']) ? (bool) $data['ativo'] : false;
        $conexao->save();

        return redirect()
            ->route('adm.conexoes.index')
            ->with('success', 'Conexão atualizada com sucesso.');
    }

    public function destroy(Conexao $conexao)
    {
        $conexao->delete();

        return redirect()
            ->route('adm.conexoes.index')
            ->with('success', 'Conexão removida com sucesso.');
    }
}
