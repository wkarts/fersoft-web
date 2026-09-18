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

        <form method="POST" action="/nfse-tomadas/salvarImportacao/{{ $doc->id }}" id="form-importar-nfse">
    @csrf
    <div class="card card-custom gutter-b mt-5">
        <div class="card-header">
            <h3 class="card-title font-weight-bold text-dark">Financeiro da Nota de Serviço</h3>
        </div>
        <div class="card-body">
            </div>
    </div>

    <div class="row mt-5">
        <div class="col-md-6">
            <label>Categoria de Conta *</label>
            <select name="categoria_conta_id" class="custom-select form-control" required>
                @foreach($categoriasConta as $c)
                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label>Veículo Geral</label>
            <select name="veiculo_id" class="custom-select form-control">
                <option value="">-- Selecione --</option>
                @foreach($veiculos as $v)
                    <option value="{{ $v->id }}">{{ $v->placa }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="text-right mt-5">
        <button type="submit" class="btn btn-success font-weight-bold" id="btn-salvar">
            <i class="la la-check"></i> Salvar como Compra de Serviço
        </button>
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