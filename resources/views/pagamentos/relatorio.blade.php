@extends('default.layout')
@section('content')
<div class="card card-custom"><div class="card-body">
    <a href="{{ route('pagamentos.index') }}">Voltar aos pagamentos</a>
    <h3 class="mt-3">Relatórios Asaas · {{ $c->ambiente === 'sandbox' ? 'Homologação' : 'Produção' }}</h3>
    <form method="get"><label>Relatório <select name="relatorio" class="form-control">
    @foreach($catalogo as $item)<option value="{{ $item['id'] }}" @selected($item['id'] === $def['id'])>{{ $item['title'] }}</option>@endforeach
    </select></label> <button class="btn btn-light-primary">Selecionar</button></form>
    <p>{{ $def['description'] }}</p>
    <form method="get"><input type="hidden" name="relatorio" value="{{ $def['id'] }}">
        @if($def['dates'])
            <label>Data de <select name="filtros[date_field]" class="form-control">@foreach($def['dates'] as $chave => $label)<option value="{{ $chave }}" @selected(($filters['date_field'] ?? '') === $chave)>{{ $label }}</option>@endforeach</select></label>
            <label>Início <input type="date" name="filtros[date_from]" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></label>
            <label>Fim <input type="date" name="filtros[date_to]" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></label>
        @endif
        @foreach($def['filters'] as $field)
            <label>{{ $field['label'] }}
            @if($field['type'] === 'select')
                <select name="filtros[{{ $field['key'] }}]" class="form-control"><option value="">Todos</option>
                @foreach($field['options'] as $valor=>$label)<option value="{{ $valor }}" @selected(($filters[$field['key']] ?? $def['defaults'][$field['key']] ?? '') === $valor)>{{ $label }}</option>@endforeach</select>
            @else
                <input name="filtros[{{ $field['key'] }}]" class="form-control" value="{{ $filters[$field['key']] ?? '' }}" @required($field['required'] ?? false)>
            @endif
            </label>
        @endforeach
        <button class="btn btn-primary" name="consultar" value="1">Consultar</button>
        <button class="btn btn-light-primary" name="csv" value="1">Baixar CSV</button>
    </form>
    @if($conta)
        <details class="my-3"><summary>Importar extrato para Conciliação Bancária</summary>
        <form method="post" action="{{ route('pagamentos.importar') }}" onsubmit="return confirm('Importar o extrato desta carteira para a conta vinculada?')">@csrf
            <input type="hidden" name="conexao" value="{{ $c->webhook_id }}">
            <p>Conta bancária vinculada: <strong>{{ $conta }}</strong></p>
            <label>De <input required type="date" class="form-control" name="inicio"></label>
            <label>Até <input required type="date" class="form-control" name="fim"></label>
            <p>Os lançamentos entram como pendentes de conciliação.</p>
            <button class="btn btn-primary">Importar período</button>
        </form></details>
    @elseif($def['id'] === 'statement' && $c->ambiente === 'production')
        <p>Vincule uma conta bancária ativa nos parâmetros de pagamento do estabelecimento para importar o extrato.</p>
    @endif
    @if($dados)
        <div class="table-responsive"><table class="table"><thead><tr>@foreach($dados['columns'] as $col) @if($col['visible'])<th>{{ $col['label'] }}</th>@endif @endforeach</tr></thead><tbody>
        @forelse($dados['rows'] as $row)<tr>@foreach($dados['columns'] as $col) @if($col['visible'])<td>{{ $row[$col['key']] }}</td>@endif @endforeach</tr>
        @empty<tr><td>Nenhum lançamento encontrado.</td></tr>@endforelse
        </tbody></table></div>
        @if($dados['has_more'])<a href="{{ route('pagamentos.relatorio',['relatorio'=>$def['id'],'consultar'=>1,'filtros'=>array_merge($filters,['offset'=>$dados['next_offset']])]) }}">Próxima página</a>@endif
    @endif
</div></div>
@endsection
