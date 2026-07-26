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
                    {{-- NOVO BOTÃO DE MANUAL --}}
                    <button type="button" class="btn btn-sm btn-info font-weight-bold shadow-sm mr-2" data-toggle="modal" data-target="#modalManualConciliacao">
                        <i class="fas fa-question-circle mr-1"></i> Como Funciona
                    </button>

                    {{-- BOTÃO: ZERO-CLICK --}}
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

                            <div class="mt-4 p-3 bg-white border rounded">
                                <label class="font-weight-bold text-dark small"><i class="fas fa-file-alt"></i> Tipo de Documento</label>
                                <select name="tipo_documento" class="form-control" required>
                                    <option value="Conciliação">Conciliação</option>
                                    <option value="Depósito">Depósito</option>
                                    <option value="PIX">PIX</option>
                                    <option value="Boleto">Boleto</option>
                                    <option value="Transferência">Transferência</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-end mb-2">
        <label class="font-weight-bold text-muted small mb-0">Títulos Encontrados</label>
        
        {{-- NOVA BARRA DE BUSCA MANUAL --}}
        <div class="input-group input-group-sm w-50">
            <div class="input-group-prepend">
                <span class="input-group-text bg-white"><i class="fas fa-search text-primary"></i></span>
            </div>
            <input type="text" id="busca_vinculo" class="form-control" placeholder="Pesquisar nome ou valor..." onkeyup="filtrarSugestoes()">
        </div>
    </div>
    
    <div id="lista_checkboxes" class="border rounded bg-white p-2 shadow-sm" style="max-height: 280px; overflow-y: auto;">
        <!-- Lista injetada pelo JS -->
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
                    <div class="alert bg-white mb-4 shadow-sm" style="border-left: 4px solid #8b5cf6;" id="info_extrato_transf"></div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-muted small">Para qual conta foi/veio o dinheiro?</label>
                        <select name="conta_destino_id" class="form-control" required>
                            <option value="">Selecione a conta bancária...</option>
                            @foreach($contasBancarias as $conta)
                                <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- NOVO CAMPO DE CATEGORIA --}}
                    <div class="form-group">
                        <label class="font-weight-bold text-muted small">Categoria Financeira</label>
                        <select name="categoria_id" id="select_cat_transf" class="form-control" required>
                            <option value="">Selecione a categoria...</option>
                            @foreach($categorias as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
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

{{-- MODAL MANUAL DE INSTRUÇÕES DA CONCILIAÇÃO --}}
<div class="modal fade" id="modalManualConciliacao" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header text-white" style="background-color: #8b5cf6;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-book-open mr-2"></i> Manual: Conciliação Bancária Inteligente</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body bg-light" style="max-height: 70vh; overflow-y: auto;">
                
                <h5 class="text-primary font-weight-bold">1. Qual o objetivo desta tela?</h5>
                <p>A conciliação bancária serve para "bater" (comparar) o que aconteceu no seu banco real com o que está lançado no ERP. Isso evita fraudes, esquecimentos e garante que o saldo do sistema seja idêntico ao saldo do banco.</p>

                <hr>

                <h5 class="text-primary font-weight-bold">2. Importando o Arquivo (Passo a Passo)</h5>
                <ol>
                    <li>Acesse o seu internet banking (Itaú, Caixa, etc.) e procure pela opção "Exportar Extrato".</li>
                    <li>Escolha o formato <strong>OFX</strong> (é o padrão universal para integração de sistemas).</li>
                    <li>No sistema, escolha a conta bancária correspondente, selecione o arquivo OFX baixado e clique em <strong>Processar Arquivo</strong>.</li>
                    <li>O sistema lerá as transações e as listará na tabela de extrato como "Pendentes".</li>
                </ol>

                <hr>

                <h5 class="text-primary font-weight-bold">3. Como analisar e baixar os lançamentos</h5>
                <p>Na coluna "Ações", você tem 4 opções para lidar com cada linha do extrato bancário:</p>
                <ul>
                    <li><strong class="text-primary">Vincular:</strong> Use quando o boleto/conta já foi lançado no sistema (Contas a Pagar/Receber), mas ainda está em aberto. O sistema buscará opções similares. Você marca a correta e ele faz a baixa automática, calculando inclusive juros e descontos.</li>
                    <li><strong class="text-dark">+ Novo:</strong> Use para despesas/receitas que caíram no banco, mas esqueceram de lançar no sistema (ex: Tarifas bancárias, PIX de última hora). Ele já cria a conta, faz a baixa e ajusta o saldo na mesma hora.</li>
                    <li><strong style="color: #8b5cf6;">Transf.:</strong> Use para movimentações de dinheiro entre suas próprias contas (ex: enviou do Itaú para o Caixa Fundo Fixo).</li>
                    <li><strong class="text-success">OK (Arquivar):</strong> Use para limpar a linha da tela caso ela já tenha sido baixada manualmente no passado ou seja um erro de exportação do banco.</li>
                </ul>

                <hr>

                <h5 class="text-primary font-weight-bold">4. Robô e Regras Automáticas</h5>
                <p>Ao usar o botão "+ Novo", você verá uma caixinha chamada <strong>"Salvar Regra"</strong>. Se você marcá-la (ideal para tarifas, energia, internet), no mês seguinte você não precisará fazer nada manualmente!</p>
                <p>Basta importar o OFX e clicar no botão verde superior <strong>"Rodar Automação"</strong>. O sistema lembrará da regra, criará as contas, fará a baixa e atualizará o saldo automaticamente para todas as linhas que tiverem o mesmo nome.</p>

            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal">Entendi</button>
            </div>
        </div>
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
    document.getElementById('info_extrato_transf').innerHTML = `
        <span class="small text-muted font-weight-bold">Descrição do Banco:</span><br>
        <strong class="text-dark">${dados.descricao}</strong><br>
        <strong class="h5" style="color: #8b5cf6;">R$ ${parseFloat(dados.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</strong>
    `;

    // INTELIGÊNCIA DE UX: Auto-selecionar a categoria de transferência
    let selectCat = document.getElementById('select_cat_transf');
    selectCat.value = ""; // Reseta o campo
    
    // Procura nas options alguma que contenha as palavras-chave exatas que você usa
    for (let i = 0; i < selectCat.options.length; i++) {
        // Converte para maiúsculo para ignorar acentos e letras minúsculas
        let nomeCat = selectCat.options[i].text.toUpperCase();
        
        // Busca por TRANSF, CAIXA ou FUNDO (de fundo fixo)
        if (nomeCat.includes('TRANSF') || nomeCat.includes('CAIXA') || nomeCat.includes('FUNDO')) {
            selectCat.selectedIndex = i;
            break; // Para no primeiro que achar e já deixa selecionado na tela
        }
    }

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
    const termoBusca = document.getElementById('busca_vinculo').value.toLowerCase().trim(); // Pega o que o usuário digitou
    const listaOriginal = (tipo === 'pagar') ? pagarSugestoes : receberSugestoes;
    
    const valorExtrato = parseFloat(dadoExtratoAtual.valor);
    const descBanco = dadoExtratoAtual.descricao.toLowerCase();

    divCheckboxes.innerHTML = '';
    let itensOrdenados = [];
    let somaPorFornecedor = {};

    // Preparação para Combo de soma exata
    listaOriginal.forEach(item => {
        if (item.status == 0) { 
            let nomeForn = (item.nome_parceiro || 'SEM_NOME').trim().toUpperCase();
            if (!somaPorFornecedor[nomeForn]) somaPorFornecedor[nomeForn] = { soma: 0, count: 0 };
            somaPorFornecedor[nomeForn].soma += parseFloat(item.valor_integral);
            somaPorFornecedor[nomeForn].count++;
        }
    });

    let comboPerfeito = null;
    for (const [nomeForn, dados] of Object.entries(somaPorFornecedor)) {
        if (Math.abs(dados.soma - valorExtrato) < 0.01 && dados.count > 1) {
            comboPerfeito = nomeForn; 
        }
    }

    // Lógica principal de Match e Filtro
    listaOriginal.forEach(item => {
        let score = 0;
        const valorTitulo = parseFloat(item.valor_integral || 0);
        const parceiroNome = (item.nome_parceiro || '').toLowerCase().trim();
        const parceiroUpper = (item.nome_parceiro || '').toUpperCase().trim();
        const dataFmt = item.data_vencimento ? item.data_vencimento.split('-').reverse().join('/') : '';
        
        // --- 1. FILTRO MANUAL (Se o usuário digitou algo na barra de pesquisa) ---
        if (termoBusca !== '') {
            // Se o que ele digitou não existe nem no nome, nem no valor e nem na data, ignora este item
            if (!parceiroNome.includes(termoBusca) && !valorTitulo.toString().includes(termoBusca) && !dataFmt.includes(termoBusca)) {
                return; // Pula para o próximo (não mostra na tela)
            } else {
                score += 50000; // Força o item pesquisado a ficar no topo
            }
        }

        // --- 2. INTELIGÊNCIA: Nomes parecidos e fragmentados (Adriano Santos Silva) ---
        // Pega palavras maiores que 3 letras do extrato do banco
        let palavrasBanco = descBanco.split(/[\s-]+/).filter(p => p.length > 3);
        let bateuNome = false;
        
        for(let palavra of palavrasBanco) {
            // Se a palavra do banco estiver no nome cadastrado no ERP, ganha ponto
            if (parceiroNome.includes(palavra)) {
                bateuNome = true;
                score += 2000; 
            }
        }

        // --- 3. INTELIGÊNCIA: Valores Exatos ---
        let diferencaValor = Math.abs(valorTitulo - valorExtrato);
        if (diferencaValor < 0.01) {
            score += 1000; 
            if (bateuNome) score += 5000; // Match Perfeito: Bateu Nome e Valor!
        } else if (diferencaValor <= 5.00) {
            score += 100; // Pode ser juros pequeno ou tarifa retida (Aproximado)
        }

        // --- 4. INTELIGÊNCIA: Faz parte do Combo Perfeito? ---
        let fazParteDoCombo = false;
        if (comboPerfeito !== null && parceiroUpper === comboPerfeito && item.status == 0) {
            score += 10000; 
            fazParteDoCombo = true;
        }

        itensOrdenados.push({ item: item, score: score, isCombo: fazParteDoCombo });
    });

    // Ordena do maior Score (mais parecido/pesquisado) para o menor
    itensOrdenados.sort((a, b) => b.score - a.score);

    if (itensOrdenados.length === 0) {
        divCheckboxes.innerHTML = '<div class="p-3 text-muted text-center font-weight-bold"><i class="fas fa-search-minus fa-2x mb-2 d-block"></i> Nenhum título corresponde à pesquisa.</div>';
        msg.innerHTML = '';
        recalcularSoma();
        return;
    }

    // Desenha na tela
    itensOrdenados.forEach((c, index) => {
        const i = c.item;
        const dataVenc = i.data_vencimento ? i.data_vencimento.split('-').reverse().join('/') : 'S/D';
        const valorFmt = parseFloat(i.valor_integral).toLocaleString('pt-BR', {minimumFractionDigits: 2});
        const badgeStatus = (i.status == 1) ? '<span class="badge badge-warning text-dark ml-2">Já Pago</span>' : '';
        const corLinha = (i.status == 1) ? 'bg-light' : '';
        
        // Se o usuário não digitou nada, aplica as regras de Auto-Check
        let checkAttribute = '';
        if (termoBusca === '') {
            if (c.isCombo || (index === 0 && c.score >= 6000 && i.status == 0)) {
                checkAttribute = 'checked';
            }
        }

        const html = `
            <div class="custom-control custom-checkbox p-2 border-bottom ${corLinha} hover-bg-light">
                <input type="checkbox" class="custom-control-input check-titulo" name="conta_ids[]" value="${i.id}" id="chk_${i.id}" data-valor="${i.valor_integral}" onchange="recalcularSoma()" ${checkAttribute}>
                <label class="custom-control-label w-100" for="chk_${i.id}" style="cursor: pointer;">
                    <div class="d-flex justify-content-between">
                        <span><strong class="${c.score > 1000 ? 'text-primary' : 'text-dark'}">${i.nome_parceiro || 'Sem Nome Vinculado'}</strong> ${badgeStatus}</span>
                        <strong class="text-primary h6 mb-0">R$ ${valorFmt}</strong>
                    </div>
                    <small class="text-muted"><i class="far fa-calendar-alt"></i> Venc: ${dataVenc} ${i.referencia ? '| Ref: '+i.referencia : ''}</small>
                </label>
            </div>
        `;
        divCheckboxes.insertAdjacentHTML('beforeend', html);
    });

    recalcularSoma();

    // Mensagens de Feedback só aparecem se não estiver buscando manualmente
    if (termoBusca === '') {
        if (itensOrdenados[0].score >= 10000) {
            msg.innerHTML = `<i class="fas fa-boxes text-success"></i> Combo Inteligente: O sistema encontrou vários títulos deste fornecedor que somam o valor exato!`;
        } else if (itensOrdenados[0].score >= 6000) {
            msg.innerHTML = `<i class="fas fa-bullseye text-success"></i> Combinação exata de título único (Nome e Valor conferem)!`;
        } else if (itensOrdenados[0].score >= 2000) {
            msg.innerHTML = `<i class="fas fa-search text-primary"></i> Encontramos nomes parecidos com a descrição do banco.`;
        } else {
            msg.innerHTML = `<i class="fas fa-info-circle text-info"></i> Selecione os títulos manualmente ou use a barra de pesquisa acima.`;
        }
    } else {
        msg.innerHTML = `<i class="fas fa-filter text-primary"></i> Mostrando resultados para: "${termoBusca}"`;
    }
}
</script>
@endsection