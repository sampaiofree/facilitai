<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function workshop(Request $request)
    {
        // Captura todos os parâmetros da URL
        $params = $request->query();

        // Monta a query string (ex: utm_source=insta&utm_medium=bio)
        $queryString = http_build_query($params);

         // Links base
        $planoBasico = "https://pay.hotmart.com/Y102167704K?off=0oeubdhw";
        $planoPro    = "https://pay.hotmart.com/Y102167704K?off=4x3odbtt";

        // Adiciona os parâmetros, se existirem
        if (!empty($queryString)) {
            $planoBasico .= '&' . $queryString;
            $planoPro    .= '&' . $queryString;
        }

        // Retorna os links prontos para a view
        return view('lp.workshop', compact('planoBasico', 'planoPro'));
    }
}
