@extends('default.layout')

@section('content')
    <style>
        /* ----- GRID GERAL ----- */
        .datatable-row, .datatable-cell {
            height: 35px !important; /* Altura uniforme */
            vertical-align: middle !important;
        }
        .datatable-cell {
            text-align: center !important;
            padding: 5px !important;
            white-space: nowrap !important;
        }
        .thead-light {
            position: sticky !important;
            top: 0 !important;
            z-index: 1 !important;
            background-color: white !important;
        }
        /* Botões personalizados */
        .btn-custom {
            padding: 3px 8px !important;
            font-size: 12px !important;
            line-height: 1.2 !important;
        }
        .btn-custom i {
            vertical-align: middle !important;
        }
        /* Responsividade e scroll para tabelas */
        .table-responsive {
            overflow-x: auto !important;
            /* Removido overflow-y para evitar scrollbar desnecessário em telas maiores */
        }
        /* Estilo para modal do log */
        #json-container {
            max-height: 500px;
            overflow-y: auto;
        }
        /* Badge customizado para ações - tamanho proporcional */
        .badge-fixed-width-status {
            min-width: 50px;
            display: inline-block;
            text-align: center;
            padding: 6px;
        }
        /* Oculta marcadores padrão de lista na árvore JSON */
        ul.json-tree {
            list-style-type: none;
            margin: 0;
            padding-left: 15px;
        }
        /* Cursor de 'mãozinha' nos toggles */
        .toggle {
            cursor: pointer;
            color: blue;
        }
        /* Caixas para dados_anteriores e dados_depois */
        .json-box {
            background-color: #fff;
            font-size: 0.9em;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>

    <div class="card card-custom gutter-b">
        <div class="card-body">
            <!-- Mensagens de Sucesso e Erro -->
            @if(session('mensagem_sucesso'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('mensagem_sucesso') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('mensagem_erro'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('mensagem_erro') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- Filtros -->
            <h4>Filtros</h4>
            <form method="get" action="{{ route('logs.list') }}">
                <div class="row">
                    <div class="form-group col-lg-2 col-md-4">
                        <label>Data Inicial</label>
                        <input type="date" name="data_inicial" class="form-control" value="{{ request('data_inicial') }}">
                    </div>

                    <div class="form-group col-lg-2 col-md-4">
                        <label>Data Final</label>
                        <input type="date" name="data_final" class="form-control" value="{{ request('data_final') }}">
                    </div>

                    <div class="form-group col-lg-2 col-md-4">
                        <label>Filial</label>
                        <select name="filial_id" class="form-control">
                            <option value="">Todas</option>
                            <option value="null" {{ request('filial_id') === 'null' ? 'selected' : '' }}>Matriz</option>
                            @foreach($filiais as $filial)
                                <option value="{{ $filial->id }}" {{ request('filial_id') == $filial->id ? 'selected' : '' }}>
                                    {{ $filial->nome_fantasia }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-lg-2 col-md-4">
                        <label>Usuário</label>
                        <select name="usuario_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id }}" {{ request('usuario_id') == $usuario->id ? 'selected' : '' }}>
                                    {{ $usuario->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-lg-2 col-md-4">
                        <label>Ação</label>
                        <select name="acao" class="form-control">
                            <option value="">Todas</option>
                            @foreach($acoes as $acao)
                                <option value="{{ $acao }}" {{ request('acao') == $acao ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $acao)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-lg-2 col-md-4">
                        <label>Modelo (busca parcial)</label>
                        <input type="text" name="modelo" class="form-control" placeholder="Digite parte do modelo, ex: Pesagem" value="{{ request('modelo') }}">
                    </div>

                    <div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="fa fa-filter"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>

            <hr>

            <!-- Lista de Logs -->
            <h4>Lista de Logs</h4>
            <div class="mb-2">
                <strong>Total de registros: {{ $logs->total() ?? count($logs) }}</strong>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>Filial</th>
                        <th>Usuário</th>
                        <th>Ação</th>
                        <th>Modelo</th>
                        <th>Registro ID</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr class="datatable-row">
                            <td class="datatable-cell">{{ $log->id }}</td>
                            <td class="datatable-cell">
                                {{ optional($log->filial)->nome_fantasia ?? 'Matriz' }}
                            </td>
                            <td class="datatable-cell">
                                {{ $log->usuario_id ?? 'N/A' }}
                                @foreach($usuarios as $usuario)
                                    @if($usuario->id == $log->usuario_id)
                                        - {{ $usuario->nome }}
                                    @endif
                                @endforeach
                            </td>
                            <td class="datatable-cell">
                                <span class="badge badge-info badge-fixed-width-status">{{ ucfirst($log->acao) }}</span>
                            </td>
                            <td class="datatable-cell">{{ $log->modelo }}</td>
                            <td class="datatable-cell">{{ $log->token }}</td>
                            <td class="datatable-cell">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="datatable-cell">
                                <button class="btn btn-sm btn-primary btn-custom btn-detalhes" data-log="{{ json_encode($log) }}">
                                    <i class="fa fa-eye"></i> Ver
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="datatable-row">
                            <td colspan="8" class="datatable-cell text-center">Nenhum Registro de Log encontrado.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="d-flex justify-content-center">
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    <!-- Modal para Visualizar Detalhes -->
    <div class="modal fade" id="modalDetalhes" tabindex="-1" role="dialog" aria-labelledby="modalDetalhesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalDetalhesLabel">Detalhes do Log</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Box para Dados Anteriores -->
                    <div id="box-dados-anteriores" class="json-box" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Dados Anteriores</h6>
                            <button type="button" class="btn btn-link btn-sm toggle-box" data-target="#json-antes-content">[+]</button>
                        </div>
                        <div id="json-antes-content" style="display:none;">
                            <div id="json-antes"></div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm mb-2" id="btn-download-antes" style="display:none;">
                        <i class="fa fa-download"></i> Download JSON Anterior
                    </button>

                    <!-- Box para Dados Depois -->
                    <div id="box-dados-depois" class="json-box" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Dados Depois</h6>
                            <button type="button" class="btn btn-link btn-sm toggle-box" data-target="#json-depois-content">[+]</button>
                        </div>
                        <div id="json-depois-content" style="display:none;">
                            <div id="json-depois"></div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm mb-2" id="btn-download-depois" style="display:none;">
                        <i class="fa fa-download"></i> Download JSON Depois
                    </button>

                    <!-- Container que exibirá a árvore JSON do Log completo -->
                    <div id="json-container" class="bg-light p-3 rounded"></div>

                    <div class="text-right mt-3">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <!-- Script para exibir modal com JSON em árvore expandível -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Função recursiva para construir a árvore HTML com botões [+]/[-]
            function buildTree(data) {
                let html = `<ul class="json-tree">`;
                for (let key in data) {
                    if (!data.hasOwnProperty(key)) continue;
                    let value = data[key];
                    // Se for string e conter JSON válido, tenta converter
                    if (typeof value === 'string') {
                        try {
                            let parsed = JSON.parse(value);
                            value = parsed;
                        } catch (e) {
                            // mantém valor original
                        }
                    }
                    if (typeof value === 'object' && value !== null) {
                        let isArray = Array.isArray(value);
                        let typeLabel = isArray ? '(array)' : '(object)';
                        html += `
                            <li>
                                <span class="toggle">[+]</span>
                                <strong>${key}</strong> ${typeLabel}
                                <div class="child" style="display:none;">
                                    ${buildTree(value)}
                                </div>
                            </li>
                        `;
                    } else {
                        html += `<li><strong>${key}:</strong> ${value}</li>`;
                    }
                }
                html += `</ul>`;
                return html;
            }

            // Expansão/colapso de itens na árvore JSON
            $(document).on('click', '.toggle', function () {
                let $child = $(this).parent().find('> .child').first();
                if ($child.is(':visible')) {
                    $child.slideUp();
                    $(this).text('[+]');
                } else {
                    $child.slideDown();
                    $(this).text('[-]');
                }
            });

            // Toggle para as caixas de JSON (dados_anteriores e dados_depois)
            $(document).on('click', '.toggle-box', function(){
                let target = $(this).data('target');
                if($(target).is(':visible')){
                    $(target).slideUp();
                    $(this).text('[+]');
                } else {
                    $(target).slideDown();
                    $(this).text('[-]');
                }
            });

            // Função para download de JSON
            function downloadJSON(filename, jsonContent) {
                let dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(jsonContent, null, 2));
                let downloadAnchorNode = document.createElement('a');
                downloadAnchorNode.setAttribute("href", dataStr);
                downloadAnchorNode.setAttribute("download", filename + ".json");
                document.body.appendChild(downloadAnchorNode);
                downloadAnchorNode.click();
                downloadAnchorNode.remove();
            }

            // Ao clicar em "Ver", mostra a modal com os detalhes do log
            $('.btn-detalhes').on('click', function () {
                let log = $(this).data('log');

                // Monta a árvore JSON completa do log
                let treeHtml = buildTree(log);
                $('#json-container').html(treeHtml);

                // Dados Anteriores
                if (log.dados_anteriores) {
                    $('#box-dados-anteriores').show();
                    let objAntes;
                    try {
                        objAntes = JSON.parse(log.dados_anteriores);
                    } catch(e) {
                        objAntes = log.dados_anteriores;
                    }
                    let treeAntes = buildTree(objAntes);
                    $('#json-antes').html(treeAntes);
                    $('#btn-download-antes').show().off('click').on('click', function() {
                        downloadJSON('dados_anteriores_log_' + log.id, objAntes);
                    });
                } else {
                    $('#box-dados-anteriores').hide();
                    $('#btn-download-antes').hide();
                }

                // Dados Depois
                if (log.dados_depois) {
                    $('#box-dados-depois').show();
                    let objDepois;
                    try {
                        objDepois = JSON.parse(log.dados_depois);
                    } catch(e) {
                        objDepois = log.dados_depois;
                    }
                    let treeDepois = buildTree(objDepois);
                    $('#json-depois').html(treeDepois);
                    $('#btn-download-depois').show().off('click').on('click', function() {
                        downloadJSON('dados_depois_log_' + log.id, objDepois);
                    });
                } else {
                    $('#box-dados-depois').hide();
                    $('#btn-download-depois').hide();
                }

                // Abre o modal
                $('#modalDetalhes').modal('show');
            });
        });
    </script>
@endsection
