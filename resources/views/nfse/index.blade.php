@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-document-fiscal.css') }}?v={{ filemtime(public_path('css/ui-document-fiscal.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-fiscal-mtr-refinements.css') }}?v={{ filemtime(public_path('css/ui-fiscal-mtr-refinements.css')) }}">
@endpush

@section('css')
    <style type="text/css">
        body.loading .modal-loading {
            display: block;
        }

        .modal-loading {
            display: none;
            position: fixed;
            z-index: 10000;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background: rgba(255, 255, 255, 0.8) url("/loading.gif") 50% 50% no-repeat;
        }

        /* Cards de Estatísticas Modernos */
        .metric-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            transition: all 0.25s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        /* Tabela e Badges */
        .table-nfse {
            border-collapse: separate;
            border-spacing: 0 6px;
        }
        .table-nfse tbody tr {
            background: #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease;
        }
        .table-nfse tbody tr:hover {
            background: #f8fafc;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .badge-status {
            font-weight: 600;
            letter-spacing: 0.3px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
        }
        .btn-action-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin: 0 2px;
            transition: transform 0.15s ease;
        }
        .btn-action-icon:hover {
            transform: scale(1.1);
        }
    </style>
@endsection

@section('content')
    <div class="document-fiscal-page document-fiscal-page--nfse ui-op-refine card card-custom gutter-b shadow-sm rounded-xl">
        <div class="card-body p-6">
            <input type="hidden" id="_token" value="{{ csrf_token() }}">
            <div class="ui-op-heading"><h3>NFS-e</h3></div>

            <!-- Topo: Filtros de Busca -->
            <form class="ui-op-form-filters @if(env('ANIMACAO')) animate__animated @endif animate__fadeIn" method="get" action="/nfse/filtro">
                <div class="row align-items-end g-3">
                    <div class="form-group col-lg-4 col-md-12 mb-3">
                        <label class="form-label font-weight-bolder text-dark">Cliente (Tomador)</label>
                        <select class="form-control select2 rounded-lg" id="kt_select2_3" name="cliente_id">
                            <option value="null">Selecione o cliente</option>
                            @foreach($clientes as $c)
                                <option @isset($cliente_id) @if($cliente_id == $c->id) selected @endif @endif value="{{$c->id}}">
                                    {{$c->id}} - {{$c->razao_social}} ({{$c->cpf_cnpj}})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                        <label class="form-label font-weight-bolder text-dark">Data Inicial</label>
                        <div class="input-group">
                            <input type="text" name="data_inicial" class="form-control rounded-left-lg" readonly value="{{{isset($dataInicial) ? $dataInicial : ''}}}" id="kt_datepicker_3" />
                            <div class="input-group-append">
                                <span class="input-group-text bg-light"><i class="la la-calendar"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                        <label class="form-label font-weight-bolder text-dark">Data Final</label>
                        <div class="input-group">
                            <input type="text" name="data_final" class="form-control rounded-left-lg" readonly value="{{{isset($dataFinal) ? $dataFinal : ''}}}" id="kt_datepicker_3" />
                            <div class="input-group-append">
                                <span class="input-group-text bg-light"><i class="la la-calendar"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                        <label class="form-label font-weight-bolder text-dark">Situação</label>
                        <select class="custom-select form-control rounded-lg" id="estado" name="estado">
                            <option @if(isset($estado) && $estado == 'TODOS') selected @endif value="">TODOS</option>
                            <option @if(isset($estado) && $estado == 'novo') selected @endif value="novo">NOVO</option>
                            <option @if(isset($estado) && $estado == 'aprovado') selected @endif value="aprovado">APROVADAS</option>
                            <option @if(isset($estado) && $estado == 'rejeitado') selected @endif value="rejeitado">REJEITADAS</option>
                            <option @if(isset($estado) && $estado == 'cancelado') selected @endif value="cancelado">CANCELADAS</option>
                            <option @if(isset($estado) && $estado == 'processando') selected @endif value="processando">PROCESSANDO</option>
                        </select>
                    </div>

                    <div class="form-group col-lg-2 col-md-12 mb-3">
                        <button class="btn btn-primary btn-block font-weight-bolder rounded-lg shadow-sm">
                            <i class="la la-search mr-1"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>

            <hr class="ui-op-separator">

            <!-- Métricas Rápidas & Ação Principal -->
            <div class="row align-items-center ui-op-metrics mb-4">
                <div class="col-md-4 col-12 mb-3 mb-md-0 ui-op-metric">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50 symbol-light-primary mr-4">
                        <span class="symbol-label font-size-h4 font-weight-boldest text-primary">
                            <i class="la la-file-invoice-dollar text-primary"></i>
                        </span>
                        </div>
                        <div>
                            <div class="text-muted font-weight-bold font-size-sm">Volume Faturado</div>
                            <div class="text-dark font-size-h4 font-weight-boldest">R$ {{ moeda($total) }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-12 mb-3 mb-md-0 ui-op-metric">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50 symbol-light-success mr-4">
                        <span class="symbol-label font-size-h4 font-weight-boldest text-success">
                            <i class="la la-list-alt text-success"></i>
                        </span>
                        </div>
                        <div>
                            <div class="text-muted font-weight-bold font-size-sm">Notas Encontradas</div>
                            <div class="text-dark font-size-h4 font-weight-boldest">
                                {{ sizeof($data) }} @if(isset($links)) <span class="text-muted font-size-sm font-weight-normal">de {{ $data->total() }}</span> @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-12 text-md-right ui-op-main-action">
                    <a href="/nfse/create" class="btn btn-success font-weight-bolder px-5 py-3 rounded-lg shadow-sm">
                        <i class="la la-plus-circle font-size-h5"></i> Nova NFS-e
                    </a>
                </div>
            </div>

            <!-- Tabela Listagem -->
            <div class="table-responsive">
                <table class="table table-head-custom table-vertical-center table-nfse ui-op-table">
                    <thead>
                    <tr class="text-uppercase text-secondary font-weight-bold">
                        <th style="width: 40px;" class="text-center">#</th>
                        <th style="min-width: 220px;">Tomador / Cliente</th>
                        <th style="min-width: 120px;">Valor Serviço</th>
                        <th style="min-width: 130px;">Situação</th>
                        <th style="min-width: 150px;">Data Criação</th>
                        <th style="min-width: 90px;" class="text-center">Número</th>
                        <th style="min-width: 260px;" class="text-center">Ações</th>
                    </tr>
                    </thead>
                    <tbody id="body">
                    @forelse($data as $item)
                        <tr>
                            <td class="text-center">
                                <label class="checkbox checkbox-single checkbox-primary justify-content-center m-0">
                                    <input type="checkbox" class="check" id="test_{{$item->id}}" />
                                    <span></span>
                                </label>

                                <!-- Elementos com suporte para leitura via .html() e .val() -->
                                <span style="display: none;" id="id">{{ $item->id }}</span>
                                <span style="display: none;" id="numero_nfse">{{ $item->numero_nfse > 0 ? $item->numero_nfse : $item->id }}</span>
                                <span style="display: none;" class="estado_{{ $item->id }}">{{ $item->estado }}</span>
                            </td>

                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-dark font-weight-bold font-size-md">{{ $item->razao_social }}</span>
                                    <span class="text-muted font-size-sm">{{ $item->documento ?? 'Não informado' }}</span>
                                </div>
                            </td>

                            <td>
                                <span class="text-dark font-weight-bolder font-size-md">R$ {{ moeda($item->valor_total) }}</span>
                            </td>

                            <td>
                                @if($item->estado == 'novo')
                                    <span class="badge badge-status badge-light-primary text-primary">Novo</span>
                                @elseif($item->estado == 'aprovado')
                                    <span class="badge badge-status badge-light-success text-success">
                                    <i class="la la-check-circle text-success mr-1"></i> Autorizada
                                </span>
                                @elseif($item->estado == 'cancelado')
                                    <span class="badge badge-status badge-light-danger text-danger">Cancelada</span>
                                @elseif($item->estado == 'processando')
                                    <span class="badge badge-status badge-light-warning text-warning">Processando</span>
                                @elseif($item->estado == 'rejeitado')
                                    <span class="badge badge-status badge-light-danger text-danger d-inline-flex align-items-center">
                                    Rejeitada
                                    @if(!empty($item->motivo_rejeicao))
                                            <i class="la la-info-circle text-danger ml-2 cursor-pointer font-size-h6"
                                               onclick="verMotivoRejeicao('{{ addslashes($item->motivo_rejeicao) }}')"
                                               title="Ver Erro"></i>
                                        @endif
                                </span>
                                @endif
                            </td>

                            <td>
                            <span class="text-dark-75 font-weight-medium">
                                {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}
                            </span>
                            </td>

                            <td class="text-center">
                            <span class="font-weight-boldest text-dark font-size-h6">
                                {{ $item->numero_nfse > 0 ? $item->numero_nfse : '-' }}
                            </span>
                            </td>

                            <td class="text-center">
                                <div class="d-inline-flex align-items-center">
                                    @if($item->estado == 'novo' || $item->estado == 'rejeitado')
                                        <!-- Ações Rascunho / Rejeitado -->
                                        <button type="button" onclick="transmitir('{{ $item->id }}')" title="Transmitir para o Portal Nacional" class="btn btn-action-icon btn-success">
                                            <i class="la la-send"></i>
                                        </button>
                                        <a class="btn btn-action-icon btn-light-warning" title="Editar NFS-e" onclick='swal("Atenção!", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="/nfse/edit/{{ $item->id }}" }})' href="#!">
                                            <i class="la la-edit text-warning"></i>
                                        </a>
                                        <a class="btn btn-action-icon btn-light-danger" title="Excluir" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/nfse/delete/{{ $item->id }}" }})' href="#!">
                                            <i class="la la-trash text-danger"></i>
                                        </a>
                                    @endif

                                    @if($item->estado == 'aprovado')
                                        <!-- Botão Direto de Impressão DANFSe v2.0 -->
                                        <a target="_blank" href="/nfse/imprimir/{{$item->id}}" class="btn btn-action-icon btn-light-info" title="Imprimir DANFSe">
                                            <i class="la la-print text-info"></i>
                                        </a>

                                        <!-- Baixar XML Autorizado -->
                                        <a target="_blank" href="/nfse/baixarXml/{{$item->id}}" class="btn btn-action-icon btn-light-dark" title="Baixar XML Nacional">
                                            <i class="la la-download text-dark"></i>
                                        </a>

                                        <!-- Enviar por WhatsApp -->
                                        <a href="/nfse/enviar-whatsapp/{{$item->id}}" class="btn btn-action-icon btn-light-success" title="Enviar no WhatsApp">
                                            <i class="lab la-whatsapp text-success font-size-h5"></i>
                                        </a>
                                    @else
                                        <a title="Ver Rascunho / Simular DANFSe" target="_blank" class="btn btn-action-icon btn-light-secondary" href="/nfse/preview-rps/{{$item->id}}">
                                            <i class="la la-eye text-muted"></i>
                                        </a>

                                    @endif

                                    <!-- Clonar Nota -->
                                    <a title="Duplicar NFS-e" class="btn btn-action-icon btn-light-primary" href="/nfse/clone/{{$item->id}}">
                                        <i class="la la-copy text-primary"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="la la-inbox font-size-h1 d-block mb-2"></i>
                                Nenhuma NFS-e localizada com os filtros selecionados.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Barra Inferior de Lote -->
            @if($certificado != null)
                <div class="card bg-light border-0 rounded-xl p-4 mt-4">
                    <div class="row g-2 ui-op-batch">
                        <div class="col-lg-2 col-md-4 col-6 ui-op-batch-action">
                            <button type="button" id="btn-enviar" onclick="enviar()" class="btn btn-success btn-block font-weight-bold shadow-sm">
                                <i class="la la-paper-plane mr-1"></i> Transmitir
                            </button>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6 ui-op-batch-action">
                            <button type="button" id="btn-imprimir" onclick="imprimir()" class="btn btn-info btn-block font-weight-bold shadow-sm">
                                <i class="la la-print mr-1"></i> Imprimir Lote
                            </button>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6 ui-op-batch-action">
                            <button type="button" id="btn-consultar" onclick="consultar()" class="btn btn-secondary btn-block font-weight-bold">
                                <i class="la la-sync mr-1"></i> Consultar
                            </button>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6 ui-op-batch-action">
                            <button type="button" id="btn-cancelar" data-toggle="modal" data-target="#modal1" onclick="setarNumero()" class="btn btn-danger btn-block font-weight-bold shadow-sm">
                                <i class="la la-times-circle mr-1"></i> Cancelar
                            </button>
                        </div>
                        <div class="col-lg-2 col-md-4 col-6 ui-op-batch-action">
                            <button type="button" id="btn-xml" onclick="setarNumero(true)" class="btn btn-warning btn-block font-weight-bold shadow-sm" data-toggle="modal" data-target="#modal5">
                                <i class="la la-envelope mr-1"></i> Enviar XML
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <input type="hidden" id="semCertificado" value="true">
            @endif

            <!-- Paginação -->
            @if(isset($links))
                <div class="d-flex justify-content-between align-items-center mt-4">
                    {{ $data->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Cancelar -->
    <div class="modal fade document-fiscal-dialog" id="modal1" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content rounded-xl shadow-lg border-0">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bolder text-white">
                        <i class="la la-exclamation-triangle mr-2 text-white"></i> Cancelar NFS-e <span id="numero_cancelamento"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">×</button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group mb-0">
                        <label class="form-label font-weight-bolder">Motivo do Cancelamento</label>
                        <select class="form-control custom-select rounded-lg" id="motivo">
                            <option value="1">1 - Erro na emissão</option>
                            <option value="2">2 - Serviço não prestado</option>
                            <option value="4">4 - Duplicidade de nota</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light-dark font-weight-bold" data-dismiss="modal">Fechar</button>
                    <button type="button" id="btn-cancelar-2" onclick="cancelar()" class="btn btn-danger font-weight-bold px-4">
                        Confirmar Cancelamento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Enviar Email -->
    <div class="modal fade document-fiscal-dialog" id="modal5" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content rounded-xl shadow-lg border-0">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title font-weight-bolder text-white">
                        <i class="la la-envelope mr-2 text-white"></i> Enviar XML da NFS-e <span id="numero_email"></span>
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">×</button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group mb-0">
                        <label class="form-label font-weight-bolder">E-mail do Destinatário</label>
                        <input type="email" id="email" placeholder="cliente@exemplo.com.br" name="email" class="form-control rounded-lg">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light-dark font-weight-bold" data-dismiss="modal">Fechar</button>
                    <button type="button" id="btn-send" onclick="enviarEmailXMl()" class="btn btn-warning font-weight-bold px-4 text-white">
                        Enviar Arquivo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-loading loading-class"></div>
@endsection

@section('javascript')
    @if(empty($config->integracao_nfse) || $config->integracao_nfse == 'webmania')
        <script type="text/javascript" src="/js/nfse_envio.js?v={{ time() }}"></script>
    @else
        <script type="text/javascript" src="/js/nfse_integra_notas.js?v={{ time() }}"></script>
    @endif

    <script type="text/javascript">
        function verMotivoRejeicao(motivo) {
            swal({
                title: "Rejeição da NFS-e",
                text: motivo,
                icon: "error",
                button: "Entendido, vou corrigir"
            });
        }
        function enviar() {
            let notas_selecionadas = [];

            // Verifica todos os inputs dentro da tabela que estejam selecionados
            $('.table-nfse tbody input[type="checkbox"]:checked').each(function() {
                // Encontra o ID da nota escondido no span da mesma linha
                let id_nota = $(this).closest('td').find('#id').text();
                if (id_nota) {
                    notas_selecionadas.push(id_nota);
                }
            });

            if (notas_selecionadas.length === 0) {
                swal("Atenção", "Selecione pelo menos uma NFS-e na tabela clicando na caixa correspondente.", "warning");
                return;
            }

            swal({
                title: "Transmitir NFS-e",
                text: "Deseja transmitir " + notas_selecionadas.length + " nota(s) para a Prefeitura?",
                icon: "info",
                buttons: ["Cancelar", "Transmitir"]
            }).then((sim) => {
                if (sim) {
                    $('.modal-loading').show();
                    // Processa a primeira da fila
                    transmitir(notas_selecionadas[0]);
                    // Dica: Para enviar em lote, precisará de criar uma rotina no JS ou enviar o array inteiro para uma nova rota do Controller.
                }
            });
        }
    </script>
@endsection
