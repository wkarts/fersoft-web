<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Espelho NFS-e #{{ $fatura->numero_nfse ?? $fatura->id }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .document-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #1e293b;
            padding: 12px;
            box-sizing: border-box;
        }
        .header-table, .grid-table, .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .header-table td, .grid-table td, .data-table td, .data-table th {
            border: 1px solid #64748b;
            padding: 5px 8px;
            vertical-align: top;
        }
        .data-table th {
            background-color: #f1f5f9;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #334155;
        }
        .section-header {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 8px;
            margin-top: 6px;
            margin-bottom: 0;
            border: 1px solid #1e293b;
        }
        .doc-title {
            font-size: 13px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0;
        }
        .doc-subtitle {
            font-size: 9px;
            text-align: center;
            color: #64748b;
            margin-top: 2px;
        }
        .label {
            font-size: 8px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .value {
            font-size: 10px;
            font-weight: 600;
            color: #0f172a;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge-status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9px;
        }
        .status-ok { background-color: #dcfce7; color: #166534; }
        .status-pendente { background-color: #fef3c7; color: #92400e; }

        .no-print {
            margin-top: 15px;
            text-align: center;
        }
        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-print:hover { background-color: #1d4ed8; }

        @media print {
            .no-print { display: none !important; }
            .document-container { border: 1px solid #000; }
        }
    </style>
</head>
<body>

<div class="document-container">

    <!-- CABEÇALHO PRINCIPAL -->
    <table class="header-table">
        <tr>
            <td width="20%" class="text-center" style="vertical-align: middle;">
                <strong style="font-size: 14px; color: #1e293b;">NFS-e</strong>
            </td>
            <td width="60%" class="text-center" style="vertical-align: middle;">
                <div class="doc-title">Nota Fiscal de Serviços Eletrônica</div>
                <div class="doc-subtitle">Documento Auxiliar de Prestação de Serviços</div>
            </td>
            <td width="20%" class="text-center" style="vertical-align: middle;">
                @if(!empty($fatura->chave_nfse))
                    <span class="badge-status status-ok">TRANSMITIDA</span>
                @else
                    <span class="badge-status status-pendente">PRÉ-VISUALIZAÇÃO</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- IDENTIFICAÇÃO DA NFS-E E RPS -->
    <div class="section-header">Identificação do Documento</div>
    <table class="grid-table">
        <tr>
            <td width="25%">
                <span class="label">Número da NFS-e</span>
                <span class="value">{{ $fatura->numero_nfse ?? 'Aguardando Emissão' }}</span>
            </td>
            <td width="25%">
                <span class="label">Série RPS</span>
                <span class="value">{{ $fatura->serie_nfse ?? '1' }}</span>
            </td>
            <td width="25%">
                <span class="label">Data de Emissão</span>
                <span class="value">{{ !empty($fatura->data_faturamento) ? date('d/m/Y', strtotime($fatura->data_faturamento)) : date('d/m/Y') }}</span>
            </td>
            <td width="25%">
                <span class="label">Competência</span>
                <span class="value">{{ !empty($fatura->data_faturamento) ? date('m/Y', strtotime($fatura->data_faturamento)) : date('m/Y') }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="label">Chave de Acesso NFS-e</span>
                <span class="value" style="font-family: monospace; font-size: 9px;">{{ $fatura->chave_nfse ?? 'NÃO GERADA' }}</span>
            </td>
            <td colspan="2">
                <span class="label">Protocolo de Autorização</span>
                <span class="value">{{ $fatura->protocolo_nfse ?? 'PENDENTE' }}</span>
            </td>
        </tr>
    </table>

    <!-- PRESTADOR DE SERVIÇOS -->
    <div class="section-header">Prestador de Serviços (Emitente)</div>
    <table class="grid-table">
        <tr>
            <td width="60%">
                <span class="label">Razão Social / Nome</span>
                <span class="value">{{ $configNota->razao_social ?? 'EMPRESA PRESTADORA' }}</span>
            </td>
            <td width="40%">
                <span class="label">CNPJ / CPF</span>
                <span class="value">{{ $configNota->cnpj ?? 'N/D' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Inscrição Municipal</span>
                <span class="value">{{ $configNota->im ?? $configNota->inscricao_municipal ?? 'N/D' }}</span>
            </td>
            <td>
                <span class="label">Optante pelo Simples Nacional</span>
                <span class="value">{{ ($configNota->opcao_simples_nacional ?? 1) == 1 ? 'Sim' : 'Não' }}</span>
            </td>
        </tr>
    </table>

    <!-- TOMADOR DE SERVIÇOS -->
    <div class="section-header">Tomador de Serviços (Cliente)</div>
    <table class="grid-table">
        <tr>
            <td width="60%">
                <span class="label">Nome / Razão Social</span>
                <span class="value">{{ $fatura->cliente->razao_social ?? $fatura->cliente->nome ?? 'N/D' }}</span>
            </td>
            <td width="40%">
                <span class="label">CNPJ / CPF</span>
                <span class="value">{{ $fatura->cliente->cpf_cnpj ?? $fatura->cliente->cnpj ?? $fatura->cliente->cpf ?? 'N/D' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Endereço</span>
                <span class="value">
                    {{ $fatura->cliente->logradouro ?? $fatura->cliente->rua ?? '' }},
                    {{ $fatura->cliente->numero ?? 'S/N' }} -
                    {{ $fatura->cliente->bairro ?? '' }}
                </span>
            </td>
            <td>
                <span class="label">Município / UF</span>
                <span class="value">
                    {{ $fatura->cliente->cidade->nome ?? $fatura->cliente->cidade ?? '' }} /
                    {{ $fatura->cliente->uf ?? $fatura->cliente->cidade->uf ?? '' }}
                </span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Inscrição Estadual</span>
                <span class="value">{{ $fatura->cliente->ie ?? $fatura->cliente->inscricao_estadual ?? 'ISENTO' }}</span>
            </td>
            <td>
                <span class="label">Telefone / E-mail</span>
                <span class="value">{{ $fatura->cliente->telefone ?? $fatura->cliente->email ?? '-' }}</span>
            </td>
        </tr>
    </table>

    <!-- DISCRIMINAÇÃO E CLASSIFICAÇÃO DOS SERVIÇOS -->
    <div class="section-header">Discriminação e Classificação dos Serviços</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="75%">Descrição dos Serviços Prestados</th>
                <th width="25%" class="text-right">Valor Total (R$)</th>
            </tr>
        </thead>
        <tbody>
            <tr style="height: 60px;">
                <td>
                    <div class="value">{{ $fatura->observacao ?? $fatura->servico->descricao_padrao ?? $fatura->servico->nome ?? 'PRESTAÇÃO DE SERVIÇOS TÉCNICOS' }}</div>
                    @if(!empty($fatura->codigo_obra))
                        <div style="margin-top: 6px; font-size: 9px; color: #1e293b;">
                            <strong>Código da Obra / ART:</strong> {{ $fatura->codigo_obra }}
                        </div>
                    @endif
                </td>
                <td class="text-right" style="vertical-align: top;">
                    <strong style="font-size: 11px;">R$ {{ number_format($fatura->valor_total, 2, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="grid-table">
        <tr>
            <td width="33%">
                <span class="label">Cód. Tributação Nacional</span>
                <span class="value">{{ $fatura->servico->codigo_tributacao_nacional ?? $fatura->servico->codigo_servico ?? '07.02.02' }}</span>
            </td>
            <td width="33%">
                <span class="label">Código NBS</span>
                <span class="value">{{ $fatura->servico->codigo_nbs ?? '101010000' }}</span>
            </td>
            <td width="34%">
                <span class="label">Alíquota ISSQN</span>
                <span class="value">{{ number_format($fatura->servico->aliquota_iss ?? 3.00, 2, ',', '.') }}%</span>
            </td>
        </tr>
    </table>

    <!-- QUADRO DE VALORES E TRIBUTOS MUNICIPAIS -->
    <div class="section-header">Totais e Impostos Municipais</div>
    <table class="data-table text-center">
        <thead>
            <tr>
                <th>Valor dos Serviços</th>
                <th>Deduções / Descontos</th>
                <th>Base de Cálculo ISSQN</th>
                <th>Alíquota</th>
                <th>Valor ISSQN (Estimado)</th>
                <th>ISS Retido</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>R$ {{ number_format($fatura->valor_total, 2, ',', '.') }}</strong></td>
                <td>R$ 0,00</td>
                <td>R$ {{ number_format($fatura->valor_total, 2, ',', '.') }}</td>
                <td>{{ number_format($fatura->servico->aliquota_iss ?? 3.00, 2, ',', '.') }}%</td>
                <td>R$ {{ number_format($fatura->valor_total * (($fatura->servico->aliquota_iss ?? 3.00) / 100), 2, ',', '.') }}</td>
                <td>{{ !empty($fatura->iss_retido) && $fatura->iss_retido ? 'SIM' : 'NÃO' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- RETENÇÕES FEDERAIS -->
    <div class="section-header">Retenções Federais</div>
    <table class="data-table text-center">
        <thead>
            <tr>
                <th>PIS</th>
                <th>COFINS</th>
                <th>CSLL</th>
                <th>IRRF</th>
                <th>INSS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>R$ 0,00 (0,00%)</td>
                <td>R$ 0,00 (0,00%)</td>
                <td>R$ 0,00 (0,00%)</td>
                <td>R$ 0,00 (0,00%)</td>
                <td>R$ 0,00 (0,00%)</td>
            </tr>
        </tbody>
    </table>

    <!-- REFORMA TRIBUTÁRIA - IBS / CBS (CAMPOS INFORMATIVOS) -->
    <div class="section-header">Reforma Tributária - IBS / CBS (Projeção Informativa)</div>
    <table class="data-table text-center">
        <thead>
            <tr>
                <th>CST IBS/CBS</th>
                <th>IBS Municipal (0,00%)</th>
                <th>IBS Estadual (0,10%)</th>
                <th>CBS Federal (0,90%)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $fatura->servico->cst_ibscbs ?? '000' }}</td>
                <td>R$ 0,00</td>
                <td>R$ {{ number_format($fatura->valor_total * 0.001, 2, ',', '.') }}</td>
                <td>R$ {{ number_format($fatura->valor_total * 0.009, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- VALOR LÍQUIDO FINAL -->
    <table class="grid-table" style="margin-top: 8px;">
        <tr>
            <td class="text-right" style="background-color: #f8fafc; padding: 10px;">
                <span class="label" style="font-size: 10px;">Valor Líquido da Nota Fiscal:</span>
                <strong style="font-size: 16px; color: #15803d;">R$ {{ number_format($fatura->valor_total, 2, ',', '.') }}</strong>
            </td>
        </tr>
    </table>

    <!-- BOTÃO IMPRIMIR -->
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">
            🖨️ Imprimir Espelho / Salvar PDF
        </button>
    </div>

</div>

</body>
</html>
