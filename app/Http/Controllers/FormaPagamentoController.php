<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormaPagamento;

class FormaPagamentoController extends Controller
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

        $formas = FormaPagamento::
        where('empresa_id', $request->empresa_id)
        ->get();

        $tiposNaoDelete = ['a_vista', '30_dias', 'conta_crediario', 'personalizado'];

        return view('formas_pagamento/list')
        ->with('formas', $formas)
        ->with('tiposNaoDelete', $tiposNaoDelete)
        ->with('title', 'Formas de pagamento');
    }

    public function new(){
        return view('formas_pagamento/register')
        ->with('podeEditar', true)
        ->with('title', 'Cadastrar forma de pagamento');
    }

    public function save(Request $request){

        $this->_validate($request);
        try{
            $request->merge(['taxa' => __replace($request->taxa)]);
            $request->merge(['chave' => strtolower($this->criaChave($request->nome))]);
            $request->merge(['status' => $request->status ? 1 : 0 ]);
            $request->merge(['infos' => $request->infos ?? '' ]);

            FormaPagamento::create($request->all());
            session()->flash('mensagem_sucesso', 'Forma de pagamento cadastrada com sucesso!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', $e->getMessage());
        }
        return redirect('/formasPagamento');
    }

    public function edit($id){

        $forma = FormaPagamento::find($id); 

        $tiposNaoEdit = ['a_vista', '30_dias', 'conta_crediario', 'personalizado'];
        $podeEditar = true;
        if(in_array($forma->chave, $tiposNaoEdit)) {
            $podeEditar = false;
        }

        if(valida_objeto($forma)){
            return view('formas_pagamento/register')
            ->with('forma', $forma)
            ->with('podeEditar', $podeEditar)
            ->with('title', 'Editar forma de pagamento');
        }else{
            return redirect('/403');
        }

    }

    private function criaChave($chave){
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/", "/(ç)/"),explode(" ","a A e E i I o O u U n N c"),$chave);
    }

    public function update(Request $request){
        $categoria = new FormaPagamento();

        $id = $request->input('id');
        $resp = $categoria
        ->where('id', $id)->first(); 

        $this->_validate($request);

        if($request->podeEditar){
            $resp->nome = $request->nome;
            $resp->prazo_dias = $request->prazo_dias;
        }
        $resp->taxa = __replace($request->taxa);
        $resp->tipo_taxa = $request->tipo_taxa;
        $resp->infos = $request->infos ?? '';
        $resp->status = $request->status ? 1 : 0;
        // $resp->chave = strtolower($this->criaChave($request->nome));

        $result = $resp->save();
        if($result){
            session()->flash('mensagem_sucesso', 'Forma de pagamento editada com sucesso!');
        }else{
            session()->flash('mensagem_erro', 'Erro ao editar forma de pagamento!');
        }

        return redirect('/formasPagamento'); 
    }

    public function delete($id){
        try{
            $forma = FormaPagamento
            ::where('id', $id)
            ->first();

            $tiposNaoDelete = ['a_vista', '30_dias', 'conta_crediario', 'personalizado'];
            if(in_array($forma->chave, $tiposNaoDelete)) {
                session()->flash('mensagem_erro', 'Não permitido!');
                return redirect()->back();
            }

            if(valida_objeto($forma)){
                if($forma->delete()){
                    session()->flash('mensagem_sucesso', 'Registro removido!');
                }else{

                    session()->flash('mensagem_erro', 'Erro!');
                }
                return redirect('/formasPagamento');
            }else{
                return redirect('403');
            }
        }catch(\Exception $e){
            return view('errors.sql')
            ->with('title', 'Erro ao deletar forma de pagamento')
            ->with('motivo', $e->getMessage());
        }
    }

    private function _validate(Request $request){
        $rules = [
            'nome' => $request->podeEditar ? 'required|max:40' : '',
            'tipo_taxa' => 'required',
            'taxa' => 'required',
            'infos' => 'max:100',
            'prazo_dias' => $request->podeEditar ? 'required' : ''
        ];

        $messages = [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => '40 caracteres maximos permitidos.',
            'infos.max' => '100 caracteres maximos permitidos.',
            'tipo_taxa.required' => 'Campo obrigatório.',
            'taxa.required' => 'Campo obrigatório.',
            'prazo_dias.required' => 'Campo obrigatório.',

        ];
        $this->validate($request, $rules, $messages);
    }

}
