{{-- Cabeçalho operacional original; mantém IDs, ações e integrações existentes. --}}
        <header class="ui-masthead">
            <button type="button" class="ui-icon-button ui-mobile-trigger" data-ui-menu-open aria-label="Abrir navegação" aria-controls="ui-sidebar" aria-expanded="false">☰</button>
            <div class="ui-mobile-brand">
                <a href="/graficos" class="brand-logo">
                    @if($logo != "" && env("LOGOCLIENTE") == 1)
                        <img width="120" height="45" alt="Logo" src="/logos/{{$logo}}" />
                    @else
                        <img width="100" alt="Logo" src="{{ $appearanceAssets['menu_logo'] ?? asset('imgs/slym.png') }}" @if(($appearanceAssets['menu_logo'] ?? asset('imgs/slym.png')) === asset('imgs/slym.png')) class="ui-brand-default-logo" @endif />
                    @endif
                </a>


            </div>
            <div class="ui-operational-actions">
            <div id="ui-actions" class="ui-actions">

                <div class="container-fluid d-flex align-items-stretch justify-content-between div-topo">
                    <div id="ui-actions-menu-wrapper" class="header-menu-wrapper header-menu-wrapper-left top-tour">
                        <div id="ui-actions-menu" class="header-menu header-menu-mobile  header-menu-layout-default">
                            <ul class="menu-nav">

                                    <!-- <li class="menu-item menu-item-submenu menu-item-rel menu-item-active" data-menu-toggle="click" aria-haspopup="true">

                                        <a href="/pedidos" class="label label-xl label-inline label-light-primary">
                                            Pedidos Mesa/Comanda: <strong id="pedidos-aberto">x0</strong>
                                        </a>
                                    </li> -->
                                    @if(isset(session('user_logged')['log_id']))
                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active" data-menu-toggle="click" aria-haspopup="true">
                                            <a href="/configNF" class="label label-xl label-inline @if($tema == 1) label-light-danger @else label-danger @endif">
                                                LOG <i class="la la-warning text-danger"></i>
                                            </a>
                                        </li>
                                    @endif

                                    @if(!session('tipo_contador'))
                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active ui-header-company" data-menu-toggle="click" aria-haspopup="true">


                                            <a class="label label-xl label-inline label-primary info-plan mr-1">
                                                <i class="la la-info text-white"></i>
                                            </a>

                                            <a href="/configNF" class="label label-xl label-inline @if($tema == 1) label-success @else label-success @endif">
                                                <span class="ui-company-label">EMPRESA ATIVA</span><strong class="ui-company-name" title="{{ session('user_logged')['empresa_nome'] }}">{{session('user_logged')['empresa_nome']}}</strong>
                                            </a>
                                        </li>

                                        <li id="ambiente-tour" class="menu-item menu-item-submenu menu-item-rel menu-item-active ui-header-environment" data-menu-toggle="click" aria-haspopup="true">
                                            <a href="/configNF" class="label label-xl label-inline @if($tema == 1) label-info @else label-info @endif">
                                                <i class="la la-check ui-environment-check" aria-hidden="true"></i><span>{{session('user_logged')['ambiente']}}</span>
                                            </a>
                                        </li>

                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active d-lg-none" data-menu-toggle="click" aria-haspopup="true">
                                            <a href="/app-versions" class="label label-xl label-inline @if($tema == 1) label-light-dark @else label-dark @endif">
                                                Versão: {{ optional($appVersionCurrent)->version ?? 'N/A' }}
                                                @include('app_versions.installed_identity')
                                            </a>
                                        </li>
                                    @endif

                                    @if(session('tipo_contador'))
                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active" data-menu-toggle="click" aria-haspopup="true">

                                            <!-- <a class="label label-xl label-inline label-primary info-plan mr-1">
                                                <i class="la la-info text-white"></i>
                                            </a> -->

                                            <a data-toggle="modal" href="#!" data-target="#modal-empresa-contador" title="Empresa selecionada: {{ session('empresa_selecionada') ? session('empresa_selecionada')['nome'] : '--' }}" class="btn btn-success pull-right @if($tema == 1) label-success @else label-success @endif btn-lg">
                                                Empresa selecionada: {{ session('empresa_selecionada') ? session('empresa_selecionada')['nome'] : '--' }}
                                            </a>
                                        </li>
                                    @endif

                                    <!-- @if(!$upgrade)
                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active" data-menu-toggle="click" aria-haspopup="true">
                                            <span class="label label-xl label-inline @if($tema == 1) label-light-primary @else label-primary @endif">
												<i style="color: #111; font-size: 20px;" class="fa fa-clock"></i>
												<strong id="timer" style="margin-left: 5px;">00:00:00</strong>
											</span>
										</li>
										@endif -->
                                    @if(!session('tipo_contador'))
                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active ui-header-theme" data-menu-toggle="click" aria-haspopup="true">
                                            <a href="{{ route('ui.themes.index') }}" class="label label-xl label-inline @if($tema == 1) label-warning @else label-warning @endif" aria-label="Tema" title="Tema"><i class="la la-palette" aria-hidden="true"></i><span>Tema</span></a>
                                        </li>
                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active ui-header-whatsapp" data-menu-toggle="click" aria-haspopup="true">
                                            {!! __view_connect_api_whatsapp_modal_button() !!}
                                        </li>
                                    @endif

                                    @if(session('tipo_contador'))
                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active ui-header-theme">
                                            <a href="{{ route('ui.themes.index') }}" class="label label-xl label-inline" aria-label="Tema" title="Tema"><i class="la la-palette" aria-hidden="true"></i><span>Tema</span></a>
                                        </li>
                                    @endif

                                    @if(!session('tipo_contador'))
                                        <li class="menu-item ui-header-pdv">
                                            <a class="btn btn-success pdv-tour" href="/frenteCaixa">
                                                <i class="la la-barcode"></i>
                                                PDV
                                            </a>
                                        </li>
                                    @endif

                                    @if($contrato == 0)
                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active" data-menu-toggle="click" aria-haspopup="true">
                                            <a href="/assinarContrato" class="label label-xl label-inline @if($tema == 1) label-light-danger @else label-danger @endif">
                                                <i class="la la-file-contract text-danger"></i>
                                                Assinar contrato
                                            </a>
                                        </li>
                                    @endif

                                    @if($upgrade)
                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active" data-menu-toggle="click" aria-haspopup="true">
                                            <a href="/payment" class="label label-xl label-inline @if($tema == 1) label-light-success @else label-success @endif">
                                                <i class="la la-money text-success"></i>
                                                Upgrade
                                            </a>
                                        </li>
                                    @endif

                                    @if(empresaComFilial() && sizeof(getLocaisUsarioLogado()) > 1)
                                        <li class="menu-item menu-item-submenu menu-item-rel ui-header-unit" data-menu-toggle="click" aria-haspopup="true">
                                            <label for="filial_id_top" class="ui-unit-caption">Unidade</label>
                                            <div class="d-flex align-items-center">
                                                <div class="label label-xl label-inline @if($tema == 1) label-light-primary @else label-primary @endif" style="width: 100%;">
                                                    {!! __view_locais_select_menu_superior() !!}
                                                </div>
                                            </div>
                                        </li>
                                    @endif

                                    @if($video_url != "")
                                        <li class="menu-item menu-item-submenu menu-item-rel menu-item-active y-mobile" data-menu-toggle="click" aria-haspopup="true" style="display: none">
                                            <a style="width: 100%" target="_blank" href="{{$video_url}}" class="btn btn-light-info">
                                                <i class="la la-video"></i>
                                                Video Ajuda

                                            </a>
                                        </li>
                                    @endif

                                    <li class="menu-item menu-item-submenu menu-item-rel menu-item-active y-mobile" data-menu-toggle="click" aria-haspopup="true" style="display: none">
                                        <span class="kt-header__topbar-welcome kt-hidden-mobile" style="margin-left: 3px; font-size: 14px;">Endereço do IP: <span style="font-weight: bold;" class="text-success text-left">{{ $ultimoAcesso != null ? $ultimoAcesso->ip_address : '--' }}</span></span>
                                    </li>

                            </ul>
                        </div>
                    </div>
                </div>

                <div class="topbar div-topo">

                    <!--begin: Search -->
                    <div class="dropdown">
                        <!--begin::Toggle-->

                        <!-- Alertas do Super -->
                        <div class="topbar-item notifica-super d-none" data-toggle="dropdown" data-offset="10px,0px">
                            <div class="btn btn-icon btn-clean btn-dropdown btn-lg mr-1 pulse-dark">
									<span class="svg-icon svg-icon-xl svg-icon-primary">
										<!--begin::Svg Icon | path:/metronic/theme/html/demo1/dist/assets/media/svg/icons/Code/Compiling.svg-->
										<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
											<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
												<path d="M17,12 L18.5,12 C19.3284271,12 20,12.6715729 20,13.5 C20,14.3284271 19.3284271,15 18.5,15 L5.5,15 C4.67157288,15 4,14.3284271 4,13.5 C4,12.6715729 4.67157288,12 5.5,12 L7,12 L7.5582739,6.97553494 C7.80974924,4.71225688 9.72279394,3 12,3 C14.2772061,3 16.1902508,4.71225688 16.4417261,6.97553494 L17,12 Z" fill="#000000"/>
												<rect fill="#000000" opacity="0.3" x="10" y="16" width="4" height="4" rx="2"/>
											</g>
										</svg>
                                        <!--end::Svg Icon-->
									</span>
                                <span class="pulse-ring"></span>
                            </div>
                        </div>

                        <div class="dropdown-menu p-0 m-0 dropdown-menu-right dropdown-menu-anim-up dropdown-menu-lg ui-notification-panel">
                            <form>
                                <!--begin::Header-->
                                <div class="d-flex flex-column pt-12 bgi-size-cover bgi-no-repeat rounded-top">

                                    <!--begin::Title-->

                                    <h4 class="d-flex flex-center rounded-top">
                                        <span class="text-primary">Notificações</span>
                                        <!-- <span class="btn btn-text btn-light-primary btn-sm font-weight-bold btn-font-md ml-2"><strong class="notif-cont"></strong> novas</span> -->
                                    </h4>

                                    <!--end::Title-->
                                    <!--begin::Tabs-->

                                    <!--end::Tabs-->
                                </div>

                                <div class="tab-content">
                                    <!--begin::Tabpane-->
                                    <div class="tab-pane active show p-8" id="topbar_notifications_super" role="tabpanel">
                                        <!--begin::Scroll-->
                                        <div class="scroll pr-7 mr-n7 ps notifica-rows" data-scroll="true" data-height="300" data-mobile-height="200" style="height: 300px; overflow: hidden;">

                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Fim Alertas do Super -->
                    </div>
                    <div class="dropdown">

                        @if(sizeof($alertas) > 0)
                            <!-- <div class="topbar-item notifica-tour" data-toggle="dropdown" data-offset="10px,0px">
								<div class="btn btn-icon btn-clean btn-dropdown btn-lg mr-1 pulse-dark">
									<span class="svg-icon svg-icon-xl svg-icon-danger">

										<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
											<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
												<rect x="0" y="0" width="24" height="24"/>
												<path d="M11.6734943,8.3307728 L14.9993074,6.09979492 L14.1213255,5.22181303 C13.7308012,4.83128874 13.7308012,4.19812376 14.1213255,3.80759947 L15.535539,2.39338591 C15.9260633,2.00286161 16.5592283,2.00286161 16.9497526,2.39338591 L22.6066068,8.05024016 C22.9971311,8.44076445 22.9971311,9.07392943 22.6066068,9.46445372 L21.1923933,10.8786673 C20.801869,11.2691916 20.168704,11.2691916 19.7781797,10.8786673 L18.9002333,10.0007208 L16.6692373,13.3265608 C16.9264145,14.2523264 16.9984943,15.2320236 16.8664372,16.2092466 L16.4344698,19.4058049 C16.360509,19.9531149 15.8568695,20.3368403 15.3095595,20.2628795 C15.0925691,20.2335564 14.8912006,20.1338238 14.7363706,19.9789938 L5.02099894,10.2636221 C4.63047465,9.87309784 4.63047465,9.23993286 5.02099894,8.84940857 C5.17582897,8.69457854 5.37719743,8.59484594 5.59418783,8.56552292 L8.79074617,8.13355557 C9.76799113,8.00149544 10.7477104,8.0735815 11.6734943,8.3307728 Z" fill="#000000"/>
												<polygon fill="#000000" opacity="0.3" transform="translate(7.050253, 17.949747) rotate(-315.000000) translate(-7.050253, -17.949747) " points="5.55025253 13.9497475 5.55025253 19.6640332 7.05025253 21.9497475 8.55025253 19.6640332 8.55025253 13.9497475"/>
											</g>
										</svg>
									</span>
									<span class="pulse-ring"></span>
								</div>
							</div> -->
                            <button type="button" class="topbar-item notifica-tour ui-notification-trigger" data-toggle="dropdown" data-offset="10px,0px" data-ui-alerts-trigger aria-label="Alertas" aria-haspopup="true" aria-expanded="false" title="Alertas">
                                <i class="la la-bell" aria-hidden="true"></i>
                                <span class="ui-notification-count">{{ sizeof($alertas) }}</span>
                            </button>
                        @endif
                        <div class="dropdown-menu p-0 m-0 dropdown-menu-right dropdown-menu-anim-up dropdown-menu-lg" data-ui-alerts-panel>
                            <form>
                                <div class="d-flex flex-column pt-12 bgi-size-cover bgi-no-repeat rounded-top">

                                    @if(sizeof($alertas) > 0)
                                        <h4 class="d-flex flex-center rounded-top">
                                            <span class="text-white">Notificações</span>
                                            <span class="btn btn-text btn-success btn-sm font-weight-bold btn-font-md ml-2">{{sizeof($alertas)}} novas</span>
                                        </h4>
                                    @endif

                                </div>
                                <!--end::Header-->
                                <!--begin::Content-->
                                <div class="tab-content">
                                    <!--begin::Tabpane-->
                                    <div class="tab-pane active show p-8" id="topbar_notifications_company" role="tabpanel">
                                        <div class="scroll pr-7 mr-n7 ps" data-scroll="true" data-height="300" data-mobile-height="200" style="height: 300px; overflow: hidden;">

                                            @if(sizeof($alertas) > 0)
                                                @foreach($alertas as $a)
                                                    <div class="d-flex align-items-center mb-6">
                                                        <!--begin::Symbol-->
                                                        <a href="{{$a['link']}}">
                                                            @if($a['titulo'] == 'Alerta validade')
                                                                <div class="symbol symbol-40 symbol-light-warning mr-5">
															<span class="symbol-label">
																<span class="svg-icon svg-icon-lg svg-icon-warning">
																	<!--begin::Svg Icon | path:/metronic/theme/html/demo1/dist/assets/media/svg/icons/Communication/Write.svg-->
																	<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
																		<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
																			<rect x="0" y="0" width="24" height="24"/>
																			<path d="M22,13.9146471 L22,19 C22,20.1045695 21.1045695,21 20,21 L14,21 C14,19.8954305 13.1045695,19 12,19 C10.8954305,19 10,19.8954305 10,21 L4,21 C2.8954305,21 2,20.1045695 2,19 L2,7 L22,7 L22,11.0853529 C21.8436105,11.0300771 21.6753177,11 21.5,11 C20.6715729,11 20,11.6715729 20,12.5 C20,13.3284271 20.6715729,14 21.5,14 C21.6753177,14 21.8436105,13.9699229 22,13.9146471 Z M9,17 C11.209139,17 13,15.209139 13,13 C13,10.790861 11.209139,9 9,9 C6.790861,9 5,10.790861 5,13 C5,15.209139 6.790861,17 9,17 Z M18,18 C18.5522847,18 19,17.5522847 19,17 C19,16.4477153 18.5522847,16 18,16 C17.4477153,16 17,16.4477153 17,17 C17,17.5522847 17.4477153,18 18,18 Z M5,21 C5.55228475,21 6,20.5522847 6,20 C6,19.4477153 5.55228475,19 5,19 C4.44771525,19 4,19.4477153 4,20 C4,20.5522847 4.44771525,21 5,21 Z" fill="#000000"/>
																			<path d="M19.5954729,5.83476152 L4.60883918,4.07162814 C4.23525261,4.02767678 3.86860536,4.19709197 3.65994764,4.51007855 L2,7 C15.3333333,7 22,7 22,7 C22,7 22,7 22,7 L22,7 C21.352294,6.35229396 20.5051936,5.94178748 19.5954729,5.83476152 Z" fill="#000000" opacity="0.3"/>
																		</g>
																	</svg>
                                                                    <!--end::Svg Icon-->
																</span>
															</span>
                                                                </div>
                                                            @elseif($a['titulo'] == 'Validade próxima')
                                                                <div class="symbol symbol-40 symbol-light-danger mr-5">
															<span class="symbol-label">
																<span class="svg-icon svg-icon-lg svg-icon-danger">
																	<!--begin::Svg Icon | path:/metronic/theme/html/demo1/dist/assets/media/svg/icons/Communication/Write.svg-->
																	<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
																		<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
																			<rect x="0" y="0" width="24" height="24"/>
																			<polygon fill="#000000" opacity="0.3" points="12 20.6599888 9.46440699 20.6354368 7.31805655 19.2852462 5.19825383 17.8937466 4.12259707 15.5974894 3.09160702 13.2808335 3.42815736 10.7675551 3.81331204 8.26126488 5.45521712 6.32891335 7.13423264 4.4287182 9.5601992 3.69080156 12 3 14.4398008 3.69080156 16.8657674 4.4287182 18.5447829 6.32891335 20.186688 8.26126488 20.5718426 10.7675551 20.908393 13.2808335 19.8774029 15.5974894 18.8017462 17.8937466 16.6819434 19.2852462 14.535593 20.6354368"/>
																			<circle fill="#000000" opacity="0.3" cx="8.5" cy="13.5" r="1.5"/>
																			<circle fill="#000000" opacity="0.3" cx="13.5" cy="7.5" r="1.5"/>
																			<circle fill="#000000" opacity="0.3" cx="14.5" cy="15.5" r="1.5"/>
																		</g>
																	</svg>
                                                                    <!--end::Svg Icon-->
																</span>
															</span>
                                                                </div>
                                                            @elseif($a['titulo'] == 'Alerta contas')
                                                                <div class="symbol symbol-40 symbol-light-info mr-5">
															<span class="symbol-label">
																<span class="svg-icon svg-icon-lg svg-icon-info">
																	<!--begin::Svg Icon | path:/metronic/theme/html/demo1/dist/assets/media/svg/icons/Communication/Write.svg-->
																	<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
																		<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
																			<rect x="0" y="0" width="24" height="24"/>
																			<path d="M2,6 L21,6 C21.5522847,6 22,6.44771525 22,7 L22,17 C22,17.5522847 21.5522847,18 21,18 L2,18 C1.44771525,18 1,17.5522847 1,17 L1,7 C1,6.44771525 1.44771525,6 2,6 Z M11.5,16 C13.709139,16 15.5,14.209139 15.5,12 C15.5,9.790861 13.709139,8 11.5,8 C9.290861,8 7.5,9.790861 7.5,12 C7.5,14.209139 9.290861,16 11.5,16 Z" fill="#000000" opacity="0.3" transform="translate(11.500000, 12.000000) rotate(-345.000000) translate(-11.500000, -12.000000) "/>
																			<path d="M2,6 L21,6 C21.5522847,6 22,6.44771525 22,7 L22,17 C22,17.5522847 21.5522847,18 21,18 L2,18 C1.44771525,18 1,17.5522847 1,17 L1,7 C1,6.44771525 1.44771525,6 2,6 Z M11.5,16 C13.709139,16 15.5,14.209139 15.5,12 C15.5,9.790861 13.709139,8 11.5,8 C9.290861,8 7.5,9.790861 7.5,12 C7.5,14.209139 9.290861,16 11.5,16 Z M11.5,14 C12.6045695,14 13.5,13.1045695 13.5,12 C13.5,10.8954305 12.6045695,10 11.5,10 C10.3954305,10 9.5,10.8954305 9.5,12 C9.5,13.1045695 10.3954305,14 11.5,14 Z" fill="#000000"/>
																		</g>
																	</svg>
                                                                    <!--end::Svg Icon-->
																</span>
															</span>
                                                                </div>
                                                            @elseif($a['titulo'] == 'Receber')
                                                                <div class="symbol symbol-40 symbol-light-success mr-5">
															<span class="symbol-label">
																<span class="svg-icon svg-icon-lg svg-icon-success">
																	<!--begin::Svg Icon | path:/metronic/theme/html/demo1/dist/assets/media/svg/icons/Communication/Write.svg-->
																	<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
																		<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
																			<rect x="0" y="0" width="24" height="24"/>
																			<path d="M2,6 L21,6 C21.5522847,6 22,6.44771525 22,7 L22,17 C22,17.5522847 21.5522847,18 21,18 L2,18 C1.44771525,18 1,17.5522847 1,17 L1,7 C1,6.44771525 1.44771525,6 2,6 Z M11.5,16 C13.709139,16 15.5,14.209139 15.5,12 C15.5,9.790861 13.709139,8 11.5,8 C9.290861,8 7.5,9.790861 7.5,12 C7.5,14.209139 9.290861,16 11.5,16 Z" fill="#000000" opacity="0.3" transform="translate(11.500000, 12.000000) rotate(-345.000000) translate(-11.500000, -12.000000) "/>
																			<path d="M2,6 L21,6 C21.5522847,6 22,6.44771525 22,7 L22,17 C22,17.5522847 21.5522847,18 21,18 L2,18 C1.44771525,18 1,17.5522847 1,17 L1,7 C1,6.44771525 1.44771525,6 2,6 Z M11.5,16 C13.709139,16 15.5,14.209139 15.5,12 C15.5,9.790861 13.709139,8 11.5,8 C9.290861,8 7.5,9.790861 7.5,12 C7.5,14.209139 9.290861,16 11.5,16 Z M11.5,14 C12.6045695,14 13.5,13.1045695 13.5,12 C13.5,10.8954305 12.6045695,10 11.5,10 C10.3954305,10 9.5,10.8954305 9.5,12 C9.5,13.1045695 10.3954305,14 11.5,14 Z" fill="#000000"/>
																		</g>
																	</svg>
                                                                    <!--end::Svg Icon-->
																</span>
															</span>
                                                                </div>
                                                            @elseif($a['titulo'] == 'Alerta estoque')
                                                                <div class="symbol symbol-40 symbol-light-dark mr-5">
															<span class="symbol-label">
																<span class="svg-icon svg-icon-lg svg-icon-dark">
																	<!--begin::Svg Icon | path:/metronic/theme/html/demo1/dist/assets/media/svg/icons/Communication/Write.svg-->
																	<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
																		<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
																			<rect x="0" y="0" width="24" height="24"/>
																			<path d="M8,4 C8.55228475,4 9,4.44771525 9,5 L9,17 L18,17 C18.5522847,17 19,17.4477153 19,18 C19,18.5522847 18.5522847,19 18,19 L9,19 C8.44771525,19 8,18.5522847 8,18 C7.44771525,18 7,17.5522847 7,17 L7,6 L5,6 C4.44771525,6 4,5.55228475 4,5 C4,4.44771525 4.44771525,4 5,4 L8,4 Z" fill="#000000" opacity="0.3"/>
																			<rect fill="#000000" opacity="0.3" x="11" y="7" width="8" height="8" rx="4"/>
																			<circle fill="#000000" cx="8" cy="18" r="3"/>
																		</g>
																	</svg>
                                                                    <!--end::Svg Icon-->
																</span>
															</span>
                                                                </div>
                                                            @endif
                                                        </a>

                                                        <div class="d-flex flex-column font-weight-bold">
                                                            <a href="{{$a['link']}}" class="text-dark-75 text-hover-primary mb-1 font-size-lg">{{$a['titulo']}}</a>
                                                            <span class="text-muted">{{$a['msg']}}</span>
                                                        </div>

                                                    </div>
                                                @endforeach
                                            @endif

                                            <div class="ps__rail-x" style="left: 0px; bottom: 0px;">
                                                <div class="ps__thumb-x" tabindex="0" style="left: 0px; width: 0px;"></div>
                                            </div>
                                            <div class="ps__rail-y" style="top: 0px; right: 0px;">
                                                <div class="ps__thumb-y" tabindex="0" style="top: 0px; height: 0px;"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="topbar_notifications_logs" role="tabpanel">
                                        <!--begin::Nav-->
                                        <div class="d-flex flex-center text-center text-muted min-h-200px">Tudo em dia!
                                            <br>Nenhuma notificação nova.
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <!--end::Dropdown-->
                    </div>

                    <div class="topbar-item user-tour">
                        <div class="ui-user-controls">
                            @php($avatarUrl = app(\App\Services\UserAvatarService::class)->currentUserUrl())
                            <a class="ui-user-avatar ui-user-avatar-link" href="{{ route('usuarios.profile') }}" aria-label="Minha foto de perfil" title="Minha foto">
                                @if($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="">
                                @else
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) session('user_logged.nome', '?'), 0, 1)) }}
                                @endif
                            </a>
                            <span class="ui-user-details">
                                <span class="kt-header__topbar-welcome kt-hidden-mobile">Olá,</span>
                                <strong class="kt-header__topbar-username kt-hidden-mobile text-info text-left">{{session('user_logged')['nome']}}</strong>
                            </span>
                            <a href="/login/logoff" class="btn btn-danger">
                                <i class="la la-sign-out-alt" aria-hidden="true"></i>
                                Logoff
                            </a>
                        </div>
                    </div>
                </div>

                <div id="ui-scrolltop" class="scrolltop">
						<span class="svg-icon">
							<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
								<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
									<polygon points="0 0 24 0 24 24 0 24" />
									<rect fill="#000000" opacity="0.3" x="11" y="10" width="2" height="10" rx="1" />
									<path d="M6.70710678,12.7071068 C6.31658249,13.0976311 5.68341751,13.0976311 5.29289322,12.7071068 C4.90236893,12.3165825 4.90236893,11.6834175 5.29289322,11.2928932 L11.2928932,5.29289322 C11.6714722,4.91431428 12.2810586,4.90106866 12.6757246,5.26284586 L18.6757246,10.7628459 C19.0828436,11.1360383 19.1103465,11.7686056 18.7371541,12.1757246 C18.3639617,12.5828436 17.7313944,12.6103465 17.3242754,12.2371541 L12.0300757,7.38413782 L6.70710678,12.7071068 Z" fill="#000000" fill-rule="nonzero" />
								</g>
							</svg>
						</span>
                </div>

            </div>


            </div>
        </header>
