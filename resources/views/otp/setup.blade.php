@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush


@section('content')
    <div class="fer-extra fer-extra--support fer-extra--otp">
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
    </div>
@endsection
