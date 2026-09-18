@extends('default.layout')
@section('content')
<div class="container mt-5">
    <form method="post" action="/contasPagar/pagar" id="form-baixa">
        @csrf
        <input type="hidden" name="id" value="{{$conta->id}}">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex align-items-center">
                <i class="la la-money-bill-wave icon-lg mr-2"></i>
                <h4 class="card-title my-2">Baixa de Conta Financeira</h4>
            </div>
            
            <div class="card-body">
                <div class="alert alert-custom alert-light-secondary border border-secondary mb-8 p-5">
                    <div class="row text-center">
                        <div class="col-md-4 border-right">
                            <span class="text-muted d-block">Fornecedor</span>
                            <strong class="font-size-lg">{{$conta->fornecedor ? $conta->fornecedor->razao_social : 'N/A'}}</strong>
                        </div>
                        <div class="col-md-4 border-right">
                            <span class="text-muted d-block">Referência / Nota</span>
                            <strong class="font-size-lg">{{ $conta->referencia }} / {{ ltrim($conta->numero_nota_fiscal, '0') }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block">Vencimento</span>
                            <strong class="font-size-lg text-danger">{{ \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y')}}</strong>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-3">
                        <label>Valor Integral (R$)</label>
                        <input type="text" class="form-control bg-light" value="{{ number_format($conta->valor_integral, 2, ',', '.') }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label>Juros (+)</label>
                        <input type="text" name="juros" class="form-control money" value="0,00">
                    </div>
                    <div class="col-md-3">
                        <label>Multa (+)</label>
                        <input type="text" name="multa" class="form-control money" value="0,00">
                    </div>
                    <div class="col-md-3">
                        <label>Desconto (-)</label>
                        <input type="text" name="desconto" class="form-control money" value="0,00">
                    </div>
                </div>

                <div class="row mt-6 mb-4">
                    <div class="col-12 text-right">
                        <div class="p-4 bg-success text-white rounded d-inline-block">
                            <h3 class="mb-0">Total a Pagar: R$ <span id="total_exibir">{{ number_format($conta->valor_integral, 2, ',', '.') }}</span></h3>
                            <input type="hidden" name="valor" id="total_input" value="{{ number_format($conta->valor_integral, 2, ',', '') }}">
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-light-primary border-left border-primary border-4 rounded mb-6">
                <div class="checkbox-inline">
                    <label class="checkbox checkbox-lg">
                        <input type="checkbox" name="usar_adiantamento" id="check_adiantamento">
                        <span class="mr-2"></span> Usar saldo de adiantamento deste fornecedor
                    </label>
                </div>
                <div id="info_adiantamento" class="mt-3 font-weight-bold text-primary" style="display:none;"></div>
            </div>

            <div class="row">
                <div class="form-group col-md-4">
                    <label>Data de Pagamento</label>
                    <div class="input-group">
                        <input type="date" name="data_pagamento" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <small class="text-muted">Selecione a data no calendário</small>
                </div>
            </div>

            <div class="row div-banco-e-pagamento">
                <div class="col-md-4">
                    <label>Forma de Pagamento</label>
                    <select name="tipo_pagamento" class="form-control">
                        @foreach(App\Models\ContaPagar::tiposPagamento() as $c)
                            <option value="{{$c}}">{{$c}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Conta Bancária</label>
                    <select name="conta_id" class="form-control">
                        @foreach($contasEmpresa as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="card-footer text-right">
                <a href="/contasPagar" class="btn btn-secondary mr-2">Cancelar</a>
                <button type="submit" class="btn btn-primary font-weight-bold">Confirmar Pagamento</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        $('.money').mask('000.000.000,00', {reverse: true});
        $('.date-input').mask('00/00/0000');

        $('#check_adiantamento').change(function() {
    if($(this).is(':checked')) {
        $('.div-banco-e-pagamento').fadeOut();
        $('#info_adiantamento').html('Calculando saldo...').show();
        
        let fornecedor_id = "{{ $conta->fornecedor_id }}"; 
        // Chamando a sua rota existente. 
        // Passamos 'fornecedor' como tipo e o ID do fornecedor.
        $.get('/adiantamentos/consulta-saldo/fornecedor/' + fornecedor_id, function(data) {
            // Ajuste o nome 'saldo' conforme o que o seu Controller retornar
            $('#info_adiantamento').html('Saldo Disponível: <strong>R$ ' + data.saldo + '</strong>');
        });
    } else {
        $('.div-banco-e-pagamento').fadeIn();
        $('#info_adiantamento').hide();
    }
});

        // Cálculo dinâmico
        $('.money').on('keyup', function() {
            let valorBase = parseFloat("{{ $conta->valor_integral }}");
            let juros = parseMoeda($('input[name="juros"]').val());
            let multa = parseMoeda($('input[name="multa"]').val());
            let desconto = parseMoeda($('input[name="desconto"]').val());
            
            let total = valorBase + juros + multa - desconto;
            
            $('#total_exibir').text(total.toLocaleString('pt-br', {minimumFractionDigits: 2}));
            $('#total_input').val(total.toFixed(2).replace('.', ','));
        });

        function parseMoeda(v) {
            return parseFloat(v.replace(/\./g, '').replace(',', '.'));
        }
    });
</script>
@endsection