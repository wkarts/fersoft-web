@extends('default.layout')
@section('content')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Tabela de Preços para NF-e (Pauta Fiscal)</h2>

    @if(session('sucesso'))
        <div class="alert alert-success">
            {{ session('sucesso') }}
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Cadastrar / Atualizar Preço</div>
        <div class="card-body">
            <form action="{{ url('/tabelaPrecoNfe/save') }}" method="POST" class="row">
                @csrf
                <div class="col-md-6">
                    <label>Produto</label>
                    <select name="produto_id" class="form-control" required>
                        <option value="">Selecione o Produto da Sucata...</option>
                        @foreach($produtos as $produto)
                            <option value="{{ $produto->id }}">{{ $produto->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Preço Base NFe (R$)</label>
                    <input type="text" name="preco_nfe" class="form-control" placeholder="Ex: 12,90" required>
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Salvar Preço</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Preços Configurados</div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover m-0">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço Base NF-e (R$)</th>
                        <th width="100">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($precos_nfe) && count($precos_nfe) > 0)
                        @foreach($precos_nfe as $preco)
                            <tr>
                                <td>{{ $preco->produto_nome }}</td>
                                <td>R$ {{ number_format($preco->preco_nfe, 2, ',', '.') }}</td>
                                <td>
                                    <a href="{{ url('/tabelaPrecoNfe/delete/'.$preco->id) }}" class="btn btn-danger btn-sm" onclick="return confirm('Excluir este preço?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="3" class="text-center py-3">Nenhum preço configurado ainda.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection