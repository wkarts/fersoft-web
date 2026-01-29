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
    </style>

    <div class="card card-custom gutter-b monitor-card">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">Painel ao Vivo - Pesagens</h3>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="card card-custom bg-light">
                        <div class="card-body">
                            <div class="text-muted">Total do dia (KG)</div>
                            <div class="font-size-h4 font-weight-bold" id="monitorTotalKg">
                                {{ number_format($resumo['total_kg'] ?? 0, 2, ',', '.') }} kg
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="card card-custom bg-light">
                        <div class="card-body">
                            <div class="text-muted">Total do dia (R$)</div>
                            <div class="font-size-h4 font-weight-bold" id="monitorTotalValor">
                                R$ {{ number_format($resumo['total_valor'] ?? 0, 2, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <div class="card card-custom bg-light">
                        <div class="card-body">
                            <div class="text-muted">Pesagens hoje</div>
                            <div class="font-size-h4 font-weight-bold" id="monitorTotalPesagens">
                                {{ $resumo['total_pesagens'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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

            <div class="table-responsive monitor-feed">
                <table class="table table-bordered table-hover monitor-table" id="monitorTabela">
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
        const monitorTotalValor = document.getElementById('monitorTotalValor');
        const monitorTotalPesagens = document.getElementById('monitorTotalPesagens');

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

        const carregarDados = async () => {
            const formData = new FormData(monitorForm);
            const params = new URLSearchParams(formData);
            const response = await fetch(`{{ route('monitor.pesagens.data') }}?${params.toString()}`);

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            monitorTableBody.innerHTML = '';

            data.eventos.forEach((evento) => {
                inserirEvento(evento);
            });

            if (data.resumo) {
                monitorTotalKg.textContent = `${Number(data.resumo.total_kg || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} kg`;
                monitorTotalValor.textContent = formatMoney(data.resumo.total_valor || 0, 2);
                monitorTotalPesagens.textContent = Number(data.resumo.total_pesagens || 0).toLocaleString('pt-BR');
            }
        };

        monitorForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            await carregarDados();

            const filialId = monitorForm.querySelector('select[name="filial_id"]').value || null;
            conectarRealtime(filialId);
        });

        const echo = new Echo({
            broadcaster: 'pusher',
            key: monitorConfig.pusherKey,
            cluster: monitorConfig.pusherCluster,
            wsHost: monitorConfig.pusherHost || window.location.hostname,
            wsPort: Number(monitorConfig.pusherPort) || 80,
            wssPort: Number(monitorConfig.pusherPort) || 443,
            forceTLS: (monitorConfig.pusherScheme || 'https') === 'https',
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
                });
        };

        conectarRealtime(monitorForm.querySelector('select[name="filial_id"]').value || null);
    </script>
@endsection
