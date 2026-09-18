@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title"><i class="fa fa-search text-primary mr-2"></i> Filtros de Pesagem</h3>
    </div>
    
    <div class="card-body">
        <!-- FORMULÁRIO DE FILTRO -->
        <form method="GET" action="">
            <div class="row align-items-end">
                <div class="col-md-2">
                    <label>Data Inicial</label>
                    <input type="date" name="data_inicio" class="form-control" value="{{ $data_inicio }}">
                </div>
                <div class="col-md-2">
                    <label>Data Final</label>
                    <input type="date" name="data_fim" class="form-control" value="{{ $data_fim }}">
                </div>
                <div class="col-md-4">
                    <label>Fornecedor</label>
                    <select name="fornecedor_id" class="form-control select2">
                        <option value="todos">Todos os Fornecedores</option>
                        @foreach($fornecedores as $f)
                            <option value="{{ $f->id }}" {{ $fornecedor_id == $f->id ? 'selected' : '' }}>
                                {{ $f->razao_social }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select name="status_pesagem" class="form-control select2">
                        <option value="todos" {{ $status_pesagem == 'todos' ? 'selected' : '' }}>Todas</option>
                        <option value="pendentes" {{ $status_pesagem == 'pendentes' ? 'selected' : '' }}>Pendentes (Sem NF)</option>
                        <option value="emitidas" {{ $status_pesagem == 'emitidas' ? 'selected' : '' }}>Emitidas (Com NF)</option>
                    </select>
                </div>
                <div class="col-md-2 text-right">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Buscar</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if(session('resumo'))
<div class="alert alert-custom alert-notice alert-light-info fade show" role="alert">
    <div class="alert-icon"><i class="flaticon-warning"></i></div>
    <div class="alert-text">
        <strong>Resultado do Processamento:</strong><br>
        Sucessos: {{ session('resumo')['sucesso'] }} notas processadas.<br>
        @if(count(session('resumo')['erros']) > 0)
            <hr>
            <strong class="text-danger">Erros encontrados:</strong><br>
            <ul class="mb-0">
                @foreach(session('resumo')['erros'] as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        @endif
    </div>
    <div class="alert-close">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true"><i class="ki ki-close"></i></span>
        </button>
    </div>
</div>
@endif

<!-- FORMULÁRIO DE GERAÇÃO (DADOS EM CIMA) -->
<form method="POST" action="/pesagem_nfe/processar">
    @csrf
    
    <div class="card card-custom">
        <div class="card-header bg-light">
            <h3 class="card-title"><i class="fa fa-file-invoice text-success mr-2"></i> Dados para Geração de Notas Fiscais</h3>
            <div class="card-toolbar">
                <button type="submit" class="btn btn-success font-weight-bold">
                    <i class="fa fa-check"></i> Processar Selecionados
                </button>
            </div>
        </div>
      <div class="card-body">
<!-- BLOCO DE CONFIGURAÇÃO DA NOTA EM CIMA -->

            <div class="row bg-light-secondary p-4 rounded mb-8 border">
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-dark">Natureza de Operação *</label>
                    <select name="natureza_id" class="form-control select2" required>
                        <option value="">Selecione...</option>
                        @foreach($naturezas as $nat)
                            <option value="{{ $nat->id }}">{{ $nat->natureza }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-dark">Tipo Pagamento (NF-e) *</label>
                    <select name="tipo_pagamento_nfe" class="form-control select2" required>
                        <option value="01">01 - Dinheiro</option>
                        <option value="15">15 - Boleto Bancário</option>
                        <option value="16">16 - Depósito Bancário</option>
                        <option value="17" selected>17 - PIX</option>
                        <option value="90">90 - Sem Pagamento</option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-dark">Data de Emissão *</label>
                    <input type="date" name="data_emissao" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="font-weight-bold text-dark">Data de Venc/Pgto *</label>
                    <input type="date" name="data_pagamento" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold text-dark">Categoria Financeira *</label>
                    <select name="categoria_id" class="form-control select2" required>
                        <option value="">Selecione...</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="font-weight-bold text-dark">Conta Bancária Origem</label>
                    <select name="conta_id" class="form-control select2">
                        <option value="">Nenhuma (Deixar Aberto)</option>
                        @foreach($contas as $conta)
                            <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <div class="checkbox-inline">
                        <label class="checkbox checkbox-success">
                            <input type="checkbox" name="integrar_financeiro" value="1" checked>
                            <span></span>
                            Contas a Pagar
                        </label>
                        <label class="checkbox checkbox-primary">
                            <input type="checkbox" name="pagamento_a_vista" value="1">
                            <span></span>
                            Já Pago (À vista)
                        </label>
                    </div>
                </div>
            </div>


          <!-- TABELA DE RESULTADOS -->
            <div class="table-responsive">
                <table class="table table-vertical-center table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 5%">
                                <label class="checkbox checkbox-success">
                                    <input type="checkbox" id="checkAll">
                                    <span></span>
                                </label>
                            </th>
                            <th>Nº Ticket</th>
                            <th>Data</th>
                            <th>Fornecedor</th>
                            <th>Status da Pesagem</th>
                            <th>NF-e Vinculada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pesagens as $p)
                        <tr>
                            <td>
                                {{-- MOSTRA O CHECKBOX APENAS SE REALMENTE NÃO EXISTIR COMPRA --}}
                                @if(empty($p->compra_id))
                                    <label class="checkbox checkbox-success">
                                        <input type="checkbox" name="pesagens[]" value="{{ $p->id }}" class="checkItem">
                                        <span></span>
                                    </label>
                                @else
                                    <i class="fa fa-lock text-muted" data-toggle="tooltip" title="Esta pesagem já gerou uma Compra. Ajustes devem ser feitos na rotina de Compras."></i>
                                @endif
                            </td>
                            <td class="font-weight-bold">#{{ $p->id }}</td>
                            <td>{{ date('d/m/Y H:i', strtotime($p->dt_registro)) }}</td>
                            <td>{{ $p->fornecedor_nome ?? 'Não Informado' }}</td>
                            <td>
                                @if($p->compra_id)
                                    <span class="badge badge-success">Processada</span>
                                @else
                                    <span class="badge badge-warning">Pendente de NF</span>
                                @endif
                            </td>
                            <td>
                                @if($p->nfe_numero)
                                    <span class="label label-inline label-light-success font-weight-bold">
                                        NF: {{ $p->nfe_numero }}
                                    </span>
                                    <small class="d-block text-muted mt-1">{{ $p->nfe_estado }}</small>
                                @elseif($p->compra_id && empty($p->nfe_numero))
                                    {{-- AVISO CLARO PARA IR CORRIGIR EM COMPRAS --}}
                                    <span class="label label-inline label-light-danger font-weight-bold">
                                        REJEITADA / ERRO
                                    </span>
                                    <small class="d-block text-danger font-weight-bold mt-1">Corrigir em Compras</small>
                                @else
                                    <span class="text-muted">Sem NF</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">Nenhuma pesagem encontrada para os filtros selecionados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
      </div>
    </div>
</form>

@endsection

@section('javascript')
<script type="text/javascript">
    $(function () {
        $('.select2').select2();

        // Script para o Checkbox "Selecionar Todos"
        $('#checkAll').on('click', function() {
            var isChecked = $(this).is(':checked');
            $('.checkItem').prop('checked', isChecked);
        });
    });
</script>
@endsection