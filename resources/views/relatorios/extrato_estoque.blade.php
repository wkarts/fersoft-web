@extends('default.layout')

@section('content')
<div class="container-fluid mt-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold"><i class="fa fa-history me-2"></i> Extrato Detalhado de Movimentação</h5>
            <span class="small badge bg-secondary text-white fw-normal">Histórico do Item</span>
        </div>
        
        <div class="card-body bg-white">
            <div class="row g-3 mb-4 p-3 bg-light rounded border">
                <div class="col-md-6">
                    <span class="text-muted d-block small uppercase fw-bold">Produto</span>
                    <h5 class="fw-bold text-primary mb-0">[{{ $produto->id }}] {{ $produto->nome }}</h5>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block small uppercase fw-bold">Referência</span>
                    <span class="badge bg-white text-dark border px-2 py-1 mt-1 fw-bold">{{ $produto->referencia ?? 'Não informada' }}</span>
                </div>
                <div class="col-md-3 text-end">
                    <span class="text-muted d-block small uppercase fw-bold">Intervalo de Datas</span>
                    <span class="fw-bold text-dark mt-1 d-block">
                        {{ \Carbon\Carbon::parse($dataInicial)->format('d/m/Y') }} à {{ \Carbon\Carbon::parse($dataFinal)->format('d/m/Y') }}
                    </span>
                </div>
            </div>

            <div class="table-responsive bg-white rounded border">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light border-bottom text-secondary fw-bold small">
                        <tr>
                            <th class="ps-3" width="140">Data / Hora</th>
                            <th width="160">Tipo de Operação</th>
                            <th width="100">Nº Nota</th>
                            <th>Cliente / Fornecedor</th>
                            <th class="text-end" width="120">Qtd Entrada</th>
                            <th class="text-end" width="120">Qtd Saída</th>
                            <th class="text-end pe-3" width="140">Saldo Geral</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <tr class="table-warning border-top border-bottom fw-bold text-dark">
                            <td class="ps-3 text-secondary">Até {{ \Carbon\Carbon::parse($dataInicial)->format('d/m/Y') }}</td>
                            <td colspan="3"><i class="fa fa-calculator me-2 text-warning"></i> SALDO RETIDO ANTERIOR AO PERÍODO</td>
                            <td class="text-end pe-3 text-primary" colspan="3">{{ number_format($saldoInicial, 3, ',', '.') }}</td>
                        </tr>

                        @php $saldoAcumulado = $saldoInicial; @endphp
                        
                        @forelse($movimentacoes as $mov)
                            @php
                                if($mov->tipo == 'entrada') {
                                    $saldoAcumulado += $mov->qtd;
                                } else {
                                    $saldoAcumulado -= $mov->qtd;
                                }
                            @endphp
                            <tr>
                                <td class="ps-3 text-muted">{{ \Carbon\Carbon::parse($mov->data)->format('d/m/Y H:i') }}</td>
                                <td class="fw-semibold text-dark">{{ $mov->operacao }}</td>
                                <td>
                                    <span class="text-secondary fw-bold">{{ $mov->numero_nota }}</span>
                                </td>
                                <td class="text-dark fw-normal">
                                    {{ $mov->pessoa }}
                                </td>
                                <td class="text-end fw-bold text-success" style="text-align: right !important;">
                                    {{ $mov->tipo == 'entrada' ? number_format($mov->qtd, 3, ',', '.') : '-' }}
                                </td>

                                <td class="text-end fw-bold text-danger" style="text-align: right !important;">
                                    {{ $mov->tipo == 'saida' ? number_format($mov->qtd, 3, ',', '.') : '-' }}
                                </td>

                                <td class="text-end pe-3 fw-bold {{ $saldoAcumulado < 0 ? 'text-danger' : 'text-dark' }}" style="text-align: right !important;">
                                    {{ number_format($saldoAcumulado, 3, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted bg-white">
                                    <i class="fa fa-info-circle"></i> Sem movimentações registradas para este item no intervalo de tempo selecionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    
                    <tfoot class="table-light border-top border-2">
                        @php
                            // O Laravel soma automaticamente todas as entradas e saídas daquela lista
                            $totalEntradas = $movimentacoes->where('tipo', 'entrada')->sum('qtd');
                            $totalSaidas = $movimentacoes->where('tipo', 'saida')->sum('qtd');
                        @endphp
                        <tr>
                            <td colspan="4" class="text-end fw-bold text-secondary text-uppercase pe-3" style="vertical-align: middle;">
                                Totais do Período filtrado:
                            </td>
                            
                            <td class="text-end fw-bold text-success" style="text-align: right !important; font-size: 1.1em;">
                                {{ number_format($totalEntradas, 3, ',', '.') }}
                            </td>
                            
                            <td class="text-end fw-bold text-danger" style="text-align: right !important; font-size: 1.1em;">
                                {{ number_format($totalSaidas, 3, ',', '.') }}
                            </td>
                            
                            <td class="text-end fw-bold bg-light" style="text-align: right !important;">
                                -
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection