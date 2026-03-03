@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/funcionarios/{{{ isset($funcionario) ? 'update' : 'save' }}}">
					<input type="hidden" name="id" value="{{{ isset($funcionario) ? $funcionario->id : 0 }}}">
                    <div class="row align-items-center">
                        <div class="col-lg-2 col-md-7 col-sm-12">
                            <div class="card card-custom gutter-b example example-compact">
                                <div class="card-header text-center">
                                    <h3 class="card-title">{{ isset($funcionario) ? 'Editar' : 'Novo' }} Funcionário</h3>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-10 col-md-5 col-sm-12">
                            <x-image-upload
                                :image-url="isset($funcionario->foto_funcionario) ? '/imgs_funcionarios/'.$funcionario->foto_funcionario : '/imgs/no_image.png'"
                                title="Foto Funcionário"
                                input-name="file"
                            />
                        </div>

                    </div>
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">


									<div class="row">
										<div class="form-group validated col-sm-10 col-lg-6">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($funcionario) ? $funcionario->nome : old('nome') }}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-3 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">CPF</label>
											<div class="">
												<input type="text" id="cpf" class="form-control @if($errors->has('cpf')) is-invalid @endif" name="cpf" value="{{{ isset($funcionario) ? $funcionario->cpf : old('cpf') }}}">
												@if($errors->has('cpf'))
												<div class="invalid-feedback">
													{{ $errors->first('cpf') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">RG</label>
											<div class="">
												<input type="text" id="rg" class="form-control @if($errors->has('rg')) is-invalid @endif" name="rg" value="{{{ isset($funcionario) ? $funcionario->rg : old('rg') }}}">
												@if($errors->has('rg'))
												<div class="invalid-feedback">
													{{ $errors->first('rg') }}
												</div>
												@endif
											</div>
										</div>
									</div>

                                    <div class="row">
                                        <div class="form-group col-lg-2 col-md-6 col-sm-12">
                                            <label class="col-form-label">Data de Nascimento</label>
                                            <div class="input-group date">
                                                <input type="text" name="data_nascimento" class="form-control" readonly
                                                       value="{{ isset($funcionario->data_nascimento) ? \Carbon\Carbon::parse($funcionario->data_nascimento)->format('d/m/Y') : old('data_nascimento') }}" id="kt_datepicker_3" />
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><i class="la la-calendar"></i></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-2 col-md-6 col-sm-12">
                                            <label class="col-form-label">Data de Admissão</label>
                                            <div class="input-group date">
                                                <input type="text" name="data_admissao" class="form-control" readonly
                                                       value="{{ isset($funcionario->data_admissao) ? \Carbon\Carbon::parse($funcionario->data_admissao)->format('d/m/Y') : old('data_admissao') }}" id="kt_datepicker_3" />
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><i class="la la-calendar"></i></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-2 col-md-6 col-sm-12">
                                            <label class="col-form-label">Tipo Sanguíneo</label>
                                            <select class="custom-select" name="tipo_sanguineo">
                                                <option value="">Selecione</option>
                                                @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $tipo)
                                                    <option value="{{ $tipo }}" {{ isset($funcionario) && $funcionario->tipo_sanguineo == $tipo ? 'selected' : '' }}>
                                                        {{ $tipo }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6 col-sm-12">
                                            <label class="col-form-label">Status do Funcionário</label>
                                            <select class="custom-select" name="status_funcionario">
                                                <option value="Ativo" {{ isset($funcionario) && $funcionario->status_funcionario == 'Ativo' ? 'selected' : '' }}>Ativo</option>
                                                <option value="Desligado" {{ isset($funcionario) && $funcionario->status_funcionario == 'Desligado' ? 'selected' : '' }}>Desligado</option>
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-12 col-sm-12">
                                            <label class="col-form-label">Número de Registro</label>
                                            <input type="text" name="numero_registro" class="form-control"
                                                   value="{{ isset($funcionario->numero_registro) ? $funcionario->numero_registro : old('numero_registro') }}">
                                        </div>



                                        <div class="form-group col-lg-3 col-md-6 col-sm-12">
                                            <label class="col-form-label">Matrícula (Ponto/AFD)</label>
                                            <input type="text" name="matricula" class="form-control"
                                                   value="{{ isset($funcionario->matricula) ? $funcionario->matricula : old('matricula') }}"
                                                   placeholder="Código de vínculo com AFD">
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6 col-sm-12">
                                            <label class="col-form-label">PIS (somente números)</label>
                                            <input type="text" name="pis" class="form-control"
                                                   value="{{ isset($funcionario->pis) ? $funcionario->pis : old('pis') }}">
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6 col-sm-12">
                                            <label class="col-form-label">Código no Relógio</label>
                                            <input type="text" name="codigo_relogio" class="form-control"
                                                   value="{{ isset($funcionario->codigo_relogio) ? $funcionario->codigo_relogio : old('codigo_relogio') }}">
                                        </div>

                                        <div class="form-group col-lg-12">
                                            <label class="col-form-label">Observação do Ponto</label>
                                            <textarea class="form-control" name="observacao_ponto">{{ isset($funcionario->observacao_ponto) ? $funcionario->observacao_ponto : old('observacao_ponto') }}</textarea>
                                        </div>

                                        <div class="form-group col-lg-12">
                                            <label class="col-form-label">Observações</label>
                                            <textarea class="form-control" name="observacoes">{{ isset($funcionario->observacoes) ? $funcionario->observacoes : old('observacoes') }}</textarea>
                                        </div>

                                    </div>


									<hr>

									<div class="row">
										<div class="form-group validated col-sm-8 col-lg-4">
											<label class="col-form-label">Rua</label>
											<div class="">
												<input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif" name="rua" value="{{{ isset($funcionario) ? $funcionario->rua : old('rua') }}}">
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
												<input id="numero" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{{ isset($funcionario) ? $funcionario->numero : old('numero') }}}">
												@if($errors->has('numero'))
												<div class="invalid-feedback">
													{{ $errors->first('numero') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-3">
											<label class="col-form-label">Bairro</label>
											<div class="">
												<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{{ isset($funcionario) ? $funcionario->bairro : old('bairro') }}}">
												@if($errors->has('bairro'))
												<div class="invalid-feedback">
													{{ $errors->first('bairro') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-8 col-lg-3">
											<label class="col-form-label">Email</label>
											<div class="">
												<input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($funcionario) ? $funcionario->email : old('email') }}}">
												@if($errors->has('email'))
												<div class="invalid-feedback">
													{{ $errors->first('email') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									<div class="row">
										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">Telefone</label>
											<div class="">
												<input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif" name="telefone" value="{{{ isset($funcionario) ? $funcionario->telefone : old('telefone') }}}">
												@if($errors->has('telefone'))
												<div class="invalid-feedback">
													{{ $errors->first('telefone') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">Celular</label>
											<div class="">
												<input id="celular" type="text" class="form-control @if($errors->has('celular')) is-invalid @endif" name="celular" value="{{{ isset($funcionario) ? $funcionario->celular : old('celular') }}}">
												@if($errors->has('celular'))
												<div class="invalid-feedback">
													{{ $errors->first('celular') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group col-lg-2 col-md-9 col-sm-12">
											<label class="col-form-label">Data de Registro</label>
											<div class="">
												<div class="input-group date">
													<input type="text" name="data_registro" class="form-control @if($errors->has('data_registro')) is-invalid @endif" readonly value="{{{ isset($funcionario->data_registro) ? \Carbon\Carbon::parse($funcionario->data_registro)->format('d/m/Y') : old('data_registro') }}}" id="kt_datepicker_3" />
													<div class="input-group-append">
														<span class="input-group-text">
															<i class="la la-calendar"></i>
														</span>
													</div>
												</div>
												@if($errors->has('data_registro'))
												<div class="invalid-feedback">
													{{ $errors->first('data_registro') }}
												</div>
												@endif

											</div>
										</div>
										<!-- @if(!isset($funcionario) || $funcionario->usuario_id == NULL)
										<div class="form-group validated col-sm-8 col-lg-5">
											<label class="col-form-label">Usuario (opcional)</label>
											<div class="">
												<select class="form-control custom-select" name="usuario_id">
													<option value="NULL">--</option>

													@foreach($usuarios as $u)
													<option
													@if(isset($funcionario))
													@if($funcionario->usuario_id == $u->id)
													selected
													@endif
													@endif
													value="{{$u->id}}">{{$u->nome}}</option>
													@endforeach
												</select>
											</div>
										</div>
										@else
										<div class="form-group validated col-sm-8 col-lg-5">
											<label class="col-form-label">Usuario:
												<strong class="text-info">{{$funcionario->usuario->nome}}</strong>
											</label>
										</div>
										@endif -->


										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">Usuario (opcional)</label>
											<div class="">
												<select class="form-control custom-select" name="usuario_id">
													<option value="NULL">--</option>

													@foreach($usuarios as $u)
													<option
													@if(isset($funcionario))
													@if($funcionario->usuario_id == $u->id)
													selected
													@endif
													@endif
													value="{{$u->id}}">{{$u->nome}}</option>
													@endforeach
												</select>
											</div>
										</div>


										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">Salário</label>
											<div class="">
												<input id="salario" type="text" class="form-control @if($errors->has('salario')) is-invalid @endif money" name="salario" value="{{{ isset($funcionario) ? $funcionario->salario : old('salario') }}}">
												@if($errors->has('salario'))
												<div class="invalid-feedback">
													{{ $errors->first('salario') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-2">
											<label class="col-form-label">Percentual de comissão</label>
											<div class="">
												<input id="percentual_comissao" type="text" class="form-control @if($errors->has('percentual_comissao')) is-invalid @endif money" name="percentual_comissao" value="{{{ isset($funcionario) ? $funcionario->percentual_comissao : old('percentual_comissao') }}}">
												@if($errors->has('percentual_comissao'))
												<div class="invalid-feedback">
													{{ $errors->first('percentual_comissao') }}
												</div>
												@endif
											</div>
										</div>
                                    </div>

                                    <div class="card card-custom gutter-b example example-compact" style="max-height: 30px; margin: 0 auto; display: flex; justify-content: center;">
                                        <div class="card-header" >
                                            <h3 class="card-title">
                                                {{isset($funcionario) ? 'Editar' : 'Novo'}} Motorista
                                            </h3>
                                        </div>
                                    </div>

                                    <br>
                                    <label class="col-form-label"> * Gestão de Frota (Obrigatório) </label>

                                    <div class="row">

                                        <div class="form-group validated col-sm-9 col-lg-3">
                                            <label class="col-form-label">CNH</label>
                                            <div class="">
                                                <input id="cnh" type="text" class="form-control @if($errors->has('cnh')) is-invalid @endif" name="cnh" value="{{{ isset($funcionario) ? $funcionario->cnh : old('cnh') }}}">
                                                @if($errors->has('cnh'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('cnh') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-9 col-sm-12">
                                            <label class="col-form-label">Categoria da CNH</label>
                                            <div class="">
                                                <select class="custom-select form-control @if($errors->has('categoria_cnh')) is-invalid @endif" name="categoria_cnh">
                                                    <option value="">-- Selecione --</option>
                                                    <option value="A" @if(isset($funcionario) && $funcionario->categoria_cnh == 'A') selected @endif>A</option>
                                                    <option value="B" @if(isset($funcionario) && $funcionario->categoria_cnh == 'B') selected @endif>B</option>
                                                    <option value="C" @if(isset($funcionario) && $funcionario->categoria_cnh == 'C') selected @endif>C</option>
                                                    <option value="D" @if(isset($funcionario) && $funcionario->categoria_cnh == 'D') selected @endif>D</option>
                                                    <option value="E" @if(isset($funcionario) && $funcionario->categoria_cnh == 'E') selected @endif>E</option>
                                                </select>
                                                @if($errors->has('categoria_cnh'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('categoria_cnh') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-9 col-sm-12">
                                            <label class="col-form-label">Data de Vencimento da CNH</label>
                                            <div class="">
                                                <div class="input-group date">
                                                    <input type="text" name="vencimento_cnh"
                                                           class="form-control @if($errors->has('vencimento_cnh')) is-invalid @endif"
                                                           readonly
                                                           value="{{ isset($funcionario->vencimento_cnh) ? \Carbon\Carbon::parse($funcionario->vencimento_cnh)->format('d/m/Y') : old('vencimento_cnh') }}"
                                                           id="kt_datepicker_3" />
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">
                                                            <i class="la la-calendar"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                                @if($errors->has('vencimento_cnh'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('vencimento_cnh') }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>


                                        <div class="form-group col-lg-3 col-md-9 col-sm-12">
                                            <label class="col-form-label">Status do Motorista</label>
                                            <div class="">
                                                <select class="custom-select form-control @if($errors->has('status_motorista')) is-invalid @endif" name="status_motorista">
                                                    <option value="Ativo" @if(isset($funcionario) && $funcionario->status_motorista == 'Ativo') selected @endif>Ativo</option>
                                                    <option value="Inativo" @if(isset($funcionario) && $funcionario->status_motorista == 'Inativo') selected @endif>Inativo</option>
                                                </select>
                                                @if($errors->has('status_motorista'))
                                                    <div class="invalid-feedback">
                                                        {{ $errors->first('status_motorista') }}
                                                    </div>
                                                @endif
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
							<a style="width: 100%" class="btn btn-danger" href="/funcionarios">
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

<!-- Inclua o modal fora do form para evitar problemas com iframes -->
<x-image-modal
    :image-url="isset($funcionario->foto_funcionario) ? '/imgs_funcionarios/'.$funcionario->foto_funcionario : '/imgs/no_image.png'"
    title="Foto Funcionário"
/>

@endsection
