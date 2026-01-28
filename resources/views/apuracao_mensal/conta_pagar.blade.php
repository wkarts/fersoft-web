@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{ route('apuracaoMensal.setConta', [$item->id])}}" enctype="multipart/form-data" id="form-register">
					@method('put')
					<input type="hidden" name="id" value="{{{ isset($conta) ? $conta->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{{ isset($conta) ? "Editar": "Cadastrar" }}} Conta a Pagar</h3>
						</div>

					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Referência</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('referencia')) is-invalid @endif" name="referencia" value="#pag_{{ $item->id }}">
												@if($errors->has('referencia'))
												<div class="invalid-feedback">
													{{ $errors->first('referencia') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-3 col-md-4 col-sm-6">
											<label class="col-form-label">Categoria</label>

											<select class="custom-select form-control" id="categoria_id" name="categoria_id">
												@foreach($categorias as $cat)
												<option value="{{$cat->id}}" @isset($conta)
													@if($cat->id == $conta->categoria_id)
													selected
													@endif
													@endisset >{{$cat->nome}}
												</option>
												@endforeach
											</select>

										</div>

										<div class="form-group col-lg-2 col-md-9 col-sm-12">
											<label class="col-form-label">Data de vencimento</label>
											<div class="">
												<div class="input-group date">
													<input type="text" name="vencimento" class="form-control @if($errors->has('vencimento')) is-invalid @endif" readonly value="{{{ isset($conta) ? \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y') : old('vencimento') }}}" id="kt_datepicker_3" />
													<div class="input-group-append">
														<span class="input-group-text">
															<i class="la la-calendar"></i>
														</span>
													</div>
													@if($errors->has('vencimento'))
													<div class="invalid-feedback">
														{{ $errors->first('vencimento') }}
													</div>
													@endif
												</div>
												

											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">Valor</label>

											<input id="valor" type="text" class="form-control @if($errors->has('valor')) is-invalid @endif money" name="valor" value="{{ number_format($item->valor_final, $casasDecimais, ',', '.') }}">
											@if($errors->has('valor'))
											<div class="invalid-feedback">
												{{ $errors->first('valor') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">Nº nota fiscal</label>

											<input id="numero_nota_fiscal" type="text" class="form-control @if($errors->has('numero_nota_fiscal')) is-invalid @endif" name="numero_nota_fiscal" value="{{{ isset($conta) ? $conta->numero_nota_fiscal : old('numero_nota_fiscal') }}}">
											@if($errors->has('numero_nota_fiscal'))
											<div class="invalid-feedback">
												{{ $errors->first('numero_nota_fiscal') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-12 col-lg-3">
											<label class="col-form-label" id="">Tipo de Pagamento</label>
											<select class="custom-select form-control @if($errors->has('tipo_pagamento')) is-invalid @endif" id="forma" name="tipo_pagamento">
												<option value="">Selecione o tipo de pagamento</option>
												@foreach(App\Models\ContaPagar::tiposPagamento() as $c)
												<option @if($item->forma_pagamento == $c) selected @endif  value="{{$c}}">{{$c}}</option>
												@endforeach
											</select>
											@if($errors->has('tipo_pagamento'))
											<div class="invalid-feedback">
												{{ $errors->first('tipo_pagamento') }}
											</div>
											@endif
										</div>

										@if(!isset($conta))
										<div class="form-group col-lg-2 col-md-9 col-sm-12">
											<label class="col-form-label">Conta Paga</label>
											
											<div class="col-lg-12 col-xl-12">
												<span class="switch switch-outline switch-success">
													<label>
														<input @if(isset($conta) && $conta->status) checked 
														@endif type="checkbox" id="pago" name="status" type="checkbox" id="status">
														<span></span>
													</label>
												</span>

											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-6 div-pago" style="display: none">
											<label class="col-form-label">Valor pago</label>

											<input id="valor_pago" type="text" class="form-control @if($errors->has('valor_pago')) is-invalid @endif money" name="valor_pago" value="{{{ isset($conta) ? number_format($conta->valor_integral, $casasDecimais, ',', '.') : old('valor_pago') }}}">
											@if($errors->has('valor_pago'))
											<div class="invalid-feedback">
												{{ $errors->first('valor_pago') }}
											</div>
											@endif
										</div>
										@endif
									</div>

								</div>
								
							</div>

						</div>
					</div>
				</div>
				<input type="hidden" name="parcelas" id="parcelas">
			</div>
			<div class="card-footer">

				<div class="row">
					<div class="col-xl-2">

					</div>
					<div class="col-lg-3 col-sm-6 col-md-4">
						<a style="width: 100%" class="btn btn-danger" href="/apuracaoMensal">
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
@section('javascript')

@endsection
