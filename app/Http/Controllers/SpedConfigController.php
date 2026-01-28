<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SpedConfig;

class SpedConfigController extends Controller
{

    protected $empresa_id = null;
    public function __construct(){
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
        $item = SpedConfig::where('empresa_id', $this->empresa_id)->first();
        return view('sped.config', compact('item'));
    }

    public function store(Request $request){
        $item = SpedConfig::where('empresa_id', $this->empresa_id)->first();
        try{
            if($item == null){
                SpedConfig::create($request->all());
                session()->flash("mensagem_sucesso", "Configuração criada com sucesso");
            }else{
                $item->fill($request->all())->save();
                session()->flash("mensagem_sucesso", "Configuração atualizada com sucesso");

            }
        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado " . $e->getMessage());
        }
        return redirect()->back();
    }
}
