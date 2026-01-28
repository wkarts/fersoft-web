@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<input type="hidden" id="filial" value="{{ $ordem->filial_id }}">

		<div class="row">
			<div class="col-md-6 col-12">
				<h4>Status: 
					@if($ordem->estado == 'pd')
					<span class="label label-xl label-inline label-light-warning">PENDENTE</span>
					@elseif($ordem->estado == 'ap')
					<span class="label label-xl label-inline label-light-success">APROVADO</span>
					@elseif($ordem->estado == 'rp')
					<span class="label label-xl label-inline label-light-danger">REPROVADO</span>
					@else
					<span class="label label-xl label-inline label-light-info">FINALIZADO</span>
					@endif

				</h4>
				@if($ordem->estado != 'rp')
				<a href="/ordemServico/alterarEstado/{{$ordem->id}}" class="btn btn-primary orange">
					<i class="la la-refresh"></i>
					Alterar estado
				</a>
				@endif

				<a target="_blank" href="/ordemServico/imprimir/{{$ordem->id}}" class="btn btn-info">
					<i class="la la-print"></i> Imprimir
				</a>
			</div>


			<div class="col-md-6 col-12">

				<h5>NFSe: 
					@if($ordem->NfNumero)
					<strong>{{$ordem->NfNumero}}</strong>
					@else
					<strong> -- </strong>
					@endif
				</h5>
				<h5>Total: <strong class="text-success">R$ {{ moeda($ordem->total_os()) }}</strong></h5>
				<h5>Usuario responsável: <strong class="text-success">{{ $ordem->usuario->nome }}</strong></h5>

				<!-- @if(!$ordem->vendaCaixa)
				<a class="btn btn-dark btn-lg" href="/ordemServico/gerarVendaCompleta/{{$ordem->id}}">
					<i class="la la-file"></i>
					Gerar Venda Completa
				</a>
				@else
				<a class="btn btn-dark btn-lg" href="/nfce/detalhes/{{$ordem->vendaCaixa->id}}">
					<i class="la la-file"></i>
					Ver Venda
				</a>
				@endif -->
			</div>
		</div>

		<input type="hidden" id="token" value="{{ csrf_token() }}">
		<input type="hidden" name="ordem_servico_id" class="ordem_servico_id" value="{{$ordem->id}}">

	</div>

	<div class="row" id="content" style="display: block">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
			<div class="m-6">
				<div class="card card-custom gutter-b example example-compact">
					<div class="col-lg-12">
						<!--begin::Portlet-->

						<form method="post" action="/ordemServico/addServico">
							@csrf

							<div class="row">
								<input type="hidden" id="_token" value="{{ csrf_token() }}">
								<input type="hidden" name="ordem_servico_id" name="" value="{{$ordem->id}}">


								<div class="col-xl-12">

									<div class="form-group validated col-sm-12 col-lg-12">
										<br>
										<h4>Serviços da OS</h4>

										<div class="kt-section kt-section--first">
											<div class="kt-section__body">

												<div class="row align-items-center">
													<div class="form-group validated col-sm-4 col-lg-4">
														<label class="col-form-label" id="lbl_cpf_cnpj">Serviço</label>
														<select style="width: 100%" required class="form-control select2 servico" id="kt_select2_1" name="servico">
															<option value="">Selecione</option>
															@foreach($servicos as $s)
															<option data-value="{{$s->valor}}" value="{{$s->id}}">{{ $s->nome }}</option>
															@endforeach
														</select>
													</div>

													<div class="form-group validated col-sm-4 col-lg-2">
														<label class="col-form-label" id="">Valor unitário</label>
														<div class="">
															<input required type="tel" id="valor_unitario" name="valor_unitario" class="form-control @if($errors->has('valor_unitario')) is-invalid @endif money valor_servico" value="">
															@if($errors->has('valor_unitario'))
															<div class="invalid-feedback">
																{{ $errors->first('valor_unitario') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-4 col-lg-2">
														<label class="col-form-label" id="">Quantidade</label>
														<div class="">
															<input type="text" id="quantidade" name="quantidade" class="form-control @if($errors->has('quantidade')) is-invalid @endif qtd money qtd_servico" value="">
															@if($errors->has('quantidade'))
															<div class="invalid-feedback">
																{{ $errors->first('quantidade') }}
															</div>
															@endif
														</div>
													</div>

													<div class="col-sm-3 col-lg-2">
														<button id="btn-add-servico" style="margin-top: 10px;" type="button" class="btn btn-success">Adicionar</button>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col-xl-12">
									<div class="row">
										<div class="col-xl-12">
											<div class="col-md-12">
												
												<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

													<table class="datatable-table tabela-servicos" style="max-width: 100%; overflow: scroll">
														<thead class="datatable-head">
															<tr class="datatable-row" style="left: 0px;">
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 300px;">Serviço</span></th>
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Quantidade</span></th>
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor unitário</span></th>
																
																<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Subtotal</span></th>

																<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Status</span></th>

																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Ações</span></th>
															</tr>
														</thead>

														<tbody class="datatable-body">


															@foreach($ordem->servicos as $s)
															<tr class="datatable-row" style="left: 0px;">

																<td class="datatable-cell"><span class="codigo" style="width: 300px;">{{$s->servico->nome}}</span></td>
																<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ moeda($s->quantidade) }}</span></td>
																<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ moeda($s->valor_unitario) }}</span></td>
																<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ moeda($s->sub_total) }}</span></td>
																<td class="datatable-cell"><span class="codigo" style="width: 100px;">
																	@if($s->status == true)
																	<span class="label label-xl label-inline label-light-success">FINALIZADO
																	</span>
																	@else
																	<span class="label label-xl label-inline label-light-warning">PENDENTE
																	</span>
																	@endif
																</span></td>
																
																<td class="datatable-cell"><span class="codigo" style="width: 120px;">
																	@if(!$s->status)
																	<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/deleteServico/{{ $s->id }}" }else{return false} })' href="#!" class="btn btn-danger btn-sm">
																		<span class="la la-trash"></span>
																	</a>
																	@endif
																	<a class="btn btn-success btn-sm" href="/ordemServico/alterarStatusServico/{{ $s->id }}">
																		<span class="la la-check"></span>
																	</a>

																</span></td>

															</tr>
															@endforeach

														</tbody>
													</table>
													<br>
												</div>
											</div>
											<div class="col-12">
												<h4>Valor total de serviços: <strong class="total-servico">R$ {{ moeda($ordem->servicos->sum('sub_total')) }}</strong></h4>
											</div>
											@if(!$ordem->nfse)
											<a class="btn btn-info float-right" href="/ordemServico/gerar_nfse/{{$ordem->id}}">
												<i class="la la-file"></i>
												Gerar NFSe
											</a>
											@else
											<div class="col-12">
												<h4>Estado: 
													@if($ordem->nfse->estado == 'novo')
													<span class="label label-xl label-inline label-light-primary">Disponível</span>

													@elseif($ordem->nfse->estado == 'aprovado')
													<span class="label label-xl label-inline label-light-success">Aprovado</span>
													@elseif($ordem->nfse->estado == 'cancelado')
													<span class="label label-xl label-inline label-light-danger">Cancelado</span>
													@else
													<span class="label label-xl label-inline label-light-warning">Rejeitado</span>
													@endif
												</h4>
												@if($ordem->nfse->estado == 'APROVADO')
												<h4>Nº NFSe: <strong>{{$ordem->nfse->numero_nfse}}</strong></h4>
												@endif

												<a class="btn btn-dark float-right" href="/nfse/edit/{{$ordem->nfse->id}}">
													<i class="la la-file"></i>
													Ver NFSe
												</a>
											</div>
											@endif

										</div>
									</div>
								</div>
							</div>
							<br>
						</form>

					</div>
				</div>
			</div>
		</div>
	</div>

	<hr>
	<div class="row" id="content" style="display: block">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
			<div class="m-6">
				<div class="card card-custom gutter-b example example-compact">
					<div class="col-lg-12">
						<!--begin::Portlet-->

						<form method="post" action="/ordemServico/saveProduto">
							@csrf

							<div class="row">
								<input type="hidden" id="_token" value="{{ csrf_token() }}">
								<input type="hidden" name="ordem_servico_id" name="" value="{{$ordem->id}}">

								<div class="col-xl-12">

									<div class="form-group validated col-sm-12 col-lg-12">
										<br>
										<h4>Produtos da OS</h4>

										<div class="kt-section kt-section--first">
											<div class="kt-section__body">

												<div class="row align-items-center">
													<div class="form-group validated col-sm-4 col-lg-4">
														<label>Produto</label>
														<select required class="form-control select2 produto-search select-search" id="kt_select2_3" name="produto_id">
															<option value="">Digite para buscar o produto</option>
														</select>
													</div>

													<div class="form-group validated col-sm-2 col-lg-2 col-5 col-sm-5 add-prod">
														<label>Valor unitário</label>
														<input required id="valor_prod" placeholder="Valor" type="text" class="form-control money valor_produto" name="valor_unitario" value="{{number_format(0, $casasDecimais, ',', '.')}}">
													</div>

													<div class="form-group validated col-sm-2 col-lg-2 col-5 col-sm-5 add-prod">
														<label>Quantidade</label>
														<input required id="quantidade" placeholder="QTD" type="text" class="form-control money qtd qtd_produto" name="quantidade" value="1">
													</div>

													<div class="col-sm-3 col-lg-2">
														<button style="margin-top: 9px;" id="btn-add-produto" type="button" class="btn btn-success">Adicionar</button>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col-xl-12">
									<div class="row">
										<div class="col-xl-12">
											<div class="">

												<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

													<table class="datatable-table tabela-produto" style="max-width: 100%; overflow: scroll">
														<thead class="datatable-head">
															<tr class="datatable-row" style="left: 0px;">
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 300px;">Produto</span></th>
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Quantidade</span></th>
																<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor unitário</span></th>
																<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Subtotal</span></th>

																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Ações</span></th>
															</tr>
														</thead>

														<tbody class="datatable-body">

															@foreach($ordem->produtos as $item)
															<tr class="datatable-row" style="left: 0px;">

																<td class="datatable-cell"><span class="codigo" style="width: 300px;">{{$item->produto->nome}}</span></td>
																<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{moeda($item->quantidade)}}</span></td>
																<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{moeda($item->valor_unitario)}}</span></td>
																<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{moeda($item->sub_total)}}</span></td>

																<td class="datatable-cell"><span class="codigo" style="width: 120px;">
																	<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/deleteProduto/{{ $item->id }}" }else{return false} })' href="#!" class="btn btn-danger btn-sm">
																		<span class="la la-trash"></span>
																	</a>

																</span></td>

															</tr>
															@endforeach

														</tbody>
													</table>
													<br>
												</div>
											</div>
											<div class="col-12">
												<h4 class="">Valor total de produtos: <strong class="total-produto">R$ {{ moeda($ordem->produtos->sum('sub_total')) }}</strong></h4>
											</div>

											@if(!$ordem->venda)
											<a class="btn btn-info float-right" href="/ordemServico/gerar_venda/{{$ordem->id}}">
												<i class="la la-file"></i>
												Gerar Venda dos Produtos
											</a>
											@else
											<div class="col-12">
												<h4>Venda: <strong>{{$ordem->venda->numero_sequencial}}</strong></h4>
												<h4>Estado: 
													@if($ordem->venda->estado == 'DISPONIVEL')
													<span class="label label-xl label-inline label-light-primary">Disponível</span>

													@elseif($ordem->venda->estado == 'APROVADO')
													<span class="label label-xl label-inline label-light-success">Aprovado</span>
													@elseif($ordem->venda->estado == 'CANCELADO')
													<span class="label label-xl label-inline label-light-danger">Cancelado</span>
													@else
													<span class="label label-xl label-inline label-light-warning">Rejeitado</span>
													@endif
												</h4>
												@if($ordem->venda->estado == 'APROVADO')
												<h4>Nº NFe: <strong>{{$ordem->venda->NfNumero}}</strong></h4>
												@endif

												<a class="btn btn-dark float-right" href="/vendas/detalhar/{{$ordem->venda->id}}">
													<i class="la la-file"></i>
													Ver venda
												</a>
											</div>
											@endif
										</div>
									</div>
								</div>
							</div>

						</form>

					</div>
				</div>
			</div>
		</div>
	</div>



	<hr>
	<div class="row" id="content" style="display: block">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
			<div class="m-6">
				<div class="card card-custom gutter-b example example-compact">
					<div class="col-lg-12">
						<!--begin::Portlet-->

						<form method="post" action="/ordemServico/saveFuncionario">
							@csrf

							<div class="row">
								<input type="hidden" id="_token" value="{{ csrf_token() }}">
								<input type="hidden" name="ordem_servico_id" name="" value="{{$ordem->id}}">


								<div class="col-xl-12">

									<div class="form-group validated col-sm-12 col-lg-12">
										<br>
										<h4>Funcionários da OS</h4>

										<div class="kt-section kt-section--first">
											<div class="kt-section__body">

												<div class="row align-items-center">
													<div class="form-group validated col-sm-4 col-lg-4">
														<label class="col-form-label" id="lbl_cpf_cnpj">Funcionário</label>
														<div class="">
															<select class="form-control select2 servico" id="kt_select2_2" name="funcionario">
																@foreach($funcionarios as $f)
																<option value="{{$f->id}}">{{$f->id}} - {{$f->nome}}</option>
																@endforeach
															</select>
														</div>
													</div>

													<div class="form-group validated col-sm-5 col-lg-5">
														<label class="col-form-label" id="">Função</label>
														<div class="">
															<input type="text" id="quantidade" name="funcao" class="form-control @if($errors->has('funcao')) is-invalid @endif" value="">
															@if($errors->has('funcao'))
															<div class="invalid-feedback">
																{{ $errors->first('funcao') }}
															</div>
															@endif
														</div>
													</div>

													<div class="col-sm-3 col-lg-2">
														<button style="margin-top: 10px;" type="submit" class="btn btn-success">Adicionar</button>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col-xl-12">
									<div class="row">
										<div class="col-xl-12">
											<div class="">

												<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

													<table class="datatable-table" style="max-width: 100%; overflow: scroll">
														<thead class="datatable-head">
															<tr class="datatable-row" style="left: 0px;">
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 300px;">Nome</span></th>
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Função</span></th>
																<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Telefone</span></th>

																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Ações</span></th>
															</tr>
														</thead>

														<tbody class="datatable-body">

															@foreach($ordem->funcionarios as $f)
															<tr class="datatable-row" style="left: 0px;">

																<td class="datatable-cell"><span class="codigo" style="width: 300px;">{{$f->funcionario->nome}}</span></td>
																<td class="datatable-cell"><span class="codigo" style="width: 200px;">{{$f->funcao}}</span></td>


																<td class="datatable-cell"><span class="codigo" style="width: 200px;">{{$f->funcionario->telefone}} / {{$f->funcionario->celular}}</span></td>

																<td class="datatable-cell"><span class="codigo" style="width: 120px;">
																	<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/deleteFuncionario/{{ $f->id }}" }else{return false} })' href="#!" class="btn btn-danger btn-sm">
																		<span class="la la-trash"></span>
																	</a>

																</span></td>

															</tr>
															@endforeach

														</tbody>
													</table>
													<br>
												</div>
											</div>

										</div>
									</div>
								</div>
							</div>

						</form>

					</div>
				</div>
			</div>
		</div>
	</div>

	<hr>
	<div class="row" id="content">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content">

			<div class="m-6">
				<div class="card card-custom gutter-b example example-compact">
					<div class="col-lg-12">

						<div class="row">

							<div class="col-xl-12">

								<div class="form-group validated col-sm-12 col-lg-12">
									<br>
									<h4>Relatórios da OS</h4>

								</div>

								<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
									<a href="/ordemServico/addRelatorio/{{$ordem->id}}" class="btn btn-info">
										<i class="la la-plus"></i>
										Adicionar Relatório
									</a>
								</div>
							</div>

							<div class="col-xl-12 mt-2">
								<div class="row">
									<div class="col-xl-12">
										<div class="">

											<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

												<table class="datatable-table" style="max-width: 100%; overflow: scroll">
													<thead class="datatable-head">
														<tr class="datatable-row" style="left: 0px;">
															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">#</span></th>
															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Data</span></th>
															<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Usuário</span></th>
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Ações</span></th>
														</tr>
													</thead>

													<tbody class="datatable-body">

														@foreach($ordem->relatorios as $r)
														<tr class="datatable-row" style="left: 0px;">

															<td class="datatable-cell"><span class="codigo" style="width: 80px;">{{$r->id}}</span></td>
															<td class="datatable-cell"><span class="codigo" style="width: 200px;">{{ \Carbon\Carbon::parse($r->data_registro)->format('d/m/Y H:i:s')}}</span></td>


															<td class="datatable-cell"><span class="codigo" style="width: 200px;">{{$r->usuario->nome}}</span></td>

															<td class="datatable-cell"><span class="codigo" style="width: 150px;">
																<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/deleteRelatorio/{{ $r->id }}" }else{return false} })' href="#!" class="btn btn-danger btn-sm">
																	<span class="la la-trash"></span>
																</a>

																<a class="btn btn-warning btn-sm" href="/ordemServico/editRelatorio/{{ $r->id }}">
																	<span class="la la-edit"></span>					
																</a>

																<a class="btn btn-info btn-sm" href="#!" onclick="modal('{{ \Carbon\Carbon::parse($r->data_registro)->format('d/m/Y H:i:s')}}', '{{$r->texto}}')">
																	<span class="la la-sticky-note"></span>					
																</a>

															</span></td>

														</tr>
														@endforeach

													</tbody>
												</table>
												<br>
											</div>
										</div>

									</div>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
		</div>

	</div>

	<hr>
	<div class="row" id="content">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content">

			<div class="m-6">
				<div class="card card-custom gutter-b example example-compact">
					<div class="col-lg-12">
						<!--begin::Portlet-->

						<form method="post" action="/ordemServico/update/{{$ordem->id}}">
							@csrf
							@method('put')
							<div class="row">
								<div class="col-xl-12">

									<div class="form-group validated col-sm-12 col-lg-12">
										<br>
										<h4>Dados adicionais da Ordem</h4>

										<div class="kt-section kt-section--first">
											<div class="kt-section__body">

												<div class="row align-items-center">


													<div class="form-group validated col-6 col-lg-2">
														<label>Desconto</label>
														<input id="desconto" placeholder="Desconto" type="tel" class="form-control money" name="desconto" value="{{number_format($ordem->desconto, $casasDecimais, ',', '.')}}">
													</div>

													<div class="form-group validated col-6 col-lg-2">
														<label>Acréscimo</label>
														<input id="acresimo" placeholder="Acrescimo" type="tel" class="form-control money" name="acrescimo" value="{{number_format($ordem->acrescimo, $casasDecimais, ',', '.')}}">
													</div>

													<div class="form-group validated col-6 col-lg-3">
														<label>Forma de pagamento</label>
														<select name="forma_pagamento" class="custom-select">
															<option value="">Selecione</option>
															@foreach(\App\Models\OrdemServico::tiposPagamento() as $f)
															<option @if($ordem->forma_pagamento == $f) selected @endif value="{{$f}}">{{$f}}</option>
															@endforeach
														</select>
													</div>



													<div class="form-group validated col-lg-8 col-12">
														<label>Observação</label>
														<input placeholder="Observação" type="text" class="form-control" name="observacao" value="{{ $ordem->observacao }}">
													</div>

													<div class="col-sm-3 col-lg-2">
														<button type="submit" class="btn btn-success">Salvar</button>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="m-6">
		<div class="row">
			<div class="col-md-6 col-12">
				<h4>Status: 
					@if($ordem->estado == 'pd')
					<span class="label label-xl label-inline label-light-warning">PENDENTE</span>
					@elseif($ordem->estado == 'ap')
					<span class="label label-xl label-inline label-light-success">APROVADO</span>
					@elseif($ordem->estado == 'rp')
					<span class="label label-xl label-inline label-light-danger">REPROVADO</span>
					@else
					<span class="label label-xl label-inline label-light-info">FINALIZADO</span>
					@endif

				</h4>
				@if($ordem->estado != 'rp')
				<a href="/ordemServico/alterarEstado/{{$ordem->id}}" class="btn btn-primary orange">
					<i class="la la-refresh"></i>
					Alterar estado
				</a>
				@endif

				<a target="_blank" href="/ordemServico/imprimir/{{$ordem->id}}" class="btn btn-info">
					<i class="la la-print"></i> Imprimir
				</a>
			</div>


			<div class="col-md-6 col-12">

				<h5>NFSe: 
					@if($ordem->NfNumero)
					<strong>{{$ordem->NfNumero}}</strong>
					@else
					<strong> -- </strong>
					@endif
				</h5>
				<h5>Total: <strong class="text-success">R$ {{ moeda($ordem->total_os()) }}</strong></h5>
				<h5>Usuario responsável: <strong class="text-success">{{ $ordem->usuario->nome }}</strong></h5>

				<!-- @if(!$ordem->vendaCaixa)
				<a class="btn btn-dark btn-lg" href="/ordemServico/gerarVendaCompleta/{{$ordem->id}}">
					<i class="la la-file"></i>
					Gerar Venda Completa
				</a>
				@else
				<a class="btn btn-dark btn-lg" href="/nfce/detalhes/{{$ordem->vendaCaixa->id}}">
					<i class="la la-file"></i>
					Ver Venda
				</a>
				@endif -->
			</div>
		</div>
	</div>


	<div class="modal fade" id="modal1" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="data"></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<p id="texto"></p>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				</div>
			</div>
		</div>
	</div>

	@endsection

	@section('javascript')

	<script type="text/javascript">
		function modal(data, texto){
			$('#texto').html(texto)
			$('#data').html(data)
			$('#modal1').modal('show')
		}
	</script>

	<script type="text/javascript" src="/js/ordem_servico.js"></script>
	@endsection

