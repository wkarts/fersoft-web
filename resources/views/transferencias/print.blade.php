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
		thead td{
			font-weight: bold;
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
					<center><label class="titulo">TRANSFERÊNCIA DE ESTOQUE</label></center>
				</td>
			</tr>
		</table>

	</div>
	<br>
	
	<table>
		<tr>
			<td class="b-top" style="width: 350px;">
				Origem: <strong>{{ $item->filial_saida ? $item->filial_saida->descricao : 'Matriz' }}</strong>
			</td>
			<td class="b-top" style="width: 350px;">
				Destino: <strong>{{ $item->filial_entrada ? $item->filial_entrada->descricao : 'Matriz' }}</strong>
			</td>
		</tr>
	</table>

	<table>
		<tr>
			<td class="b-top b-bottom" style="width: 700px; height: 50px;">
				<strong>Itens:</strong>
			</td>
		</tr>
	</table>	

	<table>
		<thead>
			<tr>
				<td class="" style="width: 400px;">
					Produto
				</td>
				<td class="" style="width: 100px;">
					Quantidade
				</td>
				<td class="" style="width: 100px;">
					Vl. venda
				</td>
				<td class="" style="width: 100px;">
					Vl. compra
				</td>
			</tr>
		</thead>

		
		<tbody>

			@foreach($item->itens as $p)
			<tr>
				<td>{{ $p->produto->nome }}</td>
				<td>{{ $p->quantidade }}</td>
				<td>{{number_format($p->produto->valor_venda, $casasDecimais, ',', '.')}}</td>
				<td>{{number_format($p->produto->valor_compra, $casasDecimais, ',', '.')}}</td>
			</tr>
			
			@endforeach
		</tbody>
	</table>
	

	
	<br>
	<table>
		<tr>
			<td class="" style="width: 700px;">
				Observação: <strong>{{ $item->observacao }}</strong>
			</td>
		</tr>
	</table>
	
</table>
<table>
	<tr>
		<td class="" style="width: 250px;">
			Data: <strong>{{\Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i')}}</strong>
		</td>


	</tr>
</table>



</body>
</html>