@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header py-3">
        <div class="card-title">
            <span class="card-icon"><i class="la la-file-alt text-primary"></i></span>
            <h3 class="card-label">Log da atualização {{ $version->version }}</h3>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('updates.index') }}" class="btn btn-light-primary font-weight-bold">
                <i class="la la-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6">
            <div>
                <span class="label label-inline label-lg font-weight-bold label-light-{{ $log->status === 'failed' ? 'danger' : ($log->status === 'completed' ? 'success' : 'warning') }}">
                    {{ __(ucfirst($log->status)) }}
                </span>
                <span class="label label-inline label-lg font-weight-bold label-light-dark ml-2">
                    {{ __(ucfirst($log->action)) }}
                </span>
            </div>
            <div class="text-muted mt-3 mt-md-0">
                Registrado em {{ $log->created_at->format('d/m/Y H:i') }}
            </div>
        </div>

        @if(!empty($version->observations))
            <div class="alert alert-info">
                <strong>Observações da versão:</strong>
                <div class="mt-2">{{ $version->observations }}</div>
            </div>
        @endif

        @if(isset($migrations) && $migrations->isNotEmpty())
            <h5 class="font-weight-bold">Migrations executadas</h5>
            <ul class="list-unstyled mb-6">
                @foreach($migrations as $migration)
                    <li class="mb-1">
                        <span class="label label-inline label-light-dark font-weight-bold mr-2">{{ strtoupper($migration->direction) }}</span>
                        {{ $migration->migration }}
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="alert alert-secondary" role="alert">
            {{ $log->message }}
        </div>

        @if(!empty($log->context))
            <h5 class="font-weight-bold">Contexto</h5>
            <pre class="bg-dark text-white p-4 rounded overflow-auto" style="max-height: 400px;">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif
    </div>
</div>
@endsection
