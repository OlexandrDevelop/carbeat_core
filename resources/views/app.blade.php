<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $gaId = config('masters.seo.google_analytics_id');
            $clarityId = config('masters.seo.microsoft_clarity_id');
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

        {{-- JetBrains Mono: used by the master portal (/master/*) for time ranges,
             plate numbers and status badges. Loaded globally (cheap, cached) rather
             than per-page to keep the Vite entry points simple. --}}
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

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

        @if (config('masters.seo.enable_analytics') && filled($clarityId))
        <script type="text/javascript">
            (function(c,l,a,r,i,t,y){
                c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
            })(window, document, "clarity", "script", @json($clarityId));
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
