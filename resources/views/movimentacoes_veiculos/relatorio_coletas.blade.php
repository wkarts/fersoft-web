@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-modules.css') }}?v={{ filemtime(public_path('css/fer-modules.css')) }}">
@endpush
@section('content')

    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            .table { width: 100% !important; border-collapse: collapse !important; }
            .table td, .table th { border: 1px solid #ddd !important; padding: 6px !important; font-size: 11px; }
        }

        /* Para a view multi-stop não ficar colada */
        .multi-stop-row {
            padding: 4px 0;
            min-height: 28px;
        }
        .multi-stop-row:not(:last-child) {
            border-bottom: 1px solid #ebedf3;
            margin-bottom: 4px;
        }
    </style>

    <div class="fer-report-screen card card-custom gutter-b">
        <div class="card-body">

            <!-- Cabeçalho e Ações -->
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <h3 class="card-title font-weight-bolder">{{ $title }}</h3>
                <button onclick="window.print()" class="btn btn-info font-weight-bold">
                    <i class="la la-print"></i> Imprimir Relatório
                </button>
            </div>

            <!-- Filtros (Oculto na Impressão) -->
            <form method="get" action="/movimentacaoVeiculo/relatorioColetas" class="fer-report-filter no-print mb-5">
                <div class="row align-items-end">
                    <div class="col-lg-3 mb-3">
                        <label class="font-weight-bold">Veículo</label>
                        <select name="veiculo_id" class="form-control select2">
                            <option value="">Todos os Veículos</option>
                            @foreach($veiculos as $v)
                                <option value="{{ $v->id }}" {{ $veiculoId == $v->id ? 'selected' : '' }}>{{ $v->placa }} - {{ $v->modelo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- FILTRO DE CLIENTE/FORNECEDOR -->
                    <div class="col-lg-3 mb-3">
                        <label class="font-weight-bold">Cliente / Fornecedor</label>
                        <select name="entidade_id" class="form-control select2">
                            <option value="">Todos os Destinos</option>
                            <optgroup label="Clientes">
                                @foreach($clientes as $c)
                                    <option value="cliente_{{ $c->id }}" {{ (isset($entidadeId) && $entidadeId == 'cliente_'.$c->id) ? 'selected' : '' }}>{{ $c->razao_social ?? $c->nome }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Fornecedores">
                                @foreach($fornecedores as $f)
                                    <option value="fornecedor_{{ $f->id }}" {{ (isset($entidadeId) && $entidadeId == 'fornecedor_'.$f->id) ? 'selected' : '' }}>[FOR] {{ $f->razao_social ?? $f->nome }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <div class="col-lg-2 mb-3">
                        <label class="font-weight-bold">Status</label>
                        <select name="status" class="form-control">
                            <option value="">Todos</option>
                            <option value="agendado" {{ $status == 'agendado' ? 'selected' : '' }}>Agendado</option>
                            <option value="iniciado" {{ $status == 'iniciado' ? 'selected' : '' }}>Em Percurso</option>
                            <option value="finalizado" {{ $status == 'finalizado' ? 'selected' : '' }}>Finalizado</option>
                            <option value="cancelado" {{ $status == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                        </select>
                    </div>

                    <div class="col-lg-2 mb-3">
                        <label class="font-weight-bold">Período</label>
                        <div class="d-flex align-items-center">
                            <input type="date" name="data_inicio" class="form-control" value="{{ $dataInicio }}" style="padding: 0.3rem;">
                            <span class="mx-2">a</span>
                            <input type="date" name="data_fim" class="form-control" value="{{ $dataFim }}" style="padding: 0.3rem;">
                        </div>
                    </div>

                    <div class="col-lg-2 mb-3 text-right">
                        <button class="btn btn-primary font-weight-bold w-100"><i class="la la-search"></i> Filtrar</button>
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
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                    <tr class="bg-light text-dark font-weight-bold">
                        <th width="10%">Saída Base</th>
                        <th width="8%">Status</th>
                        <th width="8%">Veículo</th>
                        <th width="12%">Motorista</th>
                        <th width="28%">Destinos / Rota</th>
                        <th width="12%">Chegada no Local</th>
                        <th width="12%">Saída do Local</th>
                        <th width="10%">Retorno Base</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($coletas as $c)
                        <tr>
                            <td class="align-middle">{{ \Carbon\Carbon::parse($c->data_hora_saida)->format('d/m/y H:i') }}</td>
                            <td class="align-middle">{!! $c->status_formatado !!}</td>
                            <td class="align-middle font-weight-bold">{{ $c->veiculo->placa ?? 'N/A' }}</td>
                            <td class="align-middle">{{ $c->motorista->nome ?? 'N/A' }}</td>

                            @if($c->status == 'cancelado')
                                <!-- CÉLULA MESCLADA PARA CANCELAMENTOS -->
                                <td colspan="4" class="align-middle text-center bg-light-danger" style="background-color: #ffe2e5;">
                                    <span class="text-danger font-weight-bolder"><i class="la la-info-circle text-danger mr-1"></i> Motivo do Cancelamento:</span>
                                    <span class="text-dark font-weight-bold ml-2">{{ $c->motivo_cancelamento ?? 'Motivo não informado' }}</span>
                                </td>
                            @else
                                <!-- COLUNA DE DESTINOS (MULTI-STOP) -->
                                <td class="p-2">
                                    @if(isset($c->destinos) && $c->destinos->count() > 0)
                                        @foreach($c->destinos as $dest)
                                            <div class="multi-stop-row">
                                                <span class="badge badge-secondary mr-1">{{ $dest->ordem }}º</span>
                                                @if($dest->cliente) {{ $dest->cliente->razao_social ?? $dest->cliente->nome }}
                                                @elseif($dest->fornecedor) [FOR] {{ $dest->fornecedor->razao_social ?? $dest->fornecedor->nome }}
                                                @else Endereço Avulso @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="multi-stop-row">{{ $c->cliente->razao_social ?? $c->destino ?? 'N/A' }}</div>
                                    @endif
                                </td>

                                <!-- COLUNA DE CHEGADA -->
                                <td class="p-2 text-center">
                                    @if(isset($c->destinos) && $c->destinos->count() > 0)
                                        @foreach($c->destinos as $dest)
                                            <div class="multi-stop-row text-primary font-weight-bold">
                                                {{ $dest->data_hora_chegada ? \Carbon\Carbon::parse($dest->data_hora_chegada)->format('d/m/y H:i') : '---' }}
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="multi-stop-row text-primary font-weight-bold">
                                            {{ $c->data_hora_chegada_cliente ? \Carbon\Carbon::parse($c->data_hora_chegada_cliente)->format('d/m/y H:i') : '---' }}
                                        </div>
                                    @endif
                                </td>

                                <!-- COLUNA DE SAÍDA -->
                                <td class="p-2 text-center">
                                    @if(isset($c->destinos) && $c->destinos->count() > 0)
                                        @foreach($c->destinos as $dest)
                                            <div class="multi-stop-row text-danger font-weight-bold">
                                                {{ $dest->data_hora_saida ? \Carbon\Carbon::parse($dest->data_hora_saida)->format('d/m/y H:i') : '---' }}
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="multi-stop-row text-danger font-weight-bold">
                                            {{ $c->data_hora_saida_cliente ? \Carbon\Carbon::parse($c->data_hora_saida_cliente)->format('d/m/y H:i') : '---' }}
                                        </div>
                                    @endif
                                </td>

                                <!-- COLUNA DE RETORNO A BASE -->
                                <td class="align-middle text-center font-weight-bold text-success">
                                    {{ $c->data_hora_chegada ? \Carbon\Carbon::parse($c->data_hora_chegada)->format('d/m/y H:i') : '---' }}
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 font-weight-bold text-muted">Nenhum registro encontrado no período.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    @section('javascript')
        <script>
            $(document).ready(function() {
                $('.select2').select2({ width: '100%' });
            });
        </script>
    @endsection
@endsection
