{{-- Carregado somente no layout autenticado quando APP_UI_THEME=refined.
     Não depende de PAG_LOGIN=access_4 e nunca altera os estilos do login. --}}
@php
    // Mesmos arquivos tipográficos do access_4. Poppins é o fallback seguro.
    $uiFontFiles = [
        400 => 'login2/fonts/poppins/Poppins-Regular.ttf',
        600 => 'login2/fonts/poppins/Poppins-SemiBold.ttf',
        700 => 'login2/fonts/poppins/Poppins-Bold.ttf',
    ];

    if (class_exists(\App\Services\LoginPresentationService::class)) {
        try {
            $uiPresentationService = app(\App\Services\LoginPresentationService::class);
            $uiPublishedModel = $uiPresentationService->currentModel();
            $uiSelectedFont = $uiPublishedModel
                && $uiPresentationService->validFont((string) $uiPublishedModel->font)
                    ? (string) $uiPublishedModel->font
                    : 'poppins';
            $uiFontFiles = $uiPresentationService->fontFiles($uiSelectedFont);
        } catch (\Throwable $e) {
            // Instalação sem as tabelas opcionais ou banco indisponível:
            // o novo tema continua funcional com as fontes locais.
        }
    }
@endphp
<style>
    @foreach([400, 600, 700] as $weight)
    @font-face {
        font-family: 'RefinedTypeface';
        src: url('{{ asset($uiFontFiles[$weight]) }}') format('truetype');
        font-weight: {{ $weight }};
        font-style: normal;
        font-display: swap;
    }
    @endforeach
</style>
<link rel="stylesheet" href="{{ asset('css/theme-refined.css') }}?v={{ filemtime(public_path('css/theme-refined.css')) }}">
