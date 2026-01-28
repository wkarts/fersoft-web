@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<input type="hidden" id="_token" value="{{ csrf_token() }}">

			<a href="/alertas/create" class="btn btn-success">
				<i class="la la-plus"></i>
				Novo Alerta
			</a>

			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight mt-2">
				Alertas para empresas
			</h4>

			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">ID</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Titulo</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Status</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Prioridade</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Data de Cadastro</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Ações</span></th>
							</tr>
						</thead>

						<tbody class="datatable-body">
							@foreach($data as $item)

							<tr class="datatable-row">
								<td class="datatable-cell">
									<span class="codigo" style="width: 70px;">
										{{$item->id}}
									</span>
								</td>
								
								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										{{$item->titulo}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										@if($item->status)
										<span class="label label-xl label-inline label-light-success">Ativo</span>
										@else
										<span class="label label-xl label-inline label-light-danger">Desativado</span>
										@endif
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										{{ strtoupper($item->prioridade) }}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 120px;">
										{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										<form action="{{ route('alerta.destroy', $item->id) }}" method="post" id="form-{{$item->id}}">
											@method('delete')
											@csrf
											<a href="/alertas/edit/{{$item->id}}" class="btn btn-sm btn-warning">
												<i class="la la-edit"></i>
											</a>
											@if(!$item->status)

											<button class="btn btn-sm btn-danger btn-delete">
												<i class="la la-trash"></i>
											</button>

											@endif
											@if(sizeof($item->views) > 0)
											<a href="/alertas/list/{{$item->id}}" class="btn btn-sm btn-info">
												<i class="la la-list"></i>
											</a>
											@endif
										</form>
										
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
