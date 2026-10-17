@extends('default.layout')

@section('css')
<link rel="stylesheet" href="{{ asset('css/theme-refined-studio.css') }}?v={{ filemtime(public_path('css/theme-refined-studio.css')) }}">
<script src="{{ asset('js/theme-refined-studio.js') }}?v={{ filemtime(public_path('js/theme-refined-studio.js')) }}" defer></script>
@endsection

@section('content')
@php
    $uiCurrentSlug = (string) ($currentTheme['slug'] ?? 'fer-light');
    $uiCurrentMode = (string) ($currentTheme['color_mode'] ?? 'follow');
    $uiCurrentMenu = (string) ($currentTheme['menu_mode'] ?? 'follow');
    $uiCurrentDensity = (string) ($currentTheme['density'] ?? 'comfortable');
    $uiCurrentSidebar = $uiCurrentSlug === 'fer-slate' ? 'dark' : 'light';
@endphp
<div class="ui-studio" id="ui-theme-studio">
    <header class="ui-studio__head">
        <div>
            <p class="ui-studio__eyebrow">Configurações / Temas da interface</p>
            <h1>Temas da interface</h1>
            <p>Escolha como você trabalha. Sua preferência fica vinculada ao usuário e à empresa atual.</p>
        </div>
        <div class="ui-studio__head-actions">
            @if($isSuperadmin)
                <a class="ui-studio__back" href="{{ route('config.themes.index') }}"><i class="la la-palette" aria-hidden="true"></i> Administrar temas da interface</a>
            @endif
            <a class="ui-studio__back" href="/graficos"><i class="la la-arrow-left" aria-hidden="true"></i> Voltar ao ERP</a>
        </div>
    </header>

    @if(session('ui_theme_feedback'))
        <div class="ui-studio__notice" role="status">{{ session('ui_theme_feedback') }}</div>
    @endif
    @if($errors->any())
        <div class="ui-studio__notice ui-studio__notice--error" role="alert">
            <strong>Revise os campos informados:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @unless($catalogReady)
        <div class="ui-studio__notice ui-studio__notice--error" role="alert">
            @if($catalogStatus['connection_error'] ?? false)
                Não foi possível verificar o catálogo visual no banco de dados. Os temas padrão podem ser visualizados; a escolha ficará disponível quando a conexão for restabelecida.
            @else
                O catálogo visual está incompleto. Os temas padrão podem ser visualizados; a escolha ficará disponível após a instalação do esquema.
            @endif
        </div>
    @endunless

    <section class="ui-studio__section" aria-labelledby="ui-studio-choose-title">
        <div class="ui-studio__section-head">
            <div>
                <h2 id="ui-studio-choose-title">Minha aparência</h2>
                <p>Todos os módulos e permissões permanecem os mesmos; apenas a apresentação visual muda.</p>
            </div>
            <div><span class="ui-studio__count">{{ count($availableThemes) }} temas disponíveis</span>
                <form action="{{ route('ui.themes.reset') }}" method="POST" class="mt-3">@csrf<button type="submit" class="ui-studio__publish" @disabled(!$catalogReady)>Usar padrão da empresa</button></form>
            </div>
        </div>
        <div class="ui-studio__themes">
            @foreach($availableThemes as $slug => $definition)
                @php
                    $selected = $uiCurrentSlug === (string) $slug;
                    $themeName = (string) ($definition['name'] ?? $slug);
                    $builtinSidebarChoice = in_array((string) $slug, ['fer-light', 'fer-slate'], true);
                    $sidebarChoice = $selected ? $uiCurrentSidebar : ($slug === 'fer-slate' ? 'dark' : 'light');
                @endphp
                <article class="ui-studio__theme @if($selected)ui-studio__theme--selected @endif">
                    <div class="ui-studio__theme-preview" data-ui-theme-preview
                         data-ui-sample-is-builtin="{{ isset($builtinThemes[$slug]) ? '1' : '0' }}"
                         data-ui-sample-default-dark="{{ !empty($definition['dark']) ? '1' : '0' }}"
                         data-ui-sample-default-page="{{ $definition['background'] ?? '#F5F7F7' }}"
                         data-ui-sample-default-surface="{{ $definition['surface'] ?? '#FFFFFF' }}"
                         data-ui-sample-default-ink="{{ $definition['text'] ?? '#182F36' }}"
                         data-ui-sample-default-sidebar="{{ $definition['sidebar'] ?? '#FFFFFF' }}"
                         data-ui-sample-default-accent="{{ $definition['accent'] ?? '#1F9866' }}"
                         data-ui-sample-light-page="{{ $builtinThemes['fer-light']['background'] ?? '#F5F7F7' }}"
                         data-ui-sample-light-surface="{{ $builtinThemes['fer-light']['surface'] ?? '#FFFFFF' }}"
                         data-ui-sample-light-ink="{{ $builtinThemes['fer-light']['text'] ?? '#182F36' }}"
                         data-ui-sample-light-sidebar="{{ $builtinThemes['fer-light']['sidebar'] ?? '#FFFFFF' }}"
                         data-ui-sample-light-accent="{{ $builtinThemes['fer-light']['accent'] ?? '#1F9866' }}"
                         data-ui-sample-slate-sidebar="{{ $builtinThemes['fer-slate']['sidebar'] ?? '#1D2639' }}"
                         data-ui-sample-dark-page="{{ $builtinThemes['fer-night']['background'] ?? '#11252C' }}"
                         data-ui-sample-dark-surface="{{ $builtinThemes['fer-night']['surface'] ?? '#1C333B' }}"
                         data-ui-sample-dark-ink="{{ $builtinThemes['fer-night']['text'] ?? '#EEF6F4' }}"
                         data-ui-sample-dark-sidebar="{{ $builtinThemes['fer-night']['sidebar'] ?? '#172D35' }}"
                         data-ui-sample-dark-accent="{{ $builtinThemes['fer-night']['accent'] ?? '#62D3AB' }}"
                         style="--sample-sidebar: {{ $definition['sidebar'] ?? '#fff' }};
                                --sample-page: {{ $definition['background'] ?? '#f5f7fb' }};
                                --sample-surface: {{ $definition['surface'] ?? '#fff' }};
                                --sample-ink: {{ $definition['text'] ?? '#1c2a40' }};
                                --sample-accent: {{ $definition['accent'] ?? '#168f81' }};">
                        <div class="ui-studio__sample-sidebar">
                            <i></i><i></i><i></i><i></i>
                        </div>
                        <div class="ui-studio__sample-main">
                            <div class="ui-studio__sample-heading"></div>
                            <div class="ui-studio__sample-metrics"><span></span><span></span><span></span></div>
                            <div class="ui-studio__sample-table">
                                <span></span><span></span><span></span><span></span>
                            </div>
                        </div>
                    </div>
                    <div class="ui-studio__theme-info">
                        <div>
                            <h3>{{ $themeName }}</h3>
                            <p>{{ $definition['description'] ?? 'Personalização visual.' }}</p>
                        </div>
                        @if($selected)<span class="ui-studio__selected"><i class="la la-check" aria-hidden="true"></i> Em uso</span>@endif
                    </div>
                    <form method="POST" action="{{ route('ui.themes.select') }}" class="ui-studio__theme-form">
                        @csrf
                        <input type="hidden" name="theme_slug" value="{{ $slug }}">
                        <fieldset class="ui-studio__lock" @disabled(!$catalogReady)>
                        <div class="ui-studio__fields">
                            <label>
                                <span>Modo</span>
                                <select name="color_mode">
                                    <option value="follow" @if($selected && $uiCurrentMode === 'follow') selected @endif>Padrão do tema</option>
                                    <option value="light" @if($selected && $uiCurrentMode === 'light') selected @endif>Claro</option>
                                    <option value="dark" @if($selected && $uiCurrentMode === 'dark') selected @endif>Escuro</option>
                                </select>
                            </label>
                            @if($builtinSidebarChoice)
                                <label>
                                    <span>Sidebar</span>
                                    <select name="sidebar_mode">
                                        <option value="light" @selected($sidebarChoice === 'light')>Clara</option>
                                        <option value="dark" @selected($sidebarChoice === 'dark')>Escura</option>
                                    </select>
                                </label>
                            @endif
                            <label>
                                <span>Densidade</span>
                                <select name="density">
                                    <option value="comfortable" @if($selected && $uiCurrentDensity === 'comfortable') selected @endif>Confortável</option>
                                    <option value="compact" @if($selected && $uiCurrentDensity === 'compact') selected @endif>Compacta</option>
                                </select>
                            </label>
                            <label><span>Navegação</span><select name="menu_mode">
                                <option value="follow" @if($selected && $uiCurrentMenu === 'follow') selected @endif>Padrão do usuário</option>
                                <option value="lateral" @if($selected && $uiCurrentMenu === 'lateral') selected @endif>Menu lateral</option>
                                <option value="superior" @if($selected && $uiCurrentMenu === 'superior') selected @endif>Menu superior</option>
                            </select></label>
                        </div>
                        <button type="submit" class="ui-studio__apply">{{ $selected ? 'Atualizar preferência' : 'Usar este tema' }}</button>
                        </fieldset>
                    </form>
                </article>
            @endforeach
        </div>
    </section>

</div>
@endsection
