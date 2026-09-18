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