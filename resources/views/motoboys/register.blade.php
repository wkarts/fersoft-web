@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{{ isset($item) ? '/motoboys/update': '/motoboys/store' }}}" enctype="multipart/form-data">

					<input type="hidden" name="id" value="{{{ isset($item) ? $item->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{isset($item) ? 'Editar' : 'Novo'}} Motoboy</h3>
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
											<input type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($item) ? $item->nome : old('nome') }}}">
											@if($errors->has('nome'))
											<div class="invalid-feedback">
												{{ $errors->first('nome') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Celular</label>
											<input type="text" class="form-control @if($errors->has('celular')) is-invalid @endif celular" name="celular" value="{{{ isset($item) ? $item->celular : old('celular') }}}">
											@if($errors->has('celular'))
											<div class="invalid-feedback">
												{{ $errors->first('celular') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-4">
											<label class="col-form-label">Rua</label>
											<input type="text" class="form-control @if($errors->has('rua')) is-invalid @endif" name="rua" value="{{{ isset($item) ? $item->rua : old('rua') }}}">
											@if($errors->has('rua'))
											<div class="invalid-feedback">
												{{ $errors->first('rua') }}
											</div>
											@endif
										</div>
										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Nº</label>
											<input type="text" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{{ isset($item) ? $item->numero : old('numero') }}}">
											@if($errors->has('numero'))
											<div class="invalid-feedback">
												{{ $errors->first('numero') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Bairro</label>
											<input type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{{ isset($item) ? $item->bairro : old('bairro') }}}">
											@if($errors->has('bairro'))
											<div class="invalid-feedback">
												{{ $errors->first('bairro') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Valor entrega</label>
											<input type="tel" class="form-control @if($errors->has('valor_entrega_padrao')) is-invalid @endif money" name="valor_entrega_padrao" value="{{{ isset($item) ? $item->valor_entrega_padrao : old('valor_entrega_padrao') }}}">
											@if($errors->has('valor_entrega_padrao'))
											<div class="invalid-feedback">
												{{ $errors->first('valor_entrega_padrao') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Ativo</label>
											<select class="form-control" name="status">
												<option @isset($item) @if($item->status == 1) selected @endif @endif value="1">Sim</option>
												<option @isset($item) @if($item->status == 0) selected @endif @endif value="0">Não</option>
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
								<a style="width: 100%" class="btn btn-danger" href="/motoboys">
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