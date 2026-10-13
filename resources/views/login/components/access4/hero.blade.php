@if($presentation['show_hero'])
    <div class="login-hero-content">
        @if($presentation['headline'] !== '' || $presentation['tagline'] !== '')
            <div class="login-kicker">Seu espaço de trabalho</div>
            @if($presentation['headline'] !== '')
                <h1>{{ $presentation['headline'] }}</h1>
            @endif
            @if($presentation['tagline'] !== '')
                <p>{{ $presentation['tagline'] }}</p>
            @endif
        @else
            <div class="login-kicker">Bem-vindo</div>
            <h1>Tudo pronto para continuar.</h1>
            <p>Acesse seu ambiente de trabalho com segurança e praticidade.</p>
        @endif

        {{-- O arquivo institucional histórico é outra versão da própria logo.
             Para não exibir duas marcas, só mostrar a imagem quando personalizada. --}}
        @if(!empty($presentation['hero_is_custom']) && !empty($presentation['assets']['hero']))
            <img class="login-hero-photo" src="{{ $presentation['assets']['hero'] }}"
                 alt="" loading="lazy" decoding="async">
        @endif
    </div>
@endif
