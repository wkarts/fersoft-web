@extends('default.layout')

@section('content')
<div class="container-fluid mt-5 pt-3">
    
    @if(session('mensagem_sucesso'))
        <div class="alert alert-success shadow-sm"><i class="fas fa-check-circle"></i> {{ session('mensagem_sucesso') }}</div>
    @endif
    @if(session('mensagem_erro'))
        <div class="alert alert-danger shadow-sm"><i class="fas fa-exclamation-triangle"></i> {{ session('mensagem_erro') }}</div>
    @endif

    {{-- CARD DE FILTROS --}}
    <div class="card shadow-sm mb-4" style="border: none;">
        <div class="card-header text-white" style="background-color: #8b5cf6; border-radius: 5px 5px 0 0;">
            <h5 class="mb-0 font-weight-bold"><i class="fas fa-filter mr-2"></i> Filtrar Lançamentos</h5>
        </div>
        <div class="card-body">
            <form action="{{ url('financeiro/conciliacao/list') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="text-muted small font-weight-bold">Conta Bancária</label>
                        <select name="conta_filtro" class="form-control">
                            <option value="">Todas</option>
                            @foreach($contasBancarias as $conta)
                                <option value="{{ $conta->id }}" {{ request('conta_filtro') == $conta->id ? 'selected' : '' }}>{{ $conta->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small font-weight-bold">Status</label>
                        <select name="status_filtro" class="form-control">
                            <option value="pending" {{ request('status_filtro', 'pending') == 'pending' ? 'selected' : '' }}>Pendentes (Caixa de Entrada)</option>
                            <option value="reconciled" {{ request('status_filtro') == 'reconciled' ? 'selected' : '' }}>Já Conciliados</option>
                            <option value="" {{ request('status_filtro') === '' ? 'selected' : '' }}>Todos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="text-muted small font-weight-bold">De</label>
                        <input type="date" name="data_inicio" class="form-control" value="{{ request('data_inicio') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="text-muted small font-weight-bold">Até</label>
                        <input type="date" name="data_fim" class="form-control" value="{{ request('data_fim') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn text-white w-100 font-weight-bold" style="background-color: #8b5cf6;">Filtrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- CARD DE IMPORTAÇÃO --}}
    <div class="card shadow-sm mb-4" style="border: none;">
        <div class="card-body">
            <h5 class="card-title text-secondary font-weight-bold mb-3"><i class="fas fa-upload mr-2"></i> Importar OFX</h5>
            <form action="{{ url('financeiro/conciliacao/importar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <select name="conta_bancaria_id" class="form-control" required>
                            <option value="">Selecione a conta destino...</option>
                            @foreach($contasBancarias as $conta)
                                <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="file" name="arquivo" class="form-control" accept=".ofx" required style="padding: 4px;">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 font-weight-bold">Processar Arquivo</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- TABELA DE EXTRATO --}}
    <div class="card shadow-sm" style="border: none;">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0 text-secondary font-weight-bold"><i class="fas fa-list-ul mr-2"></i> Extrato</h5>
            
            <div class="d-flex">
                {{-- NOVO BOTÃO: ZERO-CLICK --}}
                <form action="{{ url('financeiro/conciliacao/processar-automaticos') }}" method="POST" class="mr-2">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success font-weight-bold shadow-sm" title="Rodar regras memorizadas">
                        <i class="fas fa-robot mr-1"></i> Rodar Automação
                    </button>
                </form>

                {{-- BOTÃO LIMPAR EXISTENTE --}}
                <form action="{{ url('financeiro/conciliacao/limpar-pendentes') }}" method="POST" onsubmit="return confirm('Apagar importações pendentes?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger font-weight-bold shadow-sm">Limpar Pendentes</button>
                </form>
              {{-- COLAR ANTES DA TABELA COMEÇAR --}}
              @if(request('conta_filtro'))
                  <div class="alert alert-light border-left-primary shadow-sm mb-3">
                      Mostrando lançamentos de: <strong>{{ $contasBancarias->find(request('conta_filtro'))->nome ?? '' }}</strong>
                  </div>
              @endif
              
            </div>
        </div>
        {{-- ÁREA DOS BOTÕES DE AÇÃO EM MASSA --}}
<div class="mb-3 d-flex justify-content-between align-items-center pl-3 pr-3 bg-white p-3 rounded shadow-sm border">
    <div>
        {{-- BOTÃO INTELIGENTE QUE VOCÊ SUGERIU --}}
        <button type="button" class="btn btn-warning shadow-sm font-weight-bold mr-2 text-dark" onclick="selecionarAtencao()">
            <i class="fas fa-exclamation-triangle"></i> Marcar todos com "Atenção"
        </button>
        <span class="text-muted small">Ideal para baixar o que o ERP já reconheceu.</span>
    </div>
    
    {{-- BOTÃO DE ENVIAR --}}
    <button type="button" class="btn btn-success shadow-sm font-weight-bold px-4" onclick="confirmarLote()">
    <i class="fas fa-check-double"></i> Confirmar Selecionados
	</button>
</div>

{{-- SEU FORMULÁRIO COMEÇA AQUI --}}
<form id="form_lote" action="{{ url('financeiro/conciliacao/processar-lote') }}" method="POST">
    @csrf
    <table class="table table-hover mb-0">
        <thead class="bg-light text-muted">
            <tr>
                {{-- NOVA COLUNA: CAIXINHA DE "SELECIONAR TODOS" --}}
                <th class="border-0 pl-4" style="width: 40px;">
                    <input type="checkbox" id="checkAll" style="cursor: pointer; transform: scale(1.2);">
                </th>
                <th class="border-0">Data</th>
                <th class="border-0">Histórico Bancário</th>
                <th class="border-0">Valor</th>
                <th class="border-0 pr-4 text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $l)
                <tr class="{{ $l->status == 'reconciled' ? 'bg-light text-muted' : '' }}">
                    
                    {{-- NOVA COLUNA: CAIXINHA DE CADA LINHA --}}
                    <td class="align-middle pl-4">
                        @if($l->status == 'pending')
                            <input type="checkbox" name="extrato_ids[]" value="{{ $l->id }}" class="check-item" style="cursor: pointer; transform: scale(1.2);">
                        @endif
                    </td>

                    {{-- SUAS COLUNAS ORIGINAIS CONTINUAM AQUI --}}
                    <td class="align-middle">{{ date('d/m/Y', strtotime($l->data_transacao)) }}</td>
                    <td class="align-middle">
                        @if($l->status == 'reconciled') 
                            <i class="fas fa-check-circle text-success mr-1"></i> 
                        @endif
                        <span class="{{ $l->status == 'reconciled' ? 'font-weight-normal' : 'font-weight-bold text-dark' }}">{{ $l->descricao }}</span>

                        {{-- NOVA ETIQUETA COM O NOME DO BANCO --}}
                        <br>
                        <small class="badge badge-info shadow-sm mt-1" style="font-size: 0.7rem; opacity: 0.9;">
                            <i class="fas fa-university mr-1"></i>
                            {{ $l->contaBancaria->nome ?? 'ID Bancário: ' . $l->conta_bancaria_id }}
                        </small>

                        @if($l->status == 'pending')
                            <div class="mt-1">
                            @if($l->achou_pago)
                                <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-exclamation-triangle"></i> Atenção: Consta PAGO no ERP</span>
                            @elseif($l->achou_pendente)
                                <span class="badge badge-info px-2 py-1"><i class="fas fa-magic"></i> Encontrado (Pendente no ERP)</span>
                            @endif
                            </div>
                        @endif
                    </td>
                    <td class="align-middle {{ $l->tipo == 'credit' ? 'text-success' : 'text-danger' }}">
                        <strong class="h6 font-weight-bold">R$ {{ number_format($l->valor, 2, ',', '.') }}</strong>
                    </td>
                    <td class="align-middle pr-4 text-center">
                        @if($l->status == 'pending')
                            <button type="button" class="btn btn-sm btn-primary font-weight-bold mb-1" onclick="abrirModalVincular({{ json_encode($l) }})">Vincular</button>
                            <button type="button" class="btn btn-sm btn-outline-dark font-weight-bold mb-1" onclick="abrirModalNovo({{ json_encode($l) }})">+ Novo</button>
                            <button type="button" class="btn btn-sm text-white font-weight-bold mb-1" style="background-color: #8b5cf6;" onclick="abrirModalTransferir({{ json_encode($l) }})">Transf.</button>
                            
                            {{-- NOVO BOTÃO OK INDIVIDUAL LIMPO --}}
                            <button type="button" class="btn btn-sm btn-light border-secondary font-weight-bold mb-1" title="Apenas arquivar e tirar da tela" onclick="confirmarOKIndividual({{ $l->id }})">
                                <i class="fas fa-check-double text-success"></i> OK
                            </button>
                        @else
                            <span class="badge badge-success px-3 py-2 font-weight-bold"><i class="fas fa-lock mr-1"></i> CONCILIADO</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-5 text-muted">Nenhum lançamento.</td></tr>
            @endforelse
        </tbody>
    </table>
</form> 

    </div>
</div>

{{-- MODAL VINCULAR (MULTIPLA ESCOLHA) --}}
{{-- MODAL VINCULAR (MULTIPLA ESCOLHA + JUROS/DESCONTOS) --}}
<div class="modal fade" id="modalVincular" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ url('financeiro/conciliacao/conciliar') }}" method="POST" id="formVincular" onsubmit="return validarFechamento()">
            @csrf
            <input type="hidden" name="extrato_id" id="vincular_extrato_id">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #3b82f6;">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-link mr-2"></i> Vincular Títulos</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body bg-light">
                    <div class="row alert alert-primary bg-white border-primary mb-3 mx-0">
                        <div class="col-md-7" id="info_extrato_vincular"></div>
                        <div class="col-md-5 text-right border-left">
                            <small class="d-block text-muted font-weight-bold">Soma com Ajustes:</small>
                            <span class="h4 font-weight-bold text-primary" id="soma_selecionada">R$ 0,00</span>
                            
                            <small class="d-block text-muted font-weight-bold mt-2">Diferença para o Banco:</small>
                            <span class="h5 font-weight-bold text-danger" id="diferenca_valor">R$ 0,00</span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <label class="font-weight-bold text-muted small">Origem ERP</label>
                            <select name="tipo_conta" id="vincular_tipo" class="form-control font-weight-bold" onchange="filtrarSugestoes()">
                                <option value="pagar">Contas a Pagar</option>
                                <option value="receber">Contas a Receber</option>
                            </select>

                            {{-- NOVOS CAMPOS: JUROS E DESCONTOS --}}
                            <div class="mt-4 p-3 bg-white border rounded">
                                <label class="font-weight-bold text-danger small"><i class="fas fa-plus-circle"></i> Juros / Multa</label>
                                <input type="number" step="0.01" min="0" name="acrescimo" id="vincular_acrescimo" class="form-control mb-3" value="0" onkeyup="recalcularSoma()" onchange="recalcularSoma()">

                                <label class="font-weight-bold text-success small"><i class="fas fa-minus-circle"></i> Desconto</label>
                                <input type="number" step="0.01" min="0" name="desconto" id="vincular_desconto" class="form-control" value="0" onkeyup="recalcularSoma()" onchange="recalcularSoma()">
                            </div>
                        </div>
                        
                        <div class="col-md-9">
                            <label class="font-weight-bold text-muted small">Títulos Encontrados (Marque os correspondentes)</label>
                            <div id="lista_checkboxes" class="border rounded bg-white p-2" style="max-height: 280px; overflow-y: auto;">
                                </div>
                            <small id="msg_feedback" class="form-text mt-2 font-weight-bold"></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0">
                    <button type="submit" class="btn btn-primary font-weight-bold px-4" id="btnConfirmarVincular" disabled>Aguardando Fechamento...</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL + NOVO --}}
<div class="modal fade" id="modalNovo" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ url('financeiro/conciliacao/criarEConciliar') }}" method="POST">
            @csrf
            <input type="hidden" name="extrato_id" id="novo_extrato_id">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i> Lançar Despesa/Receita</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body bg-light">
                    <div class="alert alert-secondary bg-white mb-3" id="info_extrato_novo"></div>
                    <div class="form-group mb-3" id="div_categoria_pagar" style="display: none;">
                        <label class="font-weight-bold text-muted small">Categoria de Despesa (Pagar)</label>
                        <select name="categoria_id" id="select_categoria_pagar" class="form-control" disabled>
                            <option value="">Selecione a Categoria...</option>
                            @foreach($categorias as $c)
                                @if($c->tipo == 'pagar')
                                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3" id="div_categoria_receber" style="display: none;">
                        <label class="font-weight-bold text-muted small">Categoria de Receita (Receber)</label>
                        <select name="categoria_id" id="select_categoria_receber" class="form-control" disabled>
                            <option value="">Selecione a Categoria...</option>
                            @foreach($categorias as $c)
                                @if($c->tipo == 'receber')
                                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3" id="div_fornecedor" style="display: none;">
                        <label class="font-weight-bold text-muted small">Favorecido / Fornecedor</label>
                        <select name="fornecedor_id" id="select_fornecedor" class="form-control">
                            <option value="">Selecione o Fornecedor...</option>
                            @foreach($fornecedores as $f)
                                <option value="{{ $f->id }}">{{ $f->razao_social }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3" id="div_cliente" style="display: none;">
                        <label class="font-weight-bold text-muted small">Cliente (Pagador)</label>
                        <select name="cliente_id" id="select_cliente" class="form-control">
                            <option value="">Selecione o Cliente...</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}">{{ $c->razao_social }}</option>
                            @endforeach
                        </select>
                    </div>
                   
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-muted small">Forma de Pagamento</label>
                        <select name="forma_pagamento" class="form-control">
                            <option value="pix" selected>PIX</option>
                            <option value="deposito">Depósito Bancário</option>
                            <option value="transferencia">Transferência / TED</option>
                            <option value="boleto">Boleto</option>
                            <option value="dinheiro">Dinheiro</option>
                        </select>
                    </div>
                  
                    <div class="form-check mt-2 p-3 bg-white border rounded shadow-sm">
                        <input type="checkbox" class="form-check-input ml-1" id="salvar_regra" name="salvar_regra" value="1">
                        <label class="form-check-label font-weight-bold text-primary ml-4" for="salvar_regra" style="cursor: pointer;">
                            Salvar Regra (Lançar Automático no Próximo Mês)
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0">
                    <button type="submit" class="btn btn-dark font-weight-bold px-4">Salvar e Conciliar</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL TRANSFERIR --}}
<div class="modal fade" id="modalTransferir" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ url('financeiro/conciliacao/transferir') }}" method="POST">
            @csrf
            <input type="hidden" name="extrato_id" id="transf_extrato_id">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #8b5cf6;">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-exchange-alt mr-2"></i> Transferência Interna</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body bg-light">
                    <div class="alert bg-white mb-4" style="border-left: 4px solid #8b5cf6;" id="info_extrato_transf"></div>
                    <div class="form-group">
                        <label class="font-weight-bold text-muted small">Para qual conta foi o dinheiro?</label>
                        <select name="conta_destino_id" class="form-control" required>
                            <option value="">Selecione...</option>
                            @foreach($contasBancarias as $conta)
                                <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0">
                    <button type="submit" class="btn text-white font-weight-bold px-4" style="background-color: #8b5cf6;">Confirmar Fluxo</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const pagarSugestoes = @json($contasPagarSugestao ?? []);
const receberSugestoes = @json($contasReceberSugestao ?? []);
let dadoExtratoAtual = null;
  
function abrirModalVincular(dados) {
    dadoExtratoAtual = dados;
    document.getElementById('vincular_extrato_id').value = dados.id;
    document.getElementById('info_extrato_vincular').innerHTML = `
        <span class="small text-muted font-weight-bold">Valor no Extrato Bancário:</span><br>
        <strong class="h6 text-dark">${dados.descricao}</strong><br>
        <strong class="h4 text-primary">R$ ${parseFloat(dados.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</strong>
    `;
    
    document.getElementById('vincular_tipo').value = (dados.tipo == 'credit') ? 'receber' : 'pagar';
    document.getElementById('vincular_acrescimo').value = '0';
    document.getElementById('vincular_desconto').value = '0';
    
    filtrarSugestoes();
    $('#modalVincular').modal('show');
}

  
  
function selecionarAtencao() {
    let marcados = 0;
    // Pega todas as caixinhas de seleção individuais
    let checkboxes = document.querySelectorAll('.check-item');
    
    checkboxes.forEach(function(chk) {
        // Pega a linha (<tr>) inteira onde essa caixinha está
        let linha = chk.closest('tr');
        
        // Se a linha contiver a etiqueta de Atenção, ele marca a caixinha
        if (linha.innerHTML.includes('Atenção: Consta PAGO no ERP')) {
            chk.checked = true;
            marcados++;
        }
    });

    if(marcados === 0) {
        alert('Nenhum lançamento com o status "Atenção" encontrado nesta página.');
    }
}
  
  // --- FUNÇÃO DO BOTÃO DE LOTE ---
function confirmarLote() {
    // Conta quantas caixinhas estão marcadas
    let marcados = document.querySelectorAll('.check-item:checked').length;
    
    if (marcados === 0) {
        alert('Selecione pelo menos um lançamento marcando as caixinhas primeiro.');
        return;
    }
    
    if (confirm(`Você está prestes a confirmar ${marcados} lançamentos de uma vez. Deseja continuar?`)) {
        // Se confirmou, envia o formulário principal
        document.getElementById('form_lote').submit();
    }
}

// --- FUNÇÃO DO BOTÃO OK INDIVIDUAL ---
function confirmarOKIndividual(idExtrato) {
    if (confirm('Confirmar ciência deste lançamento individual? Ele será removido das pendências.')) {
        // Cria um formulário invisível na hora e envia (Resolve o problema de Form dentro de Form)
        let formInvisivel = document.createElement('form');
        formInvisivel.method = 'POST';
        formInvisivel.action = '{{ url("financeiro/conciliacao/arquivar") }}';
        formInvisivel.innerHTML = `
            @csrf
            <input type="hidden" name="extrato_id" value="${idExtrato}">
        `;
        document.body.appendChild(formInvisivel);
        formInvisivel.submit();
    }
}
  
function abrirModalNovo(dados) {
    document.getElementById('novo_extrato_id').value = dados.id;
    document.getElementById('info_extrato_novo').innerHTML = `<strong>${dados.descricao}</strong><br>R$ ${parseFloat(dados.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
    
    const divPagar = document.getElementById('div_categoria_pagar');
    const selectPagar = document.getElementById('select_categoria_pagar');
    const divReceber = document.getElementById('div_categoria_receber');
    const selectReceber = document.getElementById('select_categoria_receber');

    if (dados.tipo === 'debit') {
        // --- LÓGICA PARA SAÍDA (PAGAR) ---
        // Mostra Fornecedor e Categoria Pagar
        document.getElementById('div_fornecedor').style.display = 'block';
        divPagar.style.display = 'block';
        selectPagar.disabled = false;
        selectPagar.required = true;

        // Esconde Cliente e Categoria Receber
        document.getElementById('div_cliente').style.display = 'none';
        divReceber.style.display = 'none';
        selectReceber.disabled = true;
        selectReceber.required = false;
        selectReceber.value = ''; 
    } else {
        // --- LÓGICA PARA ENTRADA (RECEBER) ---
        // Mostra Cliente e Categoria Receber
        document.getElementById('div_cliente').style.display = 'block';
        divReceber.style.display = 'block';
        selectReceber.disabled = false;
        selectReceber.required = true;

        // Esconde Fornecedor e Categoria Pagar
        document.getElementById('div_fornecedor').style.display = 'none';
        divPagar.style.display = 'none';
        selectPagar.disabled = true;
        selectPagar.required = false;
        selectPagar.value = ''; 
    }

    $('#modalNovo').modal('show');
}

function abrirModalTransferir(dados) {
    document.getElementById('transf_extrato_id').value = dados.id;
    document.getElementById('info_extrato_transf').innerHTML = `<strong>${dados.descricao}</strong><br>R$ ${parseFloat(dados.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
    $('#modalTransferir').modal('show');
}

// === NOVO: PREENCHIMENTO AUTOMÁTICO DE JUROS/DESCONTO ===
function aplicarDiferencaAuto() {
    let somaTituloss = 0;
    document.querySelectorAll('.check-titulo:checked').forEach(chk => {
        somaTituloss += parseFloat(chk.dataset.valor);
    });
    
    let extrato = parseFloat(dadoExtratoAtual.valor);
    let diferenca = extrato - somaTituloss;
    
    if (diferenca > 0) {
        // Banco cobrou mais do que a nota -> É Juros/Multa
        document.getElementById('vincular_acrescimo').value = diferenca.toFixed(2);
        document.getElementById('vincular_desconto').value = '0';
    } else if (diferenca < 0) {
        // Banco cobrou menos do que a nota -> É Desconto
        document.getElementById('vincular_desconto').value = Math.abs(diferenca).toFixed(2);
        document.getElementById('vincular_acrescimo').value = '0';
    }
    recalcularSoma();
}

function recalcularSoma() {
    let soma = 0;
    document.querySelectorAll('.check-titulo:checked').forEach(chk => {
        soma += parseFloat(chk.dataset.valor);
    });
    
    let acrescimo = parseFloat(document.getElementById('vincular_acrescimo').value) || 0;
    let desconto = parseFloat(document.getElementById('vincular_desconto').value) || 0;
    
    let somaFinal = soma + acrescimo - desconto;
    let valorExtrato = parseFloat(dadoExtratoAtual.valor);
    let diferenca = valorExtrato - somaFinal;

    document.getElementById('soma_selecionada').innerText = `R$ ${somaFinal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
    
    let difEl = document.getElementById('diferenca_valor');
    let btnSubmit = document.getElementById('btnConfirmarVincular');

    if (Math.abs(diferenca) < 0.01 && somaFinal > 0) {
        difEl.innerHTML = `<span class="text-success"><i class="fas fa-check-circle"></i> R$ 0,00 (Fechou!)</span>`;
        btnSubmit.disabled = false;
        btnSubmit.innerText = "Confirmar Conciliação";
        btnSubmit.className = "btn btn-success font-weight-bold px-4";
    } else {
        // Se houver diferença E algum título estiver marcado, mostra o botão mágico
        let btnAuto = soma > 0 ? `<button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 mt-1 d-block w-100 font-weight-bold" onclick="aplicarDiferencaAuto()"><i class="fas fa-magic"></i> Lançar Diferença</button>` : '';
        
        difEl.innerHTML = `<span class="text-danger">R$ ${Math.abs(diferenca).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span> ${btnAuto}`;
        
        btnSubmit.disabled = true;
        btnSubmit.innerText = "Aguardando Fechamento...";
        btnSubmit.className = "btn btn-primary font-weight-bold px-4";
    }
}

function filtrarSugestoes() {
    const tipo = document.getElementById('vincular_tipo').value;
    const divCheckboxes = document.getElementById('lista_checkboxes');
    const msg = document.getElementById('msg_feedback');
    const listaOriginal = (tipo === 'pagar') ? pagarSugestoes : receberSugestoes;
    
    const valorExtrato = parseFloat(dadoExtratoAtual.valor);
    const descBanco = dadoExtratoAtual.descricao.toLowerCase();

    divCheckboxes.innerHTML = '';
    let itensOrdenados = [];

    // === NOVO: INTELIGÊNCIA DE COMBOS (SOMA POR FORNECEDOR) ===
    let somaPorFornecedor = {};
    listaOriginal.forEach(item => {
        if (item.status == 0) { // Soma apenas os pendentes
            let nomeForn = (item.nome_parceiro || 'SEM_NOME').trim().toUpperCase();
            if (!somaPorFornecedor[nomeForn]) {
                somaPorFornecedor[nomeForn] = { soma: 0, count: 0 };
            }
            somaPorFornecedor[nomeForn].soma += parseFloat(item.valor_integral);
            somaPorFornecedor[nomeForn].count++;
        }
    });

    let comboPerfeito = null;
    for (const [nomeForn, dados] of Object.entries(somaPorFornecedor)) {
        if (Math.abs(dados.soma - valorExtrato) < 0.01 && dados.count > 1) {
            comboPerfeito = nomeForn; // Descobriu que a soma de 2+ títulos desse cara dá o valor exato!
        }
    }

    listaOriginal.forEach(item => {
        let score = 0;
        const valorTitulo = parseFloat(item.valor_integral || 0);
        const parceiroNome = (item.nome_parceiro || '').toLowerCase().trim();
        const parceiroUpper = (item.nome_parceiro || '').toUpperCase().trim();
        
        let diferencaValor = Math.abs(valorTitulo - valorExtrato);
        let diferencaPercentual = valorTitulo > 0 ? (diferencaValor / valorTitulo) * 100 : 100;

        // Tenta achar qualquer palavra grande (ex: MOVESA) do banco no nome do fornecedor
        let palavrasBanco = descBanco.split(' ').filter(p => p.length > 3);
        let bateuNome = false;
        for(let palavra of palavrasBanco) {
            if (parceiroNome.includes(palavra)) {
                bateuNome = true;
                score += 2000; // Agrupa todo mundo com nome parecido no topo
                break;
            }
        }
        
        if (diferencaValor < 0.01) {
            score += 1000; // Valor individual bateu
            if (bateuNome) score += 5000; // Ouro: Nome e Valor bateram 1 pra 1
        } else if (diferencaPercentual <= 10) {
            score += 30; // Aproximado
        }

        // Se esse título faz parte do Combo Perfeito descoberto lá em cima
        let fazParteDoCombo = false;
        if (comboPerfeito !== null && parceiroUpper === comboPerfeito && item.status == 0) {
            score += 10000; // Prioridade Diamante
            fazParteDoCombo = true;
        }

        itensOrdenados.push({ item: item, score: score, isCombo: fazParteDoCombo });
    });

    itensOrdenados.sort((a, b) => b.score - a.score);

    if (itensOrdenados.length === 0) {
        divCheckboxes.innerHTML = '<div class="p-2 text-muted text-center">Nenhum título cadastrado.</div>';
        msg.innerHTML = '';
        recalcularSoma();
        return;
    }

    let selecionouAlgo = false;

    itensOrdenados.forEach((c, index) => {
        const i = c.item;
        const dataVenc = i.data_vencimento ? i.data_vencimento.split('-').reverse().join('/') : 'S/D';
        const valorFmt = parseFloat(i.valor_integral).toLocaleString('pt-BR', {minimumFractionDigits: 2});
        
        const badgeStatus = (i.status == 1) ? '<span class="badge badge-warning text-dark ml-2">Já Pago</span>' : '';
        const corLinha = (i.status == 1) ? 'bg-light' : '';
        
        // Auto-check se faz parte de um combo OU se for o primeiro da lista com score de perfeição individual
        const checkAttribute = (c.isCombo || (index === 0 && c.score >= 6000 && i.status == 0)) ? 'checked' : '';
        if (checkAttribute) selecionouAlgo = true;

        const html = `
            <div class="custom-control custom-checkbox p-2 border-bottom ${corLinha}">
                <input type="checkbox" class="custom-control-input check-titulo" name="conta_ids[]" value="${i.id}" id="chk_${i.id}" data-valor="${i.valor_integral}" onchange="recalcularSoma()" ${checkAttribute}>
                <label class="custom-control-label w-100" for="chk_${i.id}" style="cursor: pointer;">
                    <div class="d-flex justify-content-between">
                        <span><strong>${i.nome_parceiro || 'Sem Fornecedor'}</strong> ${badgeStatus}</span>
                        <strong class="text-primary">R$ ${valorFmt}</strong>
                    </div>
                    <small class="text-muted">Ref: ${i.referencia || '-'} | Venc: ${dataVenc}</small>
                </label>
            </div>
        `;
        divCheckboxes.insertAdjacentHTML('beforeend', html);
    });

    recalcularSoma();

    if (itensOrdenados[0].score >= 10000) {
        msg.innerHTML = `<i class="fas fa-boxes text-success"></i> Combo Inteligente: O sistema agrupou vários boletos deste fornecedor que somam o valor exato do banco!`;
    } else if (itensOrdenados[0].score >= 6000) {
        msg.innerHTML = `<i class="fas fa-bullseye text-success"></i> Combinação exata de título único!`;
    } else if (itensOrdenados[0].score >= 2000) {
        msg.innerHTML = `<i class="fas fa-search text-primary"></i> Fornecedor localizado pelo nome. Marque os títulos correspondentes.`;
    } else {
        msg.innerHTML = `<i class="fas fa-info-circle text-info"></i> Selecione os títulos manualmente.`;
    }
}
</script>
@endsection