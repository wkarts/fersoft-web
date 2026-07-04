@extends('default.layout')
@section('content')
<style type="text/css">
	.camera{
		height: 200px;
		width: 200px;
	}
</style>
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" id="form-cliente" action="/clientes/{{{ isset($cliente) ? 'update' : 'save' }}}" enctype="multipart/form-data">
					@if(!isset($cliente))
					<input type="hidden" name="_save_token" value="{{ session('cliente_save_token') }}">
					@endif
					<input type="hidden" name="id" value="{{{ isset($cliente) ? $cliente->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{isset($cliente) ? 'Editar' : 'Novo'}} Cliente</h3>
						</div>
					</div>
					@csrf

					@isset($cliente)
					@if($cliente->valorCredito() > 0)
					<h4>Valor de crédito: <strong class="text-info">R$ {{ number_format($cliente->valorCredito(), 2, ',', '.')}}</strong></h4>
					@endif
					@endif
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group col-sm-12 col-lg-12">
											<label>Pessoa:</label>
											<div class="radio-inline">

												<label class="radio radio-success">
													<input value="p_fisica" name="group1" type="radio" id="pessoaFisica" @if(isset($cliente)) @if(strlen($cliente->cpf_cnpj)
													< 15) checked @endif @endif @if(old('group1') == 'p_fisica') checked @endif/>
													<span></span>
													FISICA
												</label>
												<label class="radio radio-success">
													<input value="p_juridica" name="group1" type="radio" id="pessoaJuridica" @if(isset($cliente)) @if(strlen($cliente->cpf_cnpj) > 15) checked @endif @endif @if(old('group1') == 'p_juridica') checked @endif/>
													<span></span>
													JURIDICA
												</label>

												<label class="radio radio-success">
													<input value="p_ext" name="group1" type="radio" id="pessoaExt" @if(isset($cliente)) @if($cliente->cpf_cnpj == '00.000.000/0000-00') checked @endif @endif @if(old('group1') == 'p_ext') checked @endif/>
													<span></span>
													EXTERIOR
												</label>
											</div>

										</div>
									</div>

									<div class="row">
										<div class="form-group validated col-sm-3 col-lg-4">
											<label class="col-form-label" id="lbl_cpf_cnpj">CPF</label>
											<div class="">
												<input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif cpf_cnpj" name="cpf_cnpj" value="{{{ isset($cliente) ? $cliente->cpf_cnpj : old('cpf_cnpj') }}}">
												@if($errors->has('cpf_cnpj'))
												<div class="invalid-feedback">
													{{ $errors->first('cpf_cnpj') }}
												</div>
												@endif
											</div>
										</div>
										<!-- <div class="form-group validated col-lg-2 col-md-2 col-sm-6">
											<label class="col-form-label">UF</label>

											<select class="custom-select form-control" id="sigla_uf" name="sigla_uf">
												@foreach($estados as $c)
												<option @if(isset($cliente)) @if($cliente->cidade->uf == $c) selected @endif @endif value="{{$c}}" 
													@if(old('sigla_uf') == $c)
													selected
													@endif>
													{{$c}}
												</option>
												@endforeach
											</select>

										</div> -->
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
												<input id="razao_social" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif" name="razao_social" value="{{{ isset($cliente) ? $cliente->razao_social : old('razao_social') }}}">
												@if($errors->has('razao_social'))
												<div class="invalid-feedback">
													{{ $errors->first('razao_social') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-10 col-lg-6">
											<label class="col-form-label">Nome Fantasia</label>
											<div class="">
												<input id="nome_fantasia" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif" name="nome_fantasia" value="{{{ isset($cliente) ? $cliente->nome_fantasia : old('nome_fantasia') }}}">
												@if($errors->has('nome_fantasia'))
												<div class="invalid-feedback">
													{{ $errors->first('nome_fantasia') }}
												</div>
												@endif
											</div>
										</div>
									</div>


									<div class="row">

										<div class="form-group validated col-sm-3 col-lg-3">
											<label class="col-form-label" id="lbl_i_rg">IE/RG</label>
											<div class="">
												<input type="text" id="ie_rg" class="form-control @if($errors->has('ie_rg')) is-invalid @endif" name="ie_rg" value="{{{ isset($cliente) ? $cliente->ie_rg : old('ie_rg') }}}">
												@if($errors->has('ie_rg'))
												<div class="invalid-feedback">
													{{ $errors->first('ie_rg') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
											<label class="col-form-label">Consumidor Final</label>

											<select class="custom-select form-control" id="consumidor_final" name="consumidor_final">
												<option value=""></option>
												<option @if(isset($cliente) && $cliente->consumidor_final == 1) selected @endif value="1" @if(old('consumidor_final') == 1) selected @endif selected>SIM</option>
												<option @if(isset($cliente) && $cliente->consumidor_final == 0) selected @endif value="0" @if(old('consumidor_final') == 0) @endif>NAO</option>
											</select>
											@if($errors->has('consumidor_final'))
											<div class="invalid-feedback">
												{{ $errors->first('consumidor_final') }}
											</div>
											@endif

										</div>

										<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
											<label class="col-form-label">Contribuinte</label>

											<select class="custom-select form-control" id="contribuinte" name="contribuinte">
												<option value=""></option>
												<option @if(isset($cliente) && $cliente->contribuinte == 1) selected @endif value="1" @if(old('contribuinte') == 1) selected @endif selected>SIM</option>
												<option @if(isset($cliente) && $cliente->contribuinte == 0) selected @endif value="0" @if(old('contribuinte') == 0) @endif>NAO</option>
											</select>
											@if($errors->has('contribuinte'))
											<div class="invalid-feedback">
												{{ $errors->first('contribuinte') }}
											</div>
											@endif

										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label" id="lbl_i">Limite de Venda</label>
											<div class="">
												<input type="text" id="limite_venda" class="form-control @if($errors->has('limite_venda')) is-invalid @endif money" name="limite_venda" value="{{{ isset($cliente) ? moeda($cliente->limite_venda) : old('limite_venda') }}}">
												@if($errors->has('limite_venda'))
												<div class="invalid-feedback">
													{{ $errors->first('limite_venda') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label" id="lbl_i">Valor de CashBack</label>
											<div class="">
												<input type="text" id="valor_cashback" class="form-control @if($errors->has('valor_cashback')) is-invalid @endif money" name="valor_cashback" value="{{{ isset($cliente) ? moeda($cliente->valor_cashback) : old('valor_cashback') }}}">
												@if($errors->has('valor_cashback'))
												<div class="invalid-feedback">
													{{ $errors->first('valor_cashback') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label text-left col-lg-12 col-sm-12">Inativo</label>
											<div class="col-6">
												<span class="switch switch-outline switch-danger">
													<label>
														<input value="true" @if(isset($cliente) && $cliente->inativo) checked @endif type="checkbox" name="inativo" id="inativo">
														<span></span>
													</label>
												</span>
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label" id="">Data de Nascimento</label>
											<div class="">
												<input type="text" id="data_nascimento" class="form-control @if($errors->has('data_nascimento')) is-invalid @endif" data-mask="00/00/0000" data-mask-reverse="true" name="data_nascimento" value="{{{ isset($cliente) ? $cliente->data_nascimento : old('data_nascimento') }}}">
												@if($errors->has('data_nascimento'))
												<div class="invalid-feedback">
													{{ $errors->first('data_nascimento') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2 d-none">
											<label class="col-form-label" id="">Data de Aniversário</label>
											<div class="">
												<input type="text" id="data_aniversario" class="form-control @if($errors->has('data_aniversario')) is-invalid @endif" data-mask="00/00" data-mask-reverse="true" name="data_aniversario" value="{{{ isset($cliente) ? $cliente->data_aniversario : old('data_aniversario') }}}">
												@if($errors->has('data_aniversario'))
												<div class="invalid-feedback">
													{{ $errors->first('data_aniversario') }}
												</div>
												@endif
											</div>
										</div>

									</div>
									<hr>
									<h5>Endereço de Faturamento</h5>
									<div class="row">
										<div class="form-group validated col-sm-8 col-lg-8">
											<label class="col-form-label">Rua</label>
											<div class="">
												<input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif" name="rua" value="{{{ isset($cliente) ? $cliente->rua : old('rua') }}}">
												@if($errors->has('rua'))
												<div class="invalid-feedback">
													{{ $errors->first('rua') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-2 col-lg-2">
											<label class="col-form-label">Número</label>
											<div class="">
												<input id="numero" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{{ isset($cliente) ? $cliente->numero : old('numero') }}}">
												@if($errors->has('numero'))
												<div class="invalid-feedback">
													{{ $errors->first('numero') }}
												</div>
												@endif
											</div>
										</div>
									</div>
									<div class="row">
										<div class="form-group validated col-sm-8 col-lg-5">
											<label class="col-form-label">Complemento</label>
											<div class="">
												<input id="complemento" type="text" class="form-control @if($errors->has('complemento')) is-invalid @endif" name="complemento" value="{{{ isset($cliente) ? $cliente->complemento : old('complemento') }}}">
												@if($errors->has('complemento'))
												<div class="invalid-feedback">
													{{ $errors->first('complemento') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-3">
											<label class="col-form-label">Bairro</label>
											<div class="">
												<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{{ isset($cliente) ? $cliente->bairro : old('bairro') }}}">
												@if($errors->has('bairro'))
												<div class="invalid-feedback">
													{{ $errors->first('bairro') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">CEP</label>
											<div class="">
												<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{{ isset($cliente) ? $cliente->cep : old('cep') }}}">
												@if($errors->has('cep'))
												<div class="invalid-feedback">
													{{ $errors->first('cep') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-3">
											<label class="col-form-label">Email</label>
											<div class="">
												<input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($cliente) ? $cliente->email : old('email') }}}">
												@if($errors->has('email'))
												<div class="invalid-feedback">
													{{ $errors->first('email') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-4 col-md-5 col-sm-10">
											<label class="col-form-label text-left">Cidade</label>
											<select class="form-control select2 @if($errors->has('cidade_id')) is-invalid @endif" id="kt_select2_1" name="cidade_id">
												<option value="">Selecione a cidade</option>
												@foreach($cidades as $c)
												<option value="{{$c->id}}" @isset($cliente) @if($c->id == $cliente->cidade_id) selected @endif @endisset 
													@if(old('cidade_id') == $c->id)
													selected
													@endif
													>
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

										<div class="form-group validated col-lg-3 col-md-3 col-sm-6">
											<label class="col-form-label text-left">Pais</label>
											<select class="form-control select2" id="kt_select2_3" name="cod_pais">
												@foreach($pais as $p)
												<option value="{{$p->codigo}}" @if(isset($cliente)) @if($p->codigo == $cliente->cod_pais) selected @endif @else @if($p->codigo == 1058) selected @endif @endif >{{$p->codigo}} -  ({{$p->nome}})</option>
												@endforeach
											</select>
											@if($errors->has('cod_pais'))
											<div class="invalid-feedback">
												{{ $errors->first('cod_pais') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-8 col-lg-4">
											<label class="col-form-label">ID estrangeiro (Opcional)</label>
											<div class="">
												<input id="id_estrangeiro" type="text" class="form-control @if($errors->has('id_estrangeiro')) is-invalid @endif" name="id_estrangeiro" value="{{{ isset($cliente) ? $cliente->id_estrangeiro : old('id_estrangeiro') }}}">
												@if($errors->has('id_estrangeiro'))
												<div class="invalid-feedback">
													{{ $errors->first('id_estrangeiro') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-3">
											<label class="col-form-label">Telefone (Opcional)</label>
											<div class="">
												<input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif" name="telefone" value="{{{ isset($cliente) ? $cliente->telefone : old('telefone') }}}">
												@if($errors->has('telefone'))
												<div class="invalid-feedback">
													{{ $errors->first('telefone') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-3">
											<label class="col-form-label">Celular/WhatsApp (Opcional)</label>
											<div class="">
												<input id="celular" type="text" class="form-control @if($errors->has('celular')) is-invalid @endif" name="celular" value="{{{ isset($cliente) ? $cliente->celular : old('celular') }}}">
												@if($errors->has('celular'))
												<div class="invalid-feedback">
													{{ $errors->first('celular') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-3">
											<label class="col-form-label">Grupo (Opcional)</label>
											<div class="">
												
												<select class="custom-select form-control" name="grupo_id">
													<option value="0">--</option>
													@foreach($grupos as $g)
													<option @if(isset($cliente)) @if($cliente->grupo_id == $g->id) selected @endif @endif value="{{$g->id}}" 
														@if(old('grupo_id') == $g->id)
														selected
														@endif>
														{{$g->nome}}
													</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-3">
											<label class="col-form-label">Assessor (Opcional)</label>
											<div class="">
												
												<select class="custom-select form-control" name="acessor_id">
													<option value="0">--</option>
													@foreach($acessores as $a)
													<option @if(isset($cliente)) @if($cliente->acessor_id == $a->id) selected @endif @endif value="{{$a->id}}" 
														@if(old('acessor_id') == $a->id)
														selected
														@endif>
														{{$a->razao_social}}
													</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="form-group validated col-lg-12 col-md-12 col-sm-12">
											<label class="col-xl-12 col-lg-12 col-form-label text-left">Imagem</label>
											<div class="col-lg-12 col-xl-12">

												<div class="image-input image-input-outline" id="kt_image_1">
													<div class="image-input-wrapper search-div"
													@if(!isset($cliente) || $cliente->imagem == '') style="background-image: url(/foto_usuario/user.png)" @else
													style="background-image: url(/imgs_clientes/{{$cliente->imagem}})"
													@endif></div>
													<label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow search-div" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
														<i class="fa fa-pencil icon-sm text-muted"></i>
														<input type="file" id="file" name="file" accept=".png, .jpg, .jpeg">
														<input type="hidden" name="profile_avatar_remove">
													</label>
													<span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow search-div" data-action="cancel" data-toggle="tooltip" title="" data-original-title="Cancel avatar">
														<i class="fa fa-close icon-xs text-muted"></i>
													</span>

													<button type="button" id="btn-foto" class="btn btn-info btn-sm btn-block">
														<i class="la la-camera"></i>
													Tirar foto</button>

												</div>
												<span class="form-text text-muted">.png, .jpg, .jpeg</span>
												@if($errors->has('file'))
												<div class="invalid-feedback">
													{{ $errors->first('file') }}
												</div>
												@endif

											</div>
										</div>
									</div>

									<div class="row">
										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label text-left col-lg-12 col-sm-12">Dados do Contador</label>
											<div class="col-12">
												<span class="switch switch-outline switch-info">
													<label>
														<input value="true" @if(isset($cliente) && $cliente->contador_nome != "") checked @endif @if(old('info_contador')) checked @endif type="checkbox" name="info_contador" id="info_contador">
														<span></span>
													</label>
												</span>
											</div>
										</div>

										<div class="form-group validated col-sm-5 col-lg-3 ct">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input id="contador_nome" type="text" class="form-control @if($errors->has('contador_nome')) is-invalid @endif" name="contador_nome" value="{{{ isset($cliente) ? $cliente->contador_nome : old('contador_nome') }}}">
												@if($errors->has('contador_nome'))
												<div class="invalid-feedback">
													{{ $errors->first('contador_nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 ct">
											<label class="col-form-label">Telefone</label>
											<div class="">
												<input id="contador_telefone" type="text" class="form-control @if($errors->has('contador_telefone')) is-invalid @endif telefone" name="contador_telefone" value="{{{ isset($cliente) ? $cliente->contador_telefone : old('contador_telefone') }}}">
												@if($errors->has('contador_telefone'))
												<div class="invalid-feedback">
													{{ $errors->first('contador_telefone') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-5 col-lg-4 ct">
											<label class="col-form-label">Email</label>
											<div class="">
												<input id="contador_email" type="email" class="form-control @if($errors->has('contador_email')) is-invalid @endif" name="contador_email" value="{{{ isset($cliente) ? $cliente->contador_email : old('contador_email') }}}">
												@if($errors->has('contador_email'))
												<div class="invalid-feedback">
													{{ $errors->first('contador_email') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									<div class="row">
										<div class="form-group validated col-sm-8 col-lg-6 col-12">
											<label class="col-form-label">Observação</label>
											<div class="">
												<input id="observacao" type="text" class="form-control @if($errors->has('observacao')) is-invalid @endif" name="observacao" value="{{{ isset($cliente) ? $cliente->observacao : old('observacao') }}}">
												@if($errors->has('observacao'))
												<div class="invalid-feedback">
													{{ $errors->first('observacao') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3 col-12">
											<label class="col-form-label">Vendedor/Funcionário</label>
											<div class="">
												<select class="custom-select form-control" name="funcionario_id">
													<option value="0">--</option>
													@foreach($funcionarios as $f)
													<option @if(isset($cliente)) @if($cliente->funcionario_id == $f->id) selected @endif @endif value="{{$f->id}}" 
														@if(old('funcionario_id') == $f->id)
														selected
														@endif>
														{{$f->nome}}
													</option>
													@endforeach
												</select>
												@if($errors->has('funcionario_id'))
												<div class="invalid-feedback">
													{{ $errors->first('funcionario_id') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label text-left col-lg-12 col-sm-12">Receita ótica</label>
											<div class="col-12">
												<span class="switch switch-outline switch-info">
													<label>
														<input value="true" @if(isset($cliente) && $cliente->receitaOtica) checked @endif type="checkbox" name="receita_otica" id="receita_otica">
														<span></span>
													</label>

													@if(isset($cliente) && $cliente->receitaOtica)
													<button type="button" onclick="$('#modal-otica').modal('show')" class="btn btn-sm btn-info">Ver receita</button>
													@endif
												</span>
											</div>
										</div>
									</div>

									<hr>
									<h5>Endereço de Cobrança (Opcional)</h5>
									<span class="switch switch-outline switch-dark">
										<label>
											<input value="true" @if(isset($cliente) && $cliente->rua_cobranca != "") checked @endif @if(old('rua_cobranca')) checked @endif type="checkbox" id="info_endereco_cobranca">
											<span></span>
										</label>
									</span>
									<div class="row d-none endereco-cobranca">
										<div class="form-group validated col-sm-8 col-lg-6">
											<label class="col-form-label">Rua</label>
											<div class="">
												<input id="rua_cobranca" type="text" class="form-control @if($errors->has('rua_cobranca')) is-invalid @endif" name="rua_cobranca" value="{{{ isset($cliente) ? $cliente->rua_cobranca : old('rua_cobranca') }}}">
												@if($errors->has('rua_cobranca'))
												<div class="invalid-feedback">
													{{ $errors->first('rua_cobranca') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-2 col-lg-2">
											<label class="col-form-label">Número</label>
											<div class="">
												<input id="numero_cobranca" type="text" class="form-control @if($errors->has('numero_cobranca')) is-invalid @endif" name="numero_cobranca" value="{{{ isset($cliente) ? $cliente->numero_cobranca : old('numero_cobranca') }}}">
												@if($errors->has('numero_cobranca'))
												<div class="invalid-feedback">
													{{ $errors->first('numero_cobranca') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-4">
											<label class="col-form-label">Bairro</label>
											<div class="">
												<input id="bairro_cobranca" type="text" class="form-control @if($errors->has('bairro_cobranca')) is-invalid @endif" name="bairro_cobranca" value="{{{ isset($cliente) ? $cliente->bairro_cobranca : old('bairro_cobranca') }}}">
												@if($errors->has('bairro_cobranca'))
												<div class="invalid-feedback">
													{{ $errors->first('bairro_cobranca') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">CEP</label>
											<div class="">
												<input id="cep_cobranca" type="text" class="form-control @if($errors->has('cep_cobranca')) is-invalid @endif cep" name="cep_cobranca" value="{{{ isset($cliente) ? $cliente->cep_cobranca : old('cep_cobranca') }}}">
												@if($errors->has('cep_cobranca'))
												<div class="invalid-feedback">
													{{ $errors->first('cep_cobranca') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-4 col-md-6 col-sm-10">
											<label class="col-form-label">Cidade</label>

											<select class="form-control select2" style="width: 100%" id="kt_select2_2" name="cidade_cobranca_id">
												<option value="">Selecione a cidade</option>
												@foreach($cidades as $c)
												<option value="{{$c->id}}" @isset($cliente) @if($c->id == $cliente->cidade_cobranca_id)selected
													@endif
													@endisset >
													{{$c->nome}} ({{$c->uf}})
												</option>
												@endforeach
											</select>
											@if($errors->has('cidade_cobranca_id'))
											<div class="invalid-feedback">
												{{ $errors->first('cidade_cobranca_id') }}
											</div>
											@endif
										</div>
									</div>

									<hr>
									<h5>Endereço de entrega (Opcional)</h5>
									<span class="switch switch-outline switch-warning">
										<label>
											<input value="true" @if(isset($cliente) && $cliente->rua_entrega != "") checked @endif @if(old('rua_entrega')) checked @endif type="checkbox" id="info_endereco_entrega">
											<span></span>
										</label>
									</span>
									<div class="row d-none endereco-entrega">

										<div class="form-group validated col-sm-8 col-lg-4">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input id="nome_entrega" type="text" class="form-control @if($errors->has('nome_entrega')) is-invalid @endif" name="nome_entrega" value="{{{ isset($cliente) ? $cliente->nome_entrega : old('nome_entrega') }}}">
												@if($errors->has('nome_entrega'))
												<div class="invalid-feedback">
													{{ $errors->first('nome_entrega') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-3">
											<label class="col-form-label">CPF/CNPJ</label>
											<div class="">
												<input id="cpf_cnpj_entrega" type="text" class="form-control @if($errors->has('cpf_cnpj_entrega')) is-invalid @endif cpf_cnpj" name="cpf_cnpj_entrega" value="{{{ isset($cliente) ? $cliente->cpf_cnpj_entrega : old('cpf_cnpj_entrega') }}}">
												@if($errors->has('cpf_cnpj_entrega'))
												<div class="invalid-feedback">
													{{ $errors->first('cpf_cnpj_entrega') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-5">
											<label class="col-form-label">Rua</label>
											<div class="">
												<input id="rua_entrega" type="text" class="form-control @if($errors->has('rua_entrega')) is-invalid @endif" name="rua_entrega" value="{{{ isset($cliente) ? $cliente->rua_entrega : old('rua_entrega') }}}">
												@if($errors->has('rua_entrega'))
												<div class="invalid-feedback">
													{{ $errors->first('rua_entrega') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-2 col-lg-2">
											<label class="col-form-label">Número</label>
											<div class="">
												<input id="numero_entrega" type="text" class="form-control @if($errors->has('numero_entrega')) is-invalid @endif" name="numero_entrega" value="{{{ isset($cliente) ? $cliente->numero_entrega : old('numero_entrega') }}}">
												@if($errors->has('numero_entrega'))
												<div class="invalid-feedback">
													{{ $errors->first('numero_entrega') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-4">
											<label class="col-form-label">Bairro</label>
											<div class="">
												<input id="bairro_entrega" type="text" class="form-control @if($errors->has('bairro_entrega')) is-invalid @endif" name="bairro_entrega" value="{{{ isset($cliente) ? $cliente->bairro_entrega : old('bairro_entrega') }}}">
												@if($errors->has('bairro_entrega'))
												<div class="invalid-feedback">
													{{ $errors->first('bairro_entrega') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">CEP</label>
											<div class="">
												<input id="cep_entrega" type="text" class="form-control @if($errors->has('cep_entrega')) is-invalid @endif cep" name="cep_entrega" value="{{{ isset($cliente) ? $cliente->cep_entrega : old('cep_entrega') }}}">
												@if($errors->has('cep_entrega'))
												<div class="invalid-feedback">
													{{ $errors->first('cep_entrega') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-4 col-md-6 col-sm-10">
											<label class="col-form-label">Cidade</label>

											<select class="form-control select2" style="width: 100%" id="kt_select2_4" name="cidade_entrega_id">
												<option value="-">--</option>
												@foreach($cidades as $c)
												<option value="{{$c->id}}" @isset($cliente) @if($c->id == $cliente->cidade_entrega_id)selected
													@endif
													@endisset >
													{{$c->nome}} ({{$c->uf}})
												</option>
												@endforeach
											</select>
											@if($errors->has('cidade_entrega_id'))
											<div class="invalid-feedback">
												{{ $errors->first('cidade_entrega_id') }}
											</div>
											@endif
										</div>
									</div>

									<hr>
									<h5>Redes Sociais (Opcional)</h5>
									<span class="switch switch-outline switch-success">
										<label>
											<input value="true" @if(isset($cliente) && ($cliente->instagram != "" || $cliente->facebook != "")) checked @endif type="checkbox" id="inp-rede">
											<span></span>
										</label>
									</span>
									<div class="row d-none redes_sociais">
										

										<div class="form-group validated col-sm-2 col-lg-4">
											<label class="col-form-label">Instagram</label>
											<div class="">
												<input id="instagram" type="text" class="form-control @if($errors->has('instagram')) is-invalid @endif" name="instagram" value="{{{ isset($cliente) ? $cliente->instagram : old('instagram') }}}">
												
											</div>
										</div>
										<div class="form-group validated col-sm-2 col-lg-4">
											<label class="col-form-label">Facebook</label>
											<div class="">
												<input id="facebook" type="text" class="form-control @if($errors->has('facebook')) is-invalid @endif" name="facebook" value="{{{ isset($cliente) ? $cliente->facebook : old('facebook') }}}">
												
											</div>
										</div>
										<div class="form-group validated col-sm-2 col-lg-4">
											<label class="col-form-label">Linkedin</label>
											<div class="">
												<input id="linkedin" type="text" class="form-control @if($errors->has('linkedin')) is-invalid @endif" name="linkedin" value="{{{ isset($cliente) ? $cliente->linkedin : old('linkedin') }}}">
												
											</div>
										</div>

										<div class="form-group validated col-sm-2 col-lg-4">
											<label class="col-form-label">Tiktok</label>
											<div class="">
												<input id="tiktok" type="text" class="form-control @if($errors->has('tiktok')) is-invalid @endif" name="tiktok" value="{{{ isset($cliente) ? $cliente->tiktok : old('tiktok') }}}">
												
											</div>
										</div>

										<div class="form-group validated col-sm-2 col-lg-3">
											<label class="col-form-label">Whatsapp</label>
											<div class="">
												<input id="whatsapp" type="text" class="form-control @if($errors->has('whatsapp')) is-invalid @endif" name="whatsapp" value="{{{ isset($cliente) ? $cliente->whatsapp : old('whatsapp') }}}">
												
											</div>
										</div>

									</div>
								</div>

							</div>
						</div>
					</div>
				</div>

				@isset($cliente)
				<input type="hidden" id="receita" name="receita" value="{{ $cliente->receitaOtica ? json_encode($cliente->receitaOtica) : '[]' }}">
				@else
				<input type="hidden" id="receita" name="receita" value="[]">
				@endif
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
							<button style="width: 100%" type="submit" class="btn btn-success btn-salvar-cadastro">
								<i class="la la-check"></i>
								<span class="">Salvar</span>
							</button>
						</div>

					</div>
				</div>
			</form>
		</div>
	</div>
</div>
</div>

<div class="modal fade" id="modal-foto" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Tirar Foto</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-12">

						<video style="width: 100%; height: 400px;" id="video"></video>
						<canvas style="display: none" id='canvas'></canvas>

					</div>
					<div class="col-12">
						<button class="btn btn-info" type="button" id='capture'>
							<i class="la la-camera"></i>
							Capturar
						</button>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>

			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-otica" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Cadastro de receita ótica</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-lg-6 bg-light-success">
						<div class="row">

							<h5 class="col-12 mt-2">Longe olho direito</h5>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Esf.</label>
								<input type="text" value="" id="esf_od_longe" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Cil.</label>
								<input type="text" value="" id="cil_od_longe" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Eixo</label>
								<input type="text" value="" id="eixo_od_longe" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">DNP</label>
								<input type="text" value="" id="dnp_od_longe" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">DP</label>
								<input type="text" value="" id="dp_od_longe" class="form-control">
							</div>
						</div>
					</div>

					<div class="col-lg-6 bg-light-primary">
						<div class="row">

							<h5 class="col-12 mt-2">Longe olho esquerdo</h5>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Esf.</label>
								<input type="text" value="" id="esf_oe_longe" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Cil.</label>
								<input type="text" value="" id="cli_oe_longe" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Eixo</label>
								<input type="text" value="" id="eixo_oe_longe" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">DNP</label>
								<input type="text" value="" id="dnp_oe_longe" class="form-control">
							</div>
							
						</div>
					</div>
				</div>

				<div class="row mt-2">
					<div class="col-lg-6 bg-light-success">
						<div class="row">

							<h5 class="col-12 mt-2">Perto olho direito</h5>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Esf.</label>
								<input type="text" value="" id="esf_od_perto" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Cil.</label>
								<input type="text" value="" id="cil_od_perto" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Eixo</label>
								<input type="text" value="" id="eixo_od_perto" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Adição</label>
								<input type="text" value="" id="adicao_od_perto" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Altura</label>
								<input type="text" value="" id="altura_od_perto" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">DNP</label>
								<input type="text" value="" id="dnp_od_perto" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">DP</label>
								<input type="text" value="" id="dp_od_perto" class="form-control">
							</div>
						</div>
					</div>

					<div class="col-lg-6 bg-light-primary">
						<div class="row">

							<h5 class="col-12 mt-2">Perto olho esquerdo</h5>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Esf.</label>
								<input type="text" value="" id="esf_oe_perto" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Cil.</label>
								<input type="text" value="" id="cil_oe_perto" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Eixo</label>
								<input type="text" value="" id="eixo_oe_perto" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Adição</label>
								<input type="text" value="" id="adicao_oe_perto" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">Altura</label>
								<input type="text" value="" id="altura_oe_perto" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-6">
								<label class="col-form-label" id="">DNP</label>
								<input type="text" value="" id="dnp_oe_perto" class="form-control">
							</div>
							
							
						</div>
					</div>
				</div>

				<div class="row mt-2">
					<div class="col-lg-12">
						<div class="row">

							<div class="form-group validated col-sm-4 col-lg-4 col-6">
								<label class="col-form-label" id="">Armação</label>
								<input type="text" value="" id="armacao" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-2">
								<label class="col-form-label" id="">Qtd. armação</label>
								<input type="text" value="" id="qtd_armacao" class="form-control qtd-p">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-2">
								<label class="col-form-label" id="">Valor armação</label>
								<input type="text" value="" id="valor_armacao" class="form-control money">
							</div>

							<div class="form-group validated col-sm-4 col-lg-4 col-6">
								<label class="col-form-label" id="">Lente</label>
								<input type="text" value="" id="lente" class="form-control">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-2">
								<label class="col-form-label" id="">Qtd. lente</label>
								<input type="text" value="" id="qtd_lente" class="form-control qtd-p">
							</div>
							<div class="form-group validated col-sm-4 col-lg-2 col-2">
								<label class="col-form-label" id="">Valor lente</label>
								<input type="text" value="" id="valor_lente" class="form-control money">
							</div>

							<div class="form-group validated col-sm-4 col-lg-4 col-6">
								<label class="col-form-label" id="">Tratamento</label>
								<input type="text" value="" id="tratamento" class="form-control">
							</div>

							<div class="form-group validated col-sm-4 col-lg-4 col-6">
								<label class="col-form-label" id="">Médico</label>
								<input type="text" value="" id="medico" class="form-control">
							</div>

							<div class="form-group validated col-sm-4 col-lg-3 col-6">
								<label class="col-form-label" id="">Tipo de lente</label>
								<input type="text" value="" id="tipo_lente" class="form-control">
							</div>

							<div class="form-group validated col-sm-4 col-lg-3 col-6">
								<label class="col-form-label" id="">Previsão de retorno (dias)</label>
								<input data-mask="0000" type="text" value="" id="previsao_retorno_dias" class="form-control">
							</div>

							<div class="form-group validated col-sm-4 col-lg-3 col-6">
								<label class="col-form-label" id="">Previsão de retorno (dias)</label>
								<input type="text" value="" id="data" class="form-control date-input">
							</div>

							<div class="form-group validated col-12">
								<label class="col-form-label" id="">Referência</label>
								<input type="text" value="" id="referencia" class="form-control">
							</div>

							<div class="form-group validated col-12">
								<label class="col-form-label" id="">Observação</label>
								<input type="text" value="" id="observacao" class="form-control">
							</div>
						</div>
					</div>
				</div>


			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="submit" id="salvarReceita" class="btn btn-light-info font-weight-bold">Salvar</button>
			</div>
		</div>
	</div>
</div>

@section('javascript')
<script type="text/javascript">
	$(function () {
		isChecked()
		// $('#modal-otica').modal('show')
		verificaReceita()
		verificaEnderecoCobranca()
		verificaEnderecoEntrega()
		verificaRedesSociais()
	});

	$('#info_endereco_cobranca').click(() => {
		verificaEnderecoCobranca()
	})

	$('#info_endereco_entrega').click(() => {
		verificaEnderecoEntrega()
	})

	$('#inp-rede').click(() => {
		verificaRedesSociais()
	})

	function verificaEnderecoCobranca(){
		let info_endereco_cobranca = $('#info_endereco_cobranca').is(':checked')
		if(info_endereco_cobranca){
			$('.endereco-cobranca').removeClass('d-none')
		}else{
			$('.endereco-cobranca').addClass('d-none')
		}
	}

	function verificaEnderecoEntrega(){
		let info_endereco_entrega = $('#info_endereco_entrega').is(':checked')
		if(info_endereco_entrega){
			$('.endereco-entrega').removeClass('d-none')
		}else{
			$('.endereco-entrega').addClass('d-none')
		}
	}

	function verificaRedesSociais(){
		let inf = $('#inp-rede').is(':checked')
		if(inf){
			$('.redes_sociais').removeClass('d-none')
		}else{
			$('.redes_sociais').addClass('d-none')
		}
	}

	function verificaReceita(){
		let receita = $('#receita').val()
		if(receita != '[]'){
			receita = JSON.parse(receita)

			$('#esf_od_longe').val(receita.esf_od_longe)
			$('#cil_od_longe').val(receita.cil_od_longe)
			$('#eixo_od_longe').val(receita.eixo_od_longe)
			$('#dnp_od_longe').val(receita.dnp_od_longe)
			$('#dp_od_longe').val(receita.dp_od_longe)
			$('#esf_oe_longe').val(receita.esf_oe_longe)
			$('#cli_oe_longe').val(receita.cli_oe_longe)
			$('#eixo_oe_longe').val(receita.eixo_oe_longe)
			$('#dnp_oe_longe').val(receita.dnp_oe_longe)
			$('#esf_od_perto').val(receita.esf_od_perto)
			$('#cil_od_perto').val(receita.cil_od_perto)
			$('#eixo_od_perto').val(receita.eixo_od_perto)
			$('#adicao_od_perto').val(receita.adicao_od_perto)
			$('#altura_od_perto').val(receita.altura_od_perto)
			$('#dnp_od_perto').val(receita.dnp_od_perto)
			$('#dp_od_perto').val(receita.dp_od_perto)
			$('#esf_oe_perto').val(receita.esf_oe_perto)
			$('#cil_oe_perto').val(receita.cil_oe_perto)
			$('#eixo_oe_perto').val(receita.eixo_oe_perto)
			$('#adicao_oe_perto').val(receita.adicao_oe_perto)
			$('#altura_oe_perto').val(receita.altura_oe_perto)
			$('#dnp_oe_perto').val(receita.dnp_oe_perto)
			$('#armacao').val(receita.armacao)
			$('#qtd_armacao').val(receita.qtd_armacao)
			$('#valor_armacao').val(receita.valor_armacao)
			$('#lente').val(receita.lente)
			$('#qtd_lente').val(receita.qtd_lente)
			$('#valor_lente').val(receita.valor_lente)
			$('#tratamento').val(receita.tratamento)
			$('#medico').val(receita.medico)
			$('#tipo_lente').val(receita.tipo_lente)
			$('#previsao_retorno_dias').val(receita.previsao_retorno_dias)
			$('#data').val(receita.data)
			$('#referencia').val(receita.referencia)
			$('#observacao').val(receita.observacao)
		}
	}

	$('#info_contador').change(() => {
		isChecked()
	})

	function isChecked(){
		let checked = $('#info_contador').is(':checked')

		if(checked){
			$('.ct').css('display', 'block')
		}else{
			$('.ct').css('display', 'none')
		}
	}

	$('#btn-foto').click(() => {
		$('#modal-foto').modal('show')
		navigator.mediaDevices.getUserMedia({video: true})
		.then(function (mediaStream) {
			const video = document.querySelector('#video');
			video.srcObject = mediaStream;
			video.play();
		})
		.catch(function (err) {
			swal("Alerta", "Não há permissões para acessar a webcam", "warning")
		})
	})

	var blobFyle = null
	document.querySelector('#capture').addEventListener('click', function (e) {
		var canvas = document.querySelector("#canvas");  
		canvas.height = video.videoHeight;
		canvas.width = video.videoWidth;
		var context = canvas.getContext('2d');
		context.drawImage(video, 0, 0)

		let dataURI = canvas.toDataURL('image/png'); 
		// console.log(dataURI)
		$('#modal-foto').modal('hide')

		$('.image-input-wrapper').css('background-image', 'url("' + dataURI + '")')
		// var input_file = document.getElementById("file");
		// // input_file.src = dataURI;
		// $('#file').val(dataURI)
		blobFyle = dataURI

		setTimeout(() => {
			var canvas = document.querySelector("#canvas");
			canvas.toBlob(function (blob) {
				console.log(blob)
				// blobFyle = blob

			}, 'image/png')
		}, 300)
	})

	document.getElementById('form-cliente').addEventListener('submit', salvarCliente);
	function salvarCliente(event){
		event.preventDefault();
		let form = document.getElementById('form-cliente');
		if(form.dataset.submitted === 'true'){
			return false;
		}
		form.dataset.submitted = 'true';
		$('.btn-salvar-cadastro').prop('disabled', true).find('span').text('Salvando...');

		let image = document.createElement('input');

		if(blobFyle != null){

			image.setAttribute('name', 'blob');
			image.setAttribute('type', 'hidden');
			image.setAttribute('value', blobFyle);
			// $('#blob').value=blobFyle

			form.appendChild(image);

		}
		setTimeout(() => {
			form.submit();
		}, 100)

	}

	$('#receita_otica').change(() => {
		$('#modal-otica').modal('show')
	})

	$('#salvarReceita').click(() => {
		let esf_od_longe = $('#esf_od_longe').val()
		let cil_od_longe = $('#cil_od_longe').val()
		let eixo_od_longe = $('#eixo_od_longe').val()
		let dnp_od_longe = $('#dnp_od_longe').val()
		let dp_od_longe = $('#dp_od_longe').val()

		let esf_oe_longe = $('#esf_oe_longe').val()
		let cli_oe_longe = $('#cli_oe_longe').val()
		let eixo_oe_longe = $('#eixo_oe_longe').val()
		let dnp_oe_longe = $('#dnp_oe_longe').val()

		let esf_od_perto = $('#esf_od_perto').val()
		let cil_od_perto = $('#cil_od_perto').val()
		let eixo_od_perto = $('#eixo_od_perto').val()
		let adicao_od_perto = $('#adicao_od_perto').val()
		let altura_od_perto = $('#altura_od_perto').val()
		let dnp_od_perto = $('#dnp_od_perto').val()
		let dp_od_perto = $('#dp_od_perto').val()

		let esf_oe_perto = $('#esf_oe_perto').val()
		let cil_oe_perto = $('#cil_oe_perto').val()
		let eixo_oe_perto = $('#eixo_oe_perto').val()
		let adicao_oe_perto = $('#adicao_oe_perto').val()
		let altura_oe_perto = $('#altura_oe_perto').val()
		let dnp_oe_perto = $('#dnp_oe_perto').val()

		let armacao = $('#armacao').val()
		let qtd_armacao = $('#qtd_armacao').val()
		let valor_armacao = $('#valor_armacao').val()
		let lente = $('#lente').val()
		let qtd_lente = $('#qtd_lente').val()
		let valor_lente = $('#valor_lente').val()

		let tratamento = $('#tratamento').val()
		let medico = $('#medico').val()
		let tipo_lente = $('#tipo_lente').val()
		let previsao_retorno_dias = $('#previsao_retorno_dias').val()
		let data = $('#data').val()

		let referencia = $('#referencia').val()
		let observacao = $('#observacao').val()

		let js = {
			esf_od_longe: esf_od_longe,
			cil_od_longe: cil_od_longe,
			eixo_od_longe: eixo_od_longe,
			dnp_od_longe: dnp_od_longe,
			dp_od_longe: dp_od_longe,
			esf_oe_longe: esf_oe_longe,
			cli_oe_longe: cli_oe_longe,
			eixo_oe_longe: eixo_oe_longe,
			dnp_oe_longe: dnp_oe_longe,
			esf_od_perto: esf_od_perto,
			cil_od_perto: cil_od_perto,
			eixo_od_perto: eixo_od_perto,
			adicao_od_perto: adicao_od_perto,
			altura_od_perto: altura_od_perto,
			dnp_od_perto: dnp_od_perto,
			dp_od_perto: dp_od_perto,
			esf_oe_perto: esf_oe_perto,
			cil_oe_perto: cil_oe_perto,
			eixo_oe_perto: eixo_oe_perto,
			adicao_oe_perto: adicao_oe_perto,
			altura_oe_perto: altura_oe_perto,
			dnp_oe_perto: dnp_oe_perto,
			armacao: armacao,
			qtd_armacao: qtd_armacao,
			valor_armacao: valor_armacao,
			lente: lente,
			qtd_lente: qtd_lente,
			valor_lente: valor_lente,
			tratamento: tratamento,
			medico: medico,
			tipo_lente: tipo_lente,
			previsao_retorno_dias: previsao_retorno_dias,
			data: data,
			referencia: referencia,
			observacao: observacao,
		}

		$('#receita').val(JSON.stringify(js))
		$('#modal-otica').modal('hide')
	})

</script>
@endsection
@endsection