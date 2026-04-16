@extends('default.layout')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        .bg-gradient-primary {
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
        }

        .bg-gradient-info {
            background: linear-gradient(180deg, #36b9cc 10%, #258391 100%);
        }

        .display-4 {
            font-size: 2.5rem;
        }

        .card-kpi {
            transition: transform 0.2s;
            border: none;
        }

        .card-kpi:hover {
            transform: translateY(-5px);
        }

        .clickable-financeiro {
            transition: background 0.2s;
            border-radius: 5px;
            cursor: pointer;
        }

        .clickable-financeiro:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .table-container-scroll {
            max-height: 250px;
            overflow-y: auto;
        }
    </style>

    <div class="container-fluid p-4" style="background: #f8f9fc;">
        <div class="row mb-4 align-items-center">
            <div class="col-md-5">
                <h1 class="h4 mb-0 text-gray-800 font-weight-bold">
                    Dashboard Analítico Executivo
                </h1>
                <span class="badge badge-primary px-3 py-2 mt-2 shadow-sm">
                {{ $diasDesc }}
            </span>

                @if(!empty($localPadraoAtual))
                    <div class="mt-2">
                    <span class="badge badge-light border text-muted px-3 py-2 shadow-sm">
                        Local padrão atual:
                        <strong>
                            @if((string)$localPadraoAtual === 'matriz')
                                Matriz (Sede)
                            @else
                                {{
                                    optional($filiais->firstWhere('id', (int)$localPadraoAtual))->nome
                                    ?? ('Filial #' . $localPadraoAtual)
                                }}
                            @endif
                        </strong>
                    </span>
                    </div>
                @endif
            </div>

            <div class="col-md-7 text-right">
                <form action="{{ route('dashboard.analitico') }}" method="GET" class="form-inline justify-content-end">
                    <select name="filial_id" class="form-control mr-2 border-0 shadow-sm" onchange="this.form.submit()">
                        <option value="todos" {{ $filial_id == 'todos' ? 'selected' : '' }}>
                            Todas as Unidades (Consolidado)
                        </option>
                        <option value="matriz" {{ $filial_id == 'matriz' ? 'selected' : '' }}>
                            Matriz (Sede)
                        </option>

                        @foreach($filiais as $f)
                            <option value="{{ $f->id }}" {{ (string)$filial_id === (string)$f->id ? 'selected' : '' }}>
                                {{ $f->nome }}
                            </option>
                        @endforeach
                    </select>

                    <select name="periodo" class="form-control border-0 shadow-sm" onchange="this.form.submit()">
                        <option value="hoje" {{ $periodo == 'hoje' ? 'selected' : '' }}>
                            Hoje
                        </option>
                        <option value="mes" {{ $periodo == 'mes' ? 'selected' : '' }}>
                            Mês Atual
                        </option>
                        <option value="7" {{ $periodo == '7' ? 'selected' : '' }}>
                            Últimos 7 dias
                        </option>
                        <option value="30" {{ $periodo == '30' ? 'selected' : '' }}>
                            Últimos 30 dias
                        </option>
                        <option value="90" {{ $periodo == '90' ? 'selected' : '' }}>
                            Últimos 90 dias
                        </option>
                    </select>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-kpi shadow-sm bg-gradient-primary text-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small font-weight-bold opacity-75">
                                Base de Clientes
                            </h6>
                            <h1 class="display-4 font-weight-bold mb-0">
                                {{ $totalClientes }}
                            </h1>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-kpi shadow-sm bg-gradient-info text-white p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase small font-weight-bold opacity-75">
                                Total de Produtos
                            </h6>
                            <h1 class="display-4 font-weight-bold mb-0">
                                {{ $totalProdutos }}
                            </h1>
                        </div>
                        <i class="fas fa-box-open fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-left-success bg-white p-3 h-100">
                    <small class="text-uppercase font-weight-bold text-muted">
                        Faturamento
                    </small>
                    <h3 class="text-success font-weight-bold">
                        R$ {{ number_format($totalFaturamento, 2, ',', '.') }}
                    </h3>

                    <hr class="my-2">

                    <small class="text-uppercase font-weight-bold text-muted">
                        Ticket Médio
                    </small>
                    <h5 class="text-dark font-weight-bold mb-0">
                        R$ {{ number_format($ticketMedio, 2, ',', '.') }}
                    </h5>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-left-warning bg-white p-3 h-100">
                    <small class="text-uppercase font-weight-bold text-muted">
                        Custo de Estoque
                    </small>
                    <h3 class="text-warning font-weight-bold">
                        R$ {{ number_format($custoEstoque, 2, ',', '.') }}
                    </h3>
                    <p class="small text-muted mt-2 mb-0">
                        Investimento total em mercadorias.
                    </p>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 p-3 h-100 text-center">
                    <h6 class="font-weight-bold text-primary mb-3 text-uppercase">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Top 5 Produtos Vendidos (Qtd)
                    </h6>
                    <div id="chart-produtos"></div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-0 p-3 h-100 text-center">
                    <h6 class="font-weight-bold text-success mb-3 text-uppercase">
                        <i class="fas fa-medal mr-2"></i>
                        Top 5 Clientes (Faturamento)
                    </h6>
                    <div id="chart-clientes"></div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary text-uppercase">
                            <i class="fas fa-cubes mr-2"></i>
                            Produtos com Saldo
                        </h6>
                        <span class="badge badge-primary">
                        {{ count($produtosComEstoque) }} itens
                    </span>
                    </div>

                    <div class="card-body p-0 table-container-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light small sticky-top">
                            <tr>
                                <th class="pl-3 border-0">Produto</th>
                                <th class="text-right border-0">Saldo</th>
                                <th class="text-right pr-3 border-0">Vlr. Venda</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($produtosComEstoque as $pe)
                                <tr class="small border-bottom">
                                    <td class="pl-3 py-2 text-dark font-weight-bold">
                                        {{ Str::limit($pe->nome, 30) }}
                                    </td>
                                    <td class="text-right py-2 text-primary">
                                        {{ number_format($pe->quantidade, 0) }} {{ $pe->unidade_venda }}
                                    </td>
                                    <td class="text-right pr-3 py-2 text-success">
                                        R$ {{ number_format($pe->valor_venda, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        Nenhum produto com saldo nesta unidade.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-danger text-uppercase">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Alerta de Compras (Reposição)
                        </h6>
                        <span class="badge badge-danger">
                        {{ count($produtosAlerta) }} alertas
                    </span>
                    </div>

                    <div class="card-body p-0 table-container-scroll">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light small sticky-top">
                            <tr>
                                <th class="pl-3 border-0">Produto</th>
                                <th class="text-right pr-3 border-0">Estoque Atual</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($produtosAlerta as $pa)
                                <tr class="small border-bottom">
                                    <td class="pl-3 py-2 text-dark">
                                        {{ Str::limit($pa->nome, 35) }}
                                    </td>
                                    <td class="text-right pr-3 py-2 font-weight-bold {{ $pa->quantidade <= 0 ? 'text-danger' : 'text-warning' }}">
                                        {{ number_format($pa->quantidade, 0) }} {{ $pa->unidade_venda }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-success font-weight-bold">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Estoque abastecido!
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 p-3 border-left-danger h-100">
                            <h6 class="small text-danger font-weight-bold text-uppercase">
                                <i class="fas fa-arrow-down mr-1"></i>
                                Contas a Pagar
                            </h6>

                            <div
                                class="clickable-financeiro p-2 mt-2"
                                onclick="window.location.href='{{ url('/contasPagar') }}?status=vencido&filial_id={{ $filial_id }}&periodo={{ $periodo }}'"
                            >
                                <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-exclamation-circle text-danger mr-1"></i>
                                    Vencidas:
                                </span>
                                    <b class="text-danger h5 mb-0">
                                        R$ {{ number_format($pagarVencidas, 2, ',', '.') }}
                                    </b>
                                </div>
                            </div>

                            <div
                                class="clickable-financeiro p-2 mt-2"
                                onclick="window.location.href='{{ url('/contasPagar') }}?status=periodo&filial_id={{ $filial_id }}&periodo={{ $periodo }}'"
                            >
                                <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="far fa-calendar-alt text-dark mr-1"></i>
                                    A Vencer (30 dias):
                                </span>
                                    <b class="text-dark h5 mb-0">
                                        R$ {{ number_format($pagarNoPeriodo, 2, ',', '.') }}
                                    </b>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 p-3 border-left-success h-100">
                            <h6 class="small text-success font-weight-bold text-uppercase">
                                <i class="fas fa-arrow-up mr-1"></i>
                                Contas a Receber
                            </h6>

                            <div
                                class="clickable-financeiro p-2 mt-2"
                                onclick="window.location.href='{{ url('/contasReceber') }}?status=vencido&filial_id={{ $filial_id }}&periodo={{ $periodo }}'"
                            >
                                <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="fas fa-exclamation-circle text-danger mr-1"></i>
                                    Vencidas:
                                </span>
                                    <b class="text-danger h5 mb-0">
                                        R$ {{ number_format($receberVencidas, 2, ',', '.') }}
                                    </b>
                                </div>
                            </div>

                            <div
                                class="clickable-financeiro p-2 mt-2"
                                onclick="window.location.href='{{ url('/contasReceber') }}?status=periodo&filial_id={{ $filial_id }}&periodo={{ $periodo }}'"
                            >
                                <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <i class="far fa-calendar-alt text-primary mr-1"></i>
                                    A Receber (30 dias):
                                </span>
                                    <b class="text-primary h5 mb-0">
                                        R$ {{ number_format($receberNoPeriodo, 2, ',', '.') }}
                                    </b>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white font-weight-bold small text-muted text-uppercase d-flex justify-content-between">
                        <span>Saldos Bancários</span>
                        <i class="fas fa-university"></i>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody class="small">
                            @forelse($saldosContas as $s)
                                <tr class="border-bottom">
                                    <td class="pl-3 py-2 text-muted">
                                        {{ $s->nome }}
                                    </td>
                                    <td class="text-right pr-3 py-2 font-weight-bold {{ $s->saldo < 0 ? 'text-danger' : 'text-primary' }}">
                                        R$ {{ number_format($s->saldo, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-3 text-muted">
                                        Nenhuma conta cadastrada.
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

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        new ApexCharts(document.querySelector("#chart-produtos"), {
            series: [{
                name: 'Qtd Vendida',
                data: @json($produtosMaisVendidos->pluck('total_qtd'))
            }],
            chart: {
                type: 'bar',
                height: 250,
                toolbar: {
                    show: false
                }
            },
            xaxis: {
                categories: @json($produtosMaisVendidos->pluck('nome'))
            },
            colors: ['#4e73df'],
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    barHeight: '50%'
                }
            }
        }).render();

        new ApexCharts(document.querySelector("#chart-clientes"), {
            series: [{
                name: 'Faturamento R$',
                data: @json($topClientes->pluck('total'))
            }],
            chart: {
                type: 'bar',
                height: 250,
                toolbar: {
                    show: false
                }
            },
            xaxis: {
                categories: @json($topClientes->pluck('nome')),
                labels: {
                    formatter: function (val) {
                        return "R$ " + val.toFixed(0);
                    }
                }
            },
            colors: ['#1cc88a'],
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    barHeight: '50%'
                }
            }
        }).render();
    </script>
@endsection
