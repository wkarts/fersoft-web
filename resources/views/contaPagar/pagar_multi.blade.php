@extends('default.layout')
@section('content')
<div class="container">
    <div class="card card-custom gutter-b">
        <div class="card-header border-0 pt-6">
            <h3 class="card-title font-weight-bolder text-dark">Pagamento Múltiplo de Contas</h3>
            <div class="card-toolbar">
                <a href="/contasPagar" class="btn btn-light-danger"><i class="la la-arrow-left"></i> Voltar</a>
            </div>
        </div>

        <form method="post" action="/contasPagar/pagar-multi" id="form-pagar-multi">
            @csrf
            <div class="card-body">
                <div class="alert alert-custom alert-light-primary fade show mb-8" role="alert">
                    <div class="alert-text">
                        <h5>Valor Total a Pagar: <strong class="text-primary font-size-h3">R$ {{ number_format($somaTotal, 2, ',', '.') }}</strong></h5>
                    </div>
                </div>

                <table class="table table-hover table-bordered mb-8">
                    <thead class="bg-light">
                        <tr><th>Vencimento</th><th>Referência</th><th>Valor Integral</th></tr>
                    </thead>
                    <tbody>
                        @foreach($contas as $c)
                        <input type="hidden" name="conta_pagar_id[]" value="{{ $c->id }}">
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y')}}</td>
                            <td>{{ $c->referencia }}</td>
                            <td>R$ {{ number_format($c->valor_integral, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="row mb-8 p-6 bg-light rounded">
                    <div class="col-lg-4">
                        <label class="font-weight-bold">Modo de Baixa no Extrato</label>
                        <select name="tipo_lancamento" class="form-control custom-select">
                            <option value="analitico">Analítico (1 lançamento por nota)</option>
                            <option value="sintetico">Sintético (1 lançamento total)</option>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="font-weight-bold">Opções</label>
                        <div class="checkbox-inline mt-3">
                            <label class="checkbox checkbox-lg">
                                <input type="checkbox" id="check_adiantamento" name="usar_adiantamento"> 
                                <span></span> Abater Saldo de Adiantamento
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row div-banco-e-pagamento">
                    <div class="form-group col-lg-3">
                        <label>Data de Pagamento</label>
                        <input required type="text" name="data_pagamento" class="form-control date-input" value="{{ date('d/m/Y') }}">
                    </div>
                    <div class="form-group col-lg-3">
                        <label>Tipo de Pagamento</label>
                        <select required class="custom-select form-control" name="tipo_pagamento">
                            @foreach(App\Models\ContaPagar::tiposPagamento() as $c)
                            <option value="{{$c}}">{{$c}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-lg-4">
                        <label>Conta Bancária</label>
                        <select required name="conta_id" class="select2-custom custom-select">
                            @foreach($contasEmpresa as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-success font-weight-bold px-10">
                    <i class="la la-check"></i> PROCESSAR PAGAMENTO
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        $('#check_adiantamento').change(function() {
            if($(this).is(':checked')) {
                $('.div-banco-e-pagamento').fadeOut();
            } else {
                $('.div-banco-e-pagamento').fadeIn();
            }
        });
        
        $('.date-input').mask('00/00/0000');
    });
</script>
@endsection