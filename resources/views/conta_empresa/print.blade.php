<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Extrato Bancário - {{ $item->nome }}</title>
    <style>
        /* Define as margens nativas da página A4 no Dompdf */
        @page {
            margin: 1.5cm; 
        }

        body {
            background: #ffffff; /* Fundo branco para remover o quadrado cinza */
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
        }

        /* A div page agora apenas agrupa, sem forçar larguras ou margens que causam corte */
        .page {
            width: 100%;
        }

        /* Cabeçalho da Empresa */
        .table-header { width: 100%; margin-bottom: 10px; border: none; }
        .logo { max-width: 130px; max-height: 90px; }
        .empresa-info { text-align: center; line-height: 1.4; color: #444; }
        .empresa-nome { font-size: 16px; font-weight: 800; color: #000; text-transform: uppercase; }

        .titulo-relatorio {
            text-align: center;
            background: #f8f9fa;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 8px;
            margin: 15px 0;
            font-weight: bold;
            font-size: 13px;
            letter-spacing: 1px;
        }

        /* Tabela de Extrato com alinhamento preciso */
        table.extrato {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Impede que a tabela ultrapasse a margem */
        }
        .extrato th {
            border-bottom: 2px solid #000;
            padding: 10px 5px;
            text-align: left;
            font-weight: bold;
            color: #000;
        }
        .extrato td {
            padding: 8px 5px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        /* Definição das Larguras das Colunas para não "vazar" da tela */
        .col-data { width: 15%; }
        .col-desc { width: 40%; }
        .col-entrada { width: 15%; text-align: right; }
        .col-saida { width: 15%; text-align: right; }
        .col-saldo { width: 15%; text-align: right; }

        .text-right { text-align: right; padding-right: 5px !important; }
        .bold { font-weight: bold; }
        .debito { color: #d9534f; }
    </style>
  </head>
<body>

    <div class="page">
        {{-- Cabeçalho Identidade Visual --}}
        <table class="table-header">
            <tr>
                <td width="20%">
                    @if($caminhoLogo)
                        <img class="logo" src="{{ $caminhoLogo }}">
                    @else
                        {{-- Caso não tenha logo cadastrada, busca uma padrão --}}
                        @if(file_exists(public_path('imgs/logo.png')))
                            <img class="logo" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('imgs/logo.png'))) }}">
                        @endif
                    @endif
                </td>
                <td width="80%" class="empresa-info">
                    <span class="empresa-nome">{{ $empresa->nome }}</span><br>
                    {{ $empresa->rua }}, {{ $empresa->numero }} - {{ $empresa->bairro }}<br>
                    {{ $empresa->cidade }} ({{ $empresa->cidade->uf ?? 'BA' }}) - {{ $empresa->cep }} | CNPJ: {{ $empresa->cnpj }}<br>
                    Fone: {{ $empresa->telefone }}
                </td>
            </tr>
        </table>

        <div class="titulo-relatorio">EXTRATO BANCÁRIO</div>

        <div style="margin-bottom: 20px; font-size: 11px; line-height: 1.6;">
            <strong>CONTA:</strong> {{ $item->nome }} | <strong>AGÊNCIA:</strong> {{ $item->agencia }} | <strong>CONTA:</strong> {{ $item->conta }}<br>
            <strong>PERÍODO:</strong> {{ $data_inicio ? date('d/m/Y', strtotime($data_inicio)) : 'Início' }} até {{ $data_final ? date('d/m/Y', strtotime($data_final)) : date('d/m/Y') }}
        </div>

        <table class="extrato">
            <thead>
                <tr>
                    <th width="15%">DATA</th>
                    <th width="40%">DESCRIÇÃO</th>
                    <th width="15%" style="text-align: right;">ENTRADA</th>
                    <th width="15%" style="text-align: right;">SAÍDA</th>
                    <th width="15%" style="text-align: right;">SALDO</th>
                </tr>
            </thead>
            <tbody>
                {{-- Saldo Anterior --}}
                <tr class="bold">
                    <td></td>
                    <td>SALDO ANTERIOR AO PERÍODO</td>
                    <td></td>
                    <td></td>
                    <td class="text-right">
                        {{ number_format(abs($saldo_anterior), 2, ',', '.') }}
                        {{ $saldo_anterior >= 0 ? 'C' : 'D' }}
                    </td>
                </tr>

                @php $saldo_acumulado = $saldo_anterior; @endphp

                @if(isset($movimentacoes) && count($movimentacoes) > 0)
                    @foreach($movimentacoes as $m)
                        @php
                            if($m->tipo == 'entrada') {
                                $saldo_acumulado += $m->valor;
                                $entrada = $m->valor;
                                $saida = null;
                            } else {
                                $saldo_acumulado -= $m->valor;
                                $entrada = null;
                                $saida = $m->valor;
                            }
                        @endphp
                        <tr>
                            <td>{{ date('d/m/Y', strtotime($m->data_pagamento ?? $m->created_at)) }}</td>
                            <td style="text-transform: uppercase; font-size: 10px;">{{ $m->descricao }}</td>
                            <td class="text-right">
                                {{ $entrada ? number_format($entrada, 2, ',', '.') : '' }}
                            </td>
                            <td class="text-right">
                                {{ $saida ? number_format($saida, 2, ',', '.') : '' }}
                            </td>
                            <td class="text-right bold {{ $saldo_acumulado < 0 ? 'debito' : '' }}">
                                {{ number_format(abs($saldo_acumulado), 2, ',', '.') }}
                                {{ $saldo_acumulado >= 0 ? 'C' : 'D' }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">Nenhuma movimentação encontrada no período.</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr class="bold" style="background-color: #f8f9fa;">
                    <td colspan="4" class="text-right" style="padding: 12px 5px;">SALDO FINAL EM {{ date('d/m/Y', strtotime($data_final ?? date('Y-m-d'))) }}:</td>
                    <td class="text-right" style="font-size: 13px;">
                        {{ number_format(abs($saldo_acumulado), 2, ',', '.') }}
                        {{ $saldo_acumulado >= 0 ? 'C' : 'D' }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

</body>
</html>