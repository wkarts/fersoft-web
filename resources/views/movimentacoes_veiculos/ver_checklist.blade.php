@extends('default.layout')
@section('content')
    <style>
        .resposta-ok { color: #1bc5bd; font-weight: bold; background: #c9f7f5; padding: 4px 8px; border-radius: 4px; }
        .resposta-problema { color: #f64e60; font-weight: bold; background: #ffe2e5; padding: 4px 8px; border-radius: 4px; }
        .foto-box { background: #f8f9fa; border: 1px solid #e4e6ef; border-radius: 8px; padding: 10px; transition: 0.3s; }
        .foto-box:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .foto-box img { width: 100%; height: 250px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
    </style>

    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b shadow-sm border-0">
            <div class="card-header border-0 py-5">
                <h3 class="card-title align-items-start flex-column">
                <span class="card-label font-weight-bolder text-dark">
                    <i class="la la-clipboard-check icon-xl text-primary mr-2"></i> {{ $title }}
                </span>
                    <span class="text-muted mt-3 font-weight-bold font-size-sm">Respostas e Evidências Fotográficas</span>
                </h3>
                <div class="card-toolbar">
                    <a href="{{ url('/movimentacaoVeiculo') }}" class="btn btn-light-primary font-weight-bolder">
                        <i class="la la-arrow-left"></i> Voltar para Listagem
                    </a>
                </div>
            </div>

            <div class="card-body pt-0 pb-10">
                @if(!$checklist)
                    <div class="alert alert-custom alert-light-warning fade show mb-5" role="alert">
                        <div class="alert-icon"><i class="flaticon-warning text-warning fa-2x"></i></div>
                        <div class="alert-text">
                            <h5 class="font-weight-bold mb-1">Nenhum checklist encontrado!</h5>
                            <span>O motorista ainda não preencheu e enviou o checklist para esta movimentação ou não era obrigatório.</span>
                        </div>
                    </div>
                @else
                    @php
                        $respostas = json_decode($checklist->respostas_json, true) ?? [];
                        $badgeStatus = $checklist->status_geral == 'aprovado' ? 'badge-success' : 'badge-danger';
                    @endphp

                        <!-- Cabeçalho de Informações -->
                    <div class="row mb-8 p-5 rounded" style="background-color: #f3f6f9;">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <span class="text-muted font-weight-bold d-block">Motorista Responsável:</span>
                            <span class="font-size-h5 font-weight-bolder text-dark">{{ $movimentacao->motorista->nome ?? 'Não identificado' }}</span>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <span class="text-muted font-weight-bold d-block">Data/Hora do Preenchimento:</span>
                            <span class="font-size-h5 font-weight-bolder text-dark">{{ \Carbon\Carbon::parse($checklist->data_resposta)->format('d/m/Y H:i:s') }}</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted font-weight-bold d-block">Status Geral do Veículo:</span>
                            <span class="badge {{ $badgeStatus }} font-weight-bold p-2 text-uppercase mt-1">
                            {{ str_replace('_', ' ', $checklist->status_geral) }}
                        </span>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <!-- Respostas dos Itens -->
                    <h4 class="font-weight-bold text-dark mb-5"><i class="la la-list-alt text-primary"></i> 1. Inspeção de Itens</h4>
                    <div class="row">
                        <div class="col-md-6 mb-5">
                            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                                <span class="font-weight-bold text-dark-75">Pneus e Rodas:</span>
                                <span class="{{ isset($respostas['pneus']) && $respostas['pneus'] == 'ok' ? 'resposta-ok' : 'resposta-problema' }}">
                                <i class="fa {{ isset($respostas['pneus']) && $respostas['pneus'] == 'ok' ? 'fa-check text-success' : 'fa-times text-danger' }} mr-1"></i>
                                {{ isset($respostas['pneus']) && $respostas['pneus'] == 'ok' ? 'OK' : 'Avaria Reportada' }}
                            </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-5">
                            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                                <span class="font-weight-bold text-dark-75">Óleo, Água e Arla:</span>
                                <span class="{{ isset($respostas['oleo_agua']) && $respostas['oleo_agua'] == 'ok' ? 'resposta-ok' : 'resposta-problema' }}">
                                <i class="fa {{ isset($respostas['oleo_agua']) && $respostas['oleo_agua'] == 'ok' ? 'fa-check text-success' : 'fa-times text-danger' }} mr-1"></i>
                                {{ isset($respostas['oleo_agua']) && $respostas['oleo_agua'] == 'ok' ? 'OK' : 'Baixo/Vazamento' }}
                            </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-5">
                            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                                <span class="font-weight-bold text-dark-75">Freios e Estacionamento:</span>
                                <span class="{{ isset($respostas['freios']) && $respostas['freios'] == 'ok' ? 'resposta-ok' : 'resposta-problema' }}">
                                <i class="fa {{ isset($respostas['freios']) && $respostas['freios'] == 'ok' ? 'fa-check text-success' : 'fa-times text-danger' }} mr-1"></i>
                                {{ isset($respostas['freios']) && $respostas['freios'] == 'ok' ? 'OK' : 'Falha Reportada' }}
                            </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-5">
                            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                                <span class="font-weight-bold text-dark-75">Faróis e Lanternas:</span>
                                <span class="{{ isset($respostas['farois_lanternas']) && $respostas['farois_lanternas'] == 'ok' ? 'resposta-ok' : 'resposta-problema' }}">
                                <i class="fa {{ isset($respostas['farois_lanternas']) && $respostas['farois_lanternas'] == 'ok' ? 'fa-check text-success' : 'fa-times text-danger' }} mr-1"></i>
                                {{ isset($respostas['farois_lanternas']) && $respostas['farois_lanternas'] == 'ok' ? 'OK' : 'Queimado' }}
                            </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-5">
                            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                                <span class="font-weight-bold text-dark-75">Documentação do Veículo:</span>
                                <span class="{{ isset($respostas['documentacao']) && $respostas['documentacao'] == 'ok' ? 'resposta-ok' : 'resposta-problema' }}">
                                <i class="fa {{ isset($respostas['documentacao']) && $respostas['documentacao'] == 'ok' ? 'fa-check text-success' : 'fa-times text-danger' }} mr-1"></i>
                                {{ isset($respostas['documentacao']) && $respostas['documentacao'] == 'ok' ? 'OK' : 'Ausente/Irregular' }}
                            </span>
                            </div>
                        </div>
                    </div>

                    @if(!empty($checklist->observacoes))
                        <div class="mt-2 p-4 rounded" style="background-color: #fff3e0; border-left: 4px solid #ff9800;">
                            <span class="font-weight-bold text-dark"><i class="la la-comment-alt text-warning"></i> Observações / Avarias Anteriores reportadas pelo motorista:</span><br>
                            <p class="mb-0 mt-2 text-dark-75 font-size-lg">{{ $checklist->observacoes }}</p>
                        </div>
                    @endif

                    <div class="separator separator-dashed my-8"></div>

                    <!-- Evidências Fotográficas -->
                    <h4 class="font-weight-bold text-dark mb-5"><i class="la la-camera-retro text-primary"></i> 2. Evidências Fotográficas</h4>
                    <div class="row">
                        @forelse($fotos as $foto)
                            <div class="col-md-6 col-lg-4 mb-6">
                                <div class="foto-box text-center">
                                    <h6 class="text-uppercase font-weight-bolder text-muted mb-3">{{ $foto->tipo_foto }}</h6>
                                    <a href="{{ asset($foto->caminho_arquivo) }}" target="_blank" title="Clique para ampliar a imagem">
                                        <img src="{{ asset($foto->caminho_arquivo) }}" alt="{{ $foto->tipo_foto }}">
                                    </a>
                                    <span class="d-block mt-3 text-muted small"><i class="la la-search-plus"></i> Clique na foto para abrir em tamanho real</span>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <span class="text-muted font-italic">Nenhuma foto foi anexada neste checklist.</span>
                            </div>
                        @endforelse
                    </div>

                @endif
            </div>
        </div>
    </div>
@endsection
