@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{{ isset($item) ? '/impressoras/update': '/impressoras/save' }}}" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{{ isset($item) ? $item->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{isset($item) ? 'Editar' : 'Nova'}} Impressora</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-4">
											<label class="col-form-label">Descrição</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('descricao')) is-invalid @endif" name="descricao" value="{{{ isset($item) ? $item->descricao : old('descricao') }}}">
												@if($errors->has('descricao'))
												<div class="invalid-feedback">
													{{ $errors->first('descricao') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Porta</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('porta')) is-invalid @endif" name="porta" value="{{{ isset($item) ? $item->porta : old('porta') }}}">
												@if($errors->has('porta'))
												<div class="invalid-feedback">
													{{ $errors->first('porta') }}
												</div>
												@endif
											</div>
										</div>

										<div class="col-sm-3 col-lg-2 mt-4">
											<label>Ativo:</label>

											<div class="switch switch-outline switch-success">
												<label class="">
													<input @if(isset($item->status) && $item->status) checked @endisset value="true" name="status" class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
											</div>
										</div>

										<div class="col-sm-3 col-lg-2 mt-4">
											<label>Padrão:</label>

											<div class="switch switch-outline switch-info">
												<label class="">
													<input @if(isset($item->padrao) && $item->padrao) checked @endisset value="true" name="padrao" class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
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
								<a style="width: 100%" class="btn btn-danger" href="/impressoras">
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