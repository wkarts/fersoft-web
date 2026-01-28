<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventoSalario;
use App\Models\FuncionarioEvento;
use App\Models\Funcionario;
use Illuminate\Support\Facades\DB;

class FuncionarioAdicionarEventoController extends Controller
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
        $data = Funcionario::
        select('funcionarios.*')
        ->join('funcionario_eventos', 'funcionario_eventos.funcionario_id', '=', 'funcionarios.id')
        ->where('empresa_id', $this->empresa_id)
        ->when(!empty($nome), function ($query) use ($nome) {
            return $query->where('nome', 'like', "%$nome%");
        })
        ->groupBy('funcionarios.id')
        ->paginate(30);

        return view('funcionario_evento.index')
        ->with('title', 'Funcionário x Eventos')
        ->with('nome', $nome)
        ->with('data', $data);
    }

    public function create(){

        $funcionarios = Funcionario::
        select('funcionarios.*')
        ->doesntHave('eventos')
        ->orderBy('nome')
        ->where('empresa_id', $this->empresa_id)
        ->get();

        $eventos = EventoSalario::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('funcionario_evento.create')
        ->with('funcionarios', $funcionarios)
        ->with('eventos', $eventos)
        ->with('title', 'Funcionário x Eventos');
    }

    public function store(Request $request){
        try{
            DB::transaction(function () use ($request) {

                for ($i = 0; $i < sizeof($request->evento); $i++) {
                    $item = [
                        'evento_id' => $request->evento[$i],
                        'funcionario_id' => $request->funcionario_id,
                        'condicao' => $request->condicao[$i],
                        'metodo' => $request->metodo[$i],
                        'valor' => __replace($request->valor[$i]),
                        'ativo' => $request->ativo[$i]
                    ];

                    FuncionarioEvento::create($item);
                }
            });

            session()->flash("mensagem_sucesso", "Eventos adicionados!");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
            // echo $e->getLine();
            // die;
        }
        return redirect()->route('funcionarioEventos.index');
    }

    public function edit($id){

        $item = Funcionario::findOrFail($id);

        $eventos = EventoSalario::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('funcionario_evento.create')
        ->with('eventos', $eventos)
        ->with('item', $item)
        ->with('title', 'Funcionário x Eventos');
    }

    public function update(Request $request, $id){

        try{
            DB::transaction(function () use ($request, $id) {

                FuncionarioEvento::where('funcionario_id', $id)->delete();
                for ($i = 0; $i < sizeof($request->evento); $i++) {
                    $item = [
                        'evento_id' => $request->evento[$i],
                        'funcionario_id' => $id,
                        'condicao' => $request->condicao[$i],
                        'metodo' => $request->metodo[$i],
                        'valor' => __replace($request->valor[$i]),
                        'ativo' => $request->ativo[$i]
                    ];

                    FuncionarioEvento::create($item);
                }
            });

            session()->flash("mensagem_sucesso", "Eventos atualizados!");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
            // echo $e->getLine();
            // die;
        }
        return redirect()->route('funcionarioEventos.index');
    }

    public function destroy($id){
        try{

            FuncionarioEvento::where('funcionario_id', $id)->delete();

            session()->flash("mensagem_sucesso", "Eventos removido!");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
            // echo $e->getLine();
            // die;
        }
        return redirect()->route('funcionarioEventos.index');
    }

}
