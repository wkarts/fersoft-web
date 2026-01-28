@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header py-3">
        <div class="card-title">
            <span class="card-icon"><i class="la la-terminal text-primary"></i></span>
            <h3 class="card-label">Monitorando atualização {{ $version->version }}</h3>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('updates.index') }}" class="btn btn-light-primary font-weight-bold">
                <i class="la la-arrow-left"></i> Voltar para o painel
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-5">
            <div>
                <div class="font-weight-bold text-muted">Status atual</div>
                <div class="h4 mb-0" id="update-status">{{ ucfirst($version->status) }}</div>
            </div>
            <div class="text-muted">
                <div><strong>Composer:</strong> {{ $version->composer_action ?? 'none' }}</div>
                <div><strong>Provider:</strong> {{ $version->provider ?? config('updates.default_provider') }}</div>
                <div><strong>Modo:</strong> {{ strtoupper($version->mode ?? config('updates.mode', 'stable')) }}</div>
            </div>
        </div>

        <div class="mb-5">
            <h5 class="font-weight-bold">Observações</h5>
            <div class="alert alert-light mb-0">{{ $version->observations ?? 'Nenhuma observação registrada.' }}</div>
        </div>

        <div>
            <h5 class="font-weight-bold">Logs recentes</h5>
            <pre class="bg-dark text-white p-4 rounded" style="max-height: 420px; overflow-y: auto;" id="update-log-stream">
@foreach($version->logs->sortBy('id') as $log)
[{{ $log->created_at->format('d/m/Y H:i:s') }}] ({{ strtoupper($log->status) }}) {{ $log->action }}: {{ $log->message }}
@endforeach
            </pre>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const streamElement = document.getElementById('update-log-stream');
    const statusElement = document.getElementById('update-status');
    const endpoint = @json(route('updates.logs.stream', $version));
    let lastId = @json(optional($version->logs->sortByDesc('id')->first())->id ?? 0);
    let polling = true;

    const appendLogs = (logs) => {
        if (!Array.isArray(logs) || logs.length === 0) {
            return;
        }

        const lines = logs.map(log => {
            lastId = Math.max(lastId, log.id);
            const timestamp = new Date(log.created_at).toLocaleString('pt-BR');
            return `[${timestamp}] (${log.status.toUpperCase()}) ${log.action}: ${log.message}`;
        });

        const previous = streamElement.textContent.trim();
        const combined = previous.length ? `${previous}\n${lines.join('\n')}` : lines.join('\n');
        streamElement.textContent = combined;
        streamElement.scrollTop = streamElement.scrollHeight;
    };

    const poll = () => {
        if (!polling) {
            return;
        }

        fetch(`${endpoint}?last_id=${lastId}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(response => response.json())
            .then(data => {
                if (data.logs) {
                    appendLogs(data.logs);
                }
                if (data.version_status) {
                    statusElement.textContent = data.version_status.charAt(0).toUpperCase() + data.version_status.slice(1);
                }
                if (data.completed) {
                    polling = false;
                }
            })
            .catch(() => {
                // fallback: retry later
            });
    };

    setInterval(poll, 3000);
    poll();
})();
</script>
@endpush
