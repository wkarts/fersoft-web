@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Detalhes da NFS-e e Importação</h3>
    </div>
    <div class="card-body">

        @if(session('mensagem_erro'))
            <div class="alert alert-custom alert-light-danger fade show mb-5" role="alert">
                <div class="alert-icon"><i class="flaticon-warning"></i></div>
                <div class="alert-text">{!! session('mensagem_erro') !!}</div>
            </div>
        @endif

        <div class="row mb-8">
            <div class="col-md-6">
                <h4 class="text-dark">Prestador: <strong class="text-primary">{{ $doc->prestador_nome }}</strong></h4>
                <h5>CNPJ/CPF: <strong>{{ $doc->prestador_cnpj_cpf }}</strong></h5>
            </div>
            <div class="col-md-6 text-right">
                <h4 class="text-dark">Nº da Nota: <strong class="text-danger">{{ $doc->numero_nota }}</strong></h4>
                <h5>Chave: <small>{{ $doc->chave }}</small></h5>
                <h5>Emissão: <strong>{{ \Carbon\Carbon::parse($doc->data_emissao)->format('d/m/Y H:i') }}</strong></h5>
            </div>
        </div>

        <div class="row mb-8">
            <div class="col-12">
                <div class="bg-light p-4 rounded text-center">
                    <h3 class="m-0">Valor do Serviço: <span class="text-success">R$ {{ number_format($doc->valor_servico, 2, ',', '.') }}</span></h3>
                    <h5 class="m-0 mt-2 text-muted">Valor Líquido (A Pagar): R$ {{ number_format($doc->valor_liquido, 2, ',', '.') }}</h5>
                </div>
            </div>
        </div>

        <hr>

        <form method="POST" action="/nfse-tomadas/salvar" id="form-importar-nfse">
            @csrf
            <input type="hidden" name="manifesto_id" value="{{ $doc->id }}">

            <h4 class="mb-5 mt-5">Dados para o Financeiro</h4>
            <div class="row align-items-end">
                <div class="col-lg-3">
                    <label>Vincular Veículo (Opcional)</label>
                    <select name="veiculo_id" class="form-control custom-select">
                        <option value="">Selecione...</option>
                        @foreach($veiculos as $v)
                            <option value="{{ $v->id }}">{{ $v->placa }} - {{ $v->modelo }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-lg-3">
                    <label>Categoria de Conta <strong class="text-danger">*</strong></label>
                    <select name="categoria_id" class="form-control custom-select" required>
                        <option value="">Selecione...</option>
                        @foreach($categoriasConta as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3">
                    <label>Forma de Pagamento <strong class="text-danger">*</strong></label>
                    <select name="tipo_pagamento" class="form-control custom-select" required>
                        <option value="">Selecione...</option>
                        @foreach($tiposPagamento as $key => $t)
                            <option value="{{ $key }}">{{ $key }} - {{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3">
                    <label>Vencimento do Contas a Pagar</label>
                    <input type="date" name="data_vencimento" class="form-control" value="{{ \Carbon\Carbon::parse($doc->data_emissao)->format('Y-m-d') }}" required>
                </div>
            </div>

            <div class="row mt-8">
                <div class="col-12 text-right">
                    <a href="/nfse-tomadas" class="btn btn-light-danger font-weight-bold mr-2">
                        <i class="la la-arrow-left"></i> Voltar
                    </a>

                    @if($doc->compra_servico_id == 0)
                    <button type="submit" class="btn btn-success font-weight-bold" id="btn-salvar">
                        <i class="la la-check"></i> Salvar como Compra
                    </button>
                    @else
                    <button type="button" class="btn btn-secondary font-weight-bold" disabled>
                        Já Importado
                    </button>
                    @endif
                </div>
            </div>
        </form>

    </div>
</div>

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        // Previne duplo clique ao salvar
        $('#form-importar-nfse').submit(function() {
            let btn = $('#btn-salvar');
            btn.attr('disabled', 'disabled');
            btn.addClass('spinner spinner-white spinner-right');
        });
    });
</script>
@endsection

@endsection