<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Iamodelo;
use App\Models\Iaplataforma;
use Illuminate\Http\Request;

class IamodeloController extends Controller
{
    public function index()
    {
        $iaplataformas = Iaplataforma::orderBy('nome')->get();
        $iamodelos = Iamodelo::with('iaplataforma')->latest()->get();

        return view('admin.iamodelos.index', compact('iamodelos', 'iaplataformas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'iaplataforma_id' => ['required', 'exists:iaplataformas,id'],
            'nome' => ['required', 'string', 'max:50', 'unique:iamodelos,nome,NULL,id,iaplataforma_id,' . $request->input('iaplataforma_id')],
            'ativo' => ['nullable', 'boolean'],
        ]);

        Iamodelo::create([
            'iaplataforma_id' => $data['iaplataforma_id'],
            'nome' => $data['nome'],
            'ativo' => $data['ativo'] ?? true,
        ]);

        return redirect()->route('adm.iamodelos.index')->with('success', 'Modelo criado com sucesso.');
    }

    public function update(Request $request, Iamodelo $iamodelo)
    {
        $data = $request->validate([
            'iaplataforma_id' => ['required', 'exists:iaplataformas,id'],
            'nome' => ['required', 'string', 'max:50', 'unique:iamodelos,nome,' . $iamodelo->id . ',id,iaplataforma_id,' . $request->input('iaplataforma_id')],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $iamodelo->update([
            'iaplataforma_id' => $data['iaplataforma_id'],
            'nome' => $data['nome'],
            'ativo' => $data['ativo'] ?? false,
        ]);

        return redirect()->route('adm.iamodelos.index')->with('success', 'Modelo atualizado com sucesso.');
    }

    public function destroy(Iamodelo $iamodelo)
    {
        $iamodelo->delete();

        return redirect()->route('adm.iamodelos.index')->with('success', 'Modelo excluído com sucesso.');
    }
}
