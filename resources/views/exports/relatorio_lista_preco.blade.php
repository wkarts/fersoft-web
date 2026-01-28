<table>
    <thead>
        <tr>
            <th>PRODUTO</th>
            <th>VALOR VENDA PADRÃO</th>
            <th>VALOR DE COMPRA</th>
            <th>VALOR DE VENDA LISTA</th>
            <th>% LUCRO</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $i)
        <tr>
            <td>
                {{$i->produto->nome}}
            </td>
            <td>{{number_format($i->produto->valor_venda, 2, ',', '.')}}
            </td>
            <td>{{number_format($i->produto->valor_compra, 2, ',', '.')}}
            </td>
            <td>{{number_format($i->valor, 2, ',', '.')}}
            </td>
            <td>{{number_format($i->percentual_lucro, 2, ',', '.')}}
            </td>

        </tr>
        @endforeach
    </tbody>
</table>
