<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendaCaixa;
use App\Models\Venda;
use App\Models\ConfigNota;
use App\Models\AberturaCaixa;
use App\Models\ContaEmpresa;
use App\Models\ItemContaEmpresa;
use Illuminate\Support\Facades\DB;
use App\Utils\ContaEmpresaUtil;

class CaixaEmpresaController extends Controller
{
    protected $util;
    protected $empresa_id = null;

    public function __construct(ContaEmpresaUtil $util){
        $this->util = $util;

        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }

    public function index(){
        // dados caixa aberto
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
        $usuario = get_id_user();

        $aberturaNfe = AberturaCaixa::where('ultima_venda_nfe', 0)
        ->where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
            return $q->where('usuario_id', $usuario);
        })
        ->orderBy('id', 'desc')->first();

        $aberturaNfce = AberturaCaixa::where('ultima_venda_nfce', 0)
        ->where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
            return $q->where('usuario_id', $usuario);
        })
        ->orderBy('id', 'desc')->first();

        $ultimaVendaCaixa = VendaCaixa::
        where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
            return $q->where('usuario_id', $usuario);
        })
        ->orderBy('id', 'desc')->first();

        $ultimaVenda = Venda::
        where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
            return $q->where('usuario_id', $usuario);
        })
        ->orderBy('id', 'desc')->first();

        $ultimaVendaCaixa = $ultimaVendaCaixa != null ? $ultimaVendaCaixa->id : 0;
        $ultimaVenda = $ultimaVenda != null ? $ultimaVenda->id : 0;

        $vendasPdv = VendaCaixa::whereBetween('id', [(
            $aberturaNfce != null ? $aberturaNfce->primeira_venda_nfce+1 : 0), $ultimaVendaCaixa])
        ->where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
            return $q->where('usuario_id', $usuario);
        })
        ->get();

        $vendas = Venda::whereBetween('id', [(
            $aberturaNfe != null ? $aberturaNfe->primeira_venda_nfe+1 : 0), $ultimaVenda])
        ->where('empresa_id', $this->empresa_id)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config, $usuario) {
            return $q->where('usuario_id', $usuario);
        })
        ->get();

        $vendas = $this->agrupaVendas($vendas, $vendasPdv);
        $somaTiposPagamento = $this->somaTiposPagamento($vendas);

        $abertura = $this->verificaAberturaCaixa();
        $contasEmpresa = ContaEmpresa::where('empresa_id', $this->empresa_id)
        ->where('status', 1)->get();
        return view('caixa_empresa.index', compact('abertura', 'somaTiposPagamento', 'contasEmpresa'));

    }

    private function agrupaVendas($vendas, $vendasPdv){
        $temp = [];
        foreach($vendas as $v){
            $v->tipo = 'VENDA';
            array_push($temp, $v);
        }

        foreach($vendasPdv as $v){
            $v->tipo = 'PDV';
            array_push($temp, $v);
        }

        return $temp;
    }

    private function somaTiposPagamento($vendas){
        $tipos = $this->preparaTipos();
        foreach($vendas as $v){
            if($v->estado != 'CANCELADO'){
                if($v->tipo_pagamento){
                    if($v->tipo_pagamento != 99){
                        if(isset($v->NFcNumero)){
                            if(!$v->rascunho && !$v->consignado){
                                $tipos[$v->tipo_pagamento] += $v->valor_total;
                            }
                        }else{
                            if(sizeof($v->duplicatas) > 0){
                                foreach($v->duplicatas as $d){
                                    $tipos[Venda::getTipoPagamentoNFe($d->tipo_pagamento)] += $d->valor_integral;
                                }
                            }else{
                                $tipos[$v->tipo_pagamento] += $v->valor_total - $v->desconto;
                            }
                        }
                    }else{
                        if($v->fatura){
                            foreach($v->fatura as $f){
                                $tipos[trim($f->forma_pagamento)] += $f->valor;
                            }
                        }
                    }
                }
            }
        }

        return $tipos;
    }

    private function preparaTipos(){
        $temp = [];
        foreach(VendaCaixa::tiposPagamento() as $key => $tp){
            $temp[$key] = 0;
        }
        return $temp;
    }

    private function verificaAberturaCaixa(){
        $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

        $ab = AberturaCaixa::where('ultima_venda_nfce', 0)
        ->where('empresa_id', $this->empresa_id)
        ->where('status', 0)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
            return $q->where('usuario_id', get_id_user());
        })
        ->orderBy('id', 'desc')->first();

        $ab2 = AberturaCaixa::where('ultima_venda_nfe', 0)
        ->where('empresa_id', $this->empresa_id)
        ->where('status', 0)
        ->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
            return $q->where('usuario_id', get_id_user());
        })
        ->orderBy('id', 'desc')->first();

        if($ab != null && $ab2 == null){
            return $ab->valor;
        }else if($ab == null && $ab2 != null){
            $ab2->valor;
        }else if($ab != null && $ab2 != null){
            if(strtotime($ab->created_at) > strtotime($ab2->created_at)){
                $ab->valor;
            }else{
                $ab2->valor;
            }
        }else{
            return -1;
        }
        if($ab != null) return $ab;
        else return -1;
    }

    public function store(Request $request){
        try{
            // dd($request->all());
            $result = DB::transaction(function () use ($request) {
                $abertura_id = $request->abertura_id;

                for($i=0; $i<sizeof($request->conta_id); $i++){

                    $data = [
                        'conta_id' => $request->conta_id[$i],
                        'descricao' => $request->descricao[$i] ? $request->descricao[$i] : "",
                        'tipo_pagamento' => $request->tipo_pagamento[$i],
                        'valor' => __replace($request->valor[$i]),
                        'caixa_id' => $abertura_id,
                        'tipo' => 'entrada'
                    ];
                    $itemContaEmpresa = ItemContaEmpresa::create($data);
                    $this->util->atualizaSaldo($itemContaEmpresa);
                }

                return true;
            });

            //fechar o caixa
            $abertura = AberturaCaixa::findOrFail($request->abertura_id);
            $ultimaVendaCaixa = VendaCaixa::
            where('empresa_id', $request->empresa_id)
            ->orderBy('id', 'desc')->first();

            $ultimaVenda = Venda::
            where('empresa_id', $request->empresa_id)
            ->orderBy('id', 'desc')->first();

            $abertura->ultima_venda_nfce = $ultimaVendaCaixa != null ? 
            $ultimaVendaCaixa->id : 0;
            $abertura->ultima_venda_nfe = $ultimaVenda != null ? $ultimaVenda->id : 0;
            $abertura->status = true;
            $abertura->save();

            session()->flash("mensagem_sucesso", "Caixa finalizado!");
            return redirect('/caixa');
        }catch(\Exception $e){
            echo $e->getMessage();
            die;
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
            return redirect()->back();
        }

    }
}
