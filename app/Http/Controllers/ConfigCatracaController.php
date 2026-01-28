<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfigCatraca;
use App\Models\Usuario;

class ConfigCatracaController extends Controller
{
    public function index(Request $request){
        $item = ConfigCatraca::
        where('empresa_id', $request->empresa_id)
        ->first();

        $usuarios = Usuario::where('empresa_id', $request->empresa_id)->get();

        return view('catraca_config.index', compact('item', 'usuarios'))
        ->with('title', 'Configuração de Catraca');
    }

    public function store(Request $request){
        try{
            $item = ConfigCatraca::
            where('empresa_id', $request->empresa_id)
            ->first();

            if($item == null){
                ConfigCatraca::create($request->all());
            }else{
                $item->fill($request->all())->save();
            }

            session()->flash('mensagem_sucesso', 'Configurado com sucesso!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }

        return redirect()->back();

    }
}
