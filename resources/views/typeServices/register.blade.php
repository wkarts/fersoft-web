@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-catalog-config.css') }}?v={{ filemtime(public_path('css/fer-catalog-config.css')) }}">
@endpush
@section('content')
<div class="fer-catalog-config fer-catalog-config--form fer-catalog-config--typeServices">
    <div class="card card-custom">
        <div class="card-header"><h4 class="card-title">{{{ isset($type) ? "Editar": "Cadastrar" }}} Tipo de Serviço</h4></div>
        <form method="post" action="{{{ isset($type) ? '/tipoServico/update': '/tipoServico/save' }}}">
            <div class="card-body">
                <input type="hidden" name="id" value="{{{ isset($type->id) ? $type->id : 0 }}}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="row">
                    <div class="form-group col-sm-12 col-md-8 col-lg-6">
                        <label for="name">Nome</label>
                        <input value="{{{ isset($type->name) ? $type->name : old('name') }}}" id="name" name="name" type="text" class="form-control validate @if($errors->has('name')) is-invalid @endif">
                        @if($errors->has('name'))
                        <div class="invalid-feedback d-block">{{ $errors->first('name') }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end flex-wrap" style="gap: 8px">
                <a class="btn btn-danger" href="/tipoServico">Cancelar</a>
                <input type="submit" value="Salvar" class="btn btn-success">
            </div>
        </form>
    </div>
</div>
@endsection
