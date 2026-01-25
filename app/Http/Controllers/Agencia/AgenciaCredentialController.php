<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Models\Iaplataforma;
use Illuminate\Http\Request;

class AgenciaCredentialController extends Controller
{
    public function index(Request $request)
    {
        $credentials = Credential::where('user_id', $request->user()->id)
            ->with('iaplataforma')
            ->latest()
            ->get();

        return view('agencia.credentials.index', [
            'credentials' => $credentials,
            'iaplataformas' => Iaplataforma::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string'],
            'iaplataforma_id' => ['required', 'exists:iaplataformas,id'],
        ]);

        $credential = new Credential();
        $credential->user_id = $request->user()->id;
        $credential->name = $data['name'];
        $credential->label = $data['name'];
        $credential->iaplataforma_id = $data['iaplataforma_id'];
        $credential->token = $data['token'];
        $credential->save();

        return redirect()
            ->route('agencia.credentials.index')
            ->with('success', 'Credencial criada com sucesso.');
    }

    public function update(Request $request, Credential $credential)
    {
        $this->authorizeOwnership($request, $credential);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'token' => ['nullable', 'string'],
            'iaplataforma_id' => ['required', 'exists:iaplataformas,id'],
        ]);

        $credential->name = $data['name'];
        $credential->label = $data['name'];
        $credential->iaplataforma_id = $data['iaplataforma_id'];

        if (!empty($data['token'])) {
            $credential->token = $data['token'];
        }

        $credential->save();

        return redirect()
            ->route('agencia.credentials.index')
            ->with('success', 'Credencial atualizada com sucesso.');
    }

    public function destroy(Request $request, Credential $credential)
    {
        $this->authorizeOwnership($request, $credential);

        $credential->delete();

        return redirect()
            ->route('agencia.credentials.index')
            ->with('success', 'Credencial removida com sucesso.');
    }

    private function authorizeOwnership(Request $request, Credential $credential): void
    {
        if ($credential->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
