@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">


	<div class="card-body">
		<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

				<a href="/ordemServico/new" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Nova Ordem de Serviço
				</a>
			</div>
			
		</div>
		<br>

		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">


			<form method="get" action="/ordemServico/filtro">
				<div class="row align-items-center">

					<div class="form-group col-12 col-xl-3">
						<div class="row align-items-center">

							<div class="col-md-12 my-2 my-md-0">
								<label class="col-form-label">Razão social</label>

								<div class="input-icon">
									<input type="text" name="cliente" value="{{{ isset($cliente) ? $cliente : '' }}}" class="form-control" placeholder="Cliente" id="kt_datatable_search_query">
									<span>
										<i class="fa fa-search"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-12 col-xl-3">
						<div class="row align-items-center">

							<div class="col-md-12 my-2 my-md-0">
								<label class="col-form-label">Nome fantasia</label>

								<div class="input-icon">
									<input type="text" name="nome_fantasia" value="{{{ isset($nome_fantasia) ? $nome_fantasia : '' }}}" class="form-control" placeholder="Nome fantasia" id="kt_datatable_search_query">
									<span>
										<i class="fa fa-search"></i>
									</span>
								</div>
							</div>
						</div>
					</div>
					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Inicial</label>
						<div class="">
							<input type="date" name="data_inicial" class="form-control" value="{{{ isset($data_inicial) ? $data_inicial : '' }}}" />
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Final</label>
						<div class="">
							<input type="date" name="data_final" class="form-control" value="{{{ isset($data_final) ? $data_final : '' }}}" />
						</div>
					</div>

					<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
						<label class="col-form-label">Estado</label>

						<select class="custom-select form-control" id="estado" name="estado">
							<option @isset($estado) @if($estado == 'pd') selected @endif @endif value="pd">PENDENTE</option>
							<option @isset($estado) @if($estado == 'ap') selected @endif @endif value="ap">APROVADO</option>
							<option @isset($estado) @if($estado == 'rp') selected @endif @endif value="rp">REPROVADO</option>
							<option @isset($estado) @if($estado == 'fz') selected @endif @endif value="fz">FINALIZADO</option>
						</select>

					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>
			</form>

			<br>
			<h4>Lista de Ordens de Serviço</h4>
			<label>Total de registros: {{count($orders)}}</label>

			<div class="row">

				<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

					<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
						<div class="wizard-nav">

							<div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
								<!--begin::Wizard Step 1 Nav-->
								<div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
									<div class="wizard-label">
										<h3 class="wizard-title">
											<span>
												<i style="font-size: 40px" class="la la-table"></i>
												Tabela
											</span>
										</h3>
										<div class="wizard-bar"></div>
									</div>
								</div>
								<!--end::Wizard Step 1 Nav-->
								<!--begin::Wizard Step 2 Nav-->
								<div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
									<div class="wizard-label" id="grade">
										<h3 class="wizard-title">
											<span>
												<i style="font-size: 40px" class="la la-tablet"></i>
												Grade
											</span>
										</h3>
										<div class="wizard-bar"></div>
									</div>
								</div>

							</div>
						</div>
						<div class="pb-5" data-wizard-type="step-content">


							<div class="row">
								<div class="col-xl-12">

									<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

										<table class="datatable-table" style="max-width: 100%; overflow: scroll">
											<thead class="datatable-head">
												<tr class="datatable-row" style="left: 0px;">
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">AÇÕES</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">RAZÃO SOCIAL</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">VALOR</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">DATA</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">ESTADO</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">USUÁRIO</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">CONTA A RECEBER</span></th>
													
												</tr>
											</thead>
											<tbody id="body" class="datatable-body">
												@foreach($orders as $o)
												<tr class="datatable-row">
													<td class="datatable-cell">
														<span class="codigo" style="width: 200px;" id="id">
															
															<a class="btn btn-danger btn-sm" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/delete/{{ $o->id }}" }else{return false} })' href="#!">
																<i class="la la-trash"></i>	
															</a>

															<a title="Ver OS" class="btn btn-dark btn-sm" href="/ordemServico/servicosordem/{{$o->id}}">
																<i class="la la-eye"></i>	
															</a>
															
														</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 250px;" id="id">{{ $o->cliente->razao_social }}</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">{{ moeda($o->total_os()) }}</span>
													</td>

													<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">{{ __date($o->created_at) }}</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 150px;" id="id">
															@if($o->estado == 'pd')
															<a class="kt-widget__data text-primary">PENDENTE</a>
															@elseif($o->estado == 'ap')
															<a class="kt-widget__data text-success">APROVADO</a>
															@elseif($o->estado == 'rp')
															<a class="kt-widget__data text-danger">REPROVADO</a>
															@else
															<a class="kt-widget__data text-info">FINALIZADO</a>
															@endif
														</span>
													</td><td class="datatable-cell">
														<span class="codigo" style="width: 150px;" id="id">
															{{ $o->usuario->nome }}
														</span>
													</td>
												</td><td class="datatable-cell">
													<span class="codigo" style="width: 150px;" id="id">
														@if($o->contaReceber)
														<strong class="text-success">#{{ $o->contaReceber->id }}</strong>

														@else
														--
														@endif
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
					<div class="pb-5" data-wizard-type="step-content">

						<div class="row">

							@foreach($orders as $o)

							<div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
								<div class="card card-custom gutter-b example example-compact">
									<div class="card-header">
										<div class="card-title">
											<h3 style="width: 230px; font-size: 12px; height: 10px;" class="card-title">{{$o->numero_sequencial}} - {{substr($o->cliente->razao_social, 0, 30)}}
											</h3>
										</div>

										<div class="card-toolbar">
											<div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">
												<a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
													<i class="fa fa-ellipsis-h"></i>
												</a>
												<div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-right">
													<!--begin::Navigation-->
													<ul class="navi navi-hover">
														<li class="navi-header font-weight-bold py-4">
															<span class="font-size-lg">Ações:</span>
														</li>
														<li class="navi-separator mb-3 opacity-70"></li>
														@if(is_adm())
														<li class="navi-item">
															<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/delete/{{ $o->id }}" }else{return false} })' href="#!" class="navi-link">
																<span class="navi-text">
																	<span class="label label-xl label-inline label-light-danger">Remover</span>
																</span>
															</a>
														</li>

														@endif


														<li class="navi-item">
															<a href="/ordemServico/servicosordem/{{$o->id}}" class="navi-link">
																<span class="navi-text">
																	<span class="label label-xl label-inline label-light-primary">Ver OS</span>
																</span>
															</a>
														</li>

													</ul>
													<!--end::Navigation-->
												</div>
											</div>

										</div>
									</div>

									<div class="card-body">

										<div class="kt-widget__info">
											<span class="kt-widget__label">Nome fantasia:</span>
											<a target="_blank" class="kt-widget__data text-danger">{{ $o->cliente->nome_fantasia }}</a>
										</div>
										<div class="kt-widget__info">
											<span class="kt-widget__label">Valor:</span>
											<a target="_blank" class="kt-widget__data text-success">{{ number_format($o->total_os(), 2, ',', '.')}}</a>
										</div>
										<div class="kt-widget__info">
											<span class="kt-widget__label">Data:</span>
											<a class="kt-widget__data text-success">{{ \Carbon\Carbon::parse($o->created_at)->format('d/m/Y H:i')}}</a>
										</div>
										<div class="kt-widget__info">
											<span class="kt-widget__label">Usuario:</span>
											<a class="kt-widget__data text-success">{{ $o->usuario->nome }}</a>
										</div>

										<div class="kt-widget__info">
											<span class="kt-widget__label">Estado:</span>

											@if($o->estado == 'pd')
											<a class="kt-widget__data text-primary">PENDENTE</a>
											@elseif($o->estado == 'ap')
											<a class="kt-widget__data text-success">APROVADO</a>
											@elseif($o->estado == 'rp')
											<a class="kt-widget__data text-danger">REPROVADO</a>
											@else
											<a class="kt-widget__data text-info">FINALIZADO</a>
											@endif
										</div>

										@if(empresaComFilial())
										<div class="kt-widget__info">
											<span class="kt-widget__label">Local:</span>
											<strong class="text-success">
												{{ $o->filial ? $o->filial->descricao : 'Matriz' }}
											</strong>
										</div>
										@endif

										<div class="kt-widget__info">
											<span class="kt-widget__label">Conta receber:</span>

											@if($o->contaReceber)
											<strong class="text-success">#{{ $o->contaReceber->id }}</strong>

											@else
											--
											@endif
										</div>
									</div>

								</div>

							</div>

							@endforeach
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="d-flex justify-content-between align-items-center flex-wrap">
			<div class="d-flex flex-wrap py-2 mr-3">
				@if(isset($links))
				{{$orders->links()}}
				@endif
			</div>
		</div>
	</div>
</div>
</div>


@endsection	