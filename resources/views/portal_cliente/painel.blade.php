<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Parceiro - Gestão & Rastreabilidade</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1e293b;
        }
        .header-portal {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
            color: white;
            padding: 24px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            margin-bottom: 30px;
        }
        .header-logo {
            max-height: 48px;
            background: white;
            padding: 4px 10px;
            border-radius: 8px;
        }
        /* --- ESTILOS DO MENU LATERAL --- */
        .sidebar-nav {
            background: #ffffff;
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
        }
        .sidebar-nav .nav-link {
            color: #475569;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
        }
        .sidebar-nav .nav-link i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        .sidebar-nav .nav-link:hover {
            background-color: #f1f5f9;
            color: #0f766e;
        }
        .sidebar-nav .nav-link.active {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.2);
        }
        /* --- FIM ESTILOS MENU --- */

        .card-custom {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            padding: 24px;
            margin-bottom: 24px;
        }
        .card-title-custom {
            color: #0f766e;
            font-weight: 700;
            font-size: 1.15rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #f0fdf4;
            padding-bottom: 12px;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary-custom:hover {
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(15, 118, 110, 0.2);
        }
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.78rem;
        }
        .badge-pendente { background-color: #fef3c7; color: #b45309; }
        .badge-agendado { background-color: #d1fae5; color: #065f46; }
        .badge-realizado { background-color: #e0f2fe; color: #0369a1; }
        .badge-recusado { background-color: #fee2e2; color: #991b1b; }

        /* Turnos Agendamento */
        .turno-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #f8fafc;
        }
        .turno-card:hover:not(.esgotado) {
            border-color: #0f766e;
            background: #f0fdf4;
        }
        .turno-card.selecionado {
            border-color: #0f766e;
            background: #ecfdf5;
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.15);
        }
        .sla-banner {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 1px solid #fde68a;
            border-left: 5px solid #f59e0b;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 22px;
        }
        .btn-sair {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 0.88rem;
            text-decoration: none;
        }
        .btn-sair:hover { background: rgba(255, 255, 255, 0.25); color: white; }

        /* Calendário do Cliente */
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
        .cal-header { background: #0f766e; color: #fff; padding: 8px; text-align: center; font-weight: bold; border-radius: 4px; font-size: 0.85rem;}
        .cal-day { background: #f8f9fa; border: 1px solid #e2e8f0; border-radius: 6px; min-height: 100px; padding: 6px; }
        .cal-day-empty { background: #f1f5f9; border: 1px dashed #cbd5e1; min-height: 100px; border-radius: 6px; }
        .cal-day-num { font-weight: bold; font-size: 13px; margin-bottom: 6px; color: #475569; }
        .cal-event-btn { color: white; padding: 5px 8px; border-radius: 6px; font-size: 10px; margin-bottom: 5px; border: 1px solid rgba(0,0,0,0.1); display: flex; flex-direction: column; gap: 4px;}
        .event-agendado { background-color: #f59e0b; }
        .event-iniciado { background-color: #0ea5e9; }
        .event-finalizado { background-color: #10b981; }
        .event-cancelado { background-color: #ef4444; }

        /* EPI Checkboxes Styling */
        .epi-checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; }
        .epi-checkbox-group .form-check { margin-bottom: 0; padding-left: 2rem; }
        .epi-checkbox-group .form-check-input { width: 1.2rem; height: 1.2rem; margin-left: -2rem; }
        .epi-checkbox-group .form-check-label { padding-top: 0.1rem; font-size: 0.9rem; }
    </style>
    <link rel="stylesheet" href="{{ asset('css/ui-standalone-forms.css') }}?v={{ filemtime(public_path('css/ui-standalone-forms.css')) }}">
</head>
<body class="fer-standalone-form fer-portal-panel">

<?php
$listaFuncionarios = $funcionarios ?? [];
$listaTiposVeiculos = $tiposVeiculos ?? [];
$listaCacambas = $cacambasProdutos ?? [];
$listaResiduos = $produtosResiduos ?? [];
$listaColetas = $coletas ?? [];
$lotes = $lotesRastreabilidade ?? [];

// Verifica permissão na tabela de pessoas para exibir rastreabilidade
$exibeRastreabilidade = isset($cliente) && ((int)($cliente->exige_rastreabilidade ?? 0) === 1);
?>

    <!-- HEADER -->
<div class="header-portal">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            @if(isset($config) && !empty($config->logo))
                <img src="/logos/{{ $config->logo }}" alt="Logo" class="header-logo">
            @else
                <i class="fas fa-recycle fa-2x"></i>
            @endif
            <div>
                <h4 class="mb-0 fw-bold" style="font-size: 1.1rem;">{{ $config->nome_fantasia ?? $config->razao_social ?? 'Portal Ambiental' }}</h4>
                <small style="opacity: 0.85;">Painel do Parceiro - Agendamentos & Rastreabilidade</small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <span style="font-size: 0.78rem; opacity: 0.8; display: block;">Parceiro Conectado:</span>
                <strong style="font-size: 0.92rem;">{{ Session::get('cliente_nome') }}</strong>
            </div>
            <a href="/portal-cliente/sair" class="btn-sair">
                <i class="fas fa-sign-out-alt me-1"></i> Sair
            </a>
        </div>
    </div>
</div>

<div class="container mb-5">

    @if(session('sucesso'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('sucesso') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('erro'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('erro') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- TÍTULO GLOBAL E BOTÃO DE NOVO AGENDAMENTO (SEMPRE VISÍVEL) -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0" style="font-size: 1.5rem;">Painel de Controle</h3>
            <p class="text-muted small mb-0">Navegue no menu lateral para visualizar suas informações.</p>
        </div>
        <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalAgendamento">
            <i class="fas fa-calendar-plus me-2"></i> Novo Agendamento de Coleta
        </button>
    </div>

    <!-- ESTRUTURA DO DASHBOARD (MENU + CONTEÚDO) -->
    <div class="row">

        <!-- MENU LATERAL -->
        <div class="col-lg-3 mb-4">
            <div class="sidebar-nav nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">

                <button class="nav-link active" id="tab-inicio-btn" data-bs-toggle="pill" data-bs-target="#tab-inicio" type="button" role="tab">
                    <i class="fas fa-calendar-alt"></i> Página Inicial
                </button>

                @if(isset($cliente) && $cliente->exibe_contratos)
                    <button class="nav-link" id="tab-contratos-btn" data-bs-toggle="pill" data-bs-target="#tab-contratos" type="button" role="tab">
                        <i class="fas fa-file-signature"></i> Contratos & Medições
                    </button>
                @endif

                @if($exibeRastreabilidade)
                    <button class="nav-link" id="tab-rastreabilidade-btn" data-bs-toggle="pill" data-bs-target="#tab-rastreabilidade" type="button" role="tab">
                        <i class="fas fa-seedling"></i> Produção & Lotes
                    </button>
                @endif

                <button class="nav-link" id="tab-pesagens-btn" data-bs-toggle="pill" data-bs-target="#tab-pesagens" type="button" role="tab">
                    <i class="fas fa-weight-hanging"></i> Tickets de Pesagem
                </button>

                <button class="nav-link" id="tab-solicitacoes-btn" data-bs-toggle="pill" data-bs-target="#tab-solicitacoes" type="button" role="tab">
                    <i class="fas fa-clock-rotate-left"></i> Histórico de Coletas
                </button>

            </div>
        </div>

        <!-- ÁREA DE CONTEÚDO DAS ABAS -->
        <div class="col-lg-9">
            <div class="tab-content" id="v-pills-tabContent">

                <!-- ABA 1: PÁGINA INICIAL (CALENDÁRIO) -->
                <div class="tab-pane fade show active" id="tab-inicio" role="tabpanel">
                    @if(isset($coletasPorData) && isset($dt))
                        <div class="card-custom m-0">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title-custom mb-0 border-0 pb-0"><i class="fas fa-calendar-alt text-primary"></i> Programação de Coletas (Meu Calendário)</h5>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="?mes={{ $mesAnterior }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-left"></i></a>
                                    <strong class="mx-2 text-uppercase">{{ \Carbon\Carbon::parse($dt)->translatedFormat('F / Y') }}</strong>
                                    <a href="?mes={{ $mesProximo }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-right"></i></a>
                                </div>
                            </div>

                            <div class="cal-grid mb-2">
                                <div class="cal-header">Dom</div><div class="cal-header">Seg</div><div class="cal-header">Ter</div>
                                <div class="cal-header">Qua</div><div class="cal-header">Qui</div><div class="cal-header">Sex</div><div class="cal-header">Sáb</div>
                            </div>
                            <div class="cal-grid">
                                @for ($i = 0; $i < $primeiroDiaSemana; $i++)
                                    <div class="cal-day-empty"></div>
                                @endfor

                                @for ($dia = 1; $dia <= $diasNoMes; $dia++)
                                    @php
                                        $dataCurrent = $dt->copy()->day($dia)->format('Y-m-d');
                                        $coletasDia = $coletasPorData[$dataCurrent] ?? [];
                                    @endphp
                                    <div class="cal-day">
                                        <div class="cal-day-num">{{ sprintf('%02d', $dia) }}</div>
                                        @foreach($coletasDia as $coleta)
                                            @php
                                                $classeStatus = match(strtolower($coleta->status)) {
                                                    'agendado' => 'event-agendado',
                                                    'iniciado' => 'event-iniciado',
                                                    'finalizado', 'concluida' => 'event-finalizado',
                                                    'cancelado' => 'event-cancelado',
                                                    default => 'event-agendado'
                                                };

                                                $nomeFilialAg = 'Matriz';
                                                if (!empty($coleta->filial_id)) {
                                                    $fObj = \Illuminate\Support\Facades\DB::table('filials')->where('id', $coleta->filial_id)->first();
                                                    $nomeFilialAg = $fObj->descricao ?? ('Filial #' . $coleta->filial_id);
                                                }
                                                $primeiroNomeMotorista = explode(' ', trim($coleta->motorista->nome ?? 'Sem Mot.'))[0];
                                            @endphp
                                            <div class="cal-event-btn {{ $classeStatus }}" title="Status: {{ ucfirst($coleta->status) }}">
                                                <div class="d-flex align-items-center w-100">
                                                    <i class="fas fa-truck me-1"></i> <span class="text-truncate fw-bold">{{ $coleta->veiculo->placa ?? 'S/ Placa' }}</span>
                                                    <span class="ms-auto bg-white text-dark px-1 rounded font-weight-bold" style="font-size: 8px;">{{ $nomeFilialAg }}</span>
                                                </div>
                                                <div class="d-flex align-items-center opacity-75" style="font-size: 9px; margin-top: 2px;">
                                                    <i class="fas fa-user-circle me-1"></i> {{ $primeiroNomeMotorista }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endfor
                            </div>
                            <div class="d-flex gap-3 mt-3 justify-content-end" style="font-size: 0.8rem;">
                                <span><i class="fas fa-square text-warning"></i> Agendado</span>
                                <span><i class="fas fa-square text-info"></i> Em Percurso</span>
                                <span><i class="fas fa-square text-success"></i> Finalizado</span>
                                <span><i class="fas fa-square text-danger"></i> Cancelado</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- ABA 2: CONTRATOS E MEDIÇÕES -->
                @if(isset($cliente) && $cliente->exibe_contratos)
                    <div class="tab-pane fade" id="tab-contratos" role="tabpanel">
                        <div class="card-custom m-0">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                                <h5 class="card-title-custom mb-0 border-0 pb-0">
                                    <i class="fas fa-file-signature text-info"></i> Gestão de Contratos e Medições
                                </h5>
                            </div>
                            <p class="text-muted small mb-4">Acompanhe as medições do seu contrato, valide os serviços e acompanhe as emissões de Nota Fiscal (NFS-e).</p>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-sm">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Contrato</th>
                                        <th>Faturamento</th>
                                        <th>Valor (R$)</th>
                                        <th>Status (Aprovação / NF)</th>
                                        <th>Financeiro</th>
                                        <th class="text-center">Ações / Documentos</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($medicoes ?? [] as $med)
                                        <tr>
                                            <td data-label="Contrato">
                                                <strong class="text-dark">{{ $med->numero_contrato ?? 'Avulso' }}</strong><br>
                                                @if(isset($med->contrato_eng_id) && $med->contrato_eng_id)
                                                    <a href="/portal-cliente/contrato/imprimir/{{ $med->contrato_eng_id }}" target="_blank" class="badge bg-secondary text-decoration-none mt-1 shadow-sm" title="Imprimir Contrato Original">
                                                        <i class="fas fa-file-contract"></i> Ver Contrato
                                                    </a>
                                                @endif
                                            </td>
                                            <td data-label="Faturamento">{{ \Carbon\Carbon::parse($med->data_faturamento)->format('d/m/Y') }}</td>
                                            <td data-label="Valor (R$)" class="fw-bold text-success">R$ {{ number_format($med->valor_liquido, 2, ',', '.') }}</td>

                                            <!-- STATUS INTELIGENTE -->
                                            <td data-label="Status (Aprovação / NF)">
                                                @if($med->status_final_portal == 'pendente')
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Aguardando Aprovação</span>
                                                @elseif($med->status_final_portal == 'aguardando_nfse')
                                                    <span class="badge bg-info text-dark"><i class="fas fa-spinner fa-spin me-1"></i> Aguardando NF</span>
                                                @elseif($med->status_final_portal == 'concluido')
                                                    <span class="badge bg-success"><i class="fas fa-check-double me-1"></i> NF-e Emitida</span>
                                                @else
                                                    <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Rejeitada</span>
                                                    <small class="d-block text-muted mt-1" style="font-size: 10px;">{{ $med->motivo_rejeicao }}</small>
                                                @endif
                                            </td>

                                            <td data-label="Financeiro">
                                                @if(strtolower($med->status_financeiro) == 'recebido' || strtolower($med->status_financeiro) == 'pago')
                                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Pago</span>
                                                @else
                                                    <span class="badge bg-secondary"><i class="fas fa-hourglass-half me-1"></i> {{ ucfirst($med->status_financeiro) }}</span>
                                                @endif
                                            </td>

                                            <td data-label="Ações / Documentos" class="text-center">
                                                @if($med->status_final_portal == 'pendente')
                                                    <button class="btn btn-sm btn-success px-2 py-1 me-1" onclick="aprovarMedicao({{ $med->id }})" title="Aprovar Medição">
                                                        <i class="fas fa-check"></i> Aprovar
                                                    </button>
                                                    <button class="btn btn-sm btn-danger px-2 py-1" onclick="abrirModalRejeicao({{ $med->id }})" title="Rejeitar Medição">
                                                        <i class="fas fa-times"></i> Rejeitar
                                                    </button>
                                                @endif

                                                <div class="mt-1 d-flex justify-content-center gap-1 flex-wrap">
                                                    <!-- Relatório Fatura -->
                                                    <a href="/contratos/medicoes/imprimir/{{ $med->id }}" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm" style="border-radius: 6px; font-size: 10px;">
                                                        <i class="fas fa-file-pdf me-1"></i> Medição
                                                    </a>

                                                    <!-- Múltiplas Notas Fiscais -->
                                                    @if(count($med->notas_fiscais ?? []) > 0)
                                                        @foreach($med->notas_fiscais as $index => $nota)
                                                            <a href="/nfse/imprimir/{{ $nota->id }}" target="_blank" class="btn btn-sm btn-outline-success shadow-sm" style="border-radius: 6px; font-size: 10px;" title="NFS-e {{ $nota->numero_nfse }}">
                                                                <i class="fas fa-receipt me-1"></i> NF-e {{ $index + 1 }}
                                                            </a>
                                                        @endforeach
                                                    @elseif(!empty($med->numero_nfse))
                                                        <!-- Legado (Caso a nota ainda esteja na tabela principal) -->
                                                        <a href="/contratos/medicoes/danfse/{{ $med->id }}" target="_blank" class="btn btn-sm btn-outline-success shadow-sm" style="border-radius: 6px; font-size: 10px;">
                                                            <i class="fas fa-receipt me-1"></i> Ver NF-e
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Nenhuma fatura de medição disponível.</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- ABA 3: PRODUÇÃO E LOTES (RASTREABILIDADE) -->
                @if($exibeRastreabilidade)
                    <div class="tab-pane fade" id="tab-rastreabilidade" role="tabpanel">
                        <div class="card-custom m-0">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                                <h5 class="card-title-custom mb-0 border-0 pb-0">
                                    <i class="fas fa-seedling text-success"></i> Gestão de Produção & Rastreabilidade de Lotes
                                </h5>
                            </div>

                            <!-- FILTROS -->
                            <form method="GET" action="/portal-cliente/painel" class="row g-2 mb-4 bg-light p-3 rounded border align-items-end">
                                <div class="col-md-3">
                                    <label class="small fw-bold text-muted">Data Inicial</label>
                                    <input type="date" name="data_inicial" class="form-control form-control-sm" value="{{ request('data_inicial') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="small fw-bold text-muted">Data Final</label>
                                    <input type="date" name="data_final" class="form-control form-control-sm" value="{{ request('data_final') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold text-muted">Buscar por Lote</label>
                                    <input type="text" name="busca" class="form-control form-control-sm" placeholder="Ex: LOTE-2026..." value="{{ request('busca') }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
                                </div>
                            </form>

                            @if(isset($lotesRastreabilidade) && count($lotesRastreabilidade) > 0)
                                <div class="accordion" id="accordionRastreabilidade">
                                    @foreach($lotesRastreabilidade as $lote)
                                        <div class="card border mb-3 shadow-none" style="border-radius: 12px; overflow: hidden;" data-filial="{{ strtolower($lote->codigo_lote . ' ' . ($lote->fornecedor->razao_social ?? $cliente->nome_completo)) }}">
                                            <!-- CABEÇALHO DO LOTE -->
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-3" id="headingLote{{ $lote->id }}" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapseLote{{ $lote->id }}">
                                                <div>
                                                    <span class="badge bg-success me-2 px-3 py-2" style="font-size: 0.85rem;">{{ $lote->codigo_lote }}</span>
                                                    <strong class="text-dark me-2">📦 Matéria-Prima: {{ optional($lote->produtoOrigem)->nome ?? 'Material não especificado' }}</strong>
                                                    <span class="text-secondary">| NF-e: <strong>{{ $lote->nfe_entrada ?? 'N/A' }}</strong> | MTR: <strong>{{ $lote->mtr_entrada ?? 'N/A' }}</strong></span>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-white text-dark border me-2">Entrada Total: <strong>{{ number_format($lote->peso_entrada, 2, ',', '.') }} kg</strong></span>
                                                    <i class="fas fa-chevron-down text-muted"></i>
                                                </div>
                                            </div>

                                            <!-- CONTEÚDO EXPANSÍVEL -->
                                            <div id="collapseLote{{ $lote->id }}" class="collapse" data-bs-parent="#accordionRastreabilidade">
                                                <div class="card-body bg-white p-4">
                                                    <ul class="nav nav-tabs mb-3" role="tablist">
                                                        <li class="nav-item">
                                                            <a class="nav-link active fw-bold text-success" data-bs-toggle="tab" href="#resumo_{{ $lote->id }}">
                                                                <i class="fa fa-chart-pie me-1"></i> Resumo de Produção (Totais)
                                                            </a>
                                                        </li>
                                                        <li class="nav-item">
                                                            <a class="nav-link text-secondary fw-bold" data-bs-toggle="tab" href="#historico_{{ $lote->id }}">
                                                                <i class="fa fa-truck-loading me-1"></i> Rastreabilidade de Expedição (MTR)
                                                            </a>
                                                        </li>
                                                    </ul>

                                                    <div class="tab-content">
                                                        <!-- RESUMO -->
                                                        <div class="tab-pane fade show active" id="resumo_{{ $lote->id }}" role="tabpanel">
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered table-sm align-middle mb-0">
                                                                    <thead class="table-light">
                                                                    <tr>
                                                                        <th>Produto Acabado</th>
                                                                        <th class="text-end">Entrada Total (Produzido)</th>
                                                                        <th class="text-end">Saída Total (Expedido via PEPS)</th>
                                                                        <th class="text-end">Saldo Atual em Estoque</th>
                                                                    </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                    @php
                                                                        $resumoList = [];
                                                                        foreach($lote->cortes as $corte) {
                                                                            $pId = $corte->produto_gerado_id;
                                                                            if (!isset($resumoList[$pId])) {
                                                                                $resumoList[$pId] = [
                                                                                    'nome'    => optional($corte->produto)->nome ?? "Produto #{$pId}",
                                                                                    'entrada' => 0, 'saida' => 0, 'saldo' => 0,
                                                                                ];
                                                                            }
                                                                            $resumoList[$pId]['entrada'] += (float)$corte->peso_liquido;
                                                                            $resumoList[$pId]['saida']   += (float)$corte->quantidade_vendida;
                                                                            $resumoList[$pId]['saldo']   += (float)$corte->saldo_disponivel;
                                                                        }
                                                                    @endphp
                                                                    @foreach($resumoList as $item)
                                                                        <tr>
                                                                            <td data-label="Produto Acabado"><strong>{{ $item['nome'] }}</strong></td>
                                                                            <td data-label="Entrada Total (Produzido)" class="text-end text-primary fw-bold">{{ number_format($item['entrada'], 2, ',', '.') }} kg</td>
                                                                            <td data-label="Saída Total (Expedido via PEPS)" class="text-end text-danger fw-bold">{{ number_format($item['saida'], 2, ',', '.') }} kg</td>
                                                                            <td data-label="Saldo Atual em Estoque" class="text-end text-success fw-bold">{{ number_format($item['saldo'], 2, ',', '.') }} kg</td>
                                                                        </tr>
                                                                    @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>

                                                        <!-- MTR -->
                                                        <div class="tab-pane fade" id="historico_{{ $lote->id }}" role="tabpanel">
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered table-sm align-middle mb-0">
                                                                    <thead class="table-light">
                                                                    <tr>
                                                                        <th>Data / Hora</th>
                                                                        <th>Produto Expedido</th>
                                                                        <th class="text-end">Quantidade Total</th>
                                                                        <th>Destino / Venda</th>
                                                                        <th class="text-center">Download MTR</th>
                                                                    </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                    @forelse($lote->ticketsSaida as $ticket)
                                                                        <tr>
                                                                            <td data-label="Data / Hora">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                                                                            <td data-label="Produto Expedido">{{ optional($ticket->produto)->nome }}</td>
                                                                            <td data-label="Quantidade Total" class="text-end text-danger fw-bold">{{ number_format($ticket->peso - ($ticket->peso_bag ?? 0), 2, ',', '.') }} kg</td>
                                                                            <td data-label="Destino / Venda">
                                                                                @if(optional($ticket->pesagem)->venda_id)
                                                                                    Venda #{{ $ticket->pesagem->venda_id }} ({{ optional(optional($ticket->pesagem)->cliente)->razao_social ?? 'Cliente' }})
                                                                                @else
                                                                                    Expedição Direta
                                                                                @endif
                                                                            </td>
                                                                            <td data-label="Download MTR" class="text-center">
                                                                                <a href="/portal-cliente/mtr/download/{{ $ticket->id }}" target="_blank" class="btn btn-sm btn-outline-success px-3 py-1" style="border-radius: 6px;">
                                                                                    <i class="fas fa-file-pdf me-1"></i> MTR
                                                                                </a>
                                                                            </td>
                                                                        </tr>
                                                                    @empty
                                                                        <tr><td colspan="5" class="text-center text-muted py-3">Nenhum registo de saída expedido via PEPS neste lote ainda.</td></tr>
                                                                    @endforelse
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted py-5 border rounded bg-light">
                                    <i class="fas fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-1 fw-bold">Nenhum lote de rastreabilidade encontrado para este período.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- ABA 4: TICKETS DE PESAGEM -->
                <div class="tab-pane fade" id="tab-pesagens" role="tabpanel">
                    <div class="card-custom m-0">
                        <h5 class="card-title-custom"><i class="fas fa-weight-hanging text-warning"></i> Tickets de Pesagem</h5>

                        <!-- FILTROS -->
                        <form method="GET" action="/portal-cliente/painel" class="row g-2 mb-4 bg-light p-3 rounded border align-items-end">
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Data Inicial</label>
                                <input type="date" name="data_inicial" class="form-control form-control-sm" value="{{ request('data_inicial') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Data Final</label>
                                <input type="date" name="data_final" class="form-control form-control-sm" value="{{ request('data_final') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted">Ticket (Nº ou Token)</label>
                                <input type="text" name="busca" class="form-control form-control-sm" placeholder="Ex: 5733" value="{{ request('busca') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
                            </div>
                        </form>

                        @if(isset($pesagens) && count($pesagens) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Data / Protocolo</th>
                                        <th>Veículo / Motorista</th>
                                        <th>Materiais / Itens</th>
                                        <th class="text-end">Peso Final</th>
                                        <th class="text-center">Download Ticket</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($pesagens as $pes)
                                        <tr>
                                            <td>
                                                <strong>{{ \Carbon\Carbon::parse($pes->dt_registro)->format('d/m/Y') }}</strong><br>
                                                <span class="badge bg-{{ $pes->status == 'concluído' ? 'success' : 'warning' }} text-uppercase" style="font-size: 0.7rem;">{{ $pes->status }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark"><i class="fas fa-truck text-secondary me-1"></i> {{ $pes->veiculo->placa ?? 'N/A' }}</span><br>
                                                <small class="text-muted"><i class="fas fa-user-alt text-secondary me-1"></i> {{ explode(' ', trim($pes->motorista_nome ?? 'N/A'))[0] }}</small>
                                            </td>
                                            <td>
                                                @if($pes->tickets && $pes->tickets->count() > 0)
                                                    <ul class="list-unstyled mb-0 text-secondary">
                                                        @foreach($pes->tickets as $tkt)
                                                            <li>• {{ $tkt->produto->nome ?? 'Material não id.' }} ({{ number_format($tkt->peso, 2, ',', '.') }} kg)</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <small class="text-muted">Aguardando itens...</small>
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold text-success" style="font-size: 1.05rem;">
                                                {{ number_format($pes->peso_final, 2, ',', '.') }} kg
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    // Resolve NF-e (número e chave) e MTR vinculados à pesagem
                                                    $nfeChave = null;
                                                    $nfeNumero = null;

                                                    if (!empty($pes->compra)) {
                                                        $nfeChave = $pes->compra->chave ?: null;
                                                        $nfeNumero = !empty($pes->compra->nf) ? $pes->compra->nf : ($pes->compra->numero_emissao ?: null);
                                                    } elseif (!empty($pes->venda)) {
                                                        $nfeChave = $pes->venda->chave ?: null;
                                                        $nfeNumero = !empty($pes->venda->NfNumero) ? $pes->venda->NfNumero : null;
                                                    }

                                                    // Fallback para campos vinculados manualmente na pesagem
                                                    $nfeManual = trim((string) ($pes->nfe_entrada ?? $pes->nf_numero ?? ''));
                                                    if ($nfeManual !== '') {
                                                        $apenasDigitos = preg_replace('/\D/', '', $nfeManual);
                                                        if (strlen($apenasDigitos) === 44 && empty($nfeChave)) {
                                                            $nfeChave = $apenasDigitos;
                                                        } elseif (empty($nfeNumero)) {
                                                            $nfeNumero = $nfeManual;
                                                        }
                                                    }

                                                    $mtrNumero = trim((string) ($pes->mtr_entrada ?? ''));
                                                    $temNfe = !empty($nfeNumero) || !empty($nfeChave);
                                                    $temMtr = $mtrNumero !== '';
                                                @endphp

                                                <div class="d-flex justify-content-center align-items-center gap-1 flex-wrap">
                                                    @if($pes->status == 'concluído')
                                                        <a href="/portal-cliente/pesagem/download/{{ $pes->id }}" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm" style="border-radius: 6px;">
                                                            <i class="fas fa-file-pdf me-1"></i> Ticket
                                                        </a>
                                                    @else
                                                        <button class="btn btn-sm btn-light text-muted" disabled style="border-radius: 6px;">
                                                            <i class="fas fa-clock me-1"></i> Em andamento
                                                        </button>
                                                    @endif

                                                    @if($temNfe)
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-success shadow-sm"
                                                                style="border-radius: 6px;"
                                                                onclick="verDocsPesagem('{{ $pes->id }}', '{{ addslashes($nfeNumero ?? '') }}', '{{ addslashes($nfeChave ?? '') }}', '{{ addslashes($mtrNumero) }}')"
                                                                title="Ver NF-e vinculada">
                                                            <i class="fas fa-file-invoice me-1"></i> NF-e
                                                        </button>
                                                    @endif

                                                    @if($temMtr)
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-info shadow-sm"
                                                                style="border-radius: 6px;"
                                                                onclick="verDocsPesagem('{{ $pes->id }}', '{{ addslashes($nfeNumero ?? '') }}', '{{ addslashes($nfeChave ?? '') }}', '{{ addslashes($mtrNumero) }}')"
                                                                title="Ver MTR vinculado">
                                                            <i class="fas fa-recycle me-1"></i> MTR
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-5 border rounded bg-light">
                                <i class="fas fa-balance-scale fa-3x mb-3 text-secondary opacity-50"></i>
                                <p class="mb-1 fw-bold">Nenhuma pesagem encontrada.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- ABA 5: HISTÓRICO DE SOLICITAÇÕES DE COLETA -->
                <div class="tab-pane fade" id="tab-solicitacoes" role="tabpanel">
                    <div class="card-custom m-0">
                        <h5 class="card-title-custom">
                            <i class="fas fa-clock-rotate-left"></i> Histórico de Solicitações de Coleta (Via Portal)
                        </h5>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-sm">
                                <thead class="table-light">
                                <tr>
                                    <th>Protocolo / Data</th>
                                    <th>Material / Veículo / Qtd</th>
                                    <th>Turno / Previsão</th>
                                    <th class="text-center">Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if(count($listaColetas) > 0)
                                    @foreach($listaColetas as $c)
                                        <tr>
                                            <td data-label="Protocolo / Data">
                                                <span class="fw-bold text-success">#{{ $c->id }}</span><br>
                                                <small class="text-muted">{{ date('d/m/Y H:i', strtotime($c->created_at)) }}</small>
                                            </td>
                                            <td data-label="Material / Veículo / Qtd">
                                                <strong class="text-dark d-block">{{ $c->material }}</strong>
                                                @if(!empty($c->tipo_veiculo))
                                                    <small class="text-secondary"><i class="fas fa-truck me-1"></i>{{ $c->tipo_veiculo }}</small>
                                                @endif
                                                @if(!empty($c->peso_estimado))
                                                    <small class="badge bg-light text-dark border ms-1">{{ number_format($c->peso_estimado, 2, ',', '.') }} {{ $c->unidade_peso }}</small>
                                                @endif
                                            </td>
                                            <td data-label="Turno / Previsão">
                                                @if(!empty($c->data_agendada))
                                                    <span class="fw-bold text-success"><i class="fas fa-check-circle me-1"></i>{{ date('d/m/Y H:i', strtotime($c->data_agendada)) }}</span>
                                                @elseif(!empty($c->data_reserva))
                                                    <span class="fw-semibold text-dark">{{ date('d/m/Y', strtotime($c->data_reserva)) }}</span><br>
                                                    <small class="text-muted">Turno: {{ strtoupper($c->turno) }}</small>
                                                @else
                                                    <small class="text-muted">{{ $c->dias_disponiveis }}</small>
                                                @endif
                                            </td>
                                            <td data-label="Status" class="text-center">
                                                @php $st = strtolower((string)$c->status); @endphp
                                                @if($st === 'pendente')
                                                    <span class="badge-status badge-pendente">Em Análise (Previsto)</span>
                                                @elseif(in_array($st, ['agendado', 'aprovado']))
                                                    <span class="badge-status badge-agendado">Confirmado (Agendado)</span>
                                                @elseif(in_array($st, ['concluido', 'realizado']))
                                                    <span class="badge-status badge-realizado">Realizado</span>
                                                @elseif(in_array($st, ['recusado', 'cancelado', 'rejeitado']))
                                                    <span class="badge-status badge-recusado">{{ ucfirst($c->status) }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($c->status) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <p class="mb-0">Nenhum agendamento registrado pelo portal.</p>
                                        </td>
                                    </tr>
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div> <!-- Fechamento tab-content -->
        </div> <!-- Fechamento col-lg-9 -->
    </div> <!-- Fechamento row -->
</div>

<!-- MODAL DE NOVO AGENDAMENTO -->
<div class="modal fade" id="modalAgendamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-light" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold text-success"><i class="fas fa-calendar-plus me-2"></i>Nova Solicitação de Coleta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/portal-cliente/coleta/salvar" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">1. Modalidade de Transporte *</label>
                        <select name="tipo_transporte" id="tipo_transporte" class="form-select" required onchange="alternarModalidade(this.value)">
                            <option value="frota" selected>🚚 Solicitar Veículo / Caçamba (Frota da Empresa)</option>
                            <option value="cliente">🏢 Levaremos o material até o pátio (Entrega Própria)</option>
                        </select>
                    </div>

                    <div id="bloco_frota">
                        <div class="sla-banner">
                            <div class="d-flex align-items-center mb-1">
                                <i class="fas fa-stopwatch text-warning fs-5 me-2"></i>
                                <strong class="text-dark" style="font-size: 0.95rem;">Tempo Limite de Permanência: {{ number_format($parametros->tempo_franquia_horas ?? 4, 0) }} horas</strong>
                            </div>
                            <p class="mb-0 text-secondary" style="font-size: 0.85rem;">
                                O veículo fica disponível durante o turno escolhido. Se ultrapassar o limite, será aplicada taxa de hora extra de <strong>R$ {{ number_format($parametros->valor_taxa_hora_extra ?? 150, 2, ',', '.') }}</strong>.
                            </p>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-secondary small">2. Data Desejada *</label>
                                <input type="date" name="data_reserva" id="data_reserva" class="form-control" min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" required onchange="buscarVagasData(this.value)">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-secondary small">3. Turno Disponível *</label>
                                <input type="hidden" name="turno" id="turno_selecionado" required>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="turno-card text-center" id="card_turno_manha" onclick="selecionarTurno('manha')">
                                            <span class="fw-bold text-dark d-block"><i class="fas fa-sun text-warning me-1"></i> Manhã</span>
                                            <span class="badge bg-success mt-1" id="badge_vagas_manha">...</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="turno-card text-center" id="card_turno_tarde" onclick="selecionarTurno('tarde')">
                                            <span class="fw-bold text-dark d-block"><i class="fas fa-cloud-sun text-primary me-1"></i> Tarde</span>
                                            <span class="badge bg-success mt-1" id="badge_vagas_tarde">...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small">4. Tipo de Veículo *</label>
                                <select name="tipo_veiculo" id="tipo_veiculo" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    @foreach($tiposVeiculos as $tv)
                                        <option value="{{ $tv }}">{{ $tv }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-secondary small">5. Equipamento (Caçamba)</label>
                                <select name="produto_cacamba_id" class="form-select">
                                    <option value="">-- Não precisa --</option>
                                    @foreach($cacambasProdutos as $cac)
                                        <option value="{{ $cac->id }}">{{ $cac->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-secondary small">6. Material / Resíduo *</label>
                            <select name="produto_residuo_id" id="produto_residuo_id" class="form-select" onchange="preencherMaterialTexto(this)" required>
                                <option value="">-- Selecione --</option>
                                @foreach($produtosResiduos as $res)
                                    <option value="{{ $res->id }}" data-nome="{{ $res->nome }}">{{ $res->nome }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="material" id="input_material">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary small">7. Estimativa *</label>
                            <input type="number" step="0.01" name="peso_estimado" class="form-control" required placeholder="Ex: 1500">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-secondary small">Unidade</label>
                            <select name="unidade_peso" class="form-select">
                                <option value="KG">KG</option>
                                <option value="TON">TON</option>
                                <option value="M3">M³</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Anexar Fotos / Tirar Foto (opcional)</label>
                        <input type="file" name="fotos[]" class="form-control" accept="image/*" capture="environment" multiple>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Quais EPIs são obrigatórios para a coleta no local?</label>
                        <div class="epi-checkbox-group mt-1 border rounded p-3 bg-light">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="epi_obrigatorio[]" value="Capacete" id="epiCapacete">
                                <label class="form-check-label" for="epiCapacete">Capacete</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="epi_obrigatorio[]" value="Luvas" id="epiLuvas">
                                <label class="form-check-label" for="epiLuvas">Luvas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="epi_obrigatorio[]" value="Óculos" id="epiOculos">
                                <label class="form-check-label" for="epiOculos">Óculos</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="epi_obrigatorio[]" value="Botas" id="epiBotas">
                                <label class="form-check-label" for="epiBotas">Botas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="epi_obrigatorio[]" value="Protetor Auricular" id="epiAuricular">
                                <label class="form-check-label" for="epiAuricular">Prot. Auricular</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary small">Observações Adicionais</label>
                        <input type="text" name="observacao" class="form-control" placeholder="Restrições, horário limite...">
                    </div>

                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="aceitou_termo_taxa" value="1" id="checkTaxa" checked required>
                        <label class="form-check-label small text-secondary" for="checkTaxa">
                            Estou ciente das regras e tempo limite de permanência.
                        </label>
                    </div>

                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-custom">Enviar Solicitação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --- CONTROLE DE ABAS E FILTROS ---
    document.addEventListener("DOMContentLoaded", function() {
        // Mantém a aba ativa após o filtro (recarregamento da página)
        let activeTab = localStorage.getItem('activeTabPortal');
        if (activeTab) {
            let tabElement = document.querySelector(`button[data-bs-target="${activeTab}"]`);
            if (tabElement) {
                let tab = new bootstrap.Tab(tabElement);
                tab.show();
            }
        }

        // Salva a aba clicada
        let tabButtons = document.querySelectorAll('.sidebar-nav .nav-link');
        tabButtons.forEach(btn => {
            btn.addEventListener('shown.bs.tab', function (e) {
                localStorage.setItem('activeTabPortal', e.target.getAttribute('data-bs-target'));
            });
        });

        // Inicializa a busca de vagas se a data já estiver preenchida
        const dataInput = document.getElementById('data_reserva');
        if (dataInput && dataInput.value) {
            buscarVagasData(dataInput.value);
        }
    });

    // --- CONTROLE DE AGENDAMENTO DE COLETAS ---
    let vagasAtuais = { manha: 0, tarde: 0 };

    function buscarVagasData(data) {
        if (!data) return;

        vagasAtuais.manha = 0;
        vagasAtuais.tarde = 0;
        document.getElementById('badge_vagas_manha').textContent = `0 vaga(s)`;
        document.getElementById('badge_vagas_tarde').textContent = `0 vaga(s)`;

        document.getElementById('card_turno_manha').style.borderColor = '#cbd5e1';
        document.getElementById('card_turno_tarde').style.borderColor = '#cbd5e1';
        document.getElementById('turno_selecionado').value = '';

        fetch(`/portal-cliente/disponibilidade?data=${encodeURIComponent(data)}`)
            .then(r => r.json())
            .then(res => {
                if (!res.dia_util) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Data Indisponível',
                        text: res.mensagem || 'Não realizamos agendamentos de frota neste dia da semana.',
                        confirmButtonColor: '#0f766e',
                        confirmButtonText: 'Entendi'
                    });
                    document.getElementById('data_reserva').value = '';
                    return;
                }

                vagasAtuais.manha = res.manha.disponiveis;
                vagasAtuais.tarde = res.tarde.disponiveis;

                document.getElementById('badge_vagas_manha').textContent = `${res.manha.disponiveis} vaga(s)`;
                document.getElementById('badge_vagas_tarde').textContent = `${res.tarde.disponiveis} vaga(s)`;

                if (res.manha.disponiveis > 0) {
                    selecionarTurno('manha');
                } else if (res.tarde.disponiveis > 0) {
                    selecionarTurno('tarde');
                }
            })
            .catch(err => {
                console.error("Erro ao buscar vagas:", err);
                Swal.fire({
                    icon: 'error',
                    title: 'Ops!',
                    text: 'Erro ao consultar disponibilidade. Tente novamente.',
                    confirmButtonColor: '#0f766e'
                });
                document.getElementById('data_reserva').value = '';
            });
    }

    function selecionarTurno(turno) {
        if (vagasAtuais[turno] <= 0) return;
        document.getElementById('card_turno_manha').style.borderColor = '#cbd5e1';
        document.getElementById('card_turno_tarde').style.borderColor = '#cbd5e1';
        document.getElementById(`card_turno_${turno}`).style.borderColor = '#0f766e';
        document.getElementById('turno_selecionado').value = turno;
    }

    function preencherMaterialTexto(selectObj) {
        const opt = selectObj.options[selectObj.selectedIndex];
        document.getElementById('input_material').value = opt.getAttribute('data-nome') || '';
    }

    function alternarModalidade(valor) {
        document.getElementById('bloco_frota').style.display = (valor === 'cliente') ? 'none' : 'block';
    }

    // --- CONTROLE DE MEDIÇÕES E CONTRATOS ---
    function aprovarMedicao(id) {
        Swal.fire({
            title: 'Aprovar Medição?',
            text: "Você confirma a execução dos serviços desta medição?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0f766e',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, aprovar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/portal-cliente/medicao/${id}/aprovar`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                }).then(r => r.json()).then(res => {
                    if(res.success) {
                        Swal.fire('Aprovada!', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro!', res.message, 'error');
                    }
                });
            }
        });
    }

    function abrirModalRejeicao(id) {
        Swal.fire({
            title: 'Motivo da Rejeição',
            input: 'textarea',
            inputPlaceholder: 'Descreva por que esta medição está incorreta...',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Rejeitar Medição',
            cancelButtonText: 'Cancelar',
            preConfirm: (motivo) => {
                if (!motivo) Swal.showValidationMessage('O motivo é obrigatório');
                return motivo;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/portal-cliente/medicao/${id}/rejeitar`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ motivo: result.value })
                }).then(r => r.json()).then(res => {
                    if(res.success) {
                        Swal.fire('Rejeitada!', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro!', res.message, 'error');
                    }
                });
            }
        });
    }

    // --- VISUALIZAR NF-e, CHAVE E MTR DA PESAGEM ---
    function verDocsPesagem(pesagemId, nfeNumero, nfeChave, mtrNumero) {
        let html = '<div class="text-start" style="font-size: 0.92rem;">';

        html += `
            <div class="mb-3 p-2 bg-light rounded border">
                <small class="text-muted fw-bold d-block">Número da NF-e</small>
                <span class="fw-bold text-dark">${nfeNumero || 'Não informado'}</span>
            </div>
        `;

        html += `
            <div class="mb-3 p-2 bg-light rounded border">
                <small class="text-muted fw-bold d-block">Chave de Acesso NF-e</small>
                <div class="d-flex align-items-center justify-content-between gap-2 mt-1">
                    <span class="fw-bold text-dark text-break" style="font-family: monospace; font-size: 0.85rem;">${nfeChave || 'Não informada'}</span>
                    ${nfeChave ? `<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="copiarTextoPortal('${nfeChave}')" title="Copiar chave"><i class="fas fa-copy"></i></button>` : ''}
                </div>
            </div>
        `;

        html += `
            <div class="p-2 bg-light rounded border">
                <small class="text-muted fw-bold d-block">Número do MTR</small>
                <div class="d-flex align-items-center justify-content-between gap-2 mt-1">
                    <span class="fw-bold text-dark">${mtrNumero || 'Não informado'}</span>
                    ${mtrNumero ? `<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="copiarTextoPortal('${mtrNumero}')" title="Copiar MTR"><i class="fas fa-copy"></i></button>` : ''}
                </div>
            </div>
        `;

        html += '</div>';

        Swal.fire({
            title: `Documentos - Pesagem #${pesagemId}`,
            html: html,
            confirmButtonColor: '#0f766e',
            confirmButtonText: 'Fechar'
        });
    }

    function copiarTextoPortal(texto) {
        if (!texto) return;
        navigator.clipboard.writeText(texto).then(() => {
            Swal.showValidationMessage('');
            const toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1800 });
            toast.fire({ icon: 'success', title: 'Copiado!' });
        });
    }
</script>
</body>
</html>
