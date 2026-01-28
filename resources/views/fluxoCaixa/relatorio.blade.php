@extends('relatorios.default')
@section('content')

<style type="text/css">
	tfoot td{
		border-top: 1px solid #999;
		font-weight: bold;
	}
</style>
<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th width="10%" class="text-left">Data</th>
		<th width="40%" class="text-left">Vendas</th>
		<th width="12%" class="text-left">Contas a receber</th>
		<th width="12%" class="text-left">Conta crédito</th>
		<th width="12%" class="text-left">Conta a pagar</th>
		<th width="13%" class="text-left">Resultado</th>
	</tr>
</thead>

<tbody>
	@php 
	$totalVenda = 0;
	$totalContaReceber = 0;
	$totalContaPagar = 0;
	$totalCredito = 0;
	$totalResultado = 0; 
	@endphp 

	@foreach($fluxo as $key => $f)
	<tr class="@if($key%2 == 0) pure-table-odd @endif">
		<td class="text-left">{{$f['data']}}</td>
		<td class="text-left"><label>Vendas: R$ {{number_format($f['venda'], 2, ',', '.')}}</label><br>
			<label>Frente de caixa: R$ {{number_format($f['venda_caixa'], 2, ',', '.')}}</label>
			<h5>Total R$ {{number_format($f['venda']+$f['venda_caixa'], 2, ',', '.')}}</h5>
		</td>
		<td class="text-left">R$ {{number_format($f['conta_receber'], 2, ',', '.')}}</td>
		<td class="text-left">R$ {{number_format($f['credito_venda'], 2, ',', '.')}}</td>
		<td class="text-left">R$ {{number_format($f['conta_pagar'], 2, ',', '.')}}</td>
		<?php 
		$resultado = $f['credito_venda']+$f['conta_receber']+$f['venda_caixa']+$f['venda']-$f['conta_pagar'];
		?>
		<td class="text-left">
			@if($resultado > 0)
			<h5 class="text-success"> R$ {{number_format($resultado, 2, ',', '.')}}</h5>
			@elseif($resultado == 0)
			<h5 class="text-primary"> R$ {{number_format($resultado, 2, ',', '.')}}</h5>
			@else
			<h5 class="text-danger"> R$ {{number_format($resultado, 2, ',', '.')}}</h5>
			@endif
		</td>

		@php 
		$totalVenda += $f['venda']+$f['venda_caixa'];
		$totalContaReceber += $f['conta_receber'];
		$totalContaPagar += $f['conta_pagar'];
		$totalCredito += $f['credito_venda'];
		$totalResultado += $resultado; 
		@endphp
	</tr>


	@endforeach

</tbody>

</table>

@endsection
