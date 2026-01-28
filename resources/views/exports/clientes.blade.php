<table>
    <thead>
        <tr>
            <th>RAZÃO SOCIAL</th>
            <th>NOME FANTASIA</th>
            <th>CPF/CNPJ</th>
            <th>IE</th>
            <th>RUA</th>
            <th>NÚMERO</th>
            <th>BAIRRO</th>
            <th>CEP</th>
            <th>CIDADE</th>
            <th>UF</th>
            <th>TELEFONE</th>
            <th>EMAIL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $item)
        <tr>
            <td>{{ $item->razao_social }}</td>
            <td>{{ $item->nome_fantasia }}</td>
            <td>{{ $item->cpf_cnpj }}</td>
            <td>{{ $item->ie_rg }}</td>
            <td>{{ $item->rua }}</td>
            <td>{{ $item->numero }}</td>
            <td>{{ $item->bairro }}</td>
            <td>{{ $item->cep }}</td>
            <td>{{ $item->cidade->nome }}</td>
            <td>{{ $item->cidade->uf }}</td>
            <td>{{ $item->telefone }}</td>
            <td>{{ $item->email }}</td>

        </tr>
        @endforeach
    </tbody>
</table>