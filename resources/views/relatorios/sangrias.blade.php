@extends('relatorios.default')
@section('content')

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th class="text-left">Data</th>
		<th class="text-left">Usuário</th>
		<th class="text-left">Valor</th>
		<th class="text-left">Observação</th>
	</tr>
</thead>

<tbody>
	@foreach($data as $item)
	<tr>
		<td>{{ __date($item->created_at) }}</td>
		<td>{{ $item->usuario->nome }}</td>
		<td>{{ moeda($item->valor) }}</td>
		<td>{{ $item->observacao }}</td>
		
	</tr>
	
	@endforeach

</tbody>
</table>
<p>Total: <strong>{{ moeda($data->sum('valor')) }}</strong></p>

@endsection
