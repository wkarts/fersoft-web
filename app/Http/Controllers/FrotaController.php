<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FrotaController extends Controller
{
    public function __construct()
    {
        // Esta é a trava que o seu sistema usa
        $this->middleware(function ($request, $next) {
            $value = session('user_logged');
            if (!$value) {
                return redirect("/login");
            }
            return $next($request);
        });
    }

   public function manutencoes()
{
    return view('manutencoes.lista_frota')
        ->with('title', 'Manutenções de Frota'); // Adicione esta linha
}

public function relatorios()
{
    return view('movimentacaoVeiculo.relatorios_frota')
        ->with('title', 'Relatórios de Frota'); // Adicione esta linha
}

public function dashboard()
{
    return view('movimentacaoVeiculo.dashboard_frota')
        ->with('title', 'Dashboard de Frota'); // Adicione esta linha
}
}