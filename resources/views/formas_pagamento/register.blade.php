@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" @isset($forma) action="/formasPagamento/update" @else action="/formasPagamento/save" @endif>
					<input type="hidden" name="id" value="{{{ isset($forma) ? $forma->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ isset($forma) ? "Editar": "Cadastrar" }}} forma de pagamento</h3>
						</div>
					</div>
					@csrf

					@if(!$podeEditar)
					<p class="text-info">*Esta é uma forma de pagamento padrão, por isso não é possível editar nome e prazo, para que ela não apareça na caixa de seleção da venda desative!</p>
					@endif
					
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-5 col-lg-3">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input @if(!$podeEditar) disabled="disabled" @endif id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($forma) ? $forma->nome : old('nome') }}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label">Tipo da Taxa</label>
											<div class="">
												<select @if(!$podeEditar) readonly @endif name="tipo_taxa" class="form-control @if($errors->has('tipo_taxa')) is-invalid @endif">
													<option value="perc">Percentual</option>
													<option value="valor">Valor</option>
												</select>
												@if($errors->has('tipo_taxa'))
												<div class="invalid-feedback">
													{{ $errors->first('tipo_taxa') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label">Taxa</label>
											<div class="">
												<input id="taxa" type="text" class="form-control @if($errors->has('taxa')) is-invalid @endif money" name="taxa" value="{{{ isset($forma) ? $forma->taxa : old('taxa') }}}">
												@if($errors->has('taxa'))
												<div class="invalid-feedback">
													{{ $errors->first('taxa') }}
												</div>
												@endif
											</div>
										</div>

										<input type="hidden" value="{{$podeEditar}}" name="podeEditar">

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label">Prazo dias</label>
											<div class="">
												<input @if(!$podeEditar) disabled="disabled" @endif data-mask="000" id="prazo_dias" type="text" class="form-control @if($errors->has('prazo_dias')) is-invalid @endif" name="prazo_dias" value="{{{ isset($forma) ? $forma->prazo_dias : old('prazo_dias') }}}">
												@if($errors->has('prazo_dias'))
												<div class="invalid-feedback">
													{{ $errors->first('prazo_dias') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label text-left col-lg-9 col-sm-9">Status</label>
											
											<div class="col-6">
												<span class="switch switch-outline switch-info">
													<label>
														<input id="status" @isset($forma) @if($forma->status) checked @endif @else @if(old('status')) checked @endif @endif
														name="status" type="checkbox" >
														<span></span>
													</label>
												</span>
											</div>
										</div>

										<div class="form-group validated col-12">
											<label class="col-form-label">Informações adicionais (opcional)</label>
											<div class="">
												<input id="infos" type="text" class="form-control @if($errors->has('infos')) is-invalid @endif" name="infos" value="{{{ isset($forma) ? $forma->infos : old('infos') }}}">
												@if($errors->has('infos'))
												<div class="invalid-feedback">
													{{ $errors->first('infos') }}
												</div>
												@endif
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
								<a style="width: 100%" class="btn btn-danger" href="/formasPagamento">
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