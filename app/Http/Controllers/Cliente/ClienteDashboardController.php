<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\ClienteLead;
use App\Models\Conexao;
use Illuminate\View\View;

class ClienteDashboardController extends Controller
{
    public function index(): View
    {
        $clienteId = auth('client')->id();

        $conexoesCount = Conexao::where('cliente_id', $clienteId)->count();
        $conversasCount = ClienteLead::where('cliente_id', $clienteId)->count();

        return view('cliente.dashboard', compact('conexoesCount', 'conversasCount'));
    }
}
