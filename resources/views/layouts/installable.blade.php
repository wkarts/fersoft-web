{{-- Metadados compartilhados; sem interface própria nem branding duplicado. --}}
@if (config('app.pwa_enabled', false))
    <link rel="manifest" href="{{ route('app-install.manifest', [], false) }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ route('app-install.icon', ['size' => 180], false) }}">
@endif
