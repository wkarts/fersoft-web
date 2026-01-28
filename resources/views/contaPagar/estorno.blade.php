@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/contasPagar/estorno" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{$conta->id}}">

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Estornar Conta</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">

							<div class="row">
								<div class="col s12">
									@if($conta->compra_id != null)
									<h5>Fornecedor: <strong>{{$conta->compra->fornecedor->razao_social}}</strong></h5>
									@endif

									<h5>Data de registro: <strong>{{ \Carbon\Carbon::parse($conta->data_registro)->format('d/m/Y')}}</strong></h5>
									<h5>Data de vencimento: <strong>{{ \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y')}}</strong></h5>
									<h5>Valor: <strong>{{ number_format($conta->valor_integral, 2, ',', '.') }}</strong></h5>
									<h5>Categoria: <strong>{{$conta->categoria->nome}}</strong></h5>
									<h5>Referencia: <strong>{{$conta->referencia}}</strong></h5>
									<h5>Observação: <strong>{{$conta->observacao}}</strong></h5>
								</div>
							</div>

							@if($conta->estorno == 0)
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<div class="row">

										<div class="form-group validated col-sm-6 col-lg-8">
											<label class="col-form-label">Motivo Estorno</label>
											<div class="">
												<input required type="text" class="form-control @if($errors->has('motivo')) is-invalid @endif" name="motivo" value="">
												@if($errors->has('motivo'))
												<div class="invalid-feedback">
													{{ $errors->first('motivo') }}
												</div>
												@endif
											</div>
										</div>
									</div>
								</div>
							</div>
							@else

							<h5 class="mt-4">Esta conta já foi estornada</h5>
							<h5>Motivo: <strong>{{ $conta->motivo_estorno }}</strong></h5>
							@endif
						</div>
					</div>
					@if($conta->estorno == 0)
					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/contasPagar">
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
					@endif
				</form>
			</div>
		</div>
	</div>
</div>

@endsection