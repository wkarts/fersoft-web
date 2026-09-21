@extends('default.layout')
@section('content')
<div class="container-fluid">
<form method="POST" action="{{ route('mtr.emissao.store') }}">@csrf
<div class="card card-custom"><div class="card-header"><h3 class="card-title">Emissão de MTR</h3></div><div class="card-body">
@if(session('sucesso'))<div class="alert alert-success">{{ session('sucesso') }}</div>@endif
@if(session('erro'))<div class="alert alert-danger">{{ session('erro') }}</div>@endif
@include('mtr.emissao.form')
</div></div>
</form>
</div>
@endsection
