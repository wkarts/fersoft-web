<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pedido Delivery #{{ $pedido->id }}</title>
    <style>
        @page {
            margin: 3mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 8.5pt;
            line-height: 1.25;
            color: #000;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .muted {
            font-size: 7pt;
        }

        .logo {
            max-width: 32mm;
            max-height: 18mm;
            margin-bottom: 1.5mm;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }

        .pedido-numero {
            font-size: 12pt;
            font-weight: bold;
            margin: 1.5mm 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .items th {
            border-bottom: 1px solid #000;
            padding: 1.2mm 0.5mm;
            font-size: 7pt;
        }

        .items td {
            padding: 1.3mm 0.5mm;
            vertical-align: top;
            border-bottom: 1px dotted #999;
        }

        .col-desc {
            width: 54%;
        }

        .col-qtd {
            width: 10%;
            text-align: center;
        }

        .col-valor {
            width: 18%;
            text-align: right;
        }

        .detail {
            display: block;
            font-size: 7pt;
            margin-top: 0.6mm;
        }

        .summary td {
            padding: 0.6mm 0;
        }

        .summary .total td {
            padding-top: 1.2mm;
            border-top: 1px solid #000;
            font-size: 10pt;
            font-weight: bold;
        }

        .block {
            margin: 1.2mm 0;
        }

        .qr {
            width: 34mm;
            height: 34mm;
            margin-top: 2mm;
        }
    </style>
</head>
<body>
    <div class="center">
        @if($logoDataUri)
            <img class="logo" src="{{ $logoDataUri }}" alt="Logo">
        @endif

        <div class="bold">{{ $empresaNome }}</div>

        @if($empresaDocumento)
            <div>CNPJ: {{ $empresaDocumento }}</div>
        @endif

        @if($empresaEndereco)
            <div class="muted">{{ $empresaEndereco }}</div>
        @endif

        @if($empresaTelefone)
            <div class="muted">Telefone: {{ $empresaTelefone }}</div>
        @endif
    </div>

    <div class="separator"></div>

    <div class="center pedido-numero">PEDIDO #{{ $pedido->id }}</div>
    <div class="center muted">PEDIDO OPERACIONAL - NAO E DOCUMENTO FISCAL</div>

    <div class="separator"></div>

    <div class="block">
        <div><span class="bold">Cliente:</span> {{ $clienteNome }}</div>

        @if($telefone)
            <div><span class="bold">Telefone:</span> {{ $telefone }}</div>
        @endif

        @if($dataPedido)
            <div>
                <span class="bold">Data/Hora:</span>
                {{ $dataPedido->format('d/m/Y H:i') }}
            </div>
        @endif

        @if($previsaoEntrega)
            <div>
                <span class="bold">Previsao:</span>
                {{ $previsaoEntrega->format('H:i') }}
            </div>
        @endif
    </div>

    <div class="separator"></div>

    <table class="items">
        <thead>
            <tr>
                <th class="col-desc">DESCRICAO</th>
                <th class="col-qtd">QT</th>
                <th class="col-valor">UNIT.</th>
                <th class="col-valor">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @forelse($itens as $item)
                <tr>
                    <td class="col-desc">
                        <span class="bold">{{ $item['nome'] }}</span>

                        @if($item['tamanho'])
                            <span class="detail">Tamanho: {{ $item['tamanho'] }}</span>
                        @endif

                        @foreach($item['sabores'] as $index => $sabor)
                            <span class="detail">
                                Sabor {{ $index + 1 }}/{{ count($item['sabores']) }}: {{ $sabor }}
                            </span>
                        @endforeach

                        @foreach($item['adicionais'] as $adicional)
                            <span class="detail">
                                + {{ $adicional['nome'] }}
                                @if($adicional['quantidade'] > 1)
                                    ({{ number_format($adicional['quantidade'], 2, ',', '.') }})
                                @endif
                            </span>
                        @endforeach

                        @if($item['observacao'])
                            <span class="detail bold">OBS: {{ $item['observacao'] }}</span>
                        @endif
                    </td>
                    <td class="col-qtd">
                        {{ number_format($item['quantidade'], 2, ',', '.') }}
                    </td>
                    <td class="col-valor">
                        {{ number_format($item['valor_unitario'], 2, ',', '.') }}
                    </td>
                    <td class="col-valor">
                        {{ number_format($item['valor_total'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center">Pedido sem itens.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="separator"></div>

    <table class="summary">
        <tr>
            <td>Subtotal</td>
            <td class="right">R$ {{ number_format($subtotal, 2, ',', '.') }}</td>
        </tr>

        @if($desconto > 0)
            <tr>
                <td>Desconto</td>
                <td class="right">- R$ {{ number_format($desconto, 2, ',', '.') }}</td>
            </tr>
        @endif

        @if($frete > 0)
            <tr>
                <td>Taxa de entrega</td>
                <td class="right">R$ {{ number_format($frete, 2, ',', '.') }}</td>
            </tr>
        @endif

        <tr class="total">
            <td>TOTAL</td>
            <td class="right">R$ {{ number_format($total, 2, ',', '.') }}</td>
        </tr>
    </table>

    @if($formaPagamento || $trocoPara > 0)
        <div class="separator"></div>

        @if($formaPagamento)
            <div><span class="bold">Pagamento:</span> {{ $formaPagamento }}</div>
        @endif

        @if($trocoPara > 0)
            <div>
                <span class="bold">Troco para:</span>
                R$ {{ number_format($trocoPara, 2, ',', '.') }}
            </div>
        @endif
    @endif

    <div class="separator"></div>

    @if($enderecoEntrega)
        <div class="block">
            <div class="bold">ENTREGA</div>
            <div>{{ $enderecoEntrega }}</div>
        </div>
    @else
        <div class="block">
            <div class="bold">RETIRADA NO LOCAL</div>
        </div>
    @endif

    @if($observacao)
        <div class="block">
            <div class="bold">OBSERVACAO DO PEDIDO</div>
            <div>{{ $observacao }}</div>
        </div>
    @endif

    @if($qrCodeDataUri)
        <div class="separator"></div>
        <div class="center">
            <div class="bold">ROTA DE ENTREGA</div>
            <img class="qr" src="{{ $qrCodeDataUri }}" alt="QR Code da rota">
        </div>
    @endif

    <div class="separator"></div>
    <div class="center muted">FERSOFT ERP - Delivery</div>
</body>
</html>
