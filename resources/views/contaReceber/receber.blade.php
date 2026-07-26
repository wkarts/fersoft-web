@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="card card-custom gutter-b example example-compact">
        <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
            <div class="col-lg-12">
                <br>
                <form method="post" action="/contasReceber/receber">
                    @csrf
                    <input type="hidden" name="id" value="{{$conta->id}}">

                    <div class="card card-custom gutter-b example example-compact">
                        <div class="card-header">
                            <h3 class="card-title">Receber Conta - Ref: {{ $conta->referencia }}</h3>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-12">
                            <div class="row">
                                <div class="col-lg-12">
                                    <h5>Cliente: <strong>{{ $conta->cliente->razao_social ?? 'Não informado' }}</strong></h5>
                                    <h5>Vencimento: <strong class="text-danger">{{ \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y') }}</strong></h5>
                                </div>
                            </div>
                            <hr>

                            <div class="row">
                                <div class="form-group col-lg-2">
                                    <label>Valor Integral</label>
                                    <input type="text" id="valor_integral" readonly class="form-control money" value="{{ number_format($conta->valor_integral, 2, ',', '.') }}">
                                </div>

                                <div class="form-group col-lg-2">
                                    <label>Juros (+)</label>
                                    <input type="text" name="juros" id="juros" class="form-control money calc" value="0,00">
                                </div>

                                <div class="form-group col-lg-2">
                                    <label>Multa (+)</label>
                                    <input type="text" name="multa" id="multa" class="form-control money calc" value="0,00">
                                </div>

                                <div class="form-group col-lg-2">
                                    <label>Desconto (-)</label>
                                    <input type="text" name="desconto" id="desconto" class="form-control money calc" value="0,00">
                                </div>

                                <div class="form-group col-lg-3">
                                    <label class="text-success font-weight-bold">Valor Recebido (=)</label>
                                    <input type="text" name="valor_recebido" id="valor_recebido" class="form-control money" value="{{ number_format($conta->valor_integral, 2, ',', '.') }}">
                                </div>
                            </div>

                            <div class="row" id="container_adiantamento" style="display: none;">
                                <div class="form-group col-lg-8">
                                    <label class="checkbox checkbox-lg">
                                        <input type="checkbox" name="usar_adiantamento" id="usar_adiantamento" value="1">
                                        <span></span>&nbsp;&nbsp;
                                        <strong id="label_adiantamento" class="text-info">Usar saldo de adiantamento do cliente</strong>
                                    </label>
                                </div>
                            </div>

                            <div class="row" id="div_financeiro">
                                <div class="form-group col-lg-3">
                                    <label>Data de Recebimento</label>
                                    <input type="text" name="data_pagamento" class="form-control date-input" id="kt_datepicker_3" value="{{ date('d/m/Y') }}">
                                </div>

                                <div class="form-group col-lg-3">
                                    <label>Tipo de Pagamento</label>
                                    <select name="tipo_pagamento" id="forma" class="custom-select form-control">
                                        @foreach(App\Models\ContaReceber::tiposPagamento() as $tp)
                                            <option value="{{$tp}}">{{$tp}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-lg-4">
                                    <label class="text-primary font-weight-bold">Conta para Depósito</label>
                                    <select required name="conta_id" id="conta_id" class="custom-select form-control">
                                        <option value="">Selecione a conta bancária</option>
                                        @foreach($contasEmpresa as $c)
                                            <option value="{{$c->id}}">{{$c->nome}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-lg-3">
                                <a style="width: 100%" class="btn btn-danger" href="/contasReceber">Cancelar</a>
                            </div>
                            <div class="col-lg-3">
                                <button style="width: 100%" type="submit" class="btn btn-success">Receber</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
    $(function () {
        const clienteId = @json($conta->cliente_id);
        if (clienteId) {
            $.get('/adiantamentos/consulta-saldo/cliente/' + clienteId).done(function (res) {
                const saldo = Number(res.saldo || 0);
                if (saldo > 0) {
                    $('#label_adiantamento').text(
                        'Usar saldo de adiantamento do cliente (disponível: R$ ' +
                        saldo.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ')'
                    );
                    $('#container_adiantamento').show();
                }
            });
        }
        $('#usar_adiantamento').on('change', function () {
            const usar = $(this).is(':checked');
            $('#forma, #conta_id').prop('required', !usar);
            $('#forma, #conta_id').closest('.form-group').toggle(!usar);
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

    // Dispara o cálculo sempre que digitar nos campos de ajuste
    $('.calc').keyup(function() {
        calcularTotal();
    });
</script>
@endsection