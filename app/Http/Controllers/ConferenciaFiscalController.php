<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venda;
use App\Models\VendaCaixa;
use App\Models\Devolucao;
use App\Models\Cte;
use App\Models\ContaReceber;
use App\Models\NaturezaOperacao;
use App\Models\Filial;

class ConferenciaFiscalController extends Controller
{
    // Função central que busca os dados para a Tela, Excel e PDF
    // Função central que busca os dados para a Tela, Excel e PDF (OTIMIZADA)
    private function getDadosFiltrados(Request $request)
    {
        $sessao = session('user_logged');
        $empresa_id = $sessao['empresa'];

        $dataInicial = $request->data_inicial ?? date('Y-m-01');
        $dataFinal = $request->data_final ?? date('Y-m-t');
        
        $clientePesquisa = $request->cliente;
        $filialFiltro = $request->filial_id; 
        $naturezaFiltro = $request->natureza_id; 
        $tipoNotaFiltro = $request->tipo_nota; 

        $todosDados = collect();

        // 1. VENDAS (NFe)
        if (empty($tipoNotaFiltro) || $tipoNotaFiltro == 'NFe') {
            $qVendas = Venda::with(['cliente', 'itens', 'natureza'])
                ->where('empresa_id', $empresa_id)->whereBetween('data_emissao', [$dataInicial, $dataFinal]);
            
            if ($request->estado) $qVendas->where('estado', $request->estado);
            if ($naturezaFiltro) $qVendas->where('natureza_id', $naturezaFiltro);
            if ($filialFiltro === 'matriz') $qVendas->whereNull('filial_id');
            elseif (is_numeric($filialFiltro)) $qVendas->where('filial_id', $filialFiltro);

            if ($clientePesquisa) {
                $qVendas->whereHas('cliente', function($q) use ($clientePesquisa) {
                    $q->where('razao_social', 'LIKE', "%{$clientePesquisa}%");
                });
            }

            $vendas = $qVendas->get();
            
            // OTIMIZAÇÃO EXTREMA: Busca todas as contas a receber de uma só vez!
            $contasVendas = ContaReceber::whereIn('venda_id', $vendas->pluck('id'))->get()->groupBy('venda_id');

            $vendasMapeadas = $vendas->map(function($v) use ($contasVendas) {
                // Pega a conta vinculada sem consultar o banco de novo
                $contas = $contasVendas->get($v->id, collect());
                $valorRecebido = $contas->sum('valor_recebido');
                $isTransferencia = stripos($v->natureza->natureza ?? '', 'transfer') !== false;

                return [
                    'id' => $v->id, 'numero' => $v->NfNumero, 'data' => $v->data_emissao,
                    'cliente' => $v->cliente->razao_social ?? '--', 
                    'tipo' => $isTransferencia ? 'TRANSFERÊNCIA' : 'NFe',
                    'valor' => $v->valor_total, 'valor_recebido' => $valorRecebido,
                    'valor_aberto' => ($contas->count() > 0) ? ($contas->sum('valor_integral') - $valorRecebido) : $v->valor_total,
                    'situacao' => strtoupper($v->estado), 'integrado' => $contas->count() > 0,
                    'qtd_itens' => $v->itens->sum('quantidade'), 'chave' => "NFe_{$v->id}",
                    'bloqueia_integracao' => $isTransferencia 
                ];
            });
            $todosDados = $todosDados->concat($vendasMapeadas);
        }

        // 2. VENDA CAIXA (NFCe)
        if (empty($tipoNotaFiltro) || $tipoNotaFiltro == 'NFCe') {
            $qVendaCaixa = VendaCaixa::with(['cliente', 'itens', 'natureza'])
                ->where('empresa_id', $empresa_id)->whereBetween('created_at', [$dataInicial . ' 00:00:00', $dataFinal . ' 23:59:59']);
            
            if ($request->estado) $qVendaCaixa->where('estado', $request->estado);
            if ($naturezaFiltro) $qVendaCaixa->where('natureza_id', $naturezaFiltro);
            if ($filialFiltro === 'matriz') $qVendaCaixa->whereNull('filial_id');
            elseif (is_numeric($filialFiltro)) $qVendaCaixa->where('filial_id', $filialFiltro);

            if ($clientePesquisa) {
                $qVendaCaixa->whereHas('cliente', function($q) use ($clientePesquisa) {
                    $q->where('razao_social', 'LIKE', "%{$clientePesquisa}%");
                });
            }

            $vendasCaixa = $qVendaCaixa->get();
            $contasCaixa = ContaReceber::whereIn('venda_caixa_id', $vendasCaixa->pluck('id'))->get()->groupBy('venda_caixa_id');

            $vendasCaixaMapeadas = $vendasCaixa->map(function($v) use ($contasCaixa) {
                $contas = $contasCaixa->get($v->id, collect());
                $valorRecebido = $contas->sum('valor_recebido');
                return [
                    'id' => $v->id, 'numero' => $v->NFcNumero, 'data' => $v->created_at->format('Y-m-d'),
                    'cliente' => $v->cliente->razao_social ?? '--', 'tipo' => 'NFCe',
                    'valor' => $v->valor_total, 'valor_recebido' => $valorRecebido,
                    'valor_aberto' => ($contas->count() > 0) ? ($contas->sum('valor_integral') - $valorRecebido) : $v->valor_total,
                    'situacao' => strtoupper($v->estado), 'integrado' => $contas->count() > 0,
                    'qtd_itens' => $v->itens->sum('quantidade'), 'chave' => "NFCe_{$v->id}",
                    'bloqueia_integracao' => false
                ];
            });
            $todosDados = $todosDados->concat($vendasCaixaMapeadas);
        }

        // 3. DEVOLUÇÕES
        if (empty($tipoNotaFiltro) || $tipoNotaFiltro == 'DEVOLUCAO') {
            $qDevolucao = Devolucao::with(['fornecedor', 'itens'])
                ->where('empresa_id', $empresa_id)->whereBetween('data_registro', [$dataInicial, $dataFinal]);
            
            if ($request->estado) {
                if (in_array($request->estado, ['APROVADO', 'AUTORIZADO'])) $qDevolucao->where('estado', 1);
                if ($request->estado == 'CANCELADO') $qDevolucao->where('estado', 3);
            }
            if ($naturezaFiltro) $qDevolucao->where('natureza_id', $naturezaFiltro);
            if ($filialFiltro === 'matriz') $qDevolucao->whereNull('filial_id');
            elseif (is_numeric($filialFiltro)) $qDevolucao->where('filial_id', $filialFiltro);

            if ($clientePesquisa) {
                $qDevolucao->whereHas('fornecedor', function($q) use ($clientePesquisa) {
                    $q->where('razao_social', 'LIKE', "%{$clientePesquisa}%");
                });
            }

            $devolucoes = $qDevolucao->get()->map(function($d) {
                $situacao = $d->estado == 1 ? 'AUTORIZADO' : ($d->estado == 3 ? 'CANCELADO' : 'PENDENTE');
                $tipoDoc = $d->tipo == 0 ? 'DEVOLUÇÃO DE VENDA' : 'DEVOLUÇÃO DE COMPRA';
                
                return [
                    'id' => $d->id, 'numero' => $d->numero_gerado, 'data' => $d->data_registro,
                    'cliente' => $d->fornecedor->razao_social ?? '--', 'tipo' => $tipoDoc,
                    'valor' => $d->valor_integral, 'valor_recebido' => 0, 'valor_aberto' => 0,
                    'situacao' => $situacao, 'integrado' => false,
                    'qtd_itens' => $d->itens->sum('quantidade'), 'chave' => "DEV_{$d->id}",
                    'bloqueia_integracao' => true 
                ];
            });
            $todosDados = $todosDados->concat($devolucoes);
        }

        // 4. CTe
        if (empty($tipoNotaFiltro) || $tipoNotaFiltro == 'CTe') {
            $qCte = Cte::with(['remetente', 'natureza'])
                ->where('empresa_id', $empresa_id)->whereBetween('data_registro', [$dataInicial, $dataFinal]);
            
            if ($request->estado) $qCte->where('estado', $request->estado);
            if ($naturezaFiltro) $qCte->where('natureza_id', $naturezaFiltro);
            if ($filialFiltro === 'matriz') $qCte->whereNull('filial_id');
            elseif (is_numeric($filialFiltro)) $qCte->where('filial_id', $filialFiltro);

            if ($clientePesquisa) {
                $qCte->whereHas('remetente', function($q) use ($clientePesquisa) {
                    $q->where('razao_social', 'LIKE', "%{$clientePesquisa}%");
                });
            }

            $ctes = $qCte->get();
            $contasCte = ContaReceber::whereIn('numero_nota_fiscal', $ctes->pluck('cte_numero'))
                ->where('referencia', 'LIKE', '%CTe%')
                ->get()
                ->groupBy('numero_nota_fiscal');

            $ctesMapeados = $ctes->map(function($c) use ($contasCte) {
                $contas = $contasCte->get($c->cte_numero, collect());
                $valorRecebido = $contas->sum('valor_recebido');

                return [
                    'id' => $c->id, 'numero' => $c->cte_numero, 'data' => $c->data_registro,
                    'cliente' => $c->remetente->razao_social ?? '--', 'tipo' => 'CTe',
                    'valor' => $c->valor_receber, 'valor_recebido' => $valorRecebido, 
                    'valor_aberto' => ($contas->count() > 0) ? ($contas->sum('valor_integral') - $valorRecebido) : $c->valor_receber,
                    'situacao' => strtoupper($c->estado), 'integrado' => $contas->count() > 0,
                    'qtd_itens' => 0, 'chave' => "CTE_{$c->id}", 'bloqueia_integracao' => false 
                ];
            });
            $todosDados = $todosDados->concat($ctesMapeados);
        }

        $todosDados = $todosDados->sortByDesc('data')->values();

        return [
            'notas' => $todosDados,
            'totalValor' => $todosDados->sum('valor'),
            'totalRecebido' => $todosDados->sum('valor_recebido'),
            'totalAberto' => $todosDados->sum('valor_aberto'),
            'qtdNotas' => $todosDados->count(),
            'totalNFe' => $todosDados->where('tipo', 'NFe')->sum('valor'),
            'totalNFCe' => $todosDados->where('tipo', 'NFCe')->sum('valor'),
            'totalCTe' => $todosDados->where('tipo', 'CTe')->sum('valor'),
            'totalDevolucao' => $todosDados->whereIn('tipo', ['DEVOLUÇÃO DE VENDA', 'DEVOLUÇÃO DE COMPRA'])->sum('valor'),
            'listaNaturezas' => NaturezaOperacao::where('empresa_id', $empresa_id)->get(),
            'listaFiliais' => Filial::where('empresa_id', $empresa_id)->get(),
            'title' => 'Conferência de Notas'
        ];
    }

    public function index(Request $request)
    {
        return view('conferencia.index', $this->getDadosFiltrados($request));
    }

    // --- NOVA ROTA: EXCEL ---
    public function exportarExcel(Request $request)
    {
        $dados = $this->getDadosFiltrados($request)['notas'];
        $fileName = 'conferencia_notas_' . date('Y-m-d_H-i') . '.csv';
        
        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use($dados) {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // Formatação para o Excel aceitar acentos perfeitamente
            fputcsv($file, ['Data', 'Tipo', 'Número', 'Cliente / Fornecedor', 'Itens', 'Valor Nota', 'Recebido', 'Aberto', 'Situação', 'Status Financeiro'], ';');

            foreach ($dados as $n) {
                fputcsv($file, [
                    \Carbon\Carbon::parse($n['data'])->format('d/m/Y'),
                    $n['tipo'], $n['numero'], $n['cliente'], $n['qtd_itens'],
                    number_format($n['valor'], 2, ',', ''),
                    number_format($n['valor_recebido'], 2, ',', ''),
                    number_format($n['valor_aberto'], 2, ',', ''),
                    $n['situacao'],
                    $n['integrado'] ? 'Integrado' : ($n['bloqueia_integracao'] ? 'Não Integrável' : 'Pendente')
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- NOVA ROTA: PDF EM PAISAGEM ---
    public function imprimir(Request $request)
    {
        return view('conferencia.print', $this->getDadosFiltrados($request));
    }

    // (Mantenha a sua função integrarMassa() aqui exatamente como estava no último código)
    public function integrarMassa(Request $request)
    {
        $sessao = session('user_logged');
        $empresa_id = $sessao['empresa'];
        
        $notasSelecionadas = $request->notas_integrar ?? []; 

        $categoriaPadrao = \App\Models\CategoriaConta::where('empresa_id', $empresa_id)->first();
        $idCategoriaPadrao = $categoriaPadrao ? $categoriaPadrao->id : 1; 

        foreach ($notasSelecionadas as $notaChave) {
            list($tipo, $id) = explode('_', $notaChave);

            if ($tipo == 'NFe') {
                $venda = Venda::with('natureza')->find($id);
                if ($venda && !ContaReceber::where('venda_id', $venda->id)->exists()) {
                    
                    $catId = ($venda->natureza && $venda->natureza->categoria_conta_id) ? $venda->natureza->categoria_conta_id : $idCategoriaPadrao;

                    ContaReceber::create([
                        'venda_id' => $venda->id,
                        'cliente_id' => $venda->cliente_id,
                        'empresa_id' => $empresa_id,
                        'filial_id' => $venda->filial_id,
                        'valor_integral' => $venda->valor_total,
                        'valor_recebido' => 0,
                        'status' => false,
                        'data_vencimento' => $venda->data_emissao, 
                        'numero_nota_fiscal' => $venda->NfNumero,
                        'nf_numero' => $venda->NfNumero,
                        'nf_data_emissao' => $venda->data_emissao,
                        'categoria_id' => $catId, 
                        'referencia' => 'Vendas Ref. Pedido Nº ' . $venda->id . ' NFe ' . $venda->NfNumero,
                        'usuario_id' => get_id_user(), 
                    ]);
                }
            } elseif ($tipo == 'NFCe') {
                $vendaCaixa = VendaCaixa::with('natureza')->find($id);
                if ($vendaCaixa && !ContaReceber::where('venda_caixa_id', $vendaCaixa->id)->exists()) {
                    
                    $catId = ($vendaCaixa->natureza && $vendaCaixa->natureza->categoria_conta_id) ? $vendaCaixa->natureza->categoria_conta_id : $idCategoriaPadrao;

                    ContaReceber::create([
                        'venda_caixa_id' => $vendaCaixa->id,
                        'cliente_id' => $vendaCaixa->cliente_id,
                        'empresa_id' => $empresa_id,
                        'filial_id' => $vendaCaixa->filial_id,
                        'valor_integral' => $vendaCaixa->valor_total,
                        'valor_recebido' => 0,
                        'status' => false,
                        'data_vencimento' => $vendaCaixa->created_at->format('Y-m-d'),
                        'numero_nota_fiscal' => $vendaCaixa->NFcNumero,
                        'nf_numero' => $vendaCaixa->NFcNumero,
                        'nf_data_emissao' => $vendaCaixa->created_at->format('Y-m-d'),
                        'categoria_id' => $catId,
                        'referencia' => 'Vendas PDV Ref. Nº ' . $vendaCaixa->id . ' NFCe ' . $vendaCaixa->NFcNumero,
                        'usuario_id' => get_id_user(), 
                    ]);
                }
            } elseif ($tipo == 'CTE') { 
                $cte = Cte::with('natureza')->find($id);
                if ($cte && !ContaReceber::where('numero_nota_fiscal', $cte->cte_numero)->where('referencia', 'LIKE', '%CTe%')->exists()) {
                    $catId = ($cte->natureza && $cte->natureza->categoria_conta_id) ? $cte->natureza->categoria_conta_id : $idCategoriaPadrao;

                    ContaReceber::create([
                        'venda_id' => null,
                        'venda_caixa_id' => null,
                        'cliente_id' => $cte->remetente_id,
                        'empresa_id' => $empresa_id,
                        'filial_id' => $cte->filial_id,
                        'valor_integral' => $cte->valor_receber,
                        'valor_recebido' => 0,
                        'status' => false,
                        'data_vencimento' => $cte->data_registro, 
                        'numero_nota_fiscal' => $cte->cte_numero,
                        'nf_numero' => $cte->cte_numero,
                        'nf_data_emissao' => $cte->data_registro,
                        'categoria_id' => $catId,
                        'referencia' => 'Serviço de Transporte Ref. CTe Nº ' . $cte->cte_numero,
                        'usuario_id' => get_id_user(), 
                    ]);
                }
            }
        }

        return redirect()->back()->with('sucesso', 'Notas selecionadas foram integradas ao financeiro com sucesso!');
    }
}