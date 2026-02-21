@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/configNF/save" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{{ isset($config->id) ? $config->id : 0 }}}">

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ isset($config) ? "Editar": "Cadastrar" }}} Emissor Fiscal</h3>
						</div>
					</div>
					@csrf

					<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">

						<div class="wizard-nav">

							<div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
								<!--begin::Wizard Step 1 Nav-->
								<div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
									<div class="wizard-label">
										<h3 class="wizard-title">
											<span>
												DADOS DO EMISSOR
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
												OUTROS
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
									<div class="kt-section kt-section--first">
										<div class="kt-section__body">

											<div class="row">
												@if(empty($certificado))
												<div class="col-lg-12 col-sm-12 col-md-12">
													<p class="text-danger">VOCE AINDA NÃO FEZ UPLOAD DO CERTIFICADO ATÉ O MOMENTO</p>

													@if(!isset($config))
													<p class="text-danger">>>Preencha o formulário</p>
													@endif

												</div>
												<div class="col-lg-12 col-sm-12 col-md-12">

													@isset($config)
													<a class="btn btn-lg btn-light-info" href="/configNF/certificado">
														Fazer upload agora
													</a>
													@endisset
												</div>

												@else
												<div class="col-lg-12 col-sm-12 col-md-12">
													<a onclick='swal("Atenção!", "Deseja remover este certificado?", "warning").then((sim) => {if(sim){ location.href="/configNF/deleteCertificado" }else{return false} })' href="#!" class="btn btn-danger">
														Remover certificado
													</a>

													<!-- <a type="button" id="testar" class="btn btn-success spinner-white spinner-right">
														Testar ambiente
													</a> -->

													<a href="/contigencia" class="btn btn-dark spinner-white spinner-right">
														Contingência
													</a>
												</div>

												<div class="card card-custom gutter-b mt-2 ml-4">
													<div class="card-body">
														<div class="card-content">

															<h6>Serial Certificado: <strong class="green-text">{{$infoCertificado['serial']}}</strong></h6>
															<h6>Inicio: <strong class="green-text">{{$infoCertificado['inicio']}}</strong></h6>
															<h6>Expiração: <strong class="green-text">{{$infoCertificado['expiracao']}}</strong></h6>
															<h6>IDCTX: <strong class="green-text">{{$infoCertificado['id']}}</strong></h6>
															<h6>Senha: <strong class="green-text">{{$certificado->senha}}</strong></h6>

														</div>
														<a class="btn btn-sm btn-info" href="/configNF/enviar-certificado">Enviar certificado para contabilidade</a>
														@if($soapDesativado)
														<div class="alert alert-custom alert-danger fade show" role="alert" style="margin-top: 10px;">
															<div class="alert-icon"><i class="la la-warning"></i></div>
															<div class="alert-text">
																Extensão SOAP está desativada!!
															</div>
														</div>
														@endif
													</div>
												</div>

												@endif
											</div>
                                            <div class="row">
                                                <div class="form-group col-sm-2">
                                                    <label class="col-form-label">Empresa ID</label>
                                                    <input type="text" class="form-control" name="empresa_id" value="{{ isset($config) ? $config->empresa_id : old('empresa_id') }}" readonly>
                                                </div>
                                                <div class="form-group col-sm-10">
                                                    <label class="col-form-label">Token Sync</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" name="token_sync" id="token_sync" value="{{ isset($config) ? $config->token_sync : old('token_sync') }}">
                                                        <div class="input-group-append">
                                                            <button class="btn btn-secondary" type="button" onclick="gerarToken()">Gerar Token</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group validated col-sm-4 col-lg-4">
                                                    <label class="col-form-label">WhatsApp Technology</label>
                                                    <select name="whatsapp_technology" class="custom-select form-control">
                                                        @foreach(\App\Models\ConfigNota::getWhatsappTechOptions() as $key => $label)
                                                            <option value="{{ $key }}"
                                                                    @if( (old('whatsapp_technology') ?? ($config->whatsapp_technology ?? '')) === $key )
                                                                        selected
                                                                @endif
                                                            >{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                            </div>

                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="card card-custom gutter-b">
                                                        <div class="card-body">
                                                            <h5 class="mb-4">Controle de Pesagem por Balança</h5>
                                                            <div class="form-group">
                                                                <div class="checkbox-inline">
                                                                    <label class="checkbox">
                                                                        <input type="checkbox" name="bloquear_pesagem_manual_balanca" value="1" {{ (old('bloquear_pesagem_manual_balanca', $config->bloquear_pesagem_manual_balanca ?? 0)) ? 'checked' : '' }}>
                                                                        <span></span>
                                                                        Bloquear pesagem manual e obrigar leitura da balança padrão
                                                                    </label>
                                                                </div>
                                                                <small class="text-muted">Quando ativo, o peso manual é bloqueado e o operador deve usar somente a balança padrão.</small>
                                                            </div>

                                                            <div class="form-group">
                                                                <small class="text-muted">Sem balança ativa? <a href="/balancas">Cadastre uma balança</a> para habilitar leitura obrigatória via balança.</small>
                                                            </div>

                                                            <div class="form-group mb-1">
                                                                <div class="checkbox-inline">
                                                                    <label class="checkbox">
                                                                        <input type="checkbox" name="usar_valores_ticket_pesagem" value="1" {{ (old('usar_valores_ticket_pesagem', $config->usar_valores_ticket_pesagem ?? 0)) ? 'checked' : '' }}>
                                                                        <span></span>
                                                                        Usar valores (unitário/total) vindos do ticket de pesagem
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <div class="form-group mb-0">
                                                                <div class="checkbox-inline">
                                                                    <label class="checkbox">
                                                                        <input type="checkbox" name="exibir_valores_ticket_pesagem_grid" value="1" {{ (old('exibir_valores_ticket_pesagem_grid', $config->exibir_valores_ticket_pesagem_grid ?? 0)) ? 'checked' : '' }}>
                                                                        <span></span>
                                                                        Exibir colunas de valor unitário/total da pesagem na grid
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <div class="form-group mt-3 mb-0">
                                                                <div class="checkbox-inline">
                                                                    <label class="checkbox">
                                                                        <input type="checkbox" name="desbloquear_campo_peso_bag_ticket" value="1" {{ (old('desbloquear_campo_peso_bag_ticket', $config->desbloquear_campo_peso_bag_ticket ?? 0)) ? 'checked' : '' }}>
                                                                        <span></span>
                                                                        Desbloquear campo "Peso dos Recipientes" no ticket de pesagem
                                                                    </label>
                                                                </div>
                                                                <small class="text-muted">Quando ativo, o campo de recipiente fica editável e a tara não é aplicada automaticamente.</small>
                                                            </div>

                                                            <div class="form-group mt-3 mb-0">
                                                                <div class="checkbox-inline">
                                                                    <label class="checkbox">
                                                                        <input type="checkbox" name="conectar_automaticamente_balanca_padrao_usuario" value="1" {{ (old('conectar_automaticamente_balanca_padrao_usuario', $config->conectar_automaticamente_balanca_padrao_usuario ?? 0)) ? 'checked' : '' }}>
                                                                        <span></span>
                                                                        Conectar automaticamente à balança padrão do usuário no abrir do modal
                                                                    </label>
                                                                </div>
                                                                <small class="text-muted">Ao abrir o modal de ticket para lançamento das pesagens, o sistema deverá conectar-se automaticamente à balança padrão configurada para o usuário.</small>
                                                            </div>

                                                            <div class="form-group mt-3 mb-0">
                                                                <div class="checkbox-inline">
                                                                    <label class="checkbox">
                                                                        <input type="checkbox" name="conectar_automaticamente_balanca_ao_selecionar" value="1" {{ (old('conectar_automaticamente_balanca_ao_selecionar', $config->conectar_automaticamente_balanca_ao_selecionar ?? 0)) ? 'checked' : '' }}>
                                                                        <span></span>
                                                                        Conectar automaticamente ao selecionar/trocar balança
                                                                    </label>
                                                                </div>
                                                                <small class="text-muted">Se desconectar manualmente, a reconexão automática é desativada até nova ação manual.</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
											<div class="row">

												<div class="form-group validated col-sm-6 col-lg-4">
													<label class="col-form-label">CNPJ</label>
													<div class="">
														<input autofocus id="cnpj" type="text" class="form-control @if($errors->has('cnpj')) is-invalid @endif cpf_cnpj" name="cnpj" value="{{{ isset($config) ? $config->cnpj : $cnpj }}}">
														@if($errors->has('cnpj'))
														<div class="invalid-feedback">
															{{ $errors->first('cnpj') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-4 col-md-4 col-sm-4">
													<br><br>
													<button disabled type="button" id="btn-consulta-cadastro" onclick="consultaCNPJ()" class="btn btn-success spinner-white spinner-right">
														<span>
															<i class="fa fa-search"></i>
														</span>
													</button>
												</div>

											</div>

											<div class="row">
												<div class="form-group validated col-sm-12 col-lg-6">
													<label class="col-form-label">Razao Social</label>
													<div class="">
														<input id="razao_social" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif" name="razao_social" value="{{{ isset($config) ? $config->razao_social : old('razao_social') }}}">
														@if($errors->has('razao_social'))
														<div class="invalid-feedback">
															{{ $errors->first('razao_social') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-sm-12 col-lg-6">
													<label class="col-form-label">Nome Fantasia</label>
													<div class="">
														<input id="nome_fantasia" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif" name="nome_fantasia" value="{{{ isset($config) ? $config->nome_fantasia : old('nome_fantasia') }}}">
														@if($errors->has('nome_fantasia'))
														<div class="invalid-feedback">
															{{ $errors->first('nome_fantasia') }}
														</div>
														@endif
													</div>
												</div>
											</div>

											<div class="row">

												<div class="form-group validated col-sm-2 col-lg-2">
													<label class="col-form-label">Tipo</label>
													<div class="">
														<select id="tipo" class="form-control custom-select">
															<option value="f">Fisica</option>
															<option value="j">Juridica</option>
														</select>
													</div>
												</div>

												<div class="form-group validated col-sm-4 col-lg-3">
													<label id="tipo-doc" class="col-form-label">CNPJ</label>
													<div class="">
														<input id="cnpj2" type="text" class="form-control @if($errors->has('cnpj')) is-invalid @endif" name="cnpj" value="{{{ isset($config) ? $config->cnpj : old('cnpj') }}}">
														@if($errors->has('cnpj'))
														<div class="invalid-feedback">
															{{ $errors->first('cnpj') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-sm-4 col-lg-3">
													<label class="col-form-label">Inscrição Estadual</label>
													<div class="">
														<input id="ie" type="text" class="form-control @if($errors->has('ie')) is-invalid @endif" name="ie" value="{{{ isset($config) ? $config->ie : old('ie') }}}">
														@if($errors->has('ie'))
														<div class="invalid-feedback">
															{{ $errors->first('ie') }}
														</div>
														@endif
													</div>
												</div>
											</div>

											<hr>
											<h5>Endereço</h5>
											<div class="row">

												<div class="form-group validated col-sm-10 col-lg-6">
													<label class="col-form-label">Rua</label>
													<div class="">
														<input id="logradouro" type="text" class="form-control @if($errors->has('logradouro')) is-invalid @endif" name="logradouro" value="{{{ isset($config) ? $config->logradouro : old('logradouro') }}}">
														@if($errors->has('logradouro'))
														<div class="invalid-feedback">
															{{ $errors->first('logradouro') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-sm-2 col-lg-2">
													<label class="col-form-label">Nº</label>
													<div class="">
														<input id="numero" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{{ isset($config) ? $config->numero : old('numero') }}}">
														@if($errors->has('numero'))
														<div class="invalid-feedback">
															{{ $errors->first('numero') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-sm-8 col-lg-4">
													<label class="col-form-label">Complemento</label>
													<div class="">
														<input id="complemento" type="text" class="form-control @if($errors->has('complemento')) is-invalid @endif" name="complemento" value="{{{ isset($config) ? $config->complemento : old('complemento') }}}">
														@if($errors->has('complemento'))
														<div class="invalid-feedback">
															{{ $errors->first('complemento') }}
														</div>
														@endif
													</div>
												</div>
											</div>

											<div class="row">

												<div class="form-group validated col-sm-4 col-lg-4">
													<label class="col-form-label">Bairro</label>
													<div class="">
														<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{{ isset($config) ? $config->bairro : old('bairro') }}}">
														@if($errors->has('bairro'))
														<div class="invalid-feedback">
															{{ $errors->first('bairro') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">CEP</label>
													<div class="">
														<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{{ isset($config) ? $config->cep : old('cep') }}}">
														@if($errors->has('cep'))
														<div class="invalid-feedback">
															{{ $errors->first('cep') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-5 col-md-5 col-sm-10">
													<label class="col-form-label">Cidade</label>
													<select class="form-control select2" id="kt_select2_1" name="cidade">
														@foreach($cidades as $c)
														<option value="{{$c->id}}" @isset($config) @if($c->codigo == $config->codMun) selected @endif @endisset
															@if(old('cidade') == $c->id)
															selected
															@endif
															>
															{{$c->nome}} ({{$c->uf}})
														</option>
														@endforeach
													</select>
													@if($errors->has('cidade'))
													<div class="invalid-feedback">
														{{ $errors->first('cidade') }}
													</div>
													@endif
												</div>


												<div class="form-group validated col-lg-3 col-md-4 col-sm-10">
													<label class="col-form-label">Telefone</label>
													<div class="">
														<input id="telefone" type="text" class="form-control @if($errors->has('fone')) is-invalid @endif" name="fone" value="{{{ isset($config) ? $config->fone : old('fone') }}}">
														@if($errors->has('fone'))
														<div class="invalid-feedback">
															{{ $errors->first('fone') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-10">
													<label class="col-form-label">Email</label>
													<div class="">
														<input id="email" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($config) ? $config->email : old('email') }}}">
														@if($errors->has('email'))
														<div class="invalid-feedback">
															{{ $errors->first('email') }}
														</div>
														@endif
													</div>
												</div>

											</div>

											<hr>

											<div class="row">
												<div class="form-group validated col-lg-12 col-md-12 col-sm-12">
													<label class="col-form-label">CST/CSOSN Padrão</label>

													<select class="custom-select form-control" name="CST_CSOSN_padrao">
														@foreach($listaCSTCSOSN as $key => $l)
														<option value="{{$key}}"
														@if(isset($config))
														@if($key == $config->CST_CSOSN_padrao)
														selected
														@endif
														@else
														@if(old('CST_CSOSN_padrao') == $key)
														selected
														@endif
														@endif>{{$key}} - {{$l}}</option>
														@endforeach
													</select>

												</div>
											</div>

											<div class="row">
												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">CST/PIS Padrão</label>

													<select class="custom-select form-control" name="CST_PIS_padrao">
														@foreach($listaCSTPISCOFINS as $key => $l)
														<option value="{{$key}}"
														@if(isset($config))
														@if($key == $config->CST_PIS_padrao)
														selected
														@endif
														@else
														@if(old('CST_PIS_padrao') == $key)
														selected
														@endif
														@endif
														>{{$key}} - {{$l}}</option>
														@endforeach
													</select>

												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">CST/COFINS Padrão</label>

													<select class="custom-select form-control" name="CST_COFINS_padrao">
														@foreach($listaCSTPISCOFINS as $key => $l)
														<option value="{{$key}}"
														@if(isset($config))
														@if($key == $config->CST_COFINS_padrao)
														selected
														@endif
														@else
														@if(old('CST_COFINS_padrao') == $key)
														selected
														@endif
														@endif
														>{{$key}} - {{$l}}</option>
														@endforeach
													</select>

												</div>
											</div>

											<div class="row">
												<div class="form-group validated col-lg-12 col-md-12 col-sm-12">
													<label class="col-form-label">CST/IPI Padrão</label>

													<select class="custom-select form-control" name="CST_IPI_padrao">
														@foreach($listaCSTIPI as $key => $l)
														<option value="{{$key}}"
														@if(isset($config))
														@if($key == $config->CST_IPI_padrao)
														selected
														@endif
														@if(old('CST_IPI_padrao') == $key)
														selected
														@endif
														@endif
														>{{$key}} - {{$l}}</option>
														@endforeach
													</select>
												</div>
											</div>

											<div class="row">

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">Cod. benefício Padrão</label>
													<div class="">
														<input id="cBenef_padrao" type="text" class="form-control @if($errors->has('cBenef_padrao')) is-invalid @endif" name="cBenef_padrao" value="{{{ isset($config) ? $config->cBenef_padrao : old('cBenef_padrao') }}}">
														@if($errors->has('cBenef_padrao'))
														<div class="invalid-feedback">
															{{ $errors->first('cBenef_padrao') }}
														</div>
														@endif
													</div>
												</div>
												<div class="form-group validated col-lg-4 col-md-12 col-sm-12">
													<label class="col-form-label">
														Natureza de Operação Padrão Frente de Caixa
													</label>

													<select class="custom-select form-control" name="nat_op_padrao">
														@foreach($naturezas as $n)
														<option value="{{$n->id}}"
															@isset($config)
															@if($n->id == $config->nat_op_padrao)
															selected
															@endif
															@endisset
															>{{$n->natureza}}
														</option>
														@endforeach
													</select>

												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">
														Ambiente
													</label>

													<select @if(env("APP_ENV") == "demo") disabled @endif class="custom-select form-control" name="ambiente">
														<option @if(isset($config)) @if($config->ambiente == 2) selected @endif @endif value="2">2 - Homologação</option>
														<option @if(isset($config)) @if($config->ambiente == 1) selected @endif @endif value="1">1 - Produção</option>
													</select>

												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">Nº Série NFe</label>
													<div class="">
														<input id="numero_serie_nfe" type="text" class="form-control @if($errors->has('numero_serie_nfe')) is-invalid @endif" name="numero_serie_nfe" value="{{{ isset($config) ? $config->numero_serie_nfe : old('numero_serie_nfe') }}}">
														@if($errors->has('numero_serie_nfe'))
														<div class="invalid-feedback">
															{{ $errors->first('numero_serie_nfe') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">Nº Série NFCe</label>
													<div class="">
														<input id="numero_serie_nfce" type="text" class="form-control @if($errors->has('numero_serie_nfce')) is-invalid @endif" name="numero_serie_nfce" value="{{{ isset($config) ? $config->numero_serie_nfce : old('numero_serie_nfce') }}}">
														@if($errors->has('numero_serie_nfce'))
														<div class="invalid-feedback">
															{{ $errors->first('numero_serie_nfce') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">Nº Série CTe</label>
													<div class="">
														<input id="numero_serie_cte" type="text" class="form-control @if($errors->has('numero_serie_cte')) is-invalid @endif" name="numero_serie_cte" value="{{{ isset($config) ? $config->numero_serie_cte : old('numero_serie_cte') }}}">
														@if($errors->has('numero_serie_cte'))
														<div class="invalid-feedback">
															{{ $errors->first('numero_serie_cte') }}
														</div>
														@endif
													</div>
												</div>
												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">Nº Série MDFe</label>
													<div class="">
														<input id="numero_serie_mdfe" type="text" class="form-control @if($errors->has('numero_serie_mdfe')) is-invalid @endif" name="numero_serie_mdfe" value="{{{ isset($config) ? $config->numero_serie_mdfe : old('numero_serie_mdfe') }}}">
														@if($errors->has('numero_serie_mdfe'))
														<div class="invalid-feedback">
															{{ $errors->first('numero_serie_mdfe') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">Nº Série NFSe</label>
													<div class="">
														<input id="numero_serie_nfse" type="text" class="form-control @if($errors->has('numero_serie_nfse')) is-invalid @endif" name="numero_serie_nfse" value="{{{ isset($config) ? $config->numero_serie_nfse : old('numero_serie_nfse') }}}">
														@if($errors->has('numero_serie_nfse'))
														<div class="invalid-feedback">
															{{ $errors->first('numero_serie_nfse') }}
														</div>
														@endif
													</div>
												</div>
												<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
													<label class="col-form-label">Ultimo Nº NFe</label>
													<div class="">
														<input id="ultimo_numero_nfe" type="text" class="form-control @if($errors->has('ultimo_numero_nfe')) is-invalid @endif" name="ultimo_numero_nfe" value="{{{ isset($config) ? $config->ultimo_numero_nfe : old('ultimo_numero_nfe') }}}">
														@if($errors->has('ultimo_numero_nfe'))
														<div class="invalid-feedback">
															{{ $errors->first('ultimo_numero_nfe') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
													<label class="col-form-label">Ultimo Nº NFCe</label>
													<div class="">
														<input id="ultimo_numero_nfce" type="text" class="form-control @if($errors->has('ultimo_numero_nfce')) is-invalid @endif" name="ultimo_numero_nfce" value="{{{ isset($config) ? $config->ultimo_numero_nfce : old('ultimo_numero_nfce') }}}">
														@if($errors->has('ultimo_numero_nfce'))
														<div class="invalid-feedback">
															{{ $errors->first('ultimo_numero_nfce') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
													<label class="col-form-label">Ultimo Nº CTe</label>
													<div class="">
														<input id="ultimo_numero_cte" type="text" class="form-control @if($errors->has('ultimo_numero_cte')) is-invalid @endif" name="ultimo_numero_cte" value="{{{ isset($config) ? $config->ultimo_numero_cte : old('ultimo_numero_cte') }}}">
														@if($errors->has('ultimo_numero_cte'))
														<div class="invalid-feedback">
															{{ $errors->first('ultimo_numero_cte') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
													<label class="col-form-label">Ultimo Nº MDFe</label>
													<div class="">
														<input id="ultimo_numero_mdfe" type="text" class="form-control @if($errors->has('ultimo_numero_mdfe')) is-invalid @endif" name="ultimo_numero_mdfe" value="{{{ isset($config) ? $config->ultimo_numero_mdfe : old('ultimo_numero_mdfe') }}}">
														@if($errors->has('ultimo_numero_mdfe'))
														<div class="invalid-feedback">
															{{ $errors->first('ultimo_numero_mdfe') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
													<label class="col-form-label">Ultimo Nº NFSe</label>
													<div class="">
														<input id="ultimo_numero_nfse" type="text" class="form-control @if($errors->has('ultimo_numero_nfse')) is-invalid @endif" name="ultimo_numero_nfse" value="{{{ isset($config) ? $config->ultimo_numero_nfse : old('ultimo_numero_nfse') }}}">
														@if($errors->has('ultimo_numero_nfse'))
														<div class="invalid-feedback">
															{{ $errors->first('ultimo_numero_nfse') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-3 col-sm-3">
													<label class="col-form-label">CSCID</label>
													<div class="">
														<input id="csc_id" type="text" class="form-control @if($errors->has('csc_id')) is-invalid @endif" name="csc_id" value="{{{ isset($config) ? $config->csc_id : old('csc_id') }}}">
														@if($errors->has('csc_id'))
														<div class="invalid-feedback">
															{{ $errors->first('csc_id') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-3 col-md-6 col-sm-6">
													<label class="col-form-label">CSC</label>
													<div class="">
														<input id="csc" type="text" class="form-control @if($errors->has('csc')) is-invalid @endif" name="csc" value="{{{ isset($config) ? $config->csc : old('csc') }}}">
														@if($errors->has('csc'))
														<div class="invalid-feedback">
															{{ $errors->first('csc') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">Inscrição municipal (opcional)</label>
													<div class="">
														<input id="inscricao_municipal" type="text" class="form-control @if($errors->has('inscricao_municipal')) is-invalid @endif im" name="inscricao_municipal" value="{{{ isset($config) ? $config->inscricao_municipal : old('inscricao_municipal') }}}">
														@if($errors->has('inscricao_municipal'))
														<div class="invalid-feedback">
															{{ $errors->first('inscricao_municipal') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-3 col-md-5 col-sm-5">
													<label class="col-form-label">CNPJ Autorizado (opcional)</label>
													<div class="">
														<input data-mask="00.000.000/0000-00" id="aut_xml" type="text" class="form-control @if($errors->has('aut_xml')) is-invalid @endif cnpj" name="aut_xml" value="{{{ isset($config) ? $config->aut_xml : old('aut_xml') }}}">
														@if($errors->has('aut_xml'))
														<div class="invalid-feedback">
															{{ $errors->first('aut_xml') }}
														</div>
														@endif
													</div>
												</div>
											</div>

											<div class="row">
												<div class="form-group validated col-sm-4 col-lg-4 col-6">
													<label class="col-xl-12 col-lg-12 col-form-label text-left">Logo</label>
													<div class="col-lg-10 col-xl-6">

														<div class="image-input image-input-outline" id="kt_image_1">
															<div class="image-input-wrapper" @if(isset($config) && $config->logo != '')style="background-image: url(/logos/{{$config->logo}})" @else style="background-image: url(/imgs/logo.png)" @endif ></div>
															<label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
																<i class="fa fa-pencil icon-sm text-muted"></i>
																<input type="file" id="file" name="file" accept=".jpg">
																<input type="hidden" name="profile_avatar_remove">
															</label>
															<span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="" data-original-title="Cancel avatar">
																<i class="fa fa-close icon-xs text-muted"></i>
															</span>
														</div>
														<span>Imagem </span>
														<span class="form-text text-muted">.jpg</span>
														<span class="form-text text-danger">Imagem até 300kb</span>
														@if($errors->has('file'))
														<div class="invalid-feedback">
															{{ $errors->first('file') }}
														</div>
														@endif

														@if(isset($config))
														<a href="/configNF/removeLogo/{{$config->id}}">remover logo</a>
														@endif

													</div>
												</div>
											</div>

										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="pb-5" data-wizard-type="step-content">

							<div class="row">
								<div class="col-xl-12">
									<div class="kt-section kt-section--first">
										<div class="kt-section__body">
											<div class="row">
												<div class="form-group validated col-lg-6 col-md-12 col-sm-8">
													<label class="col-form-label">CST/CSOSN consumidor final (opcional)</label>

													<select class="custom-select form-control" name="sobrescrita_csonn_consumidor_final">
														<option value="">--</option>
														@foreach($listaCSTCSOSN as $key => $l)
														<option value="{{$key}}"
														@if(isset($config))
														@if($key == $config->sobrescrita_csonn_consumidor_final)
														selected
														@endif
														@else
														@if(old('sobrescrita_csonn_consumidor_final') == $key)
														selected
														@endif
														@endif>{{$key}} - {{$l}}</option>
														@endforeach
													</select>

												</div>

												<div class="form-group validated col-lg-6 col-md-4 col-sm-10">
													<label class="col-form-label">Token IBPT (opcional)</label>
													<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Realize o cadastro no site https://deolhonoimposto.ibpt.org.br/Site/Entrar para gerar o seu token"><i class="la la-info"></i></button>
													<div class="">
														<input id="token_ibpt" type="text" class="form-control @if($errors->has('token_ibpt')) is-invalid @endif" name="token_ibpt" value="{{{ isset($config) ? $config->token_ibpt : old('token_ibpt') }}}">
														@if($errors->has('token_ibpt'))
														<div class="invalid-feedback">
															{{ $errors->first('token_ibpt') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-3 col-6">
													<label class="col-form-label">Integração NFSe (opcional)</label>
													<select class="custom-select" name="integracao_nfse">
														<option value=""></option>
														<option @isset($config) @if($config->integracao_nfse == 'integranotas') selected @endif @endisset value="integranotas">Integra notas</option>
														<option @isset($config) @if($config->integracao_nfse == 'webmania') selected @endif @endisset value="webmania">Webmania</option>
                                                        <option @isset($config) @if($config->integracao_nfse == 'localnfse') selected @endif @endisset value="localnfse">Local NFSe</option>
													</select>
												</div>

												<div class="form-group validated col-lg-9 col-12">
													<label class="col-form-label">Token NFSe (opcional)</label>
													<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Integração com Webmania, entre em contato com o Admin para gerar o token da empresa"><i class="la la-info"></i></button>
													<div class="">
														<input id="token_nfse" type="text" class="form-control @if($errors->has('token_nfse')) is-invalid @endif" name="token_nfse" value="{{{ isset($config) ? $config->token_nfse : old('token_nfse') }}}">
														@if($errors->has('token_nfse'))
														<div class="invalid-feedback">
															{{ $errors->first('token_nfse') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-4 col-sm-10">
													<label class="col-form-label">Token WhatsApp(opcional)</label>
													<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Integração com WhatsApp: Cadastre-se no menu Atendimento/Chat Suporte para começar a utilizar o serviço ou entre em contato conosco para efetuar a ativação."><i class="la la-info"></i></button>
													<div class="">
														<input id="token_whatsapp" type="text" class="form-control @if($errors->has('token_whatsapp')) is-invalid @endif" name="token_whatsapp" value="{{{ isset($config) ? $config->token_whatsapp : old('token_whatsapp') }}}">
														@if($errors->has('token_whatsapp'))
														<div class="invalid-feedback">
															{{ $errors->first('token_whatsapp') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-4 col-md-4 col-sm-10">
													<label class="col-form-label">Cód. de tributação do município (opcional)</label>
													<input id="codigo_tributacao_municipio" type="text" class="form-control @if($errors->has('codigo_tributacao_municipio')) is-invalid @endif" name="codigo_tributacao_municipio" value="{{{ isset($config) ? $config->codigo_tributacao_municipio : old('codigo_tributacao_municipio') }}}">
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">% Lucro padrão</label>
													<div class="">
														<input id="senha_remover" type="text" class="form-control @if($errors->has('percentual_lucro_padrao')) is-invalid @endif perc" name="percentual_lucro_padrao" value="{{{ isset($config) ? $config->percentual_lucro_padrao : old('percentual_lucro_padrao') }}}">
														@if($errors->has('percentual_lucro_padrao'))
														<div class="invalid-feedback">
															{{ $errors->first('percentual_lucro_padrao') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-3 col-sm-3">
													<label class="col-form-label">Gerenciar estoque produto</label>
													<div class="">
														<select class="custom-select" name="gerenciar_estoque_produto">
															<option @isset($config) @if($config->gerenciar_estoque_produto == 1) selected @endif @endisset value="1">Sim</option>
															<option @isset($config) @if($config->gerenciar_estoque_produto == 0) selected @endif @endisset value="0">Não</option>

														</select>
														@if($errors->has('gerenciar_estoque_produto'))
														<div class="invalid-feedback">
															{{ $errors->first('gerenciar_estoque_produto') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-3 col-md-3 col-sm-3">
													<label class="col-form-label">Gerenciar comissão usuário</label>
													<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se sim o usuário logado receberá a comissão das vendas do PDV"><i class="la la-info"></i></button>
													<div class="">
														<select class="custom-select" name="gerenciar_comissao_usuario_logado">

															<option @isset($config) @if($config->gerenciar_comissao_usuario_logado == 0) selected @endif @endisset value="0">Não</option>
															<option @isset($config) @if($config->gerenciar_comissao_usuario_logado == 1) selected @endif @endisset value="1">Sim</option>
														</select>
														@if($errors->has('gerenciar_comissao_usuario_logado'))
														<div class="invalid-feedback">
															{{ $errors->first('gerenciar_comissao_usuario_logado') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">% Max. desconto</label>
													<div class="">
														<input type="tel" class="form-control @if($errors->has('percentual_max_desconto')) is-invalid @endif perc" name="percentual_max_desconto" value="{{{ isset($config) ? $config->percentual_max_desconto : old('percentual_max_desconto') }}}">
														@if($errors->has('percentual_max_desconto'))
														<div class="invalid-feedback">
															{{ $errors->first('percentual_max_desconto') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">Validade orçamento</label>
													<div class="">
														<input type="tel" class="form-control @if($errors->has('validade_orcamento')) is-invalid @endif number" name="validade_orcamento" value="{{{ isset($config) ? $config->validade_orcamento : old('validade_orcamento') }}}">
														@if($errors->has('validade_orcamento'))
														<div class="invalid-feedback">
															{{ $errors->first('validade_orcamento') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">Tipo caixa</label>
													<div class="">
														<select name="caixa_por_usuario" class="custom-select">
															<option @isset($config) @if($config->caixa_por_usuario == 1) selected @endif @endisset value="1">Por usuário</option>
															<option @isset($config) @if($config->caixa_por_usuario == 0) selected @endif @endisset value="0">Por empresa</option>
														</select>
														@if($errors->has('caixa_por_usuario'))
														<div class="invalid-feedback">
															{{ $errors->first('caixa_por_usuario') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-10">
													<label class="col-form-label">Senha padrão sistema (opcional)</label>
													<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se definida utilizada em algumas ações do sistema exemplo remover venda e outros"><i class="la la-info"></i></button>
													<div class="">
														<input id="senha_remover" type="password" class="form-control @if($errors->has('senha_remover')) is-invalid @endif" name="senha_remover" value="{{old('senha_remover')}}">
														@if($errors->has('senha_remover'))
														<div class="invalid-feedback">
															{{ $errors->first('senha_remover') }}
														</div>
														@endif
													</div>
													@if(isset($config))
													<a href="/configNF/removeSenha/{{$config->id}}">remover senha</a>
													@endif
												</div>

												<div class="form-group validated col-lg-2 col-md-3 col-sm-3">
													<label class="col-form-label">Casas decimais valor</label>
													<div class="">
														<select class="custom-select" name="casas_decimais">
															<option @isset($config) @if($config->casas_decimais == 2) selected @endif @endisset value="2">2</option>
															<option @isset($config) @if($config->casas_decimais == 3) selected @endif @endisset value="3">3</option>
															<option @isset($config) @if($config->casas_decimais == 4) selected @endif @endisset value="4">4</option>
															<option @isset($config) @if($config->casas_decimais == 5) selected @endif @endisset value="5">5</option>
															<option @isset($config) @if($config->casas_decimais == 6) selected @endif @endisset value="6">6</option>
															<option @isset($config) @if($config->casas_decimais == 7) selected @endif @endisset value="7">7</option>
														</select>
														@if($errors->has('casas_decimais'))
														<div class="invalid-feedback">
															{{ $errors->first('casas_decimais') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-3 col-sm-3">
													<label class="col-form-label">Casas decimais Qtd.</label>
													<div class="">
														<select class="custom-select" name="casas_decimais_qtd">
															<option @isset($config) @if($config->casas_decimais_qtd == 2) selected @endif @endisset value="2">2</option>
															<option @isset($config) @if($config->casas_decimais_qtd == 3) selected @endif @endisset @if(!($config)) selected @endif value="3">3</option>
															<option @isset($config) @if($config->casas_decimais_qtd == 4) selected @endif @endisset value="4">4</option>
															<option @isset($config) @if($config->casas_decimais_qtd == 5) selected @endif @endisset value="5">5</option>
															<option @isset($config) @if($config->casas_decimais_qtd == 6) selected @endif @endisset value="6">6</option>
															<option @isset($config) @if($config->casas_decimais_qtd == 7) selected @endif @endisset value="7">7</option>
														</select>
														@if($errors->has('casas_decimais_qtd'))
														<div class="invalid-feedback">
															{{ $errors->first('casas_decimais_qtd') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-3 col-md-6 col-sm-6">
													<label class="col-form-label">Frete Padrão</label>

													<select class="custom-select form-control" name="frete_padrao">
														@foreach($tiposFrete as $key => $t)
														<option value="{{$key}}"
														@isset($config)
														@if($key == $config->frete_padrao)
														selected
														@endif
														@endisset
														>{{$key}} - {{$t}}</option>
														@endforeach
													</select>

												</div>

												<div class="form-group validated col-lg-3 col-md-6 col-sm-6">
													<label class="col-form-label">Tipo de pagamento Padrão</label>

													<select class="custom-select form-control" name="tipo_pagamento_padrao">
														@foreach($tiposPagamento as $key => $t)
														<option value="{{$key}}"
														@isset($config)
														@if($key == $config->tipo_pagamento_padrao)
														selected
														@endif
														@endisset
														>{{$key}} - {{$t}}</option>
														@endforeach
													</select>

												</div>

												<div class="form-group validated col-lg-3 col-md-6 col-sm-6">
													<label class="col-form-label">Tipo de impressão DANFE</label>

													<select class="custom-select form-control" name="tipo_impressao_danfe">
														<option value="1" @isset($config) @if($config->tipo_impressao_danfe == 1) selected @endif @endif>Vertical</option>
														<option value="2" @isset($config) @if($config->tipo_impressao_danfe == 2) selected @endif @endif>Horizontal</option>
													</select>

												</div>

												<div class="form-group validated col-lg-2 col-md-6 col-sm-6">
													<label class="col-form-label">Email próprio</label>
													<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se sim configurar as credencias de email no menu Configurações/Configurar email"><i class="la la-info"></i></button>
													<select class="custom-select form-control" name="usar_email_proprio">
														<option @isset($config)@if($config->usar_email_proprio == 0) selected @endif @endif value="0">Não</option>
														<option @isset($config)@if($config->usar_email_proprio == 1) selected @endif @endif value="1">Sim</option>
													</select>

												</div>

												<div class="form-group validated col-lg-2 col-md-6 col-sm-6">
													<label class="col-form-label">Busca documentos</label>
													<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se sim busca os documentos do menu Entradas\Manifesto de forma automatica de 1 em 1 hora"><i class="la la-info"></i></button>
													<select class="custom-select form-control" name="busca_documento_automatico">
														<option @isset($config)@if($config->busca_documento_automatico == 0) selected @endif @endif value="0">Não</option>
														<option @isset($config)@if($config->busca_documento_automatico == 1) selected @endif @endif value="1">Sim</option>
													</select>

												</div>

												<div class="form-group validated col-lg-2 col-md-6 col-sm-6">
													<label class="col-form-label">Alerta sonoro</label>
													<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se definido soará um alerta ao finalizar açao com sucesso de venda pedido, PDV, compra, Cte, Mdfe e outros."><i class="la la-info"></i></button>
													<select id="alerta_sonoro" class="custom-select form-control" name="alerta_sonoro">
														<option value="">--</option>
														@foreach(App\Models\ConfigNota::getAlertas() as $key => $a)
														<option @isset($config) @if($config->alerta_sonoro == $key) selected @endif @endif value="{{$key}}">{{$a}}</option>
														@endforeach
													</select>

												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">Parcelamento Max.</label>
													<div class="">
														<input id="senha_remover" type="tel" class="form-control @if($errors->has('parcelamento_maximo')) is-invalid @endif" name="parcelamento_maximo" value="{{{ isset($config) ? $config->parcelamento_maximo : old('parcelamento_maximo') }}}">
														@if($errors->has('parcelamento_maximo'))
														<div class="invalid-feedback">
															{{ $errors->first('parcelamento_maximo') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">%Juros padrão</label>
													<div class="">
														<input id="senha_remover" type="tel" class="form-control @if($errors->has('juro_padrao')) is-invalid @endif money" name="juro_padrao" value="{{{ isset($config) ? $config->juro_padrao : old('juro_padrao') }}}">
														@if($errors->has('juro_padrao'))
														<div class="invalid-feedback">
															{{ $errors->first('juro_padrao') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
													<label class="col-form-label">%Multa padrão</label>
													<div class="">
														<input id="senha_remover" type="tel" class="form-control @if($errors->has('multa_padrao')) is-invalid @endif money" name="multa_padrao" value="{{{ isset($config) ? $config->multa_padrao : old('multa_padrao') }}}">
														@if($errors->has('multa_padrao'))
														<div class="invalid-feedback">
															{{ $errors->first('multa_padrao') }}
														</div>
														@endif
													</div>
												</div>

												<div class="form-group validated col-sm-12 col-lg-12">
													<label class="col-form-label">Observação para NFe (opcional)</label>
													<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Para o cálculo automático de % aproveitamento crédito, utilize R$ e %, ex: Permite o aproveitamento de ICMS no valor de R$ correspondente a alíquota de % nos termos do art.23 da lc 123"><i class="la la-info"></i></button>
													<div class="">

														<div class="row">
															<div class="col-12">
																<textarea class="form-control" name="campo_obs_nfe" id="campo_obs_nfe" >{{isset($config) ? $config->campo_obs_nfe : old('campo_obs_nfe')}}</textarea>
															</div>
														</div>

														@if($errors->has('campo_obs_nfe'))
														<div class="invalid-feedback">
															{{ $errors->first('campo_obs_nfe') }}
														</div>
														@endif
													</div>
												</div>


												<div class="form-group validated col-sm-12 col-lg-12">
													<label class="col-form-label">Observação padrão para Pedido/Orçamento (opcional)</label>
													<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Está informação será incluada na impressão de pedido/orçamento"><i class="la la-info"></i></button>
													<div class="">

														<div class="row">
															<div class="col-12">
																<textarea class="form-control" name="campo_obs_pedido" id="campo_obs_pedido" >{{isset($config) ? $config->campo_obs_pedido : old('campo_obs_pedido')}}</textarea>
															</div>
														</div>

														@if($errors->has('campo_obs_pedido'))
														<div class="invalid-feedback">
															{{ $errors->first('campo_obs_pedido') }}
														</div>
														@endif
													</div>
												</div>

												<div class="col-12">
													<label class="col-form-label">
														Graficos para tela inicial
													</label>
													<div class="" style="display: grid;grid-template-columns: 1fr 1fr 1fr;">
														@foreach(App\Models\ConfigNota::graficos() as $key => $t)
														<label>
															<input  type="checkbox" name="graficos_dash[]" value="{{$key}}" @if($config != null) @if(sizeof($config->graficos_dash) > 0 && in_array($key, $config->graficos_dash)) checked="true" @endif @endif>
															{{$t}}
														</label>
														@endforeach
													</div>
												</div>

											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/clientes">
									<i class="la la-close"></i>
									<span class="">Cancelar</span>
								</a>
							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<button style="width: 100%" type="submit" class="btn btn-success">
									<i class="la la-check"></i>
									<span class="">Salvar</span>
								</button>
							</div>

						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
@section('javascript')
<script type="text/javascript">
	$(function(){
		setTimeout(() => {
			isCnpj()
		}, 50)

		@if($config == null)
		if($('#razao_social').val() == ""){
			consultaCNPJ()
			swal('Atenção', 'Preencha o restante das informações para salvar o formulário de emitente', 'info')
		}
		@endif
	})

	$('#cnpj').keyup(() => {
		isCnpj()
	})

	function isCnpj(){
		let cnpj =

		$('#cnpj').val().replace(/[^0-9]/g,'')
		if(cnpj.length == 14){
			$('#btn-consulta-cadastro').removeAttr('disabled')
		}else{
			$('#btn-consulta-cadastro').attr('disabled', 1)
		}
	}
	$('[data-toggle="popover"]').popover()

	$('#alerta_sonoro').change(() => {
		let alerta_sonoro = $('#alerta_sonoro').val()
		var audio = new Audio('audio/'+alerta_sonoro);
		audio.addEventListener('canplaythrough', function() {
			audio.play();
		});
	})

	var fileUpload = document.getElementById("file");
	fileUpload.addEventListener("change", function () {
		if (typeof fileUpload.files != "undefined") {
			var size = parseFloat(fileUpload.files[0].size / 1024).toFixed(2);

			if(size > 300){
				$('#file').val('')
				$('.image-input-wrapper').css('background-image', 'url(/imgs/logo.png)')
				swal("Alerta", "Insira uma imagem com até 300 kb", "warning")
			}
		}
	});
</script>

<script>
    function gerarToken() {
        // Gera uma string hexadecimal aleatória de 'len' caracteres
        function randomHex(len) {
            let hex = '';
            for(let i = 0; i < len; i++) {
                hex += Math.floor(Math.random() * 16).toString(16);
            }
            return hex;
        }
        // Gera um timestamp preenchido com zeros até 20 dígitos
        function paddedTimestamp() {
            let ts = Date.now().toString();
            return ts.padStart(20, '0');
        }
        // Gera um UUID v4 (usa crypto.randomUUID se disponível)
        function generateUUID() {
            if (crypto.randomUUID) {
                return crypto.randomUUID();
            }
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                var r = Math.random() * 16 | 0,
                    v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }
        let part1 = randomHex(32);
        let part2 = paddedTimestamp();
        let part3 = generateUUID();
        let tokenData = part1 + ":::" + part2 + ":::" + part3;
        // btoa gera a codificação base64
        let encoded = btoa(tokenData);
        let token = "$aact_" + encoded;
        document.getElementById('token_sync').value = token;
    }
</script>
@endsection
@endsection
