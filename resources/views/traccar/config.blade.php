@extends('default.layout')

@section('css')
    <style type="text/css">
        body.loading .modal-loading {
            display: block;
        }

        .modal-loading {
            display: none;
            position: fixed;
            z-index: 10000;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background: rgba(255, 255, 255, 0.8)
            url("/loading.gif") 50% 50% no-repeat;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b example example-compact">
            <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-lg-12">
                    <br>
                    <form method="post" action="{{ route('traccarConfigs.save') }}">
                        @csrf

                        <div class="card card-custom gutter-b example example-compact">
                            <div class="card-header">
                                <h3 class="card-title">Configuração Traccar</h3>
                            </div>
                        </div>

                        <!-- URLs -->
                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <label>Base URL</label>
                                <input required type="url" name="base_url" value="{{ $data->base_url ?? old('base_url') }}" class="form-control" placeholder="https://api.traccar.com">
                            </div>
                            <div class="col-lg-6">
                                <label>Socket URL</label>
                                <input type="url" name="socket_url" value="{{ $data->socket_url ?? old('socket_url') }}" class="form-control" placeholder="wss://api.traccar.com/socket">
                            </div>
                        </div>

                        <!-- Usuário e Senha -->
                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <label>Usuário de Login</label>
                                <input required type="text" name="mail_user_name" value="{{ $data->mail_user_name ?? old('mail_user_name') }}" class="form-control" placeholder="Usuário da API">
                            </div>
                            <div class="col-lg-6">
                                <label>Senha</label>
                                <input type="password" name="password" class="form-control" placeholder="Deixe em branco para manter a senha atual" value="">
                                <small class="form-text text-muted">A senha atual está protegida. Preencha este campo apenas para alterar.</small>
                            </div>
                        </div>

                        <!-- Tokens -->
                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <label>Token Traccar</label>
                                <input type="text" name="token_traccar" value="{{ $data->token_traccar ?? old('token_traccar') }}" class="form-control" placeholder="Token da API Traccar">
                            </div>
                            <div class="col-lg-6">
                                <label>Token Google Maps</label>
                                <input type="text" name="token_google_maps" value="{{ $data->token_google_maps ?? old('token_google_maps') }}" class="form-control" placeholder="Token do Google Maps">
                            </div>
                        </div>

                        <!-- Mapa Padrão -->
                        <div class="row mb-4">
                            <div class="col-lg-6">
                                <label>Mapa Padrão</label>
                                <input type="text" name="default_map" value="{{ $data->default_map ?? old('default_map') }}" class="form-control" placeholder="Mapa padrão">
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-xl-2"></div>

                                <div class="col-lg-3 col-sm-6 col-md-4">
                                    <a style="width: 100%" class="btn btn-danger" href="{{ route('traccarConfigs.register') }}">
                                        <i class="la la-close"></i>
                                        <span>Cancelar</span>
                                    </a>
                                </div>

                                <div class="col-lg-3 col-sm-6 col-md-4">
                                    <button style="width: 100%" type="submit" class="btn btn-success">
                                        <i class="la la-check"></i>
                                        <span>Salvar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-loading loading-class"></div>
@endsection

@section('javascript')
    <script type="text/javascript">
        $('.btn-bkp').click(() => {
            $("body").addClass("loading");
        });
    </script>
@endsection
