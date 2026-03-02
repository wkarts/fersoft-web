@extends('default.layout')
@section('content')
<div class="card card-custom">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
    </div>
    <div class="card-body">
        <form method="post" action="{{ isset($item) ? '/ponto/relogios/update/'.$item->id : '/ponto/relogios/save' }}">
            @csrf
            @if(isset($item)) @method('PUT') @endif
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Nome</label>
                    <input class="form-control" name="nome" value="{{ $item->nome ?? old('nome') }}" required>
                </div>
                <div class="col-md-4 form-group">
                    <label>Fabricante</label>
                    <input class="form-control" name="fabricante" value="{{ $item->fabricante ?? old('fabricante') }}">
                </div>
                <div class="col-md-4 form-group">
                    <label>Modelo</label>
                    <input class="form-control" name="modelo" value="{{ $item->modelo ?? old('modelo') }}">
                </div>
                <div class="col-md-4 form-group">
                    <label>Número de Série</label>
                    <input class="form-control" name="numero_serie" value="{{ $item->numero_serie ?? old('numero_serie') }}">
                </div>
                <div class="col-md-4 form-group">
                    <label>Local</label>
                    <input class="form-control" name="local" value="{{ $item->local ?? old('local') }}">
                </div>
                <div class="col-md-4 form-group">
                    <label>Tipo Origem</label>
                    <select class="form-control" name="tipo_origem">
                        <option value="REP" @selected(($item->tipo_origem ?? old('tipo_origem')) == 'REP')>REP</option>
                        <option value="APP" @selected(($item->tipo_origem ?? old('tipo_origem')) == 'APP')>APP</option>
                        <option value="API" @selected(($item->tipo_origem ?? old('tipo_origem')) == 'API')>API</option>
                    </select>
                </div>
                <div class="col-md-12 form-group">
                    <label>Observações</label>
                    <textarea class="form-control" name="observacoes">{{ $item->observacoes ?? old('observacoes') }}</textarea>
                </div>
                <div class="col-md-3 form-group">
                    <label><input type="checkbox" name="ativo" value="1" @checked(($item->ativo ?? 1) == 1)> Ativo</label>
                </div>
            </div>
            <button class="btn btn-success">Salvar</button>
            <a href="/ponto/relogios" class="btn btn-light">Voltar</a>
        </form>
    </div>
</div>
@endsection
