@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" @isset($inventario) action="/inventario/update" @else action="/inventario/save" @endif>

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Novo Inventário
							</h3>
						</div>
					</div>
					@csrf

					<input type="hidden" name="id" value="{{{ isset($inventario) ? $inventario->id : 0 }}}">
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group col-lg-4 col-md-6 col-sm-12">
											<label class="col-form-label">Referência</label>
											<div class="">
												<div class="input-group">
													<input type="text" name="referencia" class="form-control @if($errors->has('referencia')) is-invalid @endif" value="{{{ isset($inventario) ? $inventario->referencia : old('referencia') }}}"/>
													@if($errors->has('referencia'))
													<div class="invalid-feedback">
														{{ $errors->first('referencia') }}
													</div>
													@endif
												</div>
											</div>
										</div>

										<div class="form-group col-lg-2 col-md-6 col-sm-12">
											<label class="col-form-label">Data de Início</label>
											<div class="">
												<div class="input-group date">
													<input type="text" name="inicio" class="form-control @if($errors->has('inicio')) is-invalid @endif" readonly id="kt_datepicker_3" value="{{{ isset($inventario) ? \Carbon\Carbon::parse($inventario->inicio)->format('d/m/Y') : old('cpf_cnpj') }}}"/>
													<div class="input-group-append">
														<span class="input-group-text">
															<i class="la la-calendar"></i>
														</span>
													</div>
													@if($errors->has('inicio'))
													<div class="invalid-feedback">
														{{ $errors->first('inicio') }}
													</div>
													@endif
												</div>
											</div>
										</div>

										<div class="form-group col-lg-2 col-md-6 col-sm-12">
											<label class="col-form-label">Data de Término</label>
											<div class="">
												<div class="input-group date">
													<input type="text" name="fim" class="form-control @if($errors->has('fim')) is-invalid @endif" @ readonly id="kt_datepicker_3" value="{{{ isset($inventario) ? \Carbon\Carbon::parse($inventario->fim)->format('d/m/Y') : old('fim') }}}"/>
													<div class="input-group-append">
														<span class="input-group-text">
															<i class="la la-calendar"></i>
														</span>
													</div>
													@if($errors->has('fim'))
													<div class="invalid-feedback">
														{{ $errors->first('fim') }}
													</div>
													@endif
												</div>

											</div>
										</div>

										<div class="form-group col-lg-2 col-md-6 col-sm-12">
											<label class="col-form-label">Tipo</label>
											<div class="">
												<select class="custom-select @if($errors->has('tipo')) is-invalid @endif" name="tipo">
													<option value="">--</option>
													@foreach(App\Models\Inventario::tipos() as $t)
													<option @isset($inventario) @if($inventario->tipo == $t) selected @endif @else @if(old('tipo') == $t) selected @endif @endif value="{{$t}}">{{$t}}</option>
													@endforeach
												</select>
												@if($errors->has('tipo'))
												<div class="invalid-feedback">
													{{ $errors->first('tipo') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group col-lg-8 col-md-6 col-sm-12">
											<label class="col-form-label">Observação</label>
											<div class="">
												<div class="input-group date">
													<input type="text" name="observacao" class="form-control @if($errors->has('observacao')) is-invalid @endif" value="{{{ isset($inventario) ? $inventario->observacao : old('observacao') }}}"/>

												</div>
												@if($errors->has('observacao'))
												<div class="invalid-feedback">
													{{ $errors->first('observacao') }}
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
							<a style="width: 100%" class="btn btn-danger" href="/inventario">
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