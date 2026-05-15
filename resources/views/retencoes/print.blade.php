<!DOCTYPE html>
<html>
<head>
    <title>Relatório de Retenções</title>
    <style type="text/css">
        body { font-family: sans-serif; font-size: 11px; }
        .text-right { text-align: right; }
        .b-top { border-top: 1px solid #000; }
        .b-bottom { border-bottom: 1px solid #000; }
        table { width: 100%; border-collapse: collapse; }
        thead td { font-weight: bold; padding: 5px; background: #f2f2f2; }
        tbody td { padding: 5px; border-bottom: 1px solid #ddd; }
        .titulo { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <div style="margin-bottom: 20px;">
        <table>
            <tr>
                <td width="20%">
                    @if($config->logo != "")
                        <img src="{{'data:image/png;base64,' . base64_encode(safe_file_get_contents(@public_path('logos/').$config->logo))}}" width="80px;">
                    @else
                        <img src="{{'data:image/png;base64,' . base64_encode(safe_file_get_contents(@public_path('imgs/slym.png')))}}" width="80px;">
                    @endif
                </td>
                <td width="80%" align="center">
                    <span class="titulo">Relatório de Retenções (Por Emissão)</span><br>
                    <strong>{{$config->razao_social}}</strong> | {{$config->cnpj}}
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <td>Fornecedor</td>
                <td>Data Emissão</td>
                <td class="text-right">Vl Bruto</td>
                <td class="text-right">INSS</td>
                <td class="text-right">ISS</td>
                <td class="text-right">PIS</td>
                <td class="text-right">COFINS</td>
                <td class="text-right">IR</td>
                <td class="text-right">CSLL</td>
                <td class="text-right">Outras</td>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr>
                <td>{{ $item->fornecedor->razao_social }}</td>
                <td>{{ __date($item->data_emissao) }}</td>
                <td class="text-right">{{ moeda($item->valor_integral) }}</td>
                <td class="text-right">{{ moeda($item->valor_inss) }}</td>
                <td class="text-right">{{ moeda($item->valor_iss) }}</td>
                <td class="text-right">{{ moeda($item->valor_pis) }}</td>
                <td class="text-right">{{ moeda($item->valor_cofins) }}</td>
                <td class="text-right">{{ moeda($item->valor_ir) }}</td>
                <td class="text-right">{{ moeda($item->valor_csll) }}</td>
                <td class="text-right">{{ moeda($item->outras_retencoes) }}</td>
            </tr>
            @endforeach
        </tbody>
        @php
            $t_bruto = $data->sum('valor_integral');
            $t_ret = $data->sum('valor_inss') + $data->sum('valor_iss') + $data->sum('valor_pis') + $data->sum('valor_cofins') + $data->sum('valor_ir') + $data->sum('valor_csll') + $data->sum('outras_retencoes');
        @endphp
        <tfoot>
            <tr>
                <td colspan="2" class="b-top"><strong>TOTAIS</strong></td>
                <td class="text-right b-top"><strong>{{ moeda($t_bruto) }}</strong></td>
                <td class="text-right b-top">{{ moeda($data->sum('valor_inss')) }}</td>
                <td class="text-right b-top">{{ moeda($data->sum('valor_iss')) }}</td>
                <td class="text-right b-top">{{ moeda($data->sum('valor_pis')) }}</td>
                <td class="text-right b-top">{{ moeda($data->sum('valor_cofins')) }}</td>
                <td class="text-right b-top">{{ moeda($data->sum('valor_ir')) }}</td>
                <td class="text-right b-top">{{ moeda($data->sum('valor_csll')) }}</td>
                <td class="text-right b-top">{{ moeda($data->sum('outras_retencoes')) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>