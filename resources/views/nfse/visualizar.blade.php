<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 18px; background: #f4f4f4; color: #111; font: 12px Arial, sans-serif; }
        .danfse { max-width: 900px; margin: auto; padding: 16px; background: #fff; border: 1px solid #222; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        td, th { padding: 6px; border: 1px solid #222; vertical-align: top; }
        th { background: #f0f0f0; text-align: center; font-size: 10px; text-transform: uppercase; }
        .titulo { color: #555; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .valor { display: block; margin-top: 2px; font-size: 12px; font-weight: bold; }
        .cabecalho { text-align: center; font-size: 15px; font-weight: bold; }
        .servico { min-height: 160px; padding: 10px; border: 1px solid #222; white-space: pre-wrap; }
        .acoes { max-width: 900px; margin: 0 auto 10px; text-align: right; }
        .acoes button { padding: 8px 14px; cursor: pointer; }
        .cancelada { margin-bottom: 8px; padding: 8px; border: 2px solid #b00020; color: #b00020; text-align: center; font-weight: bold; }
        @media print { body { padding: 0; background: #fff; } .danfse { border: none; } .acoes { display: none; } }
    </style>
</head>
<body>
@php
    $d = $dados ?? [];
    $ret = $d['retencoes'] ?? [];
@endphp
<div class="acoes"><button type="button" onclick="window.print()">Imprimir</button></div>
<div class="danfse">
    @if(strtoupper((string)($nota->situacao ?? '')) === 'CANCELADA')
        <div class="cancelada">NFS-e CANCELADA</div>
    @endif
    <table>
        <tr>
            <td class="cabecalho" style="width:70%">DANFSE<br><small>Documento Auxiliar da Nota Fiscal de Serviço Eletrônica</small></td>
            <td>
                <span class="titulo">Número</span><span class="valor">{{ $d['numero'] ?? $nota->numero_nota ?? '' }}</span>
                <span class="titulo">Emissão</span><span class="valor">{{ !empty($d['data_emissao']) ? date('d/m/Y H:i', strtotime($d['data_emissao'])) : '-' }}</span>
            </td>
        </tr>
    </table>
    <table>
        <tr><td>
            <span class="titulo">Prestador de serviços</span>
            <span class="valor">{{ $d['prestador_nome'] ?? $nota->prestador_nome ?? '-' }}</span>
            <div>Documento: {{ $d['prestador_documento'] ?? $nota->prestador_cnpj_cpf ?? '-' }}</div>
            <div>{{ $d['prestador_endereco'] ?? '' }}</div>
        </td></tr>
    </table>
    <table>
        <tr><td>
            <span class="titulo">Tomador de serviços</span>
            <span class="valor">{{ $d['tomador_nome'] ?? 'Não informado' }}</span>
            <div>Documento: {{ $d['tomador_documento'] ?? '-' }}</div>
        </td></tr>
    </table>
    <div class="servico"><span class="titulo">Descrição dos serviços</span><br><br>{{ $d['descricao_servico'] ?? $descricao_servico ?? 'Não informada' }}</div>
    <table style="margin-top:8px">
        <tr>
            <th>PIS</th><th>COFINS</th><th>IRRF</th><th>CSLL</th><th>INSS</th><th>ISS retido</th>
        </tr>
        <tr style="text-align:center">
            <td>R$ {{ number_format((float)($ret['valor_pis'] ?? 0), 2, ',', '.') }}</td>
            <td>R$ {{ number_format((float)($ret['valor_cofins'] ?? 0), 2, ',', '.') }}</td>
            <td>R$ {{ number_format((float)($ret['valor_ir'] ?? 0), 2, ',', '.') }}</td>
            <td>R$ {{ number_format((float)($ret['valor_csll'] ?? 0), 2, ',', '.') }}</td>
            <td>R$ {{ number_format((float)($ret['valor_inss'] ?? 0), 2, ',', '.') }}</td>
            <td>R$ {{ number_format((float)($ret['valor_iss'] ?? 0), 2, ',', '.') }}</td>
        </tr>
    </table>
    <table>
        <tr style="text-align:center">
            <td><span class="titulo">Valor bruto</span><span class="valor">R$ {{ number_format((float)($d['valor_bruto'] ?? $nota->valor_servico ?? 0), 2, ',', '.') }}</span></td>
            <td><span class="titulo">ISS apurado</span><span class="valor">R$ {{ number_format((float)($d['valor_iss_apurado'] ?? 0), 2, ',', '.') }}</span></td>
            <td><span class="titulo">Total retenções</span><span class="valor">R$ {{ number_format((float)array_sum($ret), 2, ',', '.') }}</span></td>
            <td><span class="titulo">Valor líquido</span><span class="valor">R$ {{ number_format((float)($d['valor_liquido'] ?? $nota->valor_liquido ?? 0), 2, ',', '.') }}</span></td>
        </tr>
    </table>
    @if(!empty($d['tributacao_municipal']) || !empty($d['codigo_tributacao']))
        <table><tr><td>
            <span class="titulo">Tributação municipal</span> {{ $d['tributacao_municipal'] ?? '-' }}<br>
            <span class="titulo">Código de tributação</span> {{ $d['codigo_tributacao'] ?? '-' }}
        </td></tr></table>
    @endif
</div>
</body>
</html>
