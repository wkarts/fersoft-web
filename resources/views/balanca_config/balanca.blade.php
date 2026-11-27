@extends('default.layout')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-catalog-config.css') }}?v={{ filemtime(public_path('css/fer-catalog-config.css')) }}">
@endpush
@section('content')
<div class="fer-catalog-config fer-catalog-config--form fer-catalog-config--balanca_config">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="container-fluid">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Teste de Balança e Câmeras ADP</h5>
            </div>
            <div class="card-body">
                @include('components.balanca-leitor-widget', [
                    'balancas' => $balancas,
                    'modo' => 'teste',
                    'inputBalancaId' => 'balanca_config_id_teste',
                    'inputPesoOrigemId' => 'peso_origem_teste',
                    'inputEvidenceId' => 'balanca_evidence_json_teste',
                ])
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
    <script src="{{ asset('js/adp-runtime-client.js') }}"></script>
    <script src="{{ asset('js/balanca-leitor.js') }}"></script>
@endsection
