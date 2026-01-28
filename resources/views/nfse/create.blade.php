@extends('default.layout')
@section('content')
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

										<h6>Ultima NFSe: <strong>{{ \App\Models\Nfse::lastNfse() }}</strong></h6>
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
						<form class="form fv-plugins-bootstrap fv-plugins-framework" id="form-servico" method="post" @if(isset($item) && !isset($clone)) action="/nfse/update/{{ $item->id }}" @else action="/nfse/store" @endif>
							@csrf

							@if(isset($item) && !isset($clone))
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
												<div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
													<div class="wizard-label">
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

										<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

											<!--begin: Wizard Form-->

											<!--begin: Wizard Step 1-->
											<div class="pb-5" data-wizard-type="step-content">

												<!-- Inicio da tabela -->
												<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

													<div class="row">
														<div class="form-group validated col-sm-7 col-lg-6 col-12">
															<label class="col-form-label" id="">Cliente <span class="text-danger">*</span></label>
															<div class="input-group">
																<select required class="form-control select2-custom" id="cliente" name="cliente">
																	<option value="">Selecione o cliente</option>
																	@foreach($clientes as $c)
																	<option @isset($item) @if($c->id == $item->cliente_id) selected @endif @endisset 
																		@if(old('cliente') == $c->id)
																		selected
																		@endif value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}})
																	</option>
																	@endforeach
																</select>
																<button type="button" onclick="novoCliente()" class="btn btn-warning btn-sm">
																	<i class="la la-plus-circle icon-add"></i>
																</button>
																@if($errors->has('cliente'))
																<div class="invalid-feedback">
																	{{ $errors->first('cliente') }}
																</div>
																@endif
															</div>
														</div>
													</div>

													<div class="row">

														<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
															<label class="col-form-label">CPF/CNPJ <span class="text-danger">*</span></label>
															<div class="">
																<div class="input-group">
																	<input type="tel" value="{{{ isset($item) ? $item->documento : old('documento') }}}" name="documento" class="form-control cpf_cnpj @if($errors->has('documento')) is-invalid @endif" id="documento"/>
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
															<label class="col-form-label">Inscrição municipal (IM)</label>
															<div class="">
																<div class="input-group">
																	<input value="{{{ isset($item) ? $item->im : old('im') }}}" type="tel" name="im" class="form-control" id="im"/>
																</div>
															</div>
														</div>

														<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
															<label class="col-form-label">Inscrição estadual (IE)</label>
															<div class="">
																<div class="input-group">
																	<input value="{{{ isset($item) ? $item->ie : old('ie') }}}" type="tel" name="ie" class="form-control" id="ie"/>
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
															<select name="cidade_id" required style="width: 100%" class="form-control select2 select2-custom" id="cidade_id">
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

													<div class="row">
														<div class="form-group validated col-lg-6 col-12">
															<label class="col-form-label" id="">Serviço <span class="text-danger">*</span></label>
															<div class="input-group">

																<select required class="form-control select2-custom" style="width: 100% !important;" id="servico_id" name="servico_id">
																	<option value="">Selecione o serviço</option>
																	@foreach($servicos as $s)
																	<option @isset($item) @if($s->id == $item->servico->servico_id) selected @endif @endisset 
																		@if(old('servico_id') == $s->id)
																		selected
																		@endif value="{{$s->id}}">{{ $s->nome }}</option>
																		@endforeach
																	</select>
																	@if($errors->has('servico_id'))
																	<div class="invalid-feedback">
																		{{ $errors->first('servico_id') }}
																	</div>
																	@endif
																</div>
															</div>

															<div class="form-group col-lg-4 col-12">
																<label class="col-form-label">Natureza de Operação <span class="text-danger">*</span></label>
																<div class="">
																	<div class="input-group">
																		<input required type="tel" name="natureza_operacao" class="form-control @if($errors->has('natureza_operacao')) is-invalid @endif" id="natureza_operacao" value="{{{ isset($item) ? ($item->natureza_operacao) : old('valor_servico') }}}"/>
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
															<div class="form-group col-12">
																<label class="col-form-label">Discriminação <span class="text-danger">*</span></label>
																<div class="">
																	<div class="input-group">
																		<textarea required type="text" name="discriminacao" class="form-control @if($errors->has('discriminacao')) is-invalid @endif" id="discriminacao"/>{{{ isset($item) ? $item->servico->discriminacao : old('telefone') }}}</textarea>
																		@if($errors->has('discriminacao'))
																		<div class="invalid-feedback">
																			{{ $errors->first('discriminacao') }}
																		</div>
																		@endif
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Valor do Serviço <span class="text-danger">*</span></label>
																<div class="">
																	<div class="input-group">
																		<input required type="tel" name="valor_servico" class="form-control money @if($errors->has('valor_servico')) is-invalid @endif" id="valor_servico" value="{{{ isset($item) ? moeda($item->servico->valor_servico) : old('valor_servico') }}}"/>
																		@if($errors->has('valor_servico'))
																		<div class="invalid-feedback">
																			{{ $errors->first('valor_servico') }}
																		</div>
																		@endif
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Cód. CNAE</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="codigo_cnae" class="form-control" id="codigo_cnae" value="{{{ isset($item) ? ($item->servico->codigo_cnae) : old('codigo_cnae') }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Cód. do Serviço <span class="text-danger">*</span></label>
																<div class="">
																	<div class="input-group">
																		<input required type="tel" name="codigo_servico" class="form-control @if($errors->has('codigo_servico')) is-invalid @endif" id="codigo_servico" value="{{{ isset($item) ? ($item->servico->codigo_servico) : old('codigo_servico') }}}"/>
																		@if($errors->has('codigo_servico'))
																		<div class="invalid-feedback">
																			{{ $errors->first('codigo_servico') }}
																		</div>
																		@endif
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Cód. de tributação do município</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="codigo_tributacao_municipio" class="form-control" id="codigo_tributacao_municipio" value="{{{ isset($item) ? ($item->servico->codigo_tributacao_municipio) : $config->codigo_tributacao_municipio }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Exigibilidade ISS <span class="text-danger">*</span></label>
																<div class="">
																	<div class="input-group">
																		<select class="form-control" name="exigibilidade_iss" id="exigibilidade_iss">
																			@foreach(\App\Models\Nfse::exigibilidades() as $key => $e)
																			<option @isset($item) @if($item->exigibilidade_iss == $key) selected @endif @endif value="{{$key}}">{{$e}}</option>
																			@endforeach
																		</select>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-1 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">ISS retido <span class="text-danger">*</span></label>
																<div class="">
																	<div class="input-group">
																		<select class="form-control" name="iss_retido" id="iss_retido">
																			<option @isset($item) @if($item->servico->iss_retido == 2) selected @endif @endif value="2">Não</option>
																			<option @isset($item) @if($item->servico->iss_retido == 1) selected @endif @endif value="1">Sim</option>
																		</select>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-3 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Resp. pela retenção</label>
																<div class="">
																	<div class="input-group">
																		<select class="form-control" name="responsavel_retencao_iss" id="responsavel_retencao_iss">
																			<option @isset($item) @if($item->servico->responsavel_retencao_iss == 1) selected @endif @endif value="1">Tomador</option>
																			<option @isset($item) @if($item->servico->responsavel_retencao_iss == 2) selected @endif @endif value="2">Intermediário</option>
																		</select>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Data da competência</label>
																<div class="">
																	<div class="input-group">
																		<input value="{{{ isset($item) ? ($item->servico->data_competencia) : old('data_competencia') }}}" type="date" name="data_competencia" class="form-control" id="data_competencia"/>
																	</div>
																</div>
															</div>

															<div class="form-group validated col-lg-2 col-md-6 col-sm-6">
																<label class="col-form-label">Estado do Local de Prestação</label>

																<select class="custom-select form-control" id="estado_local_prestacao_servico" name="estado_local_prestacao_servico">
																	@foreach(App\Models\Cidade::estados() as $e)
																	<option @isset($item) @if($item->estado_local_prestacao_servico == $e) selected @endif @else @if($e == $config->UF) selected @endif @endif value="{{$e}}">{{$e}}</option>
																	@endforeach
																</select>
															</div>

															<div class="form-group col-lg-3 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Cidade do Local de Prestação</label>
																<div class="">
																	<div class="input-group">
																		<input type="text" name="cidade_local_prestacao_servico" class="form-control" id="cidade_local_prestacao_servico" value="{{{ isset($item) ? ($item->servico->cidade_local_prestacao_servico) : old('cidade_local_prestacao_servico') }}}"/>
																	</div>
																</div>
															</div>
														</div>

														<hr>

														<div class="row">
															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Valor deduções</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="valor_deducoes" class="form-control money" id="valor_deducoes" value="{{{ isset($item) ? moeda($item->servico->valor_deducoes) : old('valor_deducoes') }}}"/>
																	</div>
																</div>
															</div>
															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Desconto incondicional</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="desconto_incondicional" class="form-control money" id="desconto_incondicional" value="{{{ isset($item) ? moeda($item->servico->desconto_incondicional) : old('desconto_incondicional') }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Desconto condicional</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="desconto_condicional" class="form-control money" id="desconto_condicional" value="{{{ isset($item) ? moeda($item->servico->desconto_condicional) : old('desconto_condicional') }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Outras retencoes</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="outras_retencoes" class="form-control money" id="outras_retencoes" value="{{{ isset($item) ? moeda($item->servico->outras_retencoes) : old('outras_retencoes') }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Aliquota ISS</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="aliquota_iss" class="form-control money" id="aliquota_iss" value="{{{ isset($item) ? moeda($item->servico->aliquota_iss) : old('aliquota_iss') }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Aliquota PIS</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="aliquota_pis" class="form-control money" id="aliquota_pis" value="{{{ isset($item) ? moeda($item->servico->aliquota_pis) : old('aliquota_pis') }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Aliquota COFINS</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="aliquota_cofins" class="form-control money" id="aliquota_cofins" value="{{{ isset($item) ? moeda($item->servico->aliquota_cofins) : old('aliquota_cofins') }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Aliquota INSS</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="aliquota_inss" class="form-control money" id="aliquota_inss" value="{{{ isset($item) ? moeda($item->servico->aliquota_inss) : old('aliquota_inss') }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Aliquota IR</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="aliquota_ir" class="form-control money" id="aliquota_ir" value="{{{ isset($item) ? moeda($item->servico->aliquota_ir) : old('aliquota_ir') }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Aliquota CSLL</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="aliquota_csll" class="form-control money" id="aliquota_csll" value="{{{ isset($item) ? moeda($item->servico->aliquota_csll) : old('aliquota_csll') }}}"/>
																	</div>
																</div>
															</div>

														</div>

														<hr>

														<div class="row">

															<div class="form-group col-lg-3 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Intermediário do Serviço</label>
																<div class="">
																	<div class="input-group">
																		<select class="form-control" name="intermediador" id="intermediador">
																			<option @isset($item) @if($item->intermediador == 'n') selected @endif @endif value="n">Sem Intermediário</option>
																			<option @isset($item) @if($item->intermediador == 'f') selected @endif @endif value="f">Pessoa Física</option>
																			<option @isset($item) @if($item->intermediador == 'j') selected @endif @endif value="j">Pessoa Jurídica</option>
																		</select>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6 pfj d-none">
																<label class="col-form-label">CPF/CNPJ</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="documento_intermediador" class="form-control cpf_cnpj" id="documento_intermediador" value="{{{ isset($item) ? ($item->servico->documento_intermediador) : old('documento_intermediador') }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-3 col-md-4 col-sm-6 col-6 pfj d-none">
																<label class="col-form-label">Nome/Razão Social</label>
																<div class="">
																	<div class="input-group">
																		<input type="text" name="nome_intermediador" class="form-control" id="nome_intermediador" value="{{{ isset($item) ? ($item->servico->nome_intermediador) : old('nome_intermediador') }}}"/>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6 pfj d-none">
																<label class="col-form-label">Inscrição municipal (I.M)</label>
																<div class="">
																	<div class="input-group">
																		<input type="tel" name="im_intermediador" class="form-control" id="im_intermediador" value="{{{ isset($item) ? ($item->servico->im_intermediador) : old('im_intermediador') }}}"/>
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

								<!-- Fim wizzard -->

							</div>
						</div>

						<div class="card card-custom gutter-b">
							<div class="card-body">
								<div class="row">
									<div class="col-12">
										<button @if(isset($item) && !isset($clone)) type="submit" @else type="button" @endif id="salvar" class="btn btn-success float-right">
											Salvar
										</button>
									</div>
								</div>
							</div>
						</div>
					</form>

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

								<div class="form-group validated col-sm-3 col-lg-4">
									<label class="col-form-label" id="lbl_cpf_cnpj">CPF/CNPJ</label>
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
										<input id="rua-modal" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif">

									</div>
								</div>

								<div class="form-group validated col-sm-2 col-lg-2">
									<label class="col-form-label">Número</label>
									<div class="">
										<input id="numero-modal" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif">

									</div>
								</div>

								<div class="form-group validated col-sm-8 col-lg-4">
									<label class="col-form-label">Bairro</label>
									<div class="">
										<input id="bairro-modal" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif">

									</div>
								</div>

								<div class="form-group validated col-sm-8 col-lg-2">
									<label class="col-form-label">CEP</label>
									<div class="">
										<input id="cep-modal" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif">
									</div>
								</div>

								<div class="form-group validated col-sm-8 col-lg-3">
									<label class="col-form-label">Email</label>
									<div class="">
										<input id="email-modal" type="text" class="form-control @if($errors->has('email')) is-invalid @endif">
									</div>
								</div>

								@php
								$cidade = App\Models\Cidade::getCidadeCod($config->codMun);
								@endphp
								<div class="form-group validated col-lg-4 col-md-6 col-sm-10">
									<label class="col-form-label">Cidade</label><br>
									<select style="width: 100%" class="form-control select2" id="kt_select2_5">
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
										<input id="telefone-modal" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif">
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

	<div class="modal-loading loading-class"></div>
	@endsection

	@section('javascript')
	<script type="text/javascript">

		@if($config->integracao_nfse == '' || $config->integracao_nfse == 'webmania')
		var url = 'nfse/enviar'
		@else
		var url = 'nfse/enviar-integra-notas'
		@endif
		$('#intermediador').change(() => {
			changeIntermediador()
		})

		function changeIntermediador(){
			let v = $('#intermediador').val()
			if(v == 'n'){
				$('.pfj').addClass('d-none')
			}else{
				$('.pfj').removeClass('d-none')
			}
		}

		$('#cliente').change(() => {
			let cliente = $('#cliente').val()
			if(cliente){
				buscaCliente(cliente)
			}
		})

		function buscaCliente(id){
			$.get(path + 'clientes/findCliente/'+id)
			.done((res) => {
				$('#documento').val(res.cpf_cnpj)
				$('#razao_social').val(res.razao_social)
				$('#cep').val(res.cep)
				$('#rua').val(res.rua)
				$('#ie').val(res.ie_rg)
				$('#numero').val(res.numero)
				$('#bairro').val(res.bairro)
				$('#complemento').val(res.complemento)
				$('#email').val(res.email)
				$('#telefone').val(res.telefone)
				$('#cidade_id').val(res.cidade_id).change()
			})
			.fail((err) => {
				console.log(err)
			})
		}

		$('#servico_id').change(() => {
			let servico = $('#servico_id').val()
			if(servico){
				buscaServico(servico)
			}
		})

		function buscaServico(id){
			$.get(path + 'servicos/find/'+id)
			.done((res) => {
				$('#discriminacao').val(res.nome)
				$('#valor_servico').val(res.valor.replace(".", ","))
				$('#codigo_servico').val(res.codigo_servico)
				$('#natureza_operacao').val(res.natureza)
				$('#aliquota_iss').val(res.aliquota_iss.replace(".", ","))
				$('#aliquota_pis').val(res.aliquota_pis.replace(".", ","))
				$('#aliquota_cofins').val(res.aliquota_cofins.replace(".", ","))
				$('#aliquota_inss').val(res.aliquota_inss.replace(".", ","))
			})
			.fail((err) => {
				console.log(err)
			})
		}

		function novoCliente(){
			$('#modal-cliente').modal('show')
		}

		function consultaCadastro() {
			let cnpj = $('#cpf_cnpj').val().replace(/[^0-9]/g,'')

			if (cnpj.length == 14){
				$('#btn-consulta-cadastro').addClass('spinner')
				$.get('https://publica.cnpj.ws/cnpj/' + cnpj)
				.done((data) => {
					$('#btn-consulta-cadastro').removeClass('spinner')
					if (data!= null) {
						let ie = ''
						if (data.estabelecimento.inscricoes_estaduais.length > 0) {
							ie = data.estabelecimento.inscricoes_estaduais[0].inscricao_estadual
						}
						$('#ie_rg').val(ie)
						$('#razao_social2').val(data.razao_social)
						$('#nome_fantasia2').val(data.estabelecimento.nome_fantasia)
						$("#rua-modal").val(data.estabelecimento.tipo_logradouro + " " + data.estabelecimento.logradouro)
						$('#numero-modal').val(data.estabelecimento.numero)
						$("#bairro-modal").val(data.estabelecimento.bairro);
						let cep = data.estabelecimento.cep.replace(/[^\d]+/g, '');
						$('#cep-modal').val(cep.substring(0, 5) + '-' + cep.substring(5, 9))
						$('#email-modal').val(data.estabelecimento.email)
						$('#telefone-modal').val(data.estabelecimento.telefone1)

						findCidadeCodigo(data.estabelecimento.cidade.ibge_id)

					}
				})
				.fail((err) => {
					$('#btn-consulta-cadastro').removeClass('spinner')
					console.log(err)
					swal("Erro", err.responseJSON.titulo, "error")
				})
			}else{
				swal("Alerta", "Informe corretamente o CNPJ", "warning")
			}

		}

		function findCidadeCodigo(codigo_ibge){

			$.get(path + "cidades/cidadePorCodigoIbge/" + codigo_ibge)
			.done((res) => {
				$('#kt_select2_5').val(res.id).change();
			})
			.fail((err) => {
				console.log(err)
			})

		}

		function limparCamposCliente(){
			$('#razao_social2').val('')
			$('#nome_fantasia2').val('')

			$('#rua-modal').val('')
			$('#numero-modal').val('')
			$('#bairro-modal').val('')
			$('#cep-modal').val('')
			$('#kt_select2_5').val('1').change();
		}

		function salvarCliente(){
			let js = {
				razao_social: $('#razao_social2').val(),
				nome_fantasia: $('#nome_fantasia2').val() ? $('#nome_fantasia2').val() : '',
				rua: $('#rua-modal').val() ? $('#rua-modal').val() : '',
				numero: $('#numero-modal').val() ? $('#numero-modal').val() : '',
				cpf_cnpj: $('#cpf_cnpj').val() ? $('#cpf_cnpj').val() : '',
				ie_rg: $('#ie_rg').val() ? $('#ie_rg').val() : '',
				bairro: $('#bairro-modal').val() ? $('#bairro-modal').val() : '',
				cep: $('#cep-modal').val() ? $('#cep-modal').val() : '',
				consumidor_final: $('#consumidor_final').val() ? $('#consumidor_final').val() : '',
				contribuinte: $('#contribuinte').val() ? $('#contribuinte').val() : '',
				limite_venda: $('#limite_venda').val() ? $('#limite_venda').val() : '',
				cidade_id: $('#kt_select2_5').val() ? $('#kt_select2_5').val() : 1,
				telefone: $('#telefone-modal').val() ? $('#telefone-modal').val() : '',
				celular: $('#celular-modal').val() ? $('#celular-modal').val() : '',
				email: $('#email-modal').val() ? $('#email-modal').val() : '',
			}

			if(js.razao_social == ''){
				swal("Erro", "Informe a razão social", "warning")
			}else{
				swal({
					title: "Cuidado",
					text: "Ao salvar o cliente com os dados incompletos não será possível emitir NFe até que edite o seu cadstro?",
					icon: "warning",
					buttons: ["Cancelar", 'Salvar'],
					dangerMode: true,
				})
				.then((v) => {
					if (v) {
						let token = $('#_token').val();
						$.post(path + 'clientes/quickSave',
						{
							_token: token,
							data: js
						})
						.done((res) =>{
							limparCamposCliente()
							CLIENTE = res;

							$('#cliente').append('<option value="'+res.id+'">'+ 
								res.razao_social+'</option>').change();
							swal("Sucesso", "Cliente adicionado!!", 'success')
							.then(() => {
								$('#modal-cliente').modal('hide')
								$('#cliente').val(res.id).change();

							})
						})
						.fail((err) => {
							console.log(err)
							swal("Alerta", err.responseJSON, "warning")
						})
					}
				})
			}

		}
	</script>
	@if(!isset($item) || isset($clone))
	<script type="text/javascript" src="/js/nfse_clone.js"></script>
	@endif
	@endsection