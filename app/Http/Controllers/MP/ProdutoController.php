<?php

namespace App\Http\Controllers\MP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeliveryConfig;
use App\Models\CategoriaProdutoDelivery;
use App\Models\ComplementoDelivery;

class ProdutoController extends Controller
{
    public function categorias($id){
        $delivery = DeliveryConfig::find($id);

        $categorias = CategoriaProdutoDelivery::
        where('empresa_id', $delivery->empresa_id)
        ->with('produtos')
        ->get();

        return response()->json($categorias, 200);
    }

    public function adicionaisDeProduto(Request $request){
        $delivery = DeliveryConfig::find($request->loja_id);

        $temp = ComplementoDelivery::
        where('empresa_id', $delivery->empresa_id)
        ->get();

        $data = [];
        foreach($temp as $t){
            $cat = $t->categoria ? json_decode($t->categoria) : [];
            if(in_array($request->categoria_id, $cat)){
                array_push($data, $t);
            }
        }

        return response()->json($data, 200);
    }
}
