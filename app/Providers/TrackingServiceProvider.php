<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class TrackingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // GA e Hotjar
        $googleAnalyticsId = config('services.tracking.google_analytics');
        $hotjarId          = config('services.tracking.hotjar');

        // PostHog
        $posthog           = (array) config('services.tracking.posthog');
        $posthogKey        = $posthog['key']  ?? '';
        $posthogHost       = $posthog['host'] ?? '';

        // Opções PostHog (repasse para a view)
        $posthogOptions = [
            'autocapture'       => (bool) ($posthog['autocapture']       ?? true),
            'capture_pageview'  => (bool) ($posthog['capture_pageview']  ?? true),
            'debug'             => (bool) ($posthog['debug']             ?? false),
            'session_recording' => [
                'enabled'        => (bool) ($posthog['session_recording']['enabled']       ?? true),
                'maskAllInputs'  => (bool) ($posthog['session_recording']['maskAllInputs'] ?? true),
                'captureCanvas'  => (bool) ($posthog['session_recording']['captureCanvas'] ?? false),
                'sampling'       => $posthog['session_recording']['sampling']             ?? null,
            ],
            'identify_enabled'  => (bool) ($posthog['identify_enabled'] ?? true),
        ];

        // Compartilha com TODAS as views
        View::share('googleAnalyticsId', $googleAnalyticsId);
        View::share('hotjarId', $hotjarId);

        View::share('posthogKey', $posthogKey);
        View::share('posthogHost', $posthogHost);
        View::share('posthogOptions', $posthogOptions);
    }

    public function register()
    {
        //
    }
}
