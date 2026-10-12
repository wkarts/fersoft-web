@if($presentation['show_hero'])
    <div class="login-hero-content">
        <div class="login-kicker">{{ config('app.name') }}</div>
        @if($presentation['headline'] !== '')
            <h1>{{ $presentation['headline'] }}</h1>
        @endif
        @if($presentation['tagline'] !== '')
            <p>{{ $presentation['tagline'] }}</p>
        @endif
        @if(!empty($presentation['assets']['hero']))
            <img class="login-hero-photo" src="{{ $presentation['assets']['hero'] }}"
                 alt="" loading="lazy" decoding="async">
        @endif
    </div>
@endif
