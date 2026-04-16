@extends('default.layout')

@section('content')
<style>
    @media print {
        @page { 
            size: landscape; 
            margin: 8mm; 
        } 
        
        body * { visibility: hidden; }
        #area-relatorio, #area-relatorio * { visibility: visible; }
        
        #area-relatorio { 
            position: relative;
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            top: -30px !important; 
        }

        .card { border: none !important; }
        .table-responsive { overflow: visible !important; }
        
        .table { width: 100% !important; margin-bottom: 0 !important; }
        .table th, .table td { 
            padding: 4px !important; 
            font-size: 11px !important; 
            border: 1px solid #333 !important;
        }

        .no-print { display: none !important; }
        
        .assinatura-container { 
            page-break-inside: avoid; 
            margin-top: 20px !important;
        }
    }
</style>

<div class="d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container mt-5">
        
        <div class="card card-custom mb-5 no-print" style="border-radius: 10px;">
            <div class="card-body py-4 d-flex justify-content-between align-items-center">
                <h3 class="text-dark font-weight-bold m-0">Filtro do Relatório</h3>
                
                <form action="" method="GET" class="d-flex align-items-center">
                    <label class="mr-2 font-weight-bold mb-0">Período:</label>
                    <input type="date" name="data_inicio" class="form-control mr-2" value="{{ $dataInicio }}" required>
                    <span class="mr-2">até</span>
                    <input type="date" name="data_fim" class="form-control mr-3" value="{{ $dataFim }}" required>
                    
                    <button type="submit" class="btn btn-primary btn-sm mr-2"><i class="la la-search"></i> Filtrar</button>
                    <button type="button" class="btn btn-info btn-sm" onclick="window.print()"><i class="la la-print"></i> Imprimir</button>
                </form>
            </div>
        </div>

        <div class="card card-custom gutter-b bg-white" id="area-relatorio">
            <div class="card-body pt-8">
                
                <table width="100%" style="border-collapse: collapse; margin-bottom: 30px; border-bottom: 2px solid #EEE;">
                    <tr>
                        <td width="20%" style="vertical-align: middle; padding-bottom: 15px;">
                            @if(isset($configNota) && $configNota->logo)
                                <img src="{{ asset('logos/' . $configNota->logo) }}" 
                                     alt="Logo Empresa" 
                                     style="max-height: 85px; max-width: 100%; object-fit: contain;">
                            @else
                                <div style="width: 120px; height: 60px; border: 1px dashed #CCC; display: flex; align-items: center; justify-content: center; color: #AAA; font-size: 10px;">
                                    Sua Logo
                                </div>
                            @endif
                        </td>

                        <td width="80%" style="vertical-align: middle; text-align: center; padding-bottom: 15px; padding-right: 10%;">
                            <h1 class="font-weight-bolder text-dark text-uppercase mb-2" style="font-size: 26px; margin: 0;">
                                {{ $title }}
                            </h1>
                            <h4 class="text-muted font-weight-bold" style="margin: 5px 0 0 0; font-size: 16px;">
                                Período: {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($dataFim)->format('d/m/Y') }}
                            </h4>
                        </td>
                    </tr>
                </table>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-striped">
                        <thead class="text-center" style="background-color: #f3f6f9;">
                            <tr>
                                <th class="font-weight-bold text-dark">Veículo</th>
                                <th class="font-weight-bold text-dark">Status</th>
                                <th class="font-weight-bold text-dark" title="Situação da Manutenção">Mnt.</th> <th class="font-weight-bold text-dark">KM Rodado</th>
                                <th class="font-weight-bold text-dark">Média (Km/L)</th>
                                <th class="font-weight-bold text-dark">Manutenção (R$)</th>
                                <th class="font-weight-bold text-dark">Combustível (R$)</th>
                                <th class="font-weight-bold text-dark">Arla (R$)</th>
                                <th class="font-weight-bold text-dark">Total Gasto (R$)</th>
                                <th class="font-weight-bold text-dark">Custo/KM (R$)</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @forelse($dadosRelatorio as $linha)
                                <tr>
                                    <td class="font-weight-bold text-left align-middle">
                                        {{ $linha->placa }} <br> 
                                        <span class="text-muted font-size-sm">{{ Str::limit($linha->modelo, 15) }}</span>
                                    </td>

                                    <td class="align-middle">
                                        <span class="badge badge-{{ $linha->status == 'Em Viagem' ? 'warning' : 'success' }} no-print">
                                            {{ $linha->status }}
                                        </span>
                                        <span class="d-none d-print-block font-weight-bold">{{ $linha->status }}</span>
                                    </td>

                                    <td class="align-middle">
                                        @if(isset($linha->manutencao_nivel))
                                            @if($linha->manutencao_nivel == 'vencido')
                                                <span class="badge badge-danger" title="Manutenção Vencida">V</span>
                                            @elseif($linha->manutencao_nivel == 'alerta')
                                                <span class="badge badge-warning" title="Revisão Próxima">A</span>
                                            @else
                                                <span class="badge badge-success" title="Manutenção em Dia">OK</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="align-middle">{{ number_format($linha->km_rodado, 0, ',', '.') }}</td>
                                    
                                    <td class="text-info font-weight-bold align-middle">
                                        {{ number_format($linha->media_kml, 2, ',', '.') }}
                                    </td>

                                    <td class="align-middle">{{ number_format($linha->custo_manutencao, 2, ',', '.') }}</td>
                                    <td class="align-middle">{{ number_format($linha->custo_combustivel, 2, ',', '.') }}</td>
                                    <td class="align-middle">{{ number_format($linha->custo_arla, 2, ',', '.') }}</td>
                                    <td class="text-danger font-weight-bold align-middle">{{ number_format($linha->custo_total, 2, ',', '.') }}</td>
                                    <td class="text-dark font-weight-bold align-middle">{{ number_format($linha->custo_por_km, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-5 font-weight-bold">
                                        Nenhum dado encontrado para este período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="text-center font-weight-bolder" style="background-color: #e4e6ef;">
                            <tr>
                                <td colspan="3" class="text-right text-dark">TOTAIS DA FROTA:</td> <td class="text-dark">{{ number_format($totaisGerais['km'], 0, ',', '.') }}</td>
                                <td>-</td>
                                <td class="text-dark">{{ number_format($totaisGerais['manutencao'], 2, ',', '.') }}</td>
                                <td class="text-dark">{{ number_format($totaisGerais['combustivel'], 2, ',', '.') }}</td>
                                <td class="text-dark">{{ number_format($totaisGerais['arla'], 2, ',', '.') }}</td>
                                <td class="text-danger font-size-h6">{{ number_format($totaisGerais['geral'], 2, ',', '.') }}</td>
                                <td>-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="row mt-10 d-none d-print-flex">
                    <div class="col-12 text-center mt-10">
                        <hr style="width: 300px; border-top: 1px solid #000;">
                        <span class="font-weight-bold">Visto do Gestor de Frota</span>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection