@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header py-3">
        <div class="card-title">
            <span class="card-icon"><i class="la la-book text-primary"></i></span>
            <h3 class="card-label">Controle de Release Notes</h3>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-light-info mb-6">
            <h5 class="mb-3">Release instalada</h5>
            <div><strong>Versão:</strong> {{ $installedRelease['version'] }}</div>
            <div><strong>SemVer:</strong> Major {{ $installedRelease['major'] }} | Minor {{ $installedRelease['minor'] }} | Patch {{ $installedRelease['patch'] }}</div>
            <div><strong>Data da release:</strong> {{ $installedRelease['release_date'] ? \Carbon\Carbon::parse($installedRelease['release_date'])->format('d/m/Y') : '-' }}</div>
            <div><strong>CPF responsável:</strong> {{ $installedRelease['cpf'] }}</div>
            <div><strong>Local:</strong> {{ $installedRelease['location'] }}</div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Versão</th>
                        <th>Data</th>
                        <th>SemVer</th>
                        <th>CPF</th>
                        <th>Local</th>
                        <th>Melhorias (Release Notes)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($releases as $release)
                    @php
                        $version = (string)($release['version'] ?? '0.0.0');
                        $normalized = ltrim($version, 'vV');
                        $parts = explode('.', $normalized);
                    @endphp
                    <tr>
                        <td><strong>{{ $version }}</strong></td>
                        <td>{{ isset($release['release_date']) ? \Carbon\Carbon::parse($release['release_date'])->format('d/m/Y') : '-' }}</td>
                        <td>Major {{ $parts[0] ?? 0 }} | Minor {{ $parts[1] ?? 0 }} | Patch {{ preg_replace('/\D.*/', '', (string)($parts[2] ?? 0)) }}</td>
                        <td>{{ $release['cpf'] ?? 'Não informado' }}</td>
                        <td>{{ $release['location'] ?? 'Não informado' }}</td>
                        <td>
                            <ul class="mb-0 pl-4">
                                @foreach(($release['highlights'] ?? []) as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Nenhum release note cadastrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
