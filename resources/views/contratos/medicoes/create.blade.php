@extends('default.layout')
@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h4 class="mb-4 text-primary font-weight-bold">
                <i class="fa fa-file-invoice-dollar"></i> {{ $title ?? 'Nova Medição / Faturamento Mensal' }}
            </h4>

            @if(session('mensagem_erro'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-triangle"></i> {{ session('mensagem_erro') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <form method="POST" action="{{ isset($contratoSelecionado) ? route('contratos.medicoes.store', $contratoSelecionado->id) : route('contratos.medicoes.store.avulso') }}">
                @csrf

                <!-- SELEÇÃO DE CONTRATO E CLIENTE -->
                <div class="bg-light p-3 rounded mb-4 border">
                    <div class="row">
                        <div class="form-group col-md-6 mb-0">
                            <label class="small font-weight-bold text-muted">VINCULAR CONTRATO DE ENGENHARIA</label>
                            <select name="contrato_eng_id" id="contrato_eng_id" class="form-control" onchange="mudarContrato(this)">
                                <option value="">-- Nenhum (Lançamento Avulso / Sem Contrato) --</option>
                                @foreach($contratos ?? [] as $con)
                                    <option value="{{ $con->id }}" {{ (isset($contratoSelecionado) && $contratoSelecionado->id == $con->id) ? 'selected' : '' }}>
                                        Contrato Nº {{ $con->numero_contrato ?? $con->id }} - Cliente: {{ $con->cliente->razao_social ?? 'N/D' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6 mb-0">
                            <label class="small font-weight-bold text-muted">CLIENTE <span class="text-danger">*</span></label>
                            <select name="cliente_id" id="cliente_id" class="form-control" required>
                                <option value="">Selecione o Cliente</option>
                                @foreach($clientes ?? [] as $cli)
                                    <option value="{{ $cli->id }}" {{ (isset($contratoSelecionado) && $contratoSelecionado->cliente_id == $cli->id) ? 'selected' : '' }}>
                                        {{ $cli->id }} - {{ $cli->razao_social ?? $cli->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 🚀 BOTÃO DE APURAÇÃO AUTOMÁTICA DE VIAGENS E PESAGENS -->
                <div class="bg-light-primary p-4 rounded mb-4 border border-primary">
                    <h5 class="text-primary font-weight-bold mb-3"><i class="fa fa-magic"></i> Apuração Automática via Movimentação de Frota e Balança</h5>
                    <div class="row align-items-end">
                        <div class="form-group col-md-4 mb-0">
                            <label class="font-weight-bold text-primary small"><i class="fa fa-calendar-alt"></i> DATA INICIAL</label>
                            <input type="date" id="apuracao_data_inicial" class="form-control" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="form-group col-md-4 mb-0">
                            <label class="font-weight-bold text-primary small"><i class="fa fa-calendar-alt"></i> DATA FINAL</label>
                            <input type="date" id="apuracao_data_final" class="form-control" value="{{ date('Y-m-t') }}">
                        </div>
                        <div class="form-group col-md-4 mb-0">
                            <button type="button" class="btn btn-primary btn-block font-weight-bold shadow-sm" onclick="carregarApuracaoContrato()">
                                <i class="fa fa-sync-alt"></i> Puxar Histórico de Viagens e Pesagens
                            </button>
                        </div>
                    </div>
                </div>

                <hr>
                <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-boxes"></i> 1. Itens da Medição (Serviços, Descarte e Locações)</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped" id="tabela-itens">
                        <thead class="bg-secondary text-white">
                        <tr>
                            <th style="width: 20%;">Tipo</th>
                            <th style="width: 40%;">Item (Serviço / Equipamento / Sucata)</th>
                            <th style="width: 15%;">Quantidade</th>
                            <th style="width: 20%;">Valor Unitário (R$)</th>
                            <th style="width: 5%;" class="text-center">Ação</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-primary btn-sm" onclick="adicionarItem()"><i class="fa fa-plus"></i> Adicionar Item Manual</button>
                </div>

                <hr>
                <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-users"></i> Equipe / Funcionários Envolvidos</h5>
                <div id="container-funcionarios"></div>
                <button type="button" id="add-funcionario" class="btn btn-sm btn-secondary mb-3" onclick="adicionarFuncionario()"><i class="fa fa-plus"></i> Adicionar Funcionário</button>

                <!-- 3. INFORMAÇÕES ESPECÍFICAS PARA NFS-E (APENAS UMA VEZ, SEM DUPLICAÇÃO) -->
                <hr>
                <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-map-marker-alt"></i> 3. Informações Específicas para NFS-e / Obra</h5>
                <div class="row bg-light p-3 rounded border">
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold text-dark">Serviço Principal da Medição <span class="text-danger">*</span></label>
                        <select name="servico_id" class="form-control border-secondary" required>
                            <option value="">Selecione o Serviço Fiscal...</option>
                            @foreach($servicos as $s)
                                <option value="{{ $s->id }}">{{ $s->nome }} ({{ $s->codigo_servico ?? $s->codigo_tributacao_nacional }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold text-dark">Código da Obra / CIB / ART</label>
                        <input type="text" name="codigo_obra" class="form-control border-secondary" placeholder="Ex: OBRA-12345">
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold text-dark">Cidade onde o Serviço foi Prestado</label>
                        <select name="municipio_prestacao_id" class="form-control border-secondary">
                            <option value="">Selecione a Cidade...</option>
                            @foreach($cidades as $cidade)
                                <option value="{{ $cidade->id }}" {{ ($cidade->codigo ?? $cidade->codigo_ibge) == '2910057' ? 'selected' : '' }}>
                                    {{ $cidade->nome }} ({{ $cidade->uf }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="form-group col-md-12">
                        <label class="small font-weight-bold text-muted">OBSERVAÇÕES DO LANÇAMENTO</label>
                        <textarea name="observacao" class="form-control" rows="2" placeholder="Informações adicionais..."></textarea>
                    </div>
                </div>

                <hr>
                <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-wallet"></i> 4. Condição de Pagamento e Financeiro</h5>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label class="small font-weight-bold text-muted">CATEGORIA DA CONTA <span class="text-danger">*</span></label>
                        <select name="categoria_conta_id" class="form-control" required>
                            <option value="">Selecione</option>
                            @foreach($categorias ?? [] as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nome ?? $cat->descricao }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="small font-weight-bold text-muted">TIPO DE PAGAMENTO <span class="text-danger">*</span></label>
                        <select name="tipo_pagamento" class="form-control" required>
                            <option value="">Selecione</option>
                            @foreach($tiposPagamento ?? [] as $tipo)
                                <option value="{{ $tipo }}">{{ $tipo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="small font-weight-bold text-danger">(-) ABATIMENTO SUCATA (R$)</label>
                        <input type="text" name="valor_abatimento_compra" id="valor_abatimento_compra" class="form-control money text-danger font-weight-bold" value="0,00" readonly>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="small font-weight-bold text-muted">VALOR TOTAL LÍQUIDO (R$)</label>
                        <input type="text" name="valor_total" id="valor_total" class="form-control font-weight-bold text-primary" readonly required>
                    </div>
                </div>

                <hr>
                <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-calendar-alt"></i> 5. Parcelas e Vencimentos</h5>
                <div id="container-parcelas">
                    <div class="row parcela-row mb-2">
                        <div class="col-md-5">
                            <label class="small text-muted">Vencimento</label>
                            <input type="date" name="parcelas[0][vencimento]" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-5">
                            <label class="small text-muted">Valor da Parcela (R$)</label>
                            <input type="text" name="parcelas[0][valor]" id="primeira_parcela" class="form-control money" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerParcela(this)"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" id="btn-add-parcela" class="btn btn-secondary btn-sm mb-4" onclick="adicionarParcela()"><i class="fa fa-plus"></i> Adicionar Parcela</button>

                <div class="row mt-4 border-top pt-3">
                    <div class="col-md-12 text-right">
                        <a href="{{ route('contratos.medicoes.index') }}" class="btn btn-secondary btn-sm px-4">
                            <i class="fa fa-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-success btn-sm px-4 font-weight-bold">
                            <i class="fa fa-check"></i> Gerar Faturamento / OS
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const contratosData = @json($contratos ?? []);
        const servicosLista = @json($servicos ?? []);
        const produtosLista = @json($produtos ?? []);
        const funcionariosLista = @json($funcionarios ?? []);

        let itemIdx = 0;
        let funcIdx = 0;

        document.addEventListener("DOMContentLoaded", function() {
            const contratoSelect = document.getElementById('contrato_eng_id');
            if (contratoSelect && contratoSelect.value) {
                carregarContratoCompleto(contratoSelect.value);
            } else {
                adicionarItem();
                adicionarFuncionario();
            }
        });

        function mudarContrato(select) {
            const contratoId = select.value;
            document.querySelector('#tabela-itens tbody').innerHTML = '';
            document.getElementById('container-funcionarios').innerHTML = '';
            itemIdx = 0;
            funcIdx = 0;

            if (!contratoId) {
                document.getElementById('cliente_id').value = '';
                adicionarItem();
                adicionarFuncionario();
                calcularTotalGeral();
                return;
            }

            carregarContratoCompleto(contratoId);
        }

        function carregarContratoCompleto(contratoId) {
            const contrato = contratosData.find(c => c.id == contratoId);
            if (!contrato) return;

            if (contrato.cliente_id) {
                document.getElementById('cliente_id').value = contrato.cliente_id;
            }

            document.querySelector('#tabela-itens tbody').innerHTML = '';
            itemIdx = 0;
            if (contrato.itens && contrato.itens.length > 0) {
                contrato.itens.forEach(item => {
                    adicionarItemPreenchido(item);
                });
            } else {
                adicionarItem();
            }

            document.getElementById('container-funcionarios').innerHTML = '';
            funcIdx = 0;
            if (contrato.funcionarios && contrato.funcionarios.length > 0) {
                contrato.funcionarios.forEach(f => {
                    adicionarFuncionarioPreenchido(f.funcionario_id || f.id, f.funcao || '');
                });
            } else {
                adicionarFuncionario();
            }

            calcularTotalGeral();
        }

        // Usado quando o usuário escolhe um contrato (Itens fixos)
        function adicionarItemPreenchido(dadosItem) {
            let tipo = dadosItem.tipo_item || 'Servico';
            let qtd = dadosItem.quantidade_prevista || dadosItem.quantidade || 1;
            let vlUnit = dadosItem.valor_unitario || 0;

            let nomeServico = '';
            if (dadosItem.servico_id) {
                let s = servicosLista.find(x => x.id == dadosItem.servico_id);
                if (s) nomeServico = s.nome;
            }
            let nomeProduto = '';
            if (dadosItem.produto_id) {
                let p = produtosLista.find(x => x.id == dadosItem.produto_id);
                if (p) nomeProduto = p.nome;
            }

            let descricaoNome = nomeProduto || nomeServico || 'Item do Contrato';
            let isSucata = (tipo === 'Residuo' && !dadosItem.servico_id);

            let html = `<tr>
            <td>
                <select name="itens[${itemIdx}][tipo_item]" class="form-control tipo-item" required>
                    <option value="Servico" ${tipo === 'Servico' ? 'selected' : ''}>Serviço / Descarte</option>
                    <option value="Residuo" ${tipo === 'Residuo' ? 'selected' : ''}>Resíduo / Sucata</option>
                    <option value="Frete" ${tipo === 'Frete' ? 'selected' : ''}>Frete de Transporte</option>
                    <option value="Locacao" ${tipo === 'Locacao' ? 'selected' : ''}>Locação (Equipamento)</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control mb-1 font-weight-bold text-dark ${isSucata ? 'bg-warning' : 'bg-light'}" value="${descricaoNome}" readonly>
                <input type="hidden" name="itens[${itemIdx}][descricao]" value="${descricaoNome}">
                <input type="hidden" name="itens[${itemIdx}][servico_id]" value="${dadosItem.servico_id || ''}">
                <input type="hidden" name="itens[${itemIdx}][produto_id]" value="${dadosItem.produto_id || ''}">

                ${isSucata ? '<small class="text-danger font-weight-bold d-block mt-1">(-) ABATIMENTO SUCATA</small>' : ''}
            </td>
            <td><input type="number" step="0.001" name="itens[${itemIdx}][quantidade]" class="form-control" value="${qtd}" required oninput="calcularTotalGeral()"></td>
            <td><input type="text" name="itens[${itemIdx}][valor_unitario]" class="form-control money" value="${parseFloat(vlUnit).toFixed(2).replace('.', ',')}" required oninput="calcularTotalGeral()"></td>
            <td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)"><i class="fa fa-trash"></i></button></td>
        </tr>`;

            document.querySelector('#tabela-itens tbody').insertAdjacentHTML('beforeend', html);
            itemIdx++;
        }

        // Usado quando o usuário adiciona um item Manualmente (Abre as opções)
        function adicionarItem() {
            let optionsServicos = servicosLista.map(s => `<option value="${s.id}">${s.nome}</option>`).join('');
            let optionsProdutos = produtosLista.map(p => `<option value="${p.id}">${p.nome}</option>`).join('');

            let html = `<tr>
            <td>
                <select name="itens[${itemIdx}][tipo_item]" class="form-control tipo-item" required onchange="mudarTipoItemManual(this)">
                    <option value="Servico" selected>Serviço / Descarte</option>
                    <option value="Residuo">Resíduo / Sucata</option>
                    <option value="Frete">Frete de Transporte</option>
                    <option value="Locacao">Locação (Equipamento)</option>
                </select>
            </td>
            <td>
                <select name="itens[${itemIdx}][servico_id]" class="form-control servico-select mb-1" required>
                    <option value="">Selecione o Serviço Fiscal...</option>
                    ${optionsServicos}
                </select>
                <select name="itens[${itemIdx}][produto_id]" class="form-control produto-select" style="display:none;">
                    <option value="">Selecione o Resíduo/Equipamento...</option>
                    ${optionsProdutos}
                </select>
            </td>
            <td><input type="number" step="0.001" name="itens[${itemIdx}][quantidade]" class="form-control" value="1" required oninput="calcularTotalGeral()"></td>
            <td><input type="text" name="itens[${itemIdx}][valor_unitario]" class="form-control money" value="0,00" required oninput="calcularTotalGeral()"></td>
            <td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)"><i class="fa fa-trash"></i></button></td>
        </tr>`;

            document.querySelector('#tabela-itens tbody').insertAdjacentHTML('beforeend', html);
            itemIdx++;
        }

        // Função de alternância visual exclusiva para Itens Adicionados Manualmente
        function mudarTipoItemManual(select) {
            let tr = $(select).closest('tr');
            let tipo = $(select).val();
            let servicoSelect = tr.find('.servico-select');
            let produtoSelect = tr.find('.produto-select');

            if(tipo === 'Servico' || tipo === 'Frete') {
                servicoSelect.show();
                servicoSelect.attr('required', 'required');
                produtoSelect.hide();
                produtoSelect.removeAttr('required');
                produtoSelect.val('');
            } else if (tipo === 'Residuo') {
                produtoSelect.show();
                produtoSelect.attr('required', 'required');
                servicoSelect.hide();
                servicoSelect.removeAttr('required');
                servicoSelect.val('');
            } else {
                // Locação mostra ambos
                produtoSelect.show();
                produtoSelect.attr('required', 'required');
                servicoSelect.show();
                servicoSelect.attr('required', 'required');
            }
            calcularTotalGeral();
        }

        function removerLinha(btn) {
            btn.closest('tr').remove();
            calcularTotalGeral();
        }

        function adicionarFuncionario() {
            let optionsFunc = funcionariosLista.map(f => `<option value="${f.id}">${f.nome}</option>`).join('');
            let html = `<div class="row item-funcionario mb-2">
            <div class="form-group col-md-5">
                <select name="funcionarios[${funcIdx}][funcionario_id]" class="form-control">
                    <option value="">Selecione o Funcionário</option>
                    ${optionsFunc}
                </select>
            </div>
            <div class="form-group col-md-5">
                <input type="text" name="funcionarios[${funcIdx}][funcao]" class="form-control" placeholder="Função">
            </div>
            <div class="form-group col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-block" onclick="removerFuncionario(this)"><i class="fa fa-trash"></i></button>
            </div>
        </div>`;
            document.getElementById('container-funcionarios').insertAdjacentHTML('beforeend', html);
            funcIdx++;
        }

        function adicionarFuncionarioPreenchido(funcId, funcao) {
            let optionsFunc = funcionariosLista.map(f => `<option value="${f.id}" ${f.id == funcId ? 'selected' : ''}>${f.nome}</option>`).join('');
            let html = `<div class="row item-funcionario mb-2">
            <div class="form-group col-md-5">
                <select name="funcionarios[${funcIdx}][funcionario_id]" class="form-control">
                    <option value="">Selecione o Funcionário</option>
                    ${optionsFunc}
                </select>
            </div>
            <div class="form-group col-md-5">
                <input type="text" name="funcionarios[${funcIdx}][funcao]" class="form-control" value="${funcao}" placeholder="Função">
            </div>
            <div class="form-group col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-block" onclick="removerFuncionario(this)"><i class="fa fa-trash"></i></button>
            </div>
        </div>`;
            document.getElementById('container-funcionarios').insertAdjacentHTML('beforeend', html);
            funcIdx++;
        }

        function removerFuncionario(btn) {
            btn.closest('.item-funcionario').remove();
        }

        let parcelaIdx = 1;
        function adicionarParcela() {
            let html = `<div class="row parcela-row mb-2">
            <div class="col-md-5">
                <input type="date" name="parcelas[${parcelaIdx}][vencimento]" class="form-control" required>
            </div>
            <div class="col-md-5">
                <input type="text" name="parcelas[${parcelaIdx}][valor]" class="form-control money" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm w-100" onclick="removerParcela(this)"><i class="fa fa-trash"></i></button>
            </div>
        </div>`;
            document.getElementById('container-parcelas').insertAdjacentHTML('beforeend', html);
            parcelaIdx++;
        }

        function removerParcela(btn) {
            btn.closest('.parcela-row').remove();
        }

        function calcularTotalGeral() {
            let subtotalFaturar = 0;
            let subtotalAbater = 0;

            document.querySelectorAll('#tabela-itens tbody tr').forEach(tr => {
                let tipoItem = tr.querySelector('.tipo-item').value;

                // Aceita o select normal (manual) ou o input escondido (automático)
                let servicoInput = tr.querySelector('input[name*="[servico_id]"]');
                let servicoSelect = tr.querySelector('select[name*="[servico_id]"]');
                let servicoId = (servicoInput ? servicoInput.value : null) || (servicoSelect ? servicoSelect.value : null);

                let qtd = parseFloat(tr.querySelector('input[name*="[quantidade]"]').value) || 0;
                let vlUnitStr = tr.querySelector('input[name*="[valor_unitario]"]').value || '0';
                let vlUnit = parseFloat(vlUnitStr.replace(/\./g, '').replace(',', '.')) || 0;

                // Se for Resíduo E não tiver serviço fiscal, é sucata
                if (tipoItem === 'Residuo' && !servicoId) {
                    subtotalAbater += (qtd * vlUnit);
                } else {
                    subtotalFaturar += (qtd * vlUnit);
                }
            });

            let inputAbatimento = document.getElementById('valor_abatimento_compra');
            if (inputAbatimento) {
                inputAbatimento.value = subtotalAbater.toFixed(2).replace('.', ',');
            }

            let totalLiquido = Math.max(0, subtotalFaturar - subtotalAbater);
            document.getElementById('valor_total').value = totalLiquido.toFixed(2).replace('.', ',');

            let primeiraParcela = document.getElementById('primeira_parcela');
            if (primeiraParcela) {
                primeiraParcela.value = totalLiquido.toFixed(2).replace('.', ',');
            }
        }

        // 🚀 APURAÇÃO AUTOMÁTICA DE VIAGENS E PESAGENS COM DATAS
        function carregarApuracaoContrato() {
            let contratoId = document.getElementById('contrato_eng_id').value;
            let dataInicial = document.getElementById('apuracao_data_inicial').value;
            let dataFinal = document.getElementById('apuracao_data_final').value;

            if (!contratoId) {
                alert('Por favor, selecione um Contrato vinculado primeiro.');
                return;
            }

            fetch(`/contratos/medicoes/apurar-coleta?contrato_id=${contratoId}&data_inicial=${dataInicial}&data_final=${dataFinal}`)
                .then(res => res.json())
                .then(res => {
                    if (res.sucesso) {
                        if (document.getElementById('valor_abatimento_compra')) {
                            document.getElementById('valor_abatimento_compra').value = res.total_abatimento.toFixed(2).replace('.', ',');
                        }

                        let tbody = document.querySelector('#tabela-itens tbody');
                        tbody.innerHTML = '';
                        itemIdx = 0;

                        let todosItens = [...(res.residuos || []), ...(res.abatimentos || []), ...(res.frete || []), ...(res.locacoes || [])];

                        todosItens.forEach(it => {
                            let isSucata = it.tipo_item === 'Residuo' && !it.servico_id;

                            let html = `<tr>
                            <td>
                                <select name="itens[${itemIdx}][tipo_item]" class="form-control tipo-item" required>
                                    <option value="Servico" ${it.tipo_item === 'Servico' ? 'selected' : ''}>Serviço / Descarte</option>
                                    <option value="Residuo" ${it.tipo_item === 'Residuo' ? 'selected' : ''}>Resíduo / Sucata</option>
                                    <option value="Frete" ${it.tipo_item === 'Frete' ? 'selected' : ''}>Frete de Transporte</option>
                                    <option value="Locacao" ${it.tipo_item === 'Locacao' ? 'selected' : ''}>Locação (Equipamento)</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control mb-1 font-weight-bold text-dark ${isSucata ? 'bg-warning' : 'bg-light'}" value="${it.descricao}" readonly>
                                <input type="hidden" name="itens[${itemIdx}][descricao]" value="${it.descricao}">
                                <input type="hidden" name="itens[${itemIdx}][servico_id]" value="${it.servico_id || ''}">
                                <input type="hidden" name="itens[${itemIdx}][produto_id]" value="${it.produto_id || ''}">

                                ${isSucata ? '<small class="text-danger font-weight-bold d-block mt-1">(-) ABATIMENTO SUCATA</small>' : ''}
                            </td>
                            <td><input type="number" step="0.001" name="itens[${itemIdx}][quantidade]" class="form-control" value="${it.quantidade}" required oninput="calcularTotalGeral()"></td>
                            <td><input type="text" name="itens[${itemIdx}][valor_unitario]" class="form-control money" value="${it.valor_unitario.toFixed(2).replace('.', ',')}" required oninput="calcularTotalGeral()"></td>
                            <td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)"><i class="fa fa-trash"></i></button></td>
                        </tr>`;
                            tbody.insertAdjacentHTML('beforeend', html);
                            itemIdx++;
                        });

                        calcularTotalGeral();
                        alert("Histórico de viagens e coletas importado com sucesso!");
                    } else {
                        alert("Não foram encontrados dados no período.");
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Erro ao apurar dados do contrato.');
                });
        }
    </script>
@endsection
