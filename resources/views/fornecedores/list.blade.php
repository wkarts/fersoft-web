@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
            <div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">
                <a href="/fornecedores/new" class="btn btn-lg btn-success">
                    <i class="fa fa-plus"></i> Novo Fornecedor
                </a>
            </div>
        </div>
        <br>

        <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
            <form method="get" action="/fornecedores/pesquisa">
                <div class="row align-items-center">
                    <div class="col-lg-2 col-xl-2">
                        <div class="row align-items-center">
                            <div class="col-md-12 my-2 my-md-0">
                                <div class="input-group">
                                    <select name="tipo_pesquisa" class="custom-select">
                                        @foreach(App\Models\Cliente::tiposPesquisa() as $key => $t)
                                        <option @isset($tipoPesquisa) @if($tipoPesquisa == $key) selected @endif @endif value="{{$key}}">{{$t}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-xl-5">
                        <div class="row align-items-center">
                            <div class="col-md-12 my-2 my-md-0">
                                <div class="input-group">
                                    <input type="text" name="pesquisa" class="form-control" placeholder="Pesquisa fornecedor" value="{{{ isset($pesquisa) ? $pesquisa : ''}}}">
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
            <h4>Lista de Fornecedores</h4>
            <label>Total de registros: {{count($fornecedores)}}</label>
            
            <div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
                <div class="wizard-nav">
                    <div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
                        <div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
                            <div class="wizard-label">
                                <h3 class="wizard-title">
                                    <span><i style="font-size: 40px" class="la la-table"></i> Tabela</span>
                                </h3>
                                <div class="wizard-bar"></div>
                            </div>
                        </div>
                        <div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
                            <div class="wizard-label">
                                <h3 class="wizard-title">
                                    <span><i style="font-size: 40px" class="la la-tablet"></i> Grade</span>
                                </h3>
                                <div class="wizard-bar"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pb-5" data-wizard-type="step-content">
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
                                <table class="datatable-table" style="max-width: 100%; overflow: scroll">
                                    <thead class="datatable-head">
                                        <tr class="datatable-row">
                                            <th class="datatable-cell"><span style="width: 150px;">AÇÕES</span></th>
                                            <th class="datatable-cell"><span style="width: 250px;">RAZÃO SOCIAL</span></th>
                                            <th class="datatable-cell"><span style="width: 150px;">CPF/CNPJ</span></th>
                                            <th class="datatable-cell"><span style="width: 100px;">IE/RG</span></th>
                                            <th class="datatable-cell"><span style="width: 200px;">CIDADE</span></th>
                                        </tr>
                                    </thead>
                                    <tbody id="body" class="datatable-body">
                                        @foreach($fornecedores as $c)
                                        <tr class="datatable-row">
                                            <td class="datatable-cell">
                                                <span style="width: 150px;">
                                                    <a class="btn btn-info btn-sm" title="Histórico Financeiro" onclick="abrirHistorico({{ $c->id }})" href="#!">
                                                        <i class="la la-history"></i>    
                                                    </a>
                                                    <a class="btn btn-warning btn-sm" onclick='swal("Atenção!", "Deseja editar?", "warning").then((sim) => {if(sim){ location.href="/fornecedores/edit/{{ $c->id }}" }})' href="#!">
                                                        <i class="la la-edit"></i>    
                                                    </a>
                                                    <a class="btn btn-danger btn-sm" onclick='swal("Atenção!", "Deseja remover?", "warning").then((sim) => {if(sim){ location.href="/fornecedores/delete/{{ $c->id }}" }})' href="#!">
                                                        <i class="la la-trash"></i>    
                                                    </a>
                                                </span>
                                            </td>
                                            <td class="datatable-cell"><span style="width: 250px;">{{$c->razao_social}}</span></td>
                                            <td class="datatable-cell"><span style="width: 150px;">{{$c->cpf_cnpj}}</span></td>
                                            <td class="datatable-cell"><span style="width: 100px;">{{$c->ie_rg}}</span></td>
                                            <td class="datatable-cell"><span style="width: 200px;">{{$c->cidade->nome}} ({{$c->cidade->uf}})</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pb-5" data-wizard-type="step-content">
                    <div class="row">
                        @foreach($fornecedores as $c)
                        <div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
                            <div class="card card-custom gutter-b example example-compact">
                                <div class="card-header">
                                    <div class="card-title">
                                        <h3 style="font-size: 12px;" class="card-title">{{substr($c->razao_social, 0, 30)}}</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <div class="dropdown dropdown-inline" data-toggle="tooltip" title="Ações" data-placement="left">
                                            <a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown">
                                                <i class="fa fa-ellipsis-h"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-md dropdown-menu-left">
                                                <ul class="navi navi-hover">
                                                    <li class="navi-header font-weight-bold py-4"><span class="font-size-lg">Ações:</span></li>
                                                    <li class="navi-separator mb-3 opacity-70"></li>
                                                    <li class="navi-item">
                                                        <a onclick="abrirHistorico({{ $c->id }})" href="#!" class="navi-link">
                                                            <span class="navi-icon"><i class="la la-history text-info"></i></span>
                                                            <span class="navi-text text-info font-weight-bold">Histórico</span>
                                                        </a>
                                                    </li>
                                                    <li class="navi-item">
                                                        <a href="/fornecedores/edit/{{$c->id}}" class="navi-link">
                                                            <span class="navi-icon"><i class="la la-edit text-primary"></i></span>
                                                            <span class="navi-text">Editar</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">CNPJ/CPF:</span>
                                        <a class="kt-widget__data text-success">{{ $c->cpf_cnpj }}</a>
                                    </div>
                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">IE/RG:</span>
                                        <a class="kt-widget__data text-success">{{$c->ie_rg}}</a>
                                    </div>
                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">Cidade:</span>
                                        <a class="kt-widget__data text-success">{{$c->cidade->nome}}</a>
                                    </div>
                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">UF:</span>
                                        <a class="kt-widget__data text-success">{{$c->cidade->uf}}</a>
                                    </div>
                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">Telefone:</span>
                                        <a class="kt-widget__data text-success">{{$c->telefone}}</a>
                                    </div>
                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">Email:</span>
                                        <a class="kt-widget__data text-success">{{$c->email}}</a>
                                    </div>
                                    <div class="kt-widget__info">
                                        <span class="kt-widget__label">Data Cadastro:</span>
                                        <a class="kt-widget__data text-success">
                                            {{ \Carbon\Carbon::parse($c->created_at)->format('d/m/Y H:i')}}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_historico" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="la la-history text-white mr-2"></i> Histórico: <span id="nome_fornecedor_modal"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Emissão</th>
                                <th>Nº NF</th>
                                <th>Vencimento</th>
                                <th>Pagamento</th>
                                <th>Valor Total</th>
                                <th>Valor Pago</th>
                                <th>Falta</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tabela_historico_corpo"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
function abrirHistorico(id){
    $('#tabela_historico_corpo').html('<tr><td colspan="8" class="text-center">Carregando...</td></tr>');
    $('#modal_historico').modal('show');

    $.get('/fornecedores/historico/' + id)
    .done(function(data){
        $('#nome_fornecedor_modal').text(data.fornecedor);
        let linhas = '';
        if(data.historico.length == 0){
            linhas = '<tr><td colspan="8" class="text-center">Nenhum registro encontrado.</td></tr>';
        } else {
            data.historico.forEach(function(item){
                let badge = item.status == 'Pago' ? 'badge-success' : 'badge-warning';
                linhas += `<tr>
                    <td>${item.emissao}</td>
                    <td><strong>${item.nf}</strong></td>
                    <td>${item.vencimento}</td>
                    <td>${item.pagamento}</td>
                    <td>R$ ${item.valor_total}</td>
                    <td>R$ ${item.valor_pago}</td>
                    <td class="${item.falta != '0,00' ? 'text-danger font-weight-bold' : 'text-success'}">R$ ${item.falta}</td>
                    <td><span class="badge ${badge} badge-inline">${item.status}</span></td>
                </tr>`;
            });
        }
        $('#tabela_historico_corpo').html(linhas);
    });
}
</script>
@endsection