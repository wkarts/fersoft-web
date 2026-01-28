@extends('relatorios.default')
@section('content')
<style type="text/css">
	.b-top{
		border-top: 1px solid #000; 
	}
	.b-bottom{
		border-bottom: 1px solid #000; 
	}
</style>

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th width="5%" class="text-left">Id</th>
		<th width="20%" class="text-left">Nome</th>
		<th width="35%" class="text-left">Endereço</th>
		<th width="15%" class="text-left">Cidade</th>
		<th width="15%" class="text-left">Plano</th>
		<th width="10%" class="text-left">Status</th>
	</tr>
</thead>
@php
$soma = 0;
@endphp
@foreach($empresas as $key => $e)
<tr class="@if($key%2 == 0) pure-table-odd @endif">
	<td>{{$e->id}}</td>
	<td>{{$e->nome}}</td>
	<td>{{$e->rua}}, {{$e->numero}} - {{$e->bairro}}</td>
	<td>{{$e->cidade}}</td>
	@if($e->planoEmpresa)
	<td>{{$e->planoEmpresa->plano->nome}} R$ {{number_format($e->planoEmpresa->plano->valor, 2, ',', '.')}}
		@php
		$soma += $e->planoEmpresa->getValor();
		@endphp
	</td>
	@else
	<td>--</td>
	@endif
	<td>
		@if($e->status() == -1)
		MASTER
		@elseif($e->status())
		ATIVO
		@else
		DESATIVADO
		@endif
	</td>

</tr>

@endforeach

<table style="width: 100%;">
	<tbody>
		<tr class="text-left">

			<th width="50%">Total de empresas: {{ sizeof($empresas) }}</th>
			<th width="50%">Soma: R$ {{ number_format($soma, 2, ',', '.') }}</th>
		</tr>
	</tbody>
</table>


@endsection

