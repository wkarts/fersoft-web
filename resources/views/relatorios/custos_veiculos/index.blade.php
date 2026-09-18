@extends('default.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-truck-moving me-2"></i>Apuração de Custos por Veículo (Contas a Pagar)</h5>
                    </div>
                    <div class="card-body bg-light">
                        {{-- Form de Filtros --}}
                        <form method="GET" action="{{ route('custo.veiculo.index') }}" id="form-filtro">
                            {{-- Linha 1: Filtros organizados em grid de 6 colunas --}}
                            <div class="row">
                                {{-- Data Início --}}
                                <div class="col-sm-12 col-md-4 col-lg-2 form-group">
                                    <label for="data_inicio" class="col-form-label font-weight-bold">Data Início</label>
                                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="{{ $dataInicio }}" required>
                                </div>

                                {{-- Data Fim --}}
                                <div class="col-sm-12 col-md-4 col-lg-2 form-group">
                                    <label for="data_fim" class="col-form-label font-weight-bold">Data Fim</label>
                                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="{{ $dataFim }}" required>
                                </div>

                                {{-- Regime --}}
                                <div class="col-sm-12 col-md-4 col-lg-2 form-group">
                                    <label for="regime" class="col-form-label font-weight-bold">Regime</label>
                                    <select class="form-control custom-select" id="regime" name="regime">
                                        <option value="competencia" {{ $regime === 'competencia' ? 'selected' : '' }}>Competência (Vencimento)</option>
                                        <option value="caixa" {{ $regime === 'caixa' ? 'selected' : '' }}>Caixa (Pagamento)</option>
                                    </select>
                                </div>

                                {{-- Nível de Visão --}}
                                <div class="col-sm-12 col-md-4 col-lg-2 form-group">
                                    <label for="tipo_visao" class="col-form-label font-weight-bold">Nível de Visão</label>
                                    <select class="form-control custom-select" id="tipo_visao" name="tipo_visao">
                                        <option value="resumido" {{ $tipoVisao === 'resumido' ? 'selected' : '' }}>Resumido (Por Veículo)</option>
                                        <option value="categoria" {{ $tipoVisao === 'categoria' ? 'selected' : '' }}>Analítico (Por Categoria)</option>
                                        <option value="lancamentos" {{ $tipoVisao === 'lancamentos' ? 'selected' : '' }}>Analítico (Por Lançamento)</option>
                                    </select>
                                </div>

                                {{-- Filial --}}
                                <div class="col-sm-12 col-md-4 col-lg-2 form-group">
                                    <label for="filial_id" class="col-form-label font-weight-bold">Filial</label>
                                    <select class="form-control custom-select" id="filial_id" name="filial_id">
                                        <option value="">Todas as Filiais</option>
                                        @foreach($filiais as $f)
                                            <option value="{{ $f->id }}" {{ $filialId == $f->id ? 'selected' : '' }}>
                                                {{ $f->razao_social ?? $f->nome ?? 'Filial ' . $f->id }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Veículo / Placa --}}
                                <div class="col-sm-12 col-md-4 col-lg-2 form-group">
                                    <label for="veiculo_id" class="col-form-label font-weight-bold">Veículo / Placa</label>
                                    <select class="form-control custom-select" id="veiculo_id" name="veiculo_id">
                                        <option value="">Todos os Veículos</option>
                                        @foreach($veiculos as $v)
                                            <option value="{{ $v->id }}" {{ (string)$veiculoId === (string)$v->id ? 'selected' : '' }}>
                                                {{ $v->placa }} - {{ $v->marca }} {{ $v->modelo }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Linha 2: Botões de Ação alinhados à direita com margem --}}
                            <div class="row mt-2">
                                <div class="col-12 text-right d-flex justify-content-end gap-2" style="gap: 8px;">
                                    <a href="{{ route('custo.veiculo.index') }}" class="btn btn-secondary">
                                        <i class="fa fa-eraser"></i> Limpar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-search"></i> Filtrar
                                    </button>
                                    <a href="{{ route('custo.veiculo.excel', request()->all()) }}" class="btn btn-success" target="_blank">
                                        <i class="fa fa-file-excel"></i> Excel
                                    </a>
                                    <a href="{{ route('custo.veiculo.pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
                                        <i class="fa fa-file-pdf"></i> Imprimir PDF
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Resultados --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">

                        {{-- 1. Visão Resumida por Veículo --}}
                        @if($tipoVisao === 'resumido')
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle">
                                    <thead class="table-dark">
                                    <tr>
                                        <th>Placa</th>
                                        <th>Marca / Modelo</th>
                                        <th class="text-center">Qtd. Títulos</th>
                                        <th class="text-end">Total Custo (R$)</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php $totalGeral = 0; @endphp
                                    @forelse($dados as $item)
                                        @php $totalGeral += $item->total_custo; @endphp
                                        <tr>
                                            <td><span class="badge bg-secondary font-monospace fs-6">{{ $item->placa }}</span></td>
                                            <td>{{ $item->marca }} {{ $item->modelo }}</td>
                                            <td class="text-center">{{ $item->total_titulos }}</td>
                                            <td class="text-end fw-bold text-danger">R$ {{ number_format($item->total_custo, 2, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Nenhum custo encontrado para o período/filtro selecionado.</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                    @if(count($dados) > 0)
                                        <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="3" class="text-uppercase">Total Geral:</td>
                                            <td class="text-end text-danger fs-6">R$ {{ number_format($totalGeral, 2, ',', '.') }}</td>
                                        </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        @endif

                        {{-- 2. Visão Analítica por Categoria --}}
                        @if($tipoVisao === 'categoria')
                            @forelse($dados as $placa => $categorias)
                                <div class="card mb-3 border">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold">
                                            Veículo Placa: <span class="badge bg-primary font-monospace">{{ $placa }}</span>
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead class="table-secondary">
                                                <tr>
                                                    <th>Categoria de Conta</th>
                                                    <th class="text-center">Qtd. Títulos</th>
                                                    <th class="text-end">Total (R$)</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @php $subtotal = 0; @endphp
                                                @foreach($categorias as $cat)
                                                    @php $subtotal += $cat->total_custo; @endphp
                                                    <tr>
                                                        <td>{{ $cat->categoria_nome }}</td>
                                                        <td class="text-center">{{ $cat->total_titulos }}</td>
                                                        <td class="text-end">R$ {{ number_format($cat->total_custo, 2, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                                <tfoot class="table-light fw-bold">
                                                <tr>
                                                    <td colspan="2">Total do Veículo:</td>
                                                    <td class="text-end text-danger">R$ {{ number_format($subtotal, 2, ',', '.') }}</td>
                                                </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">Nenhum registro encontrado.</div>
                            @endforelse
                        @endif

                        {{-- 3. Visão Analítica por Lançamentos --}}
                        @if($tipoVisao === 'lancamentos')
                            @forelse($dados as $placa => $lancamentos)
                                <div class="card mb-4 border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0 fw-bold">
                                            Veículo Placa: <span class="badge bg-primary font-monospace">{{ $placa }}</span>
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover align-middle mb-0">
                                                <thead class="table-secondary">
                                                <tr>
                                                    <th>Vencimento / Pago em</th>
                                                    <th>Nº NF / Referência</th>
                                                    <th>Categoria</th>
                                                    <th class="text-center">Status</th>
                                                    <th class="text-end">Valor Integral</th>
                                                    <th class="text-end">Valor Pago</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @php $totIntegral = 0; $totPago = 0; @endphp
                                                @foreach($lancamentos as $l)
                                                    @php
                                                        $totIntegral += $l->valor_integral;
                                                        $totPago += $l->valor_pago;
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            {{ date('d/m/Y', strtotime($l->data_vencimento)) }}
                                                            @if($l->data_pagamento)
                                                                <br><small class="text-muted">Pg: {{ date('d/m/Y', strtotime($l->data_pagamento)) }}</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <strong>NF:</strong> {{ $l->numero_nota_fiscal ?? 'N/A' }}
                                                            <br><small class="text-muted">{{ $l->referencia }}</small>
                                                        </td>
                                                        <td>{{ $l->categoria_nome }}</td>
                                                        <td class="text-center">
                                                            @if($l->status == 1)
                                                                <span class="badge bg-success">Pago</span>
                                                            @else
                                                                <span class="badge bg-warning text-dark">Pendente</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end">R$ {{ number_format($l->valor_integral, 2, ',', '.') }}</td>
                                                        <td class="text-end fw-bold">R$ {{ number_format($l->valor_pago, 2, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                                <tfoot class="table-light fw-bold">
                                                <tr>
                                                    <td colspan="4">Subtotal do Veículo:</td>
                                                    <td class="text-end">R$ {{ number_format($totIntegral, 2, ',', '.') }}</td>
                                                    <td class="text-end text-danger">R$ {{ number_format($totPago, 2, ',', '.') }}</td>
                                                </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">Nenhum lançamento encontrado para os filtros informados.</div>
                            @endforelse
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
