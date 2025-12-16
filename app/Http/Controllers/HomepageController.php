<?php

namespace App\Http\Controllers;

class HomepageController extends Controller
{
    public function facilitaiPricing()
    {
        return view('landing.facilitai-pricing', $this->pricingData());
    }

    protected function pricingData(): array
    {
        $features = [
            ['icon' => 'message-square', 'text' => 'Assistente inteligente que atende automático'],
            ['icon' => 'zap', 'text' => 'Fluxos de atendimento personalizados'],
            ['icon' => 'calendar', 'text' => 'Agendamento automático'],
            ['icon' => 'image', 'text' => 'Envio de mídia (vídeo, imagem, áudio, PDF)'],
            ['icon' => 'external-link', 'text' => 'Busca de links externos'],
            ['icon' => 'bell', 'text' => 'Notificação para administrador'],
            ['icon' => 'users', 'text' => 'Transferência para atendimento humano'],
            ['icon' => 'send', 'text' => 'Sequências automáticas'],
            ['icon' => 'tag', 'text' => 'Tags automáticas'],
            ['icon' => 'message-square', 'text' => 'Mensagens ilimitadas'],
            ['icon' => 'bar-chart-3', 'text' => 'Dashboard completo'],
        ];

        $monthlyPlans = [
            [
                'connections' => 1,
                'assistants' => 1,
                'price_label' => '247',
                'price_per_connection_label' => '247,00',
                'checkout' => 'https://pay.hotmart.com/U102725550Y?off=yqncr3mx&checkoutMode=10',
            ],
            [
                'connections' => 3,
                'assistants' => 3,
                'price_label' => '347',
                'price_per_connection_label' => '115,67',
                'checkout' => 'https://pay.hotmart.com/U102725550Y?off=77v5yieb&checkoutMode=10',
                'popular' => true,
            ],
            [
                'connections' => 5,
                'assistants' => 5,
                'price_label' => '447',
                'price_per_connection_label' => '89,40',
                'checkout' => 'https://pay.hotmart.com/U102725550Y?off=x8jw71pc&checkoutMode=10',
            ],
            [
                'connections' => 10,
                'assistants' => 10,
                'price_label' => '547',
                'price_per_connection_label' => '54,70',
                'checkout' => 'https://pay.hotmart.com/U102725550Y?off=r62eq6jh&checkoutMode=10',
            ],
        ];

        $yearlyPlans = [
            [
                'connections' => 1,
                'assistants' => 1,
                'price_label' => '197',
                'price_per_connection_label' => '197,00',
                'checkout' => 'https://pay.hotmart.com/U102725550Y?off=kemggz0j&checkoutMode=10',
                'savings_label' => 'Economize R$600',
            ],
            [
                'connections' => 3,
                'assistants' => 3,
                'price_label' => '297',
                'price_per_connection_label' => '99,00',
                'checkout' => 'https://pay.hotmart.com/U102725550Y?off=bcocek3y&checkoutMode=10',
                'popular' => true,
                'savings_label' => 'Economize R$600',
            ],
            [
                'connections' => 5,
                'assistants' => 5,
                'price_label' => '397',
                'price_per_connection_label' => '79,40',
                'checkout' => 'https://pay.hotmart.com/U102725550Y?off=kbejejiv&checkoutMode=10',
                'savings_label' => 'Economize R$600',
            ],
            [
                'connections' => 10,
                'assistants' => 10,
                'price_label' => '497',
                'price_per_connection_label' => '49,70',
                'checkout' => 'https://pay.hotmart.com/U102725550Y?off=kaypzmv9&checkoutMode=10',
                'savings_label' => 'Economize R$600',
            ],
        ];

        $faqs = [
            ['q' => 'Preciso saber programar?', 'a' => 'Não. Tudo é feito por formulários simples.'],
            ['q' => 'Funciona em qualquer tipo de negócio?', 'a' => 'Sim. Clínicas, lojas, prestadores de serviço, infoprodutos e muito mais.'],
            ['q' => 'Posso usar para clientes?', 'a' => 'Sim. O FacilitAI foi pensado também para agências.'],
            ['q' => 'Tem contrato?', 'a' => 'Não. Cancele quando quiser.'],
        ];

        return compact('features', 'monthlyPlans', 'yearlyPlans', 'faqs');
    }
}
