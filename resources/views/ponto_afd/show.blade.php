@extends('default.layout')
@section('content')
<div class="card card-custom">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title">{{ $title }} #{{ $arquivo->id }}</h3>
        <a href="/ponto/importacao-afd" class="btn btn-light btn-sm">Voltar</a>
    </div>
    <div class="card-body">
        <p><strong>Arquivo:</strong> {{ $arquivo->nome_original }}</p>
        <p><strong>Hash:</strong> {{ $arquivo->hash_arquivo }}</p>
        <p><strong>Status:</strong> {{ $arquivo->status }}</p>
        <table class="table table-sm table-bordered">
            <thead><tr><th>Linha</th><th>Data/Hora</th><th>PIS</th><th>CPF</th><th>Matrícula</th><th>Inconsistência</th></tr></thead>
            <tbody>
            @foreach($registros as $r)
                <tr>
                    <td>{{ $r->numero_linha }}</td>
                    <td>{{ $r->data_hora_marcacao }}</td>
                    <td>{{ $r->pis }}</td>
                    <td>{{ $r->cpf }}</td>
                    <td>{{ $r->matricula }}</td>
                    <td>{{ $r->inconsistente ? $r->motivo_inconsistencia : '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $registros->links() }}
    </div>
</div>
@endsection
