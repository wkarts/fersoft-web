@extends('default.layout')
@section('content')

    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .table { width: 100% !important; border-collapse: collapse !important; }
            .table td, .table th { border: 1px solid #ddd !important; padding: 6px !important; font-size: 11px; }
        }
    </style>

    <div class="card card-custom gutter-b">
        <div class="card-body">

            <!-- Cabeçalho e Ações (Oculto na Impressão) -->
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <h3 class="card-title font-weight-bolder">{{ $title }}</h3>
                <button onclick="window.print()" class="btn btn-info font-weight-bold">
                    <i class="la la-print"></i> Imprimir Relatório
                </button>
            </div>

            <!-- Filtros (Oculto na Impressão) -->
            <form method="get" action="/movimentacaoVeiculo/relatorioColetas" class="no-print mb-5">
                <div class="row align-items-center">
                    <div class="col-lg-3">
                        <label>Veículo</label>
                        <select name="veiculo_id" class="form-control">
                            <option value="">Todos os Veículos</option>
                            @foreach($veiculos as $v)
                                <option value="{{ $v->id }}" {{ $veiculoId == $v->id ? 'selected' : '' }}>{{ $v->placa }} - {{ $v->modelo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Todos</option>
                            <option value="agendado" {{ $status == 'agendado' ? 'selected' : '' }}>Agendado</option>
                            <option value="iniciado" {{ $status == 'iniciado' ? 'selected' : '' }}>Em Percurso</option>
                            <option value="finalizado" {{ $status == 'finalizado' ? 'selected' : '' }}>Finalizado</option>
                            <option value="cancelado" {{ $status == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label>Data Início</label>
                        <input type="date" name="data_inicio" class="form-control" value="{{ $dataInicio }}">
                    </div>
                    <div class="col-lg-2">
                        <label>Data Fim</label>
                        <input type="date" name="data_fim" class="form-control" value="{{ $dataFim }}">
                    </div>
                    <div class="col-lg-3 mt-4">
                        <button class="btn btn-primary font-weight-bold"><i class="la la-search"></i> Filtrar</button>
                    </div>
                </div>
            </form>

            <!-- KPIs Resumo -->
            <div class="row mb-4">
                <div class="col-3 text-center border-right">
                    <span class="text-muted font-weight-bold">Realizadas</span>
                    <h4 class="text-success font-weight-bolder">{{ $totalRealizadas }}</h4>
                </div>
                <div class="col-3 text-center border-right">
                    <span class="text-muted font-weight-bold">Em Percurso</span>
                    <h4 class="text-primary font-weight-bolder">{{ $totalEmCurso }}</h4>
                </div>
                <div class="col-3 text-center border-right">
                    <span class="text-muted font-weight-bold">A Realizar (Agendadas)</span>
                    <h4 class="text-warning font-weight-bolder">{{ $totalAgendadas }}</h4>
                </div>
                <div class="col-3 text-center">
                    <span class="text-muted font-weight-bold">Canceladas</span>
                    <h4 class="text-danger font-weight-bolder">{{ $totalCanceladas }}</h4>
                </div>
            </div>

            <hr>

            <!-- Tabela -->
            <div class="table-responsive mt-4">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr class="bg-light">
                        <th>Data/Hora</th>
                        <th>Status</th>
                        <th>Veículo</th>
                        <th>Motorista</th>
                        <th>Cliente / Destino</th>
                        <th>Chegada Cliente</th>
                        <th>Saída Cliente</th>
                        <th>Motivo Cancelamento</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($coletas as $c)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($c->data_hora_saida)->format('d/m/Y H:i') }}</td>
                            <td>{!! $c->status_formatado !!}</td>
                            <td>{{ $c->veiculo->placa ?? 'N/A' }}</td>
                            <td>{{ $c->motorista->nome ?? 'N/A' }}</td>
                            <td>{{ $c->cliente->razao_social ?? $c->destino }}</td>
                            <td>{{ $c->data_chegada_cliente_formatada }}</td>
                            <td>{{ $c->data_saida_cliente_formatada }}</td>
                            <td>{{ $c->motivo_cancelamento ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Nenhum registro encontrado no período.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection
