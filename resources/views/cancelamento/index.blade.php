@extends('default.layout')

@section('css')
<style type="text/css">
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
@endsection
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				@if($item == null)
				<form method="post" action="{{ route('cancelamento.store') }}">
					@csrf
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Cancelamento de licença 
								@if($contrato == 1)
								<a href="{{ route('cancelamento.download') }}" class="btn btn-sm btn-info ml-3	">
									<i class="la la-print"></i>
									Baixar Contrato Assinado
								</a>
								@endif
							</h3>


						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<div class="row mb-5">
										<div class="col-12">

											<div class="row">
												<div class="col-lg-12">
													<label>Justificativa ou motivo de cancelamento</label>
													<textarea required name="justificativa" class="form-control" rows="10"></textarea>
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
								<a style="width: 100%" class="btn btn-danger" href="/graficos">
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
				@else
				<div class="row m-2">
					<div class="col-12">
						<h4>Cancelamento de licença ativo, aberto em {{ __date($item->created_at, 1)}}</h4>
						@if($contrato == 1)
						<a href="{{ route('cancelamento.download') }}" class="btn btn-sm btn-info ml-3">
							<i class="la la-print"></i>
							Baixar Contrato Assinado
						</a>
						@endif
					</div>
				</div>
				@endif
			</div>
		</div>
	</div>
</div>

<div class="modal-loading loading-class"></div>

@endsection

@section('javascript')
<script type="text/javascript">
	$('.btn-bkp').click(() => {
		$body = $("body");
		$body.addClass("loading");

	})
</script>
@endsection