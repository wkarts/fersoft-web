@extends('default.layout')
@section('content')

    <style>
        .select2-container .select2-selection--single,
        .select2-container .select2-selection--multiple {
            min-height: 38px !important;
            border: 1px solid #ced4da !important;
            border-radius: 0.42rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px !important;
            color: #495057 !important;
            padding-left: 12px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
        .card-section-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #3F4254;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #EBEDF3;
        }
    </style>

    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="card-label">
                        <i class="la la-truck icon-xl text-primary mr-2"></i>
                        {{ $title }}
                    </h3>
                </div>
            </div>

            <div class="card-body">
                <form method="post" action="{{ isset($data->id) ? $actionUpdate : $actionSave }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $data->id ?? '' }}">

                    <!-- 1. DADOS BÁSICOS -->
                    <div class="card-section-title">
                        <i class="la la-info-circle text-primary"></i> 1. Dados Básicos do Transporte
                    </div>
                    <div class="row">
                        <div class="form-group col-lg-4 col-md-6">
                            <label class="col-form-label font-weight-bold">Veículo / Placa</label>
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

                        <div class="form-group col-lg-4 col-md-6">
                            <label class="col-form-label font-weight-bold">Tipo de Movimentação</label>
                            <select class="form-control select2" name="tipo_movimentacao_id" required>
                                <option value="">Selecione o Tipo</option>
                                @foreach($tipos as $t)
                                    <option value="{{ $t->id }}" {{ old('tipo_movimentacao_id', $data->tipo_movimentacao_id ?? '') == $t->id ? 'selected' : '' }}>
                                        {{ $t->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-lg-4 col-md-6">
                            <label class="col-form-label font-weight-bold">Motorista Responsável</label>
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

                    <!-- NOVO: LOCAL DE PARTIDA DA VIAGEM -->
                    <div class="row">
                        <div class="form-group col-lg-4 col-md-6">
                            <label class="col-form-label font-weight-bold">Local de Partida (Geolocalização)</label>
                            <select class="form-control" name="tipo_partida">
                                <option value="empresa" {{ old('tipo_partida', $data->tipo_partida ?? 'empresa') == 'empresa' ? 'selected' : '' }}>
                                    🏢 Empresa / Filial (Base)
                                </option>
                                <option value="residencia" {{ old('tipo_partida', $data->tipo_partida ?? '') == 'residencia' ? 'selected' : '' }}>
                                    🏠 Residência do Motorista (Casa)
                                </option>
                            </select>
                            <small class="form-text text-muted">Define onde o robô calcula o raio de 100m para o ponto automático.</small>
                        </div>
                    </div>

                    <!-- 2. EQUIPE E AJUDANTES -->
                    <div class="card-section-title mt-4">
                        <i class="la la-users text-primary"></i> 2. Equipe de Apoio (Ajudantes)
                    </div>
                    <div class="row">
                        <div class="form-group col-12">
                            <label class="col-form-label font-weight-bold">Ajudantes Escalados (Múltiplos)</label>
                            <select class="form-control select2" name="ajudantes_ids[]" multiple="multiple">
                                @foreach($funcionarios as $f)
                                    <option value="{{ $f->id }}"
                                        {{ (isset($data) && $data->ajudantes->contains($f->id)) ? 'selected' : '' }}>
                                        {{ $f->nome }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                <i class="la la-info-circle"></i> Selecione 1, 2, 3 ou mais ajudantes para esta viagem.
                            </small>
                        </div>
                    </div>

                    <!-- BLOCOS DE CONFIGURAÇÃO DE WHATSAPP E CHECKLIST LADO A LADO -->
                    <div class="row mt-4">
                        <!-- Notificação de Ponto -->
                        <div class="col-lg-6 col-md-12">
                            <div class="p-3 rounded h-100" style="background: #f0f8ff; border: 1px solid #b8daff;">
                                <label class="col-form-label font-weight-bold text-dark">
                                    <i class="la la-mobile text-primary"></i> Notificação de Ponto via WhatsApp
                                </label>
                                <select class="form-control" name="bater_ponto_whatsapp">
                                    <option value="1" {{ old('bater_ponto_whatsapp', $data->bater_ponto_whatsapp ?? 1) == 1 ? 'selected' : '' }}>
                                        🟢 SIM (Avisar se sair sem bater o ponto)
                                    </option>
                                    <option value="0" {{ old('bater_ponto_whatsapp', $data->bater_ponto_whatsapp ?? 1) == 0 ? 'selected' : '' }}>
                                        🔴 NÃO (Ponto estritamente presencial)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Checklist Obrigatório -->
                        <div class="col-lg-6 col-md-12">
                            <div class="p-3 rounded h-100" style="background: #fffdf0; border: 1px solid #ffeeba;">
                                <label class="col-form-label font-weight-bold text-dark">
                                    <i class="la la-clipboard-check text-warning"></i> Exigir Checklist de Pré-Viagem
                                </label>
                                <select class="form-control" name="checklist_obrigatorio">
                                    <option value="1" {{ old('checklist_obrigatorio', $data->checklist_obrigatorio ?? 1) == 1 ? 'selected' : '' }}>
                                        🟢 SIM (Obrigatório - Exige envio das fotos)
                                    </option>
                                    <option value="0" {{ old('checklist_obrigatorio', $data->checklist_obrigatorio ?? 1) == 0 ? 'selected' : '' }}>
                                        🔴 NÃO (Opcional / Sem cobrança estrita)
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 3. ROTA E DESTINO -->
                    <div class="card-section-title mt-4">
                        <i class="la la-map-marker text-primary"></i> 3. Origem e Destino
                    </div>
                    <div class="row">
                        <div class="form-group col-lg-4 col-md-6">
                            <label class="col-form-label font-weight-bold">Cliente (Opcional)</label>
                            <select name="cliente_id" id="cliente_id" class="form-control select2">
                                <option value="">Nenhum Cliente</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}" data-endereco="{{ $c->rua }}, {{ $c->numero }} - {{ $c->bairro }}" {{ (old('cliente_id', $data->cliente_id ?? '')) == $c->id ? 'selected' : '' }}>
                                        {{ $c->razao_social }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-lg-4 col-md-6">
                            <label class="col-form-label font-weight-bold">Fornecedor (Opcional)</label>
                            <select name="fornecedor_id" id="fornecedor_id" class="form-control select2">
                                <option value="">Nenhum Fornecedor</option>
                                @foreach($fornecedores as $f)
                                    <option value="{{ $f->id }}" data-endereco="{{ $f->rua }}, {{ $f->numero }} - {{ $f->bairro }}" {{ (old('fornecedor_id', $data->fornecedor_id ?? '')) == $f->id ? 'selected' : '' }}>
                                        {{ $f->razao_social }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-lg-4 col-md-12">
                            <label class="col-form-label font-weight-bold">Destino / Rota Completa</label>
                            <input type="text" id="destino_input" class="form-control" name="destino" value="{{ old('destino', $data->destino ?? '') }}" placeholder="Ex: Av. Paulista, 1000, Bela Vista, SP">
                        </div>
                    </div>

                    <!-- 4. CONTROLE DE PERCURSO -->
                    <div class="card-section-title mt-4">
                        <i class="la la-tachometer text-primary"></i> 4. Horários e KM
                    </div>
                    <div class="row">
                        <div class="form-group col-lg-3 col-md-6">
                            <label class="col-form-label font-weight-bold">Data/Hora Saída Garagem</label>
                            <input type="datetime-local" class="form-control" name="data_hora_saida"
                                   value="{{ old('data_hora_saida', isset($data->data_hora_saida) ? \Carbon\Carbon::parse($data->data_hora_saida)->format('Y-m-d\TH:i') : date('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="form-group col-lg-3 col-md-6">
                            <label class="col-form-label font-weight-bold">KM Inicial</label>
                            <input type="number" id="km_inicial" class="form-control" name="km_inicial" value="{{ old('km_inicial', $data->km_inicial ?? '') }}">
                        </div>

                        <div class="form-group col-lg-3 col-md-6">
                            <label class="col-form-label font-weight-bold">Data/Hora Retorno Garagem</label>
                            <input type="datetime-local" class="form-control" name="data_hora_chegada"
                                   value="{{ old('data_hora_chegada', isset($data->data_hora_chegada) ? \Carbon\Carbon::parse($data->data_hora_chegada)->format('Y-m-d\TH:i') : '') }}">
                        </div>

                        <div class="form-group col-lg-3 col-md-6">
                            <label class="col-form-label font-weight-bold">KM Final</label>
                            <input type="number" class="form-control" name="km_final" value="{{ old('km_final', $data->km_final ?? '') }}">
                        </div>
                    </div>

                    <!-- 5. HORÁRIOS NO CLIENTE -->
                    <div class="card-section-title mt-4">
                        <i class="la la-clock text-primary"></i> 5. Etapas do Cliente e Status
                    </div>
                    <div class="row p-3 rounded" style="background: #f4f6f9; border: 1px solid #e4e6ef;">
                        <div class="form-group col-lg-4 col-md-6">
                            <label class="col-form-label font-weight-bold">Status Atual</label>
                            <select class="form-control" name="status">
                                <option value="agendado" {{ old('status', $data->status ?? '') == 'agendado' ? 'selected' : '' }}>Agendado / Planejado</option>
                                <option value="iniciado" {{ old('status', $data->status ?? '') == 'iniciado' ? 'selected' : '' }}>Em Percurso</option>
                                <option value="finalizado" {{ old('status', $data->status ?? '') == 'finalizado' ? 'selected' : '' }}>Finalizado</option>
                            </select>
                        </div>

                        <div class="form-group col-lg-4 col-md-6">
                            <label class="col-form-label font-weight-bold">Chegada ao Cliente</label>
                            <input type="datetime-local" class="form-control" name="data_hora_chegada_cliente"
                                   value="{{ old('data_hora_chegada_cliente', isset($data->data_hora_chegada_cliente) ? \Carbon\Carbon::parse($data->data_hora_chegada_cliente)->format('Y-m-d\TH:i') : '') }}">
                        </div>

                        <div class="form-group col-lg-4 col-md-6">
                            <label class="col-form-label font-weight-bold">Saída do Cliente</label>
                            <input type="datetime-local" class="form-control" name="data_hora_saida_cliente"
                                   value="{{ old('data_hora_saida_cliente', isset($data->data_hora_saida_cliente) ? \Carbon\Carbon::parse($data->data_hora_saida_cliente)->format('Y-m-d\TH:i') : '') }}">
                        </div>
                    </div>

                    <!-- 6. ABASTECIMENTOS DA VIAGEM -->
                    <div class="card-section-title mt-4">
                        <i class="la la-gas-pump text-primary"></i> 6. Abastecimentos da Viagem
                    </div>
                    <div class="row p-3 rounded" style="background: #f8f9fa; border: 1px solid #ddd;">
                        <div class="col-12 text-right mb-2">
                            <button type="button" class="btn btn-primary btn-sm font-weight-bold" id="btn-add-abastecimento">
                                <i class="la la-plus"></i> Adicionar Abastecimento
                            </button>
                        </div>
                        <div class="col-12 table-responsive">
                            <table class="table table-bordered table-striped" id="tabela_abastecimentos">
                                <thead>
                                <tr class="bg-secondary text-dark">
                                    <th width="20%">Produto</th>
                                    <th width="12%">Local</th>
                                    <th width="14%">Data</th>
                                    <th width="12%">KM Atual</th>
                                    <th width="12%">Litros</th>
                                    <th width="12%">Vl. Unit.</th>
                                    <th width="12%">Subtotal</th>
                                    <th width="6%">Ações</th>
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
                                            <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove(); calcTotalGeral();"><i class="la la-trash"></i></button></td>
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 7. OUTRAS DESPESAS DA VIAGEM -->
                    <div class="card-section-title mt-4">
                        <i class="la la-wallet text-primary"></i> 7. Outras Despesas da Viagem (Pedágio, Alimentação, Pernoite)
                    </div>
                    <div class="row p-3 rounded" style="background: #fff3e0; border: 1px solid #ffcc80;">
                        <div class="col-12 text-right mb-2">
                            <button type="button" class="btn btn-warning btn-sm font-weight-bold" id="btn-add-despesa">
                                <i class="la la-plus"></i> Adicionar Despesa
                            </button>
                        </div>
                        <div class="col-12 table-responsive">
                            <table class="table table-bordered table-striped" id="tabela_despesas">
                                <thead>
                                <tr class="bg-warning text-dark">
                                    <th width="25%">Tipo de Despesa</th>
                                    <th width="50%">Descrição / Motivo</th>
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

                    <div class="form-group mt-4">
                        <label class="col-form-label font-weight-bold">Observações Gerais</label>
                        <textarea class="form-control" name="observacao" rows="3">{{ old('observacao', $data->observacao ?? '') }}</textarea>
                    </div>

                    <div class="card-footer mt-4 text-center">
                        <a class="btn btn-danger mr-2 font-weight-bold" href="{{ $actionCancel }}">
                            <i class="la la-close"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success font-weight-bold">
                            <i class="la la-check"></i> Salvar Movimentação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script>
        var contRow = 1000;
        var contDesp = 2000;

        $(document).ready(function() {
            $('.select2').select2({ width: '100%' });
            calcTotalGeral();

            $('#veiculo_id').on('change', function() {
                var km = $(this).find(':selected').data('km');
                if (km !== undefined) $('#km_inicial').val(km);
            });

            $('#cliente_id').on('change', function() {
                let endereco = $(this).find(':selected').data('endereco');
                if (endereco && endereco.trim() !== '' && endereco !== ' ,  - ') {
                    $('#destino_input').val(endereco);
                    $('#fornecedor_id').val('').trigger('change.select2');
                }
            });

            $('#fornecedor_id').on('change', function() {
                let endereco = $(this).find(':selected').data('endereco');
                if (endereco && endereco.trim() !== '' && endereco !== ' ,  - ') {
                    $('#destino_input').val(endereco);
                    $('#cliente_id').val('').trigger('change.select2');
                }
            });

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
                $('.select2-prod').last().select2({ width: '100%' });
                contRow++;
            });

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
        });

        function removeRow(id) {
            $(`#row_${id}`).remove();
            calcTotalGeral();
        }

        $(document).on('change', '.select2-prod', function() {
            let preco = $(this).find(':selected').data('preco');
            $(this).closest('tr').find('.unit').val(preco);
            updateRowTotal($(this).closest('tr'));
        });

        $(document).on('keyup change', '.qty, .unit', function() {
            updateRowTotal($(this).closest('tr'));
        });

        function updateRowTotal(row) {
            let qty = parseFloat(row.find('.qty').val().replace(',', '.')) || 0;
            let unit = parseFloat(row.find('.unit').val().replace(',', '.')) || 0;
            let total = qty * unit;

            row.find('.total-row').val(total.toFixed(2).replace('.', ','));
            calcTotalGeral();
        }

        function calcTotalGeral() {
            let soma = 0;
            $('.total-row').each(function() {
                let val = $(this).val().replace(/\./g, '').replace(',', '.');
                soma += parseFloat(val) || 0;
            });
            $('#total_geral_abastecimento').val(soma.toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
        }
    </script>
@endsection
