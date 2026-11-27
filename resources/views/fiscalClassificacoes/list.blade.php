@extends('default.layout')

@section('content')
<div class="container-fluid" id="kt_content">
    <div class="card card-custom gutter-b">
        <div class="card-header align-items-center py-4">
            <div class="card-title my-0">
                <h1 class="card-label font-weight-bolder mb-0" style="font-size:1.3rem">ClassTrib — Cadastros</h1>
            </div>
        </div>
        <div class="card-body">
            @if(!$catalogInstalled)
                <div class="alert alert-warning mb-0" role="alert">
                    O catálogo de classificações tributárias ainda não está instalado nesta base.
                </div>
            @elseif(!$catalogComplete)
                <div class="alert alert-warning mb-0" role="alert">
                    O catálogo de classificações tributárias está incompleto nesta base. Solicite a verificação da instalação fiscal.
                </div>
            @else
                @if($classificacoes->isEmpty())
                    <p class="text-muted mb-0" role="status">Nenhuma classificação encontrada.</p>
                @else
                    <p class="text-muted mb-3" role="status">{{ $classificacoes->total() }} {{ $classificacoes->total() === 1 ? 'classificação encontrada' : 'classificações encontradas' }}</p>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" aria-label="Classificações tributárias IBS e CBS">
                            <thead>
                                <tr>
                                    <th scope="col">ClassTrib</th>
                                    <th scope="col">CST</th>
                                    <th scope="col">Descrição</th>
                                    <th scope="col">Redução IBS</th>
                                    <th scope="col">Redução CBS</th>
                                    <th scope="col">Vigência</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($classificacoes as $classificacao)
                                    <tr>
                                        <th scope="row" class="font-weight-bold">{{ $classificacao->cclasstrib }}</th>
                                        <td>{{ $classificacao->cst_ibs_cbs ?: '—' }}</td>
                                        <td>
                                            <span class="font-weight-bold">{{ $classificacao->nome_cclasstrib ?: $classificacao->descricao_cclasstrib }}</span>
                                            @if($classificacao->nome_cclasstrib && $classificacao->descricao_cclasstrib && $classificacao->descricao_cclasstrib !== $classificacao->nome_cclasstrib)
                                                <small class="d-block text-muted">{{ $classificacao->descricao_cclasstrib }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $classificacao->predibs }}%</td>
                                        <td>{{ $classificacao->predcbs }}%</td>
                                        <td>
                                            {{ $classificacao->dinivig ? $classificacao->dinivig->format('d/m/Y') : '—' }}
                                            @if($classificacao->dfimvig)
                                                <span class="d-block text-muted">até {{ $classificacao->dfimvig->format('d/m/Y') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $classificacoes->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
