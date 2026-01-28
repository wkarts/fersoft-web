@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<div class="col-lg-12" id="content">
				<!--begin::Portlet-->
				<br>
				<div class="row">
					<div class="col-lg-6">
						<h3 class="">Total de vendas: <strong class="text-info">{{sizeof($vendas)}}</strong></h3>

						<h3 class="text-success">Valor de abertura: <strong class="">{{number_format($abertura->valor, 2, ',', '.')}}</strong></h3>
					</div>
					<div class="col-lg-6">

						<h5 class="card-title text-primary">Data de abertura:
							<strong class="text-primary">{{ \Carbon\Carbon::parse($abertura->created_at)->format('d/m/Y H:i') }}</strong>
						</h5>
						<h5 class="card-title text-danger">Data de fechamento:
							<strong class="text-danger">{{ \Carbon\Carbon::parse($abertura->updated_at)->format('d/m/Y H:i') }}</strong>
						</h5>
					</div>
				</div>
				@php $valorEmDinheiro = 0; @endphp
				<div class="row">
					<div class="col-xl-12">
						<h3 class="text-info">Total por tipo de pagamento:</h3>
						<div class="kt-section kt-section--first">
							<div class="kt-section__body">
								<div class="row">

									@foreach($somaTiposPagamento as $key => $tp)
									@if($tp > 0)

									<div class="col-sm-4 col-lg-4 col-md-6">
										<div class="card card-custom gutter-b">
											<div class="card-header">
												<h3 class="card-title">
													{{App\Models\VendaCaixa::getTipoPagamento($key)}}
												</h3>
											</div>
											<div class="card-body">
												<h4 class="text-success">R$ {{number_format($tp, 2, ',', '.')}}</h4>
											</div>

											@php if($key == '01') $valorEmDinheiro = $tp;  @endphp

										</div>
									</div>
									@endif
									@endforeach

								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xl-12">

						<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

							<table class="datatable-table" style="max-width: 100%; overflow: scroll">
								<thead class="datatable-head">
									<tr class="datatable-row" style="left: 0px;">

										<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Cliente</span></th>
										<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Data</span></th>
										<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Tipo de pagamento</span></th>

										<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Estado</span></th>
										<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Vendedor</span></th>

										<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">NFCe/NFe</span></th>

										<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Tipo</span></th>
										<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Desconto</span></th>
										<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor</span></th>
									</tr>
								</thead>

								<tbody class="datatable-body">
									@php
									$soma = 0;
									$somaDesconto = 0;
									$somaAcrescimo = 0;

									@endphp

									@foreach($vendas as $v)

									<tr class="datatable-row" >

										<td class="datatable-cell"><span class="codigo" style="width: 150px;">{{ $v->cliente->razao_social ?? 'NAO IDENTIFCADO' }}</span>
										</td>
										<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i:s')}}</span>
										</td>
										<td class="datatable-cell">
											<span class="codigo" style="width: 100px;">

												@if($v->tipo_pagamento == '99')

												Outros
												@else
												{{$v->getTipoPagamento($v->tipo_pagamento)}}
												@endif

											</span>
										</td>
										<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ $v->estado }}</span>
										</td>

										<td class="datatable-cell">
											<span class="codigo" style="width: 100px;">
												{{ $v->vendedor_setado ? $v->vendedor_setado->nome : '--' }}
											</span>
										</td>

										@if($v->tipo == 'PDV')
										<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ $v->NFcNumero > 0 ? $v->NFcNumero : '--' }}</span>
										</td>
										@else
										<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ $v->NfNumero > 0 ? $v->NfNumero : '--' }}</span>
										</td>
										@endif

										<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ $v->tipo }}</span>
										</td>
										<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->desconto, 2, ',', '.') }}</span>
										</td>
										@if(!isset($v->cpf))
										<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->valor_total-$v->desconto+$v->acrescimo, 2, ',', '.') }}</span>
										</td>
										@else
										<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->valor_total, 2, ',', '.') }}</span>
										</td>
										@endif

									</tr>

									@php
									if(!$v->consignado && !$v->rascunho && $v->estado != 'CANCELADO')
									if(!isset($v->cpf)){
										$soma += $v->valor_total-$v->desconto+$v->acrescimo;
									}else{
										$soma += $v->valor_total;
									}

									$somaDesconto += $v->desconto;
									$somaAcrescimo += $v->acrescimo;
									@endphp

									@endforeach

								</tbody>
							</table>
						</div>
					</div>
				</div>
				<br>

				<h2 class="text-info">Soma de vendas:
					<strong>{{number_format($soma, 2, ',', '.')}}</strong>
				</h2>

				@php
				$somaSuprimento = 0;
				$somaSangria = 0;
				@endphp

				@if(sizeof($nfse) > 0)
				<hr>
				<div class="card">
					<div class="card-header">
						<h5 class="text-white">Notas fiscais de serviço</h5>
					</div>
					<div class="card-body">
						<div class="table-responsive">
							<table class="table">
								<thead>
									<tr>
										<th>Tomador</th>
										<th>Data</th>
										<th>Número</th>
										<th>Estado</th>
										<th>Valor total</th>
									</tr>
								</thead>
								<tbody>
									@foreach($nfse as $n)
									<tr>
										<td>{{ $n->razao_social }}</td>
										<td>{{ __date($n->created_at) }}</td>
										<td>{{ $n->numero_nfse }}</td>
										<td>{{ strtoupper($n->estado) }}</td>
										<td>{{ moeda($n->valor_total) }}</td>

									</tr>

									@endforeach
								</tbody>
								<tfoot>
									<tr>
										<td colspan="4">Total</td>
										<td>{{ moeda($nfse->sum('valor_total')) }}</td>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
				<hr>
				@endif

				<div class="row">
					<div class="col-12 col-xl-6">
						<div class="card card-custom gutter-b">

							<div class="card-body">
								<h2 class="card-title">Suprimentos:</h2>

								@if(sizeof($suprimentos) > 0)
								@foreach($suprimentos as $s)
								<h4>Valor: R$ {{number_format($s->valor, 2, ',', '.')}}
									<a class="btn" href="/suprimentoCaixa/imprimir/{{$s->id}}" target="_blank">
										<i class="la la-print text-success"></i>
									</a>
								</h4>
								@php
								$somaSuprimento += $s->valor;
								@endphp
								@endforeach
								@else
								<h4>R$ 0,00</h4>
								@endif
							</div>
						</div>
					</div>

					<div class="col-12 col-xl-6">
						<div class="card card-custom gutter-b">

							<div class="card-body">
								<h2 class="card-title">Sangrias:</h2>

								@if(sizeof($sangrias) > 0)
								@foreach($sangrias as $s)
								<h4>Valor: R$ {{number_format($s->valor, 2, ',', '.')}}
									<a class="btn" href="/sangriaCaixa/imprimir/{{$s->id}}" target="_blank">
										<i class="la la-print text-success"></i>
									</a>
								</h4>
								@php
								$somaSangria += $s->valor;
								@endphp
								@endforeach
								@else
								<h4>R$ 0,00</h4>
								@endif
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-12">
						<div class="card card-custom gutter-b">
							<div class="card-body">
								<div class="row">

									<div class="col-lg-6 col-12">
										<h3 class="text-primary">Soma da vendas: <strong>{{number_format($soma, 2, ',', '.')}}</strong></h3>

										<h3 class="text-danger">Soma de sangria: <strong>{{number_format($somaSangria, 2, ',', '.')}}</strong></h3>

										<h3 class="text-success">Soma de suprimento: <strong>{{number_format($somaSuprimento, 2, ',', '.')}}</strong></h3>

										@if(sizeof($nfse) > 0)
										<h3 class="text-dark">Soma NFSe:
											<strong>{{ moeda($nfse->sum('valor_total')) }}</strong>
										</h3>
										@endif

										<h3 class="text-info">Resultado caixa: <strong>{{number_format($abertura->valor + $valorEmDinheiro - $somaSangria + $contasRecebidas + $soma + (sizeof($nfse) > 0 ? $nfse->sum('valor_total') : 0), 2, ',', '.')}}</strong></h3>

									</div>

									<div class="col-lg-6 col-12">

										<h3 class="text-success">Soma de contas recebidas:
											<strong>{{number_format($contasRecebidas, 2, ',', '.')}}</strong>
										</h3>

										<h3 class="text-warning">Valor em dinheiro gaveta: <strong>{{number_format($abertura->valor_dinheiro_caixa, 2, ',', '.')}}</strong></h3>

										<h3 class="text-danger">Soma de descontos:
											<strong>{{number_format($somaDesconto, 2, ',', '.')}}</strong>
										</h3>

										<h3 class="text-info">Soma de acréscimo:
											<strong>{{number_format($somaAcrescimo, 2, ',', '.')}}</strong>
										</h3>


									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-12">

                        <a href="/caixa/imprimir/{{ $abertura->id }}"
                           class="btn btn-info"
                           target="_blank"
                           rel="noopener noreferrer">
                            <i class="la la-print"></i>
                            Imprimir em A4
                        </a>

                        <a href="/caixa/imprimir80/{{ $abertura->id }}"
                           class="btn btn-primary"
                           target="_blank"
                           rel="noopener noreferrer">
                            <i class="la la-print"></i>
                            Imprimir em 80mm
                        </a>

                        <!--
						<a href="/caixa/imprimir/{{$abertura->id}}" class="btn btn-info">
							<i class="la la-print"></i>
							Imprimir em A4
						</a>

						<a href="/caixa/imprimir80/{{$abertura->id}}" class="btn btn-primary">
							<i class="la la-print"></i>
							Imprimir em 80mm
						</a>
                        -->
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

@endsection
