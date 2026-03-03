<?php

namespace App\Http\Controllers;

use App\Http\Requests\PontoFechamentoRequest;
use App\Models\PontoFechamento;
use App\Services\Ponto\PontoFechamentoService;

class PontoFechamentoController extends Controller
{
    protected $empresa_id;

    public function __construct(private PontoFechamentoService $service)
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
        $fechamentos = PontoFechamento::where('empresa_id', $this->empresa_id)->orderBy('competencia', 'desc')->paginate(24);
        return view('ponto_fechamento.index', compact('fechamentos'))
            ->with('title', 'Fechamentos de Competência');
    }

    public function fechar(PontoFechamentoRequest $request)
    {
        $value = session('user_logged');

        try {
            $this->service->fechar((int)$this->empresa_id, $request->competencia, (int)($value['id'] ?? 0), $request->observacoes);
            session()->flash('mensagem_sucesso', 'Competência fechada com sucesso!');
        } catch (\RuntimeException $e) {
            session()->flash('mensagem_erro', $e->getMessage());
        }

        return redirect('/ponto/fechamentos');
    }

    public function reabrir(PontoFechamentoRequest $request)
    {
        $value = session('user_logged');
        $this->service->reabrir((int)$this->empresa_id, $request->competencia, (int)($value['id'] ?? 0));
        session()->flash('mensagem_sucesso', 'Competência reaberta com sucesso!');
        return redirect('/ponto/fechamentos');
    }
}
