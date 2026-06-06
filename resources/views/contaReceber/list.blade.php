@extends('default.layout')
@section('content')

    <div class="card card-custom gutter-b">
        <div class="card-body">

            {{-- (GRUPO) TOPO: BOTÕES DE CADASTRO E IMPORTAÇÃO --}}
            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-12">
                    <div class="row">
                        <a href="/contasReceber/new" class="btn btn-lg btn-success">
                            <i class="fa fa-plus"></i> Nova Conta a Receber
                        </a>

                        <a href="/contasReceber/pendentes" class="btn btn-lg btn-warning ml-1">
                            <i class="fa fa-tag"></i> Débito por Cliente
                        </a>

                        <a href="/contasReceber/importacao" class="btn btn-lg btn-danger ml-1">
                            <i class="fa fa-arrow-up"></i> Importação
                        </a>
                    </div>

                    @if($comRetencoes)
                        <a href="{{ route('retencoes.index') }}" class="btn btn-sm btn-dark float-right">
                            <i class="fa fa-list"></i> Lista de retenções
                        </a>
                    @endif
                </div>
            </div>
            <br>

            {{-- (GRUPO) FILTROS: Campos para busca e refinamento da listagem --}}
            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

                <form method="get" action="/contasReceber/filtro">
                    <div class="row align-items-center">

                        <div class="form-group col-lg-6 col-xl-5">
                            <div class="row align-items-center">
                                <div class="col-md-12 my-2 my-md-0">
                                    <label class="col-form-label font-weight-bold">Cliente</label>
                                    <select class="form-control select2" id="kt_select2_3" name="clienteId">
                                        <option value="null">Selecione o cliente</option>
                                        @foreach($clientes as $c)
                                            <option @isset($clienteId) @if($clienteId == $c->id) selected @endif @endif value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}}) | {{$c->telefone}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Filtro de data</label>
                            <select class="custom-select form-control" id="tipo_filtro_data" name="tipo_filtro_data">
                                <option @if(isset($tipo_filtro_data) && $tipo_filtro_data == 1) selected @endif value="1">Vencimento</option>
                                <option @if(isset($tipo_filtro_data) && $tipo_filtro_data == 2) selected @endif value="2">Data de registro</option>
                                <option @if(isset($tipo_filtro_data) && $tipo_filtro_data == 3) selected @endif value="3">Data de pagamento</option>
                                <option @if(isset($tipo_filtro_data) && $tipo_filtro_data == 4) selected @endif value="4">Data de Emissão</option>
                            </select>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Data Inicial</label>
                            <div class="input-group date">
                                <input type="text" name="data_inicial" class="form-control date-input" value="{{{ isset($dataInicial) ? $dataInicial : '' }}}" id="kt_datepicker_3" />
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="la la-calendar"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Data Final</label>
                            <div class="input-group date">
                                <input type="text" name="data_final" class="form-control date-input" value="{{{ isset($dataFinal) ? $dataFinal : '' }}}" id="kt_datepicker_3" />
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="la la-calendar"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Estado</label>
                            <select class="custom-select form-control" id="status" name="status">
                                <option @if(isset($status) && $status == 'todos') selected @endif value="todos">TODOS</option>
                                <option @if(isset($status) && $status == 'pago') selected @endif value="pago">PAGO</option>
                                <option @if(isset($status) && $status == 'pendente') selected @endif value="pendente">PENDENTE</option>
                                <option @if(isset($status) && $status == 'vencido') selected @endif value="vencido">VENCIDO</option>
                            </select>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Categoria</label>
                            <select class="custom-select form-control" id="categoria" name="categoria">
                                <option @if(isset($categoria) && $categoria == 'todos') selected @endif value="todos">TODOS</option>
                                @foreach($categorias as $c)
                                    <option @if(isset($categoria) && $categoria == $c->id) selected @endif value="{{$c->id}}">{{$c->nome}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Conta Empresa</label>
                            <select class="custom-select form-control" name="conta_id">
                                <option value="todos">TODAS</option>
                                @foreach($contasEmpresa as $ce)
                                    <option @if(isset($conta_id) && $conta_id == $ce->id) selected @endif value="{{$ce->id}}">{{$ce->nome}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">ID da Venda</label>
                            <input type="number" name="venda_id_filtro" value="{{{ isset($venda_id_filtro) ? $venda_id_filtro : '' }}}" class="form-control" placeholder="Ex: 150">
                        </div>

                        <div class="form-group col-lg-3">
                            <label class="col-form-label font-weight-bold">Tipo de Pagamento</label>
                            <select class="custom-select form-control" name="tipo_pagamento">
                                <option value="">Todos os tipos</option>
                                @foreach(App\Models\ContaReceber::tiposPagamento() as $c)
                                    <option @isset($tipo_pagamento) @if($tipo_pagamento == $c) selected @endif @endif value="{{$c}}">{{$c}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-lg-2">
                            <label class="col-form-label font-weight-bold">Nº nota fiscal</label>
                            <input type="text" name="numero_nota_fiscal" value="{{{ isset($numero_nota_fiscal) ? $numero_nota_fiscal : '' }}}" class="form-control" placeholder="Nº nota">
                        </div>

                        @if(empresaComFilial())
                            {!! __view_locais_select_filtro("Local", isset($filial_id) ? $filial_id : '') !!}
                        @endif

                        <div class="col-lg-2">
                            <button type="submit" class="btn btn-light-primary btn-block font-weight-bold mt-8">Filtrar</button>
                        </div>
                    </div>
                </form>

                <br>
                <h4>Lista de Contas a Receber</h4>

                {{-- (GRUPO) RELATÓRIO: Botão para gerar relatório em PDF --}}
                @isset($paraImprimir)
                    <form target="_blank" method="get" action="/contasReceber/relatorio">
                        <input type="hidden" name="clienteId" value="{{{ isset($clienteId) ? $clienteId : '' }}}">
                        <input type="hidden" name="data_inicial" value="{{{ isset($dataInicial) ? $dataInicial : '' }}}">
                        <input type="hidden" name="data_final" value="{{{ isset($dataFinal) ? $dataFinal : '' }}}">
                        <input type="hidden" name="status" value="{{{ isset($status) ? $status : '' }}}">
                        <input type="hidden" name="categoria" value="{{{ isset($categoria) ? $categoria : '' }}}">
                        <input type="hidden" name="numero_nota_fiscal" value="{{{ isset($numero_nota_fiscal) ? $numero_nota_fiscal : '' }}}">
                        <input type="hidden" name="tipo_filtro_data" value="{{{ isset($tipo_filtro_data) ? $tipo_filtro_data : '' }}}">
                        <input type="hidden" name="filial_id" value="{{{ isset($filial_id) ? $filial_id : '' }}}">

                        <button class="btn btn-info mb-3">
                            <i class="fa fa-print"></i> Imprimir relatório
                        </button>
                    </form>
                @endisset

                <h6 style="color: red">*{{$infoDados}}</h6>
                <label>Total de registros: {{sizeof($contas)}}</label>

                {{-- (GRUPO) BARRA DE FERRAMENTAS: Seleção múltipla, Boletos e Excel --}}
                <div class="row">
                    <div class="col-12">
                        <button id="btn_seleciona_varios" class="btn btn-light font-weight-bold">
                            <i class="la la-list"></i> Selecionar Varios
                        </button>

                        <button style="display: none" id="btn_receber" class="btn btn-success font-weight-bold">
                            <i class="la la-check"></i> Receber Contas
                        </button>

                        <button style="display: none" id="btn_gerar" class="btn btn-info font-weight-bold">
                            <i class="la la-barcode"></i> Gerar Boletos
                        </button>

                        <a href="{{ url('/contasReceber/syncNotaFiscal') }}" class="btn btn-light font-weight-bold">
                            <i class="fa fa-sync text-info"></i> Sincronizar NF das Vendas
                        </a>

                        <a href="/contasReceber/export?{{ http_build_query(request()->all()) }}" class="btn btn-light font-weight-bold">
                            <i class="fa fa-file-excel-o text-success"></i> Exportar para Excel
                        </a>
                    </div>
                </div>

                {{-- (GRUPO) TABELAS E GRADES: Alternância entre visualização em lista ou cartões (Wizard) --}}
                <div class="row">
                    <?php $somaValor = 0; $somaPago = 0; $somaPendente = 0; ?>
                    <div class="col-12">
                        <div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
                            <div class="wizard-nav">
                                <div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
                                    <div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
                                        <div class="wizard-label">
                                            <h3 class="wizard-title"><span><i style="font-size: 40px" class="la la-table"></i> Tabela</span></h3>
                                            <div class="wizard-bar"></div>
                                        </div>
                                    </div>
                                    <div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
                                        <div class="wizard-label" id="grade">
                                            <h3 class="wizard-title"><span><i style="font-size: 40px" class="la la-tablet"></i> Grade</span></h3>
                                            <div class="wizard-bar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- CONTEÚDO 1: VISUALIZAÇÃO EM TABELA --}}
                            <div class="pb-5" data-wizard-type="step-content">
                                <div class="row">
                                    <div class="col-xl-12">
                                        <div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
                                            <table class="datatable-table" style="max-width: 100%; overflow: scroll">
                                                <thead class="datatable-head">
                                                <tr class="datatable-row">
                                                    <th class="datatable-cell"><span style="width: 160px;">AÇÕES</span></th>
                                                    <th class="datatable-cell"><span style="width: 200px;">CLIENTE</span></th>
                                                    <th class="datatable-cell"><span style="width: 200px;">CATEGORIA / REF</span></th>
                                                    <th class="datatable-cell"><span style="width: 100px;">VALOR INT.</span></th>
                                                    <th class="datatable-cell"><span style="width: 100px;">VALOR PAGO</span></th>
                                                    <th class="datatable-cell"><span style="width: 100px;">VENCIMENTO</span></th>
                                                    <th class="datatable-cell"><span style="width: 100px;">PAGAMENTO</span></th>
                                                    <th class="datatable-cell"><span style="width: 100px;">ESTADO</span></th>
                                                    <th class="datatable-cell"><span style="width: 80px;">Nº NOTA</span></th>
                                                    <th class="datatable-cell"><span style="width: 150px;">USUÁRIOS (C/B)</span></th>
                                                </tr>
                                                </thead>
                                                <tbody id="body" class="datatable-body">
                                                @foreach($contas as $c)
                                                    <tr class="datatable-row">
                                                        <td class="datatable-cell">
                                                                <span style="width: 160px;">
                                                                    @if($c->status == false)
                                                                        {{-- Checkbox para seleção múltipla --}}
                                                                        <label style="display: none" class="checkbox checkbox-success checkbox-inline mr-2" for="sel_{{$c->id}}">
                                                                            <input id="sel_{{$c->id}}" class="select" type="checkbox" name="Checkboxes5"/>
                                                                            <span></span>
                                                                        </label>

                                                                        <a href="/contasReceber/edit/{{$c->id}}" class="btn btn-warning btn-sm btn-icon" title="Editar"><i class="la la-edit"></i></a>
                                                                        <a onclick='swal("Atenção!", "Remover?", "warning").then((sim) => {if(sim){ location.href="/contasReceber/delete/{{ $c->id }}" }})' class="btn btn-danger btn-sm btn-icon" title="Excluir"><i class="la la-trash"></i></a>
                                                                        <a href="/contasReceber/receber/{{$c->id}}" class="btn btn-success btn-sm btn-icon" title="Receber"><i class="la la-money"></i></a>

                                                                        {{-- BOTÃO GERAR BOLETO (Individual) --}}
                                                                        @if(!$c->boleto)
                                                                            <a href="/boleto/gerar/{{$c->id}}" class="btn btn-info btn-sm btn-icon" title="Gerar Boleto"><i class="la la-barcode"></i></a>
                                                                        @endif
                                                                    @else
                                                                        <a title="Estornar conta" href="/contasReceber/estorno/{{$c->id}}" class="btn btn-dark btn-sm btn-icon"><i class="la la-arrow-alt-circle-left"></i></a>
                                                                        <a href="/contasReceber/imprimirRecibo/{{$c->id}}" class="btn btn-info btn-sm btn-icon" target="_blank" title="Imprimir Recibo"><i class="la la-print"></i></a>
                                                                    @endif

                                                                    {{-- BOTÃO OBSERVAÇÃO --}}
                                                                    @if($c->observacao)
                                                                        <button onclick='swal("Observação", "{{$c->observacao}}", "info")' class="btn btn-light-primary btn-sm btn-icon" title="Ver Observação"><i class="la la-sticky-note"></i></button>
                                                                    @endif
                                                                </span>
                                                        </td>

                                                        <td class="datatable-cell">
                                                                <span style="width: 200px;">
                                                                    @if($c->venda_id != null) <b>{{ $c->venda->cliente->razao_social }}</b>
                                                                    @else <b>{{ $c->cliente->razao_social ?? '--' }}</b>
                                                                    @endif
                                                                </span>
                                                        </td>

                                                        <td class="datatable-cell">
                                                                  <span style="width: 200px;">
                                                                      <b>{{$c->categoria->nome}}</b> <br>
                                                                      <small class="text-muted">{{ $c->referencia }}</small>

                                                                      {{-- TIPO DE PAGAMENTO --}}
                                                                      <br>
                                                                      <span class="label label-inline label-light-dark font-weight-bold">
                                                                          Pagt: {{ $c->tipo_pagamento ?? '--' }}
                                                                      </span>

                                                                      @if($c->filial_id)
                                                                          <br><span class="label label-inline label-light-primary">Local: {{ $c->filial->descricao }}</span>
                                                                      @endif
                                                                  </span>
                                                        </td>

                                                        <td class="datatable-cell"><span style="width: 100px;">R$ {{number_format($c->valor_integral, 2, ',', '.')}}</span></td>
                                                        <td class="datatable-cell"><span style="width: 100px;">R$ {{number_format($c->valor_recebido, 2, ',', '.')}}</span></td>

                                                        <td class="datatable-cell">
                                                                <span style="width: 100px;">
                                                                    {{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y')}}
                                                                    @if(!$c->status)
                                                                        <br><span class="text-danger" style="font-size: 10px">{{ $c->diasAtraso() }}</span>
                                                                    @endif
                                                                </span>
                                                        </td>

                                                        <td class="datatable-cell">
                                                                <span style="width: 100px;">
                                                                    {{ $c->status && $c->data_recebimento ? \Carbon\Carbon::parse($c->data_recebimento)->format('d/m/Y') : '--' }}
                                                                </span>
                                                        </td>

                                                        <td class="datatable-cell">
                                                                <span style="width: 100px;">
                                                                    @if($c->status == true) <span class="label label-xl label-inline label-light-success">Pago</span>
                                                                    @else <span class="label label-xl label-inline label-light-danger">Pendente</span> @endif
                                                                </span>
                                                        </td>


                                                        {{-- Nº NOTA COM LINKS DINÂMICOS --}}
                                                        <td class="datatable-cell">
                                                                <span style="width: 80px;">
                                                                    @if($c->numero_nota_fiscal > 0 || $c->nf_numero > 0)
                                                                        @if($c->cte_id)
                                                                            {{-- Abre o DACTE padrão do CT-e (Preservado e funcionando!) --}}
                                                                            <a href="/cteSefaz/imprimir/{{$c->cte_id}}" target="_blank" title="Imprimir DACTE" class="text-info font-weight-bold">
                                                                                <i class="la la-truck"></i> {{ $c->numero_nota_fiscal > 0 ? $c->numero_nota_fiscal : $c->nf_numero }}
                                                                            </a>
                                                                        @else
                                                                            {{-- TODAS AS NF-E (Vendas, Manuais ou Importadas): Abrem pela nova rota segura baseada no ID da Conta --}}
                                                                            <a href="/contasReceber/visualizarDanfe/{{$c->id}}" target="_blank" title="Visualizar DANFE Oficial" class="text-primary font-weight-bold">
                                                                                <i class="la la-print"></i> {{ $c->numero_nota_fiscal > 0 ? $c->numero_nota_fiscal : $c->nf_numero }}
                                                                            </a>
                                                                        @endif
                                                                    @else
                                                                        --
                                                                    @endif
                                                                </span>
                                                        </td>

                                                        {{-- COLUNA DE USUÁRIOS (CADASTROU / BAIXOU) --}}
                                                        <td class="datatable-cell">
                                                                <span style="width: 150px;">
                                                                    <small><b>Incluiu:</b> {{ $c->usuario->nome ?? 'Sistema' }}</small> <br>
                                                                    @if($c->status)
                                                                        <small><b>Baixou:</b> {{ $c->usuarioBaixa->nome ?? '--' }}</small>
                                                                    @endif
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
                            {{-- CONTEÚDO 2: VISUALIZAÇÃO EM GRADE (CARTÕES) --}}
                            <input type="hidden" id="contas" value="{{json_encode($contas)}}">
                            <div class="pb-5" data-wizard-type="step-content">
                                <div class="row">
                                    @foreach($contas as $c)
                                        <div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
                                            <div class="card card-custom gutter-b example example-compact">
                                                <div class="card-header">
                                                    <div class="card-title">
                                                        @if(!$c->status)
                                                            <label style="display: none" class="checkbox checkbox-success" for="sel_{{$c->id}}">
                                                                <input id="sel_{{$c->id}}" class="select" type="checkbox" name="Checkboxes5"/>
                                                                <span></span>
                                                            </label>
                                                        @endif
                                                        <h3 style="width: 230px; font-size: 20px; height: 10px;" class="card-title">
                                                            R$ {{number_format($c->valor_integral, $casasDecimais, ',', '.')}}
                                                        </h3>
                                                    </div>

                                                    <div class="card-toolbar">
                                                        <div class="dropdown dropdown-inline" data-toggle="tooltip" title="Ações">
                                                            <a href="#" class="btn btn-hover-light-primary btn-sm btn-icon btn-action" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fa fa-ellipsis-h"></i>
                                                            </a>
                                                            <div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-left">
                                                                <ul class="navi navi-hover">
                                                                    <li class="navi-header font-weight-bold py-4"><span class="font-size-lg">Ações:</span></li>
                                                                    <li class="navi-separator mb-3 opacity-70"></li>
                                                                    @if($c->status == false)
                                                                        <li class="navi-item"><a href="/contasReceber/edit/{{$c->id}}" class="navi-link"><span class="navi-text text-warning">Editar</span></a></li>
                                                                        <li class="navi-item"><a onclick='swal("Atenção!", "Remover?", "warning").then((sim) => {if(sim){ location.href="/contasReceber/delete/{{ $c->id }}" }})' class="navi-link"><span class="navi-text text-danger">Excluir</span></a></li>
                                                                        <li class="navi-item"><a href="/contasReceber/receber/{{$c->id}}" class="navi-link"><span class="navi-text text-success">Receber</span></a></li>
                                                                    @else
                                                                        <li class="navi-item">
                                                                            <a href="/contasReceber/imprimirRecibo/{{$c->id}}" target="_blank" class="navi-link">
                                                                                <span class="navi-text text-info">Imprimir Recibo</span>
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
                                                        <span class="kt-widget__label">Cliente:</span>
                                                        <a class="kt-widget__data text-success">@if($c->venda_id != null) {{ $c->venda->cliente->razao_social }} @else {{ $c->cliente->razao_social ?? '--' }} @endif</a>
                                                    </div>
                                                    <div class="kt-widget__info">
                                                        <span class="kt-widget__label">Categoria:</span>
                                                        <a class="kt-widget__data text-success">{{$c->categoria->nome}}</a>
                                                    </div>
                                                    <div class="kt-widget__info">
                                                        <span class="kt-widget__label">Vencimento:</span>
                                                        <a class="kt-widget__data text-success">{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y')}}</a>
                                                    </div>

                                                    <div class="kt-widget__info">
                                                        <span class="kt-widget__label">Emissão:</span>
                                                        <a class="kt-widget__data text-success">
                                                            {{ $c->nf_data_emissao ? \Carbon\Carbon::parse($c->nf_data_emissao)->format('d/m/Y') : '--' }}
                                                        </a>
                                                    </div>

                                                    <div class="kt-widget__info">
                                                        <span class="kt-widget__label">Usuários (C/B):</span>
                                                        <span class="kt-widget__data text-dark">
                                                            {{ $c->usuario->nome ?? '--' }} / {{ $c->usuarioBaixa->nome ?? '--' }}
                                                        </span>
                                                    </div>

                                                    <div class="kt-widget__info">
                                                        <span class="kt-widget__label">Data Emissão:</span>
                                                        <span class="kt-widget__data">
                                                            {{ $c->nf_data_emissao ? \Carbon\Carbon::parse($c->nf_data_emissao)->format('d/m/Y') : '--' }}
                                                        </span>
                                                    </div>

                                                    <div class="kt-widget__info">
                                                        <span class="kt-widget__label">Estado:</span>
                                                        @if($c->status == true) <span class="label label-xl label-inline label-light-success">Pago</span>
                                                        @else <span class="label label-xl label-inline label-light-danger">Pendente</span> @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                            <?php $somaValor += $c->valor_integral; $somaPago += $c->valor_recebido; if(!$c->status) $somaPendente += $c->valor_integral; ?>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="d-flex flex-wrap py-2 mr-3">@if(isset($links)) {{$contas->links()}} @endif</div>
                    </div>

                    {{-- (GRUPO) RODAPÉ: Resumo financeiro e soma dos valores selecionados --}}
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="card card-custom gutter-b example example-compact">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-4">
                                                <h3 class="font-size-lg text-danger">A Receber: <strong>R$ {{number_format($somaPendente, 2, ',', '.') }}</strong></h3>
                                            </div>
                                            <div class="col-4">
                                                <h3 class="font-size-lg text-success">Recebido: <strong>R$ {{number_format($somaPago, 2, ',', '.') }}</strong></h3>
                                            </div>
                                            <div style="display: none" class="col-4 div-valor-selecionado">
                                                <h3 class="font-size-lg text-primary">Selecionado: <strong id="valor-selecionado">R$ 0,00</strong></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <script type="text/javascript">
        var BTNSELECIONA = false;
        var ADICIONADAS = [];
        var CONTAS = [];
        var SOMA = 0;

        $(function () {
            // Carrega as contas para o JS
            CONTAS = JSON.parse($('#contas').val());
        });

        // (GRUPO) FUNÇÃO SELECIONAR VÁRIOS: Ativa os checkboxes e prepara a soma
        $('#btn_seleciona_varios').click(function() {
            BTNSELECIONA = !BTNSELECIONA;

            if(BTNSELECIONA){
                // Ativa visualmente o botão e mostra as caixinhas de seleção
                $(this).removeClass('btn-light').addClass('btn-info');
                $('.checkbox').show(); // Mostra as divs que escondem o check
                $('.select').show();   // Garante que o input apareça
                $('.btn-action').hide();
                $('.div-valor-selecionado').show();
            } else {
                // Desativa tudo e limpa as seleções
                $(this).removeClass('btn-info').addClass('btn-light');
                $('.checkbox').hide();
                $('.div-valor-selecionado').hide();
                $('.btn-action').show();
                $('.select').prop('checked', false);
                ADICIONADAS = [];
                somaArray();
                verificaBotaoAcao();
            }
        });

        // (GRUPO) CLIQUE NO CHECKBOX: Monitora cada marcação individual
        $(document).on('click', '.select', function() {
            ADICIONADAS = [];
            // Percorre todos os checks marcados para montar o array de IDs e Valores
            $('.select:checked').each(function() {
                let id = $(this).attr('id').replace('sel_', '');
                // Busca os dados da conta no array global de CONTAS
                let conta = CONTAS.find(c => c.id == id);
                if(conta) ADICIONADAS.push(conta);
            });

            somaArray();
            verificaBotaoAcao();
        });

        // (GRUPO) SOMA: Calcula o total financeiro do que foi marcado
        function somaArray(){
            SOMA = 0;
            ADICIONADAS.map((a) => {
                SOMA += parseFloat(a.valor_integral);
            });
            $('#valor-selecionado').html(formatReal(SOMA));
        }

        // (GRUPO) BOTÕES DE AÇÃO: Mostra "Receber" e "Gerar Boletos" se houver 1 ou mais itens
        function verificaBotaoAcao(){
            if(ADICIONADAS.length >= 1){
                $('#btn_gerar').css('display', 'inline-block');
                $('#btn_receber').css('display', 'inline-block');
            } else {
                $('#btn_gerar').hide();
                $('#btn_receber').hide();
            }
        }

        function formatReal(v){
            return v.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});
        }

        // (GRUPO) AÇÕES FINAIS: Envio para o servidor
        $('#btn_receber').click(() => {
            swal("Atenção!", "Deseja receber estas contas?", "warning").then((sim) => {
                if(sim){
                    let temp = ADICIONADAS.map(a => a.id);
                    location.href = '/contasReceber/receberMultiplos/' + temp.join(',');
                }
            });
        });

        $('#btn_gerar').click(() => {
            let temp = ADICIONADAS.map(a => a.id);
            location.href = '/boleto/gerarMultiplos/' + temp.join(',');
        });
    </script>
@endsection
