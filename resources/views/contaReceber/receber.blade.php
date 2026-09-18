@extends('default.layout')
@section('content')
<div class="d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="card card-custom gutter-b example example-compact">
        <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
            <div class="col-lg-12">
                <br>
                <form method="post" action="/contasReceber/receber">
                    @csrf
                    <input type="hidden" name="id" value="{{$conta->id}}">

                    <div class="card card-custom gutter-b example example-compact">
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title font-weight-bolder text-dark">
                                Receber Conta — Ref: <span class="text-primary ml-2">{{ $conta->referencia }}</span>
                            </h3>
                            <div class="card-toolbar">
                                <a href="/contasReceber" class="btn btn-light-danger font-weight-bold">
                                    <i class="la la-arrow-left"></i> Voltar
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- RESUMO DO CLIENTE E VENCIMENTO --}}
                    <div class="alert alert-custom alert-light-primary fade show mb-8" role="alert">
                        <div class="alert-icon"><i class="flaticon-user text-primary"></i></div>
                        <div class="alert-text font-size-h6">
                            Cliente: <strong>{{ $conta->cliente->razao_social ?? 'Não informado' }}</strong><br>
                            Vencimento: <strong class="text-danger">{{ \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y') }}</strong>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        {{-- BLOCO DE VALORES E CÁLCULO --}}
                        <div class="row bg-light p-6 rounded mb-8">
                            <div class="form-group col-lg-2">
                                <label class="font-weight-bold">Valor Integral</label>
                                <input type="text" id="valor_integral" readonly class="form-control money font-weight-bold" value="{{ number_format($conta->valor_integral, 2, ',', '.') }}">
                            </div>

                            <div class="form-group col-lg-2">
                                <label class="font-weight-bold text-danger">Juros (+)</label>
                                <input type="text" name="juros" id="juros" class="form-control money calc" value="0,00">
                            </div>

                            <div class="form-group col-lg-2">
                                <label class="font-weight-bold text-danger">Multa (+)</label>
                                <input type="text" name="multa" id="multa" class="form-control money calc" value="0,00">
                            </div>

                            <div class="form-group col-lg-2">
                                <label class="font-weight-bold text-success">Desconto (-)</label>
                                <input type="text" name="desconto" id="desconto" class="form-control money calc" value="0,00">
                            </div>

                            <div class="form-group col-lg-4">
                                <label class="text-success font-weight-bolder font-size-h6">Valor Final a Receber (=)</label>
                                <input type="text" name="valor_recebido" id="valor_recebido" class="form-control money font-weight-bolder font-size-h5 text-success" value="{{ number_format($conta->valor_integral, 2, ',', '.') }}">
                            </div>
                        </div>

                        {{-- DATA DE RECEBIMENTO --}}
                        <div class="row mb-4">
                            <div class="form-group col-lg-3">
                                <label class="font-weight-bold">Data de Recebimento</label>
                                <div class="input-group date">
                                    <input type="text" name="data_pagamento" class="form-control date-input" id="kt_datepicker_3" value="{{ date('d/m/Y') }}">
                                    <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                                </div>
                            </div>
                        </div>

                        {{-- SEÇÃO DE ADIANTAMENTO DE CLIENTE --}}
                        <div class="row p-4 rounded bg-light-info mb-6" id="container_adiantamento" style="display:none;">
                            <div class="form-group col-lg-12 mb-0">
                                <label class="checkbox checkbox-lg checkbox-info d-flex align-items-center font-weight-bold">
                                    <input type="checkbox" name="usar_adiantamento" id="usar_adiantamento" value="1">
                                    <span></span>&nbsp;&nbsp;
                                    <strong id="label_adiantamento" class="text-info font-size-h6">Usar adiantamento disponível</strong>
                                </label>
                            </div>
                        </div>

                        {{-- FORMA DE PAGAMENTO E DESTINO BANCÁRIO --}}
                        <div class="row p-6 bg-light rounded mb-8" id="div_financeiro">
                            <div class="form-group col-lg-4">
                                <label class="font-weight-bold">Tipo de Pagamento</label>
                                <select name="tipo_pagamento" class="custom-select form-control">
                                    @foreach(App\Models\ContaReceber::tiposPagamento() as $tp)
                                        <option value="{{$tp}}">{{$tp}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-lg-5">
                                {{-- Linha CORRIGIDA abaixo: removida a classe text-primary --}}
                                <label class="font-weight-bold">Conta Bancária / Caixa (Depósito)</label>
                                {{-- Linha CORRIGIDA abaixo: substituição de classes por form-control --}}
                                <select required name="conta_id" id="conta_id" class="form-control">
                                    <option value="">Selecione a conta para depósito...</option>
                                    @foreach($contasEmpresa as $c)
                                        <option value="{{$c->id}}">{{$c->nome}} | Saldo: R$ {{ number_format($c->saldo, 2, ',', '.') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-right">
                        <a class="btn btn-light-danger font-weight-bold px-8 mr-2" href="/contasReceber">
                            <i class="la la-close"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success font-weight-bold px-10">
                            <i class="la la-check"></i> CONFIRMAR RECEBIMENTO
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    let cliente_id = "{{ $conta->cliente_id }}";

    if(cliente_id) {
        $.get('/adiantamentos/consulta-saldo/cliente/' + cliente_id, function(res) {
            if(res && res.saldo > 0) {
                let saldoFormatado = res.saldo.toFixed(2).replace('.', ',');
                $('#label_adiantamento').text('Usar adiantamento disponível do cliente (Saldo: R$ ' + saldoFormatado + ')');
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

function parseMoeda(valor) {
    if (!valor) return 0;
    let limpo = valor.replace(/\./g, '').replace(',', '.');
    return parseFloat(limpo) || 0;
}

function formatarMoeda(valor) {
    return valor.toLocaleString('pt-br', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function calcularTotal() {
    let integral = parseMoeda($('#valor_integral').val());
    let juros    = parseMoeda($('#juros').val());
    let multa    = parseMoeda($('#multa').val());
    let desconto = parseMoeda($('#desconto').val());

    let total = (integral + juros + multa) - desconto;
    if(total < 0) total = 0;

    $('#valor_recebido').val(formatarMoeda(total));
}

$('.calc').keyup(function() {
    calcularTotal();
});
</script>
@endsection