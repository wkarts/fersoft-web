<script>
    window.chatwootSettings = {
        "position": "left",
        "type": "expanded_bubble",
        "launcherTitle": "Suporte"
    };

    (function(d, t) {
        var BASE_URL = "{{ env('CHATWOOT_BASE_URL', 'https://hubsaas.wwsoftwares.com.br') }}";
        var TOKEN = "{{ env('CHATWOOT_TOKEN', 'EsJkZ4nje1nxk9qzhu3wa6B8') }}";

        var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
        g.src = BASE_URL + "/packs/js/sdk.js";
        g.defer = true;
        g.async = true;
        s.parentNode.insertBefore(g, s);

        g.onload = function() {
            window.chatwootSDK.run({
                websiteToken: TOKEN,
                baseUrl: BASE_URL
            });
        }
    })(document, "script");
</script>
