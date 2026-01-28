@extends('default.layout')
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<form method="post" enctype="multipart/form-data" action="/clientes/importacao">
		<div class="card card-custom gutter-b example example-compact">
			<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
				<div class="col-lg-12">
					<br>
					<!--begin::Portlet-->

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">CashBack Cliente <strong class="text-success ml-1">{{ $item->razao_social }}</strong></h3>
						</div>

					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">

							<table class="table">
								<thead>
									<tr>
										<td>Data</td>
										<td>Valor do crédito</td>
										<td>Percentual</td>
										<td>Valor da venda</td>
										<td>Data de expiração</td>
										<td>Status</td>
									</tr>
								</thead>
								<tbody>
									@foreach($item->cashBacks as $c)
									<tr>
										<td>{{ __date($c->created_at, 1) }}</td>
										<td>{{ moeda($c->valor_credito) }}</td>
										<td>{{ moeda($c->valor_percentual) }}</td>
										<td>{{ moeda($c->valor_venda) }}</td>

										<td>{{ __date($c->data_expiracao, 0) }}</td>
										<td>
											@if($c->status)
											<i class="la la-check text-success"></i>
											@else
											<i class="la la-close text-danger"></i>
											@endif
										</td>
									</tr>
									@endforeach
								</tbody>
							</table>

						</div>
					</div>
					<h5>Somatório ativo: <strong class="text-success">R$ {{ moeda($item->cashBacks->where('status',1)->sum('valor_credito')) }}</strong></h5>
					<h5>Somatório expirado: <strong class="text-danger">R$ {{ moeda($item->cashBacks->where('status',0)->sum('valor_credito')) }}</strong></h5>

				</div>
			</div>
		</div>
	</form>
</div>

@endsection