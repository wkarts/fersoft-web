@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/empresas/setarPlano">
					<input type="hidden" name="id" value="{{$empresa->id}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Setar Plano</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<h2 class="text-success"> {{$empresa->nome}}</h2>
									@if($empresa->planoEmpresa)
									<h4>Plano atual <strong>{{ $empresa->planoEmpresa->plano->nome }}</strong></h4>
									@endif
									<div class="row">

										<input type="hidden" id="planos" value="{{json_encode($planos)}}" name="">
										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">Plano</label>
											<div class="">
												<select required id="plano" name="plano" class="form-control custom-select">
													<option value="">Selecione o plano</option>
													@foreach($planos as $p)
													<option value="{{$p->id}}">{{$p->nome}} - R$ {{ number_format($p->valor, 2, ',', '.') }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-2 col-lg-3">
											<label class="col-form-label">Valor</label>
											<div class="">
												<input required id="valor" name="valor" class="form-control money">
											</div>
										</div>

										<div class="form-group validated col-lg-3">
											<label class="col-form-label">Forma de pagamento</label>
											<div class="">
												<select class="custom-select" name="forma_pagamento">
													<option value="Dinheiro">Dinheiro</option>
													<option value="Cartão de débito">Cartão de débito</option>
													<option value="Cartão de crédito">Cartão de crédito</option>
													<option value="Pix">Pix</option>
													<option value="Boleto">Boleto</option>
													<option value="Transferência">Transferência</option>
												</select>
											</div>
										</div>


										<div class="form-group col-lg-2 col-md-4">
											<label class="col-form-label">Data de Expiração</label>
											<div class="">
												<div class="input-group date">
													<input required type="text" name="expiracao" class="form-control @if($errors->has('data_registro')) is-invalid @endif" data-mask="00/00/0000" data-mask-reverse="true" value="{{$exp}}" id="kt_datepicker_3" />
													<div class="input-group-append">
														<span class="input-group-text">
															<i class="la la-calendar"></i>
														</span>
													</div>
												</div>
												@if($errors->has('data_registro'))
												<div class="invalid-feedback">
													{{ $errors->first('data_registro') }}
												</div>
												@endif

												<label class="checkbox" style="margin-top: 5px;">
													<input type="checkbox" id="indeterminado" name="indeterminado"/>
													<span> </span>
													<b style="margin-left: 3px;">indeterminado</b>
												</label>
											</div>
										</div>

										<div class="form-group validated col-sm-12">
											<label class="col-form-label">Mensagem de alerta vencimento (opcional)</label>
											<div class="">
												<input class="form-control" type="text" name="mensagem_alerta">
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
								<a style="width: 100%" class="btn btn-danger" href="/empresas">
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

@section('javascript')
<script type="text/javascript">
	var planos = [];
	$(function () {
		planos = JSON.parse($('#planos').val());
	});

	$('#indeterminado').click(() => {
		let i = $('#indeterminado').is(':checked')
		if(i){
			$('#kt_datepicker_3').val('--')
		}else{
			var outraData = new Date();
			let mes = outraData.getMonth()+1;

			let d = (outraData.getDate() < 10 ? "0"+outraData.getDate() : outraData.getDate()) + "/"
			d += (mes < 10 ? "0"+mes : mes) + "/"
			d += outraData.getFullYear()

			$('#kt_datepicker_3').val(d)
		}
	})

	$('#plano').change(() => {
		let plano = $('#plano').val();
		planos.map((p) => {
			if(p.id == plano){
				let intervalo = p.intervalo_dias;
				var outraData = new Date();
				outraData.setDate(outraData.getDate() + intervalo);

				let mes = outraData.getMonth()+1;
				let d = (outraData.getDate() < 10 ? "0"+outraData.getDate() : outraData.getDate()) + "/"
				d += (mes < 10 ? "0"+mes : mes) + "/"
				d += outraData.getFullYear()

				$('#kt_datepicker_3').val(d)
				$('#valor').val(p.valor.replace(".", ","))
			}
		})
	})
</script>
@endsection
@endsection
