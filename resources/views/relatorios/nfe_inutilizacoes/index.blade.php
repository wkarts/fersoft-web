@extends('default.layout')

@section('content')
    <div class="nfe-inutilizacoes-report">
        @php
            // Ambiente fiscal auto detectado (mesmo padrão da tela de Gaps)
            $ambienteNFeCode = null;

            if (isset($ambienteNFe) && in_array((int) $ambienteNFe, [1, 2], true)) {
                $ambienteNFeCode = (int) $ambienteNFe;
            } elseif (isset($configSystemWhats) && isset($configSystemWhats->ambiente)) {
                $ambienteNFeCode = (int) $configSystemWhats->ambiente;
            }

            if (! in_array($ambienteNFeCode, [1, 2], true)) {
                $ambienteNFeCode = 1; // fallback Produção
            }

            $ambienteNFeLabel = $ambienteNFeCode === 1 ? 'Produção' : 'Homologação';
            $ambienteTextoPadrao = $ambienteNFeCode === 1 ? 'producao' : 'homologacao';
        @endphp

        <style>
            .nfe-inutilizacoes-report .card-title {
                font-size: 1.05rem;
                font-weight: 600;
                margin-bottom: 0.25rem;
            }

            .nfe-inutilizacoes-report .card-subtitle {
                font-size: 0.8rem;
                color: #6c757d;
                line-height: 1.3;
            }

            .nfe-inutilizacoes-report .filters-label {
                font-size: 0.8rem;
                font-weight: 500;
            }

            .nfe-inutilizacoes-report .filters-container .form-control {
                font-size: 0.8rem;
            }

            .nfe-inutilizacoes-report .table thead th {
                font-size: 0.75rem;
                white-space: nowrap;
                vertical-align: middle;
            }

            .nfe-inutilizacoes-report .table tbody td {
                font-size: 0.75rem;
                vertical-align: middle;
            }

            .nfe-inutilizacoes-report .pagination-summary {
                font-size: 0.8rem;
            }

            .nfe-inutilizacoes-report .badge-status {
                font-size: 0.7rem;
                font-weight: 500;
            }

            /* Cards mobile */
            .nfe-inutilizacoes-report .inut-card {
                border: 1px solid #e5e5e5;
                border-radius: 0.35rem;
                padding: 0.6rem 0.7rem;
                margin-bottom: 0.7rem;
                background-color: #fff;
            }

            .nfe-inutilizacoes-report .inut-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 0.8rem;
                margin-bottom: 0.3rem;
            }

            .nfe-inutilizacoes-report .inut-card-subtitle {
                font-size: 0.74rem;
                color: #666;
            }

            .nfe-inutilizacoes-report .inut-card-line {
                font-size: 0.74rem;
                display: flex;
                justify-content: space-between;
            }

            .nfe-inutilizacoes-report .inut-card-line span:first-child {
                color: #666;
            }

            @media (max-width: 767.98px) {
                .nfe-inutilizacoes-report .card-title {
                    font-size: 0.95rem;
                }
                .nfe-inutilizacoes-report .card-subtitle {
                    font-size: 0.72rem;
                }
                .nfe-inutilizacoes-report .filters-label {
                    font-size: 0.75rem;
                }
            }

            /* Área de diagnóstico da faixa - evitar que o modal "estoure" na horizontal */
            .nfe-inutilizacoes-report #inut_manual_diagnostico_result {
                max-width: 100%;
                overflow-y: auto;
                overflow-x: hidden;     /* não deixa criar scroll horizontal no modal */
                white-space: pre-wrap;  /* quebra linha mantendo o estilo de <pre> */
                word-break: break-word; /* permite quebrar sequências longas de números */
                box-sizing: border-box; /* respeitar padding dentro da largura */
            }
        </style>

        <div class="card gutter-b">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                    <div>
                        <h4 class="card-title">Inutilizações de NF-e</h4>
                        <br>
                        <div class="card-subtitle">
                            Histórico de faixas de numeração inutilizadas pela SEFAZ (NF-e modelo 55 e 65).
                        </div>
                        <div class="card-subtitle mt-1">
                            <strong>Ambiente NF-e atual:</strong>
                            <span class="badge badge-{{ $ambienteNFeCode === 1 ? 'success' : 'warning' }}">
                                {{ $ambienteNFeLabel }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0 text-md-right">
                        <button type="button"
                                class="btn btn-outline-info btn-sm mb-1"
                                data-toggle="modal"
                                data-target="#modalManualInutilizacoes">
                            <i class="la la-question-circle"></i> Manual / Orientações
                        </button>
                        <button type="button"
                                class="btn btn-outline-primary btn-sm mb-1"
                                id="btnNovaInutilizacaoManual">
                            <i class="la la-ban"></i> Nova inutilização manual
                        </button>
                        <a href="{{ route('relatorios.nfe_inutilizacoes.pdf', request()->query()) }}"
                           target="_blank"
                           class="btn btn-outline-success btn-sm mb-1">
                            <i class="la la-file-pdf-o"></i> PDF
                        </a>
                    </div>
                </div>

                {{-- FILTROS --}}
                <form method="GET" action="{{ route('relatorios.nfe_inutilizacoes.index') }}" class="filters-container mb-3">
                    <div class="form-row">
                        <div class="form-group col-md-3 col-12">
                            <label class="filters-label">Filial</label>
                            <select name="filial_id" class="form-control">
                                <option value="">Matriz</option>
                                @foreach($filiais as $filial)
                                    <option value="{{ $filial->id }}"
                                        {{ $filters['filial_id'] == $filial->id ? 'selected' : '' }}>
                                        {{ $filial->nome_fantasia }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-2 col-6">
                            <label class="filters-label">Modelo</label>
                            <select name="modelo" class="form-control">
                                <option value="">Todos</option>
                                <option value="55" {{ $filters['modelo'] == '55' ? 'selected' : '' }}>55</option>
                                <option value="65" {{ $filters['modelo'] == '65' ? 'selected' : '' }}>65</option>
                            </select>
                        </div>

                        <div class="form-group col-md-2 col-6">
                            <label class="filters-label">Série</label>
                            <input type="number"
                                   name="serie"
                                   class="form-control"
                                   value="{{ $filters['serie'] }}">
                        </div>

                        <div class="form-group col-md-2 col-6">
                            <label class="filters-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">Todos</option>
                                <option value="autorizada" {{ $filters['status'] == 'autorizada' ? 'selected' : '' }}>Autorizada</option>
                                <option value="rejeitada"  {{ $filters['status'] == 'rejeitada'  ? 'selected' : '' }}>Rejeitada</option>
                                <option value="erro"       {{ $filters['status'] == 'erro'       ? 'selected' : '' }}>Erro</option>
                                <option value="pendente"   {{ $filters['status'] == 'pendente'   ? 'selected' : '' }}>Pendente</option>
                            </select>
                        </div>

                        {{-- Ambiente NÃO é mais alterável pelo usuário.
                             Mantido apenas no header, como badge, usando o ambiente padrão da empresa. --}}
                        {{--
                        <div class="form-group col-md-3 col-6">
                            <label class="filters-label d-block">Ambiente</label>
                            <span class="badge badge-{{ $ambienteNFeCode === 1 ? 'success' : 'warning' }}">
                                {{ $ambienteNFeLabel }} (fixo)
                            </span>
                        </div>
                        --}}
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-2 col-6">
                            <label class="filters-label">Nº Inicial ≥</label>
                            <input type="number"
                                   name="numero_inicial"
                                   class="form-control"
                                   value="{{ $filters['numero_inicial'] }}">
                        </div>

                        <div class="form-group col-md-2 col-6">
                            <label class="filters-label">Nº Final ≤</label>
                            <input type="number"
                                   name="numero_final"
                                   class="form-control"
                                   value="{{ $filters['numero_final'] }}">
                        </div>

                        <div class="form-group col-md-2 col-6">
                            <label class="filters-label">Data inicial</label>
                            <input type="date"
                                   name="data_inicial"
                                   class="form-control"
                                   value="{{ $filters['data_inicial'] }}">
                        </div>

                        <div class="form-group col-md-2 col-6">
                            <label class="filters-label">Data final</label>
                            <input type="date"
                                   name="data_final"
                                   class="form-control"
                                   value="{{ $filters['data_final'] }}">
                        </div>

                        <div class="form-group col-md-4 d-flex align-items-end justify-content-end">
                            <button type="submit" class="btn btn-primary mr-2 btn-sm">
                                <i class="la la-filter"></i> Filtrar
                            </button>
                            <a href="{{ route('relatorios.nfe_inutilizacoes.index') }}" class="btn btn-secondary mr-2 btn-sm">
                                <i class="la la-eraser"></i> Limpar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABELA DESKTOP --}}
        <div class="card d-none d-md-block">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped mb-0">
                        <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Filial</th>
                            <th>Modelo</th>
                            <th>Série</th>
                            <th>Faixa</th>
                            <th>Ano</th>
                            <th>Ambiente</th>
                            <th>Status</th>
                            <th>Protocolo</th>
                            <th>Origem</th>
                            <th>Usuário</th>
                            <th>Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($registros as $item)
                            @php
                                $filialNome = 'Matriz';
                                if ($item->filial_id) {
                                    $f = $filiais->firstWhere('id', $item->filial_id);
                                    if ($f) $filialNome = $f->nome_fantasia;
                                }
                            @endphp
                            <tr>
                                <td>{{ optional($item->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $filialNome }}</td>
                                <td>{{ $item->modelo }}</td>
                                <td>{{ $item->serie }}</td>
                                <td>{{ $item->numero_inicial }} &rarr; {{ $item->numero_final }}</td>
                                <td>{{ $item->ano }}</td>
                                <td>
                                    @if($item->ambiente === 'producao')
                                        <span class="badge badge-success badge-status">Produção</span>
                                    @elseif($item->ambiente === 'homologacao')
                                        <span class="badge badge-warning badge-status">Homologação</span>
                                    @else
                                        <span class="badge badge-secondary badge-status">{{ $item->ambiente ?? '—' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->status === 'autorizada')
                                        <span class="badge badge-success badge-status">Autorizada</span>
                                    @elseif($item->status === 'rejeitada')
                                        <span class="badge badge-danger badge-status">Rejeitada</span>
                                    @elseif($item->status === 'erro')
                                        <span class="badge badge-warning badge-status">Erro</span>
                                    @elseif($item->status === 'pendente')
                                        <span class="badge badge-secondary badge-status">Pendente</span>
                                    @else
                                        <span class="badge badge-light badge-status">{{ $item->status ?? '—' }}</span>
                                    @endif
                                </td>
                                <td>{{ $item->protocolo }}</td>
                                <td>{{ $item->origem }}</td>
                                <td>
                                    @php
                                        $usuarioNome = null;
                                        if ($item->usuario) {
                                            $usuarioNome = $item->usuario->nome ?? $item->usuario->nome ?? null;
                                        }
                                    @endphp

                                    {{ $usuarioNome ?? $item->usuario_id ?? '—' }}
                                </td>

                                <td class="text-center">
                                    <a href="{{ route('relatorios.nfe_inutilizacoes.relatorio_individual', $item->id) }}"
                                       class="btn btn-outline-info btn-xs"
                                       title="Relatório individual da inutilização"
                                       target="_blank">
                                        <i class="la la-file-text-o"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center">
                                    Nenhuma inutilização encontrada para os filtros informados.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginação --}}
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
                    <div class="pagination-summary mb-2 mb-md-0">
                        @if($registros->total() > 0)
                            Mostrando
                            <strong>{{ $registros->firstItem() }}</strong>
                            até
                            <strong>{{ $registros->lastItem() }}</strong>
                            de
                            <strong>{{ $registros->total() }}</strong>
                            registros.
                        @else
                            Nenhum registro encontrado.
                        @endif
                    </div>
                    <div>
                        {{ $registros->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- LISTA MOBILE (cards) --}}
        <div class="card d-block d-md-none">
            <div class="card-body">
                @forelse($registros as $item)
                    @php
                        $filialNome = 'Matriz';
                        if ($item->filial_id) {
                            $f = $filiais->firstWhere('id', $item->filial_id);
                            if ($f) $filialNome = $f->nome_fantasia;
                        }
                    @endphp
                    <div class="inut-card">
                        <div class="inut-card-header">
                            <strong>{{ $filialNome }}</strong>
                            <span>Modelo {{ $item->modelo }} - Série {{ $item->serie }}</span>
                        </div>
                        <div class="inut-card-subtitle">
                            Faixa: <strong>{{ $item->numero_inicial }} &rarr; {{ $item->numero_final }}</strong><br>
                            Ano: {{ $item->ano }} |
                            Ambiente:
                            @if($item->ambiente === 'producao')
                                Produção
                            @elseif($item->ambiente === 'homologacao')
                                Homologação
                            @else
                                {{ $item->ambiente ?? '—' }}
                            @endif
                        </div>

                        <div class="inut-card-line mt-1">
                            <span>Data/Hora</span>
                            <span>{{ optional($item->created_at)->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="inut-card-line">
                            <span>Status</span>
                            <span>
                                @if($item->status === 'autorizada')
                                    <span class="badge badge-success badge-status">Autorizada</span>
                                @elseif($item->status === 'rejeitada')
                                    <span class="badge badge-danger badge-status">Rejeitada</span>
                                @elseif($item->status === 'erro')
                                    <span class="badge badge-warning badge-status">Erro</span>
                                @elseif($item->status === 'pendente')
                                    <span class="badge badge-secondary badge-status">Pendente</span>
                                @else
                                    <span class="badge badge-light badge-status">{{ $item->status ?? '—' }}</span>
                                @endif
                            </span>
                        </div>
                        <div class="inut-card-line">
                            <span>Protocolo</span>
                            <span>{{ $item->protocolo }}</span>
                        </div>
                        <div class="inut-card-line">
                            <span>Origem</span>
                            <span>{{ $item->origem }}</span>
                        </div>

                        <div class="mt-2 text-right">
                            <a href="{{ route('relatorios.nfe_inutilizacoes.relatorio_individual', $item->id) }}"
                               class="btn btn-outline-info btn-xs"
                               target="_blank">
                                Relatório
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="mb-0 text-center" style="font-size: 0.8rem;">
                        Nenhuma inutilização encontrada.
                    </p>
                @endforelse

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="pagination-summary">
                        @if($registros->total() > 0)
                            Mostrando
                            <strong>{{ $registros->firstItem() }}</strong>
                            até
                            <strong>{{ $registros->lastItem() }}</strong>
                            de
                            <strong>{{ $registros->total() }}</strong>
                            registros.
                        @else
                            Nenhum registro encontrado.
                        @endif
                    </div>
                    <div style="font-size: 0.8rem;">
                        {{ $registros->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Manual de utilização (já existia, mantido) --}}
    <div class="modal fade" id="modalManualInutilizacoes" tabindex="-1" role="dialog" aria-labelledby="modalManualInutilizacoesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalManualInutilizacoesLabel">Manual de Utilização – Inutilizações de NF-e</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="font-size: 0.85rem;">
                    <p>
                        Esta tela apresenta todas as faixas de numeração de NF-e inutilizadas pela SEFAZ
                        (modelos 55 e 65), com filtros por filial, modelo, série, status, faixa e período.
                    </p>
                    <ul>
                        <li><strong>Filtros:</strong> ajuste os campos no topo e clique em <em>Filtrar</em>.</li>
                        <li><strong>PDF:</strong> gera um relatório consolidado conforme os filtros aplicados.</li>
                        <li>
                            <strong>Nova inutilização manual:</strong> permite informar manualmente filial, série,
                            faixa e justificativa para inutilizar uma numeração específica.
                        </li>
                        <li>
                            <strong>Relatório individual:</strong> cada linha possui um botão que abre um relatório
                            individual com os detalhes da inutilização e resumo do XML de retorno da SEFAZ.
                        </li>
                        <li>
                            O <strong>ambiente de envio</strong> (Produção/Homologação) é sempre o mesmo
                            configurado nos parâmetros da empresa, não sendo possível alterá-lo nesta tela.
                        </li>
                    </ul>
                    <p>
                        Recomenda-se sempre conferir o relatório de gaps antes de inutilizar faixas extensas
                        e manter a coerência entre o ambiente configurado (produção/homologação) e o ambiente da SEFAZ.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Inutilização Manual --}}
    <div class="modal fade" id="modalInutilizacaoManual" tabindex="-1" role="dialog" aria-labelledby="modalInutilizacaoManualLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <form id="formInutilizacaoManual"
                  method="POST"
                  action="{{ route('relatorios.nfe_inutilizacoes.inutilizar_manual') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="modalInutilizacaoManualLabel">
                            Inutilização Manual de Numeração de NF-e
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="font-size: 0.85rem;">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label>Filial</label>
                                <select name="filial_id" id="inut_manual_filial_id" class="form-control">
                                    <option value="">Matriz</option>
                                    @foreach($filiais as $filial)
                                        <option value="{{ $filial->id }}">{{ $filial->nome_fantasia }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    Deixe em branco para Matriz.
                                </small>
                            </div>

                            <div class="form-group col-md-2">
                                <label>Modelo</label>
                                <select name="modelo" id="inut_manual_modelo" class="form-control">
                                    <option value="55">55</option>
                                    <option value="65">65</option>
                                </select>
                            </div>

                            <div class="form-group col-md-2">
                                <label>Série</label>
                                <input type="number"
                                       name="serie"
                                       id="inut_manual_serie"
                                       class="form-control"
                                       required>
                            </div>

                            <div class="form-group col-md-2">
                                <label>Nº Inicial</label>
                                <input type="number"
                                       name="numero_inicial"
                                       id="inut_manual_numero_inicial"
                                       class="form-control"
                                       required>
                            </div>

                            <div class="form-group col-md-2">
                                <label>Nº Final</label>
                                <input type="number"
                                       name="numero_final"
                                       id="inut_manual_numero_final"
                                       class="form-control"
                                       required>
                            </div>
                        </div>

                        <div class="row">
                            {{--
                            <div class="form-group col-md-3">
                                <label>Ano (AA)</label>
                                <input type="text"
                                       name="ano"
                                       id="inut_manual_ano"
                                       class="form-control"
                                       value="{{ \Carbon\Carbon::now()->format('y') }}">
                            </div>
                            --}}
                            <div class="form-group col-md-3">
                                <label>Ambiente</label>
                                <div>
                                    <span class="badge badge-{{ $ambienteNFeCode === 1 ? 'success' : 'warning' }}">
                                        {{ $ambienteNFeLabel }}
                                    </span>
                                </div>
                                {{-- Campo hidden com o valor real enviado para o backend --}}
                                <input type="hidden"
                                       name="ambiente"
                                       id="inut_manual_ambiente_hidden"
                                       value="{{ $ambienteTextoPadrao }}">
                                <small class="form-text text-muted">
                                    Ambiente fixo conforme configuração da empresa.
                                </small>
                            </div>

                            <div class="form-group col-md-9 col-12">
                                <label>Justificativa</label>
                                <textarea name="justificativa"
                                          id="inut_manual_justificativa"
                                          class="form-control"
                                          rows="3"
                                          maxlength="255"
                                          required></textarea>
                                <small class="form-text text-muted">
                                    A SEFAZ exige justificativa entre 15 e 255 caracteres.
                                </small>
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="form-group col-md-12">
                                <button type="button"
                                        class="btn btn-outline-info btn-sm"
                                        id="btnDiagnosticarFaixa">
                                    <i class="la la-search"></i> Diagnosticar faixa (ver números já utilizados)
                                </button>
                            </div>
                            <div class="form-group col-md-12">
                                <pre id="inut_manual_diagnostico_result"
                                     style="display:none; font-size:0.75rem; background:#f8f9fa; padding:0.5rem; border-radius:0.25rem; max-height:200px; overflow:auto;"></pre>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-light"
                                data-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="btn btn-light-success">
                            Confirmar inutilização
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================= --}}
    {{-- MODAIS REUTILIZÁVEIS     --}}
    {{-- ========================= --}}

    <!-- Modal Reutilizável de Confirmação -->
    <div class="modal fade" id="modalConfirmacao" tabindex="-1" role="dialog" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmação</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalConfirmacaoMensagem">
                    Deseja realmente continuar?
                </div>
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
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMensagemLabel">Mensagem</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalMensagemTexto">
                    Mensagem exibida aqui.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    @parent
    <script>
        // Empilhamento de múltiplos modais (mesmo padrão da tela de Gaps / Pesagens)
        $(document).on('show.bs.modal', '.modal', function () {
            const visibleModals = $('.modal:visible').length;
            const zIndex = 1040 + (10 * visibleModals);

            $(this).css('z-index', zIndex);

            setTimeout(function () {
                $('.modal-backdrop').not('.modal-stack')
                    .css('z-index', zIndex - 1)
                    .addClass('modal-stack');
            }, 0);
        });

        $(document).on('hidden.bs.modal', '.modal', function () {
            if ($('.modal:visible').length) {
                $('body').addClass('modal-open');
            }
        });

        // Modal de confirmação com callback
        function abrirModalConfirmacao(mensagemHtml, acao) {
            $('#modalConfirmacaoMensagem').html(mensagemHtml || 'Deseja realmente continuar?');
            $('#modalConfirmacao').modal('show');

            $('#btnConfirmarAcao')
                .off('click')
                .on('click', function () {
                    if (typeof acao === 'function') {
                        acao();
                    }
                    $('#modalConfirmacao').modal('hide');
                });
        }

        // Modal genérico de mensagem
        function abrirModalMensagem(titulo, mensagemHtml) {
            $('#modalMensagemLabel').html(titulo || 'Mensagem');
            $('#modalMensagemTexto').html(mensagemHtml || '');
            $('#modalMensagem').modal('show');
        }

        $(function () {
            // Abrir modal de inutilização manual
            $('#btnNovaInutilizacaoManual').on('click', function (e) {
                e.preventDefault();

                $('#inut_manual_modelo').val('55');
                //$('#inut_manual_ano').val('{{ \Carbon\Carbon::now()->format('y') }}');
                $('#inut_manual_justificativa').val(
                    'Inutilização de números não utilizados na sequência de NF-e.'
                );
                $('#inut_manual_diagnostico_result').hide().text('');

                $('#modalInutilizacaoManual').modal('show');
            });

            // Diagnóstico da faixa (AJAX)
            $('#btnDiagnosticarFaixa').on('click', function (e) {
                e.preventDefault();

                var filialId = $('#inut_manual_filial_id').val();
                var serie    = $('#inut_manual_serie').val();
                var numIni   = $('#inut_manual_numero_inicial').val();
                var numFim   = $('#inut_manual_numero_final').val();

                if (!serie || !numIni || !numFim) {
                    abrirModalMensagem(
                        'Dados incompletos',
                        'Informe <strong>série</strong>, <strong>número inicial</strong> e <strong>número final</strong> para diagnosticar a faixa.'
                    );
                    return;
                }

                $.ajax({
                    url: '{{ route('relatorios.nfe_inutilizacoes.diagnosticar_faixa') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        filial_id: filialId,
                        serie: serie,
                        numero_inicial: numIni,
                        numero_final: numFim
                    },
                    success: function (resp) {
                        if (!resp.ok) {
                            abrirModalMensagem(
                                'Diagnóstico não disponível',
                                'Não foi possível diagnosticar a faixa. Tente novamente.'
                            );
                            return;
                        }

                        var texto = '';
                        texto += 'Faixa analisada: ' + resp.faixa.inicio + ' até ' + resp.faixa.fim + '\n';
                        texto += 'Números UTILIZADOS na faixa:\n';

                        if (resp.utilizados.length === 0) {
                            texto += '  Nenhum número usado na faixa.\n';
                        } else {
                            resp.utilizados.forEach(function (item) {
                                texto += '  - Número ' + item.numero +
                                    ' (origem: ' + item.origem +
                                    ', ID origem: ' + item.origem_id +
                                    ', data: ' + (item.data_doc || '-') +
                                    ', chave: ' + (item.chave || '-') +
                                    ', status: ' + (item.status_nfe || '-') +
                                    ')\n';
                            });
                        }

                        texto += '\nNúmeros LIVRES na faixa:\n';
                        if (!resp.livres || resp.livres.length === 0) {
                            texto += '  Nenhum número livre (todos utilizados).\n';
                        } else {
                            texto += '  ' + resp.livres.join(', ') + '\n';
                        }

                        $('#inut_manual_diagnostico_result').show().text(texto);
                    },
                    error: function () {
                        abrirModalMensagem(
                            'Erro ao diagnosticar',
                            'Ocorreu um erro ao diagnosticar a faixa. Por favor, tente novamente.'
                        );
                    }
                });
            });

            // Confirmação ao enviar inutilização manual (SEM window.confirm)
            $('#formInutilizacaoManual').on('submit', function (e) {
                e.preventDefault();

                var $form = $(this);

                var modelo = $('#inut_manual_modelo').val();
                var serie  = $('#inut_manual_serie').val();
                var ini    = $('#inut_manual_numero_inicial').val();
                var fim    = $('#inut_manual_numero_final').val();
                var just   = $.trim($('#inut_manual_justificativa').val() || '');

                if (just.length < 15 || just.length > 255) {
                    abrirModalMensagem(
                        'Justificativa inválida',
                        'A justificativa deve conter entre <strong>15</strong> e <strong>255</strong> caracteres.'
                    );
                    return;
                }

                var msgHtml =
                    'Confirmar envio da inutilização para a SEFAZ?<br><br>' +
                    '<strong>Modelo:</strong> ' + modelo + '<br>' +
                    '<strong>Série:</strong> ' + serie + '<br>' +
                    '<strong>Faixa:</strong> ' + ini + ' até ' + fim + '<br>';

                abrirModalConfirmacao(msgHtml, function () {
                    $form.off('submit');
                    $form.submit();
                });
            });
        });
    </script>
@endsection
