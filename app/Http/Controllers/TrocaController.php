<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrocaVenda;
use App\Models\TrocaVendaItem;
use App\Models\ConfigNota;
use App\Models\Venda;
use App\Models\VendaCaixa;
use App\Models\ItemVendaCaixa;
use App\Models\ItemVenda;
use App\Helpers\StockMove;

class TrocaController extends Controller
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

        $trocas = TrocaVenda::
        where('empresa_id', $this->empresa_id)
        ->paginate(20);

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        return view("trocas/list")
        ->with('trocas', $trocas)
        ->with('config', $config)
        ->with('links', true)
        ->with('title', "Lista de Trocas");
    }

    public function filtro(Request $request){

        $cliente = $request->cliente;
        $data_inicial = $request->data_inicial;
        $data_final = $request->data_final;
        $status = $request->status;

        $trocas = TrocaVenda::
        select('troca_vendas.*')
        ->where('troca_vendas.empresa_id', $this->empresa_id);

        if($status){
            $trocas->where('status', $status == 1 ? 1 : 0);
        }

        if($cliente){
            $trocas->join('clientes', 'clientes.id', '=', 'troca_vendas.cliente_id')
            ->where('razao_social', 'like' , "%$cliente%");
        }

        if($data_inicial && $data_final){
            $trocas->whereBetween('troca_vendas.created_at', [
                $this->parseDate($data_inicial) . " 00:00:00",
                $this->parseDate($data_final) . " 23:59:59"
            ]);
        }

        $trocas = $trocas->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        return view("trocas/list")
        ->with('trocas', $trocas)
        ->with('cliente', $cliente)
        ->with('data_inicial', $data_inicial)
        ->with('data_final', $data_final)
        ->with('status', $status)
        ->with('config', $config)
        ->with('title', "Lista de Trocas");
    }

    private function parseDate($date, $plusDay = false){
        return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
    }

    public function nova(){
        return view("trocas/nova")
        ->with('title', "Nova troca");
    }

    public function autocomplete(Request $request){
        try{
            $retorno = [];
            $pesquisa = $request->pesquisa;
            if(is_numeric($pesquisa)){
                $vendas = Venda::
                select('id', 'cliente_id', 'valor_total', 'created_at')
                ->where('empresa_id', $this->empresa_id)
                ->where('id', 'like', "%$pesquisa%")
                ->orderBy('id', 'desc')
                ->where('troca', 0)
                ->get();

                $vendasCaixa = VendaCaixa::
                select('id', 'cliente_id', 'valor_total', 'created_at')
                ->where('empresa_id', $this->empresa_id)
                ->where('id', 'like', "%$pesquisa%")
                ->orderBy('id', 'desc')
                ->where('troca', 0)
                ->get();

                foreach($vendas as $v){
                    $v->tipo = 'pedido';
                    $v->razao_social = $v->cliente->razao_social;
                    $v->data = \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i');
                    array_push($retorno, $v);
                }

                foreach($vendasCaixa as $v){
                    $v->tipo = 'pdv';
                    $v->razao_social = $v->cliente ? $v->cliente->razao_social : 'Consumidor Final';
                    $v->data = \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i');
                    array_push($retorno, $v);
                }

            }else{
                $vendas = Venda::
                select('vendas.id', 'cliente_id', 'valor_total', 'vendas.created_at')
                ->where('vendas.empresa_id', $this->empresa_id)
                ->join('clientes', 'clientes.id', '=', 'vendas.cliente_id')
                ->orderBy('vendas.id', 'desc')
                ->where('razao_social', 'like', "%$pesquisa%")
                ->where('troca', 0)
                ->get();

                $vendasCaixa = VendaCaixa::
                select('venda_caixas.id', 'cliente_id', 'valor_total', 'venda_caixas.created_at')
                ->where('venda_caixas.empresa_id', $this->empresa_id)
                ->join('clientes', 'clientes.id', '=', 'venda_caixas.cliente_id')
                ->orderBy('venda_caixas.id', 'desc')
                ->where('razao_social', 'like', "%$pesquisa%")
                ->where('troca', 0)
                ->get();
                foreach($vendas as $v){
                    $v->tipo = 'pedido';
                    $v->data = \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i');
                    if($v->cliente_id != NULL){
                        $v->razao_social = $v->cliente->razao_social;
                    }else{
                        $v->razao_social = "--";
                    }
                    array_push($retorno, $v);
                }
                foreach($vendasCaixa as $v){
                    $v->tipo = 'pdv';
                    $v->data = \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i');
                    if($v->cliente_id != NULL){
                        $v->razao_social = $v->cliente->razao_social;
                    }else{
                        $v->razao_social = "--";
                    }
                    array_push($retorno, $v);
                }
            }

            if(sizeof($retorno) > 1){
                usort($retorno, function($a, $b){
                    return $a['created_at'] < $b['created_at'] ? 1 : 0;

                });
            }

            return response()->json($retorno, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function getVenda(Request $request){
        try{
            $venda = null;

            if($request->tipo == 'pedido'){
                $venda = Venda::where('id', $request->id)
                ->with('cliente', 'itens.produto')
                ->first();

                $venda->data = \Carbon\Carbon::parse($venda->created_at)->format('d/m/Y H:i');

            }else{
                $venda = VendaCaixa::where('id', $request->id)
                ->with('cliente', 'itens.produto')
                ->first();
                $venda->data = \Carbon\Carbon::parse($venda->created_at)->format('d/m/Y H:i');
            }
            return response()->json($venda, 200);

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function save(Request $request){
        try{

            $tipo = $request->tipo;
            $venda_id = $request->venda_id;
            $itens = json_decode($request->itens);

            if($tipo == 'pedido'){
                $venda = Venda::
                where('empresa_id', $this->empresa_id)
                ->where('id', $venda_id)
                ->first();
            }else{
                $venda = VendaCaixa::
                where('empresa_id', $this->empresa_id)
                ->where('id', $venda_id)
                ->first();
            }

            $dataTroca = [
                'venda_id' => $venda_id,
                'tipo' => $tipo,
                'valor_total' => $venda->valor_total,
                'valor_credito' => 0,
                'empresa_id' => $this->empresa_id,
                'cliente_id' => $venda->cliente_id,
                'usuario_id' => get_id_user(),
                'data_venda' => \Carbon\Carbon::parse($venda->created_at)->format('Y-m-d'),
                'status' => 0
            ];

            $troca = TrocaVenda::create($dataTroca);

            $total = 0;
            $stockMove = new StockMove();

            foreach($itens as $i){
                $qtd = (float)__replace($i->quantidade);
                $dataItem = [
                    'troca_id' => $troca->id,
                    'produto_id' => $i->produto_id,
                    'valor' => $i->valor,
                    'quantidade' => $qtd
                ];

                $total += (float)$i->valor*(float)$i->quantidade;
                TrocaVendaItem::create($dataItem);

                //atualiza o status do item para devolvido
                if($tipo == 'pedido'){
                    $it = ItemVenda::find($i->id);
                    if($it) {
                        $it->devolvido = true;
                        $it->save();
                    }
                }else{
                    $it = ItemVendaCaixa::find($i->id);
                    if($it) {
                        $it->devolvido = true;
                        $it->save();
                    }
                }

            }

            $stockMove->pluStock($i->produto_id, $qtd, $i->valor);

            $venda->troca = true;
            $venda->valor_total -= $total;
            $venda->save();

            $troca->valor_credito = $total;
            $troca->save();

            session()->flash("mensagem_sucesso", "Operação realizada!");

        }catch(\Exception $e){
            session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
        }

        return redirect('trocas');
    }

    public function creditoCliente($cliente_id){
        try{
            $valor = TrocaVenda::
            where('empresa_id', $this->empresa_id)
            ->where('cliente_id', $cliente_id)
            ->where('status', 0)
            ->sum('valor_credito');

            return response()->json($valor, 200);
        }catch(\Exception $e){
            return response()->json($e->getMessage(), 404);
        }
    }

    public function delete($id){

        $troca = TrocaVenda::
        where('id', $id)
        ->first();
        
        if(valida_objeto($troca)){

            $this->reverteEstoque($troca->itens);

            $venda = $troca->venda();
            $venda->valor_total = $troca->valor_total;
            $venda->save();
            $troca->delete();
            session()->flash("mensagem_sucesso", "Troca removida!");

            return redirect('/trocas');
        }else{
            return redirect('/403');
        }

    }

    private function reverteEstoque($itens){
        $stockMove = new StockMove();
        foreach($itens as $i){
            if(!empty($i->produto->receita)){
                //baixa por receita
                $receita = $i->produto->receita; 
                foreach($receita->itens as $rec){

                    if(!empty($rec->produto->receita)){ // se item da receita for receita
                        $receita2 = $rec->produto->receita; 
                        foreach($receita2->itens as $rec2){
                            $stockMove->pluStock(
                                $rec2->produto_id, 
                                (float) str_replace(",", ".", $i->quantidade) * 
                                ($rec2->quantidade/$receita2->rendimento)
                            );
                        }
                    }else{

                        $stockMove->pluStock(
                            $rec->produto_id, 
                            (float) str_replace(",", ".", $i->quantidade) * 
                            ($rec->quantidade/$receita->rendimento)
                        );
                    }
                }
            }else{
                $stockMove->pluStock(
                    $i->produto_id, (float) str_replace(",", ".", $i->quantidade));
            }
        }
    }
}
