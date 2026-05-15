@extends('default.layout')
@section('content')
<div class="container-fluid">
    <div class="no-print">
        <div class="row pt-3 mb-2">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h2 class="text-dark font-weight-bold"><i class="fas fa-chart-line text-info"></i> Painel de Faturamento</h2>
                <a href="{{ url('faturamento/pdf') }}?tipo_periodo={{ request('tipo_periodo', 'anual') }}&ano={{ request('ano', date('Y')) }}&filial_id={{ request('filial_id') }}" target="_blank" class="btn btn-primary shadow-sm font-weight-bold">
                    <i class="fas fa-file-pdf"></i> Gerar Declaração Oficial PDF
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-gradient-info mb-3">
                    <div class="card-body text-center py-4">
                        <h6 class="text-uppercase mb-2" style="opacity: 0.8;">Bruto Total</h6>
                        <h2 class="font-weight-bold mb-0">R$ {{ number_format($faturamentoBruto, 2, ',', '.') }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-gradient-danger mb-3">
                    <div class="card-body text-center py-4">
                        <h6 class="text-uppercase mb-2" style="opacity: 0.8;">Devoluções</h6>
                        <h2 class="font-weight-bold mb-0">R$ {{ number_format($devolucoes, 2, ',', '.') }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-0 bg-gradient-success mb-3">
                    <div class="card-body text-center py-4">
                        <h6 class="text-uppercase mb-2" style="opacity: 0.8;">Líquido</h6>
                        <h2 class="font-weight-bold mb-0">R$ {{ number_format($faturamentoLiquido, 2, ',', '.') }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow border-0 mb-4">
            <div class="card-body">
                <form action="{{ url('/faturamento') }}" method="GET" class="row mb-4 border-bottom pb-3">
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-muted">PERÍODO</label>
                        <select name="tipo_periodo" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="anual" {{ $tipo_periodo == 'anual' ? 'selected' : '' }}>Ano Calendário</option>
                            <option value="12_meses" {{ $tipo_periodo == '12_meses' ? 'selected' : '' }}>Últimos 12 Meses</option>
                        </select>
                    </div>
                    @if($tipo_periodo == 'anual')
                    <div class="col-md-2">
                        <label class="small font-weight-bold text-muted">ANO</label>
                        <select name="ano" class="form-control form-control-sm" onchange="this.form.submit()">
                            @for($i = date('Y'); $i >= 2024; $i--)
                                <option value="{{ $i }}" {{ $ano == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-muted">UNIDADE</label>
                        <select name="filial_id" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">Consolidado</option>
                            <option value="matriz" {{ $filtro_filial == 'matriz' ? 'selected' : '' }}>Matriz</option>
                            @foreach($filiais as $f)
                                <option value="{{ $f->id }}" {{ $filtro_filial == $f->id ? 'selected' : '' }}>{{ $f->descricao }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                <div style="position: relative; height:300px; width:100%">
                    <canvas id="faturamentoChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="only-print declaration-page">
        <div class="row border-bottom pb-3 mb-4">
            <div class="col-8">
                <h4 class="font-weight-bold mb-0">WELINGTON MANOEL ALELUIA</h4>
                <p class="mb-0 text-uppercase small font-weight-bold text-primary">Welcont - Serviços Contábeis e Escritório Virtual</p>
                <p class="mb-0 small">R TORQUATO BAHIA, nº 4, SL 906 - COMÉRCIO | (71) 3243-0351</p>
                <p class="mb-0 small">CEP: 40015-110 - SALVADOR/BA</p>
            </div>
            <div class="col-4 text-right">
                <p class="mb-0 small font-weight-bold">CRC: BA-018245/O</p>
                <p class="mb-0 small">CNPJ: 41.272.185/0001-49</p>
            </div>
        </div>

        <div class="text-center mb-4">
            <h3 class="font-weight-bold" style="text-decoration: underline;">DEMONSTRATIVO DE FATURAMENTO</h3>
        </div>

        <div class="body-text mb-4" style="line-height: 1.6;">
            <p>Declaro para fins de direito que a empresa qualificada abaixo obteve os seguintes faturamentos demonstrados abaixo:</p>
            
            <div class="p-3 bg-light border mb-4">
                <div class="row">
                    <div class="col-12"><strong>Empresa:</strong> INTERNACIONAL COM E EXPORTACAO DE SUCATAS METALICAS LTDA</div>
                    <div class="col-6"><strong>CNPJ:</strong> 33.172.567/0001-02</div>
                    <div class="col-6"><strong>I.E:</strong> 156.761.059</div>
                    <div class="col-12 text-truncate"><strong>Endereço:</strong> RUA DOUTOR MARIO AUGUSTO TEIXEIRA DE FREITAS, nº 392, GALPAO 34 - MASSARANDUBA</div>
                </div>
            </div>

            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr class="text-center">
                        <th width="50%">MÊS / ANO</th>
                        <th width="50%">VALOR FATURADO (R$)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($labels as $index => $mesAno)
                    <tr>
                        <td class="text-center text-uppercase">{{ $mesAno }}</td>
                        <td class="text-right pr-4">{{ number_format($valores[$index], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="font-weight-bold bg-light">
                        <td class="text-center text-uppercase">TOTAL ACUMULADO NO PERÍODO</td>
                        <td class="text-right pr-4">R$ {{ number_format($faturamentoBruto, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer-area mt-5 pt-4 text-center">
            <p>Por ser verdade, passo e firmo a presente declaração.</p>
            <p class="mb-5">SALVADOR/BA, {{ date('d') }} de {{ \Carbon\Carbon::now()->locale('pt_BR')->monthName }} de {{ date('Y') }}.</p>
            
            <div class="row mt-5">
                <div class="col-6">
                    <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto;"></div>
                    <p class="small text-uppercase">Responsável pela Empresa</p>
                </div>
                <div class="col-6">
                    <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto;"></div>
                    <p class="small text-uppercase">Contador Responsável</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var ctx = document.getElementById('faturamentoChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($labels) !!},
                datasets: [{
                    label: 'Vendas Brutas',
                    data: {!! json_encode($valores) !!},
                    backgroundColor: 'rgba(23, 162, 184, 0.7)',
                    borderColor: '#17a2b8',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    });
</script>

<style>
    /* Estilos de tela */
    .bg-gradient-info { background: linear-gradient(45deg, #17a2b8, #117a8b) !important; color: white; }
    .bg-gradient-danger { background: linear-gradient(45deg, #dc3545, #bd2130) !important; color: white; }
    .bg-gradient-success { background: linear-gradient(45deg, #28a745, #1e7e34) !important; color: white; }
    
    .only-print { display: none; }
    
    @media print {
        .no-print, .main-sidebar, .main-footer, .breadcrumb, .nav, .form-control { display: none !important; }
        .content-wrapper { margin-left: 0 !important; padding: 0 !important; border: none !important; background: white !important; }
        .only-print { display: block !important; padding: 20px; font-family: Arial, sans-serif; color: #000; }
        .table-bordered th, .table-bordered td { border: 1px solid #333 !important; padding: 8px !important; }
        body { background: white !important; }
        .bg-light { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
    }
</style>
@endsection