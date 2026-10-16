{{-- Assets exclusivos do tema autenticado. Poppins é o padrão aprovado; a fonte já escolhida em Aparência do Login continua respeitada. --}}
@php
    $uiFontFiles = [
        400 => 'login2/fonts/poppins/Poppins-Regular.ttf',
        500 => 'login2/fonts/poppins/Poppins-Medium.ttf',
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
            $uiFontFiles = array_replace(
                $uiFontFiles,
                $uiPresentationService->fontFiles($uiSelectedFont)
            );
        } catch (\Throwable $e) {
            // Instalação sem as tabelas opcionais ou banco indisponível:
            // o tema continua funcional com os arquivos locais de Poppins.
        }
    }
@endphp
<style>
    @foreach($uiFontFiles as $weight => $fontFile)
    @font-face {
        font-family: 'Poppins';
        src: url('{{ asset($fontFile) }}') format('truetype');
        font-weight: {{ $weight }};
        font-style: normal;
        font-display: swap;
    }
    @endforeach
</style>
<link rel="stylesheet" href="{{ asset('css/theme-refined.css') }}?v={{ filemtime(public_path('css/theme-refined.css')) }}">

{{-- As cores publicadas continuam sujeitas à validação e ao isolamento multiempresa. --}}
@php
    $uiResolvedTheme = $uiResolvedTheme
        ?? app(\App\Services\UiThemeService::class)->resolve(($tema ?? 1) == 2);
    $uiCssTokens = \App\Support\UiThemePalette::tokens($uiResolvedTheme);
@endphp
<link rel="stylesheet" href="{{ asset('css/theme-refined-shell.css') }}?v={{ filemtime(public_path('css/theme-refined-shell.css')) }}">
<style>
    body.ui-theme-refined,
    body.ui-theme-refined.ui-theme-refined--dark {
        @foreach($uiCssTokens as $token => $value)
            {{ $token }}: {{ $value }};
        @endforeach
    }
</style>
<script src="{{ asset('js/theme-refined-workbench.js') }}?v={{ filemtime(public_path('js/theme-refined-workbench.js')) }}" defer></script>
