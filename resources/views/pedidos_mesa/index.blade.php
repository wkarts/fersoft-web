@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="row">

			<form method="get" class="mt-3">
				<div class="row align-items-center col-12">

					<div class="form-group col-lg-8 mt-5">
						<input value="{{isset($nome) ? $nome : ''}}" type="" name="nome" class="form-control" placeholder="Nome cliente">
					</div>

					<div class="col-lg-2">
						<button class="btn btn-light-primary px-6 mt-0 mb-2 font-weight-bold">Filtrar</button>
					</div>
				</div>

			</form>

			<hr>
			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
				@if(count($data) > 0)

				<div class="row">
					@foreach($data as $item)
					<div class="col-sm-4 col-lg-4 col-md-6">

						<div class="card card-custom gutter-b">
							<div class="card-header text-white">
								<h3 style="margin-top: 20px;">Cliente: <strong>{{ $item->nome_cliente }}</strong></h3>
							</div>

							<div class="card-body" style="height: 180px;">
								<h5>Total: <strong class="text-info">R$ {{ moeda($item->somaItens()) }}</strong></h5>
								<h5>Total de itens: <strong class="text-info">{{ sizeof($item->itens) }}</strong></h5>
								<h5>Horário abertura: <strong class="text-info">{{ \Carbon\Carbon::parse($item->data_registro)->format('H:i')}}</strong></h5>
								@if($item->estado == 'aberto')
								<label class="btn btn-sm btn-success">ABERTO</label>
								@elseif($item->estado == 'fechado')
								<button class="btn btn-sm btn-light-warning">FECHADO</button>
								<a class="btn btn-danger btn-sm" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/pedidosMesa/delete/{{ $item->id }}" }else{return false} })' href="#!">remover</a>
								<a class="btn btn-success btn-sm float-right" href="/pedidosMesa/liberar/{{$item->id}}">liberar</a>
								@endif
							</div>

							<div class="card-footer">
								<a href="/pedidosMesa/ver/{{$item->id}}" class="btn btn-success w-100">Ver pedido</a>
								@if($item->estado != 'recusado')
								<a onclick='swal("Atenção!", "Deseja alterar para recusado?", "warning").then((sim) => {if(sim){ location.href="/pedidosMesa/recusar/{{ $item->id }}" }else{return false} })' href="#!" class="btn btn-danger w-100 mt-1">Recusar</a>
								@endif
							</div>
						</div>
					</div>
					@endforeach
				</div>
				@endif
			</div>
		</div>
	</div>
</div>
@endsection