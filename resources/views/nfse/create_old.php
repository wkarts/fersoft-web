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

			<div class="col-lg-12" id="content">
				<!--begin::Portlet-->

				<h3 class="card-title">DADOS INICIAIS</h3>

				<input type="hidden" id="_token" value="{{csrf_token()}}" name="">
				
				<div class="row">
					<div class="col-xl-12">

						<div class="kt-section kt-section--first">
							<div class="kt-section__body">
								@if(!empresaComFilial())
								<div class="row">
									<div class="col-lg-4 col-md-4 col-sm-6">

										<h6>Ultima NFSe: <strong>1</strong></h6>
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
									
								</div>


							</div>
						</div>
					</div>

				</div>


				<!-- Wizzard -->
				<div class="card card-custom gutter-b">


					<div class="card-body">
						<form class="form fv-plugins-bootstrap fv-plugins-framework" id="kt_form" method="post" @isset($item) action="/nfse/update/{{$item->id}}" @else action="/nfse/store" @endif>
							@csrf
							@isset($item)
							@method('put')
							@endif
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
																Tomador
															</span>
														</h3>
														<div class="wizard-bar"></div>
													</div>
												</div>
												<!--end::Wizard Step 1 Nav-->
												<!--begin::Wizard Step 2 Nav-->
												<div class="wizard-step tablet" data-wizard-type="step" data-wizard-state="current">
													<div class="wizard-label" id="grade">
														<h3 class="wizard-title">
															<span>
																Serviço
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

											<!--begin: Wizard Step 1-->
											<div class="pb-5" data-wizard-type="step-content">

												<!-- Inicio da tabela -->
												<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

													<div class="row">
														<div class="form-group validated col-sm-7 col-lg-7 col-12">
															<label class="col-form-label" id="">Cliente <span class="text-danger">*</span></label>
															<div class="input-group">

																<select required class="form-control select2" id="kt_select2_3" name="cliente">
																	<option value="">Selecione o cliente</option>
																	@foreach($clientes as $c)
																	<option @isset($item) @if($c->id == $item->cliente_id) selected @endif @endisset 
																		@if(old('cliente') == $c->id)
																		selected
																		@endif value="{{$c->id}}">{{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}})
																	</option>
																	@endforeach
																</select>
																@if($errors->has('cliente'))
																<div class="invalid-feedback">
																	{{ $errors->first('cliente') }}
																</div>
																@endif
															</div>
														</div>

														<div class="form-group col-lg-4 col-12">
															<label class="col-form-label">Natureza de Operação <span class="text-danger">*</span></label>
															<div class="">
																<div class="input-group">
																	<input required type="text" name="natureza_operacao" class="form-control @if($errors->has('natureza_operacao')) is-invalid @endif" id="natureza_operacao" value="{{{ isset($item) ? $item->natureza_operacao : old('natureza_operacao') }}}"/>
																	@if($errors->has('natureza_operacao'))
																	<div class="invalid-feedback">
																		{{ $errors->first('natureza_operacao') }}
																	</div>
																	@endif
																</div>
															</div>
														</div>
													</div>

													<div class="row">

														<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
															<label class="col-form-label">CPF/CNPJ <span class="text-danger">*</span></label>
															<div class="">
																<div class="input-group">
																	<input  type="tel" value="{{{ isset($item) ? $item->documento : old('documento') }}}" name="documento" class="form-control cpf_cnpj @if($errors->has('documento')) is-invalid @endif" id="documento"/>
																	@if($errors->has('documento'))
																	<div class="invalid-feedback">
																		{{ $errors->first('documento') }}
																	</div>
																	@endif
																</div>
															</div>
														</div>

														<div class="form-group col-lg-4 col-md-6 col-sm-6 col-12">
															<label class="col-form-label">Razão Social <span class="text-danger">*</span></label>
															<div class="">
																<div class="input-group">
																	<input value="{{{ isset($item) ? $item->razao_social : old('razao_social') }}}" required type="text" name="razao_social" class="form-control @if($errors->has('razao_social')) is-invalid @endif" id="razao_social"/>
																	@if($errors->has('razao_social'))
																	<div class="invalid-feedback">
																		{{ $errors->first('razao_social') }}
																	</div>
																	@endif
																</div>
															</div>
														</div>

														<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
															<label class="col-form-label">Inscrição municipal (I.M)</label>
															<div class="">
																<div class="input-group">
																	<input value="{{{ isset($item) ? $item->im : old('im') }}}" type="tel" name="im" class="form-control" id="im"/>
																</div>
															</div>
														</div>

														<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
															<label class="col-form-label">CEP <span class="text-danger">*</span></label>
															<div class="">
																<div class="input-group">
																	<input required type="tel" name="cep" class="form-control cep @if($errors->has('cep')) is-invalid @endif" id="cep" value="{{{ isset($item) ? $item->cep : old('cep') }}}"/>
																	@if($errors->has('cep'))
																	<div class="invalid-feedback">
																		{{ $errors->first('cep') }}
																	</div>
																	@endif
																</div>
															</div>
														</div>

														<div class="form-group col-lg-4 col-md-6 col-sm-6 col-12">
															<label class="col-form-label">Rua <span class="text-danger">*</span></label>
															<div class="">
																<div class="input-group">
																	<input required type="text" name="rua" class="form-control @if($errors->has('rua')) is-invalid @endif" id="rua" value="{{{ isset($item) ? $item->rua : old('rua') }}}"/>
																	@if($errors->has('rua'))
																	<div class="invalid-feedback">
																		{{ $errors->first('rua') }}
																	</div>
																	@endif
																</div>
															</div>
														</div>

														<div class="form-group col-lg-2 col-md-6 col-sm-6 col-12">
															<label class="col-form-label">Número <span class="text-danger">*</span></label>
															<div class="">
																<div class="input-group">
																	<input required type="text" name="numero" class="form-control @if($errors->has('numero')) is-invalid @endif" id="numero" value="{{{ isset($item) ? $item->numero : old('numero') }}}"/>
																	@if($errors->has('numero'))
																	<div class="invalid-feedback">
																		{{ $errors->first('numero') }}
																	</div>
																	@endif
																</div>
															</div>
														</div>

														<div class="form-group col-lg-3 col-md-6 col-sm-6 col-12">
															<label class="col-form-label">Bairro <span class="text-danger">*</span></label>
															<div class="">
																<div class="input-group">
																	<input required type="text" name="bairro" class="form-control @if($errors->has('bairro')) is-invalid @endif" id="bairro" value="{{{ isset($item) ? $item->bairro : old('bairro') }}}"/>
																	@if($errors->has('bairro'))
																	<div class="invalid-feedback">
																		{{ $errors->first('numero') }}
																	</div>
																	@endif
																</div>
															</div>
														</div>

														<div class="form-group col-lg-3 col-md-6 col-sm-6 col-12">
															<label class="col-form-label">Complemento</label>
															<div class="">
																<div class="input-group">
																	<input type="text" name="complemento" class="form-control" id="complemento" value="{{{ isset($item) ? $item->complemento : old('complemento') }}}"/>
																</div>
															</div>
														</div>

														<div class="form-group validated col-lg-4 col-md-6 col-sm-10">
															<label class="col-form-label">Cidade <span class="text-danger">*</span></label><br>
															<select name="cidade_id" required style="width: 100%" class="form-control select2" id="kt_select2_4">
																@foreach(App\Models\Cidade::all() as $c)
																<option @isset($item) @if($c->id == $item->cidade_id) selected @endif @endisset 
																	@if(old('cidade_id') == $c->id)
																	selected
																	@endif value="{{$c->id}}">
																	{{$c->nome}} ({{$c->uf}})
																</option>
																@endforeach
															</select>

															@if($errors->has('cidade_id'))
															<div class="invalid-feedback">
																{{ $errors->first('cidade_id') }}
															</div>
															@endif

														</div>

														<div class="form-group col-lg-3 col-md-6 col-sm-6 col-12">
															<label class="col-form-label">Email</label>
															<div class="">
																<div class="input-group">
																	<input type="email" name="email" class="form-control" id="email" value="{{{ isset($item) ? $item->email : old('email') }}}"/>
																</div>
															</div>
														</div>

														<div class="form-group col-lg-2 col-md-6 col-sm-6 col-12">
															<label class="col-form-label">Telefone</label>
															<div class="">
																<div class="input-group">
																	<input type="tel" name="telefone" class="form-control" id="telefone" value="{{{ isset($item) ? $item->telefone : old('telefone') }}}"/>
																</div>
															</div>
														</div>
													</div>

												</div>
											</div>

											<!--end: Wizard Step 1-->
											<!--begin: Wizard Step 2-->
											<div class="pb-5" data-wizard-type="step-content">

												<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

													<!-- iniciando serviço -->
													<div class="col-12">
														<h4 class="col-12">Serviço</h4>


														<div id="kt_datatable" class="row datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
															<table class="datatable-table table-dynamic" style="max-width: 100%;overflow: scroll" id="tbl">
																<thead class="datatable-head">
																	<tr class="datatable-row" style="left: 0px;">
																		<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 60px;"></span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Serviço <span class="text-danger">*</span></span></th>

																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 300px;">Discriminação <span class="text-danger">*</span></span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor do serviço <span class="text-danger">*</span></span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Cód. CNAE</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Cód. do Serviço <span class="text-danger">*</span></span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Cód. de tributação do município</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Exigibilidade ISS <span class="text-danger">*</span></span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">ISS retido <span class="text-danger">*</span></span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Data da competência</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Estado do Local de Prestação</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Cidade do Local de Prestação</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor deduções</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Desconto incondicional</span></th>

																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Desconto condicional</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Outras retencoes</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Aliquota ISS</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Aliquota PIS</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Aliquota COFINS</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Aliquota INSS</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Aliquota IR</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Aliquota CSLL</span></th>
																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Intermediário do Serviço</span></th>

																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">CPF/CNPJ</span></th>

																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Nome/Razão Social</span></th>

																		<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Inscrição municipal (I.M)</span></th>
																	</tr>
																</thead>

																<tbody class="datatable-body">
																	@isset($item)
																	@foreach($item->servicos as $itemServico)
																	<tr class="datatable-row dynamic-form">
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 60px;">
																				<button type="button" class="btn btn-sm btn-danger btn-line-delete">
																					<i class="la la-trash"></i>
																				</button>
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 200px;">
																				<select style="width: 100%; height: 20px;" required class="form-control select2 custom-select-servico select2-custom" name="servico_id[]">
																					<option value="{{$itemServico->servico_id}}">{{ $itemServico->servico->nome }}</option>
																				</select>
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 300px;">
																				<input required name="discriminacao[]" type="text" value="{{ $itemServico->discriminacao }}" class="form-control">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input required name="valor_servico[]" type="tel" class="form-control money valor_servico" value="{{ moeda($itemServico->valor_servico) }}">
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="codigo_cnae[]" type="tel" class="form-control ignore" value="{{ $itemServico->codigo_cnae }}">
																			</td>
																		</span>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input required name="codigo_servico[]" type="tel" class="form-control" value="{{ $itemServico->codigo_servico }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="codigo_tributacao_municipio[]" type="tel" class="form-control ignore" value="{{ $itemServico->codigo_tributacao_municipio }}">
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<select required class="form-control" name="exigibilidade_iss[]">
																					@foreach(\App\Models\Nfse::exigibilidades() as $key => $e)
																					<option @if($itemServico->exigibilidade_iss == $key) selected @endif value="{{$key}}">{{$e}}</option>
																					@endforeach
																				</select>
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<select required class="form-control" name="iss_retido[]">
																					<option @if($itemServico->iss_retido == 0) selected @endif value="0">Não</option>
																					<option @if($itemServico->iss_retido == 1) selected @endif value="1">Sim</option>
																				</select>
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="data_competencia[]" type="date" class="form-control ignore" value="{{ $itemServico->data_competencia }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<select class="custom-select form-control ignore" name="estado_local_prestacao_servico[]">
																					@foreach(App\Models\Cidade::estados() as $e)
																					<option @if($itemServico->estado_local_prestacao_servico == $e) selected @endif value="{{$e}}">{{$e}}</option>
																					@endforeach
																				</select>
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 200px;">
																				<input name="cidade_local_prestacao_servico[]" type="text" class="form-control ignore" value="{{ $itemServico->cidade_local_prestacao_servico }}">
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="valor_deducoes[]" type="tel" class="form-control money ignore" value="{{ moeda($itemServico->valor_deducoes) }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">

																				<input name="desconto_incondicional[]" type="tel" class="form-control money ignore" value="{{ moeda($itemServico->desconto_incondicional) }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="desconto_condicional[]" type="tel" class="form-control money ignore" value="{{ moeda($itemServico->desconto_condicional) }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="outras_retencoes[]" type="tel" class="form-control money ignore" value="{{ moeda($itemServico->outras_retencoes) }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_iss[]" type="tel" class="form-control money ignore" value="{{ moeda($itemServico->aliquota_iss) }}">
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_pis[]" type="tel" class="form-control money ignore" value="{{ moeda($itemServico->aliquota_pis) }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_cofins[]" type="tel" class="form-control money ignore" value="{{ moeda($itemServico->aliquota_cofins) }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_inss[]" type="tel" class="form-control money ignore" value="{{ moeda($itemServico->aliquota_inss) }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_ir[]" type="tel" class="form-control money ignore" value="{{ moeda($itemServico->aliquota_ir) }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_csll[]" type="tel" class="form-control money ignore" value="{{ moeda($itemServico->aliquota_csll) }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<select class="form-control" name="intermediador[]">
																					<option @if($itemServico->intermediador == 'n') selected @endif value="n">Sem Intermediário</option>
																					<option @if($itemServico->intermediador == 'f') selected @endif value="f">Pessoa Física</option>
																					<option @if($itemServico->intermediador == 'j') selected @endif value="j">Pessoa Jurídica</option>
																				</select>
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<input name="documento_intermediador[]" type="tel" class="form-control cpf_cnpj ignore" value="{{ $itemServico->documento_intermediador }}">
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<input name="nome_intermediador[]" type="text" class="form-control ignore" value="{{ $itemServico->nome_intermediador }}">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<input name="im_intermediador[]" type="text" class="form-control ignore" value="{{ $itemServico->im_intermediador }}">
																			</span>
																		</td>

																	</tr>
																	@endforeach

																	<!-- fim edit -->
																	@else
																	<tr class="datatable-row dynamic-form">
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 60px;">
																				<button type="button" class="btn btn-sm btn-danger btn-line-delete">
																					<i class="la la-trash"></i>
																				</button>
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 200px;">
																				<select style="width: 100%; height: 20px;" required class="form-control select2 custom-select-servico select2-custom" name="servico_id[]">
																				</select>
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 300px;">
																				<input required name="discriminacao[]" type="text" class="form-control">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input required name="valor_servico[]" type="tel" class="form-control money valor_servico">
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="codigo_cnae[]" type="tel" class="form-control ignore">
																			</td>
																		</span>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input required name="codigo_servico[]" type="tel" class="form-control">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="codigo_tributacao_municipio[]" type="tel" class="form-control ignore">
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<select class="form-control" name="exigibilidade_iss[]">
																					@foreach(\App\Models\Nfse::exigibilidades() as $key => $e)
																					<option value="{{$key}}">{{$e}}</option>
																					@endforeach
																				</select>
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<select class="form-control" name="iss_retido[]">
																					<option value="0">Não</option>
																					<option value="1">Sim</option>
																				</select>
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="data_competencia[]" type="date" class="form-control ignore">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<select class="custom-select form-control ignore" name="estado_local_prestacao_servico[]">
																					@foreach(App\Models\Cidade::estados() as $e)
																					<option value="{{$e}}">{{$e}}</option>
																					@endforeach
																				</select>
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 200px;">
																				<input name="cidade_local_prestacao_servico[]" type="text" class="form-control ignore">
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="valor_deducoes[]" type="tel" class="form-control money ignore">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">

																				<input name="desconto_incondicional[]" type="tel" class="form-control money ignore">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="desconto_condicional[]" type="tel" class="form-control money ignore">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="outras_retencoes[]" type="tel" class="form-control money ignore">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_iss[]" type="tel" class="form-control money ignore">
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_pis[]" type="tel" class="form-control money ignore">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_cofins[]" type="tel" class="form-control money ignore">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_inss[]" type="tel" class="form-control money ignore">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_ir[]" type="tel" class="form-control money ignore">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 100px;">
																				<input name="aliquota_csll[]" type="tel" class="form-control money ignore">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<select class="form-control" name="intermediador[]">
																					<option value="n">Sem Intermediário</option>
																					<option value="f">Pessoa Física</option>
																					<option value="j">Pessoa Jurídica</option>
																				</select>
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<input name="documento_intermediador[]" type="tel" class="form-control cpf_cnpj ignore">
																			</span>
																		</td>

																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<input name="nome_intermediador[]" type="text" class="form-control ignore">
																			</span>
																		</td>
																		<td class="datatable-cell">
																			<span class="codigo" style="width: 150px;">
																				<input name="im_intermediador[]" type="text" class="form-control ignore">
																			</span>
																		</td>

																	</tr>
																	@endif
																</tbody>
															</table>
														</div>
														
														<div class="row">
															<button type="button" class="btn btn-info btn-clone-tbl mt-3">
																<i class="la la-plus"></i> Adicionar serviço
															</button>

														</div>

														<div class="col-12">
															<h4 class="float-right">Valor total de serviço: <strong class="total-servico">R$ 0,00</strong></h4>
														</div>


													</div>

												</div>
											</div>

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
								<div class="col-12">
									<button type="submit" id="salvar" class="btn btn-success float-right">Salvar</button>
								</div>
							</div>
						</div>
					</div>
				</form>

			</div>
		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript" src="/js/nfse.js"></script>

@endsection