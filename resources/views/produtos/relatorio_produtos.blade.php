@extends('relatorios.default')
@section('content')

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th width="45%" class="text-left">Produto</th>
		<th width="10%" class="text-left">Estoque</th>
		<th width="15%" class="text-left">Categoria</th>
		<th width="15%" class="text-left">Valor de custo</th>
		<th width="15%" class="text-left">Valor de venda</th>
	</tr>
</thead>

@foreach($produtos as $key => $p)
<tr class="@if($key%2 == 0) pure-table-odd @endif">
	<td>{{$p->nome}} {{$p->str_grade}}</td>
	@if($p->estoque)
	<td>{{number_format($p->estoque->quantidade, 2, ',', '.')}}</td>
	@else
	<td>--</td>
	@endif

	<td>{{$p->categoria->nome}}</td>
	@if($p->estoque)
	<td>{{number_format($p->valor_compra*$p->estoque->quantidade, 2, ',', '.')}}</td>
	<td>{{number_format($p->valor_venda*$p->estoque->quantidade, 2, ',', '.')}}</td>
	@else
	<td>--</td>
	<td>--</td>
	@endif
</tr>
@endforeach

</table>



@endsection
