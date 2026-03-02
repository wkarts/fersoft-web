<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PontoController extends Controller
{
    protected $empresa_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            if (!session('user_logged')) {
                return redirect('/login');
            }
            return $next($request);
        });
    }

    public function dashboard()
    {
        return view('ponto.dashboard')
            ->with('title', 'Controle de Ponto');
    }

    public function relogios()
    {
        return view('ponto.relogios')->with('title', 'Relógios de Ponto');
    }

    public function importacaoAfd()
    {
        return view('ponto.importacao_afd')->with('title', 'Importação AFD');
    }

    public function marcacoes()
    {
        return view('ponto.marcacoes')->with('title', 'Marcações de Ponto');
    }

    public function jornadas()
    {
        return view('ponto.jornadas')->with('title', 'Jornadas e Escalas');
    }

    public function ajustes()
    {
        return view('ponto.ajustes')->with('title', 'Ajustes de Ponto');
    }

    public function fechamentos()
    {
        return view('ponto.fechamentos')->with('title', 'Fechamentos de Competência');
    }

    public function relatorios()
    {
        return view('ponto.relatorios')->with('title', 'Relatórios de Ponto');
    }
}
