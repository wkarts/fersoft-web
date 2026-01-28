<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApuracaoSalario;
use App\Models\Funcionario;
use App\Models\EventoSalario;
use App\Models\ApuracaoSalarioEvento;
use App\Models\CategoriaConta;
use App\Models\ContaPagar;
use Illuminate\Support\Facades\DB;

class ApuracaoSalarioController extends Controller
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
        $dt_inicio = $request->get('dt_inicio');
        $dt_fim = $request->get('dt_fim');
        $data = ApuracaoSalario::
        select('apuracao_salarios.*')
        ->join('funcionarios', 'apuracao_salarios.funcionario_id', '=', 'funcionarios.id')
        ->where('empresa_id', $this->empresa_id)
        ->when(!empty($nome), function ($query) use ($nome) {
            return $query->where('funcionarios.nome', 'like', "%$nome%");
        })
        ->when(!empty($dt_inicio), function ($query) use ($dt_inicio) {
            return $query->whereDate('apuracao_salarios.created_at', '>=', $dt_inicio);
        })
        ->when(!empty($dt_fim), function ($query) use ($dt_fim) {
            return $query->whereDate('apuracao_salarios.created_at', '<=', $dt_fim);
        })
        ->paginate(30);

        return view('apuracao_mensal.index')
        ->with('title', 'Apuração Mensal')
        ->with('nome', $nome)
        ->with('dt_inicio', $dt_inicio)
        ->with('dt_fim', $dt_fim)
        ->with('data', $data);
    }

    public function create(){

        $funcionarios = Funcionario::
        orderBy('nome')
        ->where('empresa_id', $this->empresa_id)
        ->get();
        $mesAtual = (int)date('m')-1;

        return view('apuracao_mensal.create')
        ->with('title', 'Apuração Mensal')
        ->with('mesAtual', $mesAtual)
        ->with('funcionarios', $funcionarios);
    }

    public function getEventos($id){
        try{
            $item = Funcionario::findOrFail($id);

            if(sizeof($item->eventos) == 0){
                return response()->json("", 200);
            }
            return view('apuracao_mensal.eventos', compact('item'));
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function store(Request $request){
        // echo "<pre>";
        // print_r($request->all());
        // echo "</pre>";

        try{
            DB::transaction(function () use ($request) {
                $ap = [
                    'funcionario_id' => $request->funcionario_id,
                    'mes' => $request->mes,
                    'ano' => $request->ano,
                    'valor_final' => __replace($request->valor_total),
                    'forma_pagamento' => $request->tipo_pagamento,
                    'observacao' => $request->observacao ?? ''
                ];

                $result = ApuracaoSalario::create($ap);

                for ($i = 0; $i < sizeof($request->evento); $i++) {

                    $ev = EventoSalario::find($request->evento[$i]);
                    if($ev){
                        ApuracaoSalarioEvento::create([
                            'apuracao_id' => $result->id,
                            'evento_id' => $ev->id,
                            'valor' => __replace($request->evento[$i]),
                            'metodo' => $request->metodo[$i],
                            'condicao' => $request->condicao[$i],
                            'nome' => $ev->nome
                        ]);
                    }
                }
            });

            session()->flash("mensagem_sucesso", "Apuração criada!");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        // die;
        return redirect('/apuracaoMensal');
    }

    public function destroy($id){
        try{

            ApuracaoSalario::find($id)->delete();

            session()->flash("mensagem_sucesso", "Apuração removida!");

        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->route('apuracaoMensal.index');
    }

    public function contaPagar($id){
        $item = ApuracaoSalario::findOrFail($id);

        $categorias = CategoriaConta::
        where('empresa_id', $this->empresa_id)
        ->where('tipo', 'pagar')
        ->get();

        return view('apuracao_mensal.conta_pagar')
        ->with('title', 'Nova Conta a Pagar')
        ->with('categorias', $categorias)
        ->with('item', $item);
    }

    public function setConta(Request $request, $id){
        $this->_validate($request);
        try{

            $item = ApuracaoSalario::findOrFail($id);

            print_r($request->all());

            $conta = [
                'compra_id' => null,
                'data_vencimento' => $this->parseDate($request->vencimento),
                'data_pagamento' => $this->parseDate($request->vencimento),
                'valor_integral' => str_replace(",", ".", $request->valor),
                'valor_pago' => $request->status ? __replace($request->valor_pago) : 0,
                'status' => $request->status ? true : false,
                'referencia' => $request->referencia,
                'tipo_pagamento' => $request->tipo_pagamento ?? '',
                'numero_nota_fiscal' => $request->numero_nota_fiscal ?? 0,
                'fornecedor_id' => 0,
                'categoria_id' => $request->categoria_id,
                'empresa_id' => $this->empresa_id
            ];
            $result = ContaPagar::create($conta);

            $item->conta_pagar_id = $result->id;
            $item->save();
            session()->flash("mensagem_sucesso", "Adicionado em contas a pagar!");

            // die;
        }catch(\Exception $e){
            session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
        }
        return redirect()->route('apuracaoMensal.index');

    }

    private function parseDate($date, $plusDay = false){
        if($plusDay == false)
            return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
        else
            return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
    }

    private function _validate(Request $request){
        $rules = [

            'referencia' => 'required',
            'valor' => 'required',
            'vencimento' => 'required',
            'tipo_pagamento' => 'required',
        ];

        $messages = [
            'referencia.required' => 'O campo referencia é obrigatório.',
            'tipo_pagamento.required' => 'O campo tipo de pagamento é obrigatório.',
            'valor.required' => 'O campo valor é obrigatório.',
            'vencimento.required' => 'O campo vencimento é obrigatório.'
        ];
        $this->validate($request, $rules, $messages);
    }
}
