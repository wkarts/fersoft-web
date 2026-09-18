@extends('default.layout')

@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-5">
            <h3 class="card-title font-weight-bolder text-dark">
                <i class="la la-list-alt text-primary mr-2"></i> {{ $title ?? 'Registro Geral de Marcações de Ponto' }}
            </h3>
            <div class="card-toolbar">
                <span class="text-muted font-weight-bold font-size-sm">Auditoria em tempo real</span>
            </div>
        </div>

        <div class="card-body">
            <!-- FORMULÁRIO DE FILTROS -->
            <form method="GET" action="{{ url()->current() }}" class="mb-5">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="font-weight-bold">Data Início</label>
                        <input type="date" name="data_inicio" class="form-control"
                               value="{{ request('data_inicio', date('Y-m-d')) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="font-weight-bold">Data Fim</label>
                        <input type="date" name="data_fim" class="form-control"
                               value="{{ request('data_fim', date('Y-m-d')) }}">
                    </div>

                    <div class="col-md-3">
                        <label class="font-weight-bold">Origem</label>
                        <select name="origem" class="form-control">
                            <option value="">-- Todas as Origens --</option>
                            <option value="whatsapp" {{ request('origem') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                            <option value="whatsapp_pin" {{ request('origem') == 'whatsapp_pin' ? 'selected' : '' }}>WhatsApp (PIN Ajudante)</option>
                            <option value="whatsapp_traccar" {{ request('origem') == 'whatsapp_traccar' ? 'selected' : '' }}>WhatsApp + GPS Traccar</option>
                            <option value="relogio" {{ request('origem') == 'relogio' ? 'selected' : '' }}>Relógio Físico (AFD)</option>
                            <option value="sistema" {{ request('origem') == 'sistema' ? 'selected' : '' }}>Manual / Sistema</option>
                        </select>
                    </div>

                    <div class="col-md-3 mt-3 mt-md-0 d-flex">
                        <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                            <i class="la la-filter"></i> Filtrar Marcações
                        </button>
                    </div>
                </div>
            </form>

            <div class="separator separator-dashed my-5"></div>

            <!-- LISTAGEM DAS MARCAÇÕES BRUTAS -->
            <div class="table-responsive">
                <table class="table table-head-custom table-vertical-center table-hover">
                    <thead>
                    <tr class="text-uppercase text-secondary">
                        <th>ID</th>
                        <th>Data/Hora</th>
                        <th>Colaborador</th>
                        <th>Tipo da Batida</th>
                        <th>Origem</th>
                        <th>Localização (GPS)</th>
                        <th>Status</th>
                        <th>Viagem Vinc.</th>
                        <th>Observações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($marcacoes as $m)
                        <tr>
                            <td class="text-muted font-weight-bold">#{{ $m->id }}</td>
                            <td class="font-weight-bold text-dark">
                                {{ \Carbon\Carbon::parse($m->data_hora_marcacao)->format('d/m/Y H:i:s') }}
                            </td>
                            <td>
                                <span class="font-weight-bolder text-dark">{{ $m->funcionario->nome ?? 'N/I' }}</span>
                                @if(!empty($m->funcionario->cpf))
                                    <br><small class="text-muted">CPF: {{ $m->funcionario->cpf }}</small>
                                @endif
                            </td>
                            <td>
                                @if($m->tipo_marcacao == 'entrada')
                                    <span class="badge badge-success font-weight-bold">🟢 Entrada</span>
                                @elseif($m->tipo_marcacao == 'inicio_almoco')
                                    <span class="badge badge-warning font-weight-bold">🟡 Saída Almoço</span>
                                @elseif($m->tipo_marcacao == 'fim_almoco')
                                    <span class="badge badge-info font-weight-bold">🟢 Retorno Almoço</span>
                                @elseif($m->tipo_marcacao == 'saida')
                                    <span class="badge badge-danger font-weight-bold">🔴 Saída</span>
                                @elseif($m->tipo_marcacao == 'inicio_hora_extra')
                                    <span class="badge badge-dark font-weight-bold">⚡ Início Extra</span>
                                @elseif($m->tipo_marcacao == 'fim_hora_extra')
                                    <span class="badge badge-secondary font-weight-bold">🛑 Fim Extra</span>
                                @else
                                    <span class="badge badge-light font-weight-bold">{{ strtoupper($m->tipo_marcacao) }}</span>
                                @endif
                            </td>
                            <td>
                            <span class="label label-inline label-light-primary font-weight-bold">
                                {{ strtoupper($m->origem) }}
                            </span>
                            </td>
                            <td>
                                @if($m->latitude && $m->longitude)
                                    <a href="https://maps.google.com/?q={{ $m->latitude }},{{ $m->longitude }}"
                                       target="_blank" class="btn btn-sm btn-light-success font-weight-bold">
                                        <i class="la la-map-marker"></i> Ver no Mapa
                                    </a>
                                @else
                                    <span class="text-muted"><i class="la la-map-marker-slash"></i> Sem GPS</span>
                                @endif
                            </td>
                            <td>
                                @if($m->status == 'processada')
                                    <span class="label label-dot label-success mr-1"></span>
                                    <span class="font-weight-bold text-success">Processada</span>
                                @elseif($m->status == 'pendente_localizacao')
                                    <span class="label label-dot label-warning mr-1"></span>
                                    <span class="font-weight-bold text-warning">Aguard. GPS</span>
                                @elseif($m->status == 'desconsiderada')
                                    <span class="label label-dot label-danger mr-1"></span>
                                    <span class="font-weight-bold text-danger">Desconsiderada</span>
                                @else
                                    <span class="label label-dot label-primary mr-1"></span>
                                    <span class="font-weight-bold text-dark">{{ ucfirst($m->status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($m->movimentacao_veiculo_id)
                                    <span class="badge badge-light-info font-weight-bold">#{{ $m->movimentacao_veiculo_id }}</span>
                                @else
                                    <span class="text-muted">---</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $m->observacoes ?? '---' }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="la la-calendar-times mb-2 font-size-h2 d-block"></i>
                                Nenhuma marcação de ponto encontrada no período selecionado.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(is_object($marcacoes) && method_exists($marcacoes, 'links'))
                <div class="mt-4">
                    {{ $marcacoes->appends(request()->all())->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
