@extends('default.layout')

@section('content')
    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="container mt-5">

            <div class="card card-custom mb-5 shadow-sm" style="border-radius: 10px;">
                <div class="card-body py-4 d-flex justify-content-between align-items-center flex-wrap">
                    <h3 class="text-dark font-weight-bold m-0 mb-3 mb-md-0">
                        <i class="fas fa-file-invoice-dollar text-primary mr-2"></i> {{ $title }}
                    </h3>

                    <form action="{{ url()->current() }}" method="GET" class="d-flex align-items-center flex-wrap">
                        <label class="mr-2 font-weight-bold mb-0">Período:</label>
                        <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="form-control mr-2 mb-2 mb-md-0" style="width: 150px;" required>
                        <input type="date" name="data_fim" value="{{ $dataFim }}" class="form-control mr-2 mb-2 mb-md-0" style="width: 150px;" required>

                        <button type="submit" class="btn btn-primary btn-sm mr-1 mb-2 mb-md-0">
                            <i class="fas fa-filter"></i> Filtrar Período
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-success btn-sm mb-2 mb-md-0">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </form>
                </div>
            </div>

            <div id="area-impressao">
                <div class="card card-custom shadow-sm" style="border-radius: 10px;">

                    <div class="print-cabecalho" style="display: none; text-align: center; margin-bottom: 20px;">
                        <h2 style="font-weight: bold; text-transform: uppercase; margin-bottom: 5px;">Relatório Consolidado de Frota</h2>
                        <p style="font-size: 14px; margin: 0;">Período: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} até {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}</p>
                        <div style="border-bottom: 2px solid #000; margin-top: 10px; width: 100%;"></div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-head-custom table-vertical-center table-hover table-relatorio">
                                <thead>
                                <tr class="bg-light text-left text-uppercase font-weight-bolder text-dark">
                                    <th style="min-width: 130px;">Veículo</th>
                                    <th>Status</th>
                                    <th class="text-right">KM Rodado</th>
                                    <th class="text-right">Média (KM/L)</th>
                                    <th class="text-right">Consumo Diesel</th>
                                    <th class="text-right">Consumo Arla 32</th>
                                    <th class="text-right">Manutenções</th>
                                    <th class="text-right">Custo Total</th>
                                    <th class="text-right">R$ por KM</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($dadosRelatorio as $item)
                                    <tr>
                                        <td>
                                            <span class="text-dark-75 font-weight-bolder d-block font-size-lg">{{ $item->placa }}</span>
                                            <span class="text-muted font-weight-bold">{{ $item->modelo }}</span>
                                        </td>
                                        <td>
                                            @if($item->status == 'Em Viagem')
                                                <span class="label label-inline label-light-warning font-weight-bold">Em Viagem</span>
                                            @else
                                                <span class="label label-inline label-light-success font-weight-bold">Disponível</span>
                                            @endif
                                        </td>
                                        <td class="text-right font-weight-bold">
                                            {{ number_format($item->km_rodado, 0, ',', '.') }} km
                                        </td>
                                        <td class="text-right font-weight-bold text-success">
                                            {{ number_format($item->media_kml, 2, ',', '.') }}
                                        </td>

                                        <td class="text-right">
                                            <span class="font-weight-bolder text-info d-block consumo-texto">{{ number_format($item->litros_combustivel, 0, ',', '.') }} L</span>
                                            <span class="text-muted font-weight-bold">R$ {{ number_format($item->custo_combustivel, 2, ',', '.') }}</span>
                                        </td>

                                        <td class="text-right">
                                            <span class="font-weight-bolder text-dark d-block consumo-texto">{{ number_format($item->litros_arla, 0, ',', '.') }} L</span>
                                            <span class="text-muted font-weight-bold">R$ {{ number_format($item->custo_arla, 2, ',', '.') }}</span>
                                        </td>

                                        <td class="text-right font-weight-bold text-danger">
                                            R$ {{ number_format($item->custo_manutencao, 2, ',', '.') }}
                                        </td>

                                        <td class="text-right">
                                            <span class="font-weight-bolder text-dark d-block font-size-h6">
                                                R$ {{ number_format($item->custo_total, 2, ',', '.') }}
                                            </span>
                                        </td>

                                        <td class="text-right font-weight-bolder text-primary">
                                            R$ {{ number_format($item->custo_por_km, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center font-weight-bold text-muted py-10">
                                            Nenhuma movimentação processada no período.
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            @page {
                size: landscape !important;
                margin: 1cm !important;
            }

            /* Reset de Layout */
            body * { visibility: hidden !important; }
            #area-impressao, #area-impressao * { visibility: visible !important; }
            #area-impressao {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Centralização do Cabeçalho */
            .print-cabecalho {
                display: block !important;
                width: 100% !important;
            }

            /* Tabela Compacta para não quebrar linha */
            .table-relatorio {
                width: 100% !important;
                font-size: 10px !important; /* Fonte menor para caber tudo */
            }

            .table-relatorio th, .table-relatorio td {
                padding: 4px 2px !important;
                white-space: nowrap !important; /* Evita quebra de linha nas células */
            }

            /* Escurecer valores para Preto e Branco */
            .consumo-texto {
                color: #000000 !important; /* Preto puro para total nitidez */
                font-weight: 900 !important;
            }

            .text-info, .text-primary, .text-success, .text-danger {
                color: #000000 !important; /* Força tudo que é importante para preto na impressão */
            }

            .card { border: none !important; box-shadow: none !important; }
            .bg-light { background-color: #f3f6f9 !important; -webkit-print-color-adjust: exact; }
        }
    </style>
@endsection
