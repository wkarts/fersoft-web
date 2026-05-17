@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <h3>{{ $title }}</h3>
        <div class="alert alert-info mt-3">{{ $message }}</div>
        <a href="/seguranca" class="btn btn-secondary">Voltar ao painel</a>
    </div>
</div>
@endsection
