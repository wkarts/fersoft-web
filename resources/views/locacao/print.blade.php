<!DOCTYPE html>
<html>
<head>
    <title>Relatório de Locações</title>
    <style type="text/css">
        .content{ margin-top: -30px; }
        .titulo{ font-size: 20px; margin-bottom: 0px; font-weight: bold; }
        .b-top{ border-top: 1px solid #000; }
        .b-bottom{ border-bottom: 1px solid #000; }
    </style>
</head>
<body>
<div class="content">
    <center><label class="titulo">RELATÓRIO DE LOCAÇÕES E CAÇAMBAS</label></center>
    <center><label class="titulo">NÃO É DOCUMENTO FISCAL</label></center>
</div>
<br>
<table>
    <tr>
        <td style="width: 700px;"><strong>Dados do Emitente</strong></td>
    </tr>
</table>
<table>
    <tr>
        <td class="b-top" style="width: 500px;">
            Razão social: <strong>{{$config->razao_social}}</strong>
        </td>
        <td class="b-top" style="width: 197px;">
            CNPJ: <strong>{{str_replace(" ", "", $config->cnpj)}}</strong>
        </td>
    </tr>
</table>
<table>
    <tr>
        <td class="b-top b-bottom" style="width: 700px;">
            Endereço: <strong>{{$config->logradouro}}, {{$config->numero}} - {{$config->bairro}} - {{$config->municipio}} ({{$config->UF}})</strong>
        </td>
    </tr>
</table>
<br>

<table>
    <tr>
        <td class="b-top b-bottom" style="width: 700px; height: 30px;">
            <strong>Registros de Locação:</strong>
        </td>
    </tr>
</table>

<table>
    <thead>
    <tr>
        <td style="width: 30px;">ID</td>
        <td style="width: 320px;">Cliente / Local de Entrega</td>
        <td style="width: 90px;">Tipo</td>
        <td style="width: 80px;">Data Início</td>
        <td style="width: 90px;">Data Fim</td>
        <td style="width: 90px;">Total (R$)</td>
    </tr>
    </thead>
    @php $soma = 0; @endphp
    <tbody>
    @foreach($locacoes as $l)
        <tr>
            <th class="b-top" style="text-align: left;">{{$l->id}}</th>
            <th class="b-top" style="text-align: left;">
                <strong>{{$l->cliente->razao_social}}</strong>
                @if($l->rua_entrega)
                    <br><small style="font-weight: normal;">Obra: {{$l->rua_entrega}}, {{$l->numero_entrega}} - {{$l->bairro_entrega}}</small>
                @endif
            </th>
            <th class="b-top" style="text-align: left;">{{ $l->tipo == 'cacamba' ? 'Caçamba' : 'Equipamento' }}</th>
            <th class="b-top" style="text-align: left;">{{ \Carbon\Carbon::parse($l->inicio)->format('d/m/Y')}}</th>
            <th class="b-top" style="text-align: left;">
                @if($l->fim != '1969-12-31' && $l->fim != null)
                    {{ \Carbon\Carbon::parse($l->fim)->format('d/m/Y')}}
                @else
                    A definir
                @endif
            </th>
            <th class="b-top" style="text-align: left;">{{ number_format($l->total, 2, ',', '.')}}</th>
        </tr>

        @if($l->observacao)
            <tr>
                <th colspan="6" class="b-top" style="text-align: left;">Observação: <strong style="color: blue">{{$l->observacao}}</strong></th>
            </tr>
        @endif

        @if(sizeof($l->itens) > 0)
            @foreach($l->itens as $i)
                <tr>
                    <th></th>
                    <th colspan="4" style="text-align: left; background-color: #f5f5f5;">
                        ↳ {{ $i->produto->nome }}
                        @if($i->codigo_patrimonio) <strong>[Patrimônio: {{$i->codigo_patrimonio}}]</strong> @endif
                        {{ $i->observacao ? '- ' . $i->observacao : '' }}
                    </th>
                    <th style="text-align: left; background-color: #f5f5f5;">R$ {{number_format($i->valor, 2, ',', '.')}}</th>
                </tr>
            @endforeach
        @endif

        @php $soma += $l->total; @endphp
    @endforeach
    </tbody>
</table>
<br>

<table>
    <tr>
        <td class="b-top b-bottom" style="width: 700px;">
            <center><strong>Somatório Total das Locações: R$ {{number_format($soma, 2, ',', '.')}}</strong></center>
        </td>
    </tr>
</table>
</body>
</html>
