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
        <div class="login-preview-banner">Pré-visualização restrita — sem autenticação</div>
    @endif
    <main id="login-access4" class="login-shell">
        <section class="login-public" aria-label="Apresentação institucional">
            @include('login.components.access4.identity')
            @include('login.components.access4.hero')
            @include('login.components.access4.contents')
        </section>
        <section class="login-auth" aria-label="Acesso à aplicação">
            <div class="login-form-container">
                @include('login.components.access4.form')
            </div>
        </section>
    </main>
    @if(empty($previewMode))
        {{-- Mantém modal e validação OTP do fluxo histórico, sem nova API de autenticação. --}}
        <script src="{{ asset('metronic/js/plugins.bundle.js') }}"></script>
        <script src="{{ asset('metronic/js/scripts.bundle.js') }}"></script>
        {!! __view_otp_modal() !!}
        @if($presentation['show_support'])
            @include('layouts.support')
        @endif
    @endif
    <script>
        (function () {
            'use strict';

            const recoveryToggle = document.getElementById('login-access4-recovery-toggle');
            const loginForm = document.getElementById('kt_login_signin_form');
            const recoveryForm = document.getElementById('login-access4-recovery');

            if (recoveryToggle && loginForm && recoveryForm) {
                recoveryToggle.addEventListener('click', function () {
                    loginForm.hidden = true;
                    recoveryForm.hidden = false;
                    recoveryForm.querySelector('input[name=email]')?.focus();
                });
                document.getElementById('login-access4-recovery-back')?.addEventListener('click', function () {
                    recoveryForm.hidden = true;
                    loginForm.hidden = false;
                });
            }

            document.querySelectorAll('[data-demo-account]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const login = document.getElementById('login');
                    const password = document.getElementById('senha');
                    if (!login || !password || !loginForm) return;
                    login.value = button.dataset.demoAccount || '';
                    password.value = button.dataset.demoPassword || '';
                    loginForm.requestSubmit();
                });
            });

            const phrases = Array.from(document.querySelectorAll('[data-login-phrase]'));
            const dots = Array.from(document.querySelectorAll('[data-login-dot]'));
            const seconds = {{ (int) $presentation['rotation_seconds'] }};
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            let activeIndex = 0;
            let paused = false;

            document.getElementById('login-access4-phrases')?.addEventListener('mouseenter', () => paused = true);
            document.getElementById('login-access4-phrases')?.addEventListener('mouseleave', () => paused = false);
            document.getElementById('login-access4-phrases')?.addEventListener('focusin', () => paused = true);
            document.getElementById('login-access4-phrases')?.addEventListener('focusout', () => paused = false);

            if (phrases.length > 1 && !prefersReducedMotion) {
                window.setInterval(function () {
                    if (paused || document.hidden) return;
                    phrases[activeIndex].classList.remove('is-visible');
                    dots[activeIndex]?.classList.remove('is-visible');
                    activeIndex = (activeIndex + 1) % phrases.length;
                    phrases[activeIndex].classList.add('is-visible');
                    dots[activeIndex]?.classList.add('is-visible');
                }, seconds * 1000);
            }
        }());
    </script>
    @if(empty($previewMode) && session('show_otp_modal'))
        <script>
            if (window.jQuery) {
                window.jQuery(function () {
                    window.jQuery('#otpModal').modal('show');
                });
            }
        </script>
    @endif
</body>
</html>
