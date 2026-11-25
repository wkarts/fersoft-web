@extends('default.layout')

@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-header flex-wrap border-0 pt-6 pb-0">
            <div class="card-title">
                <h3 class="card-label">
                    <i class="fa fa-file-alt text-primary"></i> Dossiê de Rastreabilidade & Histórico
                    <span class="d-block text-muted pt-2 font-size-sm">Lote Mãe: <strong>{{ $lote->codigo_lote }}</strong></span>
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('producao.gerenciamento') }}" class="btn btn-light-primary font-weight-bolder btn-sm">
                    <i class="la la-arrow-left"></i> Voltar ao Painel
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- CARDS DE INDICADORES FINANCEIROS E BALANÇO DE MASSA -->
            <div class="row mb-6">
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                    <div class="card card-custom bg-light-primary gutter-b card-stretch">
                        <div class="card-body">
                            <span class="card-title font-weight-bolder text-dark-75 font-size-h5 d-block mb-1">Matéria-Prima Inicial</span>
                            <span class="font-weight-bold text-primary font-size-h3">{{ number_format($pesoEntrada ?? 0, 2, ',', '.') }} kg</span>
                            <span class="text-muted font-size-sm d-block mt-2">{{ optional($lote->produtoOrigem)->nome ?? 'N/I' }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                    <div class="card card-custom bg-light-success gutter-b card-stretch">
                        <div class="card-body">
                            <span class="card-title font-weight-bolder text-dark-75 font-size-h5 d-block mb-1">Total Beneficiado</span>
                            <span class="font-weight-bold text-success font-size-h3">{{ number_format($totalProduzido ?? 0, 2, ',', '.') }} kg</span>
                            <span class="text-muted font-size-sm d-block mt-2">{{ $lote->itens ? $lote->itens->count() : 0 }} pesagens realizadas</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                    <div class="card card-custom bg-light-danger gutter-b card-stretch">
                        <div class="card-body">
                            <span class="card-title font-weight-bolder text-dark-75 font-size-h5 d-block mb-1">Quebra / Perda</span>
                            <span class="font-weight-bold text-danger font-size-h3">{{ number_format($perdaCalculada ?? 0, 2, ',', '.') }} kg</span>
                            <span class="text-muted font-size-sm d-block mt-2">Taxa de Perda: {{ number_format($percentualPerda ?? 0, 1, ',', '.') }}%</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                    <div class="card card-custom bg-light-info gutter-b card-stretch">
                        <div class="card-body">
                            <span class="card-title font-weight-bolder text-dark-75 font-size-h5 d-block mb-1">Margem & Rentabilidade</span>
                            <span class="font-weight-bold {{ ($lucroBruto ?? 0) < 0 ? 'text-danger' : 'text-info' }} font-size-h3">
                            R$ {{ number_format($lucroBruto ?? 0, 2, ',', '.') }}
                        </span>
                            <span class="text-muted font-size-sm d-block mt-2">Margem estimada: {{ number_format($margemLucro ?? 0, 1, ',', '.') }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DADOS CADASTRAIS DO LOTE -->
            <div class="card card-custom bg-light gutter-b">
                <div class="card-body py-4">
                    <div class="row">
                        <div class="col-md-4">
                            <span class="text-muted d-block font-size-sm">Fornecedor / Origem:</span>
                            <strong class="text-dark">{{ optional($lote->fornecedor)->razao_social ?? 'Avulso / Interno' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block font-size-sm">Nota Fiscal de Compra:</span>
                            <strong class="text-dark">{{ $lote->nfe_entrada ?? '---' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block font-size-sm">MTR / Manifesto:</span>
                            <strong class="text-dark">{{ $lote->mtr_entrada ?? '---' }}</strong>
                        </div>
                        <div class="col-md-2">
                            <span class="text-muted d-block font-size-sm">Status Atual:</span>
                            <span class="badge badge-primary">{{ strtoupper($lote->status) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 📊 TOTALIZADORES POR PRODUTO GERADO -->
            <div class="mb-8">
                <h4 class="font-size-h5 font-weight-bold mb-4"><i class="fa fa-boxes text-success"></i> Resumo de Produção por Produto (Totalizadores)</h4>
                <div class="row">
                    @forelse($resumoProdutos ?? [] as $resumo)
                        <div class="col-md-4 mb-3">
                            <div class="card border border-success bg-light-success p-4">
                                <span class="font-weight-bolder text-dark font-size-lg d-block">{{ $resumo['produto_nome'] }}</span>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="text-success font-weight-bold font-size-h4">{{ number_format($resumo['peso_total'], 2, ',', '.') }} kg</span>
                                    <span class="badge badge-success font-weight-bold">{{ $resumo['quantidade_pesagens'] }} pesagem(ns)</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-md-12 text-muted text-center py-3">Nenhum produto gerado registrado até o momento.</div>
                    @endforelse
                </div>
            </div>

            <!-- TABELA DE EXPEDIÇÃO / SAÍDAS DO LOTE (VENDAS) -->
            <div class="card card-custom gutter-b mt-4">
                <div class="card-header bg-light-primary">
                    <div class="card-title">
                        <h5 class="card-label font-weight-bolder text-primary">
                            <i class="fa fa-truck-loading text-primary mr-2"></i> Rastreabilidade de Saída / Expedição (Vendas)
                        </h5>
                    </div>
                    <div class="card-toolbar">
                    <span class="badge badge-success font-size-sm p-2 mr-2">
                        Expedido: {{ number_format($totalExpedido ?? 0, 2, ',', '.') }} kg
                    </span>
                        <span class="badge badge-warning font-size-sm p-2">
                        Saldo no Galpão: {{ number_format($saldoRestanteGalpao ?? 0, 2, ',', '.') }} kg
                    </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                            <tr>
                                <th>Data / Hora</th>
                                <th>Ticket Pesagem</th>
                                <th>Cliente</th>
                                <th>Veículo</th>
                                <th>Produto Retirado</th>
                                <th class="text-right">Peso Saída (kg)</th>
                                <th class="text-center">Venda / Fiscal</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($saidasLote ?? [] as $saida)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($saida->data_saida)->format('d/m/Y H:i') }}</td>
                                    <td><span class="badge badge-secondary">Pesagem #{{ $saida->pesagem_id }} (Ticket #{{ $saida->ticket_id }})</span></td>
                                    <td class="font-weight-bold">{{ $saida->cliente_nome ?? 'Consumidor / Avulso' }}</td>
                                    <td><span class="badge badge-light-dark font-weight-bold">{{ $saida->veiculo_placa ?? 'N/A' }}</span></td>
                                    <td>{{ $saida->produto_nome }}</td>
                                    <td class="text-right font-weight-bold text-danger">
                                        - {{ number_format($saida->peso_saida, 2, ',', '.') }} kg
                                    </td>
                                    <td class="text-center">
                                        @if($saida->venda_id)
                                            <a href="/vendas/detalhar/{{ $saida->venda_id }}" class="btn btn-xs btn-primary font-weight-bold" target="_blank">
                                                <i class="fa fa-receipt"></i> Venda #{{ $saida->venda_id }}
                                            </a>
                                        @else
                                            <span class="badge badge-light-warning">Aguardando Faturamento</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fa fa-info-circle mr-1"></i> Nenhuma pesagem de saída foi realizada para este lote até o momento.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 🕒 HISTÓRICO DETALHADO DE PESAGENS PARCIAIS -->
            <div class="mt-6">
                <h4 class="font-size-h5 font-weight-bold mb-4"><i class="fa fa-list text-primary"></i> Histórico Detalhado de Pesagens (Chão de Fábrica)</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead class="thead-light">
                        <tr>
                            <th>Data / Hora</th>
                            <th>Produto Gerado (Acabado)</th>
                            <th>Peso Bruto</th>
                            <th>Tara (Bag/Pallet)</th>
                            <th>Peso Líquido</th>
                            <th>Operador (Balanceiro)</th>
                            <th>Equipe / Ajudantes</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($lote->itens ?? [] as $item)
                            <tr>
                                <td>{{ date('d/m/Y H:i', strtotime($item->created_at)) }}</td>
                                <td>
                                    <strong class="text-dark">{{ optional($item->produto)->nome ?? 'Produto ID '.$item->produto_id }}</strong>
                                </td>
                                <td>{{ number_format($item->peso, 2, ',', '.') }} kg</td>
                                <td class="text-danger">{{ number_format($item->peso_bag, 2, ',', '.') }} kg</td>
                                <td><strong class="text-success">{{ number_format($item->peso_liquido, 2, ',', '.') }} kg</strong></td>
                                <td>{{ optional($item->usuario)->nome ?? 'Sistema / Balanceiro' }}</td>
                                <td>
                                    @if(!empty($item->equipe_nomes))
                                        <span class="badge badge-light-info">{{ $item->equipe_nomes }}</span>
                                    @elseif(!empty($item->funcionario))
                                        <span class="badge badge-light-primary">{{ $item->funcionario->nome }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Nenhuma pesagem parcial registrada para este lote ainda.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
