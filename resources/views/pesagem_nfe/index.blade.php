@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="card card-custom gutter-b">
        <div class="card-header"><h3 class="card-title"><i class="fa fa-search text-primary mr-2"></i> Filtros de pesagem</h3></div>
        <div class="card-body">
            <form method="GET" action="{{ route('pesagem-nfe.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-2 mb-3"><label>Data inicial</label><input type="date" name="data_inicio" class="form-control" value="{{ $data_inicio }}"></div>
                    <div class="col-md-2 mb-3"><label>Data final</label><input type="date" name="data_fim" class="form-control" value="{{ $data_fim }}"></div>
                    <div class="col-md-3 mb-3"><label>Fornecedor</label><select name="fornecedor_id" class="form-control select2"><option value="todos">Todos</option>@foreach($fornecedores as $f)<option value="{{ $f->id }}" {{ (string)$fornecedor_id === (string)$f->id ? 'selected' : '' }}>{{ $f->razao_social }}</option>@endforeach</select></div>
                    <div class="col-md-2 mb-3"><label>Unidade</label><select name="filial_id" class="form-control"><option value="todos" {{ $filial_id === 'todos' ? 'selected' : '' }}>Todas</option><option value="matriz" {{ $filial_id === 'matriz' ? 'selected' : '' }}>Matriz</option>@foreach($filiais as $filial)<option value="{{ $filial->id }}" {{ (string)$filial_id === (string)$filial->id ? 'selected' : '' }}>{{ $filial->descricao }}</option>@endforeach</select></div>
                    <div class="col-md-2 mb-3"><label>Status</label><select name="status_pesagem" class="form-control"><option value="pendentes" {{ $status_pesagem === 'pendentes' ? 'selected' : '' }}>Pendentes</option><option value="emitidas" {{ $status_pesagem === 'emitidas' ? 'selected' : '' }}>Emitidas</option><option value="todos" {{ $status_pesagem === 'todos' ? 'selected' : '' }}>Todas</option></select></div>
                    <div class="col-md-1 mb-3"><button class="btn btn-primary btn-block"><i class="fa fa-filter"></i></button></div>
                </div>
            </form>
        </div>
    </div>

    @if(session('resumo'))
        <div class="alert alert-info"><strong>Resultado:</strong> {{ session('resumo.sucesso', 0) }} processada(s). @if(count(session('resumo.erros', [])))<ul class="mb-0 mt-2">@foreach(session('resumo.erros') as $erro)<li>{{ $erro }}</li>@endforeach</ul>@endif</div>
    @endif

    <form method="POST" action="{{ route('pesagem-nfe.processar') }}">
        @csrf
        <div class="card card-custom">
            <div class="card-header bg-light"><h3 class="card-title"><i class="fa fa-file-invoice text-success mr-2"></i> Dados da NF-e de entrada</h3><div class="card-toolbar"><button class="btn btn-success"><i class="fa fa-check"></i> Processar selecionadas</button></div></div>
            <div class="card-body">
                <div class="row bg-light p-3 rounded mb-4 border">
                    <div class="col-md-3 mb-3"><label>Natureza de operação *</label><select name="natureza_id" class="form-control select2" required><option value="">Selecione...</option>@foreach($naturezas as $nat)<option value="{{ $nat->id }}">{{ $nat->natureza ?? $nat->descricao }}</option>@endforeach</select></div>
                    <div class="col-md-2 mb-3"><label>Pagamento NF-e *</label><select name="tipo_pagamento_nfe" class="form-control" required>@foreach(\App\Models\Compra::tiposPagamento() as $codigo => $descricao)<option value="{{ $codigo }}" {{ $codigo === '17' ? 'selected' : '' }}>{{ $codigo }} - {{ $descricao }}</option>@endforeach</select></div>
                    <div class="col-md-2 mb-3"><label>Emissão *</label><input type="date" name="data_emissao" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                    <div class="col-md-2 mb-3"><label>Vencimento *</label><input type="date" name="data_pagamento" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                    <div class="col-md-3 mb-3"><label>Categoria financeira</label><select name="categoria_id" class="form-control select2"><option value="">Selecione...</option>@foreach($categorias as $categoria)<option value="{{ $categoria->id }}">{{ $categoria->nome }}</option>@endforeach</select></div>
                    <div class="col-md-4 mb-3"><label>Conta bancária/caixa (quando já pago)</label><select name="conta_id" class="form-control select2"><option value="">Selecione...</option>@foreach($contas as $conta)<option value="{{ $conta->id }}">{{ $conta->nome }}</option>@endforeach</select></div>
                    <div class="col-md-4 mb-3 d-flex align-items-end"><label class="mr-4"><input type="checkbox" name="integrar_financeiro" value="1" checked> Gerar contas a pagar</label><label><input type="checkbox" name="pagamento_a_vista" value="1"> Já pago</label></div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead><tr><th width="45"><input type="checkbox" id="checkAll"></th><th>Ticket</th><th>Data</th><th>Fornecedor</th><th>Unidade</th><th>Situação</th><th>NF-e vinculada</th></tr></thead>
                        <tbody>
                        @forelse($pesagens as $p)
                            <tr>
                                <td>@if(!$p->compra_id)<input type="checkbox" name="pesagens[]" value="{{ $p->id }}" class="checkItem">@else<i class="fa fa-lock text-muted"></i>@endif</td>
                                <td><strong>#{{ $p->id }}</strong></td>
                                <td>{{ $p->dt_registro ? \Carbon\Carbon::parse($p->dt_registro)->format('d/m/Y H:i') : '--' }}</td>
                                <td>{{ $p->fornecedor_nome ?? 'Não informado' }}</td>
                                <td>{{ optional($filiais->firstWhere('id', $p->filial_id))->descricao ?? 'Matriz' }}</td>
                                <td>@if($p->compra_id)<span class="badge badge-success">Processada</span>@else<span class="badge badge-warning">Pendente</span>@endif</td>
                                <td>@if($p->nfe_numero)<strong>NF {{ $p->nfe_numero }}</strong><small class="d-block">{{ $p->nfe_estado }}</small>@elseif($p->compra_id)<span class="text-danger">Compra #{{ $p->compra_id }} sem autorização</span>@else<span class="text-muted">Sem NF-e</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4">Nenhuma pesagem encontrada.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $pesagens->links() }}</div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('javascript')
<script>$(function(){ if ($.fn.select2) $('.select2').select2(); $('#checkAll').on('change', function(){ $('.checkItem').prop('checked', this.checked); }); });</script>
@endsection
