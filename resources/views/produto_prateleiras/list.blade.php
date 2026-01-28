@extends('default.layout')

@section('content')
    <style type="text/css">
        /* ---------------------------------------------------------- */
        /* 1) BOTÕES DO TOPO: ajustes de padding e fonte menores     */
        /* ---------------------------------------------------------- */
        .card-body .row > a.btn-sm,
        .card-body .row > button.btn-sm {
            padding: 4px 8px !important;
            font-size: 13px !important;
            line-height: 1.1 !important;
            margin-left: 3px !important;
            margin-top: 3px !important;
        }
        .card-body .row > a.btn-sm i,
        .card-body .row > button.btn-sm i {
            font-size: 13px !important;
            vertical-align: middle !important;
        }

        /* ---------------------------------------------------------- */
        /* 2) GRID GERAL (ajuste para ocupar todo width disponível)   */
        /* ---------------------------------------------------------- */
        .table-prateleiras {
            width: 100% !important;
            font-size: 12px !important;
        }
        .table-prateleiras th,
        .table-prateleiras td {
            white-space: nowrap !important;
            vertical-align: middle !important;
            padding: 6px !important;
            font-size: 12px !important;
        }
        .table-prateleiras th {
            background-color: #f8f9fa !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
        }
        .table-prateleiras th.text-center {
            text-align: center !important;
        }
        .table-prateleiras td {
            text-align: center !important;
        }
        .table-prateleiras td:first-child,
        .table-prateleiras th:first-child {
            text-align: center;
            width: 40px;
        }
        .table-responsive {
            overflow-x: auto !important;
        }

        /* ---------------------------------------------------------- */
        /* 3) BOTÕES DE AÇÃO (editar/excluir/recuperar/etiqueta única) */
        /* ---------------------------------------------------------- */
        .acoes-prateleira .btn-custom {
            margin-right: 4px !important;
            margin-bottom: 2px !important;
            padding: 4px 8px !important;
            font-size: 12px !important;
        }
        .acoes-prateleira .btn-custom i {
            font-size: 12px !important;
        }

        /* ---------------------------------------------------------- */
        /* 4) COLUNA DATAS (fontes menores, uma embaixo da outra)     */
        /* ---------------------------------------------------------- */
        .col-datas small {
            display: block;
            font-size: 11px;
            line-height: 1.2;
        }

        /* ---------------------------------------------------------- */
        /* 5) MODAL MANUAL DE ORIENTAÇÕES                             */
        /* ---------------------------------------------------------- */
        #modal-manual .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        #modal-manual h5 {
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        #modal-manual p {
            font-size: 14px;
            line-height: 1.5;
        }

        /* ---------------------------------------------------------- */
        /* 6) MODAL IMPRESSÃO DE ETIQUETAS                             */
        /* ---------------------------------------------------------- */
        #modal-etiquetas .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        .etiqueta {
            display: inline-block;
            border: 1px dashed #333;      /* picote para recortar */
            padding: 8px;
            margin: 6px;
            text-align: center;
            width: 180px;
            vertical-align: top;
            font-size: 12px;
        }
        .etiqueta .qr-code {
            margin-bottom: 5px;
        }
        .etiqueta p {
            margin: 2px 0;
            font-size: 13px;
        }
        .etiqueta small {
            display: block;
            font-size: 10px;
            color: #555;
        }
        @media print {
            .etiqueta {
                page-break-inside: avoid;
            }
        }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="card card-custom gutter-b">
        <div class="card-body">
            {{-- ======================================================= --}}
            {{-- 1) BOTÕES SUPERIORES: MANUAL / NOVA / IMPRIMIR ETIQUETAS --}}
            {{-- ======================================================= --}}
            <div class="@if(env('ANIMACAO')) animate__animated animate__backInLeft @endif">
                <div class="row">
                    <button id="btn-manual" class="btn btn-sm btn-secondary">
                        <i class="fa fa-info-circle"></i> Manual de Orientações
                    </button>
                    <button id="btn-nova-prateleira" class="btn btn-sm btn-success">
                        <i class="fa fa-plus"></i> Nova Prateleira
                    </button>
                    <button id="btn-imprimir-etiquetas" class="btn btn-sm btn-info">
                        <i class="fa fa-barcode"></i> Imprimir Etiquetas
                    </button>
                </div>
            </div>
            <br>

            {{-- ======================================================= --}}
            {{-- 2) FILTROS: identificação, descrição, filial, por página --}}
            {{-- ======================================================= --}}
            <form method="get" action="/produto_prateleiras/">
                <div class="row align-items-end">
                    {{-- IDENTIFICAÇÃO --}}
                    <div class="form-group col-lg-3 col-xl-3">
                        <label class="col-form-label">Identificação</label>
                        <input
                            type="text"
                            name="identificacao"
                            class="form-control"
                            value="{{ request()->get('identificacao','') }}"
                            placeholder="Ex.: A-1-02-B-03"
                        >
                    </div>
                    {{-- DESCRIÇÃO --}}
                    <div class="form-group col-lg-3 col-xl-3">
                        <label class="col-form-label">Descrição</label>
                        <input
                            type="text"
                            name="descricao"
                            class="form-control"
                            value="{{ request()->get('descricao','') }}"
                            placeholder="Descrição breve"
                        >
                    </div>

                    <div class="form-group col-lg-1 col-xl-1">
                        <label class="col-form-label">Por página</label>
                        <select name="por_pagina" class="form-control">
                            @foreach([5,10,20,50,100] as $n)
                                <option value="{{ $n }}"
                                    {{ (request()->get('por_pagina',10)==$n) ? 'selected' : '' }}>
                                    {{ $n }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- FILIAL --}}
                    @if(empresaComFilial())
                        {!! __view_locais_select_filtro("Local", isset($filial_id) ? $filial_id : '') !!}
                    @endif
                    {{--  --}}
                    {{-- BOTÃO FILTRAR --}}
                    <div class="form-group col-lg-2 col-xl-2">
                        <button type="submit" class="btn btn-light-primary font-weight-bold">
                            <i class="fa fa-filter"></i> Filtrar
                        </button>
                    </div>
                </div>
                {{-- POR PÁGINA --}}
            </form>
            <br>

            {{-- Exibe o total de registros encontrados, igual ao que o view de produtos faz --}}
            <p>
                Total de prateleiras cadastradas:
                <strong class="text-info">{{ $records->total() }}</strong>
            </p>

            {{-- ======================================================= --}}
            {{-- 3) TÍTULO E BOTÃO TOGGLE DE EXCLUÍDAS                  --}}
            {{-- ======================================================= --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="card-title mb-0">Lista de Prateleiras [Endereçamento de Estoque]</h4>
                @php
                    $baseUrl    = request()->url();
                    $otherQuery = request()->except(['mostrar_excluidas','page']);
                    $queryStr   = http_build_query($otherQuery);
                    $urlClean   = $baseUrl . ($queryStr ? ('?' . $queryStr) : '');
                    $withExcl   = array_merge($otherQuery, ['mostrar_excluidas' => 1]);
                    $urlShow    = $baseUrl . '?' . http_build_query($withExcl);
                @endphp

                @if(request()->get('mostrar_excluidas'))
                    <a href="{{ $urlClean }}" class="btn btn-sm btn-outline-warning">
                        <i class="fa fa-eye-slash"></i> Ocultar excluídas
                    </a>
                @else
                    <a href="{{ $urlShow }}" class="btn btn-sm btn-outline-danger">
                        <i class="fa fa-eye"></i> Mostrar excluídas
                    </a>
                @endif
            </div>
            <br>

            {{-- ======================================================= --}}
            {{-- 4) TABELA RESPONSIVA COM PAGINAÇÃO                     --}}
            {{-- ======================================================= --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-prateleiras">
                    <thead>
                    <tr>
                        <th class="text-center"><input type="checkbox" id="select-all"></th>
                        <th class="text-center">IDENTIFICAÇÃO</th>
                        <th class="text-center">DESCRIÇÃO</th>
                        <th class="text-center">POSIÇÃO</th>
                        <th class="text-center">LOCALIZAÇÃO</th>
                        <th class="text-center">OBSERVAÇÃO</th>
                        <th class="text-center">DATAS</th>
                        <th class="text-center" style="min-width: 180px;">AÇÕES</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $item)
                        <tr @if($item->trashed()) class="table-danger" @endif>
                            <td><input type="checkbox" class="chk-prateleira" value="{{ $item->id }}"></td>
                            <td>{{ $item->identificacao }}</td>
                            <td>{{ $item->descricao }}</td>
                            <td>{{ $item->posicao }}</td>
                            <td>{{ $item->localizacao }}</td>
                            <td>{{ Str::limit($item->observacao, 40) }}</td>
                            <td class="col-datas">
                                <small>Cadastrado: {{ $item->created_at->format('d/m/Y') }}</small>
                                <small>Atualizado: {{ $item->updated_at->format('d/m/Y') }}</small>
                                @if($item->deleted_at)
                                    <small>Removido: {{ $item->deleted_at->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td class="acoes-prateleira">
                                @if(!$item->trashed())
                                    <button class="btn btn-sm btn-warning btn-custom btn-edit" data-id="{{ $item->id }}">
                                        <i class="la la-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-custom btn-delete" data-id="{{ $item->id }}">
                                        <i class="la la-trash"></i>
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-success btn-custom btn-restore" data-id="{{ $item->id }}">
                                        <i class="la la-recycle"></i>
                                    </button>
                                @endif
                                <button class="btn btn-sm btn-dark btn-custom btn-etiqueta-unica" data-id="{{ $item->id }}">
                                    <i class="la la-barcode"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Nenhuma prateleira encontrada.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ======================================================= --}}
            {{-- 5) PAGINAÇÃO                                           --}}
            {{-- ======================================================= --}}
            <div class="d-flex justify-content-center mt-3">
                {{ $records->appends(request()->except('page'))->links() }}
            </div>

        </div>

    {{-- ======================================================= --}}
    {{-- 6) MODAL: Manual de Orientações                        --}}
    {{-- ======================================================= --}}
    <div class="modal fade" id="modal-manual" tabindex="-1" role="dialog" aria-labelledby="modalManualLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="modalManualLabel" class="modal-title">Manual de Orientações para Cadastro de Prateleiras</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    {{-- TODO: Coloque aqui todo o texto do manual, exatamente como no exemplo anterior. --}}
                    <h5>1. Objetivo</h5>
                    <p>
                        Este manual explica passo a passo como criar e gerenciar prateleiras no sistema,
                        garantindo que cada uma tenha uma identificação única e seja localizada corretamente.
                    </p>

                    <h5>2. Padrão de Identificação de Prateleiras</h5>
                    <p>
                        A identificação deve seguir o padrão alfanumérico hierárquico:
                    </p>
                    <ul>
                        <li><strong>Formato geral:</strong> <code>ÁREA-CORREDOR-NÍVEL-PRATELEIRA-POSIÇÃO</code></li>
                        <li><strong>Exemplo:</strong> <code>A-1-02-B-03</code>
                            <ul>
                                <li><strong>A</strong> = Área</li>
                                <li><strong>1</strong> = Corredor</li>
                                <li><strong>02</strong> = Nível (dois dígitos)</li>
                                <li><strong>B</strong> = Prateleira dentro do nível</li>
                                <li><strong>03</strong> = Posição dentro da prateleira (dois dígitos)</li>
                            </ul>
                        </li>
                        <li>Exemplos adicionais:
                            <ul>
                                <li><code>B-2-01-A-01</code></li>
                                <li><code>Z01-A-3-C-05</code></li>
                            </ul>
                        </li>
                    </ul>

                    <h5>3. Campos do Formulário</h5>
                    <ul>
                        <li><strong>Identificação</strong> (obrigatório): única, no formato acima.</li>
                        <li><strong>Descrição</strong> (opcional): até 100 caracteres.</li>
                        <li><strong>Posição</strong> (opcional): até 10 caracteres.</li>
                        <li><strong>Localização</strong> (opcional): até 100 caracteres.</li>
                        <li><strong>Observação</strong> (opcional): texto livre.</li>
                    </ul>

                    <h5>4. Passo a passo na Tela de Listagem</h5>
                    <ol>
                        <li>
                            Clique em <strong>“Manual de Orientações”</strong> para consultar este guia.
                        </li>
                        <li>
                            Para criar nova prateleira:
                            <ul>
                                <li>Clique em <strong>“Nova Prateleira”</strong>.</li>
                                <li>Preencha <strong>Identificação</strong> (única e no padrão).</li>
                                <li>Preencha os campos opcionais (<strong>Descrição</strong>, <strong>Posição</strong>, <strong>Localização</strong>, <strong>Observação</strong>).</li>
                                <li>Clique em <strong>“Salvar”</strong>. Caso a identificação já exista, o sistema avisará.</li>
                            </ul>
                        </li>
                        <li>
                            Para editar:
                            <ul>
                                <li>Use os filtros acima para localizar a prateleira.</li>
                                <li>Clique no botão ✎ (Editar). O modal abrirá preenchido.</li>
                                <li>Faça as alterações e clique em <strong>“Salvar”</strong>.</li>
                            </ul>
                        </li>
                        <li>
                            Para excluir/recuperar:
                            <ul>
                                <li>Clique no botão 🗑 (Excluir). Será solicitada confirmação.</li>
                                <li>Para ver itens excluídos, clique no botão <strong>“Mostrar excluídas”</strong> acima.</li>
                                <li>Ao exibir excluídos, cada linha mostra ♻ (Recuperar) em vez do botão Excluir.</li>
                            </ul>
                        </li>
                        <li>
                            Para imprimir etiquetas:
                            <ul>
                                <li>Marque as caixas à esquerda das linhas desejadas.</li>
                                <li>Clique em <strong>“Imprimir Etiquetas”</strong>.</li>
                                <li>No modal, visualize uma prévia das etiquetas (QR, identificação, descrição, posição e localização) e clique em “Imprimir”.</li>
                            </ul>
                        </li>
                    </ol>

                    <h5>5. Boas práticas</h5>
                    <ul>
                        <li>Use a mesma máscara de identificação em todo o depósito.</li>
                        <li>Não duplique identificações — o sistema impede no back-end.</li>
                        <li>Se imprimir várias etiquetas, recorte ao longo da linha tracejada.</li>
                        <li>Mantenha as descrições e observações curtas para não sobrecarregar a label.</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button id="btn-print-manual" type="button" class="btn btn-primary">
                        <i class="fa fa-print"></i> Imprimir Manual
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- 7) MODAL: Cadastro / Edição de Prateleira               --}}
    {{-- ======================================================= --}}
    <div class="modal fade" id="modal-prateleira" tabindex="-1" role="dialog" aria-labelledby="modalPrateleiraLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form id="form-prateleira" method="POST" action="/produto_prateleiras/save">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="modalPrateleiraLabel" class="modal-title">Nova Prateleira  [Endereçamento de Estoque]</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="prateleira_id" name="id" value="">

                        <div class="form-group">
                            <label for="identificacao">Identificação <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="identificacao"
                                   id="identificacao"
                                   class="form-control"
                                   maxlength="50"
                                   required
                                   oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '')">
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="descricao">Descrição</label>
                                <input type="text"
                                       name="descricao"
                                       id="descricao"
                                       class="form-control"
                                       maxlength="100">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="posicao">Posição</label>
                                <input type="text"
                                       name="posicao"
                                       id="posicao"
                                       class="form-control"
                                       maxlength="10">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="localizacao">Localização</label>
                                <input type="text"
                                       name="localizacao"
                                       id="localizacao"
                                       class="form-control"
                                       maxlength="100">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="observacao">Observação</label>
                            <textarea name="observacao"
                                      id="observacao"
                                      class="form-control"
                                      rows="3"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btn-salvar-prateleira">
                            Salvar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- 8) MODAL: Impressão de Etiquetas                       --}}
    {{-- ======================================================= --}}
    <div class="modal fade" id="modal-etiquetas" tabindex="-1" role="dialog" aria-labelledby="modalEtiquetasLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document" style="max-width: 90%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pré-Visualização de Etiquetas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <iframe id="iframe-etiquetas"
                            style="width:100%; height:70vh;"
                            frameborder="0"></iframe>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Fechar
                    </button>
                    <button id="btn-print-iframe" type="button" class="btn btn-primary">
                        <i class="fa fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            // ---------------------------
            // 1) Seletor “Marcar tudo”
            // ---------------------------
            $('#select-all').on('change', function() {
                $('.chk-prateleira').prop('checked', $(this).prop('checked'));
            });
            $(document).on('change', '.chk-prateleira', function() {
                if (!$(this).prop('checked')) {
                    $('#select-all').prop('checked', false);
                }
            });

            // ---------------------------
            // 2) Abrir modal MANUAL
            // ---------------------------
            $('#btn-manual').on('click', function(e) {
                e.preventDefault();
                $('#modal-manual').modal('show');
            });

            // ---------------------------
            // 3) Botão “Imprimir Manual”
            // ---------------------------
            $('#btn-print-manual').on('click', function() {
                let modalBody = document.querySelector('#modal-manual .modal-body');
                let printWindow = window.open('', '', 'height=700,width=900');
                printWindow.document.write('<html><head><title>Manual de Orientações</title>');
                printWindow.document.write('</head><body>');
                printWindow.document.write(modalBody.innerHTML);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.focus();
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 500);
            });

            // ---------------------------
            // 4) Botão “Nova Prateleira”
            // ---------------------------
            $('#btn-nova-prateleira').on('click', function(e) {
                e.preventDefault();
                $('#modalPrateleiraLabel').text('Nova Prateleira  [Endereçamento de Estoque]');
                $('#form-prateleira').attr('action', '/produto_prateleiras/save');
                $('#prateleira_id').val('');
                $('#identificacao').val('').removeClass('is-invalid');
                $('#descricao').val('');
                $('#posicao').val('');
                $('#localizacao').val('');
                $('#observacao').val('');
                $('#modal-prateleira').modal('show');
            });

            // ---------------------------
            // 5) Botão “Editar”
            // ---------------------------
            $(document).on('click', '.btn-edit', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                $.ajax({
                    url: '/produto_prateleiras/' + id + '/json',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        $('#modalPrateleiraLabel').text('Editando Prateleira #' + data.id);
                        $('#form-prateleira').attr('action', '/produto_prateleiras/update/' + data.id);
                        $('#prateleira_id').val(data.id);
                        $('#identificacao').val(data.identificacao).removeClass('is-invalid');
                        $('#descricao').val(data.descricao);
                        $('#posicao').val(data.posicao);
                        $('#localizacao').val(data.localizacao);
                        $('#observacao').val(data.observacao);
                        $('#modal-prateleira').modal('show');
                    },
                    error: function(err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Não foi possível carregar os dados da prateleira.'
                        });
                    }
                });
            });

            // ---------------------------
            // 6) Botão “Excluir”
            // ---------------------------
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Deseja realmente excluir essa prateleira?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, excluir',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '/produto_prateleiras/delete/' + id;
                    }
                });
            });

            // ---------------------------
            // 7) Botão “Recuperar”
            // ---------------------------
            $(document).on('click', '.btn-restore', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                Swal.fire({
                    title: 'Confirmação',
                    text: 'Deseja restaurar esta prateleira?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, restaurar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '/produto_prateleiras/restore/' + id;
                    }
                });
            });

            // ---------------------------
            // 8) Etiqueta única
            // ---------------------------
            $(document).on('click', '.btn-etiqueta-unica', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                let url = '/produto_prateleiras/etiquetas?ids[]=' + id;
                $('#iframe-etiquetas').attr('src', url);
                $('#modal-etiquetas').modal('show');
            });

            // ---------------------------
            // 9) Etiquetas (múltiplas)
            // ---------------------------
            $('#btn-imprimir-etiquetas').on('click', function(e) {
                e.preventDefault();
                let ids = [];
                $('.chk-prateleira:checked').each(function() {
                    ids.push($(this).val());
                });
                if (ids.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Aviso',
                        text: 'Selecione ao menos uma prateleira para gerar etiqueta.'
                    });
                    return;
                }
                let query = ids.map(i => 'ids[]=' + i).join('&');
                let url = '/produto_prateleiras/etiquetas?' + query;
                $('#iframe-etiquetas').attr('src', url);
                $('#modal-etiquetas').modal('show');
            });

            // ---------------------------
            // 10) Imprimir Etiquetas
            // ---------------------------
            $('#btn-print-iframe').on('click', function() {
                let iframe = document.getElementById('iframe-etiquetas');
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            });
        });
    </script>
@endsection
