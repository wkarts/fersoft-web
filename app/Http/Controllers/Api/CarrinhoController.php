<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProdutoEcommerce;
use App\Models\PedidoEcommerce;
use App\Models\ConfigEcommerce;
use App\Models\Variation;
use App\Models\ItemPedidoEcommerce;
use App\Models\CupomDescontoEcommerce;
use App\Models\CupomEcommerceUtilizado;
use Illuminate\Support\Str;
use Mail;

class CarrinhoController extends Controller
{
	public function itens(Request $request){
		$cart = json_decode($request->ids);

		$produtos = [];
		foreach($cart as $i){
			// if(isset($i->variacao_id)){
			// 	$variacao = Variation::find($i->variacao_id);
			// }
			$produto = ProdutoEcommerce::find($i->id);
			$produto->produto;
			// foreach($produto->variations as $v){
			// 	$v->media;
			// }
			array_push($produtos, $produto);
		}
		return response()->json($produtos, 200);
	}

	public function salvarPedido(Request $request){
		try{
			$data = $request->data;

			$cliente = $data['cliente'];
			$carrinho = $data['carrinho'];
			$endereco = $data['endereco'];
			$total = $data['total'];
			$valorFrete = $data['valor_frete'];
			$tpFrete = $data['tipo_frete'];
			$cupom_desconto = $data['cupom_desconto'];
			$desconto = $data['desconto'];

			$cupom = CupomDescontoEcommerce::where('codigo', $cupom_desconto)
			->where('empresa_id', $request->empresa_id)
			->first();

			$pedidoData = [
				'cliente_id' => $cliente['id'],
				'endereco_id' => $endereco['id'],
				'status' => 0,
				'valor_total' => $total + $valorFrete,
				'valor_frete' => $valorFrete,
				'tipo_frete' => $tpFrete,
				'venda_id' => 0,
				'numero_nfe' => 0,
				'empresa_id' => $request->empresa_id,
				'observacao' => '',
				'rand_pedido' => '',
				'link_boleto' => '',
				'qr_code_base64' => '',
				'qr_code' => '',
				'transacao_id' => '',
				'forma_pagamento' => '',
				'status_pagamento' => '', 
				'status_detalhe' => '',
				'status_preparacao' => '',
				'codigo_rastreio' => '',
				'token' => Str::random(20),
				'cupom_desconto' => $cupom_desconto,
				'desconto' => $desconto
			];

			if($cupom){
				CupomEcommerceUtilizado::create([
					'cupom_id' => $cupom->id,
					'cliente_id' => $cliente['id']
				]);
			}

			$rsPedido = PedidoEcommerce::create($pedidoData);

			foreach($carrinho as $i){
				$itemData = [
					'pedido_id' => $rsPedido->id,
					'produto_id' => $i['id'],
					'quantidade' => $i['quantidade'],
				];

				ItemPedidoEcommerce::create($itemData);
			}

			return response()->json($rsPedido->token, 200);
		}catch(\Exception $e){
			return response()->json($e->getMessage(), 401);
		}

	}

	public function getPedido(Request $request){
		try{
			$pedido = PedidoEcommerce::
			where('token', $request->token)
			->first();
			$pedido->endereco;
			$pedido->cliente;
			$descricao = "";
			foreach($pedido->itens as $i){
				$i->produto->produto;

				$descricao .= $i->quantidade . " x " . $i->produto->nome . " "; 
			}
			$pedido->descricao = $descricao;

			$config = ConfigEcommerce::
			where('empresa_id', $request->empresa_id)
			->first();

			$pedido->mensagem_agradecimento = $config->mensagem_agradecimento;

			$pedido = $this->preparaValorTotal($pedido, $config);
			return response()->json($pedido, 200);
		}catch(\Exception $e){
			return response()->json($e->getMessage(), 401);
		}
	}

	private function preparaValorTotal($pedido, $config){
		$valor_total = $pedido->valor_total - $pedido->desconto;
		if($config->desconto_padrao_pix > 0){
			$pedido->total_pix = number_format($valor_total - (($valor_total *$config->desconto_padrao_pix)/100), 2, '.', '');
		}else{
			$pedido->total_pix = $valor_total;
		}

		if($config->desconto_padrao_cartao > 0){
			$pedido->total_cartao = number_format($valor_total  - (($valor_total *$config->desconto_padrao_cartao)/100), 2, '.', '');
		}else{
			$pedido->total_cartao = $valor_total;
		}

		if($config->desconto_padrao_boleto > 0){	
			$pedido->total_boleto = number_format($valor_total  - (($valor_total *$config->desconto_padrao_boleto)/100), 2, '.', '');
		}else{
			$pedido->total_boleto = $valor_total;
		}
		return $pedido;
	}

	public function processarPagamentoCartao(Request $request){
		$data = $request->data;

		$config = ConfigEcommerce::
		where('empresa_id', $request->empresa_id)
		->first();

		$pedido = PedidoEcommerce::find($data['pedido_id']);

		\MercadoPago\SDK::setAccessToken($config->mercadopago_access_token);
		$payment = new \MercadoPago\Payment();

		$payment->transaction_amount = $pedido->valor_total;
		$payment->description = $data['description'];
		$payment->token = $data['id'];
		$payment->installments = (int)$data['installments'];
		$payment->payment_method_id = $data['paymentMethodId'];

		$payer = new \MercadoPago\Payer();
		$payer->email = $data['email'];
		$payer->identification = array(
			"type" => $data['docType'] ?? 'CPF',
			"number" => $data['docNumber']
		);

		$payment->payer = $payer;

		$payment->save();

		if($payment->error){

			$error = $this->trataErros($payment->error);
			return response()->json($error, 401);

		}else{
			$pedido->transacao_id = $payment->id;
			$pedido->status_pagamento = $payment->status;
			$pedido->forma_pagamento = 'Cartão';
			$pedido->status_detalhe = $payment->status_detail;

			$pedido->status = 1;
			$pedido->save();

			$dataSuccess = [
				'id' => $payment->id,
				'status' => $payment->status
			];
			try{
				$this->sendMail($pedido);
			}catch(\Exception $e){

			}
			return response()->json($dataSuccess, 200);
		}

	}

	public function processarPagamentoPix(Request $request){
		$data = $request->data;

		$config = ConfigEcommerce::
		where('empresa_id', $request->empresa_id)
		->first();

		$pedido = PedidoEcommerce::find($data['pedido_id']);

		\MercadoPago\SDK::setAccessToken($config->mercadopago_access_token);
		$payment = new \MercadoPago\Payment();

		$pedido->valor_total = $data['valor_total'];

		$payment->transaction_amount = $pedido->valor_total;
		$payment->description = $data['description'];
		$payment->payment_method_id = "pix";

		$cep = str_replace("-", "", $config->cep);
		$payment->payer = array(
			"email" => $data['payerEmail'],
			"first_name" => $data['payerFirstName'],
			"last_name" => $data['payerLastName'],
			"identification" => array(
				"type" => $data['docType'] ?? 'CPF',
				"number" => $data['docNumber']
			),
			"address"=>  array(
				"zip_code" => $cep,
				"street_name" => $config->rua,
				"street_number" => $config->numero,
				"neighborhood" => $config->bairro,
				"city" => $config->cidade,
				"federal_unit" => $config->uf
			)
		);

		$payment->save();

		if($payment->error){

			$error = $this->trataErros($payment->error);
			return response()->json($error, 401);

		}else{
			$pedido->transacao_id = $payment->id;
			$pedido->status_pagamento = $payment->status;
			$pedido->forma_pagamento = 'PIX';
			$pedido->status_detalhe = $payment->status_detail;
			$pedido->link_boleto = '';

			$pedido->qr_code_base64 = $payment->point_of_interaction->transaction_data->qr_code_base64;
			$pedido->qr_code = $payment->point_of_interaction->transaction_data->qr_code;


			$pedido->status = 1; //criado;
			$pedido->save();
			$dataSuccess = [
				'id' => $payment->id,
				'status' => $payment->status,
				'qr_code_base64' => $pedido->qr_code_base64,
				'qr_code' => $pedido->qr_code,
			];

			try{
				$this->sendMail($pedido);
			}catch(\Exception $e){

			}

			return response()->json($dataSuccess, 200);
		}
	}

	public function processarPagamentoBoleto(Request $request){
		$data = $request->data;

		$config = ConfigEcommerce::
		where('empresa_id', $request->empresa_id)
		->first();

		$pedido = PedidoEcommerce::find($data['pedido_id']);

		\MercadoPago\SDK::setAccessToken($config->mercadopago_access_token);
		$payment = new \MercadoPago\Payment();

		$payment->transaction_amount = $pedido->valor_total;
		$payment->description = $data['description'];
		$payment->payment_method_id = "bolbradesco";

		$cep = str_replace("-", "", $config->cep);
		$payment->payer = array(
			"email" => $data['payerEmail'],
			"first_name" => $data['payerFirstName'],
			"last_name" => $data['payerLastName'],
			"identification" => array(
				"type" => $data['docType'] ?? 'CPF',
				"number" => $data['docNumber']
			),
			"address"=>  array(
				"zip_code" => $cep,
				"street_name" => $config->rua,
				"street_number" => $config->numero,
				"neighborhood" => $config->bairro,
				"city" => $config->cidade,
				"federal_unit" => $config->uf
			)
		);

		$payment->save();

		if($payment->error){

			$error = $this->trataErros($payment->error);
			return response()->json($error, 401);

		}else{
			$pedido->transacao_id = $payment->id;
			$pedido->status_pagamento = $payment->status;
			$pedido->forma_pagamento = 'Boleto';
			$pedido->status_detalhe = $payment->status_detail;
			$pedido->link_boleto = $payment->transaction_details->external_resource_url;

			$pedido->status = 1; //criado;
			$pedido->save();
			$dataSuccess = [
				'id' => $payment->id,
				'status' => $payment->status
			];

			try{
				$this->sendMail($pedido);
			}catch(\Exception $e){

			}
			return response()->json($dataSuccess, 200);
		}
	}

	private function sendMail($pedido){
		$config = ConfigEcommerce::
		where('empresa_id', $pedido->empresa_id)
		->first();
		Mail::send('mail.pedido_finalizado', ['pedido' => $pedido, 
			'config' => $config], function($m) use ($pedido, $config){

				$nomeEmail = $config->nome;
				$m->from(env('MAIL_USERNAME'), $nomeEmail);
				$m->subject('Pedido realizado');
				$m->to($pedido->cliente->email);
			});
	}

	private function trataErros($error){

		foreach($error->causes as $e){
			if($e->code == 4033){
				return "Parcelas inválidas";
			}
		}
		// return $error;
		return "Erro desconhecido!";
	}

	public function getStatusPix(Request $request){
		$pedido = PedidoEcommerce::
		where('token', $request->token)
		->first();

		$config = ConfigEcommerce::
		where('empresa_id', $request->empresa_id)
		->first();

		\MercadoPago\SDK::setAccessToken($config->mercadopago_access_token);

		if($pedido){
			$payStatus = \MercadoPago\Payment::find_by_id($pedido->transacao_id);

			if($payStatus->status == "approved"){
				$pedido->status_pagamento = "approved";
				$pedido->status = 2; // confirmado o pagamento;
				$pedido->save();
			}
			return response()->json($payStatus->status, 200);

		}else{
			return response()->json("erro", 404);
		}
	}

	public function getCupom(Request $request){
		$codigo = $request->codigo;
		$empresa_id = $request->empresa_id;
		$cliente = $request->cliente;

		$cupom = CupomDescontoEcommerce::
		where('empresa_id', $request->empresa_id)
		->where('codigo', $request->codigo)
		->first();

		if($cupom == null){
			return response()->json("Cupom inválido!", 404);
		}

		$usado = CupomEcommerceUtilizado::where('cliente_id', $cliente)
		->where('cupom_id', $cupom->id)
		->first();

		if($usado == null){
			return response()->json($cupom, 200);
		}else{
			return response()->json("Cupom já utilizado!", 401);
		}
	}

	public function calcularFrete(Request $request){
		$cep = $request->cep;
		$carrinho = json_decode($request->cart);

		$config = ConfigEcommerce::
		where('empresa_id', $request->empresa_id)
		->first();

		$pedido = $this->montaPedido($carrinho);
		$total = 0;

		foreach($pedido as $i){
			$total += $i->quantidade * $i->valor;
		}

		$calc = $this->calculaFretePorCep($cep, $pedido, $config);

		// $e->preco_sedex = $calc['preco_sedex'];
		// $e->prazo_sedex = $calc['prazo_sedex'];
		// $e->preco = $calc['preco'];
		// $e->prazo = $calc['prazo'];

		if($total > $config->frete_gratis_valor){
			$calc['frete_gratis'] = 1;
		}

		if($config->habilitar_retirada){
			$calc['retirada'] = 1;
		}

		return response()->json($calc, 200);
	}

	private function montaPedido($carrinho){
		$temp = [];
		foreach($carrinho as $c){
			$produto = ProdutoEcommerce::find($c->id);
			$produto->quantidade = $c->quantidade;
			array_push($temp, $produto);
		}
		return $temp;
	}

	private function calculaFretePorCep($cep, $carrinho, $config){

		$cepDestino = $cep;

		$cepOrigem = str_replace("-", "", $config->cep);
		$somaPeso = $this->somaPeso($carrinho);
		$dimensoes = $this->somaDimensoes($carrinho);

		$stringUrl = "&sCepOrigem=$cepOrigem&sCepDestino=$cepDestino&nVlPeso=$somaPeso";

		$stringUrl .= "&nVlComprimento=".$dimensoes['comprimento']."&nVlAltura=".$dimensoes['altura']."&nVlLargura=".$dimensoes['largura']."&nCdServico=04014";


		$url = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?nCdEmpresa=&sDsSenha=&sCdAvisoRecebimento=n&sCdMaoPropria=n&nVlValorDeclarado=0&nVlDiametro=0&StrRetorno=xml&nIndicaCalculo=3&nCdFormato=1" . $stringUrl;

		$unparsedResult = file_get_contents($url);
		$parsedResult = simplexml_load_string($unparsedResult);

		$stringUrl = "&sCepOrigem=$cepOrigem&sCepDestino=$cepDestino&nVlPeso=$somaPeso";

		$stringUrl .= "&nVlComprimento=".$dimensoes['comprimento']."&nVlAltura=".$dimensoes['altura']."&nVlLargura=".$dimensoes['largura']."&nCdServico=04510";

		$url = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?nCdEmpresa=&sDsSenha=&sCdAvisoRecebimento=n&sCdMaoPropria=n&nVlValorDeclarado=0&nVlDiametro=0&StrRetorno=xml&nIndicaCalculo=3&nCdFormato=1" . $stringUrl;

		$unparsedResultSedex = file_get_contents($url);
		$parsedResultSedex = simplexml_load_string($unparsedResultSedex);

		$retorno = array(
			'preco_sedex' => strval($parsedResult->cServico->Valor),
			'prazo_sedex' => strval($parsedResult->cServico->PrazoEntrega),

			'preco' => strval($parsedResultSedex->cServico->Valor),
			'prazo' => strval($parsedResultSedex->cServico->PrazoEntrega)
		);

		return $retorno;
	}

	public function somaPeso($carrinho){
		$soma = 0;
		foreach($carrinho as $i){
			$soma += $i->quantidade * $i->produto->peso_bruto;
		}
		return $soma;
	}

	public function somaDimensoes($carrinho){
		$data = [
			'comprimento' => 0,
			'altura' => 0,
			'largura' => 0
		];
		foreach($carrinho as $key => $i){

			if($i->produto->comprimento > $data['comprimento']){
				$data['comprimento'] = $i->produto->comprimento;
			}

			// if($i->produto->produto->altura > $data['altura']){
			$data['altura'] += $i->produto->altura;
			// }

			if($i->produto->largura > $data['largura']){
				$data['largura'] = $i->produto->largura;
			}

			$data['largura'] = $data['largura'];
		}
		return $data;
	}

}
