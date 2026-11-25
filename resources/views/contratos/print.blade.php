<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Impressão de Contrato' }}</title>
    <style>
        body { background-color: #525659; margin: 0; padding: 20px; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 12px; color: #2d3748; }
        .sheet { background: #ffffff; width: 210mm; min-height: 297mm; margin: 0 auto; padding: 15mm; box-sizing: border-box; box-shadow: 0 6px 18px rgba(0,0,0,0.25); }
        .header-table { width: 100%; border-bottom: 2px solid #0f766e; padding-bottom: 15px; margin-bottom: 20px; }
        .header-table td { vertical-align: middle; }
        .section-title { background: #f0fdf4; color: #0f766e; padding: 6px 10px; font-weight: bold; border-left: 4px solid #0d9488; margin-top: 20px; margin-bottom: 10px; font-size: 13px; text-transform: uppercase; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 11px; }
        .data-table th { background-color: #1e293b; color: #ffffff; padding: 7px; text-align: left; border: 1px solid #1e293b; }
        .data-table td { border: 1px solid #cbd5e1; padding: 7px; color: #334155; }
        .data-table tbody tr:nth-child(even) { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals-box { float: right; width: 250px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 10px; border-radius: 4px; margin-top: 10px; }
        .signatures { width: 100%; margin-top: 60px; table-layout: fixed; }
        .signatures td { text-align: center; vertical-align: bottom; padding: 10px; }
        .signatures .line { border-top: 1px solid #000; margin: 0 auto 5px auto; width: 80%; }
        @media print {
            body { background: #ffffff; padding: 0; }
            .sheet { width: 100%; box-shadow: none; margin: 0; padding: 0; min-height: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="no-print" style="max-width: 210mm; margin: 0 auto 15px auto; text-align: right;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #0f766e; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
        🖨️ Imprimir Contrato
    </button>
</div>

<div class="sheet">
    <!-- CABEÇALHO -->
    <table class="header-table">
        <tr>
            <td style="width: 150px;">
                @if(!empty($configNota->logo) && file_exists(public_path('logos/' . $configNota->logo)))
                    <img src="{{ asset('logos/' . $configNota->logo) }}" alt="Logo" style="max-width: 140px; max-height: 65px;">
                @else
                    <h2 style="margin:0; color:#0f766e;">{{ $empresa->nome ?? 'FerSoft ERP' }}</h2>
                @endif
            </td>
            <td class="text-center">
                <h2 style="margin: 0; font-size: 16px; color: #1e293b;">TERMO DE CONTRATO / PRESTAÇÃO DE SERVIÇOS</h2>
                <h3 style="margin: 5px 0 0 0; font-size: 14px; color: #475569;">Nº {{ $numeroExibicao }}</h3>
            </td>
            <td style="width: 150px; text-align: right; font-size: 10px; color: #64748b;">
                <strong>Emissão:</strong><br>{{ date('d/m/Y') }}
            </td>
        </tr>
    </table>

    <!-- IDENTIFICAÇÃO DAS PARTES -->
    <div class="section-title">1. Identificação das Partes</div>
    <table class="data-table">
        <tr>
            <td style="width: 50%;">
                <strong>CONTRATADA (PRESTADOR)</strong><br>
                <strong>Razão Social:</strong> {{ $empresa->razao_social ?? $empresa->nome ?? 'N/D' }}<br>
                <strong>CNPJ:</strong> {{ $empresa->cnpj ?? 'N/D' }}<br>
            </td>
            <td style="width: 50%;">
                <strong>CONTRATANTE (CLIENTE)</strong><br>
                <strong>Razão Social:</strong> {{ $dadosContrato->cliente->razao_social ?? $dadosContrato->cliente->nome ?? 'N/D' }}<br>
                <strong>CNPJ/CPF:</strong> {{ $dadosContrato->cliente->cpf_cnpj ?? 'N/D' }}<br>
                <strong>Contato Responsável:</strong> {{ $dadosContrato->contato_nome ?: 'Não informado' }} ({{ $dadosContrato->contato_telefone ?: '-' }})
            </td>
        </tr>
    </table>

    <!-- VIGÊNCIA E CONDIÇÕES -->
    <div class="section-title">2. Prazos e Condições Globais</div>
    <table class="data-table">
        <tr>
            <td style="width: 33%;"><strong>Data de Início:</strong> {{ $dadosContrato->data_inicio ? \Carbon\Carbon::parse($dadosContrato->data_inicio)->format('d/m/Y') : 'N/D' }}</td>
            <td style="width: 33%;"><strong>Data de Término:</strong> {{ $dadosContrato->data_fim ? \Carbon\Carbon::parse($dadosContrato->data_fim)->format('d/m/Y') : 'Indeterminado' }}</td>
            <td style="width: 34%;"><strong>Retenção Técnica:</strong> {{ number_format($dadosContrato->percentual_retencao ?? 0, 2, ',', '.') }}%</td>
        </tr>
        @if($dadosContrato->observacoes)
            <tr>
                <td colspan="3"><strong>Observações e Condições Adicionais:</strong><br>{{ $dadosContrato->observacoes }}</td>
            </tr>
        @endif
    </table>

    <!-- ITENS DO CONTRATO -->
    <div class="section-title">3. Tabela Tarifária de Serviços e Locações</div>

    @php
        // Lógica para separar o que é faturamento do que é abatimento
        $itensFaturar = [];
        $itensAbater = [];

        foreach($dadosContrato->itens as $it) {
            // Se for Resíduo e NÃO tiver serviço fiscal, é sucata para abatimento de crédito
            if ($it->tipo_item === 'Residuo' && empty($it->servico_id)) {
                $itensAbater[] = $it;
            } else {
                $itensFaturar[$it->tipo_item][] = $it;
            }
        }
    @endphp

        <!-- 3.1 ITENS PARA FATURAMENTO (COBRANÇA) -->
    @foreach($itensFaturar as $tipo => $itens)
        <div style="font-weight: bold; background: #e2e8f0; padding: 4px 8px; margin-top: 10px; font-size: 11px;">
            {{ match($tipo) {
                'Residuo' => 'TARIFA DE COLETA E TRANSPORTE DE RESÍDUOS',
                'Locacao' => 'MENSALIDADE DE LOCAÇÃO DE EQUIPAMENTOS',
                'Frete'   => 'VALOR POR FRETE / VIAGEM',
                default   => 'SERVIÇOS GERAIS E EXTRAS'
            } }}
        </div>
        <table class="data-table">
            <thead>
            <tr>
                <th>Item / Descrição</th>
                <th class="text-center" style="width: 15%;">Unidade</th>
                <th class="text-right" style="width: 15%;">Qtd. Prevista</th>
                <th class="text-right" style="width: 18%;">Valor Unitário</th>
                <th class="text-right" style="width: 18%;">Total Estimado</th>
            </tr>
            </thead>
            <tbody>
            @foreach($itens as $it)
                <tr>
                    <td>{{ $it->produto->nome ?? $it->servico->nome ?? 'Serviço/Produto' }}</td>
                    <td class="text-center">{{ $it->unidade }}</td>
                    <td class="text-right">{{ number_format($it->quantidade_prevista, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($it->valor_unitario, 2, ',', '.') }}</td>
                    <td class="text-right font-weight-bold">R$ {{ number_format($it->valor_total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach

    <!-- 3.2 ITENS DE ABATIMENTO (COMPRA DE SUCATA) -->
    @if(count($itensAbater) > 0)
        <div style="font-weight: bold; background: #fef3c7; color: #b45309; border-left: 3px solid #d97706; padding: 4px 8px; margin-top: 15px; font-size: 11px;">
            TARIFA DE COMPRA / ABATIMENTO DE CRÉDITO (SUCATAS E RECICLÁVEIS)
        </div>
        <table class="data-table" style="border: 1px solid #fde68a;">
            <thead>
            <tr>
                <th style="background-color: #d97706; border-color: #b45309; color: #fff;">Material Reciclável</th>
                <th class="text-center" style="width: 15%; background-color: #d97706; border-color: #b45309; color: #fff;">Unidade</th>
                <th class="text-right" style="width: 15%; background-color: #d97706; border-color: #b45309; color: #fff;">Qtd. Prevista</th>
                <th class="text-right" style="width: 18%; background-color: #d97706; border-color: #b45309; color: #fff;">Valor (Crédito Unit.)</th>
                <th class="text-right" style="width: 18%; background-color: #d97706; border-color: #b45309; color: #fff;">Crédito Estimado</th>
            </tr>
            </thead>
            <tbody>
            @foreach($itensAbater as $it)
                <tr>
                    <td style="border-color: #fde68a;">{{ $it->produto->nome ?? 'Material' }}</td>
                    <td class="text-center" style="border-color: #fde68a;">{{ $it->unidade }}</td>
                    <td class="text-right" style="border-color: #fde68a;">{{ number_format($it->quantidade_prevista, 2, ',', '.') }}</td>
                    <!-- Removido o sinal negativo, mantida apenas a cor vermelha (text-danger) -->
                    <td class="text-right text-danger" style="border-color: #fde68a;">R$ {{ number_format($it->valor_unitario, 2, ',', '.') }}</td>
                    <td class="text-right font-weight-bold text-danger" style="border-color: #fde68a;">R$ {{ number_format($it->valor_total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <div class="totals-box text-right">
        <strong>VALOR GLOBAL ESTIMADO:</strong><br>
        <span style="font-size: 16px; color: #0f766e; font-weight: bold;">R$ {{ number_format($dadosContrato->valor_contrato, 2, ',', '.') }}</span>
        @if(count($itensAbater) > 0)
            <div style="font-size: 9px; color: #b45309; margin-top: 4px;">* Este valor é sujeito a deduções financeiras referentes aos créditos gerados pela sucata acima.</div>
        @endif
    </div>
    <div style="clear: both;"></div>

    <!-- EQUIPE ALOCADA -->
    @if(isset($equipeAlocada) && count($equipeAlocada) > 0)
        <div class="section-title">4. Equipe Técnica / Funcionários Alocados</div>
        <table class="data-table">
            <thead>
            <tr>
                <th>Nome do Funcionário</th>
                <th style="width: 40%;">Cargo / Função Operacional</th>
            </tr>
            </thead>
            <tbody>
            @foreach($equipeAlocada as $func)
                <tr>
                    <td>{{ $func->nome ?? 'N/D' }}</td>
                    <td>{{ $func->cargo_nome ?? 'Operacional' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <!-- ASSINATURAS -->
    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                <strong>{{ $empresa->razao_social ?? $empresa->nome ?? 'Contratada' }}</strong><br>
                CNPJ: {{ $empresa->cnpj ?? 'N/D' }}
            </td>
            <td>
                <div class="line"></div>
                <strong>{{ $dadosContrato->cliente->razao_social ?? $dadosContrato->cliente->nome ?? 'Contratante' }}</strong><br>
                CNPJ/CPF: {{ $dadosContrato->cliente->cpf_cnpj ?? 'N/D' }}
            </td>
        </tr>
    </table>
</div>
</body>
</html>
