@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/contasPagar/pagar-multi">
					<input type="hidden" value="{{$somaTotal}}" name="valor_total">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Pagar Contas</h3>
						</div>
					</div>
					@csrf

					@php
					$conta = $contas[0];
					@endphp
					<div class="row">

						<div class="col-xl-12">

							<div class="row">
								<div class="col s12">

									<h5>Valor Total: <strong>{{ number_format($somaTotal, 2, ',', '.') }}</strong></h5>

									<br>
									<table class="table col-12 col-lg-8">
										<thead>
											<tr>
												<th>Vencimento</th>
												<th>Valor</th>
											</tr>
										</thead>
										<tbody>
											@foreach($contas as $c)
											<input type="hidden" name="conta_pagar_id[]" value="{{ $c->id }}">
											<tr>
												<td>{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y')}}</td>
												<td>R$ {{number_format($c->valor_integral, 2, ',', '')}}</td>
											</tr>
											@endforeach
										</tbody>
										<tfoot>
											<tr>
												<th class="text-info" colspan="2">Contas selecionadas</th>
											</tr>
										</tfoot>
									</table>
								</div>
							</div>

							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Valor Recebido</label>
											<div class="">
												<input required type="text" class="form-control @if($errors->has('valor')) is-invalid @endif money" name="valor" value="{{number_format($somaTotal, 2, ',', '')}}">
												@if($errors->has('valor'))
												<div class="invalid-feedback">
													{{ $errors->first('valor') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Data de recebimento</label>
											<div class="">
												<input required type="text" name="data_pagamento" class="form-control @if($errors->has('vencimento')) is-invalid @endif date-input" value="{{ date('d/m/Y') }}" id="kt_datepicker_3" />
												@if($errors->has('data_pagamento'))
												<div class="invalid-feedback">
													{{ $errors->first('data_pagamento') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-3">
											<label class="col-form-label" id="">Tipo de Pagamento</label>
											<select required class="custom-select form-control" id="forma" name="tipo_pagamento">
												<option value="">Selecione o tipo de pagamento</option>
												@foreach(App\Models\ContaReceber::tiposPagamento() as $c)
												<option value="{{$c}}">{{$c}}</option>
												@endforeach
											</select>
										</div>

										@if(sizeof($contasEmpresa) > 0)
										<div class="form-group validated col-sm-12 col-lg-4">
											<label class="col-form-label" id="">Conta</label>
											<select required name="conta_id" class="select2-custom custom-select">
												<option value=""></option>
												@foreach($contasEmpresa as $c)
												<option value="{{ $c->id }}">
													{{ $c->nome }}
												</option>
												@endforeach
											</select>
										</div>
										@endif
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
								<a style="width: 100%" class="btn btn-danger" href="/contasReceber">
									<i class="la la-close"></i>
									<span class="">Cancelar</span>
								</a>
							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<button style="width: 100%" type="submit" class="btn btn-success">
									<i class="la la-check"></i>
									<span class="">Pagar</span>
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
