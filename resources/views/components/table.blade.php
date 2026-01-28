<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="col-12">
            <a href="{{ $newItemUrl }}" class="btn btn-success">Novo Registro</a>
        </div>
        <br>
        <h3>{{ $title }}</h3>
        <a href="{{ $newItemUrl }}">Novo Registro</a>
        <table>
            <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            @foreach($records as $record)
                <tr>
                    @foreach($fields as $field)
                        <td>{{ $record->$field }}</td>
                    @endforeach
                    <td>
                        <a href="{{ $editUrl }}/{{ $record->id }}">Editar</a>
                        <a href="{{ $deleteUrl }}/{{ $record->id }}">Excluir</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
