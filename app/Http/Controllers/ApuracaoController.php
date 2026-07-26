<?php

namespace App\Http\Controllers;

use App\Models\CategoriaConta;
use App\Utils\ContaEmpresaUtil;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApuracaoController extends BaseController
{
    /**
     * Tipos de itens que compõem o estoque contábil na aplicação master.
     *
     * 00 = Mercadoria para revenda
     * 01 = Matéria-prima
     * 04 = Produto acabado
     */
    private const TIPOS_ITEM_ESTOQUE_CMV = ['00', '01', '04'];

    private const FILIAL_TODAS = 'todos';
    private const FILIAL_MATRIZ = 'matriz';

    protected $util;

    public function __construct(ContaEmpresaUtil $util)
    {
        parent::__construct();
        $this->util = $util;
        $this->redirectPage = '/apuracao';
    }

    protected function rules(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    public function index(Request $request)
    {
        $mes = $this->normalizarMes($request->input('mes'));
        $ano = $this->normalizarAno($request->input('ano'));
        $regime = $this->normalizarRegime($request->input('regime'));
        $filialId = $this->resolverFiltroFilial($request);

        $apuracao = $this->calcularApuracao(
            $mes,
            $ano,
            $regime,
            $filialId,
            $request,
            true
        );

        $nomeMatriz = DB::table('empresas')
            ->where('id', $this->empresa_id)
            ->value('nome_fantasia') ?? 'MATRIZ';

        $filiais = DB::table('filials')
            ->where('empresa_id', $this->empresa_id)
            ->orderBy('id')
            ->get();

        $periodoEncerrado = DB::table('fechamentos_mensais')
            ->where('empresa_id', $this->empresa_id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->where('regime', $regime)
            ->where('status', 'encerrado')
            ->exists();

        return view('apuracao.index', array_merge($apuracao, [
            'mes' => $mes,
            'ano' => $ano,
            'regime' => $regime,
            'filial_id' => $filialId,
            'filiais' => $filiais,
            'nomeMatriz' => $nomeMatriz,
            'periodoEncerrado' => $periodoEncerrado,
            'filialBloqueada' => false,
        ]))->with('title', 'DRE - Demonstração do Resultado do Exercício');
    }

    public function finalizar(Request $request)
    {
        $mes = $this->normalizarMes($request->input('mes'));
        $ano = $this->normalizarAno($request->input('ano'));
        $regime = $this->normalizarRegime($request->input('regime'));
        $filialId = $this->resolverFiltroFilial($request);

        if ($filialId !== self::FILIAL_TODAS) {
            return redirect()->back()->with(
                'mensagem_erro',
                'O fechamento mensal deve ser realizado em "Todas as unidades". O filtro por matriz ou filial permanece disponível apenas para análise.'
            );
        }

        $periodo = Carbon::create($ano, $mes, 1)->startOfMonth();

        if ($periodo->isAfter(now()->startOfMonth())) {
            return redirect()->back()->with('mensagem_erro', 'Não é permitido encerrar um período futuro.');
        }

        if (!$this->usuario_id) {
            return redirect()->back()->with(
                'mensagem_erro',
                'Não foi possível identificar o usuário responsável pelo fechamento.'
            );
        }

        try {
            DB::transaction(function () use ($mes, $ano, $regime, $periodo) {
                // Serializa fechamentos da mesma empresa e evita duas fotografias concorrentes.
                DB::table('empresas')
                    ->where('id', $this->empresa_id)
                    ->lockForUpdate()
                    ->first();

                $fechamentoExistente = DB::table('fechamentos_mensais')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('mes', $mes)
                    ->where('ano', $ano)
                    ->where('regime', $regime)
                    ->lockForUpdate()
                    ->first();

                if ($fechamentoExistente && $fechamentoExistente->status === 'encerrado') {
                    throw new DomainException('Este período já está encerrado para o regime selecionado.');
                }

                $snapshotExiste = DB::table('estoque_mensal_fechamentos')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('mes', $mes)
                    ->where('ano', $ano)
                    ->exists();

                if (!$snapshotExiste) {
                    $existeOutroFechamentoNoPeriodo = DB::table('fechamentos_mensais')
                        ->where('empresa_id', $this->empresa_id)
                        ->where('mes', $mes)
                        ->where('ano', $ano)
                        ->where('status', 'encerrado')
                        ->exists();

                    if (!$periodo->isSameMonth(now()) && !$existeOutroFechamentoNoPeriodo) {
                        throw new DomainException(
                            'Não existe fotografia de estoque para este período histórico. O fechamento foi bloqueado para impedir que o estoque atual seja gravado como saldo de um mês anterior.'
                        );
                    }

                    if ($periodo->isSameMonth(now())) {
                        $this->gravarSnapshotEstoque($mes, $ano);
                    }
                }

                // O resultado é sempre recalculado no servidor, sem aceitar
                // simulações ou valores financeiros enviados pelo navegador.
                $apuracao = $this->calcularApuracao(
                    $mes,
                    $ano,
                    $regime,
                    self::FILIAL_TODAS,
                    null,
                    false
                );

                $dadosFechamento = [
                    'empresa_id' => $this->empresa_id,
                    'mes' => $mes,
                    'ano' => $ano,
                    'regime' => $regime,
                    'lucro_prejuizo_liquido' => $apuracao['resultadoAcumulado'],
                    'status' => 'encerrado',
                    'usuario_id' => $this->usuario_id,
                    'data_fechamento' => now(),
                    'created_at' => $fechamentoExistente->created_at ?? now(),
                ];

                if ($fechamentoExistente) {
                    DB::table('fechamentos_mensais')
                        ->where('id', $fechamentoExistente->id)
                        ->update($dadosFechamento);
                } else {
                    DB::table('fechamentos_mensais')->insert($dadosFechamento);
                }
            }, 3);

            return redirect()->back()->with('mensagem_sucesso', 'Período encerrado com sucesso!');
        } catch (DomainException $e) {
            return redirect()->back()->with('mensagem_erro', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Erro ao encerrar a apuração mensal', [
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id,
                'mes' => $mes,
                'ano' => $ano,
                'regime' => $regime,
                'erro' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return redirect()->back()->with(
                'mensagem_erro',
                'Não foi possível encerrar o período. Consulte os logs da aplicação para obter os detalhes técnicos.'
            );
        }
    }

    /**
     * Mantém a forma atual da master de compor a DRE por categorias financeiras
     * e lançamentos diretos. Os recursos do outro projeto são incorporados sem
     * substituir essa fonte de dados por vendas/CT-e ou por contas a pagar.
     */
    private function calcularApuracao(
        int $mes,
        int $ano,
        string $regime,
        $filialId,
        ?Request $request,
        bool $permitirSimulacao
    ): array {
        [$inicio, $fim] = $this->intervaloPeriodo($mes, $ano);

        $colDataRec = $regime === 'caixa' ? 'data_recebimento' : 'data_vencimento';
        $colDataPag = $regime === 'caixa' ? 'data_pagamento' : 'data_emissao';
        $colValRec = $regime === 'caixa' ? 'valor_recebido' : 'valor_integral';
        $colValPag = $regime === 'caixa' ? 'valor_pago' : 'valor_integral';

        $mesAnterior = $mes === 1 ? 12 : $mes - 1;
        $anoAnterior = $mes === 1 ? $ano - 1 : $ano;

        $simulacaoAtiva = $permitirSimulacao
            && $request
            && $request->hasAny(['simular_est_inicial', 'simular_compras', 'simular_est_final']);

        $queryEstoqueInicial = $this->querySnapshotEstoque($mesAnterior, $anoAnterior, $filialId);
        $estoqueInicial = $queryEstoqueInicial->sum(
            DB::raw('emf.quantidade * emf.valor_unitario_custo')
        );

        $queryCompras = DB::table('item_compras as ic')
            ->join('compras as c', 'c.id', '=', 'ic.compra_id')
            ->join('produtos as p', function ($join) {
                $join->on('p.id', '=', 'ic.produto_id')
                    ->on('p.empresa_id', '=', 'c.empresa_id');
            })
            ->where('c.empresa_id', $this->empresa_id)
            ->whereIn('p.tipo_item', self::TIPOS_ITEM_ESTOQUE_CMV)
            ->where(function (Builder $query) use ($inicio, $fim) {
                // Mantém a mesma data efetiva usada pela movimentação de estoque
                // da master: retroativa, emissão e, por último, criação.
                $query->whereBetween('c.data_retroativa', [$inicio, $fim])
                    ->orWhere(function (Builder $fallbackEmissao) use ($inicio, $fim) {
                        $fallbackEmissao->whereNull('c.data_retroativa')
                            ->whereBetween('c.data_emissao', [$inicio, $fim]);
                    })
                    ->orWhere(function (Builder $fallbackCriacao) use ($inicio, $fim) {
                        $fallbackCriacao->whereNull('c.data_retroativa')
                            ->whereNull('c.data_emissao')
                            ->whereBetween('c.created_at', [$inicio, $fim]);
                    });
            });

        $this->aplicarFiltroFilial($queryCompras, $filialId, 'c.filial_id');

        $comprasBrutas = (float) $queryCompras->sum(
            DB::raw('ic.quantidade * ic.valor_unitario')
        );

        $queryDevolucoesCompra = DB::table('devolucaos')
            ->where('empresa_id', $this->empresa_id)
            ->where('tipo', 1)
            ->where('estado', 1)
            ->whereBetween('data_registro', [$inicio, $fim]);

        $this->aplicarFiltroFilial($queryDevolucoesCompra, $filialId, 'filial_id');

        $devolucoesCompra = (float) $queryDevolucoesCompra->sum('valor_devolvido');
        $comprasDoMes = $comprasBrutas - $devolucoesCompra;

        $queryEstoqueFinal = $this->querySnapshotEstoque($mes, $ano, $filialId);
        $snapshotFinalExiste = (clone $queryEstoqueFinal)->exists();
        $estoqueFinal = (float) $queryEstoqueFinal->sum(
            DB::raw('emf.quantidade * emf.valor_unitario_custo')
        );

        $avisoEstoque = null;
        $periodoSelecionado = Carbon::create($ano, $mes, 1)->startOfMonth();

        if (!$snapshotFinalExiste) {
            if ($periodoSelecionado->isSameMonth(now())) {
                $queryEstoqueAtual = DB::table('estoques as e')
                    ->join('produtos as p', function ($join) {
                        $join->on('p.id', '=', 'e.produto_id')
                            ->on('p.empresa_id', '=', 'e.empresa_id');
                    })
                    ->where('e.empresa_id', $this->empresa_id)
                    ->whereIn('p.tipo_item', self::TIPOS_ITEM_ESTOQUE_CMV);

                $this->aplicarFiltroFilial($queryEstoqueAtual, $filialId, 'e.filial_id');

                $estoqueFinal = (float) $queryEstoqueAtual->sum(
                    DB::raw('e.quantidade * e.valor_compra')
                );
            } else {
                // Um fechamento já existente sem linhas de snapshot representa
                // legitimamente um estoque zero. Sem fechamento, a ausência da
                // fotografia permanece uma inconsistência histórica.
                $snapshotZeroConfirmado = DB::table('fechamentos_mensais')
                    ->where('empresa_id', $this->empresa_id)
                    ->where('mes', $mes)
                    ->where('ano', $ano)
                    ->where('status', 'encerrado')
                    ->exists();

                $estoqueFinal = 0.0;
                $snapshotFinalExiste = $snapshotZeroConfirmado;

                if (!$snapshotZeroConfirmado) {
                    $avisoEstoque = 'O período histórico não possui fotografia de estoque. O estoque atual não foi usado como saldo retroativo.';
                }
            }
        }

        if ($simulacaoAtiva) {
            if ($request->filled('simular_est_inicial')) {
                $estoqueInicial = $this->normalizarDecimal($request->input('simular_est_inicial'));
            }

            if ($request->filled('simular_compras')) {
                $comprasDoMes = $this->normalizarDecimal($request->input('simular_compras'));
            }

            if ($request->filled('simular_est_final')) {
                $estoqueFinal = $this->normalizarDecimal($request->input('simular_est_final'));
            }
        }

        $cmvReal = ($estoqueInicial + $comprasDoMes) - $estoqueFinal;

        $receitasRes = DB::table('conta_recebers as r')
            ->join('categoria_contas as c', 'r.categoria_id', '=', 'c.id')
            ->select(
                'c.dre_grupo',
                'c.nome as categoria_nome',
                DB::raw("SUM(COALESCE(r.{$colValRec}, 0)) as total")
            )
            ->where('r.empresa_id', $this->empresa_id)
            ->where('c.empresa_id', $this->empresa_id)
            ->where('c.incluir_resultado', 1)
            ->whereBetween("r.{$colDataRec}", [$inicio, $fim])
            ->groupBy('c.dre_grupo', 'c.nome');

        $this->aplicarFiltroFilial($receitasRes, $filialId, 'r.filial_id');

        $despesasRes = DB::table('conta_pagars as p')
            ->join('categoria_contas as c', 'p.categoria_id', '=', 'c.id')
            ->select(
                'c.dre_grupo',
                'c.nome as categoria_nome',
                DB::raw("SUM(COALESCE(p.{$colValPag}, 0)) as total")
            )
            ->where('p.empresa_id', $this->empresa_id)
            ->where('c.empresa_id', $this->empresa_id)
            ->where('c.incluir_resultado', 1)
            ->whereBetween("p.{$colDataPag}", [$inicio, $fim])
            ->groupBy('c.dre_grupo', 'c.nome');

        $this->aplicarFiltroFilial($despesasRes, $filialId, 'p.filial_id');

        $lancamentosDiretos = DB::table('item_conta_empresas as i')
            ->join('categoria_contas as c', 'i.categoria_id', '=', 'c.id')
            ->join('conta_empresas as ce', 'ce.id', '=', 'i.conta_id')
            ->select(
                'c.dre_grupo',
                'c.nome as categoria_nome',
                'i.tipo',
                DB::raw('SUM(COALESCE(i.valor, 0)) as total')
            )
            ->where('i.empresa_id', $this->empresa_id)
            ->where('c.empresa_id', $this->empresa_id)
            ->where('ce.empresa_id', $this->empresa_id)
            ->where('c.incluir_resultado', 1)
            ->whereNull('i.conta_receber_id')
            ->whereNull('i.conta_pagar_id')
            ->whereBetween('i.data_pagamento', [$inicio, $fim])
            ->groupBy('c.dre_grupo', 'c.nome', 'i.tipo');

        $this->aplicarFiltroFilial($lancamentosDiretos, $filialId, 'ce.filial_id');

        $dados = [];
        $detalhes = [];

        foreach (CategoriaConta::gruposDRE() as $grupo => $descricao) {
            $dados[$grupo] = 0.0;
            $detalhes[$grupo] = [];
        }

        foreach ($receitasRes->get() as $receita) {
            $this->acumularGrupo(
                $dados,
                $detalhes,
                $receita->dre_grupo,
                $receita->categoria_nome,
                (float) $receita->total
            );
        }

        foreach ($despesasRes->get() as $despesa) {
            $this->acumularGrupo(
                $dados,
                $detalhes,
                $despesa->dre_grupo,
                $despesa->categoria_nome,
                -abs((float) $despesa->total)
            );
        }

        foreach ($lancamentosDiretos->get() as $lancamento) {
            $valor = $lancamento->tipo === 'entrada'
                ? abs((float) $lancamento->total)
                : -abs((float) $lancamento->total);

            $this->acumularGrupo(
                $dados,
                $detalhes,
                $lancamento->dre_grupo,
                $lancamento->categoria_nome,
                $valor
            );
        }

        // Recurso trazido do projeto externo: usa a devolução fiscal aprovada
        // apenas quando não existe valor já classificado no grupo DRE.
        if (abs($dados['devolucao'] ?? 0) < 0.00001) {
            $queryDevolucoesVenda = DB::table('devolucaos')
                ->where('empresa_id', $this->empresa_id)
                ->where('tipo', 0)
                ->where('estado', 1)
                ->whereBetween('data_registro', [$inicio, $fim]);

            $this->aplicarFiltroFilial($queryDevolucoesVenda, $filialId, 'filial_id');

            $devolucoesVenda = (float) $queryDevolucoesVenda->sum('valor_devolvido');

            if ($devolucoesVenda > 0) {
                $this->acumularGrupo(
                    $dados,
                    $detalhes,
                    'devolucao',
                    'Devoluções fiscais de vendas aprovadas',
                    -$devolucoesVenda
                );
            }
        }

        $dados['cmv'] = -$cmvReal;

        $saldoAnterior = 0.0;
        if ($filialId === self::FILIAL_TODAS) {
            $saldoAnterior = (float) (DB::table('fechamentos_mensais')
                ->where('empresa_id', $this->empresa_id)
                ->where('mes', $mesAnterior)
                ->where('ano', $anoAnterior)
                ->where('regime', $regime)
                ->where('status', 'encerrado')
                ->value('lucro_prejuizo_liquido') ?? 0);
        }

        $receitaBruta = (float) ($dados['receita_bruta'] ?? 0);
        $deducoesVenda = abs((float) ($dados['deducao_venda'] ?? 0));
        $devolucoesVenda = abs((float) ($dados['devolucao'] ?? 0));
        $receitaLiquida = $receitaBruta - $deducoesVenda - $devolucoesVenda;
        $lucroBruto = $receitaLiquida - $cmvReal;

        $despesasOperacionais =
            abs((float) ($dados['pessoal'] ?? 0))
            + abs((float) ($dados['administrativa'] ?? 0))
            + abs((float) ($dados['operacional'] ?? 0))
            + abs((float) ($dados['tributaria'] ?? 0));

        $outrosResultados =
            (float) ($dados['financeira'] ?? 0)
            + (float) ($dados['nao_operacional'] ?? 0);

        $resultadoPeriodo = $lucroBruto - $despesasOperacionais + $outrosResultados;
        $resultadoAcumulado = $resultadoPeriodo + $saldoAnterior;

        return [
            'dados' => $dados,
            'detalhes' => $detalhes,
            'saldoAnterior' => $saldoAnterior,
            'cmvReal' => $cmvReal,
            'estoqueInicial' => $estoqueInicial,
            'comprasBrutas' => $comprasBrutas,
            'devolucoesCompra' => $devolucoesCompra,
            'comprasDoMes' => $comprasDoMes,
            'estoqueFinal' => $estoqueFinal,
            'snapshotFinalExiste' => $snapshotFinalExiste,
            'simulacaoAtiva' => $simulacaoAtiva,
            'avisoEstoque' => $avisoEstoque,
            'receitaBruta' => $receitaBruta,
            'deducoesVenda' => $deducoesVenda,
            'devolucoesVenda' => $devolucoesVenda,
            'receitaLiquida' => $receitaLiquida,
            'lucroBruto' => $lucroBruto,
            'despesasOperacionais' => $despesasOperacionais,
            'outrosResultados' => $outrosResultados,
            'resultadoPeriodo' => $resultadoPeriodo,
            'resultadoAcumulado' => $resultadoAcumulado,
        ];
    }

    private function querySnapshotEstoque(int $mes, int $ano, $filialId): Builder
    {
        $query = DB::table('estoque_mensal_fechamentos as emf')
            ->join('produtos as p', function ($join) {
                $join->on('p.id', '=', 'emf.produto_id')
                    ->on('p.empresa_id', '=', 'emf.empresa_id');
            })
            ->where('emf.empresa_id', $this->empresa_id)
            ->where('emf.mes', $mes)
            ->where('emf.ano', $ano)
            ->whereIn('p.tipo_item', self::TIPOS_ITEM_ESTOQUE_CMV);

        $this->aplicarFiltroFilial($query, $filialId, 'emf.filial_id');

        return $query;
    }

    private function gravarSnapshotEstoque(int $mes, int $ano): void
    {
        DB::table('estoque_mensal_fechamentos')
            ->where('empresa_id', $this->empresa_id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->delete();

        DB::table('estoques as e')
            ->join('produtos as p', function ($join) {
                $join->on('p.id', '=', 'e.produto_id')
                    ->on('p.empresa_id', '=', 'e.empresa_id');
            })
            ->where('e.empresa_id', $this->empresa_id)
            ->whereIn('p.tipo_item', self::TIPOS_ITEM_ESTOQUE_CMV)
            ->select(
                'e.id',
                'e.produto_id',
                'e.filial_id',
                'e.quantidade',
                'e.valor_compra'
            )
            ->orderBy('e.id')
            ->chunkById(500, function ($itens) use ($mes, $ano) {
                $agora = now();
                $linhas = [];

                foreach ($itens as $item) {
                    $linhas[] = [
                        'empresa_id' => $this->empresa_id,
                        'usuario_id' => $this->usuario_id,
                        'filial_id' => $item->filial_id,
                        'produto_id' => $item->produto_id,
                        'quantidade' => $item->quantidade,
                        'valor_unitario_custo' => $item->valor_compra,
                        'mes' => $mes,
                        'ano' => $ano,
                        'created_at' => $agora,
                        'updated_at' => $agora,
                    ];
                }

                if ($linhas) {
                    DB::table('estoque_mensal_fechamentos')->insert($linhas);
                }
            }, 'e.id', 'id');
    }

    private function acumularGrupo(
        array &$dados,
        array &$detalhes,
        ?string $grupo,
        string $categoria,
        float $valor
    ): void {
        $grupo = trim((string) $grupo);

        if ($grupo === '' || !array_key_exists($grupo, $dados)) {
            return;
        }

        $dados[$grupo] += $valor;

        if (!isset($detalhes[$grupo][$categoria])) {
            $detalhes[$grupo][$categoria] = (object) [
                'categoria_nome' => $categoria,
                'total' => 0.0,
            ];
        }

        $detalhes[$grupo][$categoria]->total += $valor;
    }

    private function aplicarFiltroFilial(Builder $query, $filialId, string $coluna): Builder
    {
        if ($filialId === self::FILIAL_MATRIZ) {
            return $query->whereNull($coluna);
        }

        if ($filialId !== self::FILIAL_TODAS) {
            return $query->where($coluna, (int) $filialId);
        }

        return $query;
    }

    private function resolverFiltroFilial(Request $request)
    {
        // Preserva o comportamento da master: o local padrão do usuário não é
        // tratado como bloqueio de acesso. O filtro solicitado é validado para
        // garantir que a filial pertença à empresa atual.
        $filial = $request->input('filial_id', self::FILIAL_TODAS);

        if (in_array($filial, [self::FILIAL_TODAS, self::FILIAL_MATRIZ], true)) {
            return $filial;
        }

        $filialId = filter_var($filial, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if (!$filialId) {
            return self::FILIAL_TODAS;
        }

        $pertenceEmpresa = DB::table('filials')
            ->where('id', $filialId)
            ->where('empresa_id', $this->empresa_id)
            ->exists();

        return $pertenceEmpresa ? (string) $filialId : self::FILIAL_TODAS;
    }

    private function normalizarMes($mes): int
    {
        $mes = filter_var($mes, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 12],
        ]);

        return $mes ?: (int) date('m');
    }

    private function normalizarAno($ano): int
    {
        $anoAtual = (int) date('Y');
        $ano = filter_var($ano, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 2000, 'max_range' => $anoAtual + 2],
        ]);

        return $ano ?: $anoAtual;
    }

    private function normalizarRegime($regime): string
    {
        return in_array($regime, ['competencia', 'caixa'], true)
            ? $regime
            : 'competencia';
    }

    private function intervaloPeriodo(int $mes, int $ano): array
    {
        $inicio = Carbon::create($ano, $mes, 1)->startOfDay();
        $fim = (clone $inicio)->endOfMonth()->endOfDay();

        return [$inicio, $fim];
    }

    private function normalizarDecimal($valor): float
    {
        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $valor = trim((string) $valor);

        if ($valor === '') {
            return 0.0;
        }

        if (str_contains($valor, ',') && str_contains($valor, '.')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } elseif (str_contains($valor, ',')) {
            $valor = str_replace(',', '.', $valor);
        }

        return is_numeric($valor) ? (float) $valor : 0.0;
    }
}
