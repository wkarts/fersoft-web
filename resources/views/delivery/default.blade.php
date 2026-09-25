<!DOCTYPE html>
<html>

<head>
	<title>{{$title ?? 'Delivery'}}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="UTF-8" />
	<meta name="keywords" content="Delivery, Pedidos online, Restaurante" />
	<script>
		addEventListener("load", function () {
			setTimeout(hideURLbar, 0);
		}, false);

		function hideURLbar() {
			window.scrollTo(0, 1);
		}
	</script>
	<link rel="stylesheet" href="/cssboot/bootstrap.css">
	<link href="/cssboot/css_slider.css" type="text/css" rel="stylesheet" media="all">
	<link rel="stylesheet" href="/cssboot/style.css" type="text/css" media="all" />
	<link href="/cssboot/font-awesome.min.css" rel="stylesheet">
	
    @if(isset($carrinho) || isset($historico))
	<link href="/css/delivery.css" rel="stylesheet">
	<link href="/css/card.css" rel="stylesheet">
	@endif

	@if(isset($historico))
	<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
	@endif

	<link href="//fonts.googleapis.com/css?family=Lato:100,100i,300,300i,400,400i,700,700i,900,900i&amp;subset=latin-ext" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Barlow+Semi+Condensed:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

	<style type="text/css">
		.cod{
			width: 40px;
			height: 40px;
			text-align: center;
			margin-left: -5px;
		}

        /* --- O BANHO DE LOJA (CSS GLOBAL MODERNIZADO) --- */
        :root {
            --cor-primaria: #ea1d2c; 
            --cor-fundo: #f4f4f5; 
            --cor-texto-escuro: #1e293b;
            --cor-texto-claro: #64748b;
            --borda-suave: 12px;
        }

        body {
            background-color: var(--cor-fundo) !important;
            font-family: 'Lato', sans-serif !important;
            color: var(--cor-texto-escuro);
        }

        h1, h2, h3, h4, h5 {
            color: var(--cor-texto-escuro) !important;
            font-weight: 700 !important;
            letter-spacing: -0.3px;
        }

        input[type="text"], input[type="tel"], input[type="number"], select, textarea {
            border-radius: var(--borda-suave) !important;
            border: 1px solid #cbd5e1 !important;
            padding: 14px 16px !important;
            font-size: 16px !important;
            box-shadow: none !important;
            background-color: #ffffff !important;
            width: 100%;
            transition: border-color 0.2s ease;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--cor-primaria) !important;
            outline: none !important;
        }

        .btn {
            border-radius: 10px !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px;
            padding: 12px 20px !important;
            text-transform: uppercase;
            font-size: 14px !important;
            transition: all 0.2s ease;
        }

        .btn-success, .btn-primary {
            background-color: #10b981 !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3) !important;
        }

        .btn-warning {
            background-color: #f59e0b !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3) !important;
        }

        .btn-danger {
            background-color: var(--cor-primaria) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 4px 6px -1px rgba(234, 29, 44, 0.3) !important;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .card-moderno {
            background-color: #ffffff !important;
            border-radius: 16px !important;
            border: 1px solid #f1f5f9 !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05) !important;
            padding: 20px;
            margin-bottom: 20px;
        }

        footer {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border-top: none !important;
        }
        
        .top-bar {
            background-color: #ffffff !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;
            border-bottom: none !important;
        }
	</style>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>
	<script>
		window.OneSignal = window.OneSignal || [];
		OneSignal.push(function() {
			OneSignal.init({
				appId: "<?php echo env('ONE_SIGNAL_APP_ID'); ?>",
			});
		});
	</script>
	<script type="text/javascript">
		window.OneSignal = window.OneSignal || [];
		OneSignal.push(function() {
			let path = window.location.protocol + '//' + window.location.host
			let user = $('#user').val() ? $('#user').val() : 0;

			OneSignal.getUserId().then(function(userId) {
				let js = {
					user: user,
					token: userId
				}
				$.get(path + '/autenticar/saveTokenWeb', js)
				.done((res) => { console.log(res) })
				.fail((err) => { console.log(err) })
			});
		});
	</script>
</head>

<body>
	<header id="home">
		<div class="top-bar py-2">
			<div class="container">
				<div class="row middle-flex">
					<div class="col-xl-7 col-md-5 top-social-agile mb-md-0 mb-1 text-lg-left text-center">
						<div class="row">
							<div class="col-xl-3 col-6 header-top_w3layouts">
								<p class="text-da">
									<span class="fa fa-map-marker mr-2"></span>@if(isset($config)) {{$config->rua}}, {{$config->numero}} - {{$config->bairro}} @endif
								</p>
							</div>
							<div class="col-xl-3 col-6 header-top_w3layouts">
								<p class="text-da" style="color: var(--cor-primaria) !important; font-weight: bold;">
									<span class="fa fa-phone mr-2"></span>+55 @if(isset($config)){{$config->telefone}}@endif
								</p>
							</div>
							<div class="col-xl-6"></div>
						</div>
					</div>
					<div class="col-xl-5 col-md-7 top-social-agile text-md-right text-center pr-sm-0 mt-md-0 mt-2">
						<div class="row middle-flex">
							<div class="col-lg-5 col-4 top-w3layouts p-md-0 text-right">
								
								@php
									$temUsuarioAntigo = session()->has('cliente_log') && isset(session('cliente_log')['id']);
									$temWhatsApp = session()->has('telefone_cliente');
								@endphp

								@if($temUsuarioAntigo)
									<input type="hidden" value="{{session('cliente_log')['id']}}" id="user">
								@endif

								@if($temUsuarioAntigo || $temWhatsApp)
									<a href="/delivery-logoff" class="btn btn-danger text-uppercase">
                                        <span class="fa fa-sign-out mr-2"></span>Sair
                                    </a>
								@else
									<a href="/autenticar" class="btn btn-primary text-uppercase">
										<span class="fa fa-sign-in mr-2"></span>Entrar
                                    </a>
								@endif
							</div>
							<div class="col-lg-7 col-8 social-grid-w3">
								<ul class="top-right-info">
									<li><p></p></li>
									@if(isset($config))
										@if($config->link_face)
										<li class="facebook-w3"><a href="{{$config->link_face}}"><span class="fa fa-facebook-f"></span></a></li>
										@endif
										@if($config->link_twiteer)
										<li class="twitter-w3"><a href="{{$config->link_twiteer}}"><span class="fa fa-twitter"></span></a></li>
										@endif
										@if($config->link_google)
										<li class="google-w3"><a href="{{$config->link_google}}"><span class="fa fa-google-plus"></span></a></li>
										@endif
										@if($config->link_instagram)
										<li class="dribble-w3"><a href="{{$config->link_instagram}}"><span class="fa fa-instagram"></span></a></li>
										@endif
									@endif
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>
	<div class="main-top py-1">
		<div class="container">
			<div class="nav-content" style="display: flex; align-items: center; justify-content: space-between;">
				<h1 style="margin: 0; padding: 0; z-index: 10;">
                    <a id="logo" class="logo" href="/cardapio" style="display: flex; align-items: center; text-decoration: none;">
                        @if(isset($config->logo) && $config->logo != '')
                            <img src="/delivery/logos/{{ $config->logo }}" alt="Logo" style="max-height: 50px; width: auto; position: static !important; display: block; margin-right: 12px; float: none !important;">
                        @else
                            <img src="/images/logo.png" alt="Logo" style="max-height: 50px; width: auto; position: static !important; display: block; margin-right: 12px; float: none !important;">
                        @endif

                        @if(isset($config))
                        <span style="color: var(--cor-texto-escuro); font-size: 22px; font-weight: 800; line-height: 1; letter-spacing: -0.5px; position: static !important;">
                            {{ $config->nome }}
                        </span>
                        @endif
                    </a>
                </h1>
				<div class="nav_web-dealingsls">
					<nav>
						<label for="drop" class="toggle">Menu</label>
						<input type="checkbox" id="drop" />
						<ul class="menu">
                            <li><a href="/cardapio">CARDÁPIO</a></li>
                            <li><a href="/carrinho">CARRINHO</a></li>
                            <li><a href="/carrinho/meus-pedidos">MEUS PEDIDOS</a></li>
                            
                            <li>
                                  @php
                                      // Tenta pegar o ID do pedido da variável atual ou da sessão recente do cliente
                                      $pedidoAtivoId = $pedido->id ?? session('ultimo_pedido_id') ?? null;
                                  @endphp

                                  @if($pedidoAtivoId)
                                      <a href="/carrinho/finalizado/{{ $pedidoAtivoId }}" style="background-color: #fff1f2; color: #ea1d2c !important; border-radius: 8px; padding: 8px 15px !important; font-weight: 800; border: 1px solid #fecdd3; display: inline-flex; align-items: center; gap: 6px; margin-left: 10px;">
                                          <i class="fa fa-motorcycle" style="font-size: 16px;"></i> ACOMPANHAR PEDIDO
                                      </a>
                                  @else
                                      <a href="/carrinho/meus-pedidos" style="background-color: #fff1f2; color: #ea1d2c !important; border-radius: 8px; padding: 8px 15px !important; font-weight: 800; border: 1px solid #fecdd3; display: inline-flex; align-items: center; gap: 6px; margin-left: 10px;">
                                          <i class="fa fa-motorcycle" style="font-size: 16px;"></i> ACOMPANHAR PEDIDO
                                      </a>
                                  @endif
                              </li>
                        </ul>
					</nav>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="" id="log" value="{{ session()->has('cliente_log') && isset(session('cliente_log')['id']) ? session('cliente_log')['id'] : 0 }}">

    @yield('content')

	<footer class="mt-5">
		<div class="container py-xl-4">
			<div class="row footer-top">
				<div class="col-lg-6 footer-grid_section_1its footer-text">
					<h2>
                      <a class="logo text-wh" href="/cardapio" style="display: flex !important; align-items: center !important; gap: 10px !important; text-decoration: none !important; flex-wrap: nowrap !important;">
                          @if(isset($config->logo) && $config->logo != '')
                              <img src="/delivery/logos/{{ $config->logo }}" alt="Logo" style="max-height: 45px; width: auto; object-fit: contain;">
                          @else
                              <img src="/images/logo.png" alt="Logo" style="max-height: 45px; width: auto; object-fit: contain;">
                          @endif

                          @if(isset($config))
                          <span style="color: #ffffff; font-size: 20px; font-weight: bold; line-height: 1;">
                              {{ $config->nome }}
                          </span> 
                          @endif
                      </a>
                    </h2>
				</div>
				<div class="col-lg-6 footer-grid_section_1its my-lg-0 my-sm-6 my-6">
					<div class="footer-title">
						<h3 style="color: #fff !important;">Contato</h3>
					</div>
					<div class="footer-text mt-4">
						@if(isset($config))
						<p style="color: #cbd5e1;">Endereço: {{$config->rua}}, {{$config->numero}} - {{$config->bairro}}</p>
						<p class="my-2" style="color: #cbd5e1;">Telefone: +55 {{$config->telefone}}</p>
						@endif
					</div>
				</div>
			</div>
		</div>
	</footer>

	@if(View::exists('layouts.footer_company'))
		@include('layouts.footer_company')
	@endif

	<a href="#home" class="move-top text-center" style="background-color: var(--cor-primaria); border-radius: 50%;">
		<span class="fa fa-level-up" aria-hidden="true" style="color: white;"></span>
	</a>
	<?php $path = env('PATH_URL')."/";?>
	<script type="text/javascript">
		const path = "/";
	</script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	
	@if(isset($historico))
	<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
	@else
	<script type="text/javascript" src="/js/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="/js/jquery.mask.min.js"></script>
	<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
	<script type="text/javascript">
		$('#quantidade').mask('00', {reverse: true});
		$('#telefone').mask('00 00000-0000', {reverse: true});
		$(".qtd").mask('00', {reverse: true});
		$('#troco_para').mask('000000,00', {reverse: true});
		$('#cod1').mask('0', {reverse: true});
		$('#cod2').mask('0', {reverse: true});
		$('#cod3').mask('0', {reverse: true});
		$('#cod4').mask('0', {reverse: true});
		$('#cod5').mask('0', {reverse: true});
		$('#cod6').mask('0', {reverse: true});
		$('#cpf').mask('000.000.000-00', {reverse: true});
	</script>
	@endif

	@if(isset($acompanhamento))
	<script src="/jsd/acompanhamento.js?v=delivery-20260925-2" type="text/javascript"></script>
	@endif

	@if(isset($acompanhamentoPizza))
	<script src="/jsd/acompanhamentoPizza.js?v=delivery-20260925-2" type="text/javascript"></script>
	@endif

	@if(isset($carrinho))
	<script src="/jsd/carrinho.js" type="text/javascript"></script>
	@endif

	@if(isset($forma_pagamento))
	<script type="text/javascript" src="https://stc.pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js"></script>
	<script src="/jsd/card.js" type="text/javascript"></script>
	<script src="/jsd/forma_pagamento.js" type="text/javascript"></script>

	<script type="text/javascript">
		new Card({
			form: document.querySelector('form'),
			container: '.card-wrapper',
			width: 300,
			placeholders: {
				number: '•••• •••• •••• ••••',
				name: 'Nome Completo',
				expiry: '••/••••',
				cvc: 'CVC'
			},
			debug: true,
			formSelectors: {
				numberInput: 'input#number',
				expiryInput: 'input#validade',
				cvcInput: 'input#cvc',
				nameInput: 'input#nome'
			},
		});
	</script>
	@endif

	<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
	@if(isset($cadastro_ative))
	<script src="/jsd/cadastro_ative.js?v=delivery-20260925-2" type="text/javascript"></script>
	@endif

	@if(isset($pizzaJs))
	<script src="/jsd/pizza.js" type="text/javascript"></script>
	@endif

	@if(isset($login_ative))
	<script src="/jsd/login_ative.js?v=delivery-20260925-2" type="text/javascript"></script>
	@endif

	@if(isset($pass))
	<script src="/jsd/pass.js" type="text/javascript"></script>
	@endif

	@if(isset($mapaJs))
	<script src="https://maps.googleapis.com/maps/api/js?key={{env('API_KEY_MAPS')}}" async defer></script>
	@endif

	@if(isset($tokenJs))
	<script src="https://www.gstatic.com/firebasejs/7.9.1/firebase-app.js"></script>
	@endif

	<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js" integrity="sha512-AA1Bzp5Q0K1KanKKmvN/4d3IRKVlv9PYgwFPvm32nPO6QS8yH1HO7LbgB1pgiOxPtfeg5zEn2ba64MUcqJx6CA==" crossorigin="anonymous"></script>
</body>

</html>