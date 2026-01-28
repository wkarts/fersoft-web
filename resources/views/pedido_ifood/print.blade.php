<!DOCTYPE html>
<html>
<head>
	<title></title>
	<!--  -->

	<style type="text/css">

		.content{
			margin-top: -30px;
		}
		.titulo{
			font-size: 20px;
			margin-bottom: 0px;
			font-weight: bold;
		}

		.b-top{
			border-top: 1px solid #000; 
		}
		.b-bottom{
			border-bottom: 1px solid #000; 
		}

	</style>

</head>
<body>
	<div class="content">

		<center><label class="titulo">DOCUMENTO AUXILIAR DE VENDA - PEDIDO</label></center>
		<center><label class="titulo">NÃO É DOCUMENTO FISCAL</label></center>
		<center><label class="titulo">NÃO COMPROVA PAGAMENTO</label></center>

	</div>
	<br>
	<table>
		<tr>
			<td class="" style="width: 700px;">
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
				CNPJ: <strong>{{ str_replace(" ", "", $config->cnpj)}}</strong>
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
			<td class="" style="width: 700px;">
				<strong>Identificação do Destinatário</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top" style="width: 700px;">
				Nome: <strong>{{$pedido->nome_cliente}}</strong> -
				<strong style="color: #AA81FB;  font-size: 12px;">#{{$pedido->id_cliente}}</strong>
			</td>
		</tr>
	</table>

	<table>
		<tr>
			<td class="b-top" style="width: 700px;">
				Telefone: <strong>{{$pedido->telefone_cliente}}</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top" style="width: 500px;">
				Endereço: <strong>{{ $pedido->endereco }}, {{ $pedido->bairro }} </strong>
			</td>
			<td class="b-top" style="width: 200px;">
				CEP: <strong>{{ $pedido->cep }}</strong>
			</td>
		</tr>
	</table>
	

	<table>
		<tr>
			<td class="b-top" style="width: 400px;">
				<strong style="color: #AA81FB; font-size: 12px;">#{{$pedido->pedido_id}}</strong>
			</td>
			<td class="b-top" style="width: 300px;">
				Tipo: <strong>{{$pedido->tipo_pedido}}</strong>
			</td>

		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top b-bottom" style="width: 700px; height: 50px;">
				<strong>MERCADORIAS:</strong>
			</td>
		</tr>
	</table>	


	<table>
		<thead>
			<tr>
				<td class="" style="width: 95px;">
					
				</td>
				<td class="" style="width: 350px;">
					Descrição
				</td>
				<td class="" style="width: 80px;">
					Quant.
				</td>
				<td class="" style="width: 80px;">
					Vl Uni
				</td>
				<td class="" style="width: 80px;">
					Vl Liq.
				</td>
			</tr>
		</thead>
		@php
		$somaItens = 0;
		$somaTotalItens = 0;
		@endphp
		<tbody>
			@foreach($pedido->itens as $i)
			<tr>
				<th class="b-top" align="left">
					@if($i->image_url != "")
					<img style="width: 40px; border-radius: 5px" src="{{ $i->image_url }}">
					@else
					<img style="width: 40px; border-radius: 5px" src="{{ url('/imgs/no_image.png') }}">
					@endif
				</th>
				<th class="b-top" align="left">
					{{ $i->nome_produto }}
				</th class="b-top" align="left">
				<th class="b-top" align="left">{{number_format($i->quantidade, 2, ',', '.')}}</th>
				<th class="b-top" align="left">{{number_format($i->valor_unitario, 2, ',', '.')}}</th>
				<th class="b-top" align="left">{{number_format($i->total, 2, ',', '.')}}</th>

			</tr>
			@php
			$somaItens += $i->quantidade;
			@endphp

			@endforeach
		</tbody>
	</table>
	<br>

	<table>
		<tr>
			<td class="b-top b-bottom" style="width: 350px;">
				<center><strong>Quantidade de itens: {{$somaItens}}</strong></center>
			</td>

			<td class="b-top b-bottom" style="width: 350px;">
				<center><strong>Valor Total dos Itens: 
					{{number_format($pedido->valor_produtos, 2, ',', '.')}}
				</strong></center>
			</td>
		</tr>
	</table>
	<br>

	<table>
		<tr>
			
			<td class="" style="width: 250px;">
				Valor entrega (+):
				<strong> 
					{{number_format($pedido->valor_entrega, 2, ',', '.')}}
				</strong>
			</td>

			<td class="" style="width: 250px;">
				Taxas adicionais (+):
				<strong> 
					{{number_format($pedido->taxas_adicionais, 2, ',', '.')}}
				</strong>
			</td>

			<td class="" style="width: 200px;">
				Valor Total:
				<strong> 
					{{number_format($pedido->valor_total, 2, ',', '.')}}
				</strong>
			</td>
			
		</tr>
	</table>

	@if(sizeof($pedido->payments) > 0)
	<br>
	<table>
		<thead>
			<tr>
				<td class="" style="width: 233px;">
					Forma de pagamento
				</td>
				<td class="" style="width: 233px;">
					Tipo de pagamento
				</td>
				<td class="" style="width: 233px;">
					Valor
				</td>
				
			</tr>
		</thead>
		
		<tbody>
			@foreach($pedido->payments as $p)
			<tr>
				
				<th class="b-top" align="left">
					{{ $p->forma_pagamento }}
				</th>

				<th class="b-top" align="left">
					{{ $p->tipo_pagamento }}
				</th>

				<th class="b-top" align="left">{{number_format($p->valor, 2, ',', '.')}}</th>
			</tr>

			@endforeach
		</tbody>
	</table>
	@endif

</body>
</html>