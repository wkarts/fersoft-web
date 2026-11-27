<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Apontamento de Coleta - Motorista</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            padding-bottom: 30px;
        }
        .header-app {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
            color: white;
            padding: 20px 15px;
            text-align: center;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
            margin-bottom: 20px;
        }
        .card-box {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .info-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px 16px;
            border-left: 4px solid #0f766e;
            margin-bottom: 18px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.95rem;
            border: 1px solid #cbd5e1;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.15);
        }
        .btn-salvar-mais {
            background: #ffffff;
            color: #0f766e;
            border: 2px solid #0f766e;
            padding: 14px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 12px;
            width: 100%;
            transition: all 0.2s ease;
        }
        .btn-salvar-mais:hover {
            background: #0f766e;
            color: #ffffff;
        }
        .btn-concluir {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            color: white;
            border: none;
            padding: 14px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 12px;
            width: 100%;
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.25);
        }
        .btn-concluir:hover {
            color: white;
            opacity: 0.95;
        }
        .table-itens {
            font-size: 0.9rem;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/ui-standalone-forms.css') }}?v={{ filemtime(public_path('css/ui-standalone-forms.css')) }}">
</head>
<body class="fer-standalone-form">

@php
    $itensLancados = \App\Models\MovimentacaoDestinoItem::with('produto')
        ->where('movimentacao_destino_id', $destino->id)
        ->get();

    $nomeParada = $destino->cliente->razao_social
        ?? ($destino->cliente->nome
        ?? ($destino->fornecedor->razao_social
        ?? ($destino->fornecedor->nome ?? 'Cliente/Fornecedor da Rota')));
@endphp

    <!-- CABEÇALHO MOBILE -->
<div class="header-app">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="badge bg-white text-dark"><i class="fas fa-truck me-1"></i> {{ $movimentacao->veiculo->placa ?? 'Sem Placa' }}</span>
        <span class="badge bg-warning text-dark">Viagem #{{ $movimentacao->id }}</span>
    </div>
    <h5 class="mb-0 fw-bold mt-2"><i class="fas fa-clipboard-check me-2"></i> Registro de Coleta</h5>
</div>

<div class="container" style="max-width: 580px;">

    <!-- MENSAGENS DE SUCESSO E ERRO -->
    @if(session('sucesso'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('sucesso') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('mensagem_erro') || session('erro'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('mensagem_erro') ?? session('erro') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- IDENTIFICAÇÃO DO LOCAL DA PARADA -->
    <div class="card-box mb-3">
        <div class="info-card mb-0">
            <div class="fw-bold text-dark" style="font-size: 1.05rem;">
                <i class="fas fa-building text-secondary me-1"></i> {{ $nomeParada }}
            </div>
            <div class="text-secondary small mt-1">
                <i class="fas fa-map-marker-alt text-danger me-1"></i> {{ $destino->endereco_completo ?? 'Endereço não informado' }}
            </div>

            <!-- INSTRUÇÃO PLANEJADA PARA O MOTORISTA -->
            @php
                $tipoOp = $destino->tipo_operacao_caixa ?? 'nenhuma';
                $nomeOp = [
                    'nenhuma' => 'Apenas Carga Solta (Sem Caçamba)',
                    'colocar' => 'Deixar Caçamba Vazia',
                    'retirar' => 'Recolher Caçamba Cheia',
                    'trocar'  => 'Trocar Caçamba (Deixar vazia e trazer cheia)'
                ][$tipoOp] ?? 'Conforme Planejamento';
            @endphp
            <div class="mt-2 pt-2 border-top">
                <span class="badge bg-primary text-white"><i class="fas fa-tasks me-1"></i> Missão do Local:</span>
                <span class="small fw-bold text-dark ms-1">{{ $nomeOp }}</span>
            </div>
        </div>
    </div>

    <!-- TABELA DE RESÍDUOS JÁ APONTADOS NESTA PARADA (ESTILO ITENS DA NOTA) -->
    @if($itensLancados->count() > 0)
        <div class="card-box mb-3 p-3 border-success" style="background: #f0fdf4; border-color: #bbf7d0 !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-success mb-0">
                    <i class="fas fa-boxes-stacked me-1"></i> Resíduos Coletados ({{ $itensLancados->count() }})
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-itens mb-0 align-middle">
                    <tbody>
                    @foreach($itensLancados as $it)
                        <tr class="border-bottom">
                            <td class="fw-bold text-dark py-2">
                                {{ $it->produto->nome ?? 'Material' }}
                            </td>
                            <td class="text-end fw-bold text-success py-2">
                                {{ number_format($it->quantidade, 2, ',', '.') }} {{ $it->unidade }}
                            </td>
                            <td class="text-end py-2" style="width: 35px;">
                                <a href="{{ url('/coleta-motorista/item/remover/' . $it->id) }}"
                                   onclick="return confirm('Deseja realmente remover este resíduo da lista?')"
                                   class="text-danger p-1">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- FORMULÁRIO DE LANÇAMENTO DO ITEM / CAÇAMBA -->
    <div class="card-box">
        <form action="{{ url('/coleta-motorista/' . $movimentacao->id . '/destino/' . $destino->id) }}" method="POST" enctype="multipart/form-data" id="formColeta">
            @csrf

            <!-- 1. OPERAÇÃO DE CAÇAMBAS -->
            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">1. Operação de Caçamba Realizada</label>
                <select name="tipo_operacao_caixa" class="form-select" onchange="alternarCamposCacamba(this.value)">
                    <option value="nenhuma" {{ ($destino->tipo_operacao_caixa ?? 'nenhuma') == 'nenhuma' ? 'selected' : '' }}>Sem Caçamba / Apenas Carga Solta</option>
                    <option value="colocar" {{ ($destino->tipo_operacao_caixa ?? '') == 'colocar' ? 'selected' : '' }}>Deixar Caçamba no Local</option>
                    <option value="retirar" {{ ($destino->tipo_operacao_caixa ?? '') == 'retirar' ? 'selected' : '' }}>Recolher Caçamba do Local</option>
                    <option value="trocar" {{ ($destino->tipo_operacao_caixa ?? '') == 'trocar' ? 'selected' : '' }}>Trocar Caçamba (Leva Cheia, Deixa Vazia)</option>
                </select>
            </div>

            <!-- SELETORES DE CAÇAMBA (DEIXADA / RETIRADA) -->
            <div class="row g-2 mb-3" id="box_cacambas">
                <div class="col-6" id="box_caixa_deixada">
                    <label class="form-label fw-bold text-success small">Caçamba Deixada</label>
                    <select name="caixa_deixada_id" class="form-select">
                        <option value="">-- Nenhuma --</option>
                        @foreach($caixas as $cx)
                            <option value="{{ $cx->id }}" {{ ($destino->caixa_deixada_id ?? '') == $cx->id ? 'selected' : '' }}>
                                {{ $cx->referencia ? $cx->referencia . ' - ' : '' }}{{ $cx->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6" id="box_caixa_retirada">
                    <label class="form-label fw-bold text-danger small">Caçamba Retirada</label>
                    <select name="caixa_retirada_id" class="form-select">
                        <option value="">-- Nenhuma --</option>
                        @foreach($caixas as $cx)
                            <option value="{{ $cx->id }}" {{ ($destino->caixa_retirada_id ?? '') == $cx->id ? 'selected' : '' }}>
                                {{ $cx->referencia ? $cx->referencia . ' - ' : '' }}{{ $cx->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="text-muted my-3">

            <!-- 2. RESÍDUO COLETADO E PESO -->
            <h6 class="fw-bold text-dark mb-2" style="font-size: 0.95rem;">
                <i class="fas fa-dolly text-primary me-1"></i> Adicionar Material Coletado
            </h6>

            <div class="mb-3">
                <label class="form-label fw-bold text-secondary small">Qual resíduo/material foi coletado?</label>
                <select name="produto_residuo_id" id="produto_residuo_id" class="form-select">
                    <option value="">-- Selecione o resíduo --</option>
                    @foreach($produtos as $prod)
                        <option value="{{ $prod->id }}">{{ $prod->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-7">
                    <label class="form-label fw-bold text-secondary small">Peso / Volume Lido</label>
                    <input type="number" step="0.001" name="quantidade_residuo" id="quantidade_residuo" class="form-control" placeholder="Ex: 1500.00">
                </div>
                <div class="col-5">
                    <label class="form-label fw-bold text-secondary small">Unidade</label>
                    <select name="unidade_residuo" class="form-select">
                        <option value="KG">Quilos (KG)</option>
                        <option value="TON">Toneladas (TON)</option>
                        <option value="M3">Metros Cúbicos (M³)</option>
                    </select>
                </div>
            </div>

            <!-- 3. FOTO DO TICKET DE BALANÇA -->
            <div class="mb-4">
                <label class="form-label fw-bold text-secondary small">Foto do Ticket de Balança / Comprovante</label>
                <input type="file" name="foto_ticket_balanca" class="form-control" accept="image/*" capture="environment">
                @if(!empty($destino->foto_ticket_balanca))
                    <div class="mt-2 small text-success">
                        <i class="fas fa-image me-1"></i> Comprovante já enviado para este local.
                    </div>
                @else
                    <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">
                        <i class="fas fa-camera me-1"></i> Aponte a câmera para o ticket de pesagem.
                    </small>
                @endif
            </div>

            <!-- BOTÕES DUPLOS DE FLUXO -->
            <div class="row g-2">
                <div class="col-12 mb-1">
                    <button type="submit" name="acao" value="adicionar_mais" class="btn-salvar-mais">
                        <i class="fas fa-plus-circle me-1"></i> Salvar e Adicionar Mais Resíduo
                    </button>
                </div>
                <div class="col-12">
                    <button type="submit" name="acao" value="concluir_local" class="btn-concluir">
                        <i class="fas fa-check-double me-1"></i> Salvar e Concluir Este Local
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function alternarCamposCacamba(tipo) {
        const boxDeixada = document.getElementById('box_caixa_deixada');
        const boxRetirada = document.getElementById('box_caixa_retirada');

        if (tipo === 'nenhuma') {
            boxDeixada.style.display = 'none';
            boxRetirada.style.display = 'none';
        } else if (tipo === 'colocar') {
            boxDeixada.style.display = 'block';
            boxRetirada.style.display = 'none';
        } else if (tipo === 'retirar') {
            boxDeixada.style.display = 'none';
            boxRetirada.style.display = 'block';
        } else {
            // trocar ou remanejar
            boxDeixada.style.display = 'block';
            boxRetirada.style.display = 'block';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const selectOperacao = document.querySelector('select[name="tipo_operacao_caixa"]');
        if (selectOperacao) {
            alternarCamposCacamba(selectOperacao.value);
        }

        // Validação inteligente ao clicar nos botões
        const form = document.getElementById('formColeta');
        const btnSalvarMais = document.querySelector('button[value="adicionar_mais"]');
        const inputProd = document.getElementById('produto_residuo_id');
        const inputQtd = document.getElementById('quantidade_residuo');

        btnSalvarMais.addEventListener('click', function(e) {
            if (!inputProd.value || !inputQtd.value) {
                e.preventDefault();
                alert('Para adicionar mais resíduos, selecione o material e informe a quantidade/peso.');
                inputProd.focus();
            }
        });
    });
</script>
</body>
</html>
