@extends('default.layout')

@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-user-form.css') }}?v={{ filemtime(public_path('css/ui-user-form.css')) }}">
@endunless
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-user-form.css') }}?v={{ filemtime(public_path('css/ui-user-form.css')) }}">
@endpush

@section('content')
@php($fotoUsuarioUrl = app(\App\Services\UserAvatarService::class)->url($usuario->img))
<div class="ui-admin ui-admin-usuarios">
    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="ui-admin-panel gutter-b">
            <div class="container">
                <div class="ui-admin-panel-head">
                    <h1 class="card-title">Minha foto</h1>
                </div>
                <form class="ui-user-profile-photo" action="{{ route('usuarios.profile.photo') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <p>{{ $usuario->nome }}</p>
                    <div class="ui-user-profile-photo__actions">
                        <span class="ui-user-profile-photo__preview" aria-hidden="true">
                            @if($fotoUsuarioUrl)
                                <img src="{{ $fotoUsuarioUrl }}" alt="">
                            @else
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($usuario->nome, 0, 1)) }}
                            @endif
                        </span>
                        <div>
                            <label for="foto-perfil">Selecione sua foto</label>
                            <input id="foto-perfil" type="file" name="foto" accept="image/jpeg,image/png,image/webp"
                                   required class="form-control @error('foto') is-invalid @enderror">
                            @error('foto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <small class="text-muted">JPG, PNG ou WebP, até 2 MB.</small>
                        </div>
                    </div>
                    <div class="ui-user-profile-photo__buttons">
                        <button class="btn btn-success" type="submit">Salvar foto</button>
                        <a class="btn btn-light" href="/graficos">Voltar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
