@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush

@section('content')
    <div class="fer-extra fer-extra--support fer-extra--manutencoes card card-custom gutter-b">
        <div class="card-body">
            <form method="post" action="/manutencoes/save">
                @csrf
                <input type="hidden" name="id" value="{{ $data->manutencao_id ?? '' }}">
                <input type="hidden" name="manutencao_id" value="{{ $data->manutencao_id ?? '' }}">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="card-title font-weight-bolder">{{ $title }}</h3>
                    <span class="badge badge-light-primary font-weight-bold">Controle de Oficina e Frotas</span>
                </div>

                <div class="row">
                    <div class="form-group col-lg-3">
                        <label>Veículo *</label>
                        <select name="veiculo_id" id="select_veiculo" class="form-control" required>
                            <option value="">Selecione...</option>
                            @foreach($veiculos as $v)
                                <option value="{{ $v->id }}" data-km="{{ $v->quilometragem }}" {{ ($data->veiculo_id ?? '') == $v->id ? 'selected' : '' }}>
                                    {{ $v->placa }} - {{ $v->modelo ?? $v->marca }}
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
                        <label>Mecânico Interno / Responsável</label>
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
                            <label class="radio radio-primary">
                                <input type="radio" name="tipo_execucao" value="Interno" id="tipo_interno" {{ ($data->tipo_execucao ?? 'Interno') == 'Interno' ? 'checked' : '' }}>
                                <span></span> Interno
                            </label>
                            <label class="radio radio-primary">
                                <input type="radio" name="tipo_execucao" value="Externo" id="tipo_externo" {{ ($data->tipo_execucao ?? '') == 'Externo' ? 'checked' : '' }}>
                                <span></span> Externo (Oficina/Terceiro)
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-lg-4 {{ ($data->tipo_execucao ?? '') == 'Externo' ? '' : 'd-none' }}" id="box_fornecedor">
                        <label>Oficina / Fornecedor Parceiro</label>
                        <select name="fornecedor_id" class="form-control select2" style="width: 100%">
                            <option value="">Selecione a oficina...</option>
                            @foreach($fornecedores as $forn)
                                <option value="{{ $forn->id }}" {{ ($data->fornecedor_id ?? '') == $forn->id ? 'selected' : '' }}>{{ $forn->razao_social }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-lg-6">
                        <label>Descrição do Problema / Relato do Motorista</label>
                        <textarea name="descricao" class="form-control" rows="3">{{ $data->descricao ?? '' }}</textarea>
                    </div>
                    <div class="form-group col-lg-6">
                        <label>Checklist de Entrada / Observações</label>
                        <textarea name="checklist" class="form-control" rows="3">{{ is_array($data->checklist ?? '') ? implode("\n", $data->checklist) : ($data->checklist ?? '') }}</textarea>
                    </div>
                </div>

                <hr>
                <h4 class="font-weight-bold mb-3">Peças e Serviços Utilizados</h4>

                {{-- CAIXA DE ADIÇÃO DE ITENS --}}
                <div class="bg-light-secondary p-4 rounded mb-4">
                    <div class="row align-items-end">
                        <div class="form-group col-lg-2">
                            <label class="font-weight-bold">Tipo de Item</label>
                            <select id="tipo_item_adicionar" class="form-control">
                                <option value="peca">Peça / Estoque</option>
                                <option value="servico">Serviço / Mão de Obra</option>
                            </select>
                        </div>

                        {{-- Busca de Peça com Select2 --}}
                        <div class="form-group col-lg-4" id="box_busca_peca">
                            <label class="font-weight-bold">Buscar Peça do Estoque</label>
                            <select id="select_produto" class="form-control" style="width: 100%"></select>
                        </div>

                        {{-- Texto livre para Serviço --}}
                        <div class="form-group col-lg-4 d-none" id="box_texto_servico">
                            <label class="font-weight-bold">Descrição do Serviço Prestado</label>
                            <input type="text" id="descricao_servico" class="form-control" placeholder="Ex: Alinhamento e Balanceamento, Troca de Óleo...">
                        </div>

                        <div class="form-group col-lg-2">
                            <label class="font-weight-bold">Qtd / Horas</label>
                            <input type="number" id="quantidade_item" class="form-control" value="1" min="1" step="any">
                        </div>
                        <div class="form-group col-lg-2">
                            <label class="font-weight-bold">Valor Unitário (R$)</label>
                            <input type="text" id="valor_item" class="form-control" value="0.00">
                        </div>
                        <div class="form-group col-lg-2">
                            <button type="button" id="btn-add-item" class="btn btn-success font-weight-bold w-100">
                                <i class="la la-plus"></i> Inserir
                            </button>
                        </div>
                    </div>
                </div>

                <table class="table table-bordered table-hover" id="tabela_itens">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 15%">Tipo</th>
                        <th>Descrição / Produto</th>
                        <th style="width: 10%">Qtd</th>
                        <th style="width: 15%">Custo Unit.</th>
                        <th style="width: 15%">Subtotal</th>
                        <th style="width: 5%" class="text-center">Ação</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(isset($data) && $data->itens)
                        @foreach($data->itens as $item)
                            <tr>
                                <td>
                                    <input type="hidden" name="tipos_itens[]" value="{{ $item->produto_id ? 'peca' : 'servico' }}">
                                    <span class="badge badge-{{ $item->produto_id ? 'primary' : 'warning' }}">
                                        {{ $item->produto_id ? 'Peça' : 'Serviço' }}
                                    </span>
                                </td>
                                <td>
                                    <input type="hidden" name="produtos_ids[]" value="{{ $item->produto_id }}">
                                    <input type="hidden" name="produtos_nomes[]" value="{{ $item->produto->nome ?? $item->descricao }}">
                                    {{ $item->produto->nome ?? $item->descricao }}
                                </td>
                                <td><input type="hidden" name="quantidades[]" value="{{ $item->quantidade }}">{{ $item->quantidade }}</td>
                                <td><input type="hidden" name="valores[]" value="{{ $item->valor_unitario }}">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                                <td><input type="hidden" name="subtotais[]" value="{{ $item->subtotal }}">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-icon btn-light-danger btn-sm remover-item" data-subtotal="{{ $item->subtotal }}">
                                        <i class="la la-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>

                <div class="row justify-content-end mt-4">
                    <div class="col-lg-3 text-right">
                        <label class="font-weight-bolder font-size-h6">Custo Total da OS (R$):</label>
                        <input type="text" name="custo" id="custo_total_input" class="form-control form-control-lg text-right text-danger font-weight-bolder" value="{{ $data->custo ?? '0.00' }}" readonly>
                    </div>
                </div>

                <div class="card-footer text-right px-0 pb-0">
                    <a href="/manutencoes" class="btn btn-light-danger font-weight-bold">Cancelar</a>
                    <button type="submit" class="btn btn-primary font-weight-bold ml-2">Salvar Manutenção</button>
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
            // Preenche o KM automaticamente quando seleciona o veículo
            $('#select_veiculo').change(function() {
                let km = $(this).find(':selected').data('km');
                if (km) {
                    $('#input_km').val(km);
                }
            });

            // Alternador de Fornecedor para Serviço Externo
            $('input[name="tipo_execucao"]').change(function() {
                if ($(this).val() === 'Externo') {
                    $('#box_fornecedor').removeClass('d-none');
                } else {
                    $('#box_fornecedor').addClass('d-none');
                }
            });

            // Alternador de Tipo de Item (Peça vs Serviço)
            $('#tipo_item_adicionar').change(function() {
                if ($(this).val() === 'servico') {
                    $('#box_busca_peca').addClass('d-none');
                    $('#box_texto_servico').removeClass('d-none');
                    $('#valor_item').val('0.00');
                } else {
                    $('#box_texto_servico').addClass('d-none');
                    $('#box_busca_peca').removeClass('d-none');
                }
            });

            // Inicialização do Select2 com AJAX resiliente para Peças
            $('#select_produto').select2({
                placeholder: 'Digite o nome da peça/produto...',
                minimumInputLength: 0,
                ajax: {
                    url: '/manutencoes/buscar-produtos',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results || []
                        };
                    },
                    cache: true
                }
            });

            // Carrega o valor de compra quando seleciona a peça
            $('#select_produto').on('select2:select', function (e) {
                let item = e.params.data;
                if (item && item.valor) {
                    $('#valor_item').val(item.valor);
                }
            });
        });

        // Inserção de itens na grade
        $('#btn-add-item').click(function() {
            let tipo = $('#tipo_item_adicionar').val();
            let idProduto = '';
            let nomeItem = '';

            if (tipo === 'peca') {
                let selData = $('#select_produto').select2('data')[0];
                if (!selData || !selData.id) {
                    alert('Por favor, busque e selecione uma peça do estoque.');
                    return;
                }
                idProduto = selData.id;
                nomeItem = selData.text;
            } else {
                nomeItem = $('#descricao_servico').val().trim();
                if (!nomeItem) {
                    alert('Por favor, informe a descrição do serviço prestado.');
                    return;
                }
            }

            let qtd = parseFloat($('#quantidade_item').val()) || 1;
            let valor = parseFloat($('#valor_item').val().replace(',', '.')) || 0;
            let subtotal = qtd * valor;

            let badgeTipo = tipo === 'peca'
                ? '<span class="badge badge-primary">Peça</span>'
                : '<span class="badge badge-warning">Serviço</span>';

            let linha = `<tr>
            <td>
                <input type="hidden" name="tipos_itens[]" value="${tipo}">
                ${badgeTipo}
            </td>
            <td>
                <input type="hidden" name="produtos_ids[]" value="${idProduto}">
                <input type="hidden" name="produtos_nomes[]" value="${nomeItem}">
                ${nomeItem}
            </td>
            <td><input type="hidden" name="quantidades[]" value="${qtd}">${qtd}</td>
            <td><input type="hidden" name="valores[]" value="${valor}">R$ ${valor.toFixed(2)}</td>
            <td><input type="hidden" name="subtotais[]" value="${subtotal}">R$ ${subtotal.toFixed(2)}</td>
            <td class="text-center">
                <button type="button" class="btn btn-icon btn-light-danger btn-sm remover-item" data-subtotal="${subtotal}">
                    <i class="la la-trash"></i>
                </button>
            </td>
        </tr>`;

            $('#tabela_itens tbody').append(linha);
            totalGeral += subtotal;
            $('#custo_total_input').val(totalGeral.toFixed(2));

            // Limpa campos para próxima inserção
            $('#select_produto').val(null).trigger('change');
            $('#descricao_servico').val('');
            $('#quantidade_item').val(1);
            $('#valor_item').val('0.00');
        });

        // Remoção de item da grade
        $(document).on('click', '.remover-item', function() {
            let sub = parseFloat($(this).data('subtotal')) || 0;
            totalGeral -= sub;
            if (totalGeral < 0) totalGeral = 0;
            $('#custo_total_input').val(totalGeral.toFixed(2));
            $(this).closest('tr').remove();
        });
    </script>
@endsection
