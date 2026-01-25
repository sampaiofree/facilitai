<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Iaplataforma;
use Illuminate\Http\Request;

class IaplataformaController extends Controller
{
    public function index()
    {
        $iaplataformas = Iaplataforma::latest()->get();

        return view('admin.iaplataformas.index', compact('iaplataformas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required','string','max:50','unique:iaplataformas,nome'],
            'ativo' => ['nullable','boolean'],
        ]);

        Iaplataforma::create([
            'nome' => $data['nome'],
            'ativo' => $data['ativo'] ?? true,
        ]);

        return redirect()
            ->route('adm.iaplataformas.index')
            ->with('success', 'Plataforma criada com sucesso.');
    }

    public function update(Request $request, Iaplataforma $iaplataforma)
    {
        $data = $request->validate([
            'nome' => ['required','string','max:50','unique:iaplataformas,nome,' . $iaplataforma->id],
            'ativo' => ['nullable','boolean'],
        ]);

        $iaplataforma->update([
            'nome' => $data['nome'],
            'ativo' => $data['ativo'] ?? false,
        ]);

        return redirect()
            ->route('adm.iaplataformas.index')
            ->with('success', 'Plataforma atualizada com sucesso.');
    }

    public function destroy(Iaplataforma $iaplataforma)
    {
        $iaplataforma->delete();

        return redirect()
            ->route('adm.iaplataformas.index')
            ->with('success', 'Plataforma removida com sucesso.');
    }
}
