<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappApi;
use Illuminate\Http\Request;

class WhatsappApiController extends Controller
{
    public function index()
    {
        $whatsappApis = WhatsappApi::latest()->get();

        return view('admin.whatsapp-api.index', [
            'whatsappApis' => $whatsappApis,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string'],
            'slug' => ['required', 'string', 'max:120', 'unique:whatsapp_api,slug'],
            'ativo' => ['required', 'boolean'],
        ]);

        $data['ativo'] = (bool) $data['ativo'];

        WhatsappApi::create($data);

        return redirect()
            ->route('adm.whatsapp-api.index')
            ->with('success', 'Registro criado com sucesso.');
    }

    public function update(Request $request, WhatsappApi $whatsappApi)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string'],
            'slug' => ['required', 'string', 'max:120', 'unique:whatsapp_api,slug,' . $whatsappApi->id],
            'ativo' => ['required', 'boolean'],
        ]);

        $data['ativo'] = (bool) $data['ativo'];

        $whatsappApi->update($data);

        return redirect()
            ->route('adm.whatsapp-api.index')
            ->with('success', 'Registro atualizado com sucesso.');
    }

    public function destroy(WhatsappApi $whatsappApi)
    {
        $whatsappApi->delete();

        return redirect()
            ->route('adm.whatsapp-api.index')
            ->with('success', 'Registro removido com sucesso.');
    }
}
