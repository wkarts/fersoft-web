@extends('default.layout')
@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body">
        <h4 class="mb-4 text-primary font-weight-bold">
            <i class="fa fa-file-invoice-dollar"></i> Editar Medição / Faturamento #{{ $fatura->id }}
        </h4>

        <!-- EXIBIÇÃO DE ERROS OU SUCESSO -->
        @if(session('mensagem_erro'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-triangle"></i> {{ session('mensagem_erro') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('mensagem_sucesso'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle"></i> {{ session('mensagem_sucesso') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <form method="POST" action="{{ route('contratos.medicoes.update', $fatura->id) }}">
            @csrf

            <!-- Bloco de Seleção (Contrato ou Avulso) -->
            <div class="bg-light p-3 rounded mb-4 border">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label class="small font-weight-bold text-muted">VINCULAR CONTRATO DE ENGENHARIA (OPCIONAL)</label>
                        <select name="contrato_eng_id" id="contrato_eng_id" class="form-control" onchange="mudarContrato(this)">
                            <option value="">-- Nenhum (Lançamento Avulso / Sem Contrato) --</option>
                            @foreach($contratos ?? [] as $con)
                                <option value="{{ $con->id }}" {{ $fatura->contrato_eng_id == $con->id ? 'selected' : '' }}>
                                    Contrato Nº {{ $con->numero_contrato ?? $con->id }} - Cliente: {{ $con->cliente->razao_social ?? 'N/D' }} (R$ {{ number_format($con->valor_contrato ?? 0, 2, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-6">
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
                </div>
            </div>

            <hr>
            <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-boxes"></i> 1. Itens da Medição (Mão de Obra e Locações)</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped" id="tabela-itens">
                    <thead class="bg-secondary text-white">
                        <tr>
                            <th style="width: 20%;">Tipo</th>
                            <th style="width: 40%;">Item (Serviço / Equipamento)</th>
                            <th style="width: 15%;">Quantidade</th>
                            <th style="width: 20%;">Valor Unitário (R$)</th>
                            <th style="width: 5%;" class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Os itens salvos serão carregados pelo JS ou dinamizados aqui -->
                    </tbody>
                </table>
                <button type="button" class="btn btn-primary btn-sm" onclick="adicionarItem()"><i class="fa fa-plus"></i> Adicionar Item</button>
            </div>

            <hr>
            <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-users"></i> Equipe / Funcionários Envolvidos</h5>
            <div id="container-funcionarios">
                <div class="row item-funcionario mb-2">
                    <div class="form-group col-md-4">
                        <label class="small font-weight-bold text-muted">FUNCIONÁRIO</label>
                        <select name="funcionarios[0][funcionario_id]" class="form-control">
                            <option value="">Selecione o Funcionário</option>
                            @foreach($funcionarios ?? [] as $func)
                                <option value="{{ $func->id }}">{{ $func->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="small font-weight-bold text-muted">FUNÇÃO</label>
                        <input type="text" name="funcionarios[0][funcao]" class="form-control" placeholder="Ex: Eletricista / Técnico">
                    </div>
                    <div class="form-group col-md-2">
                        <label class="small font-weight-bold text-muted">DIÁRIAS / QTD</label>
                        <input type="number" step="0.01" name="funcionarios[0][diarias]" class="form-control qtd-func" value="1">
                    </div>
                    <div class="form-group col-md-2">
                        <label class="small font-weight-bold text-muted">VALOR UNIT. (R$)</label>
                        <input type="text" name="funcionarios[0][valor_diaria]" class="form-control valor-func" value="0,00">
                    </div>
                    <div class="form-group col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-block remover-funcionario"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            </div>
            <button type="button" id="add-funcionario" class="btn btn-sm btn-secondary mb-3"><i class="fa fa-plus"></i> Adicionar Funcionário</button>
            
            <hr>
            <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-wallet"></i> 2. Condição de Pagamento e Financeiro</h5>
            <div class="row">
                <div class="form-group col-md-4">
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

                <div class="form-group col-md-3">
                    <label class="small font-weight-bold text-muted">DATA DE EMISSÃO <span class="text-danger">*</span></label>
                    <input type="date" name="nf_data_emissao" class="form-control" value="{{ $fatura->data_faturamento ?? date('Y-m-d') }}" required>
                </div>

                <div class="form-group col-md-2">
                    <label class="small font-weight-bold text-muted">STATUS</label>
                    <select name="status" class="form-control" required>
                        <option value="Pendente" {{ $fatura->status == 'Pendente' ? 'selected' : '' }}>Pendente</option>
                        <option value="Finalizado" {{ $fatura->status == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                        <option value="Quitado" {{ $fatura->status == 'Quitado' ? 'selected' : '' }}>Quitado</option>
                    </select>
                </div>
              
                <div class="form-group col-md-3">
                    <label class="small font-weight-bold text-muted">VALOR TOTAL (R$)</label>
                    <input type="text" name="valor_total" id="valor_total" class="form-control font-weight-bold text-primary" value="{{ number_format($fatura->valor_total, 2, ',', '.') }}" readonly required>
                </div>
            </div>

            <!-- SEÇÃO 4: AJUSTADA COM BORDAS E SELECTS VISÍVEIS -->
            <hr>
            <h5 class="text-primary font-weight-bold mb-3"><i class="fa fa-map-marker-alt"></i> 4. Informações Específicas para NFS-e / Obra</h5>
            <div class="row bg-light p-3 rounded border">
                <!-- Serviço Principal -->
                <div class="form-group col-md-4">
                    <label class="font-weight-bold text-dark">Serviço Principal da Medição <span class="text-danger">*</span></label>
                    <select name="servico_id" class="custom-select form-control border-secondary" required>
                        <option value="">Selecione o Serviço...</option>
                        @foreach($servicos ?? [] as $s)
                            <option value="{{ $s->id }}" @if(isset($fatura) && $fatura->servico_id == $s->id) selected @endif>
                                {{ $s->nome }} ({{ $s->codigo_tributacao_nacional ?? $s->codigo_servico }})
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Serviço que definirá a alíquota e códigos na NFS-e</small>
                </div>

                <!-- Código da Obra / ART -->
                <div class="form-group col-md-4">
                    <label class="font-weight-bold text-dark">Código da Obra / CIB / ART</label>
                    <input type="text" name="codigo_obra" class="form-control border-secondary" 
                           placeholder="Ex: OBRA-12345 ou ART-2026/09" 
                           value="{{ isset($fatura) ? $fatura->codigo_obra : old('codigo_obra') }}">
                    <small class="form-text text-muted">Obrigatório para serviços de construção civil</small>
                </div>

                <!-- Cidade de Prestação do Serviço -->
                <div class="form-group col-md-4">
                    <label class="font-weight-bold text-dark">Cidade onde o Serviço foi Prestado</label>
                    <select name="cidade_prestacao_id" class="custom-select form-control border-secondary">
                        <option value="">Selecione a Cidade (Padrão: Cidade da Empresa)...</option>
                        @foreach($cidades ?? [] as $cid)
                            <option value="{{ $cid->id }}" @if(isset($fatura) && $fatura->municipio_prestacao_id == $cid->id) selected @endif>
                                {{ $cid->nome }} ({{ $cid->uf }}) - IBGE: {{ $cid->codigo }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Local de incidência do ISSQN</small>
                </div>
            </div>
          
            <!-- Campo de Observação -->
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
                    <button type="submit" class="btn btn-success btn-sm px-4">
                        <i class="fa fa-check"></i> Atualizar Lançamento e Financeiro
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

    let itemIdx = 0;

    document.addEventListener("DOMContentLoaded", function() {
        adicionarItem();
        calcularTotalGeral();
    });

    function adicionarItem() {
        let optionsServicos = servicosLista.map(s => `<option value="${s.id}">${s.nome}</option>`).join('');
        let optionsProdutos = produtosLista.map(p => `<option value="${p.id}">${p.nome}</option>`).join('');

        let html = `<tr>
            <td>
                <select name="itens[${itemIdx}][tipo_item]" class="form-control tipo-item" required>
                    <option value="Servico">Serviço (Mão de Obra)</option>
                    <option value="Locacao">Locação (Equipamento)</option>
                </select>
            </td>
            <td>
                <select name="itens[${itemIdx}][servico_id]" class="form-control servico-select">
                    <option value="">Selecione o Serviço</option>
                    ${optionsServicos}
                </select>
                <select name="itens[${itemIdx}][produto_id]" class="form-control produto-select" style="display:none;">
                    <option value="">Selecione o Equipamento</option>
                    ${optionsProdutos}
                </select>
            </td>
            <td><input type="number" step="0.01" name="itens[${itemIdx}][quantidade]" class="form-control" value="1" required oninput="calcularTotalGeral()"></td>
            <td><input type="text" name="itens[${itemIdx}][valor_unitario]" class="form-control money" required oninput="calcularTotalGeral()"></td>
            <td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        
        document.querySelector('#tabela-itens tbody').insertAdjacentHTML('beforeend', html);
        itemIdx++;
    }

    function removerLinha(btn) {
        btn.closest('tr').remove();
        calcularTotalGeral();
    }

    document.addEventListener('change', function(e) {
        if(e.target.classList.contains('tipo-item')) {
            let tr = e.target.closest('tr');
            let servicoSelect = tr.querySelector('.servico-select');
            let produtoSelect = tr.querySelector('.produto-select');
            if(e.target.value === 'Servico') {
                servicoSelect.style.display = 'block';
                servicoSelect.setAttribute('required', 'required');
                produtoSelect.style.display = 'none';
                produtoSelect.removeAttribute('required');
            } else {
                produtoSelect.style.display = 'block';
                produtoSelect.setAttribute('required', 'required');
                servicoSelect.style.display = 'none';
                servicoSelect.removeAttribute('required');
            }
        }
    });

    function calcularTotalGeral() {
        let total = 0;
        document.querySelectorAll('#tabela-itens tbody tr').forEach(tr => {
            let qtd = parseFloat(tr.querySelector('input[name*="[quantidade]"]').value) || 0;
            let vlUnitStr = tr.querySelector('input[name*="[valor_unitario]"]').value || '0';
            let vlUnit = parseFloat(vlUnitStr.replace(/\./g, '').replace(',', '.')) || 0;
            total += (qtd * vlUnit);
        });
        document.getElementById('valor_total').value = total.toFixed(2).replace('.', ',');
    }

    const funcionariosData = @json($funcionarios ?? []);

    document.addEventListener('change', function(e) {
        if(e.target.name && e.target.name.includes('[funcionario_id]')) {
            let funcId = e.target.value;
            let row = e.target.closest('.item-funcionario');
            let inputFuncao = row.querySelector('input[name*="[funcao]"]');
            
            let funcionario = funcionariosData.find(f => f.id == funcId);
            if (funcionario && inputFuncao) {
                inputFuncao.value = funcionario.funcao || 'Técnico / Operacional';
            }
        }
    });
</script>
@endsection