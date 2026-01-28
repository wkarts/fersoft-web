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

	body.loading .modal-loading {
		display: block;
	}

	.modal-loading {
		display: none;
		position: fixed;
		z-index: 10000;
		top: 0;
		left: 0;
		height: 100%;
		width: 100%;
		background: rgba(255, 255, 255, 0.8)
		url("/loading.gif") 50% 50% no-repeat;
	}
</style>

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">

	<form method="post" enctype="multipart/form-data" action="/produtos/importacao">

		<div class="card card-custom gutter-b example example-compact">
			<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
				<div class="col-lg-12">
					<br>
					<!--begin::Portlet-->


					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Importação de Produtos

								<a style="margin-left: 5px;" class="btn btn-info" href="/produtos/downloadModelo">
									<i class="las la-download"></i>Baixar modelo
								</a>
							</h3>


						</div>

					</div>
					@csrf

					<div class="row">

						<div class="col-xl-12">
							<input type="hidden" name="_token" value="{{ csrf_token() }}">
							<div class="row">
								<div class="form-group validated col-12 col-lg-6">
									<label class="col-form-label">.xls/.xlsx</label>
									<div class="">
										<span class="btn btn-primary btn-file">
											Procurar arquivo<input accept=".xls, .xlsx" name="file" type="file">
										</span>
										<label class="text-info" id="filename"></label>
									</div>
								</div>

								{!! __view_locais('Disponibilidade') !!}

							</div>
						</div>
					</div>

				</div>

				<div class="card-footer">

					<div class="row">
						<div class="col-xl-2">

						</div>

						<div class="col-lg-3 col-sm-6 col-md-4">
							<button style="width: 100%" type="submit" class="btn btn-success">
								<i class="la la-check"></i>
								<span class="">Importar Produtos</span>
							</button>
						</div>

					</div>
				</div>
			</div>
		</div>
	</form>
</div>
<div class="modal-loading loading-class"></div>

@endsection

@section('javascript')
<script type="text/javascript">
	$('.btn-success').click(() => {
		$('.modal-loading').css('display', 'block')
	})
</script>
@endsection