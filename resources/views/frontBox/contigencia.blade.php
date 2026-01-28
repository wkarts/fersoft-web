@extends('default.layout')

@section('css')
<style type="text/css">
	body.loading .modal-loading {
		display: block;
	}

	.modal-loading {
		display: none;
		position: fixed;
		z-index: 10000;
		top: 0;
		left: 0;
		height: 100%;
		width: 100%;
		background: rgba(255, 255, 255, 0.8)
		url("/loading.gif") 50% 50% no-repeat;
	}

</style>
@endsection
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">

		<form method="get" class="row">
			<div class="col-md-2">
				<label>Data inicial</label>
				<input type="date" value="{{ $start_date }}" class="form-control" name="start_date">
			</div>
			<div class="col-md-2">
				<label>Data final</label>
				<input type="date" value="{{ $end_date }}" class="form-control" name="end_date">
			</div>
			<div class="col-md-3">
				<br>
				<button class="btn btn-info mt-2">
					Filtrar
				</button>
			</div>
		</form>
		<input type="hidden" id="_token" value="{{ csrf_token() }}">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<div class="table-responsive">
				<table class="table">
					<thead>
						<tr>
							<th>#</th>
							<th>Cliente</th>
							<th>Estado</th>
							<th>Vendedor</th>
							<th>Valor total</th>
							<th>Desconto</th>
							<th>Data</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody>
						@foreach($vendas as $v)
						<tr>
							<td>{{ $v->numero_sequencial }}</td>
							<td>
								{{ $v->cliente->razao_social ?? 'NAO IDENTIFCADO' }}
							</td>
							<td>{{ $v->estado }}</td>
							<td>{{ $v->vendedor_setado ? $v->vendedor_setado->nome : '--' }}</td>
							<td>{{ moeda($v->valor_total) }}</td>
							<td>{{ moeda($v->desconto) }}</td>
							<td>{{ __date($v->created_at) }}</td>
							<td>
								@if($v->reenvio_contigencia == 0 && $v->contigencia)
								<a title="RETRANSMITIR EM CONTIGÊNCIA" id="btn_envia_{{$v->id}}" class="btn btn-warning spinner-white spinner-right btn-sm" onclick='swal("Atenção!", "Deseja enviar esta venda em contigência para Sefaz?", "warning").then((sim) => {if(sim){ transmitirContigencia({{$v->id}}) }else{return false} })' href="#!">
									<i class="las la-paper-plane"></i>
								</a>
								@endif
							</td>
						</tr>
						@endforeach
					</tbody>
				</table>
			</div>

		</div>
	</div>

</div>

<div class="modal-loading loading-class"></div>


@section('javascript')
<script type="text/javascript" src="/js/frenteCaixa.js"></script>
@endsection
@endsection