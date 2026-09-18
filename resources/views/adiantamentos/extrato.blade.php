@extends('default.layout', ['title' => 'Extrato de Adiantamentos'])

@section('content')
<div class="container-fluid">
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Extrato: <b>{{ $pessoa->razao_social }}</b></h3>
            <div class="card-tools">
                <a href="{{ route('adiantamentos.index') }}" class="btn btn-default btn-sm">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr class="bg-light">
                        <th>Data</th>
                        <th>Descrição / Documento</th>
                        <th>Entrada (+)</th>
                        <th>Saída (-)</th>
                        <th>Saldo Acumulado</th>
                        <th width="120" class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @php $saldoAcumulado = 0; @endphp

                    @foreach($lancamentos->sortBy('data') as $l)
                        @if($l->status == 'cancelado') @continue @endif

                        @php $saldoAcumulado += $l->valor_total; @endphp
                        <tr>
                            <td>{{ date('d/m/Y', strtotime($l->data)) }}</td>
                            <td>
                                <b>Adiantamento Realizado</b><br>
                                <small class="text-muted">{{ $l->descricao }}</small>
                            </td>
                            <td class="text-success">R$ {{ number_format($l->valor_total, 2, ',', '.') }}</td>
                            <td>-</td>
                            <td class="font-weight-bold">R$ {{ number_format($saldoAcumulado, 2, ',', '.') }}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    @if($l->valor_utilizado == 0)
                                        <button type="button" class="btn btn-xs btn-primary" 
                                            onclick="editarAdiantamento({{ json_encode($l) }})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form action="{{ route('adiantamentos.cancelar', $l->id) }}" method="POST" onsubmit="return confirm('Deseja realmente estornar este lançamento?')">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-danger" title="Estornar/Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge badge-secondary" title="Já possui utilizações">Utilizado</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        
                        {{-- Movimentações (Baixas de Contas) --}}
                        @foreach($l->movimentacoes as $mov)
                            @php $saldoAcumulado -= $mov->valor; @endphp
                            <tr class="text-muted">
                                <td>{{ date('d/m/Y', strtotime($mov->data)) }}</td>
                                <td>
                                    <i class="fas fa-level-up-alt fa-rotate-90"></i> 
                                    
                                    @if($mov->conta_receber_id)
                                        @php
                                            // Carrega a conta que guardamos no backend
                                            $contaReceber = \App\Models\ContaReceber::find($mov->conta_receber_id);
                                            $numeroDocumento = 0;
                                            if($contaReceber) {
                                                $numeroDocumento = $contaReceber->numero_nota_fiscal > 0 ? $contaReceber->numero_nota_fiscal : $contaReceber->nf_numero;
                                            }
                                        @endphp

                                        <strong>Baixa via Adiantamento</strong>
                                        <div class="small">
                                            @if($numeroDocumento > 0)
                                                Nota Fiscal: <span class="badge badge-light-dark font-weight-bold">Nº {{ $numeroDocumento }}</span>
                                            @else
                                                Ref: <span class="text-dark">{{ $contaReceber->referencia ?? 'Venda #' . $mov->conta_receber_id }}</span>
                                            @endif
                                        </div>
                                    @elseif($mov->conta_pagar_id)
                                          @php
                                              // Carrega a conta a pagar para pegar a nota fiscal
                                              $contaPagar = \App\Models\ContaPagar::find($mov->conta_pagar_id);
                                              $numeroNota = ($contaPagar && $contaPagar->numero_nota_fiscal) ? ltrim($contaPagar->numero_nota_fiscal, '0') : null;
                                          @endphp

                                          <strong>Abatimento de Compra</strong>
                                          <div class="small">
                                              @if($numeroNota)
                                                  Nota Fiscal: <span class="badge badge-light-dark font-weight-bold">Nº {{ $numeroNota }}</span>
                                              @else
                                                  Ref: <span class="text-dark">{{ $contaPagar->referencia ?? 'Compra #' . $mov->conta_pagar_id }}</span>
                                              @endif
                                          </div>
                                      @else
                                        Baixa: {{ $tipo == 'cliente' ? 'Venda' : 'Compra' }} #{{ $mov->conta_receber_id ?? $mov->conta_pagar_id }}
                                    @endif
                                </td>
                                <td>-</td>
                                <td class="text-danger">R$ {{ number_format($mov->valor, 2, ',', '.') }}</td>
                                <td class="font-weight-bold">R$ {{ number_format($saldoAcumulado, 2, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal de Edição --}}
<div class="modal fade" id="modalEditar" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" id="formEditar" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Editar Adiantamento</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label>Descrição</label>
                            <input type="text" name="descricao" id="edit_descricao" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Data</label>
                            <input type="date" name="data" id="edit_data" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Valor Total</label>
                            <input type="text" name="valor" id="edit_valor" class="form-control money" required>
                        </div>
                        <div class="col-md-12">
                            <div class="alert alert-warning small">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Alterar o valor atualizará automaticamente o saldo da Conta Empresa vinculada.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
    function editarAdiantamento(obj) {
        $('#edit_descricao').val(obj.descricao);
        $('#edit_data').val(obj.data);
        
        let valor = obj.valor_total.toLocaleString('pt-br', {minimumFractionDigits: 2});
        $('#edit_valor').val(valor);
        
        let url = "{{ route('adiantamentos.update', ':id') }}".replace(':id', obj.id);
        $('#formEditar').attr('action', url);
        
        $('#modalEditar').modal('show');
    }

    $(document).ready(function() {
        if($('.money').length > 0){
            $('.money').mask('#.##0,00', {reverse: true});
        }
    });
</script>
@endsection