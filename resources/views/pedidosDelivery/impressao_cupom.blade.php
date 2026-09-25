<!DOCTYPE html>
@php
    $config = \App\Models\DeliveryConfig::where('empresa_id', $pedido->empresa_id)->first();
    $nomeEmpresa = $config ? $config->nome : 'Restaurante';
@endphp
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Pedido #{{ $pedido->id }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 10px;
            width: 300px; /* Largura ideal para impressoras térmicas de cupom */
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        .bold { font-weight: bold; }
        .title { font-size: 16px; margin: 5px 0; }
        
        /* Oculta elementos na hora de mandar para a impressora */
        @media print {
            body { width: 100%; padding: 0; margin: 0; }
            @page { margin: 0; }
        }
    </style>
</head>
<body>

    <div class="text-center">
        <div class="bold title">🛒 {{ $nomeEmpresa }}</div>
        <div>PEDIDO DE DELIVERY</div>
        <div class="line"></div>
        <div class="bold">CUPOM NÃO FISCAL</div>
        <div class="bold">PEDIDO #{{ $pedido->id }}</div>
        <div>Data: {{ \Carbon\Carbon::parse($pedido->data_registro)->format('d/m/Y H:i:s') }}</div>
    </div>

    <div class="line"></div>

    <div>
        <span class="bold">Cliente:</span> {{ $pedido->cliente->nome }} {{ $pedido->cliente->sobre_nome }}<br>
        <span class="bold">Telefone:</span> {{ $pedido->telefone }}<br>
        <span class="bold">Entrega:</span> 
        @if($pedido->endereco)
            {{ $pedido->endereco->rua }}, {{ $pedido->endereco->numero }} - {{ $pedido->endereco->bairro }}
        @else
            Retirada no Balcão
        @endif
    </div>

    <div class="line"></div>

    <div class="bold text-center">ITENS DO PEDIDO</div>
    <div class="line"></div>
    
    @foreach($pedido->itens as $item)
        <div>
            <span class="bold">{{ (int)$item->quantidade }}x {{ $item->produto->produto->nome ?? 'Item' }}</span>
            <span style="float: right;">R$ {{ number_format($item->valor * $item->quantidade, 2, ',', '.') }}</span>
        </div>
        
        @if(count($item->itensAdicionais) > 0)
            @foreach($item->itensAdicionais as $add)
                <div style="font-size: 11px; padding-left: 10px;">
                    └ + {{ (int)$item->quantidade }}x {{ $add->adicional->nome() }}
                    <span style="float: right;">R$ {{ number_format($add->adicional->valor * $item->quantidade, 2, ',', '.') }}</span>
                </div>
            @endforeach
        @endif

        @if(count($item->sabores) > 0)
            <div style="font-size: 11px; padding-left: 10px;">
                └ Sabores: 
                @foreach($item->sabores as $sabor)
                    {{ $sabor->produto->produto->nome ?? 'Sabor' }}@if(!$loop->last), @endif
                @endforeach
            </div>
        @endif
        
        @if(!empty($item->observacao))
            <div style="font-size: 11px; font-style: italic; padding-left: 10px; color: #555;">
                Obs: "{{ $item->observacao }}"
            </div>
        @endif
        <div style="margin-bottom: 3px;"></div>
    @endforeach

    <div class="line"></div>

    <div>
        Subtotal: <span style="float: right;">R$ {{ number_format($pedido->valor_total - $pedido->valor_entrega + $pedido->desconto, 2, ',', '.') }}</span><br>
        @if($pedido->desconto > 0)
            Desconto: <span style="float: right;">- R$ {{ number_format($pedido->desconto, 2, ',', '.') }}</span><br>
        @endif
        Taxa de Entrega: <span style="float: right;">R$ {{ number_format($pedido->valor_entrega, 2, ',', '.') }}</span><br>
        <div class="bold" style="font-size: 13px; margin-top: 3px;">
            TOTAL A PAGAR: <span style="float: right;">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</span>
        </div>
    </div>

    <div class="line"></div>

    <div>
        <span class="bold">PAGAMENTO:</span> {{ strtoupper($pedido->forma_pagamento) }}<br>
        @if($pedido->troco_para > 0)
            <span class="bold">Troco Para:</span> R$ {{ number_format($pedido->troco_para, 2, ',', '.') }}<br>
            <span class="bold">Levar de Troco:</span> R$ {{ number_format($pedido->troco_para - $pedido->valor_total, 2, ',', '.') }}<br>
        @endif
        @if(!empty($pedido->observacao))
            <br><span class="bold">Obs. Geral:</span> {{ $pedido->observacao }}
        @endif
    </div>

    <div class="line"></div>
    <div class="text-center bold" style="margin-top: 15px;">
        Muito obrigado pela preferência!<br>
        {{ $nomeEmpresa }}
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>