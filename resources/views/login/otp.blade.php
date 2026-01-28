@extends('login.access')

@section('login-form')
    <input type="hidden" name="login" value="{{ $login }}">
    <input type="hidden" name="senha" value="{{ $senha }}">

    <div class="form-group">
        <label for="otp_code">Código de Segurança (OTP)</label>
        <input type="text"
               id="otp_code"
               name="otp_code"
               class="form-control"
               maxlength="6"
               placeholder="000000"
               required>
    </div>

    <button type="submit" class="btn btn-primary btn-block">
        Validar e Entrar
    </button>
@endsection
