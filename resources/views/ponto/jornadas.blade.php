@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush

@section('content')
<div class="fer-extra fer-extra--ponto card card-custom">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
    </div>
    <div class="card-body">
        <p>Módulo em fase inicial (FASE 1) preparado para integração incremental.</p>
    </div>
</div>
@endsection
