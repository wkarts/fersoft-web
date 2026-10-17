{{-- Navegação original do ERP com os mesmos grupos, links e condições de permissão. --}}
    <aside id="ui-sidebar" class="ui-sidebar" aria-label="Navegação da aplicação">
        <div class="ui-brand">
                <a href="/graficos" class="brand-logo" aria-label="{{ config('app.name') }} — ir ao painel">
                    @if($logo != "" && env("LOGOCLIENTE") == 1)
                        <img width="120" height="45" alt="Logo" src="/logos/{{$logo}}" />
                    @else
                        <img width="100" alt="Logo" src="{{ $appearanceAssets['menu_logo'] ?? asset('imgs/slym.png') }}" @if(($appearanceAssets['menu_logo'] ?? asset('imgs/slym.png')) === asset('imgs/slym.png')) class="ui-brand-default-logo" @endif />
                    @endif
                </a>
                <a href="/graficos" class="ui-brand-symbol" aria-label="{{ config('app.name') }} — ir ao painel">
                    <img src="{{ asset('imgs/favicon.png') }}" alt="" />
                </a>


            <button type="button" class="ui-icon-button" data-ui-collapse aria-label="Recolher navegação" aria-expanded="true">«</button>
            <button type="button" class="ui-icon-button ui-mobile-close" data-ui-menu-close aria-label="Fechar navegação">×</button>
        </div>
            @php
                $appVersionValue = trim((string) (optional($appVersionCurrent)->version ?? ''));
                $appRevisionValue = trim((string) (optional($appVersionCurrent)->commit_hash ?? ''));
                $appRevisionShort = $appRevisionValue !== '' ? substr($appRevisionValue, 0, 7) : '';
                $appVersionTooltip = 'Versão instalada: ' . ($appVersionValue !== '' ? $appVersionValue : 'N/A');
                if ($appRevisionValue !== '') {
                    $appVersionTooltip .= ' · revisão: ' . $appRevisionValue;
                }
                $appVersionTooltip .= ' · abrir histórico de versões';
            @endphp

            <div class="ui-version app-version-desktop-wrapper">
                <a href="/app-versions" class="btn btn-sm btn-block app-version-desktop-btn" title="{{ $appVersionTooltip }}">
                    <span class="app-version-desktop-head">
                        <i class="la la-code-branch" aria-hidden="true"></i>
                        <span class="app-version-desktop-main">
                            <span class="app-version-desktop-version-line">
                                <span class="app-version-desktop-tag">V. {{ $appVersionValue !== '' ? $appVersionValue : 'N/A' }}</span>
                                @if($appRevisionShort !== '')
                                    <span class="app-version-desktop-revision-short" aria-label="Revisão {{ $appRevisionShort }}" title="Revisão completa: {{ $appRevisionValue }}">{{ $appRevisionShort }}</span>
                                @endif
                            </span>
                            @include('app_versions.installed_identity')
                        </span>
                    </span>
                </a>
            </div>


        <nav id="ui-navigation" class="ui-navigation" aria-label="Módulos do sistema">
            <p class="ui-nav-caption">Áreas da aplicação</p>
                    <ul class="ui-nav-list menu-tour">

                        @if(session('user_logged')['super'] == 1)
                            <li class="menu-item menu-item-submenu menu-item @if($rotaAtiva == 'SUPER') menu-item-active menu-item-open @endif" aria-haspopup="true">
                                <a  href="javascript:;" class="menu-link menu-toggle super-bg">
									<span class="svg-icon menu-icon svg-icon-white">
										<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
											<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
												<polygon points="0 0 24 0 24 24 0 24"/>
												<path d="M12,18 L7.91561963,20.1472858 C7.42677504,20.4042866 6.82214789,20.2163401 6.56514708,19.7274955 C6.46280801,19.5328351 6.42749334,19.309867 6.46467018,19.0931094 L7.24471742,14.545085 L3.94038429,11.3241562 C3.54490071,10.938655 3.5368084,10.3055417 3.92230962,9.91005817 C4.07581822,9.75257453 4.27696063,9.65008735 4.49459766,9.61846284 L9.06107374,8.95491503 L11.1032639,4.81698575 C11.3476862,4.32173209 11.9473121,4.11839309 12.4425657,4.36281539 C12.6397783,4.46014562 12.7994058,4.61977315 12.8967361,4.81698575 L14.9389263,8.95491503 L19.5054023,9.61846284 C20.0519472,9.69788046 20.4306287,10.2053233 20.351211,10.7518682 C20.3195865,10.9695052 20.2170993,11.1706476 20.0596157,11.3241562 L16.7552826,14.545085 L17.5353298,19.0931094 C17.6286908,19.6374458 17.263103,20.1544017 16.7187666,20.2477627 C16.5020089,20.2849396 16.2790408,20.2496249 16.0843804,20.1472858 L12,18 Z" fill="#000000"/>
											</g>
										</svg>
									</span>
                                    <span class="menu-text text-light">SUPER</span>
                                    <!-- <i class="la la-arrow-down"></i> -->
                                </a>
                                <div class="menu-submenu " style="" kt-hidden-height="320">
                                    <i class="menu-arrow"></i>
                                    <ul class="menu-subnav">
                                        <li class="menu-item  menu-item-parent" aria-haspopup="true">
											<span class="menu-link">
												<span class="menu-text"></span>
											</span>
                                        </li>

                                        {{-- Administração da página única de acesso: visível somente no grupo SUPER. --}}
                                        @if(class_exists(\App\Http\Controllers\LoginAppearanceController::class))
                                            <li class="menu-item @if(request()->is('config/aparencia*')) menu-item-active @endif" aria-haspopup="true">
                                                <a href="{{ url('/config/aparencia') }}" class="menu-link">
                                                    <i class="menu-bullet menu-bullet-line"><span></span></i>
                                                    <span class="menu-text">Aparência do Login</span>
                                                </a>
                                            </li>
                                        @endif

                                        @if(config('ui_theme.active', false) && class_exists(\App\Http\Controllers\UiThemeController::class))
                                            <li class="menu-item @if(request()->is('config/temas')) menu-item-active @endif" aria-haspopup="true">
                                                <a href="{{ route('config.themes.index') }}" class="menu-link">
                                                    <i class="menu-bullet menu-bullet-line"><span></span></i>
                                                    <span class="menu-text">Temas da Interface</span>
                                                </a>
                                            </li>
                                        @endif

                                        @if(config('ui_theme.active', false) && class_exists(\App\Http\Controllers\PosThemeController::class))
                                            <li class="menu-item @if(request()->is('config/temas/pdv*')) menu-item-active @endif" aria-haspopup="true">
                                                <a href="{{ route('config.pos-themes.index') }}" class="menu-link">
                                                    <i class="menu-bullet menu-bullet-line"><span></span></i>
                                                    <span class="menu-text">Temas do PDV</span>
                                                </a>
                                            </li>
                                        @endif

                                        <li class="menu-item menu-item-submenu" aria-haspopup="true">
                                            <a href="/empresas" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">Empresas</span>
                                            </a>

                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/planos" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">Planos</span>
                                            </a>

                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/ibpt" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">IBPT</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/contrato" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">Contrato</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/financeiro" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">Financeiro</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/cidades" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">Cidades</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/representantes" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">Representantes</span>
                                            </a>
                                        </li>
                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/online" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">Empresas Online</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/etiquetas" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">Etiquetas</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/relatorioSuper" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">Relatórios</span>
                                            </a>
                                        </li>
                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/ticketsSuper" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>
													</span>
                                                </i>
                                                <span class="menu-text">Tickets</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/contadores" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>
													</span>
                                                </i>
                                                <span class="menu-text">Contadores</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/planosPendentes" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>
													</span>
                                                </i>
                                                <span class="menu-text">Planos pendentes</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/pesquisa" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>
													</span>
                                                </i>
                                                <span class="menu-text">Pesquisa de satisfação</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/alertas" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>
													</span>
                                                </i>
                                                <span class="menu-text">Alertas para empresas</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/errosLog" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>
													</span>
                                                </i>
                                                <span class="menu-text">Erros do sistema</span>
                                            </a>
                                        </li>

                                        <li class="menu-item @if(request()->routeIs('super-admin.database-health')) menu-item-active @endif" aria-haspopup="true">
                                            <a href="{{ route('super-admin.database-health') }}" class="menu-link">
                                                <i class="menu-bullet menu-bullet-line"><span></span></i>
                                                <span class="menu-text">Saúde do banco</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/connect-api" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>
													</span>
                                                </i>
                                                <span class="menu-text">Instâncias Connect|API</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/config" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>
													</span>
                                                </i>
                                                <span class="menu-text">Configurações</span>
                                            </a>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/videos" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>
													</span>
                                                </i>
                                                <span class="menu-text">Videos do Sistema</span>
                                            </a>
                                        </li>

                                        @if((bool) env('UPDATER_UI_ENABLED', false))
                                            <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                                <a href="/{{ trim(env('UPDATER_UI_PREFIX', ''), '/') }}"  class="menu-link menu-">
                                                    <i class="menu-bullet menu-bullet-line">
                                                                                                        <span>
                                                                                                        </span>
                                                    </i>
                                                    <span class="menu-text">Atualização do Sistema</span>
                                                </a>
                                            </li>
                                        @endif

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/cancelamento-super" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>
													</span>
                                                </i>
                                                <span class="menu-text">Solicitações de cancelamento</span>
                                            </a>
                                        </li>

                                        @if(env("DELIVERY") == 1)

                                            <li class="menu-item menu-item-submenu" aria-haspopup="true">
                                                <a href="javascript:;" class="menu-link menu-toggle">
                                                    <i class="menu-bullet menu-bullet-dot">
                                                        <span></span>
                                                    </i>
                                                    <span class="menu-text">Delivery</span>
                                                    <i class="menu-arrow"></i>
                                                </a>
                                                <div class="menu-submenu" style="" kt-hidden-height="800">
                                                    <i class="menu-arrow"></i>
                                                    <ul class="menu-subnav">
                                                        <li class="menu-item" aria-haspopup="true">
                                                            <a href="/cidadeDelivery" class="menu-link">
                                                                <i class="menu-bullet menu-bullet-dot">
                                                                    <span></span>
                                                                </i>
                                                                <span class="menu-text">Cidades</span>
                                                            </a>
                                                        </li>

                                                        <li class="menu-item" aria-haspopup="true">
                                                            <a href="/categoriaMasterDelivery" class="menu-link">
                                                                <i class="menu-bullet menu-bullet-dot">
                                                                    <span></span>
                                                                </i>
                                                                <span class="menu-text">Categorias</span>
                                                            </a>
                                                        </li>

                                                        <li class="menu-item" aria-haspopup="true">
                                                            <a href="/destaquesDelivery" class="menu-link">
                                                                <i class="menu-bullet menu-bullet-dot">
                                                                    <span></span>
                                                                </i>
                                                                <span class="menu-text">Destaques</span>
                                                            </a>
                                                        </li>

                                                        <!-- <li class="menu-item" aria-haspopup="true">
                                                            <a href="/configDeliveryMaster" class="menu-link">
                                                                <i class="menu-bullet menu-bullet-dot">
                                                                    <span></span>
                                                                </i>
                                                                <span class="menu-text">Configuração</span>
                                                            </a>
                                                        </li> -->

                                                        <li class="menu-item" aria-haspopup="true">
                                                            <a href="/produtosDestaque" class="menu-link">
                                                                <i class="menu-bullet menu-bullet-dot">
                                                                    <span></span>
                                                                </i>
                                                                <span class="menu-text">Produtos em Destaque</span>
                                                            </a>
                                                        </li>

                                                        <li class="menu-item" aria-haspopup="true">
                                                            <a href="/bairrosDelivery" class="menu-link">
                                                                <i class="menu-bullet menu-bullet-dot">
                                                                    <span></span>
                                                                </i>
                                                                <span class="menu-text">Bairros</span>
                                                            </a>
                                                        </li>

                                                        <li class="menu-item" aria-haspopup="true">
                                                            <a href="/lojas" class="menu-link">
                                                                <i class="menu-bullet menu-bullet-dot">
                                                                    <span></span>
                                                                </i>
                                                                <span class="menu-text">Lojas</span>
                                                            </a>
                                                        </li>

                                                    </ul>
                                                </div>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @endif

                        @if(session('user_logged')['tipo_representante'] == 1)
                            <li class="menu-item menu-item-submenu menu-item" aria-haspopup="true">
                                <a href="javascript:;" class="menu-link menu-toggle">
									<span class="svg-icon menu-icon">
										<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
											<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
												<polygon points="0 0 24 0 24 24 0 24"/>
												<path d="M12,18 L7.91561963,20.1472858 C7.42677504,20.4042866 6.82214789,20.2163401 6.56514708,19.7274955 C6.46280801,19.5328351 6.42749334,19.309867 6.46467018,19.0931094 L7.24471742,14.545085 L3.94038429,11.3241562 C3.54490071,10.938655 3.5368084,10.3055417 3.92230962,9.91005817 C4.07581822,9.75257453 4.27696063,9.65008735 4.49459766,9.61846284 L9.06107374,8.95491503 L11.1032639,4.81698575 C11.3476862,4.32173209 11.9473121,4.11839309 12.4425657,4.36281539 C12.6397783,4.46014562 12.7994058,4.61977315 12.8967361,4.81698575 L14.9389263,8.95491503 L19.5054023,9.61846284 C20.0519472,9.69788046 20.4306287,10.2053233 20.351211,10.7518682 C20.3195865,10.9695052 20.2170993,11.1706476 20.0596157,11.3241562 L16.7552826,14.545085 L17.5353298,19.0931094 C17.6286908,19.6374458 17.263103,20.1544017 16.7187666,20.2477627 C16.5020089,20.2849396 16.2790408,20.2496249 16.0843804,20.1472858 L12,18 Z" fill="#000000"/>
											</g>
										</svg>
									</span>
                                    <span class="menu-text text-light">REPRESENTANTE/CONTADOR</span>
                                    <!-- <i class="la la-arrow-down"></i> -->
                                </a>
                                <div class="menu-submenu " style="" kt-hidden-height="320">
                                    <i class="menu-arrow"></i>
                                    <ul class="menu-subnav">
                                        <li class="menu-item  menu-item-parent" aria-haspopup="true">
											<span class="menu-link">
												<span class="menu-text"></span>
											</span>
                                        </li>

                                        <li class="menu-item  menu-item-submenu" aria-haspopup="true">
                                            <a href="/rep" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">Empresas</span>
                                            </a>

                                            <a href="/rep-parceiro" class="menu-link menu-">
                                                <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                </i>
                                                <span class="menu-text">Contador</span>
                                            </a>

                                        </li>
                                    </ul>
                                </div>
                            </li>
                        @endif

                        @php
                            $menu = new App\Helpers\Menu();
                            $menu = $menu->preparaMenu();

                        @endphp

                        @foreach($menu as $m)

                            @if(!isset($m['ativo']) || $m['ativo'])
                                <li class="menu-item menu-item-submenu menu-item @if($rotaAtiva == $m['titulo']) menu-item-active menu-item-open @endif" aria-haspopup="true">
                                    <a href="javascript:;" class="menu-link menu-toggle" id="{{$m['titulo']}}-tour">
                                        {!! $m['icone'] !!}
                                        <span class="menu-text">{{$m['titulo']}}</span>

                                    </a>
                                    <div class="menu-submenu" style="" kt-hidden-height="320">
                                        <i class="menu-arrow"></i>
                                        <ul class="menu-subnav">
                                            <li class="menu-item  menu-item-parent" aria-haspopup="true">
											<span class="menu-link">
												<span class="menu-text"></span>
											</span>
                                            </li>

                                            @foreach($m['subs'] as $i)

                                                @if(!isset($i['rota_ativa']) && $i['rota'] != '')
                                                    @php
                                                        $subPath = trim((string) parse_url($i['rota'], PHP_URL_PATH), '/');
                                                        $subActive = $uri == $i['rota'] || ($subPath !== '' && (request()->is($subPath) || request()->is($subPath . '/*')));
                                                    @endphp
                                                    <li class="menu-item menu-item-submenu @if($subActive) menu-item-active @endif" aria-haspopup="true">
                                                        <a @isset($i['target']) target="_blank" @endisset href="{{$i['rota']}}" class="menu-link menu-">
                                                            <i class="menu-bullet menu-bullet-line">
													<span>

													</span>
                                                            </i>
                                                            <span autofocus class="menu-text">{{$i['nome']}}</span>
                                                        </a>

                                                    </li>
                                                @endif
                                            @endforeach

                                        </ul>
                                    </div>
                                </li>
                            @endif
                        @endforeach


                    </ul>
        </nav>
    </aside>
