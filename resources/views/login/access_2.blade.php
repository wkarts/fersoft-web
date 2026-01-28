<!DOCTYPE html>
<!--
Template Name: Metronic - Bootstrap 4 HTML, React, Angular 11 & VueJS Admin Dashboard Theme
Author: KeenThemes
Website: http://www.keenthemes.com/
Contact: support@keenthemes.com
Follow: www.twitter.com/keenthemes
Dribbble: www.dribbble.com/keenthemes
Like: www.facebook.com/keenthemes
Purchase: https://1.envato.market/EA4JP
Renew Support: https://1.envato.market/EA4JP
License: You must have a valid license purchased only from themeforest(the above link) in order to legally use the theme for your project.
-->
<html lang="pt">
<!--begin::Head-->
<head>
	<meta charset="utf-8" />
	<title>ACESSO - {{ env("APP_NAME") }}</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

	<meta name="description" content="Login page example" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700|Roboto:300,400,500,600,700">

	<link href="/metronic/css/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
	<!-- <link href="/metronic/css/uppy.bundle.css" rel="stylesheet" type="text/css" /> -->
	<link href="/metronic/css/wizard.css" rel="stylesheet" type="text/css" />

	<link href="/css/style.css" rel="stylesheet" type="text/css" />

	<!--end::Page Vendors Styles -->


	<!--begin::Global Theme Styles(used by all pages) -->
	<link href="/metronic/css/plugins.bundle.css" rel="stylesheet" type="text/css" />
	<link href="/metronic/css/prismjs.bundle.css" rel="stylesheet" type="text/css" />
	<link href="/metronic/css/style.bundle.css" rel="stylesheet" type="text/css" />

	<link href="/metronic/css/pricing.css" rel="stylesheet" type="text/css" />
	<!--end::Global Theme Styles -->

	<!--begin::Layout Skins(used by all pages) -->

	<link href="/metronic/css/light.css" rel="stylesheet" type="text/css" />
	<link href="/metronic/css/light-menu.css" rel="stylesheet" type="text/css" />
	<link href="/metronic/css/dark-brand.css" rel="stylesheet" type="text/css" />
	<link href="/metronic/css/dark-aside.css" rel="stylesheet" type="text/css" />

	<link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">

	<link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

	<link rel="shortcut icon" href="/../../imgs/favicon.png" />

	<link
	rel="stylesheet"
	href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
	/>

    <!-- 🔹 Inclusão dos scripts de rastreamento centralizados -->
    @include('layouts.tracking')

	<!-- Estilos CSS Inline -->
	<style type="text/css">
		.login {
			display: flex;
			flex-wrap: wrap;
		}

		.login-aside {
			background-image: url(/imgs/Login_Logo.png);
			background-size: 100% auto;
			background-position: center;
			background-repeat: no-repeat;
			width: 100%;
			height: auto;
			padding: 0;
			margin-left:  auto;
			margin-right: auto;
			margin-top:  100% auto; /* Adiciona margem ao topo */
			margin-bottom:  100% auto; /* Adiciona margem ao fundo */
			/*border: 1px solid #ccc;*/
			/*box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);*/
		}

		.login-content {
			display: flex;
			justify-content: center;
			align-items: center;
			background-color: #fff;
			height: auto;
			padding: 5%;
			border-radius: 10px;
			border: 2px solid #56B04C;
			box-shadow: 0 0 20px rgba(0, 0, 0, 1.8);
			color: #333;
			max-width: 100%;
			margin-top: 100px; /* Adiciona margem ao topo */
			margin-bottom: 100px; /* Adiciona margem ao fundo */
		}

		.login-form {
			width: 100%;
		}

		@media (min-width: 1025px) {
			.login-aside {
				width: 30%;
				height: 100vh;
				padding: 20px;
				margin-right: 20px;
			}

			.login-content {
				max-width: 400px;
				padding: 40px;
			}

			.login-form {
				width: 360%;
			}
		}

		@media (max-width : 1024px) {
			.stk {
				height: 0px;
				visibility: hidden;
			}

			.login-aside {
				display: none;
			}

			.login-content {
				max-width: 340px;
				padding: 40px;
				border-radius: 10px;
				border: 2px solid #56B04C;
				box-shadow: 0 0 20px rgba(0, 0, 0, 1.8);
				margin-left: auto;
				margin-right: auto;
				margin-top: 20px; /* Adiciona margem ao topo */
				margin-bottom: 20px; /* Adiciona margem ao fundo */

			}

			.login-form {
				width: 360%;
			}

			.login-content::before {
				content: "";
				display: block;
				justify-content: center;
				background-image: url(/imgs/Login_Logo.png);
				background-size: 100% auto;
				background-position: top;
				background-repeat: no-repeat;
				width: 320px;
				height: 340px; /* Ajuste este valor para alterar o tamanho da imagem */
				position: relative;
				top: -70px;
			}

            @media (max-width:1024px){

             /* Login */
             #kt_login{
              transform:translatex(0px) translatey(0px);
             }

             /* Overflow hidden */
             #kt_login .overflow-hidden{
              transform:translatex(0px) translatey(0px);
              padding-top:154px !important;
              padding-bottom:42px !important;
              height:847px;
              margin-right:auto;
             }

             /* Flex center */
             #kt_login .overflow-hidden .flex-center{
              width:273px;
              transform:translatex(5px) translatey(-32px);
             }

             /* Flex */
             #kt_login .overflow-hidden > .d-flex{
              width:271px;
              display:inline-block !important;
              transform:translatex(6px) translatey(-16px);
             }

             /* Division */
             #kt_login_signin_form .pt-lg-0{
              padding-bottom:0px !important;
              padding-right:0px;
              height:41px;
              transform:translatex(82px) translatey(-65px);
             }

             /* Division */
             .flex-root #kt_login .overflow-hidden .flex-center .login-signin #kt_login_signin_form .pt-lg-0{
              width:10% !important;
             }

             /* Login signin */
             .overflow-hidden .flex-center .login-signin{
              width:257px;
             }

            }
		}
          /* Overflow hidden */
          #kt_login .overflow-hidden{
           transform:translatex(0px) translatey(0px);
          }

          /* Division */
          #kt_login_signin_form .pt-lg-0{
           visibility:hidden;
          }

          @media (max-width:1024px){

           /* Overflow hidden */
           #kt_login .overflow-hidden{
            padding-top:0px !important;
           }

          }

          @media (max-width:667px){

           /* Overflow hidden */
           #kt_login .overflow-hidden{
            padding-left:0px !important;
            padding-right:0px !important;
            padding-top:0px !important;
            padding-bottom:0px !important;
           }

           /* Division */
           #kt_login_signin_form .pt-lg-0{
            visibility:hidden;
           }

          }

          @media (min-width:730px){

           /* Division */
           #kt_login_signin_form .pt-lg-0{
            visibility:hidden;
           }

           /* Overflow hidden */
           #kt_login .overflow-hidden{
            padding-top:0px !important;
            padding-left:0px !important;
            padding-right:0px !important;
            padding-bottom:0px !important;
           }

          }

          @media (min-width:768px){

           /* Division */
           #kt_login_signin_form .pt-lg-0{
            visibility:hidden;
           }

          }

          @media (min-width:992px){

           /* Division */
           #kt_login_signin_form .pt-lg-0{
            padding-left:7px;
            visibility:hidden;
           }

           /* Overflow hidden */
           #kt_login .overflow-hidden{
            padding-top:0px !important;
            padding-left:0px !important;
            padding-right:0px !important;
            padding-bottom:0px !important;
            transform:translatex(0px) translatey(0px);
           }

          }

          @media (min-width:1025px){

           /* Division */
           #kt_login_signin_form .pt-lg-0{
            visibility:visible;
           }

          }

          @media (min-width:1200px){

           /* Division */
           #kt_login_signin_form .pt-lg-0{
            visibility:visible;
           }

          }      
	</style>


</head>
<!--end::Head-->
<!--begin::Body-->
<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable page-loading">
	<!--begin::Main-->
	<div class="d-flex flex-column flex-root">
		<!--begin::Login-->
		<div class="login login-1 login-signin-on d-flex flex-column flex-lg-row flex-column-fluid bg-white" id="kt_login">
			<!--begin::Aside-->
			<div class="login-aside d-flex flex-column flex-row-auto stk">

				<div class="pb-13 pt-lg-0 pt-5" style="height: 80px; margin-left: auto; margin-right: auto; margin-top: 80px; margin-bottom: auto; background: #ffffff;">
					<h3 class="font-weight-bolder text-dark font-size-h4 font-size-h1-lg text-center">{{env("APP_DESC")}}</h3>

				</div>
				<!--end::Aside Bottom-->
			</div>
			<!--begin::Aside-->
			<!--begin::Content-->
			<div class="login-content flex-row-fluid d-flex flex-column justify-content-center position-relative overflow-hidden p-7 mx-auto">
				<!--begin::Content body-->
				<div class="d-flex flex-column-fluid flex-center">
					<!--begin::Signin-->
					<div class="login-form login-signin">
						<!--begin::Form-->
						<form method="post" action="/login/request" class="form" novalidate="novalidate" id="kt_login_signin_form">
							@csrf
							<!--begin::Title-->
							<div class="pb-13 pt-lg-0 pt-5">
								<h3 class="font-weight-bolder text-dark font-size-h4 font-size-h1-lg text-center">{{env("APP_NAME")}}</h3>

							</div>
							<!--begin::Title-->
							<!--begin::Form group-->
							@if(session()->has('mensagem_sucesso'))
							<span style="width: 100%;" class="label label-xl label-inline label-light-success">{{ session()->get('mensagem_sucesso') }}</span>
							@endif

							@if(!$sessaoAtiva)
							<input type="hidden" value="{{session('uri')}}" name="uri">
							<div class="form-group">
								<label class="font-size-h6 font-weight-bolder text-dark">Login</label>
								<input autocomplete="off" id="login" class="form-control form-control-solid h-auto py-6 px-6 rounded-lg" type="text" autofocus @if(session('login') != null) value="{{ session('login') }}" @else @if(isset($loginCookie)) value="{{$loginCookie}}" @endif @endif name="login"/>
							</div>
							<!--end::Form group-->
							<!--begin::Form group-->
							<div class="form-group">
								<div class="d-flex justify-content-between mt-n5">
									<label class="font-size-h6 font-weight-bolder text-dark pt-5">Senha</label>
									<a href="#!" id="btn-esqueci" class="text-primary font-size-h6 font-weight-bolder text-hover-primary pt-5" id="kt_login_forgot">Esqueceu a senha?</a>
								</div>
								<input id="senha" class="form-control form-control-solid h-auto py-6 px-6 rounded-lg" type="password" name="senha" autocomplete="off" @if(isset($senhaCookie)) value="{{$senhaCookie}}" @endif />
							</div>
							<label class="checkbox checkbox-inline checkbox-primary">
								<input type="checkbox" name="lembrar" @isset($lembrarCookie) @if($lembrarCookie == true) checked @endif @endif/>
								<span></span>
								<b style="margin-left: 3px;">Lembrar-me</b>
							</label>

							@if(session()->has('mensagem_login'))
							<p class="text-danger">{{ session()->get('mensagem_login') }}</p>
							@endif
							<!--end::Form group-->
							<!--begin::Action-->
							<div class="pb-lg-0 pb-5">
								<button type="submit" id="kt_login_signin_submit" class="btn btn-primary font-weight-bolder font-size-h6 px-8 py-4 my-3 mr-3 btn-block">Login</button>

								<a class="btn btn-success btn-block" href="/cadastro">
									<i class="la la-check"></i>
									Quero cadastrar minha empresa
								</a>

							</div>
							@else
							<div class="text-center pt-2">
								<a id="" href="{{'/' . env('ROTA_INICIAL')}}" class="btn btn-dark btn-block font-weight-bolder font-size-h6 px-8 py-4 my-3">Painel</a>
							</div>
							@endif


							<div class="login-form login-forgot pt-11">
								<!--begin::Form-->
								<a target="_blank" class="txt2" href="http://wa.me/55{{env('CONTATO_SUPORTE')}}">
									<i class="fa fa-whatsapp" aria-hidden="true"></i>
									Suporte {{env("CONTATO_SUPORTE")}}

								</a>
								<!--end::Form-->
							</div>

                            @if(env("APP_ENV") === "demo")
                                <h4 class="mt-2">Demonstração de Login</h4>
                                <button type="button" class="btn btn-success" onclick="doLogin('{{ env('DEMO_SUPER_USER', 'super') }}', '{{ env('DEMO_SUPER_PASS', '12345') }}')">
                                    Super Admin
                                </button>
                                <button type="button" class="btn btn-info" onclick="doLogin('{{ env('DEMO_ADMIN_USER', 'admin') }}', '{{ env('DEMO_ADMIN_PASS', '12345') }}')">
                                    Administrador
                                </button>
                            @endif


							<!--end::Action-->
						</form>

                        {{-- renderiza o modal OTP centralizado --}}
                        {{-- {!! __view_otp_modal() !!} --}}

						<form class="form" method="post" action="/recuperarSenha">
							@csrf


							<div id="div-senha" class="" style="display: none">
								<div class="form-group">
									<label class="font-size-h6 font-weight-bolder text-dark">Email</label>
									<input name="email" class="form-control form-control-solid h-auto py-7 px-12 rounded-lg" type="email" autocomplete="off" />
								</div>

								<div class="text-center pt-2">
									<button id="kt_login_signin_submit" class="btn btn-dark btn-block font-weight-bolder font-size-h6 px-8 py-4 my-3">Enviar</button>
								</div>
							</div>
						</form>
						<!--end::Form-->
					</div>
					<!--end::Signin-->
					<!--begin::Signup-->

				</div>
				<!--end::Content body-->
				<!--begin::Content footer-->
				<div class="d-flex justify-content-lg-start justify-content-center align-items-end py-7 py-lg-0">
					<div class="text-dark-50 font-size-lg font-weight-bolder mr-10">
						<span class="mr-1" id="ano"></span>
						<a href={{env("PORTAL_URL")}} target="_blank" class="text-dark-75 text-hover-primary">{{env("APP_NAME")}}</a>
					</div>

				</div>
				<!--end::Content footer-->
			</div>
			<!--end::Content-->
		</div>
		<!--end::Login-->
	</div>
	<!--end::Main-->
	<!-- <script>var HOST_URL = "/metronic/theme/html/tools/preview";</script> -->
	<!--begin::Global Config(global config for global JS scripts)-->
	<script>var KTAppSettings = { "breakpoints": { "sm": 576, "md": 768, "lg": 992, "xl": 1200, "xxl": 1400 }, "colors": { "theme": { "base": { "white": "#ffffff", "primary": "#3699FF", "secondary": "#E5EAEE", "success": "#1BC5BD", "info": "#8950FC", "warning": "#FFA800", "danger": "#F64E60", "light": "#E4E6EF", "dark": "#181C32" }, "light": { "white": "#ffffff", "primary": "#E1F0FF", "secondary": "#EBEDF3", "success": "#C9F7F5", "info": "#EEE5FF", "warning": "#FFF4DE", "danger": "#FFE2E5", "light": "#F3F6F9", "dark": "#D6D6E0" }, "inverse": { "white": "#ffffff", "primary": "#ffffff", "secondary": "#3F4254", "success": "#ffffff", "info": "#ffffff", "warning": "#ffffff", "danger": "#ffffff", "light": "#464E5F", "dark": "#ffffff" } }, "gray": { "gray-100": "#F3F6F9", "gray-200": "#EBEDF3", "gray-300": "#E4E6EF", "gray-400": "#D1D3E0", "gray-500": "#B5B5C3", "gray-600": "#7E8299", "gray-700": "#5E6278", "gray-800": "#3F4254", "gray-900": "#181C32" } }, "font-family": "Poppins" };</script>
	<!--end::Global Config-->
	<!--begin::Global Theme Bundle(used by all pages)-->
	<script src="/metronic/js/plugins.bundle.js" type="text/javascript"></script>
	<script src="/metronic/js/prismjs.bundle.js" type="text/javascript"></script>
	<script src="/metronic/js/scripts.bundle.js" type="text/javascript"></script>
	<script src="/metronic/js/fullcalendar.bundle.js" type="text/javascript"></script>
	<script src="/metronic/js/file.js" type="text/javascript"></script>

	<script src="/metronic/js/wizard.js" type="text/javascript"></script>
	<script src="/metronic/js/user.js" type="text/javascript"></script>



	<script type="text/javascript" src="/js/jquery.mask.min.js"></script>
	<script type="text/javascript" src="/js/mascaras.js"></script>
	<script src="/metronic/js/select2.js" type="text/javascript"></script>

	<script>

		const ano = document.getElementById("ano");
		const anoAtual = new Date();

		ano.innerHTML = "Build " + anoAtual.getFullYear() + " ©Copyright";

	</script>

	<script type="text/javascript">
		$('#btn-esqueci').click(() => {
			$('#div-senha').css('display', 'block')
			$('#kt_login_signin_form').css('display', 'none')
			$('#btn-esqueci').css('display', 'none')
		})

		function doLogin(login, senha){
			$('#login').val(login)
			$('#senha').val(senha)
			$('#kt_login_signin_form').submit()
		}
	</script>
	<!--end::Page Scripts-->
    <!-- Atendimento e Suporte Online -->
    @include('layouts.support')

    {!! __view_otp_modal(session('show_otp_modal')) !!}
    @if(session('show_otp_modal'))
        <script>
            $(function(){
                $('#otpModal').modal('show');
            });
        </script>
    @endif

</body>
<!--end::Body-->
</html>
