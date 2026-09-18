@extends('default.layout')
@section('content')
<div class="d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="card card-custom gutter-b example example-compact">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title text-white">Resumo e Ajuste Fiscal em Lote (De / Para)</h3>
        </div>
        <div class="card-body">

            @if(session('mensagem_sucesso'))
                <div class="alert alert-success font-weight-bold p-4 shadow-sm mb-4">
                    <i class="fas fa-check-circle"></i> {{ session('mensagem_sucesso') }}
                </div>
            @endif

            <!-- FILTRO DE PERÍODO E CATEGORIA PARA O RESUMO -->
            <form method="GET" action="{{ url('relatorios-financeiros/prosoft/ajuste-fiscal') }}" class="row bg-light p-3 rounded mb-4 align-items-end">
                <div class="col-md-3">
                    <label class="font-weight-bold">Data Inicial:</label>
                    <input type="date" name="data_inicio" value="{{ $dataInicio }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold">Data Final:</label>
                    <input type="date" name="data_fim" value="{{ $dataFim }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="font-weight-bold">Filtrar por Categoria:</label>
                    <select name="categoria_id" class="form-control">
                        <option value="">Todas as Categorias</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" @if($categoriaId == $cat->id) selected @endif>{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary btn-block">
                        <i class="fas fa-search"></i> Gerar Resumo
                    </button>
                </div>
            </form>

            <!-- TABELA DE RESUMO TOTALIZADO (ESTILO EXCEL) -->
            <h4 class="text-dark font-weight-bold mb-3"><i class="fas fa-table"></i> Resumo Atual por CFOP e CST</h4>
            <div class="table-responsive mb-5" style="max-height: 350px; overflow-y: auto;">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark text-center">
                        <tr>
                            <th>CFOP</th>
                            <th>CST ICMS</th>
                            <th>PIS</th>
                            <th>COFINS</th>
                            <th>Total NFs</th>
                            <th class="text-right">Total Valor (R$)</th>
                            <th>Categoria</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resumo as $r)
                        <tr>
                            <td class="text-center font-weight-bold">{{ $r->cfop }}</td>
                            <td class="text-center">{{ $r->cst }}</td>
                            <td class="text-center">{{ $r->pis }}</td>
                            <td class="text-center">{{ $r->cofins }}</td>
                            <td class="text-center font-weight-bold text-primary">{{ $r->total_nf }}</td>
                            <td class="text-right">R$ {{ number_format($r->total_valor, 2, ',', '.') }}</td>
                            <td>{{ $r->categoria_nome }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">Nenhum registro encontrado para o período/categoria selecionados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <hr class="my-5">

            <!-- FORMULÁRIO DE APLICAÇÃO DE AJUSTES EM LOTE -->
            <form method="POST" action="{{ url('relatorios-financeiros/prosoft/aplicar-ajuste') }}">
                @csrf
                <input type="hidden" name="data_inicio" value="{{ $dataInicio }}">
                <input type="hidden" name="data_fim" value="{{ $dataFim }}">

                <div class="row">
                    <!-- O QUE PROCURAR (FILTROS) -->
                    <div class="col-md-6 border-right">
                        <h5 class="text-danger font-weight-bold">1. Critérios de Busca (O que deseja alterar)</h5>
                        <p class="text-muted small">Deixe em branco os campos que não deseja filtrar.</p>
                        
                        <div class="form-group">
                            <label>CFOP Atual (Ex: 1405)</label>
                            <input type="text" name="filtro_cfop" class="form-control" placeholder="Ex: 1405">
                        </div>
                        <div class="form-group">
                            <label>CST ICMS Atual (Ex: 0102 ou 0500)</label>
                            <input type="text" name="filtro_cst_icms" class="form-control" placeholder="Ex: 0102">
                        </div>
                        <div class="form-group">
                            <label>CST PIS/COFINS Atual</label>
                            <input type="text" name="filtro_cst_pis" class="form-control" placeholder="Ex: 50">
                        </div>
                        <div class="form-group">
                            <label>Categoria de Conta E específica</label>
                            <select name="filtro_categoria_id" class="form-control">
                                <option value="">Todas</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- PARA O QUE MUDAR -->
                    <div class="col-md-6">
                        <h5 class="text-success font-weight-bold">2. Novos Valores (Para o que converter)</h5>
                        <p class="text-muted small">Preencha apenas o que deseja modificar nos itens encontrados.</p>

                        <div class="form-group">
                            <label>Novo CFOP (Ex: 1407)</label>
                            <input type="text" name="novo_cfop" class="form-control" placeholder="Ex: 1407">
                        </div>
                        <div class="form-group">
                            <label>Novo CST ICMS (Ex: 090 ou 060)</label>
                            <input type="text" name="novo_cst_icms" class="form-control" placeholder="Ex: 090">
                        </div>
                        <div class="form-group">
                            <label>Novo CST PIS / COFINS</label>
                            <input type="text" name="novo_cst_pis" class="form-control" placeholder="Ex: 01">
                        </div>
                        <div class="form-group">
                            <label>Atribuir Nova Categoria de Conta</label>
                            <select name="nova_categoria_id" class="form-control">
                                <option value="">Manter categoria atual</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="checkbox-inline mt-4 bg-light p-3 rounded border">
                            <label class="checkbox checkbox-success font-weight-bold">
                                <input type="checkbox" name="calcular_pis_cofins" value="1" checked>
                                <span></span> Recalcular PIS (1.65%) e COFINS (7.60%) automaticamente sobre o valor dos itens
                            </label>
                        </div>
                    </div>
                </div>
				
                <div class="card-footer text-right mt-4 bg-transparent border-0">
                    <a href="{{ url('relatorios-financeiros/exportar-prosoft') }}" class="btn btn-secondary mr-2">
                        <i class="fas fa-arrow-left"></i> Voltar para Exportação
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg font-weight-bold" onclick="return confirm('Tem certeza que deseja aplicar esta alteração em lote para todos os itens filtrados?')">
                        <i class="fas fa-check-double"></i> Executar Ajuste em Lote
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection