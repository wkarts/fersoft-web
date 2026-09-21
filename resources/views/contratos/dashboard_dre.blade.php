@extends('default.layout')
@section('content')

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-primary font-weight-bold m-0">
                <i class="fa fa-chart-pie"></i> {{ $title }}
            </h4>
            
            <div class="d-flex align-items-center">
                <!-- Botão Exportar Excel -->
                <a href="{{ route('contratos.dashboard-dre.excel', ['status' => $statusFiltro]) }}" class="btn btn-sm btn-success font-weight-bold mr-3">
                    <i class="fa fa-file-excel"></i> Exportar Excel
                </a>

                <!-- Filtro de Status -->
                <form method="GET" action="{{ route('contratos.dashboard-dre') }}" class="form-inline">
                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="Ativo" {{ $statusFiltro == 'Ativo' ? 'selected' : '' }}>Somente Ativos</option>
                        <option value="Finalizado" {{ $statusFiltro == 'Finalizado' ? 'selected' : '' }}>Somente Finalizados</option>
                        <option value="todos" {{ $statusFiltro == 'todos' ? 'selected' : '' }}>Todos os Contratos</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Cards de Indicadores (KPIs) -->
        <div class="row mb-4">
            <div class="col-md-2 col-6 mb-2">
                <div class="card bg-light border-0 shadow-sm p-3 text-center">
                    <span class="text-muted small font-weight-bold">OBRAS ATIVAS</span>
                    <h3 class="text-primary font-weight-bold m-0">{{ $totalContratosAtivos }}</h3>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-2">
                <div class="card bg-light border-0 shadow-sm p-3 text-center">
                    <span class="text-muted small font-weight-bold">A VENCER (30D)</span>
                    <h3 class="{{ $totalProximosFim > 0 ? 'text-warning' : 'text-secondary' }} font-weight-bold m-0">
                        {{ $totalProximosFim }}
                    </h3>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-2">
                <div class="card bg-light border-0 shadow-sm p-3 text-center">
                    <span class="text-muted small font-weight-bold">MARGEM CRÍTICA (&lt;15%)</span>
                    <h3 class="{{ $totalMargemCritica > 0 ? 'text-danger' : 'text-success' }} font-weight-bold m-0">
                        {{ $totalMargemCritica }}
                    </h3>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card bg-light border-0 shadow-sm p-3">
                    <span class="text-muted small font-weight-bold">RECEITA FATURADA</span>
                    <h4 class="text-success font-weight-bold m-0">R$ {{ number_format($totalReceitasGeral, 2, ',', '.') }}</h4>
                </div>
            </div>
            <div class="col-md-3 col-12 mb-2">
                <div class="card bg-light border-0 shadow-sm p-3">
                    <span class="text-muted small font-weight-bold">LUCRO / MARGEM GLOBAL</span>
                    <h4 class="{{ $lucroGeral >= 0 ? 'text-primary' : 'text-danger' }} font-weight-bold m-0">
                        R$ {{ number_format($lucroGeral, 2, ',', '.') }}
                    </h4>
                    <small class="{{ $margemGeral >= 0 ? 'text-primary' : 'text-danger' }} font-weight-bold">
                        Margem Geral: {{ number_format($margemGeral, 1, ',', '.') }}%
                    </small>
                </div>
            </div>
        </div>

        <!-- Bloco de Gráfico Comparativo -->
        <div class="card border rounded mb-4 bg-white p-3 shadow-sm">
            <h5 class="font-weight-bold text-dark mb-3"><i class="fa fa-chart-bar text-primary"></i> Comparativo de Receitas x Custos por Obra</h5>
            <div style="height: 280px; position: relative;">
                <canvas id="graficoDreObras"></canvas>
            </div>
        </div>

        <!-- Tabela de Resultado por Contrato -->
        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered align-middle">
                <thead class="bg-dark text-white">
                    <tr>
                        <th style="width: 8%;">Contrato</th>
                        <th style="width: 25%;">Cliente</th>
                        <th style="width: 15%;">Término Previsto</th>
                        <th class="text-right" style="width: 13%;">Faturado (R$)</th>
                        <th class="text-right" style="width: 13%;">Custos (R$)</th>
                        <th class="text-right" style="width: 13%;">Resultado (R$)</th>
                        <th class="text-center" style="width: 8%;">Margem %</th>
                        <th class="text-center" style="width: 5%;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($relatorio as $c)
                    <tr class="{{ $c->margem_critica ? 'table-danger' : '' }}">
                        <td class="font-weight-bold">
                            #{{ $c->numero_contrato ?? $c->id }}
                            @if($c->proximo_fim)
                                <br><span class="badge badge-warning" title="Vence em menos de 30 dias"><i class="fa fa-clock"></i> Próx. Fim</span>
                            @endif
                        </td>
                        <td>{{ $c->cliente_nome ?? 'Cliente não informado' }}</td>
                        <td>
                            @if(!empty($c->data_fim))
                                {{ date('d/m/Y', strtotime($c->data_fim)) }}
                                @if($c->status == 'Ativo' && !is_null($c->dias_restantes))
                                    <small class="d-block text-muted">
                                        {{ $c->dias_restantes >= 0 ? '(' . $c->dias_restantes . ' dias rest.)' : '(Vencido há ' . abs($c->dias_restantes) . ' dias)' }}
                                    </small>
                                @endif
                            @else
                                <span class="text-muted">Indefinido</span>
                            @endif
                        </td>
                        <td class="text-right font-weight-bold text-success">
                            R$ {{ number_format($c->total_receitas, 2, ',', '.') }}
                        </td>
                        <td class="text-right font-weight-bold text-danger">
                            R$ {{ number_format($c->total_despesas, 2, ',', '.') }}
                        </td>
                        <td class="text-right font-weight-bold {{ $c->lucro >= 0 ? 'text-primary' : 'text-danger' }}">
                            R$ {{ number_format($c->lucro, 2, ',', '.') }}
                        </td>
                        <td class="text-center font-weight-bold">
                            @if($c->margem_critica)
                                <span class="badge badge-danger p-2" title="Alerta: Margem abaixo de 15% ou em Prejuízo!">
                                    <i class="fa fa-exclamation-triangle"></i> {{ number_format($c->margem, 1, ',', '.') }}%
                                </span>
                            @else
                                <span class="badge badge-{{ $c->margem >= 25 ? 'success' : 'info' }} p-2">
                                    {{ number_format($c->margem, 1, ',', '.') }}%
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="/contratos/detalhes/{{ $c->id }}" class="btn btn-sm btn-info" title="Ver DRE Detalhada e Alocações">
                                <i class="fa fa-search"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Nenhum contrato encontrado para o filtro selecionado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Import do Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('graficoDreObras').getContext('2d');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! $chartLabels !!},
                datasets: [
                    {
                        label: 'Receita Faturada (R$)',
                        data: {!! $chartReceitas !!},
                        backgroundColor: 'rgba(40, 167, 69, 0.75)',
                        borderColor: '#28a745',
                        borderWidth: 1
                    },
                    {
                        label: 'Custos / Despesas (R$)',
                        data: {!! $chartDespesas !!},
                        backgroundColor: 'rgba(220, 53, 69, 0.75)',
                        borderColor: '#dc3545',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    });
</script>

@endsection