@extends('default.layout')
@section('content')

    <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-6 pb-2">
            <h3 class="card-title font-weight-bolder text-dark">
                <i class="fa fa-list text-primary mr-2 font-size-h3"></i> Naturezas de Operação
            </h3>
            <div class="card-toolbar">

                <!-- Botões de Alternância de Visualização -->
                <div class="btn-group mr-4" role="group" aria-label="Visualização">
                    <button type="button" class="btn btn-light-primary font-weight-bold btn-sm" id="btn-view-tabela" data-toggle="tooltip" title="Visualização em Tabela">
                        <i class="fa fa-table"></i> Tabela
                    </button>
                    <button type="button" class="btn btn-light-primary font-weight-bold btn-sm" id="btn-view-grade" data-toggle="tooltip" title="Visualização em Grade">
                        <i class="fa fa-th-large"></i> Grade
                    </button>
                </div>

                <a href="/naturezaOperacao/new" class="btn btn-primary font-weight-bold btn-sm">
                    <i class="fa fa-plus"></i> Nova Natureza
                </a>
            </div>
        </div>

        <div class="card-body pt-3">
            <div class="mb-4">
                <span class="text-muted font-weight-bold font-size-sm">Total de registos encontrados: <span class="text-dark">{{count($naturezas)}}</span></span>
            </div>

            <!-- ============================================== -->
            <!-- VISUALIZAÇÃO EM TABELA -->
            <!-- ============================================== -->
            <div id="view_tabela" class="table-responsive" style="display: none;">
                <table class="table table-head-custom table-vertical-center" id="kt_advance_table_widget_1">
                    <thead>
                    <tr class="text-left">
                        <th style="min-width: 200px">Descrição da Natureza</th>
                        <th style="min-width: 100px">Saída (Est/Int)</th>
                        <th style="min-width: 100px">Entrada (Est/Int)</th>
                        <th style="min-width: 120px">Finalidade</th>
                        <th style="min-width: 100px">Sobrescreve CFOP</th>
                        <th style="min-width: 100px">Movimenta Estoque</th>
                        <th class="pr-0 text-right" style="min-width: 120px">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($naturezas as $n)
                        <tr>
                            <td class="pl-0">
                                <a href="/naturezaOperacao/edit/{{$n->id}}" class="text-dark-75 font-weight-bolder text-hover-primary mb-1 font-size-lg">{{$n->natureza}}</a>
                            </td>
                            <td>
                                <span class="text-dark-75 font-weight-bolder d-block font-size-lg">{{$n->CFOP_saida_estadual}} / {{$n->CFOP_saida_inter_estadual}}</span>
                            </td>
                            <td>
                                <span class="text-dark-75 font-weight-bolder d-block font-size-lg">{{$n->CFOP_entrada_estadual}} / {{$n->CFOP_entrada_inter_estadual}}</span>
                            </td>
                            <td>
                                <span class="text-dark-75 font-weight-bolder d-block font-size-md">{{$n->getFinalidade()}}</span>
                            </td>
                            <td>
                                @if($n->sobrescreve_cfop)
                                    <span class="label label-lg label-light-success label-inline font-weight-bold">SIM</span>
                                @else
                                    <span class="label label-lg label-light-danger label-inline font-weight-bold">NÃO</span>
                                @endif
                            </td>
                            <td>
                                @if(!$n->nao_movimenta_estoque)
                                    <span class="label label-lg label-light-success label-inline font-weight-bold">SIM</span>
                                @else
                                    <span class="label label-lg label-light-danger label-inline font-weight-bold">NÃO</span>
                                @endif
                            </td>
                            <td class="pr-0 text-right">
                                <!-- Ícones corrigidos para fa fa-edit e fa fa-trash -->
                                <a href="/naturezaOperacao/edit/{{$n->id}}" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-1" data-toggle="tooltip" title="Editar">
                                    <i class="fa fa-edit text-primary"></i>
                                </a>
                                <a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/naturezaOperacao/delete/{{$n->id}}" }else{return false} })' href="#!" class="btn btn-icon btn-light btn-hover-danger btn-sm" data-toggle="tooltip" title="Excluir">
                                    <i class="fa fa-trash text-danger"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <!-- ============================================== -->
            <!-- VISUALIZAÇÃO EM GRADE (CARDS ORIGINAIS) -->
            <!-- ============================================== -->
            <div id="view_grade" style="display: none;">
                <div class="row">
                    @foreach($naturezas as $n)
                        <div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
                            <div class="card card-custom gutter-b example example-compact border">
                                <div class="card-header">
                                    <div class="card-title">
                                        <h3 style="font-size: 14px; margin: 0;" class="card-title text-dark-75 font-weight-bolder">
                                            {{$n->natureza}}
                                        </h3>
                                    </div>

                                    <div class="card-toolbar">
                                        <div class="dropdown dropdown-inline" data-toggle="tooltip" title="Ações" data-placement="left">
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
                                                        <a href="/naturezaOperacao/edit/{{$n->id}}" class="navi-link">
                                                    <span class="navi-text">
                                                        <span class="label label-xl label-inline label-light-warning"><i class="fa fa-edit mr-2"></i> Editar</span>
                                                    </span>
                                                        </a>
                                                    </li>
                                                    <li class="navi-item mt-2">
                                                        <a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/naturezaOperacao/delete/{{$n->id}}" }else{return false} })' href="#!" class="navi-link">
                                                    <span class="navi-text">
                                                        <span class="label label-xl label-inline label-light-danger"><i class="fa fa-trash mr-2"></i> Excluir</span>
                                                    </span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div class="kt-widget__info mb-2">
                                        <span class="kt-widget__label text-muted font-weight-bold">CFOP saída estadual:</span>
                                        <a class="kt-widget__data text-success font-weight-bolder">{{$n->CFOP_saida_estadual}}</a>
                                    </div>
                                    <div class="kt-widget__info mb-2">
                                        <span class="kt-widget__label text-muted font-weight-bold">CFOP entrada estadual:</span>
                                        <a class="kt-widget__data text-success font-weight-bolder">{{$n->CFOP_entrada_estadual}}</a>
                                    </div>
                                    <div class="kt-widget__info mb-2">
                                        <span class="kt-widget__label text-muted font-weight-bold">CFOP saída interestadual:</span>
                                        <a class="kt-widget__data text-success font-weight-bolder">{{$n->CFOP_saida_inter_estadual}}</a>
                                    </div>
                                    <div class="kt-widget__info mb-2">
                                        <span class="kt-widget__label text-muted font-weight-bold">CFOP entrada interestadual:</span>
                                        <a class="kt-widget__data text-success font-weight-bolder">{{$n->CFOP_entrada_inter_estadual}}</a>
                                    </div>

                                    <div class="kt-widget__info mb-2 mt-4">
                                        <span class="kt-widget__label text-muted font-weight-bold">Finalidade:</span>
                                        <span class="kt-widget__data text-dark-75">{{$n->getFinalidade()}}</span>
                                    </div>

                                    <div class="kt-widget__info mb-2">
                                        <span class="kt-widget__label text-muted font-weight-bold">Sobrescrever CFOP:</span>
                                        @if($n->sobrescreve_cfop)
                                            <span class="label label-sm label-inline label-light-success">SIM</span>
                                        @else
                                            <span class="label label-sm label-inline label-light-danger">NÃO</span>
                                        @endif
                                    </div>

                                    <div class="kt-widget__info mb-2">
                                        <span class="kt-widget__label text-muted font-weight-bold">Movimenta estoque:</span>
                                        @if(!$n->nao_movimenta_estoque)
                                            <span class="label label-sm label-inline label-light-success">SIM</span>
                                        @else
                                            <span class="label label-sm label-inline label-light-danger">NÃO</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

@endsection

@section('javascript')
    <script>
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();

            // Verifica a preferência do usuário armazenada no navegador (padrão é tabela)
            let currentView = localStorage.getItem('natureza_operacao_view') || 'tabela';

            function setView(viewType) {
                if(viewType === 'grade') {
                    $('#view_tabela').hide();
                    $('#view_grade').fadeIn();

                    // Estilo dos botões
                    $('#btn-view-grade').removeClass('btn-light-primary').addClass('btn-primary');
                    $('#btn-view-tabela').removeClass('btn-primary').addClass('btn-light-primary');
                } else {
                    $('#view_grade').hide();
                    $('#view_tabela').fadeIn();

                    // Estilo dos botões
                    $('#btn-view-tabela').removeClass('btn-light-primary').addClass('btn-primary');
                    $('#btn-view-grade').removeClass('btn-primary').addClass('btn-light-primary');
                }
                // Guarda a preferência
                localStorage.setItem('natureza_operacao_view', viewType);
            }

            // Aplica a visualização ao carregar a página
            setView(currentView);

            // Ações de clique
            $('#btn-view-grade').click(function() { setView('grade'); });
            $('#btn-view-tabela').click(function() { setView('tabela'); });
        });
    </script>
@endsection
