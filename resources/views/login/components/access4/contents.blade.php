<div class="login-content-blocks">
    @if($presentation['show_phrases'] && count($presentation['contents']['phrases']) > 0)
        <div class="login-phrases" id="login-access4-phrases" data-rotation-seconds="{{ (int) $presentation['rotation_seconds'] }}" aria-label="Mensagens institucionais">
            @foreach($presentation['contents']['phrases'] as $phrase)
                <div data-login-phrase class="{{ $loop->first ? 'is-visible' : '' }}">
                    {{ $phrase['body'] }}
                </div>
            @endforeach
            <div class="login-phrases__dots" aria-hidden="true">
                @foreach($presentation['contents']['phrases'] as $phrase)
                    <span data-login-dot class="{{ $loop->first ? 'is-visible' : '' }}"></span>
                @endforeach
            </div>
        </div>
    @endif

    @if($presentation['show_posts'])
        @foreach(array_slice($presentation['contents']['notices'], 0, 1) as $notice)
            <article class="login-publication" role="status">
                <div class="login-publication__category">COMUNICADO</div>
                @if($notice['title'] !== '')
                    <h2 class="login-publication__heading">{{ $notice['title'] }}</h2>
                @endif
                <div class="login-publication__text">{{ $notice['body'] }}</div>
            </article>
        @endforeach
        @foreach(array_slice($presentation['contents']['posts'], 0, 2) as $post)
            <article class="login-publication">
                <div class="login-publication__category">NOVIDADE</div>
                @if($post['title'] !== '')
                    <h2 class="login-publication__heading">{{ $post['title'] }}</h2>
                @endif
                @if($post['image'])
                    <img src="{{ $post['image'] }}" loading="lazy" alt="">
                @endif
                <div class="login-publication__text">{{ $post['body'] }}</div>
                @if($post['url'])
                    <a href="{{ $post['url'] }}" target="_blank" rel="noopener noreferrer">Saiba mais</a>
                @endif
            </article>
        @endforeach
    @endif
</div>
