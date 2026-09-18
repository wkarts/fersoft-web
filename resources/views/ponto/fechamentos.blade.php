@php
    $title = $title ?? 'Fechamento Mensal e Banco de Horas';
@endphp

@extends('default.layout')

@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-5">
            <h3 class="card-title font-weight-bolder text-dark">
                <i class="la la-calendar-check text-primary mr-2"></i> {{ $title }}
            </h3>
            <div class="card-toolbar">
                @if($fechamento && $fechamento->status === 'fechado')
                    <span class="badge badge-success px-4 py-2 font-weight-bold font-size-sm mr-2">
                    🔒 COMPETÊNCIA FECHADA ({{ \Carbon\Carbon::parse($fechamento->fechado_em)->format('d/m/Y H:i') }})
                </span>
                    <form method="POST" action="{{ url('/ponto/fechamento/reabrir') }}" onsubmit="return confirm('Tem certeza que deseja reabrir este mês? Os saldos congelados serão removidos.');">
                        @csrf
                        <input type="hidden" name="competencia" value="{{ $competencia }}">
                        <button type="submit" class="btn btn-sm btn-outline-danger font-weight-bold">
                            <i class="la la-unlock"></i> Reabrir Mês
                        </button>
                    </form>
                @else
                    <span class="badge badge-warning px-4 py-2 font-weight-bold font-size-sm">
                    🔓 COMPETÊNCIA EM ABERTO
                </span>
                @endif
            </div>
        </div>

        <div class="card-body">
            <!-- BARRA DE SELEÇÃO E AÇÃO -->
            <div class="row align-items-end mb-5">
                <div class="col-md-4">
                    <form method="GET" action="{{ url('/ponto/fechamento') }}" id="formCompetencia">
                        <label class="font-weight-bold text-dark font-size-sm">Mês de Competência</label>
                        <div class="input-group">
                            <input type="month" name="competencia" class="form-control font-weight-bold"
                                   value="{{ $competencia }}" onchange="document.getElementById('formCompetencia').submit()">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary font-weight-bold">Carregar</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-8 text-right mt-3 mt-md-0">
                    @if(!$fechamento)
                        <form method="POST" action="{{ url('/ponto/fechamento/processar') }}" class="d-inline" onsubmit="return confirm('Deseja processar e fechar o banco de horas de todos os colaboradores para {{ $competencia }}?');">
                            @csrf
                            <input type="hidden" name="competencia" value="{{ $competencia }}">
                            <button type="submit" class="btn btn-success font-weight-bold px-4" style="height: 38px;">
                                <i class="la la-lock mr-1"></i> Fechar Competência e Congelar Saldos
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="separator separator-dashed my-5"></div>

            <!-- TABELA CONSOLIDADA DA FOLHA -->
            <div class="table-responsive">
                <table class="table table-head-custom table-vertical-center table-hover">
                    <thead>
                    <tr class="text-uppercase text-secondary">
                        <th>Colaborador</th>
                        <th>Trabalhadas</th>
                        <th>Extras</th>
                        <th>Atrasos</th>
                        <th>Saldo Mês</th>
                        <th>Saldo Anterior</th>
                        <th>Compensado/Pago</th>
                        <th>Saldo Acumulado</th>
                        <th class="text-right">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($relatorioColaboradores as $c)
                        <tr>
                            <td>
                                <span class="font-weight-bolder text-dark">{{ $c['nome'] }}</span>
                                <br><small class="text-muted">{{ $c['cargo'] }} | CPF: {{ $c['cpf'] ?? '---' }}</small>
                            </td>
                            <td class="font-weight-bold">{{ $c['trabalhadas'] }}</td>
                            <td class="text-success font-weight-bold">+{{ $c['extras'] }}</td>
                            <td class="text-danger font-weight-bold">-{{ $c['atrasos'] }}</td>
                            <td class="font-weight-bold {{ $c['saldo_mes_min'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $c['saldo_mes'] }}
                            </td>
                            <td class="text-muted font-weight-bold">{{ $c['saldo_anterior'] }}</td>
                            <td>
                                @if($c['compensado_min'] > 0)
                                    <span class="badge badge-light-warning font-weight-bold">{{ $c['compensado_fmt'] }}</span>
                                @else
                                    <span class="text-muted">---</span>
                                @endif
                            </td>
                            <td>
                            <span class="label label-inline font-weight-bolder {{ $c['saldo_positivo'] ? 'label-light-success text-success' : 'label-light-danger text-danger' }}">
                                {{ $c['saldo_acumulado'] }}
                            </span>
                            </td>
                            <td class="text-right">
                                <button type="button" class="btn btn-icon btn-light btn-hover-primary btn-sm mr-1"
                                        title="Ajustar / Compensar Horas"
                                        onclick="abrirModalCompensacao({{ $c['funcionario_id'] }}, '{{ $c['nome'] }}', {{ $c['compensado_min'] }})">
                                    <i class="la la-balance-scale"></i>
                                </button>
                                <a href="{{ url('/ponto/espelho/pdf/' . $c['funcionario_id'] . '?data_inicio=' . $dataInicio . '&data_fim=' . $dataFim) }}"
                                   target="_blank" class="btn btn-icon btn-light-info btn-sm" title="Imprimir Espelho Mensal">
                                    <i class="la la-print"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                Nenhum colaborador encontrado para apuração da folha.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL COMPENSAR / ABATER SALDO -->
    <div class="modal fade" id="modalCompensacao" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ url('/ponto/fechamento/ajustar-saldo') }}">
                    @csrf
                    <input type="hidden" name="competencia" value="{{ $competencia }}">
                    <input type="hidden" name="funcionario_id" id="modal_func_id">

                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold">Compensação / Pagamento de Horas</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-dark font-weight-bold" id="modal_func_nome"></p>

                        <div class="form-group">
                            <label class="font-weight-bold">Minutos a Compensar / Pagar em Folha</label>
                            <input type="number" name="minutos_compensados" id="modal_minutos" class="form-control" placeholder="Ex: 120 (para 2 horas pagas)" required>
                            <small class="text-muted">Esses minutos serão subtraídos do saldo do banco de horas deste mês.</small>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Motivo / Justificativa</label>
                            <input type="text" name="motivo" class="form-control" placeholder="Ex: Horas extras pagas no holerite ou folga compensatória concedida" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary font-weight-bold">Salvar Ajuste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModalCompensacao(funcId, funcNome, minutos) {
            document.getElementById('modal_func_id').value = funcId;
            document.getElementById('modal_func_nome').innerText = 'Colaborador: ' + funcNome;
            document.getElementById('modal_minutos').value = minutos;
            $('#modalCompensacao').modal('show');
        }
    </script>
@endsection
