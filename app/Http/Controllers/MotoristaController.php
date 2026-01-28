<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Motorista;

class MotoristaController extends Controller
{
    public function __construct(){
        $this->middleware(function ($request, $next) {
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }

    public function index(Request $request){

        $data = Motorista::
        where('empresa_id', $request->empresa_id)
        ->get();

        return view('motoristas.index', compact('data'));
    }

    public function create(){
        return view('motoristas.register')
        ->with('title', 'Cadastrar Motorista');
    }

    public function edit($id){

        $item = Motorista::findOrFail($id);
        return view('motoristas.register', compact('item'))
        ->with('title', 'Editar Motorista');
    }

    public function store(Request $request){
        try{
            Motorista::create($request->all());
            session()->flash("mensagem_sucesso", "Motorista cadastrado");
            return redirect()->route('motoristas.index');
        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
            return redirect()->back();
        }
    }

    public function update(Request $request, $id){
        try{
            $item = Motorista::findOrFail($id);
            $item->fill($request->all())->save();

            session()->flash("mensagem_sucesso", "Motorista atualizado");
            return redirect()->route('motoristas.index');
        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
            return redirect()->back();
        }
    }

    public function destroy($id){
        try{

            $item = Motorista::findOrFail($id);
            $item->delete();
            session()->flash('mensagem_sucesso', 'Motorista removido!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }
}
