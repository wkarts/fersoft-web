@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush

@section('content')

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

        .form-control, .custom-select {
            height: 42px !important;
            padding: 6px 12px !important;
            font-size: 14px !important;
            line-height: 1.5 !important;
            border-radius: 6px !important;
        }

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

    <div class="fer-extra fer-extra--contratos fer-extra--canvas card shadow-sm">
        <div class="card-body p-4">
            <h4 class="mb-4 text-primary font-weight-bold">
                <i class="fa fa-file-contract"></i> {{ $title ?? 'Contrato' }}
            </h4>

            <form method="POST" action="{{ optional($data)->id ? $actionUpdate . '/' . $data->id :$actionSave }}" enctype="multipart/form-data">
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
                            @foreach($filiaisLista ?? [] as $f)
                                <option value="{{ $f->id }}" {{ (optional($data)->filial_id ==$f->id) ? 'selected' : '' }}>
                                    {{ $f->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Cliente contratante com busca por Nome e CPF/CNPJ -->
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold text-muted small"><i class="fa fa-user text-primary"></i> CLIENTE (CONTRATANTE)</label>
                        <select name="cliente_id" id="cliente_id" class="form-control select2-busca" style="width: 100%;" required>
                            <option value="">Selecione ou digite o Nome / CPF / CNPJ...</option>
                            @foreach($clientes ?? [] as $c)
                                <option value="{{ $c->id }}" {{ (optional($data)->cliente_id ==$c->id) ? 'selected' : '' }}>
                                    {{ $c->razao_social }} {{ $c->cpf_cnpj ? ' \vert{} Doc: ' . $c->cpf_cnpj : '' }}
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
                            @foreach($vendedores ?? [] as $v)
                                <option value="{{ $v->id }}" {{ (optional($data)->funcionario_id ==$v->id) ? 'selected' : '' }}>
                                    {{ $v->nome }} {{ isset($v->funcao_nome) ? '- [' .$v->funcao_nome . ']' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold text-muted small">NOME DO CONTATO (NA OBRA / EMPRESA)</label>
                        <input type="text" name="contato_nome" class="form-control" value="{{ old('contato_nome', optional($data)->contato_nome) }}" placeholder="Nome do encarregado/responsável">
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold text-muted small">TELEFONE DO CONTATO</label>
                        <input type="text" name="contato_telefone" class="form-control celular" value="{{ old('contato_telefone', optional($data)->contato_telefone) }}" placeholder="(00) 00000-0000">
                    </div>
                </div>

                <!-- 2. ITENS DO CONTRATO -->
                <div class="section-title">
                    <i class="fa fa-list-alt text-primary"></i> 2. Itens do Contrato (Tabela Tarifária do Cliente)
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-hover align-middle" id="tabela-itens">
                        <thead class="bg-dark text-white">
                        <tr>
                            <th style="width: 18%;">Tipo</th>
                            <th style="width: 40%;">Item (Resíduo / Serviço / Equipamento)</th>
                            <th style="width: 14%; text-align: center;">Unid. Cobrança</th>
                            <th style="width: 10%;">Qtd. Prevista</th>
                            <th style="width: 12%;">Valor Unit. (R$)</th>
                            <th style="width: 6%; text-align: center;">Ação</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(optional($data)->itens && count($data->itens) > 0)
                            @foreach($data->itens as $idx => $it)
                                <tr>
                                    <td>
                                        <select name="itens[{{ $idx }}][tipo_item]" class="form-control tipo-item" onchange="mudarTipoItem(this)" required>
                                            <option value="Residuo" {{ $it->tipo_item == 'Residuo' ? 'selected' : '' }}>Resíduo / Sucata (Coleta)</option>
                                            <option value="Frete" {{ $it->tipo_item == 'Frete' ? 'selected' : '' }}>Frete (Viagem Coleta)</option>
                                            <option value="Locacao" {{ $it->tipo_item == 'Locacao' ? 'selected' : '' }}>Locação (Caçamba / Equip.)</option>
                                            <option value="Servico" {{ $it->tipo_item == 'Servico' ? 'selected' : '' }}>Serviço Geral</option>
                                        </select>
                                    </td>
                                    <td>
                                        <!-- Box Produto (Resíduo ou Caçamba) -->
                                        <div class="box-produto mb-1" style="{{ in_array($it->tipo_item, ['Residuo', 'Locacao']) ? '' : 'display:none;' }}">
                                            <label class="small text-muted font-weight-bold mb-0 label-produto">
                                                {{ $it->tipo_item == 'Locacao' ? 'EQUIPAMENTO / CAÇAMBA (LOCAÇÃO):' : 'RESÍDUO / MATERIAL:' }}
                                            </label>
                                            <select name="itens[{{ $idx }}][produto_id]" class="form-control select-produto" {{ in_array($it->tipo_item, ['Residuo', 'Locacao']) ? 'required' : '' }}>
                                                <option value="">Selecione...</option>
                                                @php
                                                    $itensLista = ($it->tipo_item == 'Locacao') ? ($produtosLocacao ?? []) : ($produtos ?? []);
                                                @endphp
                                                @foreach($itensLista as $p)
                                                    <option value="{{ $p->id }}" {{ $it->produto_id == $p->id ? 'selected' : '' }}>
                                                        {{ $p->nome }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Box Serviço Fiscal (NFS-e) -->
                                        <div class="box-servico" style="{{ in_array($it->tipo_item, ['Residuo', 'Frete', 'Servico']) ? '' : 'display:none;' }}">
                                            <label class="small text-muted font-weight-bold mb-0">SERVIÇO FISCAL (NFS-E):</label>
                                            <select name="itens[{{ $idx }}][servico_id]" class="form-control select-servico" {{ in_array($it->tipo_item, ['Frete', 'Servico']) ? 'required' : '' }}>
                                                <option value="">(Nenhum / Sucata para Abatimento)</option>
                                                @foreach($servicos ?? [] as $s)
                                                    <option value="{{ $s->id }}" {{ $it->servico_id == $s->id ? 'selected' : '' }}>
                                                        {{ $s->nome }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-info d-block msg-sucata" style="{{ $it->tipo_item == 'Residuo' ? '' : 'display:none;' }}">
                                                Deixe sem serviço se for sucata para abatimento de crédito.
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <select name="itens[{{ $idx }}][unidade]" class="form-control select-unidade font-weight-bold text-center text-primary" required>
                                            <option value="TON" {{ ($it->unidade ?? 'TON') == 'TON' ? 'selected' : '' }}>TON (Tonelada)</option>
                                            <option value="KG" {{ ($it->unidade ?? '') == 'KG' ? 'selected' : '' }}>KG (Quilograma)</option>
                                            <option value="M3" {{ ($it->unidade ?? '') == 'M3' ? 'selected' : '' }}>M³ (Metro Cúbico)</option>
                                            <option value="VIAGEM" {{ ($it->unidade ?? '') == 'VIAGEM' ? 'selected' : '' }}>VIAGEM</option>
                                            <option value="MES" {{ ($it->unidade ?? '') == 'MES' ? 'selected' : '' }}>MÊS (Locação)</option>
                                            <option value="DIARIA" {{ ($it->unidade ?? '') == 'DIARIA' ? 'selected' : '' }}>DIÁRIA</option>
                                            <option value="UN" {{ ($it->unidade ?? '') == 'UN' ? 'selected' : '' }}>UN</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="itens[{{ $idx }}][quantidade_prevista]" class="form-control money" value="{{ number_format($it->quantidade_prevista, 2, ',', '.') }}" required></td>
                                    <td><input type="text" name="itens[{{ $idx }}][valor_unitario]" class="form-control money" value="{{ number_format($it->valor_unitario, 2, ',', '.') }}" required></td>
                                    <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-danger" onclick="removerLinha(this)"><i class="fa fa-trash"></i></button></td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" onclick="adicionarItem()">
                        <i class="fa fa-plus-circle"></i> Adicionar Item / Tarifa
                    </button>
                </div>

                <!-- 3. LOCAL DA OBRA / EMPRESA -->
                <div class="section-title">
                    <i class="fa fa-map-marker-alt text-primary"></i> 3. Local da Operação / Obra
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
                            @foreach($cidades ?? [] as $cid)
                                <option value="{{ $cid->id }}" {{ (optional($data)->cidade_obra_id ==$cid->id) ? 'selected' : '' }}>
                                    {{ $cid->nome }} - {{$cid->uf }}
                                </option>
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
                        <label class="font-weight-bold text-muted small">VALOR ESTIMADO DO CONTRATO (R$)</label>
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
                        <textarea name="observacoes" class="form-control" rows="2" placeholder="Observações e detalhes contratuais...">{{ old('observacoes', optional($data)->observacoes) }}</textarea>
                    </div>
                </div>

                <!-- BOTÕES -->
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
        const servicosLista = @json($servicos ?? []);
        const produtosGeraisLista = @json($produtos ?? []);          // Sem os itens de locação
        const produtosLocacaoLista = @json($produtosLocacao ?? []);    // Apenas caçambas e contêineres

        $(document).ready(function() {
            $('.select2-busca').select2({
                placeholder: "Digite para buscar...",
                allowClear: true,
                language: { noResults: function() { return "Nenhum resultado encontrado"; } }
            });
        });

        function montarOptions(tipo) {
            let lista = (tipo === 'Locacao') ? produtosLocacaoLista : produtosGeraisLista;
            let placeholder = (tipo === 'Locacao') ? 'Selecione a Caçamba / Equipamento...' : 'Selecione o Resíduo...';

            let html = `<option value="">${placeholder}</option>`;
            if (lista && lista.length > 0) {
                lista.forEach(function(item) {
                    html += `<option value="${item.id}">${item.nome}</option>`;
                });
            } else {
                html += `<option value="" disabled>(Nenhum item encontrado nesta categoria)</option>`;
            }
            return html;
        }

        function adicionarItem() {
            let optServicos = '<option value="">(Nenhum / Sucata para Abatimento)</option>';
            servicosLista.forEach(s => optServicos += `<option value="${s.id}">${s.nome}</option>`);

            let optProdutos = montarOptions('Residuo');

            let html = `<tr>
            <td>
                <select name="itens[${itemIdx}][tipo_item]" class="form-control tipo-item" onchange="mudarTipoItem(this)" required>
                    <option value="Residuo">Resíduo / Sucata (Coleta)</option>
                    <option value="Frete">Frete (Viagem Coleta)</option>
                    <option value="Locacao">Locação (Caçamba / Equip.)</option>
                    <option value="Servico">Serviço Geral</option>
                </select>
            </td>
            <td>
                <div class="box-produto mb-1">
                    <label class="small text-muted font-weight-bold mb-0 label-produto">RESÍDUO / MATERIAL:</label>
                    <select name="itens[${itemIdx}][produto_id]" class="form-control select-produto" required>
                        ${optProdutos}
                    </select>
                </div>

                <div class="box-servico">
                    <label class="small text-muted font-weight-bold mb-0">SERVIÇO FISCAL (NFS-E):</label>
                    <select name="itens[${itemIdx}][servico_id]" class="form-control select-servico">
                        ${optServicos}
                    </select>
                    <small class="text-info d-block msg-sucata">Deixe sem serviço se for sucata para abatimento de crédito.</small>
                </div>
            </td>
            <td>
                <select name="itens[${itemIdx}][unidade]" class="form-control select-unidade font-weight-bold text-center text-primary" required>
                    <option value="TON" selected>TON (Tonelada)</option>
                    <option value="KG">KG (Quilograma)</option>
                    <option value="M3">M³ (Metro Cúbico)</option>
                    <option value="VIAGEM">VIAGEM</option>
                    <option value="MES">MÊS (Locação)</option>
                    <option value="DIARIA">DIÁRIA</option>
                    <option value="UN">UN</option>
                </select>
            </td>
            <td><input type="number" step="0.01" name="itens[${itemIdx}][quantidade_prevista]" class="form-control" value="1.00" required></td>
            <td><input type="number" step="0.01" name="itens[${itemIdx}][valor_unitario]" class="form-control" placeholder="0.00" required></td>
            <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-danger" onclick="removerLinha(this)"><i class="fa fa-trash"></i></button></td>
        </tr>`;

            $('#tabela-itens tbody').append(html);
            itemIdx++;
        }

        function mudarTipoItem(select) {
            let tr = $(select).closest('tr');
            let tipo = $(select).val();

            let boxProduto   = tr.find('.box-produto');
            let labelProduto = tr.find('.label-produto');
            let selProduto   = tr.find('.select-produto');

            let boxServico   = tr.find('.box-servico');
            let selServico   = tr.find('.select-servico');
            let msgSucata    = tr.find('.msg-sucata');

            let selUnidade   = tr.find('.select-unidade');

            if (tipo === 'Residuo') {
                boxProduto.show();
                labelProduto.text('RESÍDUO / MATERIAL:');
                selProduto.html(montarOptions('Residuo')).attr('required', true);

                boxServico.show();
                selServico.removeAttr('required');
                msgSucata.show();

                selUnidade.val('TON');
            } else if (tipo === 'Locacao') {
                boxProduto.show();
                labelProduto.text('EQUIPAMENTO / CAÇAMBA (LOCAÇÃO):');
                selProduto.html(montarOptions('Locacao')).attr('required', true);

                boxServico.hide();
                selServico.removeAttr('required').val('');
                msgSucata.hide();

                selUnidade.val('MES');
            } else if (tipo === 'Frete') {
                boxProduto.hide();
                selProduto.removeAttr('required').val('');

                boxServico.show();
                selServico.attr('required', true);
                msgSucata.hide();

                selUnidade.val('VIAGEM');
            } else if (tipo === 'Servico') {
                boxProduto.hide();
                selProduto.removeAttr('required').val('');

                boxServico.show();
                selServico.attr('required', true);
                msgSucata.hide();

                selUnidade.val('UN');
            }
        }

        function removerLinha(btn) {
            $(btn).closest('tr').remove();
        }
    </script>
@endsection
