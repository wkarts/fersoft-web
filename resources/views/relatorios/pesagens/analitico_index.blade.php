{{-- resources/views/relatorios/pesagens/analitico_index.blade.php --}}
@extends('default.layout')

@section('css')
    <style type="text/css">
        .card-header {
            border-radius: 7px!important;
        }
        .table th, .table td {
            font-size: 11px;
            vertical-align: middle;
        }
    </style>
@endsection

@section('content')

    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">

        <div class="subheader py-2 py-lg-4 subheader-solid" id="kt_subheader">
            <div class="container-fluid d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">

                <div class="d-flex align-items-center flex-wrap mr-1">
                    <div class="d-flex flex-column">
                        <h2 class="text-dark font-weight-bold my-2 mr-5">
                            Relatório Analítico de Pesagens
                        </h2>
                        <span class="text-muted font-weight-bold">
                        Visão detalhada das operações de pesagem (entrada/saída) por filial, veículo e motorista
                    </span>
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    <a href="{{ route('relatorios.index') }}"
                       class="btn btn-light-primary font-weight-bold">
                        <i class="la la-arrow-left"></i> Voltar
                    </a>
                </div>

            </div>
        </div>

        <div class="container-fluid">

            <div class="card card-custom gutter-b">
                <div class="card-header">
                    <div class="card-title">
                    <span class="card-icon">
                        <i class="la la-filter text-primary"></i>
                    </span>
                        <h3 class="card-label">
                            Filtros de Pesquisa
                        </h3>
                    </div>
                    <div class="card-toolbar">
                        <a href="{{ route('relatorios.pesagens_analitico.index') }}"
                           class="btn btn-sm btn-secondary">
                            Limpar filtros
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    <form method="GET"
                          action="{{ route('relatorios.pesagens_analitico.index') }}"
                          class="mb-4">

                        <div class="form-row">

                            <div class="form-group col-md-3">
                                <label>Data inicial</label>
                                <input type="date" name="data_inicial"
                                       class="form-control"
                                       value="{{ $filtros['data_inicial'] ?? '' }}">
                            </div>

                            <div class="form-group col-md-3">
                                <label>Data final</label>
                                <input type="date" name="data_final"
                                       class="form-control"
                                       value="{{ $filtros['data_final'] ?? '' }}">
                            </div>

                            <div class="form-group col-md-3">
                                <label>Tipo de operação</label>
                                <select name="tipo_operacao" class="form-control">
                                    <option value="">Todas</option>
                                    <option value="entrada"
                                        {{ ($filtros['tipo_operacao'] ?? '') === 'entrada' ? 'selected' : '' }}>
                                        Entrada (Compra)
                                    </option>
                                    <option value="saida"
                                        {{ ($filtros['tipo_operacao'] ?? '') === 'saida' ? 'selected' : '' }}>
                                        Saída (Venda)
                                    </option>
                                </select>
                            </div>

                            <div class="form-group col-md-3">
                                <label>Filial</label>
                                <select name="filial_id" class="form-control">
                                    <option value="">Todas</option>
                                    @foreach($filiais as $filial)
                                        <option value="{{ $filial->id }}"
                                            {{ (string)($filtros['filial_id'] ?? '') === (string)$filial->id ? 'selected' : '' }}>
                                            {{ $filial->nome_fantasia }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        <div class="form-row">

                            <div class="form-group col-md-3">
                                <label>Motorista</label>
                                <select name="motorista_id" class="form-control">
                                    <option value="">Todos</option>
                                    @foreach($motoristas as $motorista)
                                        <option value="{{ $motorista->id }}"
                                            {{ (string)($filtros['motorista_id'] ?? '') === (string)$motorista->id ? 'selected' : '' }}>
                                            {{ $motorista->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-3">
                                <label>Veículo</label>
                                <select name="veiculo_id" class="form-control">
                                    <option value="">Todos</option>
                                    @foreach($veiculos as $veiculo)
                                        <option value="{{ $veiculo->id }}"
                                            {{ (string)($filtros['veiculo_id'] ?? '') === (string)$veiculo->id ? 'selected' : '' }}>
                                            {{ $veiculo->placa }} - {{ $veiculo->descricao }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-3">
                                <label>Situação / Status</label>
                                <select name="status" class="form-control">
                                    <option value="">Todas</option>
                                    <option value="em andamento"
                                        {{ ($filtros['status'] ?? '') === 'em andamento' ? 'selected' : '' }}>
                                        Em andamento
                                    </option>
                                    <option value="concluído"
                                        {{ ($filtros['status'] ?? '') === 'concluído' ? 'selected' : '' }}>
                                        Concluído
                                    </option>
                                </select>
                            </div>

                            <div class="form-group col-md-3">
                                <label>Associação venda/compra</label>
                                <select name="associacao" class="form-control">
                                    <option value=""
                                        {{ ($filtros['associacao'] ?? '') === '' ? 'selected' : '' }}>
                                        Todas
                                    </option>
                                    <option value="com"
                                        {{ ($filtros['associacao'] ?? '') === 'com' ? 'selected' : '' }}>
                                        Somente com venda/compra
                                    </option>
                                    <option value="sem"
                                        {{ ($filtros['associacao'] ?? '') === 'sem' ? 'selected' : '' }}>
                                        Somente sem venda/compra
                                    </option>
                                </select>
                            </div>

                        </div>

                        <div class="d-flex justify-content-between mt-4">

                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="la la-search"></i> Aplicar filtros
                                </button>
                            </div>

                            <div class="btn-group">
                                <a href="{{ route('relatorios.pesagens_analitico.export',
                                    array_merge(request()->all(), ['tipo' => 'pdf'])) }}"
                                   class="btn btn-sm btn-danger">
                                    <i class="la la-file-pdf-o"></i> Exportar PDF
                                </a>

                                <a href="{{ route('relatorios.pesagens_analitico.export',
                                    array_merge(request()->all(), ['tipo' => 'xlsx'])) }}"
                                   class="btn btn-sm btn-success">
                                    <i class="la la-file-excel-o"></i> Exportar Excel
                                </a>
                            </div>

                        </div>

                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-light">
                            <tr>
                                <th>Data/Hora</th>
                                <th>Tipo</th>
                                <th>Peso Bruto</th>
                                <th>Tara</th>
                                <th>Peso Líquido</th>
                                <th>Status</th>
                                <th>Venda</th>
                                <th>Compra</th>
                                <th>Placa</th>
                                <th>Veículo</th>
                                <th>Motorista</th>
                                <th>Doc. Motorista</th>
                                <th>Filial</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($pesagens as $p)
                                @php
                                    $dataHora = $p->dt_entrada ?? $p->created_at;
                                    $tipoOperacao = $p->tipo === 'compra'
                                        ? 'Entrada'
                                        : ($p->tipo === 'venda' ? 'Saída' : '');
                                    $pesoBruto   = (float) ($p->peso_bruto ?? 0);
                                    $pesoLiquido = (float) ($p->peso_liquido_real ?? 0);
                                    $tara        = max(0, $pesoBruto - $pesoLiquido);
                                @endphp
                                <tr>
                                    <td>{{ $dataHora ? $dataHora->format('d/m/Y H:i') : '' }}</td>
                                    <td>{{ $tipoOperacao }}</td>
                                    <td>{{ number_format($pesoBruto, 3, ',', '.') }}</td>
                                    <td>{{ number_format($tara, 3, ',', '.') }}</td>
                                    <td>{{ number_format($pesoLiquido, 3, ',', '.') }}</td>
                                    <td>{{ ucfirst($p->status) }}</td>
                                    <td>{{ optional($p->venda)->id }}</td>
                                    <td>{{ optional($p->compra)->id }}</td>
                                    <td>
                                        {{ $p->placa_veiculo ?: (optional($p->veiculo)->placa ?? '') }}
                                    </td>
                                    <td>{{ optional($p->veiculo)->descricao }}</td>
                                    <td>{{ optional($p->motorista)->nome ?? $p->motorista_nome }}</td>
                                    <td>{{ optional($p->motorista)->cpf }}</td>
                                    <td>{{ optional($p->filial)->nome_fantasia }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="text-center">
                                        Nenhuma pesagem encontrada para os filtros informados.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        {{ $pesagens->links() }}
                    </div>

                </div>
            </div>

        </div>
    </div>

@endsection
