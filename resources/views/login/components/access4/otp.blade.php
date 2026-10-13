{{-- Apenas a interface é nova: validação e autenticação continuam nas rotas históricas. --}}
<dialog id="otpModal"
        class="login-otp-dialog"
        aria-labelledby="otpModalLabel"
        aria-describedby="otpModalDescription"
        data-show-otp="{{ session('show_otp_modal') ? 'true' : 'false' }}"
        data-verify-url="{{ url('/otp/verify-login') }}">
    <div class="login-otp-card">
        <div class="login-otp-top">
            <div class="login-otp-symbol" aria-hidden="true">✓</div>
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
        <p class="login-otp-note">O código é verificado pelo mecanismo de autenticação já existente.</p>
    </div>
</dialog>
