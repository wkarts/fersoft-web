<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Acompanhamento de Clientes - {{ $periodoTitulo }}</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }
        h1 {
            font-size: 18px;
            text-align: center;
            margin-bottom: 0;
        }
        h2 {
            font-size: 14px;
            text-align: center;
            margin-top: 2px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 3px 4px;
        }
        th {
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
        .small {
            font-size: 9px;
        }
        .no-border {
            border: none !important;
        }
    </style>
</head>
<body>
<h1>Acompanhamento de Clientes</h1>
<h2>{{ $periodoTitulo }}</h2>

<table>
    <tr>
        <td class="no-border small">data de criação da pesagem</td>
        <td class="no-border small">
            {{ \Carbon\Carbon::now()->format('d/m/Y') }}
        </td>
    </tr>
    <tr>
        <td class="no-border small">aqui é datahora inicio >>></td>
        <td class="no-border small">
            {{ \Carbon\Carbon::parse($filtros['data_inicio'])->format('d/m/Y H:i:s') }}
        </td>
    </tr>
    <tr>
        <td class="no-border small">aqui é datahora fim >>></td>
        <td class="no-border small">
            {{ \Carbon\Carbon::parse($filtros['data_fim'])->format('d/m/Y H:i:s') }}
        </td>
    </tr>
</table>

<br>

<table>
    <thead>
    <tr>
        <th>DATA</th>
        <th>CLIENTES</th>
        <th>ID</th>
        <th>MATERIAL</th>
        <th>PESO</th>
        <th>IMPUREZA</th>
        <th>LIQUIDO</th>
        <th>VALOR (R$)</th>
        <th>VALOR TOTAL (R$)</th>
        <th>PLACA</th>
        <th>OBSERVAÇÃO</th>
    </tr>
    </thead>
    <tbody>
    @forelse($pesagens as $p)
        <tr>
            <td class="text-center">
                {{ optional($p->data ?? $p->created_at)->format('d/m/Y') }}
            </td>
            <td class="text-left">
                {{ optional($p->cliente)->razao_social ?? $p->cliente_nome ?? '' }}
            </td>
            <td class="text-right">{{ $p->id }}</td>
            <td class="text-left">
                {{ optional($p->material)->nome ?? $p->material ?? '' }}
            </td>
            <td class="text-right">
                {{ number_format($p->peso_bruto ?? $p->peso ?? 0, 2, ',', '.') }}
            </td>
            <td class="text-right">
                {{ number_format($p->impureza ?? 0, 2, ',', '.') }}
            </td>
            <td class="text-right">
                {{ number_format($p->peso_liquido ?? 0, 2, ',', '.') }}
            </td>
            <td class="text-right">
                {{ number_format($p->valor_unitario ?? 0, 2, ',', '.') }}
            </td>
            <td class="text-right">
                {{ number_format($p->valor_total ?? 0, 2, ',', '.') }}
            </td>
            <td class="text-left">
                {{ optional($p->veiculo)->placa ?? $p->placa ?? '' }}
            </td>
            <td class="text-left">
                {{ $p->observacao ?? '' }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="11" class="text-center">Nenhum registro encontrado para o período informado.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
