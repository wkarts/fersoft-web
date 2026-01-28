@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" @isset($item) action="{{route('difal.update', [$item->id])}}" @else action="{{route('difal.store')}}" @endif id="form-register">
					@csrf
					@isset($item)
					@method('put')
					@endif

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">@isset($item) Editar @else Cadastrar @endif Difal</h3>
						</div>
					</div>

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										
										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">UF</label>
											<select required class="custom-select form-control" name="uf">
												<option value="">--</option>
												@foreach(\App\Models\Cidade::estados() as $uf)
												<option @isset($item) @if($item->uf == $uf) selected @endif @endif value="{{ $uf }}">{{ $uf }}</option>
												@endforeach
											</select>

										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">CFOP</label>
											<input required type="tel" class="form-control cfop" name="cfop" value="{{{ isset($item) ? $item->cfop : old('cfop') }}}">
										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">% ICMS UF Destino</label>
											<input required type="tel" class="form-control perc" name="pICMSUFDest" value="{{{ isset($item) ? $item->pICMSUFDest : old('pICMSUFDest') }}}">
										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">% ICMS Interno</label>
											<input required type="tel" class="form-control perc" name="pICMSInter" value="{{{ isset($item) ? $item->pICMSInter : old('pICMSInter') }}}">
										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">% ICMS Interestadual UF</label>
											<input required type="tel" class="form-control perc" name="pICMSInterPart" value="{{{ isset($item) ? $item->pICMSInterPart : old('pICMSInterPart') }}}">
										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">% Fundo Combate a Pobreza</label>
											<input required type="tel" class="form-control perc" name="pFCPUFDest" value="{{{ isset($item) ? $item->pFCPUFDest : old('pFCPUFDest') }}}">
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
