@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h3 class="mb-1">Contratos de Locação / Serviços</h3>
            <div class="text-muted">Contratos, obras, medições, equipe e resultado financeiro.</div>
        </div>
        <div class="mt-2">
            <a href="{{ route('contratos.dashboard-dre') }}" class="btn btn-light-primary mr-2">
                <i class="la la-chart-bar"></i> Dashboard / DRE
            </a>
            <a href="/contratos/medicoes" class="btn btn-light-info mr-2">
                <i class="la la-file-invoice-dollar"></i> Medições
            </a>
            <a href="/contratos/new" class="btn btn-primary">
                <i class="la la-plus"></i> Novo Contrato
            </a>
        </div>
    </div>

    @if(session('mensagem_sucesso'))
        <div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>
    @endif
    @if(session('mensagem_erro'))
        <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
    @endif

    <div class="card card-custom gutter-b">
        <div class="card-body">
            <form method="POST" action="/contratos/list" class="mb-5">
                @csrf
                <div class="row">
                    <div class="col-lg-4 form-group">
                        <label>Cliente</label>
                        <select name="cliente_id" class="form-control select2">
                            <option value="">Todos</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ (string)($search['cliente_id'] ?? '') === (string)$cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->razao_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 form-group">
                        <label>Filial / Matriz</label>
                        <select name="filial_id" class="form-control">
                            <option value="">Todas</option>
                            <option value="matriz" {{ ($search['filial_id'] ?? '') === 'matriz' ? 'selected' : '' }}>Matriz</option>
                            @foreach($filiaisLista as $filial)
                                <option value="{{ $filial->id }}" {{ (string)($search['filial_id'] ?? '') === (string)$filial->id ? 'selected' : '' }}>
                                    {{ $filial->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach(['Ativo','Finalizado','Suspenso','Cancelado'] as $status)
                                <option value="{{ $status }}" {{ ($search['status'] ?? '') === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 form-group d-flex align-items-end">
                        <button class="btn btn-primary btn-block"><i class="la la-search"></i> Filtrar</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Nº Contrato</th>
                        <th>Cliente</th>
                        <th>Filial</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th class="text-right">Valor</th>
                        <th>Status</th>
                        <th style="min-width:260px">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($data as $contrato)
                        <tr>
                            <td>{{ $contrato->id }}</td>
                            <td><strong>{{ $contrato->numero_contrato ?: $contrato->id }}</strong></td>
                            <td>{{ optional($contrato->cliente)->razao_social ?: '—' }}</td>
                            <td>{{ optional($contrato->filial)->descricao ?: 'Matriz' }}</td>
                            <td>{{ optional($contrato->data_inicio)->format('d/m/Y') ?: '—' }}</td>
                            <td>{{ optional($contrato->data_fim)->format('d/m/Y') ?: '—' }}</td>
                            <td class="text-right">R$ {{ number_format((float)$contrato->valor_contrato, 2, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $contrato->status === 'Ativo' ? 'success' : 'secondary' }}">{{ $contrato->status }}</span></td>
                            <td>
                                <a href="{{ route('contratos.detalhes', $contrato->id) }}" class="btn btn-sm btn-info">Detalhes</a>
                                <a href="/contratos/medicoes/create/{{ $contrato->id }}" class="btn btn-sm btn-success">Medição</a>
                                <a href="/contratos/edit/{{ $contrato->id }}" class="btn btn-sm btn-warning">Editar</a>
                                <a href="/contratos/delete/{{ $contrato->id }}" class="btn btn-sm btn-danger" onclick="return confirm('Excluir este contrato sem medições?')">Excluir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5">Nenhum contrato encontrado.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
