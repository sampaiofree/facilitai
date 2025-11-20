<?php

namespace App\Http\Controllers;

use App\Services\MassSendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\MassCampaign;

class MassSendController extends Controller
{
    protected $service;

    public function __construct(MassSendService $service)
    {
        $this->service = $service;
    }

    /**
     * Exibe o formulário de criação de campanha
     */
    public function index()
    {
        $instances = Auth::user()->instances ?? [];
        return view('mass.index', compact('instances')); 
    }

    /**
     * Processa o envio da campanha
     */
    public function store(Request $request)
    {
        $request->validate([
            'instance_id' => 'required|integer|exists:instances,id',
            'tipo_envio' => 'required|in:texto,audio',
            'mensagem' => 'required_if:tipo_envio,texto|string|nullable',
            'arquivo' => 'required|file|mimes:csv,txt|max:2048',
            'intervalo_segundos' => 'required|integer|min:2|max:900',
        ]);

        // Salva o CSV no storage temporário
        $path = $request->file('arquivo')->store('mass_uploads');

        $dados = [
            'instance_id' => $request->instance_id,
            'nome' => $request->nome ?? null,
            'tipo_envio' => $request->tipo_envio,
            'usar_ia' => $request->boolean('usar_ia'),
            'mensagem' => $request->mensagem,
            'intervalo_segundos' => $request->intervalo_segundos,
        ];

        $campanha = $this->service->criarCampanha($dados, Storage::path($path)); 

        return redirect()->route('mass.index')->with('success', "Campanha criada e iniciada com sucesso! ({$campanha->total_contatos} contatos enfileirados)");
    }

    public function historico()
    {
        $campanhas = MassCampaign::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('mass.historico', compact('campanhas'));
    }

    public function show($id)
    {
        $campanha = MassCampaign::with('contatos')->findOrFail($id);

        abort_unless($campanha->user_id === Auth::id(), 403);

        return view('mass.show', compact('campanha'));
    }


}
