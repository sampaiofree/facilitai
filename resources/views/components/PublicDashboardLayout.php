<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class PublicDashboardLayout extends Component
{
    /**
     * Obtém a view / conteúdo que representa o componente.
     */
    public function render(): View
    {
        // Este método simplesmente diz ao Laravel para usar a view 'public-dashboard-layout'
        // que está dentro da pasta 'resources/views/components/'.
        return view('components.public-dashboard-layout');
    }
}