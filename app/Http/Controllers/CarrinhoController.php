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
use App\Models\BairroDelivery;

class CarrinhoController extends Controller
{	
	protected $config = null;
    protected $empresa_id = null;

	public function __construct(){
		
        // O NOVO PORTEIRO INTELIGENTE E BLINDADO (FRICÇÃO ZERO)
		$this->middleware(function ($request, $next) {
			$clienteLog = session('cliente_log');
            $telefone = session('telefone_cliente');
            $empresa_id = session('empresa_id'); // Pega a empresa atual
            $this->empresa_id = $empresa_id;
            $this->config = $empresa_id
                ? DeliveryConfig::where('empresa_id', $empresa_id)
                    ->whereHas('empresa', function ($query) { $query->where('status', 1); })->first()
                : null;
            if (!$this->config) {
                return $request->expectsJson()
                    ? response()->json(['message' => 'Acesse pelo link da empresa.'], 404)
                    : response('Cardápio indisponível. Acesse pelo link da empresa.', 404);
            }
            if ($clienteLog && !ClienteDelivery::where('id', $clienteLog['id'] ?? null)
                ->where('empresa_id', $empresa_id)->where('ativo', 1)->exists()) {
                session()->forget(['cliente_log', 'telefone_cliente', 'ultimo_pedido_id']);
                $clienteLog = null;
                $telefone = null;
            }

			if(!$clienteLog){
                if($telefone){
                    $telefoneNumeros = preg_replace('/[^0-9]/', '', (string) $telefone);
                    if (!preg_match('/^[0-9]{10,13}$/', $telefoneNumeros)) {
                        return $request->expectsJson()
                            ? response()->json(['message' => 'Informe um telefone válido.'], 422)
                            : redirect('/pedir/' . $empresa_id)->with('message_erro', 'Informe um telefone válido.');
                    }
                    // Procura o cliente vinculado a ESTA empresa especificamente
                    $cliente = \App\Models\ClienteDelivery::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(celular, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?", [$telefoneNumeros])
                                ->where('empresa_id', $empresa_id)
                                ->first();
                    
                    if ($cliente && !$cliente->ativo) {
                        return response('Cadastro indisponível para pedidos.', 403);
                    }
                    if(!$cliente){
                        $cliente = new \App\Models\ClienteDelivery();
                        $cliente->nome = 'Cliente';
                        $cliente->sobre_nome = '.'; 
                        $cliente->celular = $telefone;
                        $cliente->email = uniqid() . '@friccaozero.com';
                        $cliente->senha = md5(uniqid());
                        $cliente->ativo = 1;
                        $cliente->token = rand(100000, 888888); 
                        $cliente->empresa_id = $empresa_id; // SALVA O ID DA EMPRESA!
                        $cliente->cpf = '';
                        $cliente->foto = '';
                        $cliente->uid = uniqid('cli_', false);
                        $cliente->save(); 
                    }

                    // Força o login na sessão!
                    session()->regenerate();
                    session(['cliente_log' => [
                        'id' => $cliente->id,
                        'nome' => $cliente->nome,
                    ]]);

                } else {
                    // Se a pessoa tentou hackear a URL sem celular
                    if($request->ajax()){
                        return response()->json(['message' => 'Informe seu celular para pedir.'], 401);
                    }else{
                        session()->flash("message_erro", "Informe seu celular para pedir.");
                        return redirect('/pedir/' . $empresa_id);
                    }
                }
			}
			return $next($request);
		});
	}

	public function carrinho()
      {
          $clienteLog = session('cliente_log');

          // O comando 'withoutGlobalScopes' é o segredo. Ele manda o Laravel ignorar
          // qualquer regra que esconda pedidos de clientes.
          // O 'with' garante que carregamos os itens mesmo com o filtro desligado.
          $pedido = PedidoDelivery::withoutGlobalScopes()->where('empresa_id', $this->empresa_id)
              ->with(['itens' => function($query) {
                  $query->withoutGlobalScopes();
              }])
              ->where('estado', 'novo')
              ->where('cliente_id', $clienteLog['id'])
              ->orderBy('id', 'desc')
              ->first();

          return view('delivery/carrinho')
              ->with('pedido', $pedido)
              ->with('carrinho', true)
              ->with('config', $this->config)
              ->with('title', 'CARRINHO');
      }

	public function add(Request $request){
        $request->validate([
            'produto_id' => 'required|integer',
            'quantidade' => 'required|numeric|gt:0', 'observacao' => 'nullable|string|max:50',
            'adicionais' => 'nullable|array', 'adicionais.*.id' => 'required|integer',
        ]);
        \App\Models\ProdutoDelivery::where('empresa_id', $this->empresa_id)->where('status', 1)->findOrFail($request->produto_id);
        foreach ($request->input('adicionais', []) ?? [] as $adicional) {
            \App\Models\ComplementoDelivery::where('empresa_id', $this->empresa_id)->findOrFail($adicional['id']);
        }

        $adicionais = $request['adicionais'];
        $produto_id = $request['produto_id'];
        $quantidade = $request['quantidade'];
        $observacao = $request['observacao'];

        $clienteLog = session('cliente_log');
        $empresa_id = session('empresa_id'); 
        
        $usuario_valido = \Illuminate\Support\Facades\DB::table('usuarios')->where('empresa_id', $empresa_id)->first();
        if (!$usuario_valido) {
            return response()->json(['message' => 'A loja não possui usuário configurado para o pedido.'], 422);
        }
        $id_usuario = $usuario_valido->id;

        // Impede a criação de carrinhos duplicados!
        $pedido = PedidoDelivery::withoutGlobalScopes()->where('empresa_id', $this->empresa_id)
        ->where('estado', 'novo')
        ->where('cliente_id', $clienteLog['id'])
        ->orderBy('id', 'desc')
        ->first();
        
        if($pedido == null){ 
            $pedido = new PedidoDelivery();
            $pedido->cliente_id = $clienteLog['id'];
            $pedido->empresa_id = $empresa_id;
            $pedido->usuario_id = $id_usuario; 
            $pedido->valor_total = 0;
            $pedido->telefone = session('telefone_cliente') ?? '';
            $pedido->observacao = '';
            $pedido->forma_pagamento = '';
            $pedido->estado = 'novo';
            $pedido->motivoEstado = '';
            $pedido->endereco_id = null;
            $pedido->troco_para = 0;
            $pedido->desconto = 0;
            $pedido->cupom_id = null;
            $pedido->app = false;
            $pedido->valor_entrega = 0;
            $pedido->qr_code_base64 = '';
            $pedido->qr_code = '';
            $pedido->horario_cricao = date('H:i');
            $pedido->horario_leitura = '';
            $pedido->horario_entrega = '';
            $pedido->save(); 
        } 

        if($pedido->estado == 'novo'){
          
          	// withoutGlobalScopes aqui garante que o produto será achado!
            $produto = \App\Models\ProdutoDelivery::withoutGlobalScopes()->where('empresa_id', $this->empresa_id)->where('status', 1)->find($produto_id);
            
            if(!$produto) {
                echo json_encode(['error' => 'Produto não encontrado']);
                return;
            }

            $item = new ItemPedidoDelivery();
            $item->pedido_id = $pedido->id; // Forçando a gravação no ID do pedido atual
            $item->produto_id = $produto_id;
          	$item->valor = $produto->valor; // Pegando o valor correto
            $item->status = 0;
            $item->observacao = $observacao ?? '';
            $item->quantidade = $quantidade;
            $item->tamanho_id = null;
            $item->save(); 

            // Atualiza o total do pedido para não ficar 0,00
            $pedido->valor_total += ($item->valor * $quantidade);
            $pedido->save();

            if($adicionais){
                foreach($adicionais as $a){
                    $itemAdd = new ItemPedidoComplementoDelivery();
                    $itemAdd->item_pedido_id = $item->id;
                    $itemAdd->complemento_id = $a['id'];
                    $itemAdd->quantidade = 1;
                    $itemAdd->save();
                    
                    // Soma o valor do adicional se houver
                    $complemento = \App\Models\ComplementoDelivery::find($a['id']);
                    if($complemento){
                        $pedido->valor_total += ($complemento->valor * 1);
                        $pedido->save();
                    }
                }
            }
            echo json_encode($pedido);
        }else{
            echo json_encode(false);
        }
	}

	public function addPizza(Request $request){
        $request->validate([
            'sabores' => 'required|array|min:1', 'sabores.*' => 'required|integer', 'tamanho' => 'required|integer',
            'quantidade' => 'required|numeric|gt:0', 'observacao' => 'nullable|string|max:50',
            'adicionais' => 'nullable|array', 'adicionais.*.id' => 'required|integer',
        ]);
        foreach ($request->sabores as $saborId) {
            \App\Models\ProdutoDelivery::where('empresa_id', $this->empresa_id)->where('status', 1)->findOrFail($saborId);
        }
        \App\Models\TamanhoPizza::where('empresa_id', $this->empresa_id)->findOrFail($request->tamanho);
        foreach ($request->input('adicionais', []) ?? [] as $adicional) {
            \App\Models\ComplementoDelivery::where('empresa_id', $this->empresa_id)->findOrFail($adicional['id']);
        }

		$adicionais = $request['adicionais'];
		$sabores = $request['sabores'];
		$quantidade = $request['quantidade'];
		$observacao = $request['observacao'];
		$tamanho = $request['tamanho'];

		$clienteLog = session('cliente_log');
        $empresa_id = session('empresa_id'); 
        
        $usuario_valido = \Illuminate\Support\Facades\DB::table('usuarios')->where('empresa_id', $empresa_id)->first();
        if (!$usuario_valido) {
            return response()->json(['message' => 'A loja não possui usuário configurado para o pedido.'], 422);
        }
        $id_usuario = $usuario_valido->id;

		$pedido = PedidoDelivery::withoutGlobalScopes()->where('empresa_id', $this->empresa_id)
		->where('estado', 'novo')
		->where('cliente_id', $clienteLog['id'])
        ->orderBy('id', 'desc')
		->first();
		
        if($pedido == null){ 
            $pedido = new PedidoDelivery();
            $pedido->cliente_id = $clienteLog['id'];
            $pedido->empresa_id = $empresa_id;
            $pedido->usuario_id = $id_usuario;
            $pedido->valor_total = 0;
            $pedido->telefone = session('telefone_cliente') ?? '';
            $pedido->observacao = '';
            $pedido->forma_pagamento = '';
            $pedido->estado = 'novo';
            $pedido->motivoEstado = '';
            $pedido->endereco_id = null;
            $pedido->troco_para = 0;
            $pedido->desconto = 0;
            $pedido->cupom_id = null;
            $pedido->app = false;
            $pedido->valor_entrega = 0;
            $pedido->qr_code_base64 = '';
            $pedido->qr_code = '';
            $pedido->horario_cricao = date('H:i');
            $pedido->horario_leitura = '';
            $pedido->horario_entrega = '';
            $pedido->save(); 
		} 

		if($pedido->estado == 'novo'){
			
            $item = new ItemPedidoDelivery();
            $item->pedido_id = $pedido->id;
            $item->produto_id = $sabores[0];
            $item->status = false;
            $item->observacao = $observacao ?? '';
            $item->quantidade = $quantidade;
            $item->tamanho_id = $tamanho;
            // Sem snapshot: o preço continua calculado pelos sabores no fluxo existente.
            $item->valor = 0;
            $item->save();

			if($sabores){
				foreach($sabores as $s){
                    $itemPizza = new ItemPizzaPedido();
                    $itemPizza->item_pedido = $item->id;
                    $itemPizza->sabor_id = $s;
                    $itemPizza->save();
				}
			}

			if($adicionais){
				foreach($adicionais as $a){
                    $itemAdd = new ItemPedidoComplementoDelivery();
                    $itemAdd->item_pedido_id = $item->id;
                    $itemAdd->complemento_id = $a['id'];
                    $itemAdd->quantidade = 1;
                    $itemAdd->save();
				}
			}

			echo json_encode($pedido);
		}else{
			echo json_encode(false);
		}
	}
  
	public function refreshItem($id, $quantidade){
		if($quantidade > 0){
			$item = ItemPedidoDelivery::whereHas('pedido', function ($query) {
                $query->where('empresa_id', $this->empresa_id)->where('cliente_id', session('cliente_log.id'))->where('estado', 'novo');
            })->where('id', $id)->firstOrFail();
			$item->quantidade = $quantidade;

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
			where('empresa_id', $this->empresa_id)
			->where('estado', 'novo')
			//->where('valor_total', '==', 0)
			->where('cliente_id', $clienteLog['id'])
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

					$cliente = ClienteDelivery::where('id', $clienteLog['id'])->first();
					$enderecos = $cliente->enderecos;

					$ultimoPedido = PedidoDelivery::
					where('empresa_id', $this->empresa_id)
					->where('cliente_id', $cliente->id)
					->where('valor_total', '>', 0)
					->orderBy('id', 'desc')
					->first();

					$cartoes = $this->getPedidosPagSeguro($cliente->id);
					$d = $this->config;

					$bairros = BairroDelivery::orderBy('nome')->get();

					return view('delivery/forma_pagamento')
					->with('pedido', $pedido)
					->with('ultimoPedido', $ultimoPedido)
					->with('cartoes', $cartoes)
					->with('cliente', $cliente)
					->with('enderecos', $enderecos)
					->with('forma_pagamento', true)
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
					session()->flash("message_erro", "Carrinho vazio!");
					return redirect('/cardapio');
				}
			}else{
				session()->flash("message_erro", "Carrinho vazio!");
				return redirect('/cardapio');
			}
		}else{
			if($funcionamento['funcionamento'] != null){
				session()->flash("message_erro", "Delivery das " .$funcionamento['funcionamento']->inicio_expediente. " às ".$funcionamento['funcionamento']->fim_expediente);
			}else{
				session()->flash("message_erro", "Não haverá delivery no dia de hoje!");
			}
			return redirect('/cardapio');
		}
	}

	private function getPedidosPagSeguro($clienteId){
		$pedidos = PedidoDelivery::where('empresa_id', $this->empresa_id)->where('cliente_id', $clienteId)->get();
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
        $request->validate(['data' => 'required|array', 'data.pedido_id' => 'required|integer', 'data.endereco_id' => 'required']);
        if ($request->input('data.endereco_id') !== 'balcao') {
            EnderecoDelivery::where('cliente_id', session('cliente_log.id'))->findOrFail($request->input('data.endereco_id'));
        }
		$data = $request['data'];
		$pedido = PedidoDelivery::
		where('empresa_id', $this->empresa_id)
		->where('id', $data['pedido_id'])
        ->where('cliente_id', session('cliente_log.id'))
		->where('estado', 'novo')
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

            // CORREÇÃO 1: Salva a taxa de entrega corretamente no banco de dados!
			if($data['endereco_id'] != 'balcao'){
				$total += $data['valor_entrega'];
                $pedido->valor_entrega = $data['valor_entrega'];
			} else {
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
				$cupom = CodigoDesconto::where('empresa_id', $this->empresa_id)->where('codigo', $data['cupom'])->where('ativo', 1)
                    ->where(function ($query) { $query->whereNull('cliente_id')->orWhere('cliente_id', session('cliente_log.id')); })->first();
				if($cupom && $cupom->cliente_id != null){
					$cupom->ativo = false;
					$cupom->save();
				}
				$pedido->cupom_id= $cupom ? $cupom->id : NULL;
			}

            // Arruma o nome e salva NA TABELA CERTA (Cliente)
            $nomeCompleto = $data['nome'] ?? 'Cliente';
            $partes = explode(' ', trim($nomeCompleto));
            $primeiroNome = $partes[0];

            $cliente = \App\Models\ClienteDelivery::find($pedido->cliente_id);
            if($cliente){
                $cliente->nome = $primeiroNome;
                $cliente->sobre_nome = isset($partes[1]) ? implode(' ', array_slice($partes, 1)) : '';
                $cliente->save();
            }
          
            $pedido->save();
            
          	session(['ultimo_pedido_id' => $pedido->id]);
          
            // =========================================================
            // INÍCIO DO ROBÔ DO WHATSAPP (Automático e Profissional)
            // =========================================================
            try {
                $whatsappUtil = app(\App\Utils\WhatsAppUtil::class);
                $empresa_id = session('empresa_id'); 

                // 1. Monta o cabeçalho do recibo
                $textoZap = "*🍔 NOVO PEDIDO RECEBIDO! 🍔*\n\n";
                $textoZap .= "Olá *" . $primeiroNome . "*!\n";
                $textoZap .= "Recebemos o seu pedido *#" . $pedido->id . "* e ele já está no nosso sistema aguardando a confirmação da loja.\n\n";
                
                $textoZap .= "*🧾 RESUMO DO PEDIDO:*\n";
                
                // 2. Faz um loop (laço) por todos os itens do pedido
                foreach($pedido->itens as $item) {
                    $quantidade = $item->quantidade;
                    
                    $nomeProduto = $item->produto->produto->nome ?? 'Item'; 
                    $valorItem = number_format($item->valor, 2, ',', '.');
                    
                    $textoZap .= "• {$quantidade}x {$nomeProduto} (R$ {$valorItem})\n";
                    
                    // Se for Pizza, puxa os sabores e o tamanho
                    if(count($item->sabores) > 0) {
                         $saboresNomes = [];
                         foreach($item->sabores as $sabor) {
                             $saboresNomes[] = $sabor->produto->produto->nome ?? 'Sabor';
                         }
                         $textoZap .= "  └ Sabores: " . implode(', ', $saboresNomes) . "\n";
                         if($item->tamanho) {
                             $textoZap .= "  └ Tamanho: " . $item->tamanho->nome . "\n";
                         }
                    }
                    
                    // Se tiver Complementos/Adicionais (Ex: Bacon, Cheddar)
                    if(count($item->itensAdicionais) > 0) {
                         foreach($item->itensAdicionais as $adicional) {
                             $qtdAdd = $item->quantidade; // 3 hambúrgueres = 3 carnes extras
                             $vlrAdd = number_format($adicional->adicional->valor * $qtdAdd, 2, ',', '.');
                             $textoZap .= "  └ + {$qtdAdd}x " . $adicional->adicional->nome() . " (R$ {$vlrAdd})\n";
                         }
                    }
                }
                
                $textoZap .= "\n";
                
                // 3. Adiciona a Observação (Se o cliente digitou algo)
                if(!empty($pedido->observacao)) {
                     $textoZap .= "*📝 Observação:* " . $pedido->observacao . "\n\n";
                }
                
                // 4. Totais e Pagamento
                $textoZap .= "*🚚 Taxa de Entrega:* R$ " . number_format($pedido->valor_entrega, 2, ',', '.') . "\n";
                $textoZap .= "*💰 Total a Pagar:* *R$ " . number_format($pedido->valor_total, 2, ',', '.') . "*\n";
                
                $formaPagamentoStr = strtoupper($pedido->forma_pagamento);
                $textoZap .= "*💳 Forma de Pagamento:* " . $formaPagamentoStr . "\n";
                
                // Se for dinheiro e precisar de troco
                if($pedido->troco_para > 0) {
                     $textoZap .= "*💵 Troco para:* R$ " . number_format($pedido->troco_para, 2, ',', '.') . "\n";
                }

                $textoZap .= "\nAgradecemos a preferência! O restaurante já está preparando seu pedido.";

                // 5. Dispara para o Cliente
                if (!empty($pedido->telefone)) {
                    $numeroCliente = "55" . preg_replace('/[^0-9]/', '', $pedido->telefone);
                    $whatsappUtil->sendMessage($numeroCliente, $textoZap, $empresa_id);
                }

                // 6. Dispara para a Loja (Dono do Restaurante)
                $configDelivery = \App\Models\DeliveryConfig::where('empresa_id', $empresa_id)->first();
                if ($configDelivery && !empty($configDelivery->celular_notificacao)) {
                    $numeroLoja = "55" . preg_replace('/[^0-9]/', '', $configDelivery->celular_notificacao);
                    
                    $textoLoja = "*🚨 NOVO PEDIDO #{$pedido->id} NO SISTEMA! 🚨*\n\n";
                    $textoLoja .= "*Cliente:* {$primeiroNome}\n";
                    $textoLoja .= "*Telefone:* {$pedido->telefone}\n\n";
                    
                    // CORREÇÃO 2: Removi o cifrão extra ($$) que estava travando o WhatsApp!
                    $textoRecibo = str_replace("Olá *" . $primeiroNome . "*!\nRecebemos o seu pedido *#" . $pedido->id . "* e ele já está no nosso sistema aguardando a confirmação da loja.\n\n", "", $textoZap);
                    $textoRecibo = str_replace("Agradecemos a preferência! O restaurante já está preparando seu pedido.", "", $textoRecibo);
                    
                    $textoLoja .= $textoRecibo;
                    
                    $whatsappUtil->sendMessage($numeroLoja, $textoLoja, $empresa_id);
                }

            } catch (\Exception $e) {
                \Log::error("Erro ao enviar Zap no Delivery: " . $e->getMessage());
            }
            // =========================================================
            // FIM DO ROBÔ DO WHATSAPP
            // =========================================================

            return response()->json($pedido);
            
		} else {
            return response()->json(['erro' => 'Não foi possível finalizar o pedido.'], 400);
        }
	}
  
	public function historico(){
		$clienteLog = session('cliente_log');
		$pedidos = PedidoDelivery::
		where('empresa_id', $this->empresa_id)
		->where('cliente_id', $clienteLog['id'])
		->where('valor_total', '>', 0)
		->where('forma_pagamento', '<>', '')
		->orderBy('id', 'desc')
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
		::where('empresa_id', $this->empresa_id)->where('estado', 'novo')
		->where('cliente_id', $clienteLog['id'])
		->first();

		$pedidoAnterior = PedidoDelivery::where('empresa_id', $this->empresa_id)->where('cliente_id', $clienteLog['id'])->where('id', $id)->firstOrFail();

		if($pedidoTemp != null && $pedidoAnterior->estado != 'novo'){
			$pedidoTemp->delete();
		}

		if($pedidoAnterior->estado != 'novo'){
			$clienteLog = session('cliente_log');

			$pedido = PedidoDelivery::create([
				'cliente_id' => $pedidoAnterior->cliente_id,
                'empresa_id' => $this->empresa_id,
                'valor_entrega' => 0, 'qr_code_base64' => '', 'qr_code' => '',
                'horario_cricao' => date('H:i'), 'horario_leitura' => '', 'horario_entrega' => '',
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
				'app' => false
			]);

			foreach($pedidoAnterior->itens as $i){
				$item = ItemPedidoDelivery::create([
					'pedido_id' => $pedido->id,
					'produto_id' => $i->produto_id,
					'status' => false,
					'observacao' => $i->observacao,
					'quantidade' => $i->quantidade,
					'tamanho_id' => $i->tamanho_id,
                    'valor' => $i->valor ?? 0
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
        $pedido = \App\Models\PedidoDelivery::where('empresa_id', $this->empresa_id)
            ->where('cliente_id', session('cliente_log.id'))
            ->where('valor_total', '>', 0)
            ->where('forma_pagamento', '<>', '')
            ->find($id);

        if(!$pedido) {
            session()->flash('message_erro', 'Pedido não encontrado ou ainda não foi finalizado.');
            return redirect('/carrinho/meus-pedidos');
        }

        // Ao abrir um pedido válido, ele passa a ser o pedido acompanhado pelo atalho do menu.
        session(['ultimo_pedido_id' => $pedido->id]);

        $valorEntrega = $pedido->valor_entrega ?? 0;

        // Usando o nome correto do model que você encontrou:
        $config = \App\Models\DeliveryConfig::where('empresa_id', session('empresa_id'))->first();

        return view('delivery.pedido_finalizado', [
            'pedido' => $pedido,
            'valorEntrega' => $valorEntrega,
            'config' => $config
        ]);
    }
  
	public function configDelivery(){
    $d = $this->config;
    
    // Se por acaso a tabela estiver vazia, evita que o sistema quebre enviando um objeto em branco
    if (!$d) {
        return response()->json(['valor_km' => 0, 'valor_entrega' => 0], 200);
    }

    return response()->json($d->only(['valor_km', 'valor_entrega', 'valor_entrega_gratis', 'entrega_gratis_ate', 'usar_bairros', 'maximo_km_entrega', 'latitude', 'longitude']), 200);
}

	public function cupons(){
		$clienteLog = session('cliente_log');
		$cupons = CodigoDesconto::
		where('empresa_id', $this->empresa_id)
		->where('cliente_id', $clienteLog['id'])
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
		where('empresa_id', $this->empresa_id)
		->where('codigo', $codigo)
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
		where('empresa_id', $this->empresa_id)
		->where('cliente_id', $cliente)
		->where('cupom_id', $cupom->id)
		->first();
		return $pedido == null ? false : true;
	}

	private function funcionamento(){
		$atual = strtotime(date('H:i'));
		$dias = FuncionamentoDelivery::dias();
		$hoje = $dias[date('w')];
		$func = FuncionamentoDelivery::where('empresa_id', $this->empresa_id)->where('dia', $hoje)->first();

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
			$config = $this->config;
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
  
  	public function meusPedidos() {
    $clienteLog = session('cliente_log');
    if(!$clienteLog) return redirect('/autenticar');

    // Não mistura carrinhos em montagem com pedidos efetivamente enviados.
    $pedidos = \App\Models\PedidoDelivery::where('empresa_id', $this->empresa_id)
                ->where('cliente_id', $clienteLog['id'])
                ->where('valor_total', '>', 0)
                ->where('forma_pagamento', '<>', '')
                ->orderBy('id', 'desc')
                ->get();

    return view('delivery.meus_pedidos', compact('pedidos'))
        ->with('config', $this->config)
        ->with('title', 'Meus Pedidos');
}
  
    public function removeItem($id) {
        try {
            // 1. Busca o item no banco de dados usando o ID que veio da tela
            $item = ItemPedidoDelivery::whereHas('pedido', function ($query) {
                $query->where('empresa_id', $this->empresa_id)->where('cliente_id', session('cliente_log.id'))->where('estado', 'novo');
            })->findOrFail($id);

            if ($item) {
                $pedido = $item->pedido; // Guarda a referência do pedido para atualizar o total depois

                // 2. Apaga os adicionais/complementos (Bacon, borda, etc.) vinculados a este item
                if(count($item->itensAdicionais) > 0) {
                    foreach($item->itensAdicionais as $adicional) {
                        $adicional->delete();
                    }
                }

                // 3. Se for pizza, apaga os sabores vinculados a este item
                if(count($item->sabores) > 0) {
                    foreach($item->sabores as $sabor) {
                        $sabor->delete();
                    }
                }

                // 4. Agora sim, apaga o item principal do carrinho
                $item->delete();

                // 5. Atualiza o valor total do carrinho para abater o item que saiu
                if ($pedido) {
                    $novoTotal = 0;
                    
                    // Recalcula somando os itens que sobraram
                    foreach ($pedido->itens as $itemRestante) {
                        $novoTotal += ($itemRestante->valor * $itemRestante->quantidade);
                        
                        // Soma os adicionais que sobraram
                        foreach ($itemRestante->itensAdicionais as $add) {
                            $novoTotal += ($add->adicional->valor * $itemRestante->quantidade);
                        }
                    }

                    $pedido->valor_total = $novoTotal;
                    $pedido->save();
                }

                session()->flash('message_sucesso', 'Item removido do carrinho!');
            } else {
                session()->flash('message_erro', 'Item não encontrado no carrinho.');
            }

        } catch (\Exception $e) {
            session()->flash('message_erro', 'Erro ao remover o item: ' . $e->getMessage());
        }

        return redirect()->back();
    }
}