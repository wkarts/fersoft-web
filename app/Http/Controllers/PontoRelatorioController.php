<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Models\PontoMarcacao;
use App\Models\PontoOcorrencia;
use Illuminate\Http\Request;
use App\Services\Ponto\PontoExportacaoService;

class PontoRelatorioController extends Controller
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

    public function index(Request $request)
    {
        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->orderBy('nome')->get();

        $marcacoes = PontoMarcacao::where('empresa_id', $this->empresa_id)
            ->when($request->funcionario_id, fn($q) => $q->where('funcionario_id', $request->funcionario_id))
            ->when($request->data_inicio, fn($q) => $q->whereDate('data_hora_marcacao', '>=', $request->data_inicio))
            ->when($request->data_fim, fn($q) => $q->whereDate('data_hora_marcacao', '<=', $request->data_fim))
            ->orderBy('data_hora_marcacao', 'desc')
            ->paginate(60);

        $ocorrencias = PontoOcorrencia::where('empresa_id', $this->empresa_id)
            ->when($request->funcionario_id, fn($q) => $q->where('funcionario_id', $request->funcionario_id))
            ->when($request->data_inicio, fn($q) => $q->whereDate('data_referencia', '>=', $request->data_inicio))
            ->when($request->data_fim, fn($q) => $q->whereDate('data_referencia', '<=', $request->data_fim))
            ->orderBy('data_referencia', 'desc')
            ->paginate(60, ['*'], 'oc_page');

        return view('ponto_relatorios.index', compact('funcionarios', 'marcacoes', 'ocorrencias'))
            ->with('title', 'Relatórios e Espelho de Ponto');
    }

    public function csv(Request $request)
    {
        $rows = PontoOcorrencia::where('empresa_id', $this->empresa_id)
            ->when($request->funcionario_id, fn($q) => $q->where('funcionario_id', $request->funcionario_id))
            ->when($request->data_inicio, fn($q) => $q->whereDate('data_referencia', '>=', $request->data_inicio))
            ->when($request->data_fim, fn($q) => $q->whereDate('data_referencia', '<=', $request->data_fim))
            ->orderBy('data_referencia')
            ->get(['funcionario_id', 'data_referencia', 'tipo', 'minutos', 'descricao']);

        $csv = "funcionario_id;data_referencia;tipo;minutos;descricao\n";
        foreach ($rows as $r) {
            $csv .= implode(';', [
                    $r->funcionario_id,
                    $r->data_referencia,
                    $r->tipo,
                    $r->minutos,
                    str_replace(';', ',', (string)$r->descricao),
                ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ponto_ocorrencias.csv"',
        ]);
    }

    public function exportarAfd(Request $request, \App\Services\Ponto\PontoExportacaoService $exportacaoService)
    {
        $dataInicio = $request->input('data_inicio', date('Y-m-01'));
        $dataFim    = $request->input('data_fim', date('Y-m-t'));
        $funcId     = $request->filled('funcionario_id') && $request->funcionario_id !== 'TODOS'
            ? (int) $request->funcionario_id
            : null;

        $conteudoTxt = $exportacaoService->gerarAfd(
            (int) $this->empresa_id,
            $dataInicio,
            $dataFim,
            $funcId
        );

        $nomeArquivo = 'AFD_MTE_' . str_replace('-', '', $dataInicio) . '_' . str_replace('-', '', $dataFim) . '.txt';

        return response($conteudoTxt, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nomeArquivo}\"",
        ]);
    }
}
