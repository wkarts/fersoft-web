@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-modules.css') }}?v={{ filemtime(public_path('css/fer-modules.css')) }}">
@endpush
@section('content')

    <header class="fer-page__heading"><h1>Histórico de Veículos</h1></header>
<div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="fer-page fer-operational fer-detail card card-custom gutter-b">

            <!-- CABEÇALHO -->
            <div class="card-header bg-light">
                <div class="card-title">
                    <h3 class="card-label">
                        <i class="la la-route icon-xl text-primary mr-2"></i>
                        {{ $title }} - {{ $movimentacao->veiculo->placa ?? 'Sem Veículo' }}
                    </h3>
                </div>
                <div class="card-toolbar">
                    <a href="{{ url('/movimentacaoVeiculo') }}" class="btn btn-sm btn-secondary font-weight-bold">
                        <i class="la la-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>

            <div class="card-body">

                <!-- RESUMO DA VIAGEM -->
                <div class="row mb-8">
                    <div class="col-md-3">
                        <span class="text-muted font-weight-bold">Motorista</span><br>
                        <span class="font-size-h6 text-dark-75">{{ $movimentacao->motorista->nome ?? 'N/A' }}</span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted font-weight-bold">Status</span><br>
                        {!! $movimentacao->status_formatado !!}
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted font-weight-bold">Saída Garagem</span><br>
                        <span class="font-size-h6 text-primary">{{ $movimentacao->data_saida_real_formatada }}</span>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted font-weight-bold">Retorno Garagem</span><br>
                        <span class="font-size-h6 text-primary">{{ $movimentacao->data_chegada_formatada }}</span>
                    </div>
                </div>

                <div class="row">
                    <!-- BLOCO 1: ROTA DE CLIENTES -->
                    <div class="col-lg-7">
                        <h4 class="font-weight-bold mb-4 text-dark"><i class="la la-map-marker text-success"></i> Desempenho nas Entregas/Coletas</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Local / Cliente</th>
                                    <th>Chegada (Check-in)</th>
                                    <th>Saída (Check-out)</th>
                                    <th>Tempo no Local</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($movimentacao->destinos as $destino)
                                    <tr>
                                        <td class="font-weight-bold">{{ $destino->ordem }}</td>
                                        <td>
                                            @if($destino->cliente)
                                                <b>{{ $destino->cliente->razao_social ?? $destino->cliente->nome }}</b><br>
                                            @elseif($destino->fornecedor)
                                                <b>[FOR] {{ $destino->fornecedor->razao_social ?? $destino->fornecedor->nome }}</b><br>
                                            @endif
                                        </td>

                                        @if($destino->data_hora_chegada)
                                            <td class="text-success font-weight-bold">
                                                {{ \Carbon\Carbon::parse($destino->data_hora_chegada)->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="text-danger font-weight-bold">
                                                {{ $destino->data_hora_saida ? \Carbon\Carbon::parse($destino->data_hora_saida)->format('d/m/Y H:i') : 'Ainda no local' }}
                                            </td>
                                            <td>
                                                @if($destino->data_hora_saida)
                                                    {{ \Carbon\Carbon::parse($destino->data_hora_chegada)->diffInMinutes($destino->data_hora_saida) }} min
                                                @else
                                                    ---
                                                @endif
                                            </td>
                                        @else
                                            <td colspan="3" class="text-center text-muted">Aguardando visita...</td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhuma rota cadastrada para esta viagem.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- BLOCO 2: LINHA DO TEMPO DO TRACCAR (PARADAS) -->
                    <div class="col-lg-5">
                        <h4 class="font-weight-bold mb-4 text-dark"><i class="la la-stopwatch text-danger"></i> Histórico de Paradas (> 5 min)</h4>

                        <div class="timeline timeline-5 mt-3">
                            @forelse($movimentacao->historicoParadas as $parada)
                                <div class="timeline-item align-items-start mb-4">
                                    <!-- Horário de Início -->
                                    <div class="timeline-label font-weight-bolder text-dark-75 font-size-lg" style="width: 60px;">
                                        {{ \Carbon\Carbon::parse($parada->data_hora_inicio)->format('H:i') }}
                                    </div>

                                    <!-- Ícone visual -->
                                    <div class="timeline-badge ml-3">
                                        <i class="fa fa-genderless text-warning icon-xl"></i>
                                    </div>

                                    <!-- Conteúdo (Duração e Endereço) -->
                                    <div class="font-weight-normal font-size-lg timeline-content text-muted pl-3">
                                        <span class="text-dark-75 font-weight-bold">Parado por {{ $parada->duracao_minutos }} minutos</span><br>
                                        <small><i class="la la-map-pin"></i> {{ $parada->endereco }}</small><br>
                                        <small class="text-muted">Até às {{ \Carbon\Carbon::parse($parada->data_hora_fim)->format('H:i') }}</small>
                                    </div>
                                </div>
                            @empty
                                <div class="alert alert-custom alert-light-info shadow-sm" role="alert">
                                    <div class="alert-icon"><i class="la la-info-circle"></i></div>
                                    <div class="alert-text">Nenhuma parada longa registrada ou a viagem ainda está em andamento.</div>
                                </div>
                            @endforelse
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
