@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush

@section('content')
    <div class="fer-extra fer-extra--support fer-extra--manutencoes card card-custom gutter-b">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-6">
                <div>
                    <h3 class="card-title font-weight-bolder">Relatório de Alçadas e Aprovações Pendentes</h3>
                    <span class="text-muted">Serviços e orçamentos aguardando liberação da diretoria</span>
                </div>
                <div>
                    <a href="{{ route('manutencao.alertas.index') }}" class="btn btn-light-primary font-weight-bold">
                        <i class="la la-arrow-left"></i> Voltar ao Painel
                    </a>
                    <button onclick="window.print();" class="btn btn-dark font-weight-bold ml-2">
                        <i class="la la-print"></i> Imprimir
                    </button>
                </div>
            </div>

            <div class="alert alert-custom alert-light-warning fade show mb-5" role="alert">
                <div class="alert-icon"><i class="la la-wallet text-warning icon-2x"></i></div>
                <div class="alert-text font-size-h6">
                    <strong>Custo Estimado Pendente de Aprovação:</strong>
                    <span class="text-danger font-weight-bolder">R$ {{ number_format($totalEstimado, 2, ',', '.') }}</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-light">
                    <tr>
                        <th>Veículo</th>
                        <th>Problema / Serviço</th>
                        <th>Fase</th>
                        <th>Data Registro</th>
                        <th>Custo Estimado</th>
                        <th class="text-center">Ação</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($aguardando as $item)
                        <tr>
                            <td class="font-weight-bold">{{ $item->veiculo->placa ?? 'N/A' }}</td>
                            <td>
                                <strong>{{ $item->titulo }}</strong>
                                <div class="text-muted small">{{ $item->descricao }}</div>
                            </td>
                            <td>{{ strtoupper(str_replace('_', ' ', $item->fase)) }}</td>
                            <td>{{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '--' }}</td>
                            <td class="font-weight-bold text-danger">R$ {{ number_format($item->valor_estimado, 2, ',', '.') }}</td>
                            <td class="text-center">
                                <form method="post" action="{{ route('manutencao.alertas.aprovar', $item->id) }}" onsubmit="return confirm('Deseja autorizar esta manutenção?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success font-weight-bold">
                                        <i class="la la-check"></i> Autorizar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">Nenhuma solicitação aguardando autorização no momento.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
