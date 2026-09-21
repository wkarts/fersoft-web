@extends('default.layout')
@section('content')
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
    <div><h3>Central de Emissão de MTR</h3><div class="text-muted">MTR 3.0 - SINIR / IEMA</div></div>
    <div>
        <a href="{{ route('mtr.recepcao.index') }}" class="btn btn-light-info">Recepção</a>
        <a href="{{ route('mtr.emissao.create.avulso') }}" class="btn btn-primary">+ MTR Avulso</a>
    </div>
</div>
@if(session('sucesso'))<div class="alert alert-success">{{ session('sucesso') }}</div>@endif
@if(session('erro'))<div class="alert alert-danger">{{ session('erro') }}</div>@endif

<div class="card card-custom gutter-b"><div class="card-body">
<form method="GET" action="{{ route('mtr.emissao.index') }}">
<div class="row">
    <div class="col-lg-3 form-group"><label>MTR / código</label><input name="numero_mtr" class="form-control" value="{{ request('numero_mtr') }}"></div>
    <div class="col-lg-3 form-group"><label>Envolvido</label><input name="envolvido" class="form-control" value="{{ request('envolvido') }}"></div>
    <div class="col-lg-2 form-group"><label>Transportador</label><input name="transportadora" class="form-control" value="{{ request('transportadora') }}"></div>
    <div class="col-lg-2 form-group"><label>Status</label><select name="status" class="form-control"><option value="">Todos</option>@foreach(['rascunho','transmitido','cancelado','erro'] as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ $s }}</option>@endforeach</select></div>
    <div class="col-lg-2 form-group d-flex align-items-end"><button class="btn btn-primary btn-block">Filtrar</button></div>
</div>
</form>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>#</th><th>Nº MTR</th><th>Gerador</th><th>Destinador</th><th>Data</th><th>Status</th><th style="min-width:360px">Ações</th></tr></thead>
<tbody>
@forelse($manifestos as $m)
<tr>
<td>{{ $m->id }}</td><td>{{ $m->numero_mtr ?: $m->seu_codigo }}</td><td>{{ $m->gerador_nome }}</td><td>{{ $m->destinador_nome }}</td>
<td>{{ optional($m->data_expedicao)->format('d/m/Y H:i') }}</td><td>{{ $m->status }}</td>
<td>
@if($m->status === 'rascunho')
<form method="POST" action="{{ route('mtr.emissao.transmitir',$m->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Transmitir</button></form>
<a href="{{ route('mtr.emissao.edit',$m->id) }}" class="btn btn-sm btn-warning">Editar</a>
<a href="{{ route('mtr.emissao.destroy',$m->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Excluir rascunho?')">Excluir</a>
@endif
@if($m->numero_mtr)
<a target="_blank" href="{{ route('mtr.emissao.pdf',$m->numero_mtr) }}" class="btn btn-sm btn-dark">PDF</a>
<a target="_blank" href="{{ route('mtr.emissao.cdf',$m->numero_mtr) }}" class="btn btn-sm btn-secondary">CDF</a>
<button type="button" class="btn btn-sm btn-info js-mtr-status" data-id="{{ $m->id }}">Status</button>
<button type="button" class="btn btn-sm btn-success js-mtr-whatsapp" data-id="{{ $m->id }}">WhatsApp</button>
@endif
</td>
</tr>
@empty<tr><td colspan="7" class="text-center text-muted py-5">Nenhum MTR encontrado.</td></tr>@endforelse
</tbody>
</table>
</div>
{{ $manifestos->links() }}
</div></div>

<div class="row">
<div class="col-lg-6">
<div class="card card-custom"><div class="card-header"><h4 class="card-title">Importar de NF-e / Venda</h4></div><div class="card-body table-responsive">
<table class="table table-sm"><thead><tr><th>#</th><th>Cliente</th><th></th></tr></thead><tbody>
@forelse($vendasImportacao as $v)<tr><td>{{ $v->id }}</td><td>{{ $v->cliente_nome }}</td><td><a href="{{ route('mtr.emissao.create.nfe',$v->id) }}" class="btn btn-sm btn-primary">Importar</a></td></tr>
@empty<tr><td colspan="3" class="text-muted">Nenhuma venda disponível.</td></tr>@endforelse
</tbody></table>
</div></div>
</div>
<div class="col-lg-6">
<div class="card card-custom"><div class="card-header"><h4 class="card-title">Importar de Pesagem</h4></div><div class="card-body table-responsive">
<table class="table table-sm"><thead><tr><th>#</th><th>Cliente</th><th>Peso</th><th></th></tr></thead><tbody>
@forelse($ticketsImportacao as $t)<tr><td>{{ $t->id }}</td><td>{{ $t->cliente_nome }}</td><td>{{ number_format((float)$t->peso_calculado,2,',','.') }}</td><td><a href="{{ route('mtr.emissao.create.pesagem',$t->id) }}" class="btn btn-sm btn-primary">Importar</a></td></tr>
@empty<tr><td colspan="4" class="text-muted">Nenhuma pesagem disponível.</td></tr>@endforelse
</tbody></table>
</div></div>
</div>
</div>
</div>

<script>
document.addEventListener('click', async function(e){
    const statusBtn = e.target.closest('.js-mtr-status');
    if (statusBtn) {
        try {
            const r = await fetch('/mtr/emissao/consultar/'+statusBtn.dataset.id,{headers:{'Accept':'application/json'},credentials:'same-origin'});
            const d = await r.json();
            alert(d.message || d.situacao_descricao || 'Consulta concluída.');
            if (d.success) location.reload();
        } catch(err) { alert(err.message); }
    }

    const whats = e.target.closest('.js-mtr-whatsapp');
    if (whats) {
        const number = prompt('WhatsApp de destino com DDD:');
        if (!number) return;
        try {
            const r = await fetch('{{ route('mtr.emissao.whatsapp') }}',{
                method:'POST',credentials:'same-origin',
                headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body:JSON.stringify({mtr_id:whats.dataset.id,whatsapp:number})
            });
            const d=await r.json(); alert(d.message || 'Operação concluída.');
        } catch(err){alert(err.message);}
    }
});
</script>
@endsection
