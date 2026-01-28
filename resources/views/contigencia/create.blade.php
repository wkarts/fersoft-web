@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{route('contigencia.store')}}" id="form-register">
					@csrf
					<input type="hidden" name="id" value="{{{ isset($conta) ? $conta->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Contigência</h3>
						</div>
					</div>

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										
										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">Tipo</label>
											<select required class="custom-select form-control" id="tipo" name="tipo">
												<option value="">--</option>
												@foreach(\App\Models\Contigencia::tiposContigencia() as $t)
												<option value="{{ $t }}">{{ $t }}</option>
												@endforeach
											</select>

										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">Documento</label>
											<select required class="custom-select form-control" id="documento" name="documento">
												<option value="">--</option>
												<option value="NFe">NFe</option>
												<option value="NFCe">NFCe</option>
											<!-- 	<option disabled value="CTe">CTe</option>
												<option disabled value="MDFe">MDFe</option> -->
											</select>

										</div>

										<div class="form-group validated col-lg-8 col-md-4 col-sm-6">
											<label class="col-form-label">Motivo</label>

											<input required id="motivo" type="text" class="form-control @if($errors->has('motivo')) is-invalid @endif" name="motivo" value="">
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
					</div>
					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/contigencia">
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

@endsection
@section('javascript')
<script type="text/javascript">
	$(document).on("change", "#tipo", function() {
		let tipo = $(this).val()
		$("#documento option").removeAttr('disabled');
		if(tipo == 'OFFLINE'){
			$("#documento option[value='NFe']").attr('disabled', 1);
		}else{
			$("#documento option[value='NFCe']").attr('disabled', 1);
		}
	})

</script>
@endsection
