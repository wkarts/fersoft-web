<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PedidoMesa;
use App\Models\ItemPedidoMesa;

class PedidoMesaController extends Controller
{
    public function index(){
        $data = PedidoMesa::where('empresa_id', request()->empresa_id)
        ->orderBy('id', 'desc')
        ->paginate(30);

        return view('pedidos_mesa/index', compact('data'))->with('title', 'Pedidos de mesa');
    }

    public function naoAutorizados(Request $request){
        try{
            $usr = session('user_logged');
            if(!isset($usr['id'])){
                return response()->json("", 401);
            }
            $pedido = PedidoMesa::where('estado', 'fechado')
            ->orderBy('id', 'asc')
            ->whereDate('created_at', date('Y-m-d'))
            ->first();
            if($pedido == null){
                return response()->json("", 200);
            }
            return view('pedidos_mesa/nao_autorizados', compact('pedido'));
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function alterarStatusPedido(Request $request){
        $pedido = PedidoMesa::findOrFail($request->pedido_id);

        $pedido->estado = $request->estado;
        $pedido->save();

        if($pedido->estado== 'aberto'){
            session()->flash('mensagem_sucesso', 'Atendimento autorizado!');
        }else{
            session()->flash('mensagem_erro', 'Atendimento recusado!');
        }
        return redirect()->back();
    }

    public function recusar($id){
        $pedido = PedidoMesa::findOrFail($id);
        try{
            $pedido->estado = 'recusado';
            $pedido->save();
            session()->flash('mensagem_sucesso', 'Atendimento recusado!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();

    }

    public function ver($id){
        $item = PedidoMesa::findOrFail($id);

        return view('pedidos_mesa.detalhe', compact('item'))
        ->with('title', 'Visualizando pedido');
    }

    public function alterarEstado(Request $request, $id){
        $pedido = PedidoMesa::findOrFail($id);
        try{
            $pedido->estado = $request->estado;
            $pedido->save();
            session()->flash('mensagem_sucesso', 'Estado alterado!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();

    }

    public function delete($id){
        $item = PedidoMesa::findOrFail($id);
        try{

            $item->itens()->delete();
            $item->delete();
            session()->flash('mensagem_sucesso', 'Registro removido!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect('/pedidosMesa');

    }

    public function controle(){

        return view('pedidos_mesa.controle')
        ->with('title', 'Controle de Pedidos');
    }

    public function itensPendentes(Request $request){
        $data = ItemPedidoMesa::select('item_pedido_mesas.*')
        ->join('pedido_mesas', 'pedido_mesas.id', '=', 'item_pedido_mesas.pedido_id')
        ->where('pedido_mesas.empresa_id', $request->empresa_id)
        ->where('item_pedido_mesas.status', 0)
        ->orderBy('item_pedido_mesas.created_at', 'asc')
        ->get();

        return view('pedidos_mesa.itens_pendentes', compact('data'));

        return response()->json($view, 200);
    }

    public function entregue($id){
        $item = ItemPedidoMesa::findOrFail($id);
        $item->status = 1;
        $item->save();
        session()->flash('mensagem_sucesso', 'Item marcado como entregue!');
        return redirect()->back();
    }
}
