{{-- Paleta do PDV isolada do catálogo e das preferências da interface do ERP. --}}
@php
    $uiPosTheme = $uiPosTheme ?? app(\App\Services\UiPosThemeService::class)->resolve();
    $uiPosTokens = \App\Support\UiThemePalette::tokens($uiPosTheme);
@endphp
<style>
    @font-face {
        font-family: 'Poppins';
        src: url('{{ asset('login2/fonts/poppins/Poppins-Regular.ttf') }}') format('truetype');
        font-weight: 400; font-style: normal; font-display: swap;
    }
    @font-face {
        font-family: 'Poppins';
        src: url('{{ asset('login2/fonts/poppins/Poppins-Medium.ttf') }}') format('truetype');
        font-weight: 500; font-style: normal; font-display: swap;
    }
    @font-face {
        font-family: 'Poppins';
        src: url('{{ asset('login2/fonts/poppins/Poppins-SemiBold.ttf') }}') format('truetype');
        font-weight: 600; font-style: normal; font-display: swap;
    }
    @font-face {
        font-family: 'Poppins';
        src: url('{{ asset('login2/fonts/poppins/Poppins-Bold.ttf') }}') format('truetype');
        font-weight: 700; font-style: normal; font-display: swap;
    }
    body.ui-pos-refined, body.ui-pos-refined.ui-theme-refined--dark {
        --pos-on-hover: {{ \App\Support\UiThemePalette::foreground($uiPosTheme['accent_hover'] ?? '#15754F') }};
        @foreach($uiPosTokens as $token => $value)
            {{ $token }}: {{ $value }};
        @endforeach
    }
    body.ui-pos-refined.ui-theme-refined--dark { color-scheme: dark; }
</style>
