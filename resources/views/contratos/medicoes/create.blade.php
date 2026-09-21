@extends('default.layout')
@section('content')
<div class="container-fluid">
    @php $formAction = isset($contratoSelecionado) && $contratoSelecionado ? route('contratos.medicoes.store',$contratoSelecionado->id) : route('contratos.medicoes.store.avulso'); $fatura = null; @endphp
    @include('contratos.medicoes.form')
</div>
@endsection
