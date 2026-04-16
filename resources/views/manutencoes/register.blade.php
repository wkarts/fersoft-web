@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <form method="post" action="/manutencoes/save">
            @csrf
            <input type="hidden" name="manutencao_id" value="{{ $data->manutencao_id ?? '' }}">

            <h3 class="card-title text-center">{{ $title }}</h3>

            <div class="row mt-5">
                <div class="form-group col-lg-3">
                    <label>Veículo *</label>
                    <select name="veiculo_id" id="select_veiculo" class="form-control" required>
                        <option value="">Selecione...</option>
                        @foreach($veiculos as $v)
                            <option value="{{ $v->id }}" data-km="{{ $v->quilometragem }}" {{ ($data->veiculo_id ?? '') == $v->id ? 'selected' : '' }}>
                                {{ $v->placa }} - {{ $v->marca }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-2">
                    <label>KM Atual</label>
                    <input type="number" name="km_registro" id="input_km" class="form-control" value="{{ $data->km_registro ?? '' }}">
                </div>
                <div class="form-group col-lg-2">
                    <label>Data *</label>
                    <input type="date" name="data_manutencao" class="form-control" value="{{ isset($data->data_manutencao) ? \Carbon\Carbon::parse($data->data_manutencao)->format('Y-m-d') : date('Y-m-d') }}" required>
                </div>
                <div class="form-group col-lg-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="Aguardando" {{ ($data->status ?? '') == 'Aguardando' ? 'selected' : '' }}>Aguardando</option>
                        <option value="Em andamento" {{ ($data->status ?? '') == 'Em andamento' ? 'selected' : '' }}>Em andamento</option>
                        <option value="Finalizado" {{ ($data->status ?? '') == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                    </select>
                </div>
                <div class="form-group col-lg-3">
                    <label>Prioridade</label>
                    <select name="prioridade" class="form-control">
                        <option value="Normal" {{ ($data->prioridade ?? '') == 'Normal' ? 'selected' : '' }}>Normal</option>
                        <option value="Alta" {{ ($data->prioridade ?? '') == 'Alta' ? 'selected' : '' }}>Alta</option>
                        <option value="Urgente" {{ ($data->prioridade ?? '') == 'Urgente' ? 'selected' : '' }}>Urgente</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="form-group col-lg-4">
                    <label>Mecânico / Responsável</label>
                    <select name="responsavel_id" class="form-control">
                        <option value="">Selecione...</option>
                        @foreach($funcionarios as $f)
                            <option value="{{ $f->id }}" {{ ($data->responsavel_id ?? '') == $f->id ? 'selected' : '' }}>{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-4">
                    <label>Tipo de Execução</label>
                    <div class="radio-inline mt-2">
                        <label class="radio"><input type="radio" name="tipo_execucao" value="Interno" {{ ($data->tipo_execucao ?? 'Interno') == 'Interno' ? 'checked' : '' }}><span></span> Interno</label>
                        <label class="radio"><input type="radio" name="tipo_execucao" value="Externo" {{ ($data->tipo_execucao ?? '') == 'Externo' ? 'checked' : '' }}><span></span> Externo</label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="form-group col-lg-6">
                    <label>Descrição do Problema</label>
                    <textarea name="descricao" class="form-control" rows="3">{{ $data->descricao ?? '' }}</textarea>
                </div>
                <div class="form-group col-lg-6">
                    <label>Checklist do Veículo</label>
                    <textarea name="checklist" class="form-control" rows="3">{{ is_array($data->checklist ?? '') ? implode("\n", $data->checklist) : ($data->checklist ?? '') }}</textarea>
                </div>
            </div>

            <hr>
            <h4 class="mb-4">Adicionar Peças e Custos</h4>
            <div class="row align-items-end bg-light p-4 rounded">
                <div class="form-group col-lg-6">
                    <label>Buscar Peça/Produto</label>
                    <select id="select_produto" class="form-control select2" style="width: 100%"></select>
                </div>
                <div class="form-group col-lg-2">
                    <label>Qtd.</label>
                    <input type="number" id="quantidade_item" class="form-control" value="1">
                </div>
                <div class="form-group col-lg-2">
                    <label>Custo Unit.</label>
                    <input type="text" id="valor_item" class="form-control" value="0.00">
                </div>
                <div class="form-group col-lg-2">
                    <button type="button" id="btn-add-item" class="btn btn-success w-100">Adicionar</button>
                </div>
            </div>

            <table class="table table-bordered mt-4" id="tabela_itens">
                <thead>
                    <tr>
                        <th>Produto / Peça</th>
                        <th>Qtd</th>
                        <th>Custo Unit.</th>
                        <th>Subtotal</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($data) && $data->itens)
                        @foreach($data->itens as $item)
                            <tr>
                                <td>
                                    <input type="hidden" name="produtos_ids[]" value="{{ $item->produto_id }}">
                                    <input type="hidden" name="produtos_nomes[]" value="{{ $item->produto->nome ?? $item->descricao }}">
                                    {{ $item->produto->nome ?? $item->descricao }}
                                </td>
                                <td><input type="hidden" name="quantidades[]" value="{{ $item->quantidade }}">{{ $item->quantidade }}</td>
                                <td><input type="hidden" name="valores[]" value="{{ $item->valor_unitario }}">{{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                                <td><input type="hidden" name="subtotais[]" value="{{ $item->subtotal }}">{{ number_format($item->subtotal, 2, ',', '.') }}</td>
                                <td><button type="button" class="btn btn-danger btn-xs remover-item" data-subtotal="{{ $item->subtotal }}"><i class="la la-trash"></i></button></td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>

            <div class="row justify-content-end mt-4">
                <div class="col-lg-3 text-right">
                    <label class="font-weight-bold">Total (R$):</label>
                    <input type="text" name="custo" id="custo_total_input" class="form-control text-right text-danger font-weight-bold" value="{{ $data->custo ?? '0.00' }}" readonly>
                </div>
            </div>

            <div class="card-footer text-right">
                <a href="/manutencoes" class="btn btn-light-danger">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar Manutenção</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let totalGeral = parseFloat($('#custo_total_input').val()) || 0;

    $(document).ready(function() {
        // BUSCA AUTOMÁTICA DE KM
        $('#select_veiculo').change(function() {
            let km = $(this).find(':selected').data('km');
            $('#input_km').val(km);
        });

        // SELECT2 DE PRODUTOS
        $('#select_produto').select2({
            ajax: {
                url: '/manutencoes/buscar-produtos',
                dataType: 'json',
                delay: 250,
                processResults: function (data) { return { results: data.results }; }
            }
        });

        // PREÇO AUTOMÁTICO
        $('#select_produto').on('select2:select', function (e) {
            $('#valor_item').val(e.params.data.valor);
        });
    });

    $('#btn-add-item').click(function() {
        let data = $('#select_produto').select2('data')[0];
        if(!data) return alert('Selecione um produto');

        let qtd = parseFloat($('#quantidade_item').val());
        let valor = parseFloat($('#valor_item').val());
        let subtotal = qtd * valor;

        let linha = `<tr>
            <td>
                <input type="hidden" name="produtos_ids[]" value="${data.id}">
                <input type="hidden" name="produtos_nomes[]" value="${data.text}">
                ${data.text}
            </td>
            <td><input type="hidden" name="quantidades[]" value="${qtd}">${qtd}</td>
            <td><input type="hidden" name="valores[]" value="${valor}">${valor.toFixed(2)}</td>
            <td><input type="hidden" name="subtotais[]" value="${subtotal}">${subtotal.toFixed(2)}</td>
            <td><button type="button" class="btn btn-danger btn-xs remover-item" data-subtotal="${subtotal}"><i class="la la-trash"></i></button></td>
        </tr>`;

        $('#tabela_itens tbody').append(linha);
        totalGeral += subtotal;
        $('#custo_total_input').val(totalGeral.toFixed(2));
    });

    $(document).on('click', '.remover-item', function() {
        totalGeral -= $(this).data('subtotal');
        $('#custo_total_input').val(totalGeral.toFixed(2));
        $(this).closest('tr').remove();
    });
</script>
@endsection