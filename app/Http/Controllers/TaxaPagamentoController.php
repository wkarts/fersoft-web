<?php

namespace App\Http\Controllers;
use App\Models\TaxaPagamento;

use Illuminate\Http\Request;

class TaxaPagamentoController extends Controller
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

        $data = TaxaPagamento::
        where('empresa_id', $request->empresa_id)
        ->paginate(30);

        return view('taxas_pagamento/index')
        ->with('data', $data)
        ->with('title', 'Taxas de pagamento');
    }

    public function create(){
        return view('taxas_pagamento/register')
        ->with('title', 'Cadastrar taxa de pagamento');
    }

    public function edit($id){
        $item = TaxaPagamento::findOrFail($id);
        return view('taxas_pagamento/register')
        ->with('item', $item)
        ->with('title', 'Editar taxa de pagamento');
    }

    public function store(Request $request){

        $this->_validate($request);
        try{
            $request->merge(['taxa' => __replace($request->taxa)]);

            TaxaPagamento::create($request->all());
            session()->flash('mensagem_sucesso', 'Taxa de pagamento cadastrada com sucesso!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', $e->getMessage());
        }
        return redirect('/taxas-pagamento');
    }

    public function update(Request $request, $id){
        $this->_validate($request);
        try{
            $item = TaxaPagamento::findOrFail($id);
            $request->merge(['taxa' => __replace($request->taxa)]);

            $item->fill($request->all())->save();
            session()->flash('mensagem_sucesso', 'Taxa de pagamento editada com sucesso!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', $e->getMessage());
        }
        return redirect('/taxas-pagamento');
    }

    private function _validate(Request $request){
        $rules = [
            'tipo_pagamento' => 'required',
            'taxa' => 'required',
        ];

        $messages = [
            'tipo_pagamento.required' => 'O campo tipo é obrigatório.',
            'taxa.required' => 'Campo obrigatório.',

        ];
        $this->validate($request, $rules, $messages);
    }

    public function destroy($id){
        try{

            $item = TaxaPagamento::findOrFail($id);

            $item->delete();
            session()->flash('mensagem_sucesso', 'Registro removido!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }
}
