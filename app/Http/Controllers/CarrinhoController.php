<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PedidoDelivery;
use App\Models\ClienteDelivery;
use App\Models\ItemPedidoDelivery;
use App\Models\ItemPedidoComplementoDelivery;
use App\Models\EnderecoDelivery;
use App\Models\DeliveryConfig;
use App\Models\ProdutoPizza;
use App\Models\ItemPizzaPedido;
use App\Models\CodigoDesconto;
use App\Models\FuncionamentoDelivery;
use App\Models\BairroDeliveryLoja;
use App\Models\Usuario;

class CarrinhoController extends Controller
{	
	protected $config = null;
    public function __construct()
    {
        $empresaId = session('empresa_id') ?? (session('user_logged')['empresa'] ?? null);
        $this->config = $empresaId
            ? DeliveryConfig::where('empresa_id', $empresaId)->first()
            : DeliveryConfig::first();

        // Fluxo histórico "fricção zero" preservado, mas sempre limitado à empresa da sessão.
        $this->middleware(function ($request, $next) {
            $clienteLog = session('cliente_log');
            $telefone = session('telefone_cliente');
            $empresaId = session('empresa_id');

            if (!$clienteLog) {
                if ($telefone && $empresaId) {
                    $cliente = ClienteDelivery::where('empresa_id', $empresaId)
                        ->where('celular', $telefone)
                        ->first();

                    if (!$cliente) {
                        $cliente = ClienteDelivery::create([
                            'nome' => 'Cliente',
                            'sobre_nome' => '.',
                            'celular' => $telefone,
                            'email' => uniqid('delivery_', true) . '@friccaozero.local',
                            'senha' => md5(uniqid((string) $empresaId, true)),
                            'ativo' => 1,
                            'token' => random_int(100000, 999999),
                            'empresa_id' => $empresaId,
                            'cpf' => '',
                            'foto' => '',
                            'uid' => uniqid('cli_', false),
                        ]);
                    }

                    session(['cliente_log' => [
                        'id' => $cliente->id,
                        'nome' => $cliente->nome,
                    ]]);
                } else {
                    if ($request->ajax()) {
                        return response()->json(false, 401);
                    }

                    session()->flash('message_erro', 'Informe seu celular para pedir.');

                    if ($empresaId) {
                        return redirect('/pedir/' . $empresaId);
                    }

                    return redirect('/autenticar');
                }
            }

            return $next($request);
        });
    }

	public function carrinho(){

		$clienteLog = session('cliente_log');
		$pedido = PedidoDelivery::
		where('estado', 'novo')
		->where('cliente_id', $clienteLog['id'])
		->where('empresa_id', session('empresa_id'))
		->first();

		return view('delivery/carrinho')
		->with('pedido', $pedido)
		->with('carrinho', true)
		->with('config', $this->config)
		->with('title', 'CARRINHO');
		
	}

	public function add(Request $request){
		$value = session('cliente_log');

		if($value){
			$adicionais = $request['adicionais'];
			$produto_id = $request['produto_id'];
			$quantidade = $request['quantidade'];
			$observacao = $request['observacao'];

			$clienteLog = session('cliente_log');
		//verifica se cliente nao possui pedido estado novo 'nv'

			$pedido = PedidoDelivery::where('estado', 'novo')
			->where('cliente_id', $clienteLog['id'])
			->where('empresa_id', session('empresa_id'))
			->first();
			if($pedido == null){ // cria um novo
				$usuarioId = Usuario::where('empresa_id', session('empresa_id'))->where('ativo', 1)->orderBy('id')->value('id');
				if (!$usuarioId) {
					return response()->json(['erro' => 'Nenhum usuário ativo vinculado à empresa para registrar o pedido.'], 422);
				}

				$pedido = PedidoDelivery::create([
					'empresa_id' => session('empresa_id'),
					'usuario_id' => $usuarioId,
					'cliente_id' => $clienteLog['id'],
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
					'valor_entrega' => 0,
					'qr_code_base64' => '',
					'qr_code' => '',
					'horario_cricao' => date('H:i'),
					'horario_leitura' => '',
					'horario_entrega' => ''
				]);
			} // se nao usa o ja existe

			if($pedido->valor_total == 0){
				$item = ItemPedidoDelivery::create([
					'pedido_id' => $pedido->id,
					'produto_id' => $produto_id,
					'status' => false,
					'observacao' => $observacao ?? '',
					'quantidade' => $quantidade,
					'tamanho_id' => null
				]);

				if($adicionais){
					foreach($adicionais as $a){

						$itemAdd = ItemPedidoComplementoDelivery::create([
							'item_pedido_id' => $item->id,
							'complemento_id' => $a['id'],
							'quantidade' => 1,
						]);
					}
				}


				echo json_encode($pedido);
			}else{
				echo json_encode(false);
			}
		}else{
			session()->flash("message_erro", "Voce precisa estar logado, realize seu cadastro por gentileza");
			echo json_encode('401');
		}
	}

	public function addPizza(Request $request){
		$adicionais = $request['adicionais'];
		$sabores = $request['sabores'];
		$quantidade = $request['quantidade'];
		$observacao = $request['observacao'];
		$tamanho = $request['tamanho'];

		$clienteLog = session('cliente_log');
		//verifica se cliente nao possui pedido estado novo 'nv'

		$pedido = PedidoDelivery
		::where('estado', 'novo')
		->where('cliente_id', $clienteLog['id'])
		->where('empresa_id', session('empresa_id'))
		->first();
		if($pedido == null){ // cria um novo
			
			$usuarioId = Usuario::where('empresa_id', session('empresa_id'))->where('ativo', 1)->orderBy('id')->value('id');
			if (!$usuarioId) {
				return response()->json(['erro' => 'Nenhum usuário ativo vinculado à empresa para registrar o pedido.'], 422);
			}

			$pedido = PedidoDelivery::create([
				'empresa_id' => session('empresa_id'),
				'usuario_id' => $usuarioId,
				'cliente_id' => $clienteLog['id'],
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
					'valor_entrega' => 0,
					'qr_code_base64' => '',
					'qr_code' => '',
					'horario_cricao' => date('H:i'),
					'horario_leitura' => '',
					'horario_entrega' => ''
				
			]);
		} // se nao usa o ja existe

		if($pedido->valor_total == 0){
			$identifica_produto_pizza = rand(1, 1000);
			
			$item = ItemPedidoDelivery::create([
				'pedido_id' => $pedido->id,
				'produto_id' => $sabores[0],
				'status' => false,
				'observacao' => $observacao ?? '',
				'quantidade' => $quantidade,
				'tamanho_id' => $tamanho
			]);

			if($sabores){
				foreach($sabores as $s){
					ItemPizzaPedido::create([
						'item_pedido' => $item->id,
						'sabor_id' => $s
					]);
				}
			}

			if($adicionais){
				foreach($adicionais as $a){
					$itemAdd = ItemPedidoComplementoDelivery::create([
						'item_pedido_id' => $item->id,
						'complemento_id' => $a['id'],
						'quantidade' => 1,
					]);
				}
			}


			echo json_encode($pedido);
		}else{
			echo json_encode(false);
		}
	}

	public function removeItem($id){
		$item = ItemPedidoDelivery::where('id', $id)
		->whereHas('pedido', function($query){
			$query->where('empresa_id', session('empresa_id'))
			->where('cliente_id', session('cliente_log.id'))
			->where('estado', 'novo');
		})
		->firstOrFail();
		$item->delete();
		echo json_encode($item);
	}

	public function refreshItem($id, $quantidade){
		if($quantidade > 0){
			$item = ItemPedidoDelivery::where('id', $id)
			->whereHas('pedido', function($query){
				$query->where('empresa_id', session('empresa_id'))
				->where('cliente_id', session('cliente_log.id'))
				->where('estado', 'novo');
			})
			->firstOrFail();
			$item->quantidade = $quantidade;

		//verifica os adicionais
			foreach($item->itensAdicionais as $a){
				$a->quantidade = $quantidade;
				$a->save();
			}
			$item->save();
			echo json_encode($item);
		}
	}

	public function forma_pagamento($cupom = 0){

		$funcionamento = $this->funcionamento();

		$pagseguroAtivo = env("PAGSEGURO_ATIVO");
		$pagseguroEmail = env("PAGSEGURO_EMAIL");
		$pagseguroToken = env("PAGSEGURO_TOKEN");

		$pagseguroAtivado = false;
		if($pagseguroAtivo == 1 && strlen($pagseguroEmail) > 10 && strlen($pagseguroToken) > 10){
			$pagseguroAtivado = true;
		}

		if($funcionamento['status']){
			$clienteLog = session('cliente_log');
			$pedido = PedidoDelivery::
			where('estado', 'novo')
			->where('cliente_id', $clienteLog['id'])
			->where('empresa_id', session('empresa_id'))
			->first();

			if($pedido){

				if(count($pedido->itens) > 0){

					$total = 0;
					foreach($pedido->itens as $i){
						$total += ($i->produto->valor * $i->quantidade);
						if(count($i->sabores) > 0){
							$maiorValor = 0;
							$somaValores = 0;
							foreach($i->sabores as $it){
								$v = $it->maiorValor($it->sabor_id, $i->tamanho_id);
								$somaValores += $v;
								if($v > $maiorValor) $maiorValor = $v;
							}
							if(env("DIVISAO_VALOR_PIZZA") == 1){
								$maiorValor = number_format(($somaValores/sizeof($i->sabores)),2);
							}
							$total += $i->quantidade * $maiorValor;
						}
						foreach($i->itensAdicionais as $a){
							$total += $i->quantidade * $a->adicional->valor;
						}
					}



					$cliente = ClienteDelivery::
					where('id', $clienteLog['id'])
					->where('empresa_id', session('empresa_id'))
					->firstOrFail();

					$enderecos = $cliente->enderecos;


					if($clienteLog){

						$ultimoPedido = PedidoDelivery::
						where('cliente_id', $cliente->id)
						->where('empresa_id', session('empresa_id'))
						->where('valor_total', '>', 0)
						->orderBy('id', 'desc')
						->first();

						$cartoes = $this->getPedidosPagSeguro($cliente->id);
						$d = DeliveryConfig::where('empresa_id', session('empresa_id'))->first();

						$bairros = BairroDeliveryLoja::where('empresa_id', session('empresa_id'))
                        ->orderBy('nome')->get();

						return view('delivery/forma_pagamento')
						->with('pedido', $pedido)
						->with('ultimoPedido', $ultimoPedido)
						->with('cartoes', $cartoes)
						->with('cliente', $cliente)
						->with('enderecos', $enderecos)
						->with('forma_pagamento', true)
						->with('total', $total)
						->with('total', $total)
						->with('mapaJs', true)
						->with('bairros', $bairros)
						->with('usar_bairros', $d->usar_bairros)
						->with('maximo_km_entrega', $d->maximo_km_entrega)
						->with('cupom', $cupom)
						->with('pagseguroAtivado', $pagseguroAtivado)
						->with('config', $this->config)
						->with('title', 'FINALIZAR PEDIDO');
					}else{
						session()->flash("message_erro", "Voce precisa estar logado, realize seu cadastro por gentileza");
						return redirect('/autenticar/registro'); 
					}
				}else{
					session()->flash("message_erro", "Carrinho vazio!");
					return redirect('/'); 
				}
			}else{
				session()->flash("message_erro", "Carrinho vazio!");
				return redirect('/'); 
			}
		}else{
			if($funcionamento['funcionamento'] != null){
				session()->flash("message_erro", "Delivery das " .$funcionamento['funcionamento']->inicio_expediente. " às ".$funcionamento['funcionamento']->fim_expediente);

			}else{
				session()->flash("message_erro", "Não haverá delivery no dia de hoje!");
			}
			return redirect('/'); 
		}
	}

	private function getPedidosPagSeguro($clienteId){
		$pedidos = PedidoDelivery::where('cliente_id', $clienteId)
		->where('empresa_id', session('empresa_id'))
		->get();
		$arr = [];
		$cartaoInserido = [];
		foreach($pedidos as $p){
			if($p->forma_pagamento == 'pagseguro'){
				if(!in_array($p->pagseguro->numero_cartao, $cartaoInserido)){
					$p->pagseguro->src_bandeira = 'https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/68x30/'.
					$p->pagseguro->bandeira . '.png';
					array_push($arr, $p->pagseguro);
					array_push($cartaoInserido, $p->pagseguro->numero_cartao);
				}
			}
		}

		return $arr;
	}

	public function finalizarPedido(Request $request){
		$data = $request['data'];
		$pedido = PedidoDelivery::
		where('id', $data['pedido_id'])
		->where('estado', 'novo')
		->where('cliente_id', session('cliente_log.id'))
		->where('empresa_id', session('empresa_id'))
		->first();
		if($pedido){
			$total = 0;
			foreach($pedido->itens as $i){

				foreach($i->itensAdicionais as $a){
					$total += $a->adicional->valor * $i->quantidade;
				}
				
				if(count($i->sabores) > 0){
					$maiorValor = 0; 
					$somaValores = 0;
					foreach($i->sabores as $it){
						$v = $it->maiorValor($it->produto->id, $i->tamanho_id);
						$somaValores += $v;
						if($v > $maiorValor) $maiorValor = $v;
					}

					if(env("DIVISAO_VALOR_PIZZA") == 1){
						$maiorValor = number_format(($somaValores/sizeof($i->sabores)),2);
					}
					$total += ($maiorValor * $i->quantidade);
				}else{
					$total += ($i->produto->valor * $i->quantidade);
				}

			}

			if($data['desconto']){
				$total -= str_replace(",", ".", $data['desconto']);
			}

			if($data['endereco_id'] != 'balcao'){
				$total += $data['valor_entrega'];
				$pedido->valor_entrega = $data['valor_entrega'];
			}else{
				$pedido->valor_entrega = 0;
			}

			$pedido->forma_pagamento = $data['forma_pagamento'];
			$pedido->observacao = $data['observacao'] ? substr($data['observacao'], 0, 50) : '';
			$pedido->endereco_id = $data['endereco_id'] == 'balcao' ? null : $data['endereco_id'];
			$pedido->valor_total = $total;
			$pedido->telefone = $data['telefone'];
			$pedido->troco_para = $data['troco'] ? str_replace(",", ".", $data['troco']) : 0;
			$pedido->data_registro = date('Y-m-d H:i:s');
			$pedido->desconto = $data['desconto'] ? str_replace(",", ".", $data['desconto']) : 0;

			if($data['cupom'] != ''){
				$cupom = CodigoDesconto::
				where('codigo', $data['cupom'])
				->first();

				if($cupom && $cupom->cliente_id != null){
					$cupom->ativo = false;
					$cupom->save();
				}

				$pedido->cupom_id = $cupom ? $cupom->id : NULL;
			}

			$pedido->save();

			// Mantém o cadastro fricção-zero útil após o primeiro fechamento.
			if(!empty($data['nome'])){
				$nomeCompleto = trim((string) $data['nome']);
				$partes = preg_split('/\s+/', $nomeCompleto, -1, PREG_SPLIT_NO_EMPTY);
				$cliente = ClienteDelivery::where('empresa_id', session('empresa_id'))
					->find($pedido->cliente_id);
				if($cliente && count($partes) > 0){
					$cliente->nome = substr(array_shift($partes), 0, 30);
					$cliente->sobre_nome = substr(implode(' ', $partes), 0, 30);
					$cliente->save();
				}
			}

			// Recupera as notificações históricas usando exclusivamente a fachada Connect|API atual.
			try{
				$whatsappUtil = app(\App\Utils\WhatsAppUtil::class);
				$empresaId = (int) session('empresa_id');
				$nomeCliente = $pedido->cliente->nome ?? 'Cliente';

				$textoCliente = "*🍔 PEDIDO RECEBIDO!*\n\nOlá *{$nomeCliente}*! Recebemos o pedido *#{$pedido->id}*.\n";
				$textoCliente .= "Total: *R$ " . number_format((float) $pedido->valor_total, 2, ',', '.') . "*\n";
				$textoCliente .= "Forma de pagamento: *" . strtoupper((string) $pedido->forma_pagamento) . "*\n\nAguarde a confirmação da loja.";

				if(!empty($pedido->telefone)){
					$numeroCliente = preg_replace('/[^0-9]/', '', $pedido->telefone);
					if(!str_starts_with($numeroCliente, '55')){
						$numeroCliente = '55' . $numeroCliente;
					}
					$whatsappUtil->sendMessage($numeroCliente, $textoCliente, $empresaId);
				}

				$configDelivery = DeliveryConfig::where('empresa_id', $empresaId)->first();
				if($configDelivery && !empty($configDelivery->celular_notificacao)){
					$numeroLoja = preg_replace('/[^0-9]/', '', $configDelivery->celular_notificacao);
					if(!str_starts_with($numeroLoja, '55')){
						$numeroLoja = '55' . $numeroLoja;
					}
					$textoLoja = "*🚨 NOVO PEDIDO #{$pedido->id}*\nCliente: {$nomeCliente}\nTotal: R$ " . number_format((float) $pedido->valor_total, 2, ',', '.');
					$whatsappUtil->sendMessage($numeroLoja, $textoLoja, $empresaId);
				}
			}catch(\Throwable $e){
				\Log::warning('Falha ao notificar novo pedido Delivery via Connect|API: ' . $e->getMessage());
			}

			echo json_encode($pedido);
		}else{
			echo json_encode(false);
		}
	}

	public function historico(){
		$clienteLog = session('cliente_log');
		$pedidos = PedidoDelivery::
		where('cliente_id', $clienteLog['id'])
		->where('empresa_id', session('empresa_id'))
		->orderBy('id', 'desc')
		->where('valor_total', '>', 0)
		->get();

		return view('delivery/historico')
		->with('pedidos', $pedidos)
		->with('historico', true)
		->with('config', $this->config)
		->with('title', 'Historico');
	}

	public function pedir_novamente($id){
		$clienteLog = session('cliente_log');

		$pedidoTemp = PedidoDelivery
		::where('estado', 'novo')
		->where('cliente_id', $clienteLog['id'])
		->where('empresa_id', session('empresa_id'))
		->first();

		if($pedidoTemp != null){ // delete pedido novo
			$pedidoTemp->delete();
		}

		$pedidoAnterior = PedidoDelivery::
		where('id', $id)
		->where('cliente_id', $clienteLog['id'])
		->where('empresa_id', session('empresa_id'))
		->first();

		if($pedidoAnterior->estado != 'novo'){

			$clienteLog = session('cliente_log');

			$usuarioId = Usuario::where('empresa_id', session('empresa_id'))->where('ativo', 1)->orderBy('id')->value('id');
			if (!$usuarioId) {
				session()->flash('message_erro', 'Nenhum usuário ativo vinculado à empresa para registrar o pedido.');
				return redirect('/carrinho/historico');
			}

			$pedido = PedidoDelivery::create([
				'empresa_id' => session('empresa_id'),
				'usuario_id' => $usuarioId,
				'cliente_id' => $pedidoAnterior->cliente_id,
				'valor_total' => 0,
				'telefone' => '',
				'observacao' => '',
				'forma_pagamento' => '',
				'estado'=> 'novo',
				'motivoEstado'=> '',
				'endereco_id' => NULL,
				'troco_para' => 0,
				'cupom_id' => NULL,
				'desconto' => 0,
				'app' => false,
					'valor_entrega' => 0,
					'qr_code_base64' => '',
					'qr_code' => '',
					'horario_cricao' => date('H:i'),
					'horario_leitura' => '',
					'horario_entrega' => ''
			]);


			foreach($pedidoAnterior->itens as $i){

				$item = ItemPedidoDelivery::create([
					'pedido_id' => $pedido->id,
					'produto_id' => $i->produto_id,
					'status' => false,
					'observacao' => $i->observacao,
					'quantidade' => $i->quantidade,
					'tamanho_id' => $i->tamanho_id
				]);

				if($i->tamanho != null){
					
					foreach($i->sabores as $s){
						ItemPizzaPedido::create([
							'item_pedido' => $item->id,
							'sabor_id' => $s->sabor_id
						]);
					}
				}

				foreach($i->itensAdicionais as $a){

					$itemAdd = ItemPedidoComplementoDelivery::create([
						'item_pedido_id' => $item->id,
						'complemento_id' => $a->complemento_id,
						'quantidade' => 1,
					]);
				}
			}
			session()->flash("message_sucesso", "Itens do pedido adicionados ao seu carrinho!");
			return redirect('/carrinho');
		}else{
			session()->flash("message_erro", "Não foi possivel pedir novamente!");
			return redirect('/carrinho/historico');
		}
	}


	public function finalizado($id){
		$clienteLog = session('cliente_log');
		$pedido = PedidoDelivery::
		where('estado', 'novo')
		->where('valor_total', '!=', 0)
		->where('id', $id)
		->where('cliente_id', $clienteLog['id'])
		->where('empresa_id', session('empresa_id'))
		->first();

		if($pedido){
			return view('delivery/pedido_finalizado')
			->with('pedido', $pedido)
			->with('config', $this->config)
			->with('carrinho', true)
			->with('title', 'Pedido Finalizado');
		}else{
			session()->flash("message_erro", "Pedido inexistente");
			return redirect('/');
		}
	}

	public function configDelivery(){
		$d = DeliveryConfig::where('empresa_id', session('empresa_id'))->first();
		echo json_encode($d);
	}

	public function cupons(){
		$clienteLog = session('cliente_log');
		$cupons = CodigoDesconto::
		where('cliente_id', $clienteLog['id'])
		->orderBy('id', 'desc')
		->get();

		return view('delivery/cupons')
		->with('config', $this->config)
		->with('cupons', $cupons)
		->with('title', 'Cupons de Desconto');
	}

	public function cupom($codigo){
		$clienteLog = session('cliente_log');
		$cupom = CodigoDesconto::
		// where('cliente_id', $clienteLog['id'])
		where('codigo', $codigo)
		->where('ativo', true)
		->first();

		if($cupom != null){
			if($cupom->cliente_id != null){
				if($cupom->cliente_id != $clienteLog['id']){
					$cupom = null;
				}
			}else{
				if($this->validaClienteNaoUsouCupom($clienteLog['id'], $cupom))
					$cupom = null;
			}
		}

		echo json_encode($cupom);
	}

	private function validaClienteNaoUsouCupom($cliente, $cupom){

		$pedido = PedidoDelivery::
		where('cliente_id', $cliente)
		->where('empresa_id', session('empresa_id'))
		->where('cupom_id', $cupom->id)
		->first();

		return $pedido == null ? false : true;
	}

	private function funcionamento(){
		$atual = strtotime(date('H:i'));
		$dias = FuncionamentoDelivery::dias();
		$hoje = $dias[date('w')];
		$func = FuncionamentoDelivery::where('dia', $hoje)
		->where('empresa_id', session('empresa_id'))
		->first();

		if($func){
			if($atual >= strtotime($func->inicio_expediente) && $atual < strtotime($func->fim_expediente) && $func->ativo){
				return ['status' => true, 'funcionamento' => $func];
			}else{
				return ['status' => false, 'funcionamento' => $func];
			}
		}else{
			return ['status' => false, 'funcionamento' => null];
		}
	}

	public function getDadosCalculoEntrega(Request $request){
		try{
			$config = DeliveryConfig::where('empresa_id', session('empresa_id'))->first();
			if($config->usar_bairros == 0){
				$latitude_local = $config->latitude;
				$longitude_local = $config->longitude;

				$data = [ 
					'valor_km' => $config->valor_km,
					'entrega_gratis_ate' => $config->entrega_gratis_ate,
					'latitude_local' => $latitude_local,
					'longitude_local' => $longitude_local,
					'maximo_km_entrega' => $config->maximo_km_entrega
				];

				return response()->json($data, 200);
			}
		}catch(\Exception $e){
			return response()->json('', 401);

		}
	}




    public function meusPedidos()
    {
        $clienteLog = session('cliente_log');
        $empresaId = session('empresa_id');

        if (!$clienteLog) {
            return $empresaId ? redirect('/pedir/' . $empresaId) : redirect('/autenticar');
        }

        $pedidos = PedidoDelivery::where('cliente_id', $clienteLog['id'])
            ->when($empresaId, function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('delivery.meus_pedidos', compact('pedidos'))
            ->with('config', $this->config)
            ->with('title', 'Meus Pedidos');
    }
}
