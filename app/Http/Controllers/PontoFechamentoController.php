<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Funcionario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\Ponto\PontoCalculoService;

class PontoFechamentoController extends Controller
{
    protected $empresa_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = request()->empresa_id ?? auth()->user()->empresa_id ?? 1;
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $title = 'Fechamento Mensal e Banco de Horas';
        $competencia = $request->input('competencia', date('Y-m'));

        $dataInicio = Carbon::parse($competencia . '-01')->startOfMonth()->toDateString();
        $dataFim    = Carbon::parse($competencia . '-01')->endOfMonth()->toDateString();

        $fechamento = DB::table('ponto_fechamentos')
            ->where('empresa_id', $this->empresa_id)
            ->where('competencia', $competencia)
            ->first();

        $query = Funcionario::where('empresa_id', $this->empresa_id);
        if (Schema::hasColumn('funcionarios', 'ativo')) {
            $query->where('ativo', 1);
        }
        $funcionarios = $query->orderBy('nome', 'asc')->get();

        $horaParaMin = function ($horaStr) {
            if (empty($horaStr) || !str_contains($horaStr, ':')) return 0;
            $p = explode(':', $horaStr);
            $sinal = str_starts_with($horaStr, '-') ? -1 : 1;
            return $sinal * ((abs((int)$p[0]) * 60) + (int)$p[1]);
        };

        $minParaHora = function ($minutos) {
            $sinal = $minutos < 0 ? '-' : '+';
            $abs = abs($minutos);
            return sprintf('%s%02d:%02dh', $sinal, floor($abs / 60), $abs % 60);
        };

        $relatorioColaboradores = [];

        foreach ($funcionarios as $f) {
            $resumos = DB::table('ponto_resumos_diarios')
                ->where('funcionario_id', $f->id)
                ->whereBetween('data', [$dataInicio, $dataFim])
                ->get();

            $minTrabalhados = 0;
            $minExtras = 0;
            $minAtrasos = 0;

            foreach ($resumos as $r) {
                $minTrabalhados += $horaParaMin($r->horas_trabalhadas);
                $minExtras      += $horaParaMin($r->horas_extras);
                $minAtrasos     += $horaParaMin($r->horas_atraso);
            }

            $saldoMesMin = $minExtras - $minAtrasos;

            // Saldo anterior acumulado antes do mês atual
            $saldoAnteriorMin = (int) DB::table('ponto_banco_horas')
                ->where('funcionario_id', $f->id)
                ->where('data_referencia', '<', $dataInicio)
                ->orderBy('data_referencia', 'desc')
                ->value('saldo_minutos') ?? 0;

            // Lançamento de compensação deste mês (se houver)
            $registroCompensacao = DB::table('ponto_banco_horas')
                ->where('funcionario_id', $f->id)
                ->where('data_referencia', $dataFim)
                ->where('origem', 'compensacao')
                ->first();

            $minCompensados = $registroCompensacao ? (int)$registroCompensacao->minutos_debito : 0;
            $saldoFinalAcumuladoMin = $saldoAnteriorMin + $saldoMesMin - $minCompensados;

            $relatorioColaboradores[] = [
                'funcionario_id'    => $f->id,
                'nome'              => $f->nome,
                'cpf'               => $f->cpf,
                'cargo'             => $f->funcao->nome ?? '---',
                'trabalhadas'       => $minParaHora($minTrabalhados),
                'extras'            => $minParaHora($minExtras),
                'atrasos'           => $minParaHora($minAtrasos),
                'saldo_anterior'    => $minParaHora($saldoAnteriorMin),
                'saldo_mes'         => $minParaHora($saldoMesMin),
                'saldo_mes_min'     => $saldoMesMin,
                'compensado_min'    => $minCompensados,
                'compensado_fmt'    => $minParaHora($minCompensados),
                'saldo_acumulado'   => $minParaHora($saldoFinalAcumuladoMin),
                'saldo_positivo'    => $saldoFinalAcumuladoMin >= 0,
            ];
        }

        return view('ponto.fechamento', compact(
            'title',
            'competencia',
            'fechamento',
            'relatorioColaboradores',
            'dataInicio',
            'dataFim'
        ));
    }

    public function processarFechamento(Request $request, PontoCalculoService $calculoService)
    {
        $competencia = $request->input('competencia');
        $dataInicio  = Carbon::parse($competencia . '-01')->startOfMonth()->toDateString();
        $dataFim     = Carbon::parse($competencia . '-01')->endOfMonth()->toDateString();

        $query = Funcionario::where('empresa_id', $this->empresa_id);
        if (Schema::hasColumn('funcionarios', 'ativo')) {
            $query->where('ativo', 1);
        }
        $funcionarios = $query->get();

        $horaParaMin = function ($horaStr) {
            if (empty($horaStr) || !str_contains($horaStr, ':')) return 0;
            $p = explode(':', $horaStr);
            $sinal = str_starts_with($horaStr, '-') ? -1 : 1;
            return $sinal * ((abs((int)$p[0]) * 60) + (int)$p[1]);
        };

        foreach ($funcionarios as $f) {
            $cursor = Carbon::parse($dataInicio);
            while ($cursor->lte(Carbon::parse($dataFim))) {
                $calculoService->calcularDia($f->id, $cursor->toDateString());
                $cursor->addDay();
            }

            $resumos = DB::table('ponto_resumos_diarios')
                ->where('funcionario_id', $f->id)
                ->whereBetween('data', [$dataInicio, $dataFim])
                ->get();

            $minExtras = 0;
            $minAtrasos = 0;

            foreach ($resumos as $r) {
                $minExtras  += $horaParaMin($r->horas_extras);
                $minAtrasos += $horaParaMin($r->horas_atraso);
            }

            $saldoMesMin = $minExtras - $minAtrasos;

            $saldoAnteriorMin = (int) DB::table('ponto_banco_horas')
                ->where('funcionario_id', $f->id)
                ->where('data_referencia', '<', $dataInicio)
                ->orderBy('data_referencia', 'desc')
                ->value('saldo_minutos') ?? 0;

            $saldoFinal = $saldoAnteriorMin + $saldoMesMin;

            // Insere ou atualiza o saldo mensal usando as colunas reais da tabela
            DB::table('ponto_banco_horas')->updateOrInsert(
                [
                    'empresa_id'      => $this->empresa_id,
                    'funcionario_id'  => $f->id,
                    'data_referencia' => $dataFim,
                    'origem'          => 'fechamento_mensal',
                ],
                [
                    'minutos_credito' => $minExtras,
                    'minutos_debito'  => $minAtrasos,
                    'saldo_minutos'   => $saldoFinal,
                    'observacoes'     => "Fechamento mensal da competência {$competencia}",
                    'updated_at'      => now(),
                ]
            );
        }

        $fechamentoExistente = DB::table('ponto_fechamentos')
            ->where('empresa_id', $this->empresa_id)
            ->where('competencia', $competencia)
            ->first();

        $versaoAtual = $fechamentoExistente ? ((int)$fechamentoExistente->versao + 1) : 1;

        DB::table('ponto_fechamentos')->updateOrInsert(
            [
                'empresa_id'   => $this->empresa_id,
                'competencia'  => $competencia,
            ],
            [
                'status'       => 'fechado',
                'fechado_por'  => auth()->id(),
                'fechado_em'   => now(),
                'versao'       => $versaoAtual,
                'observacoes'  => "Fechamento mensal da competência {$competencia}",
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );

        return redirect()->back()->with('success', "Competência {$competencia} fechada com sucesso!");
    }

    public function reabrirFechamento(Request $request)
    {
        $competencia = $request->input('competencia');
        $dataFim     = Carbon::parse($competencia . '-01')->endOfMonth()->toDateString();

        DB::table('ponto_fechamentos')
            ->where('empresa_id', $this->empresa_id)
            ->where('competencia', $competencia)
            ->update([
                'status'       => 'aberto',
                'reaberto_por' => auth()->id(),
                'reaberto_em'  => now(),
                'updated_at'   => now(),
            ]);

        // Remove os saldos congelados gerados no fechamento da competência
        DB::table('ponto_banco_horas')
            ->where('empresa_id', $this->empresa_id)
            ->where('data_referencia', $dataFim)
            ->where('origem', 'fechamento_mensal')
            ->delete();

        return redirect()->back()->with('success', "Competência {$competencia} reaberta para ajustes.");
    }

    public function ajustarSaldo(Request $request)
    {
        $request->validate([
            'funcionario_id'      => 'required',
            'competencia'         => 'required',
            'minutos_compensados' => 'required|integer',
            'motivo'              => 'required|min:4'
        ]);

        $dataFim = Carbon::parse($request->competencia . '-01')->endOfMonth()->toDateString();

        DB::table('ponto_banco_horas')->updateOrInsert(
            [
                'empresa_id'      => $this->empresa_id,
                'funcionario_id'  => $request->funcionario_id,
                'data_referencia' => $dataFim,
                'origem'          => 'compensacao',
            ],
            [
                'minutos_credito' => 0,
                'minutos_debito'  => $request->minutos_compensados,
                'saldo_minutos'   => -$request->minutos_compensados,
                'observacoes'     => $request->motivo,
                'updated_at'      => now(),
            ]
        );

        return redirect()->back()->with('success', 'Compensação salva com sucesso!');
    }
}
