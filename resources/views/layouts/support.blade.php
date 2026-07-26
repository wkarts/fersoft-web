@php
    $chatwootBaseUrl = config('services.chatwoot.base_url');
    $chatwootToken = config('services.chatwoot.website_token');
@endphp

@if(!empty($chatwootBaseUrl) && !empty($chatwootToken))
<script>
    window.chatwootSettings = {
        position: 'left',
        type: 'expanded_bubble',
        launcherTitle: 'Suporte'
    };

    (function(d, t) {
        const baseUrl = @json($chatwootBaseUrl);
        const websiteToken = @json($chatwootToken);
        const script = d.createElement(t);
        const firstScript = d.getElementsByTagName(t)[0];

        script.src = baseUrl + '/packs/js/sdk.js';
        script.defer = true;
        script.async = true;
        firstScript.parentNode.insertBefore(script, firstScript);

        script.onload = function() {
            window.chatwootSDK.run({
                websiteToken: websiteToken,
                baseUrl: baseUrl
            });
        };
    })(document, 'script');
</script>
@endif
