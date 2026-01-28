@extends('default.layout')

@section('content')
    <style type="text/css">
        /* ----- GRID GERAL ----- */

        /* Altura fixa para todas as linhas */
        .datatable-row, .datatable-cell {
            height: 35px !important; /* Altura uniforme */
            vertical-align: middle !important; /* Alinhamento vertical */
        }

        /* Células - Alinhamento e Responsividade */
        .datatable-cell {
            text-align: center !important; /* Alinhamento horizontal */
            padding: 5px !important; /* Espaçamento interno */
            white-space: nowrap !important; /* Evita quebra de texto */
        }

        /* Cabeçalho fixo e responsivo */
        .thead-light {
            position: sticky !important;
            top: -1 !important; /* Fixação no topo */
            z-index: 1 !important; /* Prioridade sobre outros elementos */
            background-color: white !important; /* Fundo branco */
        }

        /* Botões - Estilo padrão */
        .btn-custom {
            padding: 3px 8px !important; /* Proporções */
            font-size: 12px !important; /* Texto menor */
            line-height: 1.2 !important; /* Altura ajustada */
        }

        /* Ajuste de ícones nos botões */
        .btn-custom i {
            vertical-align: middle !important; /* Centraliza ícones */
        }

        /* Badges - Largura fixa */
        .badge-fixed-width-status {
            width: 100px !important; /* Largura padrão */
            display: inline-block !important;
            text-align: center !important;
            padding: 6px !important;
        }

        /* Responsividade e scroll */
        .table-responsive {
            overflow-x: auto !important; /* Rolagem horizontal */
            overflow-y: auto !important; /* Rolagem vertical */
        }

        /* ----- GRID DE PESAGENS ----- */

        .table-pesagens {
            font-size: 12px !important; /* Tamanho menor para fontes */
            max-height: 350px !important; /* Altura máxima com scroll */
        }

        /* Ajuste específico para colunas */
        .table-pesagens th,
        .table-pesagens td {
            white-space: nowrap !important; /* Impede quebra de texto */
        }

        /* Oculta sem remover a coluna de Token/UUID (2ª coluna) */
        .table-pesagens th:nth-child(2),
        .table-pesagens td:nth-child(2) {
            display: none !important;
        }

        /* ----- GRID DE TICKETS ----- */

        .table-tickets {
            font-size: 12px !important; /* Fonte menor para tickets */
            max-height: 200px !important; /* Altura específica */
        }

        /* Altura e alinhamento ajustados */
        .table-tickets .datatable-cell {
            padding: 3px !important; /* Menor espaçamento */
            font-size: 10px !important; /* Tamanho reduzido */
        }

        /* Cabeçalho fixo para tickets */
        .table-tickets .thead-light {
            top: -1 !important; /* Fixação no topo */
            height: 30px !important; /* Altura menor */
        }

        /* Ajuste específico para botões */
        .table-tickets .btn-custom {
            padding: 2px 6px !important; /* Tamanho compacto */
            font-size: 10px !important; /* Texto menor */
        }

        /* Responsividade específica */
        .table-tickets .table-responsive {
            max-height: 200px !important; /* Altura máxima */
        }

        /* Badge para tickets */
        .table-tickets .badge-fixed-width-status {
            width: 80px !important; /* Badge menor */
            padding: 4px !important; /* Ajuste interno */
        }

    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="card card-custom gutter-b">
        <div class="card-body">
            <!-- Exibe mensagens de sucesso e erro -->
            @if(session('mensagem_sucesso'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('mensagem_sucesso') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('mensagem_erro'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('mensagem_erro') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">
                    <!-- Botão para abrir o modal de nova pesagem -->
                    <button id="btnNovaPesagem" type="button" class="btn btn-lg btn-success" data-toggle="modal" data-target="#modalPesagem">
                        <i class="fa fa-plus"></i> Nova Pesagem
                    </button>
                </div>
            </div>
            <br>

            <div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

                <h4>Filtros</h4>
                <form method="get" action="{{ route('pesagens.list') }}">
                    <div class="form-row align-items-end">
                        <!-- Pesquisar (fixo) -->
                        <div class="form-group col-lg-3 col-md-6 mb-0">
                            <label>Pesquisar</label>
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Pesquisar veículo, placa, cliente, fornecedor..."
                                value="{{ old('search', $search ?? '') }}"
                            >
                        </div>

                        <!-- Data Inicial -->
                        <div class="form-group col mb-0">
                            <label>Data Inicial</label>
                            <input
                                type="datetime-local"
                                name="data_inicial"
                                class="form-control"
                                value="{{ old('data_inicial', $dataInicial ?? '') }}"
                            >
                        </div>

                        <!-- Data Final -->
                        <div class="form-group col mb-0">
                            <label>Data Final</label>
                            <input
                                type="datetime-local"
                                name="data_final"
                                class="form-control"
                                value="{{ old('data_final', $dataFinal ?? '') }}"
                            >
                        </div>

                        <!-- Veículo -->
                        <div class="form-group col mb-0">
                            <label>Veículo</label>
                            <select name="veiculo_id" class="form-control">
                                <option value="">Todos</option>
                                @foreach($veiculos as $v)
                                    <option value="{{ $v->id }}"
                                        {{ (string)($veiculo_id ?? '') === (string)$v->id ? 'selected' : '' }}>
                                        {{ $v->placa }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="form-group col mb-0">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">Todos</option>
                                <option value="em andamento" {{ ($status ?? '')==='em andamento'?'selected':'' }}>
                                    Em Andamento
                                </option>
                                <option value="concluído" {{ ($status ?? '')==='concluído'?'selected':'' }}>
                                    Concluído
                                </option>
                            </select>
                        </div>

                        <!-- Botão Filtrar com texto -->
                        <div class="form-group col-auto mb-0 align-self-end">
                            <button class="btn btn-primary btn-filter" type="submit">
                                <i class="fa fa-filter"></i> Filtrar
                            </button>
                        </div>

                        <!-- Botão Limpar só X -->
                        <div class="form-group col-auto mb-0 align-self-end ml-2">
                            <button
                                id="btnClearFilters"
                                type="button"
                                class="btn btn-danger btn-clear"
                                title="Limpar filtros"
                            >&times;</button>
                        </div>
                    </div>
                </form>



                <!--
                <form method="get" action="{{ route('pesagens.list') }}">
                    <div class="row align-items-center">
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Data Inicial</label>
                            <input type="datetime-local" name="data_inicial" class="form-control" value="{{ old('data_inicial', $dataInicial ?? '') }}">
                        </div>
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Data Final</label>
                            <input type="datetime-local" name="data_final" class="form-control" value="{{ old('data_final', $dataFinal ?? '') }}">
                        </div>
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Veículo</label>
                            <select name="veiculo_id" class="form-control ">
                                <option value="">Selecione</option>
                                @foreach($veiculos as $veiculo)
                                    <option value="{{ $veiculo->id }}" {{ old('veiculo_id', $veiculo_id ?? '') == $veiculo->id ? 'selected' : '' }}>
                                        {{ $veiculo->placa }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">Todos</option>
                                <option value="em andamento" {{ old('status', $status ?? '') == 'em andamento' ? 'selected' : '' }}>Em Andamento</option>
                                <option value="concluído" {{ old('status', $status ?? '') == 'concluído' ? 'selected' : '' }}>Concluído</option>
                            </select>
                        </div>
                        <div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">
                            <button class="btn btn-primary btn-sm" type="submit">
                                <i class="fa fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
                -->
                <hr>

                <h4>Lista de Pesagens</h4>
                <label>Total de registros: {{ count($pesagens ?? []) }}</label>
                {{-- links de paginação --}}
                <div class="mt-3">
                    {{ $pesagens->links() }}
                </div>
                <div class="row">
                    <div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
                        <div class="table-responsive mt-3" style="max-height: 350px; overflow-y: auto; overflow-x: auto;">
                            <table class="table table-bordered table-hover table-pesagens">
                                <thead class="thead-light" style="position: sticky; top: -1; z-index: 1;">
                                <tr class="datatable-row" style="left: 0px;">
                                    <th style="width: 0%; white-space: nowrap;">ID</th>
                                    <th style="width: 0%; white-space: nowrap;">Identificação Única | UUID</th>
                                    <th style="width: 0%; white-space: nowrap;">Cliente / Fornecedor</th>
                                    <th style="width: 0%; white-space: nowrap;">Origem</th>
                                    <th style="width: 0%; white-space: nowrap;">Data / Hora</th>
                                    <th style="width: 0%; white-space: nowrap;">Veículo</th>
                                    <th style="width: 0%; white-space: nowrap;">Peso Liquido</th>
                                    <th style="width: 0%; white-space: nowrap;">Peso Bruto</th>
                                    <th style="width: 0%; white-space: nowrap;">Peso Final</th>
                                    <th style="width: 0%; white-space: nowrap;">Status</th>
                                    <th style="width: 0%; white-space: nowrap;">Ações</th>
                                </tr>
                                </thead>
                                <tbody id="body" class="datatable-body">
                                @forelse($pesagens as $pesagem)
                                    <tr class="datatable-row">
                                        <td class="datatable-cell" style="font-size: 12px;">{{ $pesagem->id }}</td>
                                        <td id="token-{{ $pesagem->id }}" class="datatable-cell" style="white-space: nowrap;">
                                            <span class="badge badge-fixed-width-token {{ $pesagem->status == 'concluído' ? 'badge-success' : 'badge-warning' }}" style="font-size: 11px;">
                                            {{ ucfirst($pesagem->token) }}
                                            </span>
                                            <style>
                                                .badge-fixed-width-token {
                                                    width: 240px;
                                                    display: inline-block;
                                                    text-align: center;
                                                    padding: 5px;
                                                }
                                            </style>
                                        </td>

                                        <td class="datatable-cell text-center" style="white-space: nowrap; font-size: 12px;">
                                            <span class="badge badge-fixed-width-cli {{ $pesagem->status == 'concluído' ? 'badge-success' : 'badge-warning' }}" style="font-size: 11px;">
                                            {{ $pesagem->tipo === 'compra'
                                                ? ($pesagem->fornecedor->razao_social ?? '–')
                                                : ($pesagem->cliente->razao_social   ?? '–')
                                            }}
                                            </span>
                                            <style>
                                                .badge-fixed-width-cli {
                                                    width: 300px;
                                                    display: inline-block;
                                                    text-align: center;
                                                    padding: 5px;
                                                }
                                            </style>

                                        </td>

                                        <td id="status-{{ $pesagem->id }}" class="datatable-cell" style="white-space: nowrap; font-size: 12px;">
                                            <span class="badge badge-fixed-width-finalidade {{ $pesagem->status == 'concluído' ? 'badge-success' : 'badge-warning' }}">
                                            {{ ucfirst($pesagem->tipo) }}
                                            </span>
                                            <style>
                                                .badge-fixed-width-finalidade {
                                                    width: 60px;
                                                    display: inline-block;
                                                    text-align: center;
                                                    padding: 5px;
                                                }
                                            </style>
                                        </td>
                                        <td class="datatable-cell text-center" style="white-space: nowrap; font-size: 12px;">{{ \Carbon\Carbon::parse($pesagem->created_at)->format('d/m/Y H:i') }}</td>

                                        <td id="veiculo-{{ $pesagem->id }}" class="datatable-cell text-center" style="white-space: nowrap; font-size: 12px;">
                                            <span class="badge {{ $pesagem->status == 'concluído' ? 'badge-success' : 'badge-warning' }}"
                                                  style="display: inline-block; padding: .35em .65em; font-size: 12px; line-height: 1.2; white-space: normal;">
                                                {{ $pesagem->veiculo->placa ?? 'N/A' }}
                                                @if(!empty($pesagem->placa_veiculo) && $pesagem->placa_veiculo !== $pesagem->veiculo->placa)
                                                    <br>
                                                    <small class="text-hover-dark" style="font-size: 12px; white-space: nowrap;">
                                                        {{ $pesagem->placa_veiculo }}
                                                        @if($pesagem->placa_carreta)
                                                            / {{ $pesagem->placa_carreta }}
                                                        @endif
                                                    </small>
                                                @elseif($pesagem->placa_carreta)
                                                    <br>
                                                    <small class="text-hover-dark" style="font-size: 12px; white-space: nowrap;">
                                                        {{ $pesagem->placa_carreta }}
                                                    </small>
                                                @endif
                                            </span>
                                        </td>

                                        <!--
                                        <td id="veiculo-{{ $pesagem->id }}" class="datatable-cell text-center" style="white-space: nowrap; font-size: 12px;">
                                            {{ $pesagem->veiculo->placa ?? 'N/A' }}

                                            @if(!empty($pesagem->placa_veiculo) && $pesagem->placa_veiculo !== $pesagem->veiculo->placa)
                                                <br>
                                                <small class="text-hover-dark" style="white-space: nowrap; font-size: 12px;">
                                                    {{ $pesagem->placa_veiculo }}
                                                    @if($pesagem->placa_carreta)
                                                        / {{ $pesagem->placa_carreta }}
                                                    @endif
                                                </small>
                                            @elseif($pesagem->placa_carreta)
                                                <br>
                                                <small class="text-hover-dark" style="white-space: nowrap; font-size: 12px;">
                                                    {{ $pesagem->placa_carreta }}
                                                </small>
                                            @endif
                                        </td>
                                        -->

                                        <!-- Peso Líquido (com visualização do desconto aplicado abaixo) -->
                                        <td id="peso-{{ $pesagem->id }}" class="datatable-cell text-center" style="white-space: nowrap; font-size: 12px;">
                                            @php
                                                $pesoLiquido = $pesagem->peso_liquido;
                                                $pesoFinal = $pesagem->peso_final_calculado;
                                                $desconto = max(0, $pesoLiquido - $pesoFinal);
                                            @endphp

                                            {{ number_format($pesoLiquido, 2, ',', '.') }} kg
                                            <br>
                                            <small class="text-hover-dark">
                                                - {{ number_format($desconto, 2, ',', '.') }} kg
                                            </small>
                                        </td>

                                        <!-- Peso Bruto -->
                                        <td id="pesoBruto-{{ $pesagem->id }}" class="datatable-cell text-center" style="white-space: nowrap; font-size: 12px;">
                                            {{ number_format($pesagem->peso_bruto, 2, ',', '.') }} kg
                                        </td>

                                        <!-- Peso Final (calculado dinâmico, mesmo antes de concluir) -->
                                        <td id="pesoFinal-{{ $pesagem->id }}" class="datatable-cell text-center" style="white-space: nowrap; font-size: 12px;">
                                            {{ number_format($pesoFinal, 2, ',', '.') }} kg
                                        </td>


                                        <!--
                                        <td id="peso-{{ $pesagem->id }}" class="datatable-cell text-center" style="white-space: nowrap; font-size: 12px;">{{ number_format($pesagem->peso, 2, ',', '.') }} kg</td>
                                        <td id="pesoBruto-{{ $pesagem->id }}" class="datatable-cell text-center" style="white-space: nowrap; font-size: 12px;">{{ number_format($pesagem->peso_liquido_bruto, 2, ',', '.') }} kg</td>
                                        <td id="pesoFinal-{{ $pesagem->id }}" class="datatable-cell text-center" style="white-space: nowrap; font-size: 12px;">{{ number_format($pesagem->peso_final, 2, ',', '.') }} kg</td>
                                        -->
                                        <td id="status-{{ $pesagem->id }}" class="datatable-cell" style="white-space: nowrap; font-size: 12px;">
                                            <span class="badge badge-fixed-width-status {{ $pesagem->status == 'concluído' ? 'badge-success' : 'badge-warning' }}">
                                                {{ ucfirst($pesagem->status) }}
                                            </span>
                                            <style>
                                                .badge-fixed-width-status {
                                                    width: 100px;
                                                    display: inline-block;
                                                    text-align: center;
                                                    padding: 5px;
                                                }
                                            </style>
                                        </td>
                                        <td class="datatable-cell text-center" style="white-space: nowrap;">
                                            @if($pesagem->status == 'em andamento')

                                                <!-- Botão para abrir o modal de tickets -->
                                                <button type="button" class="btn btn-info btn-sm btn-custom" data-toggle="modal" data-target="#modalTickets{{ $pesagem->id }}">
                                                    <i class="fa fa-ticket"></i> |  <i class="fa fa-balance-scale"></i>
                                                </button>

                                                <!-- Botão para abrir o modal de concluir -->
                                                <button type="button" class="btn btn-success btn-sm btn-custom btn-concluir-pesagem" data-id="{{ $pesagem->id }}">
                                                    <i class="fa fa-check-circle"></i>
                                                </button>

                                                <!-- Botão para editar a pesagem -->
                                                <button type="button" class="btn btn-warning btn-sm btn-custom btn-edit-pesagem" data-id="{{ $pesagem->id }}">
                                                    <i class="la la-edit"></i>
                                                </button>

                                                <!-- Botão para excluir a pesagem -->
                                                <button type="button" class="btn btn-danger btn-sm btn-custom btn-delete-pesagem" data-id="{{ $pesagem->id }}">
                                                    <i class="la la-trash"></i>
                                                </button>
                                            @endif

                                            <!-- Botão para imprimir a pesagem -->
                                            <div class="dropdown d-inline">
                                                <button class="btn btn-facebook btn-sm btn-custom dropdown-toggle" type="button" id="imprimirRelatorio{{ $pesagem->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="la la-print"></i> <span class="imprimir-icon-text">Ticket</span>
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="imprimirRelatorio{{ $pesagem->id }}">
                                                    <!-- <a class="dropdown-item" href="{{ url('/relatorios/pesagem/imprimir?pesagem_id=' . $pesagem->id . '&formato=a4') }}" target="_blank"> -->
                                                    <a class="dropdown-item" href="{{ url('/relatorios/pesagem/imprimirA4/' . $pesagem->id ) }}" target="_blank">
                                                        <i class="la la-print"></i>  A4
                                                    </a>
                                                    <a class="dropdown-item" href="{{ url('/relatorios/pesagem/imprimir80mm/' . $pesagem->id) }}" target="_blank">
                                                        <i class="la la-print"></i>  80mm
                                                    </a>
                                                    <a class="dropdown-item" href="{{ url('/relatorios/pesagem/imprimir80mmSimples/' . $pesagem->id) }}" target="_blank">
                                                        <i class="la la-print"></i>  80mm Simples
                                                    </a>
                                                </div>
                                            </div>
                                            <style>
                                                .btn-custom {
                                                    padding: 3px 8px; /* Define altura e largura proporcional */
                                                    font-size: 12px; /* Diminui o tamanho do texto */
                                                    line-height: 1.2; /* Ajusta a altura da linha para melhorar o alinhamento */
                                                }

                                                .imprimir-icon-text,
                                                .compra-icon-text,
                                                .venda-icon-text {
                                                    font-weight: normal;
                                                    font-size: 12px;
                                                    margin-left: -5px;
                                                    color: #FFFFFF;
                                                }

                                                .dropdown-menu a {
                                                    font-size: 12px;
                                                }
                                            </style>

                                            @if($pesagem->status == 'concluído')

                                                @if(
                                                        $pesagem->status === 'concluído'
                                                        && $hasOtp
                                                        && is_null($pesagem->compra_id)
                                                        && is_null($pesagem->venda_id)
                                                    )
                                                    <button
                                                        type="button"
                                                        class="btn btn-secondary btn-sm btn-custom btn-reabrir-pesagem"
                                                        data-id="{{ $pesagem->id }}"
                                                        data-token="{{ $pesagem->token }}"
                                                    >
                                                        <i class="fa fa-undo"></i> Reabrir
                                                    </button>
                                                @endif


                                                @if($pesagem->tipo == 'compra')
                                                    <!-- Botão para gerar compra da pesagem
                                                    <button type="button" class="btn btn-primary btn-sm btn-custom gerar-venda-pesagem" data-id="{{ $pesagem->id }}">
                                                    <i class="fa fa-shopping-cart"></i> <span class="compra-icon-text"> Gerar Compra</span>
                                                    </button>
                                                    -->
                                                @endif

                                                @if($pesagem->tipo == 'venda')
                                                    <!-- Botão para gerar venda da pesagem
                                                    <button type="button" class="btn btn-primary btn-sm btn-custom gerar-venda-pesagem" data-id="{{ $pesagem->id }}">
                                                    <i class="fa fa-shopping-bag"></i> <span class="venda-icon-text"> Gerar Venda</span>
                                                    </button>
                                                    -->
                                                @endif

                                                @if($pesagem->tipo == 'compra')
                                                    @if(is_null($pesagem->compra_id))
                                                        <!-- Botão para gerar compra da pesagem -->
                                                        <button type="button" class="btn btn-primary btn-sm btn-custom gerar-compra-pesagem" data-id="{{ $pesagem->id }}" data-token="{{ $pesagem->token }}">
                                                            <i class="fa fa-shopping-cart"></i> <span class="compra-icon-text"> Gerar Compra</span>
                                                        </button>
                                                    @else
                                                        <!-- Link para ver detalhes da compra -->
                                                        <a href="/compras/detalhes/{{ $pesagem->compra_id }}" class="btn btn-success btn-sm btn-custom">
                                                            <i class="fa fa-info-circle"></i>Compra #{{ $pesagem->compra_id }}
                                                        </a>
                                                    @endif
                                                @endif

                                                @if($pesagem->tipo == 'venda')
                                                    @if(is_null($pesagem->venda_id))
                                                        <!-- Botão para gerar venda da pesagem -->
                                                        <button type="button" class="btn btn-primary btn-sm btn-custom gerar-venda-pesagem"
                                                                data-id="{{ $pesagem->id }}"
                                                                data-token="{{ $pesagem->token }}">
                                                            <i class="fa fa-shopping-bag"></i> <span class="venda-icon-text"> Gerar Venda</span>
                                                        </button>
                                                    @else
                                                        <!-- Botão para redirecionar ao detalhe da venda -->
                                                        <a href="{{ url('/vendas/detalhar/' . $pesagem->venda_id) }}" class="btn btn-success btn-sm btn-custom">
                                                            <i class="fa fa-info-circle"></i>Venda #{{ $pesagem->venda_id }}
                                                        </a>
                                                    @endif
                                                @endif

                                            @endif

                                            @if($configSystemWhats && !empty($configSystemWhats->token_whatsapp))

                                            @endif

                                            <!-- Botão para abrir o modal de envio de WhatsApp -->
                                            <button type="button"
                                                    class="btn btn-{{ !$configSystemWhats || empty($configSystemWhats->token_whatsapp) ? 'secondary' : 'success' }} btn-sm btn-custom btn-enviar-whatsapp"
                                                    data-id="{{ $pesagem->id }}"
                                                    data-token="{{ $pesagem->token }}"
                                                    data-disabled="{{ !$configSystemWhats || empty($configSystemWhats->token_whatsapp) ? 'true' : 'false' }}">
                                                <i class="fa fa-whatsapp"></i>
                                            </button>

                                            <!-- Botão para publicar ou despublicar -->
                                            <button type="button"
                                                    class="btn btn-{{ $pesagem->view_public == 1 ? 'success' : 'secondary' }} btn-sm btn-custom btn-toggle-publicacao"
                                                    data-id="{{ $pesagem->id }}"
                                                    data-token="{{ $pesagem->token }}"
                                                    data-view-public="{{ $pesagem->view_public }}">
                                                <i class="la la-eye{{ $pesagem->view_public == 1 ? '' : '-slash' }}"></i>
                                                {{ $pesagem->view_public == 1 ? '' : '' }}
                                            </button>
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Nenhuma pesagem encontrada.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Unificado para Pesagem -->
    <div class="modal fade" id="modalPesagem" tabindex="-1" role="dialog" aria-labelledby="modalPesagemLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalPesagemLabel">Nova Pesagem</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <span class="badge badge-fixed-width-status" id="statusPesagem" style="width: 150px; height: 30px; padding: 6px 25px; font-size: 11px; display: inline-block; text-align: center; line-height: 18px;">
                    Status
                    </span>
                </div>
                <div class="row" style="margin: 5px 0; padding: 5px;">
                    <div style="font-size: 14px; line-height: 1.5; padding-left: 15px;">
                        <label style="font-weight: bold; margin-bottom: 2px;">Peso Líquido:</label>
                        <span id="pesoBrutoFinal" style="font-weight: normal;">0,00 kg</span>

                        <label style="font-weight: bold; margin-bottom: 2px;">Peso Bruto:</label>
                        <span id="pesoLiquidoBruto" style="font-weight: normal;">0,00 kg</span>

                        <label style="font-weight: bold; margin-bottom: 2px;">Peso Final:</label>
                        <span id="pesoFinal" style="font-weight: normal;">0,00 kg</span>
                    </div>
                </div>
                <div class="modal-body">
                    <form id="formPesagem" method="POST" action="/pesagens/save">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="pesagem_id" name="id" value="">
                        <input type="hidden" name="_method" id="_methodType" value="POST">

                        <!-- Tipo e Associado -->
                        <div class="row mb-2">
                            <!-- Tipo -->
                            <div class="form-group col-lg-2 mb-1">
                                <label for="tipo">Tipo:</label>
                                <select name="tipo" id="tipo" class="form-control" required>
                                    <option value="compra">Compra</option>
                                    <option value="venda">Venda</option>
                                </select>
                            </div>

                            <!-- Cliente/Fornecedor -->
                            <div class="form-group col-lg-10">
                                <!-- Cliente -->
                                <div id="cliente-container" class="form-group mb-1">
                                    <label>Cliente:</label>
                                    <div class="input-group">
                                        <!-- Cliente -->
                                        <select id="cliente_id" name="cliente_id" class="form-control" style="width: 90%;">
                                            <option value="">Selecione um cliente</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Fornecedor -->
                                <div id="fornecedor-container" class="form-group mb-1">
                                    <label>Fornecedor:</label>
                                    <br>
                                    <select id="fornecedor_id" name="fornecedor_id" class="form-control" style="width: 90%;">
                                        <option value="">Selecione um Fornecedor</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-1">
                            <div class="row mt-3" id="div-cliente" style="display: none;">
                                <div class="col-xl-12">
                                    <div class="card card-sm gutter-b" style="padding: 3px; border-radius: 5px;">
                                        <div class="card-body" style="padding: 3px;">
                                            <h6 style="font-size: 14px;">Razão Social: <strong id="razao_social" class="text-primary">--</strong></h6>
                                            <h6 style="font-size: 14px;">Nome Fantasia: <strong id="nome_fantasia" class="text-primary">--</strong></h6>
                                            <h6 style="font-size: 14px;">CPF/CNPJ: <strong id="cnpj" class="text-primary">--</strong></h6>
                                            <h6 style="font-size: 14px;">Fone: <strong id="fone" class="text-primary">--</strong></h6>
                                            <h6 style="font-size: 14px;">Cidade: <strong id="cidade" class="text-primary">--</strong></h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3" id="fornecedor" style="display: none;">
                                <div class="col-xl-12">
                                    <div class="card card-sm gutter-b" style="padding: 3px; border-radius: 5px;">
                                        <div class="card-body" style="padding: 3px;">
                                            <h6 style="font-size: 14px;">Razão Social: <strong id="razao_social_fornecedor" class="text-primary">--</strong></h6>
                                            <h6 style="font-size: 14px;">Nome Fantasia: <strong id="nome_fantasia_fornecedor" class="text-primary">--</strong></h6>
                                            <h6 style="font-size: 14px;">CPF/CNPJ: <strong id="cnpj_fornecedor" class="text-primary">--</strong></h6>
                                            <h6 style="font-size: 14px;">Fone: <strong id="fone_fornecedor" class="text-primary">--</strong></h6>
                                            <h6 style="font-size: 14px;">Cidade: <strong id="cidade_fornecedor" class="text-primary">--</strong></h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="row">
                            <!-- Veículo -->
                            <div class="form-group col-lg-4">
                                <label for="veiculo_id">Veículo:</label>
                                <br>
                                <!-- Veículo -->
                                <select id="veiculo_id" name="veiculo_id" class="form-control" style="width: 90%;">
                                    <option value="">Selecione um veículo</option>
                                </select>
                            </div>

                            <!-- Placa Veículo -->
                            <div class="form-group col-lg-4">
                                <label for="placa_veiculo">Placa Veículo:</label>
                                <input type="text" id="placa_veiculo" name="placa_veiculo" class="form-control">
                            </div>

                            <!-- Placa Carreta -->
                            <div class="form-group col-lg-4">
                                <label for="placa_carreta">Placa Carreta:</label>
                                <input type="text" id="placa_carreta" name="placa_carreta" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <!-- Motorista -->
                            <div class="form-group col-lg-6">
                                <label for="motorista_id">Motorista:</label>
                                <br>
                                <!-- Motorista -->
                                <select id="motorista_id" name="motorista_id" class="form-control" style="width: 90%;">
                                    <option value="">Selecione um motorista</option>
                                </select>
                            </div>

                            <!-- Nome Motorista -->
                            <div class="form-group col-lg-6">
                                <label for="motorista_nome">Nome Motorista:</label>
                                <input type="text" id="motorista_nome" name="motorista_nome" class="form-control">
                            </div>
                        </div>

                        <!-- Campos adicionais -->
                        <div class="row" style="margin: 0; padding: 0;">
                            <!-- Umidade -->
                            <div class="form-group col-md-1 col-sm-4 col-6" style="padding: 5px;">
                                <label for="umidade_desconto">Umid (%)</label>
                                <input type="number" id="umidade_desconto" name="umidade_desconto" step="0.01" class="form-control" value="0.00">
                            </div>
                            <!-- Impureza -->
                            <div class="form-group col-md-1 col-sm-4 col-6" style="padding: 5px;">
                                <label for="impureza_desconto">Impur (%)</label>
                                <input type="number" id="impureza_desconto" name="impureza_desconto" step="0.01" class="form-control" value="0.00">
                            </div>
                            <!-- Danificado -->
                            <div class="form-group col-md-2 col-sm-4 col-6" style="padding: 5px;">
                                <label for="danificado_desconto">Danificado (%)</label>
                                <div class="input-group">
                                    <input type="number" id="danificado_desconto" name="danificado_desconto" step="0.01" class="form-control" value="0.00">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <input type="checkbox" id="danificado" name="danificado">
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Quebrado -->
                            <div class="form-group col-md-2 col-sm-4 col-6" style="padding: 5px;">
                                <label for="quebrado_desconto">Quebrado (%)</label>
                                <div class="input-group">
                                    <input type="number" id="quebrado_desconto" name="quebrado_desconto" step="0.01" class="form-control" value="0.00">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <input type="checkbox" id="quebrado" name="quebrado">
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Esverdeado -->
                            <div class="form-group col-md-2 col-sm-4 col-6" style="padding: 5px;">
                                <label for="esverdeado_desconto">Esverdeado (%)</label>
                                <div class="input-group">
                                    <input type="number" id="esverdeado_desconto" name="esverdeado_desconto" step="0.01" class="form-control" value="0.00">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <input type="checkbox" id="esverdeado" name="esverdeado">
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Ardido -->
                            <div class="form-group col-md-2 col-sm-4 col-6" style="padding: 5px;">
                                <label for="ardido_desconto">Ardido (%)</label>
                                <div class="input-group">
                                    <input type="number" id="ardido_desconto" name="ardido_desconto" step="0.01" class="form-control" value="0.00">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <input type="checkbox" id="ardido" name="ardido">
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Secagem -->
                            <div class="form-group col-md-2 col-sm-4 col-6" style="padding: 5px;">
                                <label for="secagem_desconto">Secagem (%)</label>
                                <div class="input-group">
                                    <input type="number" id="secagem_desconto" name="secagem_desconto" step="0.01" class="form-control" value="0.00">
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <input type="checkbox" id="secagem" name="secagem">
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Observações -->
                        <div class="form-group">
                            <label for="observacoes">Observações:</label>
                            <textarea id="observacoes" name="observacoes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @foreach($pesagens as $pesagem)

        <!-- Modal Tickets -->
        <div class="modal fade" id="modalTickets{{ $pesagem->id }}" tabindex="-1" role="dialog" aria-labelledby="modalTicketsLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <!-- Cabeçalho -->
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalTicketsLabel">Tickets - Pesagem #{{ $pesagem->id }}</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <!-- Novo botão FECHAR -->
                        <button
                            type="button"
                            class="btn btn-secondary btn-sm btn-close-ticket ml-2"
                            title="Fechar">
                            <i class="fa fa-times"></i>Fechar
                        </button>
                    </div>

                    <!-- Corpo -->
                    <div class="modal-body">
                        <!-- Formulário para Novo/Editar Ticket -->
                        <form id="formTicket" method="POST" action="{{ route('ticketsPesagem.save') }}">
                            @csrf
                            <input type="hidden" id="ticket_id" name="id"> <!-- ID para edição -->
                            <input type="hidden" name="_method" id="_method" value="POST"> <!-- POST ou PUT -->

                            <!-- Campos -->
                            <input type="hidden" name="pesagem_id" value="{{ $pesagem->id }}">
                            <input type="hidden" name="veiculo_id" value="{{ $pesagem->veiculo_id }}">
                            <input type="hidden" name="motorista_id" value="{{ $pesagem->motorista_id ?? '' }}">
                            <input type="hidden" name="token" id="token">

                            <div class="row">
                                <div class="form-group col-lg-6">
                                    <label for="balanca-select">Balança:</label>
                                    <div class="input-group">
                                        <!-- Select de balanças -->
                                        <select id="balanca-select" class="form-control">
                                            <option value="">Selecione</option>
                                            @foreach($balancas as $balanca)
                                                <option value="{{ $balanca->id }}"
                                                        data-backend="{{ $balanca->backend_server_address }}"
                                                        data-modelo="{{ $balanca->modelo }}"
                                                        data-port="{{ $balanca->port }}">
                                                    {{ $balanca->descricao }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <!-- Botões -->
                                        <div class="input-group-append">
                                            <button id="connect" type="button" class="btn btn-success btn-sm" disabled>Conectar</button>
                                            <button id="disconnect" type="button" class="btn btn-danger btn-sm" disabled>Desconectar</button>
                                        </div>
                                    </div>

                                </div>
                                <div class="form-group col-lg-6">
                                    <div class="mt-2">
                                        <label >Pesagem / Estabilidade:</label> <span>   </span> <strong><span id="pesoEstabilidade"></span></strong>
                                        <br>
                                        <strong>Peso Bruto:</strong> <span id="pesoBruto">----</span> kg
                                        <span>   </span> <strong>Tara:</strong> <span id="pesoTara">----</span> kg
                                        <span>   </span> <strong>Peso Líquido:</strong> <span id="pesoLiquido">----</span> kg
                                    </div>
                                </div>
                                <!-- Dentro de cada modalTickets... -->
                                <div class="form-group col-lg-6">
                                    <label for="produto_id">Produto:</label>
                                    <div class="input-group" style="width:100%;">
                                        <select name="produto_id" id="produto_id" class="form-control">
                                            <option value="">Selecione</option>
                                            @foreach($produtos as $produto)
                                                <option value="{{ $produto->id }}">{{ $produto->nome }}</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger btn-sm btn-clear-produto" title="Limpar seleção">
                                                <i class="fa fa-times"></i>
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm btn-fixar-produto" title="Fixar produto">
                                                <i class="fa fa-thumb-tack"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>


                                <!-- Peso -->
                                <div class="form-group col-lg-6">
                                    <div class="row">
                                        <div class="form-group col-md-6 col-sm-12">
                                            <label for="peso">Peso (kg):</label>
                                            <input type="number" name="peso" id="peso" step="0.01" class="form-control"  value="0.00" required>
                                        </div>

                                        <div class="form-group col-md-6 col-sm-12">
                                            <label for="peso_bag">Peso dos Recipientes (kg):</label>
                                            <input type="number" name="peso_bag" id="peso_bag" step="0.01" class="form-control" value="0.00">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-lg-6">
                                    <label for="inicio">Início:</label>
                                    <input type="datetime-local" name="inicio" id="inicio" class="form-control" step="1">
                                    <label for="fim">Fim:</label>
                                    <input type="datetime-local" name="fim"    id="fim"    class="form-control" step="1">
                                </div>

                                <div class="form-group col-lg-6">
                                    <label for="tipo">Tipo:</label>
                                    <select name="tipo" id="tipo" class="form-control" required>
                                        <option value="entrada">Entrada</option>
                                        <option value="saida">Saída</option>
                                        <option value="avulsa">Avulsa</option>
                                    </select>
                                    <label for="status">Status:</label>
                                    <select name="status" id="status" class="form-control" required>
                                        <option value="em andamento">Em Andamento</option>
                                        <option value="concluído">Concluído</option>
                                    </select>
                                </div>

                                <div class="form-group col-lg-12">
                                    <label for="observacoes">Observações:</label>
                                    <textarea name="observacoes" id="observacoes" class="form-control" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="form-group col-lg-12 text-right">
                                <button type="button" class="btn btn-secondary" id="btnCancelarTicket">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Salvar Ticket</button>
                            </div>

                        </form>

                        <!-- Lista de Tickets Existentes -->
                        <hr>
                        <div class="table-responsive mt-3" style="max-height: 200px; overflow-y: auto; overflow-x: auto;">
                            <table class="table table-bordered table-striped table-hover table-tickets">
                                <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th style="width: 5%; white-space: nowrap;">ID</th>
                                    <th style="width: 20%; white-space: nowrap;">Produto</th>
                                    <th style="width: 10%; white-space: nowrap;">Tipo</th>
                                    <th style="width: 10%; white-space: nowrap;">Peso</th>
                                    <th style="width: 10%; white-space: nowrap;">Peso Recip</th>
                                    <th style="width: 10%; white-space: nowrap;">Status</th>
                                    <th style="width: 15%; white-space: nowrap;">Início</th>
                                    <th style="width: 15%; white-space: nowrap;">Fim</th>
                                    <th style="width: 15%; white-space: nowrap;">Ações</th>
                                </tr>
                                </thead>
                                <tbody id="listaTickets">
                                @forelse($pesagem->tickets as $ticket)
                                    <tr id="ticket-{{ $ticket->id }}" class="datatable-row">
                                        <td class="datatable-cell">{{ $ticket->id }}</td>
                                        <td id="produto-{{ $ticket->id }}" class="datatable-cell">{{ $ticket->produto->nome ?? 'N/A' }}</td>
                                        <td id="tipo-{{ $ticket->id }}" class="datatable-cell">{{ ucfirst($ticket->tipo) }}</td>
                                        <td id="peso-{{ $ticket->id }}" class="datatable-cell">{{ number_format($ticket->peso, 2, ',', '.') }} kg</td>
                                        <td id="peso-{{ $ticket->id }}" class="datatable-cell">{{ number_format($ticket->peso_bag, 2, ',', '.') }} kg</td>
                                        <td id="status-{{ $ticket->id }}" class="datatable-cell">
                                            <span class="badge badge-fixed-width-status {{ $ticket->status == 'concluído' ? 'badge-success' : 'badge-warning' }}">
                                                {{ ucfirst($ticket->status) }}
                                            </span>
                                        </td>
                                        <td id="inicio-{{ $ticket->id }}" class="datatable-cell">{{ $ticket->inicio ? \Carbon\Carbon::parse($ticket->inicio)->format('d/m/Y H:i') : 'N/A' }}</td>
                                        <td id="fim-{{ $ticket->id }}" class="datatable-cell">{{ $ticket->fim ? \Carbon\Carbon::parse($ticket->fim)->format('d/m/Y H:i') : 'N/A' }}</td>
                                        <td class="datatable-cell text-center">
                                            <!-- Botão Editar -->
                                            <button type="button" class="btn btn-warning btn-sm btn-custom edit-ticket" data-id="{{ $ticket->id }}">
                                                <i class="la la-edit"></i>
                                            </button>
                                            <!-- Botão Excluir -->
                                            <button type="button" class="btn btn-danger btn-sm btn-custom delete-ticket" data-id="{{ $ticket->id }}">
                                                <i class="la la-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Nenhum ticket encontrado.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Concluir -->
        <div class="modal fade" id="modalConcluir{{ $pesagem->id }}" tabindex="-1" role="dialog" aria-labelledby="modalConcluirLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalConcluirLabel">Concluir Pesagem #{{ $pesagem->id }}</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('pesagens.concluir', $pesagem->id) }}">
                            @csrf
                            <input type="hidden" name="pesagem_id" value="{{ $pesagem->id }}">
                            <div class="form-group">
                                <label for="peso">Peso Final (kg):</label>
                                <input type="number" name="peso" step="0.01" class="form-control" value="{{ $pesagem->peso }}" required>
                                <button type="button" class="btn btn-light btn-sm mt-2">
                                    <i class="fa fa-balance-scale"></i> Ler Peso
                                </button>
                            </div>
                            <div class="form-group">
                                <label for="observacoes">Observações:</label>
                                <textarea name="observacoes" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Concluir</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Modal Reutilizável -->
    <div class="modal fade" id="modalConfirmacao" tabindex="-1" role="dialog" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- Cabeçalho -->
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmação</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Corpo -->
                <div class="modal-body" id="modalConfirmacaoMensagem">
                    Deseja realmente continuar?
                </div>
                <!-- Rodapé -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Não</button>
                    <button type="button" id="btnConfirmarAcao" class="btn btn-primary">Sim</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reutilizável para Alertas e Mensagens -->
    <div class="modal fade" id="modalMensagem" tabindex="-1" role="dialog" aria-labelledby="modalMensagemLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <!-- Cabeçalho -->
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMensagemLabel">Mensagem</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Corpo -->
                <div class="modal-body" id="modalMensagemTexto">
                    Mensagem exibida aqui.
                </div>
                <!-- Rodapé -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Envio de WhatsApp -->
    <div class="modal fade" id="modalWhatsApp" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form class="modal-content" method="post" action="/pesagens/enviar-relatorio-whats">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalWhatsAppLabel">Enviar WhatsApp</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">x</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group validated col-sm-6 col-lg-6">
                            <label class="col-form-label">WhatsApp</label>
                            <input required type="text" id="celular" name="celular" class="form-control" placeholder="Número com DDD">
                        </div>
                        <input type="hidden" name="id_pesagem" id="id_pesagem">

                        <div class="form-group validated col-lg-12 col-lg-12">
                            <label class="col-form-label">Texto Adicional (Opcional)</label>
                            <textarea id="texto" name="texto" class="form-control" rows="3" placeholder="Texto adicional para WhatsApp"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <p>Selecionar anexos para envio:</p>
                        </div>
                        <div class="form-group col-lg-4 col-md-6">
                            <input type="checkbox" name="mensagem_padrao" checked> Detalhamento da Pesagem
                        </div>
                        <div class="form-group col-lg-4 col-md-6">
                            <input type="checkbox" name="relatorio_80mm" checked> Ticket 80mm
                        </div>
                        <div class="form-group col-lg-4 col-md-6">
                            <input type="checkbox" name="relatorio_a4"> Ticket A4
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-light-success font-weight-bold">Enviar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de validação OTP p/ Reabrir Pesagem -->
    <div class="modal fade" id="reabrirOtpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirme o código OTP</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <input
                        type="text"
                        id="reabrir-otp-code"
                        class="form-control mb-2"
                        placeholder="000000"
                        maxlength="6"
                    >
                    <div id="reabrir-otp-error" class="text-danger small" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button id="btn-reabrir-validate" class="btn btn-primary">Validar e Reabrir</button>
                    <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('javascript')

{{--    Controle de Carregamento de Scripts     --}}
    <script src="{{ asset('js/axios.min.js') }}"></script>

{{--    Controle de Pesagens    --}}
    <script type="text/javascript">

        //Controle Principal de Pesagens
        $(document).ready(function () {
            // Função para abrir o modal de nova pesagem
            $('#btnNovaPesagem').on('click', function () {
                resetForm('#modalPesagem'); // Reseta o formulário
                $('#formPesagem')[0].reset(); // Reseta o formulário
                $('#modalPesagemLabel').text('Nova Pesagem'); // Define o título do modal
                $('#statusPesagem').text('');
                $('#statusPesagem').removeClass('badge-success badge-warning');
                $('#formPesagem').attr('action', '/pesagens/save'); // Define a rota de criação
                $('#_methodType').val('POST'); // Método POST para criação
                $('#modalPesagem').modal('show'); // Abre o modal
                $('#cliente_id').val(null).trigger('change'); // Reseta o cliente
                $('#div-cliente').hide();
                $('#fornecedor_id').val(null).trigger('change'); // Reseta o fornecedor
                $('#fornecedor').hide();
            });

            // Função para abrir o modal de edição de pesagem
            $(document).on('click', '.btn-edit-pesagem', function () {
                const pesagemId = $(this).data('id');

                resetForm('#modalPesagem');

                $.get(`/pesagens/edit/${pesagemId}`, function (data) {

                    // Configura o tipo (compra/venda) e força o evento "change"
                    $('#tipo').val(data.tipo).trigger('change');

                    // Define os dados do cliente no select2
                    if (data.cliente_id) {
                        const clienteOption = new Option(
                            `${data.cliente.razao_social} | ${data.cliente.cpf_cnpj}`,
                            data.cliente_id,
                            true, // Selecionado
                            true  // Adicionado ao select
                        );
                        $('#cliente_id').append(clienteOption).trigger('change'); // Atualiza o select2
                    }

                    // Define os dados do fornecedor no select2
                    if (data.fornecedor_id) {
                        const fornecedorOption = new Option(
                            `${data.fornecedor.razao_social} | ${data.fornecedor.cpf_cnpj}`,
                            data.fornecedor_id,
                            true,
                            true
                        );
                        $('#fornecedor_id').append(fornecedorOption).trigger('change');
                    }

                    // Define os dados do veículo no select2
                    if (data.veiculo_id) {
                        const veiculoOption = new Option(
                            `${data.veiculo.placa} | ${data.veiculo.marcaDescricao} | ${data.veiculo.modeloDescricao}`,
                            data.veiculo_id,
                            true, // Selecionado
                            true  // Adicionado ao select
                        );
                        $('#veiculo_id').append(veiculoOption).trigger('change');
                    }

                    // Define os dados do motorista no select2
                    if (data.motorista_id) {
                        const motoristaOption = new Option(
                            `${data.motorista.nome} | ${data.motorista.cpf}`,
                            data.motorista_id,
                            true, // Selecionado
                            true  // Adicionado ao select
                        );
                        $('#motorista_id').append(motoristaOption).trigger('change');
                    }

                    // Atualiza os valores do modal
                    $('#pesoBrutoFinal').text(`${parseFloat(data.peso ?? 0).toFixed(2).replace('.', ',')} kg`);
                    $('#pesoLiquidoBruto').text(`${parseFloat(data.peso_liquido ?? 0).toFixed(2).replace('.', ',')} kg`);
                    $('#pesoFinal').text(`${parseFloat(data.peso_final ?? 0).toFixed(2).replace('.', ',')} kg`);

                    // Atualiza o status dinamicamente
                    const statusBadge = $('#statusPesagem');
                    statusBadge.text(data.status.charAt(0).toUpperCase() + data.status.slice(1));
                    statusBadge.removeClass('badge-success badge-warning');
                    if (data.status === 'concluído') {
                        statusBadge.addClass('badge-success');
                    }
                    if (data.status === 'em andamento') {
                        statusBadge.addClass('badge-warning');
                    }

                    $('#modalPesagemLabel').text(`Editar Pesagem #${pesagemId}`);
                    $('#pesagem_id').val(data.id);
                    $('#peso').val(data.peso);
                    $('#peso_liquido').val(data.peso_liquido);
                    $('#peso_final').val(data.peso_final);
                    $('#status').val(data.status);
                    $('#tipo').val(data.tipo).trigger('change'); // Define o tipo e dispara o evento
                    $('#cliente_id').val(data.cliente_id).trigger('change'); // Define o cliente
                    $('#fornecedor_id').val(data.fornecedor_id).trigger('change'); // Define o fornecedor
                    $('#veiculo_id').val(data.veiculo_id).trigger('change'); // Define o veículo
                    $('#placa_veiculo').val(data.placa_veiculo);
                    $('#placa_carreta').val(data.placa_carreta);
                    $('#motorista_id').val(data.motorista_id).trigger('change'); // Define o motorista
                    $('#motorista_nome').val(data.motorista_nome || '--');

                    // Atualiza os checkboxes com base nos valores recebidos
                    $('#danificado').prop('checked', data.danificado === 1).trigger('change');
                    $('#quebrado').prop('checked', data.quebrado === 1).trigger('change');
                    $('#esverdeado').prop('checked', data.esverdeado === 1).trigger('change');
                    $('#ardido').prop('checked', data.ardido === 1).trigger('change');
                    $('#secagem').prop('checked', data.secagem === 1).trigger('change');

                    // Atualiza os valores nos campos de desconto
                    $('#danificado_desconto').val(data.danificado_desconto || '0.00');
                    $('#quebrado_desconto').val(data.quebrado_desconto || '0.00');
                    $('#esverdeado_desconto').val(data.esverdeado_desconto || '0.00');
                    $('#ardido_desconto').val(data.ardido_desconto || '0.00');
                    $('#secagem_desconto').val(data.secagem_desconto || '0.00');

                    $('#observacoes').val(data.observacoes);

                    // Exibe os detalhes do cliente ou fornecedor
                    if (data.tipo === 'compra') {
                        $('#div-cliente').hide();
                        $('#fornecedor').show();
                        $('#razao_social_fornecedor').text(data.fornecedor.razao_social || '--');
                        $('#nome_fantasia_fornecedor').text(data.fornecedor.nome_fantasia || '--');
                        $('#cnpj_fornecedor').text(data.fornecedor.cpf_cnpj || '--');
                        $('#fone_fornecedor').text(data.fornecedor.telefone || '--');
                        $('#cidade_fornecedor').text(data.fornecedor.cidade || '--');
                    } else {
                        $('#fornecedor').hide();
                        $('#div-cliente').show();
                        $('#razao_social').text(data.cliente.razao_social || '--');
                        $('#nome_fantasia').text(data.cliente.nome_fantasia || '--');
                        $('#cnpj').text(data.cliente.cpf_cnpj || '--');
                        $('#fone').text(data.cliente.telefone || '--');
                        $('#cidade').text(data.cliente.cidade || '--');
                    }

                    $('#formPesagem').attr('action', `/pesagens/update/${data.id}`);
                    $('#_methodType').val('PUT');
                    $('#modalPesagem').modal('show');
                }).fail(function () {
                    abrirModalMensagem('Erro', 'Erro ao carregar os dados da pesagem.');
                });
            });

            // Função para resetar o formulário
            function resetForm(modalSelector) {
                const modal = $(modalSelector);

                if (modal.length === 0) {
                    console.error(`Modal não encontrado: ${modalSelector}`);
                    return; // Interrompe se o modal não existe
                }

                const form = modal.find('#formPesagem');
                if (form.length > 0) {
                    form[0].reset(); // Reseta o formulário
                } else {
                    console.warn('Formulário não encontrado no modal.');
                }

                modal.find('#modalPesagemLabel').text('Nova Pesagem'); // Define o título do modal
                modal.find('#pesagem_id').val(''); // Reseta o campo ID
                modal.find('#_methodType').val('POST'); // Método POST padrão
                modal.find('#formPesagem').attr('action', '/pesagens/save'); // Define a rota padrão de criação

                // Reseta os campos de peso
                modal.find('#pesoBrutoFinal').text('0,00 kg');
                modal.find('#pesoLiquidoBruto').text('0,00 kg');
                modal.find('#pesoFinal').text('0,00 kg');

                // Oculta seções opcionais
                modal.find('#div-cliente').hide(); // Oculta detalhes do cliente
                modal.find('#fornecedor').hide(); // Oculta detalhes do fornecedor

                // Reseta os selects
                modal.find('#cliente_id').val('').trigger('change');
                modal.find('#fornecedor_id').val('').trigger('change');
                modal.find('#veiculo_id').val('').trigger('change');
                modal.find('#motorista_id').val('').trigger('change');
            }

            // Função para cancelar a edição/inserção
            $('#modalPesagem').on('hidden.bs.modal', function () {
                resetForm('#modalPesagem'); // Reseta o formulário ao fechar o modal
            });

            // Verifica se há um modal de tickets para abrir após criar a pesagem
            @if(session('openTicketModal'))
            $('#modalTickets{{ session('openTicketModal') }}').modal('show'); // Abre o modal automaticamente
            @endif

            $('#formPesagem').on('submit', function (e) {
                e.preventDefault(); // Evita o comportamento padrão do formulário

                const form = $(this);
                const action = form.attr('action'); // URL do formulário
                const method = 'POST'; // Método HTTP (POST ou PUT)
                const formData = form.serialize(); // Serializa os dados do formulário

                // Realiza a requisição AJAX
                $.ajax({
                    url: action,
                    type: method,
                    data: formData,
                    success: function (response) {
                        // Fecha o modal e exibe uma mensagem de sucesso
                        $('#modalPesagem').modal('hide');
                        abrirModalMensagem('Sucesso', 'Pesagem salva com sucesso!');

                        // Atualiza a tabela ou recarrega a página
                        location.reload();
                    },
                    error: function (xhr) {
                        // Exibe mensagem de erro no caso de falha
                        const errors = xhr.responseJSON?.errors || { message: 'Erro ao salvar a pesagem.' };
                        let errorMessage = '<ul>';
                        for (const field in errors) {
                            errorMessage += `<li>${errors[field]}</li>`;
                        }
                        errorMessage += '</ul>';

                        abrirModalMensagem('Erro', errorMessage);
                    },
                });
            });

            // Função para excluir uma pesagem
            $(document).on('click', '.btn-delete-pesagem', function () {
                const id = $(this).data('id');
                abrirModalConfirmacao('Deseja realmente excluir esta pesagem?<br><br>Esta ação não poderá ser desfeita!', function () {
                    $.ajax({
                        url: `/pesagens/delete/${id}`,
                        type: 'DELETE',
                        data: { _token: $('meta[name="csrf-token"]').attr('content') },
                        success: function (response) {
                            abrirModalMensagem('Sucesso', 'Pesagem excluída com sucesso!');
                            location.reload();
                        },
                        error: function () {
                            abrirModalMensagem('Erro', 'Erro ao excluir a pesagem.');
                        }
                    });
                });
            });

            // Configura padrão inicial
            $('#tipo').val('venda').change();

            // Alternar entre cliente e fornecedor com base no tipo
            $('#tipo').on('change', function () {
                const tipo = $(this).val();

                const isEditing = $('#pesagem_id').val() !== ''; // Verifica se está no modo de edição

                if (!isEditing) {
                    // Resetar apenas em modo de inserção
                    $('#cliente_id').val(null).trigger('change'); // Reseta o cliente
                    $('#div-cliente').hide();
                    $('#fornecedor_id').val(null).trigger('change'); // Reseta o fornecedor
                    $('#fornecedor').hide();
                }

                if (tipo === 'compra') {
                    $('#div-cliente').hide();
                    $('#cliente-container').hide();
                    $('#fornecedor-container').show();
                    $('#cliente_id').removeAttr('name');
                    $('#fornecedor_id').attr('name', 'fornecedor_id');
                } else {
                    $('#fornecedor').hide();
                    $('#fornecedor-container').hide();
                    $('#cliente-container').show();
                    $('#fornecedor_id').removeAttr('name');
                    $('#cliente_id').attr('name', 'cliente_id');
                }
            });

            // Forçar a execução do evento change ao abrir o modal
            $('#modalPesagem').on('shown.bs.modal', function () {
                $('#tipo').trigger('change');
            });

            // Atualizar detalhes do cliente selecionado
            $('#cliente_id').on('change', function () {
                let selectedData = $(this).select2('data')[0]; // Obtém os dados da opção selecionada
                if (selectedData) {
                    $('#div-cliente').show();
                    $('#razao_social').text(selectedData.razao_social || '--');
                    $('#nome_fantasia').text(selectedData.nome_fantasia || '--');
                    $('#cnpj').text(selectedData.cpf_cnpj || '--');
                    $('#fone').text(selectedData.telefone || '--');
                    $('#cidade').text(selectedData.cidade || '--');
                } else {
                    $('#div-cliente').hide(); // Oculta os detalhes se não houver seleção
                }
            });

            // Atualizar detalhes do fornecedor selecionado
            $('#fornecedor_id').on('change', function () {
                let selectedData = $(this).select2('data')[0]; // Obtém os dados da opção selecionada
                if (selectedData) {
                    $('#fornecedor').show();
                    $('#razao_social_fornecedor').text(selectedData.razao_social || '--');
                    $('#nome_fantasia_fornecedor').text(selectedData.nome_fantasia || '--');
                    $('#cnpj_fornecedor').text(selectedData.cpf_cnpj || '--');
                    $('#fone_fornecedor').text(selectedData.telefone || '--');
                    $('#cidade_fornecedor').text(selectedData.cidade || '--');
                } else {
                    $('#fornecedor').hide(); // Oculta os detalhes se não houver seleção
                }
            });

            // Preenche placa automaticamente ao selecionar o veículo
            $('#veiculo_id').on('change', function () {
                let selectedData = $(this).select2('data')[0]; // Obtém os dados da opção selecionada
                if (selectedData) {
                    $('#placa_veiculo').val('');
                    $('#placa_veiculo').val(selectedData.placa); // Usa a field "placa"
                }
            });

            // Preenche nome do motorista ao selecionar
            $('#motorista_id').on('change', function () {
                let selectedData = $(this).select2('data')[0]; // Obtém os dados da opção selecionada
                if (selectedData) {
                    $('#motorista_nome').val('');
                    $('#motorista_nome').val(selectedData.nome); // Usa a field "nome"
                }
            });

            // Evento de clique para concluir pesagem
            $(document).on('click', '.btn-concluir-pesagem', function () {
                let pesagemId = $(this).data('id'); // Obtém o ID da pesagem

                // Chama a função para abrir o modal de confirmação
                abrirModalConfirmacao(
                    'Deseja realmente concluir esta pesagem?<br><br>Esta ação não poderá ser desfeita.',
                    function () {
                        // Realiza a requisição AJAX após a confirmação
                        $.ajax({
                            url: `/pesagens/concluir/${pesagemId}`, // Rota para concluir
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'), // Token CSRF seguro
                            },
                            success: function (response) {
                                if (response.success) {
                                    abrirModalMensagem('Sucesso', response.success); // Exibe mensagem de sucesso no modal
                                    setTimeout(() => location.reload(), 2000); // Aguarda 2 segundos antes de recarregar
                                } else {
                                    abrirModalMensagem('Erro', response.error || 'Erro ao concluir a pesagem.'); // Exibe mensagem de erro
                                }
                            },
                            error: function () {
                                abrirModalMensagem('Erro', 'Erro ao concluir a pesagem.'); // Mensagem de erro genérica
                            }
                        });
                    }
                );
            });

            $(document).on('click', '.imprimir-relatorio', function () {
                const pesagemId = $(this).data('id'); // Obtém o ID da pesagem
                const url = `/relatorios/pesagem/imprimirA4/${pesagemId}`; // Rota para gerar o relatório
                window.open(url, '_blank'); // Abre o relatório em uma nova aba
            });

            // Função para abrir o modal de confirmação
            function abrirModalConfirmacao(mensagem, acao) {
                $('#modalConfirmacaoMensagem').html(mensagem); // Define a mensagem no modal
                $('#modalConfirmacao').modal('show'); // Exibe o modal de confirmação

                $('#btnConfirmarAcao').off('click').on('click', function () {
                    acao(); // Executa a ação de confirmação
                    $('#modalConfirmacao').modal('hide'); // Fecha o modal
                });
            }

            // Modal de Mensagem/Aviso
            function abrirModalMensagem(titulo, mensagem) {
                $('#modalMensagemLabel').text(titulo);
                $('#modalMensagemTexto').html(mensagem);
                $('#modalMensagem').modal('show');
            }
        });

        //Selects de Pesagens
        $(document).ready(function () {
            // CSS para o botão de limpeza acoplado
            const clearButtonCSS = `
                                        <style>
                                            .clear-button {
                                                background-color: white;
                                                color: black;
                                                border: none;
                                                font-size: 14px;
                                                padding: 5px 5px;
                                                cursor: pointer;
                                                margin-left: 0px;
                                                border-radius: 0px;
                                            }
                                        </style>
                                    `;
            $('head').append(clearButtonCSS);

            // Função para adicionar o botão de limpeza
            function addClearButton(selectId, clearFunction) {
                const $selectContainer = $(`${selectId}`).next('.select2-container');
                $selectContainer.after(`
                                          <button type="button" class="clear-button" data-select="${selectId}">✖</button>
                                      `);

                $(document).on('click', `.clear-button[data-select="${selectId}"]`, function () {
                    clearFunction();
                });
            }

            // Select Veículo
            $('#veiculo_id').select2({
                placeholder: 'Selecione um veículo',
                ajax: {
                    url: '/pesagens/search/veiculo',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ term: params.term }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.text,
                            placa: item.placa,
                            marcaDescricao: item.marcaDescricao,
                            modeloDescricao: item.modeloDescricao,
                            motoristaNome: item.motoristaNome,
                            img: item.imgApp,
                            quilometragem: item.quilometragem,
                        })),
                    }),
                },
                templateResult: formatVeiculo,
                templateSelection: formatVeiculoSelection,
            });

            addClearButton('#veiculo_id', () => {
                $('#veiculo_id').val(null).trigger('change');
                $('#placa_veiculo').val('');
            });

            function formatVeiculo(item) {
                if (!item.id) return item.text;
                const img = item.img || '/imgs/no_veiculos.png';
                return $(`
                            <div style="display: flex; align-items: center;">
                                <img src="${img}" alt="Imagem do veículo" style="width: 40px; height: 40px; margin-right: 10px; border-radius: 50%;">
                                <div>
                                    <div><strong>${item.text}</strong></div>
                                    <small>Placa: ${item.placa} | Quilometragem: ${item.quilometragem}</small>
                                </div>
                            </div>
                        `);
            }

            function formatVeiculoSelection(item) {
                return item.text || 'Selecione um veículo';
            }

            // Select Cliente
            $('#cliente_id').select2({
                placeholder: 'Selecione um cliente',
                ajax: {
                    url: '/pesagens/search/cliente',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ term: params.term }),
                    processResults: data => ({
                        results: data.results.map(item => ({
                            id: item.id,
                            text: item.text,
                            razao_social: item.razao_social,
                            nome_fantasia: item.nome_fantasia,
                            cpf_cnpj: item.cpf_cnpj,
                            telefone: item.telefone,
                            cidade: item.cidade,
                            img: item.imgApp,
                        })),
                    }),
                },
                templateResult: formatCliente,
                templateSelection: formatClienteSelection,
            });

            addClearButton('#cliente_id', () => {
                $('#cliente_id').val(null).trigger('change');
                $('#div-cliente').hide();
            });

            function formatCliente(item) {
                if (!item.id) return item.text;
                const img = item.img || '/imgs/no_clientes.png';
                return $(`
                            <div style="display: flex; align-items: center;">
                                <img src="${img}" alt="Foto do cliente" style="width: 40px; height: 40px; margin-right: 10px; border-radius: 50%;">
                                <div>
                                    <div><strong>${item.razao_social}</strong></div>
                                    <small>CPF/CNPJ: ${item.cpf_cnpj} | Telefone: ${item.telefone}</small>
                                </div>
                            </div>
                        `);
            }

            function formatClienteSelection(item) {
                return item.text || 'Selecione um cliente';
            }

            // Select Fornecedor
            $('#fornecedor_id').select2({
                placeholder: 'Selecione um fornecedor',
                ajax: {
                    url: '/pesagens/search/fornecedor',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ term: params.term }),
                    processResults: data => ({
                        results: data.map(item => ({
                            id: item.id,
                            text: item.text,
                            razao_social: item.razao_social,
                            nome_fantasia: item.nome_fantasia,
                            cpf_cnpj: item.cpf_cnpj,
                            telefone: item.telefone,
                            cidade: item.cidade,
                            img: item.imgApp,
                        })),
                    }),
                },
                templateResult: formatFornecedor,
                templateSelection: formatFornecedorSelection,
            });

            addClearButton('#fornecedor_id', () => {
                $('#fornecedor_id').val(null).trigger('change');
                $('#fornecedor').hide();
            });

            function formatFornecedor(item) {
                if (!item.id) return item.text;
                const img = item.img || '/imgs/no_fornecedores.png';
                return $(`
                            <div style="display: flex; align-items: center;">
                                <img src="${img}" alt="Foto do fornecedor" style="width: 40px; height: 40px; margin-right: 10px; border-radius: 50%;">
                                <div>
                                    <div><strong>${item.razao_social}</strong></div>
                                    <small>CPF/CNPJ: ${item.cpf_cnpj} | Telefone: ${item.telefone}</small>
                                </div>
                            </div>
                        `);
            }

            function formatFornecedorSelection(item) {
                return item.text || 'Selecione um fornecedor';
            }

            // Select Motorista
            $('#motorista_id').select2({
                placeholder: 'Selecione um motorista',
                ajax: {
                    url: '/pesagens/search/motorista',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ term: params.term }),
                    processResults: data => ({
                        results: data.results.map(item => ({
                            id: item.id,
                            text: item.text,
                            nome: item.nome,
                            cpf: item.cpf,
                            cnh: item.cnh,
                            img: item.imgApp,
                        })),
                    }),
                },
                templateResult: formatMotorista,
                templateSelection: formatMotoristaSelection,
            });

            addClearButton('#motorista_id', () => {
                $('#motorista_id').val(null).trigger('change');
                $('#motorista_nome').val('');
            });

            function formatMotorista(item) {
                if (!item.id) return item.text;
                const img = item.img || '/imgs/no_funcionarios.png';
                return $(`
                            <div style="display: flex; align-items: center;">
                                <img src="${img}" alt="Foto do motorista" style="width: 40px; height: 40px; margin-right: 10px; border-radius: 50%;">
                                <div>${item.nome} | CPF: ${item.cpf}</div>
                            </div>
                        `);
            }

            function formatMotoristaSelection(item) {
                return item.text || 'Selecione um motorista';
            }

        });

        // == Injeção de CSS dinâmica ==
        ;(function(){
            const css = `
                /* === Ajustes para o Select2 dentro de input-group === */
                .input-group .select2-container {
                    flex: 1 1 auto;
                    width: auto !important;
                }
                .input-group .select2-container .select2-selection--single {
                    border-radius: .25rem 0 0 .25rem !important;
                    height: calc(1.5em + .75rem + 2px) !important;
                    padding: .375rem .75rem !important;
                }
                .input-group .input-group-append .btn {
                    border-top-left-radius: 0;
                    border-bottom-left-radius: 0;
                }
                /* ——— Esconde os caracteres estranhos ——— */
                .select2-container--default .select2-selection--single .select2-selection__arrow b {
                    display: none !important;
                }

                /* ——— Desenha a setinha “V” no lugar ——— */
                .select2-container--default .select2-selection--single .select2-selection__arrow:after {
                    content: "\\25BE";       /* ▼ */
                    position: absolute;
                    top: 50%;
                    right: 8px;
                    transform: translateY(-50%);
                    font-size: .7em;
                    color: #555;
                    pointer-events: none;
                }
                `;
            const style = document.createElement('style');
            style.id = 'style-produto-select2';
            style.appendChild(document.createTextNode(css));
            document.head.appendChild(style);
        })();

        //Selects de Produtos
        $(document).on('shown.bs.modal', '[id^="modalTickets"]', function () {
            const $modal  = $(this);
            const $form   = $modal.find('#formTicket');
            const $select = $modal.find('#produto_id');
            const $clear  = $modal.find('.btn-clear-produto');
            const $fix    = $modal.find('.btn-fixar-produto');

            // 1) flag hidden
            if (!$form.find('#fixar_produto_flag').length) {
                $form.append('<input type="hidden" id="fixar_produto_flag" name="fixar_produto_flag" value="0">');
            }
            const $flag = $form.find('#fixar_produto_flag');

            // 2) init Select2 uma única vez
            if (!$select.data('select2')) {
                $select.select2({
                    placeholder: 'Selecione um produto',
                    dropdownParent: $modal,
                    ajax: {
                        url: '/pesagens/search/produto',
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ term: params.term }),
                        processResults: data => ({
                            results: data.results.map(item => ({
                                id: item.id,
                                text: `${item.referencia} | ${item.nome} | ${item.codBarras}`,
                                valor_venda: item.valor_venda,
                                categoria: item.categoria,
                                img: item.imagem,
                            })),
                        }),
                    },
                    templateResult: formatProduto,
                    templateSelection: formatProdutoSelection,
                    width: '100%'
                });
            }

            // 3) Eventos de Limpar / Fixar
            //    - Limpar só se não estiver fixado
            $clear.off('click').on('click', () => {
                if ($modal.find('.input-group').data('fixado') !== true) {
                    $select.val(null).trigger('change');
                }
            });

            //    - Fixar / Desfixar
            $fix.off('click').on('click', () => {
                const $group = $modal.find('.input-group');
                const novo   = !$group.data('fixado');
                $group.data('fixado', novo);
                $fix
                    .toggleClass('btn-secondary btn-success', novo)
                    .attr('title', novo ? 'Produto fixado' : 'Fixar produto');
                $flag.val(novo ? '1' : '0');
            });

            // 4) Antes de enviar, garante que o flag seja atualizado
            $form.off('submit').on('submit', () => {
                const fixado = $modal.find('.input-group').data('fixado') === true;
                $flag.val(fixado ? '1' : '0');
            });

            // 5) Ao fechar sem editar (#ticket_id vazio), “desfixa”
            $modal.off('hidden.bs.modal.reset').on('hidden.bs.modal.reset', () => {
                if ($form.find('#ticket_id').val() === '') {
                    const $group = $modal.find('.input-group');
                    $group.data('fixado', false);
                    $fix
                        .removeClass('btn-success')
                        .addClass('btn-secondary')
                        .attr('title', 'Fixar produto');
                    $flag.val('0');
                }
            });

            // 6) Toda vez que abrir, restaura o fixado vindo do hidden
            const fixVal = ($flag.val() === '1');
            const $group = $modal.find('.input-group');
            $group.data('fixado', fixVal);
            $fix
                .toggleClass('btn-secondary btn-success', fixVal)
                .attr('title', fixVal ? 'Produto fixado' : 'Fixar produto');
        });

        // — suas funções de template —
        function formatProduto(item) {
            if (!item.id) return item.text;
            const img = item.img || '/imgs/no_image.png';
            return $(`
    <div style="display:flex;align-items:center;">
      <img src="${img}" style="width:40px;height:40px;margin-right:10px;border-radius:50%;"/>
      <div>
        <div><strong>${item.text}</strong></div>
        <small>Valor: R$${item.valor_venda||'N/A'} | Categoria: ${item.categoria||'N/A'}</small>
      </div>
    </div>
  `);
        }
        function formatProdutoSelection(item) {
            return item.text || 'Selecione um produto';
        }



        //Campos de Descontos
        $(document).ready(function () {
            function monitorarCheckboxComDesconto(checkboxId, descontoId) {
                const checkbox = $(`#${checkboxId}`);
                const descontoInput = $(`#${descontoId}`);

                // Monitora alterações no estado do checkbox
                checkbox.on('change', function () {
                    if ($(this).is(':checked')) {
                        descontoInput.prop('disabled', false); // Habilita o campo de desconto
                    } else {
                        descontoInput.prop('disabled', true).val('0.00'); // Desabilita e zera o campo de desconto
                    }
                });

                // Sincroniza o estado inicial ao carregar
                if (!checkbox.is(':checked')) {
                    descontoInput.prop('disabled', true).val('0.00'); // Desabilita e zera o valor se não estiver marcado
                } else {
                    descontoInput.prop('disabled', false); // Habilita se estiver marcado
                }
            }

            // Monitora os checkboxes e seus respectivos campos de desconto
            monitorarCheckboxComDesconto('danificado', 'danificado_desconto');
            monitorarCheckboxComDesconto('quebrado', 'quebrado_desconto');
            monitorarCheckboxComDesconto('esverdeado', 'esverdeado_desconto');
            monitorarCheckboxComDesconto('ardido', 'ardido_desconto');
            monitorarCheckboxComDesconto('secagem', 'secagem_desconto');

            // Garante a sincronização inicial para todos os checkboxes
            $('#danificado, #quebrado, #esverdeado, #ardido, #secagem').trigger('change');
        });

        //Envio Whatsapp
        $(document).ready(function () {
            // Função para abrir o modal de confirmação
            function abrirModalConfirmacao(mensagem, acao) {
                $('#modalConfirmacaoMensagem').html(mensagem); // Insere a mensagem como HTML
                $('#modalConfirmacao').modal('show'); // Exibe o modal

                $('#btnConfirmarAcao').off('click').on('click', function () {
                    acao(); // Executa a ação
                    $('#modalConfirmacao').modal('hide'); // Fecha o modal
                });
            }

            // Modal de Mensagem/Aviso
            function abrirModalMensagem(titulo, mensagem) {
                $('#modalMensagemLabel').html(titulo);
                $('#modalMensagemTexto').html(mensagem);
                $('#modalMensagem').modal('show');
            }

            // Evento para abrir o modal de envio de WhatsApp
            $(document).on('click', '.btn-enviar-whatsapp', function () {
                const isDisabled = $(this).attr('data-disabled') === 'true'; // Verifica se o botão está desabilitado

                console.log('Botão clicado:', $(this));
                console.log('Desabilitado:', isDisabled);

                if (isDisabled) {
                    console.log('Abrindo modal de mensagem.');
                    abrirModalMensagem(
                        'Aviso',
                        'Necessário habilitar o token para acesso à API do WhatsApp nas configurações do sistema.'
                    );
                    return; // Interrompe a execução caso o botão esteja desabilitado
                }

                console.log('Continuando com o envio.');

                const pesagemId = $(this).data('id'); // Obtém o ID da pesagem
                const pesagemToken = $(this).data('token'); // Obtém o Token da pesagem
                const actionUrl = `/pesagens/enviar-relatorio-whats/${pesagemId}`; // Define a URL de ação

                $('#modalWhatsAppLabel').text(`Enviar WhatsApp - Pesagem #${pesagemId} - Ticket #${pesagemToken}`);
                // Define o ID da pesagem no modal
                $('#id_pesagem').val(pesagemId);

                // Define a URL de envio no formulário
                $('#modalWhatsApp form').attr('action', actionUrl);

                // Abre o modal
                $('#modalWhatsApp').modal('show');
            });

        });

        //Visibilidade Online da Pesagem
        $(document).ready(function () {
            // Função para abrir o modal de confirmação
            function abrirModalConfirmacao(mensagem, acao) {
                $('#modalConfirmacaoMensagem').html(mensagem); // Insere a mensagem como HTML
                $('#modalConfirmacao').modal('show'); // Exibe o modal

                $('#btnConfirmarAcao').off('click').on('click', function () {
                    acao(); // Executa a ação
                    $('#modalConfirmacao').modal('hide'); // Fecha o modal
                });
            }

            // Modal de Mensagem/Aviso
            function abrirModalMensagem(titulo, mensagem) {
                $('#modalMensagemLabel').html(titulo);
                $('#modalMensagemTexto').html(mensagem);
                $('#modalMensagem').modal('show');
            }

            // Quando o botão for clicado
            $('.btn-toggle-publicacao').click(function () {
                var pesagemId = $(this).data('id');
                var viewPublic = $(this).data('view-public');
                var tokenPesagem = $(this).data('token');

                // Faz uma chamada AJAX para obter o QRCode gerado no backend
                $.ajax({
                    url: `/pesagens/gen/qrcode/${tokenPesagem}`, // Rota definida no Laravel
                    method: 'GET',
                    success: function (response) {
                        // Criação da mensagem com o QRCode e link
                        var mensagemConfirmacao = viewPublic == 1
                            ? `Deseja realmente <strong>alternar</strong> a visibilidade desta pesagem para:<br><br>
                               <strong>Indisponível online</strong><br><br>
                               <strong>Token:</strong> ${tokenPesagem}<br>
                               <strong>ID:</strong> ${pesagemId}<br><br>
                               Após a impressão do PDF, esta pesagem não estará mais acessível via QRCode ou link.<br><br>
                               <a href="${response.url}" target="_blank">Clique aqui para visualizar a pesagem</a><br><br>
                               <img src="${response.qrCodeBase64}" alt="QR Code gerado" />`
                            : `Deseja realmente <strong>alterar</strong> a visibilidade desta pesagem para:<br><br>
                               <strong>Disponível online</strong><br><br>
                               <strong>Token:</strong> ${tokenPesagem}<br>
                               <strong>ID:</strong> ${pesagemId}<br><br>
                               Esta pesagem ficará acessível via QRCode ou link.<br>
                               Porém, após a impressão do PDF, ficará automaticamente indisponível novamente.`;

                        // Exibe o modal com a mensagem gerada
                        abrirModalConfirmacao(mensagemConfirmacao, function () {
                            var novoViewPublic = viewPublic == 1 ? 0 : 1;

                            // Faz a requisição AJAX para alterar a visibilidade
                            $.ajax({
                                url: '/pesagens/togglePublicacao/' + pesagemId,
                                method: 'POST',
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content'),
                                    view_public: novoViewPublic,
                                },
                                success: function (response) {
                                    if (response.success) {
                                        location.reload();
                                    } else {
                                        abrirModalMensagem('Erro','Erro ao atualizar a visibilidade da pesagem.');
                                    }
                                },
                                error: function () {
                                    abrirModalMensagem('Erro','Erro ao fazer a requisição.');
                                },
                            });
                        });
                    },
                    error: function () {
                        abrirModalMensagem('Erro','Erro ao gerar o QRCode.');
                    },
                });
            });
        });

        //Gerar Venda a partir de uma Pesagem
        $(document).ready(function () {
            // Função para abrir o modal de confirmação
            function abrirModalConfirmacao(mensagem, acao) {
                $('#modalConfirmacaoMensagem').html(mensagem); // Insere a mensagem como HTML
                $('#modalConfirmacao').modal('show'); // Exibe o modal

                $('#btnConfirmarAcao').off('click').on('click', function () {
                    acao(); // Executa a ação
                    $('#modalConfirmacao').modal('hide'); // Fecha o modal
                });
            }

            // Modal de Mensagem/Aviso
            function abrirModalMensagem(titulo, mensagem) {
                $('#modalMensagemLabel').html(titulo);
                $('#modalMensagemTexto').html(mensagem);
                $('#modalMensagem').modal('show');
            }

            // Botão Gerar Compra
            $('.gerar-venda-pesagem').on('click', function () {
                const pesagemId = $(this).data('id');
                const tokenPesagem = $(this).data('token');

                abrirModalConfirmacao(
                    `Tem certeza de que deseja gerar uma venda para esta pesagem? <br><br>
             <strong>Token:</strong> ${tokenPesagem}<br>
             <strong>ID:</strong> ${pesagemId}<br><br>
             <em>Esta ação não poderá ser desfeita!</em>`,
                    function () {
                        $.ajax({
                            url: `/pesagens/gen/criarVenda/${pesagemId}`,
                            method: 'GET',
                            success: function (response) {
                                // Exibir mensagem de sucesso com ID da venda
                                abrirModalMensagem(
                                    'Sucesso!',
                                    `Venda gerada com sucesso!<br><br>
                                    <strong>ID da Venda:</strong> ${response.venda_id}<br>
                                    <strong>Token da Pesagem:</strong> ${tokenPesagem}<br>
                                    <strong>ID da Pesagem:</strong> ${pesagemId}`
                                );

                                setTimeout(function () {
                                    location.reload(); // Recarrega a página após 2 segundos
                                }, 2000);
                            },
                            error: function (xhr) {
                                // Exibir mensagem de erro
                                abrirModalMensagem(
                                    'Erro!',
                                    xhr.responseJSON.message || 'Ocorreu um erro ao gerar a venda.'
                                );

                                setTimeout(function () {
                                    location.reload(); // Recarrega a página após 2 segundos
                                }, 2000);
                            },
                        });
                    }
                );
            });
        });

        // Botão Gerar Compra
        $(document).ready(function () {
            // Função para abrir o modal de confirmação
            function abrirModalConfirmacao(mensagem, acao) {
                $('#modalConfirmacaoMensagem').html(mensagem); // Insere a mensagem como HTML
                $('#modalConfirmacao').modal('show'); // Exibe o modal

                $('#btnConfirmarAcao').off('click').on('click', function () {
                    acao(); // Executa a ação
                    $('#modalConfirmacao').modal('hide'); // Fecha o modal
                });
            }

            // Modal de Mensagem/Aviso
            function abrirModalMensagem(titulo, mensagem) {
                $('#modalMensagemLabel').html(titulo);
                $('#modalMensagemTexto').html(mensagem);
                $('#modalMensagem').modal('show');
            }

            // Clique no botão "Gerar Compra"
            $('.gerar-compra-pesagem').on('click', function () {
                const pesagemId = $(this).data('id');
                const tokenPesagem = $(this).data('token');

                abrirModalConfirmacao(
                    `Tem certeza de que deseja gerar uma compra para esta pesagem? <br><br>
            <strong>Token:</strong> ${tokenPesagem}<br>
            <strong>ID:</strong> ${pesagemId}<br><br>
            <em>Esta ação não poderá ser desfeita!</em>`,
                    function () {
                        $.ajax({
                            url: `/pesagens/gen/criarCompra/${pesagemId}`,
                            method: 'GET',
                            success: function (response) {
                                // Exibir mensagem de sucesso com ID da compra
                                abrirModalMensagem(
                                    'Sucesso!',
                                    `Compra gerada com sucesso!<br><br>
                            <strong>ID da Compra:</strong> ${response.compra_id}<br>
                            <strong>Token da Pesagem:</strong> ${tokenPesagem}<br>
                            <strong>ID da Pesagem:</strong> ${pesagemId}`
                                );

                                setTimeout(function () {
                                    location.reload(); // Recarrega a página após 2 segundos
                                }, 2000);
                            },
                            error: function (xhr) {
                                // Exibir mensagem de erro
                                abrirModalMensagem(
                                    'Erro!',
                                    xhr.responseJSON.message || 'Ocorreu um erro ao gerar a compra.'
                                );

                                setTimeout(function () {
                                    location.reload(); // Recarrega a página após 2 segundos
                                }, 2000);
                            },
                        });
                    }
                );
            });
        });

    </script>

{{-- Controle dos Tickets de Pesagens     --}}
    <script type="text/javascript">

        // Função para carregar tickets dinamicamente mantendo a estrutura de tabela
        function carregarTickets(pesagemId) {
            const tabelaTickets = $(`#modalTickets${pesagemId} #listaTickets`);
            tabelaTickets.empty(); // Limpa a tabela antes de recarregar

            $.get(`/ticketsPesagem/list/${pesagemId}`, function (tickets) {
                tickets.forEach(ticket => {
                    tabelaTickets.append(`
                <tr class="datatable-row">
                    <td class="datatable-cell">${ticket.id}</td>
                    <td id="produto-${ticket.id}" class="datatable-cell">${ticket.produto ? ticket.produto.nome : 'N/A'}</td>
                    <td id="tipo-${ticket.id}" class="datatable-cell">${ticket.tipo.charAt(0).toUpperCase() + ticket.tipo.slice(1)}</td>
                    <td id="peso-${ticket.id}" class="datatable-cell">${parseFloat(ticket.peso).toFixed(2).replace('.', ',')} kg</td>
                    <td id="peso-${ticket.id}" class="datatable-cell">${parseFloat(ticket.peso_bag).toFixed(2).replace('.', ',')} kg</td>
                    <td id="status-${ticket.id}" class="datatable-cell">
                        <span class="badge badge-fixed-width-status ${ticket.status === 'concluído' ? 'badge-success' : 'badge-warning'}">
                            ${ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1)}
                        </span>
                    </td>
                    <td id="inicio-${ticket.id}" class="datatable-cell">${ticket.inicio ? new Date(ticket.inicio).toLocaleString('pt-BR') : 'N/A'}</td>
                    <td id="fim-${ticket.id}" class="datatable-cell">${ticket.fim ? new Date(ticket.fim).toLocaleString('pt-BR') : 'N/A'}</td>
                    <td class="datatable-cell text-center">
                        <button type="button" class="btn btn-warning btn-sm btn-custom edit-ticket" data-id="${ticket.id}">
                            <i class="la la-edit"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-custom delete-ticket" data-id="${ticket.id}">
                            <i class="la la-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
                });

                // Preserva o estilo responsivo
                tabelaTickets.closest('.table-responsive').css({
                    'max-height': '200px',
                    'overflow-y': 'auto',
                    'overflow-x': 'auto'
                });
            });
        }

        function atualizarTicketsGrid(pesagemId) {
            $.get(`/ticketsPesagem/getDados/${pesagemId}`, function (data) {
                data.tickets.forEach(ticket => {
                    $(`#produto-${ticket.id}`).text(ticket.produto || 'N/A'); // Produto
                    $(`#tipo-${ticket.id}`).text(ticket.tipo); // Tipo
                    $(`#peso-${ticket.id}`).text(`${ticket.peso.toFixed(2).replace('.', ',')} kg`); // Peso
                    $(`#peso_bag-${ticket.id}`).text(`${ticket.peso_bag.toFixed(2).replace('.', ',')} kg`); // Peso
                    $(`#status-${ticket.id}`).html(`
                <span class="badge ${ticket.status === 'concluído' ? 'badge-success' : 'badge-warning'}">
                    ${ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1)}
                </span>
            `); // Status
                    $(`#inicio-${ticket.id}`).text(ticket.inicio || 'N/A'); // Início
                    $(`#fim-${ticket.id}`).text(ticket.fim || 'N/A'); // Fim
                });
            }).fail(() => {
                abrirModalMensagem('Erro', 'Não foi possível atualizar os tickets.');
            });
        }

        // Função para cancelar a edição/inclusão do ticket
        $(document).on('click', '#btnCancelarTicket', function () {
            const modalSelector = $(this).closest('.modal'); // Identifica o modal atual
            resetForm(modalSelector); // Reseta o formulário
        });

        //Função para resetar o formulário do ticket sem limpar o produto se estiver fixado
        function resetForm(modalSelector) {
            var $modal = $(modalSelector);
            var $form = $modal.find('#formTicket');
            var fixado = $form.find('#fixar_produto_flag').val() === '1';

            // Campos de controle
            $form.find('#ticket_id').val('');
            $form.find('#_method').val('POST');
            $form.attr('action', '{{ route("ticketsPesagem.save") }}');

            // Campos de dados
            $form.find('input[name="peso"]').val('0.00');
            $form.find('input[name="peso_bag"]').val('0.00');
            $form.find('select[name="tipo"]').val('entrada');
            $form.find('select[name="status"]').val('em andamento');
            $form.find('input[name="inicio"]').val('');
            $form.find('input[name="fim"]').val('');
            $form.find('textarea[name="observacoes"]').val('');

            // Limpa o produto **apenas** se não estiver fixado
            if (!fixado) {
                $form.find('#produto_id').val(null).trigger('change');
                $form.find('#fixar_produto_flag').val('0');
                // Atualiza visual do botão “fixar”
                var $group = $form.find('#produto_id').closest('.input-group');
                $group.data('fixado', false);
                $group.find('.btn-fixar-produto')
                    .removeClass('btn-success')
                    .addClass('btn-secondary')
                    .attr('title', 'Fixar produto');
            }
        }

        // Função para resetar o formulário do ticket
        function resetForm__(modalSelector) {
            $(modalSelector).find('#formTicket')[0].reset(); // Reseta o formulário
            $(modalSelector).find('#ticket_id').val(''); // Limpa o ID do ticket
            $(modalSelector).find('#_method').val('POST'); // Volta para método POST
            $(modalSelector).find('#formTicket').attr('action', '/ticketsPesagem/save'); // Redefine para criação
            $(modalSelector).find('#produto_id').val('').trigger('change');
        }

        // Função para ativar eventos no modal
        function ativarEventosTickets(modalId, pesagemId) {
            const modalSelector = `#modalTickets${pesagemId}`;

            // Evento para salvar ou editar ticket
            $(modalSelector).find('#formTicket').off('submit').on('submit', function (e) {
                e.preventDefault(); // Previne submissão padrão

                const form = $(this);
                const url = form.attr('action'); // URL de destino
                const method = form.find('input[name="_method"]').val(); // POST ou PUT

                // Mantém o token original ao editar para evitar conflitos
                if (method === 'POST') {
                    form.find('#token').val(Math.random().toString(36).substring(2, 15) +
                        Math.random().toString(36).substring(2, 15));
                }

                // Envia requisição AJAX
                $.ajax({
                    url: url,
                    type: method,
                    data: form.serialize(),
                    success: function (response) {
                        abrirModalMensagem('Sucesso', response.success); // Exibe mensagem de sucesso
                        carregarTickets(pesagemId); // Atualiza dinamicamente a tabela
                        atualizarTotaisGrid(pesagemId); // Atualiza os totais na grid principal
                        resetForm(modalSelector); // Reseta o formulário
                    },
                    error: function (xhr) {
                        abrirModalMensagem('Erro', xhr.responseJSON.error); // Exibe mensagem de erro
                    }
                });
            });

            // Editar Ticket
            $(modalSelector).off('click', '.edit-ticket').on('click', '.edit-ticket', function () {
                const ticketId = $(this).data('id'); // ID do ticket

                $.get(`/ticketsPesagem/edit/${ticketId}`, function (data) {
                    $(modalSelector).find('#ticket_id').val(data.id); // Preenche ID
                    $(modalSelector).find('#produto_id').val(data.produto_id).change(); // Produto
                    $(modalSelector).find('#produto_id').val(data.produto_id).trigger('change');
                    $(modalSelector).find('#peso').val(parseFloat(data.peso).toFixed(2)); // Peso
                    $(modalSelector).find('#peso_bag').val(parseFloat(data.peso_bag || 0).toFixed(2));
                    $(modalSelector).find('#tipo').val(data.tipo); // Tipo
                    $(modalSelector).find('#status').val(data.status); // Status
                    $(modalSelector).find('#inicio').val(data.inicio ? data.inicio.replace(' ', 'T') : ''); // Início
                    $(modalSelector).find('#fim').val(data.fim ? data.fim.replace(' ', 'T') : ''); // Fim
                    $(modalSelector).find('#observacoes').val(data.observacoes); // Observações

                    // Mantém o token atual ao editar
                    $(modalSelector).find('#token').val(data.token);

                    // Atualiza o formulário para PUT (edição)
                    $(modalSelector).find('#formTicket').attr('action', `/ticketsPesagem/update/${ticketId}`);
                    $(modalSelector).find('#_method').val('PUT');
                });
            });

            // Excluir Ticket
            $(modalSelector).off('click', '.delete-ticket').on('click', '.delete-ticket', function () {
                const ticketId = $(this).data('id'); // ID do ticket

                abrirModalConfirmacao('Deseja realmente excluir este ticket? Esta ação não poderá ser desfeita!', function () {
                    $.ajax({
                        url: `/ticketsPesagem/delete/${ticketId}`,
                        type: 'POST',
                        data: {_token: '{{ csrf_token() }}', _method: 'DELETE'},
                        success: function () {
                            abrirModalMensagem('Sucesso', 'Ticket excluído com sucesso!');
                            carregarTickets(pesagemId); // Atualiza dinamicamente
                            atualizarTotaisGrid(pesagemId); // Atualiza os totais
                        },
                        error: function () {
                            abrirModalMensagem('Erro', 'Erro ao excluir ticket.');
                        }
                    });
                });
            });
        }

        function atualizarTotaisGrid(pesagemId) {
            $.get(`/pesagens/getTotais/${pesagemId}`, function (data) {
                // Atualiza os campos dinamicamente na tabela
                $(`#peso-${pesagemId}`).text(`${data.peso.toFixed(2)} kg`);
                $(`#pesoBruto-${pesagemId}`).text(`${data.peso_liquido_bruto.toFixed(2)} kg`);
                $(`#pesoFinal-${pesagemId}`).text(`${data.peso_final.toFixed(2)} kg`);
            });
        }

        // Modal de Confirmação
        function abrirModalConfirmacao(mensagem, acao) {
            // Atualiza o texto no modal de confirmação
            $('#modalConfirmacaoMensagem').text(mensagem);

            // Exibe o modal de confirmação
            $('#modalConfirmacao').modal('show');

            // Remove eventos antigos para evitar duplicatas
            $('#btnConfirmarAcao').off('click');

            // Configura a ação para o botão "Sim"
            $('#btnConfirmarAcao').on('click', function () {
                acao(); // Executa a ação passada como parâmetro
                $('#modalConfirmacao').modal('hide'); // Fecha o modal após confirmar
            });
        }

        // Modal de Mensagem/Aviso
        function abrirModalMensagem(titulo, mensagem) {
            // Atualiza dinamicamente o título e a mensagem no modal
            $('#modalMensagemLabel').text(titulo); // Define o título do modal
            $('#modalMensagemTexto').text(mensagem); // Define a mensagem

            // Exibe o modal de mensagem
            $('#modalMensagem').modal('show');
        }

        // Reseta o modal de tickets ao fechar
        $('#modalTickets').on('hidden.bs.modal', function () {
            // Reseta o formulário de tickets
            $('#formTicket')[0].reset(); // Reseta todos os campos do formulário
            $('#ticket_id').val(''); // Limpa o ID do ticket
            $('#_method').val('POST'); // Retorna para método POST
            $('#formTicket').attr('action', '{{ route("ticketsPesagem.save") }}'); // Reconfigura para criar novo ticket
        });

        // Ativa os eventos sempre que o modal de tickets for aberto
        $(document).on('shown.bs.modal', '.modal', function () {
            const modalId = $(this).attr('id'); // Identifica o modal atual
            const pesagemId = modalId.replace('modalTickets', ''); // Extrai o ID da pesagem
            ativarEventosTickets(`#${modalId}`, pesagemId); // Ativa eventos dinamicamente
        });

        // === Enhancement: Date-Time Fields in #formTicket ===
        $(document).on('shown.bs.modal', '.modal', function(){
            var $modal = $(this),
                $form  = $modal.find('#formTicket');
            if (!$form.length) return;

            // 1) hidden flags
            if (!$form.find('#pin_inicio_flag').length)
                $form.append('<input type="hidden" id="pin_inicio_flag" name="pin_inicio_flag" value="0">');
            if (!$form.find('#pin_fim_flag').length)
                $form.append('<input type="hidden" id="pin_fim_flag"    name="pin_fim_flag"    value="0">');

            // 2) wrap #inicio e #fim + 3 botões
            ['#inicio','#fim'].forEach(function(sel){
                var $inp = $modal.find(sel);
                if (!$inp.parent().hasClass('input-group')) {
                    $inp.wrap('<div class="input-group"></div>');
                    $inp.after('\
                    <div class="input-group-append">\
                      <button type="button" class="btn btn-sm btn-secondary btn-clear-date" data-target="'+sel+'" title="Limpar"><i class="fa fa-times"></i></button>\
                      <button type="button" class="btn btn-sm btn-secondary btn-pin-date"   data-target="'+sel+'" title="Fixar"><i class="fa fa-thumb-tack"></i></button>\
                      <button type="button" class="btn btn-sm btn-secondary btn-now-date"   data-target="'+sel+'" title="Agora"><i class="fa fa-clock"></i></button>\
                    </div>');
                }
            });

            // helper para (des)marcar fix
            function setPin(sel, on){
                var $grp = $modal.find(sel).closest('.input-group');
                $grp.data('pinned', on);
                $form.find('#pin_'+sel.replace('#','')+'_flag').val(on? '1':'0');
                $grp.find('.btn-pin-date').toggleClass('btn-secondary btn-success', on);
            }
            // 3) restaura pins
            setPin('#inicio', $form.find('#pin_inicio_flag').val()==='1');
            setPin('#fim',    $form.find('#pin_fim_flag').val()   ==='1');

            // 4) handlers de limpar/pin/agora
            $modal
                .off('click','.btn-clear-date').on('click','.btn-clear-date', function(){
                var sel = $(this).data('target'),
                    grp = $modal.find(sel).closest('.input-group');
                if (!grp.data('pinned')) $modal.find(sel).val('');
            })
                .off('click','.btn-pin-date').on('click','.btn-pin-date', function(){
                var sel = $(this).data('target'),
                    grp = $modal.find(sel).closest('.input-group'),
                    on  = !grp.data('pinned');
                setPin(sel, on);
            })
                .off('click','.btn-now-date').on('click','.btn-now-date', function(){
                var sel = $(this).data('target'),
                    now = formatDateTimeLocal(getServerDate());
                $modal.find(sel).val(now);
            });

            // 5) sempre preenche INÍCIO qdo muda tipo
            $modal
                .off('change','select[name="tipo"]').on('change','select[name="tipo"]', function(){
                var now = formatDateTimeLocal(getServerDate());
                if (! $modal.find('#inicio').closest('.input-group').data('pinned'))
                    $modal.find('#inicio').val(now);
            })
                .find('select[name="tipo"]').trigger('change');

            // 6) limpa/preenche FIM em função do status
            $modal
                .off('change','select[name="status"]').on('change','select[name="status"]', function(){
                var st  = $(this).val(),
                    now = formatDateTimeLocal(getServerDate());
                if (st==='em andamento') {
                    if (! $modal.find('#fim').closest('.input-group').data('pinned'))
                        $modal.find('#fim').val('');
                }
                if (st==='concluído') {
                    if (! $modal.find('#fim').closest('.input-group').data('pinned'))
                        $modal.find('#fim').val(now);
                }
            })
                .find('select[name="status"]').trigger('change');
        });

        // === Preserve pins on Cancel do ticket (atualizado) ===
        var _origReset = resetForm;
        resetForm = function(modalSelector){
            var $modal = $(modalSelector),
                $form  = $modal.find('#formTicket'),
                pinI   = $form.find('#pin_inicio_flag').val()==='1',
                pinF   = $form.find('#pin_fim_flag')   .val()==='1',
                valI   = pinI ? $form.find('#inicio').val() : '',
                valF   = pinF ? $form.find('#fim')   .val() : '';
            _origReset(modalSelector);

            // Se não estava pinned → preenche novo início
            if (!pinI) {
                var now = formatDateTimeLocal(getServerDate());
                $modal.find('#inicio').val(now);
            } else {
                $modal.find('#inicio').val(valI);
            }
            // Se estava pinned, restaura fim; senão, deixa em branco
            if (pinF) {
                $modal.find('#fim').val(valF);
            }
        };

        // === Novo botão FECHAR (limpa tudo + fecha) ===
        $(document).on('click','.btn-close-ticket', function(){
            var $modal = $(this).closest('.modal');
            // reseta TUDO
            _origReset($modal);
            // limpa flags e UI de pins
            $modal.find('#pin_inicio_flag, #pin_fim_flag').val('0');
            $modal.find('.btn-pin-date').removeClass('btn-success').addClass('btn-secondary');
            $modal.find('.input-group').each(function(){ $(this).data('pinned', false); });
            $modal.modal('hide');
        });

        // === Ao fechar (fora ou X) ⇒ limpa igualmente ===
        $(document).on('hidden.bs.modal','[id^=modalTickets]', function(){
            var $modal = $(this);
            _origReset($modal);
            $modal.find('#pin_inicio_flag, #pin_fim_flag').val('0');
            $modal.find('.btn-pin-date').removeClass('btn-success').addClass('btn-secondary');
            $modal.find('.input-group').each(function(){ $(this).data('pinned', false); });
        });

    </script>

{{--    Controle da Balança     --}}
    <script type="text/javascript">
        function ativarEventosBalança(modalId) {
            let balancaSelecionada = null;
            let intervaloLeitura;

            // Seleciona a balança
            $(modalId).find('#balanca-select').on('change', function () {
                const select = $(this).find(':selected');
                balancaSelecionada = {
                    id: select.val(),
                    backend: select.data('backend'),
                    modelo: select.data('modelo'),
                    porta: select.data('port')
                };
                $(modalId).find('#connect').prop('disabled', !balancaSelecionada.id);
                $(modalId).find('#disconnect').prop('disabled', true);
            });

            // Conectar
            $(modalId).find('#connect').on('click', function () {
                if (!balancaSelecionada) {
                    abrirModalMensagem('Aviso', 'Selecione uma balança!');
                    return;
                }

                axios.get(`${balancaSelecionada.backend}/api/open?port=${balancaSelecionada.porta}`)
                    .then(() => {
                        $(modalId).find('#connect').prop('disabled', true);
                        $(modalId).find('#disconnect').prop('disabled', false);
                        iniciarLeitura(modalId, balancaSelecionada);
                    })
                    .catch(error => abrirModalMensagem('Erro', 'Erro ao conectar: ' + error.message));
            });

            // Desconectar
            $(modalId).find('#disconnect').on('click', function () {
                if (!balancaSelecionada) return;

                axios.get(`${balancaSelecionada.backend}/api/close`)
                    .then(() => {
                        $(modalId).find('#connect').prop('disabled', false);
                        $(modalId).find('#disconnect').prop('disabled', true);
                        clearInterval(intervaloLeitura);
                        $(modalId).find('#pesoAtual').text('----');
                    })
                    .catch(error => abrirModalMensagem('Erro', 'Erro ao desconectar: ' + error.message));
            });

            // Iniciar leitura contínua
            function iniciarLeitura(modalId, balancaSelecionada) {
                intervaloLeitura = setInterval(() => {
                    axios.get(`${balancaSelecionada.backend}/api/data?equip=${balancaSelecionada.modelo}`)
                        .then(response => {
                            const dados = response.data.data;
                            $(modalId).find('#pesoAtual').text(dados.peso_bruto || '----');
                            $(modalId).find('#pesoBruto').text(dados.peso_bruto || '----');
                            $(modalId).find('#pesoLiquido').text(dados.peso_liq || '----');
                            $(modalId).find('#pesoTara').text(dados.tara || '----');
                            $(modalId).find('#pesoEstabilidade').text(dados.estavel  ? "Estável" : "Oscilando");
                            $(modalId).find('#peso').val(dados.peso_bruto || 0); // Atualiza automaticamente no campo
                            if (dados.sobrecarga) {
                                $(modalId).find('#pesoEstabilidade').text("Sobrecarga");
                            }
                        })
                        .catch(error => console.error('Erro ao ler peso:', error));
                }, 200); // Atualiza a cada 500ms
            }
        }

        // Modal de Mensagem/Aviso
        function abrirModalMensagem(titulo, mensagem) {
            // Atualiza dinamicamente o título e a mensagem no modal
            $('#modalMensagemLabel').text(titulo); // Define o título do modal
            $('#modalMensagemTexto').text(mensagem); // Define a mensagem

            // Exibe o modal de mensagem
            $('#modalMensagem').modal('show');
        }

        // Ativa os eventos sempre que o modal for aberto
        $(document).on('shown.bs.modal', '.modal', function () {
            const modalId = '#' + $(this).attr('id'); // Identifica o modal atual
            ativarEventosBalança(modalId); // Chama a função para ativar eventos
        });
    </script>

    <script>
        document.getElementById('btnClearFilters').addEventListener('click', function() {
            // Inputs de texto e datas
            document.querySelector('input[name="search"]').value = '';
            document.querySelector('input[name="data_inicial"]').value = '';
            document.querySelector('input[name="data_final"]').value = '';
            // Selects
            document.querySelector('select[name="veiculo_id"]').value = '';
            document.querySelector('select[name="status"]').value = '';
            window.location = '{{ route("pesagens.list") }}';
        });
    </script>

    <script>
        // Abre o modal de confirmação com mensagem HTML e executa `acao` se o usuário clicar em “Sim”
        function abrirModalConfirmacao(mensagem, acao) {
            $('#modalConfirmacaoMensagem').html(mensagem);
            $('#modalConfirmacao').modal('show');
            $('#btnConfirmarAcao').off('click').on('click', function() {
                acao();
                $('#modalConfirmacao').modal('hide');
            });
        }

        // Abre um modal genérico de mensagem/alerta
        function abrirModalMensagem(titulo, mensagem) {
            $('#modalMensagemLabel').html(titulo);
            $('#modalMensagemTexto').html(mensagem);
            $('#modalMensagem').modal('show');
        }

        $(function(){
            // 1) Quando clicar em “Reabrir Pesagem”, exibimos o modal de confirmação
            $(document).on('click', '.btn-reabrir-pesagem', function() {
                const pesagemId    = $(this).data('id');
                const tokenPesagem = $(this).data('token');

                abrirModalConfirmacao(
                    `Tem certeza de que deseja reabrir esta pesagem? <br><br>
             <strong>Token:</strong> ${tokenPesagem}<br>
             <strong>ID:</strong> ${pesagemId}<br><br>
             <em>Essa ação pode implicar na impressão de novos relatórios caso haja mudanças no peso e impactar nos resultados.</em>`,
                    function() {
                        // 2) se confirmar, abre o modal de OTP
                        $('#reabrir-otp-error').hide();
                        $('#reabrir-otp-code').val('').focus();
                        $('#reabrirOtpModal')
                            .data('pesagem-id', pesagemId)
                            .modal('show');
                    }
                );
            });

            // 3) Ao clicar em “Validar e Reabrir” dentro do modal de OTP
            $('#btn-reabrir-validate').on('click', function(e) {
                e.preventDefault();
                const code    = $('#reabrir-otp-code').val().trim();
                const errorEl = $('#reabrir-otp-error');

                errorEl.hide();
                if (!/^\d{6}$/.test(code)) {
                    return errorEl.text('Informe um código de 6 dígitos.').show();
                }

                const pesagemId = $('#reabrirOtpModal').data('pesagem-id');
                const btn       = $(this).prop('disabled', true);

                $.ajax({
                    url: `/pesagens/reabrir/${pesagemId}`,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        code
                    }
                })
                    .done(() => {
                        $('#reabrirOtpModal').modal('hide');
                        abrirModalMensagem('Sucesso', 'Pesagem reaberta com sucesso.');
                        setTimeout(() => location.reload(), 1200);
                    })
                    .fail(xhr => {
                        let msg = 'Código inválido.';
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.error) {
                                msg = xhr.responseJSON.error;
                            } else if (xhr.responseJSON.errors) {
                                const first = Object.values(xhr.responseJSON.errors)[0];
                                msg = Array.isArray(first) ? first[0] : first;
                            }
                        }
                        errorEl.text(msg).show();
                    })
                    .always(() => {
                        btn.prop('disabled', false);
                    });
            });
        });
    </script>


@endsection
