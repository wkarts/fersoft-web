<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Boletim de Medição' }}</title>
    <style>
        body {
            background-color: #555;
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .sheet {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm;
            box-sizing: border-box;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
        }

        /* CABEÇALHO PRINCIPAL */
        .header-top { width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; padding: 2px; }
        .logo-img { max-width: 130px; max-height: 60px; object-fit: contain; }

        /* BLOCO INFORMATIVO DIREITO */
        .info-header { font-size: 10px; border-left: 1px solid #000; padding-left: 10px; }
        .info-row { margin-bottom: 3px; }
        .info-label { color: #333; width: 110px; display: inline-block; font-weight: bold;}
        .info-value { font-weight: normal; }

        /* ENDEREÇO EMITENTE */
        .emitente-address { text-align: center; font-size: 10px; margin-bottom: 15px; font-weight: bold; }

        /* TÍTULOS */
        .boletim-title { text-align: center; font-size: 15px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .boletim-subtitle { text-align: center; font-size: 11px; margin-bottom: 20px; }

        /* SEÇÕES E BLOCOS */
        .section-title {
            font-weight: bold;
            font-size: 11px;
            margin-top: 20px;
            margin-bottom: 8px;
            text-transform: uppercase;
            background-color: #333;
            color: #fff;
            padding: 4px 8px;
        }

        .partes-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10px;}
        .partes-table td { border-bottom: 1px dashed #999; padding: 6px 0; vertical-align: top;}
        .partes-table .label { width: 120px; font-weight: bold; color: #333; }

        /* TABELAS DE DADOS (COM BORDAS SÓLIDAS) */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 10px;
            border: 1px solid #000;
        }
        table.data-table th {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            font-weight: bold;
            background-color: #eaeaea;
            text-transform: uppercase;
        }
        table.data-table td {
            border: 1px solid #000;
            padding: 6px;
        }
        table.data-table tbody tr:nth-child(even) { background-color: #fcfcfc; }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-bold { font-weight: bold; }

        /* APURAÇÃO GERAL */
        .apuracao-table { width: 100%; font-size: 11px; margin-top: 10px; border-collapse: collapse; }
        .apuracao-table td { padding: 6px 8px; border: 1px solid #000; }
        .apuracao-table tr:nth-child(odd) { background-color: #f9f9f9; }
        .apuracao-saldo { font-size: 12px; font-weight: bold; background-color: #eaeaea !important; }

        /* RODAPÉ (Assinaturas) */
        .signatures { width: 100%; margin-top: 50px; font-size: 10px; page-break-inside: avoid; }
        .sig-box { width: 48%; display: inline-block; vertical-align: top; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin: 30px auto 5px auto; width: 80%; padding-top: 5px; }
        .footer-legal { text-align: center; font-size: 8px; color: #777; border-top: 1px solid #ccc; margin-top: 40px; padding-top: 10px; }

        @media print {
            body { background: #ffffff; padding: 0; }
            .sheet { width: 100%; box-shadow: none; margin: 0; padding: 0; min-height: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="no-print" style="max-width: 210mm; margin: 0 auto 15px auto; text-align: right;">
    <button onclick="window.print()" style="padding: 9px 18px; background: #3182ce; color: #fff; border: 1px solid #2b6cb0; cursor: pointer; border-radius: 4px; font-weight: bold;">
        🖨️ Imprimir PDF
    </button>
</div>

<div class="sheet">
    <!-- 1. CABEÇALHO -->
    <div class="header-top">
        <table class="header-table">
            <tr>
                <td style="width: 25%;">
                    @if(!empty($configNota->logo) && file_exists(public_path('logos/' . $configNota->logo)))
                        <img src="{{ asset('logos/' . $configNota->logo) }}" alt="Logo" class="logo-img">
                    @else
                        <div style="font-size: 14px; font-weight: bold;">{{ mb_strtoupper($empresa->nome ?? $empresa->razao_social) }}</div>
                    @endif
                </td>
                <td style="width: 75%; padding-left: 20px;">
                    <div class="info-header">
                        @php
                            $dInicio = $fatura->contrato->data_inicio ?? null;
                            $dFim = $fatura->contrato->data_fim ?? null;
                            $periodoTxt = ($dInicio ? date('d/m/Y', strtotime($dInicio)) : date('01/m/Y', strtotime($fatura->data_faturamento))) . ' a ' . ($dFim ? date('d/m/Y', strtotime($dFim)) : date('t/m/Y', strtotime($fatura->data_faturamento)));
                        @endphp

                        <div class="info-row"><span class="info-label">PERÍODO</span> <span class="info-value">| {{ $periodoTxt }}</span></div>
                        <div class="info-row"><span class="info-label">FILIAL</span> <span class="info-value">| {{ $fatura->filial->descricao ?? 'Matriz ' . ($empresa->nome ?? 'Empresa') }}</span></div>
                        <div class="info-row"><span class="info-label">MEDIÇÃO (SISTEMA)</span> <span class="info-value">| Nº {{ $fatura->id }}</span></div>
                        <div class="info-row"><span class="info-label">CONTRATO (SISTEMA)</span> <span class="info-value">| Nº {{ $fatura->contrato_eng_id ?? '-' }}</span></div>
                        <div class="info-row"><span class="info-label">REFERÊNCIA COMERCIAL</span> <span class="info-value">| Proposta Nº {{ $fatura->contrato->numero_contrato ?? $fatura->contrato_eng_id ?? 'Avulso' }}</span></div>
                        <div class="info-row"><span class="info-label">MOEDA PADRÃO</span> <span class="info-value">| BRL (R$)</span></div>
                        <div class="info-row" style="margin-top: 6px;"><span class="info-label">OBJETO</span> <span class="info-value">| Coleta, transporte e destinação final de resíduos e locações.</span></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="emitente-address">
        {{ mb_strtoupper($empresa->nome ?? $empresa->razao_social) }} - CNPJ {{ $empresa->cnpj ?? $empresa->cpf_cnpj }}<br>
        {{ $empresa->rua ?? '' }}, {{ $empresa->numero ?? 'S/N' }} - {{ $empresa->bairro ?? '' }} - {{ $empresa->cidade->nome ?? '' }}/{{ $empresa->cidade->uf ?? '' }} - CEP {{ $empresa->cep ?? '' }}
    </div>

    <!-- 3. TÍTULO -->
    <div class="boletim-title">BOLETIM DE MEDIÇÃO</div>
    <div class="boletim-subtitle">
        <strong>Nº BM-{{ $empresa->id ?? '1' }}-{{ date('Y', strtotime($fatura->created_at)) }}/{{ str_pad($fatura->id, 3, '0', STR_PAD_LEFT) }}</strong> &nbsp;&nbsp;|&nbsp;&nbsp;
        Emissão: {{ date('d/m/Y', strtotime($fatura->created_at)) }} &nbsp;&nbsp;|&nbsp;&nbsp; Página 1 de 1
    </div>

    <!-- 4. PARTES -->
    <div class="section-title">1. IDENTIFICAÇÃO DAS PARTES</div>
    <table class="partes-table">
        <tr>
            <td class="label">CONTRATADA:</td>
            <td>
                <strong>{{ mb_strtoupper($empresa->nome ?? $empresa->razao_social) }}</strong> - CNPJ {{ $empresa->cnpj ?? $empresa->cpf_cnpj }}<br>
                Endereço: {{ $empresa->rua ?? '' }}, {{ $empresa->numero ?? 'S/N' }} - {{ $empresa->cidade->nome ?? '' }}/{{ $empresa->cidade->uf ?? '' }}
            </td>
        </tr>
        <tr>
            <td class="label">CONTRATANTE:</td>
            <td>
                <strong>{{ mb_strtoupper($fatura->cliente->razao_social ?? $fatura->cliente->nome ?? 'CONSUMIDOR') }}</strong> - CNPJ {{ $fatura->cliente->cpf_cnpj ?? '' }}<br>
                Endereço: {{ $fatura->cliente->rua ?? '' }}, {{ $fatura->cliente->numero ?? 'S/N' }} - {{ $fatura->cliente->cidade->nome ?? '' }}/{{ $fatura->cliente->cidade->uf ?? '' }}<br>
                Cód. Fornecedor: {{ $fatura->cliente->id }}
            </td>
        </tr>
    </table>

    @php
        $itensLista = $fatura->itens ?? [];
        $indice = 2;
        $totalServicos = 0;
        $totalLocacao = 0;
        $totalComprasBrisa = (float) ($fatura->valor_abatimento_compra ?? 0);
    @endphp

        <!-- 5.1 SERVIÇOS (Coleta, Frete, Resíduo) -->
    @php
        // 🚀 Filtra Serviço, Frete, e apenas Resíduos COM serviço fiscal
        $itensServicos = collect($itensLista)->filter(function($i) {
            if (in_array($i->tipo_item, ['Servico', 'Frete'])) return true;
            if ($i->tipo_item == 'Residuo' && !empty($i->servico_id)) return true;
            return false;
        });
    @endphp

    @if($itensServicos->count() > 0)
        <div class="section-title">{{ $indice++ }}. SERVIÇOS DE DESTINAÇÃO E FRETES - A RECEBER</div>
        <table class="data-table">
            <thead>
            <tr>
                <th style="width:14%;">DATA DA COLETA</th>
                <th style="text-align: left;">DESCRIÇÃO DO SERVIÇO / RESÍDUO</th>
                <th style="width:12%;">QTD</th>
                <th style="width:14%;">VALOR UNIT.</th>
                <th style="width:15%;">TOTAL (R$)</th>
            </tr>
            </thead>
            <tbody>
            @foreach($itensServicos as $item)
                @php
                    $subTotal = (float) ($item->valor_total ?? 0);
                    $totalServicos += $subTotal;

                    $descricaoBruta = trim($item->descricao ?? $item->servico->nome ?? $item->produto->nome ?? 'Serviço prestado');
                    $dataLinha = '-';
                    $descLinha = $descricaoBruta;

                    if (preg_match('/^(\d{2}\/\d{2}\/\d{4})\s*-\s*(.*)/', $descricaoBruta, $matches)) {
                        $dataLinha = $matches[1];
                        $descLinha = $matches[2];
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $dataLinha }}</td>
                    <td>{{ mb_strtoupper($descLinha) }}</td>
                    <td class="text-center">{{ number_format($item->quantidade ?? 1, 3, ',', '.') }} {{ $item->unidade ?? 'UN' }}</td>
                    <td class="text-right">{{ number_format($item->valor_unitario ?? 0, 2, ',', '.') }} /{{ strtolower($item->unidade ?? 'un') }}</td>
                    <td class="text-right font-bold">{{ number_format($subTotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" class="font-bold text-right" style="background-color: #f9f9f9;">SUBTOTAL - SERVIÇOS:</td>
                <td class="text-right font-bold" style="background-color: #f9f9f9;">{{ number_format($totalServicos, 2, ',', '.') }}</td>
            </tr>
            </tbody>
        </table>
    @endif

    <!-- 5.2 LOCAÇÃO DE EQUIPAMENTOS -->
    @php
        $itensLocacao = collect($itensLista)->filter(function($i) { return $i->tipo_item == 'Locacao'; });
    @endphp

    @if($itensLocacao->count() > 0)
        <div class="section-title">{{ $indice++ }}. LOCAÇÃO DE EQUIPAMENTOS - A RECEBER</div>
        <table class="data-table">
            <thead>
            <tr>
                <th style="width:14%;">PERÍODO</th>
                <th style="text-align: left;">EQUIPAMENTO / CAÇAMBA</th>
                <th style="width:12%;">QTD</th>
                <th style="width:14%;">VALOR MENSAL</th>
                <th style="width:15%;">TOTAL (R$)</th>
            </tr>
            </thead>
            <tbody>
            @foreach($itensLocacao as $item)
                @php
                    $subTotal = (float) ($item->valor_total ?? 0);
                    $totalLocacao += $subTotal;

                    $descricaoBruta = !empty($item->descricao) ? $item->descricao : ($item->produto->nome ?? 'Locação de Equipamento');
                    $dataLinha = date('m/Y', strtotime($fatura->data_faturamento ?? $fatura->created_at));
                @endphp
                <tr>
                    <td class="text-center">Comp. {{ $dataLinha }}</td>
                    <td>{{ mb_strtoupper($descricaoBruta) }}</td>
                    <td class="text-center">{{ number_format($item->quantidade ?? 1, 1, ',', '.') }} {{ $item->unidade ?? 'UN' }}</td>
                    <td class="text-right">{{ number_format($item->valor_unitario ?? 0, 2, ',', '.') }} /{{ strtolower($item->unidade ?? 'un') }}</td>
                    <td class="text-right font-bold">{{ number_format($subTotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" class="font-bold text-right" style="background-color: #f9f9f9;">SUBTOTAL - LOCAÇÃO:</td>
                <td class="text-right font-bold" style="background-color: #f9f9f9;">{{ number_format($totalLocacao, 2, ',', '.') }}</td>
            </tr>
            </tbody>
        </table>
    @endif

    <!-- 5.3 COMPRAS / ABATIMENTOS DETALHADOS -->
    @php
        // 🚀 Pega apenas os Resíduos SEM serviço fiscal (Sucatas) e soma os valores na hora!
        $itensAbatimento = collect($itensLista)->filter(function($i) {
            return $i->tipo_item == 'Residuo' && empty($i->servico_id);
        });
        $abatimentoReal = $itensAbatimento->sum('valor_total');
    @endphp

    @if($abatimentoReal > 0 || $itensAbatimento->count() > 0)
        <div class="section-title">{{ $indice++ }}. AQUISIÇÃO DE RECICLÁVEIS E SUCATA - A PAGAR AO CLIENTE</div>
        <table class="data-table">
            <thead>
            <tr>
                <th style="width:14%;">DATA</th>
                <th style="text-align: left;">DESCRIÇÃO DA COMPRA / ABATIMENTO COMERCIAL</th>
                <th style="width:12%;">QTD</th>
                <th style="width:14%;">VALOR UNIT.</th>
                <th style="width:15%;">TOTAL (R$)</th>
            </tr>
            </thead>
            <tbody>
            @foreach($itensAbatimento as $item)
                @php
                    $descricaoBruta = trim($item->descricao ?? $item->produto->nome ?? 'Compra de Sucata');
                    $dataLinha = '-';
                    $descLinha = $descricaoBruta;
                    if (preg_match('/^(\d{2}\/\d{2}\/\d{4})\s*-\s*(.*)/', $descricaoBruta, $matches)) {
                        $dataLinha = $matches[1];
                        $descLinha = $matches[2];
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $dataLinha }}</td>
                    <td>{{ mb_strtoupper($descLinha) }}</td>
                    <td class="text-center">{{ number_format($item->quantidade ?? 1, 3, ',', '.') }} {{ $item->unidade ?? 'KG' }}</td>
                    <!-- Sem sinal negativo, apenas cor vermelha -->
                    <td class="text-right font-bold" style="color: #d63031;">{{ number_format($item->valor_unitario ?? 0, 2, ',', '.') }} /{{ strtolower($item->unidade ?? 'kg') }}</td>
                    <td class="text-right font-bold" style="color: #d63031;">{{ number_format($item->valor_total ?? 0, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" class="font-bold text-right" style="background-color: #f9f9f9;">SUBTOTAL - ABATIMENTOS:</td>
                <td class="text-right font-bold" style="color: #d63031; background-color: #f9f9f9;">{{ number_format($abatimentoReal, 2, ',', '.') }}</td>
            </tr>
            </tbody>
        </table>
    @endif

    <!-- 6. APURAÇÃO - ENCONTRO DE CONTAS -->
    <div class="section-title">{{ $indice++ }}. RESUMO DA APURAÇÃO (ENCONTRO DE CONTAS)</div>
    <table class="apuracao-table">
        @if($totalServicos > 0)
            <tr>
                <td>(+) Serviços de coleta, frete e destinação final</td>
                <td class="text-right" style="width: 25%;">R$ {{ number_format($totalServicos, 2, ',', '.') }}</td>
            </tr>
        @endif
        @if($totalLocacao > 0)
            <tr>
                <td>(+) Locação de equipamentos e caçambas</td>
                <td class="text-right">R$ {{ number_format($totalLocacao, 2, ',', '.') }}</td>
            </tr>
        @endif
        <tr>
            <td class="font-bold">(=) Total Bruto da Fatura</td>
            <td class="text-right font-bold">R$ {{ number_format($totalServicos + $totalLocacao, 2, ',', '.') }}</td>
        </tr>

        @if(isset($abatimentoReal) && $abatimentoReal > 0)
            <!-- A linha da sucata aparece no resumo com a cor vermelha -->
            <tr style="color: #d63031;">
                <td class="font-bold">(-) Desconto comercial retido por aquisição de sucatas do cliente</td>
                <td class="text-right font-bold">R$ {{ number_format($abatimentoReal, 2, ',', '.') }}</td>
            </tr>
        @endif

        <tr class="apuracao-saldo">
            <td>VALOR LÍQUIDO A PAGAR PARA A CONTRATADA</td>
            <!-- O valor líquido subtrai a sucata! -->
            <td class="text-right">R$ {{ number_format(($totalServicos + $totalLocacao) - ($abatimentoReal ?? 0), 2, ',', '.') }}</td>
        </tr>
    </table>

    <!-- 7. ASSINATURAS -->
    <div class="signatures">
        <div class="sig-box">
            <div class="font-bold">{{ mb_strtoupper($empresa->nome ?? $empresa->razao_social) }}</div>
            <div>Setor de Faturamento / Operações</div>
            <div class="sig-line">Data: ____/____/20___</div>
        </div>
        <div class="sig-box">
            <div class="font-bold">{{ mb_strtoupper($fatura->cliente->razao_social ?? $fatura->cliente->nome ?? 'CLIENTE') }}</div>
            <div>Aprovação da medição (Nome e Cargo)</div>
            <div class="sig-line">Data: ____/____/20___</div>
        </div>
    </div>

    <div class="footer-legal">
        {{ $empresa->razao_social ?? 'Empresa' }} - CNPJ {{ $empresa->cnpj ?? $empresa->cpf_cnpj }} <br>
        FerSoft ERP &bull; Automação Gerencial &bull; Documento impresso em {{ date('d/m/Y H:i') }}
    </div>
</div>
</body>
</html>
