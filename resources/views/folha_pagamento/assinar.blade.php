<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Assinatura de Contracheque</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: sans-serif; }
        .card-recibo { max-width: 480px; margin: 15px auto; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        #canvasAssinatura {
            border: 2px dashed #007bff;
            border-radius: 8px;
            background-color: #fff;
            width: 100%;
            height: 180px;
            touch-action: none;
        }
    </style>
</head>
<body>
<div class="container p-2">
    <div class="card card-recibo bg-white">
        <div class="card-header bg-primary text-white text-center py-3">
            <h6 class="mb-0 font-weight-bold">RECIBO DE PAGAMENTO DE SALÁRIO</h6>
            <small>Competência: {{ $holerite->competencia }}</small>
        </div>
        <div class="card-body">
            <p class="mb-1"><strong>Colaborador:</strong> {{ $funcionario->nome }}</p>
            <p class="mb-1"><strong>CPF:</strong> {{ $holerite->cpf }}</p>
            <p class="mb-3"><strong>Salário Base:</strong> R$ {{ number_format($holerite->salario_base, 2, ',', '.') }}</p>

            <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 13px;">
                <strong>Líquido a Receber:</strong>
                <span class="float-right font-weight-bold text-success" style="font-size: 15px;">
                    R$ {{ number_format($holerite->valor_liquido, 2, ',', '.') }}
                </span>
            </div>

            @if($holerite->assinado)
                <div class="alert alert-success text-center py-3">
                    <strong>✅ Contracheque já assinado!</strong><br>
                    <small>Assinado em {{ date('d/m/Y H:i', strtotime($holerite->data_assinatura)) }}</small>
                </div>
            @else
                <form id="formAssinatura" method="POST" action="{{ route('holerite.assinar.salvar', $holerite->token_assinatura) }}">
                    @csrf
                    <input type="hidden" name="assinatura_base64" id="assinatura_base64">

                    <label class="font-weight-bold text-dark mb-1">Assine com o dedo no quadro abaixo:</label>
                    <canvas id="canvasAssinatura"></canvas>

                    <div class="d-flex justify-content-between mt-2 mb-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limparCanvas()">Limpar Desenho</button>
                        <small class="text-muted align-self-center">Desenhe sua assinatura</small>
                    </div>

                    <button type="button" class="btn btn-success btn-block font-weight-bold py-2" onclick="confirmarAssinatura()">
                        CONFIRMAR ASSINATURA
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    const canvas = document.getElementById('canvasAssinatura');
    let signaturePad = null;

    if (canvas) {
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
        }
        window.onresize = resizeCanvas;
        resizeCanvas();

        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 100)'
        });
    }

    function limparCanvas() {
        if (signaturePad) signaturePad.clear();
    }

    function confirmarAssinatura() {
        if (signaturePad.isEmpty()) {
            alert("Por favor, desenhe sua assinatura no quadro antes de confirmar.");
            return;
        }
        document.getElementById('assinatura_base64').value = signaturePad.toDataURL();
        document.getElementById('formAssinatura').submit();
    }
</script>
</body>
</html>
