@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <h3>{{ $produto->id ? 'Editar' : 'Novo' }} registro de exemplo</h3>
        <p class="text-muted">Modelo para controller que não herda BaseController.</p>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="post" action="{{ $produto->id ? '/exemplo-seguranca-produtos/update/' . $produto->id : '/exemplo-seguranca-produtos/store' }}">
            @csrf
            @if($produto->id)
                @method('PUT')
            @endif

            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="nome" class="form-control" value="{{ old('nome', $produto->nome) }}" required>
            </div>

            <div class="form-group">
                <label>Valor de venda</label>
                <input type="text" name="valor_venda" class="form-control" value="{{ old('valor_venda', $produto->valor_venda) }}">
            </div>

            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="/exemplo-seguranca-produtos" class="btn btn-secondary">Voltar</a>
        </form>
    </div>
</div>
@endsection
