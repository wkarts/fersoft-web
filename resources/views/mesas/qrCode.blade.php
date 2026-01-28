@extends('default.layout')

@section('css')
<style type="text/css">
	@media print {
		body * {
			visibility: hidden;
		}

		.print, .print * {
			visibility: visible;
		}
	}
</style>
@endsection
@section('content')

<div class="card card-custom gutter-b noPrint">

	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<p class="text-info">Informe a url para gerar o QrCode, exemplo: https://meurestaurante.com.br</p>
			<form method="get" action="" class="row">
				<div class="col-lg-4">
					<div class="form-group">
						<input type="text" name="url" class="form-control" placeholder="URL do controle de comandas">
					</div>
				</div>
				<div class="col-lg-2">
					<button type="submit" class="btn btn-info">Gerar</button>
				</div>
			</form>
			<br>
			<h4>Lista de Mesas</h4>
			<label>Numero de registros: {{sizeof($mesas)}}</label><br>	

			@if($url != '')
			<div class="row">
				@foreach($mesas as $m)

				<div class="col-sm-4 col-lg-4 col-md-6">

					<div class="card card-custom gutter-b">
						<div class="card-header">
							<h3 class="card-title">
								{{$m->nome}}
							</h3>
						</div>	
						<div class="card-body text-center">

							<div class="print"> 
								{!! QrCode::size(300)->generate($url . "/open/$m->token") !!}
							</div>
							<br><br>
							<a href="{{$url . "/open/$m->token"}}" target="_blank">{{$url . "/open/$m->token"}}</a>
						</div>	
						<div class="card-footer">
							<form method="get" action="/mesas/imprimirQrCode">
								<input type="hidden" value="{{$url . "/open/$m->token"}}" name="url">
								<button class="btn btn-info w-100">
									<i class="la la-print"></i> Imprimir
								</button>
							</form>
						</div>
					</div>
				</div>
				@endforeach				

			</div>
			@endif
		</div>
	</div>
</div>
@endsection	