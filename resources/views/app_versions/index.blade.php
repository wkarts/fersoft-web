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

        <div class="accordion accordion-toggle-arrow" id="accordion-major-versions">
            @forelse($groupedVersions as $major => $items)
                @php($majorId = 'major-'.$major)
                <div class="card">
                    <div class="card-header" id="heading-{{ $majorId }}">
                        <div class="card-title collapsed" data-toggle="collapse" data-target="#collapse-{{ $majorId }}" aria-expanded="false" aria-controls="collapse-{{ $majorId }}">
                            Versão {{ $major }}
                            <span class="label label-light-info label-inline ml-3">{{ $items->count() }} releases</span>
                        </div>
                    </div>
                    <div id="collapse-{{ $majorId }}" class="collapse" aria-labelledby="heading-{{ $majorId }}" data-parent="#accordion-major-versions">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Versão</th>
                                            <th>Título</th>
                                            <th>Release</th>
                                            <th>Instalação</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $version)
                                            <tr>
                                                <td>
                                                    <strong>{{ $version->version }}</strong>
                                                    @if($version->is_current)
                                                        <span class="badge badge-success ml-2">Atual</span>
                                                    @endif
                                                </td>
                                                <td>{{ $version->title ?: '-' }}</td>
                                                <td>{{ optional($version->released_at)->format('d/m/Y H:i') ?: '-' }}</td>
                                                <td>{{ optional($version->installed_at)->format('d/m/Y H:i') ?: '-' }}</td>
                                                <td class="d-flex flex-wrap" style="gap: .5rem;">
                                                    @if($version->release_notes_current_html)
                                                        <button class="btn btn-sm btn-light-primary" data-toggle="modal" data-target="#release-current-{{ $version->id }}">Ver release</button>
                                                    @endif
                                                    @if($version->release_notes_cumulative_html)
                                                        <button class="btn btn-sm btn-light-warning" data-toggle="modal" data-target="#release-cumulative-{{ $version->id }}">Ver cumulativa</button>
                                                    @endif
                                                    @if($version->release_notes_cumulative_pdf_path)
                                                        <a class="btn btn-sm btn-light-info" target="_blank" href="/app-versions/{{ $version->id }}/pdf/cumulative">PDF cumulativo</a>
                                                    @endif
                                                </td>
                                            </tr>

                                            @if($version->release_notes_current_html)
                                                <div class="modal fade" id="release-current-{{ $version->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Release atual - {{ $version->version }}</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                                                            </div>
                                                            <div class="modal-body">{!! $version->release_notes_current_html !!}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($version->release_notes_cumulative_html)
                                                <div class="modal fade" id="release-cumulative-{{ $version->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Release cumulativa - {{ $version->version }}</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                                                            </div>
                                                            <div class="modal-body">{!! $version->release_notes_cumulative_html !!}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted">Nenhuma versão registrada.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
