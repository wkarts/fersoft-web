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
		color: #000 !important;
	}
</style>
<div class="card card-custom gutter-b">

	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content" >

			<div class="row" id="anime" style="display: none">
				<div class="col s8 offset-s2">
					<lottie-player src="/anime/{{\App\Models\Venda::randSuccess()}}" background="transparent" speed="0.8" style="width: 100%; height: 300px;" autoplay >
					</lottie-player>
				</div>
			</div>

			<form @if(isset($item) && !isset($clone)) action="/nferemessa/update/{{$item->id}}" @else action="/nferemessa/store" @endif class="col-lg-12" method="post">

				@if(isset($item) && !isset($clone))
				@method('put')
				@endif

				<h3 class="card-title">DADOS INICIAIS</h3>

				<input type="hidden" id="_token" value="{{csrf_token()}}" name="_token">

				@if(isset($contaPadrao) && $contaPadrao != null)
				<input type="hidden" value="1" id="contaPadrao" name="">
				@else
				<input type="hidden" value="0" id="contaPadrao" name="">
				@endif

				@if(isset($config))
				<input type="hidden" id="pass" value="{{ $config->senha_remover }}">
				@endif

				<input type="hidden" value="{{$usuario->permite_desconto}}" id="permite_desconto">
				<input type="hidden" value="{{$config->percentual_max_desconto}}" id="percentual_max_desconto">

				<div class="row">
					<div class="col-xl-12">

						<div class="kt-section kt-section--first">
							<div class="kt-section__body">
								@if(!empresaComFilial())
								<div class="row">
									<div class="col-lg-4 col-md-4 col-sm-6">

										<h6>Ultima NFe: <strong>{{$lastNF}}</strong></h6>
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

									{!! __view_locais_select() !!}

									<div class="form-group col-lg-4 col-md-4 col-sm-6">
										<label class="col-form-label">Natureza de Operação</label>
										<div class="">
											<div class="input-group date">
												<select required class="custom-select form-control" id="natureza" name="natureza">
													<option value="">Selecione</option>
													@foreach($naturezas as $n)
													<option
													@isset($item)
													@if($item->natureza_id == $n->id)
													selected
													@endif
													@else
													@if($config->nat_op_padrao == $n->id)
													selected
													@endif
													@endif
													value="{{$n->id}}">{{$n->natureza}}</option>
													@endforeach
												</select>
											</div>
										</div>
									</div>
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

									<div class="form-group col-lg-2 col-md-4 col-sm-6">
										<label class="col-form-label">Baixar estoque</label>
										<div class="">
											<div class="input-group date">
												<select class="custom-select form-control" id="baixa_estoque" name="baixa_estoque">
													<option @isset($item) @if($item->baixa_estoque == 1) selected @endif @endif value="1">Sim</option>
													<option @isset($item) @if($item->baixa_estoque == 0) selected @endif @endif value="0">Não</option>

												</select>
											</div>
										</div>
									</div>

									<div class="form-group col-lg-2 col-md-4 col-sm-6">
										<label class="col-form-label">Gerar conta a receber</label>
										<div class="">
											<div class="input-group date">
												<select class="custom-select form-control" id="gerar_conta_receber" name="gerar_conta_receber">
													<option @isset($item) @if($item->gerar_conta_receber == 1) selected @endif @endif value="0">Não</option>
													<option @isset($item) @if($item->gerar_conta_receber == 1) selected @endif @endif value="1">Sim</option>

												</select>
											</div>
										</div>
									</div>

									<div class="form-group col-lg-2 col-md-4 col-sm-6">
										<label class="col-form-label">Tipo NFe</label>
										<div class="">
											<div class="input-group date">
												<select class="custom-select form-control" id="tipo_nfe" name="tipo_nfe">
													<option @isset($item) @if($item->tipo_nfe == 'normal') selected @endif @endif value="normal">Normal</option>
													<option @isset($item) @if($item->tipo_nfe == 'remessa') selected @endif @endif value="remessa">Remessa</option>
													<option @isset($item) @if($item->tipo_nfe == 'estorno') selected @endif @endif value="estorno">Estorno</option>
												</select>
											</div>
										</div>
									</div>

								</div>

								<div class="row">
									<div class="form-group validated col-sm-7 col-lg-7 col-12">
										<label class="col-form-label" id="">Cliente</label>
										<div class="input-group">

											<select class="form-control select2" required id="kt_select2_3" name="cliente_id">
												<option value="">Selecione o cliente</option>
												@foreach($clientes as $c)
												<option @isset($item)
												@if($item->cliente_id == $c->id)
												selected
												@endif @endif value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}})</option>
												@endforeach
											</select>

											<button type="button" onclick="novoCliente()" class="btn btn-warning btn-sm">
												<i class="la la-plus-circle icon-add"></i>
											</button>

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
										<div class="form fv-plugins-bootstrap fv-plugins-framework" id="kt_form">
											<!--begin: Wizard Step 1-->
											<div class="pb-5" data-wizard-type="step-content">

												<!-- Inicio dos itens -->

												<div class="col-xl-12">

													<h4>Itens da NFe</h4>
													<div id="kt_datatable" class="row datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
														<table class="datatable-table table-dynamic" style="max-width: 100%;overflow: scroll" id="tbl">
															<thead class="datatable-head">
																<tr class="datatable-row" style="left: 0px;">
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 60px;"></span></th>

																	<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 300px;">Produto</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Valor</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Qtd</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Subtotal</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">CFOP</span></th>

																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">CST/CSOSN</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">CST/PIS</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">CST/COFINS</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">CST/IPI</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">CEST</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">%RedBC</span></th>

																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">R$ VBC ICMS</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">%ICMS</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">R$ ICMS</span></th>

																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">R$ VBC PIS</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">%PIS</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">R$ PIS</span></th>

																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">R$ VBC COFINS</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">%COFINS</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">R$ COFINS</span></th>

																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">R$ VBC IPI</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">%IPI</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">R$ IPI</span></th>


																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">modBCST</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">vBCSTRet</span></th>
																	<!-- <th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">vFrete</span></th> -->
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">vBCST</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">%ICMSST</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">vICMSST</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">pMVAST</span></th>

																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Desc. pedido</span></th>
																	<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Nº item pedido</span></th>
																</tr>
															</thead>

															<tbody class="datatable-body">

																@isset($item)

																@foreach($item->itens as $itemRemessa)
																<tr class="datatable-row dynamic-form">
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 60px;" id="id">
																			<button type="button" class="btn btn-sm btn-line-delete btn-danger">
																				<i class="la la-trash"></i>
																			</button>
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 300px;" id="id">
																			<div class="input-group">
																				<select required class="form-control custom-select-prod" name="produto_id[]">
																					<option selected value="{{$itemRemessa->produto_id}}">
																						{{$itemRemessa->produto->nome}}
																					</option>
																				</select>

																				<button type="button" class="btn btn-dark btn-sm modal-edit-nome">
																					<i class="la la-edit"></i>
																				</button>
																				<input type="hidden" name="descricao_item[]" class="descricao_item" value="{{ $itemRemessa->produto_nome }}">
																				@if($itemRemessa->produto_nome)
																				<span class="text-descricao text-danger">
																					alterado para:
																					<strong>{{ $itemRemessa->produto_nome }}</strong>
																				</span>
																				@endif
																			</div>
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{ number_format($itemRemessa->valor_unitario, $casasDecimais, ',', '')}} " required type="tel" class="form-control money" name="valor_unit[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{ number_format($itemRemessa->quantidade, $casasDecimaisQtd, '.', '')}}" required type="tel" class="form-control qtd-p" name="quantidade[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 120px;" id="id">
																			<input value="{{ number_format($itemRemessa->sub_total, $casasDecimais, ',', '') }}" required type="tel" readonly class="form-control money subtotal-item" name="sub_total[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{($itemRemessa->cfop)}}" required type="tel" class="form-control cfop" name="cfop[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 250px;" id="id">

																			<select class="custom-select form-control" name="cst_csosn[]">
																				@foreach(App\Models\Produto::listaCSTCSOSN() as $key => $c)
																				<option @if($itemRemessa->cst_csosn == $key) selected @endif value="{{$key}}">
																					{{$key}} - {{$c}}
																				</option>
																				@endforeach
																			</select>
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 200px;" id="id">
																			<select class="custom-select form-control" name="cst_pis[]">
																				@foreach(App\Models\Produto::listaCST_PIS_COFINS() as $key => $c)
																				<option @if($itemRemessa->cst_pis == $key) selected @endif value="{{$key}}">
																					{{$key}} - {{$c}}
																				</option>
																				@endforeach
																			</select>
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 200px;" id="id">
																			<select class="custom-select form-control" name="cst_cofins[]">
																				@foreach(App\Models\Produto::listaCST_PIS_COFINS() as $key => $c)
																				<option @if($itemRemessa->cst_cofins == $key) selected @endif value="{{$key}}">
																					{{$key}} - {{$c}}
																				</option>
																				@endforeach
																			</select>
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 200px;" id="id">
																			<select class="custom-select form-control" name="cst_ipi[]">
																				@foreach(App\Models\Produto::listaCST_IPI() as $key => $c)
																				<option @if($itemRemessa->cst_ipi == $key) selected @endif value="{{$key}}">
																					{{$key}} - {{$c}}
																				</option>
																				@endforeach
																			</select>
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{($itemRemessa->cest)}}" type="tel" data-mask="00000000" class="form-control ignore" name="cest[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->pRedBC)}}" type="tel" class="form-control money" name="pRedBC[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="{{moeda($itemRemessa->vbc_icms)}}" name="vbc_icms[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money perc_icms" value="{{moeda($itemRemessa->perc_icms)}}" name="perc_icms[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->valor_icms)}}" type="tel" class="form-control money" name="valor_icms[]">
																		</span>
																	</td>


																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->vbc_pis)}}" type="tel" class="form-control money" name="vbc_pis[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->perc_pis)}}" type="tel" class="form-control money perc_pis" name="perc_pis[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->valor_pis)}}" type="tel" class="form-control money" name="valor_pis[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->vbc_cofins)}}" type="tel" class="form-control money" name="vbc_cofins[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->perc_cofins)}}" type="tel" class="form-control money perc_cofins" name="perc_cofins[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->valor_cofins)}}" type="tel" class="form-control money" name="valor_cofins[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->vbc_ipi)}}" type="tel" class="form-control money" name="vbc_ipi[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->perc_ipi)}}" type="tel" class="form-control money perc_ipi" name="perc_ipi[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->valor_ipi)}}" type="tel" class="form-control money" name="valor_ipi[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<select class="custom-select form-control" name="modBCST[]">
																				@foreach(App\Models\Produto::modalidadesDeterminacaoST() as $key => $o)
																				<option @if($itemRemessa->modBCST == $key) selected @endif value="{{$key}}">
																					{{$key}} - {{$o}}
																				</option>
																				@endforeach
																			</select>
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->vBCSTRet)}}" type="tel" class="form-control money" name="vBCSTRet[]">
																		</span>
																	</td>

																	<td class="datatable-cell d-none">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->vFrete)}}" type="tel" class="form-control money" name="vFrete[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->vBCST)}}" type="tel" class="form-control money" name="vBCST[]">
																		</span>
																	</td>


																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->pICMSST)}}" type="tel" class="form-control money" name="pICMSST[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->vICMSST)}}" type="tel" class="form-control money vICMSST" name="vICMSST[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{moeda($itemRemessa->pMVAST)}}" type="tel" class="form-control money" name="pMVAST[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 120px;" id="id">
																			<input value="{{($itemRemessa->x_pedido)}}" type="text" class="form-control ignore" name="x_pedido[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="{{($itemRemessa->num_item_pedido)}}" type="text" class="form-control ignore" name="num_item_pedido[]">
																		</span>
																	</td>
																</tr>
																@endforeach

																@else
																<tr class="datatable-row dynamic-form">
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 60px;" id="id">
																			<button type="button" class="btn btn-sm btn-line-delete btn-danger">
																				<i class="la la-trash"></i>
																			</button>
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 300px;" id="id">
																			<div class="input-group">

																				<select required class="form-control custom-select-prod" name="produto_id[]">
																					<option value="">Digite para buscar o produto</option>
																				</select>
																				<button type="button" class="btn btn-dark btn-sm modal-edit-nome">
																					<i class="la la-edit"></i>
																				</button>
																				<input type="hidden" name="descricao_item[]" class="descricao_item">
																				<span class="text-descricao text-danger">

																				</span>
																			</span>
																		</div>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input required type="tel" class="form-control money"name="valor_unit[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input required type="tel" class="form-control qtd-p" name="quantidade[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 120px;" id="id">
																			<input required type="tel" class="form-control money subtotal-item" readonly name="sub_total[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input value="" required type="tel" class="form-control cfop" name="cfop[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 250px;" id="id">
																			<select class="custom-select form-control" name="cst_csosn[]">
																				@foreach(App\Models\Produto::listaCSTCSOSN() as $key => $c)
																				<option value="{{$key}}">
																					{{$key}} - {{$c}}
																				</option>
																				@endforeach
																			</select>
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 200px;" id="id">
																			<select class="custom-select form-control" name="cst_pis[]">
																				@foreach(App\Models\Produto::listaCST_PIS_COFINS() as $key => $c)
																				<option value="{{$key}}">
																					{{$key}} - {{$c}}
																				</option>
																				@endforeach
																			</select>
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 200px;" id="id">
																			<select class="custom-select form-control" name="cst_cofins[]">
																				@foreach(App\Models\Produto::listaCST_PIS_COFINS() as $key => $c)
																				<option value="{{$key}}">
																					{{$key}} - {{$c}}
																				</option>
																				@endforeach
																			</select>
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 200px;" id="id">
																			<select class="custom-select form-control" name="cst_ipi[]">
																				@foreach(App\Models\Produto::listaCST_IPI() as $key => $c)
																				<option value="{{$key}}">
																					{{$key}} - {{$c}}
																				</option>
																				@endforeach
																			</select>
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" data-mask="00000000" class="form-control ignore" name="cest[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="pRedBC[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="vbc_icms[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money perc_icms" value="0" name="perc_icms[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="valor_icms[]">
																		</span>
																	</td>


																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0"  name="vbc_pis[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money perc_pis" value="0" name="perc_pis[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="valor_pis[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="vbc_cofins[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money perc_cofins" value="0" name="perc_cofins[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="valor_cofins[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="vbc_ipi[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money perc_ipi" value="0" name="perc_ipi[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="valor_ipi[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<select class="custom-select form-control" name="modBCST[]">
																				@foreach(App\Models\Produto::modalidadesDeterminacaoST() as $key => $o)
																				<option value="{{$key}}">
																					{{$key}} - {{$o}}
																				</option>
																				@endforeach
																			</select>
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="vBCSTRet[]">
																		</span>
																	</td>

																	<td class="datatable-cell d-none">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="vFrete[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="vBCST[]">
																		</span>
																	</td>


																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="pICMSST[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="vICMSST[]">
																		</span>
																	</td>
																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="tel" class="form-control money" value="0" name="pMVAST[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 120px;" id="id">
																			<input type="text" class="form-control ignore" name="x_pedido[]">
																		</span>
																	</td>

																	<td class="datatable-cell">
																		<span class="codigo" style="width: 80px;" id="id">
																			<input type="text" class="form-control ignore" name="num_item_pedido[]">
																		</span>
																	</td>
																</tr>
																@endif

															</tbody>


														</table>
														<br>

													</div>

													<div class="row col-12">
														<button type="button" class="btn btn-info btn-clone-tbl">
															<i class="la la-plus"></i> Adicionar produto
														</button>
													</div>

												</div>
												<!-- Fim dos itens -->

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

																		<select style="width: 80%;" class="form-control select2" id="kt_select2_2" name="transportadora">
																			<option value="null">Selecione a transportadora (opcional)</option>
																			@foreach($transportadoras as $t)
																			<option @isset($item) @if($item->transportadora_id == $t->id) selected @endif @endif value="{{$t->id}}">{{$t->id}} - {{$t->razao_social}}</option>
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
																<div class="form-group validated col-sm-4 col-lg-2 col-8">
																	<label class="col-form-label" id="">Tipo</label>
																	<select class="custom-select form-control" id="frete" name="tipo_frete">
																		<option @isset($item) @if($item->tipo_frete == '0') selected @endif @endif value="0">0 - Emitente</option>
																		<option @isset($item) @if($item->tipo_frete == '1') selected @endif @endif value="1">1 - Destinatário</option>
																		<option @isset($item) @if($item->tipo_frete == '2') selected @endif @endif value="2">2 - Terceiros</option>
																		<option @isset($item) @if($item->tipo_frete == '3') selected @endif @endif value="3">3 - Própio por conta do remetente</option>

																		<option @isset($item) @if($item->tipo_frete == '4') selected @endif @endif value="4">4 - Própio por conta do destinatário</option>
																		<option @isset($item) @if($item->tipo_frete == '9') selected @endif @endif value="9">9 - Sem Frete</option>
																	</select>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Placa Veiculo</label>
																	<div class="">
																		<div class="input-group">
																			<input type="text" name="placa" class="form-control" value="{{isset($item) ? $item->placa : ''}}" id="placa"/>
																		</div>
																	</div>
																</div>

																<div class="form-group validated col-sm-2 col-lg-2 col-6">
																	<label class="col-form-label" id="">UF</label>
																	<select class="custom-select form-control" id="uf_placa" name="uf">
																		<option value="--">--</option>
																		@foreach(App\Models\Cidade::estados() as $uf)
																		<option @isset($item) @if($item->uf == $uf) selected @endif @endif value="{{$uf}}">{{$uf}}</option>
																		@endforeach
																	</select>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Valor</label>
																	<div class="">
																		<div class="input-group">
																			<input type="text" name="valor_frete" class="form-control" value="{{isset($item) ? moeda($item->valor_frete) : ''}}" id="valor_frete"/>
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
																			<input type="text" name="especie" class="form-control" value="{{isset($item) ? ($item->especie) : ''}}" id="especie"/>
																		</div>
																	</div>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Numeração de Volumes</label>
																	<div class="">
																		<div class="input-group">
																			<input type="text" name="numeracao_volumes" class="form-control" value="{{isset($item) ? ($item->numeracao_volumes) : ''}}" id="numeracao_volumes"/>
																		</div>
																	</div>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Quantidade de Volumes</label>
																	<div class="">
																		<div class="input-group">
																			<input type="tel" name="qtd_volumes" class="form-control" value="{{isset($item) ? ($item->qtd_volumes) : ''}}"/>
																		</div>
																	</div>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Peso Liquido</label>
																	<div class="">
																		<div class="input-group">
																			<input type="tel" name="peso_liquido" class="form-control" value="{{isset($item) ? ($item->peso_liquido) : ''}}" data-mask="00000.000" data-mask-reverse="true"/>
																		</div>
																	</div>
																</div>

																<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																	<label class="col-form-label">Peso Bruto</label>
																	<div class="">
																		<div class="input-group">
																			<input type="tel" name="peso_bruto" class="form-control" value="{{isset($item) ? ($item->peso_bruto) : ''}}" data-mask="00000.000" data-mask-reverse="true"/>
																		</div>
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>

											<div class="pb-5" data-wizard-type="step-content">
												<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
													<div class="row">
														<div class="col-xl-12">

															<div class="row">

																<div class="col-12">
																	<h3>Pagamento</h3>
																	<div class="row">

																		<table class="table table-dynamic">
																			<thead>
																				<tr>
																					<th></th>
																					<th>Valor da parcela</th>
																					<th>Data de vencimento</th>
																					<th>Forma de pagamento</th>
																				</tr>
																			</thead>

																			<tbody>

																				@if(isset($item) && sizeof($item->fatura) > 0)
																				@foreach($item->fatura as $f)
																				<tr class="dynamic-form">
																					<td>
																						<button type="button" class="btn btn-sm btn-danger btn-line-delete">
																							<i class="la la-trash"></i>
																						</button>
																					</td>
																					<td>
																						<input name="valor_parcela[]" placeholder="Valor da parcela" type="text" value="{{moeda($f->valor)}}" class="form-control money valor_parcela">
																					</td>
																					<td>
																						<input name="vencimento_parcela[]" placeholder="Vencimento da parcela" value="{{($f->data_vencimento)}}" type="date" class="form-control">
																					</td>
																					<td>
																						<select class="custom-select" name="forma_pagamento_parcela[]">
																							<option value="">Selecione</option>
																							@foreach(App\Models\Venda::tiposPagamento() as $key => $tp)
																							<option @if($f->tipo_pagamento == $key) selected @endif value="{{$key}}">{{$tp}}</option>
																							@endforeach
																						</select>
																					</td>
																				</tr>
																				@endforeach
																				@else
																				<tr class="dynamic-form">
																					<td>
																						<button type="button" class="btn btn-sm btn-danger btn-line-delete">
																							<i class="la la-trash"></i>
																						</button>
																					</td>
																					<td>
																						<input name="valor_parcela[]" placeholder="Valor da parcela" type="text" class="form-control money valor_parcela">
																					</td>
																					<td>
																						<input name="vencimento_parcela[]" placeholder="Vencimento da parcela" type="date" class="form-control">
																					</td>
																					<td>
																						<select class="custom-select forma-pagamento" name="forma_pagamento_parcela[]">
																							<option value="">Selecione</option>
																							@foreach(App\Models\Venda::tiposPagamento() as $key => $tp)
																							<option value="{{$key}}">{{$tp}}</option>
																							@endforeach
																						</select>
																					</td>
																				</tr>
																				@endif

																			</tbody>

																			<tfoot>
																				<tr>
																					<th>Soma</th>
																					<th class="total-fatura">0,00</th>
																				</tr>
																			</tfoot>
																		</table>
																	</div>

																	<div class="row">
																		<button type="button" class="btn btn-info btn-clone-tbl">
																			<i class="la la-plus"></i> Adicionar parcela
																		</button>
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
						</div>

					</div>
				</div>

				<div class="card card-custom gutter-b">

					@if(isset($item) && sizeof($item->referencias) > 0)

					@endif

					<div class="referencias"></div>
					<div class="card-body">

						<div class="row">
							<div class="col-lg-3 col-6">
								<button type="button" data-toggle="modal" data-target="#modal-referencia-nfe" class="btn btn-warning w-100 mt-11">
									<i class="la la-list"></i>
									Referênciar NFe
								</button>
							</div>
							<div class="col-lg-3 col-sm-6 col-6">
								<label class="col-form-label">Data de entrega</label>

								<input type="date" name="data_entrega" class="form-control" value="{{isset($item) ? $item->data_entrega : ''}}" id="data_entrega"/>

							</div>
							<div class="col-lg-2 col-sm-6 col-6">
								<label class="col-form-label">Data de emissão retroativa</label>
								<input type="date" name="data_retroativa" class="form-control" value="{{isset($item) ? $item->data_retroativa : ''}}" id="data_retroativa"/>
							</div>

							<div class="col-lg-2 col-sm-6 col-6">
								<label class="col-form-label">Data de emissão saída</label>
								<input type="date" name="data_saida" class="form-control" value="{{isset($item) ? $item->data_saida : ''}}" id="data_saida"/>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-3 col-lg-3 col-md-6 col-xl-3">
								<h5 style="margin-top: 15px;">Valor Total de Produtos: <strong class="total-nf">R$ 0,00</strong></h5>
								<h5 style="margin-top: 15px;">Valor da NFe: <strong class="total-geral text-success">R$ 0,00</strong></h5>

								<input type="hidden" name="valor_total" value="0" id="valor_total">
							</div>

							<div class="col-sm-2 col-lg-4 col-md-6 col-xl-2">
								<label class="col-form-label">Desconto</label>
								<div class="input-group">
									<div class="input-group-prepend">
										<div class="">
											<div class="input-group">
												<input @if(!$usuario->permite_desconto) readonly @endif type="text" name="desconto" class="form-control" id="desconto" value="{{isset($item) ? moeda($item->desconto) : ''}}"/>
											</div>
										</div>
										<button type="button" id="btn-desconto" @if(!$usuario->permite_desconto) disabled @endif onclick="percDesconto()" type="button" class="btn btn-warning btn-sm">
											<i class="la la-percent"></i>
										</button>
									</div>

								</div>
							</div>

							<div class="col-sm-2 col-lg-4 col-md-6 col-xl-2">
								<div class="form-group col-lg-12 col-md-12 col-sm-12 col-12">
									<label class="col-form-label">Acréscimo</label>
									<div class="input-group">
										<div class="input-group-prepend">
											<div class="">
												<input type="text" name="acrescimo" class="form-control money" id="acrescimo" value="{{isset($item) ? moeda($item->acrescimo) : ''}}"/>
											</div>
											<button type="button" onclick="setaAcresicmo()" type="button" class="btn btn-success btn-sm">
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
											<input type="text" name="obs" class="form-control" value="{{isset($item) ? $item->observacao : ''}}" id="obs"/>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row">

					<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6 col-12">
						<button type="submit" style="width: 100%;" class="btn btn-success btn-nfe" @if(!isset($item)) disabled @endif>@if(isset($item) && !isset($clone)) Atualizar NFe @else Salvar NFe @endif</button>
					</div>
				</div>
			</form>
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
														<option value="{{$u}}">{{$u}}
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
														<option  value="{{$u}}">{{$u}}
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

<div class="modal fade" id="modal-referencia-nfe" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Referência NFe</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-12">
						<div class="form-group validated">
							<div class="row">

								<table class="table table-dynamic">
									<thead>
										<tr>
											<th>Chave</th>
											<th></th>
										</tr>
									</thead>

									<tbody>
										@if(isset($item) && sizeof($item->referencias) > 0)
										@foreach($item->referencias as $r)
										<tr class="dynamic-form">
											<td>
												<input name="chave_referencia[]" placeholder="Chave NFe" type="text" value="{{ $r->chave }}" id="chave" class="form-control chave_nfe">
											</td>
											<td>
												<button type="button" class="btn btn-sm btn-danger btn-line-delete">
													<i class="la la-trash"></i>
												</button>
											</td>
										</tr>
										@endforeach

										@else
										<tr class="dynamic-form">
											<td>
												<input name="chave_referencia[]" placeholder="Chave NFe" type="text" id="" class="form-control chave_nfe">
											</td>
											<td>
												<button type="button" class="btn btn-sm btn-danger btn-line-delete">
													<i class="la la-trash"></i>
												</button>
											</td>
										</tr>
										@endif

									</tbody>
								</table>
							</div>
						</div>

					</div>

				</div>

				<div class="row col-12">
					<button type="button" class="btn btn-info btn-clone-tbl">
						<i class="la la-plus"></i> Adicionar chave
					</button>
				</div>
			</div>
			<div class="modal-footer">
				<button data-dismiss="modal" type="button" data-dismiss="modal" class="btn btn-success btn-referencias font-weight-bold">OK</button>
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

<div class="modal fade" id="modal-nome-produto" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Produto</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-12">
						<label class="col-form-label" id="">Descrição do item</label>
						<input type="text" placeholder="Descrição do item" id="descricao_item" class="form-control" value="">
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" onclick="apontarNovaDescricao()" class="btn btn-success font-weight-bold pula">OK</button>
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
									<input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif" name="cpf_cnpj">

								</div>
							</div>
							<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<label class="col-form-label">UF</label>

								<select class="custom-select form-control" id="sigla_uf" name="sigla_uf">
									@foreach(App\Models\Cidade::estados() as $c)
									<option value="{{$c}}">{{$c}}
									</option>
									@endforeach
								</select>

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
								<label class="col-form-label" id="lbl_ie_rg">RG</label>
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
@endsection

@section('javascript')
{{--    Controle de Carregamento de Scripts     --}}
<script src="{{ asset('js/axios.min.js') }}"></script>
<script src="{{ asset('js/remessa_form.js') }}"></script>
<script type="text/javascript">
	@if(!isset($item))
	$('.descricao_item').val('')
	@endif
</script>
@endsection
