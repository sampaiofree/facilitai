<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'cpf_cnpj' => ['nullable', 'string', 'max:50'],
            'mobile_phone' => ['nullable', 'string', 'max:20'],
            'customer_asaas_id' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
            'mobile_phone' => $data['mobile_phone'] ?? null,
            'customer_asaas_id' => $data['customer_asaas_id'] ?? null,
            'password' => $data['password'],
            'is_admin' => $request->boolean('is_admin'),
        ]);

        return redirect()
            ->route('adm.users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'cpf_cnpj' => ['nullable', 'string', 'max:50'],
            'mobile_phone' => ['nullable', 'string', 'max:20'],
            'customer_asaas_id' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
            'mobile_phone' => $data['mobile_phone'] ?? null,
            'customer_asaas_id' => $data['customer_asaas_id'] ?? null,
            'is_admin' => $request->boolean('is_admin'),
        ];

        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return redirect()
            ->route('adm.users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }
}
