<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aviso;

class AlertaController extends Controller
{
    public function __construct(){
        $this->middleware(function ($request, $next) {
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }

            if($request->ajax()){
                return $next($request);
            }
            if(!$value['super']){
                return redirect('/graficos');
            }
            return $next($request);
        });
    }

    public function index(){
        $data = Aviso::orderBy('id', 'desc')
        ->get();

        return view('alertas/index')
        ->with('data', $data)
        ->with('title', 'Alertas');
    }

    public function create(){
        return view('alertas/form')
        ->with('title', 'Cadastrar alerta');
    }

    public function edit($id){
        $item = Aviso::findOrFail($id);
        return view('alertas/form')
        ->with('item', $item)
        ->with('title', 'Editar alerta');
    }

    public function store(Request $request){
        $this->_validate($request);

        try{
            $request->merge(['status' => $request->status ? 1 : 0]);
            Aviso::create($request->all());
            session()->flash('mensagem_sucesso', 'Alerta adicionado!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect('/alertas');
    }

    public function update(Request $request, $id){
        $this->_validate($request);

        try{
            $item = Aviso::findOrFail($id);
            $request->merge(['status' => $request->status ? 1 : 0]);

            $item->fill($request->all())->save();
            session()->flash('mensagem_sucesso', 'Alerta atualizada!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect('/alertas');
    }

    public function destroy($id){
        try{

            $item = Aviso::findOrFail($id);
            // $item->respostas()->delete();
            $item->delete();
            session()->flash('mensagem_sucesso', 'Aviso removido!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }

    private function _validate(Request $request){

        $rules = [
            'titulo' => 'required|max:50',
            'texto' => 'required',
        ];

        $messages = [
            'titulo.required' => 'Campo obrigatório.',
            'titulo.max' => '50 caracteres maximos permitidos.',
            'texto.required' => 'Campo obrigatório.'
        ];
        $this->validate($request, $rules, $messages);
    }

    public function list($id){
        $data = Aviso::findOrFail($id);
        return view('alertas/visualizacoes')
        ->with('data', $data)
        ->with('title', 'Visualizaçoes de alerta');
    }

}
