@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">


	<div class="card-body">
		<form class="row" action="/compras/setar-validade" method="post">

			@csrf
			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
				
				<div class="card card-custom gutter-b">
					<div class="card-body">
						<h3>Fornecedor: <strong class="text-info">{{ $item->fornecedor->razao_social }}</strong></h3>
						<h3>CPF/CNPJ: <strong class="text-info">{{ $item->fornecedor->cpf_cnpj }}</strong></h3>
						<h3>DATA: <strong class="text-info">{{ __date($item->created_at) }}</strong></h3>
						<h3>LOTE: <strong class="text-info">{{ $item->lote }}</strong></h3>
						<h3>TOTAL: <strong class="text-info">{{ moeda($item->valor) }}</strong></h3>
					</div>
				</div>
				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">

								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Produto</span></th>

								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor Unit.</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Quantidade</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 170px;">Validade</span></th>

							</tr>
						</thead>


						<tbody id="body" class="datatable-body">

							@foreach($item->itens as $i)
							<tr class="datatable-row">

								<td class="datatable-cell"><span class="codigo" style="width: 120px;" id="id">{{$i->produto->nome}}</span>
								</td>

								<td class="datatable-cell"><span class="codigo" style="width: 100px;" id="id">{{number_format($i->valor_unitario, 2)}}</span>
								</td>

								<td class="datatable-cell"><span class="codigo" style="width: 100px;" id="id">{{number_format($i->quantidade, 2)}}</span>
								</td>

								<td class="datatable-cell"><span class="codigo" style="width: 170px;" id="id">
									<div class="input-field" style="margin-right: 10px;">
										<input required value="" id="data" name="validade[]" type="date" class="validate form-control">
										<input value="{{$i->id}}" name="item_id[]" type="hidden" class="validate">

									</div>
								</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>

				<button type="submit" class="btn btn-success btn-lg mt-2 pull-right">
					<i class="la la-check"></i>
					SALVAR
				</button>

			</div>

		</form>
	</div>
</div>
@endsection	