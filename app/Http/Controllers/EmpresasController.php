<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BuscarEmpresasService;

class EmpresasController extends Controller
{
    public function index()
    {
        return view('empresas.index');
    }

    public function buscar(Request $request, BuscarEmpresasService $service)
    {
        $request->validate([
            'segmento' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|max:100',
        ]);

        $empresas = $service->buscar(
            $request->segmento,
            $request->cidade,
            $request->estado
        );

        return response()->json(['empresas' => $empresas]);
    }
}
