<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Comprovante de Medição' }}</title>
    <style>
        body { 
            background-color: #4a4d50; 
            margin: 0; 
            padding: 20px; 
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif; 
            font-size: 12px; 
            color: #2d3748; 
        }

        /* Folha A4 centralizada */
        .sheet {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 12mm 15mm;
            box-sizing: border-box;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
            border-radius: 4px;
        }

        /* Cabeçalho principal */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #2b6cb0;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }

        /* Seções e Cards de Informação */
        .section-title { 
            background: #ebf8ff; 
            color: #2b6cb0;
            padding: 6px 10px; 
            font-weight: bold; 
            border-left: 4px solid #3182ce; 
            margin-top: 18px; 
            margin-bottom: 10px; 
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .row-grid {
            width: 100%;
            display: table;
            table-layout: fixed;
            margin-bottom: 10px;
        }
        .col-half {
            width: 50%;
            display: table-cell;
            vertical-align: top;
        }
        .col-half:first-child { padding-right: 8px; }
        .col-half:last-child { padding-left: 8px; }

        .box-info { 
            border: 1px solid #e2e8f0; 
            padding: 10px 12px; 
            border-radius: 6px; 
            background: #f7fafc; 
            min-height: 95px; 
        }
        .box-info strong.box-title {
            display: block;
            color: #2d3748;
            font-size: 12px;
            margin-bottom: 6px;
            border-bottom: 1px solid #edf2f7;
            padding-bottom: 4px;
        }
        .box-info p { margin: 3px 0; font-size: 11px; color: #4a5568; }

        /* Tabelas e Listagens */
        table.data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 8px; 
        }
        table.data-table th { 
            background-color: #2d3748; 
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 7px 8px;
            border: 1px solid #2d3748;
            text-align: left;
        }
        table.data-table td { 
            border: 1px solid #e2e8f0; 
            padding: 7px 8px; 
            font-size: 11px;
            color: #2d3748;
        }
        table.data-table tbody tr:nth-child(even) { background-color: #f7fafc; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Card de Destaque para o Valor Total */
        .total-box {
            margin-top: 20px;
            background: #2b6cb0;
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 6px;
            text-align: right;
            float: right;
            min-width: 240px;
        }
        .total-box span { font-size: 11px; text-transform: uppercase; display: block; opacity: 0.9; }
        .total-box h2 { margin: 3px 0 0 0; font-size: 20px; font-weight: bold; }

        .clear { clear: both; }

        .footer { 
            margin-top: 35px; 
            text-align: center; 
            font-size: 10px; 
            color: #a0aec0; 
            border-top: 1px dashed #e2e8f0; 
            padding-top: 10px; 
        }

        /* Regras de Impressão */
        @media print {
            body { background: #ffffff; padding: 0; }
            .sheet { width: 100%; box-shadow: none; margin: 0; padding: 0; min-height: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Barra de Ações Superior (Escondida ao Imprimir) -->
    <div class="no-print" style="max-width: 210mm; margin: 0 auto 15px auto; text-align: right;">
        <button onclick="window.print()" style="padding: 9px 18px; background: #3182ce; color: #fff; border: none; cursor: pointer; border-radius: 5px; font-weight: bold; font-size: 13px;">
            🖨️ Imprimir / Salvar PDF
        </button>
    </div>

    <!-- Folha A4 -->
    <div class="sheet">
        
        <!-- Cabeçalho -->
        <table class="header-table">
            <tr>
                <td style="width: 140px;">
                    @if(!empty($configNota->logo) && file_exists(public_path('logos/' . $configNota->logo)))
                        <img src="{{ asset('logos/' . $configNota->logo) }}" alt="Logo" style="max-width: 130px; max-height: 60px; object-fit: contain;">
                    @else
                        <div style="font-size: 11px; color: #a0aec0; font-weight: bold;">[LOGO EMPRESA]</div>
                    @endif
                </td>
                <td style="text-align: center;">
                    <h2 style="margin: 0; color: #1a202c; font-size: 17px; text-transform: uppercase;">{{ $empresa->nome ?? $empresa->razao_social ?? 'FERSOFT ERP' }}</h2>
                    <p style="margin: 4px 0; font-size: 11px; color: #4a5568;">
                        CNPJ: {{ $empresa->cnpj ?? $empresa->cpf_cnpj ?? 'N/D' }} &nbsp;|&nbsp; Tel: {{ $empresa->telefone ?? 'N/D' }}<br>
                        {{ $empresa->rua ?? '' }}, {{ $empresa->numero ?? '' }} - {{ $empresa->bairro ?? '' }}
                    </p>
                    <div style="margin-top: 6px; font-size: 14px; font-weight: bold; color: #2b6cb0;">
                        COMPROVANTE DE MEDIÇÃO / FATURAMENTO #{{ $fatura->id }}
                    </div>
                </td>
                <td style="width: 140px; text-align: right; font-size: 10px; color: #718096;">
                    <strong>Emissão:</strong><br>
                    {{ date('d/m/Y H:i', strtotime($fatura->created_at)) }}
                </td>
            </tr>
        </table>

        <!-- Dados do Emitente e Cliente -->
        <div class="row-grid">
            <div class="col-half">
                <div class="box-info">
                    <strong class="box-title">EMITENTE</strong>
                    <p><strong>Razão Social:</strong> {{ $empresa->nome ?? $empresa->razao_social ?? 'N/D' }}</p>
                    <p><strong>CNPJ:</strong> {{ $empresa->cnpj ?? $empresa->cpf_cnpj ?? 'N/D' }}</p>
                    <p><strong>Endereço:</strong> {{ $empresa->rua ?? '' }}, {{ $empresa->numero ?? '' }} - {{ $empresa->bairro ?? '' }}</p>
                    <p><strong>E-mail:</strong> {{ $empresa->email ?? 'N/D' }}</p>
                </div>
            </div>
            <div class="col-half">
                <div class="box-info">
                    <strong class="box-title">CLIENTE (CONTRATANTE)</strong>
                    <p><strong>Razão Social:</strong> {{ $fatura->cliente->razao_social ?? $fatura->cliente->nome ?? 'N/D' }}</p>
                    <p><strong>CNPJ / CPF:</strong> {{ $fatura->cliente->cpf_cnpj ?? $fatura->cliente->cnpj ?? $fatura->cliente->cpf ?? 'N/D' }}</p>
                    <p><strong>Endereço:</strong> {{ $fatura->cliente->rua ?? '' }}, {{ $fatura->cliente->numero ?? '' }} - {{ $fatura->cliente->bairro ?? '' }}</p>
                    <p><strong>Contato:</strong> {{ $fatura->cliente->celular ?? $fatura->cliente->telefone ?? 'N/D' }} &nbsp;|&nbsp; {{ $fatura->cliente->email ?? 'N/D' }}</p>
                </div>
            </div>
        </div>

        <!-- Dados Gerais da Medição -->
        <div class="section-title">Dados do Contrato e Faturamento</div>
        <table class="data-table">
            <tr>
                <td style="width: 50%;"><strong>Contrato Vinculado:</strong> {{ $fatura->contrato ? 'Contrato Nº ' . ($fatura->contrato->numero_contrato ?? $fatura->contrato->id) : 'Lançamento Avulso (Sem Contrato)' }}</td>
                <td style="width: 50%;"><strong>Data de Faturamento:</strong> {{ date('d/m/Y', strtotime($fatura->data_faturamento ?? $fatura->created_at)) }}</td>
            </tr>
            <tr>
                <td><strong>Tipo de Pagamento:</strong> {{ $fatura->tipo_pagamento ?? 'N/D' }}</td>
                <td><strong>Categoria Financeira:</strong> {{ $fatura->categoriaConta->nome ?? $fatura->categoriaConta->descricao ?? 'N/D' }}</td>
            </tr>
            @if($fatura->observacao)
            <tr>
                <td colspan="2"><strong>Observações:</strong> {{ $fatura->observacao }}</td>
            </tr>
            @endif
        </table>

        <!-- Itens Executados -->
        @php
            $itensLista = ($fatura->contrato && $fatura->contrato->itens && count($fatura->contrato->itens) > 0) 
                ? $fatura->contrato->itens 
                : ($fatura->itens ?? []);
        @endphp

        @if(count($itensLista) > 0)
        <div class="section-title">Itens Executados (Serviços e Locações)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Tipo</th>
                    <th>Descrição do Item / Serviço</th>
                    <th class="text-right" style="width: 15%;">Qtd</th>
                    <th class="text-right" style="width: 20%;">Valor Unit. (R$)</th>
                    <th class="text-right" style="width: 20%;">Total (R$)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itensLista as $item)
                <tr>
                    <td>{{ $item->tipo_item ?? 'Serviço' }}</td>
                    <td>{{ $item->servico->nome ?? $item->produto->nome ?? 'Prestação de Serviço' }}</td>
                    <td class="text-right">{{ number_format($item->quantidade ?? $item->quantidade_prevista ?? 1, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->valor_unitario ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format(($item->valor_total ?? (($item->quantidade ?? 1) * ($item->valor_unitario ?? 0))), 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Equipe Envolvida -->
        @if($fatura->funcionarios && $fatura->funcionarios->count() > 0)
        <div class="section-title">Equipe e Mão de Obra Alocada</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Funcionário</th>
                    <th>Função / Cargo</th>
                    <th class="text-right" style="width: 15%;">Diárias / Qtd</th>
                    <th class="text-right" style="width: 20%;">Valor Diária (R$)</th>
                    <th class="text-right" style="width: 20%;">Total (R$)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fatura->funcionarios as $func)
                <tr>
                    <td>{{ $func->funcionario->nome ?? 'N/D' }}</td>
                    <td>{{ $func->funcao ?? 'Técnico' }}</td>
                    <td class="text-right">{{ number_format($func->diarias ?? 1, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($func->valor_diaria ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format(($func->valor_total ?? (($func->diarias ?? 1) * ($func->valor_diaria ?? 0))), 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Condição de Pagamento e Vencimentos -->
        @if(isset($parcelas) && count($parcelas) > 0)
        <div class="section-title">Condições de Pagamento e Vencimentos</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Referência / Título</th>
                    <th class="text-center" style="width: 25%;">Vencimento</th>
                    <th class="text-right" style="width: 25%;">Valor da Parcela (R$)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($parcelas as $p)
                <tr>
                    <td>{{ $p->referencia }}</td>
                    <td class="text-center">{{ date('d/m/Y', strtotime($p->data_vencimento)) }}</td>
                    <td class="text-right">R$ {{ number_format($p->valor_integral, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Box Totalizador -->
        <div class="total-box">
            <span>Valor Total da Medição</span>
            <h2>R$ {{ number_format($fatura->valor_total ?? 0, 2, ',', '.') }}</h2>
        </div>
        <div class="clear"></div>

        <div class="footer">
            FerSoft ERP &bull; Gestão Empresarial e de Contratos &bull; Documento Gerado em {{ date('d/m/Y H:i') }}
        </div>

    </div>

</body>
</html>