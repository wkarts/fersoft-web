@extends('relatorios.default')
@section('content')

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th class="text-left">Cliente</th>
		<th class="text-left">Data Inicial</th>
		<th class="text-left">Data Final</th>
		<th class="text-left">Valor</th>
	</tr>
</thead>

<tbody>
	@foreach($data as $item)
	<tr>
		<td>{{ $item->cliente->razao_social }}</td>
		<td>{{ __date($item->inicio, 0) }}</td>
		<td>{{ __date($item->fim, 0) }}</td>
		<td>{{ moeda($item->total) }}</td>
		
	</tr>
	@foreach($item->itens as $it)
	<tr>
		<td>Produto: <strong>{{ $it->produto->nome }}</strong></td>
	</tr>
	@endforeach
	@endforeach
</tbody>
</table>

@endsection
