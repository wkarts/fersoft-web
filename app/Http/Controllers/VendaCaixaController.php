<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendaCaixa;
use App\Models\Venda;
use App\Helpers\StockMove;
use App\Models\ItemVendaCaixa;
use App\Models\CreditoVenda;
use App\Models\OrdemServico;
use App\Models\ManifestaDfe;
use App\Models\ConfigNota;
use App\Models\PedidoDelivery;
use App\Models\Produto;
use App\Models\ContaBancaria;
use App\Models\Tributacao;
use App\Models\FormaPagamento;
use App\Models\Cliente;
use App\Models\ProdutoPizza;
use App\Models\Pedido;
use App\Models\RemessaNfe;
use App\Models\ListaPreco;
use App\Models\AberturaCaixa;
use App\Models\Transportadora;
use App\Models\ComissaoVenda;
use App\Models\ContaReceber;
use App\Models\Acessor;
use App\Models\FaturaFrenteCaixa;
use App\Models\Categoria;
use App\Models\ComissaoAssessor;
use App\Models\Usuario;
use App\Models\Funcionario;
use App\Models\Agendamento;
use App\Models\CategoriaConta;
use App\Models\NaturezaOperacao;
use App\Models\ConfigCaixa;
use App\Models\TrocaVenda;
use App\Models\TrocaVendaCaixa;
use App\Models\VendaCaixaPreVenda;
use App\Models\ItemVendaCaixaPreVenda;
use App\Models\CashBackConfig;
use App\Models\CashBackCliente;
use Illuminate\Support\Facades\DB;
use Mail;
use NFePHP\DA\NFe\Cupom;
use NFePHP\DA\NFe\CupomNaoFiscal;
use App\Utils\WhatsAppUtil;
use NFePHP\DA\NFe\Danfce;

class VendaCaixaController extends Controller
{
  protected $empresa_id = null;
  protected $util;

  public function __construct(WhatsAppUtil $util){
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

  private function rateioCashBack($valor_cashback, $cliente_id){
    $data = CashBackCliente::where('empresa_id', $this->empresa_id)
    ->where('status', 1)
    ->where('cliente_id', $cliente_id)
    ->get();
    $soma = 0;

    $cliente = Cliente::findOrFail($cliente_id);
    $cliente->valor_cashback -= $valor_cashback;
    $cliente->save();
    foreach($data as $i){
      if($soma < $valor_cashback){
        $valorCredito = $i->valor_credito;
        if($valorCredito <= $valor_cashback){
          $i->status = 0;
          $i->valor_credito = 0;
          $i->save();
          $soma += $valorCredito;
        }else{
          $i->valor_credito -= ($valor_cashback - $soma);
          $i->save();
          $soma = $valor_cashback;
        }
      }
    }
  }

  private function saveCashBack($venda){
    $config = CashBackConfig::where('empresa_id', $venda->empresa_id)
    ->first();

    if($venda->cliente && $config != null){
      if($venda->valor_total >= $config->valor_minimo_venda){
        $valor_percentual = $config->valor_percentual;
        $dias_expiracao = $config->dias_expiracao;

        $valor_credito = $venda->valor_total * ($valor_percentual/100);
        $data = [
          'empresa_id' => $venda->empresa_id,
          'cliente_id' => $venda->cliente_id,
          'tipo' => 'pdv',
          'venda_id' => $venda->id,
          'valor_venda' => $venda->valor_total,
          'valor_credito' => $valor_credito,
          'valor_percentual' => $valor_percentual,
          'valor_percentual' => $valor_percentual,
          'status' => 1,
          'data_expiracao' => date('Y-m-d', strtotime("+$dias_expiracao days"))
        ];
        $cashBackCliente = CashBackCliente::create($data);

        $cliente = $venda->cliente;
        $cliente->valor_cashback = $cliente->valor_cashback + $valor_credito;
        $cliente->save();

        if($cliente->email){
          // $this->sendEmailCashBack($cashBackCliente);
        }

        $this->sendWhatsMessage($cashBackCliente);
      }
    }
  }

  private function sendEmailCashBack($cashBackCliente){
    Mail::send('mail.cash_back', ['cashBackCliente' => $cashBackCliente], function($m) use ($cashBackCliente){

      $nomeEmpresa = env('MAIL_NAME');
      $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
      $nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
      $emailEnvio = env('MAIL_USERNAME');

      $m->from($emailEnvio, $nomeEmpresa);
      $subject = "Cash Back #$cashBackCliente->id";

      $m->subject($subject);
      $m->to($cashBackCliente->cliente->email);
    });
  }

  private function sendWhatsMessage($cashBackCliente){
    if($cashBackCliente->cliente->celular != ''){
      // try{
      $config = CashBackConfig::where('empresa_id', $cashBackCliente->cliente->empresa_id)
      ->first();

      $configNota = ConfigNota::where('empresa_id', $cashBackCliente->cliente->empresa_id)
      ->first();
      $nodeurl = 'https://api.criarwhats.com/send';

      $number = $cashBackCliente->cliente->celular;
      $number = preg_replace('/[^0-9]/', '', $cashBackCliente->cliente->celular);
      $message = $config->mensagem_padrao_whatsapp;

      $nomeCliente = $cashBackCliente->cliente->razao_social;
      if($cashBackCliente->cliente->nome_fantasia != ''){
        $nomeCliente = $cashBackCliente->cliente->nome_fantasia;
      }

      $message = str_replace("{credito}", moeda($cashBackCliente->valor_credito), $message);
      $message = str_replace("{expiracao}", __date($cashBackCliente->data_expiracao, 0), $message);
      $message = str_replace("{nome}", $nomeCliente, $message);
      $data = [
        'receiver'  => '55'.$number,
        'msgtext'   => $message,
        'token'     => $configNota->token_whatsapp,
      ];

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
      curl_setopt($ch, CURLOPT_URL, $nodeurl);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      $response = curl_exec($ch);
      curl_close($ch);
      // }catch(\Exception $e){
      //   $this->criarLog($e, 'criar');
      // }
    }


  }

  private function criarLog($objeto, $tipo = 'criar'){
    if(isset(session('user_logged')['log_id'])){
      $record = [
        'tipo' => $tipo,
        'usuario_log_id' => session('user_logged')['log_id'],
        'tabela' => 'venda_caixas',
        'registro_id' => $objeto->id,
        'empresa_id' => $this->empresa_id
      ];
      __saveLog($record);
    }
  }

  public function save(Request $request){
    try{
      $result = DB::transaction(function () use ($request) {
        $venda = $request->venda;
        $agendamento_id = $venda['agendamento_id'];
        $fromPrevenda = isset($venda['isPrevenda']) ?
        ($venda['isPrevenda'] == 'true' ? true : false) : false;

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
        ->first();

        $pag_multi = isset($venda['pag_multi']) ? $venda['pag_multi'] : [];
    // return response()->json($pag_multi, 401);

        $casasDecimais = max((int)($config->casas_decimais ?? 2), 2);
        $valorTotalBase = (float)__replace($venda['valor_total'] ?? 0);
        $valorAcrescimo = (float)__replace($venda['acrescimo'] ?? 0);
        $valorDesconto = (float)__replace($venda['desconto'] ?? 0);
        $totalVenda = __truncateDecimal($valorTotalBase + $valorAcrescimo - $valorDesconto, $casasDecimais);

        $func = null;
        $usr = null;

        $idUsr = get_id_user();
        if($config->caixa_por_usuario == 1){

          if(isset($venda['funcionario_id'])){
            $func = Funcionario::find($venda['funcionario_id']);
            $vendedor_id = $func == null ? get_id_user() : $func->usuario->id;

          }

          if(isset($venda['vendedor_id']) && $venda['vendedor_id'] != ''){
            $usr = Usuario::find($venda['vendedor_id']);
            $vendedor_id = $usr == null ? get_id_user() : $usr->id;
          }
        }

        $vendedor_id = null;
        if(isset($venda['vendedor_id']) && $venda['vendedor_id'] != ''){
          $vendedor_id = $venda['vendedor_id'];
        }

        $assessor = null;
        if(isset($venda['assessor_id']) && $venda['assessor_id']){
          $assessor = Acessor::find($venda['assessor_id']);
        }

        if($venda['id'] == 0){

          $valor_cashback = 0;
          if(isset($venda['valor_cashback'])){
            $valor_cashback = __replace($venda['valor_cashback']);
          }
          $result = VendaCaixa::create([
            'cliente_id' => $venda['cliente'],
            'filial_id' => $venda['filial_id'],
            'usuario_id' => $idUsr,
            'vendedor_id' => $vendedor_id,
            'natureza_id' => $config->nat_op_padrao,
            'valor_total' => $totalVenda,
            'acrescimo' => __truncateDecimal($venda['acrescimo'] ?? 0, $casasDecimais),
            'troco' => __truncateDecimal($venda['troco'] ?? 0, $casasDecimais),
            'dinheiro_recebido' => __truncateDecimal($venda['dinheiro_recebido'] ?? 0, $casasDecimais),
            'forma_pagamento' => $venda['acao'] == 'credito' ? 'credito' : " ",
            'tipo_pagamento' => sizeof($pag_multi) > 0 ? '99' : $venda['tipo_pagamento'],
            'estado' => 'DISPONIVEL',
            'NFcNumero' => 0,
            'valor_cashback' => $valor_cashback,
            'chave' => '',
            'path_xml' => '',
            'nome' => $venda['nome'] ?? '',
            'rascunho' => $venda['rascunho'],
            'credito_troca' => $venda['credito_troca'] ? $venda['desconto'] : 0,
            'consignado' => $venda['consignado'],
            'cpf' => $venda['cpf'] ?? '',
            'observacao' => $venda['observacao'] ?? '',
            'desconto' => __truncateDecimal($venda['desconto'] ?? 0, $casasDecimais),
            'pedido_delivery_id' => isset($venda['delivery_id']) ? $venda['delivery_id'] : 0,
            'pedido_ifood_id' => isset($venda['pedido_ifood']) ? $venda['pedido_ifood'] : null,
            'tipo_pagamento_1' => $venda['tipo_pagamento_1'] ?? '',
            'valor_pagamento_1' => $venda['valor_pagamento_1'] ? __replace($venda['valor_pagamento_1']) :  0,
            'tipo_pagamento_2' => $venda['tipo_pagamento_2'] ?? '',
            'valor_pagamento_2' => $venda['valor_pagamento_2'] ? __replace($venda['valor_pagamento_2']) : 0,
            'tipo_pagamento_3' => $venda['tipo_pagamento_3'] ?? '',
            'valor_pagamento_3' => $venda['valor_pagamento_3'] ? __replace($venda['valor_pagamento_3']) : 0,
            'empresa_id' => $this->empresa_id,
            'bandeira_cartao' => $venda['bandeira_cartao'],
            'cAut_cartao' => $venda['cAut_cartao'] ?? '',
            'cnpj_cartao' => $venda['cnpj_cartao'] ?? '',
            'descricao_pag_outros' => $venda['descricao_pag_outros'] ?? '',
            'numero_sequencial' => VendaCaixa::lastNumero($this->empresa_id)
          ]);

          $this->criarLog($result);
          if(isset($venda['valor_cashback'])){
            if($venda['valor_cashback'] == 0 && $venda['nao_permitir_credito'] == 0){
              $this->saveCashBack($result);
            }else{
              $this->rateioCashBack($venda['valor_cashback'], $venda['cliente']);
            }
          }
        }else{
          if($fromPrevenda){
            $result = VendaCaixaPreVenda::find($venda['id']);
          }else{
            $result = VendaCaixa::find($venda['id']);
          }

          $result->cliente_id = $venda['cliente'];
          $result->usuario_id = get_id_user();
          $result->natureza_id = $config->nat_op_padrao;
          $result->valor_total = $totalVenda;
          $result->acrescimo = __truncateDecimal($venda['acrescimo'] ?? 0, $casasDecimais);
          $result->troco = __truncateDecimal($venda['troco'] ?? 0, $casasDecimais);
          $result->dinheiro_recebido = __truncateDecimal($venda['dinheiro_recebido'] ?? 0, $casasDecimais);
          $result->forma_pagamento = $venda['acao'] == 'credito' ? 'credito' : " ";
          $result->tipo_pagamento = sizeof($pag_multi) > 0 ? '99' : $venda['tipo_pagamento'];
          $result->estado = 'DISPONIVEL';
          $result->NFcNumero = 0;
          $result->chave = '';
          $result->path_xml = '';
          $result->nome = $venda['nome'] ?? '';
          $result->rascunho = $venda['rascunho'];
          if(isset($result->consignado)){
            $result->consignado = $venda['consignado'];
          }
          $result->cpf = $venda['cpf'] ?? '';
          $result->observacao = $venda['observacao'] ?? '';
          $result->desconto = __truncateDecimal($venda['desconto'] ?? 0, $casasDecimais);
          $result->pedido_delivery_id = isset($venda['delivery_id']) ? $venda['delivery_id'] : 0;
          $result->bandeira_cartao = $venda['bandeira_cartao'];
          $result->cAut_cartao = $venda['cAut_cartao'] ?? '';
          $result->cnpj_cartao = $venda['cnpj_cartao'] ?? '';
          $result->descricao_pag_outros = $venda['descricao_pag_outros'] ?? '';
          $result->prevenda_nivel = 0;
          $this->criarLog($result, 'atualizar');

          if($fromPrevenda){
            $prevenda_result = $result;
            unset($result->id);
            $result = VendaCaixa::create(json_decode($result, true));

            $prevenda_result->update();
          }else{
            $result->update();
          }

          ContaReceber::where('venda_caixa_id', $venda['id'])->delete();
          FaturaFrenteCaixa::where('venda_caixa_id', $venda['id'])->delete();
          ItemVendaCaixa::where('venda_caixa_id', $venda['id'])->delete();
          ComissaoVenda::where('venda_id', $venda['id'])->where('tabela', 'venda_caixas')->delete();
        }

        if($venda['credito_troca']){
      //recalcular valor credito
          $this->recalcularCredito($venda['desconto'], $venda['cliente']);
        }

        // if(isset($venda['valor_cashback'])){
        //   if($venda['valor_cashback']){
        //     $cliente = $result->cliente;
        //     if($cliente != null){
        //       $cliente->valor_cashback = $cliente->valor_cashback - $venda['valor_cashback'];
        //       $cliente->save();
        //     }
        //   }
        // }

        $natureza = NaturezaOperacao::find($config->nat_op_padrao);

        $categoria = $this->categoriaCrediario();

        if($natureza->categoria_conta_id != null){
          $categoria = $natureza->categoria_conta_id;
        }

        if($venda['tipo_pagamento'] == '06' && sizeof($pag_multi) == 0){

          $dataVenc = date('Y-m-d', strtotime("+30 days",
            strtotime(date('Y-m-d'))));
          $resultConta = ContaReceber::create([
            'venda_caixa_id' => $result->id,
            'venda_id' => NULL,
            'data_vencimento' => $dataVenc,
            'data_recebimento' => $dataVenc,
            'valor_integral' => $totalVenda,
            'valor_recebido' => 0,
            'status' => false,
            'referencia' => "Venda PDV " . $result->id,
            'categoria_id' => $categoria,
            'empresa_id' => $this->empresa_id,
            'cliente_id' => $venda['cliente']
          ]);
        }
        // $contCredito = 1;

        if(sizeof($pag_multi) > 0){
          foreach($pag_multi as $p){
            FaturaFrenteCaixa::create([
              'valor' => __replace($p['valor']),
              'forma_pagamento' => $p['tipo'],
              'entrada' => isset($p['entrada']) ? $p['entrada'] : 0,
              'data_vencimento' => $p['vencimento'] ? $p['vencimento'] : date('Y-m-d'),
              'venda_caixa_id' => $result->id
            ]);

            if($p['vencimento'] != date('Y-m-d')){
              $resultConta = ContaReceber::create([
                'venda_caixa_id' => $result->id,
                'venda_id' => NULL,
                'data_vencimento' => $p['vencimento'] ? $p['vencimento'] : date('Y-m-d'),
                'data_recebimento' => $p['vencimento'] ? $p['vencimento'] : date('Y-m-d'),
                'valor_integral' => __replace($p['valor']),
                'valor_recebido' => 0,
                'status' => false,
                'referencia' => "Venda PDV " . $result->id,
                'categoria_id' => $categoria,
                'empresa_id' => $this->empresa_id,
                'cliente_id' => $venda['cliente']
              ]);
            }

          //   if($p['tipo'] == '06'){
          // // return response()->json($p['tipo'], 401);
          //     $this->salvaCredito($result->id, __replace($p['valor']),
          //       $venda['cliente'], $p['obs'], $p['vencimento']);
          //   }
          }
        }

        if($venda['codigo_comanda'] > 0){
          $pedido = Pedido::
          where('comanda', $venda['codigo_comanda'])
          ->where('status', 0)
          ->where('desativado', 0)
          ->first();

          $pedido->status = 1;
          $pedido->desativado = 1;
          $pedido->save();
        }

        $itens = $venda['itens'];
        $stockMove = new StockMove();

        if($fromPrevenda){
      // retorna os itens para o estoque e depois deleta os registros
          $this->adicionaAoEstoque(ItemVendaCaixaPreVenda::where('venda_caixa_prevenda_id', $venda['id'])->get(), $config);
          ItemVendaCaixaPreVenda::where('venda_caixa_prevenda_id', $venda['id'])->delete();
        }
        if(!$fromPrevenda && $venda['id'] != 0){
          $this->adicionaAoEstoque($result->itens, $config);
          ItemVendaCaixa::where('venda_caixa_id', $venda['id'])->delete();
        }

        $valorComissaoAssesor = 0;
        foreach ($itens as $i) {
          $produto = Produto::find($i['id']);
          $cfop = 0;

          if($natureza->sobrescreve_cfop){
            $cfop = $natureza->CFOP_saida_estadual;
          }else{
            $cfop = $produto->CFOP_saida_estadual;
          }

      //calculo comissao assessor
          $prod = Produto::findOrFail($i['id']);

          $vlc = 0;
          if($assessor != null){

            $qtd = (float) str_replace(",", ".", $i['quantidade']);
            $valor = (float) str_replace(",", ".", $i['valor']);
            if($assessor->tipo_comissao == 'custo'){
              if($prod->custo_assessor > 0){
                $vlc = ($valor-$prod->custo_assessor) * $qtd;
              }
            }else{
              $vlc = ($valor*$qtd)*($assessor->percentual_comissao/100);
            }
            if($vlc > 0){
              $valorComissaoAssesor += $vlc;
            }
          }

          ItemVendaCaixa::create([
            'venda_caixa_id' => $result->id,
            'produto_id' => (int) $i['id'],
            'quantidade' => (float) str_replace(",", ".", $i['quantidade']),
            'valor' => (float) str_replace(",", ".", $i['valor']),
            'item_pedido_id' => isset($i['itemPedido']) ? $i['itemPedido'] : NULL,
            'observacao' => $i['obs'] ?? '',
            'cfop' => $cfop,
            'valor_custo' => $produto->valor_compra,
            'valor_comissao_assessor' => $vlc
          ]);


          if($venda['consignado'] == 0 && $venda['rascunho'] == 0){
            if($config->natureza->nao_movimenta_estoque == false){
              if(!isset($venda['delivery_id']) || $venda['delivery_id'] == 0){
     // nao delivery

                if($prod->gerenciar_estoque){
                  $stockMove->downStock(
                    (int) $i['id'],
                    (float) str_replace(",", ".", $i['quantidade']),
                    $venda['filial_id']
                  );
                }else{

                  if(!$prod->gerenciar_estoque && $prod->receita){
                    $receita = $prod->receita;
                    foreach($receita->itens as $itemReceita){
                      $itemReceita->rendimento = $itemReceita->rendimento <= 0 ? 1 : $itemReceita->rendimento;
                      $stockMove->downStock($itemReceita->produto_id,
                        (float)str_replace(",", ".", $i['quantidade']) * ($itemReceita->quantidade/$itemReceita->rendimento));
                    }
                  }
                }
              }
            }
          }

        }

        if($valorComissaoAssesor > 0){
          ComissaoAssessor::create([
            'venda_caixa_id' => $result->id,
            'status' => 0,
            'valor' => $valorComissaoAssesor,
            'assessor_id' => $assessor->id
          ]);
        }
        //DELIVERY
        if(isset($venda['delivery_id']) && $venda['delivery_id'] > 0){
          $pedidoDelivery = PedidoDelivery
          ::where('id', $venda['delivery_id'])
          ->first();

          foreach($pedidoDelivery->itens as $i){

            if(count($i->sabores) > 0){

              $totalSabores = count($i->sabores);
              foreach($i->sabores as $sb){
                if(!empty($sb->produto->produto->receita)){
                  $receita = $sb->produto->produto->receita;
                  foreach($receita->itens as $rec){

                    $stockMove->downStock(
                      $rec->produto_id,
                      (float) str_replace(",", ".", $i['quantidade'])
                      *
                      ((($rec->quantidade/$totalSabores)/$receita->pedacos)*$i->tamanho->pedacos)/$receita->rendimento
                    );
                  }
                }
              }
            }else{

              if(!empty($i->produto->produto->receita)){
                $receita = $i->produto->produto->receita;
                foreach($receita->itens as $rec){

                  if(!empty($rec->produto->receita)){

                    $receita2 = $rec->produto->receita;

                    foreach($receita2->itens as $rec2){
                      $stockMove->downStock(
                        $rec2->produto_id,
                        (float) str_replace(",", ".", $i['quantidade']) *
                        ($rec2->quantidade/$receita2->rendimento)
                      );
                    }
                  }else{


                    $stockMove->downStock(
                      $rec->produto_id,
                      (float) str_replace(",", ".", $i['quantidade']) *
                      ($rec->quantidade/$receita->rendimento)
                    );
                  }
                }
              }else{

                $stockMove->downStock(
                  $i->produto->produto->id,
                  (float) str_replace(",", ".", $i['quantidade'])
                );
              }
            }

          }
        }

        $vTemp = VendaCaixa::find($result->id);

        if($vendedor_id){

          $usuario = Usuario::find($vendedor_id);
          if($usuario->caixa_livre == 0 && isset($usuario->funcionario)){
            $percentual_comissao = $usuario->funcionario->percentual_comissao;
            $valorComissao = $this->calcularComissaoVenda($vTemp, $percentual_comissao);
            ComissaoVenda::create(
              [
                'funcionario_id' => $usuario->funcionario->id,
                'venda_id' => $result->id,
                'tabela' => 'venda_caixas',
                'valor' => $valorComissao,
                'status' => 0,
                'empresa_id' => $this->empresa_id
              ]
            );
          }else{
            // $func = Funcionario::find($venda['funcionario_id']);
            // if($func == null && $usr != null){
            //   $func = $usr->funcionario;
            // }
            $func = $usuario->funcionario;

            if($func != null){
              if($func->percentual_comissao > 0){
                $percentual_comissao = $func->percentual_comissao;
                $valorComissao = $this->calcularComissaoVenda($vTemp, $percentual_comissao);
                ComissaoVenda::create(
                  [
                    'funcionario_id' => $func->id,
                    'venda_id' => $result->id,
                    'tabela' => 'venda_caixas',
                    'valor' => $valorComissao,
                    'status' => 0,
                    'empresa_id' => $this->empresa_id
                  ]
                );
              }
            }
          }
        }else{

          // calculaca comissao por usuario logado
          if($config->gerenciar_comissao_usuario_logado){
            $usuario = Usuario::find(get_id_user());
            if($usuario->funcionario){
              $percentual_comissao = $usuario->funcionario->percentual_comissao;
              $valorComissao = $this->calcularComissaoVenda($vTemp, $percentual_comissao);
              ComissaoVenda::create(
                [
                  'funcionario_id' => $usuario->funcionario->id,
                  'venda_id' => $result->id,
                  'tabela' => 'venda_caixas',
                  'valor' => $valorComissao,
                  'status' => 0,
                  'empresa_id' => $this->empresa_id
                ]
              );

              $result->vendedor_id = $usuario->id;
              $result->save();
            }
          }
        }

        if($agendamento_id > 0){

          $agendamento = Agendamento::find($agendamento_id);
          $valorComissao = $this->calculaComissao($agendamento);
          $agendamento->valor_comissao = $valorComissao;
          $agendamento->status = 1;
          $agendamento->save();
        }

        if($fromPrevenda){
          VendaCaixaPreVenda::find($venda['id'])->delete();
        }

        if(isset($venda['os_id']) && $venda['os_id'] > 0){
          $ordem = OrdemServico::where('id', $venda['os_id'])->first();
          $ordem->venda_id = $result->id;
          $ordem->save();
        }

        if(isset($venda['dfe_id']) && $venda['dfe_id'] > 0){
          $item = ManifestaDfe::where('id', $venda['dfe_id'])->first();
          $item->venda_id = $result->id;
          $item->save();
        }

        $result->comissao_acessor = $valorComissaoAssesor > 0 ? true : false;

        return $result;
      });
echo json_encode($result);

}catch(\Exception $e){
  __saveError($e, $this->empresa_id);
  return response()->json($e->getMessage() . " line: " . $e->getLine(), 400);
}
}

public function prevendas(){
    //...
  $pv = VendaCaixaPreVenda::where('empresa_id', $this->empresa_id)
  ->where('prevenda_nivel', 2)
  ->orderBy('updated_at', 'desc')
  ->with('cliente')
  ->get();

  foreach($pv as $p){
    $p->cliente = $p->cliente;
    $p->vendedor = $p->vendedor();
    $p->data = \Carbon\Carbon::parse($p->updated_at)->format('d/m/Y - H:i:s');
  }

  return json_encode($pv);
}

public function prevendaAll(){
    //...
  $pv = VendaCaixaPreVenda::where('empresa_id', $this->empresa_id)
  ->orderBy('updated_at', 'desc')
  // ->where('prevenda_nivel', 1)
  ->with('cliente')
  ->get();

  foreach($pv as $p){
    $p->cliente = $p->cliente;
    $p->vendedor = $p->vendedor();
    $p->data = \Carbon\Carbon::parse($p->updated_at)->format('d/m/Y - H:i:s');
  }

  return json_encode($pv);
}

public function prevendaRetorno(Request $request){
  $result = VendaCaixaPreVenda::where('empresa_id', $this->empresa_id)->find($request->id);
  $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

  if($result->prevenda_nivel == 2){
    $result->update(['prevenda_nivel' => 1]);
    $this->adicionaAoEstoque($result->itens, $config);

    return response()->json(['message' => 'ok'], 200);
  }
  return response()->json(['message' => ''], 500);
}

public function savePreVenda(Request $request){
  $venda = $request->venda;

  $config = ConfigNota::
  where('empresa_id', $this->empresa_id)
  ->first();

    // Verifica se precisa de senha para alterar preço dos itens
  if($config->senha_alterar_preco != ''){
      // verifica se o valor de algum item foi alterado

    $itens = $venda['itens'];
    $ids = [];
    foreach($itens as $i){
      array_push($ids, $i['id']);
    }

    $produtos = Produto::whereIn('id', $ids)->get(['id', 'valor_venda', 'nome']);

    $teve_alteracao = 0;

    foreach($itens as $i){

      foreach ($produtos as $p) {
        if ($i['id'] == $p->id ) {
          if ((float) str_replace(",", ".", $i['valor']) != (float) $p->valor_venda) {
            $teve_alteracao++;
          }
        }
      }
    }

    if ($teve_alteracao && (md5($venda['senha_alterar_preco']) != $config->senha_alterar_preco)) {
      return response()->json(['message' => 'A senha para alterar o valor dos itens está incorreta'], 401);
    }
  }

  $totalVenda = str_replace(",", ".", $venda['valor_total']) + str_replace(",", ".", $venda['acrescimo']) - str_replace(",", ".", $venda['desconto']);

  if($venda['id'] == 0){
    $result = VendaCaixaPreVenda::create([
      'cliente_id' => $venda['cliente'],
      'usuario_id' => $venda['vendedor_id'],
      'natureza_id' => $config->nat_op_padrao,
      'valor_total' => $totalVenda,
      'acrescimo' => str_replace(",", ".", $venda['acrescimo']),
      'troco' => 0,
      'dinheiro_recebido' => 0,
      'forma_pagamento' => " ",
      'tipo_pagamento' => '01',
      'estado' => 'DISPONIVEL',
      'NFcNumero' => 0,
      'chave' => '',
      'path_xml' => '',
      'nome' => '',
      'rascunho' => 0,
      'prevenda_nivel' => $venda['prevenda_nivel'],
      'cpf' => $venda['cpf'] ?? '',
      'observacao' => $venda['observacao'] ?? '',
      'desconto' => $venda['desconto'],
      'pedido_delivery_id' => 0,
      'tipo_pagamento_1' => '',
      'valor_pagamento_1' => 0,
      'tipo_pagamento_2' => '',
      'valor_pagamento_2' => 0,
      'tipo_pagamento_3' => '',
      'valor_pagamento_3' => 0,
      'empresa_id' => $this->empresa_id,
      'bandeira_cartao' => '99',
      'cAut_cartao' => '',
      'cnpj_cartao' => '',
      'descricao_pag_outros' => '',
    ]);
  }else{
    $result = VendaCaixaPreVenda::find($venda['id']);

    $result->cliente_id = $venda['cliente'];
    $result->natureza_id = $config->nat_op_padrao;
    $result->valor_total = $totalVenda;
    $result->acrescimo = str_replace(",", ".", $venda['acrescimo']);
    $result->troco = str_replace(",", ".", $venda['troco']);
    $result->estado = 'DISPONIVEL';
    $result->prevenda_nivel = $venda['prevenda_nivel'];
    $result->observacao = $venda['observacao'] ?? '';
    $result->desconto = $venda['desconto'];
    $result->update();
    $this->adicionaAoEstoque($result->itens, $config);

    ItemVendaCaixaPreVenda::where('venda_caixa_prevenda_id', $venda['id'])->delete();
      // ComissaoVenda::where('venda_id', $venda['id'])->where('tabela', 'venda_caixas')->delete();
  }

  $itens = $venda['itens'];
  $natureza = NaturezaOperacao::find($config->nat_op_padrao);
  $this->removeDoEstoque($itens, $config);

  if($venda['prevenda_nivel'] == 2){
      // desconta do estoque se estiver enviando para o caixa
    $this->adicionaItensPrevenda($itens, $result, $config);
    // $this->removeDoEstoque($itens, $config);
  }else{
    $this->adicionaItensPrevenda($itens, $result, $config);
  }

  echo json_encode($result);
}

private function removeDoEstoque($itens, $config){
  $stockMove = new StockMove();

  foreach ($itens as $i) {
    if($config->natureza->nao_movimenta_estoque == false){
      if(!isset($venda['delivery_id']) || $venda['delivery_id'] == 0){
          // nao delivery
        $prod = Produto
        ::where('id', $i['id'])
        ->first();

        if(!empty($prod->receita)){

          $receita = $prod->receita;

          foreach($receita->itens as $rec){
            if(!empty($rec->produto->receita)){

              $receita2 = $rec->produto->receita;

              foreach($receita2->itens as $rec2){
                $stockMove->downStock(
                  $rec2->produto_id,
                  (float) str_replace(",", ".", $i['quantidade']) *
                  ($rec2->quantidade/$receita2->rendimento)
                );
              }
            }else{

              $stockMove->downStock(
                $rec->produto_id,
                (float) str_replace(",", ".", $i['quantidade']) *
                ($rec->quantidade/$receita->rendimento)
              );
            }
          }

        }else{
          $stockMove->downStock((int)$i['id'], (float) str_replace(",", ".", $i['quantidade']));
        }
      }
    }

  }
}

private function recalcularCredito($valor_utilizado, $cliente_id){
  $creditos = TrocaVenda::
  where('empresa_id', $this->empresa_id)
  ->where('cliente_id', $cliente_id)
  ->where('status', 0)
  ->orderBy('id', 'desc')
  ->get();

  $tempSoma = 0;
  foreach($creditos as $c){
    $tempSoma += $c->valor_credito;
    $c->status = 1;
    $c->save();
  }

  if($tempSoma > $valor_utilizado){
    $dif = $tempSoma - $valor_utilizado;
    $cr = $creditos[sizeof($creditos)-1];

    $cr->status = 0;
    $cr->valor_credito = $dif;
    $cr->save();
  }
}

private function calcularComissaoVenda($venda, $percentual_comissao){
  $valorRetorno = 0;
  foreach($venda->itens as $i){
    if($i->produto->perc_comissao > 0){
      $valorRetorno += (($i->valor*$i->quantidade) * $i->produto->perc_comissao) / 100;
    }

    if($i->produto->valor_comissao > 0){
      $valorRetorno += $i->quantidade*$i->produto->valor_comissao;
    }
  }

  if($valorRetorno == 0){
    $valorRetorno = ($venda->valor_total * $percentual_comissao) / 100;
  }
  return $valorRetorno;
}

private function categoriaCrediario(){
  $cat = CategoriaConta::
  where('empresa_id', $this->empresa_id)
  ->where('nome', 'Crediário')
  ->first();
  if($cat != null) return $cat->id;
  $cat = CategoriaConta::create([
    'nome' => 'Crediário',
    'empresa_id' => $this->empresa_id,
    'tipo'=> 'receber'
  ]);
  return $cat->id;
}

private function salvaCredito($vendaId, $totalVenda, $clienteId, $obs, $vencimento){

  $vencimento = str_replace("/", "-", $vencimento);
  $dataVenc = \Carbon\Carbon::parse($vencimento)->format('Y-m-d');

  $categoria = $this->categoriaCrediario();

  $resultConta = ContaReceber::create([
    'venda_caixa_id' => $vendaId,
    'venda_id' => NULL,
    'data_vencimento' => $dataVenc,
    'data_recebimento' => $dataVenc,
    'valor_integral' => $totalVenda,
    'valor_recebido' => 0,
    'status' => false,
    'referencia' => $obs . " - venda PDV " . $vendaId,
    'categoria_id' => $categoria,
    'empresa_id' => $this->empresa_id,
    'cliente_id' => $clienteId
  ]);
}

private function calculaComissao($agendamento){
  $soma = 0;
  $somaDesconto = 0;
  $total = $agendamento->total + $agendamento->desconto;
  foreach($agendamento->itens as $key => $i){
    $tempDesc = 0;
    $valorServico = $i->servico->valor;

    if($key < sizeof($agendamento->itens)-1){

      $media = (((($valorServico - $total)/$total))*100);

      $media = 100 - ($media * -1);
      $tempDesc = ($agendamento->desconto*$media)/100;

      $somaDesconto += $tempDesc;

    }else{
      $tempDesc = $agendamento->desconto - $somaDesconto;
    }

    $comissao = $i->servico->comissao;

    $valorComissao = ($valorServico - $tempDesc) * ($comissao/100);
    $soma += $valorComissao;
  }

  return number_format($soma,2);
}

public function diaria(){
  // $ab = AberturaCaixa::where('ultima_venda_nfe', 0)
  // ->where('ultima_venda_nfce', 0)
  // ->where('empresa_id', $this->empresa_id)
  // ->orderBy('id', 'desc')->first();

  $vendas = VendaCaixa::
  whereDate('created_at', date('Y-m-d'))
  ->where('empresa_id', $this->empresa_id)
  ->where('usuario_id', get_id_user())
  ->get();

  foreach($vendas as $v){
    $v->tipo_pagamento = VendaCaixa::getTipoPagamento($v->tipo_pagamento);
  }
  echo json_encode($vendas);
}

public function calcComissao(){

  ComissaoVenda::
  where('empresa_id', $this->empresa_id)
  ->delete();

  $comissao = ComissaoVenda::
  where('empresa_id', $this->empresa_id)
  ->get();

    // echo $comissao;
    // die;

  $vendas = VendaCaixa::
  where('empresa_id', $this->empresa_id)
  ->get();

    // echo $vendas;
    // die;

  foreach($vendas as $v){
    $comissao = ComissaoVenda::
    where('empresa_id', $this->empresa_id)
    ->where('tabela', 'venda_caixas')
    ->where('venda_id', $v->id)
    ->first();
    if($comissao == null){
      try{
        $usuario = Usuario::find($v->usuario_id);
        if(isset($usuario->funcionario)){

          $percentual_comissao = __replace($usuario->funcionario->percentual_comissao);
          $valorComissao = ($v->valor_total * $percentual_comissao) / 100;
          ComissaoVenda::create(
            [
              'funcionario_id' => $usuario->funcionario->id,
              'venda_id' => $v->id,
              'tabela' => 'venda_caixas',
              'valor' => $valorComissao,
              'status' => 0,
              'empresa_id' => $this->empresa_id,
              'created_at' => $v->created_at,
            ]
          );
        }else{
          echo $v->usuario->nome . ' - '. $v->created_at . "<br>";
        }
      }catch(\Exception $e){
        echo "Erro: ". $e->getMessage();
      }
    }

  }
}

public function gerarQrCode(Request $request){
  $config = ConfigNota::
  where('empresa_id', $this->empresa_id)
  ->first();
  $total = (float)__truncateDecimal($request->valor, 2);
  $result = $this->gerarPix($config, $total);
  if(!isset($result['erro'])){
    return response()->json($result, $result['status']);
  }else{
    return response()->json($result['erro'], $result['status']);
  }
}

private function gerarPix($config, $valor){

  $value = session('user_logged');
  $configCaixa = ConfigCaixa::
  where('usuario_id', get_id_user())
  ->first();

  if($configCaixa == null || $configCaixa->mercadopago_access_token == ""){
    return [
      "erro" => "Configuração de caixa não cadastrada credencias de PIX",
      "status" => 401
    ];
  }

  $cnpj = str_replace(" ", "", $config->cnpj);
  $nome = explode(" ", $config->razao_social);

  try{
    \MercadoPago\SDK::setAccessToken($configCaixa->mercadopago_access_token);

    $payment = new \MercadoPago\Payment();

    $payment->transaction_amount = (float)$valor;
    $payment->description = "Venda PDV";
    $payment->payment_method_id = "pix";

    $cep = str_replace("-", "", $config->cep);
    $payment->payer = array(
      "email" => $config->email,
      "first_name" => $nome[0],
      "last_name" => $nome[1],
      "identification" => array(
        "type" => strlen($cnpj) == 14 ? 'CNPJ' : 'CPF',
        "number" => $cnpj
      ),
      "address"=>  array(
        "zip_code" => str_replace("-", "", $config->cep),
        "street_name" => $config->logradouro,
        "street_number" => $config->numero,
        "neighborhood" => $config->bairro,
        "city" => $config->municipio,
        "federal_unit" => $config->UF
      )
    );

    $payment->save();

    if($payment->transaction_details){
      $qrCode = $payment->point_of_interaction->transaction_data->qr_code_base64;

      return [
        "qrcode" => $qrCode,
        "payment_id" => $payment->id,
        "status" => 200
      ];
    }else{
      return [
        "erro" => $payment->error,
        "status" => 404
      ];
    }
  }catch(\Exception $e){
    return [
      "erro" => $e->getMessage(),
      "status" => 404
    ];
  }
}

public function consultaPix($id){

  $configCaixa = ConfigCaixa::
  where('usuario_id', get_id_user())
  ->first();
  \MercadoPago\SDK::setAccessToken($configCaixa->mercadopago_access_token);

  $payStatus = \MercadoPago\Payment::find_by_id($id);
  return response()->json($payStatus->status, 200);
    // return response()->json("approved", 200);
}

private function adicionaAoEstoque($itens, $config){
  $stockMove = new StockMove();

  foreach ($itens as $i) {
    if($config->natureza->nao_movimenta_estoque == false){

      $stockMove->pluStock($i->produto_id, (float)str_replace(",", ".", $i->quantidade));
    }

  }
}

public function saveTroca(Request $request){

  $venda = $request->venda;
  $config = ConfigNota::
  where('empresa_id', $this->empresa_id)
  ->first();

    // Verifica se precisa de senha para alterar preço dos itens
  if($config->senha_alterar_preco != ''){
      // verifica se o valor de algum item foi alterado

    $itens = $venda['itens'];
    $ids = [];
    foreach($itens as $i){
      array_push($ids, $i['id']);
    }

    $produtos = Produto::whereIn('id', $ids)->get(['id', 'valor_venda', 'nome']);

    $teve_alteracao = 0;
    foreach($itens as $i){

      foreach ($produtos as $p) {
        if ($i['id'] == $p->id ) {
          if ((float) str_replace(",", ".", $i['valor']) != (float) $p->valor_venda) {
            $teve_alteracao++;
            $somaItens = (float) __replace($i['valor']) * (float) __replace($i['quantidade']) ;

          }
        }
      }
    }

    if ($teve_alteracao && (md5($venda['senha_alterar_preco']) != $config->senha_alterar_preco)) {
      return response()->json(['message' => 'A senha para alterar o valor dos itens está incorreta'], 401);
    }
  }

  $somaItens = 0;
  $itens = $venda['itens'];
  $ids = [];
  foreach($itens as $i){
    array_push($ids, $i['id']);
  }
  $produtos = Produto::whereIn('id', $ids)->get(['id', 'valor_venda', 'nome']);

  foreach($itens as $i){
    foreach ($produtos as $p) {
      if ($i['id'] == $p->id ) {

        $somaItens = (float) __replace($i['valor']) * (float) __replace($i['quantidade']) ;

      }
    }
  }


  $totalVenda = str_replace(",", ".", $venda['valor_total']) + str_replace(",", ".", $venda['acrescimo']) - str_replace(",", ".", $venda['desconto']);
  $prod_adicionados = $venda['produtosAdicionados'];
  $prod_removidos = $venda['produtosRemovidos'];


    /**
     * se não tiver ligação com NFCe -> altera a venda ao invez de criar outra
     */

    $vendaAlvo = VendaCaixa::find($venda['venda_alvo']);
    $totalVendaOriginal = __replace($vendaAlvo->valor_total);
    $totalTroca = __replace($venda['valor_total']);

    $nfce = $vendaAlvo->NFcNumero;
    $totalAdd = 0;
    $desconto = 0;

    if($totalVendaOriginal > $totalTroca){
      $valorCredito = $totalVendaOriginal - $totalTroca;
      if($vendaAlvo->cliente){
        $cliente = $vendaAlvo->cliente;

        $cliente->valor_cashback = $cliente->valor_cashback + $valorCredito;
        $cliente->save();

        $this->sendWhatsTrocaMessage($cliente, $valorCredito);
      }
    }
    if($nfce == 0 && $totalVenda == 0){
      $result = VendaCaixa::find($venda['id']);

      // $result->valor_total += $totalVenda;
      $result->valor_total = $somaItens;

      $result->troco += str_replace(",", ".", $venda['troco']);
      $result->dinheiro_recebido += str_replace(",", ".", $venda['dinheiro_recebido']);

      $result->update();
    }else{
      // $arr = [
      //   '$venda' => $venda,
      // ];

      // return response()->json($arr, 401);
      // exit();


      foreach($prod_adicionados as $p){
        $totalAdd += ((float) str_replace(",", ".", $p['valor']) * (float) str_replace(",", ".", $p['quantidade']));
      }
      $totalRm = 0;
      foreach($prod_removidos as $p){
        $totalRm += ((float) str_replace(",", ".", $p['valor']) * (float) str_replace(",", ".", $p['quantidade']));
      }

      $desconto = $totalRm > $totalAdd ? $totalAdd : $totalRm;

      $result = VendaCaixa::create([
        'cliente_id' => $vendaAlvo->cliente_id,
        'usuario_id' => $vendaAlvo->usuario_id,
        'natureza_id' => $config->nat_op_padrao,
        'valor_total' => ($totalAdd - $desconto),
        'acrescimo' => 0,
        'troco' => str_replace(",", ".", $venda['troco']),
        'dinheiro_recebido' => str_replace(",", ".", $venda['dinheiro_recebido']),
        'forma_pagamento' => $venda['acao'] == 'credito' ? 'credito' : " ",
        'tipo_pagamento' => $venda['tipo_pagamento'],
        'estado' => 'DISPONIVEL',
        'NFcNumero' => 0,
        'chave' => '',
        'path_xml' => '',
        'nome' => $venda['nome'] ?? '',
        'rascunho' => $venda['rascunho'],
        'cpf' => $vendaAlvo->cpf ?? '',
        'observacao' => $venda['observacao'] ?? 'Troca de produto',
        'desconto' => $desconto,
        'pedido_delivery_id' => 0,
        'tipo_pagamento_1' => $venda['tipo_pagamento_1'] ?? '',
        'valor_pagamento_1' => $venda['valor_pagamento_1'] ? __replace($venda['valor_pagamento_1']) :  0,
        'tipo_pagamento_2' => $venda['tipo_pagamento_2'] ?? '',
        'valor_pagamento_2' => $venda['valor_pagamento_2'] ? __replace($venda['valor_pagamento_2']) : 0,
        'tipo_pagamento_3' => $venda['tipo_pagamento_3'] ?? '',
        'valor_pagamento_3' => $venda['valor_pagamento_3'] ? __replace($venda['valor_pagamento_3']) : 0,
        'empresa_id' => $this->empresa_id,
        'bandeira_cartao' => $venda['bandeira_cartao'],
        'cAut_cartao' => $venda['cAut_cartao'] ?? '',
        'cnpj_cartao' => $venda['cnpj_cartao'] ?? '',
        'descricao_pag_outros' => $venda['descricao_pag_outros'] ?? '',
        'numero_sequencial' => VendaCaixa::lastNumero($this->empresa_id)
      ]);
    }

    /**
     * se a venda tiver ultrapassado o desconto e a venda alvo tiver sido feita com multi-pagamento
     * a diferença será adicionada ao multi-pagamento de acordo com o tipo de pagamento selcionado
     */

    // FaturaFrenteCaixa::where('venda_caixa_id', $venda['id'])->delete();
    $pag_multi = FaturaFrenteCaixa::
    where('venda_caixa_id', $vendaAlvo->id)
    ->get();

    if($totalVenda > 0 && sizeof($pag_multi) > 0){
      FaturaFrenteCaixa::create([
        'valor' => __replace($totalVenda),
        'forma_pagamento' => $venda['tipo_pagamento'],
        'venda_caixa_id' => $result->id
      ]);

      $this->salvaCredito($result->id, __replace($totalVenda),
        $vendaAlvo->cliente, 'Pagamento tipo "'.$venda['tipo_pagamento'].'"', \Carbon\Carbon::now(), true);
    }

    if($venda['codigo_comanda'] > 0){
      session()->flash('mensagem_erro', 'Pedido não suporta troca');
      return redirect('/frenteCaixa/list');
    }
    if(isset($venda['delivery_id'])){
      session()->flash('mensagem_erro', 'Pedido/Delivery não suporta troca');
      return redirect('/frenteCaixa/list');
    }

    /**
     * Pegar os produtos removidos e retornar ao estoque,
     * deletar o item_venda do banco
     * item_venda_caixas -> delete where venda_caixa_id = venda_alvo['id'] and produto_id = produto_removido['id'] and quantidade = produto_removido['quantidade']
     */
    $stockMove = new StockMove();
    foreach($prod_removidos as $p){
      if($config->natureza->nao_movimenta_estoque == false){
        $prod = Produto::where('id', $p['id'])
        ->first();

        $stockMove->pluStock(
          (int) $p['id'],
          (float) str_replace(",", ".", $p['quantidade'])
        );
      }

      ItemVendaCaixa::where('venda_caixa_id', $vendaAlvo->id)
      ->where('produto_id', (int) $p['id'])
      ->where('quantidade',(float) str_replace(",", ".", $p['quantidade']))
      ->where('valor', (float) str_replace(",", ".", $p['valor']))
      ->first()
      ->delete();
    }

    /**
     * Pegar os produtos adicionados e descontar do estoque
     * criar o item_venda no banco
     */
    $natureza = NaturezaOperacao::find($config->nat_op_padrao);
    foreach($prod_adicionados as $p){
      $produto = Produto::find($p['id']);
      $cfop = 0;

      if($natureza->sobrescreve_cfop){
        $cfop = $natureza->CFOP_saida_estadual;
      }else{
        $cfop = $produto->CFOP_saida_estadual;
      }

      $prod = Produto::where('id', $p['id'])
      ->first();

      ItemVendaCaixa::create([
        'venda_caixa_id' => $result->id ,
        'produto_id' => (int) $p['id'],
        'quantidade' => (float) str_replace(",", ".", $p['quantidade']),
        'valor' => (float) str_replace(",", ".", $p['valor']),
        'item_pedido_id' => NULL,
        'observacao' => $p['obs'] ?? '',
        'cfop' => $cfop
      ]);

      if($config->natureza->nao_movimenta_estoque == false){
        $stockMove->downStock(
          (int) $p['id'],
          (float) str_replace(",", ".", $p['quantidade'])
        );
      }
    }

    /**
     * Registra a troca
     */
    $pr = '';
    foreach($prod_removidos as $p){
      $pr .= ' - '.$p['nome'];
    }
    $pa = '';
    foreach($prod_adicionados as $p){
      $pa .= ' - '.$p['nome'];
    }

    TrocaVendaCaixa::create([
      'empresa_id' => $this->empresa_id,
      'antiga_venda_caixas_id' => $vendaAlvo->id,
      'nova_venda_caixas_id' => $result->id,
      'prod_removidos' => $pr,
      'prod_adicionados' => $pa,
      'observacao' => '',
    ]);

    if(($totalAdd - $desconto) > 0){
      $usuario = Usuario::find($result->usuario_id);

      if(isset($usuario->funcionario)){
        $percentual_comissao = $usuario->funcionario->percentual_comissao;
        $valorComissao = (($totalAdd - $desconto) * $percentual_comissao) / 100;
        ComissaoVenda::create(
          [
            'funcionario_id' => $usuario->funcionario->id,
            'venda_id' => $result->id,
            'tabela' => 'venda_caixas',
            'valor' => $valorComissao,
            'status' => 0,
            'empresa_id' => $this->empresa_id
          ]
        );
      }
    }

    echo json_encode($result);
  }

  private function sendWhatsTrocaMessage($cliente, $valorCredito){
    if($cliente->celular != ''){
      // try{
      $config = CashBackConfig::where('empresa_id', $cliente->empresa_id)
      ->first();

      $configNota = ConfigNota::where('empresa_id', $cliente->empresa_id)
      ->first();
      $nodeurl = 'https://api.criarwhats.com/send';

      $number = $cliente->celular;
      $number = preg_replace('/[^0-9]/', '', $cliente->celular);
      $message = $config->mensagem_padrao_whatsapp;

      $nomeCliente = $cliente->razao_social;
      if($cliente->nome_fantasia != ''){
        $nomeCliente = $cliente->nome_fantasia;
      }

      $message = str_replace("{credito}", moeda($valorCredito), $message);
      $message = str_replace("{expiracao}", "", $message);
      $message = str_replace("{nome}", $nomeCliente, $message);
      $data = [
        'receiver'  => '55'.$number,
        'msgtext'   => $message,
        'token'     => $configNota->token_whatsapp,
      ];

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
      curl_setopt($ch, CURLOPT_URL, $nodeurl);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      $response = curl_exec($ch);
      curl_close($ch);
      // }catch(\Exception $e){
      //   $this->criarLog($e, 'criar');
      // }
    }


  }

  private function adicionaItensPrevenda($itens, $result, $config){
    $natureza = NaturezaOperacao::find($config->nat_op_padrao);
    foreach ($itens as $i) {

      $produto = Produto::find($i['id']);
      $cfop = 0;

      if($natureza->sobrescreve_cfop){
        $cfop = $natureza->CFOP_saida_estadual;
      }else{
        $cfop = $produto->CFOP_saida_estadual;
      }

      ItemVendaCaixaPreVenda::create([
        'venda_caixa_prevenda_id' => $result->id,
        'produto_id' => (int) $i['id'],
        'quantidade' => (float) str_replace(",", ".", $i['quantidade']),
        'valor' => (float) str_replace(",", ".", $i['valor']),
        'item_pedido_id' => isset($i['itemPedido']) ? $i['itemPedido'] : NULL,
        'observacao' => $i['obs'] ?? '',
        'cfop' => $cfop
      ]);
    }
  }

  public function vendaNFe($id){
    $vendaCaixa = VendaCaixa::findOrFail($id);

    $transportadoras = Transportadora::
    where('empresa_id', $this->empresa_id)
    ->get();

    $clientes = Cliente::
    where('empresa_id', $this->empresa_id)
    ->with('cidade')
    ->where('inativo', false)
    ->get();


    $contaPadrao = ContaBancaria::
    where('empresa_id', $this->empresa_id)
    ->where('padrao', true)
    ->first();

    $unidadesDeMedida = Produto::unidadesMedida();

    $tributacao = Tributacao::
    where('empresa_id', $this->empresa_id)
    ->first();
    $anps = Produto::lista_ANP();

    if($tributacao->regime == 1){
      $listaCSTCSOSN = Produto::listaCST();
    }else{
      $listaCSTCSOSN = Produto::listaCSOSN();
    }
    $listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
    $listaCST_IPI = Produto::listaCST_IPI();

    $natureza = Produto::
    firstNatureza($this->empresa_id);

    $formasPagamento = FormaPagamento::
    where('empresa_id', $this->empresa_id)
    ->where('status', true)
    ->get();

    $usuario = Usuario::find(get_id_user());

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

    $naturezas = NaturezaOperacao::
    where('empresa_id', $this->empresa_id)
    ->get();

    $config = ConfigNota::
    where('empresa_id', $this->empresa_id)
    ->first();

    $categorias = Categoria::
    where('empresa_id', $this->empresa_id)
    ->get();

    $tiposPagamento = Venda::tiposPagamento();
    $lastNF = RemessaNfe::lastNFe();

    $ufEmit = $config->UF;
    $ufDest = $vendaCaixa->cliente ? $vendaCaixa->cliente->cidade->uf : $config->UF;

    $vendaAprovada = false;
    if($vendaCaixa->estado == 'APROVADO'){
      $vendaAprovada = true;
    }
    return view("frontBox/register_nfe")
    ->with('naturezas', $naturezas)
    ->with('formasPagamento', $formasPagamento)
    ->with('config', $config)
    ->with('vendaCaixa', $vendaCaixa)
    ->with('usuario', $usuario)
    ->with('vendaAprovada', $vendaAprovada)
    ->with('vendedores', $vendedores)
    ->with('listaCSTCSOSN', $listaCSTCSOSN)
    ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
    ->with('listaCST_IPI', $listaCST_IPI)
    ->with('natureza', $natureza)
    ->with('outroEstado', $ufEmit != $ufDest)
    ->with('contaPadrao', $contaPadrao)
    ->with('clientes', $clientes)
    ->with('categorias', $categorias)
    ->with('anps', $anps)
    ->with('unidadesDeMedida', $unidadesDeMedida)
    ->with('tributacao', $tributacao)
    ->with('transportadoras', $transportadoras)
    ->with('tiposPagamento', $tiposPagamento)
    ->with('lastNF', $lastNF)
    ->with('listaPreco', ListaPreco::where('empresa_id', $this->empresa_id)->get())
    ->with('title', "Nova NFe");
  }

  public function find($id){
    $item = VendaCaixa::with('cliente')->findOrFail($id);
    return response()->json($item, 200);
  }

  public function enviarWhats(Request $request){
    $item = VendaCaixa::findOrFail($request->venda_id);

    $numero = preg_replace('/[^0-9]/', '', $request->celular);
    $configNota = ConfigNota::where('empresa_id', $this->empresa_id)->first();

    if($numero != '' && $configNota->token_whatsapp != ''){
      $numero = "55" . $numero;
      $files = [];
      if($request->cupom_nao_fiscal){
        $dir = $this->criaPdfCupomNaofiscal($item);
        $files[] = $dir;
      }

      if($request->danfe){

        $dir = $this->criarPdfDanfce($item);
        $files[] = $dir;
      }

      if($request->xml){
        $dir = $this->criarXml($item);
        $files[] = $dir;
      }


      if(sizeof($files) > 1){
        foreach($files as $key => $file){
          $texto = "";
          if($key == 0){
            $texto = $request->texto;
          }
          $retorno = $this->util->sendMessage($numero, $texto, $this->empresa_id, $file);
        }
      }else{
        $retorno = $this->util->sendMessage($numero, $request->texto, $this->empresa_id,
          sizeof($files) == 1 ? $files[0] : null);
      }
      $ret = json_decode($retorno);

      $this->removeArquivosVenda($item);

      if($ret->success){
        session()->flash("mensagem_sucesso", "Mensagem enviada!");
      }else{
      // dd($retorno);
        session()->flash("mensagem_erro", "Falha ao enviar mensagem");
      }

    }else{
      session()->flash("mensagem_erro", "Abra o caixa para vender!");
    }
    return redirect()->back();

  }

  private function removeArquivosVenda($venda){
    if(file_exists(public_path('vendas_caixa_temp/').'CUPOM_'.$venda->id.'.pdf')){
      unlink(public_path('vendas_caixa_temp/').'CUPOM_'.$venda->id.'.pdf');
    }

    if(file_exists(public_path('vendas_caixa_temp/').'DANFCE_'.$venda->id.'.pdf')){
      unlink(public_path('vendas_caixa_temp/').'DANFCE_'.$venda->id.'.pdf');
    }

  }

  private function criarXml($venda){
    if(file_exists(public_path('xml_nfce/'.$venda->chave.'.xml'))){

      return env("PATH_URL").'/xml_nfce/'.$venda->chave.'.xml';
    }
  }


  private function criaPdfCupomNaofiscal($venda){
    if(!is_dir(public_path('vendas_caixa_temp'))){
      mkdir(public_path('vendas_caixa_temp'), 0777, true);
    }

    $config = ConfigNota::
    where('empresa_id', $this->empresa_id)
    ->first();

    if($config->logo){
      $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents(public_path('logos/') . $config->logo));
    }else{
      $logo = null;
    }

    $usuario = Usuario::find(get_id_user());

    $configCaixa = ConfigCaixa::where('usuario_id', $usuario->id)->first();

    if($configCaixa != null && $configCaixa->cupom_modelo == 2){
      $public = env('SERVIDOR_WEB') ? 'public/' : '';
      $pathLogo = public_path('logos/') . $config->logo;
      $cupom = new Cupom($venda, $pathLogo, $config, $usuario->config ? $usuario->config->impressora_modelo : 80);
      $cupom->monta();
      $pdf = $cupom->render();
    }else{
      $cupom = new CupomNaoFiscal($venda, $config);

      if($usuario->config){
        $cupom->setPaperWidth($usuario->config->impressora_modelo);
      }
      $pdf = $cupom->render($logo);
    }


    safe_file_put_contents(public_path('vendas_caixa_temp/').'CUPOM_'.$venda->id.'.pdf', $pdf);
    return env("PATH_URL").'/vendas_caixa_temp/CUPOM_'.$venda->id.'.pdf';
  }

  private function criarPdfDanfce($venda){
    if(!is_dir(public_path('vendas_caixa_temp'))){
      mkdir(public_path('vendas_caixa_temp'), 0777, true);
    }
    if(file_exists(public_path('xml_nfce/'.$venda->chave.'.xml'))){
      $xml = safe_file_get_contents(public_path('xml_nfce/').$venda->chave.'.xml');

      $config = ConfigNota::
      where('empresa_id', $this->empresa_id)
      ->first();

      try {

        if($config->logo){
          $public = env('SERVIDOR_WEB') ? 'public/' : '';
          $logo = 'data://text/plain;base64,'. base64_encode(safe_file_get_contents(public_path('logos/') . $config->logo));
        }else{
          $logo = null;
        }

        $usuario = Usuario::find(get_id_user());
        $danfce = new Danfce($xml, $venda);
        if($usuario->config){
          $danfce->setPaperWidth($usuario->config->impressora_modelo);
        }
        $pdf = $danfce->render($logo);


        safe_file_put_contents(public_path('vendas_caixa_temp/').'DANFCE_'.$venda->id.'.pdf',$pdf);

        return env("PATH_URL").'/vendas_caixa_temp/DANFCE_'.$venda->id.'.pdf';
      } catch (InvalidArgumentException $e) {
        echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
      }
    }
  }

  public function detalhesPagamento($id){
    $item = VendaCaixa::findOrFail($id);
    return view('frontBox.detalhes_pagamento', compact('item'));
  }

}
