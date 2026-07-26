@extends('default.layout')
@section('content')
<div class="d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/contasPagar/pagar" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{$conta->id}}">
					@csrf

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Pagar Conta</h3>
						</div>
					</div>

					<div class="row">
						<div class="col-xl-12">
							<div class="row">
								<div class="col-sm-12">
									@if($conta->compra_id != null)
									<h5>Fornecedor: <strong>{{$conta->compra->fornecedor->razao_social}}</strong></h5>
									@elseif($conta->fornecedor)
									<h5>Fornecedor: <strong>{{$conta->fornecedor->razao_social}}</strong></h5>
									@endif

									<h5>Data de registro: <strong>{{ \Carbon\Carbon::parse($conta->data_registro)->format('d/m/Y')}}</strong></h5>
									<h5>Data de vencimento: <strong>{{ \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y')}}</strong></h5>
									<h5>Valor Original: <strong class="text-primary">{{ number_format($conta->valor_integral, 2, ',', '.') }}</strong></h5>
									<h5>Categoria: <strong>{{$conta->categoria->nome}}</strong></h5>
									<h5>Referência: <strong>{{$conta->referencia}}</strong></h5>
								</div>
							</div>

							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<br>
									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Valor Final Pago</label>
											<input required type="text" class="form-control money" name="valor" id="valor_final" value="{{ number_format($conta->valor_integral, 2, ',', '.') }}">
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Juros (+)</label>
											<input type="text" class="form-control money" name="juros" value="0,00">
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Multa (+)</label>
											<input type="text" class="form-control money" name="multa" value="0,00">
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Desconto (-)</label>
											<input type="text" class="form-control money" name="desconto" value="0,00">
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Data de pagamento</label>
											<input required type="text" name="data_pagamento" class="form-control date-input" value="{{ date('d/m/Y') }}" id="kt_datepicker_3" />
										</div>
									</div>

									<div class="row" id="container_adiantamento" style="display: none;">
										<div class="form-group col-sm-12 col-lg-8">
											<label class="checkbox checkbox-lg">
												<input type="checkbox" name="usar_adiantamento" id="usar_adiantamento" value="1">
												<span></span>&nbsp;&nbsp;
												<strong id="label_adiantamento" class="text-info">Usar saldo de adiantamento do fornecedor</strong>
											</label>
										</div>
									</div>

									<div class="row" id="div_financeiro">
										<div class="form-group validated col-sm-12 col-lg-4">
											<label class="col-form-label">Tipo de Pagamento</label>
											<select required class="custom-select form-control" id="forma" name="tipo_pagamento">
												<option value="">Selecione o tipo</option>
												@foreach(App\Models\ContaPagar::tiposPagamento() as $c)
												<option value="{{$c}}">{{$c}}</option>
												@endforeach
											</select>
										</div>

										@if(sizeof($contasEmpresa) > 0)
										<div class="form-group validated col-sm-12 col-lg-4">
											<label class="col-form-label">Conta Bancária (Origem)</label>
											<select required name="conta_id" id="conta_id" class="select2-custom custom-select">
												<option value="">Selecione a conta</option>
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
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/contasPagar">
									<i class="la la-close"></i> Cancelar
								</a>
							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<button style="width: 100%" type="submit" class="btn btn-success">
									<i class="la la-check"></i> Confirmar Pagamento
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
		$(function () {
			const fornecedorId = @json($conta->fornecedor_id);

			if (fornecedorId) {
				$.get('/adiantamentos/consulta-saldo/fornecedor/' + fornecedorId)
					.done(function (res) {
						const saldo = Number(res.saldo || 0);
						if (saldo > 0) {
							$('#label_adiantamento').text(
								'Usar saldo de adiantamento do fornecedor (disponível: R$ ' +
								saldo.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ')'
							);
							$('#container_adiantamento').show();
						}
					});
			}

			$('#usar_adiantamento').on('change', function () {
				const usar = $(this).is(':checked');
				$('#div_financeiro').toggle(!usar);
				$('#conta_id, #forma').prop('required', !usar);
			});
		// Monitora a digitação nos campos de acréscimo e desconto
		$('input[name="juros"], input[name="multa"], input[name="desconto"]').on('keyup', function () {
			calcularTotal();
		});

		function calcularTotal() {
			// Valor base que veio da conta
			let valorBase = parseMoeda("{{ number_format($conta->valor_integral, 2, ',', '.') }}");
			
			let juros = parseMoeda($('input[name="juros"]').val());
			let multa = parseMoeda($('input[name="multa"]').val());
			let desconto = parseMoeda($('input[name="desconto"]').val());

			// Lógica: Base + Juros + Multa - Desconto
			let total = valorBase + juros + multa - desconto;

			// Impede que o valor final seja negativo
			if(total < 0) total = 0;

			// Atualiza o campo "Valor Pago" com o novo total formatado
			$('input[name="valor"]').val(total.toLocaleString('pt-br', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
		}

		function parseMoeda(valor) {
			if(!valor) return 0;
			// Remove pontos de milhar e troca vírgula por ponto
			let limpador = valor.replace(/\./g, '').replace(',', '.');
			return parseFloat(limpador) || 0;
		}
	});
</script>
@endsection