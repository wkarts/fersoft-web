@extends('relatorios.default')
@section('content')

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>

		<th width="20%" class="text-left">Razão social</th>
		<th width="20%" class="text-left">CPF/CNPJ</th>
		<th width="20%" class="text-left">Nome responsável</th>
		<th width="20%" class="text-left">Telefone</th>
		<th width="20%" class="text-left">Contador</th>
		<th width="10%" class="text-left">Plano</th>
		<th width="10%" class="text-left">Vencimento</th>
		<th width="20%" class="text-left">Valor do plano</th>
	</tr>
</thead>

@foreach($data as $key => $item)
<tr class="@if($key%2 == 0) pure-table-odd @endif">

	<td>{{$item->empresa->nome}}</td>
	<td>{{$item->empresa->cnpj}}</td>
	<td>{{$item->empresa->usuarioFirst->nome}}</td>
	<td>{{$item->empresa->telefone}}</td>
	<td>{{$item->empresa->contador ? $teim->empresa->contador->razao_social : ''}}</td>
	<td>{{$item->plano->nome}}</td>
	<td>{{ __date($item->expiracao, 0) }}</td>
	<td>{{ moeda($item->plano->valor) }}</td>

</tr>
@endforeach

@endsection
