<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include('layouts.installable')
    {{-- Somente PAG_LOGIN=access_4 habilita identidade publicada nos menus.
         Qualquer login histórico mantém integralmente seus recursos visuais. --}}
    @php
        $appearanceAssets = [];
        if (config('login_presentation.page', 'access') === 'access_4'
            && class_exists(\App\Services\LoginPresentationService::class)) {
            try {
                $appearanceAssets = app(\App\Services\LoginPresentationService::class)->assetUrls();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Identidade opcional indisponível no layout histórico.', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            }
        }
    @endphp
    @php
        $uiResolvedTheme = null;
        $uiThemeColor = $cor ?? '';
        if (config('ui_theme.active', false)) {
            $uiResolvedTheme = app(\App\Services\UiThemeService::class)->resolve(($tema ?? 1) == 2);
            $uiThemeColor = (string) ($uiResolvedTheme['background'] ?? '#F5F7F7');
        }
    @endphp
    <meta charset="utf-8" />

    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="description" content="Updates and statistics">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="{{ $uiThemeColor }}">

    {{-- Bootstrap supplies the layout grid and modal behavior. The application stylesheet
         below owns the appearance of every shared component. --}}
    <link href="/css/bootstrap4_5_1.min.css" rel="stylesheet" type="text/css">
    <link href="/css/style.css?v={{ filemtime(public_path('css/style.css')) }}" rel="stylesheet" type="text/css">

    @if(isset($fullcalendar))
        <link href='/fullcalendar/main.css' rel='stylesheet' />
    @endif
    <link rel="stylesheet" href="/css/whatsapp.css">
    <link rel="stylesheet" href="/css/vendor/select2-4.0.13.css">
    <link rel="stylesheet" href="/css/vendor/bootstrap-datepicker-1.9.0.css">
    <link rel="stylesheet" href="/css/vendor/bootstrap-timepicker-and-daterangepicker.css">
    <link rel="stylesheet" href="/css/vendor/sweetalert2-9.17.2.css">
    <link rel="stylesheet" href="/ecommerce/assets/css/jquery-ui.min.css">

    <link rel="stylesheet" href="/css/line-awesome.min.css">

    <link rel="stylesheet" type="text/css" href="/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="/css/toastr.min.css">

    @if(!empty($appearanceAssets['favicon']))
        <link rel="icon" type="image/png" href="{{ $appearanceAssets['favicon'] }}">
        <link rel="shortcut icon" type="image/png" href="{{ $appearanceAssets['favicon'] }}">
    @else
        {{-- Identidade original, sem consulta ao módulo de aparência. --}}
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('imgs/favicon.png') }}?v={{ filemtime(public_path('imgs/favicon.png')) }}">
    @endif
    @if($cor != '')
    @endif

    <!-- Inclusão Centralizada dos Scripts de Monitoramento -->
    @include('layouts.tracking')



    @if($tema == 2 && !config('ui_theme.active', false))
        <link href="/css/escuro.css" rel="stylesheet" type="text/css" />
    @endif

    <link rel="stylesheet" href="/css/animate.min.css"/>

    @yield('css')

    {{-- Resolver o preset uma vez para disponibilizar tema e densidade ao shell. --}}
    @if(config('ui_theme.active', false))
        @include('themes.refined.assets', ['uiResolvedTheme' => $uiResolvedTheme])
    @endif


    <link rel="stylesheet" href="{{ asset('css/ui-operational.css') }}?v={{ filemtime(public_path('css/ui-operational.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/fer-design-system.css') }}?v={{ filemtime(public_path('css/fer-design-system.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/ui-form-harmony.css') }}?v={{ filemtime(public_path('css/ui-form-harmony.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/ui-top-nav.css') }}?v={{ filemtime(public_path('css/ui-top-nav.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/ui-dialog-navigation.css') }}?v={{ filemtime(public_path('css/ui-dialog-navigation.css')) }}">
    @stack('styles')

</head>
@php
    $uiPreferredMenu = $uiResolvedTheme['menu_mode'] ?? 'follow';
    $uiMenuMode = in_array($uiPreferredMenu, ['lateral', 'superior'], true) ? $uiPreferredMenu : (($tipoMenu ?? 'lateral') === 'superior' ? 'superior' : 'lateral');
    $uiIdentity = (string) (session('user_logged.empresa', 0)) . ':' . (string) session('user_logged.id', 0);
@endphp
<body id="kt_body" class="ui-theme-refined ui-native-shell @if($uiResolvedTheme['dark'] ?? false)ui-theme-refined--dark @endif @if(($uiResolvedTheme['density'] ?? '') === 'compact')ui-density-compact @endif"
      data-ui-layout="{{ $uiMenuMode }}" data-ui-identity="{{ $uiIdentity }}" data-ui-theme="{{ $uiResolvedTheme['slug'] ?? 'fer-light' }}">
<a class="ui-skip" href="#kt_content">Ir para o conteúdo</a>
<div class="ui-app">
    @include('themes.refined.partials.sidebar')
    <div class="ui-menu-backdrop" data-ui-menu-close hidden></div>
    <div id="kt_wrapper" class="ui-workspace">
        @include('themes.refined.partials.masthead')
                <div id="ui-session" class="ui-session">

                    @if($ultimoAcesso != null)
                        <div class="topbar-item">
                            <div class="btn btn-icon w-auto btn-clean d-flex align-items-center btn-lg px-2">
                                <span class="kt-header__topbar-welcome kt-hidden-mobile">Ultimo acesso em: </span>
                                <span style="margin-left: 3px;" class="kt-header__topbar-username kt-hidden-mobile"> <span style="font-weight: bold;" class="text-success text-left lbl-custom">{{ \Carbon\Carbon::parse($ultimoAcesso->created_at)->format('d/m/Y H:i:s') }}</span></span>
                            </div>

                        </div>

                        <div class="topbar-item ui-session-extra">
                            <div class="btn btn-icon w-auto btn-clean d-flex align-items-center btn-lg px-2">
                                <span class="kt-header__topbar-welcome kt-hidden-mobile">Endereço do IP: </span>
                                <span style="margin-left: 3px;" class="kt-header__topbar-username kt-hidden-mobile"> <span style="font-weight: bold;" class="text-success text-left lbl-custom">{{ $ultimoAcesso != null ? $ultimoAcesso->ip_address : '--' }}</span></span>
                            </div>
                        </div>

                        @if($totalParaArmazenar > 0)
                            <div class="topbar-item ui-session-extra">
                                <div class="btn btn-icon w-auto btn-clean d-flex align-items-center btn-lg px-2">
                                    <span class="kt-header__topbar-welcome kt-hidden-mobile mr-2">Armazenamento: </span>
                                    <div class="progress progress-xs mt-2 mb-2 flex-shrink-0 w-150px w-xl-250px">
                                        <div class="progress-bar {{App\Models\Plano::backgroundArmazenamento($percentualArmazenamento)}}" role="progressbar" style="width: {{$percentualArmazenamento}}%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="font-weight-bolder text-dark ml-4">{{number_format($percentualArmazenamento,2)}}%</span>

                                    <span class="font-weight-bolder text-dark ml-4">{{$armazenamento}}/{{$totalParaArmazenar}} MB</span>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if($video_url != null)
                        <div class="topbar-item ui-session-extra">
                            <div class="btn btn-icon w-auto btn-clean d-flex align-items-center btn-lg px-2">
                                <a target="_blank" href="{{$video_url}}" class="btn btn-light-info">
                                    <i class="la la-video"></i>
                                    Video Ajuda
                                </a>
                            </div>
                        </div>
                    @endif

                    {!! __view_datahora_clock() !!}

                </div>
        <main id="kt_content" class="ui-page" tabindex="-1">
            @yield('content')
        </main>
    </div>
</div>
        <div id="box_whatsapp" class="wcard">
            <div class="wcard-header">
                <div class="wcard-logo">
                    <img src="{{ asset('imgs/slym.png') }}" alt="Nome da empresa">
                </div>
                <div class="wcard-title">
                    <h6>{{env("APP_NAME")}}</h6>
                    <p><small>{{env("APP_DESC")}}</small></p>
                    <p><small class="text-success">Online</small></p>
                </div>
            </div>
            <div class="wcard-body">
                <div id="form_whatsapp">
                    <div class="wcard-campo">
                        <label for="w_nome">Diga-nos seu nome:</label>
                        <input type="text" name="w_nome" id="w_nome">
                    </div>
                    <div class="wcard-footer">
                        <div class="wcard-mensagem">
                            <textarea name="w_mensagem" id="w_mensagem" rows="1" placeholder="Digite sua mensagem"></textarea>
                        </div>
                        <div class="wcard-send">
                            <button id="send-whats"><i class="la la-paper-plane"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<div class="modal fade" id="modal-dinamic" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="get" action="/usuarios/setTema">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title title-dinamic"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">
                    <h4 class="text-dinamic"></h4>
                </div>
                <div class="modal-footer">

                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-loading" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="get" action="/usuarios/setTema">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aguarde ...</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">
                    <lottie-player src="/anime/loader-doc.json" background="transparent" speed="0.8" style="width: 100%; height: 300px;" autoplay loop>
                    </lottie-player>

                    <h4>Atualizando tabela IBPT de seus produtos</h4>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-pesquisa" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="get" action="/usuarios/setTema">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title p-titulo"></h5>
                    <button onclick="fecharPesquisa()" type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">

                    <div class="p-texto"></div>

                    <div class="row mt-4">
                        <div class="col-12">
                            @for($i=1; $i<=10; $i++)
                                <i class="la la-star ico-pesquisa ico-{{$i}}" id="{{$i}}"></i>
                            @endfor
                        </div>

                        <div class="form-group col-lg-12">
                            <label class="col-form-label">Escreva algo se desejar</label>
                            <div class="">
                                <div class="input-group">
                                    <input type="text" name="resposta" class="form-control" value="" id="resposta"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btn-frete" class="btn btn-light-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close" onclick="fecharPesquisa()">Não responder agora</button>
                    <button id="btn-salvar-pesquisa" type="button" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
                </div>

            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-pedido-delivery" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <form method="post" action="/pedidosDelivery/lerPedido" id="form-pedido-delivery">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Pedido</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">

                </div>

                <div class="modal-footer">
                    <button id="btn-cancelar-pedido" type="button" class="btn btn-danger font-weight-bold spinner-white spinner-right">Recusar pedido</button>
                    <button id="btn-confirmar-pedido" type="button" class="btn btn-success font-weight-bold spinner-white spinner-right">Confirmar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-pedido-mesa" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="post" action="/pedidosMesa/alterarStatusPedido" id="form-pedido-mesa">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Atendimento</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">

                </div>

                <div class="modal-footer">
                    <button id="btn-cancelar-pedido-mesa" type="button" class="btn btn-danger font-weight-bold spinner-white spinner-right">Recusar</button>
                    <button id="btn-confirmar-pedido-mesa" type="button" class="btn btn-success font-weight-bold spinner-white spinner-right">Confirmar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-pedido-ifood" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <form method="post" action="/ifood/readOrder" id="form-pedido-ifood">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Pedido iFood <img width="50" src="/icones/ifood.png"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">

                </div>

                <div class="modal-footer">
                    <button id="btn-cancelar-pedido-ifood" type="button" class="btn btn-danger font-weight-bold spinner-white spinner-right">Recusar pedido</button>
                    <button id="btn-confirmar-pedido-ifood" type="button" class="btn btn-success font-weight-bold spinner-white spinner-right">Confirmar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-alerta-super" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alertas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    x
                </button>
            </div>
            <div class="modal-body">

            </div>

            <div class="modal-footer">
                <a href="/super-admin/altera-todos" type="button" class="btn btn-light-dark font-weight-bold">Visualizar todos</a>
                <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
            </div>
        </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-cancelar-pedido-ifood" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancelando Pedido</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    x
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group col-lg-6 col-12">
                    <label>Código cancelamento</label>
                    <select required id="codigo-ifood" class="custom-select form-control">
                        @foreach(\App\Models\IfoodConfig::getStatusErros() as $key => $c)
                            <option value="{{ $key }}">{{ $key }} - {{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-12 col-12">
                    <label>Descrição de cancelamento</label>
                    <input type="text" id="motivo-ifood" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
                <button type="button" onclick="cancelarPedidoIfood()" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Cancelar</button>
            </div>

        </div>
    </div>
</div>

@if(session('tipo_contador'))
    <div class="modal fade" id="modal-empresa-contador" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="post" action="/contador/set-empresa">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Selecione a empresa</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            x
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="col-12">
                            <select class="custom-select form-control" name="empresa">
                                <option value="">Selecione</option>
                                @foreach(session('user_contador') as $emp)
                                    <option @if(session('empresa_selecionada')) @if(session('empresa_selecionada')['empresa_id'] == $emp['empresa_id']) selected @endif @endif value="{{ $emp['empresa_id'] }}">{{ $emp['nome'] }} {{ $emp['documento'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success font-weight-bold spinner-white spinner-right">Selecionar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

@if(session()->has('mensagem_indeterminado'))
    <div class="modal fade" id="modal-mensagem-pagamento" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Alerta de pagamento</h5>
                </div>
                <div class="modal-body">
                    <b style="font-size: 20px;">{{session()->get('mensagem_indeterminado')}}</b>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-success font-weight-bold spinner-white spinner-right btn-ciente" data-dismiss="modal" aria-label="Close">Estou ciente</button>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="modal fade" id="modal-alerta-validade" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alerta de validade</h5>
                <button onclick="closeModalValidade()" type="button" class="close" data-dismiss="modal" aria-label="Close">
                    x
                </button>
            </div>
            <div class="modal-body">
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModalValidade()" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-alerta-estoque" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alerta de estoque</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    x
                </button>
            </div>
            <div class="modal-body">
            </div>

            <div class="modal-footer">
                <a target="_blank" href="/compras/imprimir-alerta-estoque" class="btn btn-light-info font-weight-bold">Imprimir</a>
                <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

{{-- Bibliotecas independentes dos controles utilizados pelas views atuais. --}}

<script src="/js/vendor/jquery-3.4.1.js"></script>
<script src="/ecommerce/assets/js/jquery-ui.min.js"></script>
<script src="/js/vendor/popper-1.16.1.js"></script>
<script src="/js/vendor/bootstrap-4.5.3.js"></script>
<script src="/js/vendor/moment-2.29.1.js"></script>
<script src="/js/vendor/bootstrap-datepicker-1.9.0.js"></script>
<script src="/js/vendor/bootstrap-timepicker-0.5.2.js"></script>
<script src="/js/vendor/daterangepicker-3.1.js"></script>
<script src="/js/vendor/select2-4.0.13.js"></script>
<script src="/js/vendor/inputmask-4.0.9.js"></script>
<script src="/js/vendor/apexcharts-3.22.0.js"></script>
<script src="/js/fer-runtime.js"></script>
<script src="/js/vendor/sweetalert2-9.17.2.js"></script>

<script type="text/javascript" src="/js/jquery.mask.min.js"></script>
<script type="text/javascript" src="/js/mascaras.js"></script>

<script src="/js/sweetalert.min.js"></script>
<script src="/js/toastr.min.js"></script>

<script type="text/javascript">
    function audioSuccess(){
        @if($alerta_sonoro != "")
        var audio = new Audio('/audio/{{$alerta_sonoro}}');
        audio.addEventListener('canplaythrough', function() {
            audio.play();
        });
        @endif
    }

    function audioError(){
        @if($alerta_sonoro != "")
        var audio = new Audio('/audio/error.mp3');
        audio.addEventListener('canplaythrough', function() {
            audio.play();
        });
        @endif
    }

    function audioWarning(){
        @if($alerta_sonoro != "")
        var audio = new Audio('/audio/warning.mp3');
        audio.addEventListener('canplaythrough', function() {
            audio.play();
        });
        @endif
    }
</script>

<?php $path = env('PATH_URL') . "/"; ?>
<script type="text/javascript">

    var casas_decimais = 2;
    var casas_decimais_qtd = 2;

    casas_decimais = {{$casasDecimais}}
        casas_decimais_qtd = {{$casasDecimaisQtd}}

        let prot = window.location.protocol;
    let host = window.location.host;
    const path = prot + "//" + host + "/";
</script>

@if(isset($pessoaFisicaOuJuridica))
    <script type="text/javascript" src="/js/pessoaFisicaOuJuridica.js"></script>
@endif

@if(isset($service))
    <script type="text/javascript" src="/js/service.js"></script>
@endif

@if(isset($client))
    <script type="text/javascript" src="/js/client.js"></script>
@endif

@if(isset($nf))
    <script type="text/javascript" src="/js/nf.js"></script>
@endif

@if(isset($fornecedor))
    <script type="text/javascript" src="/js/fornecedor.js"></script>
@endif

@if(isset($budget))
    <script type="text/javascript" src="/js/budget.js"></script>
@endif

@if(isset($order))
    <script type="text/javascript" src="/js/order.js"></script>
@endif

@if(isset($usuarioJs))
    <script type="text/javascript" src="/js/usuario.js"></script>
@endif

<script type="text/javascript" src="/js/google-api.js"></script>


@if(isset($purchase))
    <script type="text/javascript" src="/js/purchase.js"></script>
@endif

@if(isset($funcionario))
    <script type="text/javascript" src="/js/funcionario.js"></script>
@endif

@if(isset($produtoJs))
    <script type="text/javascript" src="/js/produto.js"></script>
@endif

@if(isset($gradeJs))
    <script type="text/javascript" src="/js/grade.js"></script>
@endif

@if(isset($pedidoJs))
    <script type="text/javascript" src="/js/pedido.js"></script>
@endif

@if(isset($servicoJs))
    <script type="text/javascript" src="/js/servicos.js"></script>
@endif

@if(isset($relatorioJs))
    <script type="text/javascript" src="/js/relatorio.js"></script>
@endif

@if(isset($compraFiscalJs))
    <script type="text/javascript" src="/js/compraFiscal.js"></script>
@endif

@if(isset($pedidoDeliveryJs))
    <script type="text/javascript" src="/js/pedidoDelivery.js"></script>
@endif

@if(isset($cidadeJs))
    <script type="text/javascript" src="/js/cidades.js"></script>
@endif

@if(isset($vendaJs))
    <script type="text/javascript" src="/js/venda.js"></script>
@endif

@if(isset($vendaJsAssincrono))
    <script type="text/javascript" src="/js/vendaJsAssincrono.js"></script>
@endif

@if(isset($creditoVenda))
    <script type="text/javascript" src="/js/creditoVenda.js"></script>
@endif

@if(isset($compraManual))
    <script type="text/javascript" src="/js/compraManual.js"></script>
@endif

@if(isset($compraManualAssincrono))
    <script type="text/javascript" src="/js/compraManualAssincrono.js"></script>
@endif

@if(isset($cotacaoJs))
    <script type="text/javascript" src="/js/cotacao.js"></script>
@endif

@if(isset($categoriaJs))
    <script type="text/javascript" src="/js/categoria.js"></script>
@endif

@if(isset($pushJs))
    <script type="text/javascript" src="/js/push.js"></script>
@endif

@if(isset($frenteCaixa))
    <script type="text/javascript" src="/js/frenteCaixa.js"></script>
@endif

@if(isset($adicional))
    <script type="text/javascript" src="/js/adicional.js"></script>
@endif

@if(isset($cloneJs))
    <script type="text/javascript" src="/js/clone.js"></script>
@endif

@if(isset($cteJs))
    <script type="text/javascript" src="/js/cte.js"></script>
@endif

@if(isset($cteEnvioJs))
    <script type="text/javascript" src="/js/cte_envio.js"></script>
@endif

@if(isset($cozinhaJs))
    <script type="text/javascript" src="/js/cozinha.js"></script>
@endif

@if(isset($codigoJs))
    <script type="text/javascript" src="/js/codigo.js"></script>
@endif

@if(isset($devolucaoJs))
    <script type="text/javascript" src="/js/devolucao.js"></script>
@endif

@if(isset($devolucaoJsEdit))
    <script type="text/javascript" src="/js/devolucaoEdit.js"></script>
@endif

@if(isset($devolucaoNF))
    <script type="text/javascript" src="/js/devolucaoNF.js"></script>
@endif

@if(isset($mdfeJs))
    <script type="text/javascript" src="/js/mdfe.js"></script>
@endif

@if(isset($mdfeEnvioJs))
    <script type="text/javascript" src="{{ asset('js/fiscal_error.js') }}?v={{ filemtime(public_path('js/fiscal_error.js')) }}"></script>
    <script type="text/javascript" src="{{ asset('js/mdfe_envio.js') }}?v={{ filemtime(public_path('js/mdfe_envio.js')) }}"></script>
@endif

@if(isset($print))
    <script type="text/javascript" src="/js/jQuery.print/jQuery.print.js"></script>
    <script type="text/javascript" src="/js/print.js"></script>
@endif

@if(isset($mapJs))
    <script src="https://maps.googleapis.com/maps/api/js?key={{env('API_KEY_MAPS')}}"
            async defer></script>
    <script type="text/javascript" src="/js/map.js"></script>
@endif

@if(isset($graficoHomeJs))
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.js"></script>

    <script type="text/javascript" src="/js/grafico_home.js"></script>
@endif

@if(session()->has('login_ibpt'))
    <script type="text/javascript" src="/js/atualizaIbpt.js"></script>
@endif

@if(session()->has('pesquisa_satisfacao'))
    @if(session()->has('forcar_pesquisa'))
        <input type="hidden" value="1" id="forcar_pesquisa">
    @endif
    <input type="hidden" value="{{session()->get('pesquisa_satisfacao')}}" id="pesquisa_id">
    <script type="text/javascript" src="/js/pesquisa_satisfacao.js"></script>
@endif

@if(isset($relatorioJS))
    <script type="text/javascript" src="/js/relatorios.js"></script>
@endif

@if(isset($dfeJS))
    <script type="text/javascript" src="/js/dfe.js"></script>
@endif

@if(isset($naoEncerradosMDFeJS))
    <script type="text/javascript" src="/js/naoEncerradosMDFe.js"></script>
@endif

@if(isset($NFeEntradaJS))
    <script type="text/javascript" src="/js/nfeEntrada.js"></script>
@endif

@if(isset($controleHorarioJs))
    <script type="text/javascript" src="/js/controleHorario.js"></script>
@endif

@if(isset($frentePedidoDeliveryJs))
    <script type="text/javascript" src="/js/frentePedidoDelivery.js"></script>
@endif

@if(isset($frentePedidoDeliveryPedidoJs))
    <script type="text/javascript" src="/js/frentePedidoDeliveryPedido.js"></script>
@endif

@if(isset($testeJs))
    <script type="text/javascript" src="/js/teste.js"></script>
@endif

@if(isset($bannerJs))
    <script type="text/javascript" src="/js/banner.js"></script>
@endif

<script src="/js/lottie-player.js"></script>


@if(isset($graficoJs))

    <script type="text/javascript" src="/js/grafico.js"></script>
@endif

@if(isset($orcamentoJs))
    <script type="text/javascript" src="/js/orcamento.js"></script>
@endif

@if(isset($atribuirComandaJs))
    <script type="text/javascript" src="/js/atribuirComandaJs.js"></script>
@endif

@if(isset($motoboyEntrega))
    <script type="text/javascript" src="/js/motoboyEntrega.js"></script>
@endif

@if(isset($comissaoJs))
    <script type="text/javascript" src="/js/comissao.js"></script>
@endif

@if(isset($empresaJs))
    <script type="text/javascript" src="/js/empresa.js"></script>
@endif

@if(isset($fullcalendar))
    <script src='/fullcalendar/main.js'></script>
    <script src='/fullcalendar/locales/pt-br.js'></script>
    <script src='/js/calendario.js'></script>
@endif

@if(isset($configJs))
    <script type="text/javascript" src="/js/config.js"></script>
@endif

@if(isset($eventoJs))
    <script type="text/javascript" src="/js/evento.js"></script>
@endif

@if(isset($veiculoJs))
    <script type="text/javascript" src="/js/veiculo.js"></script>
@endif

@if(isset($caixaJs))
    <script type="text/javascript" src="/js/caixa.js"></script>
@endif

@if(isset($dreJs))
    <script type="text/javascript" src="/js/dre.js"></script>
@endif

@if(session('user_logged')['super'] == 1)
    <script type="text/javascript" src="/js/super.js"></script>
@endif

@if(isset($contratoJs))

    <script type="text/javascript" src="/js/nicEdit-latest.js"></script>

    <script type="text/javascript">
        bkLib.onDomLoaded(function() { nicEditors.allTextAreas() });

        bkLib.onDomLoaded(function() {
            new nicEditor().panelInstance('area1');
        });

        bkLib.onDomLoaded(function() {
            new nicEditor({fullPanel : true}).panelInstance('area2');
        });
    </script>

@endif

@if(isset($textAreaEditor))

    <script type="text/javascript" src="/js/nicEdit-latest.js"></script>

    <script type="text/javascript">
        new nicEditor({fullPanel : true}).panelInstance('mensagem_agradecimento',{hasPanel : true});
    </script>

@endif

@yield('javascript')

@if(session()->has('nova_aba'))
    <script type="text/javascript">
        let rota = "<?php echo session()->get('nova_aba') ?>"
        window.open(rota);
    </script>
@endif

@if(session()->has('mensagem_indeterminado'))
    <script type="text/javascript">
        $('.btn-ciente').attr('disabled', true)
        setTimeout(() => {
            $('#modal-mensagem-pagamento').modal('show')
            setTimeout(() => {
                $('.btn-ciente').removeAttr('disabled')
            }, 5000)
        }, 500)
    </script>
@endif

@if(session()->has('mensagem_xml'))
    <script type="text/javascript">
        swal({
            title: "Atenção",
            text: '{{session()->get('mensagem_xml')}}',
            icon: "warning",
            buttons: [
                'Ver depois',
                'Sim quero enviar'
            ],
        }).then((acao) => {
            if(acao){
                location.href = path + 'enviarXml?data=true';
            }else{

            }
        })
    </script>
@endif

@if(session()->has('mensagem_certificado'))
    <script type="text/javascript">
        swal({
            title: "Atenção",
            text: '{{session()->get('mensagem_certificado')}}',
            icon: "warning",

            buttons: {
                cancel: "Ver depois",
                confirm: "Emitente",
                hello: "Compre agora",
            },
        }).then((acao) => {
            if(acao == 'hello'){
                window.open('https://wa.me/+55'+{{env("RESP_FONE")}})
            }else{
                if(acao != null){
                    location.href = path + 'configNF';
                }
            }

            @if(session()->has('mensagem_xml'))
            swal({
                title: "Atenção",
                text: '{{session()->get('mensagem_xml')}}',
                icon: "warning",
                buttons: [
                    'Ver depois',
                    'Sim quero enviar'
                ],
            }).then((acao) => {
                if(acao){
                    location.href = path + 'enviarXml';
                }else{

                }
            })
            @endif
        })
    </script>
@endif

@if(session()->has('mensagem_pagamento'))
    <script type="text/javascript">
        // swal("Atenção", '{{session()->get('mensagem_pagamento')}}', "warning")

        swal({
            title: "Atenção",
            text: '{{session()->get('mensagem_pagamento')}}',
            icon: "warning",
            buttons: [
                'Ver depois',
                'ir para pagamento'
            ],
        }).then((acao) => {
            if(acao){
                location.href = path + 'payment';
            }else{

            }
        })
    </script>
@endif

@if(session()->has('alert_vencimento'))
    <script type="text/javascript" src="/js/check_validade.js"></script>
@endif

<script src="/js/bootstrap-datepicker.pt-BR.min.js"></script>
<script src="/js/bootstrap-tour-standalone.js"></script>
@if($rotaAtiva == 'Financeiro')
    <script src="/js/tour.js"></script>
@endif
@if($configCatraca && env("CATRACA") == 1)
    <script type="text/javascript">
        var TIMER = {{ $configCatraca->segundos_requisicao }}
    </script>
    <script src="/js/catraca.js?timer=2"></script>
@endif
<script>

    jQuery(document).ready(function() {
        window.FerRuntime.init(document);
        $('.select2-selection__arrow').addClass('select2-selection__arroww')

        $('.select2-selection__arrow').removeClass('select2-selection__arrow')
        $('.delivery-arrow').removeClass('select2-selection__arrow')

        $('.menu-arrow').removeClass('menu-arrow');

        var KTBootstrapDatepicker = function() {

            var arrows;
            if (KTUtil.isRTL()) {
                arrows = {
                    leftArrow: '<i class="la la-angle-right"></i>',
                    rightArrow: '<i class="la la-angle-left"></i>'
                }
            } else {
                arrows = {
                    leftArrow: '<i class="la la-angle-left"></i>',
                    rightArrow: '<i class="la la-angle-right"></i>'
                }
            }



            // Private functions
            var demos = function() {

                // minimum setup
                $('#kt_datepicker_1').datepicker({
                    rtl: KTUtil.isRTL(),
                    todayHighlight: true,
                    orientation: "bottom left",
                    templates: arrows,
                    language: "pt-BR"
                });

                // minimum setup for modal demo
                $('#kt_datepicker_1_modal').datepicker({
                    rtl: KTUtil.isRTL(),
                    todayHighlight: true,
                    orientation: "bottom left",
                    templates: arrows,
                    language: "pt-BR"
                });

                // input group layout
                $('#kt_datepicker_2').datepicker({
                    rtl: KTUtil.isRTL(),
                    todayHighlight: true,
                    orientation: "bottom left",
                    templates: arrows,
                    language: "pt-BR"
                });

                // input group layout for modal demo
                $('#kt_datepicker_2_modal').datepicker({
                    rtl: KTUtil.isRTL(),
                    todayHighlight: true,
                    orientation: "bottom left",
                    templates: arrows,
                    language: "pt-BR"
                });

                // enable clear button
                $('#kt_datepicker_3, #kt_datepicker_3_validate').datepicker({
                    rtl: KTUtil.isRTL(),
                    todayBtn: "linked",
                    clearBtn: false,
                    format: 'dd/mm/yyyy',
                    todayHighlight: false,
                    templates: arrows,
                    language: "pt-BR"
                });

                $('#kt_daterangepicker').daterangepicker();

                // enable clear button for modal demo
                $('#kt_datepicker_3_modal').datepicker({
                    rtl: KTUtil.isRTL(),
                    todayBtn: "linked",
                    clearBtn: false,
                    format: 'dd/mm/yyyy',
                    todayHighlight: false,
                    templates: arrows,
                    language: "pt-BR"
                });

                // orientation
                $('#kt_datepicker_4_1').datepicker({
                    rtl: KTUtil.isRTL(),
                    orientation: "top left",
                    todayHighlight: true,
                    templates: arrows,
                    language: "pt-BR"
                });

                $('#kt_datepicker_4_2').datepicker({
                    rtl: KTUtil.isRTL(),
                    orientation: "top right",
                    todayHighlight: true,
                    templates: arrows,
                    language: "pt-BR"
                });

                $('#kt_datepicker_4_3').datepicker({
                    rtl: KTUtil.isRTL(),
                    orientation: "bottom left",
                    todayHighlight: true,
                    templates: arrows,
                    language: "pt-BR"
                });

                /*
                // Seleciona todos os elementos cujo ID começa com "data_"
                $('[id^="data_"]').datepicker({
                    rtl: KTUtil.isRTL(),
                    todayBtn: 'linked',
                    clearBtn: false,
                    format: 'dd/mm/yyyy',
                    todayHighlight: true,
                    templates: arrows,
                    language: 'pt-BR',
                });
                */

                // Seleciona todos os elementos cujo ID termina com "_date_pick_dynamic"
                $('[id$="_dynamic"]').datepicker({
                    rtl: KTUtil.isRTL(),
                    todayBtn: 'linked',
                    clearBtn: false,
                    format: 'dd/mm/yyyy',
                    todayHighlight: true,
                    templates: arrows,
                    language: 'pt-BR',
                });

            }

            return {
                // public functions
                init: function() {
                    demos();
                }
            };

        }();

        KTBootstrapDatepicker.init(
            {
                format: 'dd/mm/yyyy'
            }
        );
    });
</script>

<script type="text/javascript">
    $('input[type=file]').change(() => {
        var filename = $('input[type=file]').val().replace(/.*(\/|\\)/, '');
        $('#filename').html(filename)
    })

    $('#send-csv').click(() => {
        $('#send-csv').attr('disabled')
        $('#send-csv').addClass('disabled')
        $('#send-csv').addClass('spinner')
    })

    $('.btn-hide').click(() => {
        let toggle = window.localStorage.getItem('menu-toogle');

        if(!toggle){
            window.localStorage.setItem('menu-toogle', 'aside-minimize');
        }else{
            if(toggle == 'aside-minimize'){
                $('#kt_body').addClass('page-loading');
                $('#kt_body').removeClass('aside-minimize');
                window.localStorage.setItem('menu-toogle', 'page-loading');
            }else{
                $('#kt_body').removeClass('page-loading');
                $('#kt_body').addClass('aside-minimize');
                window.localStorage.setItem('menu-toogle', 'aside-minimize');
            }
        }
    })

    $(function () {
        let toggle = window.localStorage.getItem('menu-toogle');
        console.log(toggle)
        if(toggle == 'aside-minimize'){
            $('#kt_body').addClass('aside-minimize');
            $('#kt_body').removeClass('page-loading');
        }
    })
</script>

@if(session()->has('link'))
    <script type="text/javascript">
        window.open("{{session()->get('link')}}")
    </script>
@endif

@if($deliveryConfig && env("DELIVERY") == 1)
    <script type="text/javascript" src="/js/pedido_delivery.js"></script>
    <script type="text/javascript" src="/js/pedido_mesa.js"></script>
@endif

@if($ifoodConfig && env("IFOOD") == 1)
    <script type="text/javascript" src="/js/pedido_ifood.js"></script>
@endif

<!-- Atendimento e Suporte Online -->
@include('layouts.support')

<script type="text/javascript">
    const formatar = (data) => {
        const hora = data.getHours() < 10 ? '0'+data.getHours() : data.getHours();
        const min = data.getMinutes() < 10 ? '0'+data.getMinutes() : data.getMinutes();
        const seg = data.getSeconds() < 10 ? '0'+data.getSeconds() : data.getSeconds();

        return `${hora}:${min}:${seg}`;
    };
    setInterval(() => {
        let hora = formatar(new Date())
        $('#timer').html(hora)
    }, 1000)

    /*$('#btn_whatsapp, .btn-abre-whatsapp').on('click', function(e){
        e.preventDefault();

        var btn = $('#btn_whatsapp');
        var box = $('#box_whatsapp');

        if(box.is(":visible")){
            btn.children('.icone-whatsapp').removeClass('la la-times').addClass('lab la-whatsapp');
            box.fadeOut(250);
        } else {
            btn.children('.icone-whatsapp').removeClass('lab la-whatsapp').addClass('la la-times');
            box.fadeIn(250);
        }
    })*/

    /*$('#send-whats').click(() => {
        let mensagem = $('#w_mensagem').val()
        let nome = $('#w_nome').val()

        let msg = ""
        if(nome){
            msg += "Olá meu nome é "+nome+ ", ";
        }

        msg += mensagem

        let num = {{env("CONTATO_SUPORTE")}}

    let uri = "https://wa.me/55"+num+"?text="+msg
    window.open(uri)

    var btn = $('#btn_whatsapp');
    var box = $('#box_whatsapp');
    btn.children('.icone-whatsapp').removeClass('la la-times').addClass('lab la-whatsapp');
    box.fadeOut(250);
})*/


    detectar_mobile()
    function detectar_mobile() {
        if( navigator.userAgent.match(/Android/i) || navigator.userAgent.match(/webOS/i) || navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/iPad/i) || navigator.userAgent.match(/iPod/i) || navigator.userAgent.match(/BlackBerry/i) || navigator.userAgent.match(/Windows Phone/i)){
            $('.not-mobile').css('display', 'none')
            $('.y-mobile').css('display', 'block')
        }
        else {
            $('.not-mobile').css('display', 'block')
            $('.y-mobile').css('display', 'none')

        }
    }

    $('.info-plan').click(() => {
        $.get(path + 'getPlan')
            .done((res) => {
                console.log(res)
                if(res){
                    swal({
                        title: "Detalhe do plano",
                        text: "Nome do plano: " + res.plano.nome + "\n" +
                            "Data de expiração: " + res.expira + "\n" +
                            "Valor: R$ " + res.valor.replace(".", ","),
                        icon: "info",
                        buttons: [
                            'Upgrade',
                            'OK'
                        ],
                    }).then((i) => {
                        if(!i){
                            location.href = '/payment'
                        }
                    });
                }else{
                    swal("Alerta", "Nenhum plano atribuido", "info")
                }
            }).fail((err) => {
            swal("Alerta", "Pode ser que esta empresa não tenha um plano atribuído", "info")

        })
    })

    toastr.options = {
        "progressBar": true,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "10000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    }

    @if(session()->has('mensagem_sucesso'))
    toastr.success('{{ session()->get('mensagem_sucesso') }}');
    audioSuccess()
    @endif

    @if(session()->has('mensagem_erro'))
    toastr.error('{{ session()->get('mensagem_erro') }}');
    audioError()
    @endif

    @if(session()->has('mensagem_alerta'))
    toastr.warning('{{ session()->get('mensagem_alerta') }}');
    audioWarning()
    @endif

    // $('#modal-pesquisa').modal('show')
</script>

<!-- Modal/monitor global do Delivery renderizado somente dentro do body -->
@yield('global_delivery_alert')

<!-- tour -->

<script type="text/javascript" src="/js/main.js"></script>

@yield('modal-alerta-sistema')

@php
    $userLogged = session('user_logged');
    $usuarioLogadoId = $userLogged['id'] ?? $userLogged['usuario_id'] ?? null;
    $empresaId = $userLogged['empresa_id'] ?? null;
    $isAdm = !empty($userLogged['adm']);

    $tarefasCriticasAlerta = collect();
    $totalVencidasAlerta = 0;
    $totalPendentesAlerta = 0;

    if ($empresaId && $usuarioLogadoId && !session('alerta_tarefas_visto')) {
        $q = \App\Models\Tarefa::where('empresa_id', $empresaId)
            ->whereNotIn('status', ['concluida', 'cancelada']);

        if (!$isAdm) {
            $func = \App\Models\Funcionario::where('usuario_id', $usuarioLogadoId)->first();
            $funcId = $func ? $func->id : 0;
            $q->where(function($sub) use ($usuarioLogadoId, $funcId) {
                $sub->where('usuario_id', $usuarioLogadoId)->orWhere('funcionario_id', $funcId);
            });
        }

        $abertas = $q->with('funcionario')->get();
        $agora = \Carbon\Carbon::now();

        foreach ($abertas as $t) {
            $atrasou = false;
            if (!empty($t->data_limite)) {
                $dtLimite = \Carbon\Carbon::parse(\Carbon\Carbon::parse($t->data_limite)->format('Y-m-d') . ' ' . ($t->hora_limite ?: '23:59:59'));
                if ($agora->greaterThan($dtLimite)) {
                    $atrasou = true;
                    $totalVencidasAlerta++;
                }
            }
            if (!$atrasou && $t->status === 'pendente') {
                $totalPendentesAlerta++;
            }
            if ($atrasou || $t->status === 'pendente') {
                $tarefasCriticasAlerta->push([
                    'id' => $t->id,
                    'titulo' => $t->titulo,
                    'funcionario' => $t->funcionario->nome ?? 'Não atribuído',
                    'limite' => $t->data_limite ? \Carbon\Carbon::parse($t->data_limite)->format('d/m/Y') . ($t->hora_limite ? ' ' . $t->hora_limite : '') : 'Sem prazo',
                    'prioridade' => $t->prioridade,
                    'is_atrasada' => $atrasou
                ]);
            }
        }
    }
@endphp

@if(($totalVencidasAlerta > 0 || $totalPendentesAlerta > 0) && !session('alerta_tarefas_visto'))
    <div class="modal fade" id="modalAlertaObrigatorioTarefas" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
                <div class="modal-header {{ $totalVencidasAlerta > 0 ? 'bg-danger' : 'bg-warning' }} text-white py-4 px-5">
                    <div class="d-flex align-items-center">
                        <i class="la la-bell fs-1 text-white mr-3"></i>
                        <div>
                            <h4 class="modal-title font-weight-bolder text-white mb-0">
                                {{ $isAdm ? 'Alerta da Equipe: Tarefas Pendentes e Vencidas' : 'Atenção: Você possui tarefas pendentes!' }}
                            </h4>
                            <small class="text-white opacity-90">
                                {{ $isAdm ? 'Acompanhamento geral de produtividade' : 'Regularize suas tarefas para manter o fluxo em dia' }}
                            </small>
                        </div>
                    </div>
                </div>

                <div class="modal-body p-5">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6 col-12">
                            <div class="p-3 rounded border d-flex align-items-center" style="background-color: #fff5f5; border-color: #feb2b2 !important;">
                                <div class="symbol symbol-40 mr-3" style="background: #e53e3e; border-radius: 8px; color: #fff; display: flex; align-items: center; justify-content: center; width: 42px; height: 42px;">
                                    <b style="font-size: 1.2rem;">{{ $totalVencidasAlerta }}</b>
                                </div>
                                <div>
                                    <h6 class="font-weight-bolder text-danger mb-0">Tarefas Vencidas</h6>
                                    <small class="text-muted">Prazo limite expirado</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="p-3 rounded border d-flex align-items-center" style="background-color: #fffaf0; border-color: #fbd38d !important;">
                                <div class="symbol symbol-40 mr-3" style="background: #dd6b20; border-radius: 8px; color: #fff; display: flex; align-items: center; justify-content: center; width: 42px; height: 42px;">
                                    <b style="font-size: 1.2rem;">{{ $totalPendentesAlerta }}</b>
                                </div>
                                <div>
                                    <h6 class="font-weight-bolder text-warning mb-0">Tarefas Pendentes</h6>
                                    <small class="text-muted">Aguardando início</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="font-weight-bold text-dark mb-2"><i class="la la-list-alt text-primary mr-1"></i> Lista de atividades prioritárias:</h6>
                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="bg-light">
                            <tr>
                                <th>Tarefa</th>
                                @if($isAdm) <th>Responsável</th> @endif
                                <th>Prazo Limite</th>
                                <th>Prioridade</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tarefasCriticasAlerta->take(8) as $tItem)
                                <tr>
                                    <td class="font-weight-bold text-dark">{{ $tItem['titulo'] }}</td>
                                    @if($isAdm)
                                        <td><span class="badge badge-light-primary">{{ $tItem['funcionario'] }}</span></td>
                                    @endif
                                    <td class="{{ $tItem['is_atrasada'] ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                        {{ $tItem['limite'] }}
                                    </td>
                                    <td>
                                    <span class="badge {{ $tItem['prioridade'] == 'Urgente' ? 'badge-danger' : ($tItem['prioridade'] == 'Alta' ? 'badge-warning text-dark' : 'badge-primary') }}">
                                        {{ $tItem['prioridade'] }}
                                    </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer bg-light py-3 px-5 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary font-weight-bold" id="btnEstouCienteTarefas">
                        <i class="la la-check"></i> Estou Ciente / Fechar
                    </button>
                    <div>
                        <a href="/tarefas/painel" class="btn btn-primary font-weight-bold shadow-sm">
                            <i class="la la-columns"></i> Abrir Painel Kanban
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            $('#modalAlertaObrigatorioTarefas').modal('show');

            $('#btnEstouCienteTarefas').on('click', function () {
                $('#modalAlertaObrigatorioTarefas').modal('hide');
                $.ajax({
                    url: '/tarefas/marcar-alerta-lido',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    }
                });
            });
        });
    </script>
@endif


@stack('scripts')
<script src="{{ asset('js/ui-operational.js') }}?v={{ filemtime(public_path('js/ui-operational.js')) }}" defer></script>
<script src="{{ asset('js/ui-top-nav.js') }}?v={{ filemtime(public_path('js/ui-top-nav.js')) }}" defer></script>
</body>
</html>
