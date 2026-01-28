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
				<form method="post" action="{{ route('config-catraca.store') }}">
					@csrf

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Configuração de catraca</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="row mb-4">

									<div class="col-lg-3">
										<label>Usuário</label>
										<select name="usuario_id" required class="custom-select">
											<option value="">selecione</option>
											@foreach($usuarios as $u)
											<option @isset($item) @if($item->usuario_id == $u->id) selected @endif @endif value="{{ $u->id }}">{{ $u->nome }}</option>
											@endforeach
										</select>
									</div>

									<div class="col-lg-3 col-6">
										<label>Segundos para cada requisição</label>
										<input required value="{{{ isset($item) ? $item->segundos_requisicao : old('segundos_requisicao') }}}" name="segundos_requisicao" data-mask="00" class="form-control">
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
								<a style="width: 100%" class="btn btn-danger" href="/config-catraca">
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