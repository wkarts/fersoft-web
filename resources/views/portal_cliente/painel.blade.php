<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Cliente - Portal de Coletas</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        /* Topo / Header Moderno */
        .header-portal {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
            color: white;
            padding: 25px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        .header-logo {
            max-height: 50px;
            background: white;
            padding: 4px 8px;
            border-radius: 6px;
        }
        /* Cards Modernos com Sombra Suave */
        .card-custom {
            background: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
            transition: transform 0.2s ease;
        }
        .card-title-custom {
            color: #0f766e;
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #f0fdf4;
            padding-bottom: 10px;
        }
        /* Inputs e Botões */
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #d1d5db;
        }
        .form-control:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
        }
        .btn-enviar {
            background-color: #0f766e;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            border: none;
            transition: background 0.2s;
        }
        .btn-enviar:hover {
            background-color: #115e59;
            color: white;
        }
        /* Tabela Moderna */
        .table-custom {
            vertical-align: middle;
            font-size: 0.95rem;
        }
        .table-custom th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            border-top: none;
            padding: 12px;
        }
        .table-custom td {
            padding: 14px 12px;
            color: #334155;
        }
        /* Badges de Status */
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .badge-pendente { background-color: #fef3c7; color: #d97706; }
        .badge-agendado { background-color: #d1fae5; color: #065f46; }
        .badge-recusado { background-color: #fee2e2; color: #991b1b; }
        
        .btn-sair {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 6px 15px;
            font-size: 0.9rem;
            transition: background 0.2s;
            text-decoration: none;
        }
        .btn-sair:hover {
            background: rgba(255, 255, 255, 0.25);
            color: white;
        }
    </style>
</head>
<body>

<!-- TOPO / HEADER -->
<div class="header-portal">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <!-- Logo e Nome da Empresa -->
        <div class="d-flex align-items-center gap-3">
            @if(isset($config) && $config->logo)
                <img src="/logos/{{ $config->logo }}" alt="Logo" class="header-logo">
            @else
                <i class="fas fa-recycle fa-2x"></i>
            @endif
            <div>
                <h4 class="mb-0 fw-bold" style="font-size: 1.1rem;">{{ $config->nome_fantasia ?? $config->razao_social ?? 'Portal de Coletas' }}</h4>
                <small style="opacity: 0.85;">Portal Exclusivo de Solicitações</small>
            </div>
        </div>

        <!-- Identificação do Cliente e Botão Sair -->
        <div class="d-flex align-items-center gap-3">
            <div class="text-end">
                <span style="font-size: 0.85rem; opacity: 0.8; display: block;">Conectado como:</span>
                <strong style="font-size: 0.95rem;">{{ Session::get('cliente_nome') }}</strong>
            </div>
            <a href="/portal-cliente/sair" class="btn-sair">
                <i class="fas fa-sign-out-alt me-1"></i> Sair
            </a>
        </div>
    </div>
</div>

<!-- CONTEÚDO PRINCIPAL -->
<div class="container mb-5">

    @if(session('sucesso'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('sucesso') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        
        <!-- COLUNA DA ESQUERDA: FORMULÁRIO DE SOLICITAÇÃO -->
        <div class="col-lg-5">
            <div class="card-custom">
                <h5 class="card-title-custom">
                    <i class="fas fa-plus-circle"></i> Solicitar Nova Coleta
                </h5>

                <form action="/portal-cliente/coleta/salvar" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary" style="font-size: 0.9rem;">Material a ser coletado *</label>
                        <input type="text" name="material" class="form-control" required placeholder="Ex: Sucata de ferro, Papelão, Cobre...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary" style="font-size: 0.9rem;">Dias/Horários disponíveis *</label>
                        <input type="text" name="dias_disponiveis" class="form-control" required placeholder="Ex: Seg a Sex, das 08h às 17h">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary" style="font-size: 0.9rem;">Observações / Instruções</label>
                        <textarea name="observacao" class="form-control" rows="3" placeholder="Detalhes de acesso, volume aproximado..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary" style="font-size: 0.9rem;">Fotos do Material</label>
                        <input type="file" name="fotos[]" class="form-control" multiple accept="image/*">
                        <div class="form-text text-muted mt-1" style="font-size: 0.8rem;">Você pode selecionar uma ou mais fotos.</div>
                    </div>
					
                  	<div class="mb-3">
                        <label class="form-label fw-bold text-secondary" style="font-size: 0.9rem;">Como será a entrega/coleta? *</label>
                        <select name="tipo_transporte" id="tipo_transporte" class="form-select" required onchange="togglePortaria(this.value)">
                            <option value="">Selecione a modalidade...</option>
                            <option value="frota">A empresa vai buscar (Nossa Frota)</option>
                            <option value="cliente">Vou levar até a empresa (Portaria)</option>
                        </select>
                    </div>

                    <!-- CAIXA MÁGICA: Aparece apenas se escolher Portaria -->
                    <div id="box_portaria" style="display: none; background: #e0f2fe; padding: 15px; border-radius: 8px; border: 1px dashed #7dd3fc; margin-bottom: 20px;">
                        <h6 class="text-primary fw-bold mb-3"><i class="fas fa-building"></i> Detalhes da sua Chegada na Portaria</h6>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary" style="font-size: 0.85rem;">Data e Hora exata da chegada *</label>
                            <input type="datetime-local" name="data_hora_prevista" id="data_hora_prevista" class="form-control">
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold text-secondary" style="font-size: 0.85rem;">Quem você vai procurar? *</label>
                            <select name="funcionario_id" id="funcionario_id" class="form-select">
                                <option value="">Selecione o funcionário...</option>
                                @if(isset($funcionarios))
                                    @foreach($funcionarios as $f)
                                        <option value="{{ $f->id }}">{{ $f->nome }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <!-- Script para mostrar/esconder a caixa -->
                    <script>
                        function togglePortaria(valor) {
                            var box = document.getElementById('box_portaria');
                            var dataField = document.getElementById('data_hora_prevista');
                            var funcField = document.getElementById('funcionario_id');

                            if(valor === 'cliente') {
                                box.style.display = 'block';
                                dataField.required = true;
                                funcField.required = true;
                            } else {
                                box.style.display = 'none';
                                dataField.required = false;
                                funcField.required = false;
                                dataField.value = '';
                                funcField.value = '';
                            }
                        }
                    </script>
                  
                    <button type="submit" class="btn-enviar">
                        <i class="fas fa-paper-plane me-2"></i> Enviar Solicitação
                    </button>
                </form>
            </div>
        </div>

        <!-- COLUNA DA DIREITA: HISTÓRICO DE SOLICITAÇÕES -->
        <div class="col-lg-7">
            <div class="card-custom">
                <h5 class="card-title-custom">
                    <i class="fas fa-history"></i> Meu Histórico de Solicitações
                </h5>

                <div class="table-responsive">
                    <table class="table table-custom table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Material</th>
                                <th>Data Agendada</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($coletas as $c)
                                <tr>
                                    <td>{{ date('d/m/Y H:i', strtotime($c->created_at)) }}</td>
                                    <td class="fw-bold text-dark">{{ $c->material }}</td>
                                    <td>
                                        @if($c->data_agendada)
                                            {{ date('d/m/Y H:i', strtotime($c->data_agendada)) }}
                                        @else
                                            <span class="text-muted fst-italic" style="font-size: 0.85rem;">Aguardando...</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($c->status == 'pendente')
                                            <span class="badge-status badge-pendente">Em Análise</span>
                                        @elseif($c->status == 'agendado')
                                            <span class="badge-status badge-agendado">Agendado</span>
                                        @elseif($c->status == 'recusado')
                                            <span class="badge-status badge-recusado">Recusado</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($c->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-box-open fa-2x mb-2 text-black-50"></i>
                                        <p class="mb-0">Nenhuma solicitação encontrada.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>