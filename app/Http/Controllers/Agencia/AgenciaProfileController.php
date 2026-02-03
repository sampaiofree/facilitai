<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class AgenciaProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('agencia.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // nunca permite alterar o e-mail
        $data['email'] = $user->email;

        $cpfAlterado = isset($data['cpf_cnpj']) && $data['cpf_cnpj'] !== $user->cpf_cnpj;

        $user->name = $data['name'];
        $user->mobile_phone = $data['mobile_phone'];

        if ($cpfAlterado) {
            try {
                $asaas = new \App\Services\AsaasService();
                $asaasCustomer = $asaas->createCustomer([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'cpfCnpj' => $data['cpf_cnpj'],
                    'mobilePhone' => $data['mobile_phone'],
                ]);

                if ($asaasCustomer && isset($asaasCustomer['id'])) {
                    $user->cpf_cnpj = $data['cpf_cnpj'];
                    $user->customer_asaas_id = $asaasCustomer['id'];
                    $user->save();
                } else {
                    return Redirect::route('agencia.profile.edit')->withErrors([
                        'cpf_cnpj' => 'O CPF/CNPJ informado é inválido. Digite somente números e tente novamente.',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Erro ao criar cliente Asaas após alteração de CPF/CNPJ.', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);

                return Redirect::route('agencia.profile.edit')->withErrors([
                    'cpf_cnpj' => 'Não foi possível validar o CPF/CNPJ. Tente novamente.',
                ]);
            }
        } else {
            $user->save();
        }

        return Redirect::route('agencia.profile.edit')->with('status', 'profile-updated');
    }
}
