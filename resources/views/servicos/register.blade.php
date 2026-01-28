@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{{ isset($servico) ? '/servicos/update': '/servicos/save' }}}" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{{ isset($servico) ? $servico->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{isset($servico) ? 'Editar' : 'Novo'}} Serviço</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-5">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($servico) ? $servico->nome : old('nome') }}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Valor</label>
											<div class="">
												<input type="text" id="valor" class="form-control @if($errors->has('valor')) is-invalid @endif" name="valor" value="{{{ isset($servico) ? moeda($servico->valor) : old('valor') }}}">
												@if($errors->has('valor'))
												<div class="invalid-feedback">
													{{ $errors->first('valor') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Tempo de serviço(min)</label>
											<div class="">
												<input type="text" id="tempo_servico" class="form-control @if($errors->has('tempo_servico')) is-invalid @endif" name="tempo_servico" value="{{{ isset($servico) ? $servico->tempo_servico : old('tempo_servico') }}}">
												@if($errors->has('tempo_servico'))
												<div class="invalid-feedback">
													{{ $errors->first('tempo_servico') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Comissão(opcional)</label>
											<div class="">
												<input type="text" id="comissao" class="form-control @if($errors->has('comissao')) is-invalid @endif money" name="comissao" value="{{{ isset($servico) ? $servico->comissao : old('comissao') }}}">
												@if($errors->has('comissao'))
												<div class="invalid-feedback">
													{{ $errors->first('comissao') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-6 col-sm-10">
											<label class="col-form-label">Categoria</label>

											<select class="custom-select form-control" name="categoria_id">
												@foreach($categorias as $cat)
												<option value="{{$cat->id}}" @isset($produto) @if($cat->id == $servico->categoria_id)
													selected=""
													@endif
													@endisset >{{$cat->nome}}
												</option>
												@endforeach
											</select>

										</div>

										<div class="form-group validated col-lg-2 col-md-6 col-sm-10">
											<label class="col-form-label">Unidade de cobrança</label>

											<select class="custom-select form-control" name="unidade_cobranca">
												<option @isset($servico) @if($servico->unidade_cobranca == 'UN') selected @endif @endisset  value="UN">UN</option>
												<option @isset($servico) @if($servico->unidade_cobranca == 'HR') selected @endif @endisset  value="HR">HR</option>
												<option @isset($servico) @if($servico->unidade_cobranca == 'MIN') selected @endif @endisset  value="MIN">MIN</option>
											</select>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Tempo adicional(min)</label>
											<div class="">
												<input type="text" id="tempo_adicional" class="form-control @if($errors->has('tempo_adicional')) is-invalid @endif" name="tempo_adicional" value="{{{ isset($servico) ? $servico->tempo_adicional : old('tempo_adicional') }}}">
												@if($errors->has('tempo_adicional'))
												<div class="invalid-feedback">
													{{ $errors->first('tempo_adicional') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Valor adicional</label>
											<div class="">
												<input type="text" id="valor_adicional" class="form-control @if($errors->has('valor_adicional')) is-invalid @endif money" name="valor_adicional" value="{{{ isset($servico) ? $servico->valor_adicional : old('valor_adicional') }}}">
												@if($errors->has('valor_adicional'))
												<div class="invalid-feedback">
													{{ $errors->first('valor_adicional') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Tolerância</label>
											<div class="">
												<input type="text" id="tempo_tolerancia" class="form-control @if($errors->has('tempo_tolerancia')) is-invalid @endif money" name="tempo_tolerancia" value="{{{ isset($servico) ? $servico->tempo_tolerancia : old('tempo_tolerancia') }}}">
												@if($errors->has('tempo_tolerancia'))
												<div class="invalid-feedback">
													{{ $errors->first('tempo_tolerancia') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Código do serviço</label>
											<div class="">
												<input type="text" id="codigo_servico" class="form-control @if($errors->has('codigo_servico')) is-invalid @endif" name="codigo_servico" value="{{{ isset($servico) ? $servico->codigo_servico : old('codigo_servico') }}}">
												@if($errors->has('codigo_servico'))
												<div class="invalid-feedback">
													{{ $errors->first('codigo_servico') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									<div class="card">

										<div class="card-body">

											<h4>Tributação</h4>

											<div class="row">
												<div class="form-group validated col-sm-6 col-lg-2">
													<label class="col-form-label">%ISS</label>
													<div class="">
														<input type="text" id="aliquota_iss" class="form-control @if($errors->has('aliquota_iss')) is-invalid @endif" data-mask="000,00" data-mask-reverse="true" name="aliquota_iss" value="{{{ isset($servico) ? $servico->aliquota_iss : old('aliquota_iss') }}}">
														@if($errors->has('aliquota_iss'))
														<div class="invalid-feedback">
															{{ $errors->first('aliquota_iss') }}
														</div>
														@endif
													</div>
												</div>
												<div class="form-group validated col-sm-6 col-lg-2">
													<label class="col-form-label">%PIS</label>
													<div class="">
														<input type="text" id="aliquota_pis" class="form-control @if($errors->has('aliquota_pis')) is-invalid @endif" data-mask="000,00" data-mask-reverse="true" name="aliquota_pis" value="{{{ isset($servico) ? $servico->aliquota_pis : old('aliquota_pis') }}}">
														@if($errors->has('aliquota_pis'))
														<div class="invalid-feedback">
															{{ $errors->first('aliquota_pis') }}
														</div>
														@endif
													</div>
												</div>
												<div class="form-group validated col-sm-6 col-lg-2">
													<label class="col-form-label">%COFINS</label>
													<div class="">
														<input type="text" id="aliquota_cofins" class="form-control @if($errors->has('aliquota_cofins')) is-invalid @endif" data-mask="000,00" data-mask-reverse="true" name="aliquota_cofins" value="{{{ isset($servico) ? $servico->aliquota_cofins : old('aliquota_cofins') }}}">
														@if($errors->has('aliquota_cofins'))
														<div class="invalid-feedback">
															{{ $errors->first('aliquota_cofins') }}
														</div>
														@endif
													</div>
												</div>
												<div class="form-group validated col-sm-6 col-lg-2">
													<label class="col-form-label">%INSS</label>
													<div class="">
														<input type="text" id="aliquota_inss" class="form-control @if($errors->has('aliquota_inss')) is-invalid @endif" data-mask="000,00" data-mask-reverse="true" name="aliquota_inss" value="{{{ isset($servico) ? $servico->aliquota_inss : old('aliquota_inss') }}}">
														@if($errors->has('aliquota_inss'))
														<div class="invalid-feedback">
															{{ $errors->first('aliquota_inss') }}
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
					</div>
					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">
							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/categoriasConta">
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

@endsection