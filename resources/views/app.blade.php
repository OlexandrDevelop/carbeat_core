<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="language" content="{{ app()->getLocale() }}">
        <link rel="canonical" href="{{ url()->current() }}" />
        <link rel="icon" href="/favicon.ico" />

        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'GA_MEASUREMENT_ID');
        </script>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia

        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
    </body>
</html>
