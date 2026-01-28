<?php

namespace App\Http\Controllers\Ifood;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IfoodConfig;
use App\Models\PedidoIfood;
use App\Models\ConfigNota;
use App\Models\Usuario;
use App\Models\Acessor;
use App\Models\VendaCaixa;
use App\Models\ProdutoIfood;
use App\Models\Produto;
use App\Models\Certificado;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cidade;
use App\Models\Pais;
use App\Models\GrupoCliente;
use App\Models\ItemVendaCaixa;
use App\Models\ConfigCaixa;
use App\Models\AberturaCaixa;
use App\Models\Funcionario;
use App\Models\PagamentoPedidoIfood;
use App\Models\ItemPedidoIfood;
use App\Models\AdicionalItemPedidoIfood;
use App\Restaurant\IfoodService;
use Dompdf\Dompdf;
use NFePHP\DA\NFe\CupomPedidoIfood;

class OrderController extends Controller
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

        $data = PedidoIfood::
        where('empresa_id', $this->empresa_id)
        ->orderBy('data_pedido', 'desc')
        ->paginate(15);

        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        return view('pedido_ifood/index', compact('data'))
        ->with('links', true)
        ->with('title', 'Pedidos iFood');
    }

    public function filter(Request $request){

        $search = $request->search;
        $status = $request->status;
        $data = PedidoIfood::
        where('empresa_id', $this->empresa_id)
        ->orderBy('data_pedido', 'desc');
        if($search){
            $data->where('nome_cliente', 'like', "%$search%");
        }
        if($status){
            $data->where('status', $status);
        }

        $data = $data->get();

        return view('pedido_ifood/index', compact('data', 'search', 'status'))
        ->with('title', 'Pedidos iFood');
    }

    public function getNewOrders(){
        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $iFoodService = new IfoodService($config);

        $data = $iFoodService->getOrders("PLC");

        if(isset($data->message)){
            if($data->message == 'token expired'){
                return redirect('/ifood/getToken');
            }
            session()->flash("mensagem_erro", $data->message);
            return redirect('/ifood/config');
        }

        if($data == null){
            session()->flash("mensagem_erro", "Nenhum novo pedido encontrado!");
            return redirect()->back();
        }
        
        $contNewOrders = 0;
        try{

            foreach($data as $key => $item){

                $pedido = PedidoIfood::where('pedido_id', $item->orderId)
                ->first();

                $detail = $iFoodService->getOrderDetail($item->orderId);
                $orderId = $item->orderId;

                if($pedido == null){
                    $dataOrder = [
                        'status' => $item->code,
                        'pedido_id' => $item->orderId,
                        'data_pedido' => $item->createdAt,
                        'empresa_id' => $this->empresa_id
                    ];
                    $pedido = PedidoIfood::create($dataOrder);
                    $contNewOrders++;
                }else{
                //alteração de status
                    $pedido->status = $item->code;
                    $pedido->save();
                }

                if($detail){

                    $pedido->tipo_pedido = $detail->orderType;
                    if(isset($detail->delivery)){
                        $delivery = $detail->delivery->deliveryAddress;
                        $pedido->endereco = $delivery->formattedAddress;
                        $pedido->bairro = $delivery->neighborhood;
                        $pedido->cep = $delivery->postalCode;
                    }

                    if(isset($detail->customer)){
                        $customer = $detail->customer;

                        $pedido->nome_cliente = $customer->name;
                        $pedido->id_cliente = $customer->id;
                        $pedido->telefone_cliente = $customer->phone->number;
                        if(isset($customer->documentNumber)){
                            $pedido->cpf_na_nota = $customer->documentNumber;
                        }
                    }

                    $pedido->valor_produtos = $detail->total->subTotal;
                    $pedido->valor_entrega = $detail->total->deliveryFee;
                    $pedido->valor_total = $detail->total->orderAmount;
                    $pedido->taxas_adicionais = $detail->total->additionalFees;

                    $pedido->save();

                    $pedido->payments()->delete();
                    $pedido->itens()->delete();
                    if(isset($detail->payments->methods)){
                        $methods = $detail->payments->methods;
                        foreach($methods as $m){
                            $payData = [
                                'forma_pagamento' => $m->method,
                                'tipo_pagamento' => $m->type,
                                'bandeira_cartao' => isset($m->card) ? $m->card->brand : '',
                                'valor' => $m->value,
                                'pedido_id' => $pedido->id
                            ];

                            PagamentoPedidoIfood::create($payData);

                        }

                    }

                    $cont = 0;

                    foreach($detail->items as $key => $it){
                        // echo "<pre>";
                        // print_r($it);
                        // echo "</pre>";
                        // die;
                        $prod = ProdutoIfood::where('id_ifood_aux', $it->id)->first();

                        $dataItem = [
                            'pedido_id' => $pedido->id,
                            'nome_produto' => $it->name,
                            'image_url' => isset($it->imageUrl) ? $it->imageUrl : "",
                            'unidade' => $it->unit,
                            'valor_unitario' => $it->unitPrice,
                            'quantidade' => $it->quantity,
                            'total' => $it->totalPrice,
                            'valor_adicional' => $it->optionsPrice,
                            'observacao' => isset($it->observations) ? $it->observations : "",
                            'produto_id' => $prod != null ? $prod->id : 0
                        ];
                        if($orderId == '50bf73b0-3437-476b-850c-fa44d80d40e9'){
                            if($key == 1){
                                echo "teste";
                                dd($it);
                            }
                        }
                        $itemOrder = ItemPedidoIfood::create($dataItem);

                        //condicional de adicionais
                        if(isset($it->options)){
                            foreach($it->options as $op){
                                $dataAdicional = [
                                    'item_pedido_id' => $itemOrder->id,
                                    'nome' => $op->name,
                                    'unidade' => $op->unit,
                                    'quantidade' => $op->quantity,
                                    'valor_unitario' => $op->unitPrice,
                                    'total' => $op->price,
                                ];
                                AdicionalItemPedidoIfood::create($dataAdicional);
                            }
                        }

                        $cont++;

                    }
                    

                }
            }
            session()->flash('mensagem_sucesso', "Busca de novos pedidos realizada, $contNewOrders novo(s) pedidos foram encontrados!");
        }catch(\Exception $e){
            echo $e->getMessage() . " linha " . $e->getLine();
            die;
            session()->flash('mensagem_erro', "Algo deu errado: " . $e->getMessage());
        }
        return redirect('/ifood/pedidos');
    }

    public function detail($id){

        $item = PedidoIfood::findOrFail($id);

        // $config = ConfigNota::
        // where('empresa_id', $this->empresa_id)
        // ->first();

        // $iFoodService = new IfoodService($config);
        // $detail = $iFoodService->getOrderDetail($item->pedido_id);

        // dd($detail);

        return view('pedido_ifood/detail', compact('item'))
        ->with('title', 'Detalhe do pedido #' . $item->id);
    }

    public function print($id){
        $item = PedidoIfood::findOrFail($id);

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $p = view('pedido_ifood/print')
        ->with('config', $config)
        ->with('pedido', $item);
        // return $p;

        $domPdf = new Dompdf(["enable_remote" => true]);
        $domPdf->loadHtml($p);

        $pdf = ob_get_clean();

        $domPdf->setPaper("A4");
        $domPdf->render();
        $domPdf->stream("Pedido iFood $item->pedido_id.pdf", ["Attachment" => false]);
    }

    public function getNewOrdersAsync(Request $request){
        try{
            $config = IfoodConfig::
            where('empresa_id', $this->empresa_id)
            ->first();

            if($config == null){
                return response()->json("null", 200);
            }

            $iFoodService = new IfoodService($config);

            $data = $iFoodService->getOrders("PLC");

            if($data == null){
                return response()->json("null", 200);
            }else if(isset($data->message)){
                return response()->json("null", 200);

            }else{
                foreach($data as $item){

                    $pedido = PedidoIfood::where('pedido_id', $item->orderId)
                    ->first();

                    if($pedido == null){
                        $detail = $iFoodService->getOrderDetail($item->orderId);
                        if($detail){

                            $dataOrder = [
                                'status' => $item->code,
                                'pedido_id' => $item->orderId,
                                'data_pedido' => $item->createdAt,
                                'empresa_id' => $this->empresa_id
                            ];
                            $pedido = PedidoIfood::create($dataOrder);

                            $pedido->tipo_pedido = $detail->orderType;
                            if(isset($detail->delivery)){
                                $delivery = $detail->delivery->deliveryAddress;
                                $pedido->endereco = $delivery->formattedAddress;
                                $pedido->bairro = $delivery->neighborhood;
                                $pedido->cep = $delivery->postalCode;
                            }

                            if(isset($detail->customer)){
                                $customer = $detail->customer;

                                $pedido->nome_cliente = $customer->name;
                                $pedido->id_cliente = $customer->id;
                                $pedido->telefone_cliente = $customer->phone->number;
                                if(isset($customer->documentNumber)){
                                    $pedido->cpf_na_nota = $customer->documentNumber;
                                }
                            }

                            $pedido->valor_produtos = $detail->total->subTotal;
                            $pedido->valor_entrega = $detail->total->deliveryFee;
                            $pedido->valor_total = $detail->total->orderAmount;
                            $pedido->taxas_adicionais = $detail->total->additionalFees;

                            $pedido->save();

                            // $pedido->payments()->delete();
                            // $pedido->itens()->delete();
                            if(isset($detail->payments->methods)){
                                $methods = $detail->payments->methods;
                                foreach($methods as $m){
                                    $payData = [
                                        'forma_pagamento' => $m->method,
                                        'tipo_pagamento' => $m->type,
                                        'bandeira_cartao' => isset($m->card) ? $m->card->brand : '',
                                        'valor' => $m->value,
                                        'pedido_id' => $pedido->id
                                    ];

                                    PagamentoPedidoIfood::create($payData);
                                }
                            }

                            foreach($detail->items as $it){
                                $prod = ProdutoIfood::where('id_ifood_aux', $it->id)->first();
                                // echo $prod;
                                // die;
                                $dataItem = [
                                    'pedido_id' => $pedido->id,
                                    'nome_produto' => $it->name,
                                    'image_url' => isset($it->imageUrl) ? $it->imageUrl : "",
                                    'unidade' => $it->unit,
                                    'valor_unitario' => $it->unitPrice,
                                    'quantidade' => $it->quantity,
                                    'total' => $it->totalPrice,
                                    'valor_adicional' => $it->optionsPrice,
                                    'observacao' => isset($it->observations) ? $it->observations : "",
                                    'produto_id' => $prod->id
                                ];

                                $itemOrder = ItemPedidoIfood::create($dataItem);

                                if(isset($it->options)){
                                    foreach($it->options as $op){
                                        $dataAdicional = [
                                            'item_pedido_id' => $itemOrder->id,
                                            'nome' => $op->name,
                                            'unidade' => $op->unit,
                                            'quantidade' => $op->quantity,
                                            'valor_unitario' => $op->unitPrice,
                                            'total' => $op->price,
                                        ];
                                        AdicionalItemPedidoIfood::create($dataAdicional);

                                    }
                                }

                            }

                        }
                    }
                }
            }

            $pedido = PedidoIfood::where('status_leitura', 0)
            ->where('status', 'PLC')
            ->orderBy('id', 'asc')
            ->with('itens')
            ->with('payments')
            ->first();

            if($pedido == null){
                return response()->json("", 200);
            }
            return view('pedido_ifood/novo_pedido', compact('pedido'));

        }catch(\Exception $e){
            return response()->json($e->getMessage(), 401);
        }
    }

    public function cancelOrder(Request $request){
        $pedido = PedidoIfood::findOrFail($request->pedido_id);

        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();
        $iFoodService = new IfoodService($config);

        $dataStatus = $iFoodService->cancellation($pedido->pedido_id, $request->motivo, $request->codigo);

        if(isset($dataStatus->message)){
            session()->flash('mensagem_erro', $dataStatus->message);
        }else{
            $pedido->status = 'CAN';
            $pedido->save();
            session()->flash('mensagem_sucesso', 'Pedido cancelado!');
            return redirect()->back();
        }
    }

    public function readOrder(Request $request){

        $pedido = PedidoIfood::findOrFail($request->pedido_id);

        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $iFoodService = new IfoodService($config);
        if($request->status == 'CFM'){

            $dataStatus = $iFoodService->orderConfirm($pedido->pedido_id);

            // echo "<pre>";
            // print_r($dataStatus);
            // echo "</pre>";
            // die;
        }else{
            $dataStatus = $iFoodService->cancellation($pedido->pedido_id, $request->motivo, $request->codigo);

            // echo "<pre>";
            // print_r($dataStatus);
            // echo "</pre>";
            // die;
        }

        if(isset($dataStatus->message)){

        }else{
            $pedido->status = $request->status;

            $pedido->status_leitura = true;
            $pedido->save();

            if($pedido->status == 'CFM'){
                session()->flash('mensagem_sucesso', 'Pedido lido e aprovado!');
            }else{
                session()->flash('mensagem_erro', 'Pedido lido e cancelado!');
            }
            return redirect('/ifood/pedidosDetail/'.$pedido->id);
        }
    }

    public function dispatch($id){
        $pedido = PedidoIfood::findOrFail($id);
        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $iFoodService = new IfoodService($config);
        $dataStatus = $iFoodService->orderDispatch($pedido->pedido_id);

        if($dataStatus == ""){
            $pedido->status = "DSP";
            $pedido->save();
            session()->flash('mensagem_sucesso', "Pedido $pedido->pedido_id despachado!");

        }
        return redirect()->back();
    }

    public function requestDriver($id){
        $pedido = PedidoIfood::findOrFail($id);
        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $iFoodService = new IfoodService($config);
        $dataStatus = $iFoodService->requestDriver($pedido->pedido_id);
        if(isset($dataStatus->error)){
            session()->flash('mensagem_erro', $dataStatus->error->message);

        }else{
            $pedido->status_driver = 1;
            $pedido->save();
            session()->flash('mensagem_sucesso', "Solicitação de entregador feita para pedido $pedido->pedido_id");
        }
        return redirect()->back();
    }

    public function pdv($id){
        $pedido = PedidoIfood::findOrFail($id);
        $config = IfoodConfig::
        where('empresa_id', $this->empresa_id)
        ->first();

        $atributes = $this->addAtributes($pedido, $config);

        $usuario = Usuario::find(get_id_user());
        $tiposPagamento = VendaCaixa::tiposPagamento();
        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $certificado = Certificado::
        where('empresa_id', $this->empresa_id)
        ->first();

        $tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();
        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
        ->where('inativo', false)
        ->orderBy('nome')->get();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
        ->get();

        $clientes = Cliente::where('empresa_id', $this->empresa_id)
        ->orderBy('razao_social')->get();

        $atalhos = ConfigCaixa::
        where('usuario_id', get_id_user())
        ->first();

        $funcionarios = Funcionario::
        where('funcionarios.empresa_id', $this->empresa_id)
        ->select('funcionarios.*')
        ->join('usuarios', 'usuarios.id', '=', 'funcionarios.usuario_id')
        ->get();

        $view = 'main';
        if($atalhos != null && $atalhos->modelo_pdv == 1){
            $view = 'main2';
        }

        $rascunhos = $this->getRascunhos();
        $consignadas = $this->getConsignadas();

        $acessores = Acessor::where('empresa_id', $this->empresa_id)->get();

        $usuarios = Usuario::where('empresa_id', $this->empresa_id)
        ->where('ativo', 1)
        ->orderBy('nome', 'asc')
        ->get();
        $vendedores = [];
        foreach($usuarios as $u){
            if($u->funcionario){
                array_push($vendedores, $u);
            }
        }

        $estados = Cliente::estados();
        $cidades = Cidade::all();
        $pais = Pais::all();
        $grupos = GrupoCliente::get();

        $produtosMaisVendidos = $this->produtosMaisVendidos();

        return view('frontBox/main3')
        ->with('itens', $atributes)
        ->with('atalhos', $atalhos)
        ->with('rascunhos', $rascunhos)
        ->with('vendedores', $vendedores)
        ->with('produtosMaisVendidos', $produtosMaisVendidos)
        ->with('estados', $estados)
        ->with('cidades', $cidades)
        ->with('pais', $pais)
        ->with('pedido_ifood', $pedido->id)
        ->with('grupos', $grupos)
        ->with('acessores', $acessores)
        ->with('consignadas', $consignadas)
        ->with('funcionarios', $funcionarios)
        ->with('cod_comanda', $pedido->comanda)
        ->with('frenteCaixa', true)
        ->with('tiposPagamento', $tiposPagamento)
        ->with('tiposPagamentoMulti', $tiposPagamentoMulti)
        ->with('config', $config)
        ->with('usuario', $usuario)
        ->with('clientes', $clientes)
        ->with('produtos', $produtos)
        ->with('categorias', $categorias)
        ->with('certificado', $certificado)
        ->with('bairro', $pedido->_bairro)
        ->with('title', 'Finalizar Pedido iFood '.$id);

    }

    private function produtosMaisVendidos(){

        $abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
        ->where('usuario_id', get_id_user())
        ->where('status', 0)
        ->orderBy('id', 'desc')
        ->first();
        $filial = -1;

        if($abertura){
            $filial = $abertura->filial_id;
            if($filial == null){
                $filial = -1;
            }
        }
        $itens = ItemVendaCaixa::
        selectRaw('item_venda_caixas.*, count(quantidade) as qtd')
        ->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
        ->join('produtos', 'produtos.id', '=', 'item_venda_caixas.produto_id')
        ->where('venda_caixas.empresa_id', $this->empresa_id)
        ->groupBy('item_venda_caixas.produto_id')
        ->orderBy('qtd')
        ->when(empresaComFilial(), function ($q) use ($filial) {
            return $q->where(function($query) use ($filial){
                $query->where('produtos.locais', 'like', "%{$filial}%");
            });
        })
        ->limit(21)
        ->get();

        $produtos = [];
        foreach($itens as $i){
            $p = Produto::find($i->produto_id);
            if(!$p->inativo){
                array_push($produtos, $p);
            }
        }
        return $produtos;
    }


    private function getRascunhos(){
        return VendaCaixa::
        where('rascunho', 1)
        ->where('empresa_id', $this->empresa_id)
        ->limit(20)
        ->orderBy('id', 'desc')
        ->get();
    }

    private function getConsignadas(){
        return VendaCaixa::
        where('consignado', 1)
        ->where('empresa_id', $this->empresa_id)
        ->limit(20)
        ->orderBy('id', 'desc')
        ->get();
    }

    private function addAtributes($pedido, $config){

        $temp = [];
        foreach($pedido->itens as $i){

            $produtoInit = $i->produto->produto;

            $i->produto->valor_venda = (float)$i->valor_unitario;
            $i->valorAdicional = 0;

            $i->produto_id = $produtoInit->id;
            $i->produto->nome = $i->produto->nome;
            $i->item_pedido = null;
            $i->imagem = $i->produto->imagem;
            array_push($temp, $i);
        }


        return $temp;
    }

    public function imprimirPedido($id){
        $pedido = PedidoIfood::findOrFail($id);

        $cupom = new CupomPedidoIfood($pedido);
        $cupom->monta();
        $pdf = $cupom->render();

        return response($pdf)
        ->header('Content-Type', 'application/pdf');
    }
}
