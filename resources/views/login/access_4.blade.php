<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $presentation['background'] }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acesso - {{ config('app.name') }}</title>
    @include('layouts.installable')
    <link rel="icon" type="image/png" href="{{ $presentation['assets']['favicon'] }}">
    <link rel="preload" href="{{ asset($presentation['font_files'][400]) }}" as="font" type="font/ttf" crossorigin>
    <style>
        @foreach([400, 600, 700] as $weight)
        @font-face {
            font-family: 'LoginTypeface';
            src: url('{{ asset($presentation['font_files'][$weight]) }}') format('truetype');
            font-weight: {{ $weight }};
            font-style: normal;
            font-display: block;
        }
        @endforeach
    </style>
    <link rel="stylesheet" href="{{ asset('css/login-access4.css') }}?v={{ filemtime(public_path('css/login-access4.css')) }}">
</head>
<body class="login-access4 login-layout-{{ $presentation['layout'] }}"
      style="--login-accent: {{ $presentation['accent'] }}; --login-background: {{ $presentation['background'] }};">
    @if(!empty($previewMode))
        <div class="login-preview-banner" role="status">Pré-visualização — autenticação desabilitada</div>
    @endif

    <div class="login-shell" id="login-access4">
        <aside class="login-public" aria-label="Apresentação institucional">
            @include('login.components.access4.identity')
            @include('login.components.access4.hero')
            @include('login.components.access4.contents')
        </aside>

        <main class="login-auth" aria-label="Acessar a aplicação">
            <div class="login-form-container">
                @include('login.components.access4.form')
            </div>
        </main>
    </div>

    @if(empty($previewMode))
        {{-- O OTP utiliza o fluxo histórico de verificação, com diálogo nativo isolado.
             Não depende dos estilos/plug-ins Bootstrap do ERP e não altera a autenticação. --}}
        @include('login.components.access4.otp')

        @if($presentation['show_support'])
            @include('layouts.support')
        @endif
    @endif

    <script src="{{ asset('js/login-access4.js') }}?v={{ filemtime(public_path('js/login-access4.js')) }}" defer></script>
</body>
</html>
