@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">


	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-12">

				<a href="{{ route('filial.create') }}" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Nova Localização
				</a>

			</div>
		</div>


		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<br>
			<h4>Filiais</h4>
			<div class="row">

				<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

					<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
						

						<div class="row">
							<div class="col-xl-12">

								<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

									<table class="datatable-table" style="max-width: 100%; overflow: scroll">
										<thead class="datatable-head">
											<tr class="datatable-row" style="left: 0px;">
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Descrição</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Razão Social</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 170px;">Documento</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Data de cadastro</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">ATIVO</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Ações</span></th>
											</tr>
										</thead>
										<tbody id="body" class="datatable-body">
											@foreach($data as $item)
											<tr class="datatable-row">
												<td class="datatable-cell">
													<span class="codigo" style="width: 250px;" id="id">
														{{$item->descricao}}
													</span>
												</td>
												<td class="datatable-cell">
													<span class="codigo" style="width: 250px;" id="id">
														{{$item->razao_social}}
													</span>
												</td>
												<td class="datatable-cell">
													<span class="codigo" style="width: 170px;" id="id">
														{{$item->cnpj}}
													</span>
												</td>
												<td class="datatable-cell">
													<span class="codigo" style="width: 120px;" id="id">
														{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}
													</span>
												</td>
												<td class="datatable-cell">
													<span class="codigo" style="width: 100px;" id="id">
														@if($item->status)
														<span class="label label-xl label-inline label-light-success">Sim</span>
														@else
														<span class="label label-xl label-inline label-light-danger">Não</span>
														@endif
													</span>
												</td>
												

												<td class="datatable-cell">
													<span class="codigo" style="width: 200px;" id="id">
														<form action="{{ route('filial.destroy', $item->id) }}" method="post" id="form-{{$item->id}}">
															@method('delete')
															@csrf
															<a class="btn btn-sm btn-warning" onclick='swal("Atenção!", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="{{ route('filial.edit', [$item->id]) }}" }else{return false} })' href="#!">
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
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

@endsection