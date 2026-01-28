@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">

		<h3 class="center-align">Alteração de Estado do Pedido Delivery
			<strong class="text-danger">{{$pedido->id}}</strong>
		</h3>
		<h5>Você tem certeza que irá alterar o estado desse pedido de 
			@if($pedido->estado == 'novo')
			<strong class="text-primary">NOVO</strong>
			@elseif($pedido->estado == 'reprovado')
			<strong class="text-warning">REPORVADO</strong>
			@elseif($pedido->estado == 'recusado')
			<strong class="text-danger">RECUSADO</strong>
			@elseif($pedido->estado == 'aprovado')
			<strong class="text-success">APROVADO</strong>
			@else
			<strong class="text-info">FINALIZADO</strong>
			@endif 
			para 
			@if($tipo == 'novo')
			<strong class="text-primary">NOVO</strong>
			@elseif($tipo == 'reprovado')
			<strong class="text-warning">REPORVADO</strong>
			@elseif($tipo == 'cancelado')
			<strong class="text-danger">CANCELADO</strong>
			@elseif($tipo == 'aprovado')
			<strong class="text-success">APROVADO</strong>
			@else
			<strong class="text-info">FINALIZADO</strong>
			@endif 
		</h5>


		@if($tipo == 'fz')
		<p class="text-info">Você será redirecionado para frente de caixa após esta tela</p>
		@endif
		<form action="/pedidosDelivery/confirmarAlteracao" method="get">
			<input type="hidden" name="tipo" value="{{$tipo}}">
			<input type="hidden" name="id" value="{{$pedido->id}}">

			<div class="row">
				<div class="col-lg-3">
					<label>Motoboy (opcional)</label>
					<select name="motoboy_id" class="form-control">
						<option value="">--</option>
						@foreach($motoboys as $m)
						<option value="{{ $m->id }}">{{ $m->nome }} - R$ {{ moeda($m->valor_entrega_padrao) }}</option>
						@endforeach
					</select>
				</div>


				<div class="form-group validated col-sm-12 col-lg-12">
					<label class="col-form-label">Motivo da alteração (Opcional)</label>
					<div class="">
						<textarea class="form-control" name="motivoEstado" rows="3"></textarea>

					</div>
				</div>

			</div>

			<button style="width: 100%;" class="btn @if($tipo == 'rp') btn-danger @elseif($tipo == 'rc') btn-warning @elseif($tipo == 'ap') btn-success @else btn-info @endif" type="submit">
				<i class="la la-check"></i>
				Confirmar Alteração
			</button>
		</form>

	</div>
</div>

@endsection	