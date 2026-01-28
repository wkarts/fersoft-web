<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas de Prateleiras</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 10px;
        }
        .etiqueta {
            display: inline-block;
            border: 1px dashed #333;      /* picote */
            padding: 8px;
            margin: 6px;
            text-align: center;
            width: 180px;
            vertical-align: top;
            font-size: 12px;
        }
        .etiqueta .qr-code {
            margin-bottom: 5px;
        }
        .etiqueta p {
            margin: 2px 0;
            font-size: 13px;
        }
        .etiqueta small {
            display: block;
            font-size: 10px;
            color: #555;
        }
        @media print {
            .etiqueta {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
<h3>Etiquetas de Prateleiras</h3>
<hr>
@foreach($prateleiras as $p)
    <div class="etiqueta">
        {{-- QR-Code da identificação --}}
        <div class="qr-code">
            {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(80)->generate($p->identificacao) !!}
        </div>

        {{-- Dados da prateleira --}}
        <p><strong>{{ $p->identificacao }}</strong></p>
        @if($p->descricao)
            <p>{{ Str::limit($p->descricao, 20) }}</p>
        @else
            <p><em>Sem descrição</em></p>
        @endif

        @if($p->posicao)
            <small>Posição: {{ $p->posicao }}</small>
        @endif
        @if($p->localizacao)
            <small>Localização: {{ $p->localizacao }}</small>
        @endif
        @if($p->observacao)
            <small>Obs: {{ Str::limit($p->observacao, 30) }}</small>
        @endif
    </div>
@endforeach
</body>
</html>
