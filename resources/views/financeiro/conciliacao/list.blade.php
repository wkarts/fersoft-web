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

  {{-- PAINEL DE CONFRONTO: ERP x BANCO --}}
@if(isset($resumo))
    <div class="row mb-4 px-3">
        <div class="col-12 bg-white rounded shadow-sm border p-0 overflow-hidden">
            <div class="bg-dark text-white px-4 py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-balance-scale mr-2"></i> Auditoria de Caixa (Período Filtrado)</h6>
                <small>Conta Bancária Selecionada vs. Financeiro do ERP</small>
            </div>
            
            <div class="row m-0 text-center">
                {{-- BLOCO DE ENTRADAS --}}
                <div class="col-md-6 p-4 border-right">
                    <h6 class="text-success font-weight-bold mb-3"><i class="fas fa-arrow-circle-down"></i> RECEBIMENTOS (ENTRADAS)</h6>
                    <div class="d-flex justify-content-around">
                        <div>
                            <small class="text-muted d-block">Lido no Banco (OFX)</small>
                            <h4 class="text-dark">R$ {{ number_format($resumo['banco_in'], 2, ',', '.') }}</h4>
                        </div>
                        <div>
                            <small class="text-muted d-block">Baixado no ERP</small>
                            <h4 class="text-dark">R$ {{ number_format($resumo['erp_in'], 2, ',', '.') }}</h4>
                        </div>
                    </div>
                    <hr>
                    @if(abs($resumo['dif_in']) < 0.01)
                        <span class="badge badge-success px-3 py-2"><i class="fas fa-check-circle"></i> Entradas 100% Conciliadas</span>
                    @else
                        <span class="badge badge-warning text-dark px-3 py-2">
                            <i class="fas fa-exclamation-triangle"></i> Falta conciliar: R$ {{ number_format(abs($resumo['dif_in']), 2, ',', '.') }}
                        </span>
                    @endif
                </div>

                {{-- BLOCO DE SAÍDAS --}}
                <div class="col-md-6 p-4">
                    <h6 class="text-danger font-weight-bold mb-3"><i class="fas fa-arrow-circle-up"></i> PAGAMENTOS (SAÍDAS)</h6>
                    <div class="d-flex justify-content-around">
                        <div>
                            <small class="text-muted d-block">Lido no Banco (OFX)</small>
                            <h4 class="text-dark">R$ {{ number_format($resumo['banco_out'], 2, ',', '.') }}</h4>
                        </div>
                        <div>
                            <small class="text-muted d-block">Baixado no ERP</small>
                            <h4 class="text-dark">R$ {{ number_format($resumo['erp_out'], 2, ',', '.') }}</h4>
                        </div>
                    </div>
                    <hr>
                    @if(abs($resumo['dif_out']) < 0.01)
                        <span class="badge badge-success px-3 py-2"><i class="fas fa-check-circle"></i> Saídas 100% Conciliadas</span>
                    @else
                        <span class="badge badge-warning text-dark px-3 py-2">
                            <i class="fas fa-exclamation-triangle"></i> Falta conciliar: R$ {{ number_format(abs($resumo['dif_out']), 2, ',', '.') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-light border-left-info shadow-sm mb-4 mx-3">
        <i class="fas fa-info-circle text-info mr-2"></i> <strong>Dica de Auditoria:</strong> Filtre a tela por <b>Conta Bancária</b>, <b>Data Inicial</b> e <b>Data Final</b> para visualizar o Painel de Confronto de Saldos automaticamente.
    </div>
@endif
  
    {{-- LAYOUT DIVIDIDO: EXTRATO (ESQUERDA) E SUGESTÕES (DIREITA) --}}
    <div class="row">
        
        {{-- COLUNA DA ESQUERDA (EXTRATO) --}}
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card shadow-sm" style="border: none;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-secondary font-weight-bold"><i class="fas fa-list-ul mr-2"></i> Extrato</h5>
                    
                    <div class="d-flex">
                        <button type="button" class="btn btn-sm btn-info font-weight-bold shadow-sm mr-2" data-toggle="modal" data-target="#modalManualConciliacao">
                            <i class="fas fa-question-circle mr-1"></i> Como Funciona
                        </button>

                        <form action="{{ url('financeiro/conciliacao/processar-automaticos') }}" method="POST" class="mr-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success font-weight-bold shadow-sm" title="Rodar regras memorizadas">
                                <i class="fas fa-robot mr-1"></i> Rodar Automação
                            </button>
                        </form>

                        <form action="{{ url('financeiro/conciliacao/limpar-pendentes') }}" method="POST" onsubmit="return confirm('Apagar importações pendentes?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger font-weight-bold shadow-sm">Limpar Pendentes</button>
                        </form>
                    </div>
                </div>
                
                <div class="mb-3 d-flex justify-content-between align-items-center pl-3 pr-3 bg-white p-3 rounded shadow-sm border mx-3 mt-3">
                    <div>
                        <button type="button" class="btn btn-warning shadow-sm font-weight-bold mr-2 text-dark" onclick="selecionarAtencao()">
                            <i class="fas fa-exclamation-triangle"></i> Marcar "Atenção"
                        </button>
                    </div>
                    <button type="button" class="btn btn-success shadow-sm font-weight-bold px-4" onclick="confirmarLote()">
                        <i class="fas fa-check-double"></i> Confirmar Selecionados
                    </button>
                </div>

                <div class="card-body p-0" style="max-height: 70vh; overflow-y: auto;">
                    <form id="form_lote" action="{{ url('financeiro/conciliacao/processar-lote') }}" method="POST">
                        @csrf
                        <table class="table table-hover mb-0">
                            <thead class="bg-light text-muted">
                                <tr>
                                    <th class="border-0 pl-4" style="width: 40px;">
                                        <input type="checkbox" id="checkAll" style="cursor: pointer; transform: scale(1.2);">
                                    </th>
                                    <th class="border-0">Data</th>
                                    <th class="border-0">Histórico</th>
                                    <th class="border-0">Valor</th>
                                    <th class="border-0 pr-4 text-center">Ações Manuais</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $l)
                                    <tr class="extrato-row {{ $l->status == 'reconciled' ? 'bg-light text-muted' : '' }}" 
                                        id="row_{{ $l->id }}" onclick="carregarSugestoes({{ $l->id }})" style="cursor:pointer;">
                                        
                                        <td class="align-middle pl-4" onclick="event.stopPropagation();">
                                            @if($l->status == 'pending')
                                                <input type="checkbox" name="extrato_ids[]" value="{{ $l->id }}" class="check-item" style="cursor: pointer; transform: scale(1.2);">
                                            @endif
                                        </td>

                                        <td class="align-middle">{{ date('d/m/Y', strtotime($l->data_transacao)) }}</td>
                                        <td class="align-middle">
                                            @if($l->status == 'reconciled') 
                                                <i class="fas fa-check-circle text-success mr-1"></i> 
                                            @endif
                                            <span class="{{ $l->status == 'reconciled' ? 'font-weight-normal' : 'font-weight-bold text-dark' }}">{{ $l->descricao }}</span>
                                            
                                            <br>
                                            <small class="badge badge-info shadow-sm mt-1" style="font-size: 0.7rem; opacity: 0.9;">
                                                <i class="fas fa-university mr-1"></i> {{ $l->contaBancaria->nome ?? 'ID Bancário: ' . $l->conta_bancaria_id }}
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
                                        <td class="align-middle pr-4 text-center" onclick="event.stopPropagation();">
                                            @if($l->status == 'pending')
                                                <div class="btn-group-vertical">
                                                    <button type="button" class="btn btn-sm btn-primary font-weight-bold mb-1" onclick="abrirModalVincular({{ json_encode($l) }})">Opções</button>
                                                    <button type="button" class="btn btn-sm btn-outline-dark font-weight-bold mb-1" onclick="abrirModalNovo({{ json_encode($l) }})">+ Novo</button>
                                                    <button type="button" class="btn btn-sm text-white font-weight-bold mb-1" style="background-color: #8b5cf6;" onclick="abrirModalTransferir({{ json_encode($l) }})">Transf.</button>
                                                    <button type="button" class="btn btn-sm btn-light border-secondary font-weight-bold mb-1" title="Apenas arquivar e tirar da tela" onclick="confirmarOKIndividual({{ $l->id }})">
                                                        <i class="fas fa-check-double text-success"></i> OK
                                                    </button>
                                                </div>
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
        </div>

        {{-- COLUNA DA DIREITA (PAINEL INTELIGENTE) --}}
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm" style="position: sticky; top: 20px; min-height: 50vh;" id="painel_sugestoes">
                <div class="card-body text-center d-flex flex-column justify-content-center align-items-center" style="min-height: 50vh; background: #f8f9fa; border: 2px dashed #dee2e6;">
                    <i class="fas fa-mouse-pointer fa-4x text-muted mb-3" style="opacity: 0.5;"></i>
                    <h5 class="text-muted font-weight-bold">Painel Inteligente</h5>
                    <p class="text-muted small px-3">Clique em uma linha pendente do extrato ao lado para que o sistema busque o título correto automaticamente.</p>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ================================================================= --}}
{{-- MODAIS ORIGINAIS PRESERVADOS --}}
{{-- ================================================================= --}}

{{-- MODAL VINCULAR (MULTIPLA ESCOLHA) --}}
<div class="modal fade" id="modalVincular" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ url('financeiro/conciliacao/conciliar') }}" method="POST" id="formVincular" onsubmit="return validarFechamento()">
            @csrf
            <input type="hidden" name="extrato_id" id="vincular_extrato_id">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #3b82f6;">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-link mr-2"></i> Vincular Títulos Manualmente</h5>
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

                            <div class="mt-4 p-3 bg-white border rounded">
                                <label class="font-weight-bold text-danger small"><i class="fas fa-plus-circle"></i> Juros / Multa</label>
                                <input type="number" step="0.01" min="0" name="acrescimo" id="vincular_acrescimo" class="form-control mb-3" value="0" onkeyup="recalcularSoma()" onchange="recalcularSoma()">

                                <label class="font-weight-bold text-success small"><i class="fas fa-minus-circle"></i> Desconto</label>
                                <input type="number" step="0.01" min="0" name="desconto" id="vincular_desconto" class="form-control" value="0" onkeyup="recalcularSoma()" onchange="recalcularSoma()">
                            </div>
                          {{-- Adicione este bloco dentro do modalVincular --}}
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

{{-- MODAL MANUAL --}}
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
                    <li>Acesse o seu internet banking e procure pela opção "Exportar Extrato" no formato <strong>OFX</strong>.</li>
                    <li>No sistema, escolha a conta bancária correspondente, selecione o arquivo OFX baixado e clique em <strong>Processar Arquivo</strong>.</li>
                </ol>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal">Entendi</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
@section('javascript')
<script>
const pagarSugestoes = @json($contasPagarSugestao ?? []);
const receberSugestoes = @json($contasReceberSugestao ?? []);
let dadoExtratoAtual = null;

function carregarSugestoes(extrato_id) {
    $('.extrato-row').removeClass('bg-warning text-dark font-weight-bold');
    $('#row_' + extrato_id).addClass('bg-warning text-dark font-weight-bold');

    $.get("{{ url('financeiro/conciliacao/sugestoes') }}", { extrato_id: extrato_id }, function(data) {
        let painel = $('#painel_sugestoes');
        let corCarga = data.tipo_conta === 'pagar' ? 'danger' : 'success';

        painel.html(`
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-magic"></i> Painel Inteligente</span>
                <span class="badge badge-${corCarga}">R$ ${parseFloat(data.valor_banco).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
            </div>
            <div class="card-body p-3 bg-light" id="lista_sugestoes"></div>
        `);

        let lista = painel.find('#lista_sugestoes');
        let temResultados = false;

        // 1. Títulos Abertos
        if (data.sugestoes && data.sugestoes.length > 0) {
            temResultados = true;
            lista.append(`<h6 class="text-muted mb-3 font-weight-bold text-uppercase small">Títulos com valor exato/próximo:</h6>`);
            data.sugestoes.forEach(item => {
                let nome = item.fornecedor ? item.fornecedor.razao_social : (item.cliente ? item.cliente.razao_social : 'Sem Nome');
                lista.append(gerarCardUnico(item, nome, corCarga, extrato_id, data.tipo_conta));
            });
        }

        // 2. Títulos JÁ PAGOS (Elizabete)
        if (data.sugestoes_pagas && data.sugestoes_pagas.length > 0) {
            temResultados = true;
            lista.append(`<h6 class="text-warning mt-3 mb-3 font-weight-bold text-uppercase small"><i class="fas fa-exclamation-triangle"></i> Encontrado como PAGO no ERP:</h6>`);
            data.sugestoes_pagas.forEach(item => {
                let nome = item.fornecedor ? item.fornecedor.razao_social : (item.cliente ? item.cliente.razao_social : 'Sem Nome');
                lista.append(gerarCardPago(item, nome, extrato_id));
            });
        }

        // 3. Combos (Soma de 2)
        if (data.combos && data.combos.length > 0) {
            temResultados = true;
            lista.append(`<h6 class="text-success mt-4 mb-3 font-weight-bold text-uppercase small"><i class="fas fa-boxes"></i> Combinação Exata (Soma de 2):</h6>`);
            data.combos.forEach(combo => {
                let nome = combo[0].fornecedor ? combo[0].fornecedor.razao_social : (combo[0].cliente ? combo[0].cliente.razao_social : 'Sem Nome');
                lista.append(gerarCardCombo(combo, nome, corCarga, extrato_id, data.tipo_conta));
            });
        }

        // 4. Outras Opções (Supergasbras com valores diferentes)
        if (!temResultados && data.outras_opcoes && data.outras_opcoes.length > 0) {
            temResultados = true;
            lista.append(`<div class="alert alert-warning shadow-sm"><i class="fas fa-filter"></i> Valor exato não encontrado, mas achamos este parceiro próximo a esta data:</div>`);
            data.outras_opcoes.forEach(item => {
                let nome = item.fornecedor ? item.fornecedor.razao_social : (item.cliente ? item.cliente.razao_social : 'Sem Nome');
                lista.append(gerarCardUnico(item, nome, corCarga, extrato_id, data.tipo_conta));
            });
        }

        if (!temResultados) {
            lista.html(`
                <div class="alert alert-warning shadow-sm border-warning">
                    <i class="fas fa-exclamation-triangle"></i> Nada encontrado.<br><br>
                    Utilize o botão <strong>"+ Novo"</strong> na tabela ao lado para gerar o lançamento.
                </div>
            `);
        }
    });
}

function gerarCardUnico(item, nome, cor, extrato_id, tipo_conta) {
    let venc = item.data_vencimento ? item.data_vencimento.split('-').reverse().join('/') : '--/--/----';
    let valor = parseFloat(item.valor_integral).toLocaleString('pt-BR', {minimumFractionDigits: 2});
    return `
        <div class="card mb-3 border-0 shadow-sm" style="border-left: 4px solid #3b82f6 !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="text-dark" style="font-size: 1.1rem;">${nome}</strong>
                    <strong class="text-${cor} h5 mb-0">R$ ${valor}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <div>
                        <small class="d-block text-muted"><i class="far fa-calendar-alt"></i> Vencimento: ${venc}</small>
                        <small class="d-block text-muted"><i class="fas fa-file-invoice"></i> Ref: ${item.referencia || item.id}</small>
                    </div>
                    <button class="btn btn-sm btn-primary font-weight-bold px-3 shadow-sm" onclick='conciliarAgora([${item.id}], ${extrato_id}, "${tipo_conta}")'>
                        <i class="fas fa-link"></i> Bater Valor
                    </button>
                </div>
            </div>
        </div>`;
}

// NOVO: GERA O CARD PARA TÍTULOS JÁ PAGOS
function gerarCardPago(item, nome, extrato_id) {
    let pgto = item.data_pagamento || item.data_recebimento || '--/--/----';
    if(pgto !== '--/--/----') pgto = pgto.split('-').reverse().join('/');
    let valor = parseFloat(item.valor_pago || item.valor_recebido || item.valor_integral).toLocaleString('pt-BR', {minimumFractionDigits: 2});
    
    return `
        <div class="card mb-3 border-0 shadow-sm" style="border-left: 4px solid #f59e0b !important; background-color: #fffbeb;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="text-dark" style="font-size: 1.1rem;">${nome}</strong>
                    <strong class="text-warning h5 mb-0">R$ ${valor}</strong>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <div>
                        <span class="badge badge-warning text-dark mb-1">Pago em: ${pgto}</span><br>
                        <small class="text-muted"><i class="fas fa-file-invoice"></i> Ref: ${item.referencia || item.id}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary font-weight-bold px-3 shadow-sm" title="Remove este aviso da tela do banco" onclick='confirmarOKIndividual(${extrato_id})'>
                        <i class="fas fa-check-double"></i> Apenas Arquivar Banco
                    </button>
                </div>
            </div>
        </div>`;
}

function gerarCardCombo(combo, nome, cor, extrato_id, tipo_conta) {
    let valorTotal = (parseFloat(combo[0].valor_integral) + parseFloat(combo[1].valor_integral)).toLocaleString('pt-BR', {minimumFractionDigits: 2});
    let ids = JSON.stringify([combo[0].id, combo[1].id]);

    return `
        <div class="card mb-3 border-0 shadow-sm" style="border-left: 4px solid #10b981 !important; background-color: #f0fdf4;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="text-dark" style="font-size: 1.1rem;">${nome}</strong>
                    <strong class="text-${cor} h5 mb-0">R$ ${valorTotal}</strong>
                </div>
                <div class="mb-2">
                    <small class="d-block text-muted"><i class="fas fa-plus"></i> R$ ${parseFloat(combo[0].valor_integral).toLocaleString('pt-BR', {minimumFractionDigits: 2})} (Venc: ${combo[0].data_vencimento.split('-').reverse().join('/')})</small>
                    <small class="d-block text-muted"><i class="fas fa-plus"></i> R$ ${parseFloat(combo[1].valor_integral).toLocaleString('pt-BR', {minimumFractionDigits: 2})} (Venc: ${combo[1].data_vencimento.split('-').reverse().join('/')})</small>
                </div>
                <div class="text-right">
                    <button class="btn btn-sm btn-success font-weight-bold px-3 shadow-sm" onclick='conciliarAgora(${ids}, ${extrato_id}, "${tipo_conta}")'>
                        <i class="fas fa-link"></i> Conciliar Ambos
                    </button>
                </div>
            </div>
        </div>`;
}

function conciliarAgora(titulos_ids, extrato_id, tipo_conta) {
    if (!confirm('Tem certeza que deseja baixar este(s) título(s) com o valor do extrato?')) return;
    let form = document.createElement('form');
    form.method = 'POST';
    form.action = "{{ url('financeiro/conciliacao/conciliar') }}";
    form.innerHTML = `@csrf <input type="hidden" name="extrato_id" value="${extrato_id}"><input type="hidden" name="tipo_conta" value="${tipo_conta}">`;
    titulos_ids.forEach(id => { form.innerHTML += `<input type="hidden" name="conta_ids[]" value="${id}">`; });
    document.body.appendChild(form);
    form.submit();
}



// ==========================================
// FUNÇÕES ORIGINAIS (MANTIDAS INTACTAS)
// ==========================================
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
    let checkboxes = document.querySelectorAll('.check-item');
    checkboxes.forEach(function(chk) {
        let linha = chk.closest('tr');
        if (linha.innerHTML.includes('Atenção: Consta PAGO no ERP')) {
            chk.checked = true;
            marcados++;
        }
    });
    if(marcados === 0) alert('Nenhum lançamento com o status "Atenção" encontrado nesta página.');
}

function confirmarLote() {
    let marcados = document.querySelectorAll('.check-item:checked').length;
    if (marcados === 0) {
        alert('Selecione pelo menos um lançamento marcando as caixinhas primeiro.');
        return;
    }
    if (confirm(`Você está prestes a confirmar ${marcados} lançamentos de uma vez. Deseja continuar?`)) {
        document.getElementById('form_lote').submit();
    }
}

function confirmarOKIndividual(idExtrato) {
    if (confirm('Confirmar ciência deste lançamento individual? Ele será removido das pendências.')) {
        let formInvisivel = document.createElement('form');
        formInvisivel.method = 'POST';
        formInvisivel.action = '{{ url("financeiro/conciliacao/arquivar") }}';
        formInvisivel.innerHTML = `@csrf <input type="hidden" name="extrato_id" value="${idExtrato}">`;
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
        document.getElementById('div_fornecedor').style.display = 'block';
        divPagar.style.display = 'block';
        selectPagar.disabled = false; selectPagar.required = true;

        document.getElementById('div_cliente').style.display = 'none';
        divReceber.style.display = 'none';
        selectReceber.disabled = true; selectReceber.required = false; selectReceber.value = ''; 
    } else {
        document.getElementById('div_cliente').style.display = 'block';
        divReceber.style.display = 'block';
        selectReceber.disabled = false; selectReceber.required = true;

        document.getElementById('div_fornecedor').style.display = 'none';
        divPagar.style.display = 'none';
        selectPagar.disabled = true; selectPagar.required = false; selectPagar.value = ''; 
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

    let selectCat = document.getElementById('select_cat_transf');
    selectCat.value = ""; 
    for (let i = 0; i < selectCat.options.length; i++) {
        let nomeCat = selectCat.options[i].text.toUpperCase();
        if (nomeCat.includes('TRANSF') || nomeCat.includes('CAIXA') || nomeCat.includes('FUNDO')) {
            selectCat.selectedIndex = i;
            break; 
        }
    }
    $('#modalTransferir').modal('show');
}

function aplicarDiferencaAuto() {
    let somaTituloss = 0;
    document.querySelectorAll('.check-titulo:checked').forEach(chk => {
        somaTituloss += parseFloat(chk.dataset.valor);
    });
    
    let extrato = parseFloat(dadoExtratoAtual.valor);
    let diferenca = extrato - somaTituloss;
    
    if (diferenca > 0) {
        document.getElementById('vincular_acrescimo').value = diferenca.toFixed(2);
        document.getElementById('vincular_desconto').value = '0';
    } else if (diferenca < 0) {
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
    const termoBusca = document.getElementById('busca_vinculo').value.toLowerCase().trim(); 
    const listaOriginal = (tipo === 'pagar') ? pagarSugestoes : receberSugestoes;
    
    const valorExtrato = parseFloat(dadoExtratoAtual.valor);
    const descBanco = dadoExtratoAtual.descricao.toLowerCase();

    divCheckboxes.innerHTML = '';
    let itensOrdenados = [];
    let somaPorFornecedor = {};

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

    listaOriginal.forEach(item => {
        let score = 0;
        const valorTitulo = parseFloat(item.valor_integral || 0);
        const parceiroNome = (item.nome_parceiro || '').toLowerCase().trim();
        const parceiroUpper = (item.nome_parceiro || '').toUpperCase().trim();
        const dataFmt = item.data_vencimento ? item.data_vencimento.split('-').reverse().join('/') : '';
        
        if (termoBusca !== '') {
            if (!parceiroNome.includes(termoBusca) && !valorTitulo.toString().includes(termoBusca) && !dataFmt.includes(termoBusca)) {
                return; 
            } else {
                score += 50000; 
            }
        }

        let palavrasBanco = descBanco.split(/[\s-]+/).filter(p => p.length > 3);
        let bateuNome = false;
        
        for(let palavra of palavrasBanco) {
            if (parceiroNome.includes(palavra)) {
                bateuNome = true;
                score += 2000; 
            }
        }

        let diferencaValor = Math.abs(valorTitulo - valorExtrato);
        if (diferencaValor < 0.01) {
            score += 1000; 
            if (bateuNome) score += 5000; 
        } else if (diferencaValor <= 5.00) {
            score += 100; 
        }

        let fazParteDoCombo = false;
        if (comboPerfeito !== null && parceiroUpper === comboPerfeito && item.status == 0) {
            score += 10000; 
            fazParteDoCombo = true;
        }

        itensOrdenados.push({ item: item, score: score, isCombo: fazParteDoCombo });
    });

    itensOrdenados.sort((a, b) => b.score - a.score);

    if (itensOrdenados.length === 0) {
        divCheckboxes.innerHTML = '<div class="p-3 text-muted text-center font-weight-bold"><i class="fas fa-search-minus fa-2x mb-2 d-block"></i> Nenhum título corresponde à pesquisa.</div>';
        msg.innerHTML = '';
        recalcularSoma();
        return;
    }

    itensOrdenados.forEach((c, index) => {
        const i = c.item;
        const dataVenc = i.data_vencimento ? i.data_vencimento.split('-').reverse().join('/') : 'S/D';
        const valorFmt = parseFloat(i.valor_integral).toLocaleString('pt-BR', {minimumFractionDigits: 2});
        const badgeStatus = (i.status == 1) ? '<span class="badge badge-warning text-dark ml-2">Já Pago</span>' : '';
        const corLinha = (i.status == 1) ? 'bg-light' : '';
        
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

    if (termoBusca === '') {
        if (itensOrdenados[0].score >= 10000) {
            msg.innerHTML = `<i class="fas fa-boxes text-success"></i> Combo Inteligente: Múltiplos títulos exatos!`;
        } else if (itensOrdenados[0].score >= 6000) {
            msg.innerHTML = `<i class="fas fa-bullseye text-success"></i> Combinação exata de título único!`;
        } else if (itensOrdenados[0].score >= 2000) {
            msg.innerHTML = `<i class="fas fa-search text-primary"></i> Nomes parecidos encontrados.`;
        } else {
            msg.innerHTML = `<i class="fas fa-info-circle text-info"></i> Selecione os títulos manualmente.`;
        }
    } else {
        msg.innerHTML = `<i class="fas fa-filter text-primary"></i> Mostrando resultados para: "${termoBusca}"`;
    }
}

// Para manter os checkboxes marcando todos no header da tabela
$('#checkAll').click(function() {
    $('.check-item').prop('checked', this.checked);
});
</script>
@endsection