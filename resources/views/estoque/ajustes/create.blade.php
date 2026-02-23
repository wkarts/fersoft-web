@extends('LAYOUT_BASE')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Novo Ajuste de Estoque</h3>
    </div>
    <div class="card-body">
        <form method="post" action="/estoque/ajustes">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Data referência</label>
                    <input type="date" class="form-control" name="data_ref" value="{{ old('data_ref', $dataRefPadrao) }}" required>
                </div>
                <div class="form-group col-md-3">
                    <label>Filial</label>
                    <select name="filial_id" class="form-control">
                        <option value="">Todas</option>
                        @foreach($filiais as $filial)
                            <option value="{{ $filial->id }}" {{ (string)old('filial_id', $filialPadrao) === (string)$filial->id ? 'selected' : '' }}>{{ $filial->descricao }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>Observação</label>
                    <input type="text" class="form-control" name="observacao" value="{{ old('observacao') }}">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered" id="itens-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Custo Unitário</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select class="form-control" name="itens[0][produto_id]" required>
                                    <option value="">Selecione</option>
                                    @foreach($produtos as $produto)
                                        <option value="{{ $produto->id }}">{{ $produto->nome }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="form-control" name="itens[0][tipo]" required>
                                    <option value="entrada">Entrada</option>
                                    <option value="saida">Saída</option>
                                </select>
                            </td>
                            <td><input type="number" min="0.0001" step="0.0001" class="form-control" name="itens[0][quantidade]" required></td>
                            <td><input type="number" min="0" step="0.000001" class="form-control" name="itens[0][custo_unitario]"></td>
                            <td><button type="button" class="btn btn-sm btn-light-danger btn-remove">X</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="button" class="btn btn-light-primary" id="btn-add-item">Adicionar item</button>
            <button type="submit" class="btn btn-success">Salvar ajuste</button>
            <a href="/estoque/ajustes" class="btn btn-light">Cancelar</a>
        </form>
    </div>
</div>

<script>
    (function () {
        const tableBody = document.querySelector('#itens-table tbody');
        const addBtn = document.getElementById('btn-add-item');
        let idx = 1;

        const produtosHtml = `@foreach($produtos as $produto)<option value="{{ $produto->id }}">{{ $produto->nome }}</option>@endforeach`;

        addBtn.addEventListener('click', function () {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <select class="form-control" name="itens[${idx}][produto_id]" required>
                        <option value="">Selecione</option>
                        ${produtosHtml}
                    </select>
                </td>
                <td>
                    <select class="form-control" name="itens[${idx}][tipo]" required>
                        <option value="entrada">Entrada</option>
                        <option value="saida">Saída</option>
                    </select>
                </td>
                <td><input type="number" min="0.0001" step="0.0001" class="form-control" name="itens[${idx}][quantidade]" required></td>
                <td><input type="number" min="0" step="0.000001" class="form-control" name="itens[${idx}][custo_unitario]"></td>
                <td><button type="button" class="btn btn-sm btn-light-danger btn-remove">X</button></td>
            `;
            tableBody.appendChild(tr);
            idx++;
        });

        tableBody.addEventListener('click', function (event) {
            if (!event.target.classList.contains('btn-remove')) return;
            if (tableBody.querySelectorAll('tr').length === 1) return;
            event.target.closest('tr').remove();
        });
    })();
</script>
@endsection
