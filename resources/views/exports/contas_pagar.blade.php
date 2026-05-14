<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Fornecedor</th>
        <th>CPF/CNPJ</th>
        <th>Categoria</th>
        <th>Referência</th>
        <th>Valor Integral</th>
        <th>Valor Pago</th>
        <th>Data Vencimento</th>
        <th>Data Pagamento</th>
        <th>Status</th>
        <th>Tipo Pagamento</th>
        <th>Nº Nota Fiscal</th>
        <th>Data Emissão NFe</th>
        <th>Filial</th>
    </tr>
    </thead>
    <tbody>
    @foreach($contas as $c)
        @php
            $nf = $c->numero_nota_fiscal ?? null;
            $hasNf = !is_null($nf) && $nf !== '' && (int)$nf !== 0;
            $statusPago = (bool)($c->status ?? false);
        @endphp

        <tr>
            <td>{{ $c->id ?? '--' }}</td>
            <td>
                @if(isset($c->fornecedor_razao))
                    {{ $c->fornecedor_razao }}
                @else
                    --
                @endif
            </td>
            <td>
                @if(isset($c->fornecedor_cpf_cnpj))
                    {{ $c->fornecedor_cpf_cnpj }}
                @else
                    --
                @endif
            </td>
            <td>{{ isset($c->categoria_nome) ? $c->categoria_nome : '' }}</td>
            <td>{{ $c->referencia }}</td>
            <td>{{ number_format((float)($c->valor_integral ?? 0), 2, ',', '.') }}</td>
            <td>{{ number_format((float)($c->valor_pago ?? 0), 2, ',', '.') }}</td>

            <td>
                @if(!empty($c->data_vencimento))
                    {{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') }}
                @else
                    --
                @endif
            </td>

            <td>
                @if($statusPago && !empty($c->data_pagamento))
                    {{ \Carbon\Carbon::parse($c->data_pagamento)->format('d/m/Y') }}
                @else
                    --
                @endif
            </td>

            <td>{{ $statusPago ? 'Pago' : 'Pendente' }}</td>

            <td>{{ !empty($c->tipo_pagamento) ? $c->tipo_pagamento : '--' }}</td>

            <td>{{ !is_null($nf) && $nf !== '' ? $nf : '--' }}</td>

            <td>
                @if($hasNf && !empty($c->data_emissao_nfe))
                    {{ \Carbon\Carbon::parse($c->data_emissao_nfe)->format('d/m/Y') }}
                @else
                    --
                @endif
            </td>

            <td>{{ isset($c->filial_nome) ? $c->filial_nome : 'Matriz' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
