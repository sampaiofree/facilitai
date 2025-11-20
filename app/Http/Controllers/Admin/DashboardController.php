<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;


class DashboardController extends Controller
{
    public function index()
    {
        // Pega todos os usuários, exceto o próprio admin, e pagina os resultados
        $users = User::where('is_admin', false)->latest()->paginate(100);
        $totalUsers = User::where('is_admin', false)->count();

        return view('admin.dashboard', compact('users', 'totalUsers'));
    }

    

    public function exportUsers()
{
    $fileName = 'usuarios_' . now()->format('Y-m-d_H-i-s') . '.csv';

    return response()->streamDownload(function () {
        $handle = fopen('php://output', 'w');

        // Cabeçalho CSV
        fputcsv($handle, [
            'ID', 'Nome', 'WhatsApp', 'Plano',
            'Tokens Comprados', 'Tokens Bônus',
            'Tokens Usados', 'Tokens Saldo', 'Data de Cadastro'
        ]);

        // Busca todos os usuários exceto admins
        $users = User::where('is_admin', false)->with('hotmartWebhooks')->get();

        foreach ($users as $user) {
            fputcsv($handle, [
                $user->id,
                $user->name,
                $user->mobile_phone,
                optional($user->hotmartWebhooks)->offer_code,
                $user->totalTokens(),
                $user->tokensBonusValidos(),
                $user->totalTokensUsed(),
                $user->tokensAvailable(),
                optional($user->created_at)->format('d/m/Y H:i'),
            ]);
        }

        fclose($handle);
    }, $fileName, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
        'Pragma' => 'no-cache',
    ]);
}


}