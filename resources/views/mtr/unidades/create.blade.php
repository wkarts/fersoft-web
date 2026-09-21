@extends('default.layout')
@section('content')
<div class="container-fluid">
<form method="POST" action="{{ route('mtr.unidades.store') }}">
@csrf
<div class="card card-custom"><div class="card-header"><h3 class="card-title">Nova Credencial MTR</h3></div><div class="card-body">
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@include('mtr.unidades.form')
</div></div>
</form>
</div>
@endsection
