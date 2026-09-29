<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ! str_starts_with($page['component'] ?? '', 'public/') && ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        {{-- The public website is light-only: it is built on the fixed brand
             colours, so a visitor's system dark mode must never repaint it. --}}
        @unless (str_starts_with($page['component'] ?? '', 'public/'))
            <script>
                (function() {
                    const appearance = '{{ $appearance ?? "system" }}';

                    if (appearance === 'system') {
                        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                        if (prefersDark) {
                            document.documentElement.classList.add('dark');
                        }
                    }
                })();
            </script>
        @endunless

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #ffffff;
            }

            html.dark {
                background-color: #0e2135;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @php
            /*
             * Written into the HTML here rather than left to the front end,
             * because the scrapers that build a link preview - WhatsApp,
             * Facebook, LinkedIn - do not run JavaScript. They read what the
             * server sends, so a share card assembled in the browser is a share
             * card they never see.
             */
            $seo = $page['props']['seo'] ?? null;
        @endphp

        <x-inertia::head>
            <title>{{ $seo['title'] ?? config('app.name', 'Laravel') }}</title>

            @if ($seo)
                <meta name="description" content="{{ $seo['description'] }}">
                <meta name="robots" content="{{ $seo['robots'] }}">
                <link rel="canonical" href="{{ $seo['canonical'] }}">

                <meta property="og:type" content="{{ $seo['type'] }}">
                <meta property="og:site_name" content="{{ config('app.name') }}">
                <meta property="og:title" content="{{ $seo['title'] }}">
                <meta property="og:description" content="{{ $seo['description'] }}">
                <meta property="og:url" content="{{ $seo['canonical'] }}">
                <meta property="og:image" content="{{ $seo['image'] }}">
                <meta property="og:image:alt" content="{{ $seo['imageAlt'] }}">

                <meta name="twitter:card" content="summary_large_image">
                <meta name="twitter:title" content="{{ $seo['title'] }}">
                <meta name="twitter:description" content="{{ $seo['description'] }}">
                <meta name="twitter:image" content="{{ $seo['image'] }}">
            @endif
        </x-inertia::head>

        {{-- Structured data for search engines, on the public site only. --}}
        @if (str_starts_with($page['component'] ?? '', 'public/'))
            @include('partials.hotel-schema')
        @endif

    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
