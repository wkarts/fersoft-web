<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        // 1. Captura correta da empresa logada conforme a sessão
        $userLogged = session('user_logged');
        $empresaId = $userLogged['empresa'] ?? null;

        if (!$empresaId) {
            return "Erro: Empresa não identificada na sessão.";
        }

        $dataInicial = $request->data_inicial ? Carbon::parse($request->data_inicial)->startOfDay()->toDateTimeString() : Carbon::now()->startOfMonth()->toDateTimeString();
        $dataFinal = $request->data_final ? Carbon::parse($request->data_final)->endOfDay()->toDateTimeString() : Carbon::now()->endOfDay()->toDateTimeString();

        // 2. Construção das subqueries em formato RAW
        $comprasSql = "SELECT item_compras.produto_id, COALESCE(compras.filial_id, -1) as filial_id, item_compras.quantidade as qtd, compras.data_emissao as data, 'entrada' as tipo
                       FROM item_compras
                       JOIN compras ON compras.id = item_compras.compra_id
                       WHERE compras.empresa_id = {$empresaId} AND compras.estado IN ('APROVADO', 'IMPORTADO')";

        $vendasSql = "SELECT item_vendas.produto_id, COALESCE(vendas.filial_id, -1) as filial_id, item_vendas.quantidade as qtd, vendas.data_emissao as data, 'saida' as tipo
                      FROM item_vendas
                      JOIN vendas ON vendas.id = item_vendas.venda_id
                      WHERE vendas.empresa_id = {$empresaId} AND vendas.estado = 'APROVADO'";

        $vendasCaixaSql = "SELECT item_venda_caixas.produto_id, COALESCE(venda_caixas.filial_id, -1) as filial_id, item_venda_caixas.quantidade as qtd, venda_caixas.data_emissao as data, 'saida' as tipo
                           FROM item_venda_caixas
                           JOIN venda_caixas ON venda_caixas.id = item_venda_caixas.venda_caixa_id
                           WHERE venda_caixas.empresa_id = {$empresaId} AND venda_caixas.estado = 'APROVADO'";

        $alteracoesSql = "SELECT produto_id, COALESCE(filial_id, -1) as filial_id, quantidade as qtd, created_at as data, IF(tipo = 'incremento', 'entrada', 'saida') as tipo
                          FROM alteracao_estoques
                          WHERE empresa_id = {$empresaId}";

        // AJUSTE: Apenas a SAÍDA da transferência é contabilizada aqui (a entrada vem via Nota de Compra)
        $transfSaidaSql = "SELECT item_transferencias.produto_id, COALESCE(transferencias.filial_saida_id, -1) as filial_id, item_transferencias.quantidade as qtd, transferencias.created_at as data, 'saida' as tipo
                           FROM item_transferencias
                           JOIN transferencias ON transferencias.id = item_transferencias.transferencia_id
                           WHERE transferencias.empresa_id = {$empresaId}";

        // DEVOLUÇÕES: Vincula pelo primeiro ID que encontrar combinando referência e descrição
        $devolucoesSql = "SELECT p.produto_id, COALESCE(devolucaos.filial_id, -1) as filial_id, item_devolucaos.quantidade as qtd, devolucaos.data_registro as data, 'saida' as tipo
                          FROM item_devolucaos
                          JOIN devolucaos ON devolucaos.id = item_devolucaos.devolucao_id
                          JOIN (SELECT MIN(id) as produto_id, referencia, nome FROM produtos WHERE empresa_id = {$empresaId} GROUP BY referencia, nome) p
                            ON p.referencia = item_devolucaos.cod AND p.nome = item_devolucaos.nome
                          WHERE devolucaos.empresa_id = {$empresaId}";

        // Unificando a matriz de movimentações sem a entrada de transferência
        $unifiedSql = "($comprasSql) UNION ALL ($vendasSql) UNION ALL ($vendasCaixaSql) UNION ALL ($alteracoesSql) UNION ALL ($transfSaidaSql) UNION ALL ($devolucoesSql)";

        $saldosSql = "SELECT
                        produto_id,
                        filial_id,
                        SUM(CASE WHEN data < '{$dataInicial}' THEN IF(tipo = 'entrada', qtd, -qtd) ELSE 0 END) as saldo_inicial,
                        SUM(CASE WHEN data BETWEEN '{$dataInicial}' AND '{$dataFinal}' AND tipo = 'entrada' THEN qtd ELSE 0 END) as total_entradas,
                        SUM(CASE WHEN data BETWEEN '{$dataInicial}' AND '{$dataFinal}' AND tipo = 'saida' THEN qtd ELSE 0 END) as total_saidas
                      FROM ({$unifiedSql}) as mov
                      GROUP BY produto_id, filial_id";

        // 3. Query principal utilizando um JOIN nativo seguro
        $query = DB::table('produtos')
            ->join(DB::raw("({$saldosSql}) as saldos"), function ($join) {
                $join->on('produtos.id', '=', 'saldos.produto_id');
            })
            ->leftJoin('filials', 'filials.id', '=', 'saldos.filial_id')
            ->where('produtos.empresa_id', $empresaId)
            ->where('produtos.gerenciar_estoque', 1)
            ->select(
                'produtos.id',
                'produtos.referencia',
                DB::raw("CONCAT(produtos.nome, ' - ', IF(saldos.filial_id = -1, 'MATRIZ', UPPER(filials.descricao))) as descricao"),
                'produtos.valor_compra as custo_medio',
                'saldos.filial_id',
                DB::raw('COALESCE(saldos.saldo_inicial, 0) as saldo_inicial'),
                DB::raw('COALESCE(saldos.total_entradas, 0) as entradas'),
                DB::raw('COALESCE(saldos.total_saidas, 0) as saidas'),
                DB::raw('(COALESCE(saldos.saldo_inicial, 0) + COALESCE(saldos.total_entradas, 0) - COALESCE(saldos.total_saidas, 0)) as saldo_final'),
                DB::raw('(COALESCE(saldos.saldo_inicial, 0) + COALESCE(saldos.total_entradas, 0) - COALESCE(saldos.total_saidas, 0)) * produtos.valor_compra as valor_total')
            );

        // 4. Aplicação dos filtros dinâmicos
        if ($request->filled('filial_id')) {
            $query->where('saldos.filial_id', $request->filial_id);
        }
        if ($request->filled('categoria_id')) {
            $query->where('produtos.categoria_id', $request->categoria_id);
        }
        if ($request->filled('sub_categoria_id')) {
            $query->where('produtos.sub_categoria_id', $request->sub_categoria_id);
        }
        if ($request->filled('produto_id')) {
            $query->where('produtos.id', $request->produto_id);
        }

        $resultados = $query->paginate(50);

        // Listagens para preencher os componentes Select2 da View
        $filiais = DB::table('filials')->where('empresa_id', $empresaId)->select('id', 'descricao as nome')->get();
        $categorias = DB::table('categorias')->where('empresa_id', $empresaId)->select('id', 'nome')->orderBy('nome')->get();
        $subCategorias = DB::table('sub_categorias')->select('id', 'nome')->orderBy('nome')->get();
        $produtos_filtro = DB::table('produtos')->where('empresa_id', $empresaId)->where('gerenciar_estoque', 1)->select('id', 'nome', 'referencia')->orderBy('nome')->get();

        $title = $titulo = 'Saldo Real de Estoque';
        return view('relatorios.estoque', compact('title', 'titulo', 'resultados', 'dataInicial', 'dataFinal', 'filiais', 'categorias', 'subCategorias', 'produtos_filtro'));
    }

    public function extratoMovimentacao(Request $request, $produto_id)
    {
        $produto = DB::table('produtos')->where('id', $produto_id)->first();

        $dataInicial = $request->data_inicial ? Carbon::parse($request->data_inicial)->startOfDay()->toDateTimeString() : Carbon::now()->startOfMonth()->toDateTimeString();
        $dataFinal = $request->data_final ? Carbon::parse($request->data_final)->endOfDay()->toDateTimeString() : Carbon::now()->endOfDay()->toDateTimeString();
        $filialId = $request->filial_id;

        // Queries base com filtros de estado exigidos
        $comprasQuery = DB::table('item_compras')
            ->join('compras', 'compras.id', '=', 'item_compras.compra_id')
            ->leftJoin('fornecedors', 'fornecedors.id', '=', 'compras.fornecedor_id')
            ->where('item_compras.produto_id', $produto_id)
            ->whereIn('compras.estado', ['APROVADO', 'IMPORTADO']);

        $vendasQuery = DB::table('item_vendas')
            ->join('vendas', 'vendas.id', '=', 'item_vendas.venda_id')
            ->leftJoin('clientes', 'clientes.id', '=', 'vendas.cliente_id')
            ->where('item_vendas.produto_id', $produto_id)
            ->where('vendas.estado', 'APROVADO');

        $vendasCaixaQuery = DB::table('item_venda_caixas')
            ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
            ->leftJoin('clientes', 'clientes.id', '=', 'venda_caixas.cliente_id')
            ->where('item_venda_caixas.produto_id', $produto_id)
            ->where('venda_caixas.estado', 'APROVADO');

        $alteracoesQuery = DB::table('alteracao_estoques')->where('produto_id', $produto_id);

        // AJUSTE: Mantido apenas a query de Saída da Transferência no extrato individual
        $transfSaidaQuery = DB::table('item_transferencias')
            ->join('transferencias', 'transferencias.id', '=', 'item_transferencias.transferencia_id')
            ->leftJoin('filials', 'filials.id', '=', 'transferencias.filial_entrada_id')
            ->where('item_transferencias.produto_id', $produto_id);

        $devolucoesQuery = DB::table('item_devolucaos')
            ->join('devolucaos', 'devolucaos.id', '=', 'item_devolucaos.devolucao_id')
            ->leftJoin('fornecedors', 'fornecedors.id', '=', 'devolucaos.fornecedor_id')
            ->where('item_devolucaos.cod', $produto->referencia)
            ->where('item_devolucaos.nome', $produto->nome);

        // Aplica o filtro da filial correspondente à linha clicada
        if ($request->filled('filial_id')) {
            if ($filialId == '-1') {
                $comprasQuery->whereNull('compras.filial_id');
                $vendasQuery->whereNull('vendas.filial_id');
                $vendasCaixaQuery->whereNull('venda_caixas.filial_id');
                $alteracoesQuery->where(function($q) { $q->whereNull('filial_id')->orWhere('filial_id', -1); });
                $transfSaidaQuery->whereNull('transferencias.filial_saida_id');
                $devolucoesQuery->whereNull('devolucaos.filial_id');
            } else {
                $comprasQuery->where('compras.filial_id', $filialId);
                $vendasQuery->where('vendas.filial_id', $filialId);
                $vendasCaixaQuery->where('venda_caixas.filial_id', $filialId);
                $alteracoesQuery->where('filial_id', $filialId);
                $transfSaidaQuery->where('transferencias.filial_saida_id', $filialId);
                $devolucoesQuery->where('devolucaos.filial_id', $filialId);
            }
        }

        // 1. Buscando o Saldo Inicial do período
        $saldoInicial = 0;
        $saldoInicial += (clone $comprasQuery)->where('compras.data_emissao', '<', $dataInicial)->sum('item_compras.quantidade');
        $saldoInicial -= (clone $vendasQuery)->where('vendas.data_emissao', '<', $dataInicial)->sum('item_vendas.quantidade');
        $saldoInicial -= (clone $vendasCaixaQuery)->where('venda_caixas.data_emissao', '<', $dataInicial)->sum('item_venda_caixas.quantidade');
        $saldoInicial += (clone $alteracoesQuery)->where('created_at', '<', $dataInicial)->where('tipo', 'incremento')->sum('quantidade');
        $saldoInicial -= (clone $alteracoesQuery)->where('created_at', '<', $dataInicial)->where('tipo', '!=', 'incremento')->sum('quantidade');

        // Ajuste no histórico retroativo: apenas deduz as saídas de transferência
        $saldoInicial -= (clone $transfSaidaQuery)->where('transferencias.created_at', '<', $dataInicial)->sum('item_transferencias.quantidade');
        $saldoInicial -= (clone $devolucoesQuery)->where('devolucaos.data_registro', '<', $dataInicial)->sum('item_devolucaos.quantidade');

        // 2. Detalhamento das movimentações
        $compras = $comprasQuery->whereBetween('compras.data_emissao', [$dataInicial, $dataFinal])
            ->select('item_compras.quantidade as qtd', 'compras.data_emissao as data', DB::raw("'Entrada (Compra)' as operacao"), DB::raw("'entrada' as tipo"), 'compras.id as numero_nota', DB::raw("COALESCE(fornecedors.razao_social, 'Não Informado') as pessoa"));

        $vendas = $vendasQuery->whereBetween('vendas.data_emissao', [$dataInicial, $dataFinal])
            ->select('item_vendas.quantidade as qtd', 'vendas.data_emissao as data', DB::raw("'Saída (Venda)' as operacao"), DB::raw("'saida' as tipo"), 'vendas.id as numero_nota', DB::raw("COALESCE(clientes.razao_social, 'Não Informado') as pessoa"));

        $vendasCaixa = $vendasCaixaQuery->whereBetween('venda_caixas.data_emissao', [$dataInicial, $dataFinal])
            ->select('item_venda_caixas.quantidade as qtd', 'venda_caixas.data_emissao as data', DB::raw("'Saída (PDV)' as operacao"), DB::raw("'saida' as tipo"), 'venda_caixas.id as numero_nota', DB::raw("COALESCE(clientes.razao_social, 'Consumidor Final') as pessoa"));

        $alteracoes = $alteracoesQuery->whereBetween('created_at', [$dataInicial, $dataFinal])
            ->select('quantidade as qtd', 'created_at as data', DB::raw("CONCAT('Ajuste: ', COALESCE(observacao, motivo, '')) as operacao"), DB::raw("IF(tipo = 'incremento', 'entrada', 'saida') as tipo"), DB::raw("'-' as numero_nota"), DB::raw("'-' as pessoa"));

        $transfSaida = $transfSaidaQuery->whereBetween('transferencias.created_at', [$dataInicial, $dataFinal])
            ->select('item_transferencias.quantidade as qtd', 'transferencias.created_at as data', DB::raw("CONCAT('Transferência (Saída para ', COALESCE(filials.descricao, 'Matriz'), ')') as operacao"), DB::raw("'saida' as tipo"), 'transferencias.id as numero_nota', DB::raw("'-' as pessoa"));

        $devolucoes = $devolucoesQuery->whereBetween('devolucaos.data_registro', [$dataInicial, $dataFinal])
            ->select('item_devolucaos.quantidade as qtd', 'devolucaos.data_registro as data', DB::raw("'Devolução ao Fornecedor' as operacao"), DB::raw("'saida' as tipo"), 'devolucaos.id as numero_nota', DB::raw("COALESCE(fornecedors.razao_social, 'Não Informado') as pessoa"));

        // Unindo a coleção de dados sem o bloco de entradas de transferência
        $movimentacoes = $compras->unionAll($vendas)->unionAll($vendasCaixa)->unionAll($alteracoes)->unionAll($transfSaida)->unionAll($devolucoes)->orderBy('data', 'asc')->get();

        $title = 'Extrato de Movimentação - ' . $produto->nome;
        $titulo = 'Extrato de Movimentação - ' . $produto->nome;

        return view('relatorios.extrato_estoque', compact('title', 'titulo', 'produto', 'movimentacoes', 'saldoInicial', 'dataInicial', 'dataFinal'));
    }
}
