<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContaEmpresa;
use App\Models\PlanoConta;
use App\Models\ItemContaEmpresa;

class ContaEmpresaController extends Controller
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
        $data = ContaEmpresa::
        where('empresa_id', $this->empresa_id)
        ->get();

        return view('conta_empresa/index', compact('data'));
    }

    public function create(){

        $planos = PlanoConta::where('empresa_id', $this->empresa_id)
        ->get();

        if(sizeof($planos) == 0){
            session()->flash("mensagem_erro", "Defina o plano de contas");
            return redirect()->route('plano-contas.index');
        }
        return view('conta_empresa/register', compact('planos'));
    }

    public function edit($id){

        $item = ContaEmpresa::findOrFail($id);
        $planos = PlanoConta::where('empresa_id', $this->empresa_id)
        ->get();

        if(sizeof($planos) == 0){
            session()->flash("mensagem_erro", "Defina o plano de contas");
            return redirect()->route('plano-contas.index');

        }
        return view('conta_empresa/register', compact('planos', 'item'));
    }

    public function store(Request $request){
        $this->_validate($request);
        try{
            $request->merge([
                'saldo' => __replace($request->saldo_inicial),
                'saldo_inicial' => __replace($request->saldo_inicial),
            ]);
            ContaEmpresa::create($request->all());
            session()->flash("mensagem_sucesso", "Conta cadastrada!");
            return redirect()->route('contas-empresa.index');

        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
            return redirect()->back();
        }
    }

    public function update(Request $request, $id){

        try{
            $item = ContaEmpresa::findOrFail($id);

            $request->merge([
                'saldo_inicial' => __replace($request->saldo_inicial)
            ]);
            $item->fill($request->all())->save();
            session()->flash("mensagem_sucesso", "Conta atualizada!");
            return redirect()->route('contas-empresa.index');

        }catch(\Exception $e){

            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
            return redirect()->back();
        }
    }

    private function _validate(Request $request){
        $rules = [
            'nome' => 'required|max:50',
            'saldo_inicial' => 'required',
            'plano_conta_id' => 'required',
        ];

        $messages = [
            'nome.required' => 'Campo obrigatório.',
            'nome.max' => '50 caracteres maximos permitidos.',
            'saldo_inicial.required' => 'Campo obrigatório.',
            'plano_conta_id.required' => 'Campo obrigatório.',
        ];
        $this->validate($request, $rules, $messages);
    }

    public function destroy($id){
        $item = ContaEmpresa::findOrFail($id);
        $item->delete();
        session()->flash("mensagem_sucesso", "Conta removida");
        return redirect()->back();
    }

    public function show(Request $request, $id){

        $data_inicio = $request->data_inicio;
        $data_final = $request->data_final;
        $tipo = $request->tipo;

        $item = ContaEmpresa::findOrFail($id);
        $data = ItemContaEmpresa::where('conta_id', $id)
        ->orderBy('id', 'desc')
        ->when($data_inicio, function ($q) use ($data_inicio) {
            return $q->whereDate('created_at', '>=', $data_inicio);
        })
        ->when($data_final, function ($q) use ($data_final) {
            return $q->whereDate('created_at', '<=', $data_final);
        })
        ->when($tipo, function ($q) use ($tipo) {
            return $q->where('tipo', $tipo);
        })
        ->paginate(50);
       
        return view('conta_empresa/show', compact('data', 'item', 'data_inicio', 'data_final', 'tipo'));

    }
}
