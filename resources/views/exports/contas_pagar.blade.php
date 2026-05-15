<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Fornecedor</th>
        <th>CPF/CNPJ</th>
        <th>Categoria</th>
        <th>Referência</th>
        <th>Observação</th> <th>Conta Saída</th> <th>Valor Integral</th>
        <th>Juros</th> <th>Multa</th> <th>Desconto</th> <th>Valor Pago</th>
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
        @endphp

        <tr>
            <td>{{ $c->id }}</td>
            <td>{{ $c->fornecedor_razao ?? '--' }}</td>
            <td>{{ $c->fornecedor_cpf_cnpj ?? '--' }}</td>
            <td>{{ $c->categoria_nome ?? '' }}</td>
            <td>{{ $c->referencia }}</td>
            <td>{{ $c->observacao ?? '--' }}</td>
            <td>{{ $c->conta_empresa ?? '--' }}</td>
            <td>{{ number_format((float)($c->valor_integral ?? 0), 2, ',', '.') }}</td>
            <td>{{ number_format((float)($c->juros ?? 0), 2, ',', '.') }}</td>
            <td>{{ number_format((float)($c->multa ?? 0), 2, ',', '.') }}</td>
            <td>{{ number_format((float)($c->desconto ?? 0), 2, ',', '.') }}</td>
            <td>{{ number_format((float)($c->valor_pago ?? 0), 2, ',', '.') }}</td>

            <td>{{ !empty($c->data_vencimento) ? \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') : '--' }}</td>
            <td>{{ ($c->status && !empty($c->data_pagamento)) ? \Carbon\Carbon::parse($c->data_pagamento)->format('d/m/Y') : '--' }}</td>
            <td>{{ $c->status ? 'Pago' : 'Pendente' }}</td>
            <td>{{ $c->tipo_pagamento ?? '--' }}</td>
            <td>{{ $c->numero_nota_fiscal ?? '--' }}</td>
            <td>{{ ($hasNf && !empty($c->data_emissao_nfe)) ? \Carbon\Carbon::parse($c->data_emissao_nfe)->format('d/m/Y') : '--' }}</td>
            <td>{{ $c->filial_nome ?? 'Matriz' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>