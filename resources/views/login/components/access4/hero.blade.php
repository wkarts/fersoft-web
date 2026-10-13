@if($presentation['show_hero'] || !empty($previewMode))
    <div class="login-hero-content" @if(!$presentation['show_hero']) hidden @endif>
        <div class="login-kicker" data-login-text="hero_kicker"
             @if($presentation['texts']['hero_kicker'] === '') hidden @endif>{{ $presentation['texts']['hero_kicker'] }}</div>
        <h1 data-login-text="headline"
            @if($presentation['texts']['headline'] === '') hidden @endif>{{ $presentation['texts']['headline'] }}</h1>
        <p data-login-text="tagline"
           @if($presentation['texts']['tagline'] === '') hidden @endif>{{ $presentation['texts']['tagline'] }}</p>

        {{-- O arquivo institucional histórico é outra versão da própria logo.
             Para não exibir duas marcas, só mostrar a imagem quando personalizada. --}}
        @if(!empty($presentation['hero_is_custom']) && !empty($presentation['assets']['hero']))
            <img class="login-hero-photo" src="{{ $presentation['assets']['hero'] }}"
                 alt="" loading="lazy" decoding="async">
        @endif
    </div>
@endif
