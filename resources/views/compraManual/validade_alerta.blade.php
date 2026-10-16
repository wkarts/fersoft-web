@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-modules.css') }}?v={{ filemtime(public_path('css/fer-modules.css')) }}">
@endpush

@section('content')
<header class="fer-page__heading"><h1>Alerta de Validade</h1></header>

<form class="row fer-page fer-detail" action="/compras/salvarValidade" method="post">

	@csrf
	<div class="col s12">
		@if(session()->has('message'))
		<div class="row">
			<div style="border-radius: 10px;" class="col s12 {{ session('color') }}">
				<h5 class="center-align white-text">{{ session()->get('message') }}</h5>
			</div>
		</div>
		@endif
		<p class="red-text">*Datas com formato inválido serão desconsideradas</p>
		<input type="hidden" name="tamanho_array" value="{{sizeof($itens)}}">

		<table class="striped col s12">
			<thead>
				<tr>
					<th>#</th>
					<th>Produto</th>
					<th>Fornececor</th>
					<th>Data da Compra</th>
					<th>Valor Unit.</th>
					<th>Quantidade</th>
					<th>Validade</th>

				</tr>
			</thead>


			<tbody>
				@foreach($itens as $key => $i)
				<tr>
					<td>{{$i->id}}</td>
					<td class="red-text">{{$i->produto->nome}}</td>
					<td>{{$i->compra->fornecedor->razao_social}}</td>
					<td>{{$i->produto->nome}}</td>
					<td>{{number_format($i->valor_unitario, 2)}}</td>
					<td>{{number_format($i->quantidade, 2)}}</td>
					<td class="red-text">
						{{ \Carbon\Carbon::parse($i->validade)->format('d/m/Y')}}
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
		
	</div>
	
	
	
</form>
@endsection	