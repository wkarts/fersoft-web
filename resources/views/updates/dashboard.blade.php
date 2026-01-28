@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header py-3">
        <div class="card-title">
            <span class="card-icon"><i class="la la-sync-alt text-primary"></i></span>
            <h3 class="card-label">Atualizações da Aplicação</h3>
        </div>
    </div>
    <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-4">
                <div class="card card-custom card-stretch gutter-b">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h3 class="card-label">Versão atual</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="font-weight-bold display-4">{{ optional($currentVersion)->version ?? 'N/A' }}</div>
                        <div class="text-muted mt-2">Última atualização em:
                            <strong>{{ optional($currentVersion?->applied_at)->format('d/m/Y H:i') ?? '-' }}</strong>
                        </div>
                        <div class="mt-4">
                            <span class="font-weight-bold">Modo de atualização:</span>
                            @if($mode === 'dev')
                                <span class="badge badge-warning text-uppercase ml-2">Dev</span>
                            @else
                                <span class="badge badge-success text-uppercase ml-2">Stable</span>
                            @endif
                        </div>
                        <div class="text-muted mt-2">
                            <div><strong>Branch Stable:</strong> {{ $stableBranch }}</div>
                            <div><strong>Branch Dev:</strong> {{ $devBranch }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card card-custom card-stretch gutter-b">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h3 class="card-label">Versões disponíveis</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($availableVersions->isEmpty())
                            <p class="text-muted mb-0">Nenhuma nova versão encontrada.</p>
                        @else
                            <form action="{{ route('updates.apply') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label class="font-weight-bold">Selecione a versão</label>
                                    <select name="version" class="form-control" required>
                                        @foreach($availableVersions as $version)
                                            @php($sha = data_get($version->metadata, 'sha'))
                                            <option value="{{ $version->identifier }}">
                                                @if($mode === 'dev')
                                                    {{ 'dev/' . data_get($version->metadata, 'branch', $devBranch) }} –
                                                    {{ optional($version->releasedAt)->format('d/m/Y H:i') ?? '-' }} –
                                                    {{ $sha ? substr($sha, 0, 7) : $version->identifier }}
                                                @else
                                                    {{ $version->identifier }}
                                                    @if($version->description)
                                                        – {{ $version->description }}
                                                    @endif
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Provider</label>
                                    <select name="provider" class="form-control">
                                        <option value="" selected>Padrão ({{ $defaultProvider }})</option>
                                        @foreach($providers as $provider)
                                            <option value="{{ $provider }}">{{ ucfirst($provider) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Ação do Composer</label>
                                    <select name="composer_action" class="form-control">
                                        @foreach($composerActions as $action => $label)
                                            <option value="{{ $action }}" @selected($action === $defaultComposerAction)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Observações</label>
                                    <textarea name="observations" class="form-control" rows="3" placeholder="Informações adicionais sobre esta atualização"></textarea>
                                </div>
                                <div class="d-flex flex-wrap align-items-center" style="gap: 0.75rem;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="la la-cloud-download-alt"></i>
                                        Iniciar atualização
                                    </button>
                                    <button type="submit" name="follow" value="1" class="btn btn-light-primary">
                                        <i class="la la-desktop"></i>
                                        Iniciar e acompanhar em tempo real
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-custom card-stretch gutter-b">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h3 class="card-label">Histórico de versões</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Versão</th>
                                <th>Status</th>
                                <th>Aplicado em</th>
                                <th>Observações</th>
                                <th class="text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $version)
                                <tr>
                                    <td>{{ $version->version }}</td>
                                    <td>
                                        <span class="badge badge-{{ $version->status === 'completed' ? 'success' : ($version->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ __(ucfirst($version->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ optional($version->applied_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>
                                        <div class="text-muted small mb-1">
                                            <strong>Modo:</strong> {{ strtoupper($version->mode) }} | <strong>Provider:</strong> {{ $version->provider ?? $defaultProvider }}
                                        </div>
                                        <div class="text-muted small mb-1">
                                            <strong>Composer:</strong> {{ $version->composer_action ?? 'none' }}
                                        </div>
                                        <div class="small">{{ $version->observations ?? '—' }}</div>
                                    </td>
                                    <td class="text-right">
                                        @php($latestLog = $version->latestLog)
                                        @if($latestLog)
                                            <a href="{{ route('updates.logs.show', [$version, $latestLog]) }}" class="btn btn-sm btn-light-primary">
                                                <i class="la la-file-alt"></i> Logs
                                            </a>
                                        @endif
                                        @if($allowDowngrade && $version->status === 'completed')
                                            <form action="{{ route('updates.downgrade', $version) }}" method="POST" class="d-inline-block text-left mt-2">
                                                @csrf
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">Composer</label>
                                                    <select name="composer_action" class="form-control form-control-sm">
                                                        @foreach($composerActions as $action => $label)
                                                            <option value="{{ $action }}" @selected(($version->composer_action ?? $defaultComposerAction) === $action)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group mb-2">
                                                    <label class="small mb-1">Observações</label>
                                                    <textarea name="observations" class="form-control form-control-sm" rows="2"></textarea>
                                                </div>
                                                <div class="form-group mb-2">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" id="restore-{{ $version->id }}" name="restore_dependencies" value="1">
                                                        <label class="form-check-label" for="restore-{{ $version->id }}">Restaurar vendor/composer</label>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-2">
                                                    <select name="provider" class="form-control form-control-sm">
                                                        <option value="" @selected(empty($version->provider) || $version->provider === $defaultProvider)>Padrão ({{ $defaultProvider }})</option>
                                                        @foreach($providers as $provider)
                                                            <option value="{{ $provider }}" @selected($version->provider === $provider)>{{ ucfirst($provider) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                                                    <button class="btn btn-sm btn-light-danger" type="submit" onclick="return confirm('Deseja realmente reverter para esta versão?');">
                                                        <i class="la la-undo"></i> Reverter
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-primary" name="follow" value="1" type="submit" onclick="return confirm('Deseja reverter e acompanhar a execução?');">
                                                        <i class="la la-desktop"></i> Reverter e acompanhar em tempo real
                                                    </button>
                                                </div>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Nenhum histórico disponível.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer py-4">
                {{ $history->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
