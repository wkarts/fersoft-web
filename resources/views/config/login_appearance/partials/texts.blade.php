{{-- Todos os textos são dados de apresentação. Sem HTML, scripts ou novo APP_NAME. --}}
<fieldset class="appearance-options appearance-form-grid__full">
    <legend>Textos da apresentação institucional</legend>
    <p class="appearance-admin__quiet">São os textos do painel de apresentação, nas composições que o exibem.</p>
    <div class="appearance-admin__field">
        <label for="appearance-edit-hero-kicker">Rótulo acima do título institucional</label>
        <input id="appearance-edit-hero-kicker" name="hero_kicker" maxlength="100"
               placeholder="Ex.: Seu espaço de trabalho"
               value="{{ old('hero_kicker', $editorTexts['hero_kicker']) }}">
    </div>
    <div class="appearance-admin__field">
        <label for="appearance-edit-headline">Título de apresentação</label>
        <input id="appearance-edit-headline" name="headline" maxlength="180"
               placeholder="Ex.: Bem-vindo ao seu ambiente."
               value="{{ old('headline', $editorTexts['headline']) }}">
    </div>
    <div class="appearance-admin__field">
        <label for="appearance-edit-tagline">Descrição de apresentação</label>
        <textarea id="appearance-edit-tagline" name="tagline" rows="3" maxlength="1500">{{ old('tagline', $editorTexts['tagline']) }}</textarea>
    </div>
</fieldset>
<fieldset class="appearance-options appearance-form-grid__full">
    <legend>Textos do formulário de acesso</legend>
    <p class="appearance-admin__quiet">Exibidos no computador e no celular. Apague um texto para ocultá-lo; os campos de autenticação continuam disponíveis.</p>
    <div class="appearance-admin__field">
        <label for="appearance-edit-form-kicker">Rótulo acima do formulário</label>
        <input id="appearance-edit-form-kicker" name="form_kicker" maxlength="100"
               placeholder="Ex.: Área de acesso"
               value="{{ old('form_kicker', $editorTexts['form_kicker']) }}">
    </div>
    <div class="appearance-admin__field">
        <label for="appearance-edit-form-title">Título do formulário</label>
        <input id="appearance-edit-form-title" name="form_title" maxlength="180"
               placeholder="Ex.: Acesse sua conta"
               value="{{ old('form_title', $editorTexts['form_title']) }}">
    </div>
    <div class="appearance-admin__field">
        <label for="appearance-edit-form-subtitle">Descrição do formulário</label>
        <textarea id="appearance-edit-form-subtitle" name="form_subtitle" rows="3" maxlength="1500">{{ old('form_subtitle', $editorTexts['form_subtitle']) }}</textarea>
    </div>
</fieldset>
