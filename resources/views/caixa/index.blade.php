@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<div class="col-lg-12" id="content">
				<!--begin::Portlet-->

				@if(sizeof($usuarios) > 0 && $config->caixa_por_usuario == 1)
				<form class="row" method="get" id="form-filtro" action="/caixa/filtroUsuario">
					<div class="form-group validated col-lg-3 col-md-2 col-sm-6">
						<label class="col-form-label text-left">Usuário</label>
						<select class="custom-select form-control" id="sel_usuario" name="usuario">
							@foreach($usuarios as $u)
							<option @if($usuario_id == $u->id) selected @endif value="{{$u->id}}">{{$u->nome}}</option>
							@endforeach
						</select>
					</div>
				</form>
				@endif
				@php
				$estado = $abertura == null ? false : true;
				@endphp
				<div class="row">
					<div class="col-6">
						<h2 class="card-title">Estado:
							@if($estado)
							<span class="label label-xl label-inline label-light-success">Caixa aberto</span>
							@if(empresaComFilial())
							@if($abertura->filial)
							<span class="label label-xl label-inline label-light-info">Local: 
								<strong class="ml-1"> {{ $abertura->filial->descricao}}</strong>
							</span>
							@endif
							@endif
							@else
							<span class="label label-xl label-inline label-light-danger">Caixa fechado</span>
							@endif

							@if($abertura && $abertura->conta)
							<span class="label label-xl label-inline label-light-dark">
								CONTA: {{ $abertura->conta->nome }}
							</span>
							@endif
						</h2>
					</div>
					@if($abertura)
					<div class="col-6">
						<h2 class="card-title">Data de abertura:
							<strong class="text-success">{{ __date($abertura->created_at) }}</strong>
						</h2>
					</div>
					@endif
				</div>

				<div class="row">
					<div class="col-xl-12">
						@if(!$estado)
						<a data-toggle="modal" href="#!" data-target="#modal-abrir-caixa" class="btn btn-light-success">
							<i class="las la-book-open"></i>
							ABRIR CAIXA
						</a>
						@endif

						@if($estado)
						<a data-toggle="modal" href="#!" data-target="#modal-supri" class="btn btn-light-info">
							<i class="las la-money-bill"></i>
							SUPRIMENTO DE CAIXA
						</a>

						<a data-toggle="modal" href="#!" data-target="#modal-sangria" class="btn btn-light-danger">
							<i class="las la-hand-holding-usd"></i>
							SANGRIA DE CAIXA
						</a>

						@endif

						<a href="/caixa/list" class="btn btn-light-primary">
							<i class="la la-list"></i>
							LISTA
						</a>
					</div>
				</div>

				<br>

				@if($estado)
				@if(sizeof($caixa) > 0)
				<h2 class="card-title">Total de vendas: <strong class="text-info">{{sizeof($caixa['vendas'])}}</strong></h2>

				<h2 class="card-title text-success">Valor de abertura: <strong class="">{{number_format($abertura->valor, 2, ',', '.')}}</strong></h2>
				@endif
				@endif

				@php
				$somaDinheiro = 0;
				@endphp
				@if(sizeof($caixa) > 0)
				<div class="row">
					<div class="col-xl-12">
						<h3 class="text-info">Total por tipo de pagamento:</h3>
						<div class="kt-section kt-section--first">
							<div class="kt-section__body">
								<div class="row">

									@foreach($caixa['somaTiposPagamento'] as $key => $tp)
									@if($tp > 0)
									<div class="col-sm-4 col-lg-4 col-md-6">
										<div class="card card-custom gutter-b">
											<div class="card-header">
												<h3 class="card-title">
													{{App\Models\VendaCaixa::getTipoPagamento($key)}}
												</h3>
											</div>

											@php
											if($key == '01') $somaDinheiro = $tp;
											@endphp
											<div class="card-body">
												<h4 class="text-success">R$ {{number_format($tp, 2, ',', '.')}}</h4>
											</div>

										</div>
									</div>
									@endif
									@endforeach

								</div>
							</div>
						</div>
					</div>
				</div>
				@endif


				<div class="row">
					<div class="col-xl-12">

						<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

							<table class="datatable-table" style="max-width: 100%; overflow: scroll">
								<thead class="datatable-head">
									<tr class="datatable-row" style="left: 0px;">
										
										<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Cliente</span></th>
										<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Data</span></th>
										<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Tipo de pagamento</span></th>

										<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Estado</span></th>
										<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Vendedor</span></th>

										<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">NFCe/NFe</span></th>

										<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Tipo</span></th>

										<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor</span></th>
									</tr>
								</thead>

								<tbody class="datatable-body">
									@php
									$soma = 0;
									$somaDesconto = 0;
									$somaAcrescimo = 0;
									
									@endphp

									@if(sizeof($caixa) > 0)

									@foreach($caixa['vendas'] as $v)

									<tr class="datatable-row" >
										
										<td class="datatable-cell"><span class="codigo" style="width: 150px;">{{ $v->cliente->razao_social ?? 'NAO IDENTIFCADO' }}</span>
										</td>
										<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i:s')}}</span>
										</td>
										<td class="datatable-cell">
											<span class="codigo" style="width: 200px;">

												@if($v->tipo == 'PDV')

												{!! $v->tiposDePagamento() !!}
												@else
												{!! $v->tiposDePagamento() !!}
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

										@if(!isset($v->cpf))
										<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->valor_total-$v->desconto+$v->acrescimo, 2, ',', '.') }}</span>
										</td>
										@else
										<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->valor_total, 2, ',', '.') }}</span>
										</td>
										@endif

									</tr>
									
									@if($v->estado != 'CANCELADO')
									@php
									if(!isset($v->cpf)){
										$soma += $v->valor_total-$v->desconto+$v->acrescimo;
									}else{
										if(!$v->rascunho && !$v->consignado){
											$soma += $v->valor_total;
										}
									}

									$somaDesconto += $v->desconto;
									$somaAcrescimo += $v->acrescimo;
									@endphp

									@endif
									
									@endforeach
									@endif
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<br>

				@php
				$somaSuprimento = 0;
				$somaSangria = 0;
				@endphp


				@if(sizeof($caixa) > 0)
				<div class="row">
					<div class="col-12 col-xl-6">
						<div class="card card-custom gutter-b">

							<div class="card-body">
								<h2 class="card-title">Suprimentos:</h2>

								@if(sizeof($caixa['suprimentos']) > 0)
								@foreach($caixa['suprimentos'] as $s)
								<h4>R$ {{number_format($s->valor, 2, ',', '.')}}
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

								@if(sizeof($caixa['sangrias']) > 0)
								@foreach($caixa['sangrias'] as $s)
								<h4>R$ {{number_format($s->valor, 2, ',', '.')}}
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
				@endif

				@if(sizeof($caixa) == 0)
				<h2 class="text-danger text-center">NÃO É POSSÍVEL FECHAR SEM NENHUMA VENDA</h2>
				@else

				@if(sizeof($nfse['notas']) > 0)
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
									@foreach($nfse['notas'] as $n)
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
										<td>{{ moeda($nfse['notas']->sum('valor_total')) }}</td>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
				<hr>
				@endif

				@if(sizeof($contasRecebidas['contas']) > 0)
				<div class="card">
					<div class="card-header">
						<h5 class="text-white">Contas recebidas</h5>
					</div>
					<div class="card-body">
						<div class="table-responsive">
							<table class="table">
								<thead>
									<tr>
										<th>Cliente</th>
										<th>Data de vencimento</th>
										<th>Data de recebimento</th>
										<th>Valor</th>
										<th>Tipo de pagamento</th>
									</tr>
								</thead>
								<tbody>
									@foreach($contasRecebidas['contas'] as $c)
									<tr>
										<td>
											@if($c->venda_id != null || $c->venda_caixa_id != null)
											@if($c->venda_id != null)
											{{ $c->venda->cliente->razao_social }}
											@else
											@if($c->vendaCaixa->cliente)
											{{ $c->vendaCaixa->cliente->razao_social }}
											@else
											--
											@endif
											@endif
											@else
											@if($c->cliente_id != null)
											{{ $c->cliente->razao_social }}
											@else
											--
											@endif
											@endif
										</td>
										<td>{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y')}}</td>
										<td>{{ \Carbon\Carbon::parse($c->data_recebimento)->format('d/m/Y H:i')}}</td>
										<td>{{ moeda($c->valor_recebido) }}</td>
										<td>
											@if($c->tipo_pagamento)
											{{$c->tipo_pagamento}}
											@else
											--
											@endif
										</td>
									</tr>

									@if($c->tipo_pagamento == 'Dinheiro')
									@php
									$somaDinheiro += $c->valor_recebido;
									@endphp
									@endif
									@endforeach
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<hr>
				@endif

				@if(sizeof($contasPagas['contas']) > 0)
				<div class="card">
					<div class="card-header">
						<h5 class="text-white">Contas pagas</h5>
					</div>
					<div class="card-body">
						<div class="table-responsive">
							<table class="table">
								<thead>
									<tr>
										<th>Fornecedor</th>
										<th>Data de vencimento</th>
										<th>Data de pagamento</th>
										<th>Valor</th>
										<th>Tipo de pagamento</th>
									</tr>
								</thead>
								<tbody>
									@foreach($contasPagas['contas'] as $c)
									<tr>
										<td>
											@if($c->compra)
											{{ $c->compra->fornecedor->razao_social }}

											{{ $c->compra->fornecedor->cpf_cnpj }}

											@else
											@if($c->fornecedor)
											{{ $c->fornecedor->razao_social }}
											{{ $c->fornecedor->cpf_cnpj }}
											@else
											--
											@endif
											@endif
										</td>
										<td>{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y')}}</td>
										<td>{{ \Carbon\Carbon::parse($c->data_pagamento)->format('d/m/Y H:i')}}</td>
										<td>{{ moeda($c->valor_pago) }}</td>
										<td>
											@if($c->tipo_pagamento)
											{{$c->tipo_pagamento}}
											@else
											--
											@endif
										</td>
									</tr>

									@if($c->tipo_pagamento == 'Dinheiro')
									@php
									$somaDinheiro -= $c->valor_pago;
									@endphp
									@endif
									@endforeach
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<hr>
				@endif
				<div class="row">
					<div class="col-lg-6 col-12">
						<h2 class="text-warning">Soma de vendas: 
							<strong>R$ {{number_format($soma, 2, ',', '.')}}</strong>
						</h2>

						<h2 class="text-info">Total caixa dinheiro: 

							<strong>
								R$ {{number_format(($somaDinheiro + $somaSuprimento + $abertura->valor) - $somaSangria, 2, ',', '.')}}
							</strong>
						</h2>

						@isset($nfse['notas'])
						<h2 class="text-dark">Total de NFSe: 
							<strong>
								R$ {{ moeda($nfse['notas']->sum('valor_total')) }}
							</strong>
						</h2>
						@endif

						<h2 class="text-success">Total geral: 
							<strong>
								R$ {{number_format(($soma + $somaSuprimento + $abertura->valor + $contasRecebidas['soma'] - $contasPagas['soma']) - $somaSangria + (isset($nfse['notas']) ? $nfse['notas']->sum('valor_total') : 0), 2, ',', '.')}}
							</strong>
						</h2>
					</div>
					<div class="col-lg-6 col-12">
						<h2 class="text-success">Soma de contas recebidas: 
							<strong>R$ {{number_format($contasRecebidas['soma'], 2, ',', '.')}}</strong>
						</h2>
						<h2 class="text-warning">Soma de contas pagas: 
							<strong>R$ {{number_format($contasPagas['soma'], 2, ',', '.')}}</strong>
						</h2>
						<h2 class="text-danger">Soma de descontos: 
							<strong>R$ {{number_format($somaDesconto, 2, ',', '.')}}</strong>
						</h2>
						<h2 class="text-info">Soma de acréscimos: 
							<strong>R$ {{number_format($somaAcrescimo, 2, ',', '.')}}</strong>
						</h2>
					</div>
				</div>
				@endif

				@if($soma > 0)
				@if(sizeof($contasEmpresa) > 0)
				<div class="row">
					<div class="col-12">
						<a href="{{ route('caixa-empresa.index') }}" @if(sizeof($caixa) == 0) disabled @endif class="btn btn-lg btn-danger">
							<i class="la la-times"></i>
							Fechar Caixa
						</a>
					</div>
				</div>
				@else
				<div class="row">
					<div class="col-12">
						<form method="post" id="form-caixa" action="/frenteCaixa/fechar">
							@csrf
							<input type="hidden" name="valor_dinheiro_caixa" id="valor_dinheiro_caixa">
							<input type="hidden" name="abertura_id" value="{{$abertura != null ? $abertura->id : 0}}">
							<input type="hidden" name="redirect" value="/caixa">
							<button id="btn-fechar-caixa" type="button" @if(sizeof($caixa) == 0) disabled @endif class="btn btn-lg btn-danger">
								<i class="la la-times"></i>
								Fechar Caixa
							</button>
						</form>
					</div>
				</div>
				@endif
				@endif

			</div>
		</div>
	</div>
</div>

<input type="hidden" id="_token" value="{{csrf_token()}}" name="">

<div class="modal fade" id="modal-fechar-caixa" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Fechamento de Caixa</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<h4 class="ml-3">Deseja fechar o caixa?</h4>
					<div class="form-group validated col-sm-12 col-lg-12">
						<label class="col-form-label" id="">Valor em dinheiro gaveta (opcional)</label>
						<div class="">
							<input type="text" id="valor_dinheiro" name="valor_dinheiro" class="form-control money" value="">
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" onclick="fecharCaixa()" class="btn btn-light-success font-weight-bold">Fechar Caixa</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-abrir-caixa" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Abertura de caixa</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">

					{!! __view_locais_select_pdv() !!}

					<div class="form-group validated col-sm-12 col-lg-12">
						<label class="col-form-label" id="">Valor</label>
						<div class="">
							<input type="text" id="valor" name="valor" class="form-control money" value="">
						</div>
					</div>

					@if(sizeof($contasEmpresa) > 0)
					<div class="form-group validated col-12">
						<label>Conta</label>
						<select required id="conta_id" name="conta_id" class="select2-custom custom-select" style="width: 100%">
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
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="button" onclick="abrirCaixa()" class="btn btn-light-success font-weight-bold">Abrir</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-supri" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-md" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">SUPRIMENTO DE CAIXA</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-lg-4 col-6">
						<label class="col-form-label" id="">Valor</label>
						<input type="text" placeholder="Valor" id="valor_suprimento" name="valor_sangria" class="form-control money" value="">
					</div>

					<div class="form-group validated col-lg-8 col-12">
						<label class="col-form-label" id="">Tipo</label>
						<select id="tipo_suprimento" class="custom-select">
							<option value="">Selecione</option>
							@foreach(\App\Models\SuprimentoCaixa::tiposPagamento() as $key => $t)
							<option value="{{ $key }}">{{ $t }}</option>
							@endforeach
						</select>
					</div>

					@if(sizeof($contasEmpresa) > 0)
					<div class="form-group validated col-12">
						<label class="col-form-label">Conta</label>
						<select required id="conta_suprimento_id" class="select2-custom custom-select" style="width: 100%">
							<option value=""></option>
							@foreach($contasEmpresa as $c)
							<option value="{{ $c->id }}">
								{{ $c->nome }}
							</option>
							@endforeach
						</select>
					</div>
					@endif

					<div class="form-group validated col-sm-12 col-lg-12 col-12">
						<label class="col-form-label" id="">Observação</label>
						<input type="text" placeholder="Observação" id="obs_suprimento" name="obs" class="form-control" value="">
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button style="width: 100%" type="button" onclick="suprimentoCaixa()" class="btn btn-success font-weight-bold">OK</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-sangria" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-md" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">SANGRIA DE CAIXA</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-sm-6 col-lg-4 col-12">
						<label class="col-form-label" id="">Valor</label>
						<input type="text" placeholder="Valor" id="valor_sangria" name="valor_sangria" class="form-control" value="">
					</div>
					
					@if(sizeof($contasEmpresa) > 0)
					<div class="form-group validated col-lg-8 col-12">
						<label class="col-form-label">Conta</label>
						<select required id="conta_sagria_id" class="select2-custom custom-select" style="width: 100%">
							<option value=""></option>
							@foreach($contasEmpresa as $c)
							<option value="{{ $c->id }}">
								{{ $c->nome }}
							</option>
							@endforeach
						</select>
					</div>
					@endif

					<div class="form-group validated col-sm-12 col-lg-12 col-12">
						<label class="col-form-label" id="">Observação</label>
						<input type="text" placeholder="Observação" id="obs_sangria" name="obs" class="form-control" value="">
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button style="width: 100%" type="button" onclick="sangriaCaixa()" class="btn btn-success font-weight-bold">OK</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-pagamentos" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">DETALHES PAGAMENTO <strong class="venda-id"></strong></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				
			</div>
		</div>
	</div>
</div>

@section('javascript')
<script type="text/javascript">
	$('#sel_usuario').change(() => {
		$('#form-filtro').submit()
	})

	$("#btn-fechar-caixa").click(() => {
		$('#modal-fechar-caixa').modal('show')
	})

	function fecharCaixa(){
		let valor = $('#valor_dinheiro').val()
		$('#valor_dinheiro_caixa').val(valor)
		$('#form-caixa').submit()
	}

	function detalhePagamento(id){

		$.get(path + 'vendas/detalhe-pagamento/'+id)
		.done((res) => {
			console.log(res)
			$('#modal-pagamentos').modal('show')
			$('#modal-pagamentos .modal-body').html(res)
		})
		.fail((err) => {
			console.log(err)
			swal("Erro", "Algo deu errado", "error")
		})
	}

	function detalhePagamentoPdv(id){
		console.clear()
		$.get(path + 'vendasCaixa/detalhe-pagamento/'+id)
		.done((res) => {
			console.log(res)
			$('#modal-pagamentos').modal('show')
			$('#modal-pagamentos .modal-body').html(res)
		})
		.fail((err) => {
			console.log(err)
			swal("Erro", "Algo deu errado", "error")
		})
	}
</script>
@endsection
@endsection
