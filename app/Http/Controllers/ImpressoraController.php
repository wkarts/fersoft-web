<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Impressora;


class ImpressoraController extends Controller
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
        $data = Impressora::
        where('empresa_id', $this->empresa_id)
        ->get();
        return view('impressoras/index')
        ->with('data', $data)
        ->with('title', 'Impressoras');
    }

    public function new(){
        return view('impressoras/register')
        ->with('title', 'Cadastrar Impressora');
    }

    public function edit($id){
        $item = Impressora::findOrFail($id);
        return view('impressoras/register')
        ->with('item', $item)
        ->with('title', 'Editar Impressora');
    }

    public function save(Request $request){

        $this->_validate($request);

        $request->merge([ 'status' => $request->input('status') ? true : false ]);
        $request->merge([ 'padrao' => $request->input('padrao') ? true : false ]);

        if($request->input('padrao')){
            $data = Impressora::
            where('empresa_id', $this->empresa_id)
            ->where('padrao', 1)
            ->get();

            foreach($data as $d){
                $d->padrao = false;
                $d->save();
            }
        }
        
        $result = Impressora::create($request->all());

        if($result){
            session()->flash("mensagem_sucesso", "Impressora cadastrada com sucesso!");
        }else{
            session()->flash('mensagem_erro', 'Erro ao cadastrar impressora!');
        }
        
        return redirect('/impressoras');
    }

    public function update(Request $request){
        try{
            $this->_validate($request);

            $item = Impressora::findOrFail($request->id);
            $request->merge([ 'status' => $request->input('status') ? true : false ]);
            $request->merge([ 'padrao' => $request->input('padrao') ? true : false ]);

            if($request->input('padrao')){
                $data = Impressora::
                where('empresa_id', $this->empresa_id)
                ->where('padrao', 1)
                ->get();

                foreach($data as $d){
                    $d->padrao = false;
                    $d->save();
                }
            }

            $item->fill($request->all())->save();


            session()->flash("mensagem_sucesso", "Impressora editada com sucesso!");
        }catch(\Exception $e){

            session()->flash('mensagem_erro', 'Erro ao editar impressora!');
        }
        return redirect('/impressoras');
    }

    private function _validate(Request $request){
        $rules = [
            'descricao' => 'required|max:50',
            'porta' => 'required|max:20',
        ];

        $messages = [
            'descricao.required' => 'O campo descrição é obrigatório.',
            'porta.required' => 'O campo porta é obrigatório.',
            'descricao.max' => '50 caracteres maximos permitidos.',
            'porta.max' => '20 caracteres maximos permitidos.'
        ];
        $this->validate($request, $rules, $messages);
    }

    public function delete($id){
        try{
            $item = Impressora::findOrFail($id);
            $item->delete();
            session()->flash("mensagem_sucesso", "Impressora deletada com sucesso!");
        }catch(\Exception $e){

            session()->flash('mensagem_erro', 'Erro ao deletar impressora: ' . $e->getMessage());
        }
        return redirect('/impressoras');
        
    }
}
