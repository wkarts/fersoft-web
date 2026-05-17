@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif
        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>App autenticador do autorizador</h3>
                <p class="text-muted mb-0">Permite que o usuário autorizador libere operações usando códigos temporários gerados no próprio dispositivo.</p>
            </div>
            <a href="/seguranca/autorizadores" class="btn btn-light">Voltar aos autorizadores</a>
        </div>

        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="card bg-light h-100">
                    <div class="card-body">
                        <h5 class="mb-3">Autorizador</h5>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th>Usuário</th><td>{{ optional($authorizer->usuario)->nome }}</td></tr>
                            <tr><th>Login</th><td>{{ optional($authorizer->usuario)->login ?: optional($authorizer->usuario)->email }}</td></tr>
                            <tr><th>Empresa</th><td>{{ optional($authorizer->empresa)->nome ?: 'Global' }}</td></tr>
                            <tr><th>Status</th><td>{{ $authorizer->enabled ? 'Ativo' : 'Inativo' }}</td></tr>
                            <tr>
                                <th>App autenticador</th>
                                <td>
                                    @if($authorizer->hasOperationOtp())
                                        <span class="badge badge-success">Confirmado</span>
                                    @elseif($authorizer->operation_otp_secret)
                                        <span class="badge badge-warning">Pendente de confirmação</span>
                                    @else
                                        <span class="badge badge-light">Não configurado</span>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <div class="alert alert-info mt-4 mb-0">
                            Este OTP é específico para <strong>liberação de operações</strong>. Ele não substitui os tokens operacionais já existentes e não remove os tokens temporários de uso único gerados após cada autorização.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="mb-3">Configurar no celular</h5>
                        <p class="text-muted">Escaneie o QR Code em um aplicativo compatível: Google Authenticator, Microsoft Authenticator, Authy, 1Password, Bitwarden ou similar.</p>

                        <div class="mb-3">
                            <img src="{{ $setup['qr_code'] }}" alt="QR Code OTP" style="max-width: 240px; width: 100%; height: auto;">
                        </div>

                        <div class="alert alert-secondary text-left">
                            <strong>Chave manual:</strong><br>
                            <code style="font-size: 16px; word-break: break-all;">{{ $setup['secret'] }}</code><br>
                            <small>Use esta chave apenas se não conseguir escanear o QR Code.</small>
                        </div>

                        <form method="post" action="/seguranca/autorizadores/{{ $authorizer->id }}/otp/confirmar" class="mt-4">
                            @csrf
                            <div class="row justify-content-center">
                                <div class="col-md-5">
                                    <label>Código de 6 dígitos</label>
                                    <input type="text" name="code" class="form-control text-center" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="123456" required>
                                </div>
                            </div>
                            <button class="btn btn-primary mt-3">Confirmar app autenticador</button>
                        </form>

                        <div class="d-flex justify-content-center mt-4">
                            <a href="/seguranca/autorizadores/{{ $authorizer->id }}/otp?regenerate=1" class="btn btn-warning mr-2" onclick="return confirm('Gerar uma nova chave? A chave anterior deixará de funcionar após a nova confirmação.')">Gerar nova chave</a>
                            @if($authorizer->operation_otp_secret)
                                <form method="post" action="/seguranca/autorizadores/{{ $authorizer->id }}/otp/desativar" onsubmit="return confirm('Desativar o app autenticador deste autorizador?')">
                                    @csrf
                                    <button class="btn btn-danger">Desativar</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-warning">
            <strong>Importante:</strong> para exigir este código nas liberações, configure a proteção do recurso como <strong>Google Authenticator do autorizador</strong> ou <strong>Token ou Google Authenticator</strong> em Segurança &gt; Proteções de Operação.
        </div>
    </div>
</div>
@endsection
