
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

		/*.b-top{
			border-top: 1px solid #000; 
		}
		.b-bottom{
			border-bottom: 1px solid #000; 
		}*/
		.page_break { page-break-before: always; }
		td{
			font-size: 12px;
			text-align: left;
		}

		th{
			font-size: 12px;
			text-align: left;
		}
		.divider{
			width: 100%;
			height: 1px;
			background: #000;
		}
		table{
			line-height: 0.8;
		}
	</style>

</head>
<body>
	<div class="content">
		<table>
			<tr>

				@if($config->logo != "")
				<td class="" style="width: 150px;">
					<img src="{{'data:image/png;base64,' . base64_encode(file_get_contents(@public_path('logos/').$config->logo))}}" width="100px;">
				</td>
				@else
				<td class="" style="width: 150px;">
					<img src="{{'data:image/png;base64,' . base64_encode(file_get_contents(@public_path('imgs/slym.png')))}}" width="100px;">
				</td>
				@endif

				<td class="" style="width: 400px;">
					<center><label class="titulo">PRÉ VENDA</label></center>
				</td>
			</tr>
		</table>

	</div>
	<br>
	<div class="divider"></div>
	<br>
	@if($venda->cliente)
	<table style="margin-top: -15px">
		<tr>
			<td class="" style="width: 700px;">
				<strong>Dados do cliente</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top" style="width: 450px;">
				Nome: <strong>{{$venda->cliente->razao_social}}</strong>
			</td>
			<td class="b-top" style="width: 247px;">
				CPF/CNPJ: <strong>{{$venda->cliente->cpf_cnpj}}</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top" style="width: 500px;">
				Endereço: <strong>{{$venda->cliente->rua}}, {{$venda->cliente->numero}} - {{$venda->cliente->bairro}} - {{$venda->cliente->cidade->nome}} ({{$venda->cliente->cidade->uf}})</strong>
			</td>

			<td class="b-top" style="width: 200px;">
				CEP: <strong>{{$venda->cliente->cep}}</strong>
			</td>
		</tr>
	</table>

	<table>
		<tr>
			<td class="b-top" style="width: 300px;">
				Complemento: <strong>{{$venda->cliente->complemento }}</strong>
			</td>

			<td class="b-top" style="width: 200px;">
				Telefone: <strong>{{$venda->cliente->telefone}}</strong>
			</td>
			<td class="b-top" style="width: 200px;">
				Celular: <strong>{{$venda->cliente->celular}}</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top" style="width: 700px;">
				Email: <strong>{{$venda->cliente->email}}</strong>
			</td>
			
		</tr>
	</table>
	@endif

	<table>
		<tr>
			<td class="b-top" style="width: 350px;">
				Nº Doc: <strong>{{$venda->id}}</strong>
			</td>
			<td class="b-top" style="width: 347px;">

			</td>
		</tr>
	</table>

	<div class="divider"></div>

	<table>
		<tr>
			<td class="b-top b-bottom" style="width: 700px; height: 50px;">
				<strong>MERCADORIAS:</strong>
			</td>
		</tr>
	</table>	

	<table style="margin-top: -20px">
		<thead>
			<tr>
				<td class="" style="width: 95px;">
					Cod/Ref
				</td>
				<td class="" style="width: 350px;">
					Descrição
				</td>
				<td class="" style="width: 80px;">
					Qtd.
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
		$tipoDimensao = false;
		$tipoReceita = false;
		@endphp
		<tbody>
			@foreach($venda->itens as $i)
			<tr>
				<th class="b-top">{{$i->produto->id}} {{$i->produto->referencia != "" ? "/ " . $i->produto->referencia : "" }}</th>
				<th class="b-top">
					{{$i->produto->nome}}
					{{$i->produto->grade ? " (" . $i->produto->str_grade . ")" : ""}}
					@if($i->produto->lote != "")
					| Lote: {{$i->produto->lote}}, 
					Vencimento: {{$i->produto->vencimento}}
					@endif
				</th class="b-top">
				<th class="b-top">
					{{number_format($i->quantidade, $casasDecimaisQtd, ',', '.')}}
					
				</th>
				<th class="b-top">{{number_format($i->valor, $casasDecimais, ',', '.')}}</th>
				<th class="b-top">{{number_format($i->quantidade * $i->valor, $casasDecimais, ',', '.')}}</th>
			</tr>
			@php
			$somaItens += $i->quantidade;
			$somaTotalItens += $i->quantidade * $i->valor;
			if($i->altura > 0 || $i->esquerda > 0){
				$tipoDimensao = true;
			}

			if($i->produto->receita){
				$tipoReceita = true;
			}
			@endphp

			@endforeach
		</tbody>
	</table>
	<br>

	<table>
		<tr>
			<td class="b-top b-bottom" style="width: 350px;">
				<center><strong>Quantidade Total: {{$somaItens}}</strong></center>
			</td>
			<td class="b-top b-bottom" style="width: 350px;">
				<center><strong>Valor Total dos Itens: 
					{{number_format($somaTotalItens, $casasDecimais, ',', '.')}}
				</strong></center>
			</td>
		</tr>
	</table>

	<div class="divider"></div>

	<table style="margin-top: 5px">
		<tr>
			<td class="" style="width: 233px;">
				Forma de pagamento: <strong>{{ App\Models\VendaCaixa::getTipoPagamento($venda->tipo_pagamento) }}</strong>
			</td>

			<td class="" style="width: 233px;">
				Vendedor: <strong>
					{{$venda->vendedor()}}
				</strong>
			</td>
			<td class="" style="width: 233px;">
				Data: <strong>{{\Carbon\Carbon::parse($venda->created_at)->format('d/m/Y H:i')}}</strong>
			</td>
		</tr>
	</table>
	
	<table>
		<tr>
			
			@if($venda->vendedor_id)
			<td class="" style="width: 250px;">
				Vendedor: <strong>{{ $venda->vendedor_setado->funcionario->nome }}</strong>
			</td>
			@endif
			
		</tr>
	</table>

	<table>
		<tr>
			<td class="" style="width: 233px;">
				Desconto (-):
				<strong> 
					{{number_format($venda->desconto, 2, ',', '.')}}
				</strong>
			</td>

			<td class="" style="width: 233px;">
				Acrescimo (+):
				<strong> 
					{{number_format($venda->acrescimo, 2, ',', '.')}}
				</strong>
			</td>

			<td class="" style="width: 233px;">
				Valor Líquido:
				<strong> 
					{{number_format($venda->valor_total - $venda->desconto + $venda->acrescimo, $casasDecimais, ',', '.')}}
				</strong>
			</td>
			
		</tr>
	</table>

	@if($venda->observacao != "" || $config->campo_obs_pedido != "")
	<table>
		<tr>
			<td class="" style="width: 700px;">
				<span>Observação: 
					<strong>{{$config->campo_obs_pedido}}
						{{$venda->observacao}}
					</strong>
				</span>
			</td>
		</tr>
	</table>
	@endif

	<br>
	<table>
		<tr>
			
			<td class="" style="width: 350px;">
				<strong>
					________________________________________
				</strong><br><br>
				@if($venda->cliente)
				<span style="font-size: 11px;">{{$venda->cliente->razao_social}}</span>
				@else
				<span style="font-size: 11px;">Assinatura</span>
				@endif
			</td>
		</tr>
	</table>

	
</body>
</html>