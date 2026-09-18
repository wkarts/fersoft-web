@extends('default.layout')
@section('content')
<div class="container">
    <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-6">
            <h3 class="card-title font-weight-bolder text-dark">Recebimento Múltiplo de Contas</h3>
            <div class="card-toolbar">
                <a href="/contasReceber" class="btn btn-light-danger font-weight-bold">
                    <i class="la la-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <form method="post" action="/contasReceber/receberMulti">
            @csrf
            <input type="hidden" value="{{$ids}}" name="ids">
            <input type="hidden" value="{{$somaTotal}}" name="valor_total">

            <div class="card-body">
                <div class="alert alert-custom alert-light-success fade show mb-8" role="alert">
                    <div class="alert-text">
                        <h5>Valor Total a Receber: <strong class="text-success font-size-h3">R$ {{ number_format($somaTotal, 2, ',', '.') }}</strong></h5>
                    </div>
                </div>

                <table class="table table-hover table-bordered mb-8">
                    <thead class="bg-light">
                        <tr>
                            <th>Vencimento</th>
                            <th>Referência</th>
                            <th>Cliente</th>
                            <th>Valor Integral</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contas as $c)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y')}}</td>
                                <td>{{ $c->referencia }}</td>
                                <td>{{ $c->cliente->razao_social ?? 'Não informado' }}</td>
                                <td>R$ {{ number_format($c->valor_integral, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="row p-6 bg-light rounded mb-8">
                    <div class="form-group col-lg-3">
                        <label class="font-weight-bold">Valor Recebido</label>
                        <input readonly type="text" class="form-control money font-weight-bold" name="valor" value="{{number_format($somaTotal, 2, ',', '.')}}">
                    </div>

                    <div class="form-group col-lg-3">
                        <label class="font-weight-bold">Data de Recebimento</label>
                        <div class="input-group date">
                            <input required type="text" name="data_pagamento" class="form-control date-input" value="{{ date('d/m/Y') }}" id="kt_datepicker_3" />
                            <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                        </div>
                    </div>
                </div>

                <div class="row p-4 rounded bg-light-info mb-6" id="container_adiantamento" style="display:none;">
                    <div class="form-group col-lg-12 mb-0">
                        <label class="checkbox checkbox-lg checkbox-info d-flex align-items-center font-weight-bold">
                            <input type="checkbox" name="usar_adiantamento" id="usar_adiantamento" value="1">
                            <span></span>&nbsp;&nbsp;
                            <strong id="label_adiantamento" class="text-info font-size-h6">Usar adiantamento disponível</strong>
                        </label>
                    </div>
                </div>

                <div class="row p-6 bg-light rounded" id="div_financeiro">
                    <div class="form-group col-lg-4">
                        <label class="font-weight-bold">Tipo de Pagamento</label>
                        <select name="tipo_pagamento" class="custom-select form-control">
                            @foreach(App\Models\ContaReceber::tiposPagamento() as $tp)
                                <option value="{{$tp}}">{{$tp}}</option>
                            @endforeach
                        </select>
                    </div>

                    @if(sizeof($contasEmpresa) > 0)
                        <div class="form-group col-lg-5">
                            <label class="text-primary font-weight-bold">Conta Bancária / Caixa (Depósito)</label>
                            <select required name="conta_id" id="conta_id" class="custom-select form-control select2">
                                <option value="">Selecione a conta bancária...</option>
                                @foreach($contasEmpresa as $c)
                                    <option value="{{ $c->id }}">{{ $c->nome }} | Saldo: R$ {{ number_format($c->saldo, 2, ',', '.') }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card-footer text-right">
                <a class="btn btn-light-danger font-weight-bold px-8 mr-2" href="/contasReceber">
                    <i class="la la-close"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success font-weight-bold px-10">
                    <i class="la la-check"></i> PROCESSAR RECEBIMENTO EM LOTE
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    let cliente_id = "{{ $contas[0]->cliente_id ?? '' }}";

    if(cliente_id) {
        $.get('/adiantamentos/consulta-saldo/cliente/' + cliente_id, function(res) {
            if(res && res.saldo > 0) {
                let saldoFormatado = res.saldo.toFixed(2).replace('.', ',');
                $('#label_adiantamento').text('Usar adiantamento disponível (Saldo: R$ ' + saldoFormatado + ')');
                $('#container_adiantamento').fadeIn();
            }
        });
    }

    $('#usar_adiantamento').change(function() {
        if($(this).is(':checked')) {
            $('#div_financeiro').fadeOut();
            $('#conta_id').prop('required', false);
        } else {
            $('#div_financeiro').fadeIn();
            $('#conta_id').prop('required', true);
        }
    });
});
</script>
@endsection