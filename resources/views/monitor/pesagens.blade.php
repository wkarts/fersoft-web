@extends('default.layout')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .monitor-card {
            border-radius: 8px;
        }

        .monitor-table thead th {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1;
        }

        .monitor-table td,
        .monitor-table th {
            font-size: 12px;
            white-space: nowrap;
            padding: 8px 10px;
            vertical-align: middle;
        }

        @media (max-width: 992px) {
            .monitor-table td,
            .monitor-table th {
                font-size: 11px;
                padding: 6px 8px;
                white-space: normal;
            }
        }

        .monitor-badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 12px;
        }

        .monitor-row-finished {
            background-color: rgba(46, 204, 113, 0.08);
        }

        .monitor-row-updated {
            background-color: rgba(52, 152, 219, 0.06);
        }

        .monitor-feed {
            max-height: 560px;
            overflow-y: auto;
        }

        .monitor-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>

    <div class="card card-custom gutter-b monitor-card">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">Painel ao Vivo - Pesagens</h3>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-lg-3 d-flex">
                    <div class="card card-custom bg-light h-100 w-100">
                        <div class="card-body d-flex flex-column">
                            <div class="text-muted text-truncate">Total do dia (KG)</div>
                            <div class="font-size-h4 font-weight-bold text-nowrap" id="monitorTotalKg">
                                {{ number_format($resumo['total_kg'] ?? 0, 2, ',', '.') }} kg
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3 d-flex">
                    <div class="card card-custom bg-light h-100 w-100">
                        <div class="card-body d-flex flex-column">
                            <div class="text-muted text-truncate">Total do dia (R$)</div>
                            <div class="font-size-h4 font-weight-bold text-nowrap" id="monitorTotalValorVenda">
                                R$ {{ number_format($resumo['total_valor_venda'] ?? 0, 2, ',', '.') }}
                            </div>
                            <div class="text-muted mt-2 text-truncate">Vendas</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3 d-flex">
                    <div class="card card-custom bg-light h-100 w-100">
                        <div class="card-body d-flex flex-column">
                            <div class="text-muted text-truncate">Total do dia (R$)</div>
                            <div class="font-size-h4 font-weight-bold text-nowrap" id="monitorTotalValorCompra">
                                R$ {{ number_format($resumo['total_valor_compra'] ?? 0, 2, ',', '.') }}
                            </div>
                            <div class="text-muted mt-2 text-truncate">Compras</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3 d-flex">
                    <div class="card card-custom bg-light h-100 w-100">
                        <div class="card-body d-flex flex-column">
                            <div class="text-muted text-truncate">Pesagens hoje</div>
                            <div class="font-size-h4 font-weight-bold text-nowrap" id="monitorTotalPesagens">
                                {{ $resumo['total_pesagens'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row g-3 mb-4" id="contextCards"></div>

            <form id="monitorFiltros" class="form mb-4">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Data</label>
                        <input type="date" name="date" class="form-control" value="{{ $filtros['data']->format('Y-m-d') }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Filial</label>
                        <select name="filial_id" class="form-control">
                            <option value="">Todas</option>
                            @foreach($filiais as $filial)
                                <option value="{{ $filial->id }}" {{ (string)$filtros['filial_id'] === (string)$filial->id ? 'selected' : '' }}>
                                    {{ $filial->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Buscar fornecedor/motorista</label>
                        <input type="text" name="search" class="form-control" value="{{ $filtros['search'] }}" placeholder="Digite um nome">
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="la la-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>

            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tab-monitor-eventos" role="tab">Eventos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-monitor-produtos" role="tab">Produtos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-monitor-parceiros" role="tab">Fornecedor/Cliente</a>
                </li>
            </ul>

            <div class="tab-content mt-4">
                <div class="tab-pane fade show active" id="tab-monitor-eventos" role="tabpanel">
                    <div class="table-responsive monitor-feed monitor-table-wrapper">
                        <table class="table table-sm align-middle monitor-table text-nowrap" id="monitorTabela">
                            <thead class="thead-light">
                                <tr>
                                    <th>Hora</th>
                                    <th>Filial</th>
                                    <th>Ação</th>
                                    <th>ID</th>
                                    <th>Motorista</th>
                                    <th>Usuário</th>
                                    <th>Fornecedor/Cliente</th>
                                    <th>Materiais</th>
                                    <th>Peso Bruto</th>
                                    <th>Tara</th>
                                    <th>Bag</th>
                                    <th>Peso Líquido</th>
                                    <th>Peso Final</th>
                                    <th>Preço/KG</th>
                                    <th>Valor Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="monitorEventos">
                                @foreach($eventos as $evento)
                                    <tr data-pesagem-id="{{ $evento['id'] }}" class="{{ $evento['status'] === 'concluído' ? 'monitor-row-finished' : '' }}">
                                        <td>{{ $evento['timestamp'] }}</td>
                                        <td>{{ $evento['filial'] }}</td>
                                        <td>{{ $evento['acao'] }}</td>
                                        <td>#{{ $evento['id'] }}</td>
                                        <td>{{ $evento['motorista'] }}</td>
                                        <td>{{ $evento['usuario'] }}</td>
                                        <td>{{ $evento['fornecedor'] }}</td>
                                        <td>{{ $evento['produtos_resumo'] }}</td>
                                        <td>{{ number_format($evento['pesos']['bruto'] ?? 0, 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($evento['pesos']['tara'] ?? 0, 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($evento['pesos']['bag'] ?? 0, 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($evento['pesos']['liquido'] ?? 0, 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($evento['pesos']['final'] ?? 0, 2, ',', '.') }} kg</td>
                                        <td>
                                            @if(!is_null($evento['preco_kg']))
                                                R$ {{ number_format($evento['preco_kg'], 4, ',', '.') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if(!is_null($evento['valor_total']))
                                                R$ {{ number_format($evento['valor_total'], 2, ',', '.') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light monitor-badge">
                                                {{ ucfirst($evento['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-monitor-produtos" role="tabpanel">
                    <div class="table-responsive monitor-table-wrapper">
                        <table class="table table-sm align-middle monitor-table text-nowrap">
                            <thead class="thead-light">
                                <tr>
                                    <th>Produto</th>
                                    <th>Entrada Bruto (KG)</th>
                                    <th>Entrada Líquido (KG)</th>
                                    <th>Entrada Final (KG)</th>
                                    <th>Saída Bruto (KG)</th>
                                    <th>Saída Líquido (KG)</th>
                                    <th>Saída Final (KG)</th>
                                    <th>Total Final (KG)</th>
                                </tr>
                            </thead>
                            <tbody id="monitorProdutosBody">
                                @forelse($analiticoProdutos as $produto)
                                    <tr data-produto-id="{{ $produto['produto_id'] }}">
                                        <td>{{ $produto['produto_nome'] }}</td>
                                        <td>{{ number_format($produto['entrada_bruto'], 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($produto['entrada_liquido'], 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($produto['entrada_final'], 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($produto['saida_bruto'], 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($produto['saida_liquido'], 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($produto['saida_final'], 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($produto['total_final'], 2, ',', '.') }} kg</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Nenhum produto encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-monitor-parceiros" role="tabpanel">
                    <div class="table-responsive monitor-table-wrapper">
                        <table class="table table-sm align-middle monitor-table text-nowrap">
                            <thead class="thead-light">
                                <tr>
                                    <th>Fornecedor/Cliente</th>
                                    <th>Tipo</th>
                                    <th>Entrada (KG)</th>
                                    <th>Saída (KG)</th>
                                    <th>Total (KG)</th>
                                    <th>Pesagens</th>
                                </tr>
                            </thead>
                            <tbody id="monitorParceirosBody">
                                @forelse($analiticoParceiros as $parceiro)
                                    <tr data-parceiro-key="{{ $parceiro['parceiro_tipo'] }}-{{ $parceiro['parceiro_id'] ?? 'sem' }}">
                                        <td>{{ $parceiro['parceiro_nome'] }}</td>
                                        <td>{{ $parceiro['parceiro_tipo'] }}</td>
                                        <td>{{ number_format($parceiro['entrada'], 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($parceiro['saida'], 2, ',', '.') }} kg</td>
                                        <td>{{ number_format($parceiro['total'], 2, ',', '.') }} kg</td>
                                        <td>{{ $parceiro['total_pesagens'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Nenhum parceiro encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.0/dist/echo.iife.js"></script>
    <script>
        const monitorConfig = {
            empresaId: {{ $empresaId }},
            pusherKey: @json(config('broadcasting.connections.pusher.key')),
            pusherHost: @json(config('broadcasting.connections.pusher.options.host')),
            pusherPort: @json(config('broadcasting.connections.pusher.options.port')),
            pusherScheme: @json(config('broadcasting.connections.pusher.options.scheme')),
            pusherCluster: @json(config('broadcasting.connections.pusher.options.cluster')),
        };

        const monitorState = {
            channelName: null,
            limit: 200,
        };

        const monitorTableBody = document.getElementById('monitorEventos');
        const monitorForm = document.getElementById('monitorFiltros');
        const monitorTotalKg = document.getElementById('monitorTotalKg');
        const monitorTotalValorVenda = document.getElementById('monitorTotalValorVenda');
        const monitorTotalValorCompra = document.getElementById('monitorTotalValorCompra');
        const monitorTotalPesagens = document.getElementById('monitorTotalPesagens');
        const monitorProdutosBody = document.getElementById('monitorProdutosBody');
        const monitorParceirosBody = document.getElementById('monitorParceirosBody');
        const contextCards = document.getElementById('contextCards');

        const formatPeso = (valor) => `${Number(valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} kg`;
        const formatMoney = (valor, casas = 2) => `R$ ${Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: casas, maximumFractionDigits: casas })}`;

        const escapeHtml = (texto) => {
            if (texto === null || texto === undefined) {
                return '—';
            }
            return String(texto)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const montarLinha = (evento) => {
            const classeStatus = evento.status === 'concluído' ? 'monitor-row-finished' : '';
            return `
                <tr data-pesagem-id="${evento.id}" class="${classeStatus}">
                    <td>${escapeHtml(evento.timestamp)}</td>
                    <td>${escapeHtml(evento.filial)}</td>
                    <td>${escapeHtml(evento.acao)}</td>
                    <td>#${escapeHtml(evento.id)}</td>
                    <td>${escapeHtml(evento.motorista)}</td>
                    <td>${escapeHtml(evento.usuario)}</td>
                    <td>${escapeHtml(evento.fornecedor)}</td>
                    <td>${escapeHtml(evento.produtos_resumo)}</td>
                    <td>${formatPeso(evento.pesos?.bruto)}</td>
                    <td>${formatPeso(evento.pesos?.tara)}</td>
                    <td>${formatPeso(evento.pesos?.bag)}</td>
                    <td>${formatPeso(evento.pesos?.liquido)}</td>
                    <td>${formatPeso(evento.pesos?.final)}</td>
                    <td>${evento.preco_kg ? formatMoney(evento.preco_kg, 4) : '—'}</td>
                    <td>${evento.valor_total ? formatMoney(evento.valor_total, 2) : '—'}</td>
                    <td><span class="badge badge-light monitor-badge">${escapeHtml(evento.status)}</span></td>
                </tr>
            `;
        };

        const totaisContextoInicial = @json($totaisContexto ?? []);
        const analiticoProdutosInicial = @json($analiticoProdutos);
        const analiticoParceirosInicial = @json($analiticoParceiros);
        const produtoState = new Map();
        const parceiroState = new Map();
        let refreshTimeout = null;


        const renderContextCards = (totais) => {
            if (!contextCards) return;
            if (!Array.isArray(totais) || !totais.length) {
                contextCards.innerHTML = '<div class="col-12"><div class="alert alert-light">Sem dados de contexto para o dia.</div></div>';
                return;
            }

            contextCards.innerHTML = totais.map((item) => `
                <div class="col-12 col-md-6">
                    <div class="card card-custom bg-light h-100">
                        <div class="card-body">
                            <div class="font-weight-bold mb-2">Contexto: ${escapeHtml(item.contexto)}</div>
                            <div>Entrada: <strong>${formatPeso(item.entrada)}</strong></div>
                            <div>Saída: <strong>${formatPeso(item.saida)}</strong></div>
                            <div>Saldo: <strong>${formatPeso(item.saldo)}</strong></div>
                        </div>
                    </div>
                </div>
            `).join('');
        };

        const atualizarResumo = (resumo) => {
            if (!resumo) {
                return;
            }
            monitorTotalKg.textContent = `${Number(resumo.total_kg || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} kg`;
            monitorTotalValorVenda.textContent = formatMoney(resumo.total_valor_venda || 0, 2);
            monitorTotalValorCompra.textContent = formatMoney(resumo.total_valor_compra || 0, 2);
            monitorTotalPesagens.textContent = Number(resumo.total_pesagens || 0).toLocaleString('pt-BR');
        };

        const renderProdutos = () => {
            const itens = Array.from(produtoState.values()).sort((a, b) => (b.total_final || 0) - (a.total_final || 0));
            if (!itens.length) {
                monitorProdutosBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Nenhum produto encontrado.</td></tr>';
                return;
            }

            monitorProdutosBody.innerHTML = itens.map((item) => `
                <tr data-produto-id="${item.produto_id}">
                    <td>${escapeHtml(item.produto_nome)}</td>
                    <td>${formatPeso(item.entrada_bruto)}</td>
                    <td>${formatPeso(item.entrada_liquido)}</td>
                    <td>${formatPeso(item.entrada_final)}</td>
                    <td>${formatPeso(item.saida_bruto)}</td>
                    <td>${formatPeso(item.saida_liquido)}</td>
                    <td>${formatPeso(item.saida_final)}</td>
                    <td>${formatPeso(item.total_final)}</td>
                </tr>
            `).join('');
        };

        const renderParceiros = () => {
            const itens = Array.from(parceiroState.values()).sort((a, b) => (b.total || 0) - (a.total || 0));
            if (!itens.length) {
                monitorParceirosBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhum parceiro encontrado.</td></tr>';
                return;
            }

            monitorParceirosBody.innerHTML = itens.map((item) => `
                <tr data-parceiro-key="${item.parceiro_tipo}-${item.parceiro_id ?? 'sem'}">
                    <td>${escapeHtml(item.parceiro_nome)}</td>
                    <td>${escapeHtml(item.parceiro_tipo)}</td>
                    <td>${formatPeso(item.entrada)}</td>
                    <td>${formatPeso(item.saida)}</td>
                    <td>${formatPeso(item.total)}</td>
                    <td>${escapeHtml(item.total_pesagens)}</td>
                </tr>
            `).join('');
        };

        const atualizarAnaliticos = (analiticoProdutos, analiticoParceiros) => {
            produtoState.clear();
            parceiroState.clear();

            (analiticoProdutos || []).forEach((item) => {
                produtoState.set(String(item.produto_id), {
                    ...item,
                    entrada_bruto: Number(item.entrada_bruto || 0),
                    entrada_liquido: Number(item.entrada_liquido || 0),
                    entrada_final: Number(item.entrada_final || 0),
                    saida_bruto: Number(item.saida_bruto || 0),
                    saida_liquido: Number(item.saida_liquido || 0),
                    saida_final: Number(item.saida_final || 0),
                    total_final: Number(item.total_final || 0),
                });
            });

            (analiticoParceiros || []).forEach((item) => {
                const key = `${item.parceiro_tipo}-${item.parceiro_id ?? 'sem'}`;
                parceiroState.set(key, {
                    ...item,
                    entrada: Number(item.entrada || 0),
                    saida: Number(item.saida || 0),
                    total: Number(item.total || 0),
                });
            });

            renderProdutos();
            renderParceiros();
        };

        const inserirEvento = (evento) => {
            const existente = monitorTableBody.querySelector(`[data-pesagem-id="${evento.id}"]`);

            if (existente) {
                existente.outerHTML = montarLinha(evento);
                return;
            }

            monitorTableBody.insertAdjacentHTML('afterbegin', montarLinha(evento));

            const linhas = monitorTableBody.querySelectorAll('tr');
            if (linhas.length > monitorState.limit) {
                linhas[linhas.length - 1].remove();
            }
        };

        const carregarDados = async (opcoes = {}) => {
            const { atualizarFeed = true, atualizarAnaliticos = true } = opcoes;
            const formData = new FormData(monitorForm);
            const params = new URLSearchParams(formData);
            const response = await fetch(`{{ route('monitor.pesagens.data') }}?${params.toString()}`);

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (atualizarFeed) {
                monitorTableBody.innerHTML = '';
                data.eventos.forEach((evento) => {
                    inserirEvento(evento);
                });
            }

            if (atualizarAnaliticos) {
                atualizarResumo(data.resumo);
                atualizarAnaliticos(data.analitico_produtos, data.analitico_parceiros);
                renderContextCards(data.totais_contexto || []);
            }
        };

        monitorForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            await carregarDados();

            const filialId = monitorForm.querySelector('select[name="filial_id"]').value || null;
            conectarRealtime(filialId);
        });

        atualizarAnaliticos(analiticoProdutosInicial, analiticoParceirosInicial);
        renderContextCards(totaisContextoInicial);

        const agendarAtualizacaoAnaliticos = () => {
            if (refreshTimeout) {
                clearTimeout(refreshTimeout);
            }
            refreshTimeout = setTimeout(() => {
                carregarDados({ atualizarFeed: false, atualizarAnaliticos: true });
                refreshTimeout = null;
            }, 1200);
        };

        const echo = new Echo({
            broadcaster: 'pusher',
            key: monitorConfig.pusherKey,
            cluster: monitorConfig.pusherCluster,
            wsHost: monitorConfig.pusherHost || window.location.hostname,
            wsPort: Number(monitorConfig.pusherPort) || 9000,
            wssPort: Number(monitorConfig.pusherPort) || 9000,
            forceTLS: (monitorConfig.pusherScheme || 'http') === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '{{ url('/broadcasting/auth') }}',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                }
            }
        });

        const conectarRealtime = (filialId) => {
            const canalBase = filialId
                ? `empresa.${monitorConfig.empresaId}.filial.${filialId}.monitor`
                : `empresa.${monitorConfig.empresaId}.monitor`;

            if (monitorState.channelName) {
                echo.leave(monitorState.channelName);
            }

            monitorState.channelName = canalBase;

            echo.private(canalBase)
                .listen('.movimento.realtime', (evento) => {
                    if (!evento || !evento.payload) {
                        return;
                    }

                    const payload = evento.payload;
                    inserirEvento(payload);
                    agendarAtualizacaoAnaliticos();
                });
        };

        conectarRealtime(monitorForm.querySelector('select[name="filial_id"]').value || null);
    </script>
@endsection
