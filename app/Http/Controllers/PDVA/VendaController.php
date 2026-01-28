<?php

namespace App\Http\Controllers\PDVA;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VendaCaixa;
use App\Models\Venda;
use App\Models\ItemVendaCaixa;
use App\Models\FaturaFrenteCaixa;
use App\Models\ConfigNota;
use App\Models\Produto;
use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\SangriaCaixa;
use App\Models\SuprimentoCaixa;
use App\Models\AberturaCaixa;
use Illuminate\Support\Facades\DB;
use App\Helpers\StockMove;
use App\Utils\ContaEmpresaUtil;
use App\Models\ContaEmpresa;
use App\Models\ItemContaEmpresa;

class VendaController extends Controller
{

    public function store(Request $request){
        try{

            $venda = DB::transaction(function () use ($request) {
                $empresa = Empresa::findOrFail($request->empresa_id);
                $cliente = null;
                if($request->cliente_id){
                    $cliente = Cliente::findOrFail($request->cliente_id);
                }

                $config = ConfigNota::
                where('empresa_id', $empresa->id)
                ->first();

                $dataVenda = [
                    'empresa_id' => $request->empresa_id,
                    'usuario_id' => $request->usuario_id,
                    'natureza_id' => $config->nat_op_padrao,
                    'valor_total' => __replace($request->total),
                    'desconto' => $request->desconto,
                    'acrescimo' => $request->acrescimo,
                    'troco' => 0,
                    'dinheiro_recebido' => $request->valor_recebido,
                    'tipo_pagamento' => sizeof($request->fatura) == 0 ? $request->tipo_pagamento : '99',
                    'bandeira_cartao' => isset($request->dados_cartao['bandeira']) ? $request->dados_cartao['bandeira'] : '',
                    'cAut_cartao' => isset($request->dados_cartao['codigo']) ? $request->dados_cartao['codigo'] : '',
                    'cnpj_cartao' => isset($request->dados_cartao['cnpj']) ? $request->dados_cartao['cnpj'] : '',
                    'cpf' => $request->cliente_cpf_cnpj ?? '',
                    'estado' => 'DISPONIVEL',
                    'numero_sequencial' => VendaCaixa::lastNumero($empresa->id)
                ];

                $venda = VendaCaixa::create($dataVenda);
                $stockMove = new StockMove();

                foreach($request->itens as $item){
                    $product = Produto::findOrFail($item['produto_id']);
                    $dataItem = [
                        'venda_caixa_id' => $venda->id,
                        'produto_id' => $product->id,
                        'quantidade' => $item['quantidade'],
                        'valor' => $item['valor_unitario'],
                        'observacao' => '',
                        'valor_custo' => $product->valor_compra,
                    ];

                    $itemVenda = ItemVendaCaixa::create($dataItem);

                    if ($product->gerenciar_estoque) {
                        $stockMove->downStock($product->id, $item['quantidade']);
                    }
                }


                if(sizeof($request->fatura) > 0){
                    foreach($request->fatura as $fat){
                        FaturaFrenteCaixa::create([
                            'venda_caixa_id' => $venda->id,
                            'forma_pagamento' => $fat['tipo'],
                            'data_vencimento' => $fat['data'],
                            'valor' => $fat['valor']
                        ]);
                    }
                }else{
                    FaturaFrenteCaixa::create([
                        'venda_caixa_id' => $venda->id,
                        'forma_pagamento' => $request->tipo_pagamento,
                        'data_vencimento' => date('Y-m-d'),
                        'valor' => $request->total
                    ]);
                }

                return $venda;
            });

            $venda = VendaCaixa::where('id', $venda->id)
            ->with(['itensApi', 'fatura', 'cliente'])
            ->first();

            foreach($venda->fatura as $f){
                $f->tipo_pagamento = VendaCaixa::getTipoPagamento($f->tipo_pagamento);
            }
            $venda->tipo_pagamento = VendaCaixa::getTipoPagamento($venda->tipo_pagamento);
            $venda->itens = $venda->itensApi;
            $venda->total = $venda->valor_total;
            $venda->estado = 'novo';
            if($venda->estado == 'REJEITADO'){
                $venda->estado = 'rejeitado';
            }elseif($venda->estado == 'CANCELADO'){
                $venda->estado = 'cancelado';
            }elseif($venda->estado == 'APROVADO'){
                $venda->estado = 'aprovado';
            }

            return response()->json($venda, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 403);
        }
    }

    public function bandeirasCartao(){
        $bandeiras = VendaCaixa::bandeiras();
        $data = [];

        array_push($data, [
            'id' => '',
            'nome' => 'Selecione'
        ]);
        foreach($bandeiras as $key => $b){
            array_push($data, [
                'id' => $key,
                'nome' => $b
            ]);
        }
        return response()->json($data, 200);
    }

    public function tiposPagamento(){
        $tipos = VendaCaixa::tiposPagamento();
        $data = [];

        array_push($data, [
            'id' => '',
            'nome' => 'Selecione'
        ]);
        foreach($tipos as $key => $t){
            array_push($data, [
                'id' => $key,
                'nome' => $t
            ]);
        }
        return response()->json($data, 200);
    }

    public function getCaixa(Request $request){
        $item = AberturaCaixa::where('usuario_id', $request->usuario_id)->where('status', 0)->first();
        return response()->json($item, 200);
    }

    public function contasEmpresa(Request $request){
        $data = ContaEmpresa::where('empresa_id', $request->empresa_id)
        ->with(['plano'])
        ->where('status', 1)->get();
        return response()->json($data, 200);
    }

    public function storeCaixa(Request $request){
        try{
            $usuario = Usuario::findOrFail($request->usuario_id);

            $ultimaVendaNfce = VendaCaixa::
            where('empresa_id', $usuario->empresa_id)
            ->orderBy('id', 'desc')->first();

            $ultimaVendaNfe = Venda::
            where('empresa_id', $usuario->empresa_id)
            ->orderBy('id', 'desc')->first();

            $data = [
                'usuario_id' => $request->usuario_id,
                'empresa_id' => $usuario->empresa_id,
                'valor' => $request->valor ? __replace($request->valor) : 0,
                'observacao' => $request->observacao ?? '',
                'status' => 0,
                'conta_id' => $request->conta_id ?? null,
                'primeira_venda_nfe' => $ultimaVendaNfe != null ? 
                $ultimaVendaNfe->id : 0,
                'primeira_venda_nfce' => $ultimaVendaNfce != null ? 
                $ultimaVendaNfce->id : 0,
            ];
            $item = AberturaCaixa::create($data);
            return response()->json($item, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 403);
        }
    }

    public function storeSangria(Request $request){
        try{
            $data = [
                'caixa_id' => $request->caixa_id,
                'valor' => __replace($request->valor),
                'observacao' => $request->observacao ?? '',
                'conta_id' => $request->conta_id ?? null,
            ];
            $item = SangriaCaixa::create($data);

            if($request->conta_id){
                $caixa = AberturaCaixa::findOrFail($request->caixa_id);
                $data = [
                    'conta_id' => $caixa->conta_id,
                    'descricao' => "Sangria de caixa",
                    'tipo_pagamento' => '01',
                    'valor' => __replace($request->valor),
                    'caixa_id' => $caixa->id,
                    'tipo' => 'saida'
                ];
                $itemContaEmpresa = ItemContaEmpresa::create($data);
                $this->utilConta->atualizaSaldo($itemContaEmpresa);

                $data = [
                    'conta_id' => $request->conta_id,
                    'descricao' => "Sangria de caixa",
                    'tipo_pagamento' => '01',   
                    'valor' => __replace($request->valor),
                    'caixa_id' => $caixa->id,
                    'tipo' => 'entrada'
                ];
                $itemContaEmpresa = ItemContaEmpresa::create($data);
                $this->utilConta->atualizaSaldo($itemContaEmpresa);
            }
            return response()->json($item, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 403);
        }
    }

    public function storeSuprimento(Request $request){
        try{
            $data = [
                'caixa_id' => $request->caixa_id,
                'valor' => __replace($request->valor),
                'observacao' => $request->observacao ?? '',
                'conta_empresa_id' => $request->conta_id ?? null,
                'tipo_pagamento' => $request->tipo_pagamento
            ];
            $item = SuprimentoCaixa::create($data);

            if($request->conta_id){
                $caixa = AberturaCaixa::findOrFail($request->caixa_id);
                $data = [
                    'conta_id' => $caixa->conta_id,
                    'descricao' => "Suprimento de caixa",
                    'tipo_pagamento' => $request->tipo_pagamento,
                    'valor' => __replace($request->valor),
                    'caixa_id' => $caixa->id,
                    'tipo' => 'entrada'
                ];
                $itemContaEmpresa = ItemContaEmpresa::create($data);
                $this->utilConta->atualizaSaldo($itemContaEmpresa);

                $data = [
                    'conta_id' => $request->conta_id,
                    'descricao' => "Suprimento de caixa",
                    'tipo_pagamento' => $request->tipo_pagamento,   
                    'valor' => __replace($request->valor),
                    'caixa_id' => $caixa->id,
                    'tipo' => 'saida'
                ];
                $itemContaEmpresa = ItemContaEmpresa::create($data);
                $this->utilConta->atualizaSaldo($itemContaEmpresa);
            }
            return response()->json($item, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 403);
        }
    }

    public function getVendasCaixa(Request $request){
        try{

            $caixa = AberturaCaixa::findOrFail($request->caixa_id);
            $usuario = Usuario::findOrFail($request->usuario_id);
            $config = ConfigNota::
            where('empresa_id', $usuario->empresa_id)
            ->first();

            $ultimaVendaCaixa = VendaCaixa::
            where('empresa_id', $caixa->empresa_id)
            ->orderBy('id', 'desc')->first();

            $ultimaVenda = Venda::
            where('empresa_id', $caixa->empresa_id)
            ->orderBy('id', 'desc')->first();

            $aberturaNfe = AberturaCaixa::where('ultima_venda_nfe', 0)
            ->where('empresa_id', $caixa->empresa_id)
            ->orderBy('id', 'desc')->first();

            $aberturaNfce = AberturaCaixa::where('ultima_venda_nfce', 0)
            ->where('empresa_id', $caixa->empresa_id)
            ->orderBy('id', 'desc')->first();

            $ultimaFechadaNfce = AberturaCaixa::where('ultima_venda_nfce', '>', 0)
            ->where('empresa_id', $caixa->empresa_id)
            ->orderBy('id', 'desc')->first();

            $ultimaFechadaNfe = AberturaCaixa::where('ultima_venda_nfe', '>', 0)
            ->where('empresa_id', $caixa->empresa_id)
            ->orderBy('id', 'desc')->first();

            $vendasPdv = VendaCaixa::
            whereBetween('id', [
                ($aberturaNfce != null ? $aberturaNfce->primeira_venda_nfce+1 : 0), 
                ($ultimaVendaCaixa ? $ultimaVendaCaixa->id : 100000)
            ])
            ->with(['itensApi', 'cliente', 'fatura'])
            ->where('empresa_id', $caixa->empresa_id)
            ->get();

            $vendas = Venda::
            whereBetween('id', [
                ($aberturaNfe != null ? $aberturaNfe->primeira_venda_nfe+1 : 0), 
                ($ultimaVenda ? $ultimaVenda->id : 100000)
            ])
            ->with(['itensApi', 'cliente'])
            ->where('empresa_id', $caixa->empresa_id)
            ->get();

            foreach($vendasPdv as $v){
                $v->tipo_pagamento = VendaCaixa::getTipoPagamento($v->tipo_pagamento);
                foreach($v->fatura as $f){
                    $f->tipo_pagamento = $v->tipo_pagamento;
                }
            }

            foreach($vendas as $v){
                $v->tipo_pagamento = VendaCaixa::getTipoPagamento($v->tipo_pagamento);
            }

            $dataVendas = $this->agrupaVendas($vendas, $vendasPdv);

            $vendas = $dataVendas;
            $suprimentos = SuprimentoCaixa::
            whereBetween('created_at', [
                $aberturaNfe->created_at, 
                date('Y-m-d H:i:s')
            ])
            ->where('empresa_id', $caixa->empresa_id)
            ->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
                return $q->where('usuario_id', $usuario);
            })
            ->get();

            $sangrias = SangriaCaixa::
            whereBetween('created_at', [
                $aberturaNfe->created_at, 
                date('Y-m-d H:i:s')
            ])
            ->where('empresa_id', $caixa->empresa_id)
            ->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
                return $q->where('usuario_id', $usuario);
            })
            ->get();

            $totalDeVendas = 0;
            foreach($dataVendas as $v){
                $totalDeVendas += $v->valor_total;
            }
            $totalSangrias = $sangrias->sum('valor');
            $totalSuprimentos = $suprimentos->sum('valor');

            $caixa->valor_abertura = $caixa->valor;
            $data = [
                'caixa' => $caixa,
                'vendas' => $vendas,
                'suprimentos' => $suprimentos,
                'sangrias' => $sangrias,
                'totalDeVendas' => $totalDeVendas,
                'totalSangrias' => $totalSangrias,
                'totalSuprimentos' => $totalSuprimentos,
            ];

            return response()->json($data, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 403);
        }
    }

    private function agrupaVendas($vendas, $vendasPdv){
        $data = [];
        foreach($vendasPdv as $v){
            array_push($data, $v);
        }
        foreach($vendas as $v){
            array_push($data, $v);
        }

        usort($data, function($a, $b){
            return $a['created_at'] < $b['created_at'] ? 1 : -1;
        });

        foreach($data as $v){
            $v->itens = $v->itensApi;
            $v->total = $v->valor_total;
            $v->estado = 'novo';
            if($v->estado == 'REJEITADO'){
                $v->estado = 'rejeitado';
            }elseif($v->estado == 'CANCELADO'){
                $v->estado = 'cancelado';
            }elseif($v->estado == 'APROVADO'){
                $v->estado = 'aprovado';
            }
        }
        return $data;
    }

    public function dataHome(Request $request){

        $empresa_id = $request->empresa_id;
        $usuario_id = $request->usuario_id;

        try{
            $produtos = Produto::where('empresa_id', $empresa_id)
            ->count();

            $clientes = Cliente::where('empresa_id', $empresa_id)
            ->count();

            $aberturaNfe = AberturaCaixa::where('ultima_venda_nfe', 0)
            ->where('empresa_id', $empresa_id)
            ->orderBy('id', 'desc')->first();

            $aberturaNfce = AberturaCaixa::where('ultima_venda_nfce', 0)
            ->where('empresa_id', $empresa_id)
            ->orderBy('id', 'desc')->first();

            $ultimaVendaCaixa = VendaCaixa::
            where('empresa_id', $request->empresa_id)
            ->orderBy('id', 'desc')->first();

            $ultimaVenda = Venda::
            where('empresa_id', $request->empresa_id)
            ->orderBy('id', 'desc')->first();

            $vendasPdv = VendaCaixa::
            whereBetween('id', [($aberturaNfce != null ? $aberturaNfce->primeira_venda_nfce +1 : 0), 
                $ultimaVendaCaixa ? $ultimaVendaCaixa->id : 999999])
            ->where('empresa_id', $empresa_id)
            ->sum('valor_total');

            $vendas = Venda::
            whereBetween('id', [($aberturaNfe != null ? $aberturaNfe->primeira_venda_nfe+1 : 0), 
                $ultimaVenda ? $ultimaVenda->id : 999999])
            ->where('empresa_id', $empresa_id)
            ->sum('valor_total');

            $chart = $this->dataChart($empresa_id, $usuario_id);
            $empresa = Empresa::findOrFail($empresa_id);

            $data = [
                'produtos' => $produtos,
                'clientes' => $clientes,
                'soma_vendas' => $vendasPdv + $vendas,
                'chart' => $chart,
                'empresa_ativa' => $empresa->status
            ];

            return response()->json($data, 200);
        }catch(\Exception $e){
            return response()->json($e->getLine(), 403);
        }
    }

    private function dataChart($empresa_id, $usuario_id){
        $horarios = [];
        $labels = [];
        $values = [];

        for($i=0; $i<=23; $i++){

            $hora = (($i<10) ? "0$i" : $i) . ":00";
            $horaFutura = (($i<10) ? "0$i" : $i) . ":59";
            $labels[] = $hora;

            $dataAtual = date('Y-m-d');
            $nfce = VendaCaixa::where('empresa_id', $empresa_id)
            ->whereBetween('created_at', [
                $dataAtual . " " . $hora,
                $dataAtual . " " . $horaFutura,
            ])
            ->sum('valor_total');

            $nfe = Venda::where('empresa_id', $empresa_id)->sum('valor_total');

            $values[] = $nfce;

        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];

    }

}
