<?php

namespace App\Http\Controllers;

use App\Http\Requests\PontoTratamentoRequest;
use App\Models\Funcionario;
use App\Models\PontoMarcacao;
use App\Models\PontoOcorrencia;
use App\Services\Ponto\PontoJornadaTratamentoService;
use Illuminate\Http\Request;

class PontoMarcacaoController extends Controller
{
    protected $empresa_id;

    public function __construct(private PontoJornadaTratamentoService $tratamentoService)
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            if (!session('user_logged')) {
                return redirect('/login');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->orderBy('nome')->get();

        $marcacoes = PontoMarcacao::where('empresa_id', $this->empresa_id)
            ->when($request->funcionario_id, fn($q) => $q->where('funcionario_id', $request->funcionario_id))
            ->orderBy('data_hora_marcacao', 'desc')
            ->paginate(50);

        $ocorrencias = PontoOcorrencia::where('empresa_id', $this->empresa_id)
            ->when($request->funcionario_id, fn($q) => $q->where('funcionario_id', $request->funcionario_id))
            ->orderBy('data_referencia', 'desc')
            ->paginate(50, ['*'], 'ocorrencias_page');

        return view('ponto_marcacao.index', compact('funcionarios', 'marcacoes', 'ocorrencias'))
            ->with('title', 'Marcações e Tratamento de Jornada');
    }

    public function tratar(PontoTratamentoRequest $request)
    {
        $resumo = $this->tratamentoService->tratarPeriodo(
            (int)$this->empresa_id,
            (int)$request->funcionario_id,
            $request->data_inicio,
            $request->data_fim
        );

        session()->flash('mensagem_sucesso', "Tratamento concluído. Dias: {$resumo['dias']} | Ocorrências: {$resumo['ocorrencias']}");

        return redirect('/ponto/marcacoes?funcionario_id=' . $request->funcionario_id);
    }
}
