<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\ItemPedido;
use App\Models\ItemPedidoDelivery;
use App\Models\TelaPedido;

class CozinhaController extends BaseController
{
    /* --- Contrato obrigatório do BaseController --- */
    protected $model = Pedido::class;
    protected $resource = 'controleCozinha';
    protected $formTitle = 'Controle de Cozinha';
    protected $listView = 'controleCozinha.index';
    protected $registerView = 'controleCozinha.selecionar';
    protected $redirectPage = '/controleCozinha/selecionar';

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }
    /* --- Fim contrato BaseController --- */


    protected $empresa_id = null;
    public function __construct(){
		parent::__construct();
        $this->middleware(function ($request, $next) {
            $this->empresa_id = $request->empresa_id;
            $value = session('user_logged');
            if(!$value){
                return redirect("/login");
            }
            return $next($request);
        });
    }
    
    public function index($id = NULL){

        $tela = 'Todos';
        if($id != null){
            $tela = TelaPedido::find($id)->nome;
        }

        return view('controleCozinha/index')
        ->with('cozinhaJs', true)
        ->with('id', $id)
        ->with('tela', $tela)
        ->with('title', 'Controle de Pedidos');
    }

    public function buscar(Request $request){
        $itens = ItemPedido::
        select('item_pedidos.*')
        ->join('pedidos', 'pedidos.id', '=', 'item_pedidos.pedido_id')
        ->join('produtos', 'produtos.id', '=', 'item_pedidos.produto_id')
        ->where('item_pedidos.status', false)
        ->where('produtos.envia_controle_pedidos', true)
        ->where('pedidos.empresa_id', $this->empresa_id)
        ->orderBy('item_pedidos.created_at', 'desc')
        ->get();

        $itensDelivery = ItemPedidoDelivery::
        select('item_pedido_deliveries.*')
        ->join('pedido_deliveries', 'pedido_deliveries.id', '=', 'item_pedido_deliveries.pedido_id')
        ->join('produto_deliveries', 'produto_deliveries.id', '=', 'item_pedido_deliveries.produto_id')
        ->join('produtos', 'produtos.id', '=', 'produto_deliveries.produto_id')
        ->where('item_pedido_deliveries.status', 0)
        ->where('produtos.envia_controle_pedidos', 1)
        ->where('pedido_deliveries.empresa_id', $this->empresa_id)
        ->orderBy('item_pedido_deliveries.created_at', 'desc')
        ->with(['produto'])
        ->get();

        $tela = $request->tela;
        $tipoTela = null;
        if($tela > 0){
            $tipoTela = TelaPedido::find($tela);
        }

        $arr = [];
        foreach($itens as $i){
            $pTemp = $i->produto;
            $i->produto;
            $i->comanda = $i->pedido->comanda;


            $complementos = "";
            $adicionais = "";
            foreach($i->itensAdicionais as $key => $a){
                // if($a->adicional->produto->adicional){
                //     $adicionais .= $a->adicional->nome . " | ";
                // }else{
                //     $complementos .= $a->adicional->nome . " | ";
                // }
                $adicionais .= $a->adicional->nome . " | ";

                
            }
            
            if(strlen($complementos) > 0)
                $complementos = substr($complementos, 0, strlen($complementos)-2);

            if(strlen($adicionais) > 0)
                $adicionais = substr($adicionais, 0, strlen($adicionais)-2);

            $saboresPizza = "";

            foreach($i->sabores as $key => $s){
                $saboresPizza .= $s->produto->produto->nome . ($key < count($i->sabores)-1 ? " | " : "");
            }

            $i->tamanhoPizza = $i->tamanho != null ? $i->tamanho->nome() : false;


            $i->adicionais = $adicionais;
            $i->complementos = $complementos;
            $i->saboresPizza = $saboresPizza;
            $i->data = \Carbon\Carbon::parse($i->created_at)->format('d/m H:i');

            $dataPedido = \Carbon\Carbon::parse($i->created_at)->format('Y-m-d H:i:s');
            $dataAgora = date('Y-m-d H:i:s');

            $date1 = strtotime($dataPedido);
            $date2 = strtotime($dataAgora);

            $dif = (int)($date2 - $date1)/60;
            $i->cor = "white";
            if($tipoTela != null && $dif > $tipoTela->alerta_amarelo){
                $i->cor = 'yellow';
            }

            if($tipoTela != null && $dif > $tipoTela->alerta_vermelho){
                $i->cor = 'red';
            }

            $i->teste = $dif;
            $mesa = "";
            if($i->pedido->mesa_id != null){
                $mesa = $i->pedido->mesa->nome;
            }
            $i->mesa = $mesa;

            if($tela == 0 || $pTemp->tela_pedido_id == $tela){
                array_push($arr, $i);
            }
        }

        foreach($itensDelivery as $i){
            if($i->pedido->estado == 'aprovado'){
                $pTemp = $i->produto->produto;

                $i->produto->produto;
                $i->comanda = null;

                $adicionais = "";
                foreach($i->itensAdicionais as $key => $a){

                    $adicionais .= $a->adicional->nome . ($key < count($i->itensAdicionais)-1 ? " | " : "");
                }

                $saboresPizza = "";

                foreach($i->sabores as $key => $s){
                    $saboresPizza .= $s->produto->produto->nome . ($key < count($i->sabores)-1 ? " | " : "");
                }

                $i->tamanhoPizza = $i->tamanho != null ? $i->tamanho->nome() : false;

                $i->adicionais = $adicionais;
                $i->saboresPizza = $saboresPizza;
                $i->data = \Carbon\Carbon::parse($i->created_at)->format('d/m H:i');

                $dataPedido = \Carbon\Carbon::parse($i->created_at)->format('Y-m-d H:i:s');
                $dataAgora = date('Y-m-d H:i:s');

                $date1 = strtotime($dataPedido);
                $date2 = strtotime($dataAgora);
                
                $dif = (int)($date2 - $date1)/60;
                $i->cor = "white";
                if($tipoTela != null && $dif > $tipoTela->alerta_amarelo){
                    $i->cor = 'yellow';
                }

                if($tipoTela != null && $dif > $tipoTela->alerta_vermelho){
                    $i->cor = 'red';
                }

                $i->teste = $dif;
                $i->mesa = "";

                if($tela == 0 || $pTemp->tela_id == $tela){
                    array_push($arr, $i);
                }
            }
        }
        usort($arr, function($a, $b){
            return strcmp($a->created_at, $b->created_at);
        });
        return response()->json($arr, 200);
    }

    public function concluido(Request $request){
        $ehDelivery = $request->ehDelivery;

        if($ehDelivery == 1){
            $item = ItemPedidoDelivery::find($request->id);
            $item->status = true;

            return response()->json($item->save(), 200);
        }else{
            $item = ItemPedido::find($request->id);
            $item->status = true;

            return response()->json($item->save(), 200);
        }

    }

    public function selecionar(){
        $telas = TelaPedido::
        where('empresa_id', $this->empresa_id)
        ->get();
        if(sizeof($telas) > 0){
            return view('controleCozinha/selecionar')
            ->with('telas', $telas)
            ->with('title', 'Tipo de controle');
        }else{
            return redirect('/controleCozinha/controle');
        }
    }

    public function pedidosPendentes(Request $request)
    {
        $itens = \App\Models\ItemPedido::with(['pedido', 'produto', 'tamanho', 'itensAdicionais.adicional'])
            ->where('status', false)
            ->whereHas('pedido', function ($query) {
                $query->where('empresa_id', $this->empresa_id);
            })
            ->get();

        $pedidosAgrupados = [];
        foreach ($itens as $item) {
            $pedidoId = $item->pedido_id;
            if (!isset($pedidosAgrupados[$pedidoId])) {
                $pedido = $item->pedido;
                $pedido->setRelation('itens', collect());
                $pedidosAgrupados[$pedidoId] = $pedido;
            }
            $pedidosAgrupados[$pedidoId]->itens->push($item);
        }

        return response()->json(array_values($pedidosAgrupados));
    }

    public function atualizarStatus($id, $status)
    {
        $pedido = Pedido::where('empresa_id', $this->empresa_id)->find($id);

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado para esta empresa.',
            ], 404);
        }

        $pedido->status = $status;
        $pedido->save();

        return response()->json(['success' => true]);
    }

    public function apiPedidosPendentes()
    {
        try {
            $resultado = [];

            $pedidosDelivery = \App\Models\PedidoDelivery::with([
                'itens.produto.produto',
                'itens.tamanho',
                'itens.itensAdicionais.adicional',
                'cliente',
            ])
                ->where('empresa_id', $this->empresa_id)
                ->where('estado', 'aprovado')
                ->orderBy('id')
                ->get();

            foreach ($pedidosDelivery as $pedido) {
                $itensPendentes = [];

                foreach ($pedido->itens as $item) {
                    if ((int) $item->status !== 0) {
                        continue;
                    }

                    $adicionais = [];
                    foreach ($item->itensAdicionais ?? [] as $adicionalItem) {
                        $adicional = $adicionalItem->adicional ?? null;
                        if (!$adicional) {
                            continue;
                        }

                        $nome = method_exists($adicional, 'nome')
                            ? $adicional->nome()
                            : ($adicional->nome ?? 'Extra');

                        $adicionais[] = ['nome' => $nome];
                    }

                    $tamanho = $item->tamanho;
                    $nomeTamanho = '';
                    if ($tamanho) {
                        $nomeTamanho = method_exists($tamanho, 'nome')
                            ? $tamanho->nome()
                            : ($tamanho->nome ?? '');
                    }

                    $itensPendentes[] = [
                        'id' => $item->id,
                        'nome' => $item->produto->produto->nome
                            ?? $item->produto->nome
                            ?? ('Produto ID: ' . $item->produto_id),
                        'tamanho' => $nomeTamanho,
                        'quantidade' => (int) $item->quantidade,
                        'observacao' => $item->observacao ?? '',
                        'adicionais' => $adicionais,
                    ];
                }

                if ($itensPendentes) {
                    $dataPedido = $pedido->data_registro ?? $pedido->created_at;
                    $resultado[] = [
                        'id' => $pedido->id,
                        'tipo' => 'delivery',
                        'nome_cliente' => $pedido->cliente->nome ?? 'Cliente Delivery',
                        'hora' => $dataPedido ? \Carbon\Carbon::parse($dataPedido)->format('H:i') : '',
                        'itens' => $itensPendentes,
                    ];
                }
            }

            return response()->json($resultado);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'erro' => 'Não foi possível carregar a fila da cozinha.',
            ], 500);
        }
    }

    public function concluirPedidoCozinha($tipo, $id)
    {
        if ($tipo === 'delivery') {
            $pedido = \App\Models\PedidoDelivery::where('empresa_id', $this->empresa_id)->find($id);
            if (!$pedido) {
                return response()->json(['sucesso' => false], 404);
            }

            \App\Models\ItemPedidoDelivery::where('pedido_id', $pedido->id)
                ->update(['status' => 1]);
        } else {
            $pedido = Pedido::where('empresa_id', $this->empresa_id)->find($id);
            if (!$pedido) {
                return response()->json(['sucesso' => false], 404);
            }

            \App\Models\ItemPedido::where('pedido_id', $pedido->id)
                ->update(['status' => 1]);
        }

        return response()->json(['sucesso' => true]);
    }
}
