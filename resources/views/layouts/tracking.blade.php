{{-- Google Analytics --}}
@if (!empty($googleAnalyticsId))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalyticsId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', '{{ $googleAnalyticsId }}');
    </script>
@endif

{{-- Hotjar --}}
@if (!empty($hotjarId))
    <script>
        (function(h,o,t,j,a,r){
            h.hj = h.hj || function(){ (h.hj.q = h.hj.q || []).push(arguments) };
            h._hjSettings = { hjid: {{ $hotjarId }}, hjsv: 6 };
            a = o.getElementsByTagName('head')[0];
            r = o.createElement('script'); r.async = 1;
            r.src = t + h._hjSettings.hjid + j + h._hjSettings.hjsv;
            a.appendChild(r);
        })(window, document, 'https://static.hotjar.com/c/hotjar-', '.js?sv=');
    </script>
@endif

{{-- PostHog (Self-host ou Cloud) --}}
@if (!empty($posthogKey) && !empty($posthogHost))
    @php
        // Sanitiza host (sem barra no fim)
        $__phHost = rtrim($posthogHost, '/');
        $__phOpts = $posthogOptions ?? [];
        // Flags/objetos que iremos injetar no init
        $__autocapture      = $__phOpts['autocapture'] ?? true;
        $__capture_pageview = $__phOpts['capture_pageview'] ?? true;
        $__debug            = $__phOpts['debug'] ?? false;
        $__sr               = $__phOpts['session_recording'] ?? ['enabled'=>true,'maskAllInputs'=>true,'captureCanvas'=>false,'sampling'=>null];
        $__identify_enabled = $__phOpts['identify_enabled'] ?? true;
    @endphp

    <script>
        // Snippet oficial (array.js)
        !function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.async=!0,p.src=s.api_host+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:u=e,u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==u._jscall&&(e+="."+u._jscall),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="capture identify alias group set_group add_group remove_group reset isFeatureEnabled onFeatureFlags getFeatureFlag getFeatureFlagPayload opt_out_capturing has_opted_out_capturing opt_in_capturing debug".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);

        // Opções vindas do back (config/services.php -> TrackingServiceProvider)
        window.__PH_CFG__ = {
            api_host: @json($__phHost),
            autocapture: @json($__autocapture),
            capture_pageview: @json($__capture_pageview),
            debug: @json($__debug),
            session_recording: {
                enabled: @json($__sr['enabled'] ?? true),
                maskAllInputs: @json($__sr['maskAllInputs'] ?? true),
                captureCanvas: @json($__sr['captureCanvas'] ?? false),
                // sampling: número entre 0 e 1 (ou null p/ padrão)
                sampling: @json($__sr['sampling'] ?? null)
            }
        };

        // Init
        (function initPosthog(){
            var cfg = window.__PH_CFG__ || {};
            // Remove nulls não suportados diretamente
            if (cfg.session_recording && cfg.session_recording.sampling === null) {
                delete cfg.session_recording.sampling;
            }
            posthog.init(@json($posthogKey), cfg);
        })();
    </script>

    {{-- Identify opcional do usuário autenticado (controlado por env POSTHOG_IDENTIFY_ENABLED) --}}
    @if ($__identify_enabled && Auth::check())
        <script>
            (function(){
                // CUIDADO: personalize quais propriedades pessoais você quer enviar (LGPD).
                // Aqui um exemplo básico (id + email). Adicione/remova conforme sua política.
                const phUserId = @json((string) auth()->id());
                const phProps  = {
                    email: @json((string) auth()->user()->email),
                    name:  @json((string) auth()->user()->name),
                    // Ex.: tenant/empresa se fizer sentido na sua análise:
                    @if(method_exists(auth()->user(), 'empresa') && auth()->user()->empresa)
                    empresa_id: @json((string) optional(auth()->user()->empresa)->id),
                    empresa_nome: @json((string) optional(auth()->user()->empresa)->nome),
                    @endif
                };

                // Só identifica se temos um ID (evita identificar anônimo indevidamente)
                if (phUserId && window.posthog && typeof posthog.identify === 'function') {
                    posthog.identify(phUserId, phProps);
                }
            })();
        </script>
    @endif

    {{-- Pageview extra para SPA/Turbo/Livewire/HTMX (opcional) --}}
    <script>
        (function(){
            // Dispara $pageview em navegações SPA comuns.
            // Habilite livremente; se não houver essas libs, os listeners são inofensivos.
            function capturePageview(){
                try { window.posthog && posthog.capture('$pageview'); } catch(e) {}
            }

            // Turbo (Rails/Turbo)
            document.addEventListener('turbo:load', capturePageview);
            // Livewire v3
            document.addEventListener('livewire:navigated', capturePageview);
            // HTMX
            document.body && document.body.addEventListener && document.body.addEventListener('htmx:afterSettle', capturePageview);
            // Vue Router (se expuser evento custom em sua app)
            document.addEventListener('vue:route-changed', capturePageview);
        })();
    </script>
@endif
