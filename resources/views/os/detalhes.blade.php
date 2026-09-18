@extends('default.layout')
@section('content')

    <style type="text/css">
        .card-os {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.04);
            background: #ffffff;
            margin-bottom: 1.5rem;
        }
        .os-header-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #181C32;
        }
        .badge-status-lg {
            padding: 0.6rem 1.2rem;
            font-size: 0.85rem;
            font-weight: 700;
            border-radius: 8px;
            text-transform: uppercase;
        }
        .stat-box {
            background: #F4F6F9;
            border-radius: 10px;
            padding: 12px 18px;
            border-left: 4px solid #3699FF;
        }
        .stat-box.success { border-left-color: #1BC5BD; }
        .stat-box.warning { border-left-color: #FFA800; }
        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #B5B5C3;
            font-weight: 700;
        }
        .stat-value {
            font-size: 1.1rem;
            font-weight: 800;
            color: #181C32;
        }
        .table-modern thead th {
            background-color: #F3F6F9;
            color: #464E5F;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            border: none;
        }
        .section-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #212121;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>

    <div class="container-fluid p-0">
        <input type="hidden" id="filial" value="{{ $ordem->filial_id }}">
        <input type="hidden" id="token" value="{{ csrf_token() }}">
        <input type="hidden" name="ordem_servico_id" class="ordem_servico_id" value="{{$ordem->id}}">

        <!-- 1. BARRA SUPERIOR DE RESUMO E AÇÕES RÁPIDAS -->
        <div class="card card-os p-6">
            <div class="row align-items-center">
                <div class="col-lg-6 col-12 mb-4 mb-lg-0">
                    <div class="d-flex align-items-center gap-3">
                        <span class="os-header-title">Ordem de Serviço #{{ $ordem->numero_sequencial > 0 ? $ordem->numero_sequencial : $ordem->id }}</span>
                        @if($ordem->estado == 'pd')
                            <span class="badge badge-status-lg bg-light-warning text-warning">PENDENTE</span>
                        @elseif($ordem->estado == 'ap')
                            <span class="badge badge-status-lg bg-light-success text-success">APROVADO</span>
                        @elseif($ordem->estado == 'rp')
                            <span class="badge badge-status-lg bg-light-danger text-danger">REPROVADO</span>
                        @else
                            <span class="badge badge-status-lg bg-light-info text-info">FINALIZADO</span>
                        @endif
                    </div>

                    <div class="mt-2 text-muted fs-7">
                        <i class="la la-user me-1"></i> Cliente: <strong>{{ $ordem->cliente->razao_social ?? 'Não informado' }}</strong>
                        @if(isset($ordem->veiculo))
                            <span class="ms-3"><i class="la la-car me-1"></i> Veículo: <strong>{{ $ordem->veiculo->placa }} ({{ $ordem->veiculo->marca }} {{ $ordem->veiculo->modelo }})</strong></span>
                        @endif
                    </div>
                </div>

                <div class="col-lg-6 col-12 text-lg-end">
                    <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                        <!-- WhatsApp -->
                        <a target="_blank" href="/ordemServico/enviarWhatsapp/{{$ordem->id}}" class="btn btn-success font-weight-bold">
                            <i class="la la-whatsapp"></i> WhatsApp
                        </a>

                        @if($ordem->estado != 'rp')
                            <a href="/ordemServico/alterarEstado/{{$ordem->id}}" class="btn btn-primary font-weight-bold">
                                <i class="la la-refresh"></i> Alterar Estado
                            </a>
                        @endif

                        <a target="_blank" href="/ordemServico/imprimir/{{$ordem->id}}" class="btn btn-info font-weight-bold">
                            <i class="la la-print"></i> Imprimir
                        </a>

                        <a href="/ordemServico/gerarVendaCompleta/{{$ordem->id}}" class="btn btn-warning font-weight-bold" title="Finalizar e emitir Cupom Fiscal no PDV">
                            <i class="la la-cash-register"></i> PDV (NFC-e)
                        </a>
                    </div>
                </div>
            </div>

            <div class="separator separator-dashed my-5"></div>

            <!-- INDICADORES RESUMIDOS -->
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <div class="stat-label">Total da OS</div>
                        <div class="stat-value text-primary">R$ {{ moeda($ordem->total_os()) }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-box success">
                        <div class="stat-label">Total Serviços</div>
                        <div class="stat-value text-success">R$ {{ moeda($ordem->servicos->sum('sub_total')) }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-box warning">
                        <div class="stat-label">Total Produtos</div>
                        <div class="stat-value text-warning">R$ {{ moeda($ordem->produtos->sum('sub_total')) }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-box">
                        <div class="stat-label">Responsável</div>
                        <div class="stat-value fs-6 text-truncate">{{ $ordem->usuario->nome }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. LAUDO TÉCNICO, GARANTIA E ATENDIMENTO -->
        <div class="card card-os p-6">
            <div class="section-title text-primary mb-4">
                <i class="la la-clipboard-check fs-2 text-primary"></i> Checklist Técnico, Atendimento e Garantia
            </div>

            <form method="post" action="/ordemServico/salvarChecklist">
                @csrf
                <input type="hidden" name="ordem_servico_id" value="{{ $ordem->id }}">

                <div class="row">
                    <div class="form-group col-md-3 col-sm-6 mb-3">
                        <label class="form-label font-weight-bold">Vendedor / Atendente</label>
                        <select class="custom-select form-control" name="vendedor_id">
                            <option value="">Selecione o vendedor...</option>
                            @foreach($usuarios as $u)
                                <option value="{{ $u->id }}" @if(isset($ordem->vendedor_id) && $ordem->vendedor_id == $u->id) selected @endif>{{ $u->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3 col-sm-6 mb-3">
                        <label class="form-label font-weight-bold">Técnico / Mecânico</label>
                        <select class="custom-select form-control" name="tecnico_id">
                            <option value="">Selecione o mecânico...</option>
                            @foreach($funcionarios as $f)
                                <option value="{{ $f->id }}" @if(isset($ordem->tecnico_id) && $ordem->tecnico_id == $f->id) selected @endif>{{ $f->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3 col-sm-6 mb-3">
                        <label class="form-label font-weight-bold">Garantia (Dias)</label>
                        <input type="number" class="form-control" name="garantia_dias" value="{{ $ordem->garantia_dias ?? 90 }}">
                    </div>

                    <div class="form-group col-md-3 col-sm-6 mb-3">
                        <label class="form-label font-weight-bold">Status do Orçamento</label>
                        <select class="custom-select form-control" name="status_aprovacao">
                            <option value="orcamento" @if(isset($ordem->status_aprovacao) && $ordem->status_aprovacao == 'orcamento') selected @endif>1 - Em Orçamento</option>
                            <option value="aprovado" @if(isset($ordem->status_aprovacao) && $ordem->status_aprovacao == 'aprovado') selected @endif>2 - Aprovado pelo Cliente</option>
                            <option value="em_andamento" @if(isset($ordem->status_aprovacao) && $ordem->status_aprovacao == 'em_andamento') selected @endif>3 - Em Execução</option>
                            <option value="concluido" @if(isset($ordem->status_aprovacao) && $ordem->status_aprovacao == 'concluido') selected @endif>4 - Concluído</option>
                            <option value="reprovado" @if(isset($ordem->status_aprovacao) && $ordem->status_aprovacao == 'reprovado') selected @endif>5 - Recusado pelo Cliente</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-12 mb-3">
                        <label class="form-label font-weight-bold">Parecer Técnico / Recomendações da Oficina</label>
                        <textarea class="form-control" name="parecer_tecnico" rows="2" placeholder="Diagnóstico detalhado do mecânico e recomendações para o cliente...">{{ $ordem->parecer_tecnico ?? '' }}</textarea>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary font-weight-bold">
                        <i class="la la-save"></i> Salvar Laudo Técnico
                    </button>
                </div>
            </form>
        </div>

        <!-- 3. SERVIÇOS DA OS -->
        <div class="card card-os p-6">
            <div class="section-title text-dark mb-4">
                <i class="la la-tools fs-2 text-success"></i> Serviços da OS
            </div>

            <form method="post" action="/ordemServico/addServico">
                @csrf
                <input type="hidden" id="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="ordem_servico_id" value="{{$ordem->id}}">

                <div class="row align-items-end mb-4 bg-light p-4 rounded">
                    <div class="form-group validated col-md-5 col-12 mb-2 mb-md-0">
                        <label class="form-label font-weight-bold">Serviço</label>
                        <select style="width: 100%" required class="form-control select2 servico" id="kt_select2_1" name="servico">
                            <option value="">Selecione o serviço...</option>
                            @foreach($servicos as $s)
                                <option data-value="{{ number_format($s->valor, $casasDecimais, ',', '.') }}" value="{{$s->id}}">
                                    {{ $s->nome }} - R$ {{ number_format($s->valor, 2, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group validated col-md-3 col-6 mb-2 mb-md-0">
                        <label class="form-label font-weight-bold">Valor Unitário</label>
                        <input required type="tel" id="valor_unitario" name="valor_unitario" class="form-control money valor_servico" value="">
                    </div>

                    <div class="form-group validated col-md-2 col-6 mb-2 mb-md-0">
                        <label class="form-label font-weight-bold">Quantidade</label>
                        <input type="text" id="quantidade" name="quantidade" class="form-control qtd money qtd_servico" value="1">
                    </div>

                    <div class="col-md-2 col-12">
                        <button id="btn-add-servico" style="width: 100%" type="button" class="btn btn-success font-weight-bold">
                            <i class="la la-plus"></i> Adicionar
                        </button>
                    </div>
                </div>
            </form>

            <!-- TABELA DE SERVIÇOS -->
            <div class="table-responsive">
                <table class="table table-head-custom table-vertical-center table-hover table-modern tabela-servicos">
                    <thead>
                    <tr>
                        <th style="width: 35%">Serviço</th>
                        <th class="text-center" style="width: 15%">Qtd</th>
                        <th class="text-end" style="width: 15%">Valor Unitário</th>
                        <th class="text-end" style="width: 15%">Subtotal</th>
                        <th class="text-center" style="width: 10%">Status</th>
                        <th class="text-center" style="width: 10%">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($ordem->servicos as $s)
                        <tr>
                            <td class="font-weight-bold text-dark-75">{{$s->servico->nome ?? 'Serviço Removido'}}</td>
                            <td class="text-center">{{ moeda($s->quantidade) }}</td>
                            <td class="text-end">R$ {{ moeda($s->valor_unitario) }}</td>
                            <td class="text-end font-weight-bold text-dark">R$ {{ moeda($s->sub_total) }}</td>
                            <td class="text-center">
                                @if($s->status == true)
                                    <span class="badge bg-light-success text-success font-weight-bold">FINALIZADO</span>
                                @else
                                    <span class="badge bg-light-warning text-warning font-weight-bold">PENDENTE</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!$s->status)
                                    <a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/deleteServico/{{ $s->id }}" }else{return false} })' href="#!" class="btn btn-icon btn-light-danger btn-sm" title="Remover">
                                        <i class="la la-trash"></i>
                                    </a>
                                @endif
                                <a class="btn btn-icon btn-light-success btn-sm" href="/ordemServico/alterarStatusServico/{{ $s->id }}" title="Alterar Status">
                                    <i class="la la-check"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between mt-4 p-4 bg-light rounded">
                <div>
                    <h5 class="m-0">Total Serviços: <strong class="text-success total-servico">R$ {{ moeda($ordem->servicos->sum('sub_total')) }}</strong></h5>
                </div>

                <div>
                    @if(!$ordem->nfse)
                        <a class="btn btn-info font-weight-bold" href="/ordemServico/gerar_nfse/{{$ordem->id}}">
                            <i class="la la-file-invoice"></i> Gerar NFS-e de Serviços
                        </a>
                    @else
                        <div class="d-flex align-items-center gap-3">
                        <span>NFS-e:
                            @if($ordem->nfse->estado == 'aprovado')
                                <strong class="badge bg-light-success text-success">Aprovada (#{{$ordem->nfse->numero_nfse}})</strong>
                            @else
                                <strong class="badge bg-light-primary text-primary">{{ strtoupper($ordem->nfse->estado) }}</strong>
                            @endif
                        </span>
                            <a class="btn btn-dark btn-sm" href="/nfse/edit/{{$ordem->nfse->id}}">
                                <i class="la la-eye"></i> Ver NFS-e
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 4. PRODUTOS E PEÇAS DA OS -->
        <div class="card card-os p-6">
            <div class="section-title text-dark mb-4">
                <i class="la la-box fs-2 text-warning"></i> Peças e Produtos da OS
            </div>

            <form method="post" action="/ordemServico/saveProduto">
                @csrf
                <input type="hidden" id="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="ordem_servico_id" value="{{$ordem->id}}">

                <div class="row align-items-end mb-4 bg-light p-4 rounded">
                    <div class="form-group validated col-md-5 col-12 mb-2 mb-md-0">
                        <label class="form-label font-weight-bold">Buscar Produto/Peça</label>
                        <select style="width: 100%" required class="form-control select2" id="kt_select2_3" name="produto_id">
                            <option value="">Selecione a peça/produto...</option>
                            @foreach($produtos as $p)
                                <option data-valor="{{ number_format($p->valor_venda, $casasDecimais, ',', '.') }}" value="{{ $p->id }}">
                                    {{ $p->nome }} (Estoque: {{ $p->estoque_atual ?? 0 }}) - R$ {{ number_format($p->valor_venda, 2, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group validated col-md-3 col-6 mb-2 mb-md-0">
                        <label class="form-label font-weight-bold">Valor Unitário</label>
                        <input required id="valor_prod" placeholder="Valor" type="text" class="form-control money valor_produto" name="valor_unitario" value="{{number_format(0, $casasDecimais, ',', '.')}}">
                    </div>

                    <div class="form-group validated col-md-2 col-6 mb-2 mb-md-0">
                        <label class="form-label font-weight-bold">Quantidade</label>
                        <input required id="quantidade" placeholder="QTD" type="text" class="form-control money qtd qtd_produto" name="quantidade" value="1">
                    </div>

                    <div class="col-md-2 col-12">
                        <button id="btn-add-produto" style="width: 100%" type="button" class="btn btn-warning font-weight-bold text-white">
                            <i class="la la-plus"></i> Adicionar
                        </button>
                    </div>
                </div>
            </form>

            <!-- TABELA DE PRODUTOS -->
            <div class="table-responsive">
                <table class="table table-head-custom table-vertical-center table-hover table-modern tabela-produto">
                    <thead>
                    <tr>
                        <th style="width: 40%">Produto / Peça</th>
                        <th class="text-center" style="width: 15%">Qtd</th>
                        <th class="text-end" style="width: 15%">Valor Unitário</th>
                        <th class="text-end" style="width: 20%">Subtotal</th>
                        <th class="text-center" style="width: 10%">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($ordem->produtos as $item)
                        <tr>
                            <td class="font-weight-bold text-dark-75">{{$item->produto->nome ?? 'Produto Removido'}}</td>
                            <td class="text-center">{{ moeda($item->quantidade) }}</td>
                            <td class="text-end">R$ {{ moeda($item->valor_unitario) }}</td>
                            <td class="text-end font-weight-bold text-dark">R$ {{ moeda($item->sub_total) }}</td>
                            <td class="text-center">
                                <a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/deleteProduto/{{ $item->id }}" }else{return false} })' href="#!" class="btn btn-icon btn-light-danger btn-sm" title="Remover">
                                    <i class="la la-trash"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between mt-4 p-4 bg-light rounded">
                <div>
                    <h5 class="m-0">Total Produtos: <strong class="text-warning total-produto">R$ {{ moeda($ordem->produtos->sum('sub_total')) }}</strong></h5>
                </div>

                <div>
                    @if(!$ordem->venda)
                        <a class="btn btn-success font-weight-bold" href="/ordemServico/gerar_venda/{{$ordem->id}}">
                            <i class="la la-box"></i> Gerar NF-e de Peças
                        </a>
                    @else
                        <div class="d-flex align-items-center gap-3">
                        <span>NF-e Peças:
                            <strong class="badge bg-light-success text-success">Venda #{{$ordem->venda->numero_sequencial}}</strong>
                        </span>
                            <a class="btn btn-dark btn-sm" href="/vendas/detalhar/{{$ordem->venda->id}}">
                                <i class="la la-eye"></i> Ver Venda
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 5. EQUIPE E RELATÓRIOS DA OS -->
        <div class="row">
            <!-- FUNCIONÁRIOS DA OS -->
            <div class="col-lg-6 col-12">
                <div class="card card-os p-6">
                    <div class="section-title text-dark mb-4">
                        <i class="la la-users fs-2 text-info"></i> Equipe Alocada
                    </div>

                    <form method="post" action="/ordemServico/saveFuncionario">
                        @csrf
                        <input type="hidden" name="ordem_servico_id" value="{{$ordem->id}}">

                        <div class="row align-items-end mb-3">
                            <div class="form-group col-md-5 col-12">
                                <label class="form-label font-weight-bold">Funcionário</label>
                                <select class="form-control select2" id="kt_select2_2" name="funcionario">
                                    @foreach($funcionarios as $f)
                                        <option value="{{$f->id}}">{{$f->nome}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-5 col-12">
                                <label class="form-label font-weight-bold">Função</label>
                                <input type="text" name="funcao" class="form-control" placeholder="Ex: Mecânico Principal">
                            </div>

                            <div class="col-md-2 col-12">
                                <button type="submit" class="btn btn-info font-weight-bold w-100">
                                    <i class="la la-plus"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-head-custom table-vertical-center table-hover table-modern">
                            <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Função</th>
                                <th class="text-center">Ação</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($ordem->funcionarios as $f)
                                <tr>
                                    <td class="font-weight-bold">{{$f->funcionario->nome}}</td>
                                    <td>{{$f->funcao}}</td>
                                    <td class="text-center">
                                        <a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/deleteFuncionario/{{ $f->id }}" }else{return false} })' href="#!" class="btn btn-icon btn-light-danger btn-sm">
                                            <i class="la la-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RELATÓRIOS / APONTAMENTOS -->
            <div class="col-lg-6 col-12">
                <div class="card card-os p-6">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="section-title text-dark">
                            <i class="la la-sticky-note fs-2 text-primary"></i> Relatórios e Apontamentos
                        </div>
                        <a href="/ordemServico/addRelatorio/{{$ordem->id}}" class="btn btn-sm btn-info font-weight-bold">
                            <i class="la la-plus"></i> Novo Apontamento
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-head-custom table-vertical-center table-hover table-modern">
                            <thead>
                            <tr>
                                <th>Data</th>
                                <th>Usuário</th>
                                <th class="text-center">Ações</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($ordem->relatorios as $r)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($r->data_registro)->format('d/m/Y H:i')}}</td>
                                    <td class="font-weight-bold">{{$r->usuario->nome}}</td>
                                    <td class="text-center">
                                        <a class="btn btn-icon btn-light-info btn-sm me-1" href="#!" onclick="modal('{{ \Carbon\Carbon::parse($r->data_registro)->format('d/m/Y H:i:s')}}', '{{$r->texto}}')" title="Ver texto">
                                            <i class="la la-eye"></i>
                                        </a>
                                        <a class="btn btn-icon btn-light-warning btn-sm me-1" href="/ordemServico/editRelatorio/{{ $r->id }}" title="Editar">
                                            <i class="la la-edit"></i>
                                        </a>
                                        <a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/deleteRelatorio/{{ $r->id }}" }else{return false} })' href="#!" class="btn btn-icon btn-light-danger btn-sm" title="Excluir">
                                            <i class="la la-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. FECHAMENTO FINANCEIRO E DESCONTOS -->
        <div class="card card-os p-6">
            <div class="section-title text-dark mb-4">
                <i class="la la-calculator fs-2 text-primary"></i> Fechamento Financeiro e Ajustes
            </div>

            <form method="post" action="/ordemServico/update/{{$ordem->id}}">
                @csrf
                @method('put')

                <div class="row align-items-end">
                    <div class="form-group col-md-2 col-6 mb-3">
                        <label class="form-label font-weight-bold">Desconto (R$)</label>
                        <input id="desconto" placeholder="0,00" type="tel" class="form-control money" name="desconto" value="{{number_format($ordem->desconto, $casasDecimais, ',', '.')}}">
                    </div>

                    <div class="form-group col-md-2 col-6 mb-3">
                        <label class="form-label font-weight-bold">Acréscimo (R$)</label>
                        <input id="acresimo" placeholder="0,00" type="tel" class="form-control money" name="acrescimo" value="{{number_format($ordem->acrescimo, $casasDecimais, ',', '.')}}">
                    </div>

                    <div class="form-group col-md-3 col-12 mb-3">
                        <label class="form-label font-weight-bold">Forma de Pagamento</label>
                        <select name="forma_pagamento" class="custom-select form-control">
                            <option value="">Selecione...</option>
                            @foreach(\App\Models\OrdemServico::tiposPagamento() as $f)
                                <option @if($ordem->forma_pagamento == $f) selected @endif value="{{$f}}">{{$f}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-3 col-12 mb-3">
                        <label class="form-label font-weight-bold">Observação Geral</label>
                        <input placeholder="Observações..." type="text" class="form-control" name="observacao" value="{{ $ordem->observacao }}">
                    </div>

                    <div class="col-md-2 col-12 mb-3">
                        <button type="submit" class="btn btn-success font-weight-bold w-100">
                            <i class="la la-check"></i> Salvar Fechamento
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL VISUALIZAR RELATÓRIO -->
    <div class="modal fade" id="modal1" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="data"></h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <p id="texto" class="fs-6 text-dark"></p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <script type="text/javascript">
        function modal(data, texto){
            $('#texto').html(texto);
            $('#data').html('Apontamento em: ' + data);
            $('#modal1').modal('show');
        }

        $(document).ready(function() {
            // Puxa o valor do serviço automaticamente ao selecionar
            $('#kt_select2_1').on('change', function() {
                let valor = $(this).find(':selected').data('value');
                if (valor !== undefined && valor !== '') {
                    $('#valor_unitario').val(valor);
                }
            });

            // Puxa o valor da peça/produto automaticamente ao selecionar
            $('#kt_select2_3').on('change', function() {
                let valor = $(this).find(':selected').data('valor');
                if (valor !== undefined && valor !== '') {
                    $('#valor_prod').val(valor);
                }
            });
        });
    </script>
    <script type="text/javascript" src="/js/ordem_servico.js"></script>
@endsection
