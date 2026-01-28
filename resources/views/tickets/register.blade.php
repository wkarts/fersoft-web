@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/tickets/save" enctype="multipart/form-data">

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Novo Ticket</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-4">
											<label class="col-form-label">Departamento</label>
											<div class="">
												<select class="form-control custom-select @if($errors->has('assunto')) is-invalid @endif" name="departamento">
													<option value="">Selecione o departamento</option>
													@foreach(App\Models\Ticket::departamentos() as $d)
													<option @if(old('departamento') == $d) selected @endif value="{{$d}}">{{$d}}</option>
													@endforeach
												</select>
												@if($errors->has('departamento'))
												<div class="invalid-feedback">
													{{ $errors->first('departamento') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-8">
											<label class="col-form-label">Assunto</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('assunto')) is-invalid @endif" name="assunto" value="{{old('assunto')}}">
												@if($errors->has('assunto'))
												<div class="invalid-feedback">
													{{ $errors->first('assunto') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-12">
											<label class="col-form-label">Mensagem</label>
											<div class="">

												<div class="row">
													<div class="col-12">
														<textarea name="mensagem" id="mensagem" style="width: 100%;height:500px;">{{old('mensagem')}}</textarea>
													</div>
												</div>

												@if($errors->has('mensagem'))
												<div class="invalid-feedback">
													{{ $errors->first('mensagem') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									<div class="form-group row">
										<div class="col-lg-6">
											<div class="uppy" id="kt_uppy_5">
												<div class="uppy-wrapper"><div class="uppy-Root uppy-FileInput-container"><input class="uppy-FileInput-input uppy-input-control" accept=".jpg,.png,.jpeg" style="" type="file" name="file" multiple="" id="kt_uppy_5_input_control"><label class="uppy-input-label btn btn-light-primary btn-sm btn-bold" for="kt_uppy_5_input_control">Upload de Imagem</label></div></div>
												<div class="uppy-list"></div>
												<div class="uppy-status"><div class="uppy-Root uppy-StatusBar is-waiting" aria-hidden="true" dir="ltr"><div class="uppy-StatusBar-progress
													" style="width: 0%;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div><div class="uppy-StatusBar-actions"></div></div></div>
													<div class="uppy-informer uppy-informer-min"><div class="uppy uppy-Informer" aria-hidden="true"><p role="alert"> </p></div></div>
												</div>
												
												<span class="form-text text-muted">Extensões permitidas .jpg, .png</span>
												<label class="text-success" id="filename"></label>
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
									<a style="width: 100%" class="btn btn-danger" href="/tickets">
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