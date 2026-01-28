@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">

				<form method="post" action="/empresas/update">

					<input type="hidden" name="id" value="{{$empresa->id}}">
					<br>
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							@if($empresa->tipo_contador == 1)
							<h3 class="card-title">Dados do Contador</h3>
							@else
							<h3 class="card-title">Dados da Empresa</h3>
							@endif
						</div>
					</div>
					@csrf

					<div class="row">

						@if($empresa->representante)
						<div class="col-12">
							<h3>Representante: <strong class="text-info">{{ $empresa->representante->representante->nome }}</strong></h3>
						</div>
						@endif
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-10 col-lg-6">
											<label class="col-form-label">Nome/Razão Social</label>
											<div class="">
												<input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{$empresa->nome}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-10 col-lg-6">
											<label class="col-form-label">Nome Fantasia</label>
											<div class="">
												<input id="nome_fantasia" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif" name="nome_fantasia" value="{{$empresa->nome_fantasia}}">
												@if($errors->has('nome_fantasia'))
												<div class="invalid-feedback">
													{{ $errors->first('nome_fantasia') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-5">
											<label class="col-form-label" id="lbl_ie_rg">Rua</label>
											<div class="">
												<input type="text" id="rua" class="form-control @if($errors->has('rua')) is-invalid @endif" name="rua" value="{{$empresa->rua}}">
												@if($errors->has('rua'))
												<div class="invalid-feedback">
													{{ $errors->first('rua') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label" id="lbl_ie_rg">Nº</label>
											<div class="">
												<input type="text" id="numero" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{$empresa->numero}}">
												@if($errors->has('numero'))
												<div class="invalid-feedback">
													{{ $errors->first('numero') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Bairro</label>
											<div class="">
												<input type="text" id="bairro" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{$empresa->bairro}}">
												@if($errors->has('bairro'))
												<div class="invalid-feedback">
													{{ $errors->first('bairro') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Cidade</label>
											<div class="">
												<input type="text" id="cidade" class="form-control @if($errors->has('cidade')) is-invalid @endif" name="cidade" value="{{$empresa->cidade}}">
												@if($errors->has('cidade'))
												<div class="invalid-feedback">
													{{ $errors->first('cidade') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
											<label class="col-form-label">UF</label>
											<select class="custom-select form-control" id="uf2" name="uf">
												<option value="">--</option>
												@foreach(App\Models\Cidade::estados() as $e)
												<option value="{{$e}}" @if($empresa->uf == $e) selected @endif>
													{{$e}}
												</option>
												@endforeach
											</select>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">
												CPF/CNPJ
											</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('cnpj')) is-invalid @endif cpf_cnpj" name="cnpj" value="{{$empresa->cnpj}}">
												@if($errors->has('cnpj'))
												<div class="invalid-feedback">
													{{ $errors->first('cnpj') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-4">
											<label class="col-form-label" id="lbl_ie_rg">Email</label>
											<div class="">
												<input type="text" id="email" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{$empresa->email}}">
												@if($errors->has('email'))
												<div class="invalid-feedback">
													{{ $errors->first('email') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2">
											<label class="col-form-label" id="lbl_ie_rg">CEP</label>
											<div class="">
												<input type="text" id="cep" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{ $empresa->cep }}">
												@if($errors->has('cep'))
												<div class="invalid-feedback">
													{{ $errors->first('cep') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Telefone</label>
											<div class="">
												<input type="text" id="telefone" class="form-control @if($errors->has('telefone')) is-invalid @endif" name="telefone" value="{{$empresa->telefone}}">
												@if($errors->has('telefone'))
												<div class="invalid-feedback">
													{{ $errors->first('telefone') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-4">
											<label class="col-form-label">Representante legal</label>
											<div class="">
												<input type="text" id="representante_legal" class="form-control @if($errors->has('representante_legal')) is-invalid @endif" name="representante_legal" value="{{ $empresa->representante_legal }}">

											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">CPF Representante legal</label>
											<div class="">
												<input type="text" id="cpf_representante_legal" class="form-control @if($errors->has('cpf_representante_legal')) is-invalid @endif cpfp" name="cpf_representante_legal" value="{{ $empresa->cpf_representante_legal }}">

											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label text-left col-lg-12 col-sm-12">Status</label>
											<div class="col-6">
												<span class="switch switch-outline switch-primary">
													<label>
														<input value="true" @if($empresa->status) checked @endisset type="checkbox" name="status" id="status">
														<span></span>
													</label>
												</span>
											</div>
										</div>

										<!-- <div class="form-group validated col-sm-6 col-lg-5">
											<label class="col-form-label" id="lbl_ie_rg">Informação do contador</label>
											<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Campo opcional de informação do contador nome, contato, email, etc..."><i class="la la-info"></i></button>
											<div class="">
												<input type="text" id="info_contador" class="form-control @if($errors->has('info_contador')) is-invalid @endif" name="info_contador" value="{{$empresa->info_contador}}">
												@if($errors->has('info_contador'))
												<div class="invalid-feedback">
													{{ $errors->first('info_contador') }}
												</div>
												@endif
											</div>
										</div> -->

										@if($empresa->tipo_contador == 1)

										<div class="form-group validated col-lg-4 col-md-5 col-sm-10">
											<label class="col-form-label text-left">Representante (opcional)</label>
											<select class="form-control select2" id="kt_select2_1" name="representante_id">
												<option value="">Selecione o representante</option>
												@foreach($representantes as $c)
												<option value="{{$c->id}}" @if($contador != null) @if($c->id == $contador->representante_id) selected @endif @endisset
													@if(old('representante_id') == $c->id)
													selected
													@endif
													>
													{{$c->nome}} {{$c->cpf_cnpj}}
												</option>
												@endforeach
											</select>
											@if($errors->has('representante_id'))
											<div class="invalid-feedback">
												{{ $errors->first('representante_id') }}
											</div>
											@endif
										</div>

										@else
										<div class="form-group validated col-lg-4 col-md-5 col-sm-10">
											<label class="col-form-label text-left">Contador (opcional)</label>
											<select class="form-control select2" id="kt_select2_1" name="contador_id">
												<option value="">Selecione o contador</option>
												@foreach($contadores as $c)
												<option value="{{$c->id}}" @isset($empresa) @if($c->id == $empresa->contador_id) selected @endif @endisset
													@if(old('contador_id') == $c->id)
													selected
													@endif
													>
													{{$c->razao_social}} {{$c->cnpj}}
												</option>
												@endforeach
											</select>
											@if($errors->has('contador_id'))
											<div class="invalid-feedback">
												{{ $errors->first('contador_id') }}
											</div>
											@endif
										</div>
										@endif

										<div class="row">
											<div class="form-group validated col-sm-12">

												<label class="col-3 col-form-label">Permissão de Acesso:</label>

												@if(sizeof($perfis) > 0)
												<div class="form-group validated col-sm-4 col-lg-4">
													<label class="col-form-label" id="lbl_ie_rg">Perfil</label>
													<div class="">
														<select id="perfil-select" class="custom-select" name="perfil_id">
															<option value="0">--</option>
															@foreach($perfis as $p)
															<option
															@if($empresa->perfil_id == $p->id)
															selected
															@endif
															value="{{$p}}">
															{{$p->nome}}
														</option>
														@endforeach
													</select>
												</div>
											</div>
											@endif

											<input type="hidden" id="menus" value="{{json_encode($menu)}}" name="">
											@foreach($menu as $m)
											<div class="col-12 col-form-label">
												<span>
													<label class="checkbox checkbox-info">
														<input id="todos_{{str_replace(' ', '_', $m['titulo'])}}" onclick="marcarTudo('{{$m['titulo']}}')" type="checkbox">
														<span></span><strong class="text-info" style="margin-left: 5px; font-size: 16px;">{{$m['titulo']}} </strong>
													</label>
												</span>
												<div class="checkbox-inline" style="margin-top: 10px;">
													@foreach($m['subs'] as $s)

													@if($s['nome'] != 'NFS-e')

													@php
													$link = str_replace('/', '', $s['rota']);
													$link = str_replace('.', '_', $link);
													$link = str_replace(':', '_', $link);

													@endphp
													<!-- <label class="checkbox checkbox-info check-sub">
														<input id="sub_{{$link}}" @if(in_array($s['rota'], $permissoesAtivas)) checked @endif type="checkbox" name="{{$s['rota']}}">
														<span></span>{{$s['nome']}}

													</label> -->

													<label class="checkbox checkbox-info check-sub">
														<input id="sub_{{$link}}" @if(\App\Models\Empresa::validaLink($s['rota'], $permissoesAtivas)) checked @endif type="checkbox" name="{{$s['rota']}}">
														<span></span>{{$s['nome']}}

													</label>

													@endif
													@endforeach
												</div>

											</div>
											@endforeach
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
							<a style="width: 100%" class="btn btn-danger" href="/empresas">
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
			</form>
		</div>

	</div>
</div>
</div>

<div class=" d-flex flex-column flex-column-fluid" id="kt_content" style="margin-top: -20px;">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12 mt-4">

				<div class="row">
					<div class="col-xl-12">
						@if($empresa->tipo_contador == 0)
						<div class="row">
							<div class="col-sm-4 col-lg-4 col-md-4 col-12">
								<div class="card card-custom gutter-b">
									<div class="card-header">
										<h3 class="card-title">
											Total de Cadastros
										</h3>
									</div>
									<div class="card-body" style="height: 200px;">

										<h4>Clientes: <strong class="text-info">{{sizeof($empresa->clientes)}}</strong></h4>
										<h4>Fornecedores: <strong class="text-info">{{sizeof($empresa->fornecedores)}}</strong></h4>
										<h4>Produtos: <strong class="text-info">{{ $empresa->countProdutos() }}</strong></h4>
										<h4>Usuários: <strong class="text-info">{{sizeof($empresa->usuarios)}}</strong></h4>
										<h4>Veiculos: <strong class="text-info">{{sizeof($empresa->veiculos)}}</strong></h4>
									</div>
								</div>
							</div>
							<div class="col-sm-4 col-lg-4 col-md-4 col-12">

								<div class="card card-custom gutter-b">
									<div class="card-header">
										<h3 class="card-title">
											Total de Documentos
										</h3>
									</div>
									<div class="card-body" style="height: 200px;">

										<h4>NFe: <strong class="text-info">{{$empresa->nfes()}}</strong></h4>
										<h4>NFCe: <strong class="text-info">{{$empresa->nfces()}}</strong></h4>
										<h4>CTe: <strong class="text-info">{{$empresa->ctes()}}</strong></h4>
										<h4>MDFe: <strong class="text-info">{{$empresa->mdfes()}}</strong></h4>
									</div>
								</div>
							</div>

							<div class="col-sm-4 col-lg-4 col-md-4 col-12">
								<div class="card card-custom gutter-b">
									<div class="card-header">
										<h3 class="card-title">
											Registros
										</h3>
									</div>
									<div class="card-body" style="height: 200px;">

										<h4>Vendas: <strong class="text-info">{{sizeof($empresa->vendas)}}</strong></h4>
										<h4>Vendas PDV: <strong class="text-info">{{sizeof($empresa->vendasCaixa)}}</strong></h4>
									</div>
								</div>
							</div>
						</div>
						@endif
						<div class="row">
							<div class="form-group validated col-12 col-lg-4">

								<a href="/empresas/alterarSenha/{{$empresa->id}}" class="btn btn-danger">
									<i class="la la-key"></i> Alterar Senhas de usuários
								</a>
							</div>
							<div class="col-12 col-lg-4">
								<h3 class="text-info">Data de cadastro: {{ \Carbon\Carbon::parse($empresa->created_at)->format('d/m/Y H:i:s')}}</h3>
							</div>
							@if($empresa->ultimoLogin($empresa->id))
							<div class="col-12 col-lg-4">
								<h3 class="text-info">Último login realizado:
									{{ \Carbon\Carbon::parse($empresa->ultimoLogin($empresa->id)->created_at)->format('d/m/Y H:i')}}</h3>
								</div>
								@endif
							</div>

							<div class="row">
								<div class="col-sm-12 col-lg-12">
									<h3 class="">Plano atual:

										@if(!$empresa->planoEmpresa)
										<a href="/empresas/setarPlano/{{$empresa->id}}" class="btn btn-info">
											Atribuir plano
										</a>
										@else
										<span class="text-info">{{$empresa->planoEmpresa->plano->nome}} R$ {{number_format($empresa->planoEmpresa->getValor(), 2, ',', '.')}}</span>
										- Data de expiração: <span class="@if($planoExpirado) text-danger @else text-info @endif">
											@if($empresa->planoEmpresa->expiracao != '0000-00-00')
											{{ \Carbon\Carbon::parse($empresa->planoEmpresa->expiracao)->format('d/m/Y')}}
											@else
											Indeterminado
											@endif
										</span>
										@endif

										@if($planoExpirado)
										<a target="" href="/empresas/setarPlano/{{$empresa->id}}" class="btn btn-danger">
											Atribuir plano
										</a>
										@else
										@if($empresa->planoEmpresa)
										<a target="" href="/empresas/setarPlano/{{$empresa->id}}" class="btn btn-warning">
											Alterar plano
										</a>
										@endif
										@endif
									</h3>
								</div>
							</div>

							<div class="row">
								<div class="col-sm-12 col-lg-12">
									<h3 class="">Contrato:

										@if($empresa->contrato == null)
										<a href="/contrato/gerarContrato/{{$empresa->id}}" class="btn btn-warning">
											Gerar contrato
										</a>
										@else

										@if($empresa->contrato->cpf_cnpj != $empresa->cnpj)
										<a href="/contrato/gerarContrato/{{$empresa->id}}" class="btn btn-warning">
											Gerar contrato
										</a>
										@endif

										@if($empresa->contrato->status == 0)
										<span class="label label-xl label-inline label-light-danger">Não assinado</span>

										<a href="/contrato/gerarContrato/{{$empresa->id}}" class="btn btn-warning">
											Gerar contrato novamente
										</a>
										<a href="/contrato/imprimir/{{$empresa->id}}" class="btn btn-sm btn-info">
											<i class="la la-print"></i>
											Imprimir prévia
										</a>
										@else

										<span class="label label-xl label-inline label-light-success">
											<i class="la la-check"></i> Assinado
										</span>

										<a href="/contrato/download/{{$empresa->id}}" class="btn btn-sm btn-info">
											<i class="la la-print"></i>
											Baixar Contrato
										</a>
										@endif

										@if($empresa->contrato->cpf_cnpj != $empresa->cnpj)
										<p class="text-danger">O documento CPF/CNPJ da empresa este diferente d contrato, favor gerar novamente!</p>
										@endif

										@endif
									</h3>
								</div>
							</div>

							@if(!$empresa->configNota)
							<p class="text-danger">>>Esta empresa não possui os dados do emitente cadastrados</p>
							@endif
							<div class="row">
								<div class="col-sm-12">
									<a class="btn btn-info" href="/empresas/download/{{$empresa->id}}">
										<i class="la la-download"></i>
										Download certificado
									</a>

									@if($empresa->configNota)
									<a class="btn btn-primary" href="/empresas/arquivosXml/{{$empresa->id}}">
										<i class="la la-file-code"></i>
										Arquivos Xml
									</a>
									@endif
									@if($empresa->tipo_contador == 0)
									<a class="btn btn-warning" href="/empresas/configEmitente/{{$empresa->id}}">
										<i class="la la-id-card"></i>
										Configuração do emitente
									</a>
									@endif

									@if($empresa->planoEmpresa && $empresa->tipo_contador == 0)
									<a class="btn btn-success" onclick='swal("Atenção!", "Deseja realizar o login nesta empresa, a sua sessão irá expirar?", "warning").then((sim) => {if(sim){ location.href="/empresas/login/{{$empresa->id}}" }else{return false} })' href="#!">
										<i class="la la-check"></i>
										Fazer login na empresa
									</a>
									@endif
								</div>
							</div>
							<br>

							@if(env("MIGRADOR"))
							<div class="row">
								<div class="col-sm-4 col-lg-4">

									<a style="width: 100%;" href="/migrador/{{$empresa->id}}" class="btn btn-info">
										Migrador
									</a>
								</div>
							</div>
							@endif

							<br>

							<div class="row">
								<h4>Lista de pagamentos</h4>
								<table class="table">
									<thead>
										<tr>
											<th>Data</th>
											<th>Plano</th>
											<th>Forma de pagamento</th>
											<th>Valor</th>
										</tr>
									</thead>
									<tbody>
										@if($empresa->pagamentos)
										@foreach($empresa->pagamentos as $p)
										<tr>
											<td>{{ __date($p->created_at) }}</td>
											<td>{{ $p->plano->plano->nome }}</td>
											<td>{{ $p->forma_pagamento }}</td>
											<td>{{ moeda($p->valor) }}</td>
										</tr>
										@endforeach
										@endif
									</tbody>
									@if($empresa->pagamentos)
									<tfoot>
										<tr>
											<td colspan="3">Soma</td>
											<td>{{ moeda($empresa->pagamentos->sum('valor')) }}</td>
										</tr>
									</tfoot>
									@endif

								</table>
							</div>

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	@endsection
