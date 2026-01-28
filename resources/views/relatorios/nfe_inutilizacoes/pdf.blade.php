<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Inutilizações de NF-e</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        h2 {
            font-size: 13px;
            margin: 10px 0 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #333;
            padding: 3px 4px;
        }
        th {
            background-color: #eee;
        }
        .small {
            font-size: 10px;
        }
    </style>
</head>
<body>
<h1>Relatório de Inutilizações de NF-e</h1>
<div class="small">
    <strong>Empresa ID:</strong> {{ auth()->user()->empresa ?? '' }}<br>
    <strong>Filial:</strong>
    @if(!empty($filters['filial_nome']))
        {{ $filters['filial_nome'] }}
    @else
        {{ empty($filters['filial_id']) ? 'Matriz / Todas' : $filters['filial_id'] }}
    @endif
    <br>
    @if(!empty($filters['data_inicial']) || !empty($filters['data_final']))
        <strong>Período:</strong>
        {{ $filters['data_inicial'] ? \Carbon\Carbon::parse($filters['data_inicial'])->format('d/m/Y') : '...' }}
        até
        {{ $filters['data_final'] ? \Carbon\Carbon::parse($filters['data_final'])->format('d/m/Y') : '...' }}
        <br>
    @endif
    @if(!empty($filters['modelo']))
        <strong>Modelo:</strong> {{ $filters['modelo'] }}<br>
    @endif
    @if(!empty($filters['serie']))
        <strong>Série:</strong> {{ $filters['serie'] }}<br>
    @endif
    @if(!empty($filters['status']))
        <strong>Status:</strong> {{ $filters['status'] }}<br>
    @endif
    @if(!empty($filters['ambiente']))
        <strong>Ambiente:</strong> {{ $filters['ambiente'] }}<br>
    @endif
    <strong>Gerado em:</strong> {{ now()->format('d/m/Y H:i') }}
</div>

<h2>Resumo</h2>
<table class="small">
    <tr>
        <th>Total de inutilizações</th>
        <td>{{ $summary['total'] ?? 0 }}</td>
    </tr>
    <tr>
        <th>Por status</th>
        <td>
            @foreach(($summary['por_status'] ?? []) as $status => $qtd)
                <strong>{{ $status }}:</strong> {{ $qtd }}&nbsp;&nbsp;
            @endforeach
        </td>
    </tr>
    <tr>
        <th>Por ambiente</th>
        <td>
            @foreach(($summary['por_ambiente'] ?? []) as $amb => $qtd)
                <strong>{{ $amb }}:</strong> {{ $qtd }}&nbsp;&nbsp;
            @endforeach
        </td>
    </tr>
</table>

<h2>Detalhamento</h2>
<table>
    <thead>
    <tr>
        <th>Data/Hora</th>
        <th>Filial</th>
        <th>Modelo</th>
        <th>Série</th>
        <th>Faixa</th>
        <th>Ano</th>
        <th>Ambiente</th>
        <th>Status</th>
        <th>Protocolo</th>
        <th>Origem</th>
    </tr>
    </thead>
    <tbody>
    @forelse($registros as $item)
        <tr>
            <td>{{ optional($item->created_at)->format('d/m/Y H:i') }}</td>
            <td>{{ $item->filial_id ?? 'Matriz' }}</td>
            <td>{{ $item->modelo }}</td>
            <td>{{ $item->serie }}</td>
            <td>{{ $item->numero_inicial }} - {{ $item->numero_final }}</td>
            <td>{{ $item->ano }}</td>
            <td>{{ $item->ambiente }}</td>
            <td>{{ $item->status }}</td>
            <td>{{ $item->protocolo }}</td>
            <td>{{ $item->origem }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="10" class="small">Nenhuma inutilização encontrada para os filtros informados.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
