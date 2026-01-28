<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryConfig;

class LojaController extends Controller
{
    public function index(){
        $lojas = DeliveryConfig::orderBy('id', 'desc')
        ->get();
        return view('lojas/index')
        ->with('lojas', $lojas)
        ->with('title', 'Lojas');
    }

    public function filtro(Request $request){
        $status = $request->status;
        $nome = $request->nome;
        $lojas = DeliveryConfig::orderBy('id', 'desc')
        ->select('delivery_configs.*')
        ->join('empresas', 'empresas.id', '=', 'delivery_configs.empresa_id')
        ->when(!empty($nome), function ($query) use ($nome) {
            return $query->where('empresas.nome', 'like', "%$nome%");
        })
        ->when($status != 'TODOS', function ($query) use ($status) {
            return $query->where('delivery_configs.status', $status);
        })
        ->get();
        return view('lojas/index')
        ->with('lojas', $lojas)
        ->with('nome', $nome)
        ->with('status', $status)
        ->with('title', 'Lojas');
    }

    public function alterarStatus($id){
        $delivery = DeliveryConfig::find($id);

        $delivery->status = !$delivery->status;
        $delivery->save();
        session()->flash("mensagem_sucesso", "Status alterado!");

        return redirect()->back();
    }

}
