<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>CPF/CNPJ</th>
            <th>Categoria</th>
            <th>Referencia</th>
            <th>Observacao</th>
            <th>Conta Entrada</th>
            <th>Valor Integral</th>
            <th>Juros</th>
            <th>Multa</th>
            <th>Desconto</th>
            <th>Valor Recebido</th>
            <th>Data Vencimento</th>
            <th>Data Recebimento</th>
            <th>Status</th>
            <th>Tipo Pagamento</th>
            <th>No Nota Fiscal</th>
            <th>Filial</th>
        </tr>
    </thead>
    <tbody>
        @foreach($contas as $c)
            <tr>
                <td>{{ $c->id }}</td>
                <td>{{ $c->cliente_razao ?? '--' }}</td>
                <td>{{ $c->cliente_cpf_cnpj ?? '--' }}</td>
                <td>{{ $c->categoria_nome ?? '' }}</td>
                <td>{{ $c->referencia }}</td>
                <td>{{ $c->observacao ?? '--' }}</td>
                <td>{{ $c->conta_empresa ?? '--' }}</td>
                <td>{{ number_format((float)($c->valor_integral ?? 0), 2, ',', '.') }}</td>
                <td>{{ number_format((float)($c->juros ?? 0), 2, ',', '.') }}</td>
                <td>{{ number_format((float)($c->multa ?? 0), 2, ',', '.') }}</td>
                <td>{{ number_format((float)($c->desconto ?? 0), 2, ',', '.') }}</td>
                <td>{{ number_format((float)($c->valor_recebido ?? 0), 2, ',', '.') }}</td>
                <td>{{ !empty($c->data_vencimento) ? \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') : '--' }}</td>
                <td>{{ ($c->status && !empty($c->data_recebimento)) ? \Carbon\Carbon::parse($c->data_recebimento)->format('d/m/Y') : '--' }}</td>
                <td>{{ $c->status ? 'Recebido' : 'Pendente' }}</td>
                <td>{{ $c->tipo_pagamento ?? '--' }}</td>
                <td>{{ $c->numero_nota_fiscal ?? '--' }}</td>
                <td>{{ $c->filial_nome }}</td>
            </tr>
        @endforeach
    </tbody>
</table>