@extends('delivery.default')
@section('content')

<div class="container" style="padding-top: 20px; padding-bottom: 100px;">
    
    <!-- CABEÇALHO DA CATEGORIA -->
    <div class="d-flex align-items-center mb-4">
        <a href="javascript:history.back()" style="color: var(--cor-primaria); font-size: 24px; margin-right: 15px; text-decoration: none;">
            <i class="fa fa-arrow-left"></i>
        </a>
        <div>
            <h2 style="margin: 0; font-size: 22px; font-weight: 800;">{{$categoria->nome}}</h2>
            @if($categoria->descricao)
                <p style="margin: 0; font-size: 14px; color: var(--cor-texto-claro);">{{$categoria->descricao}}</p>
            @endif
        </div>
    </div>

    <?php $ativo = false; ?>

    <!-- LISTA DE PRODUTOS -->
    <div class="row">
        @foreach($produtos as $p)
            @if($p->status)
                <?php $ativo = true; ?>
                
                <div class="col-12 col-md-6">
                    <!-- O link envolve todo o cartão -->
                    <a href="{{ $p->itemPedido() ? '/cardapio/acompanhamento/'.$p->id : '#!' }}" style="text-decoration: none; color: inherit; display: block;">
                        
                        <!-- Cartão do Produto usando a nossa nova classe -->
                        <div class="card-moderno d-flex align-items-center" style="padding: 12px; {{ !$p->itemPedido() ? 'opacity: 0.5; filter: grayscale(100%);' : '' }}">
                            
                            <!-- Imagem do Produto -->
                            <div style="width: 85px; height: 85px; flex-shrink: 0; border-radius: 10px; overflow: hidden; background-color: #f1f5f9; margin-right: 15px;">
                                @if(count($p->galeria) > 0)
                                    <img src="/imagens_produtos/{{$p->galeria[0]->path}}" style="width: 100%; height: 100%; object-fit: cover;" alt="{{$p->produto->nome}}">
                                @else
                                    <img src="/imagens/sem-imagem.png" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.5;" alt="Sem imagem">
                                @endif
                            </div>

                            <!-- Textos e Preços -->
                            <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; height: 85px;">
                                <div>
                                    <h3 style="font-size: 16px; margin: 0; line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{$p->produto->nome}}
                                    </h3>
                                    
                                    @if(!$p->itemPedido())
                                        <span style="font-size: 10px; background-color: #fee2e2; color: #ef4444; padding: 3px 6px; border-radius: 4px; font-weight: bold; margin-top: 5px; display: inline-block;">
                                            ESGOTADO HOJE
                                        </span>
                                    @endif
                                </div>

                                <div class="d-flex justify-content-between align-items-end">
                                    <span style="color: #10b981; font-weight: 800; font-size: 16px;">
                                        R$ {{number_format($p->valor, 2, ',', '.')}}
                                    </span>
                                    
                                    @if($p->itemPedido())
                                        <!-- Botãozinho de "+" charmoso -->
                                        <div style="background-color: #fff0f0; color: var(--cor-primaria); width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold;">
                                            <i class="fa fa-plus"></i>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </a>
                </div>

            @endif
        @endforeach
    </div>

    <!-- MENSAGEM SE A CATEGORIA ESTIVER VAZIA -->
    @if(!$ativo)
    <div class="text-center" style="margin-top: 60px;">
        <i class="fa fa-inbox" style="font-size: 50px; color: #cbd5e1; margin-bottom: 15px;"></i>
        <h4 style="color: var(--cor-texto-claro);">Poxa, está vazio!</h4>
        <p style="color: #94a3b8; font-size: 14px;">Ainda não temos produtos aqui.</p>
    </div>
    @endif

</div>

<!-- BOTÃO FLUTUANTE DE CARRINHO (Sempre visível no rodapé, estilo iFood) -->
<div style="position: fixed; bottom: 0; left: 0; width: 100%; background: white; padding: 15px; box-shadow: 0 -4px 15px rgba(0,0,0,0.05); z-index: 1000; border-top: 1px solid #f1f5f9;">
    <a href="/carrinho" class="btn-principal d-flex justify-content-between align-items-center" style="text-decoration: none;">
        <span><i class="fa fa-shopping-basket mr-2"></i> Ver meu pedido</span>
        <span>➔</span>
    </a>
</div>

@endsection