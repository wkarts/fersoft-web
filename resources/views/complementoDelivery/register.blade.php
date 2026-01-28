@extends('default.layout')
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<!--begin::Portlet-->

				<form method="post" action="{{{ isset($complemento) ? '/deliveryComplemento/update': '/deliveryComplemento/save' }}}">
					<input type="hidden" name="id" value="{{{ isset($complemento->id) ? $complemento->id : 0 }}}">

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{{ isset($complemento) ? "Editar": "Cadastrar" }}} Adicional</h3>
						</div>

					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-4">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input required type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($complemento) ? $complemento->nome : old('nome') }}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2">
											<label class="col-form-label">Valor</label>
											<div class="">
												<input required type="text" class="form-control @if($errors->has('valor')) is-invalid @endif money" name="valor" value="{{{ isset($complemento) ? $complemento->valor : old('valor') }}}">
												@if($errors->has('valor'))
												<div class="invalid-feedback">
													{{ $errors->first('valor') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">Tipo</label>
											<select class="form-control" name="tipo">
												<option value="normal">Normal</option>
												<option @isset($complemento) @if($complemento->tipo == 'borda') selected @endif @endif value="borda">Borda Pizza</option>
											</select>
										</div>

										<div class="form-group validated col-lg-12">
											<label class="col-form-label">Categoria de produto</label>
											<select required id="categoria" class="form-control select2-custom" name="categoria[]" multiple>
												@foreach($categorias as $c)
												<option @isset($complemento) @if(in_array($c->id, $categoria_edit)) selected @endif @endif value="{{$c->id}}">{{$c->nome}}</option>
												@endforeach
											</select>

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
								<a style="width: 100%" class="btn btn-danger" href="/deliveryComplemento">
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
<script type="text/javascript">
	setTimeout(() => {
		$('.select2-selection__choice__remove').css('display', 'none')
	}, 100)

	$('#categoria').change(() => {
		setTimeout(() => {
			$('.select2-selection__choice__remove').css('display', 'none')
		}, 10)
	})
</script>
@endsection