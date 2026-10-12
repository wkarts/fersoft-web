{{-- Instalação disponibilizada pelo próprio navegador, com nome e ícone existentes. --}}
<link rel="manifest" href="{{ route('app-install.manifest', [], false) }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="apple-touch-icon" sizes="180x180" href="{{ route('app-install.icon', ['size' => 180], false) }}">
<script>
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(@json(route('app-install.worker', [], false)), {scope: '/'})
                .catch(function () {
                    // O navegador decide a elegibilidade; não exibir mensagens técnicas.
                });
        });
    }
</script>
