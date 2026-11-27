@php
    $uiSupportUrl = trim((string) config('ui_theme.support_url'));
    $uiSupportToken = trim((string) config('ui_theme.support_token'));
    $uiSupportPhone = preg_replace('/[^0-9]/', '', (string) config('ui_theme.support_phone'));
    $uiSupportValid = filter_var($uiSupportUrl, FILTER_VALIDATE_URL)
        && in_array(parse_url($uiSupportUrl, PHP_URL_SCHEME), ['https', 'http'], true);
@endphp
<dialog id="ui-support-dialog" class="ui-support-dialog" aria-labelledby="ui-support-title">
    <form method="dialog"><button class="ui-icon-button" aria-label="Fechar suporte">×</button></form>
    <h2 id="ui-support-title">Central de suporte</h2>
    <p data-ui-support-status>O atendimento online está carregando. Tente novamente em instantes.</p>
    @if(strlen($uiSupportPhone) >= 10 && strlen($uiSupportPhone) <= 15)
        <a class="ui-footer-link" href="https://wa.me/{{ $uiSupportPhone }}" target="_blank" rel="noopener noreferrer">Falar com o suporte pelo WhatsApp</a>
    @endif
    <button class="ui-footer-link" type="button" data-ui-support-retry>Tentar atendimento online</button>
</dialog>
<script>
(function () {
    'use strict';
    const config = {{ \Illuminate\Support\Js::from(['url' => $uiSupportValid ? rtrim($uiSupportUrl, '/') : '', 'token' => $uiSupportToken]) }};
    const dialog = document.getElementById('ui-support-dialog');
    let failed = false;
    function openSupport() {
        // Adapter for providers exporting the documented Chatwoot API; no hard dependency.
        const chat = window.$chatwoot;
        if (chat && typeof chat.toggle === 'function') {
            if (dialog.open) dialog.close();
            chat.toggle('open');
            return;
        }
        // Keep unknown SDK launchers usable rather than hiding an unavailable integration.
        const launcher = document.querySelector('.woot-widget-bubble:not(.ui-footer-link)');
        if (launcher && launcher.getClientRects().length) {
            if (dialog.open) dialog.close();
            launcher.click();
            return;
        }
        if (dialog.querySelector('[data-ui-support-status]')) {
            dialog.querySelector('[data-ui-support-status]').textContent = failed || !config.url || !config.token
                ? 'O atendimento online não está disponível neste momento.'
                : 'O atendimento online está carregando. Tente novamente em instantes.';
        }
        if (!dialog.open) dialog.showModal();
    }
    window.addEventListener('ui:support:open', openSupport);
    dialog.querySelector('[data-ui-support-retry]').addEventListener('click', openSupport);
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    if (!config.url || !config.token) return;
    // The launcher remains available on the right, clear of the navigation and its footer.
    window.hubSettings = Object.assign({}, window.hubSettings, {position: 'right', type: 'expanded_bubble', launcherTitle: 'Suporte'});
    const sdk = document.createElement('script');
    sdk.src = config.url + '/packs/js/sdk.js';
    sdk.async = true;
    sdk.onload = () => {
        if (!window.hubSDK || typeof window.hubSDK.run !== 'function') { failed = true; return; }
        try { window.hubSDK.run({websiteToken: config.token, baseUrl: config.url}); }
        catch (error) { failed = true; }
    };
    sdk.onerror = () => { failed = true; };
    document.head.appendChild(sdk);
})();
</script>
