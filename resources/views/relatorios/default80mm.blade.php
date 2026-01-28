<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Relatório 80mm' }}</title>
    <style>
        @page {
            size: 80mm auto; /* Largura fixa, altura dinâmica */
            margin: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            margin: 5mm;
            line-height: 1.2;
            width: 72mm; /* Define largura padrão de 80mm */
        }

        header {
            text-align: center;
            margin-bottom: 5mm;
        }

        header img {
            max-width: 60px;
            height: auto;
            margin-bottom: 5px;
        }

        header h1, header h4 {
            margin: 2px 0;
            font-size: 12px;
        }

        header small {
            font-size: 8px;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 2px;
            font-size: 9px;
        }

        th {
            border-bottom: 1px dashed #000;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        footer {
            text-align: center;
            margin-top: 10px;
            font-size: 8px;
        }

        footer img {
            max-width: 60px;
            height: auto;
            margin-top: 5px;
        }
    </style>
</head>
<body>
<!-- Cabeçalho -->
<header>
    @php $config = App\Models\ConfigNota::configStatic(); @endphp
    @if($config->logo != "")
        <img src="{{ 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('logos/' . $config->logo))) }}" alt="Logo">
    @else
        <img src="{{ 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('imgs/slym.png'))) }}" alt="Logo">
    @endif
    <h1>{{ $title ?? 'Relatório' }}</h1>
    <small>Emitido em: {{ now()->format('d/m/Y H:i') }}</small>
    <hr class="line">
</header>

<!-- Conteúdo do Relatório -->
<main>
    @yield('content')
</main>

<!-- Rodapé -->
<footer>
    <hr class="line">
    <p>{{ env('SITE_SUPORTE', 'Suporte Técnico: contato@empresa.com') }}</p>
    <p>{{ env('EMAIL_SUPORTE', 'Suporte Técnico') }}</p>
    @if($config->logo != "")
        <img src="{{ 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('logos/' . $config->logo))) }}" alt="Logo">
    @else
        <img src="{{ 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('imgs/slym.png'))) }}" alt="Logo">
    @endif
</footer>
</body>
</html>
