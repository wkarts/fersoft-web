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
