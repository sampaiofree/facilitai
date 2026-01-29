<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ClienteDashboardController extends Controller
{
    public function index(): View
    {
        return view('cliente.dashboard');
    }
}
