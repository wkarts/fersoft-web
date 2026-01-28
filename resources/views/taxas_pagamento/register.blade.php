@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" @isset($item) action="/taxas-pagamento/{{ $item->id }}" @else action="/taxas-pagamento" @endif>
					@csrf
					@isset($item)
					@method('put')
					@endif

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ isset($item) ? "Editar": "Cadastrar" }}} taxa de pagamento</h3>
						</div>
					</div>
					
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										
										<div class="form-group validated col-12 col-lg-4">
											<label class="col-form-label">Tipo</label>
											<select class="custom-select" name="tipo_pagamento" required>
												<option value="">Selecione</option>
												@foreach(\App\Models\TaxaPagamento::tiposPagamento() as $key => $t)
												<option @isset($item) @if($item->tipo_pagamento == $key) selected @endif @endif value="{{ $key }}">[{{ $key }}] {{ $t }}</option>
												@endforeach
											</select>
											@if($errors->has('tipo_pagamento'))
											<div class="invalid-feedback">
												{{ $errors->first('tipo_pagamento') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label">Taxa</label>
											<input required id="taxa" type="text" class="form-control @if($errors->has('taxa')) is-invalid @endif money" name="taxa" value="{{{ isset($item) ? moeda($item->taxa) : old('taxa') }}}">
											@if($errors->has('taxa'))
											<div class="invalid-feedback">
												{{ $errors->first('taxa') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-12 col-lg-4">
											<label class="col-form-label">Bandeira do cartão</label>
											<select class="custom-select" name="bandeira_cartao">
												<option value="">Selecione</option>
												@foreach(\App\Models\TaxaPagamento::bandeiras() as $key => $t)
												<option @isset($item) @if($item->bandeira_cartao == $key) selected @endif @endif value="{{ $key }}">{{ $t }}</option>
												@endforeach
											</select>
											@if($errors->has('bandeira_cartao'))
											<div class="invalid-feedback">
												{{ $errors->first('bandeira_cartao') }}
											</div>
											@endif
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2"></div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/taxas-pagamento">
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