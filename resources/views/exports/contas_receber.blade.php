<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exportação de Contas a Receber</title>
    <style>
        table, th, td {
            border: 1px solid #000;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            padding: 5px;
            text-align: left;
        }
        h3 {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<h3>Relatório de Contas a Receber</h3>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Cliente</th>
        <th>CPF/CNPJ</th>
        <th>Categoria</th>
        <th>Referência</th>
        <th>Valor Integral</th>
        <th>Valor Recebido</th>
        <th>Data Vencimento</th>
        <th>Data Recebimento</th>
        <th>Status</th>
        <th>Tipo Pagamento</th>
        <th>Nº Nota Fiscal</th>
        <th>Filial</th> <!-- Nova coluna -->
    </tr>
    </thead>
    <tbody>
    @foreach($contas as $c)
        <tr>
            <td>{{ $c->id }}</td>
            <td>{{ isset($c->cliente_razao) ? $c->cliente_razao : '--' }}</td>
            <td>{{ isset($c->cliente_cpf_cnpj) ? $c->cliente_cpf_cnpj : '--' }}</td>
            <td>{{ isset($c->categoria_nome) ? $c->categoria_nome : '' }}</td>
            <td>{{ $c->referencia }}</td>
            <td>{{ number_format($c->valor_integral, 2, ',', '.') }}</td>
            <td>{{ number_format($c->valor_recebido, 2, ',', '.') }}</td>
            <td>{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y') }}</td>
            <td>
                @if($c->status)
                    {{ \Carbon\Carbon::parse($c->data_recebimento)->format('d/m/Y') }}
                @else
                    --
                @endif
            </td>
            <td>{{ $c->status ? 'Recebido' : 'Pendente' }}</td>
            <td>{{ $c->tipo_pagamento }}</td>
            <td>{{ $c->numero_nota_fiscal }}</td>
            <td>{{ isset($c->filial_nome) ? $c->filial_nome : 'Matriz' }}</td> <!-- Adicionando a Filial -->
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
