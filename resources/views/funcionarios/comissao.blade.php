@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-people.css') }}?v={{ filemtime(public_path('css/ui-people.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-people.css') }}?v={{ filemtime(public_path('css/ui-people.css')) }}">
@endpush
@section('content')

<div class="ui-people ui-people-commission">
    <header class="ui-people-heading"><div><h1>Comissões</h1></div></header>
	<div class="ui-people-panel">
		<div id="kt_user_profile_aside">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form method="get" action="/funcionarios/comissaoFiltro" class="ui-people-filters">
				<div class="row align-items-center">

					<div class="form-group col-lg-3 col-md-4 col-sm-6">
						<label class="col-form-label">Vendedor</label>
						<div class="">
							<div class="input-group">
								<select class="form-control custom-select" name="funcionario_id">
									<option value="--">Todos</option>

									@foreach($funcionarios as $f)
									<option value="{{$f->id}}">{{$f->nome}}</option>
									@endforeach
								</select>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Inicial</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_inicial" class="form-control" readonly value="{{{ isset($dataInicial) ? $dataInicial : '' }}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Final</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_final" class="form-control" readonly value="{{{isset($dataFinal) ? $dataFinal : ''}}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Estado</label>
						<div class="">
							<div class="input-group date">
								<select class="custom-select form-control" id="estado" name="status">
									<option @if(isset($status) && $status == 'todos') selected @endif value="--">TODOS</option>
									<option @if(isset($status) && $status == 'pago') selected @endif value="1">PAGO</option>
									<option @if(isset($status) && $status == 'pendente') selected @endif value="0">PENDENTE</option>
								</select>
							</div>
						</div>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button class="btn ui-people-primary">Pesquisa</button>
					</div>
				</div>
			</form>



			<div class="row">
				<div class="col-lg-3 col-xl-3 col-12">
					<button style="width: 100%" class="btn btn-success" disabled id="btn-pagar" onclick="pagar()">
						<i class="la la-money"></i>
						Pagar Comissão(s) <strong id="total-select">R$ 0,00</strong></button>
					</div>
				</div>

				<div class="row">
					<div class="col-xl-12">

					<div id="kt_datatable" class="ui-people-table-scroll">

							<table class="ui-people-table">
								<thead>
									<tr>
										<th data-field="OrderID"><span></span></th>
										<th data-field="OrderID"><span>Vendedor</span></th>
										<th data-field="OrderID"><span>% Comissão</span></th>
										<th data-field="OrderID"><span>Valor da venda</span></th>
										<th data-field="OrderID"><span>Valor da comissão</span></th>
										<th data-field="Country"><span>Tipo</span></th>
										<th data-field="ShipDate"><span>Estado</span></th>

										<th data-field="CompanyName"><span>Data</span></th>

										<th data-field="CompanyName"><span>Data de pagamento</span></th>
										<th data-field="CompanyName"><span>Ações</span></th>
									</tr>
								</thead>

								<tbody id="body">

									<?php 
									$soma = 0;
									$somaVendas = 0;
									?>
									@foreach($comissoes as $e)
									<tr>
										<td id="checkbox">

											@if(!$e->status)
											<p style="width: 80px;">
												<input type="checkbox" class="check" id="test_{{$e->id}}" />
												<label for="test_{{$e->id}}"></label>
											</p>
											@else

											<p style="width: 80px;">

											</p>

											@endif

										</td>

										<td style="display: none">
											<span class="codigo" id="id">
												{{ $e->id }}
											</span>
										</td>
										<td>
											<span class="codigo">
												{{ $e->funcionario->nome }}
											</span>
										</td>

										<td><span class="codigo" >{{ number_format($e->funcionario->percentual_comissao, 2, ',', '.') }}</span>
										</td>
										
										<td>
											<span class="codigo">
												@if($e->valodDaVenda)
												{{ moeda($e->valodDaVenda->valor_total) }}
												@else
												--
												@endif
											</span>
										</td>

										<?php 
										if($e->valodDaVenda){
											$somaVendas += $e->valodDaVenda->valor_total;
										}
										?>

										<td><span class="codigo" id="">{{ moeda($e->valor) }}</span>
										</td>

										<td>
											<span class="codigo" id="">
												{{$e->tipo()}}
											</span>
										</td>

										<td>
											<span class="codigo">
												@if($e->status == true)
												<span class="label label-xl label-inline label-light-success">Pago</span>

												@else
												<span class="label label-xl label-inline label-light-danger">Pendente</span>

												@endif
											</span>
										</td>

										<td><span class="codigo">{{ \Carbon\Carbon::parse($e->created_at)->format('d/m/Y H:i')}}</span>
										</td>
										<td>
											<span class="codigo">
												@if($e->status == true)
												{{ \Carbon\Carbon::parse($e->updated_at)->format('d/m/Y H:i')}}
												@else
												--
												@endif
											</span>
										</td>
										<td><span class="codigo">

											<a class="btn btn-sm ui-people-danger" title="REMOVER" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/funcionarios/comissaoDelte/{{$e->id}}" }else{return false} })' href="#!">
												<i class="la la-trash"></i>
											</a>
										</td>

									</tr>
									<?php 
									$soma += $e->valor;
									?>
									@endforeach

								</tbody>
							</table>
						</div>
						<div class="row">
							<h3>Soma Comissão: <strong class="text-info">R$ {{number_format($soma, 2, ',', '.')}}</strong></h3>

						</div>

						<div class="row">
							<h3>Soma das vendas: <strong class="text-success">R$ {{number_format($somaVendas, 2, ',', '.')}}</strong></h3>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>

@endsection	
