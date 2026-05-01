<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $gaId = config('masters.seo.google_analytics_id');
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="language" content="{{ app()->getLocale() }}">
        <meta name="theme-color" content="#0f172a">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
        <link rel="shortcut icon" href="/favicon.svg" type="image/svg+xml" />

        @if (config('masters.seo.enable_analytics') && filled($gaId))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaId) }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($gaId));
        </script>
        @endif

        @if (isset($page['props']['structuredData']))
            <script type="application/ld+json">
                {!! json_encode($page['props']['structuredData']) !!}
            </script>
        @endif

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia

        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
    </body>
</html>
