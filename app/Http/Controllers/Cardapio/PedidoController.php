<?php

namespace App\Http\Controllers\Cardapio;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PedidoMesa;
use App\Models\Mesa;
use App\Models\ItemPedidoMesa;
use App\Models\ItemPedidoMesaPizza;
use App\Models\ItemPedidoMesaComplemento;
use Illuminate\Support\Str;
use DB;

class PedidoController extends Controller
{
    public function openTable(Request $request){
        $nome = $request->nome;
        $mesa_token = $request->mesa_token;
        $mesa = Mesa::where('token', $mesa_token)->first();
        if($mesa == null){
            return response()->json("mesa não encontrada!", 401);
        }
        $celular = $request->celular;
        try{
            $uid = Str::random(40);

            $data = [
                'uid' => $uid,
                'nome_cliente' => $nome,
                'mesa_id' => $mesa->id,
                'telefone_cliente' => $celular,
                'empresa_id' => $request->empresa_id
            ];

            $pedido = PedidoMesa::create($data);
            return response()->json($pedido, 200);

        }catch(\Exception $e){
            return response()->json("err: " . $e->getMessage(), 401);
        }

    }

    public function getPedido(Request $request){
        $uid = $request->uid;
        $item = PedidoMesa::where('uid', $uid)
        ->with('itens')
        ->first();
        if($item == null){
            return response()->json("nada encontrado", 403);
        }
        return response()->json($item, 200);
    }

    public function save(Request $request){

        $result = DB::transaction(function () use ($request) {
            $pedido = PedidoMesa::where('uid', $request->uid)->first();
            $itens = $request->itens;

            $total = 0;
            foreach($itens as $item){
                $total += (float)__replace($item['valor'])*(float)$item['qtd'];
            }
            $pedido->valor_total = $total;
            $pedido->observacao = $request->observacao ?? '';
            $pedido->save();
            foreach($itens as $item){
                $dataItem = [
                    'pedido_id' => $pedido->id,
                    'produto_id' => $item['id'],
                    'status' => 0,
                    'quantidade' => __replace($item['qtd']),
                    'observacao' => $item['observacao'] ?? '',
                    'tamanho_id' => $item['tamanho_id'] ?? null,
                    'valor' => __replace($item['valor'])

                ];
                // return response()->json($dataItem, 200);

                $tItem = ItemPedidoMesa::create($dataItem);

                if(isset($item['adicionais'])){
                    foreach($item['adicionais'] as $add){
                        ItemPedidoMesaComplemento::create([
                            'item_pedido_id' => $tItem->id,
                            'complemento_id' => $add['id'],
                            'quantidade' => __replace($item['qtd'])
                        ]);
                    }
                }

                if($item['sabores']){
                    foreach($item['sabores'] as $s){
                        ItemPedidoMesaPizza::create([
                            'item_pedido' => $tItem->id,
                            'sabor_id' => $s
                        ]);
                    }
                }
            }

            return $pedido;
        });

        // return response()->json($result, 401);
        return response()->json($result, 200);

    }

    public function mesas(Request $request){
        $data = Mesa::where('empresa_id', $request->empresa_id)->get();
        return response()->json($data, 200);

    }
}
