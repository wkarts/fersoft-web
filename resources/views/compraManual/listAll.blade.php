@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-modules.css') }}?v={{ filemtime(public_path('css/fer-modules.css')) }}">
@endpush

@section('content')
<header class="fer-page__heading"><h1>Compras</h1></header>
<div class="card card-custom gutter-b fer-page fer-detail">
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
                            <label class="col-form-label">Data Emissão Inicial</label>
                            <div class="input-group date">
                                <input type="text" name="data_inicial" class="form-control" readonly value="{{{ isset($dataInicial) ? $dataInicial : '' }}}" id="kt_datepicker_3" />
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label">Data Emissão Final</label>
                            <div class="input-group date">
                                <input type="text" name="data_final" class="form-control" readonly value="{{{ isset($dataFinal) ? $dataFinal : '' }}}" id="kt_datepicker_3" />
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label">Nº Emissão Própria</label>
                            <input type="text" name="numero_emissao" class="form-control" value="{{{ isset($numero_emissao) ? $numero_emissao : '' }}}">
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

                <!-- CABEÇALHO E CONTROLES DE VISUALIZAÇÃO -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4>Lista de Compras</h4>
                        <label>Total de registros: <strong class="text-info">{{ sizeof($compras) }}</strong></label>
                    </div>
                    <div class="btn-group" role="group" aria-label="Modo de visualização">
                        <button type="button" class="btn btn-light-primary active" id="btn-grade" onclick="mudarVisualizacao('grade')">
                            <i class="fa fa-th-large"></i> Grade
                        </button>
                        <button type="button" class="btn btn-light-primary" id="btn-tabela" onclick="mudarVisualizacao('tabela')">
                            <i class="fa fa-list"></i> Tabela
                        </button>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- VISUALIZAÇÃO EM GRADE (Original Restaurada)    -->
                <!-- ============================================== -->
                <div id="view-grade" class="row">
                    @foreach($compras as $c)
                        <div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
                            <div class="card card-custom gutter-b example example-compact">
                                <div class="card-header">
                                    <div class="card-title">
                                        <h3 style="width: 230px; font-size: 12px; height: 10px;" class="card-title">#{{$c->numero_sequencial}} - {{substr($c->fornecedor->razao_social, 0, 30)}}</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">
                                            <a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-ellipsis-h"></i>
                                            </a>
                                            <div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-right">
                                                <ul class="navi navi-hover">
                                                    <li class="navi-header font-weight-bold py-4">
                                                        <span class="font-size-lg">Ações:</span>
                                                    </li>
                                                    <li class="navi-separator mb-3 opacity-70"></li>

                                                    <li class="navi-item">
                                                        <a href="/compras/detalhes/{{ $c->id }}" class="navi-link">
                                                            <span class="navi-text"><span class="label label-xl label-inline label-light-primary">Detalhes</span></span>
                                                        </a>
                                                    </li>

                                                    @if($c->estado == 'NOVO')
                                                        <li class="navi-item">
                                                            <a href="javascript:;" onclick="abrirModalImportarXml({{ $c->id }})" class="navi-link">
                                                    <span class="navi-text">
                                                        <span class="label label-xl label-inline label-light-info">
                                                            <i class="fa fa-file-upload text-info mr-2" style="font-size: 12px;"></i> Importar XML
                                                        </span>
                                                    </span>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if($c->verificaValidade())
                                                        <li class="navi-item">
                                                            <a href="/compras/setar-validade/{{ $c->id }}" class="navi-link">
                                                                <span class="navi-text"><span class="label label-xl label-inline label-light-info">Setar Validade</span></span>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if(($c->estado == 'NOVO' || $c->estado == 'REJEITADO' || $c->estado == 'EMITIDA' || $c->estado == 'IMPORTADO') && ($c->numero_emissao == 0 || $c->numero_emissao == null))
                                                        <li class="navi-item">
                                                            <a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/compras/delete/{{ $c->id }}" }else{return false} })' href="#!" class="navi-link">
                                                                <span class="navi-text"><span class="label label-xl label-inline label-light-danger">Excluir</span></span>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if($c->estado == 'REJEITADO')
                                                        <li class="navi-item">
                                                            <a href="/compras/emitirEntrada/{{ $c->id }}" class="navi-link">
                                                    <span class="navi-text">
                                                        <span class="label label-xl label-inline label-light-danger">Reenviar NFe</span>
                                                    </span>
                                                            </a>
                                                        </li>
                                                    @else
                                                        <li class="navi-item">
                                                            <a href="/compras/emitirEntrada/{{ $c->id }}" class="navi-link">
                                                    <span class="navi-text">
                                                        <span class="label label-xl label-inline label-light-success">NFe entrada</span>
                                                    </span>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if($c->chave != "" && $c->chave != null)
                                                        <li class="navi-item">
                                                            <a onclick='swal("Atenção!", "Deseja gerar o espelho para DEVOLUÇÃO TOTAL desta nota?", "warning").then((sim) => {if(sim){ location.href="/compras/devolver/{{ $c->id }}" }else{return false} })' href="#!" class="navi-link">
                                                      <span class="navi-text">
                                                          <span class="label label-xl label-inline label-light-dark">
                                                              <i class="fa fa-reply text-white mr-2" style="font-size: 12px;"></i> Gerar Devolução Total
                                                          </span>
                                                      </span>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    <li class="navi-item">
                                                        <a href="/compras/etiqueta/{{ $c->id }}" class="navi-link">
                                                            <span class="navi-text"><span class="label label-xl label-inline label-light-dark">Gerar Etiqueta</span></span>
                                                        </a>
                                                    </li>

                                                    @if( (($c->chave == "" || $c->chave == null) && ($c->estado == 'NOVO' || $c->estado == 'EMITIDA') && ($c->numero_emissao == 0 || $c->numero_emissao == null)) || $c->estado == 'REJEITADO' )
                                                        <li class="navi-item">
                                                            <a onclick='swal("Atenção!", "Deseja editar este registro para correção?", "warning").then((sim) => {if(sim){ location.href="/compraManual/editar/{{ $c->id }}" }else{return false} })' href="#!" class="navi-link">
                                                                <span class="navi-text"><span class="label label-xl label-inline label-light-warning">Editar e Corrigir</span></span>
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
                                        <span class="kt-widget__label">Data de emissão:</span>
                                        <a class="kt-widget__data text-success">
                                            {{ $c->data_emissao != null ? \Carbon\Carbon::parse($c->data_emissao)->format('d/m/Y H:i') : '--' }}
                                        </a>
                                    </div>

                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">Estado:</span>
                                        @if($c->estado == 'REJEITADO')
                                            <span class="label label-xl label-inline label-light-danger" data-toggle="tooltip" title="Nota Rejeitada pela SEFAZ">REJEITADO</span>
                                        @elseif($c->estado == 'CANCELADO')
                                            <span class="label label-xl label-inline label-light-danger">CANCELADO</span>
                                        @elseif($c->numero_emissao > 0)
                                            <span class="label label-xl label-inline label-light-success">AUTORIZADO</span>
                                        @elseif($c->chave != "" && $c->chave != null)
                                            <span class="label label-xl label-inline label-light-dark">IMPORTADO</span>
                                        @else
                                            @if($c->estado == 'NOVO')
                                                <span class="label label-xl label-inline label-light-primary">NOVO</span>
                                            @elseif($c->estado == 'EMITIDA')
                                                <span class="label label-xl label-inline label-light-info">EMITIDA</span>
                                            @elseif($c->estado == 'APROVADO')
                                                <span class="label label-xl label-inline label-light-success">APROVADO</span>
                                            @endif
                                        @endif
                                    </div>

                                    <!-- RESTAURADO DADOS QUE FALTAVAM NA GRADE -->
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
                                    <!-- FIM DOS DADOS RESTAURADOS -->

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- ============================================== -->
                <!-- VISUALIZAÇÃO EM TABELA -->
                <!-- ============================================== -->
                <div id="view-tabela" class="table-responsive" style="display: none;">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="thead-light">
                        <tr>
                            <th># Seq / Fornecedor</th>
                            <th>Valor</th>
                            <th>NF Imp.</th>
                            <th>NF Emi.</th>
                            <th>Data Emissão</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($compras as $c)
                            <tr>
                                <td><strong>#{{$c->numero_sequencial}}</strong> - {{substr($c->fornecedor->razao_social, 0, 30)}}</td>
                                <td class="text-success font-weight-bolder">R$ {{ number_format($c->valor, $casasDecimais, ',', '.') }}</td>
                                <td class="text-info">{{ $c->nf > 0 ? $c->nf : '--' }}</td>
                                <td class="text-primary">{{ $c->numero_emissao > 0 ? $c->numero_emissao : '--' }}</td>
                                <td>{{ $c->data_emissao != null ? \Carbon\Carbon::parse($c->data_emissao)->format('d/m/Y H:i') : '--' }}</td>
                                <td>
                                    @if($c->estado == 'REJEITADO')
                                        <span class="label label-lg label-inline label-light-danger">REJEITADO</span>
                                    @elseif($c->estado == 'CANCELADO')
                                        <span class="label label-lg label-inline label-light-danger">CANCELADO</span>
                                    @elseif($c->numero_emissao > 0)
                                        <span class="label label-lg label-inline label-light-success">AUTORIZADO</span>
                                    @elseif($c->chave != "" && $c->chave != null)
                                        <span class="label label-lg label-inline label-light-dark">IMPORTADO</span>
                                    @else
                                        @if($c->estado == 'NOVO')
                                            <span class="label label-lg label-inline label-light-primary">NOVO</span>
                                        @elseif($c->estado == 'EMITIDA')
                                            <span class="label label-lg label-inline label-light-info">EMITIDA</span>
                                        @elseif($c->estado == 'APROVADO')
                                            <span class="label label-lg label-inline label-light-success">APROVADO</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">
                                        <a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-ellipsis-h"></i>
                                        </a>
                                        <div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-right">
                                            <ul class="navi navi-hover">
                                                <li class="navi-header font-weight-bold py-4">
                                                    <span class="font-size-lg">Ações:</span>
                                                </li>
                                                <li class="navi-separator mb-3 opacity-70"></li>

                                                <li class="navi-item">
                                                    <a href="/compras/detalhes/{{ $c->id }}" class="navi-link">
                                                        <span class="navi-text"><span class="label label-xl label-inline label-light-primary">Detalhes</span></span>
                                                    </a>
                                                </li>

                                                @if($c->estado == 'NOVO')
                                                    <li class="navi-item">
                                                        <a href="javascript:;" onclick="abrirModalImportarXml({{ $c->id }})" class="navi-link">
                                                    <span class="navi-text">
                                                        <span class="label label-xl label-inline label-light-info">
                                                            <i class="fa fa-file-upload text-info mr-2" style="font-size: 12px;"></i> Importar XML
                                                        </span>
                                                    </span>
                                                        </a>
                                                    </li>
                                                @endif

                                                @if($c->chave != "" && $c->chave != null)
                                                    <li class="navi-item">
                                                        <a onclick='swal("Atenção!", "Deseja gerar o espelho para DEVOLUÇÃO TOTAL desta nota?", "warning").then((sim) => {if(sim){ location.href="/compras/devolver/{{ $c->id }}" }else{return false} })' href="#!" class="navi-link">
                                                      <span class="navi-text">
                                                          <span class="label label-xl label-inline label-light-dark">
                                                              <i class="fa fa-reply text-white mr-2" style="font-size: 12px;"></i> Gerar Devolução Total
                                                          </span>
                                                      </span>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if($c->verificaValidade())
                                                    <li class="navi-item">
                                                        <a href="/compras/setar-validade/{{ $c->id }}" class="navi-link">
                                                            <span class="navi-text"><span class="label label-xl label-inline label-light-info">Setar Validade</span></span>
                                                        </a>
                                                    </li>
                                                @endif

                                                @if(($c->estado == 'NOVO' || $c->estado == 'REJEITADO' || $c->estado == 'EMITIDA' || $c->estado == 'IMPORTADO') && ($c->numero_emissao == 0 || $c->numero_emissao == null))
                                                    <li class="navi-item">
                                                        <a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/compras/delete/{{ $c->id }}" }else{return false} })' href="#!" class="navi-link">
                                                            <span class="navi-text"><span class="label label-xl label-inline label-light-danger">Excluir</span></span>
                                                        </a>
                                                    </li>
                                                @endif

                                                @if($c->estado == 'REJEITADO')
                                                    <li class="navi-item">
                                                        <a href="/compras/emitirEntrada/{{ $c->id }}" class="navi-link">
                                                    <span class="navi-text">
                                                        <span class="label label-xl label-inline label-light-danger">Reenviar NFe</span>
                                                    </span>
                                                        </a>
                                                    </li>
                                                @else
                                                    <li class="navi-item">
                                                        <a href="/compras/emitirEntrada/{{ $c->id }}" class="navi-link">
                                                    <span class="navi-text">
                                                        <span class="label label-xl label-inline label-light-success">NFe entrada</span>
                                                    </span>
                                                        </a>
                                                    </li>
                                                @endif

                                                <li class="navi-item">
                                                    <a href="/compras/etiqueta/{{ $c->id }}" class="navi-link">
                                                        <span class="navi-text"><span class="label label-xl label-inline label-light-dark">Gerar Etiqueta</span></span>
                                                    </a>
                                                </li>

                                                @if( (($c->chave == "" || $c->chave == null) && ($c->estado == 'NOVO' || $c->estado == 'EMITIDA') && ($c->numero_emissao == 0 || $c->numero_emissao == null)) || $c->estado == 'REJEITADO' )
                                                    <li class="navi-item">
                                                        <a onclick='swal("Atenção!", "Deseja editar este registro para correção?", "warning").then((sim) => {if(sim){ location.href="/compraManual/editar/{{ $c->id }}" }else{return false} })' href="#!" class="navi-link">
                                                            <span class="navi-text"><span class="label label-xl label-inline label-light-warning">Editar e Corrigir</span></span>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- PAGINAÇÃO -->
                <div class="d-flex justify-content-between align-items-center flex-wrap mt-4">
                    <div class="d-flex flex-wrap py-2 mr-3">
                        @if(isset($links))
                            {{$compras->links()}}
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- MODAIS -->
    <!-- ============================================== -->

    <!-- Modal Validade Original -->
    <div class="modal fade" id="modal-validade" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog modal-xl fer-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Compras com itens sem validade</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Importar XML (NOVO) -->
    <div class="modal fade" id="modal-importar-xml" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog fer-dialog" role="document">
            <div class="modal-content">
                <form id="form-importar-xml" method="POST" action="/compras/importarXml" enctype="multipart/form-data" onsubmit="validarXmlAntes(event)">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Importar XML (Nota Fiscal)</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">x</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="compra_id" id="compra_id_importar" value="">
                        <div class="form-group">
                            <label>Selecione o arquivo XML (.xml)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="file" accept=".xml" id="xmlFile" required>
                                <label class="custom-file-label" for="xmlFile" data-browse="Buscar">Escolher arquivo</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-light-success font-weight-bold">Importar Dados</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <script type="text/javascript" src="/js/validade.js"></script>
    <script>
        // ==============================================
        // FUNÇÕES DE ALTERNÂNCIA (TABELA / GRADE)
        // ==============================================
        function mudarVisualizacao(tipo) {
            if (tipo === 'tabela') {
                $('#view-grade').hide();
                $('#view-tabela').fadeIn();

                $('#btn-grade').removeClass('active');
                $('#btn-tabela').addClass('active');
            } else {
                $('#view-tabela').hide();
                $('#view-grade').fadeIn();

                $('#btn-tabela').removeClass('active');
                $('#btn-grade').addClass('active');
            }
            localStorage.setItem('preferencia_visualizacao_compras', tipo);
        }

        $(document).ready(function() {
            let pref = localStorage.getItem('preferencia_visualizacao_compras');
            if (pref && pref === 'tabela') {
                mudarVisualizacao('tabela');
            }
        });

        // ==============================================
        // FUNÇÕES DO MODAL DE IMPORTAÇÃO E VALIDAÇÃO XML
        // ==============================================
        function abrirModalImportarXml(id) {
            $('#compra_id_importar').val(id);
            $('.flags-importacao').remove(); // Limpa as flags anteriores
            $('#form-importar-xml')[0].reset();
            $('.custom-file-label').html('Escolher arquivo');
            $('#modal-importar-xml').modal('show');
        }

        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });

        async function validarXmlAntes(e) {
            e.preventDefault();

            let form = document.getElementById('form-importar-xml');
            let formData = new FormData(form);
            $('.flags-importacao').remove();

            try {
                let response = await fetch('/compras/validaXmlImportacao', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() }
                });
                let res = await response.json();

                if (res.status === 'error') {
                    swal("Erro", res.message, "error");
                    return;
                }

                let flags = res.flags;

                if (flags.diverge_cnpj) {
                    let conf = await swal({ title: "Atenção", text: "O CNPJ do XML é diferente do fornecedor da compra. Deseja importar mesmo assim?", icon: "warning", buttons: ["Não", "Sim"] });
                    if (!conf) return;
                }

                if (flags.diverge_tributos) {
                    let conf = await swal({ title: "Tributos Divergentes", text: "O NCM do XML é diferente do produto cadastrado nesta compra. Deseja importar mesmo assim?", icon: "warning", buttons: ["Não", "Sim"] });
                    if (!conf) return;
                }

                if (flags.compra_maior) {
                    let conf = await swal({ title: "Diferença de Peso", text: "O peso/quantidade da compra (Balança) é MAIOR que a NF-e. Deseja que o sistema gere automaticamente uma nova compra para emissão da Nota Complementar?", icon: "info", buttons: ["Não", "Sim, gerar complementar"] });
                    if (conf) {
                        $(form).append('<input type="hidden" class="flags-importacao" name="gerar_complementar" value="1">');
                    }
                }

                if (flags.nfe_maior) {
                    let conf = await swal({ title: "Diferença de Peso/Valor", text: "A quantidade ou valor da NF-e é MAIOR que a compra da balança. Deseja importar e sinalizar necessidade de Devolução da diferença?", icon: "warning", buttons: ["Não importar", "Sim, sinalizar devolução"] });
                    if (!conf) return;
                    $(form).append('<input type="hidden" class="flags-importacao" name="gerar_devolucao" value="1">');
                }

                form.submit();

            } catch (err) {
                swal("Erro", "Falha na comunicação com o servidor.", "error");
            }
        }

    </script>
    @if(session('alerta_devolucao'))
        <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Um pequeno delay garante que o SweetAlert carregou antes de tentar abrir
                setTimeout(function() {
                    swal({
                        title: "Ação Necessária!",
                        text: {!! json_encode(session('alerta_devolucao')) !!},
                        icon: "info",
                        button: "Entendido, vou emitir!",
                        closeOnClickOutside: false,
                        closeOnEsc: false
                    });
                }, 500);
            });
        </script>
    @endif
@endsection
