<!DOCTYPE html>
<html>
<head>

	<style type="text/css">
		body {
			line-height: 0.1px;
		}

		div {
			display: inline-block;
		}

		.logo {
			border: 1px solid #000;
		}

		p{
			font-size: 11px;
		}

		th{
			font-size: 9px;
			text-align: left;
		}
		td{
			font-size: 8px;
			height: 20px;
		}
		tbody td{
			font-size: 8px;
			height: 12px;
		}
		table{
			border-collapse: collapse;
		}
		.striped{
			background: #F1F2F3;
		}
		.striped th{
			height: 13px;
			padding-top: 5px;
		}

		.dados-emitente {
			margin-left: -1px;
			margin-bottom: 30px;
		}

		*{
			font-family: "Lucida Console", "Courier New", monospace;
		}

		.total{
			background: #999;
			padding: 5px;
			color: #F3F6F9;
			height: 25px;
			border-radius: 2px;
			float: right;
		}

		.assinatura{
			margin-top: 90px;
			float: right;
			display: flex;
			margin-right: -170px;
		}
		.assinatura p{
			text-align: right;
		}
		

	</style>
</head>
<body style="margin-top: -25px;">
	<div class="content">
		<table>
			<tr>
				<div style="width: 140px; height: 55px;">
					@if($config->logo != "")
					<img src="{{'data:image/png;base64,' . base64_encode(file_get_contents(@public_path('logos/').$config->logo))}}" style="height: 60px;">
					@else
					<img src="{{'data:image/png;base64,' . base64_encode(file_get_contents(@public_path('imgs/slym.png')))}}" width="100px;">
					@endif

				</div>

				<div class="dados-emitente" style="width: 550px; height: 35px;">
					<h4 style="margin-left: 5px;">{{$config->razao_social}}</h4>
					<p style="margin-left: 5px;">ENDEREÇO: {{ $config->logradouro }} <span style="margin-left: 55px;">N: {{ $config->numero }}</span> </p>
					<p style="margin-left: 5px;">
						CIDADE: {{ $config->municipio }} <span style="margin-left: 15px;">BAIRRO: {{ $config->bairro }}</span><span style="margin-left: 35px;">UF: {{ $config->UF }}</span>
					</p>
					<p style="margin-left: 5px;">
						CNPJ: {{ $config->cnpj }} <span style="margin-left: 39px;">IE: {{ $config->ie }}</span>
					</p>
					<p style="margin-left: 5px;">
						CEP: {{ $config->cep }} <span style="margin-left: 15px;">FONE: {{$config->fone }}</span> <span style="margin-left: 15px;">DATA EMISSÃO: {{ $venda->data_registro }}</span>
					</p>
				</div>

			</tr>
		</table>
		<hr>

		@if($venda->cliente)
		<table style="margin-top: 10px">
			<thead>
				<tr class="striped">
					<th style="width: 100px;">Código</th>
					<th style="width: 220px">Razão social</th>
					<th style="width: 220px">Nome fantasia</th>
					<th style="width: 150px">Fone</th>
				</tr>
				<tr>
					<td>{{ $venda->cliente->id }}</td>
					<td>{{ $venda->cliente->razao_social }}</td>
					<td>{{ $venda->cliente->nome_fantasia }}</td>
					<td>{{ $venda->cliente->telefone }}/{{ $venda->cliente->celular }}</td>
				</tr>
			</thead>
		</table>

		<table style="margin-top: -5px">
			<thead>
				<tr class="striped">
					<th style="width: 220px;">Rua</th>
					<th style="width: 80px">Número</th>
					<th style="width: 120px">Bairro</th>
					<th style="width: 120px">Complemento</th>
					<th style="width: 150px">CPF/CNPJ</th>

				</tr>
				<tr>
					<td>{{ $venda->cliente->rua }}</td>
					<td>{{ $venda->cliente->numero }}</td>
					<td>{{ $venda->cliente->bairro }}</td>
					<td>{{ $venda->cliente->complemento }}</td>
					<td>{{ $venda->cliente->cpf_cnpj }}</td>

				</tr>
			</thead>
		</table>
		@endif

		<table style="margin-top: -5px">
			<thead>
				<tr class="striped">
					<th style="width: 120px;">Parcela</th>
					<th style="width: 130px">Valor</th>
					<th style="width: 200px">Vencimento</th>
					<th style="width: 240px">Tipo do pagamento</th>

				</tr>
			</thead>
			<tbody>
				@if(sizeof($venda->fatura) > 0)
				@foreach($venda->fatura as $key => $f)
				<tr>
					<td>{{ $key+1 }}/{{ sizeof($venda->fatura) }}</td>
					<td>{{ moeda($f->valor) }}</td>
					<td>{{ __date($f->data_vencimento, 0) }}</td>
					<td>{{ \App\Models\VendaCaixa::getTipoPagamento($f->forma_pagamento) }}</td>
				</tr>
				@endforeach
				@else
				<tr>
					<td>1/1</td>
					<td>{{ moeda($venda->valor_total) }}</td>
					<td>{{ __date($venda->created_at) }}</td>
					<td>{{ \App\Models\VendaCaixa::getTipoPagamento($venda->tipo_pagamento) }}</td>
				</tr>
				@endif
			</tbody>
		</table>

		<table style="margin-top: 5px">
			<thead>
				<tr class="striped">
					<th style="width: 80px;">Código</th>
					<th style="width: 250px">Produto</th>
					<th style="width: 120px">Vl. unit.</th>
					<th style="width: 120px">Quantidade</th>
					<th style="width: 120px">Subtotal</th>

				</tr>
			</thead>
			<tbody>
				@foreach($venda->itens as $key => $p)
				<tr>
					<td>{{ $p->produto_id }}</td>
					<td>{{ $p->produto->nome }}</td>
					<td>{{ moeda($p->valor) }}</td>
					<td>{{ ($p->produto->unidade_venda == 'UN' || $p->produto->unidade_venda == 'UNID') ? number_format($p->quantidade, 0) : number_format($p->quantidade, 2) }}</td>
					<td>{{ moeda($p->valor * $p->quantidade) }}</td>

				</tr>
				@endforeach
			</tbody>
		</table>

		<h6 style="font-size: 8px;">Outras informações</h6>
		<table style="margin-top: -15px">
			<thead>
				<tr class="striped">
					<th style="width: 120px;">Desconto</th>
					<th style="width: 120px">Acréscimo</th>
					<th style="width: 270px">Observação</th>
					<th style="width: 90px">Nº NFCe</th>
					<th style="width: 90px">Troco</th>

				</tr>
			</thead>
			<tbody>
				<tr>
					<td>{{ moeda($venda->desconto) }}</td>
					<td>{{ moeda($venda->acrescimo) }}</td>
					<td>{{ $venda->observacao }}</td>
					<td>{{ $venda->NFcNumero ? $venda->NFcNumero : '' }}</td>
					<td>{{ $venda->troco }}</td>
				</tr>
			</tbody>
		</table>

		<div class="total">
			<h3>TOTAL <strong>R$ {{ moeda($venda->valor_total) }}</strong></h3>
		</div>

		<div class="assinatura">
			<div style="width: 250px; height: 50px">
				<p>Data: ___/___/______ às ___:___</p>
			</div>
			<div style="width:280px; height: 50px">
				<p style="margin-top: 10px">__________________________________________</p>
				<p style="text-align: center;">Nome por extenso</p>
			</div>
		</div>

	</div>
</body>
</html>