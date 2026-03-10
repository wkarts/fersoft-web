@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header py-3">
        <div class="card-title">
            <span class="card-icon"><i class="la la-code-branch text-primary"></i></span>
            <h3 class="card-label">Controle Interno de Versões</h3>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-light-info mb-5">
            <h5 class="mb-3">Versão atualmente instalada</h5>
            @if($currentVersion)
                <div><strong>Versão:</strong> {{ $currentVersion->version }}</div>
                <div><strong>SemVer:</strong> {{ $currentVersion->version_major }}.{{ $currentVersion->version_minor }}.{{ $currentVersion->version_patch }}</div>
                <div><strong>Título:</strong> {{ $currentVersion->title ?: '-' }}</div>
                <div><strong>Release:</strong> {{ optional($currentVersion->released_at)->format('d/m/Y H:i') ?: '-' }}</div>
                <div><strong>Instalada em:</strong> {{ optional($currentVersion->installed_at)->format('d/m/Y H:i') ?: '-' }}</div>
                <div><strong>Formato:</strong> {{ strtoupper($currentVersion->release_notes_format) }}</div>
            @else
                <div class="text-muted">Nenhuma versão interna registrada.</div>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Versão</th>
                        <th>Título</th>
                        <th>Canal</th>
                        <th>Release</th>
                        <th>Instalação</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($versions as $version)
                    <tr>
                        <td>
                            <strong>{{ $version->version }}</strong>
                            @if($version->is_current)
                                <span class="badge badge-success ml-2">Atual</span>
                            @endif
                        </td>
                        <td>{{ $version->title ?: '-' }}</td>
                        <td>{{ $version->release_channel ?: '-' }}</td>
                        <td>{{ optional($version->released_at)->format('d/m/Y H:i') ?: '-' }}</td>
                        <td>{{ optional($version->installed_at)->format('d/m/Y H:i') ?: '-' }}</td>
                        <td>
                            @if($version->release_notes_html)
                                <button class="btn btn-sm btn-light-primary" data-toggle="modal" data-target="#release-modal-{{ $version->id }}">Ver HTML</button>
                            @endif
                            @if($version->release_notes_pdf_path)
                                <a class="btn btn-sm btn-light-info" target="_blank" href="{{ asset($version->release_notes_pdf_path) }}">Abrir PDF</a>
                            @endif
                            @if(!$version->release_notes_html && !$version->release_notes_pdf_path)
                                <span class="text-muted">Sem conteúdo</span>
                            @endif
                        </td>
                    </tr>
                    @if($version->release_notes_html)
                    <div class="modal fade" id="release-modal-{{ $version->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Release Notes - {{ $version->version }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    {!! $version->release_notes_html !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">Nenhuma versão registrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $versions->links() }}
        </div>
    </div>
</div>
@endsection
