<script>
    window.hubSettings = {
        "position": "left",
        "type": "expanded_bubble",
        "launcherTitle": "Suporte"
    };

    (function(d, t) {
        var BASE_URL = "{{ env('HUB_BASE_URL') }}";
        var TOKEN = "{{ env('HUB_TOKEN') }}";

        var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
        g.src = BASE_URL + "/packs/js/sdk.js";
        g.defer = true;
        g.async = true;
        s.parentNode.insertBefore(g, s);

        g.onload = function() {
            window.hubSDK.run({
                websiteToken: TOKEN,
                baseUrl: BASE_URL
            });
        }
    })(document, "script");
</script>
