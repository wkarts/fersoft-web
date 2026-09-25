@extends('delivery.default')
@section('content')

<style type="text/css">
    /* Estilos para o Recibo Digital */
    .receipt-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid #f1f5f9;
        overflow: hidden;
    }
    
    .receipt-header {
        background: #f8fafc;
        border-bottom: 2px dashed #cbd5e1;
        padding: 20px;
        text-align: center;
    }

    .item-row {
        display: flex;
        align-items: flex-start;
        padding: 15px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .item-row:last-child {
        border-bottom: none;
    }

    .item-img {
        width: 75px;
        height: 75px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    /* Barra de Status */
    .status-tracker {
        display: flex;
        justify-content: space-between;
        margin: 30px 0;
        position: relative;
    }
    
    .status-tracker::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 10%;
        right: 10%;
        height: 4px;
        background: #e2e8f0;
        z-index: 1;
        border-radius: 2px;
    }

    .status-step {
        position: relative;
        z-index: 2;
        text-align: center;
        flex: 1;
    }

    .status-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 8px;
        border: 4px solid #fff;
    }

    .status-step.active .status-icon {
        background: var(--cor-primaria, #ea1d2c);
        color: #fff;
        box-shadow: 0 0 0 4px rgba(234, 29, 44, 0.2);
    }

    .status-step.active p {
        color: var(--cor-primaria, #ea1d2c);
        font-weight: bold;
    }

    .resumo-linha {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        color: #475569;
        font-size: 15px;
    }

    .resumo-total {
        display: flex;
        justify-content: space-between;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px solid #e2e8f0;
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
    }
</style>

@if(session()->has('message_sucesso'))
<div class="alert alert-success alert-dismissible fade show text-center" role="alert" style="border-radius: 0; font-weight: bold;">
    {{ session()->get('message_sucesso') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

<div class="container" style="padding-top: 30px; padding-bottom: 80px;">
    
    <div class="text-center mb-5">
        <lottie-player src="/anime/finish1.json" background="transparent" speed="1" style="width: 120px; height: 120px; margin: 0 auto;" autoplay></lottie-player>
        <h2 style="font-weight: 800; color: #10b981;">Pedido Recebido!</h2>
        <p class="text-secondary" style="font-size: 16px;">Obrigado <strong>{{$pedido->cliente->nome}}</strong>, a loja já recebeu o seu pedido.</p>
    </div>

    <?php
        $estadoAtual = isset($pedido->estado) ? strtolower(trim($pedido->estado)) : 'novo';
        // Verifica se a coluna entregue é igual a 1 (Sim)
        $isEntregue = (isset($pedido->entregue) && $pedido->entregue == 1);
        
        // Passo 1: Aguardando (Sempre ativo)
        $passo1 = true; 
        
        // Passo 2: Preparando (Ativa se a loja aprovou, finalizou ou se já entregou)
        $passo2 = (in_array($estadoAtual, ['aprovado', 'finalizado']) || $isEntregue); 
        
        // Passo 3: A Caminho (Ativa quando o caixa finaliza o pedido para o motoboy levar)
        $passo3 = ($estadoAtual == 'finalizado' || $isEntregue);

        // Passo 4: Entregue (Ativa SOMENTE quando a coluna entregue for 1)
        $passo4 = $isEntregue;
    ?>

    <div class="status-tracker d-none d-md-flex">
        <div class="status-step {{ $passo1 ? 'active' : '' }}">
            <div class="status-icon"><i class="fa fa-clock-o"></i></div>
            <p class="m-0">Aguardando</p>
        </div>
        <div class="status-step {{ $passo2 ? 'active' : '' }}">
            <div class="status-icon"><i class="fa fa-fire"></i></div>
            <p class="m-0">Preparando</p>
        </div>
        <div class="status-step {{ $passo3 ? 'active' : '' }}">
            <div class="status-icon"><i class="fa fa-motorcycle"></i></div>
            <p class="m-0">A Caminho</p>
        </div>
        <div class="status-step {{ $passo4 ? 'active' : '' }}">
            <div class="status-icon"><i class="fa fa-check-circle"></i></div>
            <p class="m-0">Entregue</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 col-md-12 mb-4">
            <h4 style="font-weight: 800; margin-bottom: 20px;"><i class="fa fa-shopping-basket text-danger"></i> Detalhes do Pedido <small class="text-muted">(#{{$pedido->id}})</small></h4>
            
            <div class="receipt-card p-4">
                <?php $geral = 0; ?>
                
                @if($pedido && count($pedido->itens) > 0)
                    @foreach($pedido->itens as $i)
                        
                        <?php 
                        $total = $i->produto->valor * $i->quantidade; 
                        
                        if(count($i->itensAdicionais) > 0){
                            foreach($i->itensAdicionais as $a){
                                $total += $a->quantidade * $a->adicional->valor * $i->quantidade;
                            }
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
                            $total += $maiorValor * $i->quantidade;
                        }
                        $geral += $total; 
                        ?>

                        <div class="item-row">
                            @if(isset($i->produto->galeria[0]))
                                <img src="/imagens_produtos/{{$i->produto->galeria[0]->path}}" alt="{{$i->produto->produto->nome}}" class="item-img"/>
                            @else
                                <img src="/imgs/no_image.png" alt="{{$i->produto->produto->nome}}" class="item-img"/>
                            @endif

                            <div class="ml-3 flex-grow-1">
                                <h5 style="font-size: 16px; font-weight: bold; margin-bottom: 5px;">{{$i->produto->produto->nome}}</h5>
                                
                                <div style="font-size: 13px; color: #64748b;">
                                    @if(count($i->itensAdicionais) > 0)
                                        <div class="mb-1"><strong>Adicionais:</strong> 
                                        @foreach($i->itensAdicionais as $key => $a)
                                            {{$a->adicional->nome()}}{{($key+1 >= count($i->itensAdicionais) ? '' : ', ')}}
                                        @endforeach
                                        </div>
                                    @endif

                                    @if(count($i->sabores) > 0)
                                        <div><strong>Sabores:</strong> 
                                        @foreach($i->sabores as $key => $s)
                                            {{$s->produto->produto->nome}}{{($key+1 >= count($i->sabores) ? '' : ' | ')}}
                                        @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="text-right" style="min-width: 90px;">
                                <div style="font-weight: bold; font-size: 14px; color: #94a3b8;">Qtd: {{(int)$i->quantidade}}</div>
                                <div style="font-weight: 800; font-size: 16px; color: #0f172a; margin-top: 5px;">R$ {{number_format($total, 2, ',', '.')}}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-center text-muted my-4"><i class="fa fa-frown-o fa-2x mb-2"></i><br>Nenhum item encontrado no pedido.</p>
                @endif
            </div>
        </div>

        <div class="col-lg-5 col-md-12">
            
            <div class="receipt-card mb-4">
                <div class="receipt-header">
                    <h4 style="margin: 0; font-weight: 800; font-size: 18px;">Resumo dos Valores</h4>
                    <small class="text-muted">{{ \Carbon\Carbon::parse($pedido->data_registro)->format('d/m/Y \à\s H:i') }}</small>
                </div>
                
                <div class="card-body" style="background: #fff;">
                    <div class="resumo-linha">
                        <span>Subtotal dos itens</span>
                        <span>R$ {{number_format($geral, 2, ',', '.')}}</span>
                    </div>

                    <?php $valorDesconto = 0; ?>
                    @if($pedido->cupom)
                        <?php 
                        if($pedido->cupom->tipo == 'percentual'){
                            $valorDesconto = ($geral*$pedido->cupom->valor)/100;
                        } else {
                            $valorDesconto = $pedido->cupom->valor;
                        }
                        ?>
                        <div class="resumo-linha text-success" style="font-weight: bold;">
                            <span>Desconto ({{$pedido->cupom->codigo}})</span>
                            <span>- R$ {{number_format($valorDesconto, 2, ',', '.')}}</span>
                        </div>
                    @endif

                    @if($pedido->endereco_id != null)
                        <div class="resumo-linha">
                            <span>Taxa de Entrega</span>
                            <span>R$ {{number_format($valorEntrega, 2, ',', '.')}}</span>
                        </div>
                    @endif

                    <div class="resumo-total">
                        <span>Total Pago</span>
                        <span style="color: var(--cor-primaria, #ea1d2c);">R$ {{number_format($pedido->valor_total, 2, ',', '.')}}</span>
                    </div>
                </div>
            </div>

            <div class="receipt-card p-4 bg-light">
                
                <?php
                    $nome_pagamento = "Dinheiro";
                    if($pedido->forma_pagamento == 'maquineta' || $pedido->forma_pagamento == 'credito' || $pedido->forma_pagamento == 'debito'){
                        $nome_pagamento = "Máquina de Cartão (Crédito/Débito)";
                    } elseif ($pedido->forma_pagamento == 'pagseguro') {
                        $nome_pagamento = "Pagamento On-line (".$pedido->pagseguro->parcelas."x)";
                    }
                ?>

                <h5 style="font-size: 15px; font-weight: 800; margin-bottom: 15px;"><i class="fa fa-credit-card text-secondary"></i> Pagamento</h5>
                <p style="font-size: 14px; margin-bottom: 25px; color: #475569;"><strong>{{$nome_pagamento}}</strong></p>

                <h5 style="font-size: 15px; font-weight: 800; margin-bottom: 15px;"><i class="fa fa-map-marker text-secondary"></i> Entrega</h5>
                @if($pedido->endereco_id != null)
                    <p style="font-size: 14px; margin-bottom: 0; color: #475569; line-height: 1.5;">
                        <strong>{{$pedido->endereco->rua}}, {{$pedido->endereco->numero}}</strong><br>
                        {{$pedido->endereco->bairro}}<br>
                        Ref: {{$pedido->endereco->referencia}}
                    </p>
                    
                    @if($config)
                        <div class="mt-3 p-2 rounded" style="background: #e2e8f0; font-size: 13px; text-align: center;">
                            <i class="fa fa-clock-o"></i> Tempo estimado: <strong>{{$config->tempo_medio_entrega}} min</strong>
                        </div>
                    @endif
                @else
                    <p style="font-size: 14px; margin-bottom: 0; color: #475569;">
                        <strong>Retirada no Balcão</strong><br>
                        Você vai buscar este pedido na loja.
                    </p>
                @endif
            </div>

            <button onclick="window.location.reload();" class="btn btn-outline-success btn-block mt-4" style="border-radius: 8px; padding: 12px; font-weight: bold; border-width: 2px;">
                <i class="fa fa-refresh"></i> Atualizar Status do Pedido
            </button>

            <a href="/cardapio" class="btn btn-primary btn-block mt-2" style="border-radius: 8px; padding: 12px; font-weight: bold;">
                <i class="fa fa-home"></i> Voltar ao Cardápio
            </a>

        </div>
    </div>
</div>

<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        // Recarrega a página automaticamente a cada 30 segundos (30000 milissegundos)
        // Isso fará a barra de status andar sozinha quando a loja mudar no painel
        setTimeout(function(){
            window.location.reload();
        }, 30000);
    });
</script>
@endsection