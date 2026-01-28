@extends('default.layout')
@section('content')
<style type="text/css">
	.btn-file {
		position: relative;
		overflow: hidden;
	}

	.btn-file input[type=file] {
		position: absolute;
		top: 0;
		right: 0;
		min-width: 100%;
		min-height: 100%;
		font-size: 100px;
		text-align: right;
		filter: alpha(opacity=0);
		opacity: 0;
		outline: none;
		background: white;
		cursor: inherit;
		display: block;
	}
</style>

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content" >

			<div class="col-lg-12" id="content">
				<!--begin::Portlet-->

				<h3 class="card-title">MDFe código: <strong>{{$mdfe->id}}</strong></h3>

				<form method="post" action="/mdfe/estadoFiscal" enctype="multipart/form-data">
					<input type="hidden" value="{{$mdfe->id}}" name="mdfe_id">
					<div class="row">
						<div class="col-xl-12">

							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="col-lg-6 col-md-6 col-sm-6 col-12">
											<h4>Chave: <strong class="text-info">{{ $mdfe->chave }}</strong></h4>
											<h4>Data de registro: <strong class="text-info">{{ \Carbon\Carbon::parse($mdfe->created_at)->format('d/m/Y H:i:s')}}</strong></h4>
											<h4>Valor da carga: R$ <strong class="text-info">{{ number_format($mdfe->valor_carga, 2, ',', '.') }}</strong></h4>
											
										</div>


										<div class="form-group col-3">
											<label class="col-form-label">Estado</label>
											<div class="">
												<div class="input-group date">
													<select class="custom-select form-control" id="estado" name="estado">
														<option @if($mdfe->estado == 'NOVO') selected @endif value="NOVO">NOVO</option>
														<option @if($mdfe->estado == 'APROVADO') selected @endif value="APROVADO">APROVADO</option>
														<option @if($mdfe->estado == 'REJEITADO') selected @endif value="REJEITADO">REJEITADO</option>
														<option @if($mdfe->estado == 'CANCELADO') selected @endif value="CANCELADO">CANCELADO</option>
													</select>
												</div>
											</div>
											<br>

											<div class="col-12">


												<input type="hidden" name="_token" value="{{ csrf_token() }}">

												<label class="col-form-label">Upload XML</label>
												<div class="">
													<span class="btn btn-primary btn-file">
														Procurar arquivo<input accept=".xml" name="file" type="file">
													</span>
													<label class="text-info" id="filename"></label>

												</div>

											</div>

										</div>
									</div>
								</div>
							</div>
						</div>
					</div>


					<div class="row">
						<div class="col-xl-12">

							<button class="btn btn-lg btn-light-success">
								<i class="la la-check"></i>
								Salvar
							</a>
						</button>
					</div>
				</form>

			</div>
		</div>
	</div>
</div>

@endsection	