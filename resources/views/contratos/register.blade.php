@extends('default.layout')
@section('content')

<!-- Estilos para Modernização Visual -->
<!-- Estilos para Modernização e Correção de Altura dos Campos -->
<style>
    .card {
        border-radius: 10px !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        border: none !important;
    }
    .section-title {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.1rem;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 8px;
        margin-top: 25px;
        margin-bottom: 18px;
    }

    /* Correção da altura e alinhamento interno dos inputs e selects padrão */
    .form-control, .custom-select {
        height: 42px !important;
        padding: 6px 12px !important;
        font-size: 14px !important;
        line-height: 1.5 !important;
        border-radius: 6px !important;
    }

    /* Correção específica do Select2 para centralizar o texto e não cortar as letras */
    .select2-container--default .select2-selection--single {
        height: 42px !important;
        border: 1px solid #ced4da !important;
        border-radius: 6px !important;
        padding: 4px 10px !important;
        display: flex !important;
        align-items: center !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: normal !important;
        padding-left: 2px !important;
        color: #495057 !important;
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        top: 1px !important;
    }

    .select2-dropdown {
        border-radius: 6px !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        border: 1px solid #007bff !important;
    }
</style>
<div class="card shadow-sm">
    <div class="card-body p-4">
        <h4 class="mb-4 text-primary font-weight-bold">
            <i class="fa fa-file-contract"></i> {{ $title }}
        </h4>
        
        <form method="POST" action="{{ optional($data)->id ? $actionUpdate . '/' . $data->id : $actionSave }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" value="{{ optional($data)->id }}">
            
            <!-- 1. DADOS GERAIS -->
            <div class="section-title">
                <i class="fa fa-info-circle text-primary"></i> 1. Dados Gerais do Contrato
            </div>

            <div class="row">
                <div class="form-group col-md-3">
                    <label class="font-weight-bold text-muted small">NÚMERO DO CONTRATO / PO</label>
                    <input type="text" name="numero_contrato" class="form-control" value="{{ old('numero_contrato', optional($data)->numero_contrato) }}" placeholder="Ex: CT-0101">
                </div>

                <div class="form-group col-md-3">
                    <label class="font-weight-bold text-muted small">FILIAL / MATRIZ</label>
                    <select name="filial_id" class="form-control custom-select">
                        <option value="">Matriz (Geral)</option>
                        @foreach($filiaisLista as $f)
                            <option value="{{ $f->id }}" {{ (optional($data)->filial_id == $f->id) ? 'selected' : '' }}>{{ $f->descricao }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Cliente contratante com busca por Nome e CPF/CNPJ -->
                <div class="form-group col-md-6">
                    <label class="font-weight-bold text-muted small"><i class="fa fa-user text-primary"></i> CLIENTE (CONTRATANTE)</label>
                    <select name="cliente_id" id="cliente_id" class="form-control select2-busca" style="width: 100%;" required>
                        <option value="">Selecione ou digite o Nome / CPF / CNPJ...</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}" {{ (optional($data)->cliente_id == $c->id) ? 'selected' : '' }}>
                                {{ $c->razao_social }} {{ $c->cpf_cnpj ? ' | Doc: ' . $c->cpf_cnpj : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <!-- Vendedor / Responsável -->
                <div class="form-group col-md-4">
                    <label class="font-weight-bold text-muted small"><i class="fa fa-user-tie text-primary"></i> VENDEDOR / RESPONSÁVEL</label>
                    <select name="funcionario_id" id="funcionario_id" class="form-control select2-busca" style="width: 100%;">
                        <option value="">Selecione o Vendedor...</option>
                        @foreach($vendedores as $v)
                            <option value="{{ $v->id }}" {{ (optional($data)->funcionario_id == $v->id) ? 'selected' : '' }}>
                                {{ $v->nome }} {{ isset($v->funcao_nome) ? '- [' . $v->funcao_nome . ']' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label class="font-weight-bold text-muted small">NOME DO CONTATO (NA OBRA)</label>
                    <input type="text" name="contato_nome" class="form-control" value="{{ old('contato_nome', optional($data)->contato_nome) }}" placeholder="Nome do encarregado/responsável">
                </div>

                <div class="form-group col-md-4">
                    <label class="font-weight-bold text-muted small">TELEFONE DO CONTATO</label>
                    <input type="text" name="contato_telefone" class="form-control celular" value="{{ old('contato_telefone', optional($data)->contato_telefone) }}" placeholder="(00) 00000-0000">
                </div>
            </div>

            <!-- 2. ITENS DO CONTRATO -->
            <div class="section-title">
                <i class="fa fa-list-alt text-primary"></i> 2. Itens do Contrato (Orçamento Previsto)
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-bordered table-hover align-middle" id="tabela-itens">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th style="width: 15%;">Tipo</th>
                            <th style="width: 40%;">Item (Serviço / Equipamento)</th>
                            <th style="width: 10%; text-align: center;">Unid.</th>
                            <th style="width: 12%;">Qtd. Prevista</th>
                            <th style="width: 15%;">Valor Unit. (R$)</th>
                            <th style="width: 8%; text-align: center;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(optional($data)->itens && $data->itens->count() > 0)
                            @foreach($data->itens as $idx => $it)
                            <tr>
                                <td>
                                    <select name="itens[{{$idx}}][tipo_item]" class="form-control tipo-item" onchange="mudarTipoItem(this)" required>
                                        <option value="Servico" {{ $it->tipo_item == 'Servico' ? 'selected' : '' }}>Serviço</option>
                                        <option value="Locacao" {{ $it->tipo_item == 'Locacao' ? 'selected' : '' }}>Locação</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="itens[{{$idx}}][servico_id]" class="form-control servico-select" style="{{ $it->tipo_item == 'Servico' ? '' : 'display:none;' }}">
                                        <option value="">Selecione o Serviço</option>
                                        @foreach($servicos as $s)
                                            <option value="{{ $s->id }}" data-unidade="{{ $s->unidade_cobranca }}" {{ $it->servico_id == $s->id ? 'selected' : '' }}>{{ $s->nome }}</option>
                                        @endforeach
                                    </select>
                                    <select name="itens[{{$idx}}][produto_id]" class="form-control produto-select" style="{{ $it->tipo_item == 'Locacao' ? '' : 'display:none;' }}">
                                        <option value="">Selecione o Produto</option>
                                        @foreach($produtos as $p)
                                            <option value="{{ $p->id }}" data-unidade="{{ $p->unidade_venda }}" {{ $it->produto_id == $p->id ? 'selected' : '' }}>{{ $p->nome }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-center align-middle font-weight-bold text-primary unidade-label">--</td>
                                <td><input type="text" name="itens[{{$idx}}][quantidade_prevista]" class="form-control money" value="{{ number_format($it->quantidade_prevista, 2, ',', '.') }}" required></td>
                                <td><input type="text" name="itens[{{$idx}}][valor_unitario]" class="form-control money" value="{{ number_format($it->valor_unitario, 2, ',', '.') }}" required></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removerLinha(this)"><i class="fa fa-trash"></i></button></td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" onclick="adicionarItem()">
                    <i class="fa fa-plus-circle"></i> Adicionar Item
                </button>
            </div>

            <!-- 3. LOCAL DA OBRA -->
            <div class="section-title">
                <i class="fa fa-map-marker-alt text-primary"></i> 3. Local da Obra
            </div>

            <div class="row">
                <div class="form-group col-md-2">
                    <label class="font-weight-bold text-muted small">CEP</label>
                    <input type="text" name="cep_obra" class="form-control cep" id="cep" value="{{ old('cep_obra', optional($data)->cep_obra) }}" onblur="buscaCep()" placeholder="00000-000">
                </div>
                <div class="form-group col-md-4">
                    <label class="font-weight-bold text-muted small">ENDEREÇO</label>
                    <input type="text" name="endereco_obra" class="form-control" id="rua" value="{{ old('endereco_obra', optional($data)->endereco_obra) }}">
                </div>
                <div class="form-group col-md-2">
                    <label class="font-weight-bold text-muted small">NÚMERO</label>
                    <input type="text" name="numero_obra" class="form-control" value="{{ old('numero_obra', optional($data)->numero_obra) }}">
                </div>
                <div class="form-group col-md-4">
                    <label class="font-weight-bold text-muted small">BAIRRO</label>
                    <input type="text" name="bairro_obra" class="form-control" id="bairro" value="{{ old('bairro_obra', optional($data)->bairro_obra) }}">
                </div>
                <div class="form-group col-md-12">
                    <label class="font-weight-bold text-muted small">CIDADE</label>
                    <select name="cidade_obra_id" id="cidade_obra_id" class="form-control select2-busca" style="width: 100%;">
                        <option value="">Selecione a Cidade</option>
                        @foreach($cidades as $cid)
                            <option value="{{ $cid->id }}" {{ (optional($data)->cidade_obra_id == $cid->id) ? 'selected' : '' }}>{{ $cid->nome }} - {{ $cid->uf }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 4. VALORES, PRAZOS E ANEXO -->
            <div class="section-title">
                <i class="fa fa-calculator text-primary"></i> 4. Valores, Prazos e Anexo
            </div>

            <div class="row">
                <div class="form-group col-md-3">
                    <label class="font-weight-bold text-muted small">VALOR TOTAL DO CONTRATO (R$)</label>
                    <input type="text" name="valor_contrato" class="form-control money" value="{{ optional($data)->valor_contrato ? number_format($data->valor_contrato, 2, ',', '.') : old('valor_contrato') }}" required placeholder="0,00">
                </div>
                <div class="form-group col-md-2">
                    <label class="font-weight-bold text-muted small">RETENÇÃO TÉCNICA (%)</label>
                    <input type="number" step="0.01" name="percentual_retencao" class="form-control" value="{{ old('percentual_retencao', optional($data)->percentual_retencao ?? '0') }}">
                </div>
                <div class="form-group col-md-3">
                    <label class="font-weight-bold text-muted small">DATA DE INÍCIO</label>
                    <input type="date" name="data_inicio" class="form-control" value="{{ old('data_inicio', optional($data)->data_inicio) }}" required>
                </div>
                <div class="form-group col-md-2">
                    <label class="font-weight-bold text-muted small">DATA DE TÉRMINO</label>
                    <input type="date" name="data_fim" class="form-control" value="{{ old('data_fim', optional($data)->data_fim) }}">
                </div>
                <div class="form-group col-md-2">
                    <label class="font-weight-bold text-muted small">STATUS</label>
                    <select name="status" class="form-control custom-select">
                        <option value="Ativo" {{ (optional($data)->status == 'Ativo') ? 'selected' : '' }}>Ativo</option>
                        <option value="Finalizado" {{ (optional($data)->status == 'Finalizado') ? 'selected' : '' }}>Finalizado</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="form-group col-md-6">
                    <label class="font-weight-bold text-muted small">ANEXAR CONTRATO ASSINADO (PDF)</label>
                    <input type="file" name="arquivo_contrato" class="form-control-file" accept=".pdf, image/*">
                </div>
                <div class="form-group col-md-6">
                    <label class="font-weight-bold text-muted small">OBSERVAÇÕES DO CONTRATO</label>
                    <textarea name="observacoes" class="form-control" rows="2" placeholder="Observações e detalhes adicionais...">{{ old('observacoes', optional($data)->observacoes) }}</textarea>
                </div>
            </div>

            <!-- BOTOES -->
            <div class="row mt-4 pt-3 border-top">
                <div class="col-md-12 text-right">
                    <a href="{{ $actionCancel }}" class="btn btn-secondary px-4 font-weight-bold mr-2">Voltar</a>
                    <button type="submit" class="btn btn-success px-4 font-weight-bold"><i class="fa fa-save"></i> Salvar Contrato</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let itemIdx = 999;

    $(document).ready(function() {
        // Inicializa Select2 com busca inteligente
        $('.select2-busca').select2({
            placeholder: "Digite para buscar...",
            allowClear: true,
            language: {
                noResults: function() {
                    return "Nenhum resultado encontrado";
                }
            }
        });
    });

    function adicionarItem() {
        let html = `<tr>
            <td>
                <select name="itens[${itemIdx}][tipo_item]" class="form-control tipo-item" onchange="mudarTipoItem(this)" required>
                    <option value="Servico">Serviço</option>
                    <option value="Locacao">Locação</option>
                </select>
            </td>
            <td>
                <select name="itens[${itemIdx}][servico_id]" class="form-control servico-select">
                    <option value="">Selecione o Serviço</option>
                    @foreach($servicos as $s)<option value="{{ $s->id }}" data-unidade="{{ $s->unidade_cobranca }}">{{ $s->nome }}</option>@endforeach
                </select>
                <select name="itens[${itemIdx}][produto_id]" class="form-control produto-select" style="display:none;">
                    <option value="">Selecione o Produto</option>
                    @foreach($produtos as $p)<option value="{{ $p->id }}" data-unidade="{{ $p->unidade_venda }}">{{ $p->nome }}</option>@endforeach
                </select>
            </td>
            <td class="text-center align-middle font-weight-bold text-primary unidade-label">--</td>
            <td><input type="number" step="0.01" name="itens[${itemIdx}][quantidade_prevista]" class="form-control" value="1.00" required></td>
            <td><input type="number" step="0.01" name="itens[${itemIdx}][valor_unitario]" class="form-control" placeholder="0.00" required></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="removerLinha(this)"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        $('#tabela-itens tbody').append(html);
        itemIdx++;
    }

    $(document).on('change', '.servico-select, .produto-select', function() {
        let unidade = $(this).find(':selected').data('unidade');
        let label = $(this).closest('tr').find('.unidade-label');
        if(unidade) {
            label.text(unidade);
        } else {
            label.text('--');
        }
    });

    function removerLinha(btn) {
        $(btn).closest('tr').remove();
    }

    function mudarTipoItem(select) {
        let tr = $(select).closest('tr');
        if($(select).val() === 'Servico') {
            tr.find('.servico-select').show().attr('required', true);
            tr.find('.produto-select').hide().removeAttr('required').val('');
        } else {
            tr.find('.produto-select').show().attr('required', true);
            tr.find('.servico-select').hide().removeAttr('required').val('');
        }
    }

    function buscaCep() {
        let cep = $('#cep').val().replace(/\D/g, '');
        if (cep != "") {
            let validacep = /^[0-9]{8}$/;
            if(validacep.test(cep)) {
                $("#rua").val("Buscando...");
                $("#bairro").val("Buscando...");
                
                $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {
                    if (!("erro" in dados)) {
                        $("#rua").val(dados.logradouro);
                        $("#bairro").val(dados.bairro);
                        
                        let cidadeNome = dados.localidade.toUpperCase();
                        $("#cidade_obra_id option").each(function() {
                            if ($(this).text().toUpperCase().includes(cidadeNome)) {
                                $("#cidade_obra_id").val($(this).val()).trigger('change');
                            }
                        });
                    } else {
                        $("#rua").val("");
                        $("#bairro").val("");
                        alert("CEP não encontrado.");
                    }
                });
            }
        }
    }
</script>
@endsection