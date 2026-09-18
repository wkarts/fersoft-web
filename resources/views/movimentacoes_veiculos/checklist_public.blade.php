<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist de Pré-Viagem - Fersoft ERP</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #333;
            padding-bottom: 40px;
        }
        .main-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 0 0 16px 16px;
            padding: 20px 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            margin-bottom: 15px;
            background: white;
        }
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #2c3e50;
            border-left: 4px solid #007bff;
            padding-left: 8px;
            margin-bottom: 15px;
        }
        .item-box {
            background: #fafbfc;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
            border: 1px solid #e1e4e8;
        }
        .item-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: #24292e;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        .item-title i {
            margin-right: 6px;
            color: #007bff;
        }
        /* Estilo limpo para os radio buttons virarem botões de toque */
        .options-container {
            display: flex;
            gap: 10px;
        }
        .radio-option {
            flex: 1;
            text-align: center;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            background: white;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .radio-option input {
            display: none;
        }
        .radio-option.active-ok {
            border-color: #28a745;
            background: #d4edda;
            color: #155724;
        }
        .radio-option.active-bad {
            border-color: #dc3545;
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

<div class="main-header text-center">
    <h4 class="mb-1 font-weight-bold"><i class="fas fa-clipboard-check"></i> Check-list Veicular</h4>
    <p class="mb-0 small opacity-9">Placa: <span class="badge badge-light text-primary px-2 py-1 font-weight-bold" style="font-size: 0.9rem;">{{ $movimentacao->veiculo->placa ?? 'N/A' }}</span></p>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 px-2">

            @if(session('erro'))
                <div class="alert alert-danger shadow-sm small">{{ session('erro') }}</div>
            @endif

            @if($jaRespondido)
                <div class="card card-custom text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h5 class="font-weight-bold text-success">Checklist já enviado!</h5>
                        <p class="text-muted small mb-0">Este veículo já possui inspeção registrada para esta rota.</p>
                    </div>
                </div>
            @else
                <form action="{{ url('/checklist/veiculo/' . $movimentacao->id . '/salvar') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- CARTÃO 1: SEGURANÇA -->
                    <div class="card card-custom p-3">
                        <div class="section-title">1. Condições de Segurança</div>

                        <!-- Item 1 -->
                        <div class="item-box">
                            <div class="item-title"><i class="fas fa-circle-notch"></i> Pneus e Rodas (Calibragem/Estado)</div>
                            <div class="options-container">
                                <label class="radio-option active-ok" onclick="selectOpt(this, 'ok')">
                                    <input type="radio" name="pneus" value="ok" checked> OK
                                </label>
                                <label class="radio-option" onclick="selectOpt(this, 'bad')">
                                    <input type="radio" name="pneus" value="problema"> Com Avaria
                                </label>
                            </div>
                        </div>

                        <!-- Item 2 -->
                        <div class="item-box">
                            <div class="item-title"><i class="fas fa-oil-can"></i> Óleo, Água e Arla</div>
                            <div class="options-container">
                                <label class="radio-option active-ok" onclick="selectOpt(this, 'ok')">
                                    <input type="radio" name="oleo_agua" value="ok" checked> OK
                                </label>
                                <label class="radio-option" onclick="selectOpt(this, 'bad')">
                                    <input type="radio" name="oleo_agua" value="problema"> Baixo / Vazando
                                </label>
                            </div>
                        </div>

                        <!-- Item 3 -->
                        <div class="item-box">
                            <div class="item-title"><i class="fas fa-car-crash"></i> Freios e Estacionamento</div>
                            <div class="options-container">
                                <label class="radio-option active-ok" onclick="selectOpt(this, 'ok')">
                                    <input type="radio" name="freios" value="ok" checked> OK
                                </label>
                                <label class="radio-option" onclick="selectOpt(this, 'bad')">
                                    <input type="radio" name="freios" value="problema"> Com Falha
                                </label>
                            </div>
                        </div>

                        <!-- Item 4 -->
                        <div class="item-box">
                            <div class="item-title"><i class="fas fa-lightbulb"></i> Faróis, Lanternas e Piscas</div>
                            <div class="options-container">
                                <label class="radio-option active-ok" onclick="selectOpt(this, 'ok')">
                                    <input type="radio" name="farois_lanternas" value="ok" checked> OK
                                </label>
                                <label class="radio-option" onclick="selectOpt(this, 'bad')">
                                    <input type="radio" name="farois_lanternas" value="problema"> Queimado
                                </label>
                            </div>
                        </div>

                        <!-- Item 5 -->
                        <div class="item-box mb-0">
                            <div class="item-title"><i class="fas fa-file-alt"></i> Documentação do Veículo</div>
                            <div class="options-container">
                                <label class="radio-option active-ok" onclick="selectOpt(this, 'ok')">
                                    <input type="radio" name="documento" value="ok" checked> Presente
                                </label>
                                <label class="radio-option" onclick="selectOpt(this, 'bad')">
                                    <input type="radio" name="documento" value="problema"> Ausente
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- CARTÃO 2: FOTOS -->
                    <div class="card card-custom p-3">
                        <div class="section-title">2. Evidências Fotográficas</div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark small mb-1"><i class="fas fa-camera text-primary mr-1"></i> Foto da Frente (Placa Visível)</label>
                            <input type="file" class="form-control-file border p-2 rounded bg-light small" name="fotos[frente]" accept="image/*" capture="environment" required>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold text-dark small mb-1"><i class="fas fa-tachometer-alt text-primary mr-1"></i> Foto do Painel (KM e Combustível)</label>
                            <input type="file" class="form-control-file border p-2 rounded bg-light small" name="fotos[painel]" accept="image/*" capture="environment" required>
                        </div>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold text-dark small mb-1"><i class="fas fa-comment-alt text-primary mr-1"></i> Observações / Avarias Anteriores</label>
                            <textarea class="form-control bg-light" name="observacoes" rows="2" placeholder="Descreva se o veículo já possui algum arranhão..."></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg shadow font-weight-bold py-3 mb-4">
                        <i class="fas fa-paper-plane mr-2"></i> Enviar Checklist e Liberar Rota
                    </button>
                </form>
            @endif

        </div>
    </div>
</div>

<script>
    function selectOpt(label, type) {
        let container = label.parentElement;
        container.querySelectorAll('.radio-option').forEach(opt => {
            opt.classList.remove('active-ok', 'active-bad');
        });
        if(type === 'ok') {
            label.classList.add('active-ok');
        } else {
            label.classList.add('active-bad');
        }
        label.querySelector('input').checked = true;
    }
</script>
</body>
</html>
