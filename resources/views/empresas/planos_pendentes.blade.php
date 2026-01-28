@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Planos pendentes</h4>
			

			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">
								
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Empresa</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Plano</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Data</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Expiração</span></th>

								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Representante</span></th>

								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Ações</span></th>
							</tr>
						</thead>

						<tbody class="datatable-body">
							@foreach($planos as $e)

							<tr class="datatable-row">
								
								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{$e->empresa->nome}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{$e->plano->nome}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{ \Carbon\Carbon::parse($e->created_at)->format('d/m/Y H:i')}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{ \Carbon\Carbon::parse($e->expiracao)->format('d/m/Y')}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										@if($e->representante)
										{{ $e->representante->nome }}
										@else
										--
										@endif
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 200px;">
										
										<a onclick='swal("Atenção!", "Deseja ativar este plano?", "warning").then((sim) => {if(sim){ location.href="/planosPendentes/ativar/{{ $e->id }}" }else{return false} })' href="#!" class="btn btn-sm btn-success">
											Ativar
										</a>

										<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/planosPendentes/delete/{{ $e->id }}" }else{return false} })' href="#!" class="btn btn-sm btn-danger">
											Remover
										</a>
										
									</span>
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

@endsection	
