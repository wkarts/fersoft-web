@extends('default.layout')
@section('content')

<style type="text/css">
	.img-template img{
		width: 300px;
		border: 1px solid #999;
		border-radius: 10px;
	}

	.img-template-active img{
		width: 300px;
		border: 3px solid green;
		border-radius: 10px;
	}

	.template:hover{
		cursor: pointer;
	}

	#btn_token:hover{
		cursor: pointer;
	}
</style>
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/nuvemshop/save" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{{ isset($config->id) ? $config->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ isset($config) ? "Editar": "Cadastrar" }}} Configuração da Nuvem Shop</h3>
						</div>
					</div>
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<div class="row">
										<div class="form-group validated col-sm-3 col-lg-2 col-12">
											<label class="col-form-label">Client ID</label>
											<div class="">
												<input id="client_id" type="text" class="form-control @if($errors->has('client_id')) is-invalid @endif" name="client_id" value="{{{ isset($config) ? $config->client_id : old('client_id') }}}">
												@if($errors->has('client_id'))
												<div class="invalid-feedback">
													{{ $errors->first('client_id') }}
												</div>
												@endif
												<p class="text-danger">Exemplo 5004</p>
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-6 col-12">
											<label class="col-form-label">Client secret</label>
											<div class="">
												<input id="client_secret" type="text" class="form-control @if($errors->has('client_secret')) is-invalid @endif" name="client_secret" value="{{{ isset($config) ? $config->client_secret : old('client_secret') }}}">
												@if($errors->has('client_secret'))
												<div class="invalid-feedback">
													{{ $errors->first('client_secret') }}
												</div>
												@endif
												<p class="text-danger">Exemplo iL8mfw69yXb02s6WP8iyX5VLTzO3Pvqt1s26KNwrDoDQtPyF</p>
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-4 col-12">
											<label class="col-form-label">Email</label>
											<div class="">
												<input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($config) ? $config->email : old('email') }}}">
												@if($errors->has('email'))
												<div class="invalid-feedback">
													{{ $errors->first('email') }}
												</div>
												@endif

											</div>
										</div>

										<div class="form-group col-lg-5 col-12">
											<label class="col-form-label">Natureza de Operação Padrão</label>
											<div class="">
												<div class="input-group">
													<select class="custom-select form-control" id="natureza_padrao" name="natureza_padrao">
														<option value="">Selecione a natureza de operação</option>
														@foreach($naturezas as $key => $n)
														<option @isset($config) @if($config->natureza_padrao == $n->id) selected @endif @endif value="{{$n->id}}">{{$n->natureza}}</option>
														@endforeach
													</select>
												</div>
											</div>
										</div>

										<div class="form-group col-lg-4 col-12">
											<label class="col-form-label">Forma de Pagamento Padrão</label>
											<div class="">
												<div class="input-group date">
													<select class="custom-select form-control" id="forma_pagamento_padrao" name="forma_pagamento_padrao">
														<option value="">Selecione a forma de pagamento</option>
														@foreach(App\Models\Venda::tiposPagamento() as $key => $f)
														<option @isset($config) @if($config->forma_pagamento_padrao == $key) selected @endif @endif value="{{$key}}">{{$f}}</option>
														@endforeach
													</select>
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
								<a style="width: 100%" class="btn btn-danger" href="/nuvemshop/config">
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

@section('javascript')
<script type="text/javascript">

</script>
@endsection
@endsection