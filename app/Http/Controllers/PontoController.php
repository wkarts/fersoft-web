<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Funcionario;
use App\Models\PontoMarcacao;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PontoController extends Controller
{
    protected $empresa_id;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->empresa_id = request()->empresa_id ?? auth()->user()->empresa_id ?? 1;
            return $next($request);
        });
    }

    public function dashboard()
    {
        $title = 'Painel Geral de RH e Ponto Eletrônico';
        $hoje = Carbon::now()->toDateString();
        $mesAtual = Carbon::now()->month;

        // 1. Apenas funcionários ativos
        $funcionarios = Funcionario::where('empresa_id', $this->empresa_id)
            ->where('status_funcionario', 'Ativo')
            ->orderBy('nome', 'asc')
            ->get();

        $totalFuncionarios = $funcionarios->count();

        // 2. Batidas de hoje
        $marcacoesHoje = PontoMarcacao::where('empresa_id', $this->empresa_id)
            ->whereDate('data_hora_marcacao', $hoje)
            ->where('status', 'processada')
            ->get()
            ->groupBy('funcionario_id');

        // 3. Férias cadastradas ativas
        $feriasHoje = DB::table('funcionario_ferias')
            ->where('empresa_id', $this->empresa_id)
            ->where('data_inicio', '<=', $hoje)
            ->where('data_fim', '>=', $hoje)
            ->where('status', '!=', 'cancelada')
            ->pluck('funcionario_id')
            ->toArray();

        // 4. Coleções do Dashboard
        $trabalhandoAgora = collect();
        $emAlmoco = collect();
        $naoBateramPonto = collect();
        $expedienteFinalizado = collect();
        $emFerias = collect();
        $feriasVencidas = collect();
        $aniversariantes = collect();
        $cnhVencendo = collect();

        foreach ($funcionarios as $f) {
            // Aniversariantes do Mês
            if (!empty($f->data_nascimento)) {
                $nasc = Carbon::parse($f->data_nascimento);
                if ($nasc->month == $mesAtual) {
                    $aniversariantes->push([
                        'nome'  => $f->nome,
                        'dia'   => $nasc->format('d/m'),
                        'cargo' => $f->funcao ?? 'Colaborador',
                    ]);
                }
            }

            // Alerta de CNH Vencida ou a Vencer (próximos 30 dias)
            if (!empty($f->vencimento_cnh)) {
                $dtCnh = Carbon::parse($f->vencimento_cnh);
                $diasParaVencer = Carbon::now()->diffInDays($dtCnh, false);
                if ($diasParaVencer <= 30) {
                    $cnhVencendo->push([
                        'nome'       => $f->nome,
                        'vencimento' => $dtCnh->format('d/m/Y'),
                        'categoria'  => $f->categoria_cnh ?? '---',
                        'vencida'    => $diasParaVencer < 0,
                        'dias'       => abs($diasParaVencer),
                    ]);
                }
            }

            // Em Férias Hoje
            if (in_array($f->id, $feriasHoje)) {
                $dadosFerias = DB::table('funcionario_ferias')
                    ->where('funcionario_id', $f->id)
                    ->where('data_inicio', '<=', $hoje)
                    ->where('data_fim', '>=', $hoje)
                    ->first();

                $emFerias->push([
                    'nome'   => $f->nome,
                    'inicio' => Carbon::parse($dadosFerias->data_inicio)->format('d/m/Y'),
                    'fim'    => Carbon::parse($dadosFerias->data_fim)->format('d/m/Y'),
                ]);
                continue; // Quem está de férias não entra na cobrança de ponto
            }

            // Férias Vencidas (> 1 ano e 11 meses de admissão sem gozo registrado)
            if (!empty($f->data_admissao)) {
                $admissao = Carbon::parse($f->data_admissao);
                $mesesTrabalhados = $admissao->diffInMonths(Carbon::now());

                $jaTirouFeriasRecente = DB::table('funcionario_ferias')
                    ->where('funcionario_id', $f->id)
                    ->where('data_fim', '>=', Carbon::now()->subMonths(11)->toDateString())
                    ->where('status', '!=', 'cancelada')
                    ->exists();

                if ($mesesTrabalhados >= 23 && !$jaTirouFeriasRecente) {
                    $feriasVencidas->push([
                        'nome'     => $f->nome,
                        'admissao' => $admissao->format('d/m/Y'),
                        'tempo'    => floor($mesesTrabalhados / 12) . ' ano(s) e ' . ($mesesTrabalhados % 12) . ' mês(es)',
                    ]);
                }
            }

            // Status da Jornada Hoje
            $batidas = $marcacoesHoje->get($f->id, collect())->pluck('tipo_marcacao')->toArray();

            if (empty($batidas)) {
                $naoBateramPonto->push($f);
            } elseif (in_array('saida', $batidas) || in_array('fim_hora_extra', $batidas)) {
                $expedienteFinalizado->push($f);
            } elseif (in_array('inicio_almoco', $batidas) && !in_array('fim_almoco', $batidas)) {
                $emAlmoco->push($f);
            } else {
                $trabalhandoAgora->push($f);
            }
        }

        $aniversariantes = $aniversariantes->sortBy('dia');

        return view('ponto.dashboard', compact(
            'title',
            'funcionarios', // <--- ADICIONE ESTA LINHA AQUI
            'totalFuncionarios',
            'trabalhandoAgora',
            'emAlmoco',
            'naoBateramPonto',
            'expedienteFinalizado',
            'emFerias',
            'feriasVencidas',
            'aniversariantes',
            'cnhVencendo'
        ));
    }

    public function salvarFerias(Request $request)
    {
        $request->validate([
            'funcionario_id' => 'required',
            'data_inicio'    => 'required|date',
            'data_fim'       => 'required|date|after_or_equal:data_inicio',
        ]);

        $inicio = Carbon::parse($request->data_inicio);
        $fim    = Carbon::parse($request->data_fim);
        $dias   = $inicio->diffInDays($fim) + 1;

        DB::table('funcionario_ferias')->insert([
            'empresa_id'     => $this->empresa_id,
            'funcionario_id' => $request->funcionario_id,
            'data_inicio'    => $request->data_inicio,
            'data_fim'       => $request->data_fim,
            'dias'           => $dias,
            'status'         => ($request->data_inicio <= now()->toDateString() && $request->data_fim >= now()->toDateString()) ? 'em_gozo' : 'agendada',
            'observacoes'    => $request->observacoes,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->back()->with('success', "Férias de {$dias} dias lançadas com sucesso!");
    }

    public function cancelarFerias($id)
    {
        DB::table('funcionario_ferias')
            ->where('id', $id)
            ->where('empresa_id', $this->empresa_id)
            ->update(['status' => 'cancelada', 'updated_at' => now()]);

        return redirect()->back()->with('success', 'Férias canceladas com sucesso!');
    }
}
