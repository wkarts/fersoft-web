{{-- Apenas a interface é nova: validação e autenticação continuam nas rotas históricas. --}}
<dialog id="otpModal"
        class="login-otp-dialog"
        aria-labelledby="otpModalLabel"
        aria-describedby="otpModalDescription"
        data-show-otp="{{ session('show_otp_modal') ? 'true' : 'false' }}"
        data-verify-url="{{ url('/otp/verify-login') }}">
    <div class="login-otp-card">
        <div class="login-otp-top">
            <div class="login-otp-symbol" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="5" y="10" width="14" height="11" rx="2"></rect>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                    <path d="M12 14v3"></path>
                </svg>
            </div>
            <button type="button" class="login-otp-close" id="otpModalClose" aria-label="Fechar verificação">&times;</button>
        </div>
        <h2 id="otpModalLabel">Verificação em duas etapas</h2>
        <p id="otpModalDescription">Digite o código de 6 dígitos do seu aplicativo autenticador para concluir o acesso.</p>

        <label class="login-otp-label" for="otp-code">Código de segurança</label>
        <input class="login-otp-code" id="otp-code" type="text"
               inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
               autocomplete="one-time-code" placeholder="000000"
               aria-describedby="otpModalDescription otp-error">

        <div id="otp-error" class="login-otp-error" role="alert"
             @if(!$errors->has('otp_code')) hidden @endif>{{ $errors->first('otp_code') }}</div>

        <div class="login-otp-actions">
            <button type="button" class="login-button" id="btn-otp-validate">Validar e entrar</button>
            <button type="button" class="login-secondary" id="btn-otp-cancel">Voltar ao login</button>
        </div>
    </div>
</dialog>
