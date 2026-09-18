<!DOCTYPE html>
<html>
<head>
    <title>Ordem de Serviço Logística</title>
    <style type="text/css">
        .content{ margin-top: -30px; }
        .titulo{ font-size: 20px; margin-bottom: 0px; font-weight: bold; }
        .b-top{ border-top: 1px solid #000; }
        .b-bottom{ border-bottom: 1px solid #000; }
        .align-right{ float: right; }
    </style>
</head>
<body>
<br>
<table>
    <tr>
        <td style="width: 250px; height: 170px;">
            @if($config->logo != null)
                <img src="{{'data:image/png;base64,' . base64_encode(safe_file_get_contents(@public_path('logos/').$config->logo))}}" width="250px;" height="150px;">
            @endif
        </td>

        <td style="width: 450px; height: 150px">
            <br>
            <strong style="font-size: 12px; float: right">{{$config->nome_fantasia}}</strong><br>
            <strong style="font-size: 12px; float: right;">Ordem de Serviço / Locação Logística</strong><br>
            <strong style="font-size: 12px; float: right;">_______________________________________________________</strong><br>
            <b style="font-size: 10px; float: right;">{{$config->razao_social}}</b><br>
            <b style="font-size: 10px; float: right; margin-top: -7px;">
                CNPJ: <strong>{{str_replace(" ", "", $config->cnpj)}} - IE: {{$config->ie}}</strong>
            </b><br>
            <b class="line-control" style="font-size: 10px; float: right; margin-top: -15px;"> {{$config->fone}} - {{$config->email}}</b><br>
            <b class="line-control" style="font-size: 10px; float: right; margin-top: -4px;">
                {{$config->logradouro}}, {{$config->numero}} - {{$config->bairro}}
            </b><br>
            <b class="line-control" style="font-size: 10px; float: right; margin-top: -13px;">
                {{$config->municipio}} ({{$config->UF}}) - {{$config->cep}}
            </b>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td style="width: 700px;"><hr></td>
    </tr>
</table>

<table>
    <tr>
        <td style="width: 450px;">
            <span style="font-size: 15px;">Locação / OS Nº: <strong>{{$locacao->id}}</strong> {{$locacao->contrato_id ? '(Contrato #' . $locacao->contrato_id . ')' : ''}}</span>
        </td>
        <td style="width: 250px;">
            <span style="font-size: 15px;">Data Início: <strong>{{ \Carbon\Carbon::parse($locacao->inicio)->format('d/m/Y') }}</strong></span>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td style="width: 450px;">
            <span style="font-size: 15px;">Cliente: <strong>{{$locacao->cliente->razao_social}}</strong></span>
        </td>
        <td style="width: 250px;">
				<span style="font-size: 15px;">Situação:
					<strong>
						@if($locacao->status == 0) AGENDADO / NOVO
                        @elseif($locacao->status == 1) EM USO / NO CLIENTE
                        @elseif($locacao->status == 2) AGUARDANDO RETIRADA
                        @elseif($locacao->status == 3) FINALIZADO / RETIRADO
                        @endif
					</strong>
				</span>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td style="width: 700px;">
            <span style="font-size: 13px;">Endereço Cadastro: {{$locacao->cliente->rua}}, {{$locacao->cliente->numero}} - {{$locacao->cliente->bairro}} - {{$locacao->cliente->cidade->nome}} ({{$locacao->cliente->cidade->uf}})</span>
        </td>
    </tr>
    @if($locacao->rua_entrega)
        <tr>
            <td style="width: 700px; background-color: #f9f9f9;">
                <span style="font-size: 13px;"><strong>LOCAL DA OBRA / ENTREGA:</strong> {{$locacao->rua_entrega}}, {{$locacao->numero_entrega}} - {{$locacao->bairro_entrega}} {{$locacao->cidadeEntrega ? '- ' . $locacao->cidadeEntrega->nome : ''}} @if($locacao->referencia_entrega) (Ref: {{$locacao->referencia_entrega}}) @endif</span>
            </td>
        </tr>
    @endif
</table>

<table>
    <tr>
        <td style="width: 233px;"><span style="font-size: 13px;">CPF/CNPJ: {{$locacao->cliente->cpf_cnpj}}</span></td>
        <td style="width: 233px;"><span style="font-size: 13px;">RG/IE: {{$locacao->cliente->ie_rg}}</span></td>
        <td style="width: 233px;"><span style="font-size: 13px;">Telefone: {{$locacao->cliente->telefone}} / {{$locacao->cliente->celular}}</span></td>
    </tr>
</table>

<br>

<table>
    <tr>
        <td class="b-top b-bottom" style="width: 700px; height: 30px;">
            <strong>Produtos / Patrimônios e Serviços:</strong>
        </td>
    </tr>
</table>

<table>
    <thead>
    <tr>
        <td style="width: 180px;">Descrição</td>
        <td style="width: 120px;">Cód. Patrimônio</td>
        <td style="width: 60px;">Unid.</td>
        <td style="width: 80px;">Valor (R$)</td>
        <td style="width: 180px;">Observação</td>
        <td style="width: 80px;">Total (R$)</td>
    </tr>
    </thead>

    <tbody>
    @foreach($locacao->itens as $key => $i)
        <tr @if($key%2 != 0) style="background-color: #e8eaf6" @endif>
            <th style="text-align: left;" class="b-top">{{ $i->produto->nome }}</th>
            <th style="text-align: left;" class="b-top">{{ $i->codigo_patrimonio ?? '--' }}</th>
            <th style="text-align: left;" class="b-top">{{ $i->produto->unidade_venda }}</th>
            <th style="text-align: left;" class="b-top">{{ number_format($i->valor, 2, ',', '.') }}</th>
            <th style="text-align: left;" class="b-top">{{ $i->observacao != "" ? $i->observacao : '--' }}</th>
            <th style="text-align: left;" class="b-top">{{ number_format($i->valor, 2, ',', '.') }}</th>
        </tr>
    @endforeach
    </tbody>
</table>

<table>
    <tr>
        <td class="b-top" style="width: 450px;"></td>
        <td class="b-top" style="width: 150px;">
            <strong style="float: right; margin-right: 10px;">Total da Locação:</strong>
        </td>
        <td class="b-top" style="width: 100px;">
            <strong>R$ {{number_format($locacao->total, 2, ',', '.')}}</strong>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td class="b-top b-bottom" style="width: 700px;">
            Observação: <strong>{{ $locacao->observacao != "" ? $locacao->observacao : '--' }}</strong>
        </td>
    </tr>
</table>
<br><br>
<table>
    <tr>
        <td style="width: 700px; text-align: center;">
            _______________________________________<br>
            Assinatura do Recebedor / Responsável pela Obra
        </td>
    </tr>
</table>
</body>
</html>
