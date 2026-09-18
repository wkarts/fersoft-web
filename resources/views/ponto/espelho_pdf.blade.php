<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Espelho de Ponto Eletrônico</title>
    <style>
        @page { margin: 15px 20px; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9px; color: #222; margin: 0; }
        .cabecalho { width: 100%; border: 1px solid #333; padding: 6px; margin-bottom: 8px; }
        .cabecalho td { vertical-align: top; }
        .titulo-doc { font-size: 13px; font-weight: bold; text-align: center; text-transform: uppercase; margin-bottom: 4px; }
        .tabela-ponto { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .tabela-ponto th, .tabela-ponto td { border: 1px solid #777; padding: 3px 2px; text-align: center; }
        .tabela-ponto th { background-color: #eee; font-weight: bold; font-size: 8px; }
        .linha-fds { background-color: #fbfbfb; color: #555; }
        .totais-tabela { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .totais-tabela td { border: 1px solid #333; padding: 5px; font-size: 10px; }
        .assinaturas { width: 100%; margin-top: 35px; }
        .assinaturas td { width: 50%; text-align: center; }
        .linha-assinatura { border-top: 1px solid #333; width: 75%; margin: 0 auto 4px auto; }
        .rodape-legal { font-size: 7.5px; text-align: center; color: #666; margin-top: 12px; }
    </style>
</head>
<body>

<div class="titulo-doc">ESPELHO DE PONTO ELETRÔNICO (REP-P)</div>

<table class="cabecalho">
    <tr>
        <td style="width: 55%;">
            <b>EMPREGADOR:</b> {{ strtoupper($empresa->razao_social ?? 'EMPRESA') }}<br>
            <b>CNPJ:</b> {{ $empresa->cnpj ?? '00.000.000/0001-00' }}<br>
            <b>ENDEREÇO:</b> {{ $empresa->logradouro ?? '' }}, {{ $empresa->numero ?? '' }} - {{ $empresa->municipio ?? '' }}/{{ $empresa->UF ?? '' }}
        </td>
        <td style="width: 45%;">
            <b>EMPREGADO:</b> {{ strtoupper($funcionario->nome) }}<br>
            <b>CPF:</b> {{ $funcionario->cpf ?? '---' }} &nbsp;&nbsp; <b>PIS:</b> {{ $funcionario->pis ?? '---' }}<br>
            <b>CARGO/FUNÇÃO:</b> {{ $funcionario->funcao->nome ?? 'NÃO INFORMADO' }}<br>
            <b>PERÍODO:</b> {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}
        </td>
    </tr>
</table>

<table class="tabela-ponto">
    <thead>
    <tr>
        <th rowspan="2" style="width: 55px;">DATA</th>
        <th rowspan="2" style="width: 28px;">DIA</th>
        <th colspan="2">1º TURNO</th>
        <th colspan="2">2º TURNO</th>
        <th colspan="2">HORA EXTRA</th>
        <th rowspan="2" style="width: 45px;">TRAB.</th>
        <th rowspan="2" style="width: 40px;">EXTRA</th>
        <th rowspan="2" style="width: 40px;">ATRASO</th>
        <th rowspan="2" style="width: 55px;">SITUAÇÃO</th>
    </tr>
    <tr>
        <th style="width: 38px;">ENT 1</th>
        <th style="width: 38px;">SAI 1</th>
        <th style="width: 38px;">ENT 2</th>
        <th style="width: 38px;">SAI 2</th>
        <th style="width: 38px;">ENT 3</th>
        <th style="width: 38px;">SAI 3</th>
    </tr>
    </thead>
    <tbody>
    @foreach($gradeMensal as $dia)
        <tr class="{{ $dia['eh_fds'] ? 'linha-fds' : '' }}">
            <td>{{ $dia['data'] }}</td>
            <td>{{ $dia['dia_semana'] }}</td>
            <td>{{ $dia['etapas']['ent1'] ?? '---' }}</td>
            <td>{{ $dia['etapas']['sai1'] ?? '---' }}</td>
            <td>{{ $dia['etapas']['ent2'] ?? '---' }}</td>
            <td>{{ $dia['etapas']['sai2'] ?? '---' }}</td>
            <td>{{ $dia['etapas']['ent3'] ?? '---' }}</td>
            <td>{{ $dia['etapas']['sai3'] ?? '---' }}</td>
            <td><b>{{ $dia['trabalhadas'] != '00:00' ? $dia['trabalhadas'] : '' }}</b></td>
            <td style="color: #006600;">{{ $dia['extras'] != '00:00' ? '+' . $dia['extras'] : '' }}</td>
            <td style="color: #990000;">{{ $dia['atrasos'] != '00:00' ? '-' . $dia['atrasos'] : '' }}</td>
            <td style="font-size: 8px;">{{ strtoupper($dia['status']) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totais-tabela">
    <tr style="background-color: #eee;">
        <td style="text-align: center;"><b>TOTAL TRABALHADO:</b> {{ $totais['trabalhadas'] }}</td>
        <td style="text-align: center; color: #006600;"><b>TOTAL EXTRAS:</b> +{{ $totais['extras'] }}</td>
        <td style="text-align: center; color: #990000;"><b>TOTAL ATRASOS:</b> -{{ $totais['atrasos'] }}</td>
        <td style="text-align: center;"><b>SALDO DO PERÍODO:</b> {{ $totais['saldo'] }}</td>
    </tr>
</table>

<table class="assinaturas">
    <tr>
        <td>
            <div class="linha-assinatura"></div>
            <b>{{ strtoupper($empresa->razao_social ?? 'EMPRESA') }}</b><br>
            Empregador
        </td>
        <td>
            <div class="linha-assinatura"></div>
            <b>{{ strtoupper($funcionario->nome) }}</b><br>
            Assinatura do Empregado
        </td>
    </tr>
</table>

<div class="rodape-legal">
    Documento gerado em {{ date('d/m/Y \à\s H:i') }} pelo FERSOFT ERP nos termos da Portaria nº 671/2021 do MTE.
</div>

</body>
</html>
