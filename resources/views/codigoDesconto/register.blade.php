@extends('default.layout')
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>

				<form method="post" action="{{{ isset($codigo) ? '/codigoDesconto/update': '/codigoDesconto/save' }}}">
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
											<div class="">
												<input type="text" class="form-control @if($errors->has('codigo')) is-invalid @endif" id="codigoPromocional" name="codigo" value="{{{ isset($codigo) ? $codigo->codigo : old('codigo') }}}">
												@if($errors->has('codigo'))
												<div class="invalid-feedback">
													{{ $errors->first('codigo') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-lg-1 col-md-2 col-sm-6">
											<br><br>
											<a type="button" id="gerar-codigo" class="btn btn-success spinner-white spinner-right">
												<span>
													<i class="fa fa-key"></i>
												</span>
											</a>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2 col-6">
											<label class="col-form-label">Tipo</label>
											<div class="">
												<select class="custom-select form-control" id="tipo" name="tipo">
													<option value="valor">Valor R$</option>
													<option value="percentual">Percentual %</option>
												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2">
											<label class="col-form-label">Valor</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('valor')) is-invalid @endif money" name="valor" value="{{{ isset($codigo) ? number_format($codigo->valor, 2) : old('valor') }}}" >
												@if($errors->has('valor'))
												<div class="invalid-feedback">
													{{ $errors->first('valor') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2">
											<label class="col-form-label">Valor minímo do pedido</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('valor_minimo_pedido')) is-invalid @endif money" name="valor_minimo_pedido" value="{{{ isset($codigo) ? number_format($codigo->valor_minimo_pedido, 2) : old('valor_minimo_pedido') }}}" >
												@if($errors->has('valor_minimo_pedido'))
												<div class="invalid-feedback">
													{{ $errors->first('valor_minimo_pedido') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2">
											<label class="col-form-label">Data de expiração</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('expiracao')) is-invalid @endif" name="expiracao" value="{{{ isset($codigo) ? \Carbon\carbon::parse($codigo->expiracao)->format('d/m/Y') : old('expiracao') }}}" data-mask="00/00/0000" data-mask-reverse="true">
												@if($errors->has('expiracao'))
												<div class="invalid-feedback">
													{{ $errors->first('expiracao') }}
												</div>
												@endif
											</div>
										</div>

										@isset($codigo)
										@if($codigo->cliente)
										<div>
											<br><br>
											<p class="text-danger ml-2">Notificaçao para cliente 
												<strong>{{$codigo->cliente->nome}}</strong>
											</p>
										</div>
										@else
										<div>
											<br><br>
											<p class="text-danger ml-2">Notificaçao para todos os clientes</p>
										</div>
										@endif
										@endisset

										@if(!isset($codigo))
										<div class="form-group validated col-12 col-lg-2 col-md-6 mt-4">
											Todos os Clientes
											<div class="switch switch-outline switch-success mt-2">
												<label class="">
													<input id="todos" name="todos" class="red-text" type="checkbox">
													<span class="lever"></span>

												</label>
											</div>
										</div>

										<div class="form-group validated col-12 col-lg-4 col-md-6">
											<label class="col-form-label">Cliente</label><br>
											<select class="form-control select2 @if($errors->has('cliente')) is-invalid @endif" id="kt_select2_1" name="cliente_id">
												<option value="null">Selecione o cliente</option>
												@foreach($clientes as $c)
												<option 
												value="{{$c->id}}">{{$c->id}} - {{$c->nome}}</option>
												@endforeach
											</select>
											@if($errors->has('cliente'))
											<div class="invalid-feedback">
												{{ $errors->first('cliente') }}
											</div>
											@endif
										</div>

										@endisset


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
								<a style="width: 100%" class="btn btn-danger" href="/codigoDesconto">
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