<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>{{ $title ?? 'Medição' }}</title>
<style>
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:12px;color:#222}
h1,h2,h3{margin:0 0 8px}.muted{color:#666}.row{display:table;width:100%;margin-bottom:12px}.col{display:table-cell;vertical-align:top}
table{width:100%;border-collapse:collapse;margin:12px 0}th,td{border:1px solid #bbb;padding:6px}th{background:#eee}.right{text-align:right}.summary{font-size:14px;font-weight:bold}
</style>
</head>
<body>
<h2>{{ $empresa->nome ?? $empresa->razao_social ?? 'FERSOFT WEB' }}</h2>
<div class="muted">Medição / Faturamento #{{ $fatura->id }}</div>
<hr>
<div class="row">
    <div class="col"><strong>Cliente:</strong> {{ optional($fatura->cliente)->razao_social ?: '—' }}</div>
    <div class="col"><strong>Contrato:</strong> {{ optional($fatura->contrato)->numero_contrato ?: ($fatura->contrato_eng_id ?: 'Avulso') }}</div>
</div>
<div class="row">
    <div class="col"><strong>Data:</strong> {{ optional($fatura->data_faturamento)->format('d/m/Y') ?: '—' }}</div>
    <div class="col"><strong>Status:</strong> {{ $fatura->status }}</div>
</div>
@if($fatura->codigo_obra || $fatura->cidadePrestacao)
<div><strong>Obra:</strong> {{ $fatura->codigo_obra }} {{ optional($fatura->cidadePrestacao)->nome ? '- '.optional($fatura->cidadePrestacao)->nome : '' }}</div>
@endif

<h3>Itens</h3>
<table>
<thead><tr><th>Tipo</th><th>Descrição</th><th class="right">Qtd.</th><th class="right">Valor Unit.</th><th class="right">Total</th></tr></thead>
<tbody>
@forelse($fatura->itens as $item)
<tr>
    <td>{{ $item->tipo_item }}</td>
    <td>{{ optional($item->servico)->nome ?: optional($item->produto)->nome ?: 'Item' }}</td>
    <td class="right">{{ number_format((float)$item->quantidade,2,',','.') }}</td>
    <td class="right">R$ {{ number_format((float)$item->valor_unitario,2,',','.') }}</td>
    <td class="right">R$ {{ number_format((float)$item->valor_total,2,',','.') }}</td>
</tr>
@empty<tr><td colspan="5">Sem itens detalhados.</td></tr>@endforelse
</tbody>
</table>

@if($fatura->funcionarios->count())
<h3>Equipe</h3>
<table>
<thead><tr><th>Funcionário</th><th>Função</th><th>Diárias</th><th>Valor diária</th><th>Total</th></tr></thead>
<tbody>
@foreach($fatura->funcionarios as $func)
<tr><td>{{ optional($func->funcionario)->nome }}</td><td>{{ $func->funcao }}</td><td>{{ $func->diarias }}</td><td>R$ {{ number_format((float)$func->valor_diaria,2,',','.') }}</td><td>R$ {{ number_format((float)$func->valor_total,2,',','.') }}</td></tr>
@endforeach
</tbody>
</table>
@endif

@if($parcelas->count())
<h3>Parcelas</h3>
<table>
<thead><tr><th>Vencimento</th><th>Status</th><th class="right">Valor</th></tr></thead>
<tbody>
@foreach($parcelas as $parcela)
<tr><td>{{ $parcela->data_vencimento ? CarbonCarbon::parse($parcela->data_vencimento)->format('d/m/Y') : '—' }}</td><td>{{ $parcela->status ? 'Recebida' : 'Aberta' }}</td><td class="right">R$ {{ number_format((float)$parcela->valor_integral,2,',','.') }}</td></tr>
@endforeach
</tbody>
</table>
@endif

<table>
<tr><th>Valor Total</th><td class="right summary">R$ {{ number_format((float)$fatura->valor_total,2,',','.') }}</td></tr>
<tr><th>Retenção</th><td class="right">R$ {{ number_format((float)$fatura->valor_retencao,2,',','.') }}</td></tr>
<tr><th>Valor Líquido</th><td class="right summary">R$ {{ number_format((float)$fatura->valor_liquido,2,',','.') }}</td></tr>
</table>
@if($fatura->observacao)<p><strong>Observação:</strong> {{ $fatura->observacao }}</p>@endif
</body>
</html>
