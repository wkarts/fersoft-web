<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SuperAdminAlerta;

class SuperAdminController extends Controller
{
    public function alertas(Request $request){
        $data = SuperAdminAlerta::where('visto', 0)
        ->get();

        if(sizeof($data) == 0){
            return response()->json("", 200);
        }

        return view('super_admin.alertas', compact('data'));
    }

    public function alteraStatus($id){
        $item = SuperAdminAlerta::findOrFail($id);
        $item->visto = 1;
        $item->save();
        session()->flash("mensagem_sucesso", "Alerta registrado!");

        if($item->tipo == 'Cancelamento sistema'){
            return redirect('/cancelamento-super');
        }else if($item->tipo == 'Nova empresa' || $item->tipo == 'Novo contador parceiro'){
            return redirect('/empresas');
        }
        else if($item->tipo == 'Ativação de plano'){
            return redirect('/planosPendentes');
        }
        return redirect('/errosLog');

    }

    public function alteraTodos(){
        SuperAdminAlerta::where('visto', 0)->update(['visto' => 1]);
        session()->flash("mensagem_sucesso", "Alertas registrados!");
        return redirect()->back();
    }
}
