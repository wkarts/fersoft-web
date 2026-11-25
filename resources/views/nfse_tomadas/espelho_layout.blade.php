<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; margin: 0; background: #525659; }

        .no-print { text-align: center; padding: 15px; background: #333; color: white; margin-bottom: 20px; }
        .btn-print { padding: 10px 20px; background: #1bc5bd; color: #fff; border: none; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-print:hover { background: #129b94; }

        .a4-container { background: #fff; width: 100%; max-width: 800px; margin: 0 auto; padding: 15px; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.5); }

        .tabela-bordas { width: 100%; border-collapse: collapse; border: 1px solid #000; margin-bottom: 10px; table-layout: fixed; }
        .tabela-bordas td, .tabela-bordas th { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }

        .bg-gray { background-color: #e9e9e9 !important; font-weight: bold; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-uppercase { text-transform: uppercase; }

        .lbl { font-size: 9px; color: #333; display: block; text-transform: uppercase; margin-bottom: 2px; }
        .val { font-size: 12px; font-weight: bold; word-wrap: break-word; }

        .header-title { font-size: 14px; font-weight: bold; margin-bottom: 4px; }
        .header-subtitle { font-size: 11px; }

        .chave-acesso { font-size: 12px; font-weight: bold; letter-spacing: 1px; text-align: center; }

        @media print {
            body { background: #fff; }
            .no-print { display: none; }
            .a4-container { box-shadow: none; max-width: 100%; padding: 0; }
            .bg-gray { background-color: #e9e9e9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn-print">🖨️ IMPRIMIR ESPELHO DA NOTA</button>
</div>

<div class="a4-container">

    <!-- CABEÇALHO -->
    <table class="tabela-bordas">
        <tr>
            <td width="20%" class="text-center" style="vertical-align: middle;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/bf/Coat_of_arms_of_Brazil.svg/200px-Coat_of_arms_of_Brazil.svg.png" alt="República" style="width: 60px;">
            </td>
            <td width="55%" class="text-center" style="vertical-align: middle;">
                <div class="header-title">REPÚBLICA FEDERATIVA DO BRASIL</div>
                <div class="header-title">Nota Fiscal de Serviço Eletrônica - NFS-e</div>
                <div class="header-subtitle">Documento Auxiliar da NFS-e (DANFSE)</div>
            </td>
            <td width="25%">
                <span class="lbl">Número da NFS-e</span>
                <span class="val" style="font-size: 16px;">{{ $nota->numero_nota }}</span>

                <span class="lbl" style="margin-top: 10px;">Data/Hora de Emissão</span>
                <span class="val">{{ date('d/m/Y H:i', strtotime($nota->data_emissao)) }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="3" class="text-center bg-gray">
                CHAVE DE ACESSO
            </td>
        </tr>
        <tr>
            <td colspan="3" class="text-center chave-acesso">
                {{ $nota->chave ?? 'Não informada' }}
            </td>
        </tr>
    </table>

    <!-- PRESTADOR DE SERVIÇOS -->
    <table class="tabela-bordas">
        <tr>
            <td colspan="2" class="bg-gray text-center text-uppercase">Prestador de Serviços</td>
        </tr>
        <tr>
            <td width="70%">
                <span class="lbl">Nome / Razão Social</span>
                <span class="val">{{ $nota->prestador_nome }}</span>
            </td>
            <td width="30%">
                <span class="lbl">CPF / CNPJ</span>
                <span class="val">{{ $nota->prestador_cnpj_cpf }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="lbl">Endereço</span>
                <span class="val">{{ $endereco_prestador ?? 'Não informado no XML' }}</span>
            </td>
        </tr>
    </table>

    <!-- TOMADOR DE SERVIÇOS -->
    <table class="tabela-bordas">
        <tr>
            <td colspan="2" class="bg-gray text-center text-uppercase">Tomador de Serviços</td>
        </tr>
        <tr>
            <td width="70%">
                <span class="lbl">Nome / Razão Social</span>
                <span class="val">{{ $tomador['nome'] ?? 'Não informado' }}</span>
            </td>
            <td width="30%">
                <span class="lbl">CPF / CNPJ</span>
                <span class="val">{{ $tomador['cnpj_cpf'] ?? 'Não informado' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="lbl">Endereço</span>
                <span class="val">{{ $tomador['endereco'] ?? 'Não informado' }}</span>
            </td>
        </tr>
    </table>

    <!-- DESCRIÇÃO DO SERVIÇO -->
    <table class="tabela-bordas">
        <tr>
            <td class="bg-gray text-center text-uppercase">Descrição do Serviço</td>
        </tr>
        <tr>
            <td style="min-height: 150px; height: 150px;">
                <div style="font-size: 12px; line-height: 1.5; white-space: pre-wrap;">{{ $descricao_servico ?? $nota->observacao }}</div>
            </td>
        </tr>
    </table>

    <!-- TRIBUTAÇÃO FEDERAL / RETENÇÕES -->
    <table class="tabela-bordas">
        <tr>
            <td colspan="5" class="bg-gray text-center text-uppercase">Retenções Federais e Municipais</td>
        </tr>
        <tr class="text-center">
            <td>
                <span class="lbl">PIS (R$)</span>
                <span class="val">{{ number_format($retencoes['pis'] ?? 0, 2, ',', '.') }}</span>
            </td>
            <td>
                <span class="lbl">COFINS (R$)</span>
                <span class="val">{{ number_format($retencoes['cofins'] ?? 0, 2, ',', '.') }}</span>
            </td>
            <td>
                <span class="lbl">CSLL (R$)</span>
                <span class="val">{{ number_format($retencoes['csll'] ?? 0, 2, ',', '.') }}</span>
            </td>
            <td>
                <span class="lbl">IRRF (R$)</span>
                <span class="val">{{ number_format($retencoes['irrf'] ?? 0, 2, ',', '.') }}</span>
            </td>
            <td>
                <span class="lbl">ISS Retido (R$)</span>
                <span class="val">{{ number_format($retencoes['iss_retido'] ?? 0, 2, ',', '.') }}</span>
            </td>
        </tr>
    </table>

    <!-- TOTAIS -->
    <table class="tabela-bordas">
        <tr>
            <td colspan="3" class="bg-gray text-center text-uppercase">Valores Declarados</td>
        </tr>
        <tr>
            <td width="33%" class="text-center">
                <span class="lbl">Valor do Serviço</span>
                <span class="val" style="font-size: 16px;">R$ {{ number_format($nota->valor_servico, 2, ',', '.') }}</span>
            </td>
            <td width="33%" class="text-center">
                <span class="lbl">Total de Retenções</span>
                <span class="val" style="font-size: 16px; color: #d9534f;">R$ {{ number_format($retencoes['total_retido'] ?? 0, 2, ',', '.') }}</span>
            </td>
            <td width="34%" class="text-center bg-gray">
                <span class="lbl" style="font-size: 11px;">VALOR LÍQUIDO DA NFS-E</span>
                <span class="val" style="font-size: 18px;">R$ {{ number_format($retencoes['liquido'] ?? $nota->valor_servico, 2, ',', '.') }}</span>
            </td>
        </tr>
    </table>

    <div class="text-center" style="font-size: 10px; color: #666; margin-top: 15px;">
        Documento gerado automaticamente via integração de importação de XML - FerSoft ERP
    </div>

</div>

</body>
</html>
