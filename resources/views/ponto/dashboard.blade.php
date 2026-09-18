@php
    $title = $title ?? 'Painel de Recursos Humanos e Ponto';
@endphp

@extends('default.layout')

@section('content')
    <div class="container-fluid p-0">

        <div class="d-flex justify-content-end mb-4">
            <button type="button" class="btn btn-warning font-weight-bold shadow-sm" data-toggle="modal" data-target="#modalLancarFerias">
                <i class="la la-calendar-plus mr-1"></i> Lançar Férias
            </button>
        </div>

        <!-- LINHA 1: CARDS INDICADORES -->
        <div class="row">
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card card-custom bg-light-primary border-0">
                    <div class="card-body my-1">
                        <span class="card-title font-weight-bolder text-primary font-size-h2 mb-0 d-block">{{ $totalFuncionarios }}</span>
                        <span class="font-weight-bold text-muted font-size-sm">👥 Colaboradores Ativos</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card card-custom bg-light-success border-0">
                    <div class="card-body my-1">
                        <span class="card-title font-weight-bolder text-success font-size-h2 mb-0 d-block">{{ $trabalhandoAgora->count() }}</span>
                        <span class="font-weight-bold text-muted font-size-sm">🟢 Trabalhando Agora</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card card-custom bg-light-danger border-0">
                    <div class="card-body my-1">
                        <span class="card-title font-weight-bolder text-danger font-size-h2 mb-0 d-block">{{ $naoBateramPonto->count() }}</span>
                        <span class="font-weight-bold text-muted font-size-sm">⚠️ Ausentes Hoje</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="card card-custom bg-light-warning border-0">
                    <div class="card-body my-1">
                        <span class="card-title font-weight-bolder text-warning font-size-h2 mb-0 d-block">{{ $emFerias->count() }}</span>
                        <span class="font-weight-bold text-muted font-size-sm">🏖️ Em Férias Hoje</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- LINHA 2: MONITORAMENTO DIÁRIO E ANIVERSARIANTES -->
        <div class="row">
            <!-- Ausentes / Não Bateram Ponto -->
            <div class="col-lg-6 mb-4">
                <div class="card card-custom card-stretch">
                    <div class="card-header border-0 pt-4 pb-2">
                        <h5 class="card-title font-weight-bolder text-danger mb-0">
                            <i class="la la-user-times text-danger mr-1"></i> Não Bateram o Ponto Hoje ({{ $naoBateramPonto->count() }})
                        </h5>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-borderless table-vertical-center mb-0">
                                <tbody>
                                @forelse($naoBateramPonto as $f)
                                    <tr class="border-bottom">
                                        <td class="pl-0 py-2">
                                            <span class="font-weight-bolder text-dark">{{ $f->nome }}</span>
                                            <br><small class="text-muted">{{ $f->funcao ?? 'Colaborador' }} | Tel: {{ $f->celular ?: $f->telefone }}</small>
                                        </td>
                                        <td class="text-right pr-0 py-2">
                                            <span class="badge badge-light-danger font-weight-bold">Sem Registro</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center text-success py-4">
                                            <i class="la la-check-circle text-success font-size-h3 d-block mb-1"></i>
                                            Todos os colaboradores ativos já registraram entrada!
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aniversariantes do Mês -->
            <div class="col-lg-6 mb-4">
                <div class="card card-custom card-stretch">
                    <div class="card-header border-0 pt-4 pb-2">
                        <h5 class="card-title font-weight-bolder text-primary mb-0">
                            <i class="la la-birthday-cake text-primary mr-1"></i> Aniversariantes deste Mês ({{ $aniversariantes->count() }})
                        </h5>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-borderless table-vertical-center mb-0">
                                <tbody>
                                @forelse($aniversariantes as $a)
                                    <tr class="border-bottom">
                                        <td class="pl-0 py-2">
                                            <span class="font-weight-bolder text-dark">{{ $a['nome'] }}</span>
                                            <br><small class="text-muted">{{ $a['cargo'] }}</small>
                                        </td>
                                        <td class="text-right pr-0 py-2">
                                        <span class="badge badge-light-primary font-weight-bolder font-size-sm">
                                            🎂 {{ $a['dia'] }}
                                        </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center text-muted py-4">Nenhum aniversário registrado para este mês.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LINHA 3: FÉRIAS E VENCIMENTO DE CNH -->
        <div class="row">
            <!-- Férias Vencidas -->
            <div class="col-lg-6 mb-4">
                <div class="card card-custom card-stretch">
                    <div class="card-header border-0 pt-4 pb-2">
                        <h5 class="card-title font-weight-bolder text-warning mb-0">
                            <i class="la la-exclamation-triangle text-warning mr-1"></i> Alerta de Férias Vencidas (> 1 ano e 11 meses)
                        </h5>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                            <table class="table table-borderless table-vertical-center mb-0">
                                <tbody>
                                @forelse($feriasVencidas as $fv)
                                    <tr class="border-bottom">
                                        <td class="pl-0 py-2">
                                            <span class="font-weight-bolder text-dark">{{ $fv['nome'] }}</span>
                                            <br><small class="text-muted">Admissão: {{ $fv['admissao'] }} ({{ $fv['tempo'] }})</small>
                                        </td>
                                        <td class="text-right pr-0 py-2">
                                            <span class="badge badge-light-danger font-weight-bold">Concessivo Vencido</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center text-success py-4">Nenhum colaborador com férias no limite da CLT.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerta de CNH (Motoristas) -->
            <div class="col-lg-6 mb-4">
                <div class="card card-custom card-stretch">
                    <div class="card-header border-0 pt-4 pb-2">
                        <h5 class="card-title font-weight-bolder text-dark mb-0">
                            <i class="la la-id-card text-info mr-1"></i> Alerta de CNH de Motoristas (Vencidas ou em 30 dias)
                        </h5>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                            <table class="table table-borderless table-vertical-center mb-0">
                                <tbody>
                                @forelse($cnhVencendo as $cnh)
                                    <tr class="border-bottom">
                                        <td class="pl-0 py-2">
                                            <span class="font-weight-bolder text-dark">{{ $cnh['nome'] }}</span>
                                            <br><small class="text-muted">Cat. {{ $cnh['categoria'] }} | Vencimento: {{ $cnh['vencimento'] }}</small>
                                        </td>
                                        <td class="text-right pr-0 py-2">
                                            @if($cnh['vencida'])
                                                <span class="badge badge-danger font-weight-bold">VENCIDA HÁ {{ $cnh['dias'] }} DIAS</span>
                                            @else
                                                <span class="badge badge-warning font-weight-bold">VENCE EM {{ $cnh['dias'] }} DIAS</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center text-success py-4">Todas as CNHs dos motoristas estão regulares.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL LANÇAR FÉRIAS -->
        <div class="modal fade" id="modalLancarFerias" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form method="POST" action="{{ url('/ponto/ferias/salvar') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title font-weight-bold">Agendar / Lançar Férias</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="font-weight-bold">Colaborador</label>
                                <select name="funcionario_id" class="form-control select2" required style="width: 100%;">
                                    <option value="">Selecione o colaborador...</option>
                                    @foreach($funcionarios as $func)
                                        <option value="{{ $func->id }}">{{ $func->nome }} ({{ $func->funcao ?? 'Colaborador' }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold">Data de Início</label>
                                    <input type="date" name="data_inicio" class="form-control" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold">Data de Término</label>
                                    <input type="date" name="data_fim" class="form-control" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Observações / Motivo (Opcional)</label>
                                <textarea name="observacoes" class="form-control" rows="2" placeholder="Ex: Período regular de 30 dias"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-warning font-weight-bold">Salvar Férias</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
