@extends('default.layout')
@section('css')
<style type="text/css">
	.input-group-prepend:hover{
		cursor: pointer;
	}
</style>
@endsection
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>

				<form method="post" action="{{ isset($codigo) ? '/cuponsEcommerce/update/'.$codigo->id : '/cuponsEcommerce/store' }}">
					@isset($codigo)
					@method('put')
					@endisset

					<input type="hidden" name="id" value="{{{isset($codigo) ? $codigo->id : 0}}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ isset($codigo) ? "Editar": "Cadastrar" }}} Cupom</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<div class="row">

										<div class="form-group validated col-sm-4 col-lg-4">
											<label class="col-form-label">Descrição</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('descricao')) is-invalid @endif" name="descricao" value="{{{ isset($codigo) ? $codigo->descricao : old('descricao') }}}" >
												@if($errors->has('descricao'))
												<div class="invalid-feedback">
													{{ $errors->first('descricao') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Código</label>
											<div class="input-group">
												<input readonly type="text" class="form-control @if($errors->has('codigo')) is-invalid @endif" id="codigo" name="codigo" value="{{{ isset($codigo) ? $codigo->codigo : old('codigo') }}}">
												<div class="input-group-prepend">
													<span class="input-group-text bg-info text-white" id="btn_token">
														<li class="fa fa-key"></li>
													</span>
												</div>
												@if($errors->has('codigo'))
												<div class="invalid-feedback">
													{{ $errors->first('codigo') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2 col-6">
											<label class="col-form-label">Tipo</label>
											<div class="">
												<select class="custom-select form-control" id="tipo" name="tipo">
													<option @isset($codigo) @if($codigo->tipo == 'percentual') selected @endif @endif value="percentual">Percentual %</option>
													<option @isset($codigo) @if($codigo->tipo == 'fixo') selected @endif @endif value="fixo">Valor R$</option>
												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2">
											<label class="col-form-label">Valor</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('valor')) is-invalid @endif money" name="valor" value="{{{ isset($codigo) ? moeda($codigo->valor) : old('valor') }}}" >
												@if($errors->has('valor'))
												<div class="invalid-feedback">
													{{ $errors->first('valor') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2">
											<label class="col-form-label">Valor mínimo do pedido</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('valor_minimo_pedido')) is-invalid @endif money" name="valor_minimo_pedido" value="{{{ isset($codigo) ? moeda($codigo->valor_minimo_pedido) : old('valor_minimo_pedido') }}}" >
												@if($errors->has('valor_minimo_pedido'))
												<div class="invalid-feedback">
													{{ $errors->first('valor_minimo_pedido') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Status</label>
											<span class="switch switch-outline switch-info">
												<label>
													<input id="status" @if(isset($codigo->status) && $codigo->status) checked @endisset
													name="status" type="checkbox" >
													<span></span>
												</label>
											</span>
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
								<a style="width: 100%" class="btn btn-danger" href="/cuponsEcommerce">
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
	$('#btn_token').click(() => {
		let token = generate_token(6);
		$('#codigo').val(token)
	})

	function generate_token(length){

		var a = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890".split("");
		var b = [];  
		for (var i=0; i<length; i++) {
			var j = (Math.random() * (a.length-1)).toFixed(0);
			b[i] = a[j];
		}
		return b.join("");
	}

</script>
@endsection
