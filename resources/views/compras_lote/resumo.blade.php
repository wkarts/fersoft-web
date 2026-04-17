@extends('default.layout')
@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Resumo do Processamento</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 col-12">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Notas Autorizadas</span>
                            <span class="info-box-number" style="font-size: 1.8rem;">{{ $resumo['sucesso'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-12">
                    <div class="info-box bg-danger">
                        <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Falhas / Rejeições</span>
                            <span class="info-box-number" style="font-size: 1.8rem;">{{ $resumo['rejeicao'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if(count($resumo['erros']) > 0)
            <div class="card card-danger card-outline mt-3">
                <div class="card-header"><h3 class="card-title text-danger">Relatório Detalhado de Erros</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped">
                        <thead><tr><th>Descrição do Erro</th></tr></thead>
                        <tbody>
                            @foreach($resumo['erros'] as $erro)
                            <tr><td class="text-sm">{{ $erro }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div class="mt-4">
                <a href="{{ route('compras.lote.index') }}" class="btn btn-primary btn-lg">Nova Importação</a>
                <a href="/compras" class="btn btn-outline-secondary btn-lg ml-2">Ver Lista de Compras</a>
            </div>
        </div>
    </section>
</div>
@endsection