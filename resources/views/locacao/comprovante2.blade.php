<!DOCTYPE html>
<html>
<head>
    <title>Documento Auxiliar de Locação</title>
    <style type="text/css">
        .content{ margin-top: -30px; }
        .titulo{ font-size: 20px; margin-bottom: 0px; font-weight: bold; }
        .b-top{ border-top: 1px solid #000; }
        .b-bottom{ border-bottom: 1px solid #000; }
    </style>
</head>
<body>
<div class="content">
    <center><label class="titulo">DOCUMENTO AUXILIAR DE LOCAÇÃO</label></center>
    <center><label class="titulo">NÃO É DOCUMENTO FISCAL</label></center>
    <center><label class="titulo">NÃO COMPROVA PAGAMENTO</label></center>
</div>
<br>
<table>
    <tr>
        <td style="width: 700px;">
            <strong>Dados do Emitente</strong>
        </td>
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
        <td style="width: 700px;">
            <strong>Identificação do Destinatário & Local de Entrega / Obra</strong>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td class="b-top" style="width: 450px;">
            Nome / Razão Social: <strong>{{$locacao->cliente->razao_social}}</strong>
        </td>
        <td class="b-top" style="width: 247px;">
            CPF/CNPJ: <strong>{{$locacao->cliente->cpf_cnpj}}</strong>
        </td>
    </tr>
</table>
<table>
    <tr>
        <td class="b-top" style="width: 700px;">
            Endereço Principal: <strong>{{$locacao->cliente->rua}}, {{$locacao->cliente->numero}} - {{$locacao->cliente->bairro}} - {{$locacao->cliente->cidade->nome}} ({{$locacao->cliente->cidade->uf}})</strong>
        </td>
    </tr>
    @if($locacao->rua_entrega)
        <tr>
            <td class="b-top" style="width: 700px; background-color: #f0f0f0;">
                Local da Obra / Entrega: <strong>{{$locacao->rua_entrega}}, {{$locacao->numero_entrega}} - {{$locacao->bairro_entrega}} {{$locacao->cidadeEntrega ? '- ' . $locacao->cidadeEntrega->nome . ' (' . $locacao->cidadeEntrega->uf . ')' : ''}}</strong>
                @if($locacao->referencia_entrega) (Ref: {{$locacao->referencia_entrega}}) @endif
            </td>
        </tr>
    @endif
</table>

<table>
    <tr>
        <td class="b-top b-bottom" style="width: 700px; height: 30px;">
            <strong>Itens / Patrimônios Alocados:</strong>
        </td>
    </tr>
</table>

<table>
    <thead>
    <tr>
        <td style="width: 200px;"><strong>Item / Produto</strong></td>
        <td style="width: 120px;"><strong>Cód. Patrimônio</strong></td>
        <td style="width: 272px;"><strong>Observação</strong></td>
        <td style="width: 100px;"><strong>Valor (R$)</strong></td>
    </tr>
    </thead>

    <tbody>
    @foreach($locacao->itens as $i)
        <tr>
            <th class="b-top" style="text-align: left;">{{ $i->produto->nome }}</th>
            <th class="b-top" style="text-align: left;">{{ $i->codigo_patrimonio ?? '--' }}</th>
            <th class="b-top" style="text-align: left;">{{ $i->observacao != "" ? $i->observacao : '--' }}</th>
            <th class="b-top" style="text-align: left;">{{ number_format($i->valor, 2, ',', '.') }}</th>
        </tr>
    @endforeach
    </tbody>
</table>
<br>

<table>
    <tr>
        <td class="b-top b-bottom" style="width: 250px;">
            <center><strong>Total: R$ {{number_format($locacao->total, 2, ',', '.')}}</strong></center>
        </td>
        <td class="b-top b-bottom" style="width: 225px;">
            <center><strong>Início / Entrega: {{ \Carbon\Carbon::parse($locacao->inicio)->format('d/m/Y') }}</strong></center>
        </td>
        <td class="b-top b-bottom" style="width: 225px;">
            <center><strong>Previsão Término:
                    @if($locacao->fim != '1969-12-31' && $locacao->fim != null)
                        {{ \Carbon\Carbon::parse($locacao->fim)->format('d/m/Y')}}
                    @else
                        A definir
                    @endif
                </strong></center>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td class="b-top b-bottom" style="width: 700px;">
            <center><strong>Observações / Instruções: {{ $locacao->observacao != "" ? $locacao->observacao : '--' }}</strong></center>
        </td>
    </tr>
</table>

<br><br><br>
<table>
    <tr>
        <td style="width: 350px; text-align: center;">
            _______________________________________<br>
            {{$config->razao_social}}
        </td>
        <td style="width: 350px; text-align: center;">
            _______________________________________<br>
            {{$locacao->cliente->razao_social}}
        </td>
    </tr>
</table>
</body>
</html>
