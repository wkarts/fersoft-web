@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-document-fiscal.css') }}?v={{ filemtime(public_path('css/ui-document-fiscal.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-admin-modules.css') }}?v={{ filemtime(public_path('css/ui-admin-modules.css')) }}">
@endpush

@section('content')
<div class="document-fiscal-page admin-page admin-page--prosoft">
    <div class="container-fluid">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="fas fa-chart-pie mr-2" style="font-size: 24px;"></i>
                    Dashboard de Apuração: PIS e COFINS
                </h5>
                <a href="{{ url('relatorios-financeiros/exportar-prosoft') }}" class="btn btn-light btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left"></i> Voltar para Exportação
                </a>
            </div>
            <div class="card-body bg-light">

                <!-- FILTROS -->
                <form method="GET" action="{{ url('relatorios-financeiros/prosoft/apuracao-pis-cofins') }}" class="row mb-4 bg-white p-3 border rounded shadow-sm">
                    <div class="col-md-3">
                        <label for="data_inicio" class="font-weight-bold">Data Inicial:</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="{{ $dataInicio ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="data_fim" class="font-weight-bold">Data Final:</label>
                        <input type="date" id="data_fim" name="data_fim" value="{{ $dataFim ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label for="filial_id" class="font-weight-bold">Local (Matriz / Filial):</label>
                        <select name="filial_id" id="filial_id" class="form-control">
                            <option value="">Todas (Consolidado)</option>
                            @if($permiteMatriz)
                                         <option value="matriz" {{ (request('filial_id') === 'matriz' || (isset($filial_id) && $filial_id === 'matriz')) ? 'selected' : '' }}>Matriz</option>
                                    @endif
                            @foreach($filiais as $f)
                                <option value="{{ $f->id }}" {{ (request('filial_id') == $f->id || (isset($filial_id) && $filial_id == $f->id)) ? 'selected' : '' }}>{{ $f->descricao ?? $f->nome_fantasia ?? 'Filial '.$f->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sync-alt"></i> Processar
                        </button>
                    </div>
                </form>

                <!-- DASHBOARD CARDS -->
                <div class="row">
                    <!-- DÉBITOS -->
                    <div class="col-md-4">
                        <div class="card border-danger mb-3 shadow-sm">
                            <div class="card-header bg-danger text-white font-weight-bold text-center">
                                <i class="fas fa-arrow-up"></i> DÉBITOS (Saídas/Vendas)
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>PIS a Pagar:</strong> <span class="float-right text-danger">R$ {{ number_format($apuracao['total_debito_pis'], 2, ',', '.') }}</span></p>
                                <p class="mb-0"><strong>COFINS a Pagar:</strong> <span class="float-right text-danger">R$ {{ number_format($apuracao['total_debito_cofins'], 2, ',', '.') }}</span></p>
                                <hr>
                                <small class="text-muted">Origem: Notas Fiscais de Venda e CT-e (CSTs Tributados 01, 02)</small>
                            </div>
                        </div>
                    </div>

                    <!-- CRÉDITOS -->
                    <div class="col-md-4">
                        <div class="card border-success mb-3 shadow-sm">
                            <div class="card-header bg-success text-white font-weight-bold text-center">
                                <i class="fas fa-arrow-down"></i> CRÉDITOS (Entradas/Compras)
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Crédito PIS:</strong> <span class="float-right text-success">R$ {{ number_format($apuracao['total_credito_pis'], 2, ',', '.') }}</span></p>
                                <p class="mb-0"><strong>Crédito COFINS:</strong> <span class="float-right text-success">R$ {{ number_format($apuracao['total_credito_cofins'], 2, ',', '.') }}</span></p>
                                <hr>
                                <small class="text-muted">Origem: Notas Fiscais de Compra (CSTs com Direito a Crédito 50 a 66)</small>
                            </div>
                        </div>
                    </div>

                    <!-- SALDO FINAL / APURAÇÃO -->
                    <div class="col-md-4">
                        <div class="card border-primary mb-3 shadow">
                            <div class="card-header bg-primary text-white font-weight-bold text-center">
                                <i class="fas fa-balance-scale"></i> RESULTADO DA APURAÇÃO
                            </div>
                            <div class="card-body">
                                <!-- Lógica para PIS -->
                                <p class="mb-1">
                                    <strong>Saldo PIS:</strong>
                                    @if($apuracao['saldo_pis'] > 0)
                                        <span class="float-right text-danger font-weight-bold">A Recolher: R$ {{ number_format($apuracao['saldo_pis'], 2, ',', '.') }}</span>
                                    @elseif($apuracao['saldo_pis'] < 0)
                                        <span class="float-right text-success font-weight-bold">Credor: R$ {{ number_format(abs($apuracao['saldo_pis']), 2, ',', '.') }}</span>
                                    @else
                                        <span class="float-right text-muted font-weight-bold">Zerado</span>
                                    @endif
                                </p>

                                <!-- Lógica para COFINS -->
                                <p class="mb-0">
                                    <strong>Saldo COFINS:</strong>
                                    @if($apuracao['saldo_cofins'] > 0)
                                        <span class="float-right text-danger font-weight-bold">A Recolher: R$ {{ number_format($apuracao['saldo_cofins'], 2, ',', '.') }}</span>
                                    @elseif($apuracao['saldo_cofins'] < 0)
                                        <span class="float-right text-success font-weight-bold">Credor: R$ {{ number_format(abs($apuracao['saldo_cofins']), 2, ',', '.') }}</span>
                                    @else
                                        <span class="float-right text-muted font-weight-bold">Zerado</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABELA DETALHADA POR CFOP E CST -->
                <div class="card mt-2 shadow-sm">
                    <div class="card-header font-weight-bold bg-white d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-list"></i> Detalhamento por Operação, CFOP e CST</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-striped table-sm mb-0">
                            <thead class="thead-dark text-center">
                            <tr>
                                <th>Tipo / Operação</th>
                                <th>CFOP</th>
                                <th>CST</th>
                                <th class="text-right">Valor Total do Item</th>
                                <th class="text-right">Base de Cálculo Valor</th>
                                <th class="text-right">Valor PIS</th>
                                <th class="text-right">Valor COFINS</th>
                            </tr>
                            </thead>
                            <tbody>
                            <!-- SEÇÃO DE VENDAS -->
                            @foreach($apuracao['vendas'] as $v)
                                <tr>
                                    <td>Venda de Mercadorias (Saída)</td>
                                    <td class="text-center">{{ $v->cfop }}</td>
                                    <td class="text-center font-weight-bold">{{ $v->cst }}</td>
                                    <td class="text-right">R$ {{ number_format($v->valor_total_item, 2, ',', '.') }}</td>
                                    <td class="text-right">R$ {{ number_format($v->bc_pis, 2, ',', '.') }}</td>
                                    <td class="text-right text-danger">R$ {{ number_format($v->valor_pis, 2, ',', '.') }}</td>
                                    <td class="text-right text-danger">R$ {{ number_format($v->valor_cofins, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach

                            <!-- SEÇÃO DE CT-ES -->
                            @foreach($apuracao['ctes'] as $c)
                                <tr>
                                    <td>CT-e (Transporte)</td>
                                    <td class="text-center">{{ $c->cfop }}</td>
                                    <td class="text-center font-weight-bold">{{ $c->cst }}</td>
                                    <td class="text-right">R$ {{ number_format($c->valor_total_item, 2, ',', '.') }}</td>
                                    <td class="text-right">R$ {{ number_format($c->bc_pis, 2, ',', '.') }}</td>
                                    <td class="text-right text-danger">R$ {{ number_format($c->valor_pis, 2, ',', '.') }}</td>
                                    <td class="text-right text-danger">R$ {{ number_format($c->valor_cofins, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach

                            <!-- SEÇÃO DE COMPRAS -->
                            @foreach($apuracao['compras'] as $comp)
                                <tr>
                                    <td>Compra (Entrada)</td>
                                    <td class="text-center">{{ $comp->cfop }}</td>
                                    <td class="text-center font-weight-bold">{{ $comp->cst }}</td>
                                    <td class="text-right">R$ {{ number_format($comp->valor_total_item, 2, ',', '.') }}</td>
                                    <td class="text-right">R$ {{ number_format($comp->bc_pis, 2, ',', '.') }}</td>
                                    <td class="text-right {{ $comp->cst == '50' ? 'text-success' : 'text-muted' }}">R$ {{ number_format($comp->valor_pis, 2, ',', '.') }}</td>
                                    <td class="text-right {{ $comp->cst == '50' ? 'text-success' : 'text-muted' }}">R$ {{ number_format($comp->valor_cofins, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
