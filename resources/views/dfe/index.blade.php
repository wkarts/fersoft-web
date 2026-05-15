@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-body">
        <form method="get" action="/dfe/filtro">
            <div class="row align-items-end">
                {{-- DATA INICIAL --}}
                <div class="form-group col-lg-2 col-md-6">
                    <label class="col-form-label">Data Inicial</label>
                    <div class="input-group date">
                        <input type="text" name="data_inicial" class="form-control datepicker" readonly value="{{ $data_inicial }}" id="kt_datepicker_3" />
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i class="la la-calendar"></i>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- DATA FINAL --}}
                <div class="form-group col-lg-2 col-md-6">
                    <label class="col-form-label">Data Final</label>
                    <div class="input-group date">
                        <input type="text" name="data_final" class="form-control datepicker" readonly value="{{ $data_final }}" id="kt_datepicker_3" />
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i class="la la-calendar"></i>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- STATUS IMPORTAÇÃO --}}
                <div class="form-group col-lg-2 col-md-4">
                    <label class="col-form-label">Importação</label>
                    <select name="status_importacao" class="form-control custom-select">
                        <option value="todos" {{ $status_importacao == 'todos' ? 'selected' : '' }}>TODOS</option>
                        <option value="importadas" {{ $status_importacao == 'importadas' ? 'selected' : '' }}>IMPORTADAS</option>
                        <option value="pendentes" {{ $status_importacao == 'pendentes' ? 'selected' : '' }}>PENDENTES</option>
                    </select>
                </div>

                {{-- TIPO --}}
                <div class="form-group col-lg-2 col-md-4">
                    <label class="col-form-label">Tipo</label>
                    <select name="tipo" class="form-control custom-select">
                        <option value="--">TODOS</option>
                        <option value="1" {{ $tipo == '1' ? 'selected' : '' }}>Ciência</option>
                        <option value="2" {{ $tipo == '2' ? 'selected' : '' }}>Confirmada</option>
                        <option value="3" {{ $tipo == '3' ? 'selected' : '' }}>Desconhecida</option>
                        <option value="4" {{ $tipo == '4' ? 'selected' : '' }}>Não Realizada</option>
                    </select>
                </div>

                {{-- UNIDADE --}}
                <div class="form-group col-lg-2 col-md-4">
                    <label class="col-form-label">Unidade</label>
                    <select name="filial_id" class="form-control custom-select">
                        <option value="">Todas</option>
                        <option value="matriz" {{ $filial_id == 'matriz' ? 'selected' : '' }}>Matriz</option>
                        @foreach($filiais as $f)
                            <option value="{{$f->id}}" {{ $filial_id == $f->id ? 'selected' : '' }}>{{$f->descricao}}</option>
                        @endforeach
                    </select>
                </div>

                {{-- FORNECEDOR --}}
                <div class="form-group col-lg-3 col-md-6">
                    <label class="col-form-label">Fornecedor</label>
                    <input type="text" name="fornecedor" class="form-control" value="{{ $fornecedor ?? '' }}" placeholder="Nome...">
                </div>

                {{-- N° NOTA --}}
                <div class="form-group col-lg-2 col-md-6">
                    <label class="col-form-label">Nº Nota</label>
                    <input type="text" name="nNf" class="form-control" value="{{ $nNf ?? '' }}" placeholder="Número...">
                </div>

                {{-- BOTÃO FILTRAR --}}
                <div class="col-lg-2 mb-2">
                    <button type="submit" class="btn btn-primary font-weight-bold btn-block">
                        <i class="la la-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
          

<h4 class="mt-2 mb-2 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Manifesto</h4>

<div class="row mb-4"> 
    <div class="col-md-12">
        <a href="/dfe/novaConsulta" class="btn btn-success btn-sm @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
            <i class="la la-refresh"></i>
            Nova Consulta
        </a>

        @if($busca_automatica)
        <a href="/dfe/logs" class="btn btn-warning btn-sm float-right @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
            <i class="la la-file"></i>
            Logs Automáticos
        </a>
        @endif
    </div>
</div>

<h5 class="mb-2 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Total de registros: <strong style="color: green">{{sizeof($docs)}}</strong></h5>

<input type="hidden" value="{{json_encode($docs)}}" id="docs">

<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
    <div class="col-sm-12">
        <div class="wizard wizard-3" id="kt_wizard_v3">
            <div class="wizard-nav">
                {{-- Ajustado py-lg-1 para diminuir o espaço das abas Tabela/Grade --}}
                <div class="wizard-steps px-8 py-2 px-lg-15 py-lg-1"> 
                    <div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
									</div>
								</div>

							</div>
						</div>
						<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

							<form class="form fv-plugins-bootstrap fv-plugins-framework" id="kt_form">
								<div class="pb-5" data-wizard-type="step-content">

									<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
										<div class="row">
											<div class="col-xl-12">

												<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

													<table class="datatable-table" style="max-width: 100%;">
                                                    <thead class="datatable-head">
                                                        <tr class="datatable-row" style="left: 0px;">
                                                            <th class="datatable-cell"><span style="width: 150px;">FORNECEDOR</span></th>
                                                            <th class="datatable-cell"><span style="width: 80px;">Nº NOTA</span></th>
                                                            <th class="datatable-cell"><span style="width: 90px;">VALOR</span></th>
                                                            <th class="datatable-cell"><span style="width: 80px;">EMISSÃO</span></th>
                                                            <th class="datatable-cell"><span style="width: 100px;">SITUAÇÃO</span></th>
                                                            <th class="datatable-cell"><span style="width: 80px;">ERP / FIN.</span></th>
                                                            <th class="datatable-cell"><span style="width: 120px;">AÇÕES</span></th> {{-- Aumentei um pouco a largura para caber os 4 botões --}}
                                                            <th class="datatable-cell"><span style="width: 180px;">CHAVE DE ACESSO</span></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="datatable-body">
                                                        @foreach($docs as $d)
                                                        <tr class="datatable-row" style="left: 0px;">
                                                            <td class="datatable-cell"><span style="width: 150px; white-space: normal;">{{$d->nome}}</span></td>
                                                            <td class="datatable-cell"><span style="width: 80px;">{{ $d->nNf > 0 ? $d->nNf : '---' }}</span></td>
                                                            <td class="datatable-cell"><span style="width: 90px;">R$ {{number_format($d->valor, 2, ',', '.')}}</span></td>
                                                            <td class="datatable-cell"><span style="width: 80px;">{{ \Carbon\Carbon::parse($d->data_emissao)->format('d/m/y')}}</span></td>

                                                            <td class="datatable-cell">
                                                                <span style="width: 100px;">
                                                                    <span class="label label-inline @if($d->tipo == 2) label-light-success @elseif($d->tipo == 4) label-light-danger @else label-light-primary @endif font-weight-bold">
                                                                        {{$d->estado()}}
                                                                    </span>
                                                                </span>
                                                            </td>

                                                            <td class="datatable-cell">
                                                                <span style="width: 80px; display: flex; align-items: center; gap: 5px;">
                                                                    @if($d->compra_id > 0)
                                                                        <span class="badge badge-success" title="Compra">C</span>
                                                                    @else
                                                                        <i class="la la-clock-o text-warning" style="font-size: 18px;" title="Pendente"></i>
                                                                    @endif

                                                                    @if($d->fatura_salva)
                                                                        <span class="badge badge-info" title="Financeiro Salvo">F</span>
                                                                    @endif
                                                                </span>
                                                            </td>

                                                            <td class="datatable-cell">
                                                                <span style="width: 120px; display: flex; gap: 4px;">
                                                                    @if(!empty($d->chave))
                                                                        {{-- Botão Download XML --}}
                                                                        <a href="/dfe/download/{{$d->chave}}" class="btn btn-icon btn-xs btn-success" title="XML"><i class="la la-download"></i></a>

                                                                        {{-- Botão Imprimir DANFE --}}
                                                                        <a href="/dfe/imprimirDanfe/{{$d->chave}}" target="_blank" class="btn btn-icon btn-xs btn-primary" title="Imprimir"><i class="la la-print"></i></a>

                                                                        {{-- NOVO: Botão Importar para o ERP (Carrinho) --}}
                                                                        <a href="/dfe/importar/{{$d->chave}}" class="btn btn-icon btn-xs btn-info" title="Importar XML"><i class="la la-shopping-cart"></i></a>
                                                                    @endif

                                                                    @if($d->tipo != 2)
                                                                        {{-- Botão Manifestar (Martelo) --}}
                                                                        <button onclick="setarEvento('{{$d->chave}}')" data-toggle="modal" data-target="#modal1" class="btn btn-icon btn-xs btn-warning" title="Manifestar"><i class="la la-legal"></i></button>
                                                                    @endif
                                                                </span>
                                                            </td>

                                                            <td class="datatable-cell">
                                                                <span class="text-muted" style="width: 180px; font-size: 11px; display: block; word-wrap: break-word; white-space: normal;">
                                                                    {{$d->chave}}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
												</div>
											</div>
										</div>
									</div>
									</div>

								<div class="pb-5" data-wizard-type="step-content">

									<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
										<div class="row">

											@foreach($docs as $d)
											<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6">

												<div class="card card-custom gutter-b example example-compact">
													<div class="card-header">
														<div class="card-title">
															<h3 style="width: 230px; font-size: 15px; height: 10px;" class="card-title">{{$d->nome}}</h3>
														</div>
													</div>

													<div class="card-body">
														<div class="kt-widget__info">
															<span class="kt-widget__label">Documento:</span>
															<a class="kt-widget__data text-success">{{ $d->documento }}</a>
														</div>
														<div class="kt-widget__info">
															<span class="kt-widget__label">Valor:</span>
															<a class="kt-widget__data text-success">{{number_format($d->valor, 2)}}</a>
														</div>
														<div class="kt-widget__info">
															<span class="kt-widget__label">Data:</span>
															<a class="kt-widget__data text-success">{{ \Carbon\Carbon::parse($d->data_emissao)->format('d/m/Y H:i:s')}}</a>
														</div>
														<div class="kt-widget__info">
															<span class="kt-widget__label">Nº NFe:</span>
															<a class="kt-widget__data text-success">{{ $d->nNf }}</a>
														</div>
														<div class="kt-widget__info">
															<span class="kt-widget__label">Chave:</span>
															<a class="kt-widget__data text-success" style="word-break: break-all;">{{ $d->chave }}</a>
														</div>
														<div class="kt-widget__info">
															<span class="kt-widget__label">Estado:</span>
															<a class="kt-widget__data text-success">{{ $d->estado() }}</a>
														</div>
                                                        
                                                        <div class="kt-widget__info mt-2 mb-3">
                                                            <span class="kt-widget__label">Status ERP:</span>
                                                            @if($d->compra_id > 0)
                                                                <span class="label label-success label-inline font-weight-bolder">Compra ✔</span>
                                                            @else
                                                                <span class="label label-warning label-inline font-weight-bolder text-dark">Compra ⏳</span>
                                                            @endif

                                                            @if($d->fatura_salva)
                                                                <span class="label label-info label-inline font-weight-bolder">Financeiro ✔</span>
                                                            @else
                                                                <span class="label label-light-danger label-inline font-weight-bolder text-dark">Financeiro ⏳</span>
                                                            @endif
                                                        </div>

														@if($d->tipo == 1 || $d->tipo == 2)
														<a style="width: 100%;" href="/dfe/download/{{$d->chave}}" class="btn btn-success">Completa</a>
														<a style="width: 100%;" href="/dfe/imprimirDanfe/{{$d->chave}}" class="btn btn-primary mt-1">Imprimir</a>
														@elseif($d->tipo == 3)
														<a style="width: 100%;" class="btn btn-danger">Desconhecida</a>
														@elseif($d->tipo == 4)
														<a style="width: 100%;" class="btn btn-warning">Não realizada</a>
														@else
														<a style="width: 100%;" class="btn btn-info mt-1" onclick="setarEvento('{{$d->chave}}')" data-toggle="modal" data-target="#modal1">Manifestar</a>
														@endif
													</div>
												</div>
											</div>
											@endforeach

										</div>
									</div>
								</div>
								</form>

						</div>
					</div>

				</div>	
			</div>
		</div>

	</div>
</div>

<div class="modal fade" id="modal1" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<form method="get" action="/dfe/manifestar">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Manifestação de Destinatário</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">x</button>
				</div>
				<div class="modal-body">
					<input type="hidden" id="nome" name="nome" />
					<input type="hidden" id="cnpj" name="cnpj" />
					<input type="hidden" id="valor" name="valor" />
					<input type="hidden" id="data_emissao" name="data_emissao" />
					<input type="hidden" id="num_prot" name="num_prot" />
					<input type="hidden" id="chave" name="chave" />

					<div class="form-group validated col-sm-6 col-lg-6">
						<label class="col-form-label">Tipo</label>
						<select class="custom-select form-control" name="evento" id="tipo_evento">
							<option value="2">Confirmação</option>
							<option value="1">Ciencia de operção</option>
							<option value="3">Desconhecimento</option>
							<option value="4">Operação não realizada</option>
						</select>
					</div>

					<div class="form-group validated col-sm-12 col-lg-12" id="div-just" style="display: none">
						<label class="col-form-label">Justificativa</label>
						<div class="">
							<input id="justificativa" type="text" class="form-control" name="justificativa" value="">
						</div>
					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="salvarEdit" class="btn btn-success font-weight-bold spinner-white spinner-right">Manifestar</button>
				</div>
			</div>
		</div>
	</form>
</div>

@endsection