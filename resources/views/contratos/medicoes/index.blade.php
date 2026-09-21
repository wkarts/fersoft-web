@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div><h3>Medições / Faturamentos</h3><div class="text-muted">Lançamentos vinculados a contratos ou serviços avulsos.</div></div>
        <div>
            <a href="{{ route('contratos.medicoes.create.avulso') }}" class="btn btn-primary">+ Novo Lançamento</a>
            <a href="/contratos" class="btn btn-light">Contratos</a>
        </div>
    </div>

    @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
    @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

    <div class="card card-custom gutter-b">
        <div class="card-body">
            <form method="GET" action="{{ route('contratos.medicoes.index') }}" class="mb-4">
                <div class="row">
                    <div class="col-lg-3 form-group">
                        <label>Cliente</label>
                        <select name="cliente_id" class="form-control select2">
                            <option value="">Todos</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>{{ $cliente->razao_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 form-group">
                        <label>Contrato</label>
                        <select name="contrato_eng_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach($contratos as $contrato)
                                <option value="{{ $contrato->id }}" {{ request('contrato_eng_id') == $contrato->id ? 'selected' : '' }}>#{{ $contrato->numero_contrato ?: $contrato->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 form-group"><label>Inicial</label><input type="date" name="data_inicial" class="form-control" value="{{ request('data_inicial') }}"></div>
                    <div class="col-lg-2 form-group"><label>Final</label><input type="date" name="data_final" class="form-control" value="{{ request('data_final') }}"></div>
                    <div class="col-lg-2 form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach(['Pendente','Em Andamento','Finalizado','Cancelado'] as $status)
                                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 text-right"><button class="btn btn-primary">Filtrar</button> <a href="{{ route('contratos.medicoes.index') }}" class="btn btn-light">Limpar</a></div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr><th>#</th><th>Cliente</th><th>Contrato</th><th>Data</th><th class="text-right">Valor</th><th>Status</th><th style="min-width:310px">Ações</th></tr></thead>
                    <tbody>
                    @forelse($medicoes as $m)
                        <tr>
                            <td>{{ $m->id }}</td>
                            <td>{{ optional($m->cliente)->razao_social ?: '—' }}</td>
                            <td>{{ optional($m->contrato)->numero_contrato ?: ($m->contrato_eng_id ? '#'.$m->contrato_eng_id : 'Avulso') }}</td>
                            <td>{{ optional($m->data_faturamento)->format('d/m/Y') ?: '—' }}</td>
                            <td class="text-right">R$ {{ number_format((float)$m->valor_total,2,',','.') }}</td>
                            <td>{{ $m->status }}</td>
                            <td>
                                <a target="_blank" href="{{ route('contratos.medicoes.imprimir',$m->id) }}" class="btn btn-sm btn-secondary">Imprimir</a>
                                <a target="_blank" href="{{ route('contratos.medicoes.pdf',$m->id) }}" class="btn btn-sm btn-dark">PDF</a>
                                <a href="{{ route('contratos.medicoes.whatsapp',$m->id) }}" class="btn btn-sm btn-success">WhatsApp</a>
                                <a href="{{ route('contratos.medicoes.email',$m->id) }}" class="btn btn-sm btn-info">E-mail</a>
                                @if($m->status !== 'Finalizado')
                                    <a href="{{ route('contratos.medicoes.edit',$m->id) }}" class="btn btn-sm btn-warning">Editar</a>
                                @endif
                                <a href="{{ route('contratos.medicoes.destroy',$m->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Excluir a medição e títulos ainda não recebidos?')">Excluir</a>
                            </td>
                        </tr>
                    @empty<tr><td colspan="7" class="text-center text-muted py-5">Nenhum lançamento encontrado.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
            {{ $medicoes->appends(request()->query())->links() }}
        </div>
    </div>
</div>

@if(session('imprimir_id'))
<script>
window.addEventListener('load', () => window.open('{{ route('contratos.medicoes.imprimir',session('imprimir_id')) }}','_blank'));
</script>
@endif
@endsection
