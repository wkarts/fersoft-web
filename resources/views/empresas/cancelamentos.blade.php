@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">


				<br>
				<div class="card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Solicitações de cancelamento</h3>
					</div>
				</div>
				@csrf

				<div class="row">
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th>Empresa</th>
									<th>Data</th>
									<th>Motivo</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								@foreach($data as $item)
								<tr>
									<td>{{ $item->empresa->nome }}</td>
									<td>{{ __date($item->created_at, 1) }}</td>
									<td>
										{{$item->justificativa}}
									</td>
									<td>
										<a title="Remover" class="btn btn-sm btn-danger" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/cancelamento-super-delete/{{ $item->id }}" }else{return false} })' href="#!">
											<i class="la la-trash"></i>	
										</a>

										<a title="Detalhes" class="btn btn-sm btn-primary" href="/empresas/detalhes/{{$item->empresa->id}}">
											<i class="la la-file"></i>	
										</a>
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection