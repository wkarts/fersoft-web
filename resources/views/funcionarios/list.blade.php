@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-people.css') }}?v={{ filemtime(public_path('css/ui-people.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-people.css') }}?v={{ filemtime(public_path('css/ui-people.css')) }}">
@endpush
@section('content')
<div class="ui-people ui-people-list">
    <div class="ui-people-heading">
        <div class="ui-people-heading-copy"><h1>Funcionários</h1><p>Lista de Funcionários · Total de registros: {{ count($funcionarios) }}</p></div>
        <div class="ui-people-actions">
                <a href="/funcionarios/new" class="btn btn-lg btn-success ui-people-primary">
                    <i class="fa fa-plus"></i>Novo Funcionario
                </a>
                <a href="/funcionarios/comissao" class="btn btn-lg btn-info ui-people-secondary">
                    <i class="fa fa-list"></i>Comissão
                </a>
                <!-- Botão de Importação Excel -->
                <a href="#modal_import_excel" data-toggle="modal" data-target="#modal_import_excel" class="btn btn-lg btn-success ui-people-secondary">
                    <i class="fa fa-file-excel"></i>Importar Excel
                </a>
        </div>
    </div>

        <div class="ui-people-panel" id="kt_user_profile_aside">
            <div class="row">

                <div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
                    <div class="ui-people-view-switch" id="kt_wizard_v3" data-wizard-clickable="true">
                        <div class="ui-people-tabs" role="tablist" aria-label="Visualização de funcionários">
                            <button type="button" class="ui-people-tab is-active" id="ui-people-table-tab" role="tab" aria-controls="ui-people-table" aria-selected="true" data-people-tab="table" data-wizard-type="step"><i class="la la-table" aria-hidden="true"></i> Tabela</button>
                            <button type="button" class="ui-people-tab" id="grade" role="tab" aria-controls="ui-people-grid" aria-selected="false" data-people-tab="grid" data-wizard-type="step"><i class="la la-th-large" aria-hidden="true"></i> Grade</button>
                        </div>

                        {{-- VIEW TABELA --}}
                        <div id="ui-people-table" class="ui-people-table-pane" role="tabpanel" aria-labelledby="ui-people-table-tab" data-people-panel="table" data-wizard-type="step-content">
                            <div class="row">
                                <div class="col-xl-12">
                                    <div id="kt_datatable" class="ui-people-table-scroll">
                                        <table class="ui-people-table">
                                            <thead>
                                                <tr>
                                                    <th><span>FOTO</span></th>
                                                    <th><span>NOME</span></th>
                                                    <th><span>FUNÇÃO</span></th>
                                                    <th><span>CELULAR</span></th>
                                                    <th><span>STATUS</span></th>
                                                    <th><span>ENDEREÇO</span></th>
                                                    <th><span>AÇÕES</span></th>
                                                </tr>
                                            </thead>
                                            <tbody id="body">
                                                @foreach($funcionarios as $f)
                                                @php 
                                                    $aniversariante = $f->data_nascimento && \Carbon\Carbon::parse($f->data_nascimento)->month == \Carbon\Carbon::now()->month;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <span>
                                                            @if($f->foto_funcionario)
                                                                <img src="{{ asset('imgs_funcionarios/'.$f->foto_funcionario) }}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #EEE">
                                                            @else
                                                                <div class="symbol symbol-40 symbol-light-primary"><span class="symbol-label font-size-h4">{{ substr($f->nome, 0, 1) }}</span></div>
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span>
                                                            <strong>{{$f->nome}}</strong>
                                                            @if($aniversariante)
                                                                <span class="label label-warning label-inline ml-2 animate__animated animate__infinite animate__pulse">🎂 Niver!</span>
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td>
                                                      <span>
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
                                                    
                                                    <td><span>{{ $f->telefone ?? '--' }}</span></td>
                                                    <td>
                                                        <span>
                                                            <span class="label label-inline {{ $f->status_funcionario == 'Ativo' ? 'label-light-success' : 'label-light-danger' }}">
                                                                {{ $f->status_funcionario }}
                                                            </span>
                                                        </span>
                                                    </td>
                                                    <td><span>{{$f->rua}}, {{$f->numero}} - {{$f->bairro}}</span></td>

                                                    <td>
                                                        <span>
                                                            <a class="btn btn-sm ui-people-secondary" aria-label="Editar {{ $f->nome }}" onclick='swal("Atenção!", "Deseja editar?", "warning").then((sim) => {if(sim){ location.href="/funcionarios/edit/{{ $f->id }}" } })' href="#!">
                                                                <i class="la la-edit"></i>    
                                                            </a>
                                                            <a class="btn btn-sm ui-people-danger" aria-label="Remover {{ $f->nome }}" onclick='swal("Atenção!", "Deseja remover?", "warning").then((sim) => {if(sim){ location.href="/funcionarios/delete/{{ $f->id }}" } })' href="#!">
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
                        <div id="ui-people-grid" class="ui-people-grid-pane" role="tabpanel" aria-labelledby="grade" data-people-panel="grid" data-wizard-type="step-content" hidden>
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
                                    <div class="ui-people-person {{ $aniversariante ? 'ui-people-birthday-card' : '' }}">
                                        <div class="ui-people-person-head">
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

                                        <div class="ui-people-person-body">
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
                                        <div class="ui-people-person-actions">
                                            <a href="/funcionarios/edit/{{$c->id}}" class="btn btn-sm ui-people-secondary"><i class="la la-edit"></i> Editar</a>
                                            <a href="/funcionarios/contatos/{{$c->id}}" class="btn btn-sm ui-people-secondary"><i class="la la-phone"></i> Contatos</a>
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
<!-- Modal de Importação Excel -->
<div class="modal fade ui-people-modal" id="modal_import_excel" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <form action="/funcionarios/importExcel" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Importar Funcionários (Excel)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        &times;
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                         <div class="alert alert-light-info mb-4">
                            <i class="fa fa-info-circle"></i> 
                            <strong>Dica:</strong> Para evitar erros, baixe nosso modelo padrão: 
                            <a href="/funcionarios/downloadLayout" class="font-weight-bold text-primary">Baixar Layout Exemplo</a>
                        </div>
                        <label for="ui-people-import-filial">Unidade de Destino</label>
                        <select id="ui-people-import-filial" class="form-control custom-select" name="filial_id">
                            <option value="NULL">MATRIZ (Null)</option>
                            @foreach($filiais as $f)
                                <option value="{{$f->id}}">{{ $f->nome ?? 'Filial '.$f->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ui-people-import-file">Selecione a Planilha (.xlsx)</label>
                        <input id="ui-people-import-file" type="file" name="file" class="form-control" accept=".xlsx, .xls" required>
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
@section('javascript')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('kt_wizard_v3');
    if (!root) return;
    const tabs = Array.from(root.querySelectorAll('[data-people-tab]'));
    const panels = Array.from(root.querySelectorAll('[data-people-panel]'));
    function activate(tab) {
        tabs.forEach(item => { const active = item === tab; item.classList.toggle('is-active', active); item.setAttribute('aria-selected', String(active)); item.tabIndex = active ? 0 : -1; });
        panels.forEach(panel => { panel.hidden = panel.dataset.peoplePanel !== tab.dataset.peopleTab; });
    }
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activate(tab));
        tab.addEventListener('keydown', event => {
            if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
            event.preventDefault();
            const next = tabs[(index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
            activate(next); next.focus();
        });
    });
    activate(tabs[0]);
});
</script>
@endsection
