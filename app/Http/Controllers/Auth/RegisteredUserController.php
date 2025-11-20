<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Services\AsaasService;
use Illuminate\Support\Facades\Log;
use App\Models\TokenBonus;
use App\Models\HotmarlWebhook;
use App\Services\TokenBonusService;
use Carbon\Carbon;


class RegisteredUserController extends Controller
{

    protected AsaasService $asaasService; // Adicione esta propriedade

    // Injete o AsaasService no construtor
    public function __construct(AsaasService $asaasService)
    {
        $this->asaasService = $asaasService;
    } 


    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Normaliza antes de validar
        $data = $request->all();
        $data['cpf_cnpj'] = preg_replace('/\D/', '', $request->cpf_cnpj);
        $data['mobile_phone'] = preg_replace('/\D/', '', $request->mobile_phone);

        $validated = validator($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'cpf_cnpj' => ['required', 'digits_between:11,14', 'unique:'.User::class],
            'mobile_phone' => ['required', 'digits_between:10,20'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ])->validate();

        //CONSULTAR SE JÁ EXISTE UM COMPRA COM ESSE EMAIL NO HOTMART
        $hotmarlWebhook = HotmarlWebhook::where('buyer_email', $validated['email'])
        ->whereIn('event', ['PURCHASE_COMPLETE', 'PURCHASE_APPROVED'])
        ->first();
        if (!$hotmarlWebhook) {
            //se não existir retornar com mensagem para ele comprar
            return back()->withErrors(['email' => 'Para se registrar, você precisa ter adquirido um de nossos planos primeiro. Por favor, faça uma compra antes de se registrar.']);
        }

        // Tenta criar o cliente no Asaas
        $asaasCustomerId = null;

        try {
            $asaasCustomer = $this->asaasService->createCustomer([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'cpfCnpj' => $validated['cpf_cnpj'],
                'mobilePhone' => $validated['mobile_phone'],
            ]);

            // Se o retorno contiver erro → aborta
            if (isset($asaasCustomer['error'])) {
                $msg = 'O CPF/CNPJ informado é inválido.';
                if (isset($asaasCustomer['response']['errors'][0]['description'])) {
                    $msg = $asaasCustomer['response']['errors'][0]['description']; // Mensagem real do Asaas
                }

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cpf_cnpj' => $msg
                ]);
            }

            if ($asaasCustomer && isset($asaasCustomer['id'])) {
                $asaasCustomerId = $asaasCustomer['id'];
                Log::info('Cliente Asaas criado com sucesso:', [
                    'customer_id' => $asaasCustomerId,
                    'email' => $validated['email']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Exceção ao tentar criar cliente Asaas no registro:', [
                'error' => $e->getMessage(),
                'email' => $validated['email']
            ]);

            throw \Illuminate\Validation\ValidationException::withMessages([
                'cpf_cnpj' => 'O CPF/CNPJ informado é inválido.'
            ]);
        }


        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cpf_cnpj' => $validated['cpf_cnpj'], 
            'mobile_phone' => $validated['mobile_phone'], 
            'password' => Hash::make($validated['password']),
            'customer_asaas_id' => $asaasCustomerId, // Salva o ID do cliente Asaas
        ]);

        // Bônus inicial de 50 mil tokens (válido por 7 dias)
        //$tokenbonus = new TokenBonusService;
        //$tokenbonus->criarBonus($user->id,30000, 'Bônus gratuito de boas-vindas', Carbon::now(), Carbon::now()->addDays(7),null);



        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
