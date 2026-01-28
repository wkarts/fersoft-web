<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventoSalario;

class EventoFuncionarioController extends Controller
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

        $nome = $request->nome;
        $data = EventoSalario::
        where('empresa_id', $this->empresa_id)
        ->when(!empty($nome), function ($query) use ($nome) {
            return $query->where('nome', 'like', "%$nome%");
        })
        ->paginate(30);

        return view('evento_salario.index')
        ->with('title', 'Eventos')
        ->with('nome', $nome)
        ->with('data', $data);
    }

    public function create(){
        return view('evento_salario.create', ['title' => 'Novo Evento']);
    }

    public function edit($id){
        $item = EventoSalario::findOrFail($id);

        return view('evento_salario.create', ['title' => 'Novo Evento', 'item' => $item]);
    }

    public function store(Request $request){
        $this->_validate($request);
        try{
            
            EventoSalario::create($request->all());
            session()->flash("mensagem_sucesso", "Evento cadastrado com sucesso!");

        }catch(\Exception $e){
            // echo $e->getMessage();
            // die;
            __saveError($e, $this->empresa_id);
            session()->flash('mensagem_erro', 'Erro ao cadastrar evento!');
        }
        return redirect()->route('eventosFuncionario.index');
    }

    private function _validate(Request $request){
        $rules = [
            'nome' => 'required|max:50',
            'tipo' => 'required',
            'metodo' => 'required',
            'condicao' => 'required',
            'ativo' => 'required',
        ];

        $messages = [
            'nome.required' => 'O campo Nome é obrigatório.',
            'nome.max' => '50 caracteres maximos permitidos.',
            'tipo.required' => 'O campo Tipo é obrigatório.',
            'metodo.required' => 'O campo Médoto é obrigatório.',
            'condicao.required' => 'O campo Condição é obrigatório.',
            'ativo.required' => 'O campo Ativo é obrigatório.',

        ];
        $this->validate($request, $rules, $messages);
    }

    public function update(Request $request, $id){
        $this->_validate($request);

        try{
            $item = EventoSalario::findOrFail($id);

            $item->fill($request->all())->save();
            session()->flash('mensagem_sucesso', 'Evento atualizado!');

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->route('eventosFuncionario.index');

    }

    public function destroy($id){
        try{

            $item = EventoSalario::findOrFail($id);

            $item->delete();
            session()->flash('mensagem_sucesso', 'Evento removido!');

        }catch(\Exception $e){
            __saveError($e, $this->empresa_id);
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->back();
    }
}
