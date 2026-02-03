<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AgenciaClienteController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'active');
        $query = Cliente::query()->where('user_id', $request->user()->id);

        if ($status === 'trashed') {
            $query->onlyTrashed();
        } elseif ($status === 'all') {
            $query->withTrashed();
        }

        $clientes = $query->latest()->get();

        return view('agencia.clientes.index', [
            'clientes' => $clientes,
            'status' => $status,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('clientes', 'email')->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'telefone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $cliente = new Cliente();
        $cliente->user_id = $request->user()->id;
        $cliente->nome = $data['nome'];
        $cliente->email = $data['email'];
        $cliente->telefone = $data['telefone'] ?? null;
        $cliente->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : true;
        $cliente->password = Hash::make($data['password']);
        $cliente->save();

        return redirect()
            ->route('agencia.clientes.index')
            ->with('success', 'Cliente criado com sucesso.');
    }

    public function update(Request $request, Cliente $cliente)
    {
        $this->ensureOwner($request, $cliente);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('clientes', 'email')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id))
                    ->ignore($cliente->id),
            ],
            'telefone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $cliente->nome = $data['nome'];
        $cliente->email = $data['email'];
        $cliente->telefone = $data['telefone'] ?? null;
        $cliente->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : false;

        if (!empty($data['password'])) {
            $cliente->password = Hash::make($data['password']);
        }

        $cliente->save();

        return redirect()
            ->route('agencia.clientes.index')
            ->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Request $request, Cliente $cliente)
    {
        $this->ensureOwner($request, $cliente);

        if ($cliente->conexoes()->exists()) {
            return redirect()
                ->route('agencia.clientes.index')
                ->with('error', 'Nao e possivel excluir este cliente porque ha conexoes vinculadas.');
        }

        $cliente->delete();

        return redirect()
            ->route('agencia.clientes.index')
            ->with('success', 'Cliente removido com sucesso.');
    }

    public function restore(Request $request, int $cliente)
    {
        $cliente = Cliente::withTrashed()->findOrFail($cliente);
        $this->ensureOwner($request, $cliente);

        $cliente->restore();

        return redirect()
            ->route('agencia.clientes.index', ['status' => 'trashed'])
            ->with('success', 'Cliente restaurado com sucesso.');
    }

    public function forceDelete(Request $request, int $cliente)
    {
        $cliente = Cliente::withTrashed()->findOrFail($cliente);
        $this->ensureOwner($request, $cliente);

        if ($cliente->conexoes()->withTrashed()->exists()) {
            return redirect()
                ->route('agencia.clientes.index', ['status' => 'trashed'])
                ->with('error', 'Nao e possivel excluir definitivamente este cliente porque ha conexoes vinculadas.');
        }

        $cliente->forceDelete();

        return redirect()
            ->route('agencia.clientes.index', ['status' => 'trashed'])
            ->with('success', 'Cliente excluÃ­do definitivamente.');
    }

    private function ensureOwner(Request $request, Cliente $cliente): void
    {
        if ($cliente->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
