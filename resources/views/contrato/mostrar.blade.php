@extends('default.layout')
@section('content')
@section('css')
<style type="text/css">
	.switch-success label{
		background: #1E1E2D;
		border-radius: 25px;
	}
</style>
@endsection
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<!--begin::Portlet-->
			
			{!! $texto !!}
			<br>
			<form method="post" action="/assinarContrato">
				@csrf
				<div class="row">
					<div class="col-4">
						<label>Aceito os termos:</label>

						<div class="switch switch-outline switch-success">
							<label>
								<input value="true" name="aceito" type="checkbox">
								<span class="lever"></span>
							</label>
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
								<span class="">Assinar</span>
							</button>
						</div>

					</div>
				</div>
			</form>
		</div>

	</div>
</div>

@endsection