@extends('delivery.default')
@section('content')

<div class="container" style="padding-top: 20px; padding-bottom: 160px;">

    @if(session()->has('message_sucesso'))
        <div class="alert alert-success" style="border-radius: 10px; font-weight: bold;">
            <i class="fa fa-check-circle mr-2"></i> {{ session()->get('message_sucesso') }}
        </div>
    @endif

    <div class="d-flex align-items-center mb-4">
        <a href="/cardapio" style="color: var(--cor-primaria); font-size: 24px; margin-right: 15px; text-decoration: none;">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h2 style="margin: 0; font-size: 22px; font-weight: 800; color: var(--cor-texto-escuro); text-transform: uppercase;">
            Meu Pedido
        </h2>
    </div>

    <?php $geral = 0; ?>

    @if($pedido && count($pedido->itens) > 0)
        <div class="row">
            @foreach($pedido->itens as $i)
                <div class="col-12 col-md-6">
                    <div class="card-moderno" style="padding: 15px; margin-bottom: 15px; display: flex; flex-direction: column;">
                        
                        <div style="display: flex; gap: 15px;">
                            <div style="width: 80px; height: 80px; border-radius: 10px; overflow: hidden; background: #f1f5f9; flex-shrink: 0;">
                                @if(isset($i->produto->galeria[0]))
                                    <img loading="lazy" src="/imagens_produtos/{{$i->produto->galeria[0]->path}}" style="width: 100%; height: 100%; object-fit: cover;" alt="...">
                                @else
                                    <img src="/imgs/no_image.png" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.5;" alt="...">
                                @endif
                            </div>

                            <div style="flex-grow: 1;">
                                <h4 style="font-size: 16px; font-weight: 800; margin: 0 0 5px 0; color: var(--cor-texto-escuro); line-height: 1.2;">
                                    {{$i->produto->produto->nome}}
                                </h4>

                                <?php 
                                    $total_item_bruto = $i->produto->valor * $i->quantidade; 
                                    $valor_unitario = $i->produto->valor;
                                ?>

                                @if(count($i->itensAdicionais) > 0)
                                    <div style="font-size: 12px; color: var(--cor-texto-claro); margin-bottom: 4px;">
                                        <strong style="color: #475569;">Com:</strong> 
                                        @foreach($i->itensAdicionais as $a)
                                            {{$a->adicional->nome()}} (+R${{number_format($a->adicional->valor, 2, ',', '.')}})
                                            <?php $total_item_bruto += $i->quantidade * $a->adicional->valor; ?>
                                        @endforeach
                                    </div>
                                @endif

                                @if(count($i->sabores) > 0)
                                    <div style="font-size: 12px; color: var(--cor-texto-claro); margin-bottom: 4px;">
                                        <strong style="color: #475569;">Sabores:</strong> 
                                        @foreach($i->sabores as $key => $s)
                                            {{$s->produto->produto->nome}}{{($key+1 >= count($i->sabores) ? '' : ' | ')}}
                                        @endforeach
                                        <br>Tamanho: <strong style="color: #475569;">{{$i->tamanho->nome()}}</strong>
                                    </div>

                                    <?php 
                                        $maiorValor = 0; 
                                        $somaValores = 0;
                                        foreach($i->sabores as $it){
                                            $v = $it->maiorValor($it->produto->id, $i->tamanho_id);
                                            $somaValores += $v;
                                            if($v > $maiorValor) $maiorValor = $v;
                                        }

                                        if(env("DIVISAO_VALOR_PIZZA") == 1){
                                            $maiorValor = $somaValores/sizeof($i->sabores);
                                        }

                                        foreach($i->itensAdicionais as $a){
                                            $maiorValor += $a->adicional->valor;
                                        }
                                        $total_item_bruto = $maiorValor * $i->quantidade;
                                        $valor_unitario = $maiorValor;
                                    ?>
                                @endif

                                @if($i->observacao != '')
                                    <div style="font-size: 12px; color: #ef4444; background: #fef2f2; padding: 4px 8px; border-radius: 6px; display: inline-block; margin-top: 4px; font-weight: bold;">
                                        Obs: {{$i->observacao}}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                            
                            <div>
                                <span style="font-size: 11px; color: #94a3b8; display: block; margin-bottom: -2px;">Subtotal</span>
                                <span style="color: #10b981; font-weight: 800; font-size: 18px;">
                                    R$ {{number_format($total_item_bruto, 2, ',', '.')}}
                                </span>
                            </div>

                            <div style="display: flex; gap: 8px; align-items: center;">
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: flex; align-items: center; padding: 4px; height: 40px;">
                                    <input id="qtd_item_{{$i->id}}" type="number" class="qtd text-center" value="{{(int)$i->quantidade}}" style="border: none !important; background: transparent !important; width: 45px; font-weight: bold; padding: 0 !important; box-shadow: none !important; outline: none !important;">
                                    <button onclick="refresh({{$i->id}})" style="background: var(--cor-primaria); color: white; border: none; border-radius: 6px; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                        <i class="fa fa-refresh" style="font-size: 12px;"></i>
                                    </button>
                                </div>
                                <button onclick="removeItem({{$i->id}})" style="background: #fee2e2; color: #ef4444; border: none; border-radius: 8px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s;">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php $geral += $total_item_bruto; ?>
            @endforeach
        </div>
    @else
        <div class="card-moderno text-center" style="padding: 50px 20px; margin-top: 20px;">
            <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                <i class="fa fa-shopping-basket" style="font-size: 32px; color: #94a3b8;"></i>
            </div>
            <h3 style="color: var(--cor-texto-escuro); font-weight: 800; font-size: 20px; margin-bottom: 10px;">Seu carrinho está vazio</h3>
            <p style="color: var(--cor-texto-claro); font-size: 14px; margin-bottom: 25px;">Que tal adicionar algumas delícias ao seu pedido?</p>
            <a href="/cardapio" class="btn-principal" style="text-decoration: none; display: inline-block; width: auto; padding: 12px 30px !important;">
                <i class="fa fa-bars mr-2"></i> Ver Cardápio
            </a>
        </div>
    @endif
</div>

<div style="position: fixed; bottom: 0; left: 0; width: 100%; background: white; padding: 15px; box-shadow: 0 -5px 20px rgba(0,0,0,0.08); z-index: 1000; border-top: 1px solid #f1f5f9;">
    <div class="container">
        <?php $valorMinimo = 50.00; ?>

        @if($pedido && count($pedido->itens) > 0)
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span style="font-size: 15px; font-weight: 700; color: var(--cor-texto-claro);">Total do Pedido:</span>
                <span style="font-size: 22px; font-weight: 900; color: #10b981;">R$ {{number_format($geral, 2, ',', '.')}}</span>
            </div>

            @if($geral >= $valorMinimo)
                <div class="row">
                    <div class="col-12 col-md-6 mb-2 mb-md-0">
                        <a href="/cardapio" class="btn btn-warning w-100 d-flex justify-content-center align-items-center" style="height: 50px; font-weight: bold; color: white !important;">
                            Continuar Comprando
                        </a>
                    </div>
                    <div class="col-12 col-md-6">
                        <a href="/carrinho/forma_pagamento" class="btn-success w-100 d-flex justify-content-center align-items-center btn" style="height: 50px; font-weight: bold; font-size: 16px !important;">
                            Continuar ➔
                        </a>
                    </div>
                </div>
            @else
                <div style="background: #fef2f2; border: 1px solid #fca5a5; padding: 10px; border-radius: 8px; margin-bottom: 10px;">
                    <p style="margin: 0; font-size: 13px; color: #ef4444; text-align: center; font-weight: 600;">
                        O valor mínimo é de R$ {{ number_format($valorMinimo, 2, ',', '.') }}. <br>
                        Faltam R$ {{ number_format($valorMinimo - $geral, 2, ',', '.') }}
                    </p>
                </div>
                <div class="row">
                    <div class="col-12">
                        <a href="/cardapio" class="btn btn-warning w-100 d-flex justify-content-center align-items-center" style="height: 50px; font-weight: bold; color: white !important;">
                            Adicionar Mais Produtos
                        </a>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

@endsection