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
                            <span class="ui-user-profile-photo__title">Selecione sua foto</span>
                            <div class="ui-user-profile-photo__picker">
                                <input id="foto-perfil" type="file" name="foto" accept="image/jpeg,image/png,image/webp"
                                       required class="ui-user-profile-photo__input @error('foto') is-invalid @enderror" aria-describedby="foto-perfil-arquivo foto-perfil-ajuda">
                                <label class="ui-user-profile-photo__pick-button" for="foto-perfil">Selecionar foto</label>
                                <span class="ui-user-profile-photo__filename" id="foto-perfil-arquivo" aria-live="polite">Nenhum arquivo selecionado</span>
                            </div>
                            @error('foto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <small class="text-muted" id="foto-perfil-ajuda">JPG, PNG ou WebP, até 2 MB.</small>
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

@section('javascript')
<script>
    document.querySelectorAll('.ui-user-profile-photo__input').forEach(function (input) {
        input.addEventListener('change', function () {
            const filename = input.closest('.ui-user-profile-photo__picker').querySelector('.ui-user-profile-photo__filename');
            filename.textContent = input.files.length ? input.files[0].name : 'Nenhum arquivo selecionado';
        });
    });
</script>
@endsection
