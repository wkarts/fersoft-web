@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Estoque</h3>
        <div class="card-toolbar">
            
            <a href="/estoque/listApontamentos" class="btn btn-warning btn-sm mr-2">
                <i class="la la-clipboard-list"></i> Lista de Alterações
            </a>

            <a href="/estoque/apontamentoManual" class="btn btn-info btn-sm mr-2">
                <i class="la la-plus"></i> Apontamento Manual
            </a>
            
            <a onclick='swal({
                title: "Atenção!",
                text: "Deseja zerar o estoque e TODO o histórico? Digite a senha:",
                content: "input",
                icon: "warning",
                buttons: ["Cancelar", "Confirmar"],
                dangerMode: true,
            }).then((value) => {
                if (value === "1234") { 
                    location.href="/stock/zerarEstoqueCompleto";
                } else if(value !== null) {
                    swal("Erro", "Senha incorreta!", "error");
                }
            })' class="btn btn-danger btn-sm mr-2">
                <i class="la la-trash"></i> Zerar estoque completo
            </a>

            <a href="/estoque/relatorioPdf?{{ http_build_query(request()->all()) }}" target="_blank" class="btn btn-light-danger btn-sm">
  				  <i class="la la-file-pdf"></i> Gerar PDF
			</a>
          
          	<a href="/estoque/relatorioFiscal?{{ http_build_query(request()->all()) }}" target="_blank" class="btn btn-light-success btn-sm ml-2">
   				 <i class="la la-file-invoice"></i> Relatório Fiscal
			</a>
          
        </div>
    </div>

    <div class="card-body">
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="card card-custom bg-light-danger gutter-b" style="height: 120px">
                    <div class="card-body">
                        <span class="text-danger font-weight-bold">Total em Estoque (CUSTO)</span>
                        <span class="card-title font-weight-boldest text-danger font-size-h2 mb-0 d-block">
                            R$ {{ number_format($somaEstoque['compra'], 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom bg-light-success gutter-b" style="height: 120px">
                    <div class="card-body">
                        <span class="text-success font-weight-bold">Total em Estoque (VENDA)</span>
                        <span class="card-title font-weight-boldest text-success font-size-h2 mb-0 d-block">
                            R$ {{ number_format($somaEstoque['venda'], 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <form method="get" action="/estoque" id="form-filtro" class="mb-8">
            <div class="row align-items-end">
                <div class="col-lg-3">
                    <label>Produto</label>
                    <input type="text" name="pesquisa" class="form-control" value="{{ $pesquisa }}" placeholder="Nome do produto...">
                </div>
                <div class="col-lg-3">
                    <label>Categoria</label>
                    <select name="categoria_id" class="form-control custom-select" onchange="$('#form-filtro').submit()">
                        <option value="">Todas</option>
                        @foreach($categorias as $c)
                            <option value="{{$c->id}}" {{$categoria_id == $c->id ? 'selected' : ''}}>{{$c->nome}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label>Local</label>
                    <select name="filial_id" class="form-control custom-select" onchange="$('#form-filtro').submit()">
                        <option value="">Todos</option>
                        <option value="matriz" {{$filial_id == 'matriz' ? 'selected' : ''}}>Matriz</option>
                        @foreach($filiais as $f)
                            <option value="{{$f->id}}" {{$filial_id == $f->id ? 'selected' : ''}}>{{$f->descricao}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label>Mês / Ano</label>
                    <div class="d-flex">
                        <select name="mes" class="form-control custom-select mr-1" onchange="$('#form-filtro').submit()">
                            @for($m=1;$m<=12;$m++)
                                <option value="{{$m}}" {{$mes==$m?'selected':''}}>{{str_pad($m,2,'0',STR_PAD_LEFT)}}</option>
                            @endfor
                        </select>
                        <select name="ano" class="form-control custom-select" onchange="$('#form-filtro').submit()">
                            @for($a=date('Y')-2;$a<=date('Y')+2;$a++)
                                <option value="{{$a}}" {{$ano==$a?'selected':''}}>{{$a}}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="col-lg-2">
                    <button class="btn btn-primary btn-block" type="submit">Filtrar</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-head-custom table-vertical-center">
                <thead>
                    <tr>
                        <th style="width: 50px">
                            <label class="checkbox checkbox-single">
                                <input type="checkbox" id="check-all-gerenciar">
                                <span></span>
                            </label>
                        </th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Local</th>
                        <th class="text-right">S. Inicial</th>
                        <th class="text-right">Entradas (+)</th>
                        <th class="text-right">Saídas (-)</th>
                      	<th class="text-right">Saldo</th>
                        
                        <th class="text-right">Vl. Venda</th>
                        <th class="text-right">Vl. Custo</th> 
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estoque as $e)
                    <tr>
                        <td>
                            <label class="checkbox checkbox-single">
                                <input type="checkbox" class="check-gerenciar" data-id="{{$e->produto_id}}" {{ $e->gerenciar_estoque ? 'checked' : '' }}>
                                <span></span>
                            </label>
                        </td>
                        <td>
                            <span class="text-dark-75 font-weight-bolder d-block font-size-lg">{{ $e->produto_nome }}</span>
                            <span class="text-muted font-weight-bold">ID: {{ $e->produto_id }}</span>
                        </td>
                        <td>{{ $e->categoria_nome }}</td>
                        <td class="datatable-cell">
                          <span style="width: 100px;">
                              @if($e->filial_id == null)
                                  {{-- Se o ID for nulo, é a Matriz --}}
                                  <span class="label label-inline label-light-primary font-weight-bold">MATRIZ</span>
                              @else
                                  {{-- Se tiver ID, busca a descrição da filial que carregamos no Controller --}}
                                  <span class="label label-inline label-light-success font-weight-bold">
                                      {{ $e->filial->descricao ?? 'FILIAL' }}
                                  </span>
                              @endif
                          </span>
                      </td>
                        <th class="text-right">{{ number_format($e->saldo_inicial ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right text-success">+{{ number_format($e->total_entradas ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right text-danger">-{{ number_format($e->total_saidas ?? 0, 2, ',', '.') }}</td>
                        @php
                          // Verifica se o filtro é para o mês/ano presente
                          $hoje = date('m/Y');
                          $filtro = str_pad($mes, 2, '0', STR_PAD_LEFT) . '/' . $ano;
                          $ehMesAtual = ($hoje == $filtro);
                      @endphp

                      <td class="text-right font-weight-boldest">
                          @if($ehMesAtual)
                              {{-- Se for o mês atual, mostra o Saldo Físico Real (quantidade atual no banco) --}}
                              <span class="{{ $e->quantidade < 0 ? 'text-danger' : 'text-primary' }}" title="Saldo Físico Atual">
                                  {{ number_format($e->quantidade, 2, ',', '.') }}
                              </span>
                          @else
                              {{-- Se for mês retroativo, mostra o Saldo Calculado daquele período --}}
                              <span class="{{ $e->saldo_no_periodo < 0 ? 'text-danger' : 'text-dark' }}" title="Saldo Fechamento do Período">
                                  {{ number_format($e->saldo_no_periodo, 2, ',', '.') }}
                              </span>
                          @endif
                      </td>
                        <td class="text-right">R$ {{ number_format($e->preco_venda, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($e->preco_custo, 2, ',', '.') }}</td> 
                        
                        <td class="text-right d-flex justify-content-end">
                            <a href="/estoque/apontamentoManual" class="btn btn-icon btn-light-warning btn-sm mr-1" title="Ajuste / Apontamento Manual">
                                <i class="la la-edit"></i>
                            </a>

                            <a href="/estoque/historico/{{$e->id}}" class="btn btn-icon btn-light-primary btn-sm" title="Ver Extrato">
                                <i class="la la-list"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex flex-wrap py-2 mr-3">
                {{ $estoque->appends(request()->all())->links() }}
            </div>
        </div>
    </div>
</div>

<script>
    // Lógica do botão Gerenciar (AJAX)
    $('.check-gerenciar').change(function() {
        let id = $(this).data('id');
        let status = $(this).is(':checked') ? 1 : 0;
        
        $.get('/produtos/alterarGerenciarEstoque', { id: id, status: status })
        .done(() => {
            toastr.success('Status de gerenciamento atualizado!');
        })
        .fail(() => {
            toastr.error('Erro ao atualizar.');
        });
    });

    // Marcar todos
    $('#check-all-gerenciar').click(function() {
        let status = $(this).is(':checked');
        $('.check-gerenciar').prop('checked', status).change();
    });
</script>
@endsection