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
		th{
			font-size: 13px;
		}

	</style>

</head>
<body>
	<div class="content">
		@if($config->logo != "")
		<table>
			<tr>
				<td class="" style="width: 150px;">
					<img src="{{'data:image/png;base64,' . base64_encode(file_get_contents(@public_path('logos/').$config->logo))}}" width="100px;">
				</td>

				<td class="" style="width: 550px;">
					<center><label class="titulo">RELAÓRIO ALERTA DE ESTOQUE</label></center>
				</td>
			</tr>
		</table>
		@else
		<center><label class="titulo">RELAÓRIO ALERTA DE ESTOQUE</label></center>
		@endif
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
			<td class="b-bottom" style="width: 700px;">
				Telefone: <strong>{{$config->fone}}</strong>
			</td>
		</tr>
	</table>


	<table>
		<tr>
			<td class="b-bottom" style="width: 700px; height: 50px;">
				<strong>PRODUTOS:</strong>
			</td>
		</tr>
	</table>	


	<table>
		<thead>
			<tr>
				<td class="" style="width: 330px;">
					Produto
				</td>
				<td class="" style="width: 90px;">
					Estoque Atual
				</td>
				<td class="" style="width: 90px;">
					Valor de venda
				</td>
				
				<td class="" style="width: 90px;">
					Valor de compra
				</td>
				<td class="" style="width: 90px;">
					Data de cadastro
				</td>
				
			</tr>
		</thead>
		
		<tbody>
			@foreach($data as $item)
			<tr>
				<th class="b-top">
					{{ $item->nome }}
				</th>
				
				<th class="b-top">
					@if($item->estoque)
					{{ $item->estoque->quantidade }}
					@else
					0
					@endif
				</th>
				<th class="b-top">
					{{ moeda($item->valor_venda) }}
				</th>
				<th class="b-top">
					{{ moeda($item->valor_compra) }}
				</th>
				<th class="b-top">
					{{ __date($item->created_at, 1) }}
				</th>
			</tr>

			@endforeach
		</tbody>
	</table>
	<br>

	<table>
		<tr>
			<td class="b-top" style="width: 700px;">
			</td>

		</tr>
	</table>

	<table>
		<tr>
			<td class="" style="width: 350px;">
				Data do documento: <strong>{{date('d/m/Y H:i:s')}}</strong>
			</td>
			<td class="" style="width: 347px;">

			</td>
		</tr>
	</table>

	<table>
		<tr>

			<td class="" style="width: 350px;">
				Total de linhas:
				<strong> 
					{{sizeof($data)}}
				</strong>
			</td>
			
		</tr>
	</table>


</body>
</html>