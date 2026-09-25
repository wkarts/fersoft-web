@extends('delivery.default')
@section('content')

<div class="container" style="padding-top: 30px; padding-bottom: 50px;">

    @if(session()->has('message_erro'))
        <div class="alert alert-danger" style="border-radius: 10px;">{{ session()->get('message_erro') }}</div>
    @endif

    <div class="d-flex align-items-center mb-4">
        <a href="/cardapio" style="color: var(--cor-primaria); font-size: 24px; margin-right: 15px; text-decoration: none;">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h2 style="margin: 0; font-size: 22px; font-weight: 800; text-transform: uppercase;">Meus Pedidos</h2>
    </div>

    @if(count($pedidos) == 0)
        <div class="card-moderno text-center py-5">
            <i class="fa fa-history" style="font-size: 40px; color: #cbd5e1; margin-bottom: 15px;"></i>
            <h4 style="color: var(--cor-texto-claro);">Nenhum pedido realizado ainda.</h4>
            <a href="/cardapio" class="btn-principal mt-3" style="width: auto; padding: 10px 25px !important;">IR PARA CARDÁPIO</a>
        </div>
    @endif

    <!-- LISTA DE PEDIDOS -->
    @foreach($pedidos as $p)
        <div class="card-moderno mb-4" style="padding: 0; overflow: hidden;">
            
            <!-- Cabeçalho do Pedido (Sempre visível) -->
            <div style="background: #f8fafc; padding: 15px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 12px; color: var(--cor-texto-claro); font-weight: bold;">PEDIDO #{{$p->id}}</span>
                    <div style="font-size: 13px; color: #64748b;">{{ \Carbon\Carbon::parse($p->data_registro)->format('d/m/Y - H:i')}}</div>
                </div>
                
                <!-- Badge de Status -->
                @php
                    $status = [
                        'nv' => ['Novo', 'primary'], 'ap' => ['Aprovado', 'primary'],
                        'rp' => ['Reprovado', 'danger'], 'rc' => ['Recusado', 'warning'],
                        'fi' => ['Finalizado', 'success']
                    ];
                    $s = $status[$p->estado] ?? ['Finalizado', 'info'];
                @endphp
                <span class="badge badge-{{$s[1]}}" style="font-size: 12px; padding: 6px 10px; border-radius: 6px;">{{$s[0]}}</span>
            </div>

            <!-- Detalhes do Pedido (Expandível) -->
            <div style="padding: 15px;">
                @foreach($p->itens as $i)
                    <div class="d-flex mb-3">
                        <div style="width: 50px; height: 50px; border-radius: 8px; background: #f1f5f9; margin-right: 12px; overflow: hidden;">
                            @if(isset($i->produto->galeria[0]))
                                <img src="/imagens_produtos/{{$i->produto->galeria[0]->path}}" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <img src="/imgs/no_image.png" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.5;">
                            @endif
                        </div>
                        <div style="flex-grow: 1;">
                            <h4 style="font-size: 14px; font-weight: 700; margin: 0;">{{(int)$i->quantidade}}x {{$i->produto->produto->nome}}</h4>
                            <p style="font-size: 12px; color: var(--cor-texto-claro); margin: 0;">
                                @foreach($i->itensAdicionais as $a) {{ $a->adicional->nome }} @endforeach
                            </p>
                        </div>
                        <div style="font-weight: 700; font-size: 14px;">
                            R$ {{number_format($i->produto->valor * $i->quantidade, 2, ',', '.')}}
                        </div>
                    </div>
                @endforeach

                <hr style="margin: 10px 0;">

                <div class="d-flex justify-content-between align-items-center">
                    <h4 style="font-size: 16px; font-weight: 800; margin: 0;">Total: R$ {{number_format($p->valor_total, 2, ',', '.')}}</h4>
                    
                    @if($p->estado != 'nv')
                        <a href="/carrinho/pedir_novamente/{{$p->id}}" class="btn btn-sm btn-success" style="font-size: 12px; font-weight: bold;">
                            <i class="fa fa-refresh"></i> Repetir Pedido
                        </a>
                    @endif
                </div>

                <!-- Info Adicional (Endereço/Pagamento) -->
                <div style="margin-top: 15px; font-size: 12px; color: #64748b; background: #f8fafc; padding: 10px; border-radius: 8px;">
                    <i class="fa fa-map-marker"></i> {{$p->endereco ? $p->endereco->rua.', '.$p->endereco->numero : 'Retirada no Balcão'}}
                    <br><i class="fa fa-credit-card"></i> Pagamento: 
                    {{ $p->forma_pagamento == 'credito' ? 'Crédito' : ($p->forma_pagamento == 'debito' ? 'Débito' : 'Dinheiro') }}
                </div>
            </div>
        </div>
    @endforeach
</div>

@endsection