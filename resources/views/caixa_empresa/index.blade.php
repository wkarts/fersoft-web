@extends('default.layout', ['title' => 'Finalizando caixa'])

@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="m-4 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">


				<form method="post" action="{{ route('caixa-empresa.store') }}" class="card card-custom gutter-b example example-compact">

					@csrf
					<input type="hidden" name="abertura_id" value="{{ $abertura->id }}">
					<div class="card-header" style="border-top-left-radius: 5px; border-top-right-radius: 5px;">
						<h3 class="card-title">Finalizando caixa</h3>
					</div>
					<div class="card-body">

						<div class="row">
							<div class="col-md-6">
								<h5>Data de abertura do caixa: <strong>{{ __date($abertura->created_at) }}</strong></h5>
							</div>
							<div class="col-md-6">
								<h5>Usuário: <strong>{{ $abertura->usuario->nome }}</strong></h5>
							</div>
						</div>
						@foreach($somaTiposPagamento as $key => $tipo)
						@if($tipo > 0)
						<div class="row mt-2">
							<div class="col-12">
								<div class="card">
									<div class="card-body">
										<div class="row">
											<div class="col-12 col-md-6">
												<h3>{{ App\Models\VendaCaixa::getTipoPagamento($key) }}</h3>
											</div>
											<div class="col-12 col-md-6">
												<h3><strong>R$ {{ moeda($tipo) }}</strong></h3>
											</div>
										</div>
										<div class="row line-row">
											<div class="col-12 appends">
												<div class="dynamic-form row mt-4">
													<input type="hidden" value="{{ $key }}" name="tipo_pagamento[]">

													<div class="col-7 col-md-3">
														<label>Conta</label>
														<select required name="conta_id[]" class="select2-custom custom-select">
															<option value=""></option>
															@foreach($contasEmpresa as $c)
															<option value="{{ $c->id }}">
																{{ $c->nome }}
															</option>
															@endforeach
														</select>
													</div>

													<div class="col-5 col-md-2">
														<label>Valor</label>
														<input required type="tel" class="form-control money valor_linha" name="valor[]">
													</div>

													<div class="col-12 col-md-7">
														<label>Descrição</label>
														<input type="text" class="form-control ignore descricao" name="descricao[]">
													</div>

												</div>
											</div>
										</div>
										<div class="row col-12 mt-4">
											<button type="button" class="btn btn-info btn-clone">
												<i class="la la-plus"></i> Adicionar linha
											</button>
											<div class="col-12">
												<input type="hidden" class="valor_total" value="{{ $tipo }}">
												<h5 class="float-right">Valor restante  <strong class="total-restante text-danger">R$ 0,00</strong></h5>
												<!-- <h5 class="float-right">Soma dos valores <strong class="soma-valor">R$ 0,00</strong></h5> -->
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						@endif
						@endforeach

					</div>

					<div class="row">
						<div class="col-12">
							<div class="col-md-3 float-right m-4">
								<button disabled class="btn btn-success w-100 btn-store">
									Salvar
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
<script type="text/javascript" src="/js/conta_empresa.js"></script>
@endsection
