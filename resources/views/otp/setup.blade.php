@extends('default.layout')

@section('content')
    <h3>Autenticação em Duas Etapas</h3>
    <p>Escaneie o QR Code com o aplicativo Google Authenticator:</p>
    <div>
        <img src="{{ $qrCode }}" alt="QRCode">
    </div>
    <p><strong>Chave manual:</strong> {{ $secret }}</p>
    <form method="POST" action="{{ route('otp.verify') }}">
        @csrf
        <label>Código OTP:</label>
        <input type="text" name="code" class="form-control" required>
        <button type="submit" class="btn btn-primary mt-3">Verificar</button>
    </form>
@endsection
