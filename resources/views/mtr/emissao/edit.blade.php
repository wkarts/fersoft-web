@extends('default.layout')
@section('content')
<div class="container-fluid">
<form method="POST" action="{{ route('mtr.emissao.update',$mtr->id) }}">@csrf
<div class="card card-custom"><div class="card-header"><h3 class="card-title">Editar Rascunho MTR #{{ $mtr->id }}</h3></div><div class="card-body">
@if(session('erro'))<div class="alert alert-danger">{{ session('erro') }}</div>@endif
@include('mtr.emissao.form')
</div></div>
</form>
</div>
@endsection
