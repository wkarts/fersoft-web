@extends('relatorios.default')
@section('content')
<h5>Empresa: {{ $empresa->nome }}</h5>
@if($data_inicial)
<h5>Data inicial: {{$data_inicial}} - Data final: {{$data_final}}</h5>
@endif
<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>

		<th width="20%" class="text-left">Data</th>
		<th width="20%" class="text-left">Usuário</th>
		<th width="10%" class="text-left">Tabela</th>
		<th width="10%" class="text-left">Registro</th>
		<th width="20%" class="text-left">Tipo</th>
	</tr>
</thead>

@foreach($data as $key => $e)
<tr class="@if($key%2 == 0) pure-table-odd @endif">
	<td>{{\Carbon\Carbon::parse($e->created_at)->format('d/m/Y H:i')}}</td>
	<td>{{$e->usuario->id}} - {{$e->usuario->nome}}</td>
	<td>{{ strtoupper($e->tabela) }}</td>
	<td>{{ $e->registro() }}</td>
	<td>{{ strtoupper($e->tipo) }}</td>
</tr>
@endforeach

@endsection
