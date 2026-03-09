@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/contasReceber/receber" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{$conta->id}}">

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Receber Conta</h3>
						</div>
					</div>
					@csrf
					<div class="row">

						<div class="col-xl-12">

							<div class="row">
								<div class="col s12">
									@if($conta->compra_id != null)
									<h5>Fornecedor: <strong>{{$conta->compra->fornecedor->razao_social}}</strong></h5>
									@endif

									<h5>Data de registro: <strong>{{ \Carbon\Carbon::parse($conta->data_registro)->format('d/m/Y')}}</strong></h5>
									<h5>Data de vencimento: <strong>{{ \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y')}}</strong></h5>
									<h5>Valor: <strong>{{ number_format($conta->valor_integral, 2, ',', '.') }}</strong></h5>
									<h5>Categoria: <strong>{{$conta->categoria->nome}}</strong></h5>
									<h5>Referencia: <strong>{{$conta->referencia}}</strong></h5>
									<h5>Observação: <strong>{{$conta->observacao}}</strong></h5>
								</div>
							</div>

							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										
										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Valor Recebido</label>
											<div class="">
												<input required type="text" class="form-control @if($errors->has('valor')) is-invalid @endif money" name="valor" value="">
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
<div class="row">
    <div class="form-group col-lg-4">
        <label>Juros cobrados (R$)</label>
        <input type="text" name="juros" id="juros" class="form-control money" value="0,00">
    </div>
    <div class="form-group col-lg-4">
        <label>Multa cobrada (R$)</label>
        <input type="text" name="multa" id="multa" class="form-control money" value="0,00">
    </div>
    <div class="form-group col-lg-4">
        <label>Desconto concedido (R$)</label>
        <input type="text" name="desconto" id="desconto" class="form-control money" value="0,00">
    </div>
</div>
										<div class="form-group validated col-sm-12 col-lg-4">
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
									<span class="">Receber</span>
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

<script>
    // Função para converter o valor formatado (1.200,00) em número real (1200.00)
    function parseMoeda(valor) {
        if (!valor) return 0;
        // Remove pontos de milhar e troca a vírgula decimal por ponto
        let limpo = valor.replace(/\./g, '').replace(',', '.');
        return parseFloat(limpo) || 0;
    }

    // Função para formatar o número de volta para o padrão brasileiro (1.200,00)
    function formatarMoeda(valor) {
        return valor.toLocaleString('pt-br', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Executa o cálculo
    function calcularTotal() {
        let integral = parseMoeda($('#valor_integral').val());
        let juros    = parseMoeda($('#juros').val());
        let multa    = parseMoeda($('#multa').val());
        let desconto = parseMoeda($('#desconto').val());

        // A lógica que você pediu: valor + juros + multa - desconto
        let total = (integral + juros + multa) - desconto;

        // Atualiza o campo "Valor Recebido" na tela
        $('#valor_recebido').val(formatarMoeda(total));
    }

    // Monitora os campos para disparar o cálculo automaticamente
    $(document).ready(function() {
        $('#juros, #multa, #desconto').on('keyup blur', function() {
            calcularTotal();
        });
    });
</script>