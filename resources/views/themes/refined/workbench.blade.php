{{-- Novo posto de navegação: apenas refina a experiência, não substitui ações do ERP.
     Os módulos são fornecidos pelo mesmo helper/permissões da navegação original. --}}
@php
    $uiAvailableNavigation = is_array($menu ?? null) ? $menu : [];
    $uiCurrentModule = (string) ($rotaAtiva ?? 'Início');
@endphp
<section class="ui-workbench" aria-label="Acesso rápido ao sistema">
    <div class="ui-workbench__identity">
        <span class="ui-workbench__product">{{ config('app.name') }}</span>
        <span class="ui-workbench__divider" aria-hidden="true">/</span>
        <span class="ui-workbench__module" title="{{ $uiCurrentModule }}">{{ $uiCurrentModule ?: 'Área de trabalho' }}</span>
    </div>
    <div class="ui-workbench__tools">
        <button type="button" class="ui-workbench__search" data-ui-command-open
                aria-haspopup="dialog" aria-controls="ui-command-palette"
                title="Buscar módulos (Ctrl+K)">
            <i class="la la-search" aria-hidden="true"></i>
            <span>Buscar módulos e funcionalidades</span>
            <kbd>Ctrl K</kbd>
        </button>
        <a href="/graficos" class="ui-workbench__icon" title="Dashboard" aria-label="Dashboard">
            <i class="la la-home" aria-hidden="true"></i>
        </a>
        <a href="{{ route('ui.themes.index') }}" class="ui-workbench__theme"
           title="Escolher o visual da sua empresa e do seu usuário">
            <i class="la la-palette" aria-hidden="true"></i>
            <span>Temas</span>
        </a>
    </div>
</section>

<div id="ui-command-palette" class="ui-command" role="dialog" aria-modal="true"
     aria-labelledby="ui-command-title" aria-hidden="true" hidden>
    <div class="ui-command__backdrop" data-ui-command-close></div>
    <div class="ui-command__dialog" role="document">
        <div class="ui-command__heading">
            <h2 id="ui-command-title">Acessar módulo ou funcionalidade</h2>
            <button type="button" class="ui-command__close" aria-label="Fechar pesquisa" data-ui-command-close>
                <i class="la la-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="ui-command__input-row">
            <i class="la la-search" aria-hidden="true"></i>
            <input type="search" id="ui-command-search" autocomplete="off" spellcheck="false"
                   placeholder="Pesquisar produtos, fiscal, financeiro, clientes..."
                   aria-controls="ui-command-results">
            <span class="ui-command__shortcut">ESC para fechar</span>
        </div>
        <div class="ui-command__results" id="ui-command-results" tabindex="-1">
            @foreach($uiAvailableNavigation as $group)
                @if((!isset($group['ativo']) || $group['ativo']) && !empty($group['subs']))
                    <section class="ui-command__group" data-ui-command-group>
                        <h3>{{ $group['titulo'] ?? 'Outros' }}</h3>
                        @foreach($group['subs'] as $item)
                            @if(!isset($item['rota_ativa']) && !empty($item['rota']))
                                <a href="{{ $item['rota'] }}"
                                   @isset($item['target']) target="_blank" rel="noopener" @endisset
                                   class="ui-command__result"
                                   data-ui-command-result
                                   data-search="{{ ($group['titulo'] ?? '') . ' ' . ($item['nome'] ?? '') }}"
                                   tabindex="-1">
                                    <span class="ui-command__result-icon"><i class="la la-angle-right" aria-hidden="true"></i></span>
                                    <span>{{ $item['nome'] ?? '' }}</span>
                                    <span class="ui-command__group-label">{{ $group['titulo'] ?? '' }}</span>
                                </a>
                            @endif
                        @endforeach
                    </section>
                @endif
            @endforeach
            <div class="ui-command__empty" data-ui-command-empty hidden>Nenhum módulo disponível com este termo.</div>
        </div>
        <div class="ui-command__foot">Os módulos exibidos respeitam as permissões atuais do ERP.</div>
    </div>
</div>
