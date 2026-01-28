@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/cashback-config/store" id="form-register">
					<input type="hidden" name="id" value="{{{ isset($conta) ? $conta->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Configuração CashBack</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Percentual de crédito sobre a venda</label>
											<div class="">
												<input required type="text" class="form-control @if($errors->has('valor_percentual')) is-invalid @endif perc" name="valor_percentual" value="{{ $item != null ? $item->valor_percentual : old('valor_percentual') }}">
												@if($errors->has('valor_percentual'))
												<div class="invalid-feedback">
													{{ $errors->first('valor_percentual') }}
												</div>
												@endif
												
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Percentual máximo por venda</label>
											<div class="">
												<input required type="text" class="form-control @if($errors->has('percentual_maximo_venda')) is-invalid @endif perc" name="percentual_maximo_venda" value="{{ $item != null ? $item->percentual_maximo_venda : old('percentual_maximo_venda') }}">
												@if($errors->has('percentual_maximo_venda'))
												<div class="invalid-feedback">
													{{ $errors->first('percentual_maximo_venda') }}
												</div>
												@endif
												
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Dias expiração</label>
											<div class="">
												<input required type="text" class="form-control @if($errors->has('dias_expiracao')) is-invalid @endif" data-mask="0000" name="dias_expiracao" value="{{ $item != null ? $item->dias_expiracao : old('dias_expiracao') }}">
												@if($errors->has('dias_expiracao'))
												<div class="invalid-feedback">
													{{ $errors->first('dias_expiracao') }}
												</div>
												@endif
												
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Valor minímo de venda</label>
											<div class="">
												<input required type="text" class="form-control @if($errors->has('valor_minimo_venda')) is-invalid @endif money" name="valor_minimo_venda" value="{{ $item != null ? moeda($item->valor_minimo_venda) : old('valor_minimo_venda') }}">
												@if($errors->has('valor_minimo_venda'))
												<div class="invalid-feedback">
													{{ $errors->first('valor_minimo_venda') }}
												</div>
												@endif
												
											</div>
										</div>

										<div class="form-group validated col-12">
											<label class="col-form-label">Mensagem padrão do whatsApp</label>
											<div class="">
												<textarea class="form-control" name="mensagem_padrao_whatsapp">{{ $item != null ? $item->mensagem_padrao_whatsapp : old('mensagem_padrao_whatsapp') }}</textarea>
												@if($errors->has('mensagem_padrao_whatsapp'))
												<div class="invalid-feedback">
													{{ $errors->first('mensagem_padrao_whatsapp') }}
												</div>
												@endif
												
											</div>
											<p class="text-danger ml-1 mr-1">*Use {credito} para o valor do crédito, use {expiracao} para data de expiração, use {nome} para o nome do cliente - EXEMPLO: O valor do seu CashBack é de {credito}, com validade até {expiracao}, obrigado {nome}</p>
										</div>

										<div class="form-group validated col-12">
											<label class="col-form-label">Mensagem de expiração 5 dias</label>
											<div class="">
												
												<textarea class="form-control" name="mensagem_automatica_5_dias">{{ $item != null ? $item->mensagem_automatica_5_dias : old('mensagem_automatica_5_dias') }}</textarea>
												@if($errors->has('mensagem_automatica_5_dias'))
												<div class="invalid-feedback">
													{{ $errors->first('mensagem_automatica_5_dias') }}
												</div>
												@endif
												
											</div>
											<p class="text-danger ml-1 mr-1">EXEMPLO: O valor {credito} irá expirar!</p>
										</div>

										<div class="form-group validated col-12">
											<label class="col-form-label">Mensagem de expiração 1 dia</label>
											<div class="">
												
												<textarea class="form-control" name="mensagem_automatica_1_dia">{{ $item != null ? $item->mensagem_automatica_1_dia : old('mensagem_automatica_1_dia') }}</textarea>
												@if($errors->has('mensagem_automatica_1_dia'))
												<div class="invalid-feedback">
													{{ $errors->first('mensagem_automatica_1_dia') }}
												</div>
												@endif
												
											</div>
											<p class="text-danger ml-1 mr-1">EXEMPLO: O valor {credito} irá expirar!</p>
										</div>

									</div>
									
								</div>

							</div>
						</div>
					</div>

				</div>
				<div class="card-footer">
					<div class="row">
						<div class="col-lg-9">
						</div>
						<div class="col-lg-3 col-12">
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

@endsection

