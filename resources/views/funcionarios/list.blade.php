@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
            <div class="col-12">
                <a href="/funcionarios/new" class="btn btn-lg btn-success">
                    <i class="fa fa-plus"></i>Novo Funcionario
                </a>
                <a href="/funcionarios/comissao" class="btn btn-lg btn-info">
                    <i class="fa fa-list"></i>Comissão
                </a>
                <!-- Botão de Importação Excel -->
                <a href="#" data-toggle="modal" data-target="#modal_import_excel" class="btn btn-lg btn-success" style="background-color: #1d6f42; border-color: #1d6f42;">
                    <i class="fa fa-file-excel"></i>Importar Excel
                </a>
            </div>
        <br>

        <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
            <br>
            <h4>Lista de Funcionários</h4>
            <label>Total de registros: {{count($funcionarios)}}</label>
            <div class="row">

                <div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
                    <div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
                        <div class="wizard-nav">
                            <div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
                                <div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
                                    <div class="wizard-label">
                                        <h3 class="wizard-title"><span><i style="font-size: 40px" class="la la-table"></i>Tabela</span></h3>
                                        <div class="wizard-bar"></div>
                                    </div>
                                </div>
                                <div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
                                    <div class="wizard-label" id="grade">
                                        <h3 class="wizard-title"><span><i style="font-size: 40px" class="la la-tablet"></i>Grade</span></h3>
                                        <div class="wizard-bar"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- VIEW TABELA --}}
                        <div class="pb-5" data-wizard-type="step-content">
                            <div class="row">
                                <div class="col-xl-12">
                                    <div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
                                        <table class="datatable-table" style="max-width: 100%; overflow: scroll">
                                            <thead class="datatable-head">
                                                <tr class="datatable-row" style="left: 0px;">
                                                    <th class="datatable-cell"><span style="width: 60px;">FOTO</span></th>
                                                    <th class="datatable-cell"><span style="width: 200px;">NOME</span></th>
                                                    <th class="datatable-cell"><span style="width: 120px;">FUNÇÃO</span></th>
                                                    <th class="datatable-cell"><span style="width: 100px;">CELULAR</span></th>
                                                    <th class="datatable-cell"><span style="width: 100px;">STATUS</span></th>
                                                    <th class="datatable-cell"><span style="width: 250px;">ENDEREÇO</span></th>
                                                    <th class="datatable-cell"><span style="width: 150px;">AÇÕES</span></th>
                                                </tr>
                                            </thead>
                                            <tbody id="body" class="datatable-body">
                                                @foreach($funcionarios as $f)
                                                @php 
                                                    $aniversariante = $f->data_nascimento && \Carbon\Carbon::parse($f->data_nascimento)->month == \Carbon\Carbon::now()->month;
                                                @endphp
                                                <tr class="datatable-row">
                                                    <td class="datatable-cell">
                                                        <span style="width: 60px;">
                                                            @if($f->foto_funcionario)
                                                                <img src="{{ asset('imgs_funcionarios/'.$f->foto_funcionario) }}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #EEE">
                                                            @else
                                                                <div class="symbol symbol-40 symbol-light-primary"><span class="symbol-label font-size-h4">{{ substr($f->nome, 0, 1) }}</span></div>
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td class="datatable-cell">
                                                        <span style="width: 200px;">
                                                            <strong>{{$f->nome}}</strong>
                                                            @if($aniversariante)
                                                                <span class="label label-warning label-inline ml-2 animate__animated animate__infinite animate__pulse">🎂 Niver!</span>
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td class="datatable-cell">
                                                      <span style="width: 120px;">
                                                          {{ optional($f->getRelation('funcao'))->nome ?? $f->funcao_id ?? '--' }}

                                                          {{-- Alerta de CNH --}}
                                                          @if($f->vencimento_cnh)
                                                              @php
                                                                  $hoje = \Carbon\Carbon::now();
                                                                  $vencimento = \Carbon\Carbon::parse($f->vencimento_cnh);
                                                                  $diasParaVencer = $hoje->diffInDays($vencimento, false);
                                                              @endphp

                                                              @if($diasParaVencer <= 0)
                                                                  <span class="label label-danger label-inline font-weight-bold" title="CNH Vencida!">CNH VENCIDA!</span>
                                                              @elseif($diasParaVencer <= 30)
                                                                  <span class="label label-warning label-inline font-weight-bold" title="Vence em {{ $diasParaVencer }} dias">VENCE EM {{ $diasParaVencer }}D</span>
                                                              @endif
                                                          @endif
                                                      </span>
                                                  </td>
                                                    
                                                    <td class="datatable-cell"><span style="width: 100px;">{{ $f->telefone ?? '--' }}</span></td>
                                                    <td class="datatable-cell">
                                                        <span style="width: 100px;">
                                                            <span class="label label-inline {{ $f->status_funcionario == 'Ativo' ? 'label-light-success' : 'label-light-danger' }}">
                                                                {{ $f->status_funcionario }}
                                                            </span>
                                                        </span>
                                                    </td>
                                                    <td class="datatable-cell"><span style="width: 250px;">{{$f->rua}}, {{$f->numero}} - {{$f->bairro}}</span></td>

                                                    <td class="datatable-cell">
                                                        <span style="width: 150px;">
                                                            <a class="btn btn-sm btn-warning" onclick='swal("Atenção!", "Deseja editar?", "warning").then((sim) => {if(sim){ location.href="/funcionarios/edit/{{ $f->id }}" } })' href="#!">
                                                                <i class="la la-edit"></i>    
                                                            </a>
                                                            <a class="btn btn-sm btn-danger" onclick='swal("Atenção!", "Deseja remover?", "warning").then((sim) => {if(sim){ location.href="/funcionarios/delete/{{ $f->id }}" } })' href="#!">
                                                                <i class="la la-trash"></i>    
                                                            </a>
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

                        {{-- VIEW GRADE (CARDS) --}}
                        <div class="pb-5" data-wizard-type="step-content">
                            <div class="row">
                                @foreach($funcionarios as $c)
                                @php 
                                    $dataNasc = $c->data_nascimento ? \Carbon\Carbon::parse($c->data_nascimento) : null;
                                    $aniversariante = $dataNasc && $dataNasc->month == \Carbon\Carbon::now()->month;
                                    
                                    // Cálculo do tempo de empresa em Português
                                    \Carbon\Carbon::setLocale('pt_BR');
                                    $tempoEmpresa = $c->data_admissao ? \Carbon\Carbon::parse($c->data_admissao)->diffForHumans(null, true) : '--';
                                @endphp
                                <div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
                                    <div class="card card-custom gutter-b {{ $aniversariante ? 'bg-light-warning border border-warning' : '' }}">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title">
                                                <div class="symbol symbol-50 symbol-light mr-4">
                                                    @if($c->foto_funcionario)
                                                        <img src="{{ asset('imgs_funcionarios/'.$c->foto_funcionario) }}" style="object-fit: cover;">
                                                    @else
                                                        <span class="symbol-label font-size-h3 font-weight-boldest text-primary">{{ substr($c->nome, 0, 1) }}</span>
                                                    @endif
                                                </div>
                                                <div class="d-flex flex-column">
                                                <a href="#" class="text-dark font-weight-bold text-hover-primary font-size-h6">
                                                    {{substr($c->nome, 0, 25)}}
                                                </a>
                                                <span class="text-muted font-weight-bold font-size-sm mb-1">
                                                    {{ optional($c->getRelation('funcao'))->nome ?? 'Função não definida' }}
                                                </span>

                                                {{-- Alerta de CNH nos Cards --}}
                                                @if($c->vencimento_cnh)
                                                    @php
                                                        $hoje = \Carbon\Carbon::now();
                                                        $vencimento = \Carbon\Carbon::parse($c->vencimento_cnh);
                                                        $diasParaVencer = $hoje->diffInDays($vencimento, false);
                                                    @endphp

                                                    @if($diasParaVencer <= 0)
                                                        <span class="label label-danger label-inline font-weight-bold" style="width: fit-content;" title="CNH Vencida!">CNH VENCIDA!</span>
                                                    @elseif($diasParaVencer <= 30)
                                                        <span class="label label-warning label-inline font-weight-bold" style="width: fit-content;" title="Vence em {{ $diasParaVencer }} dias">CNH VENCE EM {{ $diasParaVencer }}D</span>
                                                    @endif
                                                @endif
                                            </div>
                                            </div>
                                            <div class="card-toolbar">
                                                @if($aniversariante)
                                                    <span class="btn btn-icon btn-circle btn-sm btn-light-danger pulse pulse-danger" title="Aniversariante!">
                                                        <i class="fa fa-birthday-cake"></i>
                                                        <span class="pulse-ring"></span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="card-body pt-2">
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="font-weight-bold mr-2 text-dark-75">Status:</span>
                                                <span class="label {{ $c->status_funcionario == 'Ativo' ? 'label-light-success' : 'label-light-danger' }} label-inline">{{ $c->status_funcionario }}</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="font-weight-bold mr-2 text-dark-75">Celular:</span>
                                                <span class="text-primary">{{ $c->telefone ?? '--' }}</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="font-weight-bold mr-2 text-dark-75">Endereço:</span>
                                                <span class="text-muted">{{$c->rua}}, {{$c->numero}}</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="font-weight-bold mr-2 text-dark-75">Tempo de Casa:</span>
                                                <span class="text-success font-weight-bolder">{{ $tempoEmpresa }}</span>
                                            </div>
                                        </div>
                                        <div class="card-footer py-3 d-flex justify-content-between bg-light-secondary">
                                            <a href="/funcionarios/edit/{{$c->id}}" class="btn btn-sm btn-info font-weight-bold"><i class="la la-edit"></i> Editar</a>
                                            <a href="/funcionarios/contatos/{{$c->id}}" class="btn btn-sm btn-secondary font-weight-bold"><i class="la la-phone"></i> Contatos</a>
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
    </div>
</div>
<!-- Modal de Importação Excel -->
<div class="modal fade" id="modal_import_excel" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <form action="/funcionarios/importExcel" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Importar Funcionários (Excel)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i aria-hidden="true" class="ki ki-close"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                         <div class="alert alert-light-info mb-4">
                            <i class="fa fa-info-circle"></i> 
                            <strong>Dica:</strong> Para evitar erros, baixe nosso modelo padrão: 
                            <a href="/funcionarios/downloadLayout" class="font-weight-bold text-primary">Baixar Layout Exemplo</a>
                        </div>
                        <label>Unidade de Destino</label>
                        <select class="form-control custom-select" name="filial_id">
                            <option value="NULL">MATRIZ (Null)</option>
                            @foreach($filiais as $f)
                                <option value="{{$f->id}}">{{ $f->nome ?? 'Filial '.$f->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Selecione a Planilha (.xlsx)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx, .xls" required>
                        <small class="form-text text-muted">A importação será vinculada à Matriz.</small>
                    </div>

                    <div class="alert alert-custom alert-light-success mb-5" role="alert">
                        <div class="alert-icon"><i class="flaticon-info"></i></div>
                        <div class="alert-text">
                            Para evitar erros de leitura, utilize a planilha padrão <strong>funcionario.xlsx</strong>.<br><br>
                            A primeira linha da planilha deve conter os cabeçalhos em minúsculo (ex: <i>nome, cpf, rg, funcao, telefone, celular, rua, numero, bairro</i>).<br>
                            <em>* Cadastros com CPFs já existentes no sistema serão ignorados para evitar duplicidade.</em>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success font-weight-bold">Importar Planilha</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection