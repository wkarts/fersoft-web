@extends('default.layout')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm mb-4">
                    <!-- CABEÇALHO COM O BOTÃO DE AJUSTE FISCAL -->
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 d-flex align-items-center">
                            <!-- Ícone do Prosoft adicionado aqui -->
                            <img src="{{ asset('imgs/prosoft-icone.png') }}" alt="Ícone Prosoft" style="width: 26px; height: 26px; margin-right: 10px; border-radius: 4px; box-shadow: 0 0 3px rgba(0,0,0,0.3);">
                            Exportação Fiscal Prosoft
                        </h5>

                        <a href="{{ url('relatorios-financeiros/prosoft/ajuste-fiscal') }}" class="btn btn-warning btn-sm font-weight-bold">
                            <i class="fas fa-cogs"></i> Ajuste Fiscal em Lote (De / Para)
                        </a>
                    </div>
                    <div class="card-body">

                        <!-- Filtro por Período e Local -->
                        <form method="GET" action="{{ url('relatorios-financeiros/exportar-prosoft') }}" class="row mb-3">
                            <div class="col-md-3">
                                <label for="data_inicio">Data Inicial:</label>
                                <input type="date" id="data_inicio" name="data_inicio" value="{{ $dataInicio ?? '' }}" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="data_fim">Data Final:</label>
                                <input type="date" id="data_fim" name="data_fim" value="{{ $dataFim ?? '' }}" class="form-control">
                            </div>

                            <!-- NOVO CAMPO: Seletor de Matriz e Filial -->
                            <div class="col-md-4">
                                <label for="filial_id">Local (Matriz / Filial):</label>
                                <select name="filial_id" id="filial_id" class="form-control">
                                    <option value="">Todas (Misturado)</option>
                                    <option value="matriz" {{ (request('filial_id') === 'matriz' || (isset($filial_id) && $filial_id === 'matriz')) ? 'selected' : '' }}>Matriz</option>
                                    @foreach(\App\Models\Filial::all() as $f)
                                        <option value="{{ $f->id }}" {{ (request('filial_id') == $f->id || (isset($filial_id) && $filial_id == $f->id)) ? 'selected' : '' }}>{{ $f->nome ?? 'Filial '.$f->id }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-secondary btn-block">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                            </div>
                        </form>

                        <form id="formExportProsoft" action="{{ url('relatorios-financeiros/exportar-prosoft') }}" method="POST">
                            @csrf

                            <!-- NOVO CAMPO OCULTO: Garante que a exportação ZIP saiba qual filial está selecionada -->
                            <input type="hidden" name="filial_id" value="{{ request('filial_id', 'matriz') }}">

                            <ul class="nav nav-tabs" id="prosoftTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="compras-tab" data-toggle="tab" href="#compras" role="tab" aria-controls="compras" aria-selected="true">
                                        Notas de Entrada / Compras (<span id="countCompras">0</span>)
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="servicos-tab" data-toggle="tab" href="#servicos" role="tab" aria-controls="servicos" aria-selected="false">
                                        Serviços Tomados (<span id="countServicos">0</span>)
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content border border-top-0 p-3 bg-white mb-3" id="prosoftTabsContent">

                                <!-- ABA 1: COMPRAS E ITENS -->
                                <div class="tab-pane fade show active" id="compras" role="tabpanel" aria-labelledby="compras-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnMarcarTodasCompras">
                                            <i class="fas fa-check-square"></i> Marcar / Desmarcar Todas
                                        </button>
                                        <div class="text-right">
                                        <span class="badge badge-info p-2 font-weight-normal mr-2" style="font-size: 13px;">
                                            Total Selecionado: <strong id="totalSelecionadoCompras">R$ 0,00</strong>
                                        </span>
                                            <span class="badge badge-secondary p-2 font-weight-normal" style="font-size: 13px;">
                                            Total Geral Listado: <strong>R$ {{ number_format($compras->sum('valor') ?? 0, 2, ',', '.') }}</strong>
                                        </span>
                                        </div>
                                    </div>
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-striped table-bordered table-sm">
                                            <thead>
                                            <tr>
                                                <th width="40" class="text-center">#</th>
                                                <th>ID</th>
                                                <th>Nº Nota / Emissão</th>
                                                <th>Fornecedor</th>
                                                <th>Data Emissão</th>
                                                <th class="text-right">Valor Total</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($compras ?? [] as $compra)
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" name="compra_ids[]" value="{{ $compra->id }}" data-valor="{{ $compra->valor ?? 0 }}" class="compra-checkbox">
                                                    </td>
                                                    <td>{{ $compra->id }}</td>
                                                    <td>{{ (!empty($compra->nf) && $compra->nf != '0') ? $compra->nf : $compra->numero_emissao }}</td>
                                                    <td>{{ $compra->fornecedor->razao_social ?? 'N/D' }}</td>
                                                    <td>{{ date('d/m/Y', strtotime($compra->data_emissao)) }}</td>
                                                    <td class="text-right">R$ {{ number_format($compra->valor, 2, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">Nenhuma compra encontrada no período.</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                            @if(isset($compras) && count($compras) > 0)
                                                <tfoot>
                                                <tr class="bg-light font-weight-bold">
                                                    <td colspan="5" class="text-right">Soma Total das Compras Listadas:</td>
                                                    <td class="text-right text-primary">R$ {{ number_format($compras->sum('valor'), 2, ',', '.') }}</td>
                                                </tr>
                                                </tfoot>
                                            @endif
                                        </table>
                                    </div>
                                </div>

                                <!-- ABA 2: SERVIÇOS TOMADOS -->
                                <div class="tab-pane fade" id="servicos" role="tabpanel" aria-labelledby="servicos-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnMarcarTodasServicos">
                                            <i class="fas fa-check-square"></i> Marcar / Desmarcar Todas
                                        </button>
                                        <div class="text-right">
                                        <span class="badge badge-info p-2 font-weight-normal mr-2" style="font-size: 13px;">
                                            Total Selecionado: <strong id="totalSelecionadoServicos">R$ 0,00</strong>
                                        </span>
                                            <span class="badge badge-secondary p-2 font-weight-normal" style="font-size: 13px;">
                                            Total Geral Listado: <strong>R$ {{ number_format($servicos->sum('valor') ?? 0, 2, ',', '.') }}</strong>
                                        </span>
                                        </div>
                                    </div>
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-striped table-bordered table-sm">
                                            <thead>
                                            <tr>
                                                <th width="40" class="text-center">#</th>
                                                <th>ID</th>
                                                <th>Nº Nota</th>
                                                <th>Fornecedor</th>
                                                <th>Data Emissão</th>
                                                <th class="text-right">Valor Total</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($servicos ?? [] as $servico)
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" name="compra_ids[]" value="{{ $servico->id }}" data-valor="{{ $servico->valor ?? 0 }}" class="servico-checkbox">
                                                    </td>
                                                    <td>{{ $servico->id }}</td>
                                                    <td>{{ (!empty($servico->nf) && $servico->nf != '0') ? $servico->nf : $servico->numero_emissao }}</td>
                                                    <td>{{ $servico->fornecedor->razao_social ?? 'N/D' }}</td>
                                                    <td>{{ date('d/m/Y', strtotime($servico->data_emissao)) }}</td>
                                                    <td class="text-right">R$ {{ number_format($servico->valor, 2, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">Nenhum serviço encontrado no período.</td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                            @if(isset($servicos) && count($servicos) > 0)
                                                <tfoot>
                                                <tr class="bg-light font-weight-bold">
                                                    <td colspan="5" class="text-right">Soma Total dos Serviços Listados:</td>
                                                    <td class="text-right text-primary">R$ {{ number_format($servicos->sum('valor'), 2, ',', '.') }}</td>
                                                </tr>
                                                </tfoot>
                                            @endif
                                        </table>
                                    </div>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-md-12 text-right">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-download"></i> Gerar e Baixar Arquivos Prosoft (.ZIP)
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            function formatMoeda(valor) {
                return 'R$ ' + Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function updateTotalsAndCounters() {
                // Compras
                var comprasMarcadas = document.querySelectorAll('.compra-checkbox:checked');
                var totalValorCompras = 0;
                comprasMarcadas.forEach(function(cb) {
                    totalValorCompras += parseFloat(cb.getAttribute('data-valor') || 0);
                });
                var elCountCompras = document.getElementById('countCompras');
                var elTotalCompras = document.getElementById('totalSelecionadoCompras');
                if (elCountCompras) elCountCompras.innerText = comprasMarcadas.length;
                if (elTotalCompras) elTotalCompras.innerText = formatMoeda(totalValorCompras);

                // Serviços
                var servicosMarcados = document.querySelectorAll('.servico-checkbox:checked');
                var totalValorServicos = 0;
                servicosMarcados.forEach(function(cb) {
                    totalValorServicos += parseFloat(cb.getAttribute('data-valor') || 0);
                });
                var elCountServicos = document.getElementById('countServicos');
                var elTotalServicos = document.getElementById('totalSelecionadoServicos');
                if (elCountServicos) elCountServicos.innerText = servicosMarcados.length;
                if (elTotalServicos) elTotalServicos.innerText = formatMoeda(totalValorServicos);
            }

            function initListeners() {
                // Botão Compras
                var btnCompras = document.getElementById('btnMarcarTodasCompras');
                if (btnCompras) {
                    btnCompras.addEventListener('click', function() {
                        var checkboxes = document.querySelectorAll('.compra-checkbox');
                        var todosMarcados = Array.from(checkboxes).every(function(cb) { return cb.checked; });
                        checkboxes.forEach(function(cb) { cb.checked = !todosMarcados; });
                        updateTotalsAndCounters();
                    });
                }

                // Botão Serviços
                var btnServicos = document.getElementById('btnMarcarTodasServicos');
                if (btnServicos) {
                    btnServicos.addEventListener('click', function() {
                        var checkboxes = document.querySelectorAll('.servico-checkbox');
                        var todosMarcados = Array.from(checkboxes).every(function(cb) { return cb.checked; });
                        checkboxes.forEach(function(cb) { cb.checked = !todosMarcados; });
                        updateTotalsAndCounters();
                    });
                }

                // Seleção individual de checkboxes
                document.addEventListener('change', function(e) {
                    if (e.target.matches('.compra-checkbox, .servico-checkbox')) {
                        updateTotalsAndCounters();
                    }
                });

                updateTotalsAndCounters();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initListeners);
            } else {
                initListeners();
            }
        })();
    </script>
@endsection
