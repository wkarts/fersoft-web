@extends('relatorios.default')
@section('content')

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th class="text-left">Razão social</th>
		<th class="text-left">Nome fantasia</th>
		<th class="text-left">CPF/CNPJ</th>
		<th class="text-left">IE</th>
		<th class="text-left">Endereço</th>
		<th class="text-left">CEP</th>
		<th class="text-left">Cidade</th>
		<th class="text-left">UF</th>
		<th class="text-left">IE</th>
		<!-- <th class="text-left">Email</th> -->
		<th class="text-left">Telefone</th>
	</tr>
</thead>

<tbody>
	@foreach($data as $item)
	<tr>
		<td>{{ $item->razao_social }}</td>
		<td>{{ $item->nome_fantasia }}</td>
		<td>{{ $item->cpf_cnpj }}</td>
		<td>{{ $item->ie_rg }}</td>
		<td>{{ $item->rua }}, {{ $item->numero }} - {{ $item->bairro }}</td>
		<td>{{ $item->cep }}</td>
		<td>{{ $item->cidade->nome }}</td>
		<td>{{ $item->cidade->uf }}</td>
		<td>{{ $item->ie_rg }}</td>
		<!-- <td>{{ $item->email }}</td> -->
		<td>{{ $item->telefone }}</td>

	</tr>
	@endforeach
</tbody>
</table>

@endsection
