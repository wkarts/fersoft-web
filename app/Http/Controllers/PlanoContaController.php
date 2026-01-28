<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\PlanoContaUtil;
use App\Models\PlanoConta;

class PlanoContaController extends Controller
{
    protected $util;
    protected $empresa_id = null;
    
    public function __construct(PlanoContaUtil $util){
        $this->util = $util;
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
        $data = PlanoConta::where('empresa_id', $this->empresa_id)
        ->orderBy('descricao')
        ->get();

        return view('plano-contas.index', compact('data'))
        ->with('title', 'Plano de Contas');
    }

    public function issue(){
        try{ 
            $this->util->criaPlanoDeContas($this->empresa_id);
            session()->flash("mensagem_sucesso", "Plano de contas criado!");
        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }
        return redirect()->back();
    }

    public function store(Request $request){
        if($request->plano_conta_id){

            $plano = PlanoConta::findOrFail($request->plano_conta_id);
            $grau = $plano->grauItem();
            // echo $plano;
            // die;
            $ultimo = $plano->dependentes->last();
            $descricao = "";

            if($ultimo){
                $temp = explode("-", $ultimo->descricao);
                $temp = trim($temp[0]);

                $temp = explode(".", $temp);
                foreach($temp as $key => $t){
                    if(sizeof($temp)-1 > $key){
                        $descricao .= "$t.";
                    }else{
                        if($grau != 5){
                            $descricao .= (int)$t+1;
                        }else{
                            $descricao .= "0".((int)$t+1);
                        }
                    }
                }
            }else{
                $descricao = explode("-", $plano->descricao);
                $descricao = trim($descricao[0]) . ".01";
            }

            
            $descricao = $descricao . " - $request->descricao";

            PlanoConta::create([
                'empresa_id' => $this->empresa_id,
                'descricao' => $descricao,
                'plano_conta_id' => $request->plano_conta_id
            ]);
            session()->flash("mensagem_sucesso", "Registro adicionado!");
        }else{
            $plano = PlanoConta::findOrFail($request->edit_id);
            $plano->descricao = $request->descricao;
            $plano->save();
            session()->flash("mensagem_sucesso", "Registro atualizado!");
        }
        return redirect()->back();
    }

    public function destroy($id){
        $item = PlanoConta::findOrFail($id);
        $item->delete();
        session()->flash("mensagem_sucesso", "Registro removido");
        return redirect()->back();
    }
}
