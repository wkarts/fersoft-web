<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Registro de Abastecimento</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- FontAwesome (Ícones) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/ui-standalone-forms.css') }}?v={{ filemtime(public_path('css/ui-standalone-forms.css')) }}">
</head>
<body class="fer-fuel-form fer-standalone-form">

<div class="header-bg">
    <i class="fas fa-gas-pump fa-3x mb-2"></i>
    <h4 class="font-weight-bold">Registro de Abastecimento</h4>
    <p class="mb-0">Placa: <b>{{ $movimentacao->veiculo->placa ?? 'Não informada' }}</b></p>
</div>

<div class="container pb-5">

    @if(session('sucesso'))
        <div class="alert alert-success text-center font-weight-bold p-4 card-custom">
            <i class="fas fa-check-circle fa-3x mb-3"></i><br>
            {{ session('sucesso') }}
        </div>
    @else

        <div class="card card-custom">
            <div class="card-body">
                <form action="/abastecimento-motorista/salvar" method="POST" enctype="multipart/form-data" id="formAbastecimento">
                    @csrf
                    <input type="hidden" name="movimentacao_id" value="{{ $movimentacao->id }}">

                    <!-- TIPO DE COMBUSTÍVEL -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted">Qual combustível?</label>
                        <select name="produto_id" class="form-control" required style="padding: 10px 12px; height: auto;">
                            <option value="">Selecione...</option>
                            @foreach($combustiveis as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- LITROS -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted">Quantidade (Litros)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="quantidade" class="form-control" placeholder="Ex: 100" required>
                            <div class="input-group-append">
                                <span class="input-group-text font-weight-bold">L</span>
                            </div>
                        </div>
                    </div>

                    <!-- VALOR TOTAL -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted">Valor Total Pago</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold bg-success text-white">R$</span>
                            </div>
                            <!-- Substitua aqui pela sua máscara jQuery se preferir -->
                            <input type="text" name="valor_total" class="form-control money" placeholder="Ex: 500,00" required>
                        </div>
                    </div>

                    <!-- KM DO PAINEL -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted">KM Atual do Painel</label>
                        <input type="number" name="km_abastecimento" class="form-control" placeholder="Ex: 125000" required>
                        <small class="text-info mt-1 d-block"><i class="fas fa-info-circle"></i> Olhe o painel do caminhão agora.</small>
                    </div>

                    <!-- FOTO DO CUPOM FISCAL (Direto da câmera) -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted">Foto do Cupom / Bomba</label>
                        <div class="custom-file">
                            <!-- O "capture='environment'" abre a câmera traseira do celular direto -->
                            <input type="file" name="foto_cupom" class="custom-file-input" id="fotoCupom" accept="image/*" capture="environment">
                            <label class="custom-file-label" for="fotoCupom" data-browse="📸 Câmera">Tirar Foto</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-custom mt-4" id="btnSalvar">
                        <i class="fas fa-save mr-2"></i> Salvar Abastecimento
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>

<!-- Scripts básicos para funcionar no celular -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Muda o texto do input file quando o motorista tira a foto
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        if(fileName) {
            $(this).next('.custom-file-label').addClass("selected").html('<i class="fas fa-check text-success"></i> Foto Capturada');
        }
    });

    // Impede clique duplo no botão de salvar
    $('#formAbastecimento').submit(function() {
        $('#btnSalvar').html('<i class="fas fa-spinner fa-spin"></i> Enviando...').attr('disabled', true);
    });
</script>
</body>
</html>
