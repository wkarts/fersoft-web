@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush

@section('content')
<div class="fer-extra fer-extra--support fer-extra--manutencoes d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="card card-custom gutter-b example example-compact">
        <div class="container">
            <div class="col-lg-12">
                <br>
                <div class="card-header">
                    <h3 class="card-title">Manutenções de Frota</h3>
                </div>
                <div class="card-body">
                    <p>Tela de listagem de manutenções carregada com sucesso!</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection