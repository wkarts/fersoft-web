@extends('default.layout')

@section('content')
<div class="d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container mt-5">
        
        <div class="card card-custom mb-5" style="border-radius: 10px;">
            <div class="card-body py-4 d-flex justify-content-between align-items-center">
                <h3 class="text-dark font-weight-bold m-0">{{ $title }}</h3>
                
                <form action="" method="GET" class="d-flex align-items-center">
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

        <div class="row">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card card-custom bg-primary text-white" style="border-radius: 10px;">
                    <div class="card-body">
                        <div class="font-weight-bolder font-size-h2 mt-3">{{ number_format($totalKm, 0, ',', '.') }} <span class="font-size-sm">KM</span></div>
                        <div class="font-weight-bold font-size-lg mt-1">Total Rodado (Mês)</div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card card-custom bg-danger text-white" style="border-radius: 10px;">
                    <div class="card-body">
                        <div class="font-weight-bolder font-size-h2 mt-3">{{ number_format($totalLitros, 0, ',', '.') }} <span class="font-size-sm">L</span></div>
                        <div class="font-weight-bold font-size-lg mt-1">Litros Consumidos</div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card card-custom bg-success text-white" style="border-radius: 10px;">
                    <div class="card-body">
                        <div class="font-weight-bolder font-size-h2 mt-3">{{ $mediaKmPorLitro }} <span class="font-size-sm">KM/L</span></div>
                        <div class="font-weight-bold font-size-lg mt-1">Média de Consumo</div>
                    </div>
                </div>
            </div> 

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card card-custom bg-dark text-white" style="border-radius: 10px;">
                    <div class="card-body">
                        <div class="font-weight-bolder font-size-h2 mt-3">{{ $emPercurso }} <span class="font-size-sm">em rua</span> / {{ $disponiveis }} <span class="font-size-sm">pátio</span></div>
                        <div class="font-weight-bold font-size-lg mt-1">Status Global da Frota</div>
                    </div>
                </div>
            </div>
        </div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card card-custom gutter-b">
            <div class="card-header">
                <h3 class="card-title font-weight-bolder text-warning">Veículos Parados / Manutenção Crítica</h3>
            </div>
            <div class="card-body">
                @forelse($veiculosManutencao as $vm)
                    <span class="badge badge-light-danger p-3 mr-2">
                        <i class="fa fa-wrench mr-1"></i> {{ $vm->placa }} - {{ $vm->modelo }}
                    </span>
                @empty
                    <p class="text-muted">Nenhum veículo em manutenção crítica no momento.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>      
      
        @php
            $hasAlerts = false;
            foreach($veiculos as $veiculo) {
                if($veiculo->status_manutencao_nivel == 'vencido' || $veiculo->status_manutencao_nivel == 'alerta') {
                    $hasAlerts = true;
                    break;
                }
            }
        @endphp

        @if($hasAlerts)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card card-custom border border-danger">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bolder text-danger">Alertas de Manutenção Críticos</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($veiculos as $veiculo)
                                @php $status = $veiculo->status_manutencao_nivel; @endphp
                                @if($status == 'vencido' || $status == 'alerta')
                                    <div class="col-md-4 mb-3">
                                        <div class="p-3 border rounded {{ $status == 'vencido' ? 'bg-light-danger' : 'bg-light-warning' }}">
                                            <span class="font-weight-bold">{{ $veiculo->placa }}</span> - {{ $veiculo->modelo }} <br>
                                            @if($status == 'vencido')
                                                <span class="badge badge-danger">MANUTENÇÃO VENCIDA ({{ number_format($veiculo->proxima_manutencao_km, 0, ',', '.') }} KM)</span>
                                            @else
                                                <span class="badge badge-warning">REVISÃO PRÓXIMA</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
<div class="row mt-4">
            <div class="col-12">
                <div class="card card-custom gutter-b @if(count($motoristasAlerta) > 0) border border-warning @endif">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label font-weight-bolder text-dark">
                                <i class="fa fa-id-card text-warning mr-2"></i> Alertas de CNH
                            </span>
                            <span class="text-muted mt-3 font-weight-bold font-size-sm">Motoristas com CNH vencida ou a vencer nos próximos 30 dias</span>
                        </h3>
                    </div>
                    
                    <div class="card-body pt-3 pb-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-vertical-center">
                                <tbody>
                                    @forelse($motoristasAlerta as $mot)
                                        @php
                                            $hoje = \Carbon\Carbon::now();
                                            $vencimento = \Carbon\Carbon::parse($mot->vencimento_cnh);
                                            $diasParaVencer = $hoje->diffInDays($vencimento, false);
                                        @endphp
                                        <tr>
                                            <td class="pl-0" style="width: 50px">
                                                <div class="symbol symbol-50 symbol-light mr-2">
                                                    <span class="symbol-label">
                                                        <span class="font-size-h4 font-weight-bold text-primary">{{ substr($mot->nome, 0, 1) }}</span>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="pl-0">
                                                <a href="/funcionarios/edit/{{ $mot->id }}" class="text-dark-75 font-weight-bolder text-hover-primary mb-1 font-size-lg">
                                                    {{ $mot->nome }}
                                                </a>
                                                <span class="text-muted font-weight-bold d-block">Categoria: {{ $mot->categoria_cnh ?? 'Não informada' }}</span>
                                            </td>
                                            <td>
                                                <span class="text-dark-75 font-weight-bolder d-block font-size-lg">
                                                    Vencimento: {{ \Carbon\Carbon::parse($mot->vencimento_cnh)->format('d/m/Y') }}
                                                </span>
                                            </td>
                                            <td class="text-right pr-0">
                                                @if($diasParaVencer <= 0)
                                                    <span class="label label-danger label-inline font-weight-bold p-3" title="CNH Vencida!">VENCIDA</span>
                                                @else
                                                    <span class="label label-warning label-inline font-weight-bold p-3" title="Vence em {{ $diasParaVencer }} dias">EM {{ $diasParaVencer }} DIAS</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted font-weight-bold py-5">
                                                <i class="la la-check-circle text-success mb-2" style="font-size: 30px"></i><br>
                                                Todas as CNHs estão dentro da validade.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-lg-8 mb-4">
                <div class="card card-custom gutter-b h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title font-weight-bolder">Histórico: KM vs Custos Financeiros</h3>
                    </div>
                    <div class="card-body" style="height: 350px; position: relative;">
                        <canvas id="graficoHistorico"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="card card-custom gutter-b h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title font-weight-bolder">Despesas por Categoria</h3>
                    </div>
                    <div class="card-body d-flex justify-content-center align-items-center" style="height: 350px; position: relative;">
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
        // --- 1. GRÁFICO DE HISTÓRICO ---
        const ctxHistorico = document.getElementById('graficoHistorico').getContext('2d');
        new Chart(ctxHistorico, {
            type: 'bar',
            data: {
                labels: {!! $graficoMeses !!},
                datasets: [
                    {
                        type: 'line',
                        label: 'Contas a Pagar (R$)',
                        data: {!! $graficoContas !!},
                        borderColor: '#f64e60',
                        backgroundColor: '#f64e60',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'Despesas de Viagem (R$)',
                        data: {!! $graficoDespesas !!},
                        borderColor: '#ffa800',
                        backgroundColor: '#ffa800',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: 'KM Rodado',
                        data: {!! $graficoKm !!},
                        backgroundColor: '#3699ff',
                        borderRadius: 4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'R$ Custo' } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'KM Rodado' } }
                }
            }
        });

        // --- 2. GRÁFICO DE CATEGORIAS ---
        const ctxCategorias = document.getElementById('graficoCategorias').getContext('2d');
        const labelsPizza = {!! $pizzaLabels !!};
        const valoresPizza = {!! $pizzaValores !!};

        if(valoresPizza.length > 0) {
            new Chart(ctxCategorias, {
                type: 'doughnut',
                data: {
                    labels: labelsPizza,
                    datasets: [{
                        data: valoresPizza,
                        backgroundColor: ['#1bc5bd', '#8950fc', '#ffa800', '#f64e60', '#3699ff', '#e4e6ef'] 
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        } else {
            document.getElementById('graficoCategorias').parentElement.innerHTML = '<h5 class="text-muted mt-5">Nenhum custo registrado.</h5>';
        }
    });
</script>
@endsection