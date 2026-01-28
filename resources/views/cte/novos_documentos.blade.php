@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<h4>Nova Consulta</h4>

			
			<p id="sem-resultado" style="display: none" class="center-align text-danger">Nenhum novo resultado...</p>

			<div class="col-xl-12" id="table">
				<a href="/cte/manifesta" class="btn btn-info">
					<i class="la la-undo"></i>
					Voltar para os documentos
				</a>
				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Nome</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Documento</span></th>
								<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Valor</span></th>
								<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Data Emissão</span></th>


								<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Chave</span></th>

							</tr>
						</thead>

						<tbody class="datatable-body">

							@foreach($novos as $d)

							<tr class="datatable-row" style="left: 0px;">
								<td class="datatable-cell"><span class="codigo" style="width: 150px;">{{$d['nome']}}</span>
								</td>
								<td class="datatable-cell"><span class="codigo" style="width: 150px;">{{$d['documento']}}</span>
								</td>
								<td class="datatable-cell"><span class="codigo" style="width: 80px;">{{number_format($d['valor'], 2)}}</span>
								</td>
								<td class="datatable-cell"><span class="codigo" style="width: 80px;">{{ \Carbon\Carbon::parse($d['data_emissao'])->format('d/m/Y H:i:s')}}</span>
								</td>

								<td class="datatable-cell"><span class="codigo" style="width: 200px;">{{$d['chave']}}</span>
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