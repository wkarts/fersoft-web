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

{{-- Os tokens são validados no backend; o ERP clássico jamais carrega este bloco. --}}
@php
    $uiResolvedTheme = $uiResolvedTheme
        ?? app(\App\Services\UiThemeService::class)->resolve(($tema ?? 1) == 2);
    $uiCssTokens = [
        '--ui-accent' => $uiResolvedTheme['accent'] ?? '#168F81',
        '--ui-accent-hover' => $uiResolvedTheme['accent_hover'] ?? '#11786D',
        '--ui-bg' => $uiResolvedTheme['background'] ?? '#F5F7FB',
        '--ui-surface' => $uiResolvedTheme['surface'] ?? '#FFFFFF',
        '--ui-aside' => $uiResolvedTheme['sidebar'] ?? '#FFFFFF',
        '--ui-ink' => $uiResolvedTheme['text'] ?? '#1C2A40',
        '--ui-border' => $uiResolvedTheme['border'] ?? '#DEE5EF',
        '--ui-sidebar-ink' => $uiResolvedTheme['sidebar_text'] ?? '#24334E',
    ];
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
