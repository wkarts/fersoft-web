{{-- Operational dashboard: ALL numbers and filters come from the existing controller. --}}
@php
    $localPadraoAtual = $localPadraoAtual ?? '';
    $uiUnitName = (string) $localPadraoAtual === 'matriz' ? 'Matriz (Sede)'
        : (optional($filiais->firstWhere('id', (int) $localPadraoAtual))->nome ?? ('Filial #' . $localPadraoAtual));
    $uiProductsMax = max(1, (float) $produtosMaisVendidos->max('total_qtd'));
    $uiClientsMax = max(1, (float) $topClientes->max('total'));
@endphp
<div class="ui-dashboard">
    <header class="ui-page-heading">
        <div>
            <p class="ui-eyebrow">Visão geral / Gestão executiva</p>
            <h1>Dashboard Analítico Executivo</h1>
            <p class="ui-subtitle">Indicadores operacionais, estoque e movimentações da empresa selecionada.</p>
            <div class="ui-filter-summary">
                <span class="ui-chip"><i class="la la-filter" aria-hidden="true"></i>{{ $diasDesc }}</span>
                @if(!empty($localPadraoAtual))<span>Local padrão atual: <strong>{{ $uiUnitName }}</strong></span>@endif
            </div>
        </div>
        <form action="{{ route('dashboard.analitico') }}" method="GET" class="ui-filter-row" aria-label="Filtros do dashboard">
            <label>Unidade
                <select name="filial_id" onchange="this.form.submit()">
                    <option value="todos" {{ $filial_id == 'todos' ? 'selected' : '' }}>Todas as Unidades (Consolidado)</option>
                    <option value="matriz" {{ $filial_id == 'matriz' ? 'selected' : '' }}>Matriz (Sede)</option>
                    @foreach($filiais as $f)<option value="{{ $f->id }}" {{ (string)$filial_id === (string)$f->id ? 'selected' : '' }}>{{ $f->nome }}</option>@endforeach
                </select>
            </label>
            <label>Período
                <select name="periodo" onchange="this.form.submit()">
                    @foreach(['hoje' => 'Hoje', 'mes' => 'Mês Atual', '7' => 'Últimos 7 dias', '30' => 'Últimos 30 dias', '90' => 'Últimos 90 dias'] as $key => $label)
                        <option value="{{ $key }}" {{ (string)$periodo === (string)$key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <noscript><button type="submit" class="btn btn-primary">Aplicar filtros</button></noscript>
        </form>
    </header>
    <section class="ui-metrics" aria-label="Indicadores">
        <article class="ui-metric">
            <div class="ui-metric-label">Base de clientes<span class="ui-widget-icon"><i class="la la-users" aria-hidden="true"></i></span></div>
            <strong class="ui-metric-value">{{ $totalClientes }}</strong><p class="ui-metric-note">Clientes cadastrados</p>
        </article>
        <article class="ui-metric">
            <div class="ui-metric-label">Total de produtos<span class="ui-widget-icon"><i class="la la-cube" aria-hidden="true"></i></span></div>
            <strong class="ui-metric-value">{{ $totalProdutos }}</strong><p class="ui-metric-note">Itens do catálogo</p>
        </article>
        <article class="ui-metric">
            <div class="ui-metric-label">Faturamento<span class="ui-widget-icon"><i class="la la-line-chart" aria-hidden="true"></i></span></div>
            <strong class="ui-metric-value ui-amount">R$ {{ number_format($totalFaturamento, 2, ',', '.') }}</strong>
            <div class="ui-metric-ticket"><span>Ticket médio</span><strong>R$ {{ number_format($ticketMedio, 2, ',', '.') }}</strong></div>
        </article>
        <article class="ui-metric">
            <div class="ui-metric-label">Custo de estoque<span class="ui-widget-icon"><i class="la la-cubes" aria-hidden="true"></i></span></div>
            <strong class="ui-metric-value">R$ {{ number_format($custoEstoque, 2, ',', '.') }}</strong><p class="ui-metric-note">Investimento total em mercadorias</p>
        </article>
    </section>
    <div class="ui-dashboard-columns">
        <section class="ui-panel" aria-labelledby="ui-products-chart-title">
            <div class="ui-panel-heading"><span class="ui-widget-icon"><i class="la la-line-chart" aria-hidden="true"></i></span><h2 id="ui-products-chart-title">Top 5 produtos vendidos</h2><small>Quantidade</small></div>
            @if($produtosMaisVendidos->isEmpty())
                <div class="ui-chart-empty" id="chart-produtos"><div><i class="la la-line-chart" aria-hidden="true"></i><strong>Sem dados para exibição</strong><span class="ui-subtitle">Nenhuma série disponível para este período.</span></div></div>
            @else
                <ol class="ui-chart-bars" id="chart-produtos" aria-label="Quantidades vendidas">
                    @foreach($produtosMaisVendidos as $item)
                        <li><div class="ui-bar-label"><span title="{{ $item->nome }}">{{ $item->nome }}</span><strong>{{ $item->total_qtd }}</strong></div><div class="ui-bar-track" aria-hidden="true"><div class="ui-bar" style="width:{{ min(100, max(0, (float) $item->total_qtd / $uiProductsMax * 100)) }}%"></div></div></li>
                    @endforeach
                </ol>
            @endif
        </section>
        <section class="ui-panel" aria-labelledby="ui-clients-chart-title">
            <div class="ui-panel-heading"><span class="ui-widget-icon"><i class="la la-users" aria-hidden="true"></i></span><h2 id="ui-clients-chart-title">Top 5 clientes</h2><small>Faturamento</small></div>
            @if($topClientes->isEmpty())
                <div class="ui-chart-empty" id="chart-clientes"><div><i class="la la-line-chart" aria-hidden="true"></i><strong>Sem dados para exibição</strong><span class="ui-subtitle">Nenhuma série disponível para este período.</span></div></div>
            @else
                <ol class="ui-chart-bars" id="chart-clientes" aria-label="Faturamento por cliente">
                    @foreach($topClientes as $item)
                        <li><div class="ui-bar-label"><span title="{{ $item->nome }}">{{ $item->nome }}</span><strong>R$ {{ number_format($item->total, 2, ',', '.') }}</strong></div><div class="ui-bar-track" aria-hidden="true"><div class="ui-bar" style="width:{{ min(100, max(0, (float) $item->total / $uiClientsMax * 100)) }}%"></div></div></li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
    <div class="ui-dashboard-columns">
        <section class="ui-panel" aria-labelledby="ui-stock-title">
            <div class="ui-panel-heading"><span class="ui-widget-icon"><i class="la la-cube" aria-hidden="true"></i></span><h2 id="ui-stock-title">Produtos com saldo</h2><span class="ui-chip">{{ count($produtosComEstoque) }} itens</span></div>
            <div class="ui-data-scroll" tabindex="0" role="region" aria-label="Produtos com saldo, tabela rolável">
                <table class="ui-data-table"><thead><tr><th scope="col">Produto</th><th scope="col" class="ui-number">Saldo</th><th scope="col" class="ui-number">Valor de venda</th></tr></thead><tbody>
                    @forelse($produtosComEstoque as $pe)
                        <tr><td title="{{ $pe->nome }}">{{ Str::limit($pe->nome, 30) }}</td><td class="ui-number ui-amount">{{ number_format($pe->quantidade, 0) }} {{ $pe->unidade_venda }}</td><td class="ui-number ui-amount">R$ {{ number_format($pe->valor_venda, 2, ',', '.') }}</td></tr>
                    @empty<tr><td colspan="3" class="ui-empty">Nenhum produto com saldo nesta unidade.</td></tr>@endforelse
                </tbody></table>
            </div>
        </section>
        <section class="ui-panel" aria-labelledby="ui-stock-alert-title">
            <div class="ui-panel-heading"><span class="ui-widget-icon"><i class="la la-warning" aria-hidden="true"></i></span><h2 id="ui-stock-alert-title">Alerta de compras <small>(reposição)</small></h2><span class="ui-chip ui-chip-danger">{{ count($produtosAlerta) }} {{ count($produtosAlerta) === 1 ? 'alerta' : 'alertas' }}</span></div>
            <div class="ui-data-scroll" tabindex="0" role="region" aria-label="Alertas de reposição">
                <table class="ui-data-table"><thead><tr><th scope="col">Produto</th><th scope="col" class="ui-number">Estoque atual</th></tr></thead><tbody>
                    @forelse($produtosAlerta as $pa)
                        <tr><td title="{{ $pa->nome }}">{{ Str::limit($pa->nome, 35) }}</td><td class="ui-number {{ $pa->quantidade <= 0 ? 'ui-danger' : 'ui-warn' }}">{{ number_format($pa->quantidade, 0) }} {{ $pa->unidade_venda }}</td></tr>
                    @empty<tr><td colspan="2" class="ui-empty">Estoque abastecido!</td></tr>@endforelse
                </tbody></table>
            </div>
        </section>
    </div>
    <div class="ui-finance-grid">
        @foreach([
            ['title' => 'Contas a pagar', 'path' => '/contasPagar', 'overdue' => $pagarVencidas, 'due' => $pagarNoPeriodo, 'label' => 'A pagar (30 dias)', 'icon' => 'la-arrow-down'],
            ['title' => 'Contas a receber', 'path' => '/contasReceber', 'overdue' => $receberVencidas, 'due' => $receberNoPeriodo, 'label' => 'A receber (30 dias)', 'icon' => 'la-arrow-up'],
        ] as $finance)
            <section class="ui-panel">
                <div class="ui-panel-heading"><span class="ui-widget-icon"><i class="la {{ $finance['icon'] }}" aria-hidden="true"></i></span><h2>{{ $finance['title'] }}</h2></div>
                <a class="ui-finance-link" href="{{ url($finance['path']) . '?' . http_build_query(['status' => 'vencido', 'filial_id' => $filial_id, 'periodo' => $periodo]) }}"><span>Vencidas</span><strong class="ui-danger">R$ {{ number_format($finance['overdue'], 2, ',', '.') }}</strong></a>
                <a class="ui-finance-link" href="{{ url($finance['path']) . '?' . http_build_query(['status' => 'periodo', 'filial_id' => $filial_id, 'periodo' => $periodo]) }}"><span>{{ $finance['label'] }}</span><strong class="ui-amount">R$ {{ number_format($finance['due'], 2, ',', '.') }}</strong></a>
            </section>
        @endforeach
        <section class="ui-panel">
            <div class="ui-panel-heading"><span class="ui-widget-icon"><i class="la la-university" aria-hidden="true"></i></span><h2>Saldos bancários</h2></div>
            <table class="ui-data-table"><tbody>
                @forelse($saldosContas as $s)
                    <tr><th scope="row">{{ $s->nome }}</th><td class="ui-number {{ $s->saldo < 0 ? 'ui-danger' : 'ui-amount' }}">R$ {{ number_format($s->saldo, 2, ',', '.') }}</td></tr>
                @empty<tr><td colspan="2" class="ui-empty">Nenhuma conta cadastrada.</td></tr>@endforelse
            </tbody></table>
        </section>
    </div>
</div>
