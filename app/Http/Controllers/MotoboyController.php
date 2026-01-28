<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Motoboy;
use App\Models\PedidoMotoboy;
use Illuminate\Support\Facades\DB;

class MotoboyController extends Controller
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
        $data = Motoboy::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('motoboys/index')
        ->with('data', $data)
        ->with('title', 'Motoboys');
    }

    public function create(){
        return view('motoboys/register')
        ->with('title', 'Cadastrar Motoboy');
    }

    public function edit($id){
        $item = Motoboy::findOrFail($id);
        if(valida_objeto($item)){
            return view('motoboys/register', compact('item'))
            ->with('title', 'Editar Motoboy');
        }else{
            return redirect('403');
        }
    }

    public function store(Request $request){
        $this->_validate($request);
        try{
            $request->merge([
                'valor_entrega_padrao' => __replace($request->valor_entrega_padrao)
            ]);

            Motoboy::create($request->all());
            session()->flash('mensagem_sucesso', 'Registro criado!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao salvar: ' . $e->getMessage());
        }
        return redirect('/motoboys');
    }

    public function update(Request $request){
        $this->_validate($request);
        try{
            $item = Motoboy::findOrFail($request->id);
            $request->merge([
                'valor_entrega_padrao' => __replace($request->valor_entrega_padrao)
            ]);

            $item->fill($request->all())->update();
            session()->flash('mensagem_sucesso', 'Registro atualizado!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao salvar: ' . $e->getMessage());
        }
        return redirect('/motoboys');
    }

    private function _validate(Request $request){
        $rules = [
            'nome' => 'required|max:60',
            'celular' => 'required|max:15',
            'rua' => 'required|max:60',
            'bairro' => 'required|max:30',
            'numero' => 'required|max:10',
            'valor_entrega_padrao' => 'required',
        ];

        $messages = [
            'nome.required' => 'Campo obrigatório.',
            'nome.max' => '60 caracteres maximos permitidos.',
            'celular.required' => 'Campo obrigatório.',
            'celular.max' => '15 caracteres maximos permitidos.',
            'rua.required' => 'Campo obrigatório.',
            'rua.max' => '60 caracteres maximos permitidos.',
            'bairro.required' => 'Campo obrigatório.',
            'bairro.max' => '30 caracteres maximos permitidos.',
            'numero.required' => 'Campo obrigatório.',
            'numero.max' => '10 caracteres maximos permitidos.',
            'valor_entrega_padrao.required' => 'Campo obrigatório.',
        ];
        $this->validate($request, $rules, $messages);
    }

    public function delete($id){
        $item = Motoboy::findOrFail($id);
        if(valida_objeto($item)){
            $item->delete();
            session()->flash('mensagem_sucesso', 'Registro removido!');
            return redirect('/motoboys');

        }else{
            return redirect('403');
        }
    }

    public function entregas($id){
        $item = Motoboy::findOrFail($id);
        if(valida_objeto($item)){
            $entregas = PedidoMotoboy::where('motoboy_id', $id)->get();
            return view('motoboys/entregas', compact('item', 'entregas'))
            ->with('title', 'Entregas Motoboy');
        }else{
            return redirect('403');
        }
    }

    public function updatEntregas(Request $request){
        try{
            DB::transaction(function () use ($request) {
                for($i=0; $i<sizeof($request->check); $i++){
                    $pedido = PedidoMotoboy::findOrFail($request->check[$i]);

                    $pedido->status_pagamento = 1;
                    $pedido->save();
                }
                return true;
            });
            session()->flash('mensagem_sucesso', 'entregas atualizadas!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'aldo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }
}
