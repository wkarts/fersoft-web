@extends('default.layout')
@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body">
        <h4 class="mb-4 text-primary font-weight-bold"><i class="fa fa-file-contract"></i> {{ $title ?? 'Lista de Contratos' }}</h4>
        
        <form method="GET" action="/contratos" class="bg-light p-3 rounded mb-4 border">
            <div class="row align-items-end">
                <div class="form-group col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">CLIENTE</label>
                    <select name="cliente_id" class="form-control form-control-sm">
                        <option value="">Todos os Clientes</option>
                        @foreach($clientes ?? [] as $c)
                            <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->razao_social ?? $c->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-3 mb-2">
                    <label class="small font-weight-bold text-muted">FILIAL / MATRIZ</label>
                    <select name="filial_id" class="form-control form-control-sm">
                        <option value="">Todas</option>
                        <option value="matriz" {{ request('filial_id') == 'matriz' ? 'selected' : '' }}>Matriz (Principal)</option>
                        @foreach($filiaisLista ?? [] as $f)
                            <option value="{{ $f->id }}" {{ request('filial_id') == $f->id ? 'selected' : '' }}>
                                {{ $f->descricao ?? $f->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-2 mb-2">
                    <label class="small font-weight-bold text-muted">STATUS</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="Ativo" {{ request('status') == 'Ativo' ? 'selected' : '' }}>Ativo</option>
                        <option value="Finalizado" {{ request('status') == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                    </select>
                </div>

                <div class="form-group col-md-4 mb-2 d-flex">
                    <button type="submit" class="btn btn-primary btn-sm mr-2 px-3"><i class="fa fa-search"></i> Filtrar</button>
                    <a href="/contratos" class="btn btn-secondary btn-sm px-3"><i class="fa fa-eraser"></i> Limpar</a>
                </div>
            </div>
        </form>

        <div class="row mb-3">
            <div class="col-md-12">
                <a href="{{ $newItemUrl ?? '/contratos/new' }}" class="btn btn-success float-right btn-sm font-weight-bold">
                    <i class="fa fa-plus"></i> {{ $newItemText ?? 'Novo Contrato' }}
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover align-middle">
                <thead class="bg-secondary text-white">
                    <tr>
                        <th style="width: 8%;">Nº Contrato</th>
                        <th style="width: 22%;">Cliente</th>
                        <th style="width: 12%;">Filial / Matriz</th>
                        <th style="width: 10%;">Data Início</th>
                        <th style="width: 11%;">Valor Total</th>
                        <th style="width: 11%;">Faturado</th>
                        <th style="width: 11%;">Saldo</th>
                        <th style="width: 7%;">Status</th>
                        <th style="width: 8%;" class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
    $registros = $data ?? $lista ?? []; 
@endphp

@forelse($registros as $item)
@php
    // Garante que $item é um objeto para evitar "Attempt to read property 'id' on int"
    if (!is_object($item)) { continue; }

    $valorFaturado = \DB::table('faturas_engenharia')
        ->where('contrato_eng_id', $item->id)
        ->sum('valor_total');

    $valorTotal = $item->valor_contrato ?? $item->valor_total ?? 0;
    $saldo      = $valorTotal - $valorFaturado;
@endphp
                    <tr>
                        <td class="align-middle font-weight-bold">{{ $item->numero_contrato ?? $item->id }}</td>
                        <td class="align-middle">{{ $item->cliente->razao_social ?? $item->cliente->nome ?? 'N/D' }}</td>
                        <td class="align-middle">{{ $item->filial->descricao ?? $item->filial->nome ?? 'Matriz' }}</td>
                        <td class="align-middle">{{ isset($item->data_inicio) ? \Carbon\Carbon::parse($item->data_inicio)->format('d/m/Y') : 'N/D' }}</td>
                        <td class="align-middle font-weight-bold">R$ {{ number_format($valorTotal, 2, ',', '.') }}</td>
                        <td class="align-middle text-success font-weight-bold">R$ {{ number_format($valorFaturado, 2, ',', '.') }}</td>
                        <td class="align-middle {{ $saldo < 0 ? 'text-danger' : 'text-primary' }} font-weight-bold">
                            R$ {{ number_format($saldo, 2, ',', '.') }}
                        </td>
                        <td class="align-middle">
                            <span class="badge badge-{{ ($item->status ?? '') == 'Ativo' ? 'success' : 'secondary' }}">
                                {{ $item->status ?? 'Ativo' }}
                            </span>
                        </td>
                        <td class="text-center align-middle">
                            <div class="btn-group" role="group">
                                <!-- Botão Histórico Direto por Link -->
                                <a href="/contratos/detalhes/{{ $item->id }}" class="btn btn-info btn-sm" title="Histórico e Saldos"><i class="fa fa-chart-line"></i></a>
                                <a href="/contratos/edit/{{ $item->id }}" class="btn btn-warning btn-sm text-white" title="Editar"><i class="fa fa-edit"></i></a>
                                <a href="/contratos/delete/{{ $item->id }}" class="btn btn-danger btn-sm" title="Excluir" onclick="return confirm('Deseja excluir este contrato?')"><i class="fa fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Nenhum contrato encontrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection