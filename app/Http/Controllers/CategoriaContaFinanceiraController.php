<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoriaContaFinanceira;
use App\Models\SubCategoriaContaFinanceira;

class CategoriaContaFinanceiraController extends Controller
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
        $data = CategoriaContaFinanceira::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('categoria_conta_financeira/index')
        ->with('data', $data)
        ->with('title', 'Categorias de Conta Financeira');
    }

    public function new(){
        return view('categoria_conta_financeira/register')
        ->with('title', 'Cadastrar Categoria');
    }

    public function save(Request $request){
        $categoria = new CategoriaContaFinanceira();
        $this->_validate($request);

        $result = $categoria->create($request->all());

        if($result){
            session()->flash("mensagem_sucesso", "Categoria cadastrada com sucesso.");
        }else{
            session()->flash('mensagem_erro', 'Erro ao cadastrar categoria.');
        }

        return redirect('/categoriaContaFinanceira');
    }

    public function edit($id){
        $categoria = new CategoriaContaFinanceira(); 

        $resp = $categoria
        ->where('id', $id)->first();  

        if(valida_objeto($resp)){

            return view('categoria_conta_financeira/register')
            ->with('categoria', $resp)
            ->with('title', 'Editar Categoria de Conta Financeira');
        }else{
            return redirect('/403');
        }

    }

    public function update(Request $request){
        $categoria = new CategoriaContaFinanceira();

        $id = $request->input('id');
        $resp = $categoria
        ->where('id', $id)->first(); 

        $this->_validate($request);


        $resp->nome = $request->input('nome');
        $resp->tipo = $request->input('tipo');

        $result = $resp->save();
        if($result){
            session()->flash('mensagem_sucesso', 'Categoria editada com sucesso!');
        }else{
            session()->flash('mensagem_erro', 'Erro ao editar categoria!');
        }

        return redirect('/categoriaContaFinanceira'); 
    }

    public function delete($id){
        $resp = CategoriaContaFinanceira
        ::where('id', $id)
        ->first();
        if(valida_objeto($resp)){

            if($resp->delete()){
                session()->flash('mensagem_sucesso', 'Registro removido!');
            }else{
                session()->flash('mensagem_erro', 'Erro!');
            }
            return redirect('/categoriaContaFinanceira');
        }else{
            return redirect('/403');
        }
    }

    private function _validate(Request $request){
        $rules = [
            'nome' => 'required|max:50',
            'tipo' => 'required',
        ];

        $messages = [
            'nome.required' => 'O campo nome é obrigatório.',
            'tipo.required' => 'O campo tipo é obrigatório.',
            'nome.max' => '50 caracteres maximos permitidos.'
        ];
        $this->validate($request, $rules, $messages);
    }

    private function _validateSub(Request $request){
        $rules = [
            'nome' => 'required|max:50',
        ];

        $messages = [
            'nome.required' => 'O campo nome é obrigatório.',
            'nome.max' => '50 caracteres maximos permitidos.'
        ];
        $this->validate($request, $rules, $messages);
    }


    public function newSub($id){
        $categoria = new CategoriaContaFinanceira(); 

        $resp = $categoria
        ->where('id', $id)->first();  

        if(valida_objeto($resp)){

            return view('categoria_conta_financeira/register_sub')
            ->with('categoria', $resp)
            ->with('title', 'Nova Sub-Categoria de Conta Financeira');
        }else{
            return redirect('/403');
        }

    }

    public function editSub($id){

        $resp = SubCategoriaContaFinanceira::
        where('id', $id)->first();

        if(valida_objeto($resp->categoria)){

            return view('categoria_conta_financeira/register_sub')
            ->with('categoria', $resp->categoria)
            ->with('sub', $resp)
            ->with('title', 'Editar Sub-Categoria de Conta Financeira');
        }else{
            return redirect('/403');
        }

    }

    public function saveSub(Request $request){
        $this->_validateSub($request);

        try{
            SubCategoriaContaFinanceira::create([
                'categoria_id' => $request->categoria_id,
                'nome' => $request->nome
            ]);

            session()->flash('mensagem_sucesso', 'SubCategoria cadastrada com sucesso!');
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Erro ao cadastrar categoria!');
        }

        return redirect('/categoriaContaFinanceira'); 
    }

    public function updateSub(Request $request){
        $categoria = new SubCategoriaContaFinanceira();

        $id = $request->input('id');
        $resp = $categoria
        ->where('id', $id)->first(); 

        $this->_validateSub($request);

        $resp->nome = $request->input('nome');

        $result = $resp->save();
        if($result){
            session()->flash('mensagem_sucesso', 'SubCategoria editada com sucesso!');
        }else{
            session()->flash('mensagem_erro', 'Erro ao editar subcategoria!');
        }

        return redirect('/categoriaContaFinanceira'); 
    }

}
