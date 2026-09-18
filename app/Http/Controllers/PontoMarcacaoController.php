<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PontoMarcacao;
use App\Models\Funcionario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PontoMarcacaoController extends Controller
{
    protected $empresa_id = null;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $userLogged = session('user_logged');
            $this->empresa_id = $request->empresa_id ?? ($userLogged['empresa_id'] ?? null);

            if (!$userLogged) {
                return redirect("/login");
            }
            return $next($request);
        });
    }

    /**
     * Dashboard / Listagem Básica das Marcações
     */
    public function index(Request $request)
    {
        $title = 'Registro Geral de Marcações de Ponto';

        $query = PontoMarcacao::with(['funcionario'])
            ->where('empresa_id', $this->empresa_id)
            ->orderBy('data_hora_marcacao', 'desc');

        if ($request->filled('data_inicio')) {
            $query->whereDate('data_hora_marcacao', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('data_hora_marcacao', '<=', $request->data_fim);
        }
        if ($request->filled('origem')) {
            $query->where('origem', $request->origem);
        }

        $marcacoes = $query->paginate(25);

        return view('ponto.marcacoes', compact('title', 'marcacoes'));
    }

    /**
     * Tela de Espelho de Ponto (Listagem Geral e Filtro por Funcionário)
     */
    public function espelho(Request $request)
    {
        $title = 'Espelho de Ponto dos Colaboradores';
        $dataInicio = $request->input('data_inicio', date('Y-m-01'));
        $dataFim = $request->input('data_fim', date('Y-m-t'));
        $funcionarioId = $request->input('funcionario_id');

        // 1. Consulta das marcações brutas
        $query = PontoMarcacao::with(['funcionario'])
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data_hora_marcacao', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
            ->orderBy('data_hora_marcacao', 'desc');

        if (!empty($funcionarioId) && $funcionarioId !== 'TODOS') {
            $query->where('funcionario_id', $funcionarioId);
        }

        // Se selecionou um colaborador específico, processa os cálculos das datas do período
        if (!empty($funcionarioId) && $funcionarioId !== 'TODOS') {
            $datasComBatidas = PontoMarcacao::where('funcionario_id', $funcionarioId)
                ->whereBetween('data_hora_marcacao', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
                ->selectRaw('DATE(data_hora_marcacao) as data')
                ->groupBy('data')
                ->pluck('data');

            $calculoService = app(\App\Services\Ponto\PontoCalculoService::class);
            foreach ($datasComBatidas as $d) {
                $calculoService->calcularDia((int)$funcionarioId, $d);
            }
        }
        $marcacoes = $query->get();



        // 2. Consulta dos resumos diários consolidados
        $resumoQuery = DB::table('ponto_resumos_diarios')
            ->where('empresa_id', $this->empresa_id)
            ->whereBetween('data', [$dataInicio, $dataFim]);

        if (!empty($funcionarioId) && $funcionarioId !== 'TODOS') {
            $resumoQuery->where('funcionario_id', $funcionarioId);
        }

        $resumos = $resumoQuery->get();

        // Função auxiliar para converter 'HH:mm' em minutos inteiros
        $horaParaMinutos = function ($horaStr) {
            if (empty($horaStr) || !str_contains($horaStr, ':')) return 0;
            $partes = explode(':', $horaStr);
            return ((int) $partes[0] * 60) + (int) $partes[1];
        };

        $totalMinTrabalhados = 0;
        $totalMinExtras = 0;
        $totalMinAtrasos = 0;

        foreach ($resumos as $r) {
            $totalMinTrabalhados += $horaParaMinutos($r->horas_trabalhadas);
            $totalMinExtras      += $horaParaMinutos($r->horas_extras);
            $totalMinAtrasos     += $horaParaMinutos($r->horas_atraso);
        }

        $saldoMinutos = $totalMinExtras - $totalMinAtrasos;

        // Formatação de minutos para string amigável (ex: 160:30h)
        $minutosParaHora = function ($minutos) {
            $sinal = $minutos < 0 ? '-' : '';
            $abs = abs($minutos);
            $h = floor($abs / 60);
            $m = $abs % 60;
            return sprintf('%s%02d:%02dh', $sinal, $h, $m);
        };

        $totais = [
            'trabalhadas' => $minutosParaHora($totalMinTrabalhados),
            'extras'      => $minutosParaHora($totalMinExtras),
            'atrasos'     => $minutosParaHora($totalMinAtrasos),
            'saldo'       => $minutosParaHora($saldoMinutos),
            'saldo_positivo' => $saldoMinutos >= 0,
        ];

        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)
            ->orderBy('nome', 'asc')
            ->get();

        return view('ponto.espelho', compact(
            'title',
            'marcacoes',
            'funcionarios',
            'dataInicio',
            'dataFim',
            'funcionarioId',
            'totais'
        ));
    }

    /**
     * Tratamento/Ajuste manual de marcação
     */
    public function tratar(Request $request)
    {
        try {
            $request->validate([
                'funcionario_id' => 'required',
                'data_hora_marcacao' => 'required',
                'tipo_marcacao' => 'required'
            ]);

            PontoMarcacao::create([
                'empresa_id' => $this->empresa_id,
                'funcionario_id' => $request->funcionario_id,
                'data_hora_marcacao' => Carbon::parse($request->data_hora_marcacao)->format('Y-m-d H:i:s'),
                'tipo_marcacao' => $request->tipo_marcacao,
                'origem' => 'manual_rh',
                'status' => 'ajustada',
                'observacoes' => $request->observacoes ?? 'Ajuste manual pelo RH'
            ]);

            session()->flash('mensagem_sucesso', 'Ponto lançado/ajustado com sucesso!');
            return redirect()->back();
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao ajustar ponto: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Exclusão de Marcação
     */
    public function delete($id)
    {
        try {
            $marcacao = PontoMarcacao::where('empresa_id', $this->empresa_id)->findOrFail($id);
            $marcacao->delete();

            session()->flash('mensagem_sucesso', 'Marcação removida com sucesso!');
            return redirect()->back();
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao remover marcação: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function ajusteManual(Request $request, \App\Services\Ponto\PontoCalculoService $calculoService)
    {
        $request->validate([
            'funcionario_id' => 'required|exists:funcionarios,id',
            'data'           => 'required|date',
            'hora'           => 'required',
            'tipo_marcacao'  => 'required|in:entrada,inicio_almoco,fim_almoco,saida,inicio_hora_extra,fim_hora_extra',
            'justificativa'  => 'required|min:5|max:255',
        ]);

        $dataHora = Carbon::parse($request->data . ' ' . $request->hora);

        // Registra a marcação manual com auditoria
        $usuarioNome = auth()->user()->nome ?? auth()->user()->name ?? 'RH/Gestor';

        PontoMarcacao::create([
            'empresa_id'         => $this->empresa_id,
            'funcionario_id'     => $request->funcionario_id,
            'data_hora_marcacao' => $dataHora,
            'origem'             => 'sistema',
            'tipo_marcacao'      => $request->tipo_marcacao,
            'status'             => 'processada',
            'observacoes'        => "Ajuste Manual por {$usuarioNome}. Motivo: {$request->justificativa}",
        ]);

        // Dispara o recálculo do dia automaticamente
        $calculoService->calcularDia((int) $request->funcionario_id, $request->data);

        return redirect()->back()->with('success', 'Ajuste manual de ponto registrado com sucesso!');
    }

    public function espelhoPdf(Request $request, $id, \App\Services\Ponto\PontoCalculoService $calculoService)
    {
        $funcionario = Funcionario::where('empresa_id', $this->empresa_id)->findOrFail($id);

        $dataInicio = $request->input('data_inicio', date('Y-m-01'));
        $dataFim    = $request->input('data_fim', date('Y-m-t'));

        // 1. Garante que todos os dias do período foram calculados
        $inicio = Carbon::parse($dataInicio);
        $fim    = Carbon::parse($dataFim);

        while ($inicio->lte($fim)) {
            $calculoService->calcularDia($funcionario->id, $inicio->format('Y-m-d'));
            $inicio->addDay();
        }

        // 2. Busca os dados da empresa/filial
        $empresa = DB::table('config_notas')->where('empresa_id', $this->empresa_id)->first();
        if (!$empresa) {
            $empresa = DB::table('filials')->where('id', $this->empresa_id)->first();
        }

        // 3. Monta o calendário completo do mês linha por linha
        $resumos = DB::table('ponto_resumos_diarios')
            ->where('funcionario_id', $funcionario->id)
            ->whereBetween('data', [$dataInicio, $dataFim])
            ->get()
            ->keyBy('data');

        $marcacoesBrutas = PontoMarcacao::where('funcionario_id', $funcionario->id)
            ->whereBetween('data_hora_marcacao', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'])
            ->where('status', '!=', 'desconsiderada')
            ->orderBy('data_hora_marcacao', 'asc')
            ->get()
            ->groupBy(function($m) {
                return Carbon::parse($m->data_hora_marcacao)->format('Y-m-d');
            });

        $gradeMensal = [];
        $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

        $totalMinTrabalhados = 0;
        $totalMinExtras = 0;
        $totalMinAtrasos = 0;

        $cursor = Carbon::parse($dataInicio);
        while ($cursor->lte($fim)) {
            $dStr = $cursor->format('Y-m-d');
            $r = $resumos->get($dStr);
            $batidasDia = $marcacoesBrutas->get($dStr, collect());

            $etapas = [
                'ent1' => null,
                'sai1' => null,
                'ent2' => null,
                'sai2' => null,
                'ent3' => null,
                'sai3' => null,
            ];

            foreach ($batidasDia as $b) {
                $hm = Carbon::parse($b->data_hora_marcacao)->format('H:i');
                if ($b->tipo_marcacao == 'entrada')            $etapas['ent1'] = $hm;
                elseif ($b->tipo_marcacao == 'inicio_almoco')  $etapas['sai1'] = $hm;
                elseif ($b->tipo_marcacao == 'fim_almoco')     $etapas['ent2'] = $hm;
                elseif ($b->tipo_marcacao == 'saida')          $etapas['sai2'] = $hm;
                elseif ($b->tipo_marcacao == 'inicio_hora_extra') $etapas['ent3'] = $hm;
                elseif ($b->tipo_marcacao == 'fim_hora_extra')    $etapas['sai3'] = $hm;
            }

            $horaParaMin = function ($horaStr) {
                if (empty($horaStr) || !str_contains($horaStr, ':')) return 0;
                $p = explode(':', $horaStr);
                return ((int)$p[0] * 60) + (int)$p[1];
            };

            if ($r) {
                $totalMinTrabalhados += $horaParaMin($r->horas_trabalhadas);
                $totalMinExtras      += $horaParaMin($r->horas_extras);
                $totalMinAtrasos     += $horaParaMin($r->horas_atraso);
            }

            $gradeMensal[] = [
                'data'         => $cursor->format('d/m/Y'),
                'dia_semana'   => $diasSemana[$cursor->dayOfWeek],
                'etapas'       => $etapas,
                'trabalhadas'  => $r->horas_trabalhadas ?? '00:00',
                'extras'       => $r->horas_extras ?? '00:00',
                'atrasos'      => $r->horas_atraso ?? '00:00',
                'status'       => $r->status ?? 'normal',
                'eh_fds'       => in_array($cursor->dayOfWeek, [0, 6]),
            ];

            $cursor->addDay();
        }

        $minutosParaHora = function ($minutos) {
            $sinal = $minutos < 0 ? '-' : '';
            $abs = abs($minutos);
            $h = floor($abs / 60);
            $m = $abs % 60;
            return sprintf('%s%02d:%02dh', $sinal, $h, $m);
        };

        $totais = [
            'trabalhadas' => $minutosParaHora($totalMinTrabalhados),
            'extras'      => $minutosParaHora($totalMinExtras),
            'atrasos'     => $minutosParaHora($totalMinAtrasos),
            'saldo'       => $minutosParaHora($totalMinExtras - $totalMinAtrasos),
        ];

        // 4. Renderiza em PDF via DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ponto.espelho_pdf', compact(
            'funcionario',
            'empresa',
            'dataInicio',
            'dataFim',
            'gradeMensal',
            'totais'
        ))->setPaper('a4', 'portrait');

        $nomeArquivo = 'Espelho_Ponto_' . str_replace(' ', '_', $funcionario->nome) . '_' . date('m_Y') . '.pdf';
        return $pdf->stream($nomeArquivo);
    }

    /**
     * Retorna uma mensagem formatada para envio via WhatsApp com o extrato diário.
     */
    public function obterMensagemResumoDiario(int $funcionarioId, string $data): string
    {
        $funcionario = Funcionario::find($funcionarioId);
        $resumo = DB::table('ponto_resumos_diarios')
            ->where('funcionario_id', $funcionarioId)
            ->where('data', $data)
            ->first();

        if (!$resumo) {
            return "✅ *Ponto registrado com sucesso!*\nTenha um ótimo descanso!";
        }

        // Busca o saldo acumulado total no banco de horas
        $saldoAcumuladoMinutos = DB::table('ponto_resumos_diarios')
            ->where('funcionario_id', $funcionarioId)
            ->selectRaw("SUM(
                (CAST(SUBSTRING_INDEX(horas_extras, ':', 1) AS SIGNED) * 60 + CAST(SUBSTRING_INDEX(horas_extras, ':', -1) AS SIGNED)) -
                (CAST(SUBSTRING_INDEX(horas_atraso, ':', 1) AS SIGNED) * 60 + CAST(SUBSTRING_INDEX(horas_atraso, ':', -1) AS SIGNED))
            ) as total_minutos")
            ->value('total_minutos') ?? 0;

        $formataSaldo = function ($minutos) {
            $sinal = $minutos < 0 ? '-' : '+';
            $abs = abs($minutos);
            $h = floor($abs / 60);
            $m = $abs % 60;
            return sprintf('%s%02d:%02dh', $sinal, $h, $m);
        };

        $saldoDiaStr = $resumo->horas_banco;
        $saldoIcone = str_starts_with($saldoDiaStr, '-') ? '🔻' : '⚡';

        $dataFmt = Carbon::parse($data)->format('d/m/Y');
        $primeiroNome = explode(' ', trim($funcionario->nome ?? 'Colaborador'))[0];

        $msg  = "🏁 *EXPEDIENTE FINALIZADO*\n";
        $msg .= "Olá, *{$primeiroNome}*!\n\n";
        $msg .= "📅 *Data:* {$dataFmt}\n";
        $msg .= "⏱️ *Jornada Realizada:* {$resumo->horas_trabalhadas}h\n";
        $msg .= "🎯 *Previsto:* {$resumo->horas_previstas}h\n";

        if ($resumo->horas_extras !== '00:00') {
            $msg .= "🟢 *Horas Extras:* +{$resumo->horas_extras}h\n";
        }
        if ($resumo->horas_atraso !== '00:00') {
            $msg .= "🔴 *Atraso/Saída Antecipada:* -{$resumo->horas_atraso}h\n";
        }

        $msg .= "\n{$saldoIcone} *Saldo do Dia:* {$saldoDiaStr}h\n";
        $msg .= "🏦 *Saldo Geral Acumulado:* " . $formataSaldo($saldoAcumuladoMinutos) . "\n\n";
        $msg .= "Tenha um excelente descanso! 🚀";

        return $msg;
    }
}
