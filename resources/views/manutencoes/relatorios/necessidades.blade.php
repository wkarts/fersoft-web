@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush

@section('content')
    <div class="fer-extra fer-extra--support fer-extra--manutencoes card card-custom gutter-b">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-6">
                <div>
                    <h3 class="card-title font-weight-bolder">Relatório Gerencial de Veículos com Necessidade de Manutenção</h3>
                    <span class="text-muted">Lista de veículos parados, bloqueados ou aguardando intervenção mecânica</span>
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

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                    <tr>
                        <th>Placa</th>
                        <th>Veículo / Modelo</th>
                        <th>KM Atual</th>
                        <th>Serviço / Quebra</th>
                        <th>Fase Atual</th>
                        <th>Impedido?</th>
                        <th>Prioridade</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($pendencias as $p)
                        <tr>
                            <td class="font-weight-bold">{{ $p->veiculo->placa ?? 'N/A' }}</td>
                            <td>{{ $p->veiculo->marca ?? '' }} {{ $p->veiculo->modelo ?? '' }}</td>
                            <td>{{ number_format($p->veiculo->quilometragem ?? 0, 0, '', '.') }}</td>
                            <td>{{ $p->titulo }}</td>
                            <td>{{ strtoupper(str_replace('_', ' ', $p->fase)) }}</td>
                            <td>
                                @if($p->impedir_circulacao)
                                    <span class="badge badge-danger">SIM (Parado)</span>
                                @else
                                    <span class="badge badge-success">NÃO (Em operação)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $p->prioridade == 'Urgente' ? 'danger' : 'warning' }}">
                                    {{ $p->prioridade }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">Nenhum veículo com pendência crítica registrada.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
