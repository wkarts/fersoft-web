@extends('default.layout')
@section('content')

    <style type="text/css">
        /* Cards de Métricas Superiores */
        .card-metric {
            border-radius: 12px;
            padding: 18px 22px;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            margin-bottom: 15px;
        }
        .metric-title { font-size: 0.85rem; font-weight: 600; opacity: 0.95; }
        .metric-value { font-size: 1.8rem; font-weight: 800; margin-top: 5px; }

        .badge-status-legend {
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.78rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        #calendar {
            margin-top: 15px;
            background: #ffffff;
            padding: 18px;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .fc-col-header-cell {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
            padding: 8px 0 !important;
            font-weight: 700 !important;
            font-size: 0.82rem !important;
            border: none !important;
        }
        .fc-col-header-cell:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        .fc-col-header-cell:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }
        .fc-col-header-cell a { color: #ffffff !important; text-decoration: none !important; }

        .fc-daygrid-day {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0 !important;
        }
        .fc-daygrid-day-frame {
            min-height: 80px !important;
        }

        .fc-event {
            border-radius: 4px !important;
            padding: 2px 6px !important;
            font-weight: 600 !important;
            font-size: 0.73rem !important;
            border: none !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08) !important;
            cursor: pointer !important;
            margin-bottom: 2px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            transition: transform 0.1s ease;
        }
        .fc-event:hover {
            transform: translateY(-1px);
            filter: brightness(0.92);
        }
        .fc-daygrid-more-link {
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            color: #3b82f6 !important;
            padding: 2px 4px !important;
        }
        .fc-toolbar-title {
            font-size: 1.3rem !important;
            font-weight: 800 !important;
            color: #1e293b !important;
        }
        .fc-button-primary {
            background-color: #ffffff !important;
            color: #3b82f6 !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            font-weight: 700 !important;
            font-size: 0.82rem !important;
            box-shadow: none !important;
        }
        .fc-button-primary:hover, .fc-button-primary:active {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
            border-color: #3b82f6 !important;
        }

        /* Estilização do Resumo do Modal */
        .task-info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
            height: 100%;
        }
    </style>

    <div class="container-fluid p-0">

        <!-- BANNER DE TAREFAS VENCIDAS -->
        @if(isset($tarefasAtrasadas) && count($tarefasAtrasadas) > 0)
            <div class="alert alert-custom alert-danger fade show p-4 mb-4 rounded-lg shadow-sm" role="alert">
                <div class="alert-icon"><i class="la la-exclamation-triangle fs-1"></i></div>
                <div class="alert-text">
                    <h5 class="font-weight-bold m-0">Atenção! Você possui {{ count($tarefasAtrasadas) }} tarefa(s) VENCIDA(S)!</h5>
                    <small>Regularize as tarefas em atraso para manter a produtividade da equipe.</small>
                </div>
                <div class="alert-close">
                    <a href="/tarefas/painel" class="btn btn-sm btn-white font-weight-bold text-danger">Ver no Kanban</a>
                </div>
            </div>
        @endif

        <!-- CARDS DE METRICAS -->
        <div class="row g-3">
            <div class="col-xl-3 col-md-6 col-12">
                <div class="card-metric" style="background-color: #10b981;">
                    <div class="metric-title">OS / Agendamentos Concluídos</div>
                    <div class="metric-value">{{ $totalFinalizados ?? 0 }}</div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12">
                <div class="card-metric" style="background-color: #3b82f6;">
                    <div class="metric-title">OS em Execução na Oficina</div>
                    <div class="metric-value">{{ $totalExecucao ?? 0 }}</div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12">
                <div class="card-metric" style="background-color: #8b5cf6;">
                    <div class="metric-title">Minhas Tarefas Pendentes</div>
                    <div class="metric-value">{{ $tarefasPendentes ?? 0 }}</div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 col-12">
                <div class="card-metric" style="background-color: {{ (isset($tarefasAtrasadas) && count($tarefasAtrasadas) > 0) ? '#dc2626' : '#0ea5e9' }};">
                    <div class="metric-title">Tarefas Vencidas / Atrasadas</div>
                    <div class="metric-value">{{ isset($tarefasAtrasadas) ? count($tarefasAtrasadas) : 0 }}</div>
                </div>
            </div>
        </div>

        <!-- BARRA DE AÇÕES -->
        <div class="card card-custom gutter-b p-5 border-0 shadow-sm rounded-lg">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <button type="button" class="btn btn-primary font-weight-bold" data-toggle="modal" data-target="#modal1">
                        <i class="la la-plus"></i> Novo Agendamento OS
                    </button>

                    <button type="button" class="btn btn-dark font-weight-bold ms-2" data-toggle="modal" data-target="#modal-nova-tarefa">
                        <i class="la la-check-square"></i> Nova Tarefa
                    </button>

                    <a target="_blank" href="/agendamentos/comissao" class="btn btn-light-info font-weight-bold ms-2">
                        <i class="las la-percent"></i> Comissões
                    </a>
                    <a target="_blank" href="/agendamentos/servicos" class="btn btn-light-primary font-weight-bold ms-2">
                        <i class="la la-list"></i> Relatório
                    </a>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="font-weight-bold me-2 text-muted">Status:</span>
                    <span class="badge-status-legend bg-warning text-white">🚗 OS Agendada</span>
                    <span class="badge-status-legend bg-primary text-white">🚗 OS Execução</span>
                    <span class="badge-status-legend text-white" style="background: #8b5cf6;">👤 Tarefa Pendente</span>
                    <span class="badge-status-legend bg-info text-white">⏱ Tarefa Em Andamento</span>
                    <span class="badge-status-legend bg-danger text-white">⚠️ Tarefa Atrasada</span>
                    <span class="badge-status-legend bg-success text-white">✓ Concluída</span>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="row align-items-center mt-5 bg-light p-4 rounded">
                <div class="form-group col-lg-4 col-md-6 mb-2 mb-lg-0">
                    <label class="form-label font-weight-bold">Atendente / Mecânico / Responsável</label>
                    <select class="form-control select2" style="width: 100%" id="kt_select2_1">
                        <option value="null">Todos os Atendentes</option>
                        @foreach($funcionarios as $f)
                            <option value="{{$f->id}}">{{$f->nome}}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-lg-4 col-md-6 mb-2 mb-lg-0">
                    <label class="form-label font-weight-bold">Cliente</label>
                    <select class="form-control select2" style="width: 100%" id="kt_select2_7">
                        <option value="null">Todos os Clientes</option>
                        @foreach($clientes as $c)
                            <option value="{{$c->id}}">{{$c->razao_social}}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-4 col-md-12">
                    <button id="filtrar" style="width: 100%; margin-top: 24px;" class="btn btn-primary font-weight-bold">
                        <i class="la la-search"></i> Filtrar Agenda
                    </button>
                </div>
            </div>

            <!-- CALENDÁRIO -->
            <div id='calendar'></div>
        </div>
    </div>

    <!-- MODAL VISUALIZAR E EXECUTAR TAREFA (DESIGN PROFISSIONAL) -->
    <div class="modal fade" id="modal-ver-tarefa" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg rounded-lg">
                <div class="modal-header bg-dark text-white py-4 px-5">
                    <div class="d-flex align-items-center">
                        <span class="badge badge-light-primary font-weight-bold me-2 px-3 py-2 fs-7" id="task_modal_badge_id">#ID</span>
                        <h5 class="modal-title text-white font-weight-bold m-0" id="task_modal_title">Resumo da Tarefa</h5>
                    </div>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>

                <div class="modal-body p-6">
                    <!-- CABEÇALHO DO CARD -->
                    <div class="p-4 rounded-lg border-0 mb-4" style="background: #f1f5f9;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary text-white font-weight-bold px-3 py-1" id="task_modal_prioridade">Normal</span>
                            <span class="badge font-weight-bold px-3 py-1" id="task_modal_status_badge">Pendente</span>
                        </div>
                        <h3 class="font-weight-bold text-dark m-0" id="task_modal_nome">Título da Tarefa</h3>
                        <p class="text-muted mt-2 mb-0 fs-6" id="task_modal_descricao">Descrição detalhada...</p>
                    </div>

                    <!-- QUADRO DE INFORMAÇÕES RESUMIDAS -->
                    <div class="row g-3">
                        <div class="col-md-6 col-12">
                            <div class="task-info-box">
                                <small class="text-muted d-block font-weight-bold text-uppercase fs-8 mb-1">👤 Pessoa Responsável</small>
                                <span class="text-dark font-weight-bolder fs-6" id="task_modal_responsavel">-</span>
                            </div>
                        </div>

                        <div class="col-md-6 col-12">
                            <div class="task-info-box">
                                <small class="text-muted d-block font-weight-bold text-uppercase fs-8 mb-1">⏱️ Tempo Gasto na Tarefa</small>
                                <span class="text-success font-weight-bolder fs-6" id="task_modal_tempo">0 Minutos</span>
                            </div>
                        </div>

                        <div class="col-md-6 col-12 mt-3">
                            <div class="task-info-box">
                                <small class="text-muted d-block font-weight-bold text-uppercase fs-8 mb-1">📅 Data de Início</small>
                                <span class="text-primary font-weight-bolder fs-6" id="task_modal_inicio">-</span>
                            </div>
                        </div>

                        <div class="col-md-6 col-12 mt-3">
                            <div class="task-info-box">
                                <small class="text-muted d-block font-weight-bold text-uppercase fs-8 mb-1">⏰ Prazo Limite (Finalização)</small>
                                <span class="text-danger font-weight-bolder fs-6" id="task_modal_limite">-</span>
                            </div>
                        </div>

                        <div class="col-12 mt-3 d-none" id="div_justificativa_exibir">
                            <div class="alert alert-warning p-3 m-0 rounded-lg">
                                <strong class="d-block text-warning-emphasis mb-1"><i class="la la-info-circle"></i> Justificativa de Atraso Registrada:</strong>
                                <span id="task_modal_justificativa" class="text-dark fs-7"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-3 px-5 d-flex justify-content-between">
                    <a id="btn_editar_tarefa" href="#" class="btn btn-outline-secondary font-weight-bold">
                        <i class="la la-edit"></i> Editar Tarefa
                    </a>

                    <div class="d-flex gap-2" id="task_modal_actions">
                        <!-- Botões dinâmicos via JS: Iniciar, Pausar, Finalizar -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL CADASTRAR TAREFA RÁPIDA -->
    <div class="modal fade" id="modal-nova-tarefa" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="post" action="/tarefas/save">
                    @csrf
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title text-white"><i class="la la-user-plus me-2"></i> Cadastrar Nova Tarefa</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-bold">Título da Tarefa *</label>
                            <input required type="text" name="titulo" class="form-control" placeholder="Ex: Fazer apuração mensal de ISS">
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label font-weight-bold">Descrição / Orientações</label>
                            <textarea name="descricao" class="form-control" rows="3" placeholder="Detalhes de como fazer a tarefa..."></textarea>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Responsável *</label>
                                <select required class="form-control select2" style="width: 100%" name="funcionario_id">
                                    @foreach($funcionarios as $f)
                                        <option value="{{$f->id}}">{{$f->nome}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Prioridade *</label>
                                <select required class="custom-select form-control" name="prioridade">
                                    <option value="Baixa">Baixa</option>
                                    <option value="Normal" selected>Normal</option>
                                    <option value="Alta">Alta</option>
                                    <option value="Urgente">Urgente</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Data de Início *</label>
                                <input required type="date" name="data" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>

                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Data Limite (Prazo)</label>
                                <input type="date" name="data_limite" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Horário de Início</label>
                                <input type="time" name="hora_estimada" class="form-control" value="08:00">
                            </div>

                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Horário Limite</label>
                                <input type="time" name="hora_limite" class="form-control" value="18:00">
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label font-weight-bold">Repetir / Recorrência</label>
                                <select class="custom-select form-control" name="recorrencia" id="select_recorrencia">
                                    <option value="nao" selected>Não repetir</option>
                                    <option value="diario">Diariamente (Dias úteis)</option>
                                    <option value="semanal">Semanalmente</option>
                                    <option value="mensal">Mensalmente</option>
                                </select>
                            </div>

                            <div class="form-group col-md-6 mb-3 d-none" id="div_qtd_recorrencia">
                                <label class="form-label font-weight-bold">Quantidade de Repetições</label>
                                <input type="number" min="1" max="12" name="qtd_recorrencia" class="form-control" value="1" placeholder="Ex: 3 vezes">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success font-weight-bold">
                            <i class="la la-save"></i> Salvar Tarefa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 1: NOVO AGENDAMENTO DE OS -->
    <div class="modal fade" id="modal1" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg">
                <form method="post" action="/agendamentos/save">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white"><i class="la la-calendar-plus text-white me-2"></i> Novo Agendamento de Ordem de Serviço</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>

                    <div class="modal-body p-5">
                        <div class="row">
                            <div class="form-group col-md-8 col-12 mb-3">
                                <label class="form-label font-weight-bold">Cliente *</label>
                                <select required class="form-control select2" name="cliente_id" id="kt_select2_3" style="width: 100%;">
                                    <option value="">Selecione o Cliente</option>
                                    @foreach($clientes as $c)
                                        <option value="{{$c->id}}">{{$c->razao_social}} ({{$c->cpf_cnpj}})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-4 col-12 mb-3">
                                <label class="form-label font-weight-bold">Veículo</label>
                                <select class="form-control" name="veiculo_id" id="select_veiculo" style="width: 100%;">
                                    <option value="">Selecione o cliente...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6 col-12 mb-3">
                                <label class="form-label font-weight-bold">Atendente / Mecânico Responsável *</label>
                                <select required class="form-control select2" name="funcionario_id" style="width: 100%;">
                                    <option value="">Selecione o Responsável</option>
                                    @foreach($funcionarios as $f)
                                        <option value="{{$f->id}}">{{$f->nome}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-6 col-12 mb-3">
                                <label class="form-label font-weight-bold">Data do Agendamento *</label>
                                <input required type="date" name="data" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6 col-6 mb-3">
                                <label class="form-label font-weight-bold">Horário de Início *</label>
                                <input required type="time" name="inicio" class="form-control" value="08:00">
                            </div>

                            <div class="form-group col-md-6 col-6 mb-3">
                                <label class="form-label font-weight-bold">Horário de Término Estimado *</label>
                                <input required type="time" name="termino" class="form-control" value="09:00">
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label font-weight-bold">Queixa do Cliente / Observações</label>
                            <textarea name="observacao" class="form-control" rows="3" placeholder="Relato do cliente, serviços necessários ou observações gerais..."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary font-weight-bold">
                            <i class="la la-save"></i> Confirmar Agendamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL JUSTIFICATIVA DE ATRASO -->
    <div class="modal fade" id="modal-justificativa-atraso" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white"><i class="la la-exclamation-triangle me-2"></i> Tarefa Atrasada!</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-dark font-weight-bold mb-2">Esta tarefa está vencida. Informe a justificativa do atraso obrigatoriamente:</p>
                    <textarea id="input_justificativa_texto" class="form-control" rows="4" placeholder="Digite o motivo do atraso..."></textarea>
                    <input type="hidden" id="tarefa_id_atrasada" value="">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn_confirmar_conclusao_atrasada" class="btn btn-success font-weight-bold">
                        <i class="la la-check"></i> Concluir Tarefa
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript">

        window.mapaTarefasCache = {};
        var calendar = null;

        $(document).ready(function() {
            var calendarEl = document.getElementById('calendar');

            if (calendarEl) {
                calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: 'pt-br',
                    initialView: 'dayGridMonth',
                    initialDate: '2026-09-01',
                    editable: false,
                    selectable: false,
                    dayMaxEvents: 3,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    buttonText: {
                        today: 'Hoje',
                        month: 'Mês',
                        week: 'Semana',
                        day: 'Dia'
                    },
                    events: {
                        url: '/agendamentos/all',
                        method: 'GET',
                        extraParams: function() {
                            return {
                                funcionario_id: $('#kt_select2_1').val() !== 'null' ? $('#kt_select2_1').val() : '',
                                cliente_id: $('#kt_select2_7').val() !== 'null' ? $('#kt_select2_7').val() : ''
                            };
                        },
                        success: function(events) {
                            if (Array.isArray(events)) {
                                events.forEach(function(item) {
                                    let props = item.extendedProps || item;
                                    if (props.tarefa_id || (item.id && item.id.toString().indexOf('tarefa_') !== -1)) {
                                        let id = props.tarefa_id || item.id.toString().replace('tarefa_', '');
                                        window.mapaTarefasCache[id] = props;
                                    }
                                });
                            }
                        }
                    },
                    eventClick: function(info) {
                        info.jsEvent.preventDefault();
                        processarCliqueEvento(info.event);
                    }
                });

                calendar.render();
            }

            // 🚀 GARANTIA DUPLA: Se o evento não disparar via FullCalendar, dispara via clique jQuery no DOM
            $(document).on('click', '.fc-event', function(e) {
                let titleText = $(this).text().trim();
                for (let id in window.mapaTarefasCache) {
                    let t = window.mapaTarefasCache[id];
                    if (t.titulo && titleText.indexOf(t.titulo) !== -1) {
                        abrirModalResumoTarefa(t);
                        break;
                    }
                }
            });

            $('#filtrar').click(function(e) {
                e.preventDefault();
                if (calendar) {
                    calendar.refetchEvents();
                }
            });

            $(document).on('change', '#select_recorrencia', function() {
                if ($(this).val() !== 'nao') {
                    $('#div_qtd_recorrencia').removeClass('d-none');
                } else {
                    $('#div_qtd_recorrencia').addClass('d-none');
                }
            });

            $('#kt_select2_3').on('change', function() {
                let clienteId = $(this).val();
                if (clienteId && clienteId !== 'null') {
                    $.get('/ordemServico/getVeiculosCliente/' + clienteId, function(res) {
                        let options = '<option value="">Selecione o veículo...</option>';
                        if (res && res.length > 0) {
                            res.forEach(function(v) {
                                options += `<option value="${v.id}">${v.placa} - ${v.marca} ${v.modelo}</option>`;
                            });
                        } else {
                            options = '<option value="">Nenhum veículo cadastrado</option>';
                        }
                        $('#select_veiculo').html(options);
                    });
                }
            });
        });

        function processarCliqueEvento(event) {
            let props = event.extendedProps || {};
            let taskId = props.tarefa_id || (event.id ? event.id.toString().replace('tarefa_', '') : null);

            if (props.tipo === 'tarefa' || taskId) {
                let dadosCompletos = window.mapaTarefasCache[taskId] || props;
                abrirModalResumoTarefa(dadosCompletos, taskId);
                return;
            }

            if (event.url) {
                window.location.href = event.url;
            }
        }

        function abrirModalResumoTarefa(props, fallbackId) {
            let taskId = props.tarefa_id || fallbackId;

            $('#task_modal_badge_id').text('#' + taskId);
            $('#task_modal_nome').text(props.titulo || 'Sem título');
            $('#task_modal_descricao').text(props.descricao || 'Sem orientações adicionais informadas.');
            $('#task_modal_responsavel').text(props.funcionario || 'Não atribuído');
            $('#task_modal_prioridade').text(props.prioridade || 'Normal');
            $('#task_modal_inicio').text(props.data_inicio || '-');
            $('#task_modal_limite').text(props.data_limite || 'Sem prazo definido');
            $('#task_modal_tempo').text(props.tempo_gasto || '0 Minutos');
            $('#btn_editar_tarefa').attr('href', '/tarefas/edit/' + taskId);

            // Status Badge
            let badgeClass = 'bg-secondary text-white';
            let statusNome = 'Pendente';
            if (props.status === 'pendente') { badgeClass = 'bg-warning text-white'; statusNome = 'Pendente'; }
            if (props.status === 'em_andamento') { badgeClass = 'bg-info text-white'; statusNome = 'Em Andamento'; }
            if (props.status === 'concluida') { badgeClass = 'bg-success text-white'; statusNome = 'Concluída'; }
            if (props.is_atrasada) { badgeClass = 'bg-danger text-white'; statusNome = 'ATRASADA / VENCIDA'; }

            $('#task_modal_status_badge').attr('class', 'badge px-3 py-1 ' + badgeClass).text(statusNome);

            if (props.justificativa_atraso) {
                $('#div_justificativa_exibir').removeClass('d-none');
                $('#task_modal_justificativa').text(props.justificativa_atraso);
            } else {
                $('#div_justificativa_exibir').addClass('d-none');
            }

            // Ações Diretas na Agenda
            let actionHtml = '';
            if (props.status === 'pendente') {
                actionHtml += `<button onclick="executarAcaoTarefa(${taskId}, 'iniciar')" class="btn btn-primary font-weight-bold shadow-sm me-2"><i class="la la-play"></i> Iniciar Tarefa</button>`;
            }
            if (props.status === 'em_andamento') {
                actionHtml += `<button onclick="executarAcaoTarefa(${taskId}, 'pausar')" class="btn btn-warning font-weight-bold shadow-sm me-2"><i class="la la-pause"></i> Pausar</button>`;
            }
            if (props.status !== 'concluida') {
                actionHtml += `<button onclick="finalizarTarefaComJustificativa(${taskId}, ${props.is_atrasada ? 'true' : 'false'})" class="btn btn-success font-weight-bold shadow-sm"><i class="la la-check"></i> Concluir Tarefa</button>`;
            }

            $('#task_modal_actions').html(actionHtml);
            $('#modal-ver-tarefa').modal('show');
        }

        function executarAcaoTarefa(taskId, acao) {
            $.ajax({
                url: '/tarefas/' + acao + '/' + taskId,
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) {
                    swal("Sucesso!", res.mensagem || "Status atualizado!", "success");
                    $('#modal-ver-tarefa').modal('hide');
                    if (calendar) {
                        calendar.refetchEvents();
                    }
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON ? xhr.responseJSON.mensagem : "Erro ao processar ação.";
                    swal("Atenção!", msg, "error");
                }
            });
        }

        function finalizarTarefaComJustificativa(id, isAtrasada) {
            if (isAtrasada) {
                $('#tarefa_id_atrasada').val(id);
                $('#input_justificativa_texto').val('');
                $('#modal-ver-tarefa').modal('hide');
                $('#modal-justificativa-atraso').modal('show');
                setTimeout(function() {
                    $('#input_justificativa_texto').focus();
                }, 500);
            } else {
                swal({
                    title: "Concluir Tarefa?",
                    text: "Deseja marcar esta tarefa como finalizada?",
                    icon: "info",
                    buttons: ["Cancelar", "Sim, Concluir"]
                }).then(sim => {
                    if (sim) {
                        $.ajax({
                            url: `/tarefas/finalizar/${id}`,
                            type: 'GET',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            success: function(res) {
                                swal("Concluída!", res.mensagem || "Tarefa finalizada com sucesso!", "success");
                                $('#modal-ver-tarefa').modal('hide');
                                if (calendar) {
                                    calendar.refetchEvents();
                                }
                            },
                            error: function(xhr) {
                                let msg = xhr.responseJSON ? xhr.responseJSON.mensagem : "Erro ao finalizar tarefa.";
                                swal("Atenção!", msg, "error");
                            }
                        });
                    }
                });
            }
        }

        $(document).on('click', '#btn_confirmar_conclusao_atrasada', function() {
            let id = $('#tarefa_id_atrasada').val();
            let justificativa = $('#input_justificativa_texto').val();

            if (!justificativa || justificativa.trim() === "") {
                alert("A justificativa é obrigatória para finalizar tarefas atrasadas.");
                $('#input_justificativa_texto').focus();
                return;
            }

            $.ajax({
                url: `/tarefas/finalizar/${id}`,
                type: 'GET',
                data: { justificativa: justificativa },
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(res) {
                    $('#modal-justificativa-atraso').modal('hide');
                    swal("Concluída!", res.mensagem || "Tarefa finalizada com sucesso!", "success");
                    if (calendar) {
                        calendar.refetchEvents();
                    }
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON ? xhr.responseJSON.mensagem : "Erro ao finalizar tarefa.";
                    swal("Atenção!", msg, "error");
                }
            });
        });
    </script>
@endsection
