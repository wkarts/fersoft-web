@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">


		<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

			<table class="datatable-table" style="max-width: 100%; overflow: scroll">
				<thead class="datatable-head">
					<tr class="datatable-row" style="left: 0px;">
						<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">#</span></th>
						<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Nome</span></th>

						<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Ações</span></th>
					</tr>
				</thead>
				<tbody id="body" class="datatable-body">
					@foreach($categorias as $c)
					<tr class="datatable-row">

						<td class="datatable-cell">
							<span class="codigo" style="width: 150px;">
								<img src="{{$c->img_url}}" style="width: 100px; border-radius: 20%;">
							</span>
						</td>

						<td class="datatable-cell">
							<span class="codigo" style="width: 150px;">
								{{ $c->nome }}
							</span>
						</td>
						

						<td class="datatable-cell">
							<span class="codigo" style="width: 100px;">

								<div class="switch switch-outline switch-primary">
									<form id="form-check-{{$c->id}}" method="get" action="/categoriaDeLoja/alterarStatus/{{$c->id}}">
										<label class="">
											<input class="click" value="{{$c->id}}" @if($c->marcado == 1) checked @endif type="checkbox">
											<span class="lever"></span>
										</label>
									</form>
								</div>
								
							</span>
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

	</div>
</div>


@endsection	

@section('javascript')
<script type="text/javascript">
	$('.click').change((target) => {
		$('#form-check-'+target.target.value).submit()
	})
</script>
@endsection