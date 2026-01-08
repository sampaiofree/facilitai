<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Services\WebshareService; 
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Services\EvolutionService;
use App\Services\ConversationsService;
use App\Services\BuscarEmpresasService;
use App\Http\Controllers\InstanceController;

use App\Console\Commands\ColetarEmpresaNova;
use App\Models\LeadEmpresa;
use App\Services\OpenAIService;
use App\Models\Instance;
use App\Models\Chat;
use DOMDocument;
use DOMXPath;
use App\Services\AsaasService;
use App\Models\Lead;
use Carbon\Carbon;


class ProfileController extends Controller 
{

    
    public function conv($conv_id = null)
    {

        /*$evo  = new EvolutionService(); 
        $response = $evo->enviarPresenca('177', '5562995772922', 'composing');
        dd($response); exit;*/


        $chat = Chat::where('conv_id', $conv_id)->first();

        if (!$chat) {
            return response()->json(['error' => 'Chat not found'], 404);
        }

        $op = new ConversationsService(null, $chat->contact, $chat->instance_id);
        $itens = $op->getConversationItems($chat->conv_id);
        dump($itens)->depth(100);
        die;

    }
    

    public function teste(Request $request)
    {

        $evo  = new EvolutionService(); 
        $evo->enviarMedia('5562995772922', 'https://app.3f7.org/storage/user_images/34/FRaXnlJzKBENPxN8C40wRFCsVzeUBwemRCekFQFc.jpg', '177');
        dd($evo); exit;

        /*$chat = Chat::where('assistant_id', 4)
        //->where('updated_at', '<=', Carbon::now()->subDay()) //Exclui as ultimas 24horas //->where('updated_at', '<', Carbon::today()) // exclui hoje
        ->where('bot_enabled', '1')
        ->orderBy('updated_at', 'asc')
        ->first();
            
        $mensagem = "[#Notificação do sistema: Informe o cliente que antecipamos a blackfriday. O cliente pode escolher qualquer curso com 80% de desconto. Depois que o cliente escolher o curso envio o link do checkout com a Query string offDiscount=80OFF]";


        $op = new ConversationsService($mensagem, $chat->contact, '177');
        $op->enviarMSG();
        die;

        $evo  = new EvolutionService(); 
        $evo->enviarMedia('5562995772922', 'https://app.3f7.org/storage/user_images/1/l0gZhaP9AGvIUyTJdgYcNm7SfRiiU1p596xj3ugF.mp3', '177');
        dd($evo); exit;*/

    
    //$chat = Chat::where('assistant_id', 4)
    //->where('updated_at', '<', Carbon::today())
    /*->where('updated_at', '<=', Carbon::now()->subDay()) //->where('updated_at', '<', Carbon::today())
    ->where('bot_enabled', '1') // exclui hoje
    ->orderBy('updated_at', 'desc')
    ->first();

    $contact = $chat->contact;

    //dd($chat);   //conv_68fb941bcb0881948d0f26497d4cab870784f68f3a4b16d9

    $mensagem = "[#Notificação do sistema: Retome a conversa para tentar vender um curso para o cliente.]";


    //$mensagem = "Sim";
    //$contact = '558491943877';*/

    // 2) Pega últimas mensagens do assistente
    $op = new ConversationsService(null, null, '176');
    //dd($op->teste());
    $itens = $op->getConversationItems('conv_690e815fbdb481908429d714845cc8c60273bfaf3748d17c');
    dump($itens)->depth(50);
    die;

    
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
