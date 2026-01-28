@php
$somaLucro = 0;
$somaVendas = 0;
@endphp
@foreach($data as $item)
<table>
    <thead>
        <tr>
            <th style="width: 200px">DATA</th>
            <th style="width: 250px">CLIENTE</th>
            <th style="width: 200px">Nº DA VENDA/PEDIDO</th>
            <th style="width: 150px">TOTAL</th>
            <th style="width: 150px">TIPO</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $item['data'] }}</td>
            <td>{{ $item['cliente'] }}</td>
            <td>{{ $item['numero']}}</td>
            <td>R$ {{ number_format($item['total'], $casasDecimais, ',', '.')}}</td>
            <td>{{ $item['tipo']}}</td>

        </tr>

        <tr style="background: #EEE5FF">
            <td>Produto</td>
            <td>Quantidade</td>
            <td>Valor unit.</td>
            <td>Custo</td>
            <td>Lucro</td>
            <td style="width: 150px">Lucro %</td>
        </tr>

        @foreach($item['itens'] as $i)

        <tr>
            <td>{{$i['produto']}}</td>
            <td>{{ number_format($i['quantidade'], $casasDecimaisQtd, ',', '.') }}</td>
            <td>{{ number_format($i['valor'], $casasDecimais, ',', '.') }}</td>
            <td>{{ number_format($i['custo'], $casasDecimais, ',', '.') }}</td>
            <td>{{ number_format($i['lucro'], $casasDecimais, ',', '.') }}</td>
            <td>{{ number_format($i['lucro_perc'], 2, ',', '.') }}</td>
        </tr>
        @php
        $somaLucro += ($i['lucro'] * $i['quantidade']);
        @endphp

        @endforeach

        @php
        $somaVendas += $item['total'];
        @endphp

        <tr style="background: #EE2D41; color: #fff;">
            <td>Total de lucro</td>
            <td>R$ {{ number_format($somaLucro, $casasDecimais, ',', '.')}}</td>
        </tr>

    </tbody>
</table>
@endforeach

<tr style="background: #EE2D41; color: #fff;">
    <td>Soma Geral de Vendas</td>
    <td>R$ {{ number_format($somaVendas, 2, ',', '.') }}</td>
</tr>
