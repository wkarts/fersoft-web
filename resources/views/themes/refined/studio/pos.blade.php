@extends('default.layout')

@section('css')
<link rel="stylesheet" href="{{ asset('css/theme-refined-studio.css') }}?v={{ filemtime(public_path('css/theme-refined-studio.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-pos-studio.css') }}?v={{ filemtime(public_path('css/ui-pos-studio.css')) }}">
@endsection

@section('content')
@php
    $posEditColors = is_array($editingTheme?->colors ?? null) ? $editingTheme->colors : [];
    $posBase = $builtinThemes['pos-default'];
    $posFields = [
        'accent' => 'Ações principais',
        'accent_hover' => 'Ações em destaque',
        'background' => 'Fundo do caixa',
        'surface' => 'Cartões e diálogos',
        'text' => 'Texto principal',
        'muted' => 'Texto auxiliar',
        'border' => 'Bordas e separadores',
    ];
@endphp
<div class="ui-studio ui-studio--admin ui-pos-studio" id="ui-pos-theme-admin">
    <header class="ui-studio__head">
        <div>
            <p class="ui-studio__eyebrow">Configurações / Temas / PDV</p>
            <h1>Temas do PDV</h1>
            <p>Defina as cores operacionais do caixa sem alterar a aparência pessoal do ERP.</p>
        </div>
        <a class="ui-studio__back" href="{{ route('config.themes.index') }}"><i class="la la-arrow-left" aria-hidden="true"></i> Temas da interface</a>
    </header>

    @if(session('ui_pos_theme_feedback'))
        <div class="ui-studio__notice" role="status">{{ session('ui_pos_theme_feedback') }}</div>
    @endif
    @if($errors->any())
        <div class="ui-studio__notice ui-studio__notice--error" role="alert">
            <strong>Revise o modelo:</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @unless($catalogReady)
        <div class="ui-studio__notice ui-studio__notice--error" role="alert">
            O PDV mantém o visual padrão atual. Para salvar modelos e escolher outro padrão, execute
            <code>php artisan ui-themes:install</code> para diagnosticar e
            <code>php artisan ui-themes:install --apply</code> para instalar as migrations aditivas.
        </div>
    @endunless

    <section class="ui-studio__section" aria-labelledby="pos-default-heading">
        <div class="ui-studio__section-head"><div>
            <p class="ui-studio__eyebrow">Padrão operacional</p>
            <h2 id="pos-default-heading">Aparência aplicada aos caixas</h2>
            <p>O modelo escolhido é único para o PDV. Preferências de tema de usuários e empresas do ERP não o modificam.</p>
        </div></div>
        <div class="ui-studio__panel ui-pos-studio__default">
            <div>
                <span class="ui-pos-studio__pill">Atual</span>
                <strong>{{ $publishedThemes[$defaultSlug]['name'] ?? $posBase['name'] }}</strong>
                <span>{{ $publishedThemes[$defaultSlug]['description'] ?? $posBase['description'] }}</span>
            </div>
            <form method="POST" action="{{ route('config.pos-themes.default') }}">
                @csrf
                <label for="ui-pos-default-select">Mudar padrão do PDV</label>
                <select id="ui-pos-default-select" name="theme_slug" required @disabled(!$catalogReady)>
                    @foreach($publishedThemes as $slug => $theme)
                        <option value="{{ $slug }}" @selected(old('theme_slug', $defaultSlug) === $slug)>{{ $theme['name'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="ui-studio__apply" @disabled(!$catalogReady)>Salvar padrão</button>
            </form>
        </div>
    </section>

    <section class="ui-studio__section" aria-labelledby="pos-builtins-heading">
        <div class="ui-studio__section-head"><div>
            <p class="ui-studio__eyebrow">Modelos de fábrica</p>
            <h2 id="pos-builtins-heading">Paletas prontas para o PDV</h2>
            <p>Os modelos de fábrica são preservados. Faça uma cópia para editar e publicar uma variação.</p>
        </div></div>
        <div class="ui-pos-studio__cards">
            @foreach($builtinThemes as $slug => $theme)
                <article class="ui-studio__panel ui-pos-studio__card">
                    <div class="ui-pos-studio__swatch" style="--pos-preview-bg: {{ $theme['background'] }}; --pos-preview-surface: {{ $theme['surface'] }}; --pos-preview-text: {{ $theme['text'] }}; --pos-preview-accent: {{ $theme['accent'] }}" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </div>
                    <h3>{{ $theme['name'] }}</h3>
                    <p>{{ $theme['description'] }}</p>
                    <div class="ui-pos-studio__actions">
                        @if($defaultSlug === $slug)<span class="ui-pos-studio__pill">Padrão atual</span>@endif
                        <form method="POST" action="{{ route('config.pos-themes.duplicate-builtin', $slug) }}">
                            @csrf
                            <button type="submit" class="ui-studio__publish" @disabled(!$catalogReady)>Criar cópia editável</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="ui-studio__section" aria-labelledby="pos-custom-heading">
        <div class="ui-studio__section-head"><div>
            <p class="ui-studio__eyebrow">Exclusivo do superusuário</p>
            <h2 id="pos-custom-heading">Modelos personalizados</h2>
            <p>Edite o rascunho, publique e então selecione o modelo como padrão. O padrão em uso não pode ser arquivado.</p>
        </div></div>
        <div class="ui-studio__admin-grid">
            <div class="ui-studio__panel">
                <h3>{{ $editingTheme ? 'Editar rascunho' : 'Criar modelo do PDV' }}</h3>
                @if($editingTheme)
                    <a class="ui-studio__back" href="{{ route('config.pos-themes.index') }}">Cancelar edição</a>
                @endif
                <fieldset class="ui-studio__lock" @disabled(!$catalogReady)>
                    <form method="POST" action="{{ $editingTheme ? route('config.pos-themes.update', $editingTheme->id) : route('config.pos-themes.store') }}" class="ui-studio__admin-form">
                        @csrf
                        <label class="ui-studio__full">Nome do modelo
                            <input name="name" type="text" maxlength="120" required value="{{ old('name', $editingTheme?->name ?? '') }}">
                        </label>
                        <label class="ui-studio__full">Descrição
                            <input name="description" type="text" maxlength="400" value="{{ old('description', $editingTheme?->description ?? '') }}">
                        </label>
                        @foreach($posFields as $key => $label)
                            <label class="ui-studio__color-field"><span>{{ $label }}</span>
                                <input type="color" name="{{ $key }}" value="{{ old($key, $posEditColors[$key] ?? $posBase[$key]) }}" required>
                            </label>
                        @endforeach
                        <p class="ui-pos-studio__help ui-studio__full">Texto e cores de ação precisam manter contraste suficiente com os cartões. O formulário valida a paleta antes de salvá-la.</p>
                        <button type="submit" class="ui-studio__apply ui-studio__full">{{ $editingTheme ? 'Salvar rascunho' : 'Criar rascunho' }}</button>
                    </form>
                </fieldset>
            </div>
            <div class="ui-studio__panel">
                <h3>Catálogo criado</h3>
                @forelse($customThemes as $model)
                    <div class="ui-studio__model">
                        <div><strong>{{ $model->name }}</strong><small>{{ $model->slug }} · {{ $model->status === 'published' ? 'Publicado' : ($model->status === 'draft' ? 'Rascunho' : 'Arquivado') }}</small></div>
                        <div class="ui-studio__model-actions">
                            @if($model->status === 'draft')
                                <a class="ui-studio__publish" href="{{ route('config.pos-themes.index', ['edit_theme' => $model->id]) }}">Editar</a>
                                <form method="POST" action="{{ route('config.pos-themes.publish', $model->id) }}">@csrf<button class="ui-studio__publish" type="submit">Publicar</button></form>
                            @elseif($model->status === 'published' && $defaultSlug !== $model->slug)
                                <form method="POST" action="{{ route('config.pos-themes.archive', $model->id) }}">@csrf<button class="ui-studio__publish" type="submit">Arquivar</button></form>
                            @elseif($model->status === 'published')
                                <span class="ui-pos-studio__pill">Padrão atual</span>
                            @endif
                            <form method="POST" action="{{ route('config.pos-themes.duplicate', $model->id) }}">@csrf<button class="ui-studio__publish" type="submit">Duplicar</button></form>
                        </div>
                    </div>
                @empty
                    <div class="ui-studio__empty">Ainda não há modelos personalizados para o PDV.</div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection
