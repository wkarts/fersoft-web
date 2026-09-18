<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pesagem;
use App\Models\Produto;
use App\Models\Fornecedor;
use App\Models\Cliente;
use App\Models\Veiculo;

class GestaoEstoqueController extends BaseController
{
    protected $redirectPage = '/gestao-estoque';
    protected $formTitle = 'Gestão de Estoque Físico';

    public function __construct()
    {
        parent::__construct();
        
        // Vamos garantir que o ID da empresa e filial venham da sessão
        $this->middleware(function ($request, $next) {
            $this->empresa_id = session('empresa_id'); 
            $this->filial_id = session('filial_id'); // Se você tiver filial
            return $next($request);
        });
    }

    protected function rules(): array { return []; }
    protected function messages(): array { return []; }

    public function index(Request $request)
    {
        $title = $this->formTitle;
        $dataInicial = $request->input('data_inicial', date('Y-m-01'));
        $dataFinal = $request->input('data_final', date('Y-m-d'));
        
        $produtoId = $request->input('produto_id');
        $parceiroNome = $request->input('parceiro_nome');
        $tipoMovimento = $request->input('tipo');

        $sessionData = session('user_logged');
		$empresaId = $sessionData['empresa'] ?? 1;

        $produtos = DB::table('produtos')->where('empresa_id', $empresaId)->orderBy('nome')->get();

        // ----------------------------------------------------------------
        // CONSULTA BASE (Usada para Analítico e Parceiros)
        // ----------------------------------------------------------------
        $queryBase = DB::table('estoque_fisico_movimentos')
          ->join('produtos', function($join) use ($empresaId) {
              $join->on('estoque_fisico_movimentos.produto_id', '=', 'produtos.id')
                   ->where('produtos.empresa_id', $empresaId);
          })
          ->leftJoin('pesagens', function($join) use ($empresaId) {
              $join->on('estoque_fisico_movimentos.pesagem_id', '=', 'pesagens.id')
                   ->where('pesagens.empresa_id', $empresaId);
          })
          ->leftJoin('fornecedors', 'pesagens.fornecedor_id', '=', 'fornecedors.id') 
          ->leftJoin('clientes', 'pesagens.cliente_id', '=', 'clientes.id')
          ->where('estoque_fisico_movimentos.empresa_id', $empresaId)
          ->whereRaw("DATE(estoque_fisico_movimentos.data_movimento) >= ?", [$dataInicial])
          ->whereRaw("DATE(estoque_fisico_movimentos.data_movimento) <= ?", [$dataFinal]);

        if ($produtoId) {
            $queryBase->where('estoque_fisico_movimentos.produto_id', $produtoId);
        }
        if ($tipoMovimento) {
            $queryBase->where('estoque_fisico_movimentos.tipo', $tipoMovimento);
        }
        if ($parceiroNome) {
            $queryBase->where(function($q) use ($parceiroNome) {
                $q->where('fornecedors.razao_social', 'like', '%' . $parceiroNome . '%')
                  ->orWhere('clientes.razao_social', 'like', '%' . $parceiroNome . '%');
            });
        }

        // 1. DADOS ANALÍTICOS (Paginados para a aba Analítica)
        $queryAnalitico = clone $queryBase;
        $analitico = $queryAnalitico->select(
                'estoque_fisico_movimentos.*',
                'produtos.nome as produto_nome',
                'pesagens.id as ticket_id',
                'fornecedors.razao_social as fornecedor_nome',
                'clientes.razao_social as cliente_nome'
            )
            ->orderBy('estoque_fisico_movimentos.data_movimento', 'desc')
            ->paginate(50);

        // 2. DADOS AGRUPADOS POR PARCEIRO (Nova Aba)
        $queryParceiros = clone $queryBase;
        $dadosPorParceiroRaw = $queryParceiros->select(
                'estoque_fisico_movimentos.*',
                'produtos.nome as produto_nome',
                'pesagens.id as ticket_id',
                'fornecedors.razao_social as fornecedor_nome',
                'clientes.razao_social as cliente_nome'
            )
            ->orderBy('estoque_fisico_movimentos.data_movimento', 'desc')
            ->get();

        $dadosAgrupados = $dadosPorParceiroRaw->groupBy(function($item) {
            return $item->fornecedor_nome ?? $item->cliente_nome ?? 'Movimento Manual';
        });

        $resumoParceiros = [];
        foreach($dadosAgrupados as $nome => $movimentos) {
            $pesoLiquido = $movimentos->sum('quantidade');
            $valorTotal = $movimentos->sum('valor_total');
            
            $resumoParceiros[$nome] = [
                'movimentos'   => $movimentos,
                'peso_bruto'   => $movimentos->sum('peso_bruto'),
                'impureza'     => $movimentos->sum('peso_impureza'),
                'peso_liquido' => $pesoLiquido,
                'valor_total'  => $valorTotal,
                'preco_medio'  => $pesoLiquido > 0 ? ($valorTotal / $pesoLiquido) : 0
            ];
        }

        // 3. CONSULTA SINTÉTICA GERAL (Aba Resumo)
        $querySintetico = DB::table('estoque_fisico_movimentos')
            ->join('produtos', function($join) use ($empresaId) {
                $join->on('estoque_fisico_movimentos.produto_id', '=', 'produtos.id')
                     ->where('produtos.empresa_id', '=', $empresaId);
            })
            ->leftJoin('pesagens', function($join) use ($empresaId) {
                $join->on('estoque_fisico_movimentos.pesagem_id', '=', 'pesagens.id')
                     ->where('pesagens.empresa_id', '=', $empresaId);
            })
            ->leftJoin('fornecedors', 'pesagens.fornecedor_id', '=', 'fornecedors.id') 
            ->leftJoin('clientes', 'pesagens.cliente_id', '=', 'clientes.id')
            ->where('estoque_fisico_movimentos.empresa_id', $empresaId)
            ->where('estoque_fisico_movimentos.data_movimento', '<=', $dataFinal);

        if ($produtoId) { $querySintetico->where('estoque_fisico_movimentos.produto_id', $produtoId); }
        if ($tipoMovimento) { $querySintetico->where('estoque_fisico_movimentos.tipo', $tipoMovimento); }
        if ($parceiroNome) {
            $querySintetico->where(function($q) use ($parceiroNome) {
                $q->where('fornecedors.razao_social', 'like', '%' . $parceiroNome . '%')
                  ->orWhere('clientes.razao_social', 'like', '%' . $parceiroNome . '%');
            });
        }

        $sinteticoRaw = $querySintetico->select(
                'produtos.id as produto_id', 'produtos.nome as produto_nome',
                DB::raw("SUM(CASE WHEN DATE(estoque_fisico_movimentos.data_movimento) < '{$dataInicial}' AND estoque_fisico_movimentos.tipo = 'entrada' THEN estoque_fisico_movimentos.quantidade ELSE 0 END) - SUM(CASE WHEN DATE(estoque_fisico_movimentos.data_movimento) < '{$dataInicial}' AND estoque_fisico_movimentos.tipo = 'saida' THEN estoque_fisico_movimentos.quantidade ELSE 0 END) as estoque_inicial"),
                DB::raw("SUM(CASE WHEN DATE(estoque_fisico_movimentos.data_movimento) >= '{$dataInicial}' AND estoque_fisico_movimentos.tipo = 'entrada' THEN estoque_fisico_movimentos.quantidade ELSE 0 END) as entradas_periodo"),
                DB::raw("SUM(CASE WHEN DATE(estoque_fisico_movimentos.data_movimento) >= '{$dataInicial}' AND estoque_fisico_movimentos.tipo = 'saida' THEN estoque_fisico_movimentos.quantidade ELSE 0 END) as saidas_periodo"),
                DB::raw("SUM(CASE WHEN DATE(estoque_fisico_movimentos.data_movimento) >= '{$dataInicial}' AND estoque_fisico_movimentos.tipo = 'entrada' THEN estoque_fisico_movimentos.valor_total ELSE 0 END) as valor_entradas_periodo")
            )
            ->groupBy('produtos.id', 'produtos.nome')
            ->get();

        $sintetico = $sinteticoRaw->map(function($item) {
            $item->saldo_atual = $item->estoque_inicial + $item->entradas_periodo - $item->saidas_periodo;
            $item->preco_medio = $item->entradas_periodo > 0 ? ($item->valor_entradas_periodo / $item->entradas_periodo) : 0;
            $item->valor_total_estoque = $item->saldo_atual * $item->preco_medio;
            return $item;
        })->filter(function($item) {
            return $item->estoque_inicial != 0 || $item->entradas_periodo != 0 || $item->saidas_periodo != 0 || $item->saldo_atual != 0;
        });

        $totais = [
            'entradas_kg' => $sintetico->sum('entradas_periodo'),
            'saidas_kg'   => $sintetico->sum('saidas_periodo'),
            'saldo_kg'    => $sintetico->sum('saldo_atual'),
            'valor_patrimonio' => $sintetico->sum('valor_total_estoque'),
        ];

        // ----------------------------------------------------------------
        // EXPORTAÇÃO PARA PDF E EXCEL
        // ----------------------------------------------------------------
        if ($request->export === 'pdf') {
            $html = view('estoque.exportar', compact('title', 'analitico', 'sintetico', 'totais', 'dataInicial', 'dataFinal'))->render();
            $dompdf = new \Dompdf\Dompdf(['enable_remote' => true]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            return response($dompdf->output())->header('Content-Type', 'application/pdf')->header('Content-Disposition', 'inline; filename="estoque-analitico.pdf"');
        }

        if ($request->export === 'excel') {
            return view('estoque.exportar', compact('title', 'analitico', 'sintetico', 'totais', 'dataInicial', 'dataFinal'))
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename="estoque-analitico.xls"');
        }

        // Enviando a nova variável $resumoParceiros para a View
        return view('estoque.index', compact('title', 'analitico', 'sintetico', 'resumoParceiros', 'totais', 'dataInicial', 'dataFinal', 'produtos'));
    }
  
  
    public function sincronizar(Request $request)
    {
        try {
            $sessionData = session('user_logged');
            $empresaId = $sessionData['empresa'] ?? 1;
            $usuarioId = $sessionData['id'] ?? null;

            $configNota = DB::table('config_notas')->where('empresa_id', $empresaId)->first();
            $cnpjEmpresa = $configNota ? preg_replace('/[^0-9]/', '', $configNota->cnpj) : '';

            $pesagens = Pesagem::where('empresa_id', $empresaId)
                ->whereIn('status', ['concluído', 'Concluído', 'CONCLUIDO'])
                ->get();

            if ($pesagens->isEmpty()) {
                return redirect()->back()->with('warning', 'Nenhuma pesagem concluída encontrada para esta empresa.');
            }

            $inseridos = 0;
            foreach ($pesagens as $pesagem) {
                $filialIdSalvar = ($pesagem->filial_nome == 'Matriz' || session('filial_nome') == 'Matriz') ? null : $pesagem->filial_id;
                
                $tickets = DB::table('tickets_pesagem')->where('pesagem_id', $pesagem->id)->get();
                $ticketsAgrupados = $tickets->groupBy('produto_id');

                foreach ($ticketsAgrupados as $produtoId => $ticketsProduto) {
                    if (empty($produtoId)) continue;

                    if (!DB::table('estoque_fisico_movimentos')->where('pesagem_id', $pesagem->id)->where('produto_id', $produtoId)->exists()) {
                        
                        $tipoMovimento = (strtolower($pesagem->tipo) === 'venda') ? 'saida' : 'entrada';
                        
                        // 1. CÁLCULO DA TARA E PESO LÍQUIDO DA BALANÇA
                        $pesoEntrada = $ticketsProduto->where('tipo', 'entrada')->sum('peso');
                        $pesoSaida = $ticketsProduto->where('tipo', 'saida')->sum('peso');
                        
                        $pesoLiquidoBalanca = abs($pesoEntrada - $pesoSaida);
                        
                        // Fallback de segurança para ticket único manual
                        if ($pesoLiquidoBalanca == 0) {
                            $pesoLiquidoBalanca = $pesoEntrada > 0 ? $pesoEntrada : $pesoSaida;
                        }

                        // Subtrai peso do bag (tara extra)
                        $pesoBagTotal = $ticketsProduto->sum('peso_bag');
                        $pesoLiquidoBalanca = max(0, $pesoLiquidoBalanca - $pesoBagTotal);

                        if ($pesoLiquidoBalanca <= 0) continue;

                        // 2. CÁLCULO DA IMPUREZA CORRETA (Somando os campos de desconto)
                        $percentualAbatimento = 0;
                        if ($pesagem->danificado)   { $percentualAbatimento += (float) $pesagem->danificado_desconto; }
                        if ($pesagem->quebrado)     { $percentualAbatimento += (float) $pesagem->quebrado_desconto; }
                        if ($pesagem->esverdeado)   { $percentualAbatimento += (float) $pesagem->esverdeado_desconto; }
                        if ($pesagem->ardido)       { $percentualAbatimento += (float) $pesagem->ardido_desconto; }
                        if ($pesagem->secagem)      { $percentualAbatimento += (float) $pesagem->secagem_desconto; }
                        $percentualAbatimento += (float) ($pesagem->umidade_desconto ?? 0);
                        $percentualAbatimento += (float) ($pesagem->impureza_desconto ?? 0);

                        // Aplica a porcentagem em cima do peso líquido da balança
                        $pesoImpurezaFinal = $pesoLiquidoBalanca * ($percentualAbatimento / 100);

                        // 3. PESO FINAL PARA O ESTOQUE (Abatendo a impureza em kg)
                        $pesoFinalEstoque = max(0, $pesoLiquidoBalanca - $pesoImpurezaFinal);

                        // 4. VALOR UNITÁRIO
                        $ticketRef = $ticketsProduto->firstWhere('valor_unitario', '>', 0) ?? $ticketsProduto->first();
                        $valorUnitario = (float) ($ticketRef->valor_unitario ?? 0);

                        if ($valorUnitario == 0) {
                            $tabelaPrecoId = null;
                            if ($tipoMovimento === 'entrada' && $pesagem->fornecedor_id) {
                                $fornecedor = Fornecedor::find($pesagem->fornecedor_id);
                                $tabelaPrecoId = $fornecedor->tabela_preco_id ?? 1; 
                            } elseif ($tipoMovimento === 'saida' && $pesagem->cliente_id) {
                                $cliente = Cliente::find($pesagem->cliente_id);
                                $tabelaPrecoId = $cliente->tabela_preco_id ?? 1;
                            }

                            if ($tabelaPrecoId) {
                                $tipoFrete = 'ENTREGA';
                                if ($pesagem->veiculo_id) {
                                    $veiculo = Veiculo::find($pesagem->veiculo_id);
                                    if ($veiculo && !empty($veiculo->proprietario_documento)) {
                                        $docVeiculo = preg_replace('/[^0-9]/', '', $veiculo->proprietario_documento);
                                        if ($docVeiculo === $cnpjEmpresa && $docVeiculo !== '00000000000000' && $docVeiculo !== '00000000000') $tipoFrete = 'COLETA';
                                    }
                                }
                                $valorUnitario = DB::table('tabela_preco_itens')->where('tabela_preco_id', $tabelaPrecoId)->where('produto_id', $produtoId)->where('tipo_frete', $tipoFrete)->value('valor_kg') ?? 0;
                            }
                        }

                        if ($valorUnitario == 0) {
                            $produtoBase = \App\Models\Produto::find($produtoId);
                            $valorUnitario = $tipoMovimento === 'entrada' ? (float) ($produtoBase->valor_compra ?? 0) : (float) ($produtoBase->valor_venda ?? 0);
                        }

                        // GRAVA O REGISTRO PERFEITO NO BANCO
                        \DB::table('estoque_fisico_movimentos')->insert([
                            'empresa_id'     => $empresaId,
                            'filial_id'      => $filialIdSalvar, 
                            'usuario_id'     => $usuarioId ?? $pesagem->usuario_id,
                            'produto_id'     => $produtoId, 
                            'pesagem_id'     => $pesagem->id, 
                            'tipo'           => $tipoMovimento,
                            
                            'peso_bruto'     => $pesoLiquidoBalanca, // A Tara já abatida
                            'peso_impureza'  => $pesoImpurezaFinal,  // Impureza calculada (Ex: 175)
                            'quantidade'     => $pesoFinalEstoque,   // Peso Limpo (Ex: 3.325)
                            
                            'valor_unitario' => $valorUnitario, 
                            'valor_total'    => ($pesoFinalEstoque * $valorUnitario),
                            'data_movimento' => date('Y-m-d', strtotime($pesagem->created_at ?? now())),
                            'created_at'     => now(), 
                            'updated_at'     => now(),
                        ]);
                        $inseridos++;
                    }
                }
            }
            return redirect()->back()->with('success', "Sincronização concluída! {$inseridos} registros adicionados com a impureza correta.");
        } catch (\Exception $e) {
            \Log::error('Erro ao sincronizar estoque: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Erro interno na sincronização: ' . $e->getMessage()]);
        }
    }
}