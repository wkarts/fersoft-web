@extends('default.layout')
@section('content')

<style>
    .select2-container .select2-selection--single {
        height: 38px !important; 
        border: 1px solid #ced4da !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px !important;
        color: #495057 !important;
        padding-left: 12px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>

<div class="d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="card card-custom gutter-b example example-compact">
        <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
            <div class="col-lg-12">
                <br>
                <form method="post" action="{{ isset($data->id) ? $actionUpdate : $actionSave }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $data->id ?? '' }}">
                    
                    <div class="card-header text-center">
                        <h3 class="card-title">{{ $title }}</h3>
                    </div>

                    <div class="kt-section kt-section--first mt-5">
                        <div class="kt-section__body">
                            
                            <div class="row">
                                <div class="form-group col-lg-4">
                                    <label class="col-form-label">Veículo</label>
                                    <select id="veiculo_id" class="form-control select2" name="veiculo_id" required>
                                        <option value="">Selecione um Veículo</option>
                                        @foreach($veiculos as $veiculo)
                                            <option value="{{ $veiculo->id }}" data-km="{{ $veiculo->quilometragem }}"
                                                {{ old('veiculo_id', $data->veiculo_id ?? '') == $veiculo->id ? 'selected' : '' }}>
                                                {{ $veiculo->placa }} - {{ $veiculo->modelo }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-lg-4">
                                    <label class="col-form-label">Tipo de Movimentação</label>
                                    <select class="form-control select2" name="tipo_movimentacao_id" required>
                                        <option value="">Selecione o Tipo</option>
                                        @foreach($tipos as $t)
                                            <option value="{{ $t->id }}" {{ old('tipo_movimentacao_id', $data->tipo_movimentacao_id ?? '') == $t->id ? 'selected' : '' }}>
                                                {{ $t->nome }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-lg-4">
                                    <label class="col-form-label">Motorista</label>
                                    <select class="form-control select2" name="motorista_id" required>
                                        <option value="">Selecione um Motorista</option>
                                        @foreach($funcionarios as $f)
                                            <option value="{{ $f->id }}" {{ old('motorista_id', $data->motorista_id ?? '') == $f->id ? 'selected' : '' }}>
                                                {{ $f->nome }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group col-lg-3">
                                    <label class="col-form-label">Ajudante (Opcional)</label>
                                    <select class="form-control select2" name="ajudante_id">
                                        <option value="">Selecione um Ajudante</option>
                                        @foreach($funcionarios as $f)
                                            <option value="{{ $f->id }}" {{ old('ajudante_id', $data->ajudante_id ?? '') == $f->id ? 'selected' : '' }}>
                                                {{ $f->nome }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-lg-3">
                                    <label class="col-form-label">Cliente (Opcional)</label>
                                    <select name="cliente_id" class="form-control select2">
                                        <option value="">Nenhum Cliente</option>
                                        @foreach($clientes as $c)
                                            <option value="{{ $c->id }}" {{ (old('cliente_id', $data->cliente_id ?? '')) == $c->id ? 'selected' : '' }}>
                                                {{ $c->razao_social }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-lg-3">
                                    <label class="col-form-label">Fornecedor (Opcional)</label>
                                    <select name="fornecedor_id" class="form-control select2">
                                        <option value="">Nenhum Fornecedor</option>
                                        @foreach($fornecedores as $f)
                                            <option value="{{ $f->id }}" {{ (old('fornecedor_id', $data->fornecedor_id ?? '')) == $f->id ? 'selected' : '' }}>
                                                {{ $f->razao_social }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-lg-3">
                                    <label class="col-form-label">Destino / Rota</label>
                                    <input type="text" class="form-control" name="destino" value="{{ old('destino', $data->destino ?? '') }}" placeholder="Ex: Rota 01 - Bahia">
                                </div>
                            </div>

                            <hr>
                            <h5 class="mt-4 mb-4">Controle de Percurso</h5>
                            <div class="row">
                                <div class="form-group col-lg-3 col-md-6">
                                    <label class="col-form-label">Data/Hora Saída</label>
                                    <input type="datetime-local" class="form-control" name="data_hora_saida" 
                                        value="{{ old('data_hora_saida', isset($data->data_hora_saida) ? \Carbon\Carbon::parse($data->data_hora_saida)->format('Y-m-d\TH:i') : date('Y-m-d\TH:i')) }}">
                                </div>

                                <div class="form-group col-lg-3 col-md-6">
                                    <label class="col-form-label">KM Inicial</label>
                                    <input type="number" id="km_inicial" class="form-control" name="km_inicial" value="{{ old('km_inicial', $data->km_inicial ?? '') }}">
                                </div>

                                <div class="form-group col-lg-3 col-md-6">
                                    <label class="col-form-label">Data/Hora Chegada</label>
                                    <input type="datetime-local" class="form-control" name="data_hora_chegada" 
                                        value="{{ old('data_hora_chegada', isset($data->data_hora_chegada) ? \Carbon\Carbon::parse($data->data_hora_chegada)->format('Y-m-d\TH:i') : '') }}">
                                </div>

                                <div class="form-group col-lg-3 col-md-6">
                                    <label class="col-form-label">KM Final</label>
                                    <input type="number" class="form-control" name="km_final" value="{{ old('km_final', $data->km_final ?? '') }}">
                                </div>
                            </div>

                            <hr>
                            <h5 class="mt-4 mb-4">Abastecimentos da Viagem</h5>
                            <div class="row" style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                                <div class="col-12 text-right mb-2">
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-add-abastecimento">
                                        <i class="la la-plus"></i> Adicionar Abastecimento
                                    </button>
                                </div>
                                <div class="col-12">
                                    <table class="table table-bordered table-striped" id="tabela_abastecimentos">
                                        <thead>
                                            <tr class="bg-secondary text-white">
                                                <th width="20%">Produto</th>
                                                <th width="10%">Local</th>
                                                <th width="12%">Data</th>
                                                <th width="10%">KM Atual</th>
                                                <th width="12%">Litros</th>
                                                <th width="12%">Vl. Unit.</th>
                                                <th width="14%">Subtotal</th>
                                                <th width="8%">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(isset($data) && $data->abastecimentos)
                                                @foreach($data->abastecimentos as $key => $item)
                                                    <tr id="row_old_{{ $key }}">
                                                        <td>
                                                            <select name="abastecimentos[old_{{ $key }}][produto_id]" class="form-control select2">
                                                                @foreach($combustiveis as $c)
                                                                    <option value="{{ $c->id }}" {{ $item->produto_id == $c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="abastecimentos[old_{{ $key }}][tipo]" class="form-control">
                                                                <option value="externo" {{ $item->tipo == 'externo' ? 'selected' : '' }}>Externo</option>
                                                                <option value="interno" {{ $item->tipo == 'interno' ? 'selected' : '' }}>Interno</option>
                                                            </select>
                                                        </td>
                                                        <td><input type="date" name="abastecimentos[old_{{ $key }}][data]" class="form-control" value="{{ $item->data_abastecimento }}"></td>
                                                        <td><input type="number" name="abastecimentos[old_{{ $key }}][km_abastecimento]" class="form-control" value="{{ $item->km_abastecimento }}"></td>
                                                        <td><input type="text" name="abastecimentos[old_{{ $key }}][quantidade]" class="form-control qty" value="{{ number_format($item->quantidade, 3, ',', '.') }}"></td>
                                                        <td><input type="text" name="abastecimentos[old_{{ $key }}][unitario]" class="form-control unit" value="{{ number_format($item->valor_unitario, 3, ',', '.') }}"></td>
                                                        <td><input type="text" name="abastecimentos[old_{{ $key }}][total]" class="form-control total-row" value="{{ number_format($item->valor_total, 2, ',', '.') }}" readonly></td>
                                                        <td><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove(); calcTotalGeral();"><i class="la la-trash"></i></button></td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                        <tfoot>
                                            <tr class="font-weight-bold">
                                                <td colspan="6" class="text-right">Total Acumulado na Viagem:</td>
                                                <td><input type="text" id="total_geral_abastecimento" class="form-control" readonly value="0,00" style="background: #e9ecef; font-weight: bold;"></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
<hr>
<h5 class="mt-4 mb-4">Outras Despesas da Viagem</h5>
<div class="row" style="background: #fff3e0; padding: 15px; border-radius: 8px; border: 1px solid #ffcc80;">
    <div class="col-12 text-right mb-2">
        <button type="button" class="btn btn-warning btn-sm" id="btn-add-despesa">
            <i class="la la-plus"></i> Adicionar Despesa
        </button>
    </div>
    <div class="col-12">
        <table class="table table-bordered table-striped" id="tabela_despesas">
            <thead>
                <tr class="bg-warning text-dark">
                    <th width="30%">Tipo de Despesa</th>
                    <th width="45%">Descrição / Motivo</th>
                    <th width="15%">Valor (R$)</th>
                    <th width="10%">Ações</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($data) && $data->despesas)
                    @foreach($data->despesas as $key => $desp)
                        <tr id="row_desp_old_{{ $key }}">
                            <td>
                                <select name="despesas[old_{{ $key }}][tipo]" class="form-control">
                                    <option value="Pedágio" {{ $desp->tipo == 'Pedágio' ? 'selected' : '' }}>Pedágio</option>
                                    <option value="Alimentação" {{ $desp->tipo == 'Alimentação' ? 'selected' : '' }}>Alimentação</option>
                                    <option value="Pernoite" {{ $desp->tipo == 'Pernoite' ? 'selected' : '' }}>Pernoite</option>
                                    <option value="Outros" {{ $desp->tipo == 'Outros' ? 'selected' : '' }}>Outros</option>
                                </select>
                            </td>
                            <td><input type="text" name="despesas[old_{{ $key }}][descricao]" class="form-control" value="{{ $desp->descricao }}"></td>
                            <td><input type="text" name="despesas[old_{{ $key }}][valor]" class="form-control val-despesa" value="{{ number_format($desp->valor, 2, ',', '.') }}"></td>
                            <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove();"><i class="la la-trash"></i></button></td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
                            <div class="form-group mt-5">
                                <label class="col-form-label">Observações da Viagem</label>
                                <textarea class="form-control" name="observacao" rows="3">{{ old('observacao', $data->observacao ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-lg-12 text-center">
                                    <a class="btn btn-danger mr-2" href="{{ $actionCancel }}">
                                        <i class="la la-close"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="la la-check"></i> Salvar Movimentação
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
    // Usamos um contador alto para as novas linhas não darem conflito com as já salvas (IDs do banco)
    var contRow = 1000;

    $(document).ready(function() {
        // Inicializa o Select2 padrão do Laravel
        $('.select2').select2({ width: '100%' });

        // Calcula o total geral logo ao carregar a página (importante para edições)
        calcTotalGeral();

        // TAREFA: KM Inicial Automático
        // Quando o usuário escolhe um caminhão, buscamos o KM que salvamos lá no "data-km" do select
        $('#veiculo_id').on('change', function() {
            var km = $(this).find(':selected').data('km');
            if (km !== undefined) $('#km_inicial').val(km);
        });

        // TAREFA: Adicionar Nova Linha de Abastecimento
        $('#btn-add-abastecimento').click(function() {
            var row = `
            <tr id="row_${contRow}">
                <td>
                    <select name="abastecimentos[${contRow}][produto_id]" class="form-control select2-prod" required>
                        <option value="">Selecione</option>
                        @foreach($combustiveis as $c)
                            <option value="{{ $c->id }}" data-preco="{{ $c->valor_compra }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="abastecimentos[${contRow}][tipo]" class="form-control">
                        <option value="externo">Externo</option>
                        <option value="interno">Interno</option>
                    </select>
                </td>
                <td><input type="date" name="abastecimentos[${contRow}][data]" class="form-control" value="{{ date('Y-m-d') }}" required></td>
                <td><input type="number" name="abastecimentos[${contRow}][km_abastecimento]" class="form-control" placeholder="KM"></td>
                <td><input type="number" step="0.001" name="abastecimentos[${contRow}][quantidade]" class="form-control qty" value="0.000"></td>
                <td><input type="number" step="0.001" name="abastecimentos[${contRow}][unitario]" class="form-control unit" value="0.000"></td>
                <td><input type="text" name="abastecimentos[${contRow}][total]" class="form-control total-row" readonly value="0,00"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(${contRow})">
                        <i class="la la-trash"></i>
                    </button>
                </td>
            </tr>`;

            $('#tabela_abastecimentos tbody').append(row);
            // Inicializamos o select2 apenas na linha recém criada
            $('.select2-prod').last().select2({ width: '100%' });
            contRow++;
        });
    });

    // Função para remover a linha e atualizar o total geral
    function removeRow(id) {
        $(`#row_${id}`).remove();
        calcTotalGeral();
    }

    // TAREFA: Preço Automático
    // Quando escolhe Diesel ou Arla, já preenche o preço de custo vindo do cadastro de produtos
    $(document).on('change', '.select2-prod', function() {
        let preco = $(this).find(':selected').data('preco');
        $(this).closest('tr').find('.unit').val(preco);
        updateRowTotal($(this).closest('tr'));
    });

    // TAREFA: Calcular Subtotal da Linha (Qtd x Valor)
    $(document).on('keyup change', '.qty, .unit', function() {
        updateRowTotal($(this).closest('tr'));
    });

    function updateRowTotal(row) {
        // Converte vírgulas em pontos para o JS entender o número
        let qty = parseFloat(row.find('.qty').val().replace(',', '.')) || 0;
        let unit = parseFloat(row.find('.unit').val().replace(',', '.')) || 0;
        let total = qty * unit;
        
        // Exibe o total formatado com vírgula para o usuário
        row.find('.total-row').val(total.toFixed(2).replace('.', ','));
        calcTotalGeral();
    }

    // TAREFA: Calcular Total Acumulado (Soma de todas as linhas)
    function calcTotalGeral() {
        let soma = 0;
        $('.total-row').each(function() {
            // Remove pontos de milhar e troca vírgula por ponto para somar
            let val = $(this).val().replace(/\./g, '').replace(',', '.');
            soma += parseFloat(val) || 0;
        });
        // Formata para o padrão brasileiro (R$ 1.234,56)
        $('#total_geral_abastecimento').val(soma.toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
    }
   
  var contDesp = 2000;
$('#btn-add-despesa').click(function() {
    var row = `
    <tr id="row_desp_${contDesp}">
        <td>
            <select name="despesas[${contDesp}][tipo]" class="form-control">
                <option value="Pedágio">Pedágio</option>
                <option value="Alimentação">Alimentação</option>
                <option value="Pernoite">Pernoite</option>
                <option value="Outros">Outros</option>
            </select>
        </td>
        <td><input type="text" name="despesas[${contDesp}][descricao]" class="form-control" placeholder="Ex: Pedágio BR-116"></td>
        <td><input type="number" step="0.01" name="despesas[${contDesp}][valor]" class="form-control val-despesa" value="0.00"></td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove();">
                <i class="la la-trash"></i>
            </button>
        </td>
    </tr>`;
    $('#tabela_despesas tbody').append(row);
    contDesp++;
});
</script>
@endsection