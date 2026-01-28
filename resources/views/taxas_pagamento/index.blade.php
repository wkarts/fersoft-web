@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">

				<a href="/taxas-pagamento/create" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Nova taxa de pagamento
				</a>
			</div>
		</div>

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			

			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">

								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Tipo</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Taxa</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Bandeira cartão</span></th>
								
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Ações</span></th>
							</tr>
						</thead>

						<tbody class="datatable-body">
							@foreach($data as $item)

							<tr class="datatable-row">
								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{ $item->getTipo() }}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 120px;">
										{{ moeda($item->taxa) }}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 200px;">
										{{ $item->getBandeira() }}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										<form action="{{ route('taxas-pagamento.destroy', $item->id) }}" method="post" id="form-{{$item->id}}">
											@method('delete')
											@csrf

											<a href="{{ route('taxas-pagamento.edit', [$item->id]) }}" class="btn btn-sm btn-warning">
												<i class="la la-edit"></i>
											</a>

											<button class="btn btn-sm btn-danger btn-delete">
												<i class="la la-trash"></i>
											</button>
										</form>

									</span>
								</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
				<div class="d-flex justify-content-between align-items-center flex-wrap">
					<div class="d-flex flex-wrap py-2 mr-3">
						{{$data->links()}}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection	
