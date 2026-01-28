@extends('default.layout')

@section('css')

<style type="text/css">

	#focus-codigo:hover{
		cursor: pointer
	}

	.search-prod{
		position: absolute;
		top: 0;
		margin-top: 40px;
		left: 10;
		width: 100%;
		max-height: 200px;
		overflow: auto;
		z-index: 9999;
		border: 1px solid #eeeeee;
		border-radius: 4px;
		background-color: #fff;
		box-shadow: 0px 1px 6px 1px rgba(0, 0, 0, 0.4);
	}

	.search-prod label:hover{
		cursor: pointer;
	}

	.search-prod label{
		margin-left: 10px;
		width: 100%;
		margin-top: 7px;
		font-size: 14px;
		color: #000 !important;
	}

	body.loading .modal-loading {
		display: block;
	}

	.modal-loading {
		display: none;
		position: fixed;
		z-index: 10000;
		top: 0;
		left: 0;
		height: 100%;
		width: 100%;
		background: rgba(255, 255, 255, 0.8)
		url("/loading.gif") 50% 50% no-repeat;
	}
</style>
@endsection
@section('content')
<style type="text/css">
	#focus-codigo:hover{
		cursor: pointer
	}
</style>
<div class="card card-custom gutter-b">

	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content" >

			<div class="row" id="anime" style="display: none">
				<div class="col s8 offset-s2">
					<lottie-player src="/anime/{{\App\Models\Venda::randSuccess()}}" background="transparent" speed="0.8" style="width: 100%; height: 300px;" autoplay>
					</lottie-player>
				</div>
			</div>

			<div class="col-lg-12" id="content">
				<!--begin::Portlet-->

				<h3 class="card-title">VENDA BALCÃO</h3>
				<input type="hidden" id="formasPagamento" value="{{json_encode($formasPagamento)}}" name="">
				<input type="hidden" id="_token" value="{{csrf_token()}}" name="">
				<input type="hidden" value="{{$config->parcelamento_maximo}}" id="parcelamento_maximo">
				<input type="hidden" value="{{json_encode($venda)}}" id="venda_edit" name="">
				<input type="hidden" value="{{ $venda->id }}" id="venda_id" name="">
				
				<div class="row">
					<div class="col-xl-12">

						<div class="kt-section kt-section--first">
							<div class="kt-section__body">
								
								<div class="row">

									{!! __view_locais_select() !!}
									
									
									@if(isset($listaPreco))
									<div class="form-group col-lg-3 col-md-4 col-sm-6">
										<label class="col-form-label">Lista de Preço</label>
										<div class="">
											<div class="input-group date">
												<select class="custom-select form-control" id="lista_id" name="lista_id">
													<option value="0">Padrão</option>
													@foreach($listaPreco as $l)
													<option value="{{$l->id}}">{{$l->nome}} - {{$l->percentual_alteracao}}%</option>
													@endforeach
												</select>
											</div>
										</div>
									</div>
									@endif

									<div class="form-group validated col-lg-4 col-12">
										<label class="col-form-label" id="">Cliente</label>
										<div class="input-group">

											<select class="form-control select2" id="kt_select2_3" name="cliente">
												<option value="">Selecione o cliente</option>
												@foreach($clientes as $c)
												<option @if($venda->cliente_id == $c->id) selected @endif value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}})</option>
												@endforeach
											</select>

										</div>
									</div>

									<div class="form-group col-lg-3 col-md-4 col-sm-6 col-6">
										<label class="col-form-label">Identificação Cliente</label>
										<div class="">
											<div class="input-group">
												<input type="text" name="cliente_nome" class="form-control" id="cliente_nome"
												 value="{{$venda->cliente_nome}}"/>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="card card-custom gutter-b">


								<div class="card-body">

									<div class="row">
										<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

											<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
												<!--begin: Wizard Nav-->

												<div class="wizard-nav">

													<div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
														<!--begin::Wizard Step 1 Nav-->
														<div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
															<div class="wizard-label">
																<h3 class="wizard-title">
																	<span>
																		ITENS
																	</span>
																</h3>
																<div class="wizard-bar"></div>
															</div>
														</div>
														<!--end::Wizard Step 1 Nav-->
														<!--begin::Wizard Step 2 Nav-->
														<div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
															<div class="wizard-label">
																<h3 class="wizard-title">
																	<span>
																		TRANSPORTE
																	</span>
																</h3>
																<div class="wizard-bar"></div>
															</div>
														</div>

														<div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
															<div class="wizard-label">
																<h3 class="wizard-title">
																	<span>
																		PAGAMENTO
																	</span>
																</h3>
																<div class="wizard-bar"></div>
															</div>
														</div>
													</div>
												</div>
												<input class="mousetrap" type="" autofocus style="border: none; width: 0px; height: 0px;" id="codBarras">

												<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

													<!--begin: Wizard Form-->
													<form class="form fv-plugins-bootstrap fv-plugins-framework" id="kt_form">
														<!--begin: Wizard Step 1-->
														<div class="pb-5" data-wizard-type="step-content">

															<!-- Inicio da tabela -->

															<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
																<div class="row">
																	<div class="col-xl-12">
																		<div class="row align-items-center">
																			<div class="form-group validated col-sm-6 col-lg-5 col-12">
																				<label class="col-form-label" id="">Produto</label>
																				<div class="input-group">

																					<input placeholder="Digite para buscar o produto" type="search" id="produto-search" class="form-control">
																					<div class="search-prod" style="display: none">
																					</div>

																				</div>

																			</div>

																			<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																				<label class="col-form-label">Quantidade</label>
																				<div class="">
																					<div class="input-group">
																						<input type="text" name="quantidade" class="form-control qtd-p" value="0" id="quantidade"/>
																					</div>
																				</div>
																			</div>

																			<input type="hidden" name="quantidade" class="form-control qtd-p" value="1" id="quantidade_dim"/>

																			<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																				<label class="col-form-label">Valor Unitário</label>
																				<div class="">
																					<div class="input-group">
																						<input @if(!$usuario->permite_desconto) disabled @endif type="text" name="valor" class="form-control money-p" value="0" id="valor"/>
																					</div>
																				</div>
																			</div>

																			<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																				<label class="col-form-label">Subtotal</label>
																				<div class="">
																					<div class="input-group">
																						<input @if(!$usuario->permite_desconto) disabled @endif type="text" name="subtotal" class="form-control money" value="0" id="subtotal"/>
																					</div>
																				</div>
																			</div>
																			<div class="col-lg-1 col-md-4 col-sm-6 col-6">
																				<a href="#!" style="margin-top: 10px;" id="addProd" class="btn btn-light-success px-6 font-weight-bold">
																					<i class="la la-plus"></i>
																				</a>

																			</div>

																		</div>
																	</div>
																</div>


																<!-- Inicio tabela -->

																<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

																	<table class="datatable-table" style="max-width: 100%; overflow: scroll;" id="prod">
																		<thead class="datatable-head">
																			<tr class="datatable-row" style="left: 0px;">
																				<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">Item</span></th>
																				<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">Cód Prod</span></th>
																				<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 300px;">Nome</span></th>
																				<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Quantidade</span></th>
																				<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor</span></th>

																				<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Subtotal</span></th>

																				<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Ações</span></th>
																			</tr>
																		</thead>

																		<tbody id="body" class="datatable-body">
																			<tr class="datatable-row">

																			</tr>
																		</tbody>
																	</table>
																	<!-- Fim da tabela -->
																</div>
																<h6 class="mt-2">Quantidade de itens: <strong id="soma-quantidade" class="text-info">0</strong></h6>
																<h6 class="mt-2">Valor total de produtos: <strong id="soma-produtos" class="text-info">R$ 0,00</strong></h6>

															</div>
														</div>

														<!--end: Wizard Step 1-->
														<!--begin: Wizard Step 2-->
														<div class="pb-5" data-wizard-type="step-content">

															<!-- Inicio do card -->

															<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
																<div class="row">
																	<div class="col-xl-12">
																		<h3>Transportadora</h3>

																		<div class="row align-items-center">
																			<div class="form-group validated col-sm-6 col-lg-7 col-12">
																				<div class="input-group">
																					<select style="width: 80%" class="form-control select2"  id="kt_select2_2" name="transportadora">
																						<option value="">Selecione a transportadora (opcional)</option>
																						@foreach($transportadoras as $t)
																						<option value="{{$t->id}}">{{$t->id}} - {{$t->razao_social}}</option>
																						@endforeach
																					</select>
																				</div>
																			</div>
																		</div>
																	</div>
																</div>
																<hr>

																<div class="row">
																	<div class="col-xl-12">
																		<h3>Frete</h3>

																		<div class="row align-items-center">
																			<div class="form-group validated col-sm-4 col-lg-4 col-8">
																				<label class="col-form-label" id="">Tipo</label>
																				<select class="custom-select form-control" id="frete" name="frete">
																					<option @if($venda->tipo == '0') selected @endif value="0">0 - Emitente</option>
																					<option @if($venda->tipo == '1') selected @endif  value="1">1 - Destinatário</option>
																					<option @if($venda->tipo == '2') selected @endif  value="2">2 - Terceiros</option>

																					<option @if($venda->tipo == '3') selected @endif  value="3">3 - Própio por conta do remetente</option>

																					<option @if($venda->tipo == '4') selected @endif  value="4">4 - Própio por conta do destinatário</option>
																					<option @if($venda->tipo == '9') selected @endif  value="9">9 - Sem Frete</option>
																				</select>
																			</div>

																			<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																				<label class="col-form-label">Placa Veiculo</label>
																				<div class="">
																					<div class="input-group">
																						<input type="text" name="placa" class="form-control" value="{{ $venda->placa }}" id="placa"/>
																					</div>
																				</div>
																			</div>

																			<div class="form-group validated col-sm-2 col-lg-2 col-6">
																				<label class="col-form-label" id="">UF</label>
																				<select class="custom-select form-control" id="uf_placa" name="uf_placa">
																					<option value="--">--</option>
																					@foreach(App\Models\Cidade::estados() as $uf)
																					<option @if($venda->uf == $uf) selected @endif value="{{ $uf }}">{{ $uf }}</option>
																					@endforeach
																				</select>
																			</div>

																			<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																				<label class="col-form-label">Valor</label>
																				<div class="">
																					<div class="input-group">
																						<input type="text" name="valor_frete" class="form-control" value="{{ moeda($venda->valor) }}" id="valor_frete"/>
																					</div>
																				</div>
																			</div>
																		</div>
																	</div>
																</div>
																<hr>
																<div class="row">
																	<div class="col-xl-12">
																		<h3>Volume</h3>

																		<div class="row align-items-center">

																			<div class="form-group col-lg-3 col-md-4 col-sm-6 col-6">
																				<label class="col-form-label">Espécie</label>
																				<div class="">
																					<div class="input-group">
																						<input type="text" name="especie" class="form-control" value="{{ $venda->especie }}" id="especie"/>
																					</div>
																				</div>
																			</div>

																			<div class="form-group col-lg-3 col-md-4 col-sm-6 col-6">
																				<label class="col-form-label">Numeração de Volumes</label>
																				<div class="">
																					<div class="input-group">
																						<input type="text" name="numeracaoVol" class="form-control" value="{{ $venda->numeracao_volumes }}" id="numeracaoVol"/>
																					</div>
																				</div>
																			</div>

																			<div class="form-group col-lg-3 col-md-4 col-sm-6 col-6">
																				<label class="col-form-label">Quantidade de Volumes</label>
																				<div class="">
																					<div class="input-group">
																						<input type="text" name="qtdVol" class="form-control" value="{{ $venda->quantidade_volumes }}" id="qtdVol"/>
																					</div>
																				</div>
																			</div>

																			<div class="form-group col-lg-3 col-md-4 col-sm-6 col-6">
																				<label class="col-form-label">Peso Liquido</label>
																				<div class="">
																					<div class="input-group">
																						<input type="text" name="pesoL" class="form-control" value="{{ $venda->peso_liquido }}" id="pesoL"/>
																					</div>
																				</div>
																			</div>

																			<div class="form-group col-lg-3 col-md-4 col-sm-6 col-6">
																				<label class="col-form-label">Peso Bruto</label>
																				<div class="">
																					<div class="input-group">
																						<input type="text" name="pesoB" class="form-control" value="{{ $venda->peso_bruto }}" id="pesoB"/>
																					</div>
																				</div>
																			</div>

																		</div>
																	</div>
																</div>

															</div>

														</div>
														<!--end: Wizard Step 2-->

														<div class="pb-5" data-wizard-type="step-content">

															<!-- Inicio do card -->

															<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
																<div class="row">
																	<div class="col-xl-12">

																		<div class="row">

																			<div class="col-lg-4 col-md-4 col-sm-5 col-12">
																				<h3>Pagamento</h3>


																				<div class="row">

																					<div class="row">
																						<div class="form-group validated col-sm-12 col-lg-12 col-12">
																							<label class="col-form-label" id="">Tipo de Pagamento</label>
																							<select class="custom-select form-control" id="tipoPagamento" name="tipoPagamento">
																								<option value="--">Selecione o Tipo de pagamento</option>
																								@foreach($tiposPagamento as $key => $t)
																								<option 
																								@if($config->tipo_pagamento_padrao == $key)
																								selected
																								@endif
																								value="{{$key}}">{{$key}} - {{$t}}</option>
																								@endforeach
																							</select>
																						</div>
																					</div>
																					<div class="row">

																						<div class="form-group validated col-sm-12 col-lg-12 col-12">
																							<label class="col-form-label" id="">Forma de Pagamento</label>
																							<select class="custom-select form-control" id="formaPagamento" name="formaPagamento">
																								<option value="--">Selecione a forma de pagamento</option>
																								@foreach($formasPagamento as $f)
																								<option value="{{$f->chave}}">{{$f->nome}}</option>
																								@endforeach
																							</select>
																						</div>
																					</div>
																					<div class="row">

																						<div class="form-group col-lg-8 col-md-8 col-sm-8 col-12">
																							<label class="col-form-label">Qtd. de Parcelas</label>
																							<div class="">
																								<div class="input-group">
																									<input type="text" name="qtdParcelas" class="form-control" value="" id="qtdParcelas"/>
																								</div>
																							</div>
																						</div>

																						<div class="form-group col-lg-4 col-md-4 col-sm-4 col-12">
																							<br>
																							<a data-toggle="modal" onclick="renderizarPagamento()" id="btn-modal-pagamentos"data-target="#modal-pagamentos" type="button" style="margin-top: 20px;" class="btn btn-light-info font-weight-bold disabled">
																								<i class="la la-list"></i>
																							</a>
																						</div>

																					</div>

																					<div class="row">

																						<div class="form-group col-lg-6 col-md-6 col-sm-6 col-12">
																							<label class="col-form-label">Data Vencimento</label>
																							<div class="">
																								<div class="input-group date">
																									<input type="text" name="data" class="form-control" id="kt_datepicker_3" />
																									<div class="input-group-append">
																										<span class="input-group-text">
																											<i class="la la-calendar"></i>
																										</span>
																									</div>
																								</div>
																							</div>
																						</div>

																						<div class="form-group col-lg-6 col-md-6 col-sm-6 col-12">
																							<label class="col-form-label">Valor Parcela</label>
																							<div class="">
																								<div class="input-group">
																									<input type="text" name="valor_parcela" class="form-control" value="" id="valor_parcela"/>
																								</div>
																							</div>
																						</div>
																					</div>
																					<div class="row">
																						<div class="col-lg-12 col-md-12 col-sm-12 col-12">
																							<a id="add-pag" href="#!" style="width: 100%;" class="btn btn-light-success">
																								<i class="la la-check"></i>
																								Adicionar Pagamento
																							</a>
																						</div>
																					</div>

																				</div>
																			</div>

																			<div class="offset-lg-1 col-lg-7 col-md-7 col-sm-6 col-12">
																				<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
																					<div class="row">
																						<div class="col-xl-12">


																							<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

																								<table id="fatura" class="datatable-table" style="max-width: 100%;">
																									<thead class="datatable-head">
																										<tr class="datatable-row" style="left: 0px;">
																											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Parcela</span></th>
																											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Data</span></th>
																											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Valor</span></th>
																											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Tipo</span></th>
																											
																										</tr>
																									</thead>

																									<tbody class="datatable-body">

																									</tbody>
																								</table>
																							</div>

																						</div>
																					</div>
																					<div class="row">
																						<button type="button" style="margin-top: 10px;" id="delete-parcelas" class="btn btn-light-danger">
																							<i class="la la-close"></i>
																							Excluir parcelas
																						</button>
																					</div>
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

									<!-- Fim wizzard -->

								</div>
							</div>
							<div class="card card-custom gutter-b">


								<div class="card-body">
									<div class="row">
										<div class="col-sm-3 col-lg-3 col-md-6 col-xl-3">
											<h3 style="margin-top: 15px;">Valor Total: <strong class="text-success" id="totalNF">R$ 0,00</strong></h3>
										</div>

										<div class="col-sm-2 col-lg-4 col-md-6 col-xl-2">
											<div class="form-group col-lg-12 col-md-12 col-sm-12 col-12">
												<label class="col-form-label">Desconto</label>
												<div class="input-group">
													<div class="input-group-prepend">
														<div class="">
															<div class="input-group">
																<input @if(!$usuario->permite_desconto) readonly @endif type="text" name="desconto" class="form-control" value="" id="desconto"/>
															</div>
														</div>
														<button id="btn-desconto" @if(!$usuario->permite_desconto) disabled @endif onclick="percDesconto()" type="button" class="btn btn-warning btn-sm">
															<i class="la la-percent"></i>
														</button>
													</div>

												</div>

											</div>
										</div>

										<div class="col-sm-2 col-lg-4 col-md-6 col-xl-2">
											<div class="form-group col-lg-12 col-md-12 col-sm-12 col-12">
												<label class="col-form-label">Acréscimo</label>
												<div class="input-group">
													<div class="input-group-prepend">
														<div class="">
															<input type="text" name="acrescimo" class="form-control money" value="" id="acrescimo"/>
														</div>
														<button onclick="setaAcresicmo()" type="button" class="btn btn-success btn-sm">
															<i class="la la-percent"></i>
														</button>
													</div>
												</div>
											</div>
										</div>
										<div class="col-sm-8 col-lg-8 col-md-12 col-xl-5">
											<div class="form-group col-lg-12 col-md-12 col-sm-12 col-12">
												<label class="col-form-label">Informação Adicional</label>
												<div class="">
													<div class="input-group">
														<input type="text" name="obs" class="form-control" value="" id="obs"/>
													</div>
												</div>
											</div>
										</div>
									</div>

									<div class="row mt-4">
										<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6 col-12">
											<button id="salvar-venda" style="width: 100%;" onclick="salvarVenda()" class="btn btn-success" disabled>Atualizar Venda</button>
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

<div class="modal-loading loading-class"></div>

<div class="modal fade" id="modal-cartao" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">INFORME OS DADOS DO CARTÃO</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-sm-3 col-lg-3 col-6">
						<label class="col-form-label">Bandeira</label>
						<select class="custom-select" id="bandeira_cartao">
							<option value="">--</option>
							@foreach(App\Models\Venda::bandeiras() as $key => $b)
							<option value="{{$key}}">{{$b}}</option>
							@endforeach
						</select>
					</div>
					<div class="form-group validated col-sm-4 col-lg-4 col-6">
						<label class="col-form-label">Código autorização(opcional)</label>
						<input type="text" placeholder="Código autorização" id="cAut_cartao" class="form-control" value="">
					</div>

					<div class="form-group validated col-sm-4 col-lg-5 col-12">
						<label class="col-form-label">CNPJ(opcional)</label>
						<input type="text" placeholder="CNPJ" id="cnpj_cartao" data-mask="00.000.000/0000-00" name="cnpj_cartao" class="form-control" value="">
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button style="width: 100%" type="button" data-dismiss="modal" class="btn btn-success font-weight-bold">Salvar</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-pagamentos" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-md" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">PAGAMENTOS</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">

					<div class="form-group validated col-sm-4 col-lg-4">
						<label class="col-form-label" id="">Intervalo (dias)</label>
						<div class="">
							<input type="text" id="intervalo" id="intervalo" class="form-control" value="30">
						</div>
					</div>

					<div class="form-group validated col-sm-6 col-lg-6">
						<label class="col-form-label" id="">Quantidade de parcelas</label>
						<div class="">
							<select class="custom-select form-control" id="qtd_parcelas">

							</select>
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="submit" id="gerarPagamentos" class="btn btn-light-info font-weight-bold">Gerar</button>

			</div>

		</div>
	</div>
</div>


@endsection
@section('javascript')
<script type="text/javascript" src="/js/vendas_balcao.js"></script>
@endsection
