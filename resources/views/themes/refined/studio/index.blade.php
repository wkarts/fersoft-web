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
    $uiEditColors = is_array($editingTheme?->colors ?? null) ? $editingTheme->colors : [];
@endphp
<div class="ui-studio" id="ui-theme-studio">
    <header class="ui-studio__head">
        <div>
            <p class="ui-studio__eyebrow">Configurações / Aparência do ERP</p>
            <h1>Temas da interface</h1>
            <p>Escolha como você trabalha. Sua preferência fica vinculada ao usuário e à empresa atual.</p>
        </div>
        <a class="ui-studio__back" href="/graficos"><i class="la la-arrow-left" aria-hidden="true"></i> Voltar ao ERP</a>
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

    <section class="ui-studio__section" aria-labelledby="ui-studio-choose-title">
        <div class="ui-studio__section-head">
            <div>
                <h2 id="ui-studio-choose-title">Minha aparência</h2>
                <p>Todos os módulos e permissões permanecem os mesmos; apenas a apresentação visual muda.</p>
            </div>
            <div><span class="ui-studio__count">{{ count($availableThemes) }} temas disponíveis</span>
                <form action="{{ route('ui.themes.reset') }}" method="POST" class="mt-3">@csrf<button type="submit" class="ui-studio__publish">Usar padrão da empresa</button></form>
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
                    </form>
                </article>
            @endforeach
        </div>
    </section>

    @if($isSuperadmin)
        <section class="ui-studio__section" aria-labelledby="ui-studio-admin-title">
            <div class="ui-studio__section-head">
                <div>
                    <p class="ui-studio__eyebrow">Exclusivo do superadministrador</p>
                    <h2 id="ui-studio-admin-title">Catálogo de temas</h2>
                    <p>Você cria e publica os temas. Empresas e usuários podem apenas escolher os autorizados.</p>
                </div>
            </div>
            <div class="ui-studio__admin-grid">
                <div class="ui-studio__panel">
                    <h3>{{ $editingTheme ? 'Editar rascunho' : 'Criar novo tema' }}</h3>
                    <p>Escolha o nome, o comportamento e as cores. Somente modelos publicados ficam disponíveis para as empresas.</p>
                    @if($editingTheme)
                        <a class="ui-studio__back" href="{{ route('ui.themes.index') }}">Cancelar edição e criar outro tema</a>
                    @endif
                    <form method="POST" action="{{ $editingTheme ? route('ui.themes.update', $editingTheme->id) : route('ui.themes.store') }}" class="ui-studio__admin-form">
                        @csrf
                        <label class="ui-studio__full">Nome do tema
                            <input type="text" name="name" maxlength="120" value="{{ old('name', $editingTheme?->name ?? '') }}" required placeholder="Ex.: Corporativo Azul">
                        </label>
                        <label class="ui-studio__full">Descrição
                            <input type="text" name="description" maxlength="400" value="{{ old('description', $editingTheme?->description ?? '') }}" placeholder="Como é este tema">
                        </label>
                        @foreach([
                            'accent' => ['Cor principal', '#1F9866'],
                            'accent_hover' => ['Cor principal ao passar o mouse', '#15754F'],
                            'background' => ['Fundo das páginas', '#F5F7F7'],
                            'surface' => ['Cartões e superfícies', '#FFFFFF'],
                            'sidebar' => ['Menu lateral', '#FFFFFF'],
                            'sidebar_text' => ['Texto do menu', '#566C72'],
                            'text' => ['Texto principal', '#182F36'],
                            'border' => ['Bordas e divisores', '#DCE7E7'],
                        ] as $field => $color)
                            <label class="ui-studio__color-field">
                                <span>{{ $color[0] }}</span>
                                <input type="color" name="{{ $field }}" value="{{ old($field, $uiEditColors[$field] ?? $builtinThemes['fer-light'][$field] ?? $color[1]) }}" required>
                            </label>
                        @endforeach
                        <label>Menu predominante
                            <select name="sidebar_style">
                                <option value="light" @if(old('sidebar_style', $editingTheme?->sidebar_style ?? 'light') === 'light') selected @endif>Claro</option>
                                <option value="dark" @if(old('sidebar_style', $editingTheme?->sidebar_style ?? 'light') === 'dark') selected @endif>Escuro</option>
                            </select>
                        </label>
                        <label>Densidade inicial
                            <select name="density">
                                <option value="comfortable" @if(old('density', $editingTheme?->density ?? 'comfortable') === 'comfortable') selected @endif>Confortável</option>
                                <option value="compact" @if(old('density', $editingTheme?->density ?? 'comfortable') === 'compact') selected @endif>Compacta</option>
                            </select>
                        </label>
                        <button type="submit" class="ui-studio__apply ui-studio__full">{{ $editingTheme ? 'Salvar alterações do rascunho' : 'Criar rascunho' }}</button>
                    </form>
                </div>
                <div class="ui-studio__panel">
                    <h3>Modelos personalizados</h3>
                    <p>Publique somente quando o modelo estiver pronto para ser disponibilizado.</p>
                    @forelse($customThemes as $model)
                        <div class="ui-studio__model">
                            <div>
                                <strong>{{ $model->name }}</strong>
                                <small>{{ $model->slug }}</small>
                            </div>
                            <div class="ui-studio__model-actions">
                                @if($model->status === 'published')
                                    <span class="ui-studio__published">Publicado</span>
                                    <details class="ui-studio__archive">
                                        <summary>Arquivar</summary>
                                        <form method="POST" action="{{ route('ui.themes.archive', $model->id) }}">
                                            @csrf
                                            <button type="submit" class="ui-studio__publish">Confirmar arquivamento</button>
                                        </form>
                                    </details>
                                @elseif($model->status === 'draft')
                                    <a class="ui-studio__publish" href="{{ route('ui.themes.index') }}?edit_theme={{ $model->id }}">Editar</a>
                                    <form method="POST" action="{{ route('ui.themes.publish', $model->id) }}">
                                        @csrf
                                        <button type="submit" class="ui-studio__publish">Publicar</button>
                                    </form>
                                @else
                                    <span class="ui-studio__published">Arquivado</span>
                                @endif
                                <form method="POST" action="{{ route('ui.themes.duplicate', $model->id) }}">
                                    @csrf
                                    <button type="submit" class="ui-studio__publish">Duplicar</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="ui-studio__empty">Nenhum tema personalizado criado. Os temas padrão já estão disponíveis.</div>
                    @endforelse

                    <div class="ui-studio__separator"></div>
                    <h3>Disponibilizar para uma empresa</h3>
                    <p>Conceda acesso ao tema publicado sem modificar a preferência pessoal dos usuários.</p>
                    <form method="POST" action="{{ route('ui.themes.grant') }}" class="ui-studio__admin-form">
                        @csrf
                        <div class="ui-studio__full ui-studio__company-chooser" data-ui-company-chooser
                             data-search-url="{{ route('ui.themes.company.search') }}">
                            <label>Empresa
                                <input type="search" autocomplete="off" data-ui-company-search
                                       placeholder="Pesquisar nome ou código da empresa (mínimo 2 caracteres)"
                                       aria-label="Pesquisar empresa" required>
                            </label>
                            <input type="hidden" name="company_id" data-ui-company-id>
                            <div class="ui-studio__company-results" role="listbox" aria-label="Empresas encontradas"
                                 aria-live="polite" data-ui-company-results hidden></div>
                        </div>
                        <label class="ui-studio__full">Tema
                            <select name="theme_slug" required>
                                @foreach($availableThemes as $slug => $definition)
                                    <option value="{{ $slug }}">{{ $definition['name'] ?? $slug }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit" class="ui-studio__apply ui-studio__full">Liberar tema à empresa</button>
                    </form>
                    <div class="ui-studio__separator"></div>
                    <h3>Definir padrão da empresa</h3>
                    <p>O padrão vale apenas quando o usuário não definiu sua preferência pessoal.</p>
                    <form method="POST" action="{{ route('ui.themes.company.default') }}" class="ui-studio__admin-form">
                        @csrf
                        <div class="ui-studio__full ui-studio__company-chooser" data-ui-company-chooser
                             data-search-url="{{ route('ui.themes.company.search') }}">
                            <label>Empresa
                                <input type="search" autocomplete="off" data-ui-company-search
                                       placeholder="Pesquisar nome ou código da empresa (mínimo 2 caracteres)"
                                       aria-label="Pesquisar empresa" required>
                            </label>
                            <input type="hidden" name="company_id" data-ui-company-id>
                            <div class="ui-studio__company-results" role="listbox" aria-label="Empresas encontradas"
                                 aria-live="polite" data-ui-company-results hidden></div>
                        </div>
                        <label class="ui-studio__full">Tema já liberado para a empresa
                            <select name="theme_slug" required>
                                @foreach($availableThemes as $slug => $definition)
                                    <option value="{{ $slug }}">{{ $definition['name'] ?? $slug }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit" class="ui-studio__apply ui-studio__full">Salvar padrão da empresa</button>
                    </form>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
