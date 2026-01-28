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

				@if($item->estado == 'novo' || $item->estado == 'rejeitado')
				<a href="/nferemessa/edit_xml/{{$item->id}}" class="btn btn-info">
					<i class="la la-file"></i>
					Alterar XML da NFe
				</a>
				@endif
				<form method="post" action="/nferemessa/estadoFiscal/{{$item->id}}" enctype="multipart/form-data">
					@method('put')

					<div class="row mt-4">
						<div class="col-xl-12">

							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="col-lg-6 col-md-6 col-sm-6 col-12">
											<h5>Cliente: <strong class="text-success">{{$item->cliente->razao_social}}</strong></h5>
											<h5>CNPJ: <strong class="text-success">{{$item->cliente->cpf_cnpj}}</strong></h5>
											<h5>Data: <strong class="text-success">{{ \Carbon\Carbon::parse($item->data_registro)->format('d/m/Y H:i:s')}}</strong></h5>
											<h5>Valor Total: <strong class="text-success">{{ number_format($item->valor_total, 2, ',', '.') }}</strong></h5>
											<h5>Cidade: <strong class="text-success">{{ $item->cliente->cidade->nome }} ({{ $item->cliente->cidade->uf }})</strong></h5>
											<h5>Chave NFe: <strong class="text-success">{{$item->chave != "" ? $item->chave : '--'}}</strong></h5>
										</div>


										<div class="form-group col-3">
											<label class="col-form-label">Estado</label>
											<div class="">
												<div class="input-group date">
													<select class="custom-select form-control" id="estado" name="estado">
														<option @if($item->estado == 'novo') selected @endif value="novo">DISPONIVEL</option>
														<option @if($item->estado == 'aprovado') selected @endif value="aprovado">APROVADO</option>
														<option @if($item->estado == 'rejeitado') selected @endif value="rejeitado">REJEITADO</option>
														<option @if($item->estado == 'cancelado') selected @endif value="cancelado">CANCELADO</option>
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