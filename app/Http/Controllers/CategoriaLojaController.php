<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoriaLojaSegmento;
use App\Models\CategoriaMasterDelivery;
use App\Models\DeliveryConfig;

class CategoriaLojaController extends Controller
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
        $categorias = CategoriaMasterDelivery::all();
        foreach($categorias as $c){
            $c->marcado = false;
            $delivery = DeliveryConfig::
            where('empresa_id', $this->empresa_id)
            ->first();

            if($delivery != null){
                $temp = CategoriaLojaSegmento::
                where('loja_id', $delivery->id)
                ->where('categoria_id', $c->id)
                ->first();

                if($temp != null){
                    $c->marcado = true;
                }
            }
        }

        return view('categoriasLoja/index')
        ->with('title', 'Categorias de loja')
        ->with('categorias', $categorias);
    }

    public function alterarStatus($id){
        // $categoria = CategoriaMasterDelivery::find($id);
        $delivery = DeliveryConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        if($delivery == null){
            session()->flash('mensagem_erro', 'Configure o delivery');
            return redirect('/configDelivery');
        }

        $cat = CategoriaLojaSegmento::
        where('categoria_id', $id)
        ->where('loja_id', $delivery->id)
        ->first();

        if($cat == null){
            CategoriaLojaSegmento::create([
                'categoria_id' => $id,
                'loja_id' => $delivery->id
            ]);

            session()->flash('mensagem_sucesso', 'Categoria adicionada');
        }else{
            $cat->delete();
            session()->flash('mensagem_erro', 'Categoria removida');
        }
        return redirect()->back();
    }
}
