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
				<td class="" style="width: 250px;">
					<img src="{{'data:image/png;base64,' . base64_encode(file_get_contents(@public_path('logos/').$config->logo))}}" width="100px;">
				</td>
				@else
				<td class="" style="width: 250px;">
					<img src="{{'data:image/png;base64,' . base64_encode(file_get_contents(@public_path('imgs/slym.png')))}}" width="100px;">
				</td>
				@endif

				<td class="" style="width: 400px;">
					<center><label class="titulo">Retenções</label></center>
				</td>
			</tr>
		</table>

		<table>
		<tr>
			<td class="" style="width: 1025px;">
				<strong>Dados da empresa</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top" style="width: 500px;">
				Razão social: <strong>{{$config->razao_social}}</strong>
			</td>
			<td class="b-top" style="width: 525px;">
				Documento: <strong>{{$config->cnpj}}</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top" style="width: 1025px;">
				Endereço: <strong>{{$config->logradouro}}, {{$config->numero}} - {{$config->bairro}} - {{$config->municipio}} ({{$config->UF}})</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-top b-bottom" style="width: 400px;">
				Complemento: <strong>{{$config->complemento}}</strong>
			</td>
			<td class="b-top b-bottom" style="width: 300px;">
				CEP: <strong>{{$config->cep}}</strong>
			</td>
			<td class="b-top b-bottom" style="width: 325px;">
				Telefone: <strong>{{$config->fone}}</strong>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td class="b-bottom" style="width: 1025px;">
				Email: <strong>{{$config->email}}</strong>
			</td>

		</tr>
	</table>

	<table>
		<tr>
			<td class="b-bottom" style="width: 1025px; height: 50px;">
				<strong>Registros:</strong>
			</td>
		</tr>
	</table>

	<table>
		<thead>
			<tr>
				<td class="" style="width: 320px;">
					Fornecedor
				</td>
				<td class="" style="width: 120px;">
					Data de cadastro
				</td>
				<td class="" style="width: 100px;">
					Valor à pagar
				</td>
				<td class="" style="width: 80px;">
					INSS
				</td>
				<td class="" style="width: 80px;">
					ISS
				</td>
				<td class="" style="width: 80px;">
					PIS
				</td>
				<td class="" style="width: 70px;">
					COFINS
				</td>
				<td class="" style="width: 70px;">
					IR
				</td>
				<td class="" style="width: 70px;">
					Outras retenções
				</td>
			</tr>
		</thead>


		<tbody>

			@foreach($data as $item)
			<tr>
				<td>{{ $item->fornecedor->razao_social }}</td>
				<td>{{ __date($item->created_at) }}</td>
				<td>{{ moeda($item->valor_integral) }}</td>
				<td>{{ moeda($item->valor_inss) }}</td>
				<td>{{ moeda($item->valor_iss) }}</td>
				<td>{{ moeda($item->valor_pis) }}</td>
				<td>{{ moeda($item->valor_cofins) }}</td>
				<td>{{ moeda($item->valor_ir) }}</td>
				<td>{{ moeda($item->outras_retencoes) }}</td>
			</tr>

			@endforeach
		</tbody>
        <tfoot>
            <tr>
                <td class="b-top" colspan="2"></td>
                <td class="b-top">{{ moeda($data->sum('valor_integral')) }}</td>
                <td class="b-top">{{ moeda($data->sum('valor_inss')) }}</td>
                <td class="b-top">{{ moeda($data->sum('valor_iss')) }}</td>
                <td class="b-top">{{ moeda($data->sum('valor_pis')) }}</td>
                <td class="b-top">{{ moeda($data->sum('valor_cofins')) }}</td>
                <td class="b-top">{{ moeda($data->sum('valor_ir')) }}</td>
                <td class="b-top">{{ moeda($data->sum('outras_retencoes')) }}</td>
            </tr>
        </tfoot>
        @php
            $total_integral = $data->sum('valor_integral');
            $total_retencoes = $data->sum('valor_inss') + $data->sum('valor_iss') + $data->sum('valor_pis') + $data->sum('valor_cofins') + $data->sum('valor_ir') + $data->sum('outras_retencoes');
            $total_liquido = $total_integral - $total_retencoes;
            if ($total_liquido < 0) $total_liquido = 0;
        @endphp
        <tr>
            <td class="b-top" colspan="2"><strong>Totais Finais</strong></td>
            <td class="b-top">{{ moeda($total_integral) }}</td>
            <td class="b-top" colspan="6"><strong>Retenções: {{ moeda($total_retencoes) }} &nbsp; | &nbsp; Valor Líquido Pago: {{ moeda($total_liquido) }}</strong></td>
        </tr>

    </table>

</body>
</html>
