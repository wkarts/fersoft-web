@extends('default.layout')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login-appearance-admin.css') }}?v={{ filemtime(public_path('css/login-appearance-admin.css')) }}">
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-catalog-config.css') }}?v={{ filemtime(public_path('css/fer-catalog-config.css')) }}">
@endpush
@section('content')
<div class="fer-catalog-config fer-catalog-config--appearance fer-catalog-config--config">
@php
    $editorSettings = $editorTemplate['settings'];
    $previewUrl = $editorModel
        ? url('/config/aparencia/preview/' . $editorModel->id)
        : ($editorOrigin === 'preset'
            ? url('/config/aparencia/preview/predefinido/' . $editorTemplate['layout'])
            : url('/config/aparencia/preview/padrao'));
    $editorAction = $editorModel
        ? url('/config/aparencia/modelos/' . $editorModel->id)
        : url('/config/aparencia/modelos');
    $currentEditorUrl = $active
        ? url('/config/aparencia?tab=editor&edit=' . $active->id)
        : url('/config/aparencia?tab=editor');
    $currentPreviewUrl = $active
        ? url('/config/aparencia/preview/' . $active->id)
        : url('/config/aparencia/preview/padrao');
@endphp

<div class="appearance-admin" id="appearance-admin">
    <header class="appearance-header">
        <div class="appearance-header__text">
            <div class="appearance-eyebrow">Configurações / Identidade visual</div>
            <h1>Aparência do acesso</h1>
            <p>Personalize a tela de entrada, modelos, comunicados e identidade visual em um único lugar.</p>
        </div>
        <div class="appearance-header__actions">
            <a href="{{ $currentPreviewUrl }}" class="appearance-btn appearance-btn--outline" target="_blank" rel="noopener">
                <i class="la la-eye" aria-hidden="true"></i> Ver página atual
            </a>
            <a href="{{ $currentEditorUrl }}" class="appearance-btn appearance-btn--primary" data-appearance-editor-link>
                <i class="la la-pen" aria-hidden="true"></i> Editar aparência atual
            </a>
        </div>
    </header>

    @if(session('mensagem_sucesso'))
        <div class="appearance-feedback appearance-feedback--success" role="status">{{ session('mensagem_sucesso') }}</div>
    @endif
    @if($errors->any())
        <div class="appearance-feedback appearance-feedback--error" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="appearance-overview" aria-label="Resumo da aparência">
        <div class="appearance-overview__primary">
            <span class="appearance-overview__label">Aparência publicada</span>
            <strong>{{ $active?->name ?? 'Padrão original' }}</strong>
            <span>{{ $active ? 'Versão personalizada em uso' : 'Composição inicial preservada' }}</span>
        </div>
        <div class="appearance-overview__stat">
            <strong>{{ count($readyTemplates) }}</strong>
            <span>Modelos prontos</span>
        </div>
        <div class="appearance-overview__stat">
            <strong>{{ $draftCount }}</strong>
            <span>Modelos salvos</span>
        </div>
        <div class="appearance-overview__stat">
            <strong>{{ $publishedContentsCount }}</strong>
            <span>Conteúdos publicados</span>
        </div>
    </div>

    <nav class="appearance-nav" role="tablist" aria-label="Administração da tela de acesso">
        <a href="{{ url('/config/aparencia?tab=modelos') }}" role="tab" aria-controls="appearance-panel-modelos"
           data-appearance-tab="modelos">Modelos prontos</a>
        <a href="{{ url('/config/aparencia?tab=editor') }}" role="tab" aria-controls="appearance-panel-editor"
           data-appearance-tab="editor">Editor de aparência</a>
        <a href="{{ url('/config/aparencia?tab=conteudos') }}" role="tab" aria-controls="appearance-panel-conteudos"
           data-appearance-tab="conteudos">Frases e comunicados</a>
        <a href="{{ url('/config/aparencia?tab=identidade') }}" role="tab" aria-controls="appearance-panel-identidade"
           data-appearance-tab="identidade">Logos e ícones</a>
    </nav>

    <section class="appearance-panel" id="appearance-panel-modelos" role="tabpanel" data-appearance-panel="modelos">
        <div class="appearance-section-heading">
            <div>
                <h2>Comece com um modelo pronto</h2>
                <p>Oito opções completas, disponíveis agora. Você pode visualizar, personalizar ou publicar qualquer uma.</p>
            </div>
        </div>

        <article class="appearance-original">
            <div class="appearance-original__text">
                <span class="appearance-original__label">Original protegido</span>
                <h3>Padrão access_4</h3>
                <p>Edite sem sobrescrever o original. Ao salvar, uma nova versão é criada automaticamente.</p>
            </div>
            <div class="appearance-original__actions">
                @unless($active)
                    <span class="appearance-status appearance-status--active">Em uso</span>
                @endunless
                <a class="appearance-btn appearance-btn--outline" href="{{ url('/config/aparencia/preview/padrao') }}"
                   target="_blank" rel="noopener">Pré-visualizar</a>
                <a class="appearance-btn appearance-btn--primary" href="{{ url('/config/aparencia?tab=editor') }}">Editar padrão original</a>
                @if($active)
                    <form method="POST" action="{{ url('/config/aparencia/padrao/restaurar') }}">
                        @csrf
                        <button type="submit" class="appearance-btn appearance-btn--subtle">Restaurar padrão</button>
                    </form>
                @endif
            </div>
        </article>

        <div class="appearance-preset-grid">
            @foreach($readyTemplates as $key => $template)
                <article class="appearance-preset appearance-preset--{{ $key }}">
                    <div class="appearance-preset__visual" aria-hidden="true"
                         style="--preset-accent: {{ $template['settings']['accent_color'] }}; --preset-bg: {{ $template['settings']['background_color'] }};">
                        <div class="appearance-preset__side">
                            <span class="appearance-preset__logo"></span>
                            <span class="appearance-preset__headline"></span>
                            <span class="appearance-preset__subtitle"></span>
                        </div>
                        <div class="appearance-preset__signin">
                            <span class="appearance-preset__title"></span>
                            <span class="appearance-preset__field"></span>
                            <span class="appearance-preset__field"></span>
                            <span class="appearance-preset__button"></span>
                        </div>
                    </div>
                    <div class="appearance-preset__info">
                        <strong>{{ $template['name'] }}</strong>
                        <span>{{ $template['description'] }}</span>
                    </div>
                    <div class="appearance-preset__actions">
                        <a href="{{ url('/config/aparencia/preview/predefinido/' . $key) }}" target="_blank"
                           rel="noopener" class="appearance-btn appearance-btn--outline">Prévia</a>
                        <a href="{{ url('/config/aparencia?tab=editor&preset=' . $key) }}"
                           class="appearance-btn appearance-btn--outline">Personalizar</a>
                        <form method="POST" action="{{ url('/config/aparencia/modelos/predefinido/' . $key) }}">
                            @csrf
                            <button type="submit" class="appearance-btn appearance-btn--primary">Usar agora</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="appearance-section-heading appearance-section-heading--saved">
            <div>
                <h2>Meus modelos</h2>
                <p>Suas versões continuam armazenadas mesmo quando outra aparência é publicada.</p>
            </div>
            <a href="{{ url('/config/aparencia?tab=editor') }}" class="appearance-btn appearance-btn--outline">Nova personalização</a>
        </div>
        <div class="appearance-saved-grid">
            @forelse($models as $model)
                <article class="appearance-saved">
                    <div class="appearance-saved__top">
                        <div>
                            <strong>{{ $model->name }}</strong>
                            <p>{{ $presets[$model->layout] ?? $model->layout }} · {{ $fonts[$model->font] ?? $model->font }}</p>
                        </div>
                        @if($model->is_active)
                            <span class="appearance-status appearance-status--active">Publicado</span>
                        @else
                            <span class="appearance-status">Salvo</span>
                        @endif
                    </div>
                    <div class="appearance-saved__actions">
                        <a href="{{ url('/config/aparencia?tab=editor&edit=' . $model->id) }}" class="appearance-btn appearance-btn--outline">Editar</a>
                        <a href="{{ url('/config/aparencia/preview/' . $model->id) }}" target="_blank"
                           rel="noopener" class="appearance-btn appearance-btn--outline">Prévia</a>
                        <form method="POST" action="{{ url('/config/aparencia/modelos/' . $model->id . '/duplicar') }}">
                            @csrf
                            <button type="submit" class="appearance-btn appearance-btn--subtle">Duplicar</button>
                        </form>
                        @unless($model->is_active)
                            <form method="POST" action="{{ url('/config/aparencia/modelos/' . $model->id . '/publicar') }}">
                                @csrf
                                <button type="submit" class="appearance-btn appearance-btn--primary">Publicar</button>
                            </form>
                        @endunless
                    </div>
                </article>
            @empty
                <div class="appearance-empty">
                    <strong>Nenhum modelo personalizado ainda.</strong>
                    <p>Os oito modelos acima já estão disponíveis. Você também pode editar o padrão original diretamente.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="appearance-panel" id="appearance-panel-editor" role="tabpanel" data-appearance-panel="editor">
        <div class="appearance-section-heading">
            <div>
                <h2>{{ $editorOrigin === 'default' ? 'Editar o padrão original' : ($editorOrigin === 'preset' ? 'Personalizar modelo pronto' : 'Editar modelo salvo') }}</h2>
                <p>
                    @if($editorOrigin === 'default')
                        O original permanece intacto. Salvar cria automaticamente uma nova versão no banco.
                    @elseif($editorModel?->is_active)
                        O modelo está publicado. Uma edição cria outra versão sem alterar a publicação anterior.
                    @else
                        Personalize e escolha entre salvar como rascunho ou publicar imediatamente.
                    @endif
                </p>
            </div>
        </div>
        <div class="appearance-editor-grid">
            <form method="POST" action="{{ $editorAction }}" id="appearance-editor-form"
                  class="appearance-editor-form" data-appearance-editor>
                @csrf
                <div class="appearance-editor-form__heading">
                    <h3>Configurações visuais</h3>
                    <span class="appearance-status">{{ $editorModel ? 'Versão #' . $editorModel->id : 'Nova versão automática' }}</span>
                </div>
                <div class="appearance-form-grid">
                    <div class="appearance-admin__field appearance-form-grid__full">
                        <label for="appearance-edit-name">Nome da versão</label>
                        <input id="appearance-edit-name" name="name" required maxlength="100"
                               value="{{ old('name', $editorOrigin === 'default' ? 'Minha versão do padrão' : $editorTemplate['name']) }}">
                    </div>
                    <div class="appearance-admin__field">
                        <label for="appearance-edit-layout">Composição</label>
                        <select id="appearance-edit-layout" name="layout">
                            @foreach($presets as $key => $label)
                                <option value="{{ $key }}" @selected(old('layout', $editorTemplate['layout']) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="appearance-admin__field">
                        <label for="appearance-edit-font">Tipografia</label>
                        <select id="appearance-edit-font" name="font">
                            @foreach($fonts as $key => $label)
                                <option value="{{ $key }}" @selected(old('font', $editorTemplate['font']) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @include('config.login_appearance.partials.texts')
                    <div class="appearance-admin__field">
                        <label for="appearance-edit-accent">Cor principal</label>
                        <div class="appearance-color-input">
                            <input id="appearance-edit-accent" type="color" name="accent_color"
                                   value="{{ old('accent_color', $editorSettings['accent_color'] ?? '#1f9866') }}">
                            <span data-appearance-color-label="accent_color">{{ $editorSettings['accent_color'] ?? '#1f9866' }}</span>
                        </div>
                    </div>
                    <div class="appearance-admin__field">
                        <label for="appearance-edit-background">Cor de fundo</label>
                        <div class="appearance-color-input">
                            <input id="appearance-edit-background" type="color" name="background_color"
                                   value="{{ old('background_color', $editorSettings['background_color'] ?? '#f5f7f7') }}">
                            <span data-appearance-color-label="background_color">{{ $editorSettings['background_color'] ?? '#f5f7f7' }}</span>
                        </div>
                    </div>
                    <div class="appearance-admin__field appearance-form-grid__full">
                        <label for="appearance-edit-rotation">Intervalo das frases (segundos)</label>
                        <input id="appearance-edit-rotation" type="number" name="rotation_seconds"
                               min="4" max="30" value="{{ old('rotation_seconds', $editorSettings['rotation_seconds'] ?? 8) }}">
                    </div>
                </div>

                <fieldset class="appearance-options">
                    <legend>Elementos visíveis</legend>
                    <div class="appearance-options__grid">
                        @foreach(['show_hero'=>'Apresentação institucional', 'show_phrases'=>'Frases rotativas', 'show_posts'=>'Notícias e avisos', 'show_signup'=>'Cadastro de empresas', 'show_support'=>'Suporte'] as $key => $label)
                            <label class="appearance-option">
                                <input type="checkbox" name="{{ $key }}" value="1"
                                       @checked(old($key, $editorSettings[$key] ?? false))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                @include('config.login_appearance.partials.logo')

                <div class="appearance-savebar">
                    <button type="submit" class="appearance-btn appearance-btn--outline">Salvar rascunho</button>
                    <button type="submit" name="publish_now" value="1" class="appearance-btn appearance-btn--primary">
                        Salvar e aplicar no login
                    </button>
                </div>
                <p class="appearance-admin__quiet">
                    A publicação é versionada. O padrão original, os modelos anteriores e os arquivos cadastrados não são excluídos.
                </p>
            </form>

            <aside class="appearance-editor-preview" aria-label="Pré-visualização da página de acesso">
                <div class="appearance-editor-preview__header">
                    <div>
                        <strong>Pré-visualização</strong>
                        <p>Composição, textos e cores atualizam enquanto você edita.</p>
                    </div>
                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="appearance-btn appearance-btn--outline">
                        Ampliar
                    </a>
                </div>
                <div class="appearance-editor-preview__screen">
                    <iframe id="appearance-preview-frame" title="Pré-visualização do login"
                            src="{{ $previewUrl }}" loading="lazy"></iframe>
                </div>
                <p class="appearance-editor-preview__hint">A prévia não autentica usuários. A fonte selecionada será aplicada integralmente na publicação.</p>
            </aside>
        </div>
    </section>

    <section class="appearance-panel" id="appearance-panel-conteudos" role="tabpanel" data-appearance-panel="conteudos">
        <div class="appearance-section-heading">
            <div>
                <h2>Frases, notícias e avisos</h2>
                <p>Publique comunicações globais ou exclusivas para um modelo, com ordem e período de exibição.</p>
            </div>
        </div>
        @include('config.login_appearance.partials.contents')
    </section>

    <section class="appearance-panel" id="appearance-panel-identidade" role="tabpanel" data-appearance-panel="identidade">
        <div class="appearance-section-heading">
            <div>
                <h2>Logos e arquivos institucionais</h2>
                <p>Envie, revise e publique versões sem substituir os arquivos históricos do ERP.</p>
            </div>
        </div>
        @include('config.login_appearance.partials.assets')
    </section>
</div>
<script src="{{ asset('js/login-appearance-admin.js') }}?v={{ filemtime(public_path('js/login-appearance-admin.js')) }}" defer></script>
</div>
@endsection