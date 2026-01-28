<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Inutilização de NF-e #{{ $registro->id }}</title>
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
            margin: 12px 0 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
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
        .block {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<h1>Relatório Individual de Inutilização de NF-e</h1>

<div class="block small">
    <strong>Empresa ID:</strong> {{ $registro->empresa_id }}<br>
    <strong>Registro ID:</strong> {{ $registro->id }}<br>
    <strong>Gerado em:</strong> {{ now()->format('d/m/Y H:i') }}
</div>

<h2>Dados principais da inutilização</h2>
<table class="small">
    <tr>
        <th>Filial</th>
        <td>{{ $registro->filial_id ?? 'Matriz' }}</td>
    </tr>
    <tr>
        <th>Modelo</th>
        <td>{{ $registro->modelo }}</td>
    </tr>
    <tr>
        <th>Série</th>
        <td>{{ $registro->serie }}</td>
    </tr>
    <tr>
        <th>Faixa</th>
        <td>{{ $registro->numero_inicial }} &rarr; {{ $registro->numero_final }}</td>
    </tr>
    <tr>
        <th>Ano (AA)</th>
        <td>{{ $registro->ano }}</td>
    </tr>
    <tr>
        <th>Ambiente</th>
        <td>{{ $registro->ambiente }}</td>
    </tr>
    <tr>
        <th>Status</th>
        <td>{{ $registro->status }}</td>
    </tr>
    <tr>
        <th>Protocolo</th>
        <td>{{ $registro->protocolo }}</td>
    </tr>
    <tr>
        <th>Origem</th>
        <td>{{ $registro->origem }}</td>
    </tr>
    <tr>
        <th>Usuário ID</th>
        <td>{{ $registro->usuario_id }}</td>
    </tr>
    <tr>
        <th>Data/Hora registro</th>
        <td>{{ optional($registro->created_at)->format('d/m/Y H:i') }}</td>
    </tr>
</table>

@if($xmlResumo)
    <h2>Resumo do XML de retorno da SEFAZ</h2>
    <table class="small">
        <tr>
            <th>tpAmb</th>
            <td>{{ $xmlResumo['tpAmb'] ?? '' }}</td>
        </tr>
        <tr>
            <th>verAplic</th>
            <td>{{ $xmlResumo['verAplic'] ?? '' }}</td>
        </tr>
        <tr>
            <th>cStat</th>
            <td>{{ $xmlResumo['cStat'] ?? '' }}</td>
        </tr>
        <tr>
            <th>xMotivo</th>
            <td>{{ $xmlResumo['xMotivo'] ?? '' }}</td>
        </tr>
        <tr>
            <th>cUF</th>
            <td>{{ $xmlResumo['cUF'] ?? '' }}</td>
        </tr>
        <tr>
            <th>CNPJ</th>
            <td>{{ $xmlResumo['CNPJ'] ?? '' }}</td>
        </tr>
        <tr>
            <th>Modelo (mod)</th>
            <td>{{ $xmlResumo['mod'] ?? '' }}</td>
        </tr>
        <tr>
            <th>Série</th>
            <td>{{ $xmlResumo['serie'] ?? '' }}</td>
        </tr>
        <tr>
            <th>Faixa inutilizada</th>
            <td>{{ $xmlResumo['nNFIni'] ?? '' }} &rarr; {{ $xmlResumo['nNFFin'] ?? '' }}</td>
        </tr>
        <tr>
            <th>Data/hora de recebimento</th>
            <td>{{ $xmlResumo['dhRecbto'] ?? '' }}</td>
        </tr>
        <tr>
            <th>Protocolo (nProt)</th>
            <td>{{ $xmlResumo['nProt'] ?? '' }}</td>
        </tr>
    </table>
@endif

@if($registro->xml_retorno)
    <h2>XML bruto (retorno da SEFAZ)</h2>
    <pre class="small" style="white-space: pre-wrap; word-wrap: break-word;">
{{ $registro->xml_retorno }}
    </pre>
@endif

</body>
</html>
