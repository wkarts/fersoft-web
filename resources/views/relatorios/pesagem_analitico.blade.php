@extends('relatorios.default')

@section('content')
<style>
    :root {
        --cinza-borda: #e6e9ed;
        --cinza-texto: #4b5563;
        --azul: #2563eb;
        --verde: #16a34a;
        --amarelo: #f59e0b;
        --preto: #0f172a;
    }
    body { font-family: 'Inter', Arial, sans-serif; font-size: 12px; color: var(--cinza-texto); background: #f8fafc; }
    h2 { margin: 0 0 4px 0; font-weight: 700; color: var(--preto); letter-spacing: -0.01em; }
    .sub { margin: 0 0 6px 0; font-size: 12px; color: #6b7280; }
    .meta { margin-bottom: 14px; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .actions a { margin-left: 8px; }
    .btn { padding: 8px 12px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; }
    .btn-outline { border-color: var(--azul); color: var(--azul); background: #fff; }
    .btn-primary { background: var(--azul); color: #fff; box-shadow: 0 10px 20px rgba(37,99,235,0.12); }
    .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .card { border: 1px solid var(--cinza-borda); border-radius: 12px; padding: 16px 18px; margin-bottom: 16px; box-shadow: 0 12px 32px rgba(15,23,42,0.06); background: #fff; }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px 14px; }
    .table { width: 100%; border-collapse: collapse; margin-top: 12px; border-radius: 8px; overflow: hidden; }
    .table th, .table td { border: 1px solid #edf0f4; padding: 8px; text-align: left; }
    .table th { background: #f8fafc; font-size: 12px; text-transform: uppercase; letter-spacing: 0.02em; color: #374151; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 10px; color: #fff; letter-spacing: 0.04em; }
    .badge-compra { background: var(--azul); }
    .badge-venda { background: var(--verde); }
    .badge-avulsa { background: var(--amarelo); color: #111827; }
    .badge-status { background: #111827; }
    .totais { border: 1px solid var(--cinza-borda); border-radius: 10px; padding: 12px 14px; background: linear-gradient(120deg, #f8fafc, #eef2ff); box-shadow: 0 6px 16px rgba(0,0,0,0.03); }
    .stat { font-weight: 700; color: var(--preto); font-size: 15px; }
    .stat-label { color: #6b7280; font-size: 12px; display: block; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.04em; }
    .nota-tag { padding: 6px 10px; background: #f1f5f9; border-radius: 8px; display: inline-block; margin-top: 4px; color: #0f172a; }
    .empty { padding: 20px; text-align: center; color: #6b7280; background: #f8fafc; border: 1px dashed var(--cinza-borda); border-radius: 8px; }
    .filter-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .chip { background: #eef2ff; color: #312e81; padding: 6px 10px; border-radius: 999px; font-size: 11px; border: 1px solid #c7d2fe; }
    .resume-table { width: 100%; border-collapse: collapse; margin: 12px 0 20px; }
    .resume-table th, .resume-table td { border: 1px solid #e5e7eb; padding: 8px; font-size: 12px; text-align: left; }
    .resume-table th { background: #111827; color: #fff; letter-spacing: 0.04em; text-transform: uppercase; }
    .resume-table td { background: #f9fafb; }
</style>

<div class="card" style="border: none; padding: 20px;">
    <div class="meta">
        <div>
            <h2>{{ $title ?? 'Relatório Analítico de Pesagens' }}</h2>
            <p class="sub">Período: {{ $data_inicial && $data_final ? "$data_inicial até $data_final" : 'Todos' }} | Tipo: {{ $filtro_tipo ? ucfirst($filtro_tipo) : 'Todos' }} | Total de Pesagens: {{ $total_pesagens }}</p>

            <div class="filter-chips">
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
        <div class="actions">
            <a class="btn btn-outline" href="{{ $html_download_url }}" target="_blank">⬇ Baixar HTML</a>
            <a class="btn btn-primary" href="{{ $pdf_url }}" target="_blank">⬇ Baixar PDF</a>
        </div>
    </div>
</div>

<div class="cards">
    <div class="totais">
        <span class="stat">{{ number_format($total_liquido, 2, ',', '.') }} kg</span>
        <span class="stat-label">Peso Líquido</span>
    </div>
    <div class="totais">
        <span class="stat">{{ number_format($total_final, 2, ',', '.') }} kg</span>
        <span class="stat-label">Peso Final</span>
    </div>
    @if($configEmitente)
        <div class="totais">
            <span class="stat">{{ $configEmitente->razao_social }}</span>
            <span class="stat-label">CNPJ {{ $configEmitente->cnpj }}</span>
        </div>
    @endif
</div>

@if($totais_por_tipo && $totais_por_tipo->count())
    <table class="resume-table">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Qtd. Pesagens</th>
                <th>Peso Líquido</th>
                <th>Peso Final</th>
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
    <div class="card">
        <div class="card-header">
            <div>
                <strong>#{{ $item['id'] }}</strong> - {{ ucfirst($item['tipo']) }}
                <span class="badge badge-{{ $item['tipo'] === 'venda' ? 'venda' : ($item['tipo'] === 'compra' ? 'compra' : 'avulsa') }}">{{ strtoupper($item['tipo']) }}</span>
                <span class="badge badge-status">{{ ucfirst($item['status'] ?? 'em andamento') }}</span>
            </div>
            <div>{{ \Carbon\Carbon::parse($item['data'])->format('d/m/Y') }}</div>
        </div>

        <div class="grid">
            <div><small>Documento</small><br><strong>{{ $item['documento'] }}</strong></div>
            <div><small>NF-e vinculada</small><br>
                @if($item['nota_fiscal'])
                    <span class="nota-tag">{{ $item['nota_fiscal']['tipo'] }} nº {{ $item['nota_fiscal']['numero'] }}{{ $item['nota_fiscal']['serie'] ? ' / Série ' . $item['nota_fiscal']['serie'] : '' }} ({{ strtoupper($item['nota_fiscal']['estado'] ?? '-') }}{{ $item['nota_fiscal']['data'] ? ' - ' . \Carbon\Carbon::parse($item['nota_fiscal']['data'])->format('d/m/Y') : '' }})</span>
                @else
                    <span class="nota-tag">-</span>
                @endif
            </div>
            <div><small>Usuário</small><br><strong>{{ $item['usuario'] ?? '-' }}</strong></div>
            <div><small>Motorista</small><br><strong>{{ $item['motorista'] ?? '-' }}</strong></div>
            <div><small>Veículo</small><br><strong>{{ $item['veiculo'] ?? '-' }}</strong></div>
            <div><small>Cliente</small><br><strong>{{ $item['cliente'] ?? '-' }}</strong></div>
            <div><small>Fornecedor</small><br><strong>{{ $item['fornecedor'] ?? '-' }}</strong></div>
            <div><small>Peso Líquido</small><br><strong>{{ number_format($item['peso_liquido'], 2, ',', '.') }} kg</strong></div>
            <div><small>Peso Final</small><br><strong>{{ number_format($item['peso_final'], 2, ',', '.') }} kg</strong></div>
            <div><small>Abatimentos (%)</small><br><strong>{{ number_format(array_sum($item['abatimentos']), 2, ',', '.') }}%</strong></div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Tipo</th>
                    <th>Produto</th>
                    <th>Peso Bruto</th>
                    <th>Peso Sacaria</th>
                    <th>Peso Líquido</th>
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
@endsection
