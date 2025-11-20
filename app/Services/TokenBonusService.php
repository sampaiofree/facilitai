<?php

namespace App\Services;

use App\Models\TokenBonus;
use App\Models\User;
use Illuminate\Support\Carbon;
use App\Models\HotmarlWebhook;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\Return_;

class TokenBonusService
{
    /**
     * Cria um bônus de tokens para um usuário.
     *
     * @param User|int $user Usuário (ou ID) que vai receber os tokens
     * @param int $tokens Quantidade de tokens a serem adicionados
     * @param string|null $informacoes Texto informativo
     * @param int $diasValidos Quantos dias o bônus será válido (padrão: 7)
     * @param string|null $hotmart Código ou ID da transação Hotmart, se aplicável
     * @return TokenBonus
     */
    public function criarBonus($user, int $tokens, ?string $informacoes = null, $dataInicio, $dataFim, ?string $hotmart = null): TokenBonus
    {
        $userInstance = $user instanceof User ? $user : User::find($user);
        if (!$userInstance) {
            throw new \Exception("Usuário não encontrado para bônus de tokens.");
        }

        // Se tiver uma compra hotmart aprovada, processa os bônus relacionados
        /*if ($hotmartModel = $userInstance->hotmartWebhooks()) {
            $this->bonusHotmart($hotmartModel);
        }*/

        return TokenBonus::create([
            'user_id'     => $userInstance->id,
            'tokens'      => $tokens,
            'informacoes' => $informacoes ?? 'Bônus de tokens',
            'inicio'      => $dataInicio,
            'fim'         => $dataFim,
            'hotmart'     => $hotmart,
        ]);
    }


    public function bonusHotmart(HotmarlWebhook $hotmart)
    {
        return;
        $user = $hotmart->user;

        // Verifica se o usuário já está registrado no sistema
        if (!$user) {
            Log::info("⏳ Usuário ainda não registrado na plataforma para a transação {$hotmart->transaction}");
            return;
        }

        // Se o evento NÃO for de aprovação, apaga bônus dessa transação
        if (!in_array($hotmart->event, ['PURCHASE_APPROVED', 'PURCHASE_COMPLETE'])) {
            $apagados = TokenBonus::where('hotmart', $hotmart->transaction)->delete();
            Log::info("🧹 Evento {$hotmart->event} → apagados {$apagados} bônus da transação {$hotmart->transaction}");
            return;
        }

        // Verifica se já existem 12 registros de bônus para esta transação
        $bonusCount = TokenBonus::where('hotmart', $hotmart->transaction)->count();
        if ($bonusCount >= 12) {
            Log::info("🔁 Bônus já existentes para transação {$hotmart->transaction}, ignorado.");
            return;
        }

        $tokensMensal = [
            'qgwj4ldg'    => 3000000,
        ];

        // Define quantidade de tokens por mês conforme o offer_code
        $mapaDeTokens = [
            //Plano 1 conexão
            'c8n7uxen'    => 1000000,
            '6507rpho'    => 1000000,
            'a2ykgt3s'    => 1000000,

            //promoção blackfriday
            'ngx9x5sp'    => 2000000,
            

            //Plano 2 conexoes
            'hkasortp'    => 3000000,
            'bxgewgqh'    => 3000000,
            'ghpkyyuq'    => 3000000,
            
            //Agencia START
            'seesl6xb'    => 5000000,
            'u55e5gnk'    => 5000000,
            'ca9g29lkJWT' => 5000000,
            'ca9g29lk' => 5000000,
            
            //Agencia
            '2sr5xelf' => 15000000,
            '2sr5xelfDRP' => 15000000,

            // Plano mensal
            'qgwj4ldg'    => 3000000, //1 conexão - R$197
            '4x3odbtt'    => 500000, //1 conexão - R$197

            //plano semestral
            'cyvxmia3'    => 5000000, //agencia start
        ];

        $tokensPorMes = $mapaDeTokens[$hotmart->offer_code] ?? null;

        if (!$tokensPorMes) {
            Log::warning("⚠️ Offer code desconhecido: {$hotmart->offer_code} na transação {$hotmart->transaction}");
            return;
        }

        $inicio = Carbon::now()->startOfDay();
        $fim = (clone $inicio)->addDays(30);

        TokenBonus::create([
                'user_id'     => $user->id,
                'tokens'      => $tokensPorMes,
                'informacoes' => "Bônus mensal - Plano {$hotmart->offer_code}",
                'inicio'      => $inicio,
                'fim'         => $fim,
                'hotmart'     => $hotmart->transaction,
        ]);

        if ($hotmart->offer_code != 'ngx9x5sp' AND $hotmart->offer_code !='ca9g29lk'){
            return;
        }
        

        // Se for o plano mensal qgwj4ldg → apenas 1 bônus
        /*if ($hotmart->offer_code === 'qgwj4ldg' OR $hotmart->offer_code === '4x3odbtt') {
            $inicio = Carbon::now()->startOfDay();
            $fim = (clone $inicio)->addDays(30);

            TokenBonus::create([
                'user_id'     => $user->id,
                'tokens'      => $tokensPorMes,
                'informacoes' => "Bônus mensal - Plano {$hotmart->offer_code}",
                'inicio'      => $inicio,
                'fim'         => $fim,
                'hotmart'     => $hotmart->transaction,
            ]);

            Log::info("🎁 1 bônus mensal gerado para {$user->email} - Plano {$hotmart->offer_code} - Transação {$hotmart->transaction}");
            return;
        }

        //PLANO SEMESTRAL
        if ($hotmart->offer_code === 'cyvxmia3') {

            for ($i = 0; $i < 6; $i++) {
                $inicio = Carbon::now()->addMonths($i)->startOfDay();
                $fim    = (clone $inicio)->addDays(30);
                $mes = $i + 1;

                TokenBonus::create([
                    'user_id'     => $user->id,
                    'tokens'      => $tokensPorMes,
                    'informacoes' => "Bônus {$mes}/6 - Plano {$hotmart->offer_code}",
                    'inicio'      => $inicio,
                    'fim'         => $fim,
                    'hotmart'     => $hotmart->transaction,
                ]);
            }

            Log::info("🎁 12 bônus mensais gerados para {$user->email} - Plano {$hotmart->offer_code} - Transação {$hotmart->transaction}");
            return;

        }*/

        // Cria 12 registros de bônus mensais
        for ($i = 0; $i < 12; $i++) {
            $inicio = Carbon::now()->addMonths($i)->startOfDay();
            $fim    = (clone $inicio)->addDays(30);
            $mes = $i + 1;

            TokenBonus::create([
                'user_id'     => $user->id,
                'tokens'      => $tokensPorMes,
                'informacoes' => "Bônus {$mes}/12 - Plano {$hotmart->offer_code}",
                'inicio'      => $inicio,
                'fim'         => $fim,
                'hotmart'     => $hotmart->transaction,
            ]);
        }

        Log::info("🎁 12 bônus mensais gerados para {$user->email} - Plano {$hotmart->offer_code} - Transação {$hotmart->transaction}");
        return;
    }

}
