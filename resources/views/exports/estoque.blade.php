<table>
    <thead>
        <tr>
            <th>PRODUTO</th>
            <th>ESTOQUE ATUAL</th>
            <th>CUSTO</th>
            <th>MARGEM LUCRO</th>
            <th>VALOR DE VENDA</th>
            <th>PROJEÇÃO TOTAL DE VENDAS</th>
            <th>VALOR TOTAL DE ESTOQUE</th>
            <th>DATA ULT. COMPRA</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $p)
        <tr>
            <td>{{$p->nome}} {{$p->str_grade}}</td>
            @if($p->unidade_venda == 'UNID' || $p->unidade_venda == 'UN')
            <td>{{number_format($p->quantidade)}} {{$p->unidade_venda}}</td>
            @else
            <td>{{number_format($p->quantidade, 3, ',', '.')}} {{$p->unidade_venda}}</td>
            @endif
            <td>R$ {{number_format($p->valor_compra, 2, ',', '.')}}</td>
            <td>{{number_format($p->percentual_lucro, 2)}}%</td>
            <td>R$ {{number_format($p->valor_venda, 2, ',', '.')}}</td>
            <td>R$ {{number_format($p->valor_venda*$p->quantidade, 2, ',', '.')}}</td>
            <td>R$ {{number_format($p->valor_compra*$p->quantidade, 2, ',', '.')}}</td>
            <td>{{$p->data_ultima_compra}}</td>
        </tr>
        @endforeach
    </tbody>
</table>
