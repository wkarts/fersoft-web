<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Relatório Analítico de Pesagens' }}</title>
    <style>
        @page { margin: 12mm 12mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #0f172a; margin: 0; padding: 0; background: #f5f7fb; }
        h1 { margin: 0; font-size: 20px; letter-spacing: -0.02em; }
        .page { padding: 12px; }
        .header { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; background: #0f172a; color: #fff; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; border: 1px solid #0ea5e9; }
        .header small { display: block; opacity: .9; }
        .filters { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px; }
        .chip { background: rgba(255,255,255,0.08); color: #e5e7eb; border: 1px solid rgba(255,255,255,0.14); padding: 5px 9px; border-radius: 999px; font-size: 10px; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 12px; }
        .summary-card { border-radius: 10px; padding: 12px; background: #fff; border: 1px solid #e5e7eb; box-shadow: 0 10px 26px rgba(15,23,42,0.08); }
        .summary-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 2px; display: block; }
        .summary-value { font-size: 19px; font-weight: 700; color: #0f172a; }
        .summary-accent { height: 4px; border-radius: 999px; background: linear-gradient(90deg, #0ea5e9, #2563eb); margin-top: 8px; }
        .resume-table { width: 100%; border-collapse: collapse; margin: 0 0 14px; font-size: 10px; }
        .resume-table th, .resume-table td { border: 1px solid #d9e0e7; padding: 7px 8px; text-align: left; }
        .resume-table th { background: #0f172a; color: #fff; letter-spacing: 0.05em; text-transform: uppercase; }
        .resume-table td { background: #fff; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #2563eb; border-radius: 12px; margin-bottom: 12px; box-shadow: 0 10px 26px rgba(15,23,42,0.06); padding: 0; overflow: hidden; break-inside: avoid-page; page-break-inside: avoid; }
        .card-header { display: flex; justify-content: space-between; gap: 8px; align-items: center; padding: 10px 12px; background: #f1f5f9; color: #0f172a; border-bottom: 1px solid #e2e8f0; }
        .card-header .left { display: flex; align-items: center; gap: 8px; }
        .card-title { font-weight: 700; font-size: 13px; margin: 0; }
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; border: 1px solid #e2e8f0; }
        .badge-compra { background: #e0f2fe; color: #0f172a; border-color: #bae6fd; }
        .badge-venda { background: #dcfce7; color: #0f172a; border-color: #bbf7d0; }
        .badge-avulsa { background: #fef9c3; color: #854d0e; border-color: #fde68a; }
        .badge-status { background: #0f172a; color: #fff; border-color: #0f172a; }
        .bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); background: #0b6bb5; color: #e0f2fe; padding: 8px 10px; gap: 6px; border-bottom: 1px solid #dfe6ef; }
        .bar.secondary { background: #f8fafc; color: #0f172a; }
        .bar strong { font-size: 13px; }
        .bar small { display: block; text-transform: uppercase; letter-spacing: 0.05em; font-size: 10px; opacity: .9; }
        .info { padding: 10px 12px; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px; border-bottom: 1px solid #e5e7eb; background: #fff; }
        .info strong { color: #0f172a; }
        .info small { display: block; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px; font-size: 10px; }
        .tickets { width: 100%; border-collapse: collapse; font-size: 10px; }
        .tickets th, .tickets td { border: 1px solid #e5e7eb; padding: 7px 6px; }
        .tickets tr { break-inside: avoid-page; page-break-inside: avoid; }
        .tickets th { background: #f8fafc; text-transform: uppercase; letter-spacing: 0.04em; font-size: 10px; color: #0f172a; }
        .tickets td { background: #fff; }
        .empty { text-align: center; padding: 16px; border: 1px dashed #dfe6ef; border-radius: 10px; background: #f8fafc; color: #6b7280; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div>
            <h1>{{ $title ?? 'Relatório Analítico de Pesagens' }}</h1>
            <small>Período: {{ $data_inicial && $data_final ? "$data_inicial até $data_final" : 'Todos' }} · Tipo: {{ $filtro_tipo ? ucfirst($filtro_tipo) : 'Todos' }} · Total de Pesagens: {{ $total_pesagens }}</small>
            <div class="filters">
                @if($filtro_status)
                    <span class="chip">Status: {{ ucfirst($filtro_status) }}</span>
                @endif
                @if($filtro_nota)
                    <span class="chip">NF-e: {{ $filtro_nota === 'com' ? 'Com emissão' : 'Sem emissão' }}</span>
                @endif
                @if($filtro_cliente)
                    <span class="chip">Cliente: {{ $filtro_cliente->razao_social }}</span>
                @endif
                @if($filtro_fornecedor)
                    <span class="chip">Fornecedor: {{ $filtro_fornecedor->razao_social }}</span>
                @endif
            </div>
        </div>
        @if($configEmitente)
            <div style="text-align: right;">
                <strong>{{ $configEmitente->razao_social }}</strong>
                <small>CNPJ: {{ $configEmitente->cnpj }}</small>
                @if($configEmitente->uf)
                    <small>UF: {{ $configEmitente->uf }}</small>
                @endif
                @if($configEmitente->ie)
                    <small>IE: {{ $configEmitente->ie }}</small>
                @endif
            </div>
        @endif
    </div>

    <div class="summary">
        <div class="summary-card primary">
            <div class="summary-label">Peso Líquido Total</div>
            <div class="summary-value">{{ number_format($total_liquido, 2, ',', '.') }} kg</div>
            <div class="summary-accent"></div>
        </div>
        <div class="summary-card secondary">
            <div class="summary-label">Peso Final Líquido</div>
            <div class="summary-value">{{ number_format($total_final, 2, ',', '.') }} kg</div>
            <div class="summary-accent"></div>
        </div>
        <div class="summary-card dark">
            <div class="summary-label">Total de Pesagens</div>
            <div class="summary-value">{{ $total_pesagens }}</div>
            <div class="summary-accent"></div>
        </div>
    </div>

    @if($totais_por_tipo && $totais_por_tipo->count())
        <table class="resume-table">
            <thead>
            <tr>
                <th>Tipo</th>
                <th>Qtd.</th>
                <th>Peso Líquido Total</th>
                <th>Peso Final Líquido</th>
            </tr>
            </thead>
            <tbody>
            @foreach($totais_por_tipo as $tipo => $totais)
                <tr>
                    <td>{{ ucfirst($tipo) }}</td>
                    <td>{{ $totais['quantidade'] }}</td>
                    <td>{{ number_format($totais['peso_liquido'], 2, ',', '.') }} kg</td>
                    <td>{{ number_format($totais['peso_final'], 2, ',', '.') }} kg</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @forelse($relatorio as $item)
        @php
            // Consolidação exclusivamente visual: primeiro e último estado físico pela ordem real das leituras.
            $ticketsOrdenados = collect($item['tickets'] ?? [])->sortBy(function ($ticket) {
                $createdAt = $ticket['created_at'] ?? null;
                $timestamp = 0;
                if ($createdAt instanceof \Carbon\CarbonInterface) {
                    $timestamp = $createdAt->timestamp;
                } elseif ($createdAt) {
                    try {
                        $timestamp = \Carbon\Carbon::parse($createdAt)->timestamp;
                    } catch (\Throwable $e) {
                        $timestamp = 0;
                    }
                }
                return sprintf('%020d-%020d', $timestamp, (int) ($ticket['id'] ?? 0));
            })->values();

            $primeiroTicket = $ticketsOrdenados->first();
            $ultimoTicket = $ticketsOrdenados->last();
            $pesoInicialOperacao = $primeiroTicket ? (float) ($primeiroTicket['peso'] ?? 0) : null;
            $pesoFinalVeiculo = $ultimoTicket ? (float) ($ultimoTicket['peso'] ?? 0) : null;
        @endphp
        <div class="card">
            <div class="card-header">
                <div class="left">
                    <span class="card-title">#{{ $item['id'] }}</span>
                    <span class="badge badge-{{ $item['tipo'] === 'venda' ? 'venda' : ($item['tipo'] === 'compra' ? 'compra' : 'avulsa') }}">{{ strtoupper($item['tipo']) }}</span>
                    <span class="badge badge-status">{{ ucfirst($item['status'] ?? 'em andamento') }}</span>
                </div>
                <div>{{ \Carbon\Carbon::parse($item['data'])->format('d/m/Y H:i') }}</div>
            </div>

            <div class="bar">
                <div>
                    <small>Peso Inicial</small>
                    <strong>{{ $pesoInicialOperacao !== null ? number_format($pesoInicialOperacao, 2, ',', '.') . ' kg' : '-' }}</strong>
                </div>
                <div>
                    <small>Peso Final</small>
                    <strong>{{ $pesoFinalVeiculo !== null ? number_format($pesoFinalVeiculo, 2, ',', '.') . ' kg' : '-' }}</strong>
                </div>
                <div>
                    <small>Peso Líquido Total</small>
                    <strong>{{ number_format($item['peso_liquido'], 2, ',', '.') }} kg</strong>
                </div>
                <div>
                    <small>Peso Final Líquido</small>
                    <strong>{{ number_format($item['peso_final'], 2, ',', '.') }} kg</strong>
                </div>
                <div>
                    <small>Abatimentos</small>
                    <strong>{{ number_format(array_sum($item['abatimentos']), 2, ',', '.') }} %</strong>
                </div>
                <div>
                    <small>Documento</small>
                    <strong>{{ $item['documento'] }}</strong>
                </div>
            </div>

            <div class="bar secondary">
                <div>
                    <small>NF-e Vinculada</small>
                    @if($item['nota_fiscal'])
                        <strong>{{ $item['nota_fiscal']['tipo'] }} nº {{ $item['nota_fiscal']['numero'] }}{{ $item['nota_fiscal']['serie'] ? ' / Série ' . $item['nota_fiscal']['serie'] : '' }} ({{ strtoupper($item['nota_fiscal']['estado'] ?? '-') }}{{ $item['nota_fiscal']['data'] ? ' - ' . \Carbon\Carbon::parse($item['nota_fiscal']['data'])->format('d/m/Y') : '' }})</strong>
                    @else
                        <strong>-</strong>
                    @endif
                </div>
                <div>
                    <small>Usuário</small>
                    <strong>{{ $item['usuario'] ?? '-' }}</strong>
                </div>
                <div>
                    <small>Motorista</small>
                    <strong>{{ $item['motorista'] ?? '-' }}</strong>
                </div>
                <div>
                    <small>Veículo</small>
                    <strong>{{ $item['veiculo'] ?? '-' }}</strong>
                </div>
            </div>

            <div class="info">
                <div>
                    <small>Cliente</small>
                    <strong>{{ $item['cliente'] ?? '-' }}</strong>
                </div>
                <div>
                    <small>Fornecedor</small>
                    <strong>{{ $item['fornecedor'] ?? '-' }}</strong>
                </div>
                <div>
                    <small>Abatimentos Detalhados (%)</small>
                    <strong>
                        Umidade: {{ number_format($item['abatimentos']['umidade'], 2, ',', '.') }} ·
                        Impureza: {{ number_format($item['abatimentos']['impureza'], 2, ',', '.') }} ·
                        Danificado: {{ number_format($item['abatimentos']['danificado'], 2, ',', '.') }} ·
                        Quebrado: {{ number_format($item['abatimentos']['quebrado'], 2, ',', '.') }} ·
                        Esverdeado: {{ number_format($item['abatimentos']['esverdeado'], 2, ',', '.') }} ·
                        Ardido: {{ number_format($item['abatimentos']['ardido'], 2, ',', '.') }} ·
                        Secagem: {{ number_format($item['abatimentos']['secagem'], 2, ',', '.') }}
                    </strong>
                </div>
            </div>

            <table class="tickets">
                <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Tipo</th>
                    <th>Produto</th>
                    <th>Leitura</th>
                    <th>Recipiente</th>
                    <th>Leitura Ajustada</th>
                    <th>Registrado em</th>
                </tr>
                </thead>
                <tbody>
                @foreach($item['tickets'] as $ticket)
                    <tr>
                        <td>{{ $ticket['id'] }}</td>
                        <td>{{ ucfirst($ticket['tipo']) }}</td>
                        <td>{{ $ticket['produto'] ?? '-' }}</td>
                        <td>{{ number_format(max(0, $ticket['peso']), 2, ',', '.') }} kg</td>
                        <td>{{ number_format(max(0, $ticket['peso_bag']), 2, ',', '.') }} kg</td>
                        <td>{{ number_format($ticket['peso_liquido'], 2, ',', '.') }} kg</td>
                        <td>{{ optional($ticket['created_at'])->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="empty">Nenhuma pesagem encontrada para os filtros informados.</div>
    @endforelse
</div>
</body>
</html>
