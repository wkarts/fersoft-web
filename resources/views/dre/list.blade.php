@extends('default.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="m-0">Relatórios DRE</h2>
            <a href="/dre" class="btn btn-primary">
                <i class="la la-plus"></i> Novo DRE
            </a>
        </div>

        <div class="row">
            @forelse($docs as $d)
                <div class="col-12 col-sm-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="d-block">
                                    {{ \Carbon\Carbon::parse($d->inicio)->format('d/m/Y') }}
                                    –
                                    {{ \Carbon\Carbon::parse($d->fim)->format('d/m/Y') }}
                                </strong>
                                <small class="text-muted">Criado em {{ \Carbon\Carbon::parse($d->created_at)->format('d/m/Y H:i') }}</small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-toggle="dropdown">
                                    <i class="fa fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="/dre/ver/{{ $d->id }}" class="dropdown-item">
                                        <i class="la la-eye mr-2"></i> Ver
                                    </a>
                                    <a href="#"
                                       class="dropdown-item text-danger"
                                       onclick="event.preventDefault();
                                            if(confirm('Deseja realmente excluir este relatório?')) {
                                                window.location.href='/dre/delete/{{ $d->id }}'
                                            }">
                                        <i class="la la-trash mr-2"></i> Excluir
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card-body d-flex flex-column">
                            <div class="mb-3">
                                <h5 class="card-title mb-1">Lucro / Prejuízo</h5>
                                <p class="h4 mb-0 {{ $d->lucro_prejuizo >= 0 ? 'text-success' : 'text-danger' }}">
                                    R$ {{ number_format($d->lucro_prejuizo, 2, ',', '.') }}
                                </p>
                            </div>

                            <div class="mb-3 mt-auto">
                                <h5 class="card-title mb-1">Observação</h5>
                                <p class="mb-0 text-truncate" style="max-height:3em; overflow:hidden;">
                                    {{ $d->observacao ?: '—' }}
                                </p>
                            </div>

                            <a href="/dre/ver/{{ $d->id }}" class="mt-2 btn btn-block btn-outline-primary">
                                <i class="la la-arrow-right mr-1"></i> Detalhes
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center mb-0">
                        Não há nenhum relatório DRE criado ainda.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
