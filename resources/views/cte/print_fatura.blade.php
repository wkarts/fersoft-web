@extends('relatorios.default')
@section('content')

<hr>

<p>Empresa: <strong>{{ $config->razao_social }}</strong> - CPF/CNPJ: <strong>{{ str_replace(" ", "", $config->cnpj) }}</strong></p>
<p>Endereço: <strong>{{ $config->rua }}, {{ $config->numero }} - {{ $config->bairro }} - 
{{ $config->municipio }} ({{ $config->UF }})</strong></p>
<p>CEP: <strong>{{ $config->cep }}</strong> - Telefone: <strong>{{ $config->fone }}</strong></p>
<hr>

<p>Número da fatura: <strong>{{ $item->numero_fatura }}</strong></p>
<p>Vencimento: <strong>{{ \Carbon\Carbon::parse($item->vencimento)->format('d/m/Y') }}</strong></p>

<h5>Valor Total: <strong>R$ {{ moeda($item->valor_total-$item->desconto) }}</strong> - {{ valor_por_extenso($item->valor_total-$item->desconto) }}</h5>
<p style="margin-top: -20px">Desconto: <strong>{{ moeda($item->desconto) }}</strong></p>

<hr>

<p>Nome do Sacado: <strong>{{ $item->remetente->razao_social }}</strong> - CPF/CNPJ: <strong>{{ $item->remetente->cpf_cnpj }}</strong></p>
<p>Endereço: <strong>{{ $item->remetente->rua }}, {{ $item->remetente->numero }} - {{ $item->remetente->bairro }} - 
{{ $item->remetente->cidade->nome }} ({{ $item->remetente->cidade->uf }})</strong></p>
<p>CEP: <strong>{{ $item->remetente->cep }}</strong> - Telefone: <strong>{{ $item->remetente->telefone }}</strong></p>


<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th width="10%" class="text-left">CTe</th>
		<th width="15%" class="text-left">Remetente</th>
		<th width="20%" class="text-left">Chave</th>
		<th width="10%" class="text-left">Valor Mercadoria</th>
		<th width="10%" class="text-left">Peso</th>
		<th width="10%" class="text-left">Frete</th>
		<th width="15%" class="text-left">Unidade</th>
	</tr>
</thead>

<tbody>
	@php
	$somaMercadoria = 0;
	$somaPeso = 0;
	$somaFrete = 0;
	@endphp
	@foreach($item->documentos as $key => $it)

	<tr class="@if($key%2 == 0) pure-table-odd @endif">
		<td>{{ $it->cte_numero }}</td>
		<td>{{ $item->remetente->razao_social }}</td>
		<td>{{ $it->cte->chave == "" ? "--" : $it->cte->chave }}</td>
		<td>{{ moeda($it->valor_mercadoria) }}</td>
		<td>{{ ($it->peso) }}</td>
		<td>{{ moeda($it->frete) }}</td>
		<td>{{ ($it->unidade) }}</td>
	</tr>
	@php
	$somaMercadoria += $it->valor_mercadoria;
	$somaPeso += $it->peso;
	$somaFrete += $it->frete;
	@endphp
	@endforeach
</tbody>

<tfoot style="font-weight: bold">
	<td colspan="2">Total de documentos: {{ sizeof($item->documentos) }}</td>
	<td></td>
	<td>R$ {{ moeda($somaMercadoria) }}</td>
	<td>{{ ($somaPeso) }}</td>
	<td>R$ {{ moeda($somaFrete) }}</td>

</tfoot>

</table>


@endsection
