<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PedidoDelivery;
use App\Models\ItemPedidoDelivery;
use App\Models\DeliveryConfig;
use App\Models\ClienteDelivery;
use NFePHP\DA\NFe\PedidoPrint;
use App\Models\VendaCaixa;
use App\Models\ConfigNota;
use Comtele\Services\CreditService;
use Comtele\Services\TextMessageService;
use App\Models\EnderecoDelivery;
use App\Models\ProdutoDelivery;
use App\Models\CategoriaProdutoDelivery;
use App\Models\Produto;
use App\Models\Usuario;
use App\Models\ItemPizzaPedido;
use App\Models\ComplementoDelivery;
use App\Models\Certificado;
use App\Models\ItemPedidoComplementoDelivery;
use App\Models\BairroDelivery;
use App\Models\Categoria;
use App\Models\ConfigCaixa;
use App\Models\BairroDeliveryLoja;
use App\Models\Cliente;
use App\Models\TamanhoPizza;
use App\Models\Funcionario;
use App\Models\Acessor;
use App\Models\Cidade;
use App\Models\Pais;
use App\Models\GrupoCliente;
use App\Models\AvaliacaoDelivery;
use App\Models\Motoboy;
use App\Models\PedidoMotoboy;
use App\Models\AberturaCaixa;
use App\Models\ItemVendaCaixa;

class PedidoDeliveryController extends Controller
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

	public function today(){

		$pedidosNovo = $this->filtroPedidos(date("Y-m-d"),
			date('Y-m-d', strtotime('+1 day')), 'novo');

		$pedidosAprovado = $this->filtroPedidos(date("Y-m-d"),
			date('Y-m-d', strtotime('+1 day')), 'aprovado');

		$pedidosCancelado = $this->filtroPedidos(date("Y-m-d"),
			date('Y-m-d', strtotime('+1 day')), 'cancelado');

		$pedidosFinalizado = $this->filtroPedidos(date("Y-m-d"),
			date('Y-m-d', strtotime('+1 day')), 'finalizado');

		$carrinho = [];

		return view('pedidosDelivery/list')
		->with('tipo', 'Pedidos de Hoje')
		->with('pedidosNovo', $pedidosNovo)
		->with('pedidosAprovado', $pedidosAprovado)
		->with('pedidosCancelado', $pedidosCancelado)
		->with('pedidosFinalizado', $pedidosFinalizado)
		->with('carrinho', $carrinho)
		->with('somaNovos', $this->somaPedidos($pedidosNovo))
		->with('somaAprovados', $this->somaPedidos($pedidosAprovado))
		->with('somaCancelados', $this->somaPedidos($pedidosCancelado))
		->with('somaFinalizados', $this->somaPedidos($pedidosFinalizado))
		->with('dataInicial', date('d/m/Y'))
		->with('dataFinal', date('d/m/Y'))
		->with('title', 'Pedidos de Delivery');

	}

	private function somaPedidos($arr){
		$v = 0;
		foreach($arr as $r){

			$v += $r->somaItens();
		}
		return $v;
	}

	private function somaCarrinho($arr){
		$v = 0;
		foreach($arr as $r){
			$v += $r->somaCarrinho();
		}
		return $v;
	}

	public function filtro(Request $request){
		$dataInicial = $request->data_inicial;
		$dataFinal = $request->data_final;

		$pedidosNovo = $this->filtroPedidos($this->parseDate($dataInicial),
			$this->parseDate($dataFinal, true), 'novo');

		$pedidosAprovado = $this->filtroPedidos($this->parseDate($dataInicial),
			$this->parseDate($dataFinal, true), 'aprovado');

		$pedidosCancelado = $this->filtroPedidos($this->parseDate($dataInicial),
			$this->parseDate($dataFinal, true), 'cancelado');


		$pedidosFinalizado = $this->filtroPedidos($this->parseDate($dataInicial),
			$this->parseDate($dataFinal, true), 'finalizado');

		$carrinho = [];
		return view('pedidosDelivery/list')
		->with('tipo', 'Pedidos de Hoje')
		->with('pedidosNovo', $pedidosNovo)
		->with('pedidosAprovado', $pedidosAprovado)
		->with('pedidosCancelado', $pedidosCancelado)
		->with('pedidosFinalizado', $pedidosFinalizado)
		->with('somaNovos', $this->somaPedidos($pedidosNovo))
		->with('somaAprovados', $this->somaPedidos($pedidosAprovado))
		->with('somaCancelados', $this->somaPedidos($pedidosCancelado))
		->with('somaFinalizados', $this->somaPedidos($pedidosFinalizado))
		->with('dataInicial', $dataInicial)
		->with('dataFinal', $dataFinal)
		->with('carrinho', $carrinho)
		->with('title', 'Pedidos de Delivery periodo '. $dataInicial . ' até ' . $dataFinal);
	}

	public function verCarrinhos(){
		$pedidos = PedidoDelivery::
		where('estado', 'novo')
		->where('valor_total', 0)
		->get();

		return view('pedidosDelivery/carrinhos')
		->with('pedidos', $pedidos)
		->with('title', 'Carrinhos em Aberto');
	}

	public function verCarrinho($id){
		$pedido = PedidoDelivery::
		where('id', $id)
		->first();

		return view('pedidosDelivery/verCarrinho')
		->with('pedido', $pedido)
		->with('title', 'Itens do Carrinho Aberto');
	}

	public function push($id){
		$pedido = PedidoDelivery::
		where('id', $id)
		->first();

		return view('push/new')
		->with('pushJs', true)
		->with('titulo', $this->randomTitles())
		->with('mensagem', $this->randomMensagem($pedido))
		->with('title', 'Nova Push');
	}

	private function randomTitles(){
		$titles = [
			'Fecha seu carrinho conosco',
			'Vamos finaizar o pedido',
			'Não perca isso',
			'Não deixe de finalizar'
		];
		return $titles[rand(0,3)];
	}

	private function randomMensagem($pedido){
		$messages = [
			'Seu carrinho esta em, R$ '. number_format($pedido->somaItens(), 2),
			'Vamos fechar este pedido preparamos um desconto para você',
			'Finalize já este carrinho conosco, preparamos o melhor para você :)',
		];
		return $messages[rand(0,2)];
	}

	public function gerarQrCode($pedido){
		if(env("QRCODE_MAPS") == 1 && $pedido->endereco_id != null){
			$linkDeEntrega = env("PATH_URL") . "/rotaEntrega/$pedido->id";

			\QrCode::size(250)
			->format('png')
			->generate($linkDeEntrega, public_path('rotas/'.$pedido->id.'.png'));
		}
	}

	public function verPedido($id){
		$pedido = PedidoDelivery::findOrFail($id);

		$saldoSms = 0;
		// $this->gerarQrCode($pedido);

		return view('pedidosDelivery/detalhe')
		->with('tipo', 'Detalhes do Pedido')
		->with('pedido', $pedido)
		->with('pedidoDeliveryJs', true)
		->with('title', 'Pedidos de Delivery');
		
	}

	public function alterarStatus($id){
		$item = ItemPedidoDelivery
		::where('id', $id)
		->first();

		$item->status = true;
		$item->save();
		return redirect("/pedidosDelivery/verPedido/".$item->pedido->id);
	}

	public function alterarPedido(Request $request){
		$id = $request->id;
		$tipo = $request->tipo;

		$pedido = PedidoDelivery::findOrFail($id);
		$motoboys = Motoboy::where('empresa_id', $this->empresa_id)
		->get();

		if(valida_objeto($pedido)){
			return view('pedidosDelivery/alterarEstado')
			->with('tipo', 'Detalhes do Pedido')
			->with('pedido', $pedido)
			->with('tipo', $tipo)
			->with('motoboys', $motoboys)
			->with('title', 'Pedidos de Delivery');
		}else{
			redirect('/403');
		}
	}

	public function emAberto(){
		$pedidos = PedidoDelivery::
		where('estado', 'novo')
		->where('valor_total', '>', 0)
		->get();

		return response()->json(count($pedidos), 200);
	}

	public function confirmarAlteracao(Request $request){
		$config = ConfigNota::first();

		if($config == null){

			session()->flash('mensagem_erro', 'Defina a configuração do emitente para continuar!');
			return redirect()->back();
		}

		$id = $request->id;
		$tipo = $request->tipo;

		$pedido = PedidoDelivery
		::where('id', $id)
		->first();

		$valorEntrega = 0;

		if($request->motoboy_id){
			$motoboy = Motoboy::findOrFail($request->motoboy_id);

			PedidoMotoboy::create([
				'motoboy_id' => $motoboy->id, 
				'pedido_id' => $pedido->id,
				'valor' => $motoboy->valor_entrega_padrao,
				'status_pagamento' => 0
			]);
			$valorEntrega = $motoboy->valor_entrega_padrao;
		}
		
		$msg = '';
		if($tipo == 'aprovado'){
			$msg = 'Seu pedido foi aprovado, esta sendo preparado par envio';
		}

		if($tipo == 'cancelado'){
			$msg = 'Seu pedido foi cancelado!';
		}

		if(strlen($msg) > 0){
			$this->sendPushAlteracao($msg, $pedido->cliente);
		}

		$pedido->estado = $tipo;
		$pedido->motivoEstado = $request->motivoEstado ?? '';

		if($tipo == 'finalizado'){
			$pedido->horario_entrega = date('H:i');
		}

		$pedido->save();

		$observacao = "Pedido: " . $pedido->id . ", Cliente: " . $pedido->cliente->nome . " " 
		.$pedido->cliente->sobre_nome .
		($pedido->endereco_id !=  NULL ? 
			" - Endereço: " . $pedido->endereco->rua .", " . $pedido->endereco->numero ." - "
			.$pedido->endereco->_bairro->nome : '');

		if($tipo == 'finalizado'){
			//Abrir frente de caixa

			$usuario = Usuario::find(get_id_user());
			$tiposPagamento = VendaCaixa::tiposPagamento();
			$certificado = Certificado::where('empresa_id', $this->empresa_id)->first();
			
			$tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();
			
			$categorias = Categoria::orderBy('nome')->get();
			$clientes = Cliente::orderBy('razao_social')->get();

			$atalhos = ConfigCaixa::
			where('usuario_id', get_id_user())
			->first();

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

			$rascunhos = $this->getRascunhos();
			$funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get(); 
			$produtosMaisVendidos = $this->produtosMaisVendidos();

			return view('frontBox/main3')
			->with('itens', $this->addAtributes($pedido->itens))
			->with('frenteCaixa', true)
			->with('delivery_id', $pedido->id)
			->with('valor_total', $pedido->valor_total)
			->with('atalhos', $atalhos)
			->with('acessores', $acessores)
			->with('rascunhos', $rascunhos)
			->with('estados', $estados)
			->with('funcionarios', $funcionarios)
			->with('cidades', $cidades)
			->with('pais', $pais)
			->with('grupos', $grupos)
			->with('produtosMaisVendidos', $produtosMaisVendidos)
			->with('tiposPagamento', $tiposPagamento)
			->with('tiposPagamentoMulti', $tiposPagamentoMulti)
			->with('config', $config)
			->with('certificado', $certificado)
			->with('usuario', $usuario)
			->with('vendedores', $vendedores)
			->with('observacao', $observacao)
			->with('valor_entrega', $valorEntrega)
			->with('tiposPagamentoMulti', $tiposPagamentoMulti)
			->with('categorias', $categorias)
			->with('clientes', $clientes)
			->with('title', 'Finalizar Comanda '.$id);
		}else{

			session()->flash('mensagem_sucesso', 'Pedido Alterado!');
			return redirect('/pedidosDelivery');
		}

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


	private function sendPushAlteracao($msg, $cliente){

		$tkTemp = [];
		if(count($cliente->tokens) > 0){
			foreach($cliente->tokens as $t){
				if(!in_array($t->user_id, $tkTemp)){

					array_push($tkTemp, $t->user_id);
				}
			}

			$data = [
				'heading' => [
					"en" => 'Alteração de pedido'
				],
				'content' => [
					"en" => $msg
				],
				'image' => '',
				'referencia_produto' => 0,
			];

			$this->sendMessageOneSignal($data, $tkTemp);
		}

		if(count($cliente->tokensWeb) > 0){
			foreach($cliente->tokensWeb as $t){
				if(!in_array($t->token, $tkTemp)){

					array_push($tkTemp, $t->token);
				}
			}

			$data = [
				'heading' => [
					"en" => 'Alteração de pedido'
				],
				'content' => [
					"en" => $msg
				],
				'image' => '',
				'referencia_produto' => 0,
			];

			$this->sendMessageOneSignal($data, $tkTemp);
		}

	}

	private function addAtributes($itens){
		$temp = [];
		foreach($itens as $i){
			$i->produto;

			$valorAdicional = 0;

			foreach($i->itensAdicionais as $ad){
				$valorAdicional += $ad->adicional->valor;
			}

			$i->valorAdicional = $valorAdicional;
			$somaValores = 0;
			if(sizeof($i->sabores) > 0){
				$i->sabores;

				$maiorValor = 0;

				foreach($i->sabores as $sb){

					
					$sb->produto->produto;
					
					$v = $sb->maiorValor($sb->sabor_id, $i->tamanho_id);
					$somaValores += $v;
					if($v > $maiorValor) $maiorValor = $v;


				}
				if(env("DIVISAO_VALOR_PIZZA") == 1){
					$maiorValor = $somaValores/sizeof($i->sabores);
				}

				$i->maiorValor = $maiorValor;
			}else{
				$i->produto->valor_venda = $i->produto->valor;
			}
			$i->produto_id = $i->produto->produto->id; // converte id de delivery para produto comum
			$i->produto->nome = $i->produto->produto->nome;
			$i->produto->imagem = $i->produto->produto->imagem;
			array_push($temp, $i);
		}
    	// echo json_encode($temp);
		return $temp;
	}

	public function irParaFrenteCaixa($id){

		$pedido = PedidoDelivery
		::where('id', $id)
		->first();

		$config = ConfigNota::first();
		$tiposPagamento = VendaCaixa::tiposPagamento();

		$observacao = "Pedido: " . $pedido->id . ", Cliente: " . $pedido->cliente->nome . " " 
		.$pedido->cliente->sobre_nome .
		($pedido->endereco_id !=  NULL ? 
			" - Endereço: " . $pedido->endereco->rua .", " . $pedido->endereco->numero ." - "
			.$pedido->endereco->_bairro->nome : '');

		// if($pedido)

		$usuario = Usuario::find(get_id_user());
		$atalhos = ConfigCaixa::
		where('usuario_id', get_id_user())
		->first();

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

		$rascunhos = $this->getRascunhos();
		$funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get(); 
		$produtosMaisVendidos = $this->produtosMaisVendidos();

		$tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();
		$certificado = Certificado::where('empresa_id', $this->empresa_id)->first();

		$valorEntrega = 0;

		$categorias = Categoria::orderBy('nome')->get();
		$clientes = Cliente::orderBy('razao_social')->get();
		
		return view('frontBox/main3')
		->with('itens', $this->addAtributes($pedido->itens))
		->with('frenteCaixa', true)
		->with('delivery_id', $pedido->id)
		->with('valor_total', $pedido->valor_total)
		->with('atalhos', $atalhos)
		->with('acessores', $acessores)
		->with('rascunhos', $rascunhos)
		->with('estados', $estados)
		->with('funcionarios', $funcionarios)
		->with('cidades', $cidades)
		->with('pais', $pais)
		->with('grupos', $grupos)
		->with('produtosMaisVendidos', $produtosMaisVendidos)
		->with('tiposPagamento', $tiposPagamento)
		->with('tiposPagamentoMulti', $tiposPagamentoMulti)
		->with('config', $config)
		->with('certificado', $certificado)
		->with('usuario', $usuario)
		->with('vendedores', $vendedores)
		->with('observacao', $observacao)
		->with('valor_entrega', $valorEntrega)
		->with('tiposPagamentoMulti', $tiposPagamentoMulti)
		->with('categorias', $categorias)
		->with('clientes', $clientes)
		->with('title', 'Finalizar Comanda '.$id);
		
	}

	private function parseDate($date, $plusDay = false){
		if($plusDay == false)
			return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
		else
			return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
	}

	private function filtroPedidos($dataInicial, $dataFinal, $estado, $sinal = '>'){
		$pedidos = PedidoDelivery::
		whereBetween('data_registro', [$dataInicial, 
			$dataFinal])
		->where('estado', $estado)
		->where('valor_total', $sinal, 0)
		->where('empresa_id', $this->empresa_id)
		->get();
		return $pedidos;
	}

	public function print($id){
		$pedido = PedidoDelivery::
		where('id', $id)
		->first();

		if(valida_objeto($pedido)){
			$public = env('SERVIDOR_WEB') ? 'public/' : '';
			$ped = new PedidoPrint($pedido);
			$ped->monta();
			$pdf = $ped->render();

			return response($pdf)
			->header('Content-Type', 'application/pdf');
		}else{
			return redirect('/403');
		}
	}

	public function sendSms(Request $request){

		$phone = $request['telefone'];
		$msg = $request['texto'];
		$res = $this->sendGo($phone, $msg);
		echo json_encode($res);
	}

	private function sendGo($phone, $msg){
		$nomeEmpresa = env('SMS_NOME_EMPRESA');
		$nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
		$nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
		$content = $msg . " Att, $nomeEmpresa";
		$textMessageService = new TextMessageService(env('SMS_KEY'));
		$res = $textMessageService->send("Sender", $content, [$phone]);
		return $res;
	}

	public function sendPush(Request $request){
		$cliente = ClienteDelivery::where('id', $request->cliente)
		->first();
		$tkTemp = [];
		if(count($cliente->tokens) > 0){
			foreach($cliente->tokens as $t){
				if(!in_array($t->token, $tkTemp)){

					array_push($tkTemp, $t->user_id);
				}
			}

			$data = [
				'heading' => [
					"en" => $request->titulo
				],
				'content' => [
					"en" => $request->texto
				],
				'image' => $request->imagem ?? '',
				'referencia_produto' => 0,
			];

			$this->sendMessageOneSignal($data, $tkTemp);
		}
		echo json_encode('sucesso');

	}

	public function sendPushWeb(Request $request){
		$cliente = ClienteDelivery::where('id', $request->cliente)
		->first();
		$tkTemp = [];
		if(sizeof($cliente->tokensWeb) > 0){
			foreach($cliente->tokensWeb as $t){
				if(!in_array($t->token, $tkTemp)){

					array_push($tkTemp, $t->token);
				}
			}

			$data = [
				'heading' => [
					"en" => $request->titulo
				],
				'content' => [
					"en" => $request->texto
				],
				'image' => $request->imagem ?? '',
				'referencia_produto' => 0,
			];

			$this->sendMessageOneSignal($data, $tkTemp);
		}
		echo json_encode('sucesso');

	}

	public function sendMessageOneSignal($data, $tokens = null){

		$fields = [
			'app_id' => env('ONE_SIGNAL_APP_ID'),
			'contents' => $data['content'],
			'headings' => $data['heading'],
			'large_icon' => env('PATH_URL').'/imgs/logo.png',
			'small_icon' => 'notification_icon'
		];

		if($data['image'] != '')
			$fields['big_picture'] = $data['image'];

		if($tokens == null){
			$fields['included_segments'] = array('All');
			if($data['image'] != '')
				$fields['chrome_web_image'] = $data['image'];
		}else{
			$fields['include_player_ids'] = $tokens;
		}


		if($data['referencia_produto'] > 0){
			$fields['web_url'] = env('PATH_URL') . "/cardapio/verProduto/" . $data['referencia_produto'];
			$produtoDelivery = ProdutoDelivery::find($data['referencia_produto']);
			if($produtoDelivery != null){
				$produtoDelivery->pizza;
				$produtoDelivery->galeria;
				$produtoDelivery->categoria;
				$produtoDelivery->produto;
				$fields['data'] = ["referencia" => $produtoDelivery];
			}
		}

		$fields = json_encode($fields);
		// print("\nJSON sent:\n");
		// print($fields);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
			'Authorization: Basic '.env('ONE_SIGNAL_KEY')));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	public function frente(){
		$config = DeliveryConfig::
		where('empresa_id', $this->empresa_id)
		->first();

		$bairros = [];

		$clientes = ClienteDelivery::orderBy('nome')
		->where('empresa_id', $this->empresa_id)
		->get();
		
		$tamanhos = TamanhoPizza::where('empresa_id', $this->empresa_id)->get();

		$adicionais = ComplementoDelivery::where('empresa_id', $this->empresa_id)->get();

		foreach($adicionais as $a){
			$a->nome = $a->nome();
		}

		$categorias = CategoriaProdutoDelivery::
		where('empresa_id', $this->empresa_id)
		->get();

		$produtos = ProdutoDelivery::
		select('produto_deliveries.*')
		->orderBy('produtos.nome')
		->join('produtos', 'produtos.id', '=', 'produto_deliveries.produto_id')
		->where('produtos.empresa_id', $this->empresa_id)
		->limit(21)
		->get();

		if($config != null){
			return view('pedidosDelivery/frente')
			->with('frentePedidoDeliveryJs', true)
			->with('config', $config)
			->with('bairros', $bairros)
			->with('categorias', $categorias)
			->with('produtos', $produtos)
			->with('clientes', $clientes)
			->with('tamanhos', $tamanhos)
			->with('adicionais', $adicionais)
			->with('title', 'Frente de Pedido');
		}else{

			session()->flash('mensagem_erro', 'Defina as configurações!');
			return redirect('/configDelivery');
		}

	}

	public function clientes(){
		$clientes = ClienteDelivery::all();
		$arr = array();
		foreach($clientes as $c){
			$t = str_replace(" ", "", $c->celular);
			$t = str_replace("-", "", $t);
			$arr[$c->id. ' - ' .$c->nome . ' | ' . $t ] = null;
                //array_push($arr, $temp);
		}
		return response()->json($arr, 200);
	}

	public function abrirPedidoCaixa(Request $request){

		if(isset($request->cliente)){
			$pedidoEmAberto = PedidoDelivery::where('estado', 'novo')
			->where('cliente_id', $request->cliente)
			->first();
			if($pedidoEmAberto == null){
				$pedido = PedidoDelivery::create([
					'cliente_id' => $request->cliente,
					'valor_total' => 0,
					'telefone' => '',
					'observacao' => '',
					'forma_pagamento' => '',
					'estado'=> 'novo',
					'motivoEstado'=> '',
					'endereco_id' => NULL,
					'troco_para' => 0,
					'desconto' => 0,
					'cupom_id' => NULL,
					'app' => false,
					'empresa_id' => $this->empresa_id
				]);
				return response()->json($pedido, 200);

			}else{
				session()->flash('mensagem_erro', 'Este cliente possui um pedido em aberto, PEDIDO ' . $pedidoEmAberto->id. '!');
				return response()->json($pedidoEmAberto, 200);

			}
		}
		return response()->json(false, 403);

	}

	public function novoClienteDeliveryCaixa(Request $request){
		$cli = ClienteDelivery::create(
			[
				'nome' => $request->nome,
				'sobre_nome' => $request->sobre_nome,
				'celular' => $request->celular,
				'email' => '',
				'token' => '',
				'ativo' => 1,
				'senha' => $request->senha ? md5($request->senha) : md5('123'),
				'empresa_id' => $this->empresa_id
			]
		);
		if($cli){
			// novo cliente renderiza nova view caixa
			$cliente = ClienteDelivery::find($cli->id);
			$pedido = PedidoDelivery::create([
				'cliente_id' => $cliente->id,
				'valor_total' => 0,
				'telefone' => '',
				'observacao' => '',
				'forma_pagamento' => '',
				'estado'=> 'novo',
				'motivoEstado'=> '',
				'endereco_id' => NULL,
				'troco_para' => 0,
				'desconto' => 0,
				'cupom_id' => NULL,
				'app' => false,
				'empresa_id' => $this->empresa_id
			]);
			// criou o pedido
			if($pedido){
				return redirect('pedidosDelivery/frenteComPedido/'.$pedido->id);
			}
		}else{
			return redirect('pedidosDelivery/frenteErro');
		}
	}

	public function frenteComPedido($id){
		$pedido = PedidoDelivery::findOrFail($id);

		$clientes = ClienteDelivery::orderBy('nome')
		->where('empresa_id', $this->empresa_id)
		->get();

		if($pedido->estado == 'ap' || $pedido->valor_total > 0){
			return redirect('/pedidosDelivery/verPedido/' . $pedido->id);
		}
		$config = DeliveryConfig::
		where('empresa_id', $this->empresa_id)
		->first();

		$bairros = BairroDeliveryLoja::orderBy('nome')
		->where('empresa_id', $this->empresa_id)->get();

		$produtos = ProdutoDelivery::
		select('produto_deliveries.*')
		->orderBy('produtos.nome')
		->join('produtos', 'produtos.id', '=', 'produto_deliveries.produto_id')
		->where('produtos.empresa_id', $this->empresa_id)
		->limit(21)
		->with('pizza')
		->get();

		$categorias = CategoriaProdutoDelivery::
		where('empresa_id', $this->empresa_id)
		->get();

		foreach($produtos as $p){
			$p->produto;
		}
		$tamanhos = TamanhoPizza::
		orderBy('nome')
		->where('empresa_id', $this->empresa_id)
		->get();;

		$adicionais = ComplementoDelivery::all();
		foreach($adicionais as $a){
			$a->nome = $a->nome();
		}

		$valorEntrega = 0;

		if($pedido->endereco){
			if($config->usar_bairros){
				$bairro = BairroDelivery::find($pedido->endereco->bairro_id);
				$valorEntrega = $bairro->valor_entrega;
			}else{
				$valorEntrega = $config->valor_entrega;
			}
		}

		$pizzas = [];

		foreach($produtos as $p){

			$p->pizza;
			$p->produto;

			foreach($p->pizza as $pz){
				$pz->tamanho;
			}
			if(sizeof($p->pizza) > 0){
				array_push($pizzas, $p);
			}

		}

		return view('pedidosDelivery/frente')
		// ->with('frentePedidoDeliveryJs', true)
		// ->with('frentePedidoDeliveryPedidoJs', true)
		->with('pedido', $pedido)
		->with('config', $config)
		->with('produtos', $produtos)
		->with('pizzas', $pizzas)
		->with('categorias', $categorias)
		->with('bairros', $bairros)
		->with('adicionais', $adicionais)
		->with('tamanhos', $tamanhos)
		->with('clientes', $clientes)
		->with('valorEntrega', $valorEntrega)
		->with('title', 'Frente de Pedido');

	}

	public function setEndereco(Request $request){
		$pedido = PedidoDelivery::find($request->pedido_id);
		$pedido->endereco_id = $request->endereco;
		if($request->endereco == '') $pedido->endereco_id = NULL;
		$res = $pedido->save();

		$endereco = EnderecoDelivery::with('_bairro')->find($request->endereco);
		return response()->json($endereco, 200);
	}

	public function novoEnderecoClienteCaixa(Request $request){
		$pedido = PedidoDelivery::findOrFail($request->pedido_id);

		$config = DeliveryConfig::
		where('empresa_id', $this->empresa_id)
		->first();

		$endereco = EnderecoDelivery::create(
			[
				'cliente_id' => $pedido->cliente_id,
				'rua' => $request->rua ?? '',
				'numero' => $request->numero ?? '',
				'bairro' => $request->bairro ?? '',
				'bairro_id' => $request->bairro_id ?? 0,
				'referencia' => $request->referencia ?? '',
				'latitude' => '',
				'longitude' => '',
				'cidade_id' => $config->cidade_id
			]
		);

		$pedido->endereco_id = $endereco->id;
		$pedido->save();
		return redirect('/pedidosDelivery/frenteComPedido/'.$pedido->id);
	}

	public function saveItemCaixa(Request $request){
		$pedido = PedidoDelivery::find($request->pedido_id);

		$this->_validateItem($request);

		$produto = $request->input('produto');
		$produto = explode("-", $produto);
		$produto = $produto[0];

		$result = ItemPedidoDelivery::create([
			'pedido_id' => $pedido->id,
			'produto_id' => $produto,
			'quantidade' => str_replace(",", ".", $request->quantidade),
			'status' => false,
			'tamanho_id' => $request->tamanho_pizza_id ?? NULL,
			'observacao' => $request->observacao ?? '',
			'valor' => str_replace(",", ".", $request->valor)
		]);

		$saborDup = false;
		if($request->tamanho_pizza_id && $request->sabores_escolhidos){
			$saborDup = false;

			$sabores = explode(",", $request->sabores_escolhidos);
			if(count($sabores) > 0){
				foreach($sabores as $sab){
					$prod = Produto
					::where('id', $sab)
					->first();

					$item = ItemPizzaPedido::create([
						'item_pedido' => $result->id,
						'sabor_id' => $prod->delivery->id,
					]);

					if($prod->id == $produto) $saborDup = true;
				}
			}else{
				$item = ItemPizzaPedido::create([
					'item_pedido' => $result->id,
					'sabor_id' => $produto_id,
				]);
			}
		}

		if(!$saborDup && $request->tamanho_pizza_id){

			$item = ItemPizzaPedido::create([
				'item_pedido' => $result->id,
				'sabor_id' => $produto,
			]);

		}

		else if($request->tamanho_pizza_id){

			$item = ItemPizzaPedido::create([
				'item_pedido' => $result->id,
				'sabor_id' => $produto,
			]);
		}


		if($request->adicioanis_escolhidos){
			$adicionais = explode(",", $request->adicioanis_escolhidos);
			foreach($adicionais as $id){

				$id = (int)$id;

				$adicional = ComplementoDelivery
				::where('id', $id)
				->first();


				$item = ItemPedidoComplementoDelivery::create([
					'item_pedido_id' => $result->id,
					'complemento_id' => $adicional->id,
					'quantidade' => str_replace(",", ".", $request->quantidade),
				]);
			}
		}

		session()->flash('mensagem_sucesso', 'Item Adicionado!');
		return redirect()->back();

	}

	private function _validateItem(Request $request){
		$validaTamanho = false;
		if($request->input('produto')){
			$produto = $request->input('produto');
			$produto = explode("-", $produto);
			$produto = $produto[0];

			$p = ProdutoDelivery::
			where('id', $produto)
			->first();

			if(strpos(strtolower($p->categoria->nome), 'izza') !== false){
				$validaTamanho = true;
			}
		}
		$rules = [
			'produto' => 'required',
			'quantidade' => 'required',
			'tamanho_pizza_id' => $validaTamanho ? 'required' : '',
		];

		$messages = [
			'produto.required' => 'O campo produto é obrigatório.',
			'produto.min' => 'Clique sobre o produto desejado.',
			'quantidade.required' => 'O campo quantidade é obrigatório.',
			'tamanho_pizza_id.required' => 'Selecione um tamanho.',
		];

		$this->validate($request, $rules, $messages);
	}

	public function produtos(){
		$products = ProdutoDelivery::all();
		$arr = array();
		foreach($products as $p){
			if($p->status){
				$arr[$p->id. ' - ' .$p->produto->nome] = null;
			}
                //array_push($arr, $temp);
		}
		echo json_encode($arr);
	}

	public function deleteItem($id){
		$item = ItemPedidoDelivery::find($id);
		$item->delete();

		session()->flash('mensagem_sucesso', 'Item Removido!');
		return redirect('/pedidosDelivery/frenteComPedido/'.$item->pedido->id);
	}

	public function getProdutoDelivery($id){
		$produto = ProdutoDelivery::find($id);
		foreach($produto->pizza as $tp){
			$tp->tamanho;
		}
		$produto->produto;
		return response()->json($produto, 200);
	}

	public function frenteComPedidoFinalizar(Request $request){
		$pedido = PedidoDelivery::find($request->pedido_id);
		$total = $pedido->somaItens();
		if($pedido->endereco_id != NULL){
			$config = DeliveryConfig::first();
			$total -= $config->valor_entrega;
		}

		$total += str_replace(",", ".", $request->taxa_entrega);

		$pedido->valor_total = $total;
		$pedido->estado = 'ap';
		$pedido->telefone = $request->telefone;
		$pedido->troco_para = str_replace(",", ".", $request->troco_para);
		$pedido->data_registro = date('Y-m-d H:i:s');
		$pedido->save();

		session()->flash('mensagem_sucesso', 'Pedido realizado!');

		echo "<script>window.open('". env('PATH_URL') . '/pedidosDelivery/print/' . $pedido->id ."', '_blank');</script>";

		return redirect('/pedidosDelivery/frente');
	}

	public function removerCarrinho($id){
		$pedido = PedidoDelivery::find($id);

		$pedido->delete();
		return redirect('/pedidosDelivery/verCarrinhos');
	}

	public function store(Request $request){
		$data = $request->data;
		try{
			$pedido = PedidoDelivery::findOrFail($data['pedido_id']);

			if(isset($data['sabores'])){
				$prodId = $data['sabores'][0];
			}else{
				$prodId = $data['produto'];
			}
			$item = ItemPedidoDelivery::create([
				'pedido_id' => $pedido->id,
				'produto_id' => $prodId,
				'quantidade' => str_replace(",", ".", $data['qtd']),
				'status' => false,
				'tamanho_id' => $data['tamanho_pizza_id'] ?? NULL,
				'observacao' => $data['observacao'] ?? '',
				'valor' => str_replace(",", ".", $data['valor'])
			]);

			if(isset($data['sabores'])){
				foreach($data['sabores'] as $s){
					ItemPizzaPedido::create([
						'item_pedido' => $item->id,
						'sabor_id' => $s,
					]);
				}
			}

			if(isset($data['adicionais'])){
				foreach($data['adicionais'] as $a){
					ItemPedidoComplementoDelivery::create([
						'item_pedido_id' => $item->id,
						'complemento_id' => $a,
						'quantidade' => str_replace(",", ".", $data['qtd']),
					]);
				}
			}

			return response()->json($pedido, 200);
		}catch(\Exception $e){
			return response()->json($e->getMessage(), 401);      
		}      
	}

	public function find($id){
		try{
			$item = PedidoDelivery::with('itens')
			->with('cliente')
			->with('endereco')
			->findOrFail($id);
			return response()->json($item, 200);
		}catch(\Exception $e){
			return response()->json($e->getMessage(), 401);      
		}  
	}

	public function finalizarFrente(Request $request){
		$pedido = PedidoDelivery::findOrFail($request->pedido_id);

		$pedido->troco_para = $request->troco_para ? __replace($request->troco_para) : 0;
		$pedido->observacao = $request->observacao_pedido ?? '';
		$pedido->estado = $request->estado_pedido;
		$pedido->forma_pagamento = $request->forma_pagamento;
		$pedido->valor_entrega = (float)$request->valor_entrega;
		$total = 0;
		foreach($pedido->itens as $i){
			$total += $i->valor;
			$valorAdicional = 0;
			foreach($i->itensAdicionais as $it){
				$valorAdicional += $it->adicional->valor;
			}
			$total += $valorAdicional;
			$i->valor = $i->valor + $valorAdicional;
			// echo $i->valor;
			// die;
			$i->save();
		}
		$pedido->valor_total = $total+$pedido->valor_entrega;
		// echo $pedido->valor_total;
		// die;
		$pedido->save();

		session()->flash('mensagem_sucesso', 'Pedido finalizado com sucesso!');
		return redirect('/pedidosDelivery/frente');
	}

	public function pedidosNaoLidos(Request $request){
		try{
			$usr = session('user_logged');
			if(!isset($usr['id'])){
				return response()->json("", 401);
			}
			$pedido = PedidoDelivery::where('pedido_lido', false)
			->orderBy('id', 'asc')
			->with('itens')
			->where('app', 1)
			->whereDate('created_at', date('Y-m-d'))
			->where('empresa_id', $this->empresa_id)
			->first();
			if($pedido == null){
				return response()->json("", 200);
			}
			return view('pedidosDelivery/novo_pedido', compact('pedido'));
		}catch(\Exception $e){
			return response()->json($e->getMessage(), 401);
		}
	}

	public function lerPedido(Request $request){
		$pedido = PedidoDelivery::findOrFail($request->pedido_id);

		$pedido->estado = $request->estado;

		$pedido->pedido_lido = true;
		$pedido->horario_leitura = date('H:i');
		$pedido->save();

		if($pedido->estado== 'aprovado'){
			session()->flash('mensagem_sucesso', 'Pedido lido e aprovado!');
		}else{
			session()->flash('mensagem_erro', 'Pedido lido e recusado!');
		}
		return redirect('/pedidosDelivery/verPedido/'.$pedido->id);
	}

	public function teste(){
		$somaAvaliacao = AvaliacaoDelivery::
		whereBetween('created_at', [
			date('Y-m-d',strtotime("-90 days")) . " 00:00:00",
			date('Y-m-d') . " 23:59:59"
		])
		->sum('nota');

		$countAvaliacao = AvaliacaoDelivery::
		whereBetween('created_at', [
			date('Y-m-d',strtotime("-90 days")) . " 00:00:00",
			date('Y-m-d') . " 23:59:59"
		])
		->count();

		if($countAvaliacao == 0) return $somaAvaliacao;
		return $somaAvaliacao/$countAvaliacao;
	}

	public function delete($id){
		$item = PedidoDelivery::find($id);
		$item->delete();

		session()->flash('mensagem_sucesso', 'Pedido Removido!');
		return redirect('/pedidosDelivery');
	}

}
