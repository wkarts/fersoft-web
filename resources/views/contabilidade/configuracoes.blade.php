@extends('default/menu_'.$tipoMenu)

@section('content')
<div class="container-fluid mt-4">
    <h3>{{ $title }}</h3>

    <form action="{{ route('contabilidade.configuracoes.save') }}" method="POST">
        @csrf
        <div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <i class="fa fa-cogs"></i> <strong>Identificação Prosoft e Estoque (Matriz)</strong>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label>Código Empresa (Prosoft)</label>
                <input type="text" name="codigo_prosoft" class="form-control" placeholder="0160" maxlength="4"
                       value="{{ isset($config->codigo_prosoft) ? $config->codigo_prosoft : '' }}">
                <small class="text-muted">Ex: 0160 (4 dígitos).</small>
            </div>

            <div class="col-md-9 mb-3">
                <label>Conta Contábil de Estoque (Padrão)</label>
                <select name="conta_estoque" class="form-control select2">
                    <option value="">Selecione a conta de estoque...</option>
                    @foreach($planoContas as $c)
                        <option value="{{$c->id}}" {{ isset($config->conta_estoque) && $config->conta_estoque == $c->id ? 'selected' : '' }}>
                            {{$c->classificador}} - {{$c->nome}}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Utilizada para lançamentos de estoque inicial e final.</small>
            </div>
        </div>
    </div>
</div>
        <div class="card mb-3">
            <div class="card-header bg-light"><strong>Configurações de Impostos</strong></div>
            <div class="card-body">
                <small class="text-muted d-block mb-3">
                    <i class="fa fa-info-circle"></i> <strong>Orientação:</strong> Informe contas de <strong>Passivo Circulante (a recolher)</strong>, conforme mapeado pelo seu contador para o SPED.
                </small>
                <div class="row">
                    @php
                        $impostos = [
                            'conta_irrf_recolher' => 'IRRF', 'conta_iss_recolher' => 'ISS', 
                            'conta_pis_recolher' => 'PIS', 'conta_cofins_recolher' => 'COFINS',
                            'conta_csll_recolher' => 'CSLL', 'conta_inss_recolher' => 'INSS'
                        ];
                    @endphp
                    @foreach($impostos as $col => $label)
                        <div class="col-md-4 mb-3">
                            <label>Conta {{ $label }}</label>
                            <select name="{{ $col }}" class="form-control select2">
                                <option value="">Selecione...</option>
                                @foreach($planoContas as $c)
                                    <option value="{{$c->id}}" {{ isset($config->$col) && $config->$col == $c->id ? 'selected' : '' }}>
                                        {{$c->classificador}} - {{$c->nome}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light"><strong>Configurações Financeiras</strong></div>
            <div class="card-body">
                <small class="text-muted d-block mb-3">
                    <i class="fa fa-info-circle"></i> <strong>Orientação:</strong> Informe contas de <strong>Resultado (Despesas ou Receitas Financeiras)</strong> para juros, multas e descontos.
                </small>
                <div class="row">
                    @php
                        $financeiro = [
                            'conta_juros_pagos' => 'Juros Pagos', 
                            'conta_multas_pagas' => 'Multa Paga', 
                            'conta_descontos_obtidos' => 'Descontos Obtidos',
                            'conta_juros_recebidos' => 'Juros Recebidos', 
                            'conta_multas_recebidas' => 'Multa Recebida', 
                            'conta_descontos_concedidos' => 'Descontos Concedidos'
                        ];
                    @endphp
                    @foreach($financeiro as $col => $label)
                        <div class="col-md-4 mb-3">
                            <label>{{ $label }}</label>
                            <select name="{{ $col }}" class="form-control select2">
                                <option value="">Selecione...</option>
                                @foreach($planoContas as $c)
                                    <option value="{{$c->id}}" {{ isset($config->$col) && $config->$col == $c->id ? 'selected' : '' }}>
                                        {{$c->classificador}} - {{$c->nome}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light"><strong>Contas Padrão</strong></div>
            <div class="card-body">
                <small class="text-muted d-block mb-3">
                    <i class="fa fa-info-circle"></i> <strong>Orientação:</strong> Informe as contas coletivas de <strong>Ativo (Clientes)</strong> ou <strong>Passivo (Fornecedores)</strong>.
                </small>
                <div class="row">
                    @php
                        $padroes = [
                            'conta_cliente_padrao' => 'Conta Padrão Clientes',
                            'conta_fornecedor_padrao' => 'Conta Padrão Fornecedores'
                        ];
                    @endphp
                    @foreach($padroes as $col => $label)
                        <div class="col-md-4 mb-3">
                            <label>{{ $label }}</label>
                            <select name="{{ $col }}" class="form-control select2">
                                <option value="">Selecione...</option>
                                @foreach($planoContas as $c)
                                    <option value="{{$c->id}}" {{ isset($config->$col) && $config->$col == $c->id ? 'selected' : '' }}>
                                        {{$c->classificador}} - {{$c->nome}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success btn-lg mb-4">Salvar todas as configurações</button>
    </form>
</div>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endsection