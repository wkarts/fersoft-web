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
						<form class="form fv-plugins-bootstrap fv-plugins-framework" id="form-servico" method="post" action="/nfse/store">
							@csrf
							<input type="hidden" id="os_id" value="{{ $ordem->id }}" name="os_id">
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
																	<option @if($ordem->cliente_id == $c->id) selected @endif value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}})</option>
																	@endforeach
																</select>
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

													<div class="row">
														<div class="form-group validated col-lg-6 col-12">
															<label class="col-form-label" id="">Serviço <span class="text-danger">*</span></label>
															<div class="input-group">

																<select required class="form-control select2" style="width: 100% !important;" id="kt_select2_1" name="servico_id">
																	<option value="">Selecione o serviço</option>
																	@foreach($servicos as $s)
																	<option @if($servico->servico_id == $s->id) selected @endif value="{{$s->id}}">{{ $s->nome }}</option>
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
																		<textarea required type="text" name="discriminacao" class="form-control @if($errors->has('discriminacao')) is-invalid @endif" id="discriminacao"/>{{ $discriminacao }}</textarea>
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
																		<input required type="tel" name="valor_servico" class="form-control money @if($errors->has('valor_servico')) is-invalid @endif" id="valor_servico" value="{{ moeda($total)}}"/>
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
																		<input required type="tel" name="codigo_servico" class="form-control @if($errors->has('codigo_servico')) is-invalid @endif" id="codigo_servico" value="{{ $servico->servico->codigo_servico }}"/>
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
																		<input type="tel" name="codigo_tributacao_municipio" class="form-control" id="codigo_tributacao_municipio" value="{{{ isset($item) ? ($item->servico->codigo_tributacao_municipio) : old('codigo_tributacao_municipio') }}}"/>
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
																			<option @isset($item) @if($item->iss_retido == 0) selected @endif @endif value="0">Não</option>
																			<option @isset($item) @if($item->iss_retido == 1) selected @endif @endif value="1">Sim</option>
																		</select>
																	</div>
																</div>
															</div>

															<div class="form-group col-lg-3 col-md-4 col-sm-6 col-6">
																<label class="col-form-label">Resp. pela retenção</label>
																<div class="">
																	<div class="input-group">
																		<select class="form-control" name="responsavel_retencao_iss" id="responsavel_retencao_iss">
																			<option @isset($item) @if($item->responsavel_retencao_iss == 1) selected @endif @endif value="1">Tomador</option>
																			<option @isset($item) @if($item->responsavel_retencao_iss == 2) selected @endif @endif value="2">Intermediário</option>
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
																	<option @isset($item) @if($item->estado_local_prestacao_servico == $e) selected @endif @endif value="{{$e}}">{{$e}}</option>
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
										<button type="button" id="salvar" class="btn btn-success float-right">Salvar</button>
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
	<script type="text/javascript">
		@if($config->integracao_nfse == '' || $config->integracao_nfse == 'webmania')
		var url = 'nfse/enviar'
		@else
		var url = 'nfse/enviar-integra-notas'
		@endif
		$(function(){
			setTimeout(() => {
				buscaCliente()
				// buscaServico()
			}, 100)
		})

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

		$('#kt_select2_3').change(() => {
			let cliente = $('#kt_select2_3').val()
			if(cliente){
				buscaCliente()
			}
		})

		function buscaCliente(){
			let id = $('#kt_select2_3').val()
			$.get(path + 'clientes/findCliente/'+id)
			.done((res) => {
				console.log(res)
				$('#documento').val(res.cpf_cnpj)
				$('#razao_social').val(res.razao_social)
				$('#cep').val(res.cep)
				$('#rua').val(res.rua)
				$('#numero').val(res.numero)
				$('#bairro').val(res.bairro)
				$('#complemento').val(res.complemento)
				$('#email').val(res.email)
				$('#telefone').val(res.telefone)
				$('#kt_select2_4').val(res.cidade_id).change()
			})
			.fail((err) => {
				console.log(err)
			})
		}

		$('#kt_select2_1').change(() => {
			let servico = $('#kt_select2_1').val()
			if(servico){
				buscaServico()
			}
		})

		function buscaServico(){
			let id = $('#kt_select2_1').val()
			$.get(path + 'servicos/find/'+id)
			.done((res) => {
				console.log(res)
				$('#discriminacao').val(res.nome)
				// $('#valor_servico').val(res.valor.replace(".", ","))
				$('#codigo_servico').val(res.codigo_servico)
				$('#aliquota_iss').val(res.aliquota_iss.replace(".", ","))
				$('#aliquota_pis').val(res.aliquota_pis.replace(".", ","))
				$('#aliquota_cofins').val(res.aliquota_cofins.replace(".", ","))
				$('#aliquota_inss').val(res.aliquota_inss.replace(".", ","))
			})
			.fail((err) => {
				console.log(err)
			})
		}
	</script>
	<script type="text/javascript" src="/js/nfse_clone.js"></script>

	@endsection