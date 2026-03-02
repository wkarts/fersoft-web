<?php

namespace App\Http\Controllers;

use App\Http\Requests\PontoEscalaRequest;
use App\Http\Requests\PontoJornadaRequest;
use App\Http\Requests\PontoTurnoRequest;
use App\Models\PontoEscala;
use App\Models\PontoJornada;
use App\Models\PontoTurno;

class PontoJornadaController extends Controller
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

    public function index()
    {
        $jornadas = PontoJornada::where('empresa_id', $this->empresa_id)->orderBy('id', 'desc')->get();
        $turnos = PontoTurno::where('empresa_id', $this->empresa_id)->orderBy('id', 'desc')->get();
        $escalas = PontoEscala::where('empresa_id', $this->empresa_id)->orderBy('id', 'desc')->get();

        return view('ponto_jornada.index', compact('jornadas', 'turnos', 'escalas'))
            ->with('title', 'Jornadas, Turnos e Escalas');
    }

    public function storeJornada(PontoJornadaRequest $request)
    {
        PontoJornada::create([
            'empresa_id' => $this->empresa_id,
            'nome' => $request->nome,
            'regras_semana' => $request->input('regras_semana', []),
            'tolerancia_atraso_min' => (int)$request->input('tolerancia_atraso_min', 0),
            'tolerancia_extra_min' => (int)$request->input('tolerancia_extra_min', 0),
            'ativo' => (bool)$request->input('ativo', true),
        ]);

        session()->flash('mensagem_sucesso', 'Jornada cadastrada com sucesso!');
        return redirect('/ponto/jornadas');
    }

    public function storeTurno(PontoTurnoRequest $request)
    {
        PontoTurno::create(array_merge($request->validated(), [
            'empresa_id' => $this->empresa_id,
            'cruza_meia_noite' => (bool)$request->input('cruza_meia_noite', false),
            'ativo' => (bool)$request->input('ativo', true),
        ]));

        session()->flash('mensagem_sucesso', 'Turno cadastrado com sucesso!');
        return redirect('/ponto/jornadas');
    }

    public function storeEscala(PontoEscalaRequest $request)
    {
        PontoEscala::create(array_merge($request->validated(), [
            'empresa_id' => $this->empresa_id,
            'regras' => $request->input('regras', []),
            'ativo' => (bool)$request->input('ativo', true),
        ]));

        session()->flash('mensagem_sucesso', 'Escala cadastrada com sucesso!');
        return redirect('/ponto/jornadas');
    }
}
