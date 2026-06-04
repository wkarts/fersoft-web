@extends('default.layout')

@section('content')
<style>
    /* FORÇA A EXIBIÇÃO DO SELECT2 EM QUALQUER TEMA E RESOLVE O ACHATAMENTO */
    .select2-container {
        width: 100% !important;
        display: block !important;
    }
    
    .select2-container .select2-selection--single {
        background-color: #ffffff !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        height: 48px !important;
        min-height: 48px !important;
        display: flex !important;
        align-items: center !important;
        padding-left: 10px !important;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        color: #212529 !important;
        font-weight: bold !important;
        font-size: 1.1rem !important;
        line-height: normal !important;
        padding-left: 0 !important;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 46px !important;
        top: 1px !important;
        right: 10px !important;
    }
    
    /* Input de datas acompanhando o tamanho gigante e bordas visíveis */
    .input-gigante {
        height: 48px !important;
        font-size: 1.1rem !important;
        font-weight: bold !important;
        background-color: #ffffff !important;
        border: 1px solid #ced4da !important;
        color: #212529 !important;
    }
</style>

<div class="container-fluid mt-3">

    <!-- CÁLCULO DOS TOTAIS DA PÁGINA -->
    @php
        $totalValor = $resultados->sum('valor_total');
        $totalSaldo = $resultados->sum('saldo_final');
    @endphp

    <!-- PAINEL DE VALOR TOTAL NO TOPO -->
    <div class="card border-0 shadow-sm mb-3 bg-info text-white" style="background-color: #20c997 !important;">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold"><i class="fa fa-dollar-sign me-2"></i> Valor Total dos Produtos (Nesta Página)</h5>
            <h3 class="mb-0 fw-bold">R$ {{ number_format($totalValor, 2, ',', '.') }}</h3>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold"><i class="fa fa-boxes me-2"></i> Posição de Saldo Real de Estoque</h5>
        </div>
        
        <div class="card-body bg-light-50">
            <form method="GET" action="{{ url('/estoque/saldo-real') }}" class="row g-3 align-items-end mb-4 p-3 bg-white rounded shadow-sm border">
    
                <!-- Primeira Linha: Datas e Filial -->
                <div class="col-md-3">
                    <label class="form-label fw-bold text-secondary">Data Inicial</label>
                    <input type="date" name="data_inicial" class="form-control input-gigante" value="{{ request('data_inicial') ?? \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold text-secondary">Data Final</label>
                    <input type="date" name="data_final" class="form-control input-gigante" value="{{ request('data_final') ?? \Carbon\Carbon::now()->format('Y-m-d') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold text-secondary">Matriz / Filial</label>
                    <!-- Substituído form-select por form-control para evitar conflito com o Select2 -->
                    <select name="filial_id" class="form-control select2">
                        <option value="">Todas (Matriz e Filiais)</option>
                        <option value="-1" {{ request('filial_id') == '-1' ? 'selected' : '' }}>Matriz</option>
                        @foreach($filiais as $f)
                            <option value="{{ $f->id }}" {{ request('filial_id') == $f->id ? 'selected' : '' }}>{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Segunda Linha: Categorias -->
                <div class="col-md-6">
                    <label class="form-label fw-bold text-secondary">Categoria</label>
                    <select name="categoria_id" class="form-control select2">
                        <option value="">Todas as Categorias</option>
                        @foreach($categorias as $c)
                            <option value="{{ $c->id }}" {{ request('categoria_id') == $c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold text-secondary">Sub Categoria</label>
                    <select name="sub_categoria_id" class="form-control select2">
                        <option value="">Todas as Sub Categorias</option>
                        @foreach($subCategorias as $sub)
                            <option value="{{ $sub->id }}" {{ request('sub_categoria_id') == $sub->id ? 'selected' : '' }}>
                                {{ $sub->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Terceira Linha: Produto (Gigante) e Botão Filtrar -->
                <div class="col-md-10">
                    <label class="form-label fw-bold text-secondary">Produto</label>
                    <select name="produto_id" class="form-control select2">
                        <option value="">Todos os Produtos</option>
                        @foreach($produtos_filtro as $p)
                            <option value="{{ $p->id }}" {{ request('produto_id') == $p->id ? 'selected' : '' }}>
                                [{{ $p->id }}] {{ $p->referencia ?? '' }} - {{ $p->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm" style="height: 48px; font-size: 1.1rem;">
                        <i class="fa fa-filter me-1"></i> Filtrar
                    </button>
                </div>

            </form>
            
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Referência</th>
                            <th>Descrição do Produto</th>
                            <th class="text-end">Saldo Inicial</th>
                            <th class="text-end">Entradas</th>
                            <th class="text-end">Saídas</th>
                            <th class="text-end">Saldo Final</th>
                            <th class="text-end">Custo Médio</th>
                            <th class="text-end">Valor Total</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resultados as $item)
                        <tr>
                            <td class="fw-bold">{{ $item->id }}</td>
                            <td class="fw-bold">{{ $item->referencia ?? '-' }}</td>
                            <td class="text-uppercase fw-bold text-dark" style="font-size: 1.05rem;">{{ $item->descricao }}</td>
                            
                            <td class="text-end fw-bold" style="text-align: right !important; color: #6c757d;">
                                {{ number_format($item->saldo_inicial, 3, ',', '.') }}
                            </td>
                            
                            <td class="text-end fw-bold" style="text-align: right !important; color: #198754;">
                                {{ number_format($item->entradas, 3, ',', '.') }}
                            </td>
                            
                            <td class="text-end fw-bold" style="text-align: right !important; color: #fd7e14;">
                                {{ number_format($item->saidas, 3, ',', '.') }}
                            </td>
                            
                            <td class="text-end fw-bold fs-6 {{ $item->saldo_final >= 0 ? 'text-primary' : 'text-danger' }}" style="text-align: right !important;">
                                {{ number_format($item->saldo_final, 3, ',', '.') }}
                            </td>
                            
                            <td class="text-end fw-bold" style="text-align: right !important;">R$ {{ number_format($item->custo_medio, 2, ',', '.') }}</td>
                            <td class="text-end fw-bold text-success" style="text-align: right !important;">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
                            <td class="text-center">
                                <a href="{{ url('/estoque/extrato/' . $item->id) }}?data_inicial={{ request('data_inicial') }}&data_final={{ request('data_final') }}&filial_id={{ request('filial_id') }}" 
                                   class="btn btn-sm btn-outline-info fw-bold rounded px-2 py-1" 
                                   target="_blank">
                                    <i class="fa fa-eye me-1"></i> Extrato
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5">Nenhum registro localizado.</td>
                        </tr>
                        @endforelse
                    </tbody>

                    <!-- RODAPÉ COM O TOTAL DA QUANTIDADE -->
                    @if(count($resultados) > 0)
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="6" class="text-end fw-bold text-uppercase fs-6" style="vertical-align: middle;">
                                Total de Peças em Estoque (Nesta Página):
                            </td>
                            <td class="text-end fw-bold fs-5 text-warning" style="text-align: right !important;">
                                {{ number_format($totalSaldo, 3, ',', '.') }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                    @endif

                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                {{ $resultados->appends(request()->query())->links() }}
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Inicializa o Select2
        $('.select2').select2({
            width: '100%',
            placeholder: "Selecione...",
            allowClear: true
        });
    });
</script>
@endsection