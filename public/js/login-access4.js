/* Acesso visual access_4: interacoes independentes de jQuery e Bootstrap.
   Credenciais e OTP continuam no UserController e no OtpController existentes. */
(function () {
    'use strict';

    function ready() {
        var loginForm = document.getElementById('kt_login_signin_form');
        var recoveryForm = document.getElementById('login-access4-recovery');
        var recoveryToggle = document.getElementById('login-access4-recovery-toggle');
        var recoveryBack = document.getElementById('login-access4-recovery-back');

        if (loginForm && recoveryForm && recoveryToggle) {
            recoveryToggle.addEventListener('click', function () {
                loginForm.hidden = true;
                recoveryForm.hidden = false;
                var email = recoveryForm.querySelector('input[name="email"]');
                if (email) email.focus();
            });

            if (recoveryBack) {
                recoveryBack.addEventListener('click', function () {
                    recoveryForm.hidden = true;
                    loginForm.hidden = false;
                    recoveryToggle.focus();
                });
            }
        }

        document.querySelectorAll('[data-demo-account]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!loginForm) return;
                var login = loginForm.querySelector('input[name="login"]');
                var password = loginForm.querySelector('input[name="senha"]');
                if (!login || !password) return;
                login.value = button.dataset.demoAccount || '';
                password.value = button.dataset.demoPassword || '';
                loginForm.requestSubmit();
            });
        });

        var phrases = Array.from(document.querySelectorAll('[data-login-phrase]'));
        var dots = Array.from(document.querySelectorAll('[data-login-dot]'));
        var phraseContainer = document.getElementById('login-access4-phrases');
        var active = 0;
        var paused = false;

        if (phraseContainer) {
            phraseContainer.addEventListener('mouseenter', function () { paused = true; });
            phraseContainer.addEventListener('mouseleave', function () { paused = false; });
            phraseContainer.addEventListener('focusin', function () { paused = true; });
            phraseContainer.addEventListener('focusout', function () { paused = false; });
        }

        if (phrases.length > 1 && phraseContainer && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            var seconds = Math.max(4, Math.min(30, Number(phraseContainer.dataset.rotationSeconds || 8)));
            window.setInterval(function () {
                if (paused || document.hidden) return;
                phrases[active].classList.remove('is-visible');
                if (dots[active]) dots[active].classList.remove('is-visible');
                active = (active + 1) % phrases.length;
                phrases[active].classList.add('is-visible');
                if (dots[active]) dots[active].classList.add('is-visible');
            }, seconds * 1000);
        }

        // Dialog nativo: escondido por padrao, centralizado pelo navegador e
        // exibido APENAS quando a etapa de senha solicitou OTP na sessao.
        var dialog = document.getElementById('otpModal');
        if (!dialog || !loginForm) return;

        var codeInput = document.getElementById('otp-code');
        var errorBox = document.getElementById('otp-error');
        var validate = document.getElementById('btn-otp-validate');
        var close = document.getElementById('otpModalClose');
        var cancel = document.getElementById('btn-otp-cancel');

        function showError(message) {
            if (!errorBox) return;
            errorBox.textContent = String(message || 'Não foi possível validar o código.');
            errorBox.hidden = false;
        }

        function clearError() {
            if (!errorBox) return;
            errorBox.textContent = '';
            errorBox.hidden = true;
        }

        function closeDialog() {
            if (typeof dialog.close === 'function' && dialog.open) dialog.close();
            else dialog.removeAttribute('open');
            if (codeInput) codeInput.value = '';
            clearError();
        }

        if (close) close.addEventListener('click', closeDialog);
        if (cancel) cancel.addEventListener('click', closeDialog);

        if (dialog.dataset.showOtp === 'true') {
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else {
                dialog.setAttribute('open', '');
            }
            if (codeInput) codeInput.focus();
        }

        // Nao fechar o modal ao clicar fora: previne confusao com a etapa OTP.
        if (codeInput) {
            codeInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
                clearError();
            });
            codeInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (validate) validate.click();
                }
            });
        }

        if (validate) validate.addEventListener('click', async function () {
            var code = codeInput ? codeInput.value.trim() : '';
            clearError();

            if (!/^\d{6}$/.test(code)) {
                showError('Informe os 6 dígitos do código de segurança.');
                if (codeInput) codeInput.focus();
                return;
            }

            var login = loginForm.querySelector('input[name="login"]');
            var password = loginForm.querySelector('input[name="senha"]');
            if (!login || !password || !login.value || !password.value) {
                showError('Feche esta janela, informe novamente login e senha e tente entrar.');
                return;
            }

            var token = document.querySelector('meta[name="csrf-token"]');
            if (!token || !dialog.dataset.verifyUrl) {
                showError('A verificação não está disponível. Atualize a página e tente novamente.');
                return;
            }

            validate.disabled = true;
            validate.textContent = 'Verificando...';

            try {
                var requestBody = new URLSearchParams();
                requestBody.set('_token', token.content);
                requestBody.set('otp_code', code);

                var response = await fetch(dialog.dataset.verifyUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: requestBody.toString()
                });

                var payload = {};
                try { payload = await response.json(); } catch (_) {}

                if (response.ok && payload.valid === true) {
                    var hidden = loginForm.querySelector('input[name="otp_code"]');
                    if (!hidden) {
                        hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'otp_code';
                        loginForm.appendChild(hidden);
                    }
                    hidden.value = code;

                    closeDialog();
                    // Submeter novamente ao endpoint historico, que revalida o
                    // codigo e estabelece a sessao como fazia anteriormente.
                    loginForm.requestSubmit();
                } else {
                    var validation = payload.errors && payload.errors.otp_code;
                    var detail = Array.isArray(validation) ? validation[0] : null;
                    showError(detail || payload.error ||
                        (response.status === 419
                            ? 'Sua sessão expirou. Atualize a página e tente novamente.'
                            : 'Código inválido ou não foi possível verificar. Tente novamente.'));
                }
            } catch (_) {
                showError('Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.');
            } finally {
                validate.disabled = false;
                validate.textContent = 'Validar e entrar';
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready, {once: true});
    } else {
        ready();
    }
}());
