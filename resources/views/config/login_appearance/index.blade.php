@extends('default.layout')

@section('css')
<style>
    .appearance-admin { max-width: 1380px; margin: 0 auto; }
    .appearance-admin__header { display:flex; flex-wrap:wrap; justify-content:space-between; gap:12px; align-items:center; margin:20px 0; }
    .appearance-admin__grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:18px; align-items:start; }
    .appearance-admin__card { background:#fff; border:1px solid #e4e8ed; border-radius:13px; padding:20px; margin-bottom:18px; }
    .appearance-admin__card h3 { margin:0 0 12px; font-size:18px; font-weight:700; }
    .appearance-admin__field { margin-bottom:12px; }
    .appearance-admin__field label { display:block; font-weight:600; margin-bottom:5px; }
    .appearance-admin__field input:not([type=checkbox]):not([type=file]),.appearance-admin__field select,.appearance-admin__field textarea { display:block; width:100%; border:1px solid #d8e0e6; border-radius:7px; padding:9px; min-height:39px; }
    .appearance-admin__check { display:flex; align-items:center; gap:8px; margin-bottom:9px; }
    .appearance-admin__actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:13px; }
    .appearance-admin__actions a,.appearance-admin__actions button { border:1px solid #dbe3e7; border-radius:7px; padding:9px 13px; font-weight:600; cursor:pointer; text-decoration:none; background:#f9fbfc; color:#253945; font-size:12px; }
    .appearance-admin__actions button.primary { background:#198958; border-color:#198958; color:#fff; }
    .appearance-admin__actions button[disabled] { opacity:.55; cursor:not-allowed; }
    .appearance-admin__quiet { font-size:12px; line-height:1.5; color:#6d7d8a; }
    .appearance-admin__asset { display:flex; align-items:center; gap:12px; padding:12px; border:1px solid #e9eef1; border-radius:9px; margin-bottom:10px; }
    .appearance-admin__asset img { width:70px; max-height:62px; object-fit:contain; background:#f7faf8; border:1px solid #e5eeeb; border-radius:6px; }
    .appearance-admin__asset .text { flex:1; min-width:0; overflow-wrap:anywhere; }
    .appearance-admin__model { border:1px solid #e3e9ee; border-radius:11px; margin-bottom:14px; padding:15px; }
    .appearance-admin__model.is-active { border-color:#72baa0; background:#f9fdfa; }
    .appearance-admin__flag { font-size:11px; border-radius:12px; padding:4px 9px; background:#e7f6ec; color:#167b4b; }
    .appearance-admin__divider { border-top:1px solid #e9edf1; margin:19px 0; }
    .appearance-admin__notice { margin:16px 0; padding:13px; background:#e8f6ed; border:1px solid #c5e9d3; border-radius:9px; }
    .appearance-admin__section { margin-top:22px; }
</style>
@endsection

@section('content')
<div class="container-fluid appearance-admin">
    <div class="appearance-admin__header">
        <div>
            <h2>Aparência & Comunicação</h2>
            <div class="appearance-admin__quiet">
                Uma página de acesso, múltiplos modelos visuais e uma única autenticação.
            </div>
        </div>
        <a href="/config" class="btn btn-light">Configurações existentes</a>
    </div>

    @if(session('mensagem_sucesso'))
        <div class="appearance-admin__notice">{{ session('mensagem_sucesso') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="appearance-admin__grid">
        <section class="appearance-admin__card">
            <h3>Identidade da aplicação</h3>
            <div class="appearance-admin__field">
                <label>Nome da aplicação — somente leitura</label>
                <input type="text" readonly value="{{ config('app.name') }}">
                <small class="appearance-admin__quiet">Obtido de APP_NAME. Não é gravado no banco.</small>
            </div>
            <div class="appearance-admin__field">
                <label>Modelo publicado</label>
                <div class="appearance-admin__quiet">
                    {{ $active?->name ?? 'Configuração inicial do ambiente' }}
                    @if($active)
                        <span class="appearance-admin__flag">Publicado</span>
                    @endif
                </div>
            </div>
            <p class="appearance-admin__quiet">
                As fontes atuais são arquivos locais homologados. A escolha da fonte é resolvida antes de montar o HTML.
                O nome e os ícones das empresas continuam independentes da identidade global.
            </p>
        </section>

        <section class="appearance-admin__card">
            <h3>Criar um modelo</h3>
            <p class="appearance-admin__quiet">Selecione uma composição pronta, personalize e salve como rascunho.</p>
            <form method="POST" action="{{ url('/config/aparencia/modelos') }}">
                @csrf
                <div class="appearance-admin__field">
                    <label for="appearance-new-name">Nome administrativo</label>
                    <input id="appearance-new-name" name="name" required maxlength="100" placeholder="Ex.: Institucional moderno">
                </div>
                <div class="appearance-admin__field">
                    <label for="appearance-new-layout">Composição</label>
                    <select id="appearance-new-layout" name="layout">
                        @foreach($presets as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="appearance-admin__field">
                    <label for="appearance-new-font">Tipografia</label>
                    <select id="appearance-new-font" name="font">
                        @foreach($fonts as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="appearance-admin__field">
                    <label for="appearance-new-headline">Título institucional</label>
                    <input id="appearance-new-headline" name="headline" maxlength="180">
                </div>
                <div class="appearance-admin__field">
                    <label for="appearance-new-tagline">Descrição</label>
                    <textarea id="appearance-new-tagline" name="tagline" rows="3" maxlength="1500"></textarea>
                </div>
                <div class="row">
                    <div class="col-6 appearance-admin__field">
                        <label>Cor principal</label>
                        <input name="accent_color" type="color" value="#1f9866">
                    </div>
                    <div class="col-6 appearance-admin__field">
                        <label>Cor de fundo</label>
                        <input name="background_color" type="color" value="#f5f7f7">
                    </div>
                </div>
                <div class="appearance-admin__field">
                    <label>Intervalo entre frases (segundos)</label>
                    <input name="rotation_seconds" type="number" min="4" max="30" value="8">
                </div>
                @foreach(['show_hero'=>'Apresentação institucional', 'show_phrases'=>'Frases rotativas', 'show_posts'=>'Publicações', 'show_signup'=>'Cadastro de empresas', 'show_support'=>'Suporte'] as $key => $label)
                    <label class="appearance-admin__check">
                        <input type="checkbox" name="{{ $key }}" value="1" checked>
                        {{ $label }}
                    </label>
                @endforeach
                <div class="appearance-admin__actions">
                    <button class="primary" type="submit">Criar rascunho</button>
                </div>
            </form>
        </section>
    </div>

    <section class="appearance-admin__section">
        <h3>Modelos visuais cadastrados</h3>
        <p class="appearance-admin__quiet">Pode criar quantos modelos desejar sem acrescentar novas páginas físicas de login.</p>
        <div class="appearance-admin__grid">
            @forelse($models as $model)
                <article class="appearance-admin__model {{ $model->is_active ? 'is-active' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <strong>{{ $model->name }}</strong>
                        @if($model->is_active)
                            <span class="appearance-admin__flag">Publicado</span>
                        @else
                            <span class="appearance-admin__quiet">Rascunho</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ url('/config/aparencia/modelos/' . $model->id) }}">
                        @csrf
                        <div class="appearance-admin__field">
                            <label>Nome do modelo</label>
                            <input name="name" value="{{ $model->name }}" required maxlength="100">
                        </div>
                        <div class="appearance-admin__field">
                            <label>Composição</label>
                            <select name="layout">
                                @foreach($presets as $key => $label)
                                    <option value="{{ $key }}" @if($model->layout === $key) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="appearance-admin__field">
                            <label>Tipografia</label>
                            <select name="font">
                                @foreach($fonts as $key => $label)
                                    <option value="{{ $key }}" @if($model->font === $key) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="appearance-admin__field">
                            <label>Título</label>
                            <input name="headline" value="{{ $model->settings['headline'] ?? '' }}" maxlength="180">
                        </div>
                        <div class="appearance-admin__field">
                            <label>Descrição</label>
                            <textarea name="tagline" maxlength="1500" rows="2">{{ $model->settings['tagline'] ?? '' }}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-6 appearance-admin__field">
                                <label>Cor principal</label>
                                <input type="color" name="accent_color" value="{{ $model->settings['accent_color'] ?? '#1f9866' }}">
                            </div>
                            <div class="col-6 appearance-admin__field">
                                <label>Cor de fundo</label>
                                <input type="color" name="background_color" value="{{ $model->settings['background_color'] ?? '#f5f7f7' }}">
                            </div>
                        </div>
                        <div class="appearance-admin__field">
                            <label>Rotação (segundos)</label>
                            <input name="rotation_seconds" type="number" value="{{ $model->settings['rotation_seconds'] ?? 8 }}" min="4" max="30">
                        </div>
                        @foreach(['show_hero'=>'Apresentação', 'show_phrases'=>'Frases', 'show_posts'=>'Publicações', 'show_signup'=>'Cadastro', 'show_support'=>'Suporte'] as $key => $label)
                            <label class="appearance-admin__check">
                                <input type="checkbox" name="{{ $key }}" value="1" @if(!empty($model->settings[$key])) checked @endif>
                                {{ $label }}
                            </label>
                        @endforeach
                        <div class="appearance-admin__actions">
                            <button type="submit">{{ $model->is_active ? 'Criar novo rascunho' : 'Salvar rascunho' }}</button>
                        </div>
                    </form>
                    <div class="appearance-admin__divider"></div>
                    <div class="appearance-admin__actions">
                        <a href="{{ url('/config/aparencia/preview/' . $model->id) }}" target="_blank" rel="noopener">Pré-visualizar</a>
                        <form method="POST" action="{{ url('/config/aparencia/modelos/' . $model->id . '/duplicar') }}">
                            @csrf
                            <button type="submit">Duplicar</button>
                        </form>
                        @unless($model->is_active)
                            <form method="POST" action="{{ url('/config/aparencia/modelos/' . $model->id . '/publicar') }}">
                                @csrf
                                <button class="primary" type="submit">Publicar modelo</button>
                            </form>
                        @endunless
                    </div>
                </article>
            @empty
                <div class="appearance-admin__card">
                    Nenhum modelo cadastrado. A aplicação continua usando a configuração inicial do ambiente.
                </div>
            @endforelse
        </div>
    </section>

    <section class="appearance-admin__section appearance-admin__card">
        <h3>Frases, notícias e avisos</h3>
        <p class="appearance-admin__quiet">Publicações podem valer para todos os modelos ou para uma composição específica.</p>
        <form method="POST" action="{{ url('/config/aparencia/conteudos') }}" enctype="multipart/form-data">
            @csrf
            <div class="appearance-admin__grid">
                <div>
                    <div class="appearance-admin__field">
                        <label>Tipo</label>
                        <select name="type">
                            <option value="phrase">Frase rotativa</option>
                            <option value="post">Notícia / publicação</option>
                            <option value="notice">Aviso</option>
                        </select>
                    </div>
                    <div class="appearance-admin__field">
                        <label>Título opcional</label>
                        <input name="title" maxlength="200">
                    </div>
                    <div class="appearance-admin__field">
                        <label>Texto</label>
                        <textarea name="body" required rows="4" maxlength="4000"></textarea>
                    </div>
                    <div class="appearance-admin__field">
                        <label>Imagem opcional</label>
                        <input name="image" type="file" accept="image/png,image/jpeg,image/webp">
                    </div>
                </div>
                <div>
                    <div class="appearance-admin__field">
                        <label>Modelo</label>
                        <select name="presentation_id">
                            <option value="">Todos os modelos</option>
                            @foreach($models as $model)
                                <option value="{{ $model->id }}">{{ $model->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="appearance-admin__field">
                        <label>Link externo HTTPS opcional</label>
                        <input name="link_url" type="url" placeholder="https://">
                    </div>
                    <div class="appearance-admin__field">
                        <label>Status</label>
                        <select name="status">
                            <option value="draft">Rascunho</option>
                            <option value="published">Publicado</option>
                            <option value="archived">Arquivado</option>
                        </select>
                    </div>
                    <div class="appearance-admin__field">
                        <label>Ordem</label>
                        <input name="sort_order" type="number" value="0">
                    </div>
                    <div class="appearance-admin__field">
                        <label>Início (opcional)</label>
                        <input name="starts_at" type="datetime-local">
                    </div>
                    <div class="appearance-admin__field">
                        <label>Fim (opcional)</label>
                        <input name="ends_at" type="datetime-local">
                    </div>
                </div>
            </div>
            <div class="appearance-admin__actions">
                <button type="submit" class="primary">Salvar conteúdo</button>
            </div>
        </form>
        <div class="appearance-admin__divider"></div>
        @forelse($contents as $content)
            <details class="appearance-admin__model">
                <summary style="cursor:pointer;font-weight:600">
                    {{ $content->title ?: \Illuminate\Support\Str::limit($content->body, 65) }}
                    <span class="appearance-admin__quiet">({{ $content->type }} · {{ $content->status }})</span>
                </summary>
                <form method="POST" action="{{ url('/config/aparencia/conteudos/' . $content->id) }}" enctype="multipart/form-data" class="mt-3">
                    @csrf
                    <div class="appearance-admin__grid">
                        <div>
                            <div class="appearance-admin__field">
                                <label>Tipo</label>
                                <select name="type">
                                    @foreach(['phrase'=>'Frase','post'=>'Notícia','notice'=>'Aviso'] as $key=>$label)
                                        <option value="{{ $key }}" @if($content->type === $key) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="appearance-admin__field"><label>Título</label><input name="title" value="{{ $content->title }}"></div>
                            <div class="appearance-admin__field"><label>Texto</label><textarea name="body" required rows="3">{{ $content->body }}</textarea></div>
                            <div class="appearance-admin__field"><label>Nova imagem</label><input name="image" type="file" accept="image/png,image/jpeg,image/webp"></div>
                        </div>
                        <div>
                            <div class="appearance-admin__field"><label>Modelo</label>
                                <select name="presentation_id">
                                    <option value="">Todos</option>
                                    @foreach($models as $model)
                                        <option value="{{ $model->id }}" @if($content->presentation_id == $model->id) selected @endif>{{ $model->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="appearance-admin__field"><label>Status</label>
                                <select name="status">
                                    @foreach(['draft'=>'Rascunho','published'=>'Publicado','archived'=>'Arquivado'] as $key=>$label)
                                        <option value="{{ $key }}" @if($content->status === $key) selected @endif>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="appearance-admin__field"><label>Link HTTPS</label><input name="link_url" value="{{ $content->link_url }}"></div>
                            <div class="appearance-admin__field"><label>Ordem</label><input name="sort_order" type="number" value="{{ $content->sort_order }}"></div>
                            <div class="appearance-admin__field"><label>Início</label><input name="starts_at" type="datetime-local" value="{{ $content->starts_at?->format('Y-m-d\TH:i') }}"></div>
                            <div class="appearance-admin__field"><label>Fim</label><input name="ends_at" type="datetime-local" value="{{ $content->ends_at?->format('Y-m-d\TH:i') }}"></div>
                        </div>
                    </div>
                    <div class="appearance-admin__actions"><button type="submit">Salvar alterações</button></div>
                </form>
                <form method="POST" action="{{ url('/config/aparencia/conteudos/' . $content->id . '/arquivar') }}" class="appearance-admin__actions">
                    @csrf
                    <button type="submit">Arquivar conteúdo</button>
                </form>
            </details>
        @empty
            <p class="appearance-admin__quiet">Nenhum conteúdo cadastrado.</p>
        @endforelse
    </section>

    <section class="appearance-admin__section appearance-admin__card">
        <h3>Logos, favicon e imagem institucional</h3>
        <p class="appearance-admin__quiet">
            Os arquivos são versionados. Substituir a logo institucional não altera a logo própria das empresas.
            Para publicar o favicon, envie um PNG quadrado com pelo menos 192 × 192 pixels.
        </p>
        <div class="appearance-admin__grid">
            @foreach(['login_logo'=>'Logo da tela de acesso','menu_logo'=>'Logo institucional do menu','favicon'=>'Favicon e ícone da instalação','hero'=>'Imagem institucional'] as $key=>$label)
                <div>
                    <h4>{{ $label }}</h4>
                    <form method="POST" enctype="multipart/form-data" action="{{ url('/config/aparencia/arquivos/' . $key) }}">
                        @csrf
                        <div class="appearance-admin__field">
                            <label>Enviar nova versão</label>
                            <input name="image" type="file" required accept="{{ $key === 'favicon' ? 'image/png' : 'image/png,image/jpeg,image/webp' }}">
                        </div>
                        <div class="appearance-admin__actions"><button type="submit">Enviar arquivo</button></div>
                    </form>
                    <div class="appearance-admin__divider"></div>
                    @foreach($assets->where('asset_key', $key)->take(8) as $asset)
                        <div class="appearance-admin__asset">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($asset->path) }}"
                                 alt="Prévia de {{ $label }}" loading="lazy">
                            <div class="text">
                                <div class="appearance-admin__quiet">Versão #{{ $asset->id }}</div>
                                <small>{{ $asset->width }} × {{ $asset->height }}</small>
                                @if($asset->is_active)
                                    <div><span class="appearance-admin__flag">Publicada</span></div>
                                @else
                                    <form method="POST" action="{{ url('/config/aparencia/arquivos/' . $asset->id . '/publicar') }}">
                                        @csrf
                                        <div class="appearance-admin__actions"><button type="submit">Publicar esta versão</button></div>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
