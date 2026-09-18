@extends('default.layout')
@section('content')
<div class="container-fluid">
    
    {{-- CABEÇALHO E BOTÕES DE AÇÃO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0 text-dark fw-bold">{{ $title }}</h2>
        
        <div class="btn-group shadow-sm">
            <a href="{{ route('estoque.index', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger" title="Exportar para PDF">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <a href="{{ route('estoque.index', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success" title="Exportar para Excel">
                <i class="fas fa-file-excel"></i> Excel
            </a>
            <form action="{{ route('estoque.sincronizar') }}" method="POST" class="m-0" onsubmit="return confirm('Deseja puxar todo o histórico de pesagens concluídas para o estoque?');">
                @csrf
                <button type="submit" class="btn btn-warning" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                    <i class="fas fa-sync-alt"></i> Sincronizar Histórico
                </button>
            </form>
        </div>
    </div>

    {{-- FILTROS DE BUSCA MELHORADOS --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body pb-0"> {{-- pb-0 tira o excesso de margem embaixo --}}
            <form action="{{ route('estoque.index') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold mb-1">Data Inicial</label>
                        <input type="date" name="data_inicial" class="form-control" value="{{ $dataInicial }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold mb-1">Data Final</label>
                        <input type="date" name="data_final" class="form-control" value="{{ $dataFinal }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold mb-1">Produto</label>
                        <select name="produto_id" class="form-control">
                            <option value="">Todos os Produtos</option>
                            @foreach($produtos as $produto)
                                <option value="{{ $produto->id }}" {{ request('produto_id') == $produto->id ? 'selected' : '' }}>
                                    {{ $produto->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold mb-1">Fornecedor / Cliente</label>
                        <input type="text" name="parceiro_nome" class="form-control" placeholder="Buscar por nome..." value="{{ request('parceiro_nome') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label fw-bold mb-1">Tipo Movimento</label>
                        <select name="tipo" class="form-control">
                            <option value="">Entradas e Saídas</option>
                            <option value="entrada" {{ request('tipo') == 'entrada' ? 'selected' : '' }}>Apenas Entradas</option>
                            <option value="saida" {{ request('tipo') == 'saida' ? 'selected' : '' }}>Apenas Saídas</option>
                        </select>
                    </div>
                    <div class="col-md-1 mb-3">
                        <button type="submit" class="btn btn-primary w-100 shadow-sm">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- CARDS TOTALIZADORES GERAIS --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <h6 class="card-title text-uppercase mb-1">Entradas no Período</h6>
                    <h3 class="m-0 fw-bold">{{ number_format($totais['entradas_kg'] ?? 0, 2, ',', '.') }} <small class="fs-6">KG</small></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <h6 class="card-title text-uppercase mb-1">Saídas no Período</h6>
                    <h3 class="m-0 fw-bold">{{ number_format($totais['saidas_kg'] ?? 0, 2, ',', '.') }} <small class="fs-6">KG</small></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <h6 class="card-title text-uppercase mb-1">Saldo Atual (Físico)</h6>
                    <h3 class="m-0 fw-bold">{{ number_format($totais['saldo_kg'] ?? 0, 2, ',', '.') }} <small class="fs-6">KG</small></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <h6 class="card-title text-uppercase mb-1">Valor do Estoque Atual</h6>
                    <h3 class="m-0 fw-bold"><small class="fs-6">R$</small> {{ number_format($totais['valor_patrimonio'] ?? 0, 2, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- NAVEGAÇÃO DAS ABAS --}}
    <ul class="nav nav-tabs mb-3" id="estoqueTabs">
        <li class="nav-item">
            <a class="nav-link active fw-bold" id="aba-sintetico" href="javascript:void(0);" onclick="trocarAba('sintetico')">Visão Sintética (Resumo)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold" id="aba-fornecedores" href="javascript:void(0);" onclick="trocarAba('fornecedores')">Por Fornecedor / Cliente</a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold" id="aba-analitico" href="javascript:void(0);" onclick="trocarAba('analitico')">Visão Analítica (Extrato)</a>
        </li>
    </ul>

    {{-- CONTEÚDO DAS ABAS --}}
    <div id="conteudo-sintetico" style="display: block;">
        {{-- TABELA SINTÉTICA --}}
        <div class="card shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped table-bordered m-0 align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th class="text-start">Produto</th>
                            <th>Estoque Inicial</th>
                            <th>Entradas (KG)</th>
                            <th>Saídas (KG)</th>
                            <th>Saldo Atual</th>
                            <th>Preço Médio (R$/KG)</th>
                            <th>Valor Total (R$)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sintetico as $linha)
                            <tr>
                                <td class="fw-bold text-start">{{ $linha->produto_nome }}</td>
                                <td class="text-center text-secondary">{{ number_format($linha->estoque_inicial, 2, ',', '.') }}</td>
                                <td class="text-center text-success fw-bold">+ {{ number_format($linha->entradas_periodo, 2, ',', '.') }}</td>
                                <td class="text-center text-danger fw-bold">- {{ number_format($linha->saidas_periodo, 2, ',', '.') }}</td>
                                <td class="text-center fw-bold text-primary">{{ number_format($linha->saldo_atual, 2, ',', '.') }}</td>
                                <td class="text-end">R$ {{ number_format($linha->preco_medio, 4, ',', '.') }}</td>
                                <td class="text-end fw-bold">R$ {{ number_format($linha->valor_total_estoque, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Nenhum dado encontrado no resumo sintético.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="conteudo-analitico" style="display: none;">
        {{-- TABELA ANALÍTICA --}}
        <div class="card shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped m-0 align-middle" style="font-size: 0.9rem;">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Data</th>
                            <th>Pesagem (Ticket)</th>
                            <th>Tipo</th>
                            <th class="text-start">Fornecedor / Cliente</th>
                            <th class="text-start">Produto</th>
                            <th class="text-end">Peso Bruto</th>
                            <th class="text-end">Insumo/Impureza</th>
                            <th class="text-end">Peso Líquido</th>
                            <th class="text-end">Valor/KG</th>
                            <th class="text-end">Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($analitico as $linha)
                            <tr>
                                <td class="text-center">{{ date('d/m/Y', strtotime($linha->data_movimento)) }}</td>
                                <td class="text-center fw-bold">#{{ $linha->ticket_id ?? $linha->pesagem_id }}</td>
                                <td class="text-center">
                                    @if($linha->tipo == 'entrada')
                                        <span class="badge bg-success">Entrada</span>
                                    @else
                                        <span class="badge bg-danger">Saída</span>
                                    @endif
                                </td>
                                <td class="text-start text-truncate" style="max-width: 200px;" title="{{ $linha->fornecedor_nome ?? $linha->cliente_nome ?? 'Movimento Manual' }}">
                                    {{ $linha->fornecedor_nome ?? $linha->cliente_nome ?? 'Movimento Manual' }}
                                </td>
                                <td class="text-start fw-bold">{{ $linha->produto_nome }}</td>
                                <td class="text-end text-muted">{{ number_format($linha->peso_bruto, 2, ',', '.') }}</td>
                                <td class="text-end text-danger">{{ number_format($linha->peso_impureza, 2, ',', '.') }}</td>
                                <td class="text-end fw-bold text-primary">{{ number_format($linha->quantidade, 2, ',', '.') }}</td>
                                <td class="text-end">R$ {{ number_format($linha->valor_unitario, 4, ',', '.') }}</td>
                                <td class="text-end fw-bold">R$ {{ number_format($linha->valor_total, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">Nenhum registro detalhado encontrado neste período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    {{-- TOTALIZADORES NO FINAL DA TABELA ANALÍTICA --}}
                    @if(count($analitico) > 0)
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="5" class="text-end text-uppercase">Totais desta página:</td>
                            <td class="text-end">{{ number_format($analitico->sum('peso_bruto'), 2, ',', '.') }}</td>
                            <td class="text-end text-danger">{{ number_format($analitico->sum('peso_impureza'), 2, ',', '.') }}</td>
                            <td class="text-end text-primary">{{ number_format($analitico->sum('quantidade'), 2, ',', '.') }}</td>
                            <td class="text-end">-</td>
                            <td class="text-end">R$ {{ number_format($analitico->sum('valor_total'), 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            <div class="card-footer bg-white border-0 pt-3">
                {{ $analitico->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
  	{{-- ABA POR FORNECEDOR / CLIENTE --}}
    <div id="conteudo-fornecedores" style="display: none;">
        <div class="card shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover m-0 align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th style="width: 40px;"></th> {{-- Coluna para a seta --}}
                            <th class="text-start">Fornecedor / Cliente</th>
                            <th class="text-end">Peso Bruto</th>
                            <th class="text-end">Impureza</th>
                            <th class="text-end">Peso Líquido</th>
                            <th class="text-end">Preço Médio</th>
                            <th class="text-end">Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resumoParceiros as $nome => $dados)
                            {{-- LINHA PRINCIPAL (TOTALIZADOR DO PARCEIRO) --}}
                            <tr data-bs-toggle="collapse" data-bs-target="#parceiro-{{ $loop->index }}" style="cursor: pointer;" class="fw-bold bg-light" title="Clique para ver os tickets">
                                <td class="text-center text-primary"><i class="fas fa-chevron-down"></i></td>
                                <td class="text-start text-uppercase">{{ $nome }}</td>
                                <td class="text-end text-secondary">{{ number_format($dados['peso_bruto'], 2, ',', '.') }}</td>
                                <td class="text-end text-danger">{{ number_format($dados['impureza'], 2, ',', '.') }}</td>
                                <td class="text-end text-primary">{{ number_format($dados['peso_liquido'], 2, ',', '.') }} kg</td>
                                <td class="text-end text-secondary">R$ {{ number_format($dados['preco_medio'], 4, ',', '.') }}</td>
                                <td class="text-end text-success">R$ {{ number_format($dados['valor_total'], 2, ',', '.') }}</td>
                            </tr>
                            
                            {{-- LINHA OCULTA (DETALHES/TICKETS) --}}
                            <tr>
                                <td colspan="7" class="p-0 border-0">
                                    <div class="collapse" id="parceiro-{{ $loop->index }}">
                                        <div class="p-3 bg-white border-bottom shadow-inner">
                                            <table class="table table-sm table-bordered m-0 align-middle" style="font-size: 0.85rem;">
                                                <thead class="table-secondary text-center">
                                                    <tr>
                                                        <th>Data</th>
                                                        <th>Ticket</th>
                                                        <th class="text-start">Produto</th>
                                                        <th class="text-end">Peso Bruto</th>
                                                        <th class="text-end">Impureza</th>
                                                        <th class="text-end">Peso Líq.</th>
                                                        <th class="text-end">R$/KG</th>
                                                        <th class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($dados['movimentos'] as $linha)
                                                        <tr>
                                                            <td class="text-center">{{ date('d/m/Y', strtotime($linha->data_movimento)) }}</td>
                                                            <td class="text-center fw-bold">#{{ $linha->ticket_id ?? $linha->pesagem_id }}</td>
                                                            <td class="text-start fw-bold">{{ $linha->produto_nome }}</td>
                                                            <td class="text-end text-muted">{{ number_format($linha->peso_bruto, 2, ',', '.') }}</td>
                                                            <td class="text-end text-danger">{{ number_format($linha->peso_impureza, 2, ',', '.') }}</td>
                                                            <td class="text-end text-primary fw-bold">{{ number_format($linha->quantidade, 2, ',', '.') }}</td>
                                                            <td class="text-end">R$ {{ number_format($linha->valor_unitario, 4, ',', '.') }}</td>
                                                            <td class="text-end fw-bold">R$ {{ number_format($linha->valor_total, 2, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Nenhum fornecedor/cliente movimentou estoque neste período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

{{-- SCRIPT INFALÍVEL PARA TROCA DE ABAS --}}
<script>
    function trocarAba(abaId) {
        // Lista de todas as abas
        const abas = ['sintetico', 'analitico', 'fornecedores'];
        
        abas.forEach(function(id) {
            // Remove a classe active dos botões
            document.getElementById('aba-' + id).classList.remove('active');
            // Esconde todas as divs de conteúdo
            document.getElementById('conteudo-' + id).style.display = 'none';
        });
        
        // Adiciona classe active e mostra a aba clicada
        document.getElementById('aba-' + abaId).classList.add('active');
        document.getElementById('conteudo-' + abaId).style.display = 'block';
    }
</script>
@endsection