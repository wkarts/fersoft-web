@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-dark font-weight-bold">
            <i class="fas fa-file-invoice-dollar text-success mr-2"></i> Lote de Pagamento de Pesagens
        </h5>
    </div>
    
    <div class="card-body">
        <form action="/pagamento-lote/gerar-cnab" method="POST" id="formLote">
            @csrf
            <div class="d-flex justify-content-between mb-4">
                <button type="button" class="btn btn-light-info font-weight-bold" onclick="marcarTodos()">
                    <i class="la la-list"></i> Selecionar / Desmarcar Todos
                </button>
                <button type="submit" class="btn btn-success font-weight-bold">
                    <i class="fas fa-check"></i> Gerar Arquivo P/ Banco (PIX)
                </button>
            </div>

            <div class="datatable datatable-bordered datatable-default datatable-primary datatable-loaded">
                <table class="table table-hover">
                    <thead class="datatable-head">
                        <tr class="datatable-row" style="left: 0px;">
                            <th class="datatable-cell" width="50"><span>Pagar</span></th>
                            <th class="datatable-cell"><span>Nº Pesagem</span></th>
                            <th class="datatable-cell"><span>Data</span></th>
                            <th class="datatable-cell"><span>Fornecedor</span></th>
                            <th class="datatable-cell"><span>Chave PIX</span></th>
                            <th class="datatable-cell"><span>Frete Aplicado</span></th>
                            <th class="datatable-cell"><span>Total (Kg)</span></th>
                            <th class="datatable-cell"><span>Valor a Pagar</span></th>
                        </tr>
                    </thead>
                    <tbody class="datatable-body">
                        @forelse($listaParaPagamento as $item)
                            <tr class="datatable-row" style="left: 0px;">
                                <td class="datatable-cell text-center">
                                    <span>
                                        @if($item->tabela_ok && $item->chave_pix != '')
                                            <label class="checkbox checkbox-single">
                                                <input type="checkbox" name="pesagens_ids[]" value="{{ $item->id }}" class="chk-pagar" onchange="verificarBotoes()">
                                                <span></span>
                                            </label>
                                        @else
                                            <i class="fas fa-exclamation-triangle text-warning" title="Verifique o PIX ou a Tabela de Preços do Fornecedor"></i>
                                        @endif
                                    </span>
                                </td>
                                <td class="datatable-cell font-weight-bold"><span>#{{ $item->id }}</span></td>
                                <td class="datatable-cell"><span>{{ \Carbon\Carbon::parse($item->data)->format('d/m/Y') }}</span></td>
                                <td class="datatable-cell"><span>{{ $item->fornecedor_nome }}</span></td>
                                <td class="datatable-cell text-primary font-weight-bold"><span>{{ $item->chave_pix ?: 'NÃO CADASTRADA' }}</span></td>
                                <td class="datatable-cell"><span><span class="label label-inline label-light-info font-weight-bold">{{ $item->frete_usado }}</span></span></td>
                                <td class="datatable-cell"><span>{{ number_format($item->peso_total, 2, ',', '.') }} kg</span></td>
                                <td class="datatable-cell">
                                    <span>
                                        @if($item->tabela_ok)
                                            <strong class="text-success h6">R$ {{ number_format($item->valor_calculado, 2, ',', '.') }}</strong>
                                            <input type="hidden" name="valores[{{ $item->id }}]" value="{{ $item->valor_calculado }}">
                                        @else
                                            <span class="text-danger small">Falta Tabela de Preço</span>
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr class="datatable-row">
                                <td colspan="8" class="datatable-cell text-center text-muted"><span>Nenhuma pesagem pendente de pagamento encontrada.</span></td>
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