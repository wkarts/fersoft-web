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
