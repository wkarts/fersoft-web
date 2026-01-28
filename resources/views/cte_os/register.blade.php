@extends('default.layout')
@section('content')

<style type="text/css">
	.btn-file {
		position: relative;
		overflow: hidden;
	}

	.btn-file input[type=file] {
		position: absolute;
		top: 0;
		right: 0;
		min-width: 100%;
		min-height: 100%;
		font-size: 100px;
		text-align: right;
		filter: alpha(opacity=0);
		opacity: 0;
		outline: none;
		background: white;
		cursor: inherit;
		display: block;
	}
</style>
<div class="card card-custom gutter-b">

	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content" >

			<div class="row" id="anime" style="display: none">
				<div class="col s8 offset-s2">
					<lottie-player src="/anime/success.json" background="transparent" speed="0.8" style="width: 100%; height: 300px;" autoplay >
					</lottie-player>
				</div>
			</div>

			<div class="col-lg-12" id="content">

				@if(!isset($cte))
				<h1 class="text-success">EMISSÃO DE CTe Os</h1>
				@else
				<h1 class="text-success">EDITAR CTe Os</h1>
				@endif

				<input type="hidden" id="cte_id" value="{{{ isset($cte) ? $cte->id : 0}}}" name="">

				<h3 class="card-title">DADOS INICIAIS</h3>

				<input type="hidden" id="clientes" value="{{json_encode($clientes)}}" name="">
				<input type="hidden" id="_token" value="{{csrf_token()}}" name="">
				<div class="row">
					<div class="col-xl-12">

						<div class="kt-section kt-section--first">
							<div class="kt-section__body">

								<div class="row">
									<div class="col-lg-4 col-md-4 col-sm-6">

										<h6>Ultima CTe: <strong>{{$lastCte}}</strong></h6>
									</div>
									<div class="col-lg-4 col-md-4 col-sm-6">

										@if($config->ambiente == 2)
										<h6>Ambiente: <strong class="text-primary">Homologação</strong></h6>
										@else
										<h6>Ambiente: <strong class="text-success">Produção</strong></h6>
										@endif
									</div>
								</div>

								<div class="row">
									<div class="form-group col-lg-4 col-md-4 col-sm-6">
										<label class="col-form-label">Natureza de Operação</label>
										<div class="">

											<div class="input-group date">
												<select class="custom-select form-control" id="natureza" name="natureza">
													@foreach($naturezas as $n)
													<option 
													@if($config->nat_op_padrao == $n->id)
													selected
													@endif

													@if(isset($cte))
													@if($n->id == $cte->natureza_id)
													selected
													@endif
													@endif
													value="{{$n->id}}">{{$n->natureza}}</option>
													@endforeach
												</select>
											</div>
										</div>
									</div>

									<div class="form-group validated col-sm-3 col-lg-3 col-12">
										<label class="col-form-label">CST</label>
										<select class="custom-select form-control" id="cst" name="cst">
											@foreach(App\Models\Cte::getCsts() as $key => $c)
											<option @if(isset($cte)) @if($key == $cte->cst) selected @endif @endif value="{{$key}}">{{$c}}</option>
											@endforeach
										</select>
									</div>

									<div class="form-group col-sm-3 col-lg-2 col-12">
										<label class="col-form-label">%ICMS</label>
										<div class="">
											<div class="input-group">
												<input type="text" name="perc_icms" class="form-control type-ref money-p" value="@if(isset($cte)) {{$cte->perc_icms}} @else 0 @endif" id="perc_icms"/>
											</div>
										</div>
									</div>
									
								</div>

								<div class="row">
									<div class="form-group validated col-sm-6 col-lg-6 col-12">
										
										<label class="col-form-label" id="">Emitente</label>
										<div class="input-group">
											<div class="input-group-prepend w-100">
												<select class="form-control select2" style="width: 100%" id="kt_select2_1" name="cliente">
													<option value="null">Selecione o Emitente</option>
													@foreach($clientes as $c)
													<option @if(isset($cte)) @if($cte->emitente_id == $c->id) selected @endif @endif value="{{$c->id}}">{{$c->razao_social}} ({{$c->cpf_cnpj}})</option>
													@endforeach
												</select>
												<button type="button" onclick="novoEmitente()" class="btn btn-success btn-sm">
													<i class="la la-plus-circle icon-add"></i>
												</button>
											</div>
										</div>

										<hr>
										<div class="row" id="info-emitente" style="display: none">
											<div class="col-xl-12">

												<div class="card card-custom gutter-b">
													<div class="card-body">

														<h4 class="center-align">EMITENTE SELECIONADO</h4>
														<h6>Razao Social: <strong id="nome-emitente" class="text-info"></strong></h6>

														<h6>CNPJ: <strong id="cnpj-emitente" class="text-info"></strong>
														</h6>

														<h6>IE: <strong id="ie-emitente" class="text-info"></strong>
														</h6>

														<h6>Rua: <strong id="rua-emitente" class="text-info"></strong>
														</h6>
														<h6>Nro: <strong id="nro-emitente" class="text-info"></strong>
														</h6>
														<h6>Bairro: <strong id="bairro-emitente" class="text-info"></strong>
														</h6>
														<h6>Cidade: <strong id="cidade-emitente" class="text-info"></strong>
														</h6>
													</div>

												</div>
											</div>

										</div>
									</div>

									<div class="form-group validated col-sm-6 col-lg-6 col-12">
										<label class="col-form-label" id="">Tomador</label>
										<div class="input-group">
											<div class="input-group-prepend w-100">
												<select class="form-control select2" style="width: 100%" id="kt_select2_2" name="cliente">
													<option value="null">Selecione o Tomador</option>
													@foreach($clientes as $c)
													<option @if(isset($cte)) @if($cte->tomador_id == $c->id) selected @endif @endif value="{{$c->id}}">{{$c->razao_social}} ({{$c->cpf_cnpj}})</option>
													@endforeach
												</select>
												<button type="button" onclick="novoTomador()" class="btn btn-info btn-sm">
													<i class="la la-plus-circle icon-add"></i>
												</button>
											</div>
										</div>
										<hr>
										<div class="row" id="info-tomador" style="display: none">
											<div class="col-xl-12">

												<div class="card card-custom gutter-b">
													<div class="card-body">

														<h4 class="center-align">TOMADOR SELECIONADO</h4>

														<h6>Razao Social: <strong id="nome-tomador" class="text-danger"></strong></h6>

														<h6>CNPJ: <strong id="cnpj-tomador" class="text-danger"></strong>
														</h6>

														<h6>IE: <strong id="ie-tomador" class="text-danger"></strong>
														</h6>

														<h6>Rua: <strong id="rua-tomador" class="text-danger"></strong>
														</h6>
														<h6>Nro: <strong id="nro-tomador" class="text-danger"></strong>
														</h6>
														<h6>Bairro: <strong id="bairro-tomador" class="text-danger"></strong>
														</h6>
														<h6>Cidade: <strong id="cidade-tomador" class="text-danger"></strong>
														</h6>
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

					<div class="card-body">

						<div class="row">
							<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

								<h1>Informações da Carga</h1>

								<div class="row">

									<div class="form-group validated col-sm-3 col-lg-3 col-12">
										<label class="col-form-label" id="">Veiculo</label>
										<select class="custom-select form-control" id="veiculo_id" name="veiculo_id">
											<option value="">--</option>
											@foreach($veiculos as $v)
											<option @isset($cte) @if($v->id == $cte->veiculo_id) selected @endif @endisset value="{{$v->id}}">{{$v->modelo}} {{$v->placa}}</option>
											@endforeach
										</select>
									</div>

									<div class="form-group validated col-sm-3 col-lg-2 col-12">
										<label class="col-form-label" id="">Tomador</label>
										<select class="custom-select form-control" id="tomador" name="tomador">
											@foreach($tiposTomador as $key => $t)
											<option @isset($cte) @if($cte->tomador == $key) selected @endif @endisset value="{{$key}}">{{$key ."-".$t}}</option>
											@endforeach
										</select>
									</div>

									<div class="form-group col-sm-3 col-lg-2 col-12">
										<label class="col-form-label">Valor de transporte</label>
										<div class="">
											<div class="input-group">
												<input type="text" name="valor_transporte" class="form-control type-ref" value="@isset($cte) {{$cte->valor_transporte}} @endisset" id="valor_transporte"/>
											</div>
										</div>
									</div>
									<div class="form-group col-sm-3 col-lg-2 col-12">
										<label class="col-form-label">Valor à receber</label>
										<div class="">
											<div class="input-group">
												<input type="text" name="valor_receber" class="form-control type-ref money" value="@isset($cte) {{$cte->valor_receber}} @endisset" id="valor_receber"/>
											</div>
										</div>
									</div>
									<div class="form-group validated col-sm-3 col-lg-3 col-12">
										<label class="col-form-label" id="">Modelo de transporte</label>
										<select class="custom-select form-control" id="modal-transp" name="modal-transp">
											@foreach($modals as $key => $t)
											<option value="{{$key}}">{{$key ."-".$t}}</option>
											@endforeach
										</select>
									</div>

								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="card card-custom gutter-b">

					<div class="card-body">

						<div class="row">
							<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

								<h1>Informações da Entrega</h1>

								<div class="row">
									<div class="form-group col-sm-4 col-lg-4 col-12">

										<label class="col-form-label" id="">Municipio de envio</label><br>
										<select class="form-control select2 select-mun" style="width: 100%" id="kt_select2_5">
											<option value="null">Selecione a cidade</option>
											@foreach($cidades as $c)
											<option @isset($cte) @if($c->id == $cte->municipio_envio) selected @endif @endisset value="{{$c->id}}">{{$c->nome}} ({{$c->uf}})</option>
											@endforeach
										</select>
									</div>

									<div class="form-group col-sm-4 col-lg-4 col-12">

										<label class="col-form-label" id="">Municipio de Inicio</label><br>
										<select class="form-control select2 select-mun" style="width: 100%" id="kt_select2_8" >
											<option value="null">Selecione a cidade</option>
											@foreach($cidades as $c)
											<option @isset($cte) @if($c->id == $cte->municipio_inicio) selected @endif @endisset value="{{$c->id}}">{{$c->nome}} ({{$c->uf}})</option>
											@endforeach
										</select>
									</div>
									<div class="form-group col-sm-4 col-lg-4 col-12">

										<label class="col-form-label" id="">Municipio final</label><br>
										<select class="form-control select2 select-mun" style="width: 100%" id="kt_select2_7">
											<option value="null">Selecione a cidade</option>
											@foreach($cidades as $c)
											<option @isset($cte) @if($c->id == $cte->municipio_fim) selected @endif @endisset value="{{$c->id}}">{{$c->nome}} ({{$c->uf}})</option>
											@endforeach
										</select>
									</div>
								</div>

								<div class="row">

									<div class="form-group col-sm-8 col-lg-6 col-12">
										<label class="col-form-label">Descrição serviço</label>
										<div class="">
											<div class="input-group">
												<input type="text" name="descricao_servico" class="form-control type-ref" value="{{ isset($cte) ? $cte->descricao_servico : ''}}" id="descricao_servico"/>
											</div>
										</div>
									</div>

									<div class="form-group col-sm-3 col-lg-2 col-12">
										<label class="col-form-label">Qtd. carga</label>
										<div class="">
											<div class="input-group">
												<input type="text" name="quantidade_carga" class="form-control type-ref" value="@isset($cte) {{$cte->quantidade_carga}} @endisset" id="quantidade_carga" data-mask="000000,0000" data-mask-reverse="true"/>
											</div>
										</div>
									</div>

									<div class="form-group col-sm-3 col-lg-2 col-12">
										<label class="col-form-label">Data de viagem</label>
										<div class="">
											<div class="input-group">
												<input type="text" name="data_viagem" class="form-control type-ref" value="@isset($cte) {{$cte->data_viagem}} @endisset" id="data_viagem" data-mask="00/00/0000" data-mask-reverse="true"/>
											</div>
										</div>
									</div>

									<div class="form-group col-sm-3 col-lg-2 col-12">
										<label class="col-form-label">Horário de viagem</label>
										<div class="">
											<div class="input-group">
												<input type="text" name="horario_viagem" class="form-control type-ref" value="@isset($cte) {{$cte->horario_viagem}} @endisset" id="horario_viagem" data-mask="00:00" data-mask-reverse="true"/>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>


				<div class="row align-items-center">
					<div class="form-group col-sm-6 col-lg-8 col-12">
						<label class="col-form-label">Informação Adicional</label>
						<div class="">
							<div class="input-group">
								<input type="text" name="obs" class="form-control type-ref" value="{{ isset($cte) ? $cte->observacao : ''}}" id="obs"/>
							</div>
						</div>
					</div>
					<div class="col-sm-1"></div>
					<div class="col-sm-3 col-lg-3 col-md-3 col-xl-3 col-12">
						<a id="finalizar" style="width: 100%; margin-top: 15px;" href="#" onclick="salvarCTe()" class="btn btn-success disabled">Salvar</a>
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
									<input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif" name="cpf_cnpj">
									
								</div>
							</div>
							<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<label class="col-form-label text-left col-lg-12 col-sm-12">UF</label>

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
							<div class="form-group validated col-sm-10 col-lg-10">
								<label class="col-form-label">Razao Social/Nome</label>
								<div class="">
									<input id="razao_social2" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif">
									
								</div>
							</div>

							<div class="form-group validated col-sm-10 col-lg-10">
								<label class="col-form-label">Nome Fantasia</label>
								<div class="">
									<input id="nome_fantasia2" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif">
								</div>
							</div>

							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label" id="lbl_ie_rg">RG</label>
								<div class="">
									<input type="text" id="ie_rg" class="form-control @if($errors->has('ie_rg')) is-invalid @endif">
								</div>
							</div>
							<div class="form-group validated col-lg-3 col-md-3 col-sm-10">
								<label class="col-form-label text-left col-lg-12 col-sm-12">Consumidor Final</label>

								<select class="custom-select form-control" id="consumidor_final">
									<option value="1">SIM</option>
									<option value="0">NAO</option>
								</select>

							</div>

							<div class="form-group validated col-lg-3 col-md-3 col-sm-10">
								<label class="col-form-label text-left col-lg-12 col-sm-12">Contribuinte</label>

								<select class="custom-select form-control" id="contribuinte">

									<option value="1">SIM</option>
									<option value="0">NAO</option>
								</select>
							</div>

							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label" id="lbl_ie_rg">Limite de Venda</label>
								<div class="">
									<input type="text" id="limite_venda" class="form-control @if($errors->has('limite_venda')) is-invalid @endif money"  value="0">
									
								</div>
							</div>

						</div>
						<hr>
						<h5>Endereço de Faturamento</h5>
						<div class="row">
							<div class="form-group validated col-sm-8 col-lg-8">
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

							<div class="form-group validated col-sm-8 col-lg-5">
								<label class="col-form-label">Bairro</label>
								<div class="">
									<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif">
									
								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">CEP</label>
								<div class="">
									<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif">
									
								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-4">
								<label class="col-form-label">Email</label>
								<div class="">
									<input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif">
									
								</div>
							</div>

							@php
							$cidade = App\Models\Cidade::getCidadeCod($config->codMun);
							@endphp
							<div class="form-group validated col-lg-6 col-md-6 col-sm-10">
								<label class="col-form-label">Cidade</label><br>
								<select style="width: 100%" class="form-control select2" id="kt_select2_9">
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
				<input type="hidden" value="" id="remetente_destinatario" name="">
			</div>
			<div class="modal-footer">
				<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
				<button type="button" onclick="salvarCliente()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript" src="/js/cte_os.js"></script>
@endsection
