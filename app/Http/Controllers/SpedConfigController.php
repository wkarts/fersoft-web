<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SpedConfig;
use App\Models\SpedRegra1400;

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

    public function index(Request $request){
        $filial_id = $request->filial_id; 
        
        $item = SpedConfig::where('empresa_id', $this->empresa_id)
            ->when(empty($filial_id), function($q) { return $q->whereNull('filial_id'); })
            ->when(!empty($filial_id), function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->first();

        // Busca as regras já cadastradas para exibir na tabela
        $regras1400 = SpedRegra1400::where('empresa_id', $this->empresa_id)
            ->when(empty($filial_id), function($q) { return $q->whereNull('filial_id'); })
            ->when(!empty($filial_id), function($q) use ($filial_id) { return $q->where('filial_id', $filial_id); })
            ->get();

        // NOVO: Busca as filiais para o seletor visual
        $filiais = \App\Models\Filial::where('empresa_id', $this->empresa_id)->get();
        // NOVO: Passamos o filial_id atual para a view saber quem está selecionado
        $filialSelecionada = $filial_id;

        return view('sped.config', compact('item', 'regras1400', 'filiais', 'filialSelecionada'));
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