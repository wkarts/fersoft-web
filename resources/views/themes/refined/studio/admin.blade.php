@extends('default.layout')

@section('css')
<link rel="stylesheet" href="{{ asset('css/theme-refined-studio.css') }}?v={{ filemtime(public_path('css/theme-refined-studio.css')) }}">
<script src="{{ asset('js/theme-refined-studio.js') }}?v={{ filemtime(public_path('js/theme-refined-studio.js')) }}" defer></script>
@endsection

@section('content')
@php
    $uiEditColors = is_array($editingTheme?->colors ?? null) ? $editingTheme->colors : [];
@endphp
<div class="ui-studio ui-studio--admin" id="ui-theme-admin">
    <header class="ui-studio__head">
        <div>
            <p class="ui-studio__eyebrow">Configurações / Temas da interface</p>
            <h1>Temas da interface</h1>
            <p>Gerencie o catálogo visual e a disponibilidade dos temas para as empresas.</p>
        </div>
        <div class="ui-studio__head-actions">
            @if(Route::has('config.pos-themes.index'))
                <a class="ui-studio__back" href="{{ route('config.pos-themes.index') }}"><i class="la la-cash-register" aria-hidden="true"></i> Temas do PDV</a>
            @endif
            <a class="ui-studio__back" href="{{ route('ui.themes.index') }}"><i class="la la-arrow-left" aria-hidden="true"></i> Minha aparência</a>
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
                Não foi possível verificar o catálogo visual no banco de dados. Confira a conexão antes de instalar migrations ou administrar temas.
            @else
                O catálogo visual está incompleto. Execute o diagnóstico `php artisan ui-themes:install` no servidor para identificar migrations pendentes ou divergência de esquema. A criação, publicação e liberação ficam indisponíveis até a correção.
            @endif
        </div>
    @endunless
        <section class="ui-studio__section" aria-labelledby="ui-studio-standards-title">
            <div class="ui-studio__section-head">
                <div>
                    <p class="ui-studio__eyebrow">Exclusivo do superadministrador</p>
                    <h2 id="ui-studio-standards-title">Padrões da aplicação</h2>
                    <p>Defina o tema inicial da aplicação e ajuste os modelos oficiais. Preferências pessoais e padrões específicos das empresas continuam prioritários.</p>
                </div>
            </div>
            <div class="ui-studio__admin-grid">
                <div class="ui-studio__panel">
                    <h3>Tema padrão global</h3>
                    <p>Usado quando a empresa e o usuário ainda não escolheram outro tema.</p>
                    @unless($globalSettingsReady)
                        <div class="ui-studio__notice ui-studio__notice--error" role="status">A configuração global requer a migration aditiva. Execute <code>php artisan ui-themes:install --apply</code>. Os temas e padrões por empresa já instalados continuam funcionando.</div>
                    @endunless
                    <form method="POST" action="{{ route('ui.themes.global.default') }}" class="ui-studio__admin-form">
                        @csrf
                        <label class="ui-studio__full">Tema inicial de todas as empresas sem padrão próprio
                            <select name="theme_slug" required @disabled(!$catalogReady || !$globalSettingsReady)>
                                @foreach($standardThemes as $slug => $definition)
                                    <option value="{{ $slug }}" @selected($globalDefaultSlug === $slug)>{{ $definition['name'] ?? $slug }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit" class="ui-studio__apply ui-studio__full" @disabled(!$catalogReady || !$globalSettingsReady)>Salvar padrão global</button>
                    </form>
                    @if($globalSettingsReady && $globalDefaultSlug !== config('ui_theme.default_preset'))
                        <form method="POST" action="{{ route('ui.themes.global.restore') }}" class="ui-studio__restore-form">
                            @csrf
                            <button type="submit" class="ui-studio__publish">Restaurar padrão original</button>
                        </form>
                    @endif
                </div>
                <div class="ui-studio__panel">
                    <h3>Modelos oficiais</h3>
                    <p>Altere cores e nome sem mudar o identificador das escolhas já salvas. O botão restaurar recupera a paleta original do projeto.</p>
                    @foreach($builtinThemes as $slug => $original)
                        @php
                            $factoryOverride = $factoryOverrides->get($slug);
                            $factory = $standardThemes[$slug] ?? $original;
                            $factoryStyle = $factoryOverride?->sidebar_style
                                ?? (in_array($slug, ['fer-slate', 'fer-night', 'fer-graphite'], true) ? 'dark' : 'light');
                            $factorySubmitted = old('_factory_slug') === $slug;
                            $factoryValue = fn ($field, $fallback) => $factorySubmitted ? old($field, $fallback) : $fallback;
                        @endphp
                        <details class="ui-studio__factory" @if($factorySubmitted) open @endif>
                            <summary><span><strong>{{ $factory['name'] }}</strong><small>{{ $slug }}</small></span><span class="ui-studio__published">{{ $factoryOverride ? 'Personalizado' : 'Original' }}</span></summary>
                            <form method="POST" action="{{ route('ui.themes.factory.update', $slug) }}" class="ui-studio__admin-form">
                                @csrf
                                <input type="hidden" name="_factory_slug" value="{{ $slug }}">
                                <label class="ui-studio__full">Nome do modelo
                                    <input type="text" name="name" maxlength="120" value="{{ $factoryValue('name', $factory['name']) }}" required>
                                </label>
                                <label class="ui-studio__full">Descrição
                                    <input type="text" name="description" maxlength="400" value="{{ $factoryValue('description', $factory['description'] ?? '') }}">
                                </label>
                                @foreach([
                                    'accent' => 'Cor principal', 'accent_hover' => 'Cor principal ao passar o mouse',
                                    'background' => 'Fundo das páginas', 'surface' => 'Cartões e superfícies',
                                    'sidebar' => 'Menu lateral', 'sidebar_text' => 'Texto do menu',
                                    'text' => 'Texto principal', 'border' => 'Bordas e divisores',
                                ] as $field => $label)
                                    <label class="ui-studio__color-field"><span>{{ $label }}</span>
                                        <input type="color" name="{{ $field }}" value="{{ $factoryValue($field, $factory[$field] ?? $original[$field]) }}" required>
                                    </label>
                                @endforeach
                                <label>Paleta do menu
                                    <select name="sidebar_style">
                                        <option value="light" @selected($factoryValue('sidebar_style', $factoryStyle) === 'light')>Clara</option>
                                        <option value="dark" @selected($factoryValue('sidebar_style', $factoryStyle) === 'dark')>Escura</option>
                                    </select>
                                </label>
                                <label>Densidade inicial
                                    <select name="density">
                                        <option value="comfortable" @selected($factoryValue('density', $factory['density'] ?? 'comfortable') === 'comfortable')>Confortável</option>
                                        <option value="compact" @selected($factoryValue('density', $factory['density'] ?? 'comfortable') === 'compact')>Compacta</option>
                                    </select>
                                </label>
                                <button type="submit" class="ui-studio__apply ui-studio__full" @disabled(!$catalogReady)>Salvar modelo oficial</button>
                            </form>
                            @if($factoryOverride)
                                <form method="POST" action="{{ route('ui.themes.factory.restore', $slug) }}" class="ui-studio__restore-form">
                                    @csrf
                                    <button type="submit" class="ui-studio__publish">Restaurar cores originais</button>
                                </form>
                            @endif
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
        <section class="ui-studio__section" aria-labelledby="ui-studio-admin-title">
            <div class="ui-studio__section-head">
                <div>
                    <p class="ui-studio__eyebrow">Exclusivo do superadministrador</p>
                    <h2 id="ui-studio-admin-title">Catálogo de temas</h2>
                    <p>Você cria e publica os temas. Empresas e usuários podem apenas escolher os autorizados.</p>
                </div>
            </div>
            <fieldset class="ui-studio__lock" @disabled(!$catalogReady)>
            <div class="ui-studio__admin-grid">
                <div class="ui-studio__panel">
                    <h3>{{ $editingTheme ? 'Editar rascunho' : 'Criar novo tema' }}</h3>
                    <p>Escolha o nome, o comportamento e as cores. Depois de publicar, libere o modelo às empresas ou promova-o a padrão geral.</p>
                    @if($editingTheme)
                        <a class="ui-studio__back" href="{{ route('config.themes.index') }}">Cancelar edição e criar outro tema</a>
                    @endif
                    <form method="POST" action="{{ $editingTheme ? route('ui.themes.update', $editingTheme->id) : route('ui.themes.store') }}" class="ui-studio__admin-form">
                        @csrf
                        <label class="ui-studio__full">Nome do tema
                            <input type="text" name="name" maxlength="120" value="{{ old('name', $editingTheme?->name ?? '') }}" required placeholder="Ex.: Verde corporativo">
                        </label>
                        <label class="ui-studio__full">Descrição
                            <input type="text" name="description" maxlength="400" value="{{ old('description', $editingTheme?->description ?? '') }}" placeholder="Como é este tema">
                        </label>
                        <label class="ui-studio__full">Preencher cores do menu
                            <select name="sidebar_style" data-ui-sidebar-preset
                                    data-ui-sidebar-light="{{ $builtinThemes['fer-light']['sidebar'] ?? '#FFFFFF' }}"
                                    data-ui-sidebar-text-light="{{ $builtinThemes['fer-light']['sidebar_text'] ?? '#566C72' }}"
                                    data-ui-sidebar-dark="{{ $builtinThemes['fer-slate']['sidebar'] ?? '#1D2639' }}"
                                    data-ui-sidebar-text-dark="{{ $builtinThemes['fer-slate']['sidebar_text'] ?? '#B9C7D8' }}">
                                <option value="light" @if(old('sidebar_style', $editingTheme?->sidebar_style ?? 'light') === 'light') selected @endif>Paleta clara</option>
                                <option value="dark" @if(old('sidebar_style', $editingTheme?->sidebar_style ?? 'light') === 'dark') selected @endif>Paleta escura</option>
                            </select>
                            <small>Ao escolher uma paleta, os campos Menu lateral e Texto do menu são preenchidos. Você pode personalizar as cores antes de salvar.</small>
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
                                    <form method="POST" action="{{ route('ui.themes.promote', $model->id) }}">
                                        @csrf
                                        <button type="submit" class="ui-studio__publish">Tornar padrão geral</button>
                                    </form>
                                    <details class="ui-studio__archive">
                                        <summary>Arquivar</summary>
                                        <form method="POST" action="{{ route('ui.themes.archive', $model->id) }}">
                                            @csrf
                                            <button type="submit" class="ui-studio__publish">Confirmar arquivamento</button>
                                        </form>
                                    </details>
                                @elseif($model->status === 'standard')
                                    <span class="ui-studio__published">Padrão geral</span>
                                    <form method="POST" action="{{ route('ui.themes.demote', $model->id) }}">
                                        @csrf
                                        <button type="submit" class="ui-studio__publish">Restringir às empresas liberadas</button>
                                    </form>
                                @elseif($model->status === 'draft')
                                    <a class="ui-studio__publish" href="{{ route('config.themes.index') }}?edit_theme={{ $model->id }}">Editar</a>
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
            </fieldset>
        </section>
</div>
@endsection
