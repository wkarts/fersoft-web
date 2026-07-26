@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">{{ $title ?? 'Tabela de Preços para NF-e (Pauta Fiscal)' }}</h2>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Cadastrar ou atualizar preço</div>
        <div class="card-body">
            <form action="{{ route('tabela-preco-nfe.save') }}" method="POST">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-5 mb-3">
                        <label>Produto</label>
                        <select name="produto_id" class="form-control select2" required>
                            <option value="">Selecione o produto...</option>
                            @foreach($produtos as $produto)
                                <option value="{{ $produto->id }}" {{ old('produto_id') == $produto->id ? 'selected' : '' }}>{{ $produto->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Unidade</label>
                        <select name="filial_id" class="form-control">
                            <option value="">Matriz / preço geral</option>
                            @foreach($filiais ?? [] as $filial)
                                <option value="{{ $filial->id }}" {{ old('filial_id') == $filial->id ? 'selected' : '' }}>{{ $filial->descricao }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label>Preço NF-e (R$)</label>
                        <input type="text" name="preco_nfe" class="form-control money" value="{{ old('preco_nfe') }}" placeholder="0,00" required>
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Salvar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Preços configurados</div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead><tr><th>Produto</th><th>Unidade</th><th class="text-right">Preço NF-e</th><th width="100">Ações</th></tr></thead>
                <tbody>
                @forelse($precos_nfe as $preco)
                    <tr>
                        <td>{{ $preco->produto_nome }}</td>
                        <td>{{ $preco->filial_nome ?? 'Matriz / geral' }}</td>
                        <td class="text-right">R$ {{ number_format((float)$preco->preco_nfe, 2, ',', '.') }}</td>
                        <td>
                            <form action="{{ route('tabela-preco-nfe.delete', $preco->id) }}" method="POST" onsubmit="return confirm('Excluir este preço?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-4">Nenhum preço configurado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>$(function(){ if ($.fn.select2) $('.select2').select2(); });</script>
@endsection
