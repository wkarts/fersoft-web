{{-- resources/views/relatorios/nfe_numeracao_gaps/index.blade.php --}}
@extends('default.layout')

@section('content')
    <div class="report-nfe-gaps">
        @php
            // Ambiente fiscal auto detectado
            // Prioriza variável já passada (se algum controller enviar),
            // depois usa ConfigNota (configSystemWhats compartilhado no BaseController)
            $ambienteNFeCode = null;

            if (isset($ambienteNFe) && in_array((int) $ambienteNFe, [1, 2], true)) {
                $ambienteNFeCode = (int) $ambienteNFe;
            } elseif (isset($configSystemWhats) && isset($configSystemWhats->ambiente)) {
                $ambienteNFeCode = (int) $configSystemWhats->ambiente;
            }

            if (! in_array($ambienteNFeCode, [1, 2], true)) {
                // fallback padrão: Produção
                $ambienteNFeCode = 1;
            }

            $ambienteNFeLabel = $ambienteNFeCode === 1 ? 'Produção' : 'Homologação';
        @endphp

        <style>
            .report-nfe-gaps .card-title {
                font-size: 1.05rem;
                font-weight: 600;
                display: block;
                margin-bottom: 0.15rem;
            }

            .report-nfe-gaps .card-subtitle {
                font-size: 0.8rem;
                color: #6c757d;
                display: block;
                line-height: 1.3;
            }

            .report-nfe-gaps .table thead th {
                white-space: nowrap;
                font-size: 0.74rem;
                vertical-align: middle;
            }

            .report-nfe-gaps .table tbody td {
                font-size: 0.74rem;
                vertical-align: middle;
            }

            .report-nfe-gaps .table td.text-mono {
                font-family: monospace;
                font-size: 0.7rem;
            }

            .report-nfe-gaps .legend-text {
                font-size: 0.75rem;
            }

            .report-nfe-gaps .badge-origin {
                min-width: 1.4rem;
                text-align: center;
                font-size: 0.7rem;
                font-weight: 600;
            }

            .report-nfe-gaps .badge-origin-v {
                background-color: #e3f2fd;
                color: #0d47a1;
            }

            .report-nfe-gaps .badge-origin-c {
                background-color: #e8f5e9;
                color: #1b5e20;
            }

            .report-nfe-gaps .badge-yes {
                background-color: #fff3cd;
                color: #856404;
                font-size: 0.7rem;
            }

            .report-nfe-gaps .badge-no {
                background-color: #f8f9fa;
                color: #6c757d;
                font-size: 0.7rem;
            }

            .report-nfe-gaps .badge-status {
                font-size: 0.7rem;
                font-weight: 500;
            }

            .report-nfe-gaps .filters-label {
                font-size: 0.8rem;
                font-weight: 500;
            }

            .report-nfe-gaps .filters-container .form-control,
            .report-nfe-gaps .filters-container .select2-selection {
                font-size: 0.8rem;
            }

            .report-nfe-gaps .pagination-summary {
                font-size: 0.8rem;
            }

            /* Cards mobile */
            .report-nfe-gaps .gap-card {
                border: 1px solid #e5e5e5;
                border-radius: 0.35rem;
                padding: 0.6rem 0.7rem;
                margin-bottom: 0.7rem;
                background-color: #fff;
            }

            .report-nfe-gaps .gap-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 0.8rem;
                margin-bottom: 0.3rem;
            }

            .report-nfe-gaps .gap-card-header strong {
                font-size: 0.82rem;
            }

            .report-nfe-gaps .gap-card-subtitle {
                font-size: 0.74rem;
                color: #666;
            }

            .report-nfe-gaps .gap-card-block-title {
                font-size: 0.75rem;
                font-weight: 600;
                margin-top: 0.35rem;
                margin-bottom: 0.2rem;
            }

            .report-nfe-gaps .gap-card-line {
                font-size: 0.74rem;
                display: flex;
                justify-content: space-between;
            }

            .report-nfe-gaps .gap-card-line span:first-child {
                color: #666;
            }

            /* Badge do ambiente dentro do modal */
            .report-nfe-gaps .ambiente-badge-modal {
                font-size: 0.85rem;
                font-weight: 600;
                padding: 0.4rem 0.8rem;
                border-radius: 999px;
            }

            @media (max-width: 767.98px) {
                .report-nfe-gaps .card-title {
                    font-size: 0.95rem;
                }
                .report-nfe-gaps .card-subtitle {
                    font-size: 0.72rem;
                }
                .report-nfe-gaps .filters-label {
                    font-size: 0.75rem;
                }
            }
        </style>

        {{-- FILTROS --}}
        <div class="card gutter-b">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Relatório de Saltos de Numeração de NF-e</h4>
                        <br>
                        <div class="card-subtitle">
                            Análise de lacunas de numeração por filial, série e período.
                        </div>
                        <div class="legend-text mt-1">
                            <strong>Gaps:</strong>
                            lacunas na sequência de numeração de NF-e (números pulados entre dois documentos).
                        </div>
                        <div class="legend-text mt-1">
                            <strong>Ambiente NF-e atual:</strong>
                            <span class="badge badge-{{ $ambienteNFeCode === 1 ? 'success' : 'warning' }}">
                                {{ $ambienteNFeLabel }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-2 mt-md-0 text-md-right">
                        <button type="button"
                                class="btn btn-outline-info btn-sm mb-2"
                                data-toggle="modal"
                                data-target="#modalManualNfeGaps">
                            <i class="la la-question-circle"></i> Manual / Orientações
                        </button>
                        <br class="d-none d-md-block">
                        <span class="badge badge-light">
                            <i class="la la-file-text-o"></i>
                            {{ $registros->total() ?? 0 }} gaps encontrados
                        </span>
                    </div>

                </div>

                <form method="GET" action="{{ route('relatorios.nfe_numeracao_gaps.index') }}" class="filters-container">
                    <div class="form-row">
                        <div class="form-group col-12 col-md-3">
                            <label class="filters-label">Filial</label>
                            <select name="filial_id" class="form-control select2">
                                <option value="">Matriz</option>
                                @foreach($filiais as $filial)
                                    <option value="{{ $filial->id }}"
                                        {{ $filters['filial_id'] == $filial->id ? 'selected' : '' }}>
                                        {{ $filial->nome_fantasia }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-12 col-md-3">
                            <label class="filters-label">Série</label>
                            <input type="text"
                                   name="serie"
                                   class="form-control"
                                   value="{{ $filters['serie'] }}"
                                   placeholder="Número da série">
                        </div>

                        <div class="form-group col-6 col-md-3">
                            <label class="filters-label">Data inicial</label>
                            <input type="date"
                                   name="data_inicial"
                                   class="form-control"
                                   value="{{ $filters['data_inicial'] }}">
                        </div>

                        <div class="form-group col-6 col-md-3">
                            <label class="filters-label">Data final</label>
                            <input type="date"
                                   name="data_final"
                                   class="form-control"
                                   value="{{ $filters['data_final'] }}">
                        </div>
                    </div>

                    <div class="form-row align-items-center">
                        <div class="form-group col-12 col-md-6">
                            <div class="form-check mt-2">
                                <input type="checkbox"
                                       name="somente_sem_chave"
                                       value="1"
                                       id="somente_sem_chave"
                                       class="form-check-input"
                                    {{ $filters['somente_sem_chave'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="somente_sem_chave" style="font-size: 0.8rem;">
                                    Exibir apenas gaps em que o documento anterior ou atual está sem chave de NF-e
                                </label>
                            </div>
                        </div>

                        <div class="form-group col-12 col-md-6 text-md-right mt-3 mt-md-0">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="la la-filter"></i> Filtrar
                            </button>
                            <a href="{{ route('relatorios.nfe_numeracao_gaps.index') }}" class="btn btn-secondary btn-sm">
                                <i class="la la-eraser"></i> Limpar
                            </a>

                            {{-- Botão para PDF analítico (mantém filtros atuais) --}}
                            <a href="{{ route('relatorios.nfe_numeracao_gaps.pdf', request()->query()) }}"
                               class="btn btn-outline-success btn-sm" target="_blank">
                                <i class="la la-file-pdf-o"></i> PDF Analítico
                            </a>
                        </div>
                    </div>

                    <div class="mt-3 legend-text">
                        <strong>Legenda:</strong>
                        Origem:&nbsp;
                        <span class="badge badge-origin badge-origin-v">V</span> Venda,&nbsp;
                        <span class="badge badge-origin badge-origin-c">C</span> Compra.<br>
                        Quando um documento não possuir chave de NF-e,
                        será exibido <strong>"Sem chave"</strong> na coluna <strong>Chave</strong>.
                    </div>
                </form>
            </div>
        </div>

        {{-- TABELA DESKTOP (md+) --}}
        <div class="card d-none d-md-block">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm mb-0">
                        <thead>
                        <tr>
                            <th rowspan="2" class="text-center align-middle">Filial</th>
                            <th rowspan="2" class="text-center align-middle">Série</th>

                            <th colspan="5" class="text-center">Documento anterior</th>
                            <th colspan="5" class="text-center">Documento atual</th>
                            <th colspan="3" class="text-center">Faixa pulada</th>
                            <th rowspan="2" class="text-center align-middle">Ações</th>
                        </tr>
                        <tr>
                            {{-- Documento anterior --}}
                            <th class="text-center">Número</th>
                            <th class="text-center">Origem<br>(V/C)</th>
                            <th class="text-center">Data</th>
                            <th class="text-center">Chave</th>
                            <th class="text-center">Status</th>

                            {{-- Documento atual --}}
                            <th class="text-center">Número</th>
                            <th class="text-center">Origem<br>(V/C)</th>
                            <th class="text-center">Data</th>
                            <th class="text-center">Chave</th>
                            <th class="text-center">Status</th>

                            {{-- Faixa pulada --}}
                            <th class="text-center">Nº inicial</th>
                            <th class="text-center">Nº final</th>
                            <th class="text-center">Qtde</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($registros as $registro)
                            @php
                                $origemAnterior = $registro->origem_usado_anterior ?? '';
                                $origemAtual    = $registro->origem_usado_atual ?? '';
                            @endphp
                            <tr>
                                {{-- Filial / Série --}}
                                <td class="text-nowrap">{{ $registro->filial_label }}</td>
                                <td class="text-center text-nowrap">{{ $registro->serie }}</td>

                                {{-- Documento anterior --}}
                                <td class="text-right">
                                    {{ $registro->numero_usado_anterior }}
                                </td>
                                <td class="text-center">
                                    @if($origemAnterior === 'v' || $origemAnterior === 'V')
                                        <span class="badge badge-origin badge-origin-v" title="Venda">V</span>
                                    @elseif($origemAnterior === 'c' || $origemAnterior === 'C')
                                        <span class="badge badge-origin badge-origin-c" title="Compra">C</span>
                                    @else
                                        <span class="badge badge-light badge-origin" title="Origem não mapeada">
                                            {{ strtoupper(substr($origemAnterior, 0, 1)) ?: '-' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ optional($registro->data_doc_anterior)->format('d/m/Y') }}
                                </td>
                                <td class="text-mono text-center">
                                    @if(empty($registro->chave_nfe_anterior))
                                        <span class="badge badge-yes">Sem chave</span>
                                    @else
                                        {{ $registro->chave_nfe_anterior }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light badge-status">
                                        {{ $registro->status_nfe_anterior ?? '-' }}
                                    </span>
                                </td>

                                {{-- Documento atual --}}
                                <td class="text-right">
                                    {{ $registro->numero_usado_atual }}
                                </td>
                                <td class="text-center">
                                    @if($origemAtual === 'v' || $origemAtual === 'V')
                                        <span class="badge badge-origin badge-origin-v" title="Venda">V</span>
                                    @elseif($origemAtual === 'c' || $origemAtual === 'C')
                                        <span class="badge badge-origin badge-origin-c" title="Compra">C</span>
                                    @else
                                        <span class="badge badge-light badge-origin" title="Origem não mapeada">
                                            {{ strtoupper(substr($origemAtual, 0, 1)) ?: '-' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ optional($registro->data_doc_atual)->format('d/m/Y') }}
                                </td>
                                <td class="text-mono text-center">
                                    @if(empty($registro->chave_nfe_atual))
                                        <span class="badge badge-yes">Sem chave</span>
                                    @else
                                        {{ $registro->chave_nfe_atual }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light badge-status">
                                        {{ $registro->status_nfe_atual ?? '-' }}
                                    </span>
                                </td>

                                {{-- Faixa pulada --}}
                                <td class="text-right">
                                    {{ $registro->numero_inicial_pulado }}
                                </td>
                                <td class="text-right">
                                    {{ $registro->numero_final_pulado }}
                                </td>
                                <td class="text-right font-weight-bold">
                                    {{ $registro->quantidade_pulada }}
                                </td>

                                {{-- Ações: Inutilização --}}
                                <td class="text-center">
                                    @if($registro->numero_inicial_pulado && $registro->numero_final_pulado)
                                        {{-- Botão para inutilizar faixa (já existente) --}}
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm js-abrir-inutilizacao"
                                                title="Inutilizar faixa de numeração pulada"
                                                data-filial-id="{{ $registro->filial_id ?? '' }}"
                                                data-filial-label="{{ $registro->filial_label }}"
                                                data-serie="{{ $registro->serie }}"
                                                data-numero-inicial="{{ $registro->numero_inicial_pulado }}"
                                                data-numero-final="{{ $registro->numero_final_pulado }}">
                                            Inutilizar
                                        </button>

                                        {{-- Novo: botão para ignorar gap (não exibir mais) --}}
                                        <form method="POST"
                                              action="{{ route('relatorios.nfe_numeracao_gaps.ignorar_gap') }}"
                                              class="d-inline-block js-form-ignorar-gap">
                                            @csrf
                                            <input type="hidden" name="filial_id" value="{{ $registro->filial_id }}">
                                            <input type="hidden" name="serie" value="{{ $registro->serie }}">
                                            <input type="hidden" name="numero_inicial" value="{{ $registro->numero_inicial_pulado }}">
                                            <input type="hidden" name="numero_final" value="{{ $registro->numero_final_pulado }}">
                                            <input type="hidden" name="motivo" value="">
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-sm js-btn-ignorar-gap"
                                                    title="Ignorar este gap (não exibir mais na listagem)">
                                                Ignorar
                                            </button>
                                        </form>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="text-center">
                                    Nenhum registro encontrado para os filtros informados.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginação + resumo --}}
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
                    <div class="pagination-summary mb-2 mb-md-0">
                        @if($registros->total() > 0)
                            Mostrando
                            <strong>{{ $registros->firstItem() }}</strong>
                            até
                            <strong>{{ $registros->lastItem() }}</strong>
                            de
                            <strong>{{ $registros->total() }}</strong>
                            gaps encontrados.
                        @else
                            Nenhum gap encontrado para os filtros aplicados.
                        @endif
                    </div>
                    <div>
                        {{ $registros->appends(request()->except('page'))->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- LISTA MOBILE (sm) – cards por gap --}}
        <div class="card d-block d-md-none">
            <div class="card-body">
                @forelse($registros as $registro)
                    @php
                        $origemAnterior = $registro->origem_usado_anterior ?? '';
                        $origemAtual    = $registro->origem_usado_atual ?? '';
                    @endphp
                    <div class="gap-card">
                        <div class="gap-card-header">
                            <strong>{{ $registro->filial_label }}</strong>
                            <span>Série {{ $registro->serie }}</span>
                        </div>

                        <div class="gap-card-subtitle">
                            Faixa pulada:
                            <strong>{{ $registro->numero_inicial_pulado }} &rarr; {{ $registro->numero_final_pulado }}</strong>
                            ({{ $registro->quantidade_pulada }} nº)
                        </div>

                        <div class="gap-card-block-title">Documento anterior</div>
                        <div class="gap-card-line">
                            <span>Número</span>
                            <span>{{ $registro->numero_usado_anterior }}</span>
                        </div>
                        <div class="gap-card-line">
                            <span>Origem</span>
                            <span>
                                @if($origemAnterior === 'v' || $origemAnterior === 'V')
                                    <span class="badge badge-origin badge-origin-v">V</span>
                                @elseif($origemAnterior === 'c' || $origemAnterior === 'C')
                                    <span class="badge badge-origin badge-origin-c">C</span>
                                @else
                                    {{ strtoupper(substr($origemAnterior, 0, 1)) ?: '-' }}
                                @endif
                            </span>
                        </div>
                        <div class="gap-card-line">
                            <span>Data</span>
                            <span>{{ optional($registro->data_doc_anterior)->format('d/m/Y') }}</span>
                        </div>
                        <div class="gap-card-line">
                            <span>Status</span>
                            <span>{{ $registro->status_nfe_anterior ?? '-' }}</span>
                        </div>
                        <div class="gap-card-line">
                            <span>Chave</span>
                            <span style="max-width: 60%; text-align:right; word-wrap:break-word;">
                                @if(empty($registro->chave_nfe_anterior))
                                    <span class="badge badge-yes">Sem chave</span>
                                @else
                                    {{ $registro->chave_nfe_anterior }}
                                @endif
                            </span>
                        </div>

                        <div class="gap-card-block-title mt-2">Documento atual</div>
                        <div class="gap-card-line">
                            <span>Número</span>
                            <span>{{ $registro->numero_usado_atual }}</span>
                        </div>
                        <div class="gap-card-line">
                            <span>Origem</span>
                            <span>
                                @if($origemAtual === 'v' || $origemAtual === 'V')
                                    <span class="badge badge-origin badge-origin-v">V</span>
                                @elseif($origemAtual === 'c' || $origemAtual === 'C')
                                    <span class="badge badge-origin badge-origin-c">C</span>
                                @else
                                    {{ strtoupper(substr($origemAtual, 0, 1)) ?: '-' }}
                                @endif
                            </span>
                        </div>
                        <div class="gap-card-line">
                            <span>Data</span>
                            <span>{{ optional($registro->data_doc_atual)->format('d/m/Y') }}</span>
                        </div>
                        <div class="gap-card-line">
                            <span>Status</span>
                            <span>{{ $registro->status_nfe_atual ?? '-' }}</span>
                        </div>
                        <div class="gap-card-line">
                            <span>Chave</span>
                            <span style="max-width: 60%; text-align:right; word-wrap:break-word;">
                                @if(empty($registro->chave_nfe_atual))
                                    <span class="badge badge-yes">Sem chave</span>
                                @else
                                    {{ $registro->chave_nfe_atual }}
                                @endif
                            </span>
                        </div>

                        <div class="mt-2 text-right">
                            @if($registro->numero_inicial_pulado && $registro->numero_final_pulado)
                                {{-- Botão já existente: inutilizar faixa --}}
                                <button type="button"
                                        class="btn btn-outline-danger btn-xs js-abrir-inutilizacao"
                                        data-filial-id="{{ $registro->filial_id ?? '' }}"
                                        data-filial-label="{{ $registro->filial_label }}"
                                        data-serie="{{ $registro->serie }}"
                                        data-numero-inicial="{{ $registro->numero_inicial_pulado }}"
                                        data-numero-final="{{ $registro->numero_final_pulado }}">
                                    Inutilizar faixa
                                </button>

                                {{-- Novo: botão para ignorar gap (mobile) --}}
                                <form method="POST"
                                      action="{{ route('relatorios.nfe_numeracao_gaps.ignorar_gap') }}"
                                      class="d-inline-block js-form-ignorar-gap">
                                    @csrf
                                    <input type="hidden" name="filial_id" value="{{ $registro->filial_id }}">
                                    <input type="hidden" name="serie" value="{{ $registro->serie }}">
                                    <input type="hidden" name="numero_inicial" value="{{ $registro->numero_inicial_pulado }}">
                                    <input type="hidden" name="numero_final" value="{{ $registro->numero_final_pulado }}">
                                    <input type="hidden" name="motivo" value="">
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-xs js-btn-ignorar-gap"
                                            title="Ignorar este gap (não exibir mais na listagem)">
                                        Ignorar
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="mb-0 text-center" style="font-size: 0.8rem;">
                        Nenhum gap encontrado para os filtros aplicados.
                    </p>
                @endforelse

                {{-- Paginação (reaproveita mesma paginação) --}}
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="pagination-summary">
                        @if($registros->total() > 0)
                            Mostrando
                            <strong>{{ $registros->firstItem() }}</strong>
                            até
                            <strong>{{ $registros->lastItem() }}</strong>
                            de
                            <strong>{{ $registros->total() }}</strong>
                            gaps.
                        @else
                            Nenhum gap encontrado.
                        @endif
                    </div>
                    <div style="font-size: 0.8rem;">
                        {{ $registros->appends(request()->except('page'))->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================= --}}
    {{-- MODAIS REUTILIZÁVEIS (CONFIRMAÇÃO / MSG) --}}
    {{-- Padrão usado em Pesagens                   --}}
    {{-- ========================================= --}}

    <!-- Modal Reutilizável de Confirmação -->
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

    {{-- ========================================= --}}
    {{-- MODAL: INUTILIZAÇÃO DOS GAPS DE NUMERAÇÃO --}}
    {{-- ========================================= --}}
    <div class="modal fade" id="modalInutilizacaoNfeGap" tabindex="-1" role="dialog" aria-labelledby="modalInutilizacaoNfeGapLabel" aria-hidden="true">
        {{-- CENTRALIZAÇÃO VERTICAL --}}
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <form id="formInutilizarNumeracao"
                  method="POST"
                  action="{{ route('relatorios.nfe_numeracao_gaps.inutilizar') }}">
                @csrf

                {{-- Para backend reutilizar a mesma lógica antiga --}}
                <input type="hidden" name="filial_id" id="inutil_filial_id">
                <input type="hidden" id="inutil_filial_label">
                <input type="hidden" name="ano" id="inutil_ano" value="{{ \Carbon\Carbon::now()->format('y') }}">
                {{-- Campo real que será enviado para o backend --}}
                <input type="hidden" name="ambiente" id="inutil_ambiente_hidden" value="{{ $ambienteNFeCode }}">

                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="modalInutilizacaoNfeGapLabel">
                            Inutilizar Numeração de NFe (Gaps)
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label for="inutil_modelo">Modelo</label>
                                <input type="text"
                                       class="form-control"
                                       id="inutil_modelo"
                                       name="modelo"
                                       value="55"
                                       readonly>
                            </div>

                            <div class="form-group col-md-3">
                                <label for="inutil_serie">Série</label>
                                <input type="text"
                                       class="form-control"
                                       id="inutil_serie"
                                       name="serie"
                                       readonly>
                            </div>

                            <div class="form-group col-md-3">
                                <label for="inutil_numero_inicial">Número Inicial</label>
                                <input type="number"
                                       class="form-control"
                                       id="inutil_numero_inicial"
                                       name="numero_inicial"
                                       required>
                            </div>

                            <div class="form-group col-md-3">
                                <label for="inutil_numero_final">Número Final</label>
                                <input type="number"
                                       class="form-control"
                                       id="inutil_numero_final"
                                       name="numero_final"
                                       required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-4">
                                <label>Ambiente</label>
                                <div>
                                    <span
                                        id="inutil_ambiente_badge"
                                        class="ambiente-badge-modal badge badge-{{ $ambienteNFeCode == 1 ? 'success' : 'warning' }}">
                                        {{ $ambienteNFeLabel }}
                                    </span>
                                </div>
                                <small class="form-text text-muted">
                                    Ambiente de envio da inutilização.
                                </small>
                            </div>

                            <div class="form-group col-md-8">
                                <label for="inutil_justificativa">Justificativa</label>
                                <textarea
                                    class="form-control"
                                    id="inutil_justificativa"
                                    name="justificativa"
                                    rows="3"
                                    maxlength="255"
                                    required
                                ></textarea>
                                <small class="form-text text-muted">
                                    A SEFAZ exige justificativa entre 15 e 255 caracteres.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-light-secondary"
                                data-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit"
                                class="btn btn-light-success">
                            Confirmar Inutilização
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    {{-- MODAL: Manual de utilização – Relatório de Gaps --}}
    <div class="modal fade" id="modalManualNfeGaps" tabindex="-1" role="dialog" aria-labelledby="modalManualNfeGapsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalManualNfeGapsLabel">
                        Manual de Utilização – Relatório de Saltos de Numeração de NF-e
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="font-size: 0.85rem;">
                    <p>
                        Este relatório identifica <strong>lacunas (gaps)</strong> na sequência de numeração
                        de NF-e, por filial e série, comparando sempre um documento anterior e o documento atual.
                    </p>
                    <ul>
                        <li>
                            <strong>Filtros:</strong> selecione filial, série e período, e clique em
                            <em>Filtrar</em> para restringir a pesquisa.
                        </li>
                        <li>
                            A opção <strong>"Exibir apenas gaps em que o documento anterior ou atual está sem chave"</strong>
                            ajuda a localizar lançamentos que ainda não possuem chave de NF-e associada.
                        </li>
                        <li>
                            A coluna <strong>Faixa pulada</strong> mostra o intervalo de numeração que não foi utilizado
                            entre o documento anterior e o atual.
                        </li>
                        <li>
                            O botão <strong>Inutilizar</strong> abre um modal específico para envio da inutilização
                            dessa faixa para a SEFAZ, já preenchendo filial, série e faixa automaticamente.
                        </li>
                        <li>
                            O <strong>ambiente NF-e atual</strong> (Produção/Homologação) é exibido no topo da tela
                            e é sempre o mesmo configurado nos parâmetros da empresa. Não é possível alterar o ambiente
                            por esta tela.
                        </li>
                        <li>
                            O botão <strong>PDF Analítico</strong> gera um relatório detalhado em PDF,
                            com base nos filtros atuais, para conferência ou arquivamento.
                        </li>
                    </ul>
                    <p>
                        <strong>Recomendação:</strong> antes de inutilizar faixas extensas, valide se não há documentos em trânsito
                        (autorização, cancelamento, carta de correção) e, em caso de rejeição <code>[241]</code>
                        ("Um número da faixa já foi utilizado"), utilize o diagnóstico de faixa da tela de inutilizações
                        para entender quais números estão ocupados.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    @parent
    <script>
        // Ambiente fiscal padrão vindo do backend (1 = Produção, 2 = Homologação)
        const AMBIENTE_NFE_PADRAO = {{ (int)($ambienteNFeCode ?? 1) }};

        // ============================
        // Empilhamento de múltiplos modais
        // (garante que o modal de confirmação fique acima do de inutilização)
        // ============================
        $(document).on('show.bs.modal', '.modal', function () {
            const visibleModals = $('.modal:visible').length;
            const zIndex = 1040 + (10 * visibleModals);

            $(this).css('z-index', zIndex);

            // Ajusta o backdrop deste modal específico
            setTimeout(function () {
                $('.modal-backdrop').not('.modal-stack')
                    .css('z-index', zIndex - 1)
                    .addClass('modal-stack');
            }, 0);
        });

        $(document).on('hidden.bs.modal', '.modal', function () {
            // Evita que o body perca o scroll lock enquanto ainda houver modal aberto
            if ($('.modal:visible').length) {
                $('body').addClass('modal-open');
            }
        });

        // ============================
        // Funções genéricas de modal
        // ============================

        // Modal de confirmação com mensagem HTML
        function abrirModalConfirmacao(mensagem, acao) {
            $('#modalConfirmacaoMensagem').html(mensagem);
            $('#modalConfirmacao').modal('show');

            $('#btnConfirmarAcao').off('click').on('click', function () {
                if (typeof acao === 'function') {
                    acao();
                }
                $('#modalConfirmacao').modal('hide');
            });
        }

        // Modal genérico de mensagem/alerta
        function abrirModalMensagem(titulo, mensagem) {
            $('#modalMensagemLabel').html(titulo);
            $('#modalMensagemTexto').html(mensagem);
            $('#modalMensagem').modal('show');
        }

        // ============================
        // Modal de Inutilização de Gaps
        // ============================

        /**
         * Abre o modal de inutilização preenchendo os campos padrão.
         *
         * Espera um objeto `dados` com:
         *  - filial_id / filial_label
         *  - modelo
         *  - serie
         *  - numero_inicial
         *  - numero_final
         *  - justificativa_padrao (opcional)
         */
        function abrirModalInutilizacaoNfeGap(dados) {
            dados = dados || {};

            $('#inutil_filial_id').val(dados.filial_id || '');
            $('#inutil_filial_label').val(dados.filial_label || '');
            $('#inutil_modelo').val(dados.modelo || '55');
            $('#inutil_serie').val(dados.serie || '');
            $('#inutil_numero_inicial').val(dados.numero_inicial || '');
            $('#inutil_numero_final').val(dados.numero_final || '');

            // Ambiente sempre fixo no padrão detectado
            var ambienteUsado = String(AMBIENTE_NFE_PADRAO);
            $('#inutil_ambiente_hidden').val(ambienteUsado); // valor efetivo enviado ao backend

            var justificativaPadrao = dados.justificativa_padrao ||
                'Inutilização de números não utilizados na sequência de NF-e, conforme relatório de gaps.';

            $('#inutil_justificativa').val(justificativaPadrao);

            $('#modalInutilizacaoNfeGap').modal('show');
        }

        $(function () {
            // Clique nos botões já existentes (.js-abrir-inutilizacao)
            $(document).on('click', '.js-abrir-inutilizacao', function (e) {
                e.preventDefault();

                var $btn = $(this);

                var filialId    = $btn.data('filial-id') || '';
                var filialLabel = $btn.data('filial-label') || '';
                var serie       = $btn.data('serie') || '';
                var numInicial  = $btn.data('numero-inicial') || '';
                var numFinal    = $btn.data('numero-final') || '';

                var faixaTexto = numInicial + ' até ' + numFinal;
                var contexto   = 'Filial: ' + (filialLabel || filialId || 'Matriz') +
                    ' | Série: ' + (serie || '-') +
                    ' | Faixa: ' + faixaTexto;

                var justificativaPadrao =
                    'Inutilização de números não utilizados na sequência de NF-e, ' +
                    'conforme relatório de gaps. ' + contexto;

                var dados = {
                    filial_id:           filialId,
                    filial_label:        filialLabel,
                    modelo:              55,
                    serie:               serie,
                    numero_inicial:      numInicial,
                    numero_final:        numFinal,
                    justificativa_padrao: justificativaPadrao
                };

                abrirModalInutilizacaoNfeGap(dados);
            });

            // Clique no botão de ignorar gap (desktop e mobile)
            $(document).on('click', '.js-btn-ignorar-gap', function (e) {
                e.preventDefault();

                var $form = $(this).closest('form.js-form-ignorar-gap');
                if (!$form.length) {
                    return;
                }

                var serie      = $form.find('input[name="serie"]').val();
                var numInicial = $form.find('input[name="numero_inicial"]').val();
                var numFinal   = $form.find('input[name="numero_final"]').val();

                var msg = '' +
                    'Deseja realmente <strong>ignorar</strong> este gap?<br><br>' +
                    '<strong>Série:</strong> ' + (serie || '-') + '<br>' +
                    '<strong>Faixa:</strong> ' + (numInicial || '-') + ' até ' + (numFinal || '-') + '<br>' +
                    '<small>Ele deixará de ser exibido no relatório de saltos de numeração.</small>';

                abrirModalConfirmacao(msg, function () {
                    $form.submit();
                });
            });

            // Antes de enviar o form de inutilização, valida e pede confirmação
            $('#formInutilizarNumeracao').on('submit', function (e) {
                e.preventDefault();

                var $form = $(this);

                var modelo        = $('#inutil_modelo').val();
                var serie         = $('#inutil_serie').val();
                var numInicial    = $('#inutil_numero_inicial').val();
                var numFinal      = $('#inutil_numero_final').val();
                var filialLabel   = $('#inutil_filial_label').val() || $('#inutil_filial_id').val() || 'Matriz';
                var justificativa = $.trim($('#inutil_justificativa').val() || '');

                // Validação de tamanho da justificativa (SEFAZ: 15 a 255 caracteres)
                if (justificativa.length < 15 || justificativa.length > 255) {
                    abrirModalMensagem(
                        'Justificativa inválida',
                        'A justificativa deve conter entre <strong>15</strong> e <strong>255</strong> caracteres.'
                    );
                    return;
                }

                var msg = '' +
                    'Confirmar envio da inutilização para a SEFAZ?<br><br>' +
                    '<strong>Filial:</strong> ' + filialLabel + '<br>' +
                    '<strong>Modelo:</strong> ' + modelo + '<br>' +
                    '<strong>Série:</strong> ' + serie + '<br>' +
                    '<strong>Número inicial:</strong> ' + numInicial + '<br>' +
                    '<strong>Número final:</strong> ' + numFinal + '<br>';

                abrirModalConfirmacao(msg, function () {
                    // Remove o handler para evitar loop e submete de verdade
                    $form.off('submit');
                    $form.submit();
                });
            });
        });

    </script>
@endsection
