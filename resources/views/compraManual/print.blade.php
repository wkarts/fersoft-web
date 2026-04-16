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
		.page_break { page-break-before: always; }

	</style>

</head>
<body>
	<div class="content">
		<table>
			<tr>

				@if($config->logo != "")
				<td class="" style="width: 150px;">
					<img src="{{'data:image/png;base64,' . base64_encode(safe_file_get_contents(@public_path('logos/').$config->logo))}}" width="100px;">
				</td>
				@else
				<td class="" style="width: 150px;">
					<img src="{{'data:image/png;base64,' . base64_encode(safe_file_get_contents(@public_path('imgs/slym.png')))}}" width="100px;">
				</td>
				@endif

				<td class="" style="width: 400px;">
					<center><label class="titulo">PEDIDO DE COMPRA</label></center>
				</td>
			</tr>
		</table>

	</div>
	<br>
	<table>
		<tr>
			<td class="" style="width: 700px;">
				<strong>Dados da empresa</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top" style="width: 500px;">
				Razão social: <strong>{{$config->razao_social}}</strong>
			</td>
			<td class="b-top" style="width: 197px;">
				CNPJ: <strong>{{$config->cnpj}}</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top" style="width: 700px;">
				Endereço: <strong>{{$config->logradouro}}, {{$config->numero}} - {{$config->bairro}} - {{$config->municipio}} ({{$config->UF}})</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top b-bottom" style="width: 300px;">
				Complemento: <strong>{{$config->complemento}}</strong>
			</td>
			<td class="b-top b-bottom" style="width: 200px;">
				CEP: <strong>{{$config->cep}}</strong>
			</td>
			<td class="b-top b-bottom" style="width: 200px;">
				Telefone: <strong>{{$config->fone}}</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-bottom" style="width: 700px;">
				Email: <strong>{{$config->email}}</strong>
			</td>

		</tr>
	</table>
	<br>
	<table>
		<tr>
			<td class="" style="width: 700px;">
				<strong>Dados do fornecedor</strong>
			</td>
		</tr>
	</table>

	<table>
		<tr>
			<td class="b-top" style="width: 450px;">
				Nome: <strong>{{$compra->fornecedor->razao_social}}</strong>
			</td>
			<td class="b-top" style="width: 247px;">
				CPF/CNPJ: <strong>{{$compra->fornecedor->cpf_cnpj}}</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top" style="width: 500px;">
				Endereço: <strong>{{$compra->fornecedor->rua}}, {{$compra->fornecedor->numero}} - {{$compra->fornecedor->bairro}} - {{$compra->fornecedor->cidade->nome}} ({{$compra->fornecedor->cidade->uf}})</strong>
			</td>

			<td class="b-top" style="width: 200px;">
				CEP: <strong>{{$compra->fornecedor->cep}}</strong>
			</td>
		</tr>
	</table>

	<table>
		<tr>
			<td class="b-top" style="width: 300px;">
				Complemento: <strong>{{$compra->fornecedor->complemento }}</strong>
			</td>

			<td class="b-top" style="width: 200px;">
				Telefone: <strong>{{$compra->fornecedor->telefone}}</strong>
			</td>
			<td class="b-top" style="width: 200px;">
				Celular: <strong>{{$compra->fornecedor->celular}}</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top" style="width: 700px;">
				Email: <strong>{{$compra->fornecedor->email}}</strong>
			</td>

		</tr>
	</table>

	<table>
		<tr>
			<td class="b-top" style="width: 350px;">
				Nº Doc: <strong>{{$compra->id}}</strong>
			</td>
			<td class="b-top" style="width: 347px;">

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
					Cod
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
			@foreach($compra->itens as $i)
			<tr>
				<th class="b-top">{{$i->produto->id}}</th>
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
					@if($i->largura > 0 || $i->esquerda > 0)
					<span style="font-size: 12px;">x{{number_format($i->quantidade_dimensao, 2, ',', '.')}}</span>
					@endif
				</th>
				<th class="b-top">{{number_format($i->valor_unitario, $casasDecimais, ',', '.')}}</th>
				<th class="b-top">{{number_format($i->quantidade * $i->valor_unitario, $casasDecimais, ',', '.')}}</th>

			</tr>
			@php
			$somaItens += $i->quantidade;
			$somaTotalItens += $i->quantidade * $i->valor_unitario;
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

	@if($compra->fatura()->exists())
	<table>
		<tr>
			<td class="b-bottom" style="width: 700px; height: 50px;">
				<strong>FATURA:</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-bottom" style="width: 150px;">
				Vencimento
			</td>
			<td class="b-bottom" style="width: 150px;">
				Valor
			</td>
		</tr>
		@foreach($compra->fatura as $key => $d)
		<tr>
			<td class="b-bottom">
				<strong>{{ \Carbon\Carbon::parse($d->data_vencimento)->format('d/m/Y')}}</strong>
			</td>
			<td class="b-bottom">
				<strong>{{number_format($d->valor_integral, $casasDecimais, ',', '.')}}</strong>
			</td>
		</tr>
		@endforeach
	</table>
	@endif
	<br>
	<table>
		<tr>

		</tr>
	</table>
	<table>
		<tr>
			<td class="" style="width: 350px;">
				Vendedor: <strong>
					{{$compra->usuario->nome}}
				</strong>
			</td>
			<td class="" style="width: 350px;">
				Data da venda: <strong>{{\Carbon\Carbon::parse($compra->created_at)->format('d/m/Y H:i')}}</strong>
			</td>
		</tr>
	</table>

	<table>
		<tr>
			<td class="" style="width: 170px;">
				Desconto (-):
				<strong>
					{{number_format($compra->desconto, 2, ',', '.')}}
				</strong>
			</td>


			<td class="" style="width: 170px;">
				Frete (+):
				<strong> {{number_format($compra->valor_frete, 2, ',', '.')}}</strong>
			</td>

			<td class="" style="width: 200px;">
				Valor Líquido:
				<strong>
					{{number_format($compra->valor - $compra->desconto, $casasDecimais, ',', '.')}}
				</strong>
			</td>

		</tr>
	</table>

	@if($compra->observacao != "")
	<table>
		<tr>
			<td class="" style="width: 700px;">
				<span>Observação:
					<strong>
						{{$compra->observacao}}
					</strong>
				</span>
			</td>
		</tr>
	</table>
	@endif

	<br><br><br>
	<table>
		<tr>
			<td class="" style="width: 350px;">
				<strong>
					________________________________________
				</strong><br>
				<span style="font-size: 11px;">{{$config->razao_social}}</span>

			</td>

			<td class="" style="width: 350px;">
				<strong>
					________________________________________
				</strong><br>
				<span style="font-size: 11px;">{{$compra->fornecedor->razao_social}}</span>
			</td>
		</tr>
	</table>


</body>
</html>
