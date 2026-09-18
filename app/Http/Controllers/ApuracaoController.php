<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoriaConta;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Utils\ContaEmpresaUtil;

class ApuracaoController extends BaseController
{
    protected $util;

    public function __construct(ContaEmpresaUtil $util){
        parent::__construct();
        $this->util = $util;
        $this->redirectPage = '/apuracao';
    }

    protected function rules(): array { return []; }
    protected function messages(): array { return []; }

    public function index(Request $request)
    {
        $mes = (int) ($request->mes ?? date('m'));
        $ano = (int) ($request->ano ?? date('Y'));
        $filial_id = $request->filled('filial_id') ? $request->filial_id : null;
        $regime = $request->regime ?? 'competencia';

        // Inicializa as estruturas básicas para a View
        $dados = [
            'receita_bruta'   => 0,
            'deducao_venda'   => 0, // Novo
            'devolucao'       => 0,
            'cmv'             => 0,
            'pessoal'         => 0, // Novo
            'administrativa'  => 0,
            'operacional'     => 0,
            'tributaria'      => 0,
            'financeira'      => 0,
            'nao_operacional' => 0,
        ];

        // Inicialize também no array $detalhes para que os sub-itens apareçam ao clicar
        $detalhes = [
            'receita_bruta'   => [],
            'deducao_venda'   => [], // Novo
            'pessoal'         => [], // Novo
            'administrativa'  => [],
            'operacional'     => [],
            'tributaria'      => [],
            'financeira'      => [],
            'nao_operacional' => [],
        ];

        // 1. RECEITA BRUTA
        $receitasObtidas = $this->calcularReceitaBruta($mes, $ano, $filial_id);
        $dados['receita_bruta'] = array_sum($receitasObtidas);
        
        foreach ($receitasObtidas as $nome => $total) {
            $detalhes['receita_bruta'][] = (object)[
                'categoria_nome' => $nome,
                'total' => $total
            ];
        }

        // 2. DEDUÇÕES (Devolução de Venda)
        $dados['devolucao'] = $this->calcularDevolucoesVenda($mes, $ano, $filial_id);

        // 3. CUSTO DAS MERCADORIAS VENDIDAS (CMV)
        $cmvData = $this->calcularCMV($mes, $ano, $filial_id, $request);
        $cmvReal = $cmvData['cmv_real'];
        $estoqueInicial = $cmvData['estoque_inicial'];
        $comprasDoMes = $cmvData['entradas_liquidas'];
        $estoqueFinal = $cmvData['estoque_final'];

        // 4. MOVIMENTAÇÕES FINANCEIRAS DETALHADAS
        $dados['financeira'] = $this->calcularMovimentacoesFinanceiras($mes, $ano, $filial_id);

        // 5. DEMAIS DESPESAS OPERACIONAIS / ADMINISTRATIVAS / TRIBUTÁRIAS
        $financeiroGrupado = $this->calcularDespesasGrupadas($mes, $ano, $filial_id);
        foreach ($financeiroGrupado as $grupo => $valor) {
            if ($grupo !== 'financeira' && array_key_exists($grupo, $dados)) {
                // CORREÇÃO: Força o não_operacional a ser negativo, pois vem do contas a pagar (despesa)
                $dados[$grupo] = ($grupo === 'nao_operacional') ? -$valor : $valor;
            }
        }

        // Alimenta os detalhes de cada grupo (sub-itens do DRE)
        foreach ($detalhes as $grupo => $lista) {
            if ($grupo == 'receita_bruta') continue;
            
            if ($grupo == 'financeira') {
                $detalhes['financeira'] = $this->obterDetalhesFinanceiros($mes, $ano, $filial_id);
            } else {
                $det = $this->obterDetalhesPorGrupo($grupo, $mes, $ano, $filial_id);
                // Inverte o sinal dos detalhes do não operacional para a visualização
                if ($grupo === 'nao_operacional') {
                    foreach ($det as &$d) $d->total = -abs($d->total);
                }
                $detalhes[$grupo] = $det;
            }
        }

        // Dados auxiliares para a View
        $nomeMatriz = DB::table('empresas')->where('id', $this->empresa_id)->value('nome_fantasia') ?? 'MATRIZ';
        $filiais = DB::table('filials')->where('empresa_id', $this->empresa_id)->get();
        
        $mesAnterior = $mes == 1 ? 12 : $mes - 1;
        $anoAnterior = $mes == 1 ? $ano - 1 : $ano;
        $saldoAnterior = DB::table('fechamentos_mensais')
            ->where('empresa_id', $this->empresa_id)
            ->where('mes', $mesAnterior)
            ->where('ano', $anoAnterior)
            ->value('lucro_prejuizo_liquido') ?? 0;

        return view('apuracao.index', compact(
            'mes', 'ano', 'filial_id', 'filiais', 'nomeMatriz', 'saldoAnterior', 'regime',
            'dados', 'detalhes', 'cmvReal', 'estoqueInicial', 'comprasDoMes', 'estoqueFinal'
        ))->with('title', 'DRE - Demonstração do Resultado do Exercício');
    }

    private function calcularReceitaBruta($mes, $ano, $filial_id)
    {
        $queryVendas = DB::table('vendas')
            ->where('empresa_id', $this->empresa_id)
            ->where('estado', 'APROVADO') 
            ->whereNotNull('tipo_pagamento') 
            ->where('tipo_pagamento', '!=', '90') 
            ->whereMonth('data_emissao', $mes)
            ->whereYear('data_emissao', $ano);

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $queryVendas->where('filial_id', $filial_id);
        } elseif ($filial_id == 'matriz') {
            $queryVendas->whereNull('filial_id');
        }

        $queryCtes = DB::table('ctes') 
            ->where('empresa_id', $this->empresa_id)
            ->where('estado', 'APROVADO')
            ->whereMonth('data_emissao', $mes) 
            ->whereYear('data_emissao', $ano);

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $queryCtes->where('filial_id', $filial_id);
        } elseif ($filial_id == 'matriz') {
            $queryCtes->whereNull('filial_id');
        }

        return [
            'Vendas de Mercadorias' => $queryVendas->sum('valor_total'),
            'Serviços de Transporte' => $queryCtes->sum('valor_receber'),
        ];
    }

    private function calcularDevolucoesVenda($mes, $ano, $filial_id)
    {
        $query = DB::table('devolucaos')
            ->where('empresa_id', $this->empresa_id)
            ->where('tipo', 0) 
            ->whereMonth('created_at', $mes) 
            ->whereYear('created_at', $ano);

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $query->where('filial_id', $filial_id);
        } elseif ($filial_id == 'matriz') {
            $query->whereNull('filial_id');
        }

        return $query->sum('valor_devolvido');
    }

    private function calcularCMV($mes, $ano, $filial_id, Request $request)
    {
        $mesAnterior = $mes == 1 ? 12 : $mes - 1;
        $anoAnterior = $mes == 1 ? $ano - 1 : $ano;

        // 1. ESTOQUE INICIAL
        if ($request->filled('simular_est_inicial')) {
            $estoqueInicial = (float) $request->simular_est_inicial;
        } else {
            $queryEstoqueInicial = DB::table('estoque_mensal_fechamentos')
                ->where('empresa_id', $this->empresa_id)
                ->where('mes', $mesAnterior)
                ->where('ano', $anoAnterior);

            if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
                $queryEstoqueInicial->where('filial_id', $filial_id);
            } elseif ($filial_id == 'matriz') {
                $queryEstoqueInicial->whereNull('filial_id');
            }
            $estoqueInicial = $queryEstoqueInicial->sum(DB::raw('quantidade * valor_unitario_custo'));
        }

        // 2. COMPRAS LÍQUIDAS
        if ($request->filled('simular_compras')) {
            $compras = (float) $request->simular_compras;
        } else {
            $queryCompras = DB::table('conta_pagars as p')
                ->join('categoria_contas as c', 'p.categoria_id', '=', 'c.id')
                ->where('p.empresa_id', $this->empresa_id)
                ->where('c.dre_grupo', 'cmv')
                ->whereMonth('p.data_emissao', $mes)
                ->whereYear('p.data_emissao', $ano);

            if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
                $queryCompras->where('p.filial_id', $filial_id);
            } elseif ($filial_id == 'matriz') {
                $queryCompras->whereNull('p.filial_id');
            }
            $compras = $queryCompras->sum('p.valor_integral');
        }

        // 3. ESTOQUE FINAL
        if ($request->filled('simular_est_final')) {
            $estoqueFinal = (float) $request->simular_est_final;
        } else {
            $queryEstoqueFinal = DB::table('estoque_mensal_fechamentos')
                ->where('empresa_id', $this->empresa_id)
                ->where('mes', $mes)
                ->where('ano', $ano);

            if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
                $queryEstoqueFinal->where('filial_id', $filial_id);
            } elseif ($filial_id == 'matriz') {
                $queryEstoqueFinal->whereNull('filial_id');
            }
            $estoqueFinal = $queryEstoqueFinal->sum(DB::raw('quantidade * valor_unitario_custo'));

            // Se o mês ainda não foi fechado (retornou 0), pega o momento atual para não estourar o CMV
            if($estoqueFinal <= 0){
                $queryEstoqueAtual = DB::table('estoques as e')
                    ->join('produtos as p', 'p.id', '=', 'e.produto_id') // Faz o join para ler o tipo
                    ->where('e.empresa_id', $this->empresa_id)
                    ->where('p.tipo_item', '00')       // Apenas tipo 00
                    ->where('e.quantidade', '>', 0);   // Apenas saldo positivo
                
                if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
                    $queryEstoqueAtual->where('e.filial_id', $filial_id);
                } elseif ($filial_id == 'matriz') {
                    $queryEstoqueAtual->whereNull('e.filial_id');
                }
                
                $estoqueFinal = $queryEstoqueAtual->sum(DB::raw('e.quantidade * e.valor_compra'));
            }
        }

        // RETORNO DA FUNÇÃO (Isso garante que a matemática fecha certinha)
        return [
            'estoque_inicial' => $estoqueInicial,
            'entradas_liquidas' => $compras,
            'estoque_final' => $estoqueFinal,
            'cmv_real' => ($estoqueInicial + $compras) - $estoqueFinal
        ];
    }
  
    private function calcularMovimentacoesFinanceiras($mes, $ano, $filial_id)
    {
        // 1. Juros e Multas PAGOS (Contas a Pagar)
        $queryJurosPagar = DB::table('conta_pagars')
            ->where('empresa_id', $this->empresa_id)
            ->whereMonth('data_emissao', $mes) 
            ->whereYear('data_emissao', $ano);
        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') $queryJurosPagar->where('filial_id', $filial_id);
        $jurosE_MultasPagos = (float) $queryJurosPagar->sum(DB::raw('juros + multa'));

        // 2. Descontos CONCEDIDOS (Contas a Receber) -> É uma despesa financeira pois reduz o faturamento
        $queryDescontoReceber = DB::table('conta_recebers')
            ->where('empresa_id', $this->empresa_id)
            ->whereRaw('MONTH(COALESCE(nf_data_emissao, data_vencimento)) = ?', [$mes])
            ->whereRaw('YEAR(COALESCE(nf_data_emissao, data_vencimento)) = ?', [$ano]);
            
        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $queryDescontoReceber->where('filial_id', $filial_id);
        }
        
        $descontosConcedidos = (float) $queryDescontoReceber->sum('desconto');

        // 3. Contas lançadas com o grupo DRE 'financeira' (Ex: Tarifas Bancárias)
        $queryDespesasFixasFin = DB::table('conta_pagars as p')
            ->join('categoria_contas as c', 'p.categoria_id', '=', 'c.id')
            ->where('p.empresa_id', $this->empresa_id)
            ->where('c.dre_grupo', 'financeira')
            ->whereMonth('p.data_emissao', $mes)
            ->whereYear('p.data_emissao', $ano);
        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') $queryDespesasFixasFin->where('p.filial_id', $filial_id);
        $despesasContasFin = (float) $queryDespesasFixasFin->sum('p.valor_integral');

        // Retorna a soma de tudo convertida para negativo (despesa)
        return -($jurosE_MultasPagos + $descontosConcedidos + $despesasContasFin);
    }

    private function obterDetalhesFinanceiros($mes, $ano, $filial_id)
    {
        $detalhes = [];

        // Detalhe 1: Juros e Multas Pagos
        $queryJuros = DB::table('conta_pagars')
            ->where('empresa_id', $this->empresa_id)
            ->whereMonth('data_emissao', $mes)
            ->whereYear('data_emissao', $ano);
        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') $queryJuros->where('filial_id', $filial_id);
        $totalJuros = $queryJuros->sum(DB::raw('juros + multa'));
        
        if ($totalJuros > 0) {
            $detalhes[] = (object)['categoria_nome' => 'Juros e Multas Pagos', 'total' => -$totalJuros];
        }

        /// Detalhe 2: Descontos Concedidos no Recebimento
        $queryDescontos = DB::table('conta_recebers')
            ->where('empresa_id', $this->empresa_id)
            ->whereRaw('MONTH(COALESCE(nf_data_emissao, data_vencimento)) = ?', [$mes])
            ->whereRaw('YEAR(COALESCE(nf_data_emissao, data_vencimento)) = ?', [$ano]);
            
        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $queryDescontos->where('filial_id', $filial_id);
        }
        
        $totalDescontos = $queryDescontos->sum('desconto');

        if ($totalDescontos > 0) {
            $detalhes[] = (object)['categoria_nome' => 'Descontos Concedidos (CR)', 'total' => -$totalDescontos];
        }

        // Detalhe 3: Demais categorias financeiras
        $categoriasFin = $this->obterDetalhesPorGrupo('financeira', $mes, $ano, $filial_id);
        foreach ($categoriasFin as $cat) {
            $detalhes[] = (object)['categoria_nome' => $cat->categoria_nome, 'total' => -$cat->total];
        }

        return $detalhes;
    }

    private function calcularDespesasGrupadas($mes, $ano, $filial_id)
    {
        $query = DB::table('conta_pagars as p')
            ->join('categoria_contas as c', 'p.categoria_id', '=', 'c.id')
            ->select('c.dre_grupo', DB::raw("SUM(p.valor_integral) as total"))
            ->where('p.empresa_id', $this->empresa_id)
            ->where('c.incluir_resultado', 1) 
            ->whereMonth('p.data_emissao', $mes) 
            ->whereYear('p.data_emissao', $ano)
            ->groupBy('c.dre_grupo');

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $query->where('p.filial_id', $filial_id);
        } elseif ($filial_id == 'matriz') {
            $query->whereNull('p.filial_id');
        }

        return $query->pluck('total', 'c.dre_grupo')->toArray();
    }

    private function obterDetalhesPorGrupo($grupo, $mes, $ano, $filial_id)
    {
        $query = DB::table('conta_pagars as p')
            ->join('categoria_contas as c', 'p.categoria_id', '=', 'c.id')
            ->select('c.nome as categoria_nome', DB::raw("SUM(p.valor_integral) as total"))
            ->where('p.empresa_id', $this->empresa_id)
            ->where('c.dre_grupo', $grupo)
            ->where('c.incluir_resultado', 1)
            ->whereMonth('p.data_emissao', $mes)
            ->whereYear('p.data_emissao', $ano)
            ->groupBy('c.nome');

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $query->where('p.filial_id', $filial_id);
        } elseif ($filial_id == 'matriz') {
            $query->whereNull('p.filial_id');
        }

        return $query->get()->toArray();
    }

    public function finalizar(Request $request)
    {
        $mes = $request->mes; 
        $ano = $request->ano;
        
        DB::beginTransaction();
        try {
            // 1. Descobre o ID do usuário de forma segura ANTES de fazer os inserts
            $userId = 1; // Fallback: ID de segurança
            if (auth()->check()) {
                $userId = auth()->user()->id;
            } elseif (isset($this->usuario_id)) {
                $userId = $this->usuario_id;
            }

            // 2. Busca o estoque atual (Apenas tipo '00' e quantidade > 0)
            $itensEstoque = DB::table('estoques as e')
                ->join('produtos as p', 'p.id', '=', 'e.produto_id')
                ->where('e.empresa_id', $this->empresa_id)
                ->where('p.tipo_item', '00')          // Alterado: busca apenas o tipo 00
                ->where('e.quantidade', '>', 0)       // Novo: ignora estoques zerados ou negativos
                ->select('e.produto_id', 'e.filial_id', 'e.quantidade', 'e.valor_compra')
                ->get();

            // 3. Salva o fechamento do estoque de cada item
            foreach ($itensEstoque as $item) {
                DB::table('estoque_mensal_fechamentos')->insert([
                    'empresa_id'           => $this->empresa_id, 
                    'produto_id'           => $item->produto_id, 
                    'filial_id'            => $item->filial_id,
                    'usuario_id'           => $userId, // Variável declarada logo acima
                    'quantidade'           => $item->quantidade, 
                    'valor_unitario_custo' => $item->valor_compra,
                    'mes'                  => $mes, 
                    'ano'                  => $ano, 
                    'created_at'           => now(), 
                    'updated_at'           => now()
                ]);
            }

            // 4. Salva o resumo do fechamento do mês
            DB::table('fechamentos_mensais')->insert([
                'empresa_id'             => $this->empresa_id, 
                'mes'                    => $mes, 
                'ano'                    => $ano, 
                'lucro_prejuizo_liquido' => $request->lucro_prejuizo_liquido ?? 0, 
                'regime'                 => $request->regime ?? 'competencia',
                'status'                 => 'encerrado',
                'usuario_id'             => $userId,        // Adicionado conforme a imagem
                'data_fechamento'        => now(),          // Adicionado conforme a imagem
                'created_at'             => now()           // Mantido conforme a imagem
            ]);
            
            DB::commit();
            return redirect()->back()->with('mensagem_sucesso', 'Período encerrado com sucesso!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
    }
}