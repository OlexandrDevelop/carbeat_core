<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $gaId = config('masters.seo.google_analytics_id');
            $seo = is_array($page['props']['seo'] ?? null) ? $page['props']['seo'] : [];
            $title = trim((string) ($seo['title'] ?? ''));
            $description = trim((string) ($seo['description'] ?? ''));
            $canonical = trim((string) ($seo['canonical'] ?? ''));
            $robots = trim((string) ($seo['robots'] ?? ''));
            $ogImage = trim((string) ($seo['ogImage'] ?? ''));
            $structuredData = $seo['structuredData'] ?? ($page['props']['structuredData'] ?? null);
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="language" content="{{ app()->getLocale() }}">
        <meta name="theme-color" content="#0f172a">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
        <link rel="shortcut icon" href="/favicon.svg" type="image/svg+xml" />

        @if ($title !== '')
        <title>{{ $title }}</title>
        <meta property="og:title" content="{{ $title }}" />
        <meta name="twitter:title" content="{{ $title }}" />
        @endif

        @if ($description !== '')
        <meta name="description" content="{{ $description }}" />
        <meta property="og:description" content="{{ $description }}" />
        <meta name="twitter:description" content="{{ $description }}" />
        @endif

        @if ($canonical !== '')
        <link rel="canonical" href="{{ $canonical }}" />
        <meta property="og:url" content="{{ $canonical }}" />
        <meta name="twitter:url" content="{{ $canonical }}" />
        @endif

        @if ($robots !== '')
        <meta name="robots" content="{{ $robots }}" />
        @endif

        <meta property="og:type" content="website" />
        <meta property="og:locale" content="{{ app()->getLocale() === 'uk' ? 'uk_UA' : 'en_US' }}" />
        <meta name="twitter:card" content="summary_large_image" />

        @if ($ogImage !== '')
        <meta property="og:image" content="{{ $ogImage }}" />
        <meta name="twitter:image" content="{{ $ogImage }}" />
        @endif

        @if (config('masters.seo.enable_analytics') && filled($gaId))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaId) }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($gaId));
        </script>
        @endif

        @if ($structuredData)
            <script type="application/ld+json">
                {!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
            </script>
        @endif

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia

        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
    </body>
</html>
