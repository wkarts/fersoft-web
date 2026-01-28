@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{{ $contract != null ? '/contrato/update': '/contrato/save' }}}" enctype="multipart/form-data">


					<input type="hidden" name="id" value="{{{ $contract != null ? $contract->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{$contract != null ? 'Editar' : 'Novo'}} Contrato</h3>
						</div>

					</div>
					@csrf
					<div class="row">
						<div class="col-12">
							<textarea name="texto" id="area" style="width:100%;height:500px;">{{$contract != null ? $contract->texto : ''}}</textarea>
						</div>

						<div class="form-group validated col-sm-8 col-lg-3">
							<label class="col-form-label">Dias de acessos para forçar assinatura</label>
							<div class="">
								<input data-mask="000" id="accessos_forcar_assinar" type="text" class="form-control @if($errors->has('accessos_forcar_assinar')) is-invalid @endif" name="accessos_forcar_assinar" value="{{{ isset($contract) ? $contract->accessos_forcar_assinar : old('accessos_forcar_assinar') }}}">
								@if($errors->has('accessos_forcar_assinar'))
								<div class="invalid-feedback">
									{{ $errors->first('accessos_forcar_assinar') }}
								</div>
								@endif
							</div>
						</div>

						<div class="form-group validated col-sm-6 col-lg-3">
							<label class="col-form-label text-left col-lg-12 col-sm-12">Usar Certificado Digital</label>
							<div class="col-6">
								<span class="switch switch-outline switch-success">
									<label>
										<input value="true" @if(isset($contract) && $contract->usar_certificado) checked @endif type="checkbox" name="usar_certificado" id="usar_certificado">
										<span></span>
									</label>
								</span>
							</div>
						</div>

					</div>

					<div class="row" style="margin-top: 10px; margin-bottom: 10px;">

						<div class="col-lg-3 col-sm-6 col-md-4">
							<button style="width: 100%" type="submit" class="btn btn-success">
								<i class="la la-check"></i>
								<span class="">Salvar</span>
							</button>
						</div>
						<div class="col-lg-3 col-sm-6 col-md-4">

							@if($contract != null)
							<a href="/contrato/impressao" style="width: 100%" class="btn btn-info">
								<i class="la la-print"></i>
								Impressão
							</a>
							@endif
						</div>

					</div>

				</form>
			</div>
		</div>

	</div>
</div>

@endsection