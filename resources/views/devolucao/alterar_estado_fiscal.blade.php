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

				<h3 class="card-title">Devolução código: <strong>{{$devolucao->id}}</strong></h3>

				<form method="post" action="/devolucao/estadoFiscal" enctype="multipart/form-data">
					<input type="hidden" value="{{$devolucao->id}}" name="devolucao_id">
					<div class="row">
						<div class="col-xl-12">

							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="col-lg-6 col-md-6 col-sm-6 col-12">
											<h4>Cliente: <strong class="text-info">{{$devolucao->fornecedor->razao_social}}</strong></h4>
											<h5>CNPJ: <strong class="text-info">{{$devolucao->fornecedor->cpf_cnpj}}</strong></h5>
											<h5>Data: <strong class="text-info">{{ \Carbon\Carbon::parse($devolucao->data_registro)->format('d/m/Y H:i:s')}}</strong></h5>
											<h5>Valor Integral: <strong class="text-info">{{ number_format($devolucao->valor_integral, 2, ',', '.') }}</strong></h5>
											<h5>Valor Devolvido: <strong class="text-info">{{ number_format($devolucao->valor_devolvido, 2, ',', '.') }}</strong></h5>
											<h5>Cidade: <strong class="text-info">{{ $devolucao->fornecedor->cidade->nome }} ({{ $devolucao->fornecedor->cidade->uf }})</strong></h5>
											<h5>Chave NFe: <strong class="text-info">{{$devolucao->chave != "" ? $devolucao->chave : '--'}}</strong></h5>
										</div>


										<div class="form-group col-3">
											<label class="col-form-label">Estado</label>
											<div class="">
												<div class="input-group date">
													<select class="custom-select form-control" id="estado" name="estado">
														<option @if($devolucao->estado == 0) selected @endif value="0">DISPONIVEL</option>
														<option @if($devolucao->estado == 1) selected @endif value="1">APROVADO</option>
														<option @if($devolucao->estado == '2') selected @endif value="2">REJEITADO</option>
														<option @if($devolucao->estado == '3') selected @endif value="3">CANCELADO</option>
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