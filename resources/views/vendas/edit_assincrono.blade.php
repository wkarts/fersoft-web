@extends('default.layout')
@section('content')
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
	}

	/* =========================
	   REFORMA (VISUALIZAÇÃO)
	   - NÃO EDITÁVEL
	   - TAMANHOS MENORES
	   ========================= */
	.rt-card{
		border: 1px dashed rgba(0,0,0,.18);
		border-radius: 6px;
		padding: 8px 8px 0 8px;
		margin-top: 6px;
	}
	.rt-title{
		display:flex;
		align-items:center;
		justify-content:space-between;
		margin: 0 0 8px 0;
	}
	.rt-title h6{
		margin:0;
		font-weight:700;
		letter-spacing:.2px;
		font-size: 12px;
	}
	.rt-badge{
		font-size: 10px;
		padding: 1px 6px;
		border-radius: 20px;
		background: rgba(54,153,255,.10);
		color: #3699FF;
		font-weight: 700;
	}
	.rt-mini label{
		font-size: 10px !important;
		margin-bottom: 3px !important;
		opacity: .85;
	}
	.rt-mini .form-control{
		height: calc(1.05em + .65rem + 2px) !important;
		font-size: 11px !important;
		padding: .20rem .38rem !important;
	}
	.rt-mini .form-group{
		margin-bottom: 8px !important;
	}

	.rt-row-tight{ margin-left:-6px; margin-right:-6px; }
	.rt-row-tight > [class*="col-"]{ padding-left:6px; padding-right:6px; }
</style>
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content" >

			<div class="row" id="anime" style="display: none">
				<div class="col s8 offset-s2">
					<lottie-player src="/anime/success.json" background="transparent" speed="0.8" style="width: 100%; height: 300px;" autoplay >
					</lottie-player>
				</div>
			</div>

			<div class="col-lg-12" id="content">
				<!--begin::Portlet-->

				<h3 class="card-title">DADOS INICIAIS</h3>

				<input type="hidden" value="{{json_encode($venda)}}" id="venda_edit" name="">
				<input type="hidden" id="formasPagamento" value="{{json_encode($formasPagamento)}}" name="">
				<input type="hidden" value="{{$usuario->permite_desconto}}" id="permite_desconto">
				<input type="hidden" value="{{$config->percentual_max_desconto}}" id="percentual_max_desconto">
				<input type="hidden" value="{{$config->parcelamento_maximo}}" id="parcelamento_maximo">
				<input type="hidden" id="clientes" value="{{json_encode($clientes)}}" name="">

				@if(isset($config))
				<input type="hidden" id="pass" value="{{ $config->senha_remover }}">
				@endif
				
				<input type="hidden" id="_token" value="{{csrf_token()}}" name="">
				<div class="row">
					<div class="col-xl-12">

						<div class="kt-section kt-section--first">
							<div class="kt-section__body">
								@if(!empresaComFilial())
								<div class="row">
									<div class="col-lg-4 col-md-4 col-sm-6">

										<h6>Ultima NF-e: <strong>{{$lastNF}}</strong></h6>
									</div>
									<div class="col-lg-4 col-md-4 col-sm-6">

										@if($config->ambiente == 2)
										<h6>Ambiente: <strong class="text-primary">Homologação</strong></h6>
										@else
										<h6>Ambiente: <strong class="text-success">Produção</strong></h6>
										@endif
									</div>
								</div>
								@endif

								<div class="row">

									{!! __view_locais_select_edit("Local", $venda->filial_id) !!}

									<div class="form-group col-lg-4 col-md-4 col-sm-6">
										<label class="col-form-label">Natureza de Operação</label>
										<div class="">
											<div class="input-group date">
												<select class="custom-select form-control" id="natureza" name="natureza">
													@foreach($naturezas as $n)
													<option 
													@if($venda->natureza_id == $n->id)
													selected
													@endif
													value="{{$n->id}}">{{$n->natureza}}</option>
													@endforeach
												</select>
											</div>
										</div>
									</div>
									@if(isset($listaPreco))
									<div class="form-group col-lg-2 col-md-4 col-sm-6">
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
								</div>

								<div class="row">
									<div class="form-group validated col-sm-7 col-lg-8 col-12">
										<label class="col-form-label" id="">Cliente</label>
										<div class="input-group">

											<select class="form-control select2" id="kt_select2_3" name="cliente">
												<option value="null">Selecione o cliente</option>
												@foreach($clientes as $c)
												<option @if($venda->cliente_id == $c->id) selected @endif value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}})</option>
												@endforeach
											</select>

											<button type="button" onclick="novoCliente()" class="btn btn-warning btn-sm">
												<i class="la la-plus-circle icon-add"></i>
											</button>

										</div>
									</div>
								</div>

								<div class="row" id="div-cliente" style="display: none">
									<div class="col-xl-12">

										<div class="card card-custom gutter-b">
											<div class="card-body">

												<h4 class="center-align">CLIENTE SELECIONADO</h4>
												<div class="row">

													<div class="col-sm-6 col-lg-6 col-12">
														<h5>Razão Social: <strong id="razao_social" class="text-success">--</strong></h5>
														<h5>Nome Fantasia: <strong id="nome_fantasia" class="text-success">--</strong></h5>
														<h5>Logradouro: <strong id="logradouro" class="text-success">--</strong></h5>
														<h5>Numero: <strong id="numero" class="text-success">--</strong></h5>
														<h5>Limite: <strong id="limite" class="text-success"></strong></h5>
													</div>
													<div class="col-sm-6 col-lg-6 col-12">
														<h5>CPF/CNPJ: <strong id="cnpj" class="text-success">--</strong></h5>
														<h5>RG/IE: <strong id="ie" class="text-success">--</strong></h5>
														<h5>Fone: <strong id="fone" class="text-success">--</strong></h5>
														<h5>Cidade: <strong id="cidade" class="text-success">--</strong></h5>

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


				<!-- Wizzard -->
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
																<div class="form-group validated col-sm-6 col-lg-6 col-12">
																	<label class="col-form-label" id="">Produto</label>
																	<div class="input-group">
																		<div class="input-group-prepend">
																			<span class="input-group-text" id="focus-codigo">
																				<li class="la la-barcode"></li>
																			</span>
																		</div>


																		<input placeholder="Digite para buscar o produto" type="search" id="produto-search" class="form-control">
																		<div class="search-prod" style="display: none">
																		</div>
																		
																		<button type="button" onclick="novoProduto()" class="btn btn-info btn-sm">
																			<i class="la la-plus-circle icon-add"></i>
																		</button>
																	</div>
																</div>

																<div class="form-group col-lg-1 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Qtd.</label>
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
																			<input @if(!$usuario->permite_desconto) disabled @endif type="text" name="subtotal" class="form-control" value="0" id="subtotal"/>
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

													<!-- =========================
														 REFORMA TRIBUTÁRIA • RESUMO (2 CARDS)
														 - 1) Totais IBS/CBS/IS + NBS (GERAL)
														 - 2) Detalhe do item selecionado (POR ITEM)
														 Observação: apenas visualização (readonly). Os valores são preenchidos via JS.
														 ========================= -->
													<div class="row" id="rt-reforma-row" style="margin-top: 6px;">
														<div class="col-lg-6 col-md-12">
															<div class="rt-card rt-mini" id="rt-visao-geral">
																<div class="rt-title">
																	<h6>REFORMA • VISÃO GERAL</h6>
																	<span class="rt-badge">Somente leitura</span>
																</div>

																<div class="row">
																	<div class="form-group col-6">
																		<label class="col-form-label">Base IBS (Total)</label>
																		<input type="text" readonly class="form-control" id="rt_total_ibs_bc" value="">
																	</div>
																	<div class="form-group col-6">
																		<label class="col-form-label">Valor IBS (Total)</label>
																		<input type="text" readonly class="form-control" id="rt_total_ibs_vlr" value="">
																	</div>
																	<div class="form-group col-6">
																		<label class="col-form-label">Base CBS (Total)</label>
																		<input type="text" readonly class="form-control" id="rt_total_cbs_bc" value="">
																	</div>
																	<div class="form-group col-6">
																		<label class="col-form-label">Valor CBS (Total)</label>
																		<input type="text" readonly class="form-control" id="rt_total_cbs_vlr" value="">
																	</div>
																	<div class="form-group col-6">
																		<label class="col-form-label">Base IS (Total)</label>
																		<input type="text" readonly class="form-control" id="rt_total_is_bc" value="">
																	</div>
																	<div class="form-group col-6">
																		<label class="col-form-label">Valor IS (Total)</label>
																		<input type="text" readonly class="form-control" id="rt_total_is_vlr" value="">
																	</div>

																	<div class="form-group col-12" style="margin-bottom: 8px !important;">
																		<label class="col-form-label">NBS (Resumo)</label>
																		<input type="text" readonly class="form-control" id="rt_total_nbs_resumo" value="">
																	</div>
																</div>
															</div>
														</div>

														<div class="col-lg-6 col-md-12">
															<div class="rt-card rt-mini" id="rt-visao-item">
																<div class="rt-title">
																	<h6>REFORMA • ITEM SELECIONADO</h6>
																	<span class="rt-badge" id="rt_item_badge">Item: -</span>
																</div>

																<div class="row">
																	<div class="form-group col-12" style="margin-bottom: 8px !important;">
																		<label class="col-form-label">Classificação IBS/CBS</label>
																		<input type="text" readonly class="form-control" id="rt_item_classificacao" value="">
																	</div>

																	<div class="form-group col-6">
																		<label class="col-form-label">% IBS</label>
																		<input type="text" readonly class="form-control" id="rt_item_ibs_aliq" value="">
																	</div>
																	<div class="form-group col-6">
																		<label class="col-form-label">Vlr IBS</label>
																		<input type="text" readonly class="form-control" id="rt_item_ibs_vlr" value="">
																	</div>

																	<div class="form-group col-6">
																		<label class="col-form-label">% CBS</label>
																		<input type="text" readonly class="form-control" id="rt_item_cbs_aliq" value="">
																	</div>
																	<div class="form-group col-6">
																		<label class="col-form-label">Vlr CBS</label>
																		<input type="text" readonly class="form-control" id="rt_item_cbs_vlr" value="">
																	</div>

																	<div class="form-group col-6">
																		<label class="col-form-label">% IS</label>
																		<input type="text" readonly class="form-control" id="rt_item_is_aliq" value="">
																	</div>
																	<div class="form-group col-6">
																		<label class="col-form-label">Vlr IS</label>
																		<input type="text" readonly class="form-control" id="rt_item_is_vlr" value="">
																	</div>

																	<div class="form-group col-12" style="margin-bottom: 8px !important;">
																		<label class="col-form-label">NBS (Item)</label>
																		<input type="text" readonly class="form-control" id="rt_item_nbs" value="">
																	</div>
																</div>
															</div>
														</div>
													</div>
													<!-- /REFORMA TRIBUTÁRIA • RESUMO (2 CARDS) -->
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

																		<select class="form-control select2" id="kt_select2_2" style="width: 80%;" name="transportadora">
																			<option value="null">Selecione a transportadora (opcional)</option>
																			@foreach($transportadoras as $t)
																			<option 
																			@if($venda->transportadora_id == $t->id)
																			selected
																			@endif
																			value="{{$t->id}}">{{$t->id}} - {{$t->razao_social}}</option>
																			@endforeach
																		</select>
																		<button type="button" onclick="novaTransportadora()" class="btn btn-warning btn-sm">
																			<i class="la la-plus-circle icon-add"></i>
																		</button>
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
																	<label class="col-form-label" id="">Transportadora</label>
																	<select class="custom-select form-control" id="frete" name="frete">
																		<option @if($config->frete_padrao == '0') selected @endif value="0">0 - Emitente</option>
																		<option @if($config->frete_padrao == '1') selected @endif  value="1">1 - Destinatário</option>
																		<option @if($config->frete_padrao == '2') selected @endif  value="2">2 - Terceiros</option>

																		<option @if($config->frete_padrao == '3') selected @endif value="3">3 - Própio por conta do remetente</option>

																		<option @if($config->frete_padrao == '4') selected @endif value="4">4 - Própio por conta do destinatário</option>
																		
																		<option @if($config->frete_padrao == '9') selected @endif  value="9">9 - Sem Frete</option>
																	</select>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Placa Veiculo</label>
																	<div class="">
																		<div class="input-group">
																			<input type="text" name="placa" class="form-control" value="@if($venda->frete != null) {{$venda->frete->placa}} @endif" id="placa"/>
																		</div>
																	</div>
																</div>

																<div class="form-group validated col-sm-2 col-lg-2 col-6">
																	<label class="col-form-label" id="">UF</label>
																	<select class="custom-select form-control" id="uf_placa" name="uf_placa">
																		<option value="--">--</option>
																		@foreach(App\Models\Venda::estados() as $e)
																		<option @if($venda->frete != null) @if($venda->frete->uf == $e) selected @endif @endif value="{{$e}}">{{$e}}</option>
																		@endforeach
																	</select>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Valor</label>
																	<div class="">
																		<div class="input-group">
																			<input type="text" name="valor_frete" class="form-control" value="@if($venda->frete != null) {{$venda->frete->valor}} @endif" id="valor_frete"/>
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
																
																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Espécie</label>
																	<div class="">
																		<div class="input-group">
																			<input type="text" name="especie" class="form-control" value="@if($venda->frete != null) {{$venda->frete->especie}} @endif" id="especie"/>
																		</div>
																	</div>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Numeração de Volumes</label>
																	<div class="">
																		<div class="input-group">
																			<input type="text" name="numeracaoVol" class="form-control" value="@if($venda->frete != null) {{$venda->frete->numeracaoVolumes}} @endif" id="numeracaoVol"/>
																		</div>
																	</div>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Quantidade de Volumes</label>
																	<div class="">
																		<div class="input-group">
																			<input type="text" name="qtdVol" class="form-control" value="@if($venda->frete != null) {{$venda->frete->qtdVolumes}} @endif" id="qtdVol"/>
																		</div>
																	</div>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Peso Liquido</label>
																	<div class="">
																		<div class="input-group">
																			<input type="text" name="pesoL" class="form-control" value="@if($venda->frete != null) {{$venda->frete->peso_liquido}} @endif" id="pesoL"/>
																		</div>
																	</div>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Peso Bruto</label>
																	<div class="">
																		<div class="input-group">
																			<input type="text" name="pesoB" class="form-control" value="@if($venda->frete != null) {{$venda->frete->peso_bruto}} @endif" id="pesoB"/>
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
																					@if($venda->tipo_pagamento == $key)
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
																				<label class="col-form-label">Quantidade de Parcelas</label>
																				<div class="">
																					<div class="input-group">
																						<input type="text" name="qtdParcelas" class="form-control" value="" id="qtdParcelas"/>
																					</div>
																				</div>
																			</div>
																			<div class="form-group col-lg-4 col-md-4 col-sm-4 col-12">
																				<br>
																				<a href="#!" data-toggle="modal" onclick="renderizarPagamento()" id="btn-modal-pagamentos"data-target="#modal-pagamentos" type="button" style="margin-top: 20px;" class="btn btn-light-info font-weight-bold">
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

																					<table id="fatura" class="datatable-table" style="max-width: 100%; overflow: scroll;">
																						<thead class="datatable-head">
																							<tr class="datatable-row" style="left: 0px;">
																								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Parcela</span></th>
																								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Data</span></th>
																								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Valor</span></th>
																								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Tipo</span></th>
																								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">#</span></th>
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
					</div>
				</div>

				<div class="card card-custom gutter-b">
					<div class="card-body">
						<div class="row">
							<div class="col-lg-3 col-6">
								<button data-toggle="modal" data-target="#modal-referencia-nfe" class="btn btn-warning w-100 mt-11">
									<i class="la la-list"></i>
									Referênciar NFe
								</button>
							</div>
							<div class="col-xl-2 col-sm-6 col-6">
								<label class="col-form-label">Data de entrega</label>
								<input type="text" name="data_entrega" class="form-control date-input" value="@if($venda->data_entrega != null) {{\Carbon\Carbon::parse($venda->data_entrega)->format('d/m/Y')}} @endif" id="data_entrega"/>
							</div>
							<div class="col-xl-2 col-sm-6 col-6">
								<label class="col-form-label">Data de emissão retroativa</label>
								<input type="text" name="data_retroativa" class="form-control date-input" value="@if($venda->data_retroativa != null) {{\Carbon\Carbon::parse($venda->data_retroativa)->format('d/m/Y')}} @endif" id="data_retroativa"/>
							</div>

							<div class="col-xl-2 col-sm-6 col-6">
								<label class="col-form-label">Data de emissão saída</label>
								<input type="text" name="data_saida" class="form-control date-input" value="@if($venda->data_saida != null) {{\Carbon\Carbon::parse($venda->data_saida)->format('d/m/Y')}} @endif" id="data_saida"/>
							</div>

							<div class="col-xl-2 col-sm-6 col-6">
								<label class="col-form-label">Data da venda</label>
								<input type="text" name="data_venda" class="form-control date-input" value="{{\Carbon\Carbon::parse($venda->created_at)->format('d/m/Y')}}" id="data_venda"/>
							</div>

						</div>
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
													<input @if(!$usuario->permite_desconto) readonly @endif type="text" name="desconto" class="form-control" value="{{ moeda($venda->desconto) }}" id="desconto"/>
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
												<input type="text" name="acrescimo" class="form-control money" value="{{ moeda($venda->acrescimo) }}" id="acrescimo"/>
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
											<input type="text" name="obs" class="form-control" value="{{$venda->observacao}}" id="obs"/>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6 col-12">
								@if(!isset($venda))
								<a id="salvar-orcamento" style="width: 100%;" href="#" onclick="swal('Erro', 'Não é possível salvar como orçamento em editar!', 'error')" class="btn btn-primary disabled">Salvar como Orçamento</a>
								@endif
							</div>

							<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6 col-12">
								<a id="salvar-venda" style="width: 100%;" href="#" @if(isset($venda)) onclick="atualizarVenda('nfe')" @else onclick="salvarVenda('nfe')" @endif class="btn btn-success disabled">Atualizar Venda</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


<div class="modal fade" id="modal-cliente" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Novo Cliente</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">

				<div class="row">
					<div class="col-xl-12">

						<div class="row">
							<div class="form-group col-sm-12 col-lg-12">
								<label>Pessoa:</label>
								<div class="radio-inline">
									<label class="radio radio-success">
										<input name="group1" type="radio" id="pessoaFisica"/>
										<span></span>
										FISICA
									</label>
									<label class="radio radio-success">
										<input name="group1" type="radio" id="pessoaJuridica"/>
										<span></span>
										JURIDICA
									</label>
								</div>
							</div>
						</div>
						<div class="row">

							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label" id="lbl_cpf_cnpj">CPF</label>
								<div class="">
									<input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif cpf_cnpj" name="cpf_cnpj">

								</div>
							</div>

							<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<br><br>
								<a type="button" id="btn-consulta-cadastro" onclick="consultaCadastro()" class="btn btn-success spinner-white spinner-right">
									<span>
										<i class="fa fa-search"></i>
									</span>
								</a>
							</div>

						</div>

						<div class="row">
							<div class="form-group validated col-sm-10 col-lg-6">
								<label class="col-form-label">Razao Social/Nome</label>
								<div class="">
									<input id="razao_social2" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-10 col-lg-6">
								<label class="col-form-label">Nome Fantasia</label>
								<div class="">
									<input id="nome_fantasia2" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif">
								</div>
							</div>

							<div class="form-group validated col-sm-3 col-lg-3">
								<label class="col-form-label" id="lbl_ie_">IE</label>
								<div class="">
									<input type="text" id="ie_rg" class="form-control @if($errors->has('ie_rg')) is-invalid @endif">
								</div>
							</div>
							<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
								<label class="col-form-label">Consumidor Final</label>

								<select class="custom-select form-control" id="consumidor_final">
									<option value="1">SIM</option>
									<option value="0">NAO</option>
								</select>

							</div>

							<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
								<label class="col-form-label">Contribuinte</label>

								<select class="custom-select form-control" id="contribuinte">

									<option value="1">SIM</option>
									<option value="0">NAO</option>
								</select>
							</div>

							<div class="form-group validated col-sm-3 col-lg-2">
								<label class="col-form-label" id="lbl_ie_rg">Limite de Venda</label>
								<div class="">
									<input type="text" id="limite_venda" class="form-control @if($errors->has('limite_venda')) is-invalid @endif money"  value="0">

								</div>
							</div>

						</div>
						<hr>
						<h5>Endereço de Faturamento</h5>
						<div class="row">
							<div class="form-group validated col-sm-8 col-lg-6">
								<label class="col-form-label">Rua</label>
								<div class="">
									<input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-2 col-lg-2">
								<label class="col-form-label">Número</label>
								<div class="">
									<input id="numero2" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-4">
								<label class="col-form-label">Bairro</label>
								<div class="">
									<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-2">
								<label class="col-form-label">CEP</label>
								<div class="">
									<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif">
								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Email</label>
								<div class="">
									<input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif">
								</div>
							</div>

							@php
							$cidade = App\Models\Cidade::getCidadeCod($config->codMun);
							@endphp
							<div class="form-group validated col-lg-4 col-md-6 col-sm-10">
								<label class="col-form-label">Cidade</label><br>
								<select style="width: 100%" class="form-control select2" id="kt_select2_4">
									@foreach(App\Models\Cidade::all() as $c)
									<option @if($cidade->id == $c->id) selected @endif value="{{$c->id}}">
										{{$c->nome}} ({{$c->uf}})
									</option>
									@endforeach
								</select>

							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Telefone (Opcional)</label>
								<div class="">
									<input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif">
								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Celular (Opcional)</label>
								<div class="">
									<input id="celular" type="text" class="form-control @if($errors->has('celular')) is-invalid @endif">
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
				<button type="button" onclick="salvarCliente()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
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

<div class="modal fade" id="modal-fatura" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Editar fatura</h5>
			</div>
			<div class="modal-body">
				<div class="row">
					<input type="hidden" id="fat_numero">
					<div class="form-group validated col-lg-3 col-6">
						<label class="col-form-label" id="">Valor</label>
						<input type="text" id="valor_fatura" class="form-control money">
					</div>

					<div class="form-group validated col-lg-3 col-6">
						<label class="col-form-label" id="">Data</label>
						<input type="text" id="data_fatura" class="form-control" data-mask="00/00/0000" data-mask-reverse="true">
					</div>

					<div class="form-group validated col-lg-4 col-6">
						<label class="col-form-label" id="">Tipo de pagamento</label>
						<select class="custom-select form-control" id="tipo_fatura">
							@foreach($tiposPagamento as $key => $t)
							<option value="{{$t}}">{{$key}} - {{$t}}</option>
							@endforeach
						</select>
					</div>

					<div class="form-group validated col-lg-2 col-6">
						<label class="col-form-label" id="">Entrada</label>
						<select id="data_entrada" class="custom-select">
							<option value="0">Não</option>
							<option value="1">Sim</option>
						</select>
					</div>

				</div>

			</div>
			<div class="modal-footer">
				<button type="button" id="btn-frete" onclick="salvarFatura()" class="btn btn-success font-weight-bold spinner-white spinner-right">Savar</button>
			</div>
		</div>
	</div>
</div>

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
							<option
							@if($venda->bandeira_cartao == $key)
							selected
							@endif
							value="{{$key}}">{{$b}}</option>
							@endforeach
						</select>
					</div>
					<div class="form-group validated col-sm-4 col-lg-4 col-6">
						<label class="col-form-label">Código autorização(opcional)</label>
						<input value="{{$venda->cAut_cartao}}" type="text" placeholder="Código autorização" id="cAut_cartao" class="form-control" value="">
					</div>

					<div class="form-group validated col-sm-4 col-lg-5 col-12">
						<label class="col-form-label">CNPJ(opcional)</label>
						<input value="{{$venda->cnpj_cartao}}" type="text" placeholder="CNPJ" id="cnpj_cartao" data-mask="00.000.000/0000-00" name="cnpj_cartao" class="form-control" value="">
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button style="width: 100%" type="button" data-dismiss="modal" class="btn btn-success font-weight-bold">Salvar</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-pag-outros" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">INFORME A DESCRIÇAO DO TIPO DE PAGAMENTO OUTROS</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">


					<div class="form-group validated col-12">
						<label class="col-form-label">Descrição</label>
						<input type="text" placeholder="Descrição" id="descricao_pag_outros" name="descricao_pag_outros" class="form-control" value="">
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button style="width: 100%" type="button" data-dismiss="modal" class="btn btn-success font-weight-bold">Salvar</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-edit-item" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Alterar item da venda</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">

					<div class="form-group validated col-12 col-lg-6">
						<label class="col-form-label">Descrição</label>
						<input type="text" id="produto_nome" name="produto_nome" class="form-control" value="">
					</div>

					<input type="hidden" id="id_item" name="">
					<div class="form-group validated col-12 col-lg-3">
						<label class="col-form-label">Quantidade</label>
						<input type="text" id="qtd_item" name="qtd_item" class="form-control qtd-p" value="">
					</div>

					<div class="form-group validated col-12 col-lg-3">
						<label class="col-form-label">Valor unitário</label>
						<input type="text" id="vl_item" name="vl_item" class="form-control money" value="">
					</div>

					<div class="form-group validated col-12 col-lg-4">
						<label class="col-form-label">Número do pedido</label>
						<input type="text" id="x_pedido" name="x_pedido" class="form-control" value="">
					</div>

					<div class="form-group validated col-12 col-lg-4">
						<label class="col-form-label">Nº item do pedido</label>
						<input type="text" id="num_item_pedido" name="num_item_pedido" class="form-control" value="">
					</div>

				</div>

			</div>
			<div class="modal-footer">
				<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
				<button type="button" id="salvar-edit" data-dismiss="modal" class="btn btn-success font-weight-bold">Salvar</button>
			</div>
		</div>
	</div>
</div>


<div class="modal fade" id="modal-produto" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Novo Produto</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">

				<div class="wizard wizard-3" id="kt_wizard_v4" data-wizard-state="between" data-wizard-clickable="true">
					<!--begin: Wizard Nav-->

					<div class="wizard-nav">

						<div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
							<!--begin::Wizard Step 1 Nav-->
							<div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
								<div class="wizard-label">
									<h3 class="wizard-title">
										<span>
											IDENTIFICAÇÃO
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
											ALÍQUOTAS
										</span>
									</h3>
									<div class="wizard-bar"></div>
								</div>
							</div>
						</div>
					</div>

					<div class="card-body">
						<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

							<!--begin: Wizard Form-->
							<form class="form fv-plugins-bootstrap fv-plugins-framework form-prod" id="kt_form">
								<!--begin: Wizard Step 1-->
								<p class="kt-widget__data text-danger">Campos com (*) obrigatório</p>

								<div class="pb-5" data-wizard-type="step-content">
									<div class="row">

										<div class="col-xl-12">
											<div class="row">

												<div class="form-group validated col-sm-9 col-lg-9">
													<label class="col-form-label">Nome*</label>
													<div class="">
														<input type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" id="nome">
													</div>
												</div>

												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Referência</label>
													<div class="">
														<input type="text" class="form-control @if($errors->has('referencia')) is-invalid @endif" id="referencia">
													</div>
												</div>

												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Valor de Compra*</label>
													<div class="">
														<input type="text" id="valor_compra" class="form-control @if($errors->has('valor_compra')) is-invalid @endif money">
													</div>
												</div>

												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">% lucro*</label>
													<div class="">
														<input type="text" id="percentual_lucro" class="form-control money" name="percentual_lucro" value="{{$config->percentual_lucro_padrao }}">
													</div>
												</div>

												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Valor de Venda*</label>
													<div class="">
														<input type="text" id="valor_venda" class="form-control @if($errors->has('valor_venda')) is-invalid @endif money">

													</div>
												</div>


												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Iniciar com Estoque</label>
													<div class="">
														<input type="text" id="estoque" class="form-control @if($errors->has('estoque')) is-invalid @endif money">

													</div>
												</div>

												<div class="form-group validated col-sm-4 col-lg-4">
													<label class="col-form-label">Código de Barras EAN13</label>
													<div class="">
														<input type="text" class="form-control @if($errors->has('codBarras')) is-invalid @endif" id="codBarras">
													</div>
												</div>


												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Estoque minimo</label>
													<div class="">
														<input type="text" id="estoque_minimo" class="form-control @if($errors->has('estoque_minimo')) is-invalid @endif">
													</div>
												</div>


												<div class="form-group validated col-sm-6 col-lg-4">
													<label class="col-form-label">Gerenciar estoque</label>
													<div class="col-6">
														<span class="switch switch-outline switch-primary">
															<label>
																<input value="true" type="checkbox" id="gerenciar_estoque">
																<span></span>
															</label>
														</span>
													</div>
												</div>

												<div class="form-group validated col-sm-6 col-lg-2">
													<label class="col-form-label">Inativo</label>
													<div class="col-6">
														<span class="switch switch-outline switch-danger">
															<label>
																<input value="true" type="checkbox" id="inativo">
																<span></span>
															</label>
														</span>
													</div>
												</div>

												<div class="form-group validated col-lg-3 col-md-5 col-sm-10">
													<label class="col-form-label ">Categoria</label>
													<div class="input-group">

														<select id="categoria_id" class="form-control custom-select">
															@foreach($categorias as $cat)
															<option value="{{$cat->id}}">{{$cat->nome}}
															</option>
															@endforeach
														</select>

													</div>
												</div>

												<div class="form-group validated col-sm-4 col-lg-3">
													<label class="col-form-label">Limite maximo desconto %</label>
													<div class="">
														<input type="text" id="limite_maximo_desconto" class="form-control @if($errors->has('limite_maximo_desconto')) is-invalid @endif">
													</div>
												</div>



												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Alerta de Venc. (Dias)</label>
													<div class="">
														<input type="text" id="alerta_vencimento" class="form-control @if($errors->has('alerta_vencimento')) is-invalid @endif">
													</div>
												</div>


												<div class="form-group validated col-lg-3 col-md-6 col-sm-10">
													<label class="col-form-label">Unidade de compra *</label>

													<select class="custom-select form-control" id="unidade_compra" id="unidade_compra">
														@foreach($unidadesDeMedida as $u)
														<option @if($u == 'UN') selected @endif value="{{$u}}">{{$u}}
														</option>
														@endforeach
													</select>
												</div>


												<div class="form-group validated col-sm-3 col-lg-3" id="conversao" style="display: none">
													<label class="col-form-label">Conversão Unitária</label>
													<div class="">
														<input type="text" id="conversao_unitaria" class="form-control @if($errors->has('conversao_unitaria')) is-invalid @endif">
													</div>
												</div>
												<div class="form-group validated col-lg-3 col-md-6 col-sm-10">
													<label class="col-form-label">Unidade de venda *</label>

													<select class="custom-select form-control" id="unidade_venda">
														@foreach($unidadesDeMedida as $u)
														<option @if($u == 'UN') selected @endif value="{{$u}}">{{$u}}
														</option>
														@endforeach
													</select>

												</div>

												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">NCM *</label>
													<div class="">
														<input data-mask="0000.00.00" type="text" id="NCM" class="form-control @if($errors->has('NCM')) is-invalid @endif" value="{{$tributacao->ncm_padrao}}">
													</div>
												</div>

												<div class="form-group validated col-sm-2 col-lg-3">
													<label class="col-form-label">CEST</label>
													<div class="">
														<input type="text" id="CEST" class="form-control @if($errors->has('CEST')) is-invalid @endif">
													</div>
												</div>
												<hr>

												<div class="form-group validated col-12">
													<h3>Derivado Petróleo</h3>
												</div>

												<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
													<label class="col-form-label">ANP</label>

													<select class="custom-select form-control" id="anp">
														<option value="">--</option>
														@foreach($anps as $key => $a)
														<option value="{{$key}}">[{{$key}}] - {{$a}}
														</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">%GLP</label>

													<input type="text" id="perc_glp" class="form-control @if($errors->has('perc_glp')) is-invalid @endif trib">
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">%GNn</label>

													<input type="text" id="perc_gnn" class="form-control @if($errors->has('perc_gnn')) is-invalid @endif trib">
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">%GNi</label>

													<input type="text" id="perc_gni" class="form-control @if($errors->has('perc_gni')) is-invalid @endif trib">
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">Valor de partida</label>

													<input type="text" id="valor_partida" class="form-control @if($errors->has('valor_partida')) is-invalid @endif money">
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">Un. tributável</label>

													<input type="text" id="unidade_tributavel" class="form-control @if($errors->has('unidade_tributavel')) is-invalid @endif" data-mask="AAAA">
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">Qtd. tributável</label>

													<input type="text" id="quantidade_tributavel" class="form-control @if($errors->has('quantidade_tributavel')) is-invalid @endif" data-mask="00000,00" data-mask-reverse="true">
												</div>


												<hr>
												<div class="form-group validated col-12">
													<h3>Dados de dimensão e peso do produto (Opcional)</h3>
												</div>


												<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
													<label class="col-form-label">Largura (cm)</label>

													<input type="text" id="largura" class="form-control @if($errors->has('largura')) is-invalid @endif">

												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
													<label class="col-form-label">Altura (cm)</label>

													<input type="text" id="altura" class="form-control @if($errors->has('altura')) is-invalid @endif">
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
													<label class="col-form-label">Comprimento (cm)</label>

													<input type="text" id="comprimento" class="form-control @if($errors->has('comprimento')) is-invalid @endif">
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
													<label class="col-form-label">Peso liquido</label>

													<input type="text" id="peso_liquido" class="form-control @if($errors->has('peso_liquido')) is-invalid @endif">
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
													<label class="col-form-label">Peso bruto</label>

													<input type="text" id="peso_bruto" class="form-control @if($errors->has('peso_bruto')) is-invalid @endif">
												</div>

												<div class="col-lg-12 col-xl-12">
													<p class="text-danger">*Se atente a preencher todos os dados para utilizar a Api dos correios.</p>
												</div>

											</div>

										</div>
									</div>

								</div>
							</div>
							<div class="pb-5" data-wizard-type="step-content">

								<div class="row">

									<div class="col-xl-12">

										<div class="row">

											<div class="form-group validated col-lg-12 col-md-10 col-sm-10">
												<label class="col-form-label">
													@if($tributacao->regime == 1)
													CST
													@else
													CSOSN
													@endif
												*</label>

												<select class="custom-select form-control" id="CST_CSOSN">
													@foreach($listaCSTCSOSN as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_CSOSN)
														selected
														@endif
														@else
														@if($key == $config->CST_CSOSN_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>

											</div>

											<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
												<label class="col-form-label">CST PIS *</label>

												<select class="custom-select form-control" id="CST_PIS">
													@foreach($listaCST_PIS_COFINS as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_PIS)
														selected
														@endif
														@else
														@if($key == $config->CST_PIS_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>

											</div>

											<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
												<label class="col-form-label">CST COFINS *</label>

												<select class="custom-select form-control" id="CST_COFINS">
													@foreach($listaCST_PIS_COFINS as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_COFINS)
														selected
														@endif
														@else
														@if($key == $config->CST_COFINS_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>

											</div>

											<div class="form-group validated col-lg-12 col-md-10 col-sm-10">
												<label class="col-form-label">CST IPI *</label>

												<select class="custom-select form-control" id="CST_IPI">
													@foreach($listaCST_IPI as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_IPI)
														selected
														@endif
														@else
														@if($key == $config->CST_IPI_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>
											</div>

											<div class="form-group validated col-lg-12 col-md-10 col-sm-10">
												<label class="col-form-label">
													@if($tributacao->regime == 1)
													CST Exportação
													@else
													CSOSN Exportação
													@endif
												*</label>

												<select class="custom-select form-control" id="CST_CSOSN_EXP">
													<option value="">--</option>
													@foreach($listaCSTCSOSN as $key => $c)
													<option value="{{$key}}" @if(isset($produto)) @if($key==$produto->CST_CSOSN_EXP)
														selected
														@endif
														@endif

														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>

											</div>

											<div class="form-group validated col-sm-4 col-lg-3">
												<label class="col-form-label">CFOP saida interno *</label>
												<div class="">
													<input type="text" id="CFOP_saida_estadual" class="form-control @if($errors->has('CFOP_saida_estadual')) is-invalid @endif" value="{{{ isset($produto->CFOP_saida_estadual) ? $produto->CFOP_saida_estadual : $natureza->CFOP_saida_estadual }}}">
												</div>
											</div>
											<div class="form-group validated col-sm-4 col-lg-3">
												<label class="col-form-label">CFOP saida externo *</label>
												<div class="">
													<input type="text" id="CFOP_saida_inter_estadual" class="form-control @if($errors->has('CFOP_saida_inter_estadual')) is-invalid @endif" value="{{{ isset($produto->CFOP_saida_inter_estadual) ? $produto->CFOP_saida_inter_estadual : $natureza->CFOP_saida_inter_estadual }}}">
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%ICMS *</label>
												<div class="">
													<input type="text" id="perc_icms" class="form-control trib @if($errors->has('perc_icms')) is-invalid @endif" value="{{{ isset($produto->perc_icms) ? $produto->perc_icms : $tributacao->icms }}}">
												</div>
											</div>
											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%PIS *</label>
												<div class="">
													<input type="text" id="perc_pis" class="form-control trib @if($errors->has('perc_pis')) is-invalid @endif" value="{{{ isset($produto->perc_pis) ? $produto->perc_pis : $tributacao->pis }}}">
												</div>
											</div>
											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%COFINS *</label>
												<div class="">
													<input type="text" id="perc_cofins" class="form-control trib @if($errors->has('perc_cofins')) is-invalid @endif" value="{{{ isset($produto->perc_cofins) ? $produto->perc_cofins : $tributacao->cofins }}}">
												</div>
											</div>
											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%IPI *</label>
												<div class="">
													<input type="text" id="perc_ipi" class="form-control trib @if($errors->has('perc_ipi')) is-invalid @endif" value="{{{ isset($produto->perc_ipi) ? $produto->perc_ipi : $tributacao->ipi }}}">
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%ISS*</label>
												<div class="">
													<input type="text" id="perc_iss" class="form-control trib @if($errors->has('perc_iss')) is-invalid @endif" value="{{{ isset($produto->perc_iss) ? $produto->perc_iss : 0.00 }}}">
												</div>
											</div>

											<div class="form-group validated col-sm-2 col-lg-2">
												<label class="col-form-label">%Redução BC</label>
												<div class="">
													<input type="text" id="pRedBC" class="form-control @if($errors->has('pRedBC')) is-invalid @endif" value="{{{ isset($produto->pRedBC) ? $produto->pRedBC : 0.00 }}}">

												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">Cod benefício</label>
												<div class="">
													<input type="text" id="cBenef" class="form-control @if($errors->has('cBenef')) is-invalid @endif" value="{{{ isset($produto->cBenef) ? $produto->cBenef : old('cBenef') }}}">

												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%ICMS interestadual</label>
												<div class="">
													<input type="text" id="perc_icms_interestadual" class="form-control @if($errors->has('perc_icms_interestadual')) is-invalid @endif trib" value="{{{ isset($produto->perc_icms_interestadual) ? $produto->perc_icms_interestadual : old('perc_icms_interestadual') }}}">

												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%ICMS interno</label>
												<div class="">
													<input type="text" id="perc_icms_interno" class="form-control @if($errors->has('perc_icms_interno')) is-invalid @endif trib" value="{{{ isset($produto->perc_icms_interno) ? $produto->perc_icms_interno : old('perc_icms_interno') }}}">

												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%FCP interestadual</label>
												<div class="">
													<input type="text" id="perc_fcp_interestadual" class="form-control @if($errors->has('perc_fcp_interestadual')) is-invalid @endif trib" value="{{{ isset($produto->perc_fcp_interestadual) ? $produto->perc_fcp_interestadual : old('perc_fcp_interestadual') }}}">

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
			<div class="modal-footer">
				<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
				<button type="button" onclick="salvarProduto()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-cod-barras" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">INFORME O CÓDIGO MANUAL</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-sm-12 col-lg-12 col-12">
						<label class="col-form-label" id="">Código de barras</label>
						<input type="text" placeholder="Código de barras" id="cod-barras2" name="cod-barras2" class="form-control pula" value="">
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button style="width: 100%" type="button" onclick="apontarCodigoDeBarras()" class="btn btn-success font-weight-bold pula">OK</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-dimensao" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-md" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Dimensão do produto</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-sm-4 col-lg-4 dim-area">
						<label class="col-form-label" id="">Altura</label>
						<div class="">
							<input type="text" id="altura-dim" class="form-control money">
						</div>
					</div>
					<div class="form-group validated col-sm-4 col-lg-4 dim-area">
						<label class="col-form-label" id="">Largura</label>
						<div class="">
							<input type="text" id="largura-dim" class="form-control money">
						</div>
					</div>
					<div class="form-group validated col-sm-4 col-lg-4 dim-area">
						<label class="col-form-label" id="">Profundidade</label>
						<div class="">
							<input type="text" id="profundidade-dim" class="form-control money">
						</div>
					</div>

					<div class="form-group validated col-sm-4 col-lg-4 dim-dimensao">
						<label class="col-form-label" id="">Lateral esquerda</label>
						<div class="">
							<input type="text" id="esquerda-dim" class="form-control money">
						</div>
					</div>
					<div class="form-group validated col-sm-4 col-lg-4 dim-dimensao">
						<label class="col-form-label" id="">Lateral direita</label>
						<div class="">
							<input type="text" id="direita-dim" class="form-control money">
						</div>
					</div>
					<div class="form-group validated col-sm-4 col-lg-4 dim-dimensao">
						<label class="col-form-label" id="">Superior</label>
						<div class="">
							<input type="text" id="superior-dim" class="form-control money">
						</div>
					</div>
					<div class="form-group validated col-sm-4 col-lg-4 dim-dimensao">
						<label class="col-form-label" id="">Inferior</label>
						<div class="">
							<input type="text" id="inferior-dim" class="form-control money">
						</div>
					</div>

					<div class="form-group validated col-sm-4 col-lg-4">
						<label class="col-form-label" id="">Acréscimo perca</label>
						<div class="">
							<input disabled type="text" id="acrescimo_perca-dim" class="form-control money">
						</div>
					</div>
					<div class="form-group validated col-sm-4 col-lg-4">
						<label class="col-form-label" id="">Quantidade</label>
						<div class="">
							<input type="text" id="qtd-dim" class="form-control money" value="1">
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button onclick="calcularDimensao()" data-dismiss="modal" type="button" data-dismiss="modal" class="btn btn-success font-weight-bold">OK</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-referencia-nfe" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Referência NF-e</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-12 col-lg-10">

						<div class="">
							<input placeholder="Chave" type="text" id="chave" class="form-control">
						</div>
					</div>

					<div class="form-group validated col-12 col-lg-2">
						<button onclick="addChave()" class="btn btn-success">
							<i class="la la-plus"></i>
						</button>
					</div>
				</div>

				<div class="row">
					<div class="col-12">

						<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
							<table class="datatable-table" id="chaves">
								<thead class="datatable-head">
									<tr class="datatable-row">
										<th class="datatable-cell datatable-cell-sort">
											Chave
										</th>
									</tr>
								</thead>
								<tbody class="datatable-body" id="chaves"></tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button data-dismiss="modal" type="button" data-dismiss="modal" class="btn btn-success font-weight-bold">OK</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="modal-transportadora" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Nova Transportadora</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">

				<div class="row">
					<div class="col-xl-12">

						<div class="row">
							<div class="form-group col-sm-12 col-lg-12">
								<label>Pessoa:</label>
								<div class="radio-inline">
									<label class="radio radio-success">
										<input name="group1" type="radio" id="pessoaFisica3"/>
										<span></span>
										FISICA
									</label>
									<label class="radio radio-success">
										<input name="group1" type="radio" id="pessoaJuridica3"/>
										<span></span>
										JURIDICA
									</label>

								</div>

							</div>
						</div>
						<div class="row">

							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label" id="lbl_cpf_cnpj3">CPF</label>
								<div class="">
									<input type="text" id="cpf_cnpj3" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif" name="cpf_cnpj">

								</div>
							</div>
							<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<label class="col-form-label text-left col-lg-12 col-sm-12">UF</label>

								<select class="custom-select form-control" id="sigla_uf3" name="sigla_uf">
									@foreach(App\Models\Cidade::estados() as $c)
									<option value="{{$c}}">{{$c}}
									</option>
									@endforeach
								</select>

							</div>
							<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<br><br>
								<a type="button" id="btn-consulta-cadastro3" onclick="consultaCadastro3()" class="btn btn-success spinner-white spinner-right">
									<span>
										<i class="fa fa-search"></i>
									</span>
								</a>
							</div>

						</div>

						<div class="row">
							<div class="form-group validated col-sm-10 col-lg-10">
								<label class="col-form-label">Razao Social/Nome</label>
								<div class="">
									<input id="razao_social3" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif">

								</div>
							</div>
						</div>

						<div class="row">
							<div class="form-group validated col-sm-8 col-lg-8">
								<label class="col-form-label">Logradouro</label>
								<div class="">
									<input id="logradouro3" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-4">
								<label class="col-form-label">Email</label>
								<div class="">
									<input id="email3" type="text" class="form-control @if($errors->has('email')) is-invalid @endif">

								</div>
							</div>

							@php
							$cidade = App\Models\Cidade::getCidadeCod($config->codMun);
							@endphp
							<div class="form-group validated col-lg-6 col-md-6 col-sm-10">
								<label class="col-form-label text-left col-lg-4 col-sm-12">Cidade</label><br>
								<select style="width: 100%" class="form-control select2" id="kt_select2_10">
									@foreach(App\Models\Cidade::all() as $c)
									<option @if($cidade->id == $c->id) selected @endif value="{{$c->id}}">
										{{$c->nome}} ({{$c->uf}})
									</option>
									@endforeach
								</select>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Telefone (Opcional)</label>
								<div class="">
									<input id="telefone3" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif">
								</div>
							</div>

						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
				<button type="button" onclick="salvarTransportadora()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</div>
	</div>
</div>
<script>
(function(){
    function rtParseNumber(v){
        if(v === null || v === undefined) return 0;
        if(typeof v === 'number') return isFinite(v) ? v : 0;
        v = String(v).trim();
        if(!v) return 0;

        // remove currency and spaces
        v = v.replace(/\s+/g,'').replace(/[R$\u00A0]/g,'');
        // keep digits, dot, comma, minus
        v = v.replace(/[^0-9\-,.]/g,'');

        // BR format: 1.234,56 -> 1234.56
        var hasComma = v.indexOf(',') !== -1;
        var hasDot = v.indexOf('.') !== -1;

        if(hasComma && hasDot){
            // assume dot thousands, comma decimal
            v = v.replace(/\./g,'').replace(',', '.');
        }else if(hasComma && !hasDot){
            v = v.replace(',', '.');
        }
        var n = parseFloat(v);
        return isFinite(n) ? n : 0;
    }

    function rtFmtMoney(n){
        n = rtParseNumber(n);
        try{
            return 'R$ ' + n.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }catch(e){
            return 'R$ ' + (Math.round(n*100)/100).toFixed(2).replace('.',',');
        }
    }

    function rtFmtPct(n){
        n = rtParseNumber(n);
        try{
            return n.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '%';
        }catch(e){
            return (Math.round(n*100)/100).toFixed(2).replace('.',',') + '%';
        }
    }

    function rtFirst(obj, keys, def){
        if(!obj) return def;
        for(var i=0;i<keys.length;i++){
            var k = keys[i];
            if(Object.prototype.hasOwnProperty.call(obj,k) && obj[k] !== null && obj[k] !== undefined && obj[k] !== ''){
                return obj[k];
            }
        }
        return def;
    }

    function rtGetItens(){
        // Prefer o array global do JS da tela
        if(Array.isArray(window.ITENS)) return window.ITENS;

        // fallback: tenta montar algo mínimo a partir da tabela (sem reforma)
        var trs = document.querySelectorAll('#body tr');
        var fake = [];
        trs.forEach(function(tr){
            fake.push({});
        });
        return fake;
    }

    function rtUpdateTotalCard(){
        var itens = rtGetItens();

        var sumIbsBc=0, sumIbsVlr=0, sumCbsBc=0, sumCbsVlr=0, sumIsBc=0, sumIsVlr=0;
        var nbsSet = {};
        for(var i=0;i<itens.length;i++){
            var it = itens[i] || {};
            sumIbsBc  += rtParseNumber(rtFirst(it, ['rt_ibs_bc','ibs_bc','vbcibs','bc_ibs','ibsBase'], 0));
            sumIbsVlr += rtParseNumber(rtFirst(it, ['rt_ibs_vlr','ibs_vlr','vibs','valor_ibs','ibs'], 0));
            sumCbsBc  += rtParseNumber(rtFirst(it, ['rt_cbs_bc','cbs_bc','vbccbs','bc_cbs','cbsBase'], 0));
            sumCbsVlr += rtParseNumber(rtFirst(it, ['rt_cbs_vlr','cbs_vlr','vcbs','valor_cbs','cbs'], 0));
            sumIsBc   += rtParseNumber(rtFirst(it, ['rt_is_bc','is_bc','vbcis','bc_is','isBase'], 0));
            sumIsVlr  += rtParseNumber(rtFirst(it, ['rt_is_vlr','is_vlr','vis','valor_is','is'], 0));

            var nbs = rtFirst(it, ['nbs','NBS','nbs_codigo','nbsCodigo','codigo_nbs'], '');
            if(nbs) nbsSet[String(nbs)] = true;
        }

        var el;
        el = document.getElementById('rt_total_ibs_bc');  if(el) el.value = rtFmtMoney(sumIbsBc);
        el = document.getElementById('rt_total_ibs_vlr'); if(el) el.value = rtFmtMoney(sumIbsVlr);
        el = document.getElementById('rt_total_cbs_bc');  if(el) el.value = rtFmtMoney(sumCbsBc);
        el = document.getElementById('rt_total_cbs_vlr'); if(el) el.value = rtFmtMoney(sumCbsVlr);
        el = document.getElementById('rt_total_is_bc');   if(el) el.value = rtFmtMoney(sumIsBc);
        el = document.getElementById('rt_total_is_vlr');  if(el) el.value = rtFmtMoney(sumIsVlr);

        var nbsKeys = Object.keys(nbsSet);
        var nbsResumo = nbsKeys.length ? ('Códigos: ' + nbsKeys.slice(0,6).join(', ') + (nbsKeys.length>6 ? ' …' : '') + ' | Qtd: ' + nbsKeys.length) : '';
        el = document.getElementById('rt_total_nbs_resumo'); if(el) el.value = nbsResumo;
    }

    function rtFillItemCard(it, index){
        it = it || {};
        var badge = document.getElementById('rt_item_badge');
        if(badge) badge.textContent = 'Item: ' + (index !== null && index !== undefined ? (index+1) : '-');

        var el;
        el = document.getElementById('rt_item_classificacao');
        if(el) el.value = rtFirst(it, ['classificacao_ibs_cbs','ibs_cbs_classificacao','class_trib_ibs_cbs','class_trib','classificacao','rt_item_classificacao'], '');

        el = document.getElementById('rt_item_ibs_aliq'); if(el) el.value = rtFmtPct(rtFirst(it, ['rt_ibs_aliq','ibs_aliq','pIbs','aliq_ibs','ibsAliq'], 0));
        el = document.getElementById('rt_item_ibs_vlr');  if(el) el.value = rtFmtMoney(rtFirst(it, ['rt_ibs_vlr','ibs_vlr','vIbs','valor_ibs','ibs'], 0));

        el = document.getElementById('rt_item_cbs_aliq'); if(el) el.value = rtFmtPct(rtFirst(it, ['rt_cbs_aliq','cbs_aliq','pCbs','aliq_cbs','cbsAliq'], 0));
        el = document.getElementById('rt_item_cbs_vlr');  if(el) el.value = rtFmtMoney(rtFirst(it, ['rt_cbs_vlr','cbs_vlr','vCbs','valor_cbs','cbs'], 0));

        el = document.getElementById('rt_item_is_aliq');  if(el) el.value = rtFmtPct(rtFirst(it, ['rt_is_aliq','is_aliq','pIs','aliq_is','isAliq'], 0));
        el = document.getElementById('rt_item_is_vlr');   if(el) el.value = rtFmtMoney(rtFirst(it, ['rt_is_vlr','is_vlr','vIs','valor_is','is'], 0));

        el = document.getElementById('rt_item_nbs');
        if(el) el.value = rtFirst(it, ['nbs','NBS','nbs_codigo','nbsCodigo','codigo_nbs'], '');
    }

    function rtBindRowClick(){
        var tbody = document.getElementById('body');
        if(!tbody) return;

        tbody.addEventListener('click', function(ev){
            var tr = ev.target;
            while(tr && tr.tagName !== 'TR') tr = tr.parentElement;
            if(!tr) return;

            // tenta descobrir o índice pelo 1º TD (Item)
            var tds = tr.querySelectorAll('td');
            var idx = null;
            if(tds && tds.length){
                var n = parseInt(String(tds[0].innerText || '').trim(), 10);
                if(!isNaN(n) && n > 0) idx = n-1;
            }

            var itens = rtGetItens();
            if(idx !== null && idx >= 0 && idx < itens.length){
                rtFillItemCard(itens[idx], idx);
            }else{
                // fallback: seleciona o primeiro, se existir
                if(itens.length) rtFillItemCard(itens[0], 0);
            }
        }, true);
    }

    // Atualização contínua (leve) sem depender do JS existente
    var lastSig = null;
    function rtTick(){
        try{
            var itens = rtGetItens();
            var sig = '';
            if(Array.isArray(window.ITENS)){
                sig = 'A|' + itens.length;
                for(var i=0;i<Math.min(itens.length, 10);i++){
                    var it = itens[i] || {};
                    sig += '|' + rtParseNumber(rtFirst(it, ['subtotal','SubTotal','valor_total','total'], 0));
                    sig += '|' + rtParseNumber(rtFirst(it, ['rt_ibs_vlr','ibs_vlr','valor_ibs','ibs'], 0));
                    sig += '|' + rtParseNumber(rtFirst(it, ['rt_cbs_vlr','cbs_vlr','valor_cbs','cbs'], 0));
                    sig += '|' + rtParseNumber(rtFirst(it, ['rt_is_vlr','is_vlr','valor_is','is'], 0));
                }
            }else{
                var rows = document.querySelectorAll('#body tr').length;
                sig = 'D|' + rows;
            }
            if(sig !== lastSig){
                lastSig = sig;
                rtUpdateTotalCard();

                // se não tem item selecionado ainda, tenta preencher o primeiro
                var badge = document.getElementById('rt_item_badge');
                if(badge && (badge.textContent === 'Item: -' || badge.textContent === '' || badge.textContent === 'Item:')){
                    var itens2 = rtGetItens();
                    if(itens2.length) rtFillItemCard(itens2[0], 0);
                }
            }
        }catch(e){}
        setTimeout(rtTick, 700);
    }

    document.addEventListener('DOMContentLoaded', function(){
        rtBindRowClick();
        rtTick();
    });
})();
</script>
@endsection
