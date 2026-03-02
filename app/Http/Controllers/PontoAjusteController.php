<?php

namespace App\Http\Controllers;

use App\Http\Requests\PontoAjusteAprovacaoRequest;
use App\Http\Requests\PontoAjusteRequest;
use App\Models\Funcionario;
use App\Models\PontoAjuste;
use App\Models\PontoAjusteAprovacao;

class PontoAjusteController extends Controller
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
        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->orderBy('nome')->get();
        $ajustes = PontoAjuste::where('empresa_id', $this->empresa_id)->orderBy('id', 'desc')->paginate(30);

        return view('ponto_ajuste.index', compact('funcionarios', 'ajustes'))
            ->with('title', 'Ajustes de Ponto');
    }

    public function store(PontoAjusteRequest $request)
    {
        $value = session('user_logged');

        PontoAjuste::create(array_merge($request->validated(), [
            'empresa_id' => $this->empresa_id,
            'solicitado_por' => $value['id'] ?? null,
            'status' => 'pendente',
        ]));

        session()->flash('mensagem_sucesso', 'Ajuste solicitado com sucesso!');
        return redirect('/ponto/ajustes');
    }

    public function aprovar(PontoAjusteAprovacaoRequest $request)
    {
        $value = session('user_logged');
        $ajuste = PontoAjuste::where('empresa_id', $this->empresa_id)->findOrFail($request->ponto_ajuste_id);

        PontoAjusteAprovacao::create([
            'empresa_id' => $this->empresa_id,
            'ponto_ajuste_id' => $ajuste->id,
            'aprovador_id' => $value['id'] ?? null,
            'status' => $request->status,
            'parecer' => $request->parecer,
            'nivel' => $request->input('nivel', 1),
            'aprovado_em' => now(),
        ]);

        $ajuste->status = $request->status;
        $ajuste->save();

        session()->flash('mensagem_sucesso', 'Aprovação registrada com sucesso!');
        return redirect('/ponto/ajustes');
    }
}
