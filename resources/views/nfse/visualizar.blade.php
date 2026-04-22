<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font-size: 11px; color: #000; background-color: #f4f4f4; padding: 20px; }
        .danfse-container { background: #fff; padding: 20px; border: 1px solid #333; max-width: 900px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header-table, .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .header-table td, .info-table td { border: 1px solid #000; padding: 5px; vertical-align: top; }
        .title { font-weight: bold; font-size: 10px; text-transform: uppercase; display: block; color: #555; }
        .content { font-weight: bold; font-size: 12px; display: block; margin-top: 2px; }
        .servicos-box { border: 1px solid #000; padding: 10px; min-height: 250px; margin-bottom: 10px; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td { border: 1px solid #000; padding: 8px; text-align: center; width: 25%; }
        @media print { .d-print-none { display: none; } body { padding: 0; background: none; } .danfse-container { border: none; box-shadow: none; } }
    </style>
</head>
<body>

<div class="danfse-container">
    {{-- Cabeçalho Superior --}}
    <table class="header-table">
        <tr>
            <td width="20%" class="text-center">
                <img src="/logo.png" style="max-height: 50px;"> {{-- Logo da sua empresa ou prefeitura --}}
            </td>
            <td width="55%" class="text-center">
                <span style="font-size: 14px; font-weight: bold;">DANFSE</span><br>
                <span>Documento Auxiliar da Nota Fiscal de Serviço Eletrônica</span>
            </td>
            <td width="25%">
                <span class="title">Número da Nota</span>
                <span class="content text-primary" style="font-size: 16px;">{{ $xml->infNFSe->nNFSe }}</span>
                <span class="title">Data de Emissão</span>
                <span class="content">{{ date('d/m/Y H:i', strtotime($xml->infNFSe->dhProc)) }}</span>
            </td>
        </tr>
    </table>

    {{-- Dados do Prestador --}}
    <table class="info-table">
        <tr>
            <td>
                <span class="title">Prestador de Serviços</span>
                <span class="content">{{ $xml->infNFSe->emit->xNome }}</span>
                <span>CNPJ: {{ $xml->infNFSe->emit->CNPJ }}</span><br>
                <span>{{ $xml->infNFSe->emit->enderNac->xLgr }}, {{ $xml->infNFSe->emit->enderNac->nro }} - {{ $xml->infNFSe->emit->enderNac->xBairro }}</span>
            </td>
        </tr>
    </table>

    {{-- Dados do Tomador --}}
    <table class="info-table">
        <tr>
            <td>
                <span class="title">Tomador de Serviços (Sua Empresa)</span>
                <span class="content">{{ $xml->infNFSe->DPS->infDPS->toma->xNome }}</span>
                <span>CNPJ/CPF: {{ $xml->infNFSe->DPS->infDPS->toma->CNPJ ?? $xml->infNFSe->DPS->infDPS->toma->CPF }}</span>
            </td>
        </tr>
    </table>

    {{-- Descrição do Serviço --}}
    <div class="servicos-box">
        <span class="title">Descrição dos Serviços</span>
        <div style="font-size: 13px; margin-top: 10px; white-space: pre-wrap;">{{ $xml->infNFSe->DPS->infDPS->serv->cServ->xDescServ }}</div>
        
        <hr style="border-top: 1px dashed #ccc;">
        <span class="title">Informações Adicionais / Tributação</span>
        <div class="mt-2">
            <strong>Tributação Municipal (xTribMun):</strong> {{ $xml->infNFSe->xTribMun ?? 'Não informado' }}<br>
            <strong>Cód. Tributação Nacional:</strong> {{ $xml->infNFSe->DPS->infDPS->serv->cServ->cTribNac }}
        </div>
    </div>

    {{-- Valores --}}
    <table class="footer-table">
        <tr>
            <td>
                <span class="title">Valor Total do Serviço</span>
                <span class="content">R$ {{ number_format((float)$xml->infNFSe->valores->vLiq, 2, ',', '.') }}</span>
            </td>
            <td>
                <span class="title">Base de Cálculo</span>
                <span class="content">R$ {{ number_format((float)$xml->infNFSe->valores->vBC, 2, ',', '.') }}</span>
            </td>
            <td>
                <span class="title">Alíquota ISS</span>
                <span class="content">{{ number_format((float)$xml->infNFSe->valores->pAliqAplic, 2, ',', '.') }}%</span>
            </td>
            <td>
                <span class="title">Valor do ISS</span>
                <span class="content text-danger">R$ {{ number_format((float)$xml->infNFSe->valores->vISSQN, 2, ',', '.') }}</span>
            </td>
        </tr>
    </table>

    {{-- Chave de Acesso --}}
    <div class="mt-2 p-2 border" style="background: #eee; font-family: monospace;">
        <span class="title">Chave de Acesso da NFS-e Nacional</span>
        <span style="font-size: 13px;">{{ (string)$xml->infNFSe['Id'] }}</span>
    </div>

    <div class="mt-4 text-center d-print-none">
        <button onclick="window.print()" class="btn btn-primary font-weight-bold">
            <i class="la la-print"></i> Imprimir NFS-e
        </button>
    </div>
</div>

</body>
</html>