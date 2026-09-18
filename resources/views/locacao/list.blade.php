@extends('default.layout')
@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-body">
            <br>
            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

                <!-- FORMULÁRIO DE PESQUISA COM CLIENTE E FORNECEDOR -->
                <form method="get" action="/locacao/pesquisa">
                    <div class="row align-items-center">

                        <div class="form-group col-lg-3 col-md-4 col-sm-6">
                            <label class="col-form-label">Cliente / Razão Social</label>
                            <div class="input-group">
                                <input type="text" name="cliente" class="form-control" value="{{{isset($cliente) ? $cliente : ''}}}" placeholder="Nome do Cliente"/>
                            </div>
                        </div>

                        <!-- NOVO FILTRO DE FORNECEDOR -->
                        <div class="form-group col-lg-3 col-md-4 col-sm-6">
                            <label class="col-form-label">Fornecedor (Sucata)</label>
                            <div class="input-group">
                                <input type="text" name="fornecedor" class="form-control" value="{{{isset($fornecedor) ? $fornecedor : ''}}}" placeholder="Nome do Fornecedor"/>
                            </div>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label">Tipo</label>
                            <select class="custom-select form-control" name="tipo">
                                <option value="">TODOS</option>
                                <option @if(isset($tipo) && $tipo == 'cacamba') selected @endif value="cacamba">Caçamba / Container</option>
                                <option @if(isset($tipo) && $tipo == 'equipamento') selected @endif value="equipamento">Equipamento / Máquina</option>
                            </select>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label">Status Logístico</label>
                            <select class="custom-select form-control" id="estado" name="estado">
                                <option @if(isset($estado) && $estado === '') selected @endif value="">TODOS</option>
                                <option @if(isset($estado) && $estado === '0') selected @endif value="0">AGENDADO / NOVO</option>
                                <option @if(isset($estado) && $estado === '1') selected @endif value="1">EM USO / NO CLIENTE</option>
                                <option @if(isset($estado) && $estado === '2') selected @endif value="2">AGUARDANDO RETIRADA</option>
                                <option @if(isset($estado) && $estado === '3') selected @endif value="3">FINALIZADO / RETIRADO</option>
                            </select>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label">Data Inicial</label>
                            <div class="input-group date">
                                <input type="text" name="data_inicial" class="form-control date-out" readonly value="{{{isset($dataInicial) ? $dataInicial : ''}}}" id="kt_datepicker_3" />
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="la la-calendar"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label">Data Final</label>
                            <div class="input-group date">
                                <input type="text" name="data_final" class="form-control" readonly value="{{{isset($dataFinal) ? $dataFinal : ''}}}" id="kt_datepicker_3" />
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="la la-calendar"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-1 col-xl-1 mt-2 mt-lg-0">
                            <button style="margin-top: 13px;" class="btn btn-light-primary px-6 font-weight-bold">
                                <i class="la la-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
                <br>
                <h4>Controle de Locações e Caçambas</h4>
                <label>Total de registros: {{sizeof($locacoes)}}</label>

                <div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                    <div class="col-12">
                        <div class="row">
                            <a href="/locacao/novo" class="btn btn-success ml-3 mb-3">
                                <i class="la la-plus"></i> Nova Locação
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    @foreach($locacoes as $e)
                        <div class="col-sm-12 col-lg-6 col-md-6 col-xl-6">
                            <div class="card card-custom gutter-b example example-compact border-top-3 @if($e->status == 2) border-warning @elseif($e->status == 1) border-primary @elseif($e->status == 3) border-success @else border-secondary @endif">
                                <div class="card-header">
                                    <div class="card-title">
                                        <h3 class="card-title font-weight-bold text-dark">
                                            R$ {{number_format($e->total, 2, ',', '.')}}
                                            <small class="ml-2 font-weight-bold text-muted">
                                                ({{$e->tipo == 'cacamba' ? 'Caçamba/Container' : 'Equipamento'}})
                                            </small>
                                        </h3>
                                    </div>

                                    <div class="card-toolbar">
                                        <div class="dropdown dropdown-inline" data-toggle="tooltip" title="Ações">
                                            <a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-ellipsis-h"></i>
                                            </a>
                                            <div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-right">
                                                <ul class="navi navi-hover">
                                                    <li class="navi-header font-weight-bold py-4">
                                                        <span class="font-size-lg">Operações Logísticas:</span>
                                                    </li>
                                                    <li class="navi-separator mb-3 opacity-70"></li>

                                                    @if($e->status == 0)
                                                        <li class="navi-item">
                                                            <a href="#!" onclick="abrirModalLogistica('/locacao/marcarComoEntregue/{{$e->id}}', 'Agendar Entrega no Cliente/Local')" class="navi-link">
                                                                <span class="navi-text text-primary font-weight-bold"><i class="la la-truck text-primary mr-2"></i> Confirmar Entrega</span>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if($e->status == 1)
                                                        <li class="navi-item">
                                                            <a href="#!" onclick="abrirModalLogistica('/locacao/solicitarRetirada/{{$e->id}}', 'Agendar Solicitação de Retirada')" class="navi-link">
                                                                <span class="navi-text text-warning font-weight-bold"><i class="la la-exclamation-triangle text-warning mr-2"></i> Solicitar Retirada</span>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    @if($e->status == 1 || $e->status == 2)
                                                        <li class="navi-item">
                                                            <a href="/locacao/finalizarLocacao/{{$e->id}}" class="navi-link">
                                                                <span class="navi-text text-success font-weight-bold"><i class="la la-check-circle text-success mr-2"></i> Retirar e Finalizar</span>
                                                            </a>
                                                        </li>
                                                    @endif

                                                    <li class="navi-separator my-3 opacity-70"></li>
                                                    <li class="navi-item">
                                                        <a href="/locacao/edit/{{$e->id}}" class="navi-link">
                                                            <span class="navi-text"><span class="label label-xl label-inline label-light-warning">Editar Cadastrais</span></span>
                                                        </a>
                                                    </li>
                                                    <li class="navi-item">
                                                        <a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/locacao/delete/{{ $e->id }}" }else{return false} })' href="#!" class="navi-link">
                                                            <span class="navi-text"><span class="label label-xl label-inline label-light-danger">Excluir</span></span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="kt-widget__info mb-2">
								<span class="kt-widget__label font-weight-bold">
									{{ $e->finalidade == 'coleta_fornecedor' ? 'Fornecedor:' : 'Cliente:' }}
								</span>
                                        <strong class="text-dark">
                                            {{ $e->cliente ? $e->cliente->razao_social : ($e->fornecedor ? $e->fornecedor->razao_social : '---') }}
                                        </strong>
                                    </div>

                                    <!-- ITENS / PATRIMÔNIOS ALOCADOS -->
                                    <div class="kt-widget__info mb-2 p-2 rounded bg-light-secondary">
                                        <span class="kt-widget__label font-weight-bold text-primary">Equipamento(s) / Patrimônio:</span>
                                        <div class="mt-1">
                                            @forelse($e->itens as $item)
                                                <span class="badge badge-light-primary font-weight-bold mb-1">
											<i class="la la-box text-primary"></i> {{$item->produto->nome}}
                                                    @if($item->codigo_patrimonio)
                                                        <strong class="text-danger">({{$item->codigo_patrimonio}})</strong>
                                                    @endif
										</span>
                                            @empty
                                                <span class="text-muted font-italic font-size-sm">Nenhum item adicionado</span>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="kt-widget__info mb-2">
                                        <span class="kt-widget__label font-weight-bold">Situação:</span>
                                        @if($e->status == 0)
                                            <span class="label label-xl label-inline label-light-primary">0 - AGENDADO / NOVO</span>
                                        @elseif($e->status == 1)
                                            <span class="label label-xl label-inline label-light-info">1 - EM USO / NO CLIENTE</span>
                                        @elseif($e->status == 2)
                                            <span class="label label-xl label-inline label-light-warning font-weight-bold">2 - AGUARDANDO RETIRADA</span>
                                        @elseif($e->status == 3)
                                            <span class="label label-xl label-inline label-light-success">3 - FINALIZADO / RETIRADO</span>
                                        @endif
                                    </div>

                                    @if($e->rua_entrega)
                                        <div class="kt-widget__info mb-2">
                                            <span class="kt-widget__label font-weight-bold">Endereço Obra:</span>
                                            <small class="text-muted">{{$e->rua_entrega}}, {{$e->numero_entrega}} - {{$e->bairro_entrega}}</small>
                                        </div>
                                    @endif

                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">Início / Entrega:</span>
                                        <span class="text-dark font-weight-bold">
									{{\Carbon\Carbon::parse($e->inicio)->format('d/m/Y')}}
                                            @if($e->data_entrega) <small class="text-success">({{\Carbon\Carbon::parse($e->data_entrega)->format('H:i')}}h)</small> @endif
								</span>
                                    </div>

                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">Término / Retirada:</span>
                                        <span class="text-dark font-weight-bold">
									@if($e->fim != '1969-12-31' && $e->fim != null)
                                                {{\Carbon\Carbon::parse($e->fim)->format('d/m/Y')}}
                                            @else
                                                <span class="text-muted">A definir (Sob demanda)</span>
                                            @endif
								</span>
                                    </div>
                                </div>

                                <div class="card-footer d-flex justify-content-between align-items-center">
                                    <a href="/locacao/itens/{{$e->id}}" class="btn btn-light-primary font-weight-bold">
                                        <i class="la la-list"></i> Itens / Patrimônios
                                    </a>

                                    <!-- BOTEES DE ACAO RAPIDA COM MODAL LOGISTICO -->
                                    @if($e->status == 1)
                                        <button type="button" onclick="abrirModalLogistica('/locacao/solicitarRetirada/{{$e->id}}', 'Agendar Busca de Caçamba/Equipamento')" class="btn btn-warning btn-sm font-weight-bold">
                                            <i class="la la-phone"></i> Cliente pediu busca
                                        </button>
                                    @elseif($e->status == 2)
                                        <a href="/locacao/finalizarLocacao/{{$e->id}}" class="btn btn-success btn-sm font-weight-bold">
                                            <i class="la la-truck"></i> Confirmar Retirada
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex flex-wrap py-2 mr-3">
                        @if(isset($links))
                            {{$locacoes->links()}}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE AGENDAMENTO DE TRANSPORTE (ENTREGA / RETIRADA) -->
    <div class="modal fade" id="modalAgendamentoTransporte" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="modalAgendamentoTitle">Agendar Transporte</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">x</button>
                </div>
                <form id="formAgendamentoTransporte" method="post" action="">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-12">
                                <label class="font-weight-bold">Data e Hora Prevista de Saída</label>
                                <input type="datetime-local" class="form-control" name="data_hora_saida" id="data_hora_saida" required value="{{ date('Y-m-d\TH:i') }}">
                            </div>

                            <!-- SELECT DE VEÍCULOS -->
                            <div class="form-group col-12">
                                <label class="font-weight-bold">Selecione o Veículo / Caminhão</label>
                                <select class="form-control select2-modal" style="width: 100%" id="select_veiculo_modal" name="veiculo_id" required>
                                    <option value="">Selecione o Veículo</option>
                                    @foreach(App\Models\Veiculo::where('empresa_id', request()->empresa_id ?? session('user_logged')->empresa_id)->get() as $v)
                                        <option value="{{$v->id}}">{{$v->placa}} - {{$v->modelo}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- SELECT DE MOTORISTAS -->
                            <div class="form-group col-12">
                                <label class="font-weight-bold">Selecione o Motorista</label>
                                <select class="form-control select2-modal" style="width: 100%" id="select_motorista_modal" name="motorista_id" required>
                                    <option value="">Selecione o Motorista</option>
                                    @foreach(App\Models\Funcionario::where('empresa_id', request()->empresa_id ?? session('user_logged')->empresa_id)->get() as $m)
                                        <option value="{{$m->id}}">{{$m->nome}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success font-weight-bold">Confirmar Agendamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @section('javascript')
        <script type="text/javascript">
            function abrirModalLogistica(urlAcao, titulo) {
                $('#formAgendamentoTransporte').attr('action', urlAcao);
                $('#modalAgendamentoTitle').text(titulo);
                $('#modalAgendamentoTransporte').modal('show');
            }

            $('#modalAgendamentoTransporte').on('shown.bs.modal', function () {
                $('.select2-modal').select2({
                    dropdownParent: $('#modalAgendamentoTransporte')
                });
            });
        </script>
    @endsection
@endsection
