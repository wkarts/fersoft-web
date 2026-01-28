@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">


	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-12">

				<a href="{{ route('apuracaoMensal.create') }}" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Nova Apuração
				</a>

			</div>
		</div>
		<br>

		<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="">
			<div class="row align-items-center">


				<div class="form-group col-lg-3">
					<label class="col-form-label">Nome</label>
					<div class="">
						<div class="input-group">
							<input type="text" name="nome" class="form-control" value="{{{isset($nome) ? $nome : ''}}}" />
						</div>
					</div>
				</div>
				<div class="form-group col-lg-2">
					<label class="col-form-label">Data de início</label>
					<div class="">
						<div class="input-group">
							<input type="date" name="dt_inicio" class="form-control" value="{{{isset($dt_inicio) ? $dt_inicio : ''}}}" />
						</div>
					</div>
				</div>
				<div class="form-group col-lg-2">
					<label class="col-form-label">Data de fim</label>
					<div class="">
						<div class="input-group">
							<input type="date" name="dt_fim" class="form-control" value="{{{isset($dt_fim) ? $dt_fim : ''}}}" />
						</div>
					</div>
				</div>
				<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
					<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
				</div>
			</div>
		</form>


		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			
			<br>
			<h4>Lista de Apurações</h4>
			<label>Total de registros: {{$data->total()}}</label>
			<div class="row">

				<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

					<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
						

						<div class="row">
							<div class="col-xl-12">

								<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

									<table class="datatable-table" style="max-width: 100%; overflow: scroll">
										<thead class="datatable-head">
											<tr class="datatable-row" style="left: 0px;">
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Funcionário</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Data de registro</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Valor Final</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Mês/Ano</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Adicionado em conta a pagar</span></th>
												
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Ações</span></th>
											</tr>
										</thead>
										<tbody id="body" class="datatable-body">
											@foreach($data as $item)
											<tr class="datatable-row">
												<td class="datatable-cell">
													<span class="codigo" style="width: 250px;" id="id">
														{{$item->funcionario->nome}}
													</span>
												</td>
												<td class="datatable-cell">
													<span class="codigo" style="width: 150px;" id="id">
														{{ \Carbon\Carbon::parse($item->created)->format('d/m/Y H:i')}}
													</span>
												</td>
												<td class="datatable-cell">
													<span class="codigo" style="width: 150px;" id="id">
														R${{ number_format($item->valor_final, 2, ',', '.') }}
													</span>
												</td>
												
												<td class="datatable-cell">
													<span class="codigo" style="width: 150px;" id="id">
														{{strtoupper($item->mes)}}/{{$item->ano}}
													</span>
												</td>

												<td class="datatable-cell">
													<span class="codigo" style="width: 150px;" id="id">
														@if($item->conta_pagar_id == 0)
														<span class="label label-xl label-inline label-light-danger">Não</span>
														@else
														<span class="label label-xl label-inline label-light-success">Sim</span>
														<a target="_blank" href="/contasPagar/edit/{{$item->conta_pagar_id}}">#{{$item->conta_pagar_id}}</a>
														@endif
													</span>
												</td>

												<td class="datatable-cell">
													<span class="codigo" style="width: 200px;" id="id">
														<form action="{{ route('apuracaoMensal.destroy', $item->id) }}" method="post" id="form-{{$item->id}}">
															@method('delete')
															@csrf

															@if(!$item->conta_pagar_id)
															<a class="btn btn-dark btn-sm" href="/apuracaoMensal/contaPagar/{{$item->id}}">
																<i class="la la-money"></i>
															</a>
															@endif

															<button @if($item->conta_pagar_id) disabled @endif class="btn btn-sm btn-danger btn-delete">
																<i class="la la-trash"></i>
															</button>

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

				</div>

				<div class="d-flex justify-content-between align-items-center flex-wrap">
					<div class="d-flex flex-wrap py-2 mr-3">

						{{$data->links()}}

					</div>
				</div>
			</div>
		</div>
	</div>

	@endsection