@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush

@section('content')
    <div class="fer-extra fer-extra--contratos fer-extra--canvas card shadow-sm border-0">
        <div class="card-body">
            <h4 class="mb-4 text-primary font-weight-bold">
                <i class="fa fa-file-invoice-dollar"></i> Editar Medição / Faturamento #{{ $fatura->id }}
            </h4>

            @if(session('mensagem_erro'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-triangle"></i> {{ session('mensagem_erro') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif

            @if(session('mensagem_sucesso'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa fa-check-circle"></i> {{ session('mensagem_sucesso') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif

            <form method="POST" action="{{ route('contratos.medicoes.update', $fatura->id) }}">
                @csrf

                <!-- Bloco de Seleção (Contrato ou Avulso) -->
                <div class="bg-light p-3 rounded mb-4 border">
                    <div class="row">
                        <div class="form-group col-md-5">
                            <label class="small font-weight-bold text-muted">VINCULAR CONTRATO (OPCIONAL)</label>
                            <select name="contrato_eng_id" id="contrato_eng_id" class="form-control">
                                <option value="">-- Nenhum (Lançamento Avulso / Sem Contrato) --</option>
                                @foreach($contratos ?? [] as $con)
                                    <option value="{{ $con->id }}" {{ $fatura->contrato_eng_id == $con->id ? 'selected' : '' }}>
                                        Contrato Nº {{ $con->numero_contrato ?? $con->id }} - Cliente: {{ $con->cliente->razao_social ?? 'N/D' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-4">
                            <label class="small font-weight-bold text-muted">CLIENTE <span class="text-danger">*</span></label>
                            <select name="cliente_id" id="cliente_id" class="form-control" required>
                                <option value="">Selecione o Cliente</option>
                                @foreach($clientes ?? [] as $cli)
                                    <option value="{{ $cli->id }}" {{ $fatura->cliente_id == $cli->id ? 'selected' : '' }}>
                                        {{ $cli->id }} - {{ $cli->razao_social ?? $cli->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-3">
                            <label class="small font-weight-bold text-muted">GRUPO DE FATURAMENTO</label>
                            <select name="grupo_faturamento" class="form-control font-weight-bold">
                                <option value="servico" {{ ($fatura->grupo_faturamento ?? 'servico') == 'servico' ? 'selected' : '' }}>Resíduos / Serviços (NFS-e)</option>
                                <option value="locacao" {{ ($fatura->grupo_faturamento ?? '') == 'locacao' ? 'selected' : '' }}>Locação Caçambas / Equip.</option>
                                <option value="frete" {{ ($fatura->grupo_faturamento ?? '') == 'frete' ? 'selected' : '' }}>Frete / Transporte</option>
                                <option value="geral" {{ ($fatura->grupo_faturamento ?? '') == 'geral' ? 'selected' : '' }}>Geral / Consolidado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 🚀 BOTÃO DE APURAÇÃO AUTOMÁTICA DE COLETAS E VIAGENS -->
                <div class="bg-light-primary p-4 rounded mb-4 border border-primary">
                    <div class="row align-items-end">
                        <div class="form-group col-md-4 mb-0">
                            <label class="font-weight-bold text-primary small"><i class="fa fa-calendar-alt"></i> DATA INICIAL DO PERÍODO</label>
                            <input type="date" id="apuracao_data_inicial" class="form-control" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="form-group col-md-4 mb-0">
                            <label class="font-weight-bold text-primary small"><i class="fa fa-calendar-alt"></i> DATA FINAL DO PERÍODO</label>
                            <input type="date" id="apuracao_data_final" class="form-control" value="{{ date('Y-m-t') }}">
                        </div>
                        <div class="form-group col-md-4 mb-0">
                            <button type="button" class="btn btn-primary btn-block font-weight-bold shadow-sm" onclick="carregarApuracaoContrato()">
                                <i class="fa fa-magic"></i> Puxar Histórico de Viagens e Pesagens
                            </button>
                        </div>
                    </div>
                </div>
                <hr>
                <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-boxes"></i> 1. Itens da Medição (Resíduos, Fretes e Locações)</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped" id="tabela-itens">
                        <thead class="bg-secondary text-white">
                        <tr>
                            <th style="width: 20%;">Tipo</th>
                            <th style="width: 40%;">Item (Serviço / Resíduo / Caçamba)</th>
                            <th style="width: 15%;">Quantidade</th>
                            <th style="width: 20%;">Valor Unitário (R$)</th>
                            <th style="width: 5%;" class="text-center">Ação</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($fatura->itens as $idx => $it)
                            @php
                                // Verifica se o item gravado é sucata (é Residuo e não tem Serviço vinculado)
                                $isSucataItem = ($it->tipo_item === 'Residuo' && empty($it->servico_id));
                            @endphp
                            <tr>
                                <td>
                                    <!-- AQUI: Adicionado os 4 Tipos para evitar o Erro do Banco de Dados! -->
                                    <select name="itens[{{$idx}}][tipo_item]" class="form-control tipo-item" required>
                                        <option value="Servico" {{ ($it->tipo_item ?? '') === 'Servico' ? 'selected' : '' }}>Serviço / Descarte</option>
                                        <option value="Residuo" {{ ($it->tipo_item ?? '') === 'Residuo' ? 'selected' : '' }}>Resíduo / Sucata</option>
                                        <option value="Frete" {{ ($it->tipo_item ?? '') === 'Frete' ? 'selected' : '' }}>Frete de Transporte</option>
                                        <option value="Locacao" {{ ($it->tipo_item ?? '') === 'Locacao' ? 'selected' : '' }}>Locação (Equipamento)</option>
                                    </select>
                                </td>
                                <td>
                                    <!-- Exibe o nome correto ou a descrição da apuração (com as datas) de forma limpa e protegida -->
                                    <input type="text" class="form-control mb-1 font-weight-bold text-dark {{ $isSucataItem ? 'bg-warning' : 'bg-light' }}" value="{{ $it->descricao ?? optional($it->servico)->nome ?? optional($it->produto)->nome ?? 'Item da Medição / Frete' }}" readonly>

                                    <input type="hidden" name="itens[{{$idx}}][descricao]" value="{{ $it->descricao }}">
                                    <input type="hidden" name="itens[{{$idx}}][servico_id]" value="{{ $it->servico_id }}">
                                    <input type="hidden" name="itens[{{$idx}}][produto_id]" value="{{ $it->produto_id }}">

                                    @if($isSucataItem)
                                        <small class="text-danger font-weight-bold d-block mt-1">(-) ABATIMENTO SUCATA</small>
                                    @endif
                                </td>
                                <td><input type="number" step="0.001" name="itens[{{$idx}}][quantidade]" class="form-control" value="{{ $it->quantidade }}" required oninput="calcularTotalGeral()"></td>
                                <td><input type="text" name="itens[{{$idx}}][valor_unitario]" class="form-control money" value="{{ number_format($it->valor_unitario, 2, ',', '.') }}" required oninput="calcularTotalGeral()"></td>
                                <td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)"><i class="fa fa-trash"></i></button></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-primary btn-sm" onclick="adicionarItem()"><i class="fa fa-plus"></i> Adicionar Item Manual</button>
                </div>

                <hr>
                <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-wallet"></i> 2. Condição de Pagamento e Abatimento de Sucatas</h5>
                <div class="row">
                    <div class="form-group col-md-3">
                        <label class="small font-weight-bold text-muted">CATEGORIA DA CONTA <span class="text-danger">*</span></label>
                        <select name="categoria_conta_id" class="form-control" required>
                            <option value="">Selecione</option>
                            @foreach($categorias ?? [] as $cat)
                                <option value="{{ $cat->id }}" {{ $fatura->categoria_conta_id == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->nome ?? $cat->descricao }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-2">
                        <label class="small font-weight-bold text-muted">DATA DE EMISSÃO <span class="text-danger">*</span></label>
                        <input type="date" name="nf_data_emissao" class="form-control" value="{{ date('Y-m-d', strtotime($fatura->data_faturamento ?? now())) }}" required>
                    </div>

                    <div class="form-group col-md-2">
                        <label class="small font-weight-bold text-muted">STATUS</label>
                        <select name="status" class="form-control" required>
                            <option value="Pendente" {{ $fatura->status == 'Pendente' ? 'selected' : '' }}>Pendente</option>
                            <option value="Finalizado" {{ $fatura->status == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                            <option value="Quitado" {{ $fatura->status == 'Quitado' ? 'selected' : '' }}>Quitado</option>
                        </select>
                    </div>

                    <div class="form-group col-md-2">
                        <label class="small font-weight-bold text-danger">(-) DESC. COMPRA SUCATA (R$)</label>
                        <input type="text" name="valor_abatimento_compra" id="valor_abatimento_compra" class="form-control money font-weight-bold text-danger" value="{{ number_format($fatura->valor_abatimento_compra ?? 0, 2, ',', '.') }}" readonly>
                    </div>

                    <div class="form-group col-md-3">
                        <label class="small font-weight-bold text-muted">VALOR TOTAL LÍQUIDO (R$)</label>
                        <input type="text" name="valor_total" id="valor_total" class="form-control font-weight-bold text-primary" value="{{ number_format($fatura->valor_total, 2, ',', '.') }}" readonly required>
                    </div>
                </div>

                <!-- SEÇÃO 3: INFORMAÇÕES ESPECÍFICAS PARA NFS-E -->
                <hr>
                <h5 class="text-primary font-weight-bold mb-3"><i class="fa fa-map-marker-alt"></i> 3. Informações Específicas para NFS-e / Obra</h5>
                <div class="row bg-light p-3 rounded border">
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold text-dark">Serviço Principal da Medição <span class="text-danger">*</span></label>
                        <select name="servico_id" class="custom-select form-control border-secondary" required>
                            <option value="">Selecione o Serviço...</option>
                            @foreach($servicos ?? [] as $s)
                                <option value="{{ $s->id }}" {{ $fatura->servico_id == $s->id ? 'selected' : '' }}>
                                    {{ $s->nome }} ({{ $s->codigo_tributacao_nacional ?? $s->codigo_servico }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold text-dark">Código da Obra / ART / CIB</label>
                        <input type="text" name="codigo_obra" class="form-control border-secondary" placeholder="Ex: ART-2026/09" value="{{ $fatura->codigo_obra }}">
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold text-dark">Cidade onde o Serviço foi Prestado</label>
                        <select name="municipio_prestacao_id" class="custom-select form-control border-secondary">
                            <option value="">Selecione a Cidade...</option>
                            @foreach($cidades ?? [] as $cid)
                                <option value="{{ $cid->id }}" {{ $fatura->municipio_prestacao_id == $cid->id ? 'selected' : '' }}>
                                    {{ $cid->nome }} ({{ $cid->uf }}) - IBGE: {{ $cid->codigo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="form-group col-md-12">
                        <label class="small font-weight-bold text-muted">OBSERVAÇÕES DO LANÇAMENTO</label>
                        <textarea name="observacao" class="form-control" rows="2">{{ $fatura->observacao }}</textarea>
                    </div>
                </div>

                <div class="row mt-4 border-top pt-3">
                    <div class="col-md-12 text-right">
                        <a href="{{ route('contratos.medicoes.index') }}" class="btn btn-secondary btn-sm px-4">
                            <i class="fa fa-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-success btn-sm px-4 font-weight-bold">
                            <i class="fa fa-check"></i> Salvar Alterações
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        let itemIdx = {{ $fatura->itens->count() }};

        function adicionarItem() {
            let html = `<tr>
                <td>
                    <select name="itens[${itemIdx}][tipo_item]" class="form-control tipo-item" required>
                        <option value="Servico">Serviço / Descarte</option>
                        <option value="Residuo">Resíduo / Sucata</option>
                        <option value="Frete">Frete de Transporte</option>
                        <option value="Locacao">Locação (Equipamento)</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control mb-1 bg-light font-weight-bold text-dark" value="Item Adicionado Manualmente">
                    <input type="hidden" name="itens[${itemIdx}][descricao]" value="Item Manual">
                    <input type="hidden" name="itens[${itemIdx}][servico_id]" value="">
                    <input type="hidden" name="itens[${itemIdx}][produto_id]" value="">
                </td>
                <td><input type="number" step="0.001" name="itens[${itemIdx}][quantidade]" class="form-control" value="1" required oninput="calcularTotalGeral()"></td>
                <td><input type="text" name="itens[${itemIdx}][valor_unitario]" class="form-control money" value="0,00" required oninput="calcularTotalGeral()"></td>
                <td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)"><i class="fa fa-trash"></i></button></td>
            </tr>`;

            document.querySelector('#tabela-itens tbody').insertAdjacentHTML('beforeend', html);
            itemIdx++;
        }

        function removerLinha(btn) {
            btn.closest('tr').remove();
            calcularTotalGeral();
        }

        function calcularTotalGeral() {
            let subtotalFaturar = 0;
            let subtotalAbater = 0;

            document.querySelectorAll('#tabela-itens tbody tr').forEach(tr => {
                let tipoItem = tr.querySelector('.tipo-item').value;
                let servicoId = tr.querySelector('input[name*="[servico_id]"]').value;

                let qtd = parseFloat(tr.querySelector('input[name*="[quantidade]"]').value) || 0;
                let vlUnitStr = tr.querySelector('input[name*="[valor_unitario]"]').value || '0';
                let vlUnit = parseFloat(vlUnitStr.replace(/\./g, '').replace(',', '.')) || 0;

                // Se for Resíduo E não tiver serviço, é sucata (abatimento)
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
        }

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

                        // 🚀 AQUI: Juntamos tudo e filtramos possíveis listas vazias
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
                        alert("Histórico importado com sucesso!");
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
