@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Resultado do Processamento</h3>
    </div>
    
    <div class="card-body">
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="bg-light-success p-5 rounded" style="border-left: 5px solid #1bc5bd">
                    <h4 class="text-success mb-0">Notas Autorizadas: <strong>{{ $resumo['sucesso'] }}</strong></h4>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bg-light-danger p-5 rounded" style="border-left: 5px solid #f64e60">
                    <h4 class="text-danger mb-0">Falhas / Rejeições: <strong>{{ $resumo['rejeicao'] }}</strong></h4>
                </div>
            </div>
        </div>

        @if(count($resumo['erros']) > 0)
        <div class="separator separator-dashed my-5"></div>
        <h4 class="text-muted mb-3">Relatório Detalhado de Erros</h4>
        <div class="table-responsive">
            <table class="table table-hover table-light-danger">
                <thead>
                    <tr class="text-uppercase">
                        <th style="min-width: 100px">Descrição do Erro</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumo['erros'] as $erro)
                    <tr>
                        <td class="text-dark-75 font-weight-bolder">{{ $erro }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="card-footer px-0 pb-0 mt-5">
            <a href="{{ route('compras.lote.index') }}" class="btn btn-primary font-weight-bold">Nova Importação</a>
            <a href="/compras" class="btn btn-light-primary font-weight-bold ml-2">Ver Lista de Compras</a>
        </div>
    </div>
</div>
@endsection