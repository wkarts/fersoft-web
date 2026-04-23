@extends('default.layout')

@section('content')
    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="container mt-5">

            <div class="card card-custom mb-5 shadow-sm" style="border-radius: 10px;">
                <div class="card-body py-4 d-flex justify-content-between align-items-center flex-wrap">
                    <h3 class="text-dark font-weight-bold m-0 mb-3 mb-md-0">
                        <i class="fas fa-tachometer-alt text-primary mr-2"></i> {{ $title }}
                    </h3>

                    <form action="" method="GET" class="d-flex align-items-center flex-wrap">
                        <label class="mr-2 font-weight-bold mb-0">Período:</label>
                        <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="form-control mr-2" style="width: 160px;">
                        <input type="date" name="data_fim" value="{{ $dataFim }}" class="form-control mr-2" style="width: 160px;">

                        <label class="mr-2 font-weight-bold mb-0">Veículo:</label>
                        <select name="veiculo_id" class="form-control mr-2" style="width: 200px;" onchange="this.form.submit()">
                            <option value="">TODOS OS VEÍCULOS</option>
                            @foreach($veiculos as $v)
                                <option value="{{ $v->id }}" {{ $veiculoSelecionado == $v->id ? 'selected' : '' }}>
                                    {{ $v->placa }} - {{ $v->modelo }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-primary btn-sm mr-1">Filtrar</button>

                        @if($veiculoSelecionado || request('data_inicio'))
                            <a href="/movimentacaoVeiculo/dashboard" class="btn btn-light-danger btn-sm">Limpar</a>
                        @endif
                    </form>
                </div>
            </div>

            <h5 class="text-muted mb-4 text-uppercase font-weight-bold">Eficiência Operacional</h5>
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card bg-primary text-white shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body text-center">
                            <div class="font-size-h1 font-weight-bolder">{{ number_format($totalKm, 0, ',', '.') }}</div>
                            <div class="font-weight-bold text-uppercase">KM Rodados</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card bg-success text-white shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body text-center">
                            <div class="font-size-h1 font-weight-bolder">{{ $mediaKmPorLitro }}</div>
                            <div class="font-weight-bold text-uppercase">Média KM/L (Diesel)</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card bg-info text-white shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body text-center">
                            <div class="font-size-h1 font-weight-bolder">{{ number_format($totalLitros, 0, ',', '.') }} L</div>
                            <div class="font-weight-bold text-uppercase">Litros Diesel</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card bg-dark text-white shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body text-center">
                            <div class="font-size-h1 font-weight-bolder">{{ number_format($totalLitrosArla, 0, ',', '.') }} L</div>
                            <div class="font-weight-bold text-uppercase">Litros Arla 32</div>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="text-muted mb-4 mt-2 text-uppercase font-weight-bold">Custos Financeiros</h5>
            <div class="row">
                <div class="col-md-2 col-sm-6 mb-4">
                    <div class="card bg-light-primary border-primary shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body text-center py-4">
                            <div class="text-primary font-weight-bolder font-size-h4">R$ {{ number_format($custoDiesel, 2, ',', '.') }}</div>
                            <small class="font-weight-bold text-muted text-uppercase">Diesel</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-4">
                    <div class="card bg-light shadow-sm h-100" style="border-radius: 10px; border: 1px solid #E4E6EF;">
                        <div class="card-body text-center py-4">
                            <div class="text-dark font-weight-bolder font-size-h4">R$ {{ number_format($totalCustoArla, 2, ',', '.') }}</div>
                            <small class="font-weight-bold text-muted text-uppercase">Arla 32</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card bg-light-warning border-warning shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body text-center py-4">
                            <div class="text-warning font-weight-bolder font-size-h4">R$ {{ number_format($custoManutencao, 2, ',', '.') }}</div>
                            <small class="font-weight-bold text-muted text-uppercase">Manutenção/Contas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6 mb-4">
                    <div class="card bg-light-info border-info shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body text-center py-4">
                            <div class="text-info font-weight-bolder font-size-h4">R$ {{ number_format($custoViagem, 2, ',', '.') }}</div>
                            <small class="font-weight-bold text-muted text-uppercase">Desp. Viagem</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-12 mb-4">
                    <div class="card bg-danger text-white shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-body text-center py-4">
                            <div class="font-weight-bolder font-size-h3">R$ {{ number_format($totalDespesas, 2, ',', '.') }}</div>
                            <small class="font-weight-bold text-uppercase">Total Geral</small>
                        </div>
                    </div>
                </div>
            </div>

            @if(count($veiculosManutencao) > 0 || count($motoristasAlerta) > 0)
                <h5 class="text-muted mb-4 mt-2 text-uppercase font-weight-bold">Avisos e Pendências</h5>
                <div class="row">
                    @if(count($veiculosManutencao) > 0)
                        <div class="col-lg-6 mb-4">
                            <div class="card border-danger shadow-sm h-100" style="border-radius: 10px;">
                                <div class="card-header border-0 pt-5">
                                    <h3 class="card-title font-weight-bolder text-danger"><i class="fa fa-wrench mr-2"></i> Veículos em Manutenção</h3>
                                </div>
                                <div class="card-body">
                                    @foreach($veiculosManutencao as $vm)
                                        <div class="d-flex align-items-center mb-3 p-3 bg-light-danger rounded">
                                            <span class="font-weight-bold text-dark">{{ $vm->placa }}</span> - {{ $vm->modelo }}
                                            <span class="ml-auto badge badge-danger">{{ strtoupper($vm->manutencao_status) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(count($motoristasAlerta) > 0)
                        <div class="col-lg-6 mb-4">
                            <div class="card border-warning shadow-sm h-100" style="border-radius: 10px;">
                                <div class="card-header border-0 pt-5">
                                    <h3 class="card-title font-weight-bolder text-warning"><i class="fa fa-id-card mr-2"></i> Alertas de CNH</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-borderless table-vertical-center">
                                            <tbody>
                                            @foreach($motoristasAlerta as $mot)
                                                @php
                                                    $hoje = \Carbon\Carbon::now();
                                                    $venc = \Carbon\Carbon::parse($mot->vencimento_cnh);
                                                    $dias = $hoje->diffInDays($venc, false);
                                                @endphp
                                                <tr>
                                                    <td class="pl-0"><span class="font-weight-bolder text-dark">{{ $mot->nome }}</span></td>
                                                    <td>Vence: {{ $venc->format('d/m/Y') }}</td>
                                                    <td class="text-right">
                                                        @if($dias <= 0) <span class="badge badge-danger">VENCIDA</span>
                                                        @else <span class="badge badge-warning">VENCE EM {{ $dias }} DIAS</span> @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="row mt-4">
                <div class="col-lg-8 mb-4">
                    <div class="card card-custom shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-header border-0 pt-5">
                            <h3 class="card-title font-weight-bolder">Histórico: KM vs Custos</h3>
                        </div>
                        <div class="card-body" style="height: 350px;">
                            <canvas id="graficoHistorico"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="card card-custom shadow-sm h-100" style="border-radius: 10px;">
                        <div class="card-header border-0 pt-5">
                            <h3 class="card-title font-weight-bolder">Composição do Custo</h3>
                        </div>
                        <div class="card-body d-flex align-items-center" style="height: 350px;">
                            <canvas id="graficoCategorias"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            const ctxHist = document.getElementById('graficoHistorico').getContext('2d');
            new Chart(ctxHist, {
                data: {
                    labels: {!! $graficoMeses !!},
                    datasets: [
                        {
                            type: 'line',
                            label: 'Custo Total (R$)',
                            data: {!! $graficoContas !!},
                            borderColor: '#f64e60',
                            backgroundColor: '#f64e60',
                            fill: false,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            type: 'line',
                            label: 'Desp. Viagem (R$)',
                            data: {!! $graficoDespesas !!},
                            borderColor: '#ffa800',
                            backgroundColor: '#ffa800',
                            fill: false,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            type: 'bar',
                            label: 'KM Rodado',
                            data: {!! $graficoKm !!},
                            backgroundColor: '#3699ff',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { position: 'left', title: { display: true, text: 'Financeiro (R$)' } },
                        y1: { position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'KM' } }
                    }
                }
            });

            const ctxCat = document.getElementById('graficoCategorias').getContext('2d');
            new Chart(ctxCat, {
                type: 'doughnut',
                data: {
                    labels: {!! $pizzaLabels !!},
                    datasets: [{
                        data: {!! $pizzaValores !!},
                        backgroundColor: ['#3699ff', '#181c32', '#ffa800', '#f64e60']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        });
    </script>
@endsection
