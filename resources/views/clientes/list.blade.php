@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-12">
				<div class="row">

					<a style="margin-left: 5px; margin-top: 5px;" href="/clientes/new" class="btn btn-lg btn-success">
						<i class="fa fa-plus"></i>Novo Cliente
					</a>
					

					<a style="margin-left: 5px; margin-top: 5px;" href="/clientes/importacao" class="btn btn-lg btn-danger">
						<i class="fa fa-arrow-up"></i>Importação
					</a>

					<a style="margin-left: 5px; margin-top: 5px;" href="/cashback-config" class="btn btn-lg btn-info">
						<i class="fa fa-cog"></i>Configuração Cash Back
					</a>

				</div>
			</div>
		</div>
		<br>

		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<form method="get" action="/clientes/pesquisa">
				<div class="row align-items-center">

					<div class="col-lg-2 col-xl-2">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<div class="input-group">

									<select name="tipo_pesquisa" class="custom-select">
										@foreach(App\Models\Cliente::tiposPesquisa() as $key => $t)
										<option @isset($tipoPesquisa) @if($tipoPesquisa == $key) selected @endif @endif value="{{$key}}">{{$t}}</option>
										@endforeach
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-2 col-xl-2">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<div class="input-group">

									<select name="ordem" class="custom-select">
										<option value="">selecione a ordem</option>
										<option @isset($ordem) @if($ordem == 'desc') @endif @endif value="desc">Mais recente</option>
										<option @isset($ordem) @if($ordem == 'asc') @endif @endif value="asc">Mais antigo</option>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-4 col-12">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<div class="input-group">
									<input type="text" name="pesquisa" class="form-control" placeholder="Pesquisa cliente" id="kt_datatable_search_query" value="{{{ isset($pesquisa) ? $pesquisa : ''}}}">
									
									<div class="input-group-prepend">
										<span class="input-group-text">
											<i class="la la-birthday-cake"></i>
										</span>
									</div>
									<div class="input-group-append">
										<span class="input-group-text">
											<label class="checkbox checkbox-inline checkbox-info">
												<input type="checkbox" name="aniversariante" @isset($aniversariante) @if($aniversariante == true) checked @endif @endif/>
												<span></span>
											</label>
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-2 col-12">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<div class="input-group">
									<input type="text" name="cpf_cnpj" class="form-control cpf_cnpj" placeholder="Pesquisa CPF/CNPJ" id="kt_datatable_search_query" value="{{{ isset($cpf_cnpj) ? $cpf_cnpj : ''}}}">
									
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>

			</form>
			<br>
			<h4>Lista de Clientes</h4>

			@isset($paraImprimir)
			<form method="get" action="/clientes/relatorio">
				<input type="hidden" name="pesquisa" value="{{{ isset($pesquisa) ? $pesquisa : '' }}}">
				<input type="hidden" name="tipo_pesquisa" value="{{{ isset($tipoPesquisa) ? $tipoPesquisa : '' }}}">

				<input type="hidden" name="aniversariante" value="{{ $aniversariante }}">
				<button style="margin-left: 5px; margin-top: 5px;" class="btn btn-info">
					<i class="fa fa-print"></i>Imprimir relatório
				</button>
			</form>
			@endisset
			@if(isset($totalGeralClientes))
			<label>Total de clientes cadastrados: <strong class="text-info">{{$totalGeralClientes}}</strong></label>
			@endif
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
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 300px;">AÇÕES</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">RAZÃO SOCIAL</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">NOME FANTASIA</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">CPF/CNPJ</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">IE/RG</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">CIDADE</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">TELEFONE</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">EMAIL</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">VALOR CASHBACK</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">DATA DE CADASTRO</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">DATA DE NASCIMENTO</span></th>
													
												</tr>
											</thead>
											<tbody id="body" class="datatable-body">
												@foreach($clientes as $c)
												<tr class="datatable-row">
													<td class="datatable-cell">
														<span class="codigo" style="width: 300px;" id="id">
															<a class="btn btn-warning btn-sm" onclick='swal("Atenção!", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="/clientes/edit/{{ $c->id }}" }else{return false} })' href="#!">
																<i class="la la-edit"></i>	
															</a>
															<a class="btn btn-danger btn-sm" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/clientes/delete/{{ $c->id }}" }else{return false} })' href="#!">
																<i class="la la-trash"></i>	
															</a>

															@if($c->celular)
															<a class="btn btn-success btn-sm" href="#!" onclick="whatsAppClick('{{$c->celular}}')">
																<i class="la la-whatsapp"></i>	
															</a>
															@endif

															@if($c->telefone)
															<a class="btn btn-success btn-sm" href="#!" onclick="whatsAppClick('{{$c->telefone}}')">
																<i class="la la-whatsapp"></i>	
															</a>
															@endif

															@if(sizeof($c->cashBacks) > 0)
															<a title="Lista de CashBack" class="btn btn-dark btn-sm" href="/clientes/cashBacks/{{ $c->id }}">
																<i class="la la-money"></i>	
															</a>
															@endif

															<a title="Upload de documentos" class="btn btn-info btn-sm" href="/clientes/upload/{{ $c->id }}">
																<i class="la la-paperclip"></i>	
															</a>
															
														</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 250px;" id="id">{{$c->razao_social}}</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">{{$c->nome_fantasia}}</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">{{$c->cpf_cnpj}}</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 100px;" id="id">{{$c->ie_rg}}</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 200px;" id="id">{{$c->cidade->nome}} ({{$c->cidade->uf}})</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 120px;" id="id">{{$c->telefone}}</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 100px;" id="id">{{$c->email}}</span>
													</td>

													<td class="datatable-cell"><span class="codigo" style="width: 100px;" id="id">{{ moeda($c->valor_cashback) }}</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 100px;" id="id">
															{{\carbon\carbon::parse($c->created_at)->format('d/m/Y')}}
														</span>
													</td>

													@if($c->data_nascimento != "")
													<td class="datatable-cell">
														<span class="codigo" style="width: 100px;" id="id">
															{{$c->data_nascimento}}
														</span>
													</td>
													@else
													<td class="datatable-cell">
														<span class="codigo" style="width: 100px;" id="id">
															--
														</span>
													</td>
													@endif

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

								@foreach($clientes as $c)

								<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6">
									<div class="card card-custom gutter-b example example-compact">
										<div class="card-header">
											<div class="flex-shrink-0 mr-4 mt-lg-0 mt-3">
												<div class="symbol symbol-circle symbol-lg-75 mt-4">
													@if($c->imagem != "" && file_exists(public_path('imgs_clientes/').$c->imagem))
													<img src="/imgs_clientes/{{ $c->imagem }}" alt="image">
													@else
													<img src="/foto_usuario/user.png" alt="image">
													@endif
												</div>
												<div class="symbol symbol-lg-75 symbol-circle symbol-primary d-none">
													<span class="font-size-h3 font-weight-boldest">JM</span>
												</div>
											</div>
											<div class="card-title">
												<h3 style="width: 230px; font-size: 12px; height: 10px;" class="card-title">{{substr($c->razao_social, 0, 30)}}
												</h3>
											</div>

											<div class="card-toolbar">
												<div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">
													<a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
														<i class="fa fa-ellipsis-h"></i>
													</a>
													<div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-left">
														<!--begin::Navigation-->
														<ul class="navi navi-hover">
															<li class="navi-header font-weight-bold py-4">
																<span class="font-size-lg">Ações:</span>
															</li>
															<li class="navi-separator mb-3 opacity-70"></li>
															<li class="navi-item">
																<a href="/clientes/edit/{{$c->id}}" class="navi-link">
																	<span class="navi-text">
																		<span class="label label-xl label-inline label-light-primary">Editar</span>
																	</span>
																</a>
															</li>
															<li class="navi-item">
																<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/clientes/delete/{{ $c->id }}" }else{return false} })' href="#!" class="navi-link">
																	<span class="navi-text">
																		<span class="label label-xl label-inline label-light-danger">Excluir</span>
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
												<a target="_blank" class="kt-widget__data text-success">{{ $c->nome_fantasia }}</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">CNPJ/CPF:</span>
												<a target="_blank" class="kt-widget__data text-success">{{ $c->cpf_cnpj }}</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">IE/RG:</span>
												<a class="kt-widget__data text-success">{{$c->ie_rg}}</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">Cidade:</span>
												<a class="kt-widget__data text-success">{{$c->cidade->nome}} ({{$c->cidade->uf}})</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">Data de cadastro:</span>
												<a class="kt-widget__data text-success">
													{{\carbon\carbon::parse($c->created_at)->format('d/m')}}
												</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">Data de nascimento:</span>
												<a class="kt-widget__data text-success">
													@if($c->data_nascimento != "")
													{{$c->data_nascimento}}
													@else
													--
													@endif
												</a>
											</div>

											<div class="kt-widget__info">
												<span class="kt-widget__label">UF:</span>
												<a class="kt-widget__data text-success">{{$c->cidade->uf}}</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">Telefone:</span>
												<a class="kt-widget__data text-success">{{$c->telefone}}</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">Email:</span>
												<a class="kt-widget__data text-success">{{$c->email}}</a>
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

		</div>

		<div class="d-flex justify-content-between align-items-center flex-wrap">
			<div class="d-flex flex-wrap py-2 mr-3">
				@if(isset($links))
				{{$clientes->links()}}
				@endif
			</div>
		</div>
	</div>
</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
	function whatsAppClick(fone){
		fone = fone.replace(/[^0-9]/g,'');
		let uri = "https://wa.me/55"+fone+"?text=Olá"
		window.open(uri)
	}
</script>
@endsection