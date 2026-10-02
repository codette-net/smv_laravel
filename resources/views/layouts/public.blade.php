<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        // Inline Blade sections are escaped when stored. Decode that single layer so
        // the final {{ }} output below remains the sole HTML-escaping boundary.
        $sectionValue = static fn (string $value): string => html_entity_decode(
            trim($value),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
        $seoTitle = $sectionValue($__env->yieldContent('title', 'Sales en Marketing Vacatures'));
        $seoDescription = $sectionValue($__env->yieldContent('meta_description', 'Vind actuele sales- en marketingvacatures en werkgevers op Sales en Marketing Vacatures.'));
        $seoCanonical = $sectionValue($__env->yieldContent('canonical', url()->current()));
        $seoRobots = config('app.env') === 'production' ? $sectionValue($__env->yieldContent('robots', 'index, follow')) : 'noindex, nofollow';
        $seoType = $sectionValue($__env->yieldContent('og_type', 'website'));
        $darkHeaderAtTop = trim($__env->yieldContent('header_theme', 'light')) === 'dark';
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:type" content="{{ $seoType }}">
    @stack('structured_data')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-inter text-slate-700 antialiased">
<div class="flex min-h-screen flex-col overflow-hidden">
    <div class="h-px" id="public-nav-sentinel" aria-hidden="true"></div>
    <x-app.header-new :dark-at-top="$darkHeaderAtTop" />

    <main class="grow">
        @yield('content')
    </main>

    <x-app.footer/>
</div>
</body>
</html>
