<div class="login-form-container__eyebrow">Acesso seguro</div>
<h2>{{ config('app.name') }}</h2>
<p class="login-form-container__subtitle">Informe suas credenciais para acessar a plataforma.</p>

@if(session()->has('mensagem_login'))
    <div class="login-flash" role="alert">{{ session('mensagem_login') }}</div>
@endif
@if(session()->has('mensagem_sucesso'))
    <div class="login-flash login-flash--ok" role="status">{{ session('mensagem_sucesso') }}</div>
@endif
@if($errors->any())
    <div class="login-flash" role="alert">{{ $errors->first() }}</div>
@endif

@if(empty($sessaoAtiva))
    <form id="kt_login_signin_form" method="post" action="{{ url('/login/request') }}" autocomplete="on">
        @csrf
        <input type="hidden" name="uri" value="{{ session('uri') }}">
        <div class="login-group">
            <label for="login">Login</label>
            <input id="login" name="login" type="text" required autofocus
                   autocomplete="username"
                   value="{{ old('login', session('login') ?? ($loginCookie ?? '')) }}"
                   @if(!empty($previewMode)) disabled @endif>
        </div>
        <div class="login-group">
            <label for="senha">Senha</label>
            <input id="senha" name="senha" type="password" required
                   autocomplete="current-password"
                   value="{{ old('senha', $senhaCookie ?? '') }}"
                   @if(!empty($previewMode)) disabled @endif>
        </div>
        <div class="login-actions">
            <label>
                <input type="checkbox" name="lembrar" value="1"
                       @if(!empty($lembrarCookie)) checked @endif
                       @if(!empty($previewMode)) disabled @endif>
                Lembrar-me
            </label>
            <button id="login-access4-recovery-toggle" type="button" class="login-links"
                    @if(!empty($previewMode)) disabled @endif>
                Esqueci minha senha
            </button>
        </div>
        <button id="kt_login_signin_submit" class="login-button" type="submit"
                @if(!empty($previewMode)) disabled @endif>Entrar</button>
        @if($presentation['show_signup'])
            <a class="login-secondary" href="{{ url('/cadastro') }}">Cadastrar minha empresa</a>
        @endif
        @if(config('app.env') === 'demo' && empty($previewMode))
            <button class="login-secondary" type="button"
                    data-demo-account="{{ env('DEMO_SUPER_USER', 'super') }}"
                    data-demo-password="{{ env('DEMO_SUPER_PASS', '') }}">
                Acesso demonstrativo — Superusuário
            </button>
            <button class="login-secondary" type="button"
                    data-demo-account="{{ env('DEMO_ADMIN_USER', 'admin') }}"
                    data-demo-password="{{ env('DEMO_ADMIN_PASS', '') }}">
                Acesso demonstrativo — Administrador
            </button>
        @endif
    </form>
    <form id="login-access4-recovery" method="post" action="{{ url('/recuperarSenha') }}" hidden>
        @csrf
        <div class="login-group">
            <label for="login-access4-email">E-mail cadastrado</label>
            <input id="login-access4-email" name="email" type="email" required
                   autocomplete="email" @if(!empty($previewMode)) disabled @endif>
        </div>
        <button class="login-button" type="submit"
                @if(!empty($previewMode)) disabled @endif>Solicitar recuperação</button>
        <button class="login-secondary" id="login-access4-recovery-back" type="button">Voltar ao login</button>
    </form>
@else
    <p>Você já possui uma sessão ativa.</p>
    <a class="login-secondary" href="{{ url('/' . trim((string) env('ROTA_INICIAL', 'graficos'), '/')) }}">
        Ir para o painel
    </a>
@endif

<footer class="login-footer">
    @if($presentation['show_support'] && config('app.email_suporte'))
        <a href="mailto:{{ config('app.email_suporte') }}">Suporte</a>
    @endif
</footer>
