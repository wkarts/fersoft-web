<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Etiqueta Visitante</title>
    <style>
        /* Configurações para impressora térmica de 80mm */
        @page { margin: 0; size: 80mm 100%; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 10px; width: 75mm; text-align: center; }
        .titulo { font-size: 16px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
        .data { font-size: 12px; margin-bottom: 15px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
        .visitante { font-size: 18px; font-weight: bold; margin: 10px 0; }
        .detalhes { font-size: 14px; text-align: left; margin: 10px 0; line-height: 1.5; }
        .qrcode { margin: 15px auto; width: 120px; height: 120px; }
        .qrcode img { width: 100%; }
        .rodape { font-size: 10px; border-top: 1px dashed #000; padding-top: 10px; margin-top: 10px; }
        
        /* Esconde elementos indesejados na hora de imprimir */
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()"> <!-- O window.print() abre a tela da impressora sozinho -->

    <div class="titulo">VISITANTE</div>
    <div class="data">Entrada: {{ $movimento->data_hora_entrada->format('d/m/Y H:i') }}</div>

    <div class="visitante">{{ $movimento->visitante->nome }}</div>
    
    <div class="detalhes">
        <strong>Doc:</strong> {{ $movimento->visitante->cpf }}<br>
        <strong>Destino:</strong> {{ $movimento->funcionarioVisitado->nome }}<br>
        <strong>Veículo:</strong> {{ $movimento->placa_veiculo ?? 'N/A' }}
    </div>

    <!-- Gera o QR Code dinâmico usando uma API gratuita baseada no ID do movimento -->
    <div class="qrcode">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ url('/portaria/saida/'.$movimento->id) }}" alt="QR Code Baixa">
    </div>
    
    <div class="rodape">
        Apresente este crachá na saída.<br>
        Uso obrigatório nas dependências.
    </div>

</body>
</html>