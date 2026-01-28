<?php

namespace App\Http\Controllers\MP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeliveryConfig;
use App\Models\FuncionamentoDelivery;
use App\Models\CategoriaMasterDelivery;
use App\Models\BairroDeliveryLoja;
use App\Models\DestaqueDelivery;
use App\Models\AvaliacaoDelivery;
use App\Models\CurtidaLoja;

use App\Models\ClienteDelivery;
use App\Models\CodigoDesconto;
use App\Models\PedidoDelivery;

class LojaController extends Controller
{
    public function lojas(){

        $lojas = DeliveryConfig::
        where('status', 1)
        ->limit(30)
        ->with('categorias')
        ->with('galeria')
        ->get();

        $dia = date('w');
        $hora = date('H:i');
        $dia = FuncionamentoDelivery::getDia($dia);

        foreach($lojas as $l){

            $funcionamento = $l->getFuncionamento($dia);
            $l->fim_expediente = $funcionamento['fim_expediente'];
            $l->aberto = $funcionamento['aberto'];

        }
        return response()->json($lojas, 200);
    }

    public function search(Request $request){
        $lojas = DeliveryConfig::
        where('status', 1)
        ->where('nome', 'like', "%$request->search%")
        ->with('categorias')
        ->with('galeria')
        ->get();

        return response()->json($lojas, 200);
        
    }

    public function categorias(){
        $categorias = CategoriaMasterDelivery::all();
        return response()->json($categorias, 200);
    }

    public function getLoja(Request $request){

        $loja = DeliveryConfig::
        where('id', $request->loja_id)
        ->with('categorias')
        ->with('galeria')
        ->first();

        $dia = date('w');
        $hora = date('H:i');
        $dia = FuncionamentoDelivery::getDia($dia);

        $curtiu = CurtidaLoja::where([
            'empresa_id' => $loja->empresa_id,
            'cliente_id' => $request->cliente_id
        ])->first();

        $funcionamento = $loja->getFuncionamento($dia);
        $loja->fim_expediente = $funcionamento['fim_expediente'];
        $loja->aberto = $funcionamento['aberto'];
        $loja->curtiu = $curtiu != null ? true : false;
        $loja->funcionamentos = FuncionamentoDelivery::
        where('empresa_id', $loja->empresa_id)
        ->get();

        $loja->bairros = BairroDeliveryLoja::
        where('empresa_id', $loja->empresa_id)
        ->get();

        $loja->tipos_pay = $this->getTiposPagamento($loja);

        return response()->json($loja, 200);
    }

    private function getTiposPagamento($loja){
        $tipos = DeliveryConfig::tiposPagamento();
        $temp = [];
        $loja->tipos_pagamento = json_decode($loja->tipos_pagamento);
        foreach($tipos as $key => $t){
            if(sizeof($loja->tipos_pagamento) > 0 && in_array($key, $loja->tipos_pagamento)){
                array_push($temp, $t);
            }
        }
        return $temp;
    }

    public function banners(){
        try{
            $data = DestaqueDelivery::
            where('status', 1)
            ->orderBy('ordem', 'desc')
            ->get();

            foreach($data as $d){
                if($d->empresa_id){
                    $d->loja = $d->empresa->deliveryConfig;
                }
            }

            return response()->json($data, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function avaliacoes(Request $request){
        try{
            $data = AvaliacaoDelivery::
            with('cliente')
            ->with('empresa')
            ->with('pedido')
            ->when(!empty($request->lastItemId > 0), function ($q) use ($request) {
                return $q->where('id', '<', $request->lastItemId);
            })
            ->get();

            return response()->json($data, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function cupons(Request $request){
        try{
            $config = DeliveryConfig::find($request->loja_id);
            $data = CodigoDesconto::
            where('ativo', 1)
            ->where('cliente_id', $request->user_id)
            ->orWhere('cliente_id', null)
            ->get();

            $avaibles = [];
            foreach($data as $item){
                $ex = PedidoDelivery::
                where('cliente_id', $request->user_id)
                ->where('cupom_id', $item->id)
                ->exists();
                if(!$ex){
                    array_push($avaibles, $item);
                }
            }

            return response()->json($avaibles, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function like(Request $request){
        try{

            $config = DeliveryConfig::find($request->loja);
            $c = CurtidaLoja::where([
                'empresa_id' => $config->empresa_id,
                'cliente_id' => $request->user
            ])->first();

            if($c == null){
                CurtidaLoja::updateOrCreate([
                    'empresa_id' => $config->empresa_id,
                    'cliente_id' => $request->user
                ]);
            }else{
                $c->delete();
            }
            return response()->json("ok", 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }
}
