@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-dark font-weight-bold">
            <i class="fas fa-file-invoice-dollar text-success mr-2"></i> Lote de Pagamento de Pesagens
        </h5>
    </div>
    
    <div class="card-body">
        
        {{-- FILTRO DE PERÍODO --}}
        <div class="card shadow-sm mb-5 bg-light">
            <div class="card-body py-3">
                <form action="{{ url()->current() }}" method="GET">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label font-weight-bold text-dark">Data Inicial</label>
                            <input type="date" name="data_inicial" class="form-control" value="{{ $dataInicial }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label font-weight-bold text-dark">Data Final</label>
                            <input type="date" name="data_final" class="form-control" value="{{ $dataFinal }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100 font-weight-bold">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <form action="/pagamento-lote/gerar-cnab" method="POST" id="formLote">
            @csrf
            <div class="d-flex justify-content-between mb-4">
                <button type="button" class="btn btn-light-info font-weight-bold shadow-sm" onclick="marcarTodos()">
                    <i class="la la-list"></i> Selecionar / Desmarcar Todos
                </button>
                <button type="submit" class="btn btn-success font-weight-bold shadow-sm" id="btnGerar" disabled>
                    <i class="fas fa-check"></i> Gerar Arquivo P/ Banco (PIX)
                </button>
            </div>

            <div class="datatable datatable-bordered datatable-default datatable-primary datatable-loaded">
                <table class="table table-hover align-middle">
                    <thead class="datatable-head">
                        <tr class="datatable-row bg-secondary">
                            <th class="datatable-cell text-center" width="60"><span>Pagar</span></th>
                            <th class="datatable-cell"><span>Nº Ticket</span></th>
                            <th class="datatable-cell"><span>Data</span></th>
                            <th class="datatable-cell"><span>Fornecedor</span></th>
                            <th class="datatable-cell"><span>Chave PIX</span></th>
                            <th class="datatable-cell text-center"><span>Frete</span></th>
                            <th class="datatable-cell text-right"><span>Preço/KG</span></th>
                            <th class="datatable-cell text-right"><span>Peso Líq.</span></th>
                            <th class="datatable-cell text-right"><span>Valor a Pagar</span></th>
                        </tr>
                    </thead>
                    <tbody class="datatable-body">
                        @forelse($listaParaPagamento as $item)
                            <tr class="datatable-row">
                                <td class="datatable-cell text-center">
                                    <span>
                                        @if($item->tabela_ok && $item->chave_pix != '')
                                            <label class="checkbox checkbox-single checkbox-primary mb-0 mt-2">
                                                <input type="checkbox" name="pesagens_ids[]" value="{{ $item->id }}" class="chk-pagar" onchange="verificarBotoes()">
                                                <span></span>
                                            </label>
                                        @else
                                            <i class="fas fa-exclamation-triangle text-warning mt-2" title="Verifique o PIX, Preços do Fornecedor ou se o Peso Líquido está zerado"></i>
                                        @endif
                                    </span>
                                </td>
                                <td class="datatable-cell font-weight-bold"><span>#{{ $item->id }}</span></td>
                                <td class="datatable-cell"><span>{{ \Carbon\Carbon::parse($item->data)->format('d/m/Y') }}</span></td>
                                <td class="datatable-cell"><span>{{ $item->fornecedor_nome }}</span></td>
                                <td class="datatable-cell text-primary font-weight-bold"><span>{{ $item->chave_pix ?: 'NÃO CADASTRADA' }}</span></td>
                                <td class="datatable-cell text-center">
                                    <span>
                                        @if($item->frete_usado == 'COLETA')
                                            <span class="label label-inline label-light-primary font-weight-bold">COLETA</span>
                                        @else
                                            <span class="label label-inline label-light-info font-weight-bold">ENTREGA</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="datatable-cell text-right">
                                    <span>R$ {{ number_format($item->valor_kg, 4, ',', '.') }}</span>
                                </td>
                                <td class="datatable-cell text-right text-info font-weight-bold">
                                    <span>{{ number_format($item->peso_total, 2, ',', '.') }} kg</span>
                                </td>
                                <td class="datatable-cell text-right">
                                    <span>
                                        @if($item->tabela_ok)
                                            <strong class="text-success h6 mb-0">R$ {{ number_format($item->valor_calculado, 2, ',', '.') }}</strong>
                                            <input type="hidden" name="valores[{{ $item->id }}]" value="{{ $item->valor_calculado }}">
                                        @else
                                            <span class="text-danger small font-weight-bold">Falta Tabela de Preço</span>
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr class="datatable-row">
                                <td colspan="9" class="datatable-cell text-center text-muted py-4">
                                    <span>Nenhuma pesagem pendente de pagamento encontrada para o período selecionado.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

@endsection

@section('javascript')
<script>
    function marcarTodos() {
        let checkboxes = document.querySelectorAll('.chk-pagar');
        let marcados = document.querySelectorAll('.chk-pagar:checked').length;
        let checar = marcados < checkboxes.length; 
        
        checkboxes.forEach(chk => chk.checked = checar);
        verificarBotoes();
    }

    function verificarBotoes() {
        let marcados = document.querySelectorAll('.chk-pagar:checked').length;
        document.getElementById('btnGerar').disabled = marcados === 0;
    }
</script>
@endsection