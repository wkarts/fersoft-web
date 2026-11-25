@extends('default.layout')
@section('content')

    <div class="card card-custom gutter-b">
        <div class="card-header border-0 py-5">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label font-weight-bolder text-dark">Controle de Lotes & Rastreabilidade</span>
                <span class="text-muted mt-3 font-weight-bold font-size-sm">Árvore de entrada, produção e expedição</span>
            </h3>
        </div>

        <div class="card-body">
            <form method="GET" action="" class="mb-8">
                <div class="input-group">
                    <input type="text" name="pesquisa" class="form-control" placeholder="Procurar por Código, Fornecedor, NF-e ou MTR" value="{{ request('pesquisa') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Procurar</button>
                    </div>
                </div>
            </form>

            <div class="accordion accordion-toggle-arrow" id="accordionLotes">
                @foreach($lotes as $lote)
                    <!-- LOTE MÃE (Cabeçalho do Accordion) -->
                    <div class="card mb-2" style="border: 1px solid #E4E6EF;">
                        <div class="card-header bg-light" id="heading{{ $lote->id }}">
                            <div class="card-title collapsed" data-toggle="collapse" data-target="#collapse{{ $lote->id }}">
                                <div class="d-flex justify-content-between w-100 align-items-center">
                                    <div>
                                        <span class="badge badge-success font-weight-bold mr-2">{{ $lote->codigo_lote }}</span>
                                        <strong class="text-dark">{{ optional($lote->fornecedor)->razao_social ?? 'S/ Fornecedor' }}</strong>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-muted mr-3"><i class="fa fa-calendar-alt"></i> {{ $lote->created_at->format('d/m/Y') }}</span>
                                        <span class="badge badge-secondary font-weight-bold">Entrada: {{ number_format($lote->peso_entrada, 2, ',', '.') }} kg</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DETALHES (Corpo do Accordion) -->
                        <div id="collapse{{ $lote->id }}" class="collapse" data-parent="#accordionLotes">
                            <div class="card-body">

                                <div class="row mb-5">
                                    <div class="col-md-4">
                                        <p class="mb-1"><strong>Produto Origem:</strong> {{ optional($lote->produtoOrigem)->nome }}</p>
                                        <p class="mb-1"><strong>NF-e Entrada:</strong> {{ $lote->nfe_entrada ?? 'N/A' }}</p>
                                        <p class="mb-0"><strong>MTR Entrada:</strong> {{ $lote->mtr_entrada ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1"><strong>Peso Produzido:</strong> <span class="text-info">{{ number_format($lote->peso_produzido, 2, ',', '.') }} kg</span></p>
                                        <p class="mb-1"><strong>Quebra/Perda:</strong> <span class="text-danger">{{ number_format($lote->peso_perda, 2, ',', '.') }} kg</span></p>
                                        <p class="mb-0"><strong>Status:</strong> <span class="label label-inline label-light-primary">{{ strtoupper($lote->status) }}</span></p>
                                    </div>
                                </div>

                                <ul class="nav nav-tabs nav-tabs-line" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-toggle="tab" href="#resumo_{{ $lote->id }}">
                                            <span class="nav-icon"><i class="fa fa-chart-pie"></i></span>
                                            <span class="nav-text font-weight-bold">Resumo de Saldos</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="tab" href="#historico_{{ $lote->id }}">
                                            <span class="nav-icon"><i class="fa fa-history"></i></span>
                                            <span class="nav-text">Histórico de Pesagens (Detalhado)</span>
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content mt-4">
                                    <!-- TAB 1: RESUMO CONSOLIDADO POR PRODUTO (Agrupado) -->
                                    <div class="tab-pane fade show active" id="resumo_{{ $lote->id }}" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm table-striped">
                                                <thead class="thead-dark">
                                                <tr>
                                                    <th>Produto (Sublote)</th>
                                                    <th class="text-right">Entrada Total (Produzido)</th>
                                                    <th class="text-right">Saída Total (Expedido via PEPS)</th>
                                                    <th class="text-right">Saldo Atual em Estoque</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @forelse($lote->resumo_produtos as $resumo)
                                                    <tr>
                                                        <td class="align-middle"><strong>{{ $resumo['nome'] }}</strong></td>
                                                        <td class="text-right align-middle text-info font-weight-bolder" style="font-size: 1.1em;">
                                                            {{ number_format($resumo['entrada'], 2, ',', '.') }} kg
                                                        </td>
                                                        <td class="text-right align-middle text-danger font-weight-bolder" style="font-size: 1.1em;">
                                                            {{ number_format($resumo['saida'], 2, ',', '.') }} kg
                                                        </td>
                                                        <td class="text-right align-middle text-success font-weight-bolder" style="font-size: 1.1em;">
                                                            {{ number_format($resumo['saldo'], 2, ',', '.') }} kg
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center text-muted">Nenhum produto gerado neste lote ainda.</td></tr>
                                                @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- TAB 2: HISTÓRICO DETALHADO (Tickets Individuais) -->
                                    <div class="tab-pane fade" id="historico_{{ $lote->id }}" role="tabpanel">
                                        <div class="row">
                                            <!-- Histórico de Entradas (Cortes da Produção) -->
                                            <div class="col-lg-6 mb-4">
                                                <h6 class="text-info"><i class="fa fa-arrow-down text-info"></i> Pesagens de Entrada (Produção)</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm">
                                                        <thead class="thead-light">
                                                        <tr>
                                                            <th>Data</th>
                                                            <th>Produto</th>
                                                            <th class="text-right">Peso Líquido</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        @forelse($lote->cortes as $corte)
                                                            <tr>
                                                                <td>{{ $corte->created_at->format('d/m H:i') }}</td>
                                                                <td>{{ optional($corte->produto)->nome }}</td>
                                                                <td class="text-right">{{ number_format($corte->peso_liquido, 2, ',', '.') }} kg</td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="3" class="text-center">Sem registos</td></tr>
                                                        @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Histórico de Saídas (Tickets de Venda) -->
                                            <div class="col-lg-6 mb-4">
                                                <h6 class="text-danger"><i class="fa fa-arrow-up text-danger"></i> Pesagens de Saída (Expedição)</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm">
                                                        <thead class="thead-light">
                                                        <tr>
                                                            <th>Data</th>
                                                            <th>Produto</th>
                                                            <th class="text-right">Peso Saída</th>
                                                            <th>Destino / Venda</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        @forelse($lote->ticketsSaida as $ticket)
                                                            <tr>
                                                                <td>{{ $ticket->created_at->format('d/m H:i') }}</td>
                                                                <td>{{ optional($ticket->produto)->nome }}</td>
                                                                <td class="text-right text-danger">{{ number_format($ticket->peso - ($ticket->peso_bag ?? 0), 2, ',', '.') }} kg</td>
                                                                <td>
                                                                    @if(optional($ticket->pesagem)->venda_id)
                                                                        <a href="/vendas/detalhar/{{ $ticket->pesagem->venda_id }}" target="_blank">Venda #{{ $ticket->pesagem->venda_id }}</a>
                                                                    @else
                                                                        <span class="text-muted">Balcão/Pendente</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="4" class="text-center">Sem registos de saída</td></tr>
                                                        @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $lotes->links() }}
            </div>
        </div>
    </div>
@endsection
