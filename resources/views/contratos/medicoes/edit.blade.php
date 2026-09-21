@extends('default.layout')
@section('content')
<div class="container-fluid">
    @php $formAction = route('contratos.medicoes.update',$fatura->id); $contratoSelecionado = $fatura->contrato; @endphp
    @include('contratos.medicoes.form')
</div>
@endsection
