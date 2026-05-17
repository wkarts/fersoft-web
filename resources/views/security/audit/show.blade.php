@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Log #{{ $log->id }}</h3>
                <p class="text-muted mb-0">{{ $log->modelo }} — {{ $log->acao }} — {{ optional($log->created_at)->format('d/m/Y H:i:s') }}</p>
            </div>
            <div>
                <a href="/seguranca/auditoria" class="btn btn-light">Voltar</a>
                @if($canExportJson)<a href="/seguranca/auditoria/exportar-json?log_id={{ $log->id }}" class="btn btn-outline-primary">Baixar JSON</a>@endif
                @if($canRestore)<a href="/seguranca/auditoria/{{ $log->id }}/restaurar/preview" class="btn btn-warning">Restaurar/Reverter</a>@endif
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3"><strong>Empresa:</strong> {{ $log->empresa_id ?? '-' }}</div>
            <div class="col-md-3"><strong>Usuário:</strong> {{ optional($log->usuario)->nome ?? $log->usuario_id ?? '-' }}</div>
            <div class="col-md-3"><strong>Filial:</strong> {{ optional($log->filial)->nome_fantasia ?? 'Matriz' }}</div>
            <div class="col-md-3"><strong>IP:</strong> {{ $log->ip_address }}</div>
        </div>

        @if(!$canViewJson)
            <div class="alert alert-warning">O JSON completo deste log não está liberado para seu perfil/tenant.</div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <h5>Dados anteriores</h5>
                <pre style="max-height:520px;overflow:auto;background:#f7f7f7;padding:12px;border:1px solid #ddd;">{{ json_encode($payload['dados_anteriores'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
            <div class="col-md-6">
                <h5>Dados depois</h5>
                <pre style="max-height:520px;overflow:auto;background:#f7f7f7;padding:12px;border:1px solid #ddd;">{{ json_encode($payload['dados_depois'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection
