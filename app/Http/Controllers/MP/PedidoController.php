<?php

namespace App\Http\Controllers\MP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClienteDelivery;
use App\Models\PedidoDelivery;
use App\Models\ItemPedidoDelivery;
use App\Models\ItemPedidoComplementoDelivery;
use App\Models\DeliveryConfig;
use App\Models\ItemPizzaPedido;
use App\Models\AvaliacaoDelivery;

class PedidoController extends Controller
{
    public function gerarPix(Request $request){
        try{
            $cliente = ClienteDelivery::findOrFail($request->usuario_id);
            $cliente->cpf = $request->cpf;
            $cliente->save();

            $config = DeliveryConfig::findOrFail($request->loja_id);

            $pedido = PedidoDelivery::create([
                'cliente_id' => $cliente->id,
                'valor_total' => $request->total,
                'valor_entrega' => $request->valor_entrega,
                'telefone' => '',
                'observacao' => $request->observacao ?? '',
                'forma_pagamento' => $request->forma_pagamento,
                'estado'=> 'novo',
                'motivoEstado'=> '',
                'endereco_id' => $request->endereco != null ? $request->endereco['id'] : null,
                'troco_para' => 0,
                'desconto' => __replace($request->desconto),
                'cupom_id' => $request->cupom ? $request->cupom['id'] : null,
                'app' => true,
                'empresa_id' => $config->empresa_id,
                'horario_entrega' => '',
                'horario_leitura' => '',
                'horario_cricao' => date('H:i'),
            ]);

            //salvar Itens

            foreach($request->cart as $item){

                $prod = [
                    'pedido_id' => $pedido->id,
                    'produto_id' => $item['id'],
                    'status' => 0,
                    'quantidade' => (float)$item['quantidade'],
                    'observacao' => $item['observacao'] ?? '',
                    'tamanho_id' => null, 
                    'valor' => (float)$item['valorDoItem']
                ];

                $tItem = ItemPedidoDelivery::create($prod);

                if($item['sabores']){
                    foreach($item['sabores'] as $s){
                        ItemPizzaPedido::create([
                            'item_pedido' => $tItem->id,
                            'sabor_id' => $s['nome']
                        ]);
                    }
                }

                if(isset($item['adicionais'])){
                    foreach($item['adicionais'] as $add){
                        ItemPedidoComplementoDelivery::create([
                            'item_pedido_id' => $tItem->id,
                            'complemento_id' => $add['id'],
                            'quantidade' => (float)$item['quantidade']

                        ]);
                    }
                }
            }

            \MercadoPago\SDK::setAccessToken($config->mercadopago_access_token);

            $payment = new \MercadoPago\Payment();

            $payment->transaction_amount = (float) number_format($request->total,2);
            $payment->description = "Pagamento do pedido " . $pedido->id;
            $payment->payment_method_id = "pix";

            $cep = str_replace("-", "", $config->cep);
            $cpf = preg_replace('/[^0-9]/', '', $request->cpf);

            $payment->payer = array(
                "email" => $cliente->email,
                "first_name" => $cliente->nome,
                "last_name" => $cliente->sobre_nome,
                "identification" => array(
                    "type" => 'CPF',
                    "number" => $cpf
                ),
                "address"=>  array(
                    "zip_code" => $cep,
                    "street_name" => $config->rua,
                    "street_number" => $config->numero,
                    "neighborhood" => $config->bairro,
                    "city" => $config->cidade->nome,
                    "federal_unit" => $config->cidade->uf
                )
            );

            $payment->save();

            if($payment->transaction_details){
                $pedido->transacao_id = $payment->id;
                $pedido->status_pagamento = $payment->status;
                $pedido->qr_code_base64 = $payment->point_of_interaction->transaction_data->qr_code_base64;
                $pedido->qr_code = $payment->point_of_interaction->transaction_data->qr_code;

                $pedido->save();
                return response()->json($pedido, 200);

            }else{
                return response()->json($payment->error, 404);
            }

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function consultaPix($id){
        try{

            $pedido = PedidoDelivery::findOrFail($id);

            $config = DeliveryConfig::where('empresa_id', $pedido->empresa_id)->first();
            \MercadoPago\SDK::setAccessToken($config->mercadopago_access_token);

            if($pedido){
                $payStatus = \MercadoPago\Payment::find_by_id($pedido->transacao_id);

                // $payStatus->status = "approved";
                if($payStatus->status == "approved"){

                    $pedido->status_pagamento = $payStatus->status;
                    $pedido->estado = 'aprovado';
                    $pedido->save();
                }
            }
            return response()->json($pedido->status_pagamento, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function ultimoPedidoParaConfirmar($userId){
        $pedido = PedidoDelivery::
        where('cliente_id', $userId)
        ->orderBy('id', 'desc')
        ->first();
        try{
            return response()->json($pedido, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }
    public function consultaPedidoLido($id){
        $pedido = PedidoDelivery::
        with('empresa')
        ->findOrFail($id);

        try{
            return response()->json($pedido, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function all(Request $request){
        try{
            $data = PedidoDelivery::
            with('itens')
            ->with('endereco')
            ->with('empresa')
            ->with('avaliacao')
            ->where('cliente_id', $request->cliente_id)
            ->limit(10)
            ->orderBy('id', 'desc')
            ->when(!empty($request->lastItemId > 0), function ($q) use ($request) {
                return $q->where('id', '<', $request->lastItemId);
            })
            ->get();

            $dataAtual = date("Y-m-d H:i:s");

            foreach($data as $item){
                $dif = strtotime($dataAtual) - strtotime($item->created_at);

                $dif = $dif/60/60;
                $item->is_avaliation = $dif < 4 && !$item->avaliacao && $item->horario_entrega != '';
            }

            return response()->json($data, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function countPedidos($id){
        try{
            $data = PedidoDelivery::where('cliente_id', $id)->count();

            return response()->json($data, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function gerarPedido(Request $request){
        try{
            $cliente = ClienteDelivery::findOrFail($request->usuario_id);

            $config = DeliveryConfig::findOrFail($request->loja_id);

            $pedido = PedidoDelivery::create([
                'cliente_id' => $cliente->id,
                'valor_total' => $request->total,
                'valor_entrega' => $request->valor_entrega,
                'telefone' => '',
                'observacao' => $request->observacao ?? '',
                'forma_pagamento' => $request->forma_pagamento,
                'estado'=> 'novo',
                'motivoEstado'=> '',
                'endereco_id' => $request->endereco != null ? $request->endereco['id'] : null,
                'troco_para' => 0,
                'desconto' => __replace($request->desconto),
                'cupom_id' => $request->cupom ? $request->cupom['id'] : null,
                'app' => true,
                'empresa_id' => $config->empresa_id,
                'horario_entrega' => '',
                'horario_leitura' => '',
                'horario_cricao' => date('H:i'),
            ]);

            //salvar Itens

            foreach($request->cart as $item){

                $prod = [
                    'pedido_id' => $pedido->id,
                    'produto_id' => $item['id'],
                    'status' => 0,
                    'quantidade' => (float)$item['quantidade'],
                    'observacao' => $item['observacao'] ?? '',
                    'tamanho_id' => $item['tamanho_id'], 
                    'valor' => (float)$item['valorDoItem']
                ];

                $tItem = ItemPedidoDelivery::create($prod);

                if($item['sabores']){
                    foreach($item['sabores'] as $s){
                        ItemPizzaPedido::create([
                            'item_pedido' => $tItem->id,
                            'sabor_id' => $s['sabor_id']
                        ]);
                    }
                }

                if(isset($item['adicionais'])){
                    foreach($item['adicionais'] as $add){
                        ItemPedidoComplementoDelivery::create([
                            'item_pedido_id' => $tItem->id,
                            'complemento_id' => $add['id'],
                            'quantidade' => (float)$item['quantidade']

                        ]);
                    }
                }
            }

            return response()->json($pedido, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function gerarPedidoCartao(Request $request){
        try{
            $cliente = ClienteDelivery::findOrFail($request->usuario_id);

            $config = DeliveryConfig::findOrFail($request->loja_id);

            $pedido = PedidoDelivery::create([
                'cliente_id' => $cliente->id,
                'valor_total' => $request->total,
                'valor_entrega' => $request->valor_entrega,
                'telefone' => '',
                'observacao' => $request->observacao ?? '',
                'forma_pagamento' => $request->forma_pagamento,
                'estado'=> 'novo',
                'motivoEstado'=> '',
                'endereco_id' => $request->endereco != null ? $request->endereco['id'] : null,
                'troco_para' => 0,
                'desconto' => 0,
                'cupom_id' => NULL,
                'app' => true,
                'empresa_id' => $config->empresa_id,
                'horario_entrega' => '',
                'horario_leitura' => '',
                'horario_cricao' => date('H:i'),
            ]);

            //salvar Itens

            foreach($request->cart as $item){

                $prod = [
                    'pedido_id' => $pedido->id,
                    'produto_id' => $item['id'],
                    'status' => 0,
                    'quantidade' => (float)$item['quantidade'],
                    'observacao' => $item['observacao'] ?? '',
                    'tamanho_id' => null, 
                    'valor' => (float)$item['valorDoItem']
                ];

                $tItem = ItemPedidoDelivery::create($prod);

                if(isset($item['adicionais'])){
                    foreach($item['adicionais'] as $add){
                        ItemPedidoComplementoDelivery::create([
                            'item_pedido_id' => $tItem->id,
                            'complemento_id' => $add['id'],
                            'quantidade' => (float)$item['quantidade']

                        ]);
                    }
                }
            }

            \MercadoPago\SDK::setAccessToken($config->mercadopago_access_token);
            $payment = new \MercadoPago\Payment();

            $payment->transaction_amount = (float) number_format($request->total,2);

            $payment->description = 'Pagamento de pedido #' . $pedido->id;
            $payment->token = $request->token;
            $payment->installments = 1;
            $payment->payment_method_id = $request->paymentMethodId;

            $payer = new \MercadoPago\Payer();
            $payer->email = $request->email;
            $payer->identification = array(
                "type" => 'CPF',
                "number" => $request->docNumber
            );

            $payment->payer = $payer;

            $payment->save();

            if($payment->error){

                $error = $this->trataErros($payment->error);
                return response()->json($payment->error, 401);

            }else{
                $pedido->transacao_id = $payment->id;
                $pedido->status_pagamento = $payment->status;
                $pedido->estado = 'aprovado';
                $pedido->save();
                return response()->json($pedido, 200);
            }

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    private function trataErros($error){

        foreach($error->causes as $e){
            if($e->code == 4033){
                return "Parcelas inválidas";
            }
        }
        // return $error;
        return "Erro desconhecido!";
    }

    public function avaliar(Request $request){
        try{
            $item = PedidoDelivery::findOrFail($request->pedido);

            $descPedido = "";

            foreach($item->itens as $key => $i){
                $descPedido .= number_format($i->quantidade, 0) . "x";
                $descPedido .= " " . $i->produto->produto->nome . " R$";
                $descPedido .= " " . number_format($i->valor, 2, ',', '.');
                if($key < sizeof($item->itens)-1){
                    $descPedido .= " | ";
                }
            }
            $data = [
                'pedido_id' => $item->id,
                'empresa_id' => $item->empresa_id,
                'cliente_id' => $item->cliente_id,
                'descricao_pedido' => $descPedido,
                'observacao_cliente' => $request->observacao ?? '',
                'nota' => $request->nota
            ];

            $avaliacao = AvaliacaoDelivery::create($data);

            $this->calcularMediaNota($item);
            return response()->json($avaliacao, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    private function calcularMediaNota($pedido){
        $intervalo = 90;

        $config = DeliveryConfig::where('empresa_id', $pedido->empresa_id)
        ->first();
        if($config != null){
            $somaAvaliacao = AvaliacaoDelivery::
            whereBetween('created_at', [
                date('Y-m-d',strtotime("-$intervalo days")) . " 00:00:00",
                date('Y-m-d') . " 23:59:59"
            ])
            ->where('empresa_id', $config->id)
            ->sum('nota');

            $countAvaliacao = AvaliacaoDelivery::
            whereBetween('created_at', [
                date('Y-m-d',strtotime("-$intervalo days")) . " 00:00:00",
                date('Y-m-d') . " 23:59:59"
            ])
            ->where('empresa_id', $config->id)
            ->count();

            if($countAvaliacao == 0){
                $config->avaliacao_media = $somaAvaliacao;
                $config->save();
            }else{
                $config->avaliacao_media = $somaAvaliacao/$countAvaliacao;
                $config->save();
            }
        }
    }
}
