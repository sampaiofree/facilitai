<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;


class ProfileController extends Controller 
{

    
    public function conv($conv_id = null)
    {
        return response()->json(['error' => 'Chat functionality removed'], 410);
    }
    

    public function teste(Request $request)
    {
        return response()->json(['error' => 'Chat functionality removed'], 410);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }


    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // nunca permite alterar o e-mail
        $data['email'] = $user->email;

        // detecta alteração de CPF
        $cpfAlterado = isset($data['cpf_cnpj']) && $data['cpf_cnpj'] !== $user->cpf_cnpj;

        // atualiza nome e telefone sem salvar ainda
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
                    // só altera se o Asaas aprovou
                    $user->cpf_cnpj = $data['cpf_cnpj'];
                    $user->customer_asaas_id = $asaasCustomer['id'];
                    $user->save();

                    
                } else {
                    return Redirect::route('profile.edit')->withErrors([
                        'cpf_cnpj' => 'O CPF/CNPJ informado é inválido. Digite somente números e tente novamente.'
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Erro ao criar cliente Asaas após alteração de CPF/CNPJ.', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ]);

                return Redirect::route('profile.edit')->withErrors([
                    'cpf_cnpj' => 'Não foi possível validar o CPF/CNPJ. Tente novamente.'
                ]);
            }
        } else {
            // se não alterou o CPF, salva normalmente
            $user->save();
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }


    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
