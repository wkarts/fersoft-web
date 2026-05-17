@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Pré-visualização de Restauração</h3>
                <p class="text-muted mb-0">Log #{{ $log->id }} — {{ $log->modelo }} — {{ $log->acao }}</p>
            </div>
            <a href="/seguranca/auditoria/{{ $log->id }}" class="btn btn-light">Voltar</a>
        </div>

        <div class="alert alert-danger">
            Confira cuidadosamente os dados antes de confirmar. Esta operação cria novo log de auditoria ao salvar o registro restaurado/revertido.
        </div>

        <div class="row mb-4">
            <div class="col-md-3"><strong>Modo:</strong> {{ $preview['mode'] }}</div>
            <div class="col-md-3"><strong>Tabela:</strong> {{ $preview['table'] }}</div>
            <div class="col-md-3"><strong>Registro:</strong> {{ $preview['record_id'] ?? '-' }}</div>
            <div class="col-md-3"><strong>Registro atual:</strong> {{ $preview['current_exists'] ? 'Encontrado' : 'Não encontrado' }}</div>
        </div>

        @if(!empty($preview['ignored_fields']))
            <div class="alert alert-info">Campos ignorados por não existirem mais na tabela: {{ implode(', ', $preview['ignored_fields']) }}</div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <h5>Registro atual</h5>
                <pre style="max-height:520px;overflow:auto;background:#f7f7f7;padding:12px;border:1px solid #ddd;">{{ json_encode($preview['current'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
            <div class="col-md-6">
                <h5>Dados que serão aplicados</h5>
                <pre style="max-height:520px;overflow:auto;background:#f7f7f7;padding:12px;border:1px solid #ddd;">{{ json_encode($preview['target'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>

        <form method="post" action="/seguranca/auditoria/{{ $log->id }}/restaurar" onsubmit="return confirm('Confirmar restauração/reversão deste log?');">
            @csrf
            <input type="hidden" name="mode" value="{{ $preview['mode'] }}">
            <button class="btn btn-danger">Confirmar restauração/reversão</button>
            <a href="/seguranca/auditoria/{{ $log->id }}/restaurar/preview?mode=clone" class="btn btn-outline-warning">Pré-visualizar como clone</a>
        </form>
    </div>
</div>
@endsection
