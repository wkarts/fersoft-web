@php
    $whatsapp = preg_replace('/\D+/', '', (string) config('app.dev_whatsapp'));
    $whatsappDisplay = trim((string) config('app.dev_whatsapp_display'));

    if ($whatsappDisplay === '') {
        $whatsappDisplay = $whatsapp;
    }

    $whatsappMessage = rawurlencode(
        'Olá, estou recebendo a mensagem de acesso não permitido e preciso solicitar a liberação.'
    );
@endphp

@if(config('ui_theme.active', false))
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso não permitido</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700">
    <link rel="stylesheet" href="{{ asset('css/ui-auth.css') }}?v={{ filemtime(public_path('css/ui-auth.css')) }}">
</head>
<body class="fer-auth fer-auth--error">
<main class="flex-root"><div class="fer-error-card">
@endif
<h1>
    Acesso não permitido, contate o desenvolvedor para liberação!

    @if ($whatsapp !== '')
        <a
            href="https://wa.me/{{ $whatsapp }}?text={{ $whatsappMessage }}"
            target="_blank"
            rel="noopener noreferrer"
        >
            WhatsApp: {{ $whatsappDisplay }}
        </a>
    @endif
</h1>
@if(config('ui_theme.active', false))
</div></main>
</body>
</html>
@endif
