<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pagamento de Salário</title>
    <style>
        @page {
            margin: 20px 25px;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .borda-box {
            border: 1px solid #000;
        }
        .borda-baixo {
            border-bottom: 1px solid #000;
        }
        .borda-direita {
            border-right: 1px solid #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }

        .cabecalho-tabela td {
            padding: 3px 5px;
            vertical-align: top;
        }
        .titulo-recibo {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .tabela-itens {
            width: 100%;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-collapse: collapse;
        }
        .tabela-itens th {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 9px;
            padding: 4px;
            background-color: #f7f7f7;
        }
        .tabela-itens td {
            font-size: 9.5px;
            padding: 3px 5px;
            vertical-align: top;
        }
        .linha-evento td {
            height: 16px;
        }

        .tabela-resumo {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
        }
        .tabela-resumo td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 9px;
        }
        .caixa-liquido {
            background-color: #eee;
            font-size: 10.5px;
        }
        .legenda-via {
            font-size: 8.5px;
            margin-top: 3px;
        }
        .assinatura-box {
            border: 1px solid #000;
            border-top: none;
            padding: 12px 6px 8px 6px;
            text-align: center;
        }
    </style>
</head>
<body>

<table class="borda-box cabecalho-tabela">
    <tr>
        <td colspan="4" class="borda-baixo borda-direita">
            <span class="titulo-recibo">RECIBO DE PAGAMENTO DE SALÁRIO MENSAL</span>
        </td>
        <td class="borda-baixo" style="width: 25%;">
            Competência<br>
            <div class="text-right bold" style="font-size: 11px;">{{ $holerite->competencia }}</div>
        </td>
    </tr>
    <tr>
        <td colspan="4" class="borda-baixo borda-direita">
            <span style="font-size: 8px;">Empresa</span><br>
            <span class="bold">{{ $dadosEmpresa['razao_social'] }}</span><br>
            {{ $dadosEmpresa['endereco'] }}<br>
            CNPJ: {{ $dadosEmpresa['cnpj'] }}
        </td>
        <td class="borda-baixo">
            <span style="font-size: 8px;">Divisão R.H.</span><br>
            <span class="bold">{{ $holerite->divisao_rh ?? '001.000.000' }}</span><br><br>
            <span style="font-size: 8px;">Função</span><br>
            <span class="bold">{{ $holerite->funcao ?? ($funcionario->cargo ?? 'Auxiliar') }}</span>
        </td>
    </tr>
    <tr>
        <td style="width: 10%;" class="borda-direita">Nº Reg.<br><strong>{{ $holerite->matricula }}</strong></td>
        <td style="width: 10%;" class="borda-direita">Chapa<br>&nbsp;</td>
        <td colspan="3">Nome<br><strong style="font-size: 10.5px;">{{ $funcionario->nome }}</strong></td>
    </tr>
</table>

<table class="tabela-itens">
    <thead>
    <tr>
        <th style="width: 8%;" class="text-center borda-direita">Cód.</th>
        <th style="width: 48%;" class="borda-direita">Descrição</th>
        <th style="width: 12%;" class="text-center borda-direita">Horas/Dias</th>
        <th style="width: 16%;" class="text-right borda-direita">Vencimentos</th>
        <th style="width: 16%;" class="text-right">Descontos</th>
    </tr>
    </thead>
    <tbody>
    @foreach($itens as $item)
        <tr class="linha-evento">
            <td class="text-center borda-direita">{{ $item->codigo_evento }}</td>
            <td class="borda-direita">{{ $item->descricao }}</td>
            <td class="text-center borda-direita">{{ $item->referencia ?: '' }}</td>
            <td class="text-right borda-direita">{{ $item->tipo == 'provento' ? number_format($item->valor, 2, ',', '.') : '' }}</td>
            <td class="text-right">{{ $item->tipo == 'desconto' ? number_format($item->valor, 2, ',', '.') : '' }}</td>
        </tr>
    @endforeach
    @for($i = count($itens); $i < 10; $i++)
        <tr class="linha-evento">
            <td class="borda-direita">&nbsp;</td>
            <td class="borda-direita"></td>
            <td class="borda-direita"></td>
            <td class="borda-direita"></td>
            <td></td>
        </tr>
    @endfor
    </tbody>
</table>

<table class="tabela-resumo">
    <tr>
        <td rowspan="2" style="width: 14%;" class="bold text-center">RESUMO DO<br>SALÁRIO</td>
        <td style="width: 20%;">Salário Base<br><span class="bold">{{ number_format($holerite->salario_base, 2, ',', '.') }}</span></td>
        <td style="width: 20%;">Sal. Contribuição<br><span class="bold">{{ number_format($holerite->base_inss, 2, ',', '.') }}</span></td>
        <td style="width: 23%;">Total de Vencimentos<br><span class="bold">{{ number_format($holerite->total_vencimentos, 2, ',', '.') }}</span></td>
        <td style="width: 23%;">Total de Descontos<br><span class="bold">{{ number_format($holerite->total_descontos, 2, ',', '.') }}</span></td>
    </tr>
    <tr>
        <td>Base Cál. F.G.T.S<br><span class="bold">{{ number_format($holerite->base_fgts, 2, ',', '.') }}</span></td>
        <td>F.G.T.S do Mês<br><span class="bold">{{ number_format($holerite->valor_fgts, 2, ',', '.') }}</span></td>
        <td>Base Cál. I.R.<br><span class="bold">{{ number_format($holerite->base_irrf, 2, ',', '.') }}</span></td>
        <td colspan="2" class="caixa-liquido">
            <strong>L Í Q U I D O &nbsp; A &nbsp; R E C E B E R</strong>
            <div class="text-right bold" style="font-size: 12px; margin-top: 2px;">
                R$ {{ number_format($holerite->valor_liquido, 2, ',', '.') }}
            </div>
        </td>
    </tr>
</table>

<div class="assinatura-box">
    <div class="bold" style="font-size: 8.5px; margin-bottom: 8px;">
        DECLARO TER RECEBIDO A IMPORTÂNCIA LÍQUIDA DISCRIMINADA NESTE RECIBO
    </div>
    <table style="width: 90%; margin: 0 auto;">
        <tr>
            <td style="width: 30%; border-bottom: 1px solid #000; height: 35px; vertical-align: bottom;">
                {{ !empty($holerite->data_assinatura) ? date('d/m/Y H:i', strtotime($holerite->data_assinatura)) : '' }}
            </td>
            <td style="width: 15%;"></td>
            <td style="width: 55%; border-bottom: 1px solid #000; height: 35px; vertical-align: bottom; text-align: center;">
                @if(!empty($holerite->assinatura_imagem))
                    <img src="{{ $holerite->assinatura_imagem }}" style="max-height: 35px; max-width: 180px;">
                @elseif(!empty($holerite->assinado) && $holerite->tipo_assinatura == 'whatsapp_texto')
                    <span style="font-size: 8px; color: #003366; font-weight: bold;">
                            [ASSINADO DIGITALMENTE VIA WHATSAPP]<br>
                            IP/ID: {{ $holerite->ip_assinatura ?? 'WHATSAPP' }}
                        </span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="text-center" style="font-size: 8px; padding-top: 4px;">DATA</td>
            <td></td>
            <td class="text-center" style="font-size: 8px; padding-top: 4px;">ASSINATURA DO FUNCIONÁRIO</td>
        </tr>
    </table>
</div>

<table style="width: 100%;">
    <tr>
        <td class="legenda-via text-left">Recibo de Pagamento</td>
        <td class="legenda-via text-right bold">Via/Empregador</td>
    </tr>
</table>

</body>
</html>
