@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInDown">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<form method="get" action="/compras/pesquisa">
				<div class="row align-items-center">
					<div class="col-lg-5 col-xl-5">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<div class="input-icon">
									<input type="text" name="pesquisa" class="form-control" placeholder="Pesquisa por Produto" id="kt_datatable_search_query">
									<span>
										<i class="fa fa-search"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>
			</form>
			<br>
			<form method="get" action="/compras/filtro">
				<div class="row align-items-center">

					<div class="form-group col-lg-4 col-xl-4">
						<div class="row align-items-center">

							<div class="col-md-12 my-2 my-md-0">
								<label class="col-form-label">Fornecedor</label>

								<div class="input-icon">
									<input type="text" name="fornecedor" value="{{{ isset($fornecedor) ? $fornecedor : '' }}}" class="form-control" placeholder="Fornecedor" id="kt_datatable_search_query">
									<span>
										<i class="fa fa-search"></i>
									</span>
								</div>
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
								<input type="text" name="data_final" class="form-control" readonly value="{{{ isset($dataFinal) ? $dataFinal : '' }}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-2 col-sm-3">
						<label class="col-form-label">Nº NFe</label>
						<div class="">
							<div class="input-group">
								<input type="text" name="numero_nfe" class="form-control" value="{{{isset($numero_nfe) ? $numero_nfe : ''}}}" />
							</div>
						</div>
					</div>

					@if(empresaComFilial())
					{!! __view_locais_select_filtro("Local", isset($filial_id) ? $filial_id : '') !!}
					@endif

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
					</div>
				</div>
			</form>
            <br><br>
            @if(isset($compras) && count($compras) > 0)
                <a href="{{ url('/compraFiscal/syncDataEmissaoRetroativa') }}" class="btn btn-light">
                    <i class="fa fa-sync"></i> Sincronizar Data de Emissão Retroativa
                </a>
            @endif
            <br><br>
			<br>
			<h4>Lista de Compras</h4>
			<label>Total de registros: <strong class="text-info">{{ sizeof($compras) }}</strong></label>
			<div class="row">
				@foreach($compras as $c)

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<div class="card-title">
								<h3 style="width: 230px; font-size: 12px; height: 10px;" class="card-title">#{{$c->numero_sequencial}} - {{substr($c->fornecedor->razao_social, 0, 30)}}
								</h3>
							</div>

							<div class="card-toolbar">
								<div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">
									<a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										<i class="fa fa-ellipsis-h"></i>
									</a>
									<div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-right">
										<!--begin::Navigation-->
										<ul class="navi navi-hover">
											<li class="navi-header font-weight-bold py-4">
												<span class="font-size-lg">Ações:</span>
											</li>
											<li class="navi-separator mb-3 opacity-70"></li>
											<li class="navi-item">
												<a href="/compras/detalhes/{{ $c->id }}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-primary">Detalhes</span>
													</span>
												</a>
											</li>

											@if($c->verificaValidade())
											<li class="navi-item">
												<a href="/compras/setar-validade/{{ $c->id }}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-info">
															Setar Validade
														</span>
													</span>
												</a>
											</li>
											@endif

											@if($c->estado == 'NOVO' || $c->estado == 'REJEITADO')
											<li class="navi-item">
												<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/compras/delete/{{ $c->id }}" }else{return false} })' href="#!" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-danger">Excluir</span>
													</span>
												</a>
											</li>
											@endif
											<li class="navi-item">
												<a href="/compras/emitirEntrada/{{ $c->id }}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-success">Emitir NFe entrada</span>
													</span>
												</a>
											</li>

											<li class="navi-item">
												<a href="/compras/etiqueta/{{ $c->id }}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-dark">Gerar Etiqueta</span>
													</span>
												</a>
											</li>
											@if($c->nf == 0 && $c->estado == 'NOVO')
											<li class="navi-item">
												<a onclick='swal("Atenção!", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="/compraManual/editar/{{ $c->id }}" }else{return false} })' href="#!" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-warning">Editar</span>
													</span>
												</a>
											</li>
											@endif
										</ul>
									</div>
								</div>
							</div>
						</div>

						<div class="card-body">
							<div class="kt-widget__info">
								<span class="kt-widget__label">Valor:</span>
								<a target="_blank" class="kt-widget__data text-success">{{ number_format($c->valor, $casasDecimais, ',', '.') }}</a>
							</div>
							<div class="kt-widget__info">
								<span class="kt-widget__label">NFe de importação:</span>
								<a class="kt-widget__data text-success">{{ $c->nf > 0 ? $c->nf : '--' }}</a>
							</div>
							<div class="kt-widget__info">
								<span class="kt-widget__label">NFe de emissão:</span>
								<a class="kt-widget__data text-success">{{ $c->numero_emissao > 0 ? $c->numero_emissao : '--' }}</a>
							</div>
							<div class="kt-widget__info">
								<span class="kt-widget__label">Data de emissão:
								</span>
								<a class="kt-widget__data text-success">
									{{ $c->data_emissao != null ? \Carbon\Carbon::parse($c->data_emissao)->format('d/m/Y H:i') : '--' }}
								</a>
							</div>

							<div class="kt-widget__info">
								<span class="kt-widget__label">Estado:</span>
								@if($c->nf > 0)
								<span class="label label-xl label-inline label-light-info">IMPORTADO
								</span>
								@else

								@if($c->estado == 'NOVO')
								<span class="label label-xl label-inline label-light-primary">NOVO
								</span>
								@elseif($c->estado == 'APROVADO')
								<span class="label label-xl label-inline label-light-success">APROVADO
								</span>
								@elseif($c->estado == 'REJEITADO')
								<span REJEITADO="label label-xl label-inline label-light-warning">APROVADO
								</span>
								@elseif($c->estado == 'CANCELADO')
								<span REJEITADO="label label-xl label-inline label-light-dange">CANCELADO
								</span>
								@endif
								@endif
							</div>
							<div class="kt-widget__info">
								<span class="kt-widget__label">Usuário:</span>
								<a class="kt-widget__data text-success">{{ $c->usuario->nome }}</a>
							</div>

							@if(empresaComFilial())
							<div class="kt-widget__info">
								<span class="kt-widget__label">Local:</span>
								<a target="_blank" class="kt-widget__data text-success">
									{{ $c->filial_id ? $c->filial->descricao : 'Matriz' }}
								</a>
							</div>
							@endif
							<div class="kt-widget__info">
								<span class="kt-widget__label">Desconto:</span>
								<a target="_blank" class="kt-widget__data text-danger">{{ number_format($c->desconto, 2, ',', '.') }}</a>
							</div>
							<div class="kt-widget__info">
								<span class="kt-widget__label">Acréscimo:</span>
								<a target="_blank" class="kt-widget__data text-success">{{ number_format($c->acrescimo, 2, ',', '.') }}</a>
							</div>
							<div class="kt-widget__info">
								<span class="kt-widget__label">Data de cadastro:</span>
								<a target="_blank" class="kt-widget__data text-info">
									{{ \Carbon\Carbon::parse($c->created_at)->format('d/m/Y H:i:s')}}
								</a>
							</div>
						</div>
					</div>
				</div>
				@endforeach
			</div>
			<div class="d-flex justify-content-between align-items-center flex-wrap">
				<div class="d-flex flex-wrap py-2 mr-3">
					@if(isset($links))
					{{$compras->links()}}
					@endif
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-validade" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Compras com itens sem validade</h5>
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

@endsection
@section('javascript')
<script type="text/javascript" src="/js/validade.js"></script>
@endsection
