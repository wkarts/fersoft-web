<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioEstoqueController extends BaseController
{
    // --- PROPRIEDADES EXIGIDAS PELO BASECONTROLLER ---
    protected $redirectPage = '/estoque/saldo-real';
    protected $formTitle    = 'Relatório de Estoque';
    // -------------------------------------------------

    // --- MÉTODOS OBRIGATÓRIOS DO BASECONTROLLER ---
    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }
    // ----------------------------------------------

    public function relatorioSaldoReal(Request $request)
    {
        $empresaId = $this->getEmpresaIdSessao();

        if (!$empresaId) {
            return "Erro: Empresa não identificada na sessão.";
        }

        $dataInicial = $this->parseDataInicio(
            $request->input('data_inicial'),
            Carbon::now()->startOfMonth()
        );

        $dataFinal = $this->parseDataFim(
            $request->input('data_final'),
            Carbon::now()->endOfDay()
        );

        $movimentacoes = $this->movimentacoesUnificadasEstoque($empresaId);

        $saldos = DB::query()
            ->fromSub($movimentacoes, 'mov')
            ->select('produto_id', 'filial_id')
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN data < ?
                        THEN CASE WHEN tipo = 'entrada' THEN qtd ELSE -qtd END
                        ELSE 0
                    END
                ) as saldo_inicial",
                [$dataInicial]
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN data BETWEEN ? AND ? AND tipo = 'entrada'
                        THEN qtd
                        ELSE 0
                    END
                ) as total_entradas",
                [$dataInicial, $dataFinal]
            )
            ->selectRaw(
                "SUM(
                    CASE
                        WHEN data BETWEEN ? AND ? AND tipo = 'saida'
                        THEN qtd
                        ELSE 0
                    END
                ) as total_saidas",
                [$dataInicial, $dataFinal]
            )
            ->groupBy('produto_id', 'filial_id');

        $query = DB::table('produtos')
            ->joinSub($saldos, 'saldos', function ($join) {
                $join->on('produtos.id', '=', 'saldos.produto_id');
            })
            ->leftJoin('filials', 'filials.id', '=', 'saldos.filial_id')
            ->where('produtos.empresa_id', $empresaId)
            ->where('produtos.gerenciar_estoque', 1)
            ->select(
                'produtos.id',
                'produtos.referencia',
                'saldos.filial_id'
            )
            ->selectRaw("
                CONCAT(
                    produtos.nome,
                    ' - ',
                    CASE
                        WHEN saldos.filial_id = -1 THEN 'MATRIZ'
                        ELSE UPPER(COALESCE(filials.descricao, 'SEM FILIAL'))
                    END
                ) as descricao
            ")
            ->selectRaw('COALESCE(produtos.valor_compra, 0) as custo_medio')
            ->selectRaw('COALESCE(saldos.saldo_inicial, 0) as saldo_inicial')
            ->selectRaw('COALESCE(saldos.total_entradas, 0) as entradas')
            ->selectRaw('COALESCE(saldos.total_saidas, 0) as saidas')
            ->selectRaw('
                (
                    COALESCE(saldos.saldo_inicial, 0)
                    + COALESCE(saldos.total_entradas, 0)
                    - COALESCE(saldos.total_saidas, 0)
                ) as saldo_final
            ')
            ->selectRaw('
                (
                    COALESCE(saldos.saldo_inicial, 0)
                    + COALESCE(saldos.total_entradas, 0)
                    - COALESCE(saldos.total_saidas, 0)
                ) * COALESCE(produtos.valor_compra, 0) as valor_total
            ');

        if ($request->filled('filial_id')) {
            $query->where('saldos.filial_id', (int) $request->input('filial_id'));
        }

        if ($request->filled('categoria_id')) {
            $query->where('produtos.categoria_id', (int) $request->input('categoria_id'));
        }

        if ($request->filled('sub_categoria_id')) {
            $query->where('produtos.sub_categoria_id', (int) $request->input('sub_categoria_id'));
        }

        if ($request->filled('produto_id')) {
            $query->where('produtos.id', (int) $request->input('produto_id'));
        }

        $resultados = $query
            ->orderBy('produtos.nome')
            ->paginate(50)
            ->appends($request->query());

        $filiais = DB::table('filials')
            ->where('empresa_id', $empresaId)
            ->select('id', 'descricao as nome')
            ->orderBy('descricao')
            ->get();

        $categorias = DB::table('categorias')
            ->where('empresa_id', $empresaId)
            ->select('id', 'nome')
            ->orderBy('nome')
            ->get();

        $subCategorias = DB::table('sub_categorias')
            ->when($request->filled('categoria_id'), function ($query) use ($request) {
                return $query->where('categoria_id', (int) $request->input('categoria_id'));
            })
            ->select('id', 'nome', 'categoria_id')
            ->orderBy('nome')
            ->get();

        $produtos_filtro = DB::table('produtos')
            ->where('empresa_id', $empresaId)
            ->where('gerenciar_estoque', 1)
            ->select('id', 'nome', 'referencia')
            ->orderBy('nome')
            ->get();

        $title = $titulo = 'Saldo Real de Estoque';

        return view('relatorios.estoque', compact(
            'title',
            'titulo',
            'resultados',
            'dataInicial',
            'dataFinal',
            'filiais',
            'categorias',
            'subCategorias',
            'produtos_filtro'
        ));
    }

    public function extratoMovimentacao(Request $request, $produto_id)
    {
        $empresaId = $this->getEmpresaIdSessao();

        if (!$empresaId) {
            return "Erro: Empresa não identificada na sessão.";
        }

        $produto = DB::table('produtos')
            ->where('empresa_id', $empresaId)
            ->where('id', (int) $produto_id)
            ->first();

        if (!$produto) {
            abort(404, 'Produto não encontrado.');
        }

        $dataInicial = $this->parseDataInicio(
            $request->input('data_inicial'),
            Carbon::now()->startOfMonth()
        );

        $dataFinal = $this->parseDataFim(
            $request->input('data_final'),
            Carbon::now()->endOfDay()
        );

        $filialId = $request->filled('filial_id') ? (int) $request->input('filial_id') : null;

        $comprasQuery = DB::table('item_compras')
            ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
            ->leftJoin('fornecedors', 'fornecedors.id', '=', 'compras.fornecedor_id')
            ->where('compras.empresa_id', $empresaId)
            ->where('item_compras.produto_id', (int) $produto_id)
            ->whereIn('compras.estado', ['APROVADO', 'IMPORTADO']);

        $vendasQuery = DB::table('item_vendas')
            ->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
            ->leftJoin('clientes', 'clientes.id', '=', 'vendas.cliente_id')
            ->where('vendas.empresa_id', $empresaId)
            ->where('item_vendas.produto_id', (int) $produto_id)
            ->where('vendas.estado', 'APROVADO');

        $vendasCaixaQuery = DB::table('item_venda_caixas')
            ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
            ->leftJoin('clientes', 'clientes.id', '=', 'venda_caixas.cliente_id')
            ->where('venda_caixas.empresa_id', $empresaId)
            ->where('item_venda_caixas.produto_id', (int) $produto_id)
            ->where('venda_caixas.estado', 'APROVADO');

        $alteracoesQuery = DB::table('alteracao_estoques')
            ->where('empresa_id', $empresaId)
            ->where('produto_id', (int) $produto_id);

        $transfSaidaQuery = DB::table('item_transferencias')
            ->join('transferencias', 'transferencias.id', '=', 'item_transferencias.transferencia_id')
            ->leftJoin('filials', 'filials.id', '=', 'transferencias.filial_entrada_id')
            ->where('transferencias.empresa_id', $empresaId)
            ->where('transferencias.estado', 'aprovado')
            ->where('item_transferencias.produto_id', (int) $produto_id);

        $transfEntradaQuery = DB::table('item_transferencias')
            ->join('transferencias', 'transferencias.id', '=', 'item_transferencias.transferencia_id')
            ->leftJoin('filials', 'filials.id', '=', 'transferencias.filial_saida_id')
            ->where('transferencias.empresa_id', $empresaId)
            ->where('transferencias.estado', 'aprovado')
            ->where('item_transferencias.produto_id', (int) $produto_id);

        $devolucoesQuery = DB::table('item_devolucaos')
            ->join('devolucaos', 'devolucaos.id', '=', 'item_devolucaos.devolucao_id')
            ->leftJoin('fornecedors', 'fornecedors.id', '=', 'devolucaos.fornecedor_id')
            ->where('devolucaos.empresa_id', $empresaId)
            ->where('devolucaos.estado', 1)
            ->where('item_devolucaos.cod', $produto->referencia)
            ->where('item_devolucaos.nome', $produto->nome);

        if ($request->filled('filial_id')) {
            if ($filialId === -1) {
                $comprasQuery->whereNull('compras.filial_id');
                $vendasQuery->whereNull('vendas.filial_id');
                $vendasCaixaQuery->whereNull('venda_caixas.filial_id');

                $alteracoesQuery->where(function ($query) {
                    $query->whereNull('filial_id')
                        ->orWhere('filial_id', -1);
                });

                $transfSaidaQuery->whereNull('transferencias.filial_saida_id');
                $transfEntradaQuery->whereNull('transferencias.filial_entrada_id');
                $devolucoesQuery->whereNull('devolucaos.filial_id');
            } else {
                $comprasQuery->where('compras.filial_id', $filialId);
                $vendasQuery->where('vendas.filial_id', $filialId);
                $vendasCaixaQuery->where('venda_caixas.filial_id', $filialId);
                $alteracoesQuery->where('filial_id', $filialId);
                $transfSaidaQuery->where('transferencias.filial_saida_id', $filialId);
                $transfEntradaQuery->where('transferencias.filial_entrada_id', $filialId);
                $devolucoesQuery->where('devolucaos.filial_id', $filialId);
            }
        }

        $saldoInicial = 0;

        $saldoInicial += (clone $comprasQuery)
            ->where($this->rawDataCompras(), '<', $dataInicial)
            ->sum('item_compras.quantidade');

        $saldoInicial -= (clone $vendasQuery)
            ->where($this->rawDataVendas(), '<', $dataInicial)
            ->sum('item_vendas.quantidade');

        $saldoInicial -= (clone $vendasCaixaQuery)
            ->where($this->rawDataVendasCaixa(), '<', $dataInicial)
            ->sum('item_venda_caixas.quantidade');

        $saldoInicial += (clone $alteracoesQuery)
            ->where('created_at', '<', $dataInicial)
            ->where('tipo', 'incremento')
            ->sum('quantidade');

        $saldoInicial -= (clone $alteracoesQuery)
            ->where('created_at', '<', $dataInicial)
            ->where('tipo', '!=', 'incremento')
            ->sum('quantidade');

        $saldoInicial -= (clone $transfSaidaQuery)
            ->where($this->rawDataTransferencias(), '<', $dataInicial)
            ->sum('item_transferencias.quantidade');

        $saldoInicial += (clone $transfEntradaQuery)
            ->where($this->rawDataTransferencias(), '<', $dataInicial)
            ->sum('item_transferencias.quantidade');

        $saldoInicial += (clone $devolucoesQuery)
            ->where('devolucaos.data_registro', '<', $dataInicial)
            ->where('devolucaos.tipo', 0)
            ->sum('item_devolucaos.quantidade');

        $saldoInicial -= (clone $devolucoesQuery)
            ->where('devolucaos.data_registro', '<', $dataInicial)
            ->where('devolucaos.tipo', 1)
            ->sum('item_devolucaos.quantidade');

        $compras = $comprasQuery
            ->whereBetween($this->rawDataCompras(), [$dataInicial, $dataFinal])
            ->select(
                'item_compras.quantidade as qtd',
                DB::raw('COALESCE(compras.data_emissao, compras.created_at) as data'),
                DB::raw("'Entrada (Compra)' as operacao"),
                DB::raw("'entrada' as tipo"),
                'compras.id as numero_nota',
                DB::raw("COALESCE(fornecedors.razao_social, 'Não Informado') as pessoa")
            );

        $vendas = $vendasQuery
            ->whereBetween($this->rawDataVendas(), [$dataInicial, $dataFinal])
            ->select(
                'item_vendas.quantidade as qtd',
                DB::raw('COALESCE(vendas.data_emissao, vendas.data_registro, vendas.created_at) as data'),
                DB::raw("'Saída (Venda)' as operacao"),
                DB::raw("'saida' as tipo"),
                'vendas.NfNumero as numero_nota',
                DB::raw("COALESCE(clientes.razao_social, 'Não Informado') as pessoa")
            );

        $vendasCaixa = $vendasCaixaQuery
            ->whereBetween($this->rawDataVendasCaixa(), [$dataInicial, $dataFinal])
            ->select(
                'item_venda_caixas.quantidade as qtd',
                DB::raw('COALESCE(venda_caixas.data_emissao, venda_caixas.data_registro, venda_caixas.created_at) as data'),
                DB::raw("'Saída (PDV)' as operacao"),
                DB::raw("'saida' as tipo"),
                'venda_caixas.id as numero_nota',
                DB::raw("COALESCE(clientes.razao_social, 'Consumidor Final') as pessoa")
            );

        $alteracoes = $alteracoesQuery
            ->whereBetween('created_at', [$dataInicial, $dataFinal])
            ->select(
                'quantidade as qtd',
                'created_at as data',
                DB::raw("CONCAT('Ajuste: ', COALESCE(observacao, motivo, '')) as operacao"),
                DB::raw("CASE WHEN tipo = 'incremento' THEN 'entrada' ELSE 'saida' END as tipo"),
                DB::raw("'-' as numero_nota"),
                DB::raw("'-' as pessoa")
            );

        $transfSaida = $transfSaidaQuery
            ->whereBetween($this->rawDataTransferencias(), [$dataInicial, $dataFinal])
            ->select(
                'item_transferencias.quantidade as qtd',
                DB::raw('COALESCE(transferencias.data_emissao, transferencias.created_at) as data'),
                DB::raw("CONCAT('Transferência (Saída para ', COALESCE(filials.descricao, 'Matriz'), ')') as operacao"),
                DB::raw("'saida' as tipo"),
                'transferencias.id as numero_nota',
                DB::raw("'-' as pessoa")
            );

        $transfEntrada = $transfEntradaQuery
            ->whereBetween($this->rawDataTransferencias(), [$dataInicial, $dataFinal])
            ->select(
                'item_transferencias.quantidade as qtd',
                DB::raw('COALESCE(transferencias.data_emissao, transferencias.created_at) as data'),
                DB::raw("CONCAT('Transferência (Entrada de ', COALESCE(filials.descricao, 'Matriz'), ')') as operacao"),
                DB::raw("'entrada' as tipo"),
                'transferencias.id as numero_nota',
                DB::raw("'-' as pessoa")
            );

        $devolucoes = $devolucoesQuery
            ->whereBetween('devolucaos.data_registro', [$dataInicial, $dataFinal])
            ->select(
                'item_devolucaos.quantidade as qtd',
                'devolucaos.data_registro as data',
                DB::raw("CASE WHEN devolucaos.tipo = 0 THEN 'Devolução (Entrada)' ELSE 'Devolução (Saída)' END as operacao"),
                DB::raw("CASE WHEN devolucaos.tipo = 0 THEN 'entrada' ELSE 'saida' END as tipo"),
                'devolucaos.numero_gerado as numero_nota',
                DB::raw("COALESCE(fornecedors.razao_social, 'Não Informado') as pessoa")
            );

        $movimentacoesBase = $compras
            ->unionAll($vendas)
            ->unionAll($vendasCaixa)
            ->unionAll($alteracoes)
            ->unionAll($transfSaida)
            ->unionAll($transfEntrada)
            ->unionAll($devolucoes);

        $movimentacoes = DB::query()
            ->fromSub($movimentacoesBase, 'mov')
            ->orderBy('data', 'asc')
            ->get();

        $title = 'Extrato de Movimentação - ' . $produto->nome;
        $titulo = 'Extrato de Movimentação - ' . $produto->nome;

        return view('relatorios.extrato_estoque', compact(
            'title',
            'titulo',
            'produto',
            'movimentacoes',
            'saldoInicial',
            'dataInicial',
            'dataFinal'
        ));
    }

    private function getEmpresaIdSessao(): ?int
    {
        $userLogged = session('user_logged');
        $empresaId = $userLogged['empresa'] ?? null;

        return $empresaId ? (int) $empresaId : null;
    }

    private function movimentacoesUnificadasEstoque(int $empresaId): Builder
    {
        $compras = DB::table('item_compras')
            ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
            ->where('compras.empresa_id', $empresaId)
            ->whereIn('compras.estado', ['APROVADO', 'IMPORTADO'])
            ->select(
                'item_compras.produto_id',
                DB::raw('COALESCE(compras.filial_id, -1) as filial_id'),
                DB::raw('item_compras.quantidade as qtd'),
                DB::raw('COALESCE(compras.data_emissao, compras.created_at) as data'),
                DB::raw("'entrada' as tipo")
            );

        $vendas = DB::table('item_vendas')
            ->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
            ->where('vendas.empresa_id', $empresaId)
            ->where('vendas.estado', 'APROVADO')
            ->select(
                'item_vendas.produto_id',
                DB::raw('COALESCE(vendas.filial_id, -1) as filial_id'),
                DB::raw('item_vendas.quantidade as qtd'),
                DB::raw('COALESCE(vendas.data_emissao, vendas.data_registro, vendas.created_at) as data'),
                DB::raw("'saida' as tipo")
            );

        $vendasCaixa = DB::table('item_venda_caixas')
            ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
            ->where('venda_caixas.empresa_id', $empresaId)
            ->where('venda_caixas.estado', 'APROVADO')
            ->select(
                'item_venda_caixas.produto_id',
                DB::raw('COALESCE(venda_caixas.filial_id, -1) as filial_id'),
                DB::raw('item_venda_caixas.quantidade as qtd'),
                DB::raw('COALESCE(venda_caixas.data_emissao, venda_caixas.data_registro, venda_caixas.created_at) as data'),
                DB::raw("'saida' as tipo")
            );

        $alteracoes = DB::table('alteracao_estoques')
            ->where('empresa_id', $empresaId)
            ->whereNotNull('produto_id')
            ->select(
                'produto_id',
                DB::raw('COALESCE(filial_id, -1) as filial_id'),
                DB::raw('quantidade as qtd'),
                DB::raw('created_at as data'),
                DB::raw("CASE WHEN tipo = 'incremento' THEN 'entrada' ELSE 'saida' END as tipo")
            );

        $transferenciasSaida = DB::table('item_transferencias')
            ->join('transferencias', 'transferencias.id', '=', 'item_transferencias.transferencia_id')
            ->where('transferencias.empresa_id', $empresaId)
            ->where('transferencias.estado', 'aprovado')
            ->select(
                'item_transferencias.produto_id',
                DB::raw('COALESCE(transferencias.filial_saida_id, -1) as filial_id'),
                DB::raw('item_transferencias.quantidade as qtd'),
                DB::raw('COALESCE(transferencias.data_emissao, transferencias.created_at) as data'),
                DB::raw("'saida' as tipo")
            );

        $transferenciasEntrada = DB::table('item_transferencias')
            ->join('transferencias', 'transferencias.id', '=', 'item_transferencias.transferencia_id')
            ->where('transferencias.empresa_id', $empresaId)
            ->where('transferencias.estado', 'aprovado')
            ->select(
                'item_transferencias.produto_id',
                DB::raw('COALESCE(transferencias.filial_entrada_id, -1) as filial_id'),
                DB::raw('item_transferencias.quantidade as qtd'),
                DB::raw('COALESCE(transferencias.data_emissao, transferencias.created_at) as data'),
                DB::raw("'entrada' as tipo")
            );

        $produtosDevolucao = DB::table('produtos')
            ->where('empresa_id', $empresaId)
            ->selectRaw('MIN(id) as produto_id, referencia, nome')
            ->groupBy('referencia', 'nome');

        $devolucoes = DB::table('item_devolucaos')
            ->join('devolucaos', 'devolucaos.id', '=', 'item_devolucaos.devolucao_id')
            ->joinSub($produtosDevolucao, 'p', function ($join) {
                $join->on('p.referencia', '=', 'item_devolucaos.cod')
                    ->on('p.nome', '=', 'item_devolucaos.nome');
            })
            ->where('devolucaos.empresa_id', $empresaId)
            ->where('devolucaos.estado', 1)
            ->select(
                'p.produto_id',
                DB::raw('COALESCE(devolucaos.filial_id, -1) as filial_id'),
                DB::raw('item_devolucaos.quantidade as qtd'),
                DB::raw('devolucaos.data_registro as data'),
                DB::raw("CASE WHEN devolucaos.tipo = 0 THEN 'entrada' ELSE 'saida' END as tipo")
            );

        return $compras
            ->unionAll($vendas)
            ->unionAll($vendasCaixa)
            ->unionAll($alteracoes)
            ->unionAll($transferenciasSaida)
            ->unionAll($transferenciasEntrada)
            ->unionAll($devolucoes);
    }

    private function rawDataCompras()
    {
        return DB::raw('COALESCE(compras.data_emissao, compras.created_at)');
    }

    private function rawDataVendas()
    {
        return DB::raw('COALESCE(vendas.data_emissao, vendas.data_registro, vendas.created_at)');
    }

    private function rawDataVendasCaixa()
    {
        return DB::raw('COALESCE(venda_caixas.data_emissao, venda_caixas.data_registro, venda_caixas.created_at)');
    }

    private function rawDataTransferencias()
    {
        return DB::raw('COALESCE(transferencias.data_emissao, transferencias.created_at)');
    }

    private function parseDataInicio(?string $data, Carbon $fallback): string
    {
        return $this->parseData($data, $fallback)->startOfDay()->toDateTimeString();
    }

    private function parseDataFim(?string $data, Carbon $fallback): string
    {
        return $this->parseData($data, $fallback)->endOfDay()->toDateTimeString();
    }

    private function parseData(?string $data, Carbon $fallback): Carbon
    {
        if (!$data) {
            return $fallback->copy();
        }

        try {
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) {
                return Carbon::createFromFormat('d/m/Y', $data);
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                return Carbon::createFromFormat('Y-m-d', $data);
            }

            return Carbon::parse($data);
        } catch (\Throwable $e) {
            return $fallback->copy();
        }
    }
}
